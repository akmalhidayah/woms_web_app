<?php

namespace Tests\Feature;

use App\Models\EquipmentInspectionForm;
use App\Models\EquipmentInspectionUnit;
use Database\Seeders\EquipmentInspectionUnitBackfillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EquipmentInspectionUnitBackfillSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_is_idempotent_and_preserves_existing_unit_identity_and_form_status(): void
    {
        $this->missingLegacyForm('mesin-lipat-hidrolik', 'Mesin Lipat Hidrolik');
        $this->missingLegacyForm('mesin-bor-orion', 'Mesin Bor Orion');

        $rolling = EquipmentInspectionForm::query()->where('slug', 'mesin-roll')->firstOrFail();
        $rolling->update(['status' => EquipmentInspectionForm::STATUS_INACTIVE]);
        $existingPublicCode = Str::lower(Str::random(32));
        EquipmentInspectionUnit::query()->create([
            'equipment_inspection_form_id' => $rolling->id,
            'asset_code' => 'MR-OFFICIAL',
            'name' => 'Nama Unit Existing',
            'location' => 'Lokasi Lama',
            'public_code' => $existingPublicCode,
            'lifecycle_status' => EquipmentInspectionUnit::STATUS_INACTIVE,
            'public_enabled' => false,
            'sort_order' => 77,
        ]);

        app(EquipmentInspectionUnitBackfillSeeder::class)->run();

        $expectedCodes = [
            'mesin-lipat' => 'MB01',
            'mesin-lipat-hidrolik' => 'MB02',
            'mesin-bor-orion' => 'MB001',
            'mesin-bor-meja' => 'BO01',
            'mesin-potong' => 'MP01',
            'mesin-roll' => 'MR-OFFICIAL',
            'mesin-las-rc-500' => 'ML-RC05',
            'forklift-9-54' => 'FL954',
            'plasma-cutting-esp-150' => 'PC01',
            'plasma-cutting-tomahawk-1538' => 'PC02',
            'overhead-crane-demac-556980' => 'OC01',
            'overhead-crane-mhe-20-3055-92' => 'OC02',
        ];

        foreach ($expectedCodes as $slug => $assetCode) {
            $form = EquipmentInspectionForm::query()->where('slug', $slug)->firstOrFail();
            $unit = $form->units()->sole();
            $this->assertSame($assetCode, $unit->asset_code, $slug);
            $this->assertSame('Workshop Machine', $unit->location, $slug);
            $this->assertFalse($unit->public_enabled, $slug);
        }

        $this->assertSame($existingPublicCode, $rolling->units()->sole()->public_code);
        $this->assertSame(EquipmentInspectionUnit::STATUS_INACTIVE, $rolling->units()->sole()->lifecycle_status);
        $this->assertSame(EquipmentInspectionForm::STATUS_INACTIVE, $rolling->fresh()->status);

        $identityBeforeSecondRun = EquipmentInspectionUnit::query()
            ->orderBy('id')
            ->get(['id', 'equipment_inspection_form_id', 'asset_code', 'public_code'])
            ->map->getAttributes()
            ->all();

        app(EquipmentInspectionUnitBackfillSeeder::class)->run();

        $identityAfterSecondRun = EquipmentInspectionUnit::query()
            ->orderBy('id')
            ->get(['id', 'equipment_inspection_form_id', 'asset_code', 'public_code'])
            ->map->getAttributes()
            ->all();
        $this->assertSame($identityBeforeSecondRun, $identityAfterSecondRun);
        $this->assertSame(12, EquipmentInspectionUnit::query()->count());
    }

    public function test_backfill_skips_code_conflicts_and_forms_with_multiple_existing_units(): void
    {
        $folding = EquipmentInspectionForm::query()->where('slug', 'mesin-lipat')->firstOrFail();
        $cutting = EquipmentInspectionForm::query()->where('slug', 'mesin-potong')->firstOrFail();
        $rolling = EquipmentInspectionForm::query()->where('slug', 'mesin-roll')->firstOrFail();

        $this->unit($rolling, 'mb01', 'Lokasi Pemilik Kode');
        $this->unit($cutting, 'MP-EXISTING-1', 'Lokasi Satu');
        $this->unit($cutting, 'MP-EXISTING-2', 'Lokasi Dua');

        app(EquipmentInspectionUnitBackfillSeeder::class)->run();

        $this->assertSame(0, $folding->units()->count());
        $this->assertSame(2, $cutting->units()->count());
        $this->assertSame(['Lokasi Satu', 'Lokasi Dua'], $cutting->units()->pluck('location')->all());
        $this->assertSame('mb01', $rolling->units()->sole()->asset_code);
    }

    private function missingLegacyForm(string $slug, string $name): EquipmentInspectionForm
    {
        return EquipmentInspectionForm::query()->create([
            'slug' => $slug,
            'name' => $name,
            'status' => EquipmentInspectionForm::STATUS_DRAFT,
            'sort_order' => 0,
            'version' => 1,
            'is_legacy' => false,
        ]);
    }

    private function unit(EquipmentInspectionForm $form, string $assetCode, string $location): EquipmentInspectionUnit
    {
        return EquipmentInspectionUnit::query()->create([
            'equipment_inspection_form_id' => $form->id,
            'asset_code' => $assetCode,
            'name' => $form->name,
            'location' => $location,
            'public_code' => Str::lower(Str::random(32)),
            'lifecycle_status' => EquipmentInspectionUnit::STATUS_ACTIVE,
            'public_enabled' => false,
            'sort_order' => 0,
        ]);
    }
}
