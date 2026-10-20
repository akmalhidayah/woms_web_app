<?php

namespace App\Support\Inspector;

use App\Models\EquipmentInspectionForm;

final class EquipmentInspectionFormRepository
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function activeCatalog(): array
    {
        return EquipmentInspectionForm::query()->active()->with('items')
            ->orderBy('sort_order')->orderBy('id')->get()
            ->mapWithKeys(fn (EquipmentInspectionForm $form): array => [$form->slug => $this->template($form)])->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActive(string $slug): ?array
    {
        $form = EquipmentInspectionForm::query()->active()->where('slug', $slug)->with('items')->first();

        return $form ? $this->template($form) : null;
    }

    /**
     * The result deliberately excludes private image paths. It is safe to store as a document snapshot.
     *
     * @return array<string, mixed>
     */
    public function template(EquipmentInspectionForm $form): array
    {
        $form->loadMissing('items');
        $groups = [];

        foreach ($form->items as $item) {
            if (! isset($groups[$item->group_key])) {
                $groups[$item->group_key] = [
                    'id' => $item->group_key,
                    'name' => $item->group_name,
                    'items' => [],
                ];
            }
            $groups[$item->group_key]['items'][] = ['id' => $item->item_key, 'label' => $item->label];
        }

        return [
            'id' => $form->slug,
            'master_id' => (int) $form->id,
            'name' => $form->name,
            'icon' => 'clipboard-check',
            'description' => $form->description,
            'version' => (int) $form->version,
            'has_image' => filled($form->image_path),
            'groups' => array_values($groups),
            'item_count' => $form->items->count(),
        ];
    }
}
