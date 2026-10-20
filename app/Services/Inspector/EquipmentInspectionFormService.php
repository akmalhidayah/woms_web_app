<?php

namespace App\Services\Inspector;

use App\Models\EquipmentInspectionForm;
use App\Models\EquipmentInspectionFormItem;
use App\Models\User;
use App\Support\Inspector\EquipmentInspectionFormImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class EquipmentInspectionFormService
{
    public function __construct(private readonly EquipmentInspectionFormImageStorage $images) {}

    public function create(User $actor, array $data, ?UploadedFile $image): EquipmentInspectionForm
    {
        $stored = $image ? $this->images->store($image) : null;
        try {
            return DB::transaction(function () use ($actor, $data, $stored): EquipmentInspectionForm {
                if ($data['action'] === 'publish' && $stored === null) {
                    throw ValidationException::withMessages(['image' => 'Foto peralatan wajib sebelum form baru diaktifkan.']);
                }
                $form = EquipmentInspectionForm::query()->create([
                    'slug' => $this->uniqueSlug($data['name']),
                    'name' => $data['name'],
                    'description' => $data['description'] ?: null,
                    'sort_order' => (int) $data['sort_order'],
                    'status' => $data['action'] === 'publish' ? EquipmentInspectionForm::STATUS_ACTIVE : EquipmentInspectionForm::STATUS_DRAFT,
                    'version' => 1,
                    'is_legacy' => false,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                    ...$this->imageColumns($stored),
                ]);
                $this->syncItems($form, $data['items']);
                $this->audit($form, $actor, $data['action'] === 'publish' ? 'created_and_published' : 'draft_created');

                return $form->fresh('items');
            });
        } catch (Throwable $exception) {
            $this->images->cleanup($stored['path'] ?? null);
            throw $exception;
        }
    }

    public function update(User $actor, EquipmentInspectionForm $existing, array $data, ?UploadedFile $image): EquipmentInspectionForm
    {
        $stored = $image ? $this->images->store($image) : null;
        $oldPath = null;
        try {
            $form = DB::transaction(function () use ($actor, $existing, $data, $stored, &$oldPath): EquipmentInspectionForm {
                $form = EquipmentInspectionForm::query()->whereKey($existing->id)->lockForUpdate()->with('items')->firstOrFail();
                $before = $this->auditSnapshot($form);
                $oldPath = $stored ? $form->image_path : null;
                $publishing = $data['action'] === 'publish' || $form->status === EquipmentInspectionForm::STATUS_ACTIVE;
                $publishingSavedRevision = $publishing
                    && $form->status === EquipmentInspectionForm::STATUS_DRAFT
                    && $this->wasPublishedBefore($form);
                if ($publishing && ! $form->is_legacy && $stored === null && blank($form->image_path)) {
                    throw ValidationException::withMessages(['image' => 'Foto peralatan wajib sebelum form diaktifkan.']);
                }

                $form->forceFill([
                    'name' => $data['name'],
                    'description' => $data['description'] ?: null,
                    'sort_order' => (int) $data['sort_order'],
                    'status' => $publishing ? EquipmentInspectionForm::STATUS_ACTIVE : EquipmentInspectionForm::STATUS_DRAFT,
                    'updated_by' => $actor->id,
                    ...($stored ? $this->imageColumns($stored) : []),
                ])->save();
                $this->syncItems($form, $data['items']);
                $form->load('items');
                $after = $this->auditSnapshot($form);
                $changed = $before !== $after;
                if ($publishing && ($changed || $publishingSavedRevision)) {
                    $form->increment('version');
                    $form->refresh();
                }
                $this->audit($form, $actor, $publishing ? 'definition_published' : 'draft_updated', [
                    'changed' => $changed,
                    'before' => $changed ? $before : null,
                    'after' => $changed ? $after : null,
                ]);

                return $form->fresh('items');
            });
        } catch (Throwable $exception) {
            $this->images->cleanup($stored['path'] ?? null);
            throw $exception;
        }

        if ($oldPath) {
            $this->images->cleanup($oldPath);
        }

        return $form;
    }

    public function setActive(User $actor, EquipmentInspectionForm $existing, bool $active): EquipmentInspectionForm
    {
        return DB::transaction(function () use ($actor, $existing, $active): EquipmentInspectionForm {
            $form = EquipmentInspectionForm::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
            if ($active) {
                if (! $form->items()->exists()) {
                    throw ValidationException::withMessages(['form' => 'Form tanpa item tidak dapat diaktifkan.']);
                }
                if (! $form->is_legacy && blank($form->image_path)) {
                    throw ValidationException::withMessages(['form' => 'Unggah foto peralatan sebelum mengaktifkan form.']);
                }
            }
            $target = $active ? EquipmentInspectionForm::STATUS_ACTIVE : EquipmentInspectionForm::STATUS_INACTIVE;
            if ($form->status !== $target) {
                $publishSavedRevision = $active
                    && $form->status === EquipmentInspectionForm::STATUS_DRAFT
                    && $this->wasPublishedBefore($form);
                $form->forceFill([
                    'status' => $target,
                    'updated_by' => $actor->id,
                    'version' => $form->version + ($publishSavedRevision ? 1 : 0),
                ])->save();
                $this->audit($form, $actor, $active ? 'activated' : 'deactivated');
            }

            return $form;
        });
    }

    private function wasPublishedBefore(EquipmentInspectionForm $form): bool
    {
        return $form->is_legacy || $form->logs()
            ->whereIn('event', ['created_and_published', 'definition_published', 'deactivated'])
            ->exists();
    }

    private function syncItems(EquipmentInspectionForm $form, array $submitted): void
    {
        $existing = $form->items()->get()->keyBy('id');
        $submittedIds = collect($submitted)->pluck('id')->filter()->map(fn ($id): int => (int) $id);
        if ($submittedIds->duplicates()->isNotEmpty() || $submittedIds->diff($existing->keys())->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => 'Daftar item tidak sesuai master form. Muat ulang halaman.']);
        }
        $deletingIds = $existing->keys()->diff($submittedIds)->all();

        $groupKeys = [];
        $usedGroupKeys = [];
        foreach ($submitted as $position => $row) {
            $groupName = filled($row['group_name'] ?? null) ? trim($row['group_name']) : null;
            $groupIdentity = Str::lower($groupName ?? '__ungrouped__');
            $item = filled($row['id'] ?? null) ? $existing->get((int) $row['id']) : null;
            if (! isset($groupKeys[$groupIdentity])) {
                $candidate = $item?->group_key
                    ?? (filled($row['group_key'] ?? null) ? $row['group_key'] : null);
                if (! $candidate || isset($usedGroupKeys[$candidate])) {
                    $candidate = 'group-'.Str::uuid();
                }
                $groupKeys[$groupIdentity] = $candidate;
                $usedGroupKeys[$candidate] = true;
            }
            $values = [
                'label' => trim($row['label']),
                'group_key' => $groupKeys[$groupIdentity],
                'group_name' => $groupName,
                'sort_order' => $position + 1,
            ];
            if ($item) {
                $item->update($values);
            } else {
                $form->items()->create([
                    ...$values,
                    'item_key' => 'item-'.Str::uuid(),
                ]);
            }
        }

        if ($deletingIds !== []) {
            $form->items()->whereIn('id', $deletingIds)->delete();
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'form-inspeksi';
        $slug = $base;
        $suffix = 2;
        while (EquipmentInspectionForm::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function imageColumns(?array $stored): array
    {
        return $stored ? [
            'image_path' => $stored['path'],
            'image_mime_type' => $stored['mime_type'],
            'image_size' => $stored['size'],
            'image_sha256' => $stored['sha256'],
        ] : [];
    }

    private function auditSnapshot(EquipmentInspectionForm $form): array
    {
        $form->loadMissing('items');

        return [
            'name' => $form->name,
            'description' => $form->description,
            'sort_order' => (int) $form->sort_order,
            'image_sha256' => $form->image_sha256,
            'items' => $form->items->map(fn (EquipmentInspectionFormItem $item): array => [
                'item_key' => $item->item_key,
                'label' => $item->label,
                'group_key' => $item->group_key,
                'group_name' => $item->group_name,
                'sort_order' => (int) $item->sort_order,
            ])->values()->all(),
        ];
    }

    private function audit(EquipmentInspectionForm $form, User $actor, string $event, array $metadata = []): void
    {
        $form->logs()->create([
            'actor_user_id' => $actor->id,
            'event' => $event,
            'form_version' => $form->version,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
