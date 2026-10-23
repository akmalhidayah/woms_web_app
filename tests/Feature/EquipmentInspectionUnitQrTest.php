<?php

namespace Tests\Feature;

use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionForm;
use App\Models\EquipmentInspectionSignature;
use App\Models\EquipmentInspectionUnit;
use App\Models\EquipmentInspectionUnitLink;
use App\Models\User;
use App\Services\Inspector\EquipmentInspectionService;
use App\Services\Inspector\EquipmentInspectionUnitService;
use App\Support\Inspector\EquipmentFormCatalog;
use App\Support\Inspector\EquipmentInspectionSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EquipmentInspectionUnitQrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.url' => 'https://woms.test',
            'equipment-inspection.public_base_url' => 'https://woms.test',
        ]);
    }

    public function test_admin_manages_unit_and_non_admin_is_rejected(): void
    {
        $admin = $this->admin();
        $inspector = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $form = EquipmentInspectionForm::query()->where('slug', 'mesin-lipat')->firstOrFail();
        $payload = [
            'equipment_inspection_form_id' => $form->id,
            'asset_code' => 'MB-001',
            'name' => 'Mesin Lipat Utama',
            'location' => 'Workshop Mesin',
            'description' => 'Unit fisik pertama.',
            'lifecycle_status' => EquipmentInspectionUnit::STATUS_ACTIVE,
            'public_enabled' => '1',
            'sort_order' => 1,
            'remove_image' => '0',
        ];

        $this->actingAs($inspector)->post(route('admin.inspection-units.store'), $payload)->assertForbidden();
        $this->actingAs($admin)->post(route('admin.inspection-units.store'), $payload)->assertRedirect();
        $unit = EquipmentInspectionUnit::query()->where('asset_code', 'MB-001')->firstOrFail();
        $this->assertSame(32, strlen($unit->public_code));
        $this->assertTrue($unit->public_enabled);
        $this->assertDatabaseHas('equipment_inspection_unit_logs', ['equipment_inspection_unit_id' => $unit->id, 'event' => 'created']);

        $originalCode = $unit->public_code;
        $payload['name'] = 'Mesin Lipat Renamed';
        $this->put(route('admin.inspection-units.update', $unit), $payload)->assertRedirect();
        $this->assertSame($originalCode, $unit->fresh()->public_code);

        $payload['asset_code'] = 'MB-001';
        $this->post(route('admin.inspection-units.store'), $payload)->assertSessionHasErrors('asset_code');
        $this->get(route('admin.inspection-units.show', $unit))
            ->assertOk()
            ->assertDontSee('Hubungkan Riwayat Inspeksi Lama');
        $this->patch(route('admin.inspection-units.retire', $unit))->assertRedirect();
        $unit->refresh();
        $this->assertSame(EquipmentInspectionUnit::STATUS_RETIRED, $unit->lifecycle_status);
        $this->assertFalse($unit->public_enabled);
        $this->delete(route('admin.inspection-units.show', $unit))->assertStatus(405);
        $this->assertDatabaseHas('equipment_inspection_units', ['id' => $unit->id]);
    }

    public function test_admin_configures_one_permanent_qr_from_master_form_without_duplicate_unit(): void
    {
        $admin = $this->admin();
        $nonAdmin = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $form = EquipmentInspectionForm::query()->where('slug', 'mesin-lipat')->firstOrFail();

        $this->actingAs($nonAdmin)->put(route('admin.inspection-forms.qr.save', $form), [
            'asset_code' => 'RC 05',
            'qr_form_id' => $form->id,
        ])->assertForbidden();

        $this->actingAs($admin)->get(route('admin.inspection-forms.index'))
            ->assertOk()
            ->assertSee('Atur QR Code')
            ->assertDontSee('Hubungkan Histori Lama')
            ->assertDontSee('Master Unit Peralatan');
        $this->put(route('admin.inspection-forms.qr.save', $form), [
            'asset_code' => 'RC 05',
            'location' => 'Workshop Mesin',
            'qr_form_id' => $form->id,
        ])->assertRedirect();

        $unit = EquipmentInspectionUnit::query()->where('equipment_inspection_form_id', $form->id)->sole();
        $originalPublicCode = $unit->public_code;
        $this->assertSame('RC 05', $unit->asset_code);
        $this->assertSame('Workshop Mesin', $unit->location);
        $this->assertSame($form->name, $unit->name);
        $this->assertSame(EquipmentInspectionUnit::STATUS_ACTIVE, $unit->lifecycle_status);
        $this->assertFalse($unit->public_enabled);
        $this->assertSame(32, strlen($unit->public_code));

        $this->get(route('admin.inspection-forms.index'))
            ->assertOk()
            ->assertSee('RC 05');
        $this->get(route('admin.inspection-units.label', $unit))
            ->assertOk()
            ->assertSee('assets/branding/logos/logo-sig.png', false)
            ->assertSee('assets/branding/logos/logo-st2.png', false);

        $this->put(route('admin.inspection-forms.qr.save', $form), [
            'asset_code' => 'RC 05-A',
            'location' => 'Area Fabrikasi',
            'qr_form_id' => $form->id,
        ])->assertRedirect();

        $this->assertSame(1, EquipmentInspectionUnit::query()->where('equipment_inspection_form_id', $form->id)->count());
        $this->assertSame($originalPublicCode, $unit->fresh()->public_code);
        $this->assertSame('RC 05-A', $unit->fresh()->asset_code);
        $this->assertSame('Area Fabrikasi', $unit->fresh()->location);
        $this->assertDatabaseHas('equipment_inspection_unit_logs', [
            'equipment_inspection_unit_id' => $unit->id,
            'event' => 'updated_from_master_form',
        ]);
    }

    public function test_simplified_qr_flow_stops_when_form_has_multiple_legacy_units(): void
    {
        $admin = $this->admin();
        $form = EquipmentInspectionForm::query()->where('slug', 'mesin-lipat')->firstOrFail();
        $this->unit($form, 'LEGACY-01');
        $this->unit($form, 'LEGACY-02');

        $this->actingAs($admin)->put(route('admin.inspection-forms.qr.save', $form), [
            'asset_code' => 'NEW-CODE',
            'qr_form_id' => $form->id,
        ])->assertSessionHasErrors('asset_code');

        $this->assertSame(2, EquipmentInspectionUnit::query()->where('equipment_inspection_form_id', $form->id)->count());
    }

    public function test_inspector_unit_selection_is_validated_and_locked_after_number_issued(): void
    {
        config(['equipment-inspection.require_unit_for_new_inspections' => true]);
        $inspector = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $folding = EquipmentInspectionForm::query()->where('slug', 'mesin-lipat')->firstOrFail();
        $cutting = EquipmentInspectionForm::query()->where('slug', 'mesin-potong')->firstOrFail();
        $rolling = EquipmentInspectionForm::query()->where('slug', 'mesin-roll')->firstOrFail();
        $unit = $this->unit($folding, 'MB-001');
        $other = $this->unit($cutting, 'MP-001');
        $inactive = $this->unit($folding, 'MB-002', EquipmentInspectionUnit::STATUS_INACTIVE);
        $retired = $this->unit($folding, 'MB-RETIRED', EquipmentInspectionUnit::STATUS_RETIRED);
        $answers = $this->answers('mesin-lipat');

        $this->actingAs($inspector)->post(route('inspector.inspections.store', $rolling->slug), [
            'inspection_date' => now()->toDateString(),
            'answers' => $this->answers($rolling->slug),
        ])->assertSessionHasErrors('equipment_inspection_unit_id');
        $this->post(route('inspector.inspections.store', 'mesin-lipat'), [
            'inspection_date' => now()->toDateString(),
            'answers' => $answers,
        ])->assertRedirect();
        $inspection = EquipmentInspection::query()->latest('id')->firstOrFail();
        $this->assertDatabaseHas('equipment_inspection_unit_links', [
            'equipment_inspection_id' => $inspection->id,
            'equipment_inspection_unit_id' => $unit->id,
            'link_source' => EquipmentInspectionUnitLink::SOURCE_DIRECT,
            'unit_code_snapshot' => 'MB-001',
        ]);
        $this->get(route('inspector.inspections.show', $inspection))
            ->assertOk()
            ->assertSee('name="equipment_inspection_unit_id" value="'.$unit->id.'"', false)
            ->assertDontSee('Unit Peralatan')
            ->assertDontSee('Terhubung otomatis ke QR peralatan.')
            ->assertDontSee('Ringkasan pemeriksaan')
            ->assertDontSee('Belum diperiksa');
        $this->post(route('inspector.inspections.store', 'mesin-lipat'), [
            'inspection_date' => now()->toDateString(),
            'equipment_inspection_unit_id' => $other->id,
            'answers' => $answers,
        ])->assertSessionHasErrors('equipment_inspection_unit_id');
        $this->post(route('inspector.inspections.store', 'mesin-lipat'), [
            'inspection_date' => now()->toDateString(),
            'equipment_inspection_unit_id' => $inactive->id,
            'answers' => $answers,
        ])->assertSessionHasErrors('equipment_inspection_unit_id');
        $this->post(route('inspector.inspections.store', 'mesin-lipat'), [
            'inspection_date' => now()->toDateString(),
            'equipment_inspection_unit_id' => $retired->id,
            'answers' => $answers,
        ])->assertSessionHasErrors('equipment_inspection_unit_id');

        $replacement = $this->unit($folding, 'MB-003');
        $this->post(route('inspector.inspections.store', 'mesin-lipat'), [
            'inspection_date' => now()->toDateString(),
            'answers' => $answers,
        ])->assertSessionHasErrors('equipment_inspection_unit_id');

        $inspection->update(['document_no' => '001/INSP/TEST']);
        $this->expectException(ValidationException::class);
        app(EquipmentInspectionService::class)->save($inspector, $inspection, null, [
            'inspection_date' => now()->toDateString(),
            'equipment_inspection_unit_id' => $replacement->id,
            'lock_version' => $inspection->lock_version,
            'answers' => $answers,
        ], []);
    }

    public function test_public_history_uses_approved_signed_snapshot_and_excludes_private_metadata(): void
    {
        Storage::fake('local');
        $inspector = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $manager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $form = EquipmentInspectionForm::query()->where('slug', 'mesin-lipat')->firstOrFail();
        $unit = $this->unit($form, 'MB-PUBLIC');
        $unit->update(['name' => 'Unit <script>alert(1)</script>', 'public_enabled' => true]);

        $approvedAnswers = $this->answers('mesin-lipat', 'B', 'Bearing perlu pelumasan ulang');
        $normalItemKey = array_keys($approvedAnswers)[1];
        $approvedAnswers[$normalItemKey]['remark'] = 'Kondisi normal setelah pemeriksaan';
        $approved = app(EquipmentInspectionService::class)->save($inspector, null, 'mesin-lipat', [
            'inspection_date' => now()->subDay()->toDateString(),
            'equipment_inspection_unit_id' => $unit->id,
            'answers' => $approvedAnswers,
        ], []);
        $this->approveForTest($approved, $inspector, $manager, '001/INSP/PUBLIC');
        $pdfBinary = '%PDF-1.4 approved inspection';
        $pdfPath = 'equipment-inspections/'.$approved->public_id.'/final/test.pdf';
        Storage::disk('local')->put($pdfPath, $pdfBinary);
        $approved->forceFill([
            'final_pdf_path' => $pdfPath,
            'final_pdf_sha256' => hash('sha256', $pdfBinary),
            'archived_at' => now(),
        ])->save();
        $this->actingAs($inspector)->get(route('inspector.inspections.show', $approved))
            ->assertOk()
            ->assertSee('MB-PUBLIC')
            ->assertSee('Identitas unit terkunci karena dokumen pernah ditandatangani.');

        $draft = app(EquipmentInspectionService::class)->save($inspector, null, 'mesin-lipat', [
            'inspection_date' => now()->toDateString(),
            'equipment_inspection_unit_id' => $unit->id,
            'answers' => $this->answers('mesin-lipat'),
        ], []);
        $draft->update(['document_no' => 'DRAFT-SECRET']);

        $otherForm = EquipmentInspectionForm::query()->where('slug', 'mesin-potong')->firstOrFail();
        $otherUnit = $this->unit($otherForm, 'MB-OTHER-PUBLIC');
        $otherUnit->update(['public_enabled' => true]);
        $otherInspection = app(EquipmentInspectionService::class)->save($inspector, null, $otherForm->slug, [
            'inspection_date' => now()->subDays(2)->toDateString(),
            'equipment_inspection_unit_id' => $otherUnit->id,
            'answers' => $this->answers($otherForm->slug),
        ], []);
        $this->approveForTest($otherInspection, $inspector, $manager, 'OTHER-UNIT-SECRET');

        $response = $this->get(route('public.equipment-history.show', $unit->public_code));
        $response->assertOk()
            ->assertSee('MB-PUBLIC')
            ->assertSee('001/INSP/PUBLIC')
            ->assertSee('Kurang Normal')
            ->assertSee('Remarks:')
            ->assertSee('Bearing perlu pelumasan ulang')
            ->assertSee('Kondisi normal setelah pemeriksaan')
            ->assertSee(route('public.equipment-history.pdf', [$unit->public_code, $approved->public_id]), false)
            ->assertDontSee('DRAFT-SECRET')
            ->assertDontSee('OTHER-UNIT-SECRET')
            ->assertDontSee('signature_path')
            ->assertDontSee('token_hash')
            ->assertSee($form->name)
            ->assertDontSee('Unit &lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

        $this->get(route('public.equipment-history.pdf', [$unit->public_code, $approved->public_id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertContent($pdfBinary);
        $this->get(route('public.equipment-history.pdf', [$unit->public_code, $draft->public_id]))
            ->assertNotFound();
        $this->get(route('public.equipment-history.pdf', [$unit->public_code, $otherInspection->public_id]))
            ->assertNotFound();

        $this->post(route('public.equipment-history.show', $unit->public_code))->assertStatus(405);
        $this->get('/equipment/q/'.str_repeat('z', 32))->assertNotFound();

        $approved->delete();
        $this->get(route('public.equipment-history.show', $unit->public_code))
            ->assertOk()
            ->assertDontSee('001/INSP/PUBLIC');

        $unit->update(['public_enabled' => false]);
        $this->get(route('public.equipment-history.show', $unit->public_code))->assertNotFound();
    }

    public function test_public_history_rejects_direct_link_when_signed_unit_identity_is_missing(): void
    {
        $inspector = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $manager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $form = EquipmentInspectionForm::query()->where('slug', 'mesin-lipat')->firstOrFail();
        $unit = $this->unit($form, 'MB-INTEGRITY');
        $unit->update(['public_enabled' => true]);
        $inspection = app(EquipmentInspectionService::class)->save($inspector, null, 'mesin-lipat', [
            'inspection_date' => now()->toDateString(),
            'equipment_inspection_unit_id' => $unit->id,
            'answers' => $this->answers('mesin-lipat'),
        ], []);
        $this->approveForTest($inspection, $inspector, $manager, 'INTEGRITY-001');

        $payload = $inspection->signatures()->firstOrFail()->signed_payload;
        unset($payload['unit']);
        $hash = EquipmentInspectionSnapshot::hash($payload);
        foreach ($inspection->signatures()->get() as $signature) {
            $signature->forceFill(['signed_payload' => $payload, 'content_hash' => $hash])->save();
        }

        $this->get(route('public.equipment-history.show', $unit->public_code))
            ->assertOk()
            ->assertDontSee('INTEGRITY-001')
            ->assertSee('Belum ada riwayat pemeriksaan yang disetujui.');
    }

    public function test_history_paginates_only_valid_snapshots_and_latest_stays_available_on_page_two(): void
    {
        config(['equipment-inspection.public_history_per_page' => 1]);
        $inspector = User::factory()->create(['role' => User::ROLE_INSPECTOR, 'name' => 'Inspektor Snapshot']);
        $manager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $form = EquipmentInspectionForm::query()->where('slug', 'mesin-lipat')->firstOrFail();
        $unit = $this->unit($form, 'MB-PAGES');
        $unit->update(['public_enabled' => true]);
        $documents = [];
        foreach (['OLDER', 'LATEST', 'INVALID', 'DELETED'] as $index => $number) {
            $inspection = app(EquipmentInspectionService::class)->save($inspector, null, $form->slug, [
                'inspection_date' => now()->subDays(5 - $index)->toDateString(),
                'equipment_inspection_unit_id' => $unit->id,
                'answers' => $this->answers($form->slug, 'B', $number.' remark'),
            ], []);
            $inspection->answers()->update(['group_label' => 'Mekanik Snapshot']);
            $this->approveForTest($inspection, $inspector, $manager, $number);
            $documents[$number] = $inspection;
        }
        $documents['INVALID']->signatures()->where('role_key', EquipmentInspectionSignature::ROLE_MANAGER)
            ->update(['content_hash' => str_repeat('0', 64)]);
        $documents['DELETED']->delete();
        $signedBefore = $documents['LATEST']->signatures()->get()->map->getAttributes()->all();
        $inspector->update(['name' => 'New profile name']);
        app(\App\Services\Inspector\EquipmentCiltService::class)->replace(
            $this->admin(), $unit, \Illuminate\Http\UploadedFile::fake()->createWithContent('CILT.pdf', '%PDF-1.4 reference'),
        );
        $this->assertSame($signedBefore, $documents['LATEST']->signatures()->get()->map->getAttributes()->all());

        $url = route('public.equipment-history.show', $unit->public_code);
        $pageOne = $this->get($url)->assertOk()->assertDontSee('INVALID remark')->assertDontSee('DELETED remark')
            ->assertSee('Inspektor Snapshot')->assertDontSee('New profile name');
        $this->assertSame('LATEST', $pageOne->viewData('latest')['document_no']);
        $this->assertSame(['LATEST'], $pageOne->viewData('inspections')->getCollection()->pluck('document_no')->all());
        $this->assertTrue($pageOne->viewData('inspections')->hasMorePages());
        $pageTwo = $this->get($url.'?page=2')->assertOk()->assertSee('OLDER remark');
        $this->assertSame('LATEST', $pageTwo->viewData('latest')['document_no']);
        $this->assertSame(['OLDER'], $pageTwo->viewData('inspections')->getCollection()->pluck('document_no')->all());
        $this->assertFalse($pageTwo->viewData('inspections')->hasMorePages());
        $this->assertSame('Mekanik Snapshot', $pageTwo->viewData('latest')['items'][0]['group']);
    }

    public function test_qr_rotation_invalidates_old_url_without_changing_history(): void
    {
        $admin = $this->admin();
        $form = EquipmentInspectionForm::query()->where('slug', 'mesin-lipat')->firstOrFail();
        $unit = $this->unit($form, 'MB-QR');
        $unit->update(['public_enabled' => true]);
        $oldCode = $unit->public_code;

        $qrResponse = $this->actingAs($admin)->get(route('admin.inspection-units.qr', $unit));
        $qrResponse->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringNotContainsString('approval', strtolower($qrResponse->getContent()));
        $this->assertStringNotContainsString('bearer', strtolower($qrResponse->getContent()));
        $this->post(route('admin.inspection-units.rotate', $unit))->assertRedirect();
        $unit->refresh();
        $this->assertNotSame($oldCode, $unit->public_code);
        $this->get(route('public.equipment-history.show', $oldCode))->assertNotFound();
        $this->get(route('public.equipment-history.show', $unit->public_code))->assertOk();
        $this->assertDatabaseHas('equipment_inspection_unit_logs', ['equipment_inspection_unit_id' => $unit->id, 'event' => 'public_code_rotated']);
    }

    public function test_legacy_mapping_does_not_mutate_signed_document(): void
    {
        $admin = $this->admin();
        $inspector = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $manager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $form = EquipmentInspectionForm::query()->where('slug', 'mesin-lipat')->firstOrFail();
        $inspection = app(EquipmentInspectionService::class)->save($inspector, null, 'mesin-lipat', [
            'inspection_date' => now()->toDateString(),
            'answers' => $this->answers('mesin-lipat'),
        ], []);
        $inspection->update(['template_hash' => str_repeat('a', 64)]);
        $this->approveForTest($inspection, $inspector, $manager, 'LEGACY-001');
        $unit = $this->unit($form, 'MB-LEGACY');
        $inspection->refresh();
        $before = $inspection->only(['document_no', 'template_hash', 'template_snapshot']);
        $payloadHashBefore = EquipmentInspectionSnapshot::hash(EquipmentInspectionSnapshot::payload($inspection));
        $signaturesBefore = $inspection->signatures()->get()->mapWithKeys(fn (EquipmentInspectionSignature $signature): array => [
            $signature->id => $signature->getAttributes(),
        ])->all();

        $nonAdmin = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $this->actingAs($nonAdmin)->post(route('admin.inspection-units.legacy-links.store', $unit), [
            'inspection_id' => $inspection->id,
        ])->assertForbidden();

        app(EquipmentInspectionUnitService::class)->linkLegacy($admin, $unit, $inspection, 'Verifikasi fisik oleh Admin');
        app(EquipmentInspectionUnitService::class)->linkLegacy($admin, $unit, $inspection, 'Pengulangan aman');
        $this->assertSame($before, $inspection->fresh()->only(['document_no', 'template_hash', 'template_snapshot']));
        $this->assertSame($payloadHashBefore, EquipmentInspectionSnapshot::hash(EquipmentInspectionSnapshot::payload($inspection->fresh())));
        $this->assertSame($signaturesBefore, $inspection->signatures()->get()->mapWithKeys(fn (EquipmentInspectionSignature $signature): array => [
            $signature->id => $signature->getAttributes(),
        ])->all());
        $this->assertDatabaseHas('equipment_inspection_unit_links', [
            'equipment_inspection_id' => $inspection->id,
            'equipment_inspection_unit_id' => $unit->id,
            'link_source' => EquipmentInspectionUnitLink::SOURCE_ADMIN_LEGACY,
        ]);
        $this->assertDatabaseHas('equipment_inspection_logs', ['equipment_inspection_id' => $inspection->id, 'event' => 'unit_linked_legacy']);
        $this->assertSame(1, EquipmentInspectionUnitLink::query()->where('equipment_inspection_id', $inspection->id)->count());

        $other = $this->unit($form, 'MB-OTHER');
        $this->expectException(ValidationException::class);
        app(EquipmentInspectionUnitService::class)->linkLegacy($admin, $other, $inspection, null);
    }

    public function test_legacy_mapping_rejects_document_without_approved_valid_signatures(): void
    {
        $admin = $this->admin();
        $inspector = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $form = EquipmentInspectionForm::query()->where('slug', 'mesin-lipat')->firstOrFail();
        $inspection = app(EquipmentInspectionService::class)->save($inspector, null, $form->slug, [
            'inspection_date' => now()->toDateString(),
            'answers' => $this->answers($form->slug),
        ], []);
        $unit = $this->unit($form, 'MB-UNSIGNED');

        $this->expectException(ValidationException::class);
        app(EquipmentInspectionUnitService::class)->linkLegacy($admin, $unit, $inspection, null);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);
    }

    private function unit(EquipmentInspectionForm $form, string $code, string $status = EquipmentInspectionUnit::STATUS_ACTIVE): EquipmentInspectionUnit
    {
        return EquipmentInspectionUnit::query()->create([
            'equipment_inspection_form_id' => $form->id,
            'asset_code' => $code,
            'name' => 'Unit '.$code,
            'location' => 'Workshop',
            'public_code' => Str::lower(Str::random(32)),
            'lifecycle_status' => $status,
            'public_enabled' => false,
            'sort_order' => 0,
        ]);
    }

    private function answers(string $slug, string $findingRating = 'A', string $remark = ''): array
    {
        $form = EquipmentFormCatalog::find($slug);
        $index = 0;

        return collect($form['groups'])->flatMap(fn (array $group): array => $group['items'])
            ->mapWithKeys(function (array $item) use (&$index, $findingRating, $remark): array {
                $rating = $index++ === 0 ? $findingRating : 'A';

                return [$item['id'] => ['rating' => $rating, 'remark' => $rating === 'A' ? '' : $remark]];
            })->all();
    }

    private function approveForTest(EquipmentInspection $inspection, User $inspector, User $manager, string $number): void
    {
        $inspection->forceFill([
            'document_no' => $number,
            'signed_at' => now(),
            'status' => EquipmentInspection::STATUS_APPROVED,
        ])->save();
        $payload = EquipmentInspectionSnapshot::payload($inspection);
        $hash = EquipmentInspectionSnapshot::hash($payload);
        $base = [
            'document_version' => 1,
            'signature_sha256' => hash('sha256', 'signature'),
            'signed_at' => now(),
            'content_hash' => $hash,
            'signed_payload' => $payload,
        ];
        $inspection->signatures()->create([
            ...$base,
            'role_key' => EquipmentInspectionSignature::ROLE_INSPECTOR,
            'step' => 1,
            'signer_user_id' => $inspector->id,
            'signer_name' => $inspector->name,
            'signer_position' => 'Inspektor',
            'signature_path' => 'equipment-inspections/test/inspector.png',
        ]);
        $inspection->signatures()->create([
            ...$base,
            'role_key' => EquipmentInspectionSignature::ROLE_MANAGER,
            'step' => 2,
            'signer_user_id' => $manager->id,
            'signer_name' => $manager->name,
            'signer_position' => 'Manager Workshop',
            'signature_path' => 'equipment-inspections/test/manager.png',
        ]);
    }
}
