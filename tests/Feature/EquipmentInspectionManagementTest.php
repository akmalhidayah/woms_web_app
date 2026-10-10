<?php

namespace Tests\Feature;

use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionApproval;
use App\Models\EquipmentInspectionSignature;
use App\Models\User;
use App\Services\Inspector\EquipmentInspectionService;
use App\Support\AdminActionCenter;
use App\Support\Inspector\EquipmentFormCatalog;
use App\Support\Inspector\EquipmentInspectionIndexTabs;
use App\Support\Inspector\EquipmentInspectionSnapshot;
use App\Support\RecentApprovalSignatureResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class EquipmentInspectionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabs_and_sidebar_use_disjoint_status_groups(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);
        $owner = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $other = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $draft = $this->inspection($owner);
        $pending = $this->inspection($owner, EquipmentInspection::STATUS_MANAGER);
        $approved = $this->inspection($owner, EquipmentInspection::STATUS_APPROVED);
        $this->inspection($other);

        $this->assertSame(['new' => 2, 'approval' => 1, 'history' => 1], EquipmentInspectionIndexTabs::counts($admin));
        $this->assertSame(['new' => 1, 'approval' => 1, 'history' => 1], EquipmentInspectionIndexTabs::counts($owner));
        $this->assertSame(2, app(AdminActionCenter::class)->sidebarCounts($admin)['inspeksi_baru']);
        $this->assertFalse(EquipmentInspectionIndexTabs::apply(EquipmentInspection::query(), 'new')->whereKey($pending->id)->exists());
        $this->assertFalse(EquipmentInspectionIndexTabs::apply(EquipmentInspection::query(), 'history')->whereKey($draft->id)->exists());
        $this->assertTrue(EquipmentInspectionIndexTabs::apply(EquipmentInspection::query(), 'completed')->whereKey($approved->id)->exists());
    }

    public function test_owner_deletion_cancels_approval_but_preserves_signed_audit_and_media(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $inspection = $this->inspection($owner, EquipmentInspection::STATUS_MANAGER);
        $signature = $inspection->signatures()->firstOrFail();
        $approval = $inspection->approvals()->firstOrFail();
        Storage::disk('local')->put($signature->signature_path, 'existing signature bytes');

        $this->actingAs($owner)->delete(route('inspector.inspections.destroy', $inspection), ['lock_version' => $inspection->lock_version])
            ->assertRedirect(route('inspector.inspections.index'));

        $this->assertSoftDeleted('equipment_inspections', ['id' => $inspection->id]);
        $this->assertSame(EquipmentInspectionApproval::CANCELLED, $approval->fresh()->status);
        $this->assertNull($approval->fresh()->token_hash);
        $this->assertFalse(EquipmentInspectionApproval::query()->whereKey($approval->id)->active(true)->exists());
        $this->assertDatabaseHas('equipment_inspection_signatures', ['id' => $signature->id, 'content_hash' => $signature->content_hash]);
        $this->assertDatabaseHas('equipment_inspection_logs', ['equipment_inspection_id' => $inspection->id, 'event' => 'inspection_deleted', 'actor_user_id' => $owner->id]);
        $this->assertSame(4, $inspection->answers()->count());
        Storage::disk('local')->assertExists($signature->signature_path);
        $this->get(route('inspector.inspections.show', $inspection->public_id))->assertNotFound();
    }

    public function test_deletion_rejects_other_owners_final_reports_and_stale_versions(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $other = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $draft = $this->inspection($owner);
        $approved = $this->inspection($owner, EquipmentInspection::STATUS_APPROVED);

        $this->actingAs($other)->delete(route('inspector.inspections.destroy', $draft), ['lock_version' => $draft->lock_version])->assertForbidden();
        $this->actingAs($owner)->delete(route('inspector.inspections.destroy', $approved), ['lock_version' => $approved->lock_version])->assertForbidden();
        $this->delete(route('inspector.inspections.destroy', $draft), ['lock_version' => $draft->lock_version + 1])->assertSessionHasErrors('inspection');
        $this->assertNull($draft->fresh()->deleted_at);
        $this->assertNull($approved->fresh()->deleted_at);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);
        $this->actingAs($admin)->delete(route('admin.inspections.destroy', $approved), ['lock_version' => $approved->lock_version])->assertForbidden();
        $this->delete(route('admin.inspections.destroy', $draft), ['lock_version' => $draft->lock_version])->assertRedirect();
        $this->assertSoftDeleted('equipment_inspections', ['id' => $draft->id]);
    }

    public function test_progress_modal_exposes_only_current_steps_without_signer_tokens(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $inspection = $this->inspection($owner, EquipmentInspection::STATUS_MANAGER);
        $approval = $inspection->approvals()->firstOrFail();
        $this->actingAs($owner)->getJson(route('admin.inspections.approval-progress', $inspection))->assertForbidden();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);
        $this->actingAs($admin)->getJson(route('admin.inspections.approval-progress', $inspection))
            ->assertOk()->assertJsonPath('signed_count', 1)->assertJsonPath('total', 2)->assertJsonPath('percent', 50)
            ->assertJsonCount(2, 'steps')->assertJsonPath('steps.1.state', 'pending')
            ->assertJsonPath('steps.1.resend_url', route('admin.inspections.resend', [$inspection, $approval]))
            ->assertDontSee($approval->token_encrypted)->assertDontSee($approval->token_hash);
    }

    public function test_inspector_can_reuse_own_signature_on_new_and_draft_forms(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $this->travelTo(now()->subMinute());
        $previous = $this->inspection($owner, EquipmentInspection::STATUS_MANAGER);
        $this->travelBack();
        $signature = $previous->signatures()->firstOrFail();
        Storage::disk('local')->put($signature->signature_path, 'existing signature bytes');

        $other = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $otherSignature = $this->inspection($other, EquipmentInspection::STATUS_MANAGER)->signatures()->firstOrFail();
        $otherSignature->update(['signature_sha256' => hash('sha256', 'other user signature bytes')]);
        Storage::disk('local')->put($otherSignature->signature_path, 'other user signature bytes');

        $expected = 'data:image/png;base64,'.base64_encode('existing signature bytes');
        $draft = $this->inspection($owner);
        foreach ([route('inspector.equipment-forms.show', 'mesin-lipat'), route('inspector.inspections.show', $draft)] as $url) {
            $this->actingAs($owner)->get($url)->assertOk()
                ->assertViewHas('recentSignatureDataUrl', $expected)
                ->assertSee('Gunakan TTD Terakhir')
                ->assertDontSee(base64_encode('other user signature bytes'));
        }

        $this->get(route('inspector.inspections.show', $previous))->assertOk()
            ->assertViewHas('recentSignatureDataUrl', fn ($value): bool => $value === null)
            ->assertDontSee('Gunakan TTD Terakhir');
    }

    public function test_manager_approval_reuses_inspection_signature_and_shows_two_steps(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $previous = $this->inspection($owner, EquipmentInspection::STATUS_APPROVED);
        $signature = $previous->signatures()->where('role_key', EquipmentInspectionSignature::ROLE_MANAGER)->firstOrFail();
        $manager = User::query()->findOrFail($signature->signer_user_id);
        Storage::disk('local')->put($signature->signature_path, 'existing signature bytes');

        $pending = $this->inspection($owner, EquipmentInspection::STATUS_MANAGER);
        $approval = $pending->approvals()->firstOrFail();
        $approval->update(['signer_user_id' => $manager->id, 'signer_name' => $manager->name]);

        $this->actingAs($manager)->get(route('approval.equipment-inspection.show', $approval->token_encrypted))
            ->assertOk()
            ->assertViewHas('recentSignatureDataUrl', 'data:image/png;base64,'.base64_encode('existing signature bytes'))
            ->assertSee('Gunakan TTD Terakhir')
            ->assertSee('Tahap 2 dari 2')
            ->assertDontSee('Tahap 2 dari 3');

        $other = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $this->actingAs($other)->get(route('approval.equipment-inspection.show', $approval->token_encrypted))->assertNotFound();
    }

    public function test_unreadable_or_changed_inspection_signature_falls_back_without_using_public_file(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $owner = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $this->travelTo(now()->subMinute());
        $older = $this->inspection($owner, EquipmentInspection::STATUS_MANAGER)->signatures()->firstOrFail();
        $this->travelBack();
        Storage::disk('local')->put($older->signature_path, 'existing signature bytes');
        $latest = $this->inspection($owner, EquipmentInspection::STATUS_MANAGER)->signatures()->firstOrFail();
        Storage::disk('local')->put($latest->signature_path, 'changed signature bytes');
        Storage::disk('public')->put($latest->signature_path, 'existing signature bytes');
        $resolver = app(RecentApprovalSignatureResolver::class);

        $this->assertSame('data:image/png;base64,'.base64_encode('existing signature bytes'), $resolver->latestFullSignatureForUser($owner));
        $this->assertNull($resolver->latestInitialForHppManager($owner));

        Storage::disk('local')->delete($older->signature_path);
        $this->assertNull($resolver->latestFullSignatureForUser($owner));
    }

    public function test_inspector_without_signature_history_does_not_get_reuse_button(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_INSPECTOR]);

        $this->actingAs($owner)->get(route('inspector.equipment-forms.show', 'mesin-lipat'))->assertOk()
            ->assertViewHas('recentSignatureDataUrl', fn ($value): bool => $value === null)
            ->assertDontSee('Gunakan TTD Terakhir');
    }

    private function inspection(User $owner, string $status = EquipmentInspection::STATUS_DRAFT): EquipmentInspection
    {
        $form = EquipmentFormCatalog::find('mesin-lipat');
        $answers = collect($form['groups'])->flatMap(fn (array $group) => $group['items'])
            ->mapWithKeys(fn (array $item) => [$item['id'] => ['rating' => 'A', 'remark' => '']])->all();
        $inspection = app(EquipmentInspectionService::class)->save($owner, null, 'mesin-lipat', [
            'inspection_date' => now()->toDateString(), 'answers' => $answers,
        ], []);
        if ($status === EquipmentInspection::STATUS_DRAFT) {
            return $inspection;
        }

        $inspection->update(['status' => $status, 'document_no' => 'TEST-INSP-'.$inspection->id, 'signed_at' => now()]);
        $payload = EquipmentInspectionSnapshot::payload($inspection);
        $signature = ['document_version' => 1, 'role_key' => EquipmentInspectionSignature::ROLE_INSPECTOR, 'step' => 1,
            'signer_user_id' => $owner->id, 'signer_name' => $owner->name, 'signer_position' => 'Inspektor',
            'signature_path' => 'equipment-inspections/'.$inspection->public_id.'/signatures/inspector.png',
            'signature_sha256' => hash('sha256', 'existing signature bytes'), 'signed_at' => now(),
            'content_hash' => EquipmentInspectionSnapshot::hash($payload), 'signed_payload' => $payload];
        $inspection->signatures()->create($signature);
        $manager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $token = Str::random(64);
        $inspection->approvals()->create(['document_version' => 1, 'step_order' => 2, 'role_key' => EquipmentInspectionSignature::ROLE_MANAGER,
            'signer_user_id' => $manager->id, 'signer_name' => $manager->name, 'signer_position' => 'Manager Workshop',
            'status' => $status === EquipmentInspection::STATUS_APPROVED ? EquipmentInspectionApproval::APPROVED : EquipmentInspectionApproval::PENDING,
            'token_hash' => hash('sha256', $token), 'token_encrypted' => $status === EquipmentInspection::STATUS_APPROVED ? null : $token,
            'token_expires_at' => now()->addDays(7), 'activated_at' => now(),
            'decided_at' => $status === EquipmentInspection::STATUS_APPROVED ? now() : null]);
        if ($status === EquipmentInspection::STATUS_APPROVED) {
            $inspection->signatures()->create(array_replace($signature, ['role_key' => EquipmentInspectionSignature::ROLE_MANAGER, 'step' => 2,
                'signer_user_id' => $manager->id, 'signer_name' => $manager->name, 'signer_position' => 'Manager Workshop',
                'signature_path' => 'equipment-inspections/'.$inspection->public_id.'/signatures/manager.png']));
        }

        return $inspection;
    }
}
