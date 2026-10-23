<?php

namespace Database\Seeders;

use App\Models\EquipmentInspectionForm;
use App\Models\EquipmentInspectionUnit;
use App\Models\User;
use App\Services\Inspector\EquipmentInspectionUnitService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EquipmentInspectionUnitBackfillSeeder extends Seeder
{
    /**
     * Existing slugs are the identity. Display names are documentation only and
     * are never used to locate or rename a form.
     *
     * @var array<string, array{name: string, asset_code: string}>
     */
    private const TARGETS = [
        'mesin-lipat' => ['name' => 'Mesin Lipat Manual', 'asset_code' => 'MB01'],
        'mesin-lipat-hidrolik' => ['name' => 'Mesin Lipat Hidrolik', 'asset_code' => 'MB02'],
        'mesin-bor-orion' => ['name' => 'Mesin Bor Orion', 'asset_code' => 'MB001'],
        'mesin-bor-meja' => ['name' => 'Mesin Bor Meja', 'asset_code' => 'BO01'],
        'mesin-potong' => ['name' => 'Mesin Potong', 'asset_code' => 'MP01'],
        'mesin-roll' => ['name' => 'Mesin Roll', 'asset_code' => 'MR01'],
        'mesin-las-rc-500' => ['name' => 'Mesin Las RC 05', 'asset_code' => 'ML-RC05'],
        'forklift-9-54' => ['name' => 'Forklift 9-54', 'asset_code' => 'FL954'],
        'plasma-cutting-esp-150' => ['name' => 'Plasma Cutting ESP-150', 'asset_code' => 'PC01'],
        'plasma-cutting-tomahawk-1538' => ['name' => 'Plasma Cutting Tomahawk 1538', 'asset_code' => 'PC02'],
        'overhead-crane-demac-556980' => ['name' => 'Overhead Crane Demac 10T (556980)', 'asset_code' => 'OC01'],
        'overhead-crane-mhe-20-3055-92' => ['name' => 'Overhead Crane MHE 10T (20-3055-92)', 'asset_code' => 'OC02'],
    ];

    public function run(): void
    {
        $service = app(EquipmentInspectionUnitService::class);
        $forms = EquipmentInspectionForm::query()
            ->whereIn('slug', array_keys(self::TARGETS))
            ->with(['units' => fn ($query) => $query->orderBy('id')])
            ->get()
            ->keyBy('slug');
        $plans = [];
        $stats = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];

        // Finish the read-only preflight for every target before changing data.
        foreach (self::TARGETS as $slug => $target) {
            $form = $forms->get($slug);
            if (! $form) {
                $this->skip($stats, $slug, 'Master Form tidak ditemukan.');

                continue;
            }
            if ($form->units->count() > 1) {
                $this->skip($stats, $slug, 'Master Form memiliki beberapa unit existing.');

                continue;
            }

            $unit = $form->units->first();
            $existingCode = trim((string) $unit?->asset_code);
            $assetCode = $existingCode !== '' ? $existingCode : $target['asset_code'];
            if ($existingCode === '') {
                $codeOwner = EquipmentInspectionUnit::query()
                    ->whereRaw('UPPER(asset_code) = ?', [Str::upper($target['asset_code'])])
                    ->when($unit !== null, fn ($query) => $query->where('id', '!=', $unit->id))
                    ->first();
                if ($codeOwner) {
                    $this->skip($stats, $slug, 'Kode usulan '.$target['asset_code'].' sudah digunakan unit lain.');

                    continue;
                }
            }

            $plans[] = compact('form', 'unit', 'assetCode');
        }

        foreach ($plans as $plan) {
            /** @var EquipmentInspectionForm $form */
            $form = $plan['form'];
            /** @var EquipmentInspectionUnit|null $unit */
            $unit = $plan['unit'];
            $actorId = $form->updated_by ?: $form->created_by;
            $actor = $actorId ? User::query()->find($actorId) : null;
            $before = $unit?->only(['asset_code', 'name', 'location']);

            try {
                $saved = $service->savePrimaryForForm(
                    $actor,
                    $form,
                    $plan['assetCode'],
                    EquipmentInspectionUnitService::DEFAULT_LOCATION,
                );
            } catch (ValidationException $exception) {
                $message = collect($exception->errors())->flatten()->first() ?: 'Validasi unit gagal.';
                $this->skip($stats, $form->slug, $message);

                continue;
            }

            if ($saved->wasRecentlyCreated) {
                $stats['created']++;
                $this->command?->info($form->slug.': unit '.$saved->asset_code.' dibuat.');
            } elseif ($before !== $saved->only(['asset_code', 'name', 'location'])) {
                $stats['updated']++;
                $this->command?->info($form->slug.': identitas unit dilengkapi tanpa merotasi QR.');
            } else {
                $stats['unchanged']++;
                $this->command?->line($form->slug.': tidak ada perubahan.');
            }
        }

        $this->command?->info(sprintf(
            'Backfill unit selesai: %d dibuat, %d diperbarui, %d tetap, %d dilewati.',
            $stats['created'],
            $stats['updated'],
            $stats['unchanged'],
            $stats['skipped'],
        ));
    }

    /**
     * @param  array{created: int, updated: int, unchanged: int, skipped: int}  $stats
     */
    private function skip(array &$stats, string $slug, string $reason): void
    {
        $stats['skipped']++;
        $this->command?->warn($slug.': dilewati — '.$reason);
    }
}
