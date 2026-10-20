<?php

namespace App\Support\Inspector;

use App\Models\EquipmentInspectionForm;
use Illuminate\Support\Facades\DB;

final class LegacyEquipmentInspectionFormBackfill
{
    /**
     * @return array{created: int, skipped: int}
     */
    public function run(): array
    {
        $result = ['created' => 0, 'skipped' => 0];

        DB::transaction(function () use (&$result): void {
            foreach (EquipmentFormCatalog::legacy() as $slug => $definition) {
                if (EquipmentInspectionForm::query()->where('slug', $slug)->lockForUpdate()->exists()) {
                    $result['skipped']++;

                    continue;
                }

                $form = EquipmentInspectionForm::query()->create([
                    'slug' => $slug,
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'status' => EquipmentInspectionForm::STATUS_ACTIVE,
                    'sort_order' => $definition['sort_order'],
                    'version' => 1,
                    'is_legacy' => true,
                ]);

                $position = 0;
                foreach ($definition['groups'] as $group) {
                    foreach ($group['items'] as $item) {
                        $form->items()->create([
                            'item_key' => $item['id'],
                            'label' => $item['label'],
                            'group_key' => $group['id'],
                            'group_name' => $group['name'],
                            'sort_order' => ++$position,
                        ]);
                    }
                }

                $form->logs()->create([
                    'event' => 'legacy_backfilled',
                    'form_version' => 1,
                    'metadata' => ['slug' => $slug, 'item_count' => $position],
                    'created_at' => now(),
                ]);
                $result['created']++;
            }
        });

        return $result;
    }
}
