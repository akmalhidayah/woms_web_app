<?php

namespace Tests\Feature;

use App\Models\EquipmentInspectionForm;
use App\Models\EquipmentInspectionUnit;
use App\Models\EquipmentInspectionUnitLog;
use App\Models\User;
use App\Services\Inspector\EquipmentCiltService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class EquipmentCiltTest extends TestCase
{
    // Real commits on the isolated testing database exercise after-commit cleanup.
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_admin_upload_replace_and_delete_preserve_unit_identity_and_clean_old_files(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();
        $identity = $unit->only(['public_code', 'asset_code', 'equipment_inspection_form_id', 'public_enabled']);
        $url = route('admin.inspection-units.cilt.store', $unit);

        $this->actingAs($admin)->from(route('admin.inspection-forms.index'))->post($url, [
            'cilt_pdf' => $this->pdf('CILT Awal.pdf'), 'qr_form_id' => $unit->equipment_inspection_form_id,
        ])->assertRedirect(route('admin.inspection-forms.index'))->assertSessionHasNoErrors();
        $unit->refresh();
        $oldPath = $unit->cilt_path;
        Storage::disk('local')->assertExists($oldPath);
        $this->assertSame($admin->id, $unit->cilt_uploaded_by);
        $this->assertSame('CILT Awal.pdf', $unit->cilt_original_name);
        $this->assertArrayNotHasKey('cilt_path', $unit->toArray());
        $this->get(route('admin.inspection-units.cilt.preview', $unit))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertStreamedContent($this->binary());

        $this->post($url, ['cilt_pdf' => $this->pdf('Pengganti.pdf')])->assertRedirect()->assertSessionHasNoErrors();
        $unit->refresh();
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($unit->cilt_path);
        $this->assertNotSame($oldPath, $unit->cilt_path);
        $this->assertSame($identity, $unit->only(array_keys($identity)));

        $path = $unit->cilt_path;
        $this->delete(route('admin.inspection-units.cilt.destroy', $unit))->assertRedirect();
        Storage::disk('local')->assertMissing($path);
        $this->assertNull($unit->fresh()->cilt_path);
        $this->assertSame($identity, $unit->fresh()->only(array_keys($identity)));
        $this->delete(route('admin.inspection-units.cilt.destroy', $unit))->assertRedirect();
        $this->assertSame(3, $unit->logs()->whereIn('event', ['cilt_uploaded', 'cilt_replaced', 'cilt_deleted'])->count());
    }

    public function test_only_published_non_retired_unit_can_expose_its_own_cilt(): void
    {
        $unit = $this->unit();
        app(EquipmentCiltService::class)->replace($this->admin(), $unit, $this->pdf());
        $url = route('public.equipment-history.cilt', $unit->public_code);
        $this->get($url)->assertNotFound();
        $unit->update(['public_enabled' => true]);
        $this->get($url)->assertOk()->assertStreamedContent($this->binary())
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Content-Disposition', 'inline; filename="Standar-CILT.pdf"');
        $this->get(route('public.equipment-history.show', $unit->public_code))->assertOk()
            ->assertSee('Buka Dokumen CILT')->assertSee('CILT.pdf')
            ->assertDontSee($unit->fresh()->cilt_path, false);
        $other = $this->unit();
        $other->update(['public_enabled' => true]);
        $this->get(route('public.equipment-history.cilt', $other->public_code))->assertNotFound();
        $this->get('/equipment/q/'.$unit->id.'/cilt')->assertNotFound();
        $this->get('/equipment/q/'.str_repeat('z', 32).'/cilt')->assertNotFound();
        $unit->update(['lifecycle_status' => EquipmentInspectionUnit::STATUS_RETIRED]);
        $this->get($url)->assertNotFound();
        $unit->update(['lifecycle_status' => EquipmentInspectionUnit::STATUS_ACTIVE, 'public_enabled' => false]);
        $this->get($url)->assertNotFound();
    }

    public function test_guest_and_unauthorized_roles_cannot_manage_or_preview_cilt(): void
    {
        $unit = $this->unit();
        $this->post(route('admin.inspection-units.cilt.store', $unit), ['cilt_pdf' => $this->pdf()])->assertRedirect(route('login'));
        foreach ([User::ROLE_INSPECTOR, User::ROLE_APPROVER, User::ROLE_USER, User::ROLE_PKM] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->post(route('admin.inspection-units.cilt.store', $unit), ['cilt_pdf' => $this->pdf()])->assertForbidden();
            $this->get(route('admin.inspection-units.cilt.preview', $unit))->assertForbidden();
            $this->delete(route('admin.inspection-units.cilt.destroy', $unit))->assertForbidden();
        }
        $this->assertNull($unit->fresh()->cilt_path);
    }

    public function test_admin_requires_inspection_menu_permission(): void
    {
        \App\Models\AdminRoleMenuAccess::query()->where('admin_role', User::ADMIN_ROLE_ADMIN)
            ->where('menu_key', \App\Support\AdminMenuRegistry::MENU_INSPEKSI)->delete();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_ADMIN]);
        $unit = $this->unit();
        $this->actingAs($admin)->post(route('admin.inspection-units.cilt.store', $unit), ['cilt_pdf' => $this->pdf()])->assertForbidden();
        $this->get(route('admin.inspection-units.cilt.preview', $unit))->assertForbidden();
        $this->delete(route('admin.inspection-units.cilt.destroy', $unit))->assertForbidden();
    }

    public function test_invalid_upload_never_replaces_existing_cilt(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();
        app(EquipmentCiltService::class)->replace($admin, $unit, $this->pdf());
        $before = $unit->fresh()->getAttributes();
        $this->actingAs($admin);
        foreach ([
            UploadedFile::fake()->createWithContent('fake.pdf', '<html>not a PDF</html>'),
            UploadedFile::fake()->createWithContent('document.php', $this->binary()),
            UploadedFile::fake()->createWithContent('document.html', $this->binary()),
            UploadedFile::fake()->createWithContent('large.pdf', $this->binary().str_repeat('x', 10 * 1024 * 1024)),
        ] as $file) {
            $this->post(route('admin.inspection-units.cilt.store', $unit), ['cilt_pdf' => $file])
                ->assertSessionHasErrors('cilt_pdf');
        }
        $this->assertSame($before, $unit->fresh()->getAttributes());
        $this->assertSame([$before['cilt_path']], Storage::disk('local')->allFiles('equipment-cilt'));
    }

    public function test_failed_database_update_keeps_previous_pdf_and_removes_staged_file(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();
        $service = app(EquipmentCiltService::class);
        $service->replace($admin, $unit, $this->pdf());
        $before = $unit->fresh()->getAttributes();
        EquipmentInspectionUnitLog::creating(function (): void {
            throw new RuntimeException('Simulated audit write failure');
        });
        try {
            $service->replace($admin, $unit, $this->pdf('Replacement.pdf'));
            $this->fail('The simulated database failure was not propagated.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated audit write failure', $exception->getMessage());
        } finally {
            EquipmentInspectionUnitLog::flushEventListeners();
        }
        $this->assertSame($before, $unit->fresh()->getAttributes());
        $this->assertSame([$before['cilt_path']], Storage::disk('local')->allFiles('equipment-cilt'));
    }

    public function test_missing_or_foreign_file_path_is_not_exposed(): void
    {
        Log::spy();
        $unit = $this->unit();
        $unit->update(['public_enabled' => true]);
        $this->get(route('public.equipment-history.show', $unit->public_code))->assertOk()
            ->assertSee('Dokumen Standar CILT belum tersedia untuk peralatan ini.');
        app(EquipmentCiltService::class)->replace($this->admin(), $unit, $this->pdf());
        $path = $unit->fresh()->cilt_path;
        Storage::disk('local')->delete($path);
        $this->get(route('public.equipment-history.show', $unit->public_code))->assertOk()
            ->assertSee('Dokumen Standar CILT belum tersedia untuk peralatan ini.')->assertDontSee('Buka Dokumen CILT');
        $this->get(route('public.equipment-history.cilt', $unit->public_code))->assertNotFound()->assertDontSee($path, false);
        $other = $this->unit();
        app(EquipmentCiltService::class)->replace($this->admin(), $other, $this->pdf());
        $unit->forceFill(['cilt_path' => $other->fresh()->cilt_path])->save();
        $this->get(route('public.equipment-history.cilt', $unit->public_code))->assertNotFound();
        $unit->forceFill(['cilt_path' => '../../secrets.pdf'])->save();
        $this->get(route('public.equipment-history.cilt', $unit->public_code))->assertNotFound();
        Log::shouldHaveReceived('warning')->with('Equipment CILT document unavailable.', ['unit_id' => $unit->id]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);
    }

    private function unit(): EquipmentInspectionUnit
    {
        return EquipmentInspectionUnit::query()->create([
            'equipment_inspection_form_id' => EquipmentInspectionForm::query()->firstOrFail()->id,
            'name' => 'Unit CILT', 'asset_code' => 'CILT-'.Str::random(8),
            'public_code' => Str::lower(Str::random(32)),
            'lifecycle_status' => EquipmentInspectionUnit::STATUS_ACTIVE, 'public_enabled' => false,
        ]);
    }

    private function pdf(string $name = 'CILT.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $this->binary());
    }

    private function binary(): string
    {
        return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n";
    }
}
