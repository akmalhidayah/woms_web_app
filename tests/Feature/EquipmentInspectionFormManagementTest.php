<?php

namespace Tests\Feature;

use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionForm;
use App\Models\EquipmentInspectionSignature;
use App\Models\User;
use App\Services\Inspector\EquipmentInspectionService;
use App\Support\Inspector\EquipmentFormCatalog;
use App\Support\Inspector\EquipmentInspectionAdminUnread;
use App\Support\Inspector\LegacyEquipmentInspectionFormBackfill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EquipmentInspectionFormManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_backfill_is_complete_idempotent_and_does_not_overwrite_admin_changes(): void
    {
        $this->assertSame(10, EquipmentInspectionForm::query()->count());
        $this->assertSame(10, EquipmentInspectionForm::query()->active()->count());
        $this->assertSame(73, \App\Models\EquipmentInspectionFormItem::query()->count());
        $this->assertSame([
            'mesin-lipat', 'mesin-potong', 'mesin-roll', 'mesin-las-rc-500', 'mesin-bor-meja',
            'forklift-9-54', 'plasma-cutting-esp-150', 'plasma-cutting-tomahawk-1538',
            'overhead-crane-demac-556980', 'overhead-crane-mhe-20-3055-92',
        ], EquipmentInspectionForm::query()->orderBy('sort_order')->pluck('slug')->all());

        $folding = EquipmentInspectionForm::query()->where('slug', 'mesin-lipat')->with('items')->firstOrFail();
        $this->assertSame('mesin-lipat-utama-bearing-shaft', $folding->items->first()->item_key);
        $drill = EquipmentInspectionForm::query()->where('slug', 'mesin-bor-meja')->with('items')->firstOrFail();
        $this->assertSame(['Tipe 2Y 4132', 'Tipe BS-40'], $drill->items->pluck('group_name')->unique()->values()->all());

        $folding->update(['name' => 'Nama hasil edit Admin', 'status' => EquipmentInspectionForm::STATUS_INACTIVE, 'version' => 7]);
        $folding->items->first()->update(['label' => 'Item hasil edit Admin']);
        $result = app(LegacyEquipmentInspectionFormBackfill::class)->run();

        $this->assertSame(['created' => 0, 'skipped' => 10], $result);
        $this->assertSame(10, EquipmentInspectionForm::query()->count());
        $this->assertDatabaseHas('equipment_inspection_forms', [
            'slug' => 'mesin-lipat', 'name' => 'Nama hasil edit Admin', 'status' => 'inactive', 'version' => 7,
        ]);
        $this->assertDatabaseHas('equipment_inspection_form_items', [
            'equipment_inspection_form_id' => $folding->id, 'item_key' => 'mesin-lipat-utama-bearing-shaft', 'label' => 'Item hasil edit Admin',
        ]);
    }

    public function test_admin_can_create_edit_preview_and_deactivate_form_while_non_admin_is_rejected(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);
        $inspector = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $payload = [
            'name' => 'Mesin Uji Baru',
            'description' => 'Form dari database.',
            'sort_order' => 50,
            'action' => 'publish',
            'image' => $this->validImage(),
            'items' => [
                ['group_name' => 'Mekanik', 'label' => 'Check Bearing'],
                ['group_name' => 'Kelistrikan', 'label' => 'Check Panel'],
            ],
        ];

        $this->actingAs($inspector)->post(route('admin.inspection-forms.store'), $payload)->assertForbidden();
        $payload['image'] = $this->validImage();
        $this->actingAs($admin)->post(route('admin.inspection-forms.store'), $payload)->assertRedirect();

        $this->get(route('admin.inspection-forms.index'))
            ->assertOk()
            ->assertDontSee('Form awal')
            ->assertDontSee('>Versi<', false);

        $form = EquipmentInspectionForm::query()->where('slug', 'mesin-uji-baru')->with('items')->firstOrFail();
        $this->assertSame(EquipmentInspectionForm::STATUS_ACTIVE, $form->status);
        $this->assertCount(2, $form->items);
        $this->assertNotNull($form->image_path);
        Storage::disk('local')->assertExists($form->image_path);
        $this->assertArrayNotHasKey('image_path', EquipmentFormCatalog::find($form->slug));
        $this->get(route('admin.inspection-forms.edit', $form))
            ->assertOk()
            ->assertDontSee('Grup (opsional)')
            ->assertDontSee('Nama grup opsional')
            ->assertDontSee('Urutan tampil')
            ->assertSee('name="sort_order"', false)
            ->assertSeeInOrder(['Check Bearing', 'Tambah Item']);
        $this->get(route('admin.inspection-forms.preview', $form))->assertOk()->assertSee('Check Bearing');
        $this->get(route('admin.inspection-forms.image', $form))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($inspector)->get(route('inspector.equipment-forms.index'))
            ->assertOk()
            ->assertSee('Mesin Uji Baru')
            ->assertSee(route('inspector.equipment-forms.image', $form->slug), false)
            ->assertSee('class="flex cursor-pointer flex-col', false)
            ->assertSee('aria-label="Pilih Form Mesin Uji Baru"', false);
        $this->get(route('inspector.equipment-forms.image', $form->slug))->assertOk()->assertHeader('Cache-Control', 'private, no-store');

        $firstKey = $form->items->first()->item_key;
        $removedItemId = $form->items[1]->id;
        $originalImagePath = $form->image_path;
        $this->actingAs($admin)->put(route('admin.inspection-forms.update', $form), [
            'name' => 'Mesin Uji Renamed',
            'description' => 'Diperbarui.',
            'sort_order' => 3,
            'action' => 'publish',
            'image' => $this->validImage(),
            'items' => [
                ['id' => $form->items[0]->id, 'item_key' => $form->items[0]->item_key, 'group_key' => $form->items[0]->group_key, 'group_name' => 'Mekanik', 'label' => 'Check Bearing Updated'],
                ['group_name' => 'Mekanik', 'label' => 'Check Gearbox'],
            ],
        ])->assertRedirect();

        $form->refresh()->load('items');
        $this->assertSame('mesin-uji-baru', $form->slug);
        $this->assertSame(2, $form->version);
        $this->assertSame($firstKey, $form->items->first()->item_key);
        $this->assertDatabaseMissing('equipment_inspection_form_items', ['id' => $removedItemId]);
        $this->assertSame(['Check Bearing Updated', 'Check Gearbox'], $form->items->pluck('label')->all());
        $this->assertNotSame($originalImagePath, $form->image_path);
        Storage::disk('local')->assertMissing($originalImagePath);
        Storage::disk('local')->assertExists($form->image_path);

        $first = $form->items[0];
        $second = $form->items[1];
        $this->put(route('admin.inspection-forms.update', $form), [
            'name' => 'Mesin Uji Renamed',
            'description' => 'Diperbarui.',
            'sort_order' => 3,
            'action' => 'publish',
            'items' => [
                ['id' => $second->id, 'item_key' => $second->item_key, 'group_key' => $second->group_key, 'group_name' => 'Mekanik', 'label' => $second->label],
                ['id' => $first->id, 'item_key' => $first->item_key, 'group_key' => $first->group_key, 'group_name' => 'Mekanik', 'label' => $first->label],
            ],
        ])->assertRedirect();
        $form->refresh()->load('items');
        $this->assertSame([$second->item_key, $first->item_key], $form->items->pluck('item_key')->all());
        $this->assertSame(3, $form->version);

        $this->patch(route('admin.inspection-forms.deactivate', $form))->assertRedirect();
        $this->assertSame(EquipmentInspectionForm::STATUS_INACTIVE, $form->fresh()->status);
        $this->actingAs($inspector)->get(route('inspector.equipment-forms.show', $form->slug))->assertNotFound();

        $form->refresh()->load('items');
        $draftItems = $form->items->map(fn ($item): array => [
            'id' => $item->id,
            'item_key' => $item->item_key,
            'group_key' => $item->group_key,
            'group_name' => $item->group_name,
            'label' => $item->label.' Draft',
        ])->all();
        $this->actingAs($admin)->put(route('admin.inspection-forms.update', $form), [
            'name' => $form->name,
            'description' => $form->description,
            'sort_order' => $form->sort_order,
            'action' => 'save_draft',
            'items' => $draftItems,
        ])->assertRedirect();
        $form->refresh();
        $this->assertSame(3, $form->version);
        $this->assertSame(EquipmentInspectionForm::STATUS_DRAFT, $form->status);

        $this->put(route('admin.inspection-forms.update', $form), [
            'name' => $form->name,
            'description' => $form->description,
            'sort_order' => $form->sort_order,
            'action' => 'publish',
            'items' => $draftItems,
        ])->assertRedirect();
        $form->refresh();
        $this->assertSame(4, $form->version);
        $this->assertSame(EquipmentInspectionForm::STATUS_ACTIVE, $form->status);
    }

    public function test_new_form_requires_valid_image_before_publish_and_private_image_is_authorized(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);
        $inspector = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $data = [
            'name' => 'Tanpa Foto', 'description' => null, 'sort_order' => 1, 'action' => 'publish',
            'items' => [['group_name' => '', 'label' => 'Check Satu']],
        ];

        $this->actingAs($admin)->post(route('admin.inspection-forms.store'), $data)->assertSessionHasErrors('image');
        $data['image'] = UploadedFile::fake()->createWithContent('not-image.jpg', 'not an image');
        $this->post(route('admin.inspection-forms.store'), $data)->assertSessionHasErrors('image');

        $legacy = EquipmentInspectionForm::query()->where('slug', 'mesin-potong')->firstOrFail();
        $this->actingAs($inspector)->get(route('inspector.equipment-forms.image', $legacy->slug))->assertNotFound();
        $this->actingAs($user)->get(route('inspector.equipment-forms.image', $legacy->slug))->assertForbidden();
        $this->get(route('admin.inspection-forms.index'))->assertForbidden();
    }

    public function test_master_validation_and_unique_slug_generation_are_server_side(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);
        $this->actingAs($admin)->post(route('admin.inspection-forms.store'), [
            'name' => '', 'sort_order' => 0, 'action' => 'save_draft', 'items' => [],
        ])->assertSessionHasErrors(['name', 'items']);

        foreach ([1, 2] as $iteration) {
            $this->post(route('admin.inspection-forms.store'), [
                'name' => 'Nama Slug Sama', 'description' => null, 'sort_order' => $iteration,
                'action' => 'save_draft', 'items' => [['group_name' => '', 'label' => 'Check']],
            ])->assertRedirect();
        }

        $this->assertDatabaseHas('equipment_inspection_forms', ['slug' => 'nama-slug-sama']);
        $this->assertDatabaseHas('equipment_inspection_forms', ['slug' => 'nama-slug-sama-2']);

        $first = EquipmentInspectionForm::query()->where('slug', 'nama-slug-sama')->with('items')->firstOrFail();
        $second = EquipmentInspectionForm::query()->where('slug', 'nama-slug-sama-2')->with('items')->firstOrFail();
        $this->put(route('admin.inspection-forms.update', $first), [
            'name' => $first->name, 'description' => null, 'sort_order' => 1, 'action' => 'save_draft',
            'items' => [[
                'id' => $second->items->first()->id,
                'item_key' => $second->items->first()->item_key,
                'group_key' => $second->items->first()->group_key,
                'group_name' => '',
                'label' => 'Manipulasi item form lain',
            ]],
        ])->assertSessionHasErrors('items');
        $this->assertSame('Check', $first->items->first()->fresh()->label);
    }

    public function test_inactive_master_blocks_new_inspection_but_existing_draft_keeps_snapshot(): void
    {
        $inspector = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $form = EquipmentFormCatalog::find('mesin-lipat');
        $answers = collect($form['groups'])->flatMap(fn (array $group): array => $group['items'])
            ->mapWithKeys(fn (array $item): array => [$item['id'] => ['rating' => 'A', 'remark' => '']])->all();
        $inspection = app(EquipmentInspectionService::class)->save($inspector, null, 'mesin-lipat', [
            'inspection_date' => now()->toDateString(), 'answers' => $answers,
        ], []);
        $snapshot = $inspection->template_snapshot;

        $master = EquipmentInspectionForm::query()->where('slug', 'mesin-lipat')->firstOrFail();
        $master->update(['status' => EquipmentInspectionForm::STATUS_INACTIVE, 'name' => 'Nama Master Baru']);
        $master->items()->first()->update(['label' => 'Label Master Baru']);

        $this->actingAs($inspector)->get(route('inspector.equipment-forms.show', 'mesin-lipat'))->assertNotFound();
        $this->post(route('inspector.inspections.store', 'mesin-lipat'), [
            'inspection_date' => now()->toDateString(), 'answers' => $answers,
        ])->assertNotFound();
        $this->get(route('inspector.inspections.show', $inspection))->assertOk()->assertSee('Mesin Lipat')->assertDontSee('Nama Master Baru');
        $this->put(route('inspector.inspections.update', $inspection), [
            'inspection_date' => now()->toDateString(), 'lock_version' => $inspection->lock_version, 'answers' => $answers,
        ])->assertRedirect(route('inspector.inspections.show', $inspection));
        $this->assertSame($snapshot, $inspection->fresh()->template_snapshot);
    }

    public function test_unread_badge_tracks_current_submitted_version_per_admin_and_excludes_drafts_and_deleted_records(): void
    {
        $adminA = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);
        $adminB = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);
        $inspector = User::factory()->create(['role' => User::ROLE_INSPECTOR]);
        $draft = $this->draft($inspector);
        $unread = app(EquipmentInspectionAdminUnread::class);

        $this->assertSame(0, $unread->count($adminA));
        $draft->update(['document_no' => '001/INSP/TEST', 'signed_at' => now(), 'status' => EquipmentInspection::STATUS_READY]);
        $this->addInspectorSignature($draft, 1);
        $this->assertSame(1, $unread->count($adminA));
        $this->assertSame(1, $unread->count($adminB));

        $this->actingAs($adminA)->get(route('admin.inspections.show', $draft))->assertOk();
        $this->assertSame(0, $unread->count($adminA));
        $this->assertSame(1, $unread->count($adminB));

        $draft->update(['document_version' => 2, 'status' => EquipmentInspection::STATUS_READY, 'signed_at' => now()]);
        $this->addInspectorSignature($draft, 2);
        $this->assertSame(1, $unread->count($adminA));

        $draft->delete();
        $this->assertSame(0, $unread->count($adminA));
        $this->assertSame(0, $unread->count($adminB));
    }

    private function draft(User $inspector): EquipmentInspection
    {
        $form = EquipmentFormCatalog::find('mesin-lipat');
        $answers = collect($form['groups'])->flatMap(fn (array $group): array => $group['items'])
            ->mapWithKeys(fn (array $item): array => [$item['id'] => ['rating' => 'A', 'remark' => '']])->all();

        return app(EquipmentInspectionService::class)->save($inspector, null, 'mesin-lipat', [
            'inspection_date' => now()->toDateString(), 'answers' => $answers,
        ], []);
    }

    private function addInspectorSignature(EquipmentInspection $inspection, int $version): void
    {
        $inspection->signatures()->create([
            'role_key' => EquipmentInspectionSignature::ROLE_INSPECTOR,
            'step' => 1,
            'signer_user_id' => $inspection->inspector_user_id,
            'signer_name' => $inspection->inspector_name,
            'signer_position' => 'Inspektor',
            'signature_path' => 'equipment-inspections/'.$inspection->public_id.'/signatures/test-'.$version.'.png',
            'signature_sha256' => hash('sha256', 'signature-'.$version),
            'signed_at' => now(),
            'document_version' => $version,
            'content_hash' => hash('sha256', 'content-'.$version),
            'signed_payload' => ['version' => $version],
        ]);
    }

    private function validImage(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'equipment.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAAFElEQVR42mNkYGD4z8DAwMDEAAUADikBA07hWZQAAAAASUVORK5CYII=', true)
        );
    }
}
