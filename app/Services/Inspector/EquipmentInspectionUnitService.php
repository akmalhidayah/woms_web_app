<?php

namespace App\Services\Inspector;

use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionForm;
use App\Models\EquipmentInspectionSignature;
use App\Models\EquipmentInspectionUnit;
use App\Models\EquipmentInspectionUnitLink;
use App\Models\User;
use App\Support\Inspector\EquipmentInspectionSnapshot;
use App\Support\Inspector\EquipmentInspectionUnitImageStorage;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class EquipmentInspectionUnitService
{
    public const DEFAULT_LOCATION = 'Workshop Machine';

    public function __construct(
        private readonly EquipmentInspectionUnitImageStorage $images,
        private readonly EquipmentAssetCodeGenerator $assetCodes,
    ) {}

    public function savePrimaryForForm(?User $actor, EquipmentInspectionForm $existingForm, string $assetCode, ?string $location = null): EquipmentInspectionUnit
    {
        try {
            return DB::transaction(function () use ($actor, $existingForm, $assetCode, $location): EquipmentInspectionUnit {
                $form = EquipmentInspectionForm::query()->whereKey($existingForm->id)->lockForUpdate()->firstOrFail();
                $units = EquipmentInspectionUnit::query()
                    ->where('equipment_inspection_form_id', $form->id)
                    ->lockForUpdate()
                    ->orderBy('id')
                    ->get();

                if ($units->count() > 1) {
                    throw ValidationException::withMessages([
                        'asset_code' => 'Master Peralatan memiliki lebih dari satu unit. Selesaikan konflik unit lama sebelum menggunakan pengelolaan QR sederhana.',
                    ]);
                }

                $code = trim($assetCode);
                $normalizedLocation = filled($location) ? trim($location) : self::DEFAULT_LOCATION;
                $unit = $units->first();
                $codeOwner = EquipmentInspectionUnit::query()
                    ->whereRaw('UPPER(asset_code) = ?', [Str::upper($code)])
                    ->when($unit !== null, fn ($query) => $query->where('id', '!=', $unit->id))
                    ->first();
                if ($codeOwner) {
                    throw ValidationException::withMessages(['asset_code' => 'Kode alat sudah digunakan oleh peralatan lain.']);
                }
                if (! $unit) {
                    $unit = EquipmentInspectionUnit::query()->create([
                        'equipment_inspection_form_id' => $form->id,
                        'asset_code' => $code,
                        'name' => $form->name,
                        'location' => $normalizedLocation,
                        'description' => $form->description,
                        'image_path' => null,
                        'image_mime_type' => null,
                        'image_size' => null,
                        'image_sha256' => null,
                        'public_code' => $this->uniquePublicCode(),
                        'lifecycle_status' => EquipmentInspectionUnit::STATUS_ACTIVE,
                        'public_enabled' => false,
                        'sort_order' => $form->sort_order,
                        'created_by' => $actor?->id,
                        'updated_by' => $actor?->id,
                    ]);
                    $this->audit($unit, $actor, 'created_from_master_form', ['state' => $this->auditSnapshot($unit)]);

                    return $unit;
                }

                $before = $this->auditSnapshot($unit);
                if ($unit->asset_code !== $code || $unit->name !== $form->name || $unit->location !== $normalizedLocation) {
                    $unit->forceFill([
                        'asset_code' => $code,
                        'name' => $form->name,
                        'location' => $normalizedLocation,
                        'updated_by' => $actor?->id,
                    ])->save();
                    $this->audit($unit, $actor, 'updated_from_master_form', [
                        'before' => $before,
                        'after' => $this->auditSnapshot($unit),
                    ]);
                }

                return $unit;
            });
        } catch (QueryException $exception) {
            $driverCode = (string) ($exception->errorInfo[0] ?? $exception->getCode());
            $vendorCode = (int) ($exception->errorInfo[1] ?? 0);
            if (in_array($driverCode, ['23000', '23505'], true) || $vendorCode === 1062) {
                throw ValidationException::withMessages([
                    'asset_code' => 'Kode alat sudah digunakan atau penyimpanan bersamaan terdeteksi. Muat ulang lalu coba lagi.',
                ]);
            }

            throw $exception;
        }
    }

    public function createAutomaticForForm(User $actor, EquipmentInspectionForm $existingForm): EquipmentInspectionUnit
    {
        return DB::transaction(function () use ($actor, $existingForm): EquipmentInspectionUnit {
            $form = EquipmentInspectionForm::query()->whereKey($existingForm->id)->lockForUpdate()->firstOrFail();
            $units = EquipmentInspectionUnit::query()
                ->where('equipment_inspection_form_id', $form->id)
                ->lockForUpdate()
                ->orderBy('id')
                ->limit(2)
                ->get();

            if ($units->count() > 1) {
                throw ValidationException::withMessages([
                    'name' => 'Master Peralatan memiliki lebih dari satu unit sehingga kode otomatis tidak dapat ditentukan.',
                ]);
            }
            if ($units->count() === 1) {
                return $units->first();
            }

            $unit = EquipmentInspectionUnit::query()->create([
                'equipment_inspection_form_id' => $form->id,
                'asset_code' => $this->assetCodes->nextForName($form->name),
                'name' => $form->name,
                'location' => self::DEFAULT_LOCATION,
                'description' => $form->description,
                'image_path' => null,
                'image_mime_type' => null,
                'image_size' => null,
                'image_sha256' => null,
                'public_code' => $this->uniquePublicCode(),
                'lifecycle_status' => EquipmentInspectionUnit::STATUS_ACTIVE,
                'public_enabled' => false,
                'sort_order' => $form->sort_order,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $this->audit($unit, $actor, 'created_automatically_from_master_form', [
                'state' => $this->auditSnapshot($unit),
            ]);

            return $unit;
        });
    }

    public function create(User $actor, array $data, ?UploadedFile $image): EquipmentInspectionUnit
    {
        $stored = $image ? $this->images->store($image) : null;
        try {
            return DB::transaction(function () use ($actor, $data, $stored): EquipmentInspectionUnit {
                $status = $data['lifecycle_status'];
                $unit = EquipmentInspectionUnit::query()->create([
                    ...$this->columns($data),
                    'public_code' => $this->uniquePublicCode(),
                    'public_enabled' => $status === EquipmentInspectionUnit::STATUS_RETIRED ? false : (bool) $data['public_enabled'],
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                    ...$this->imageColumns($stored),
                ]);
                $this->audit($unit, $actor, 'created', ['state' => $this->auditSnapshot($unit)]);

                return $unit;
            });
        } catch (Throwable $exception) {
            $this->images->cleanup($stored['path'] ?? null);
            throw $exception;
        }
    }

    public function update(User $actor, EquipmentInspectionUnit $existing, array $data, ?UploadedFile $image): EquipmentInspectionUnit
    {
        $stored = $image ? $this->images->store($image) : null;
        $oldPath = null;
        try {
            $unit = DB::transaction(function () use ($actor, $existing, $data, $stored, &$oldPath): EquipmentInspectionUnit {
                $unit = EquipmentInspectionUnit::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
                $before = $this->auditSnapshot($unit);
                $hasHistory = $unit->links()->exists();
                if ($hasHistory && ((int) $data['equipment_inspection_form_id'] !== (int) $unit->equipment_inspection_form_id
                    || $data['asset_code'] !== $unit->asset_code)) {
                    throw ValidationException::withMessages([
                        'asset_code' => 'Master Peralatan dan kode unit tidak dapat diubah setelah unit memiliki riwayat inspeksi.',
                    ]);
                }

                $removeImage = (bool) $data['remove_image'] && ! $stored;
                if ($stored || $removeImage) {
                    $oldPath = $unit->image_path;
                }
                $status = $data['lifecycle_status'];
                $unit->forceFill([
                    ...$this->columns($data),
                    'public_enabled' => $status === EquipmentInspectionUnit::STATUS_RETIRED ? false : (bool) $data['public_enabled'],
                    'updated_by' => $actor->id,
                    ...($stored ? $this->imageColumns($stored) : []),
                    ...($removeImage ? $this->emptyImageColumns() : []),
                ])->save();
                $this->audit($unit, $actor, 'updated', [
                    'before' => $before,
                    'after' => $this->auditSnapshot($unit),
                ]);

                return $unit;
            });
        } catch (Throwable $exception) {
            $this->images->cleanup($stored['path'] ?? null);
            throw $exception;
        }

        $this->images->cleanup($oldPath);

        return $unit;
    }

    public function setPublicEnabled(User $actor, EquipmentInspectionUnit $existing, bool $enabled): EquipmentInspectionUnit
    {
        return DB::transaction(function () use ($actor, $existing, $enabled): EquipmentInspectionUnit {
            $unit = EquipmentInspectionUnit::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
            if ($enabled && $unit->lifecycle_status === EquipmentInspectionUnit::STATUS_RETIRED) {
                throw ValidationException::withMessages(['unit' => 'Unit retired tidak dapat dipublikasikan.']);
            }
            if ((bool) $unit->public_enabled !== $enabled) {
                $unit->forceFill(['public_enabled' => $enabled, 'updated_by' => $actor->id])->save();
                $this->audit($unit, $actor, $enabled ? 'public_enabled' : 'public_disabled');
            }

            return $unit;
        });
    }

    public function retire(User $actor, EquipmentInspectionUnit $existing): EquipmentInspectionUnit
    {
        return DB::transaction(function () use ($actor, $existing): EquipmentInspectionUnit {
            $unit = EquipmentInspectionUnit::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
            if ($unit->lifecycle_status !== EquipmentInspectionUnit::STATUS_RETIRED || $unit->public_enabled) {
                $unit->forceFill([
                    'lifecycle_status' => EquipmentInspectionUnit::STATUS_RETIRED,
                    'public_enabled' => false,
                    'updated_by' => $actor->id,
                ])->save();
                $this->audit($unit, $actor, 'retired');
            }

            return $unit;
        });
    }

    /**
     * @return array{unit: EquipmentInspectionUnit, previous_code: string}
     */
    public function rotatePublicCode(User $actor, EquipmentInspectionUnit $existing): array
    {
        return DB::transaction(function () use ($actor, $existing): array {
            $unit = EquipmentInspectionUnit::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
            $previous = $unit->public_code;
            $unit->forceFill(['public_code' => $this->uniquePublicCode(), 'updated_by' => $actor->id])->save();
            $this->audit($unit, $actor, 'public_code_rotated', [
                'previous_code_fingerprint' => substr(hash('sha256', $previous), 0, 16),
            ]);

            return ['unit' => $unit, 'previous_code' => $previous];
        });
    }

    public function resolveForInspection(int $formId, ?int $requestedUnitId, bool $required, bool $isNew): ?int
    {
        $unitIds = EquipmentInspectionUnit::query()
            ->where('equipment_inspection_form_id', $formId)
            ->active()
            ->lockForUpdate()
            ->orderBy('id')
            ->limit(2)
            ->pluck('id');

        if ($unitIds->count() > 1) {
            if ($isNew || $requestedUnitId !== null || $required) {
                throw ValidationException::withMessages([
                    'equipment_inspection_unit_id' => 'Terdapat lebih dari satu unit aktif pada Master Peralatan ini. Hubungi Admin untuk menyelesaikan konflik unit.',
                ]);
            }

            return null;
        }
        if ($requestedUnitId !== null) {
            return $requestedUnitId;
        }
        if ($unitIds->count() === 1) {
            return (int) $unitIds->first();
        }
        if ($required && $unitIds->isEmpty()) {
            throw ValidationException::withMessages([
                'equipment_inspection_unit_id' => 'Kode alat dan QR belum diatur. Hubungi Admin untuk mengatur QR pada Master Peralatan ini.',
            ]);
        }
        return null;
    }

    public function assignDirect(
        User $actor,
        EquipmentInspection $inspection,
        int $formId,
        ?int $unitId,
    ): ?EquipmentInspectionUnitLink {
        $link = $inspection->unitLink()->lockForUpdate()->first();
        if ($unitId === null) {
            if ($link) {
                throw ValidationException::withMessages(['equipment_inspection_unit_id' => 'Unit yang sudah dipilih tidak dapat dikosongkan.']);
            }

            return null;
        }

        $unit = EquipmentInspectionUnit::query()->whereKey($unitId)->lockForUpdate()->first();
        if (! $unit || (int) $unit->equipment_inspection_form_id !== $formId) {
            throw ValidationException::withMessages(['equipment_inspection_unit_id' => 'Unit tidak sesuai dengan Master Peralatan.']);
        }
        $sameUnit = $link && (int) $link->equipment_inspection_unit_id === (int) $unit->id;
        if ($sameUnit) {
            if ($link->link_source === EquipmentInspectionUnitLink::SOURCE_DIRECT
                || $inspection->document_no || $inspection->signed_at || $inspection->signatures()->exists()) {
                return $link;
            }
        }
        if (! $sameUnit && $unit->lifecycle_status !== EquipmentInspectionUnit::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['equipment_inspection_unit_id' => 'Hanya unit aktif yang dapat dipilih untuk pemeriksaan baru.']);
        }
        if ($link && ($inspection->signed_at || $inspection->document_no || $inspection->signatures()->exists())) {
            throw ValidationException::withMessages(['equipment_inspection_unit_id' => 'Identitas unit terkunci setelah laporan pernah ditandatangani.']);
        }

        $before = $link ? [
            'unit_id' => (int) $link->equipment_inspection_unit_id,
            'asset_code' => $link->unit_code_snapshot,
            'name' => $link->unit_name_snapshot,
        ] : null;
        $unit->loadMissing('form');
        $unitName = $unit->form?->name ?: $unit->name;
        $values = [
            'equipment_inspection_unit_id' => $unit->id,
            'link_source' => EquipmentInspectionUnitLink::SOURCE_DIRECT,
            'unit_code_snapshot' => $unit->asset_code,
            'unit_name_snapshot' => $unitName,
            'linked_by' => $actor->id,
            'linked_at' => now(),
        ];
        if ($link) {
            $link->update($values);
        } else {
            $link = $inspection->unitLink()->create($values);
        }
        $inspection->logs()->create([
            'actor_user_id' => $actor->id,
            'event' => $before ? 'unit_changed' : 'unit_linked',
            'document_version' => $inspection->document_version,
            'lock_version' => $inspection->lock_version,
            'metadata' => [
                'source' => EquipmentInspectionUnitLink::SOURCE_DIRECT,
                'before' => $before,
                'after' => ['unit_id' => (int) $unit->id, 'asset_code' => $unit->asset_code, 'name' => $unitName],
            ],
            'created_at' => now(),
        ]);
        $this->audit($unit, $actor, 'inspection_linked_direct', ['inspection_public_id' => $inspection->public_id]);

        return $link;
    }

    public function linkLegacy(User $actor, EquipmentInspectionUnit $existing, EquipmentInspection $inspection, ?string $reason): EquipmentInspectionUnitLink
    {
        return DB::transaction(function () use ($actor, $existing, $inspection, $reason): EquipmentInspectionUnitLink {
            $unit = EquipmentInspectionUnit::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
            $document = EquipmentInspection::withTrashed()->whereKey($inspection->id)->lockForUpdate()->firstOrFail();
            $current = $document->unitLink()->lockForUpdate()->first();
            if ($current) {
                if ((int) $current->equipment_inspection_unit_id === (int) $unit->id) {
                    return $current;
                }
                throw ValidationException::withMessages(['inspection_id' => 'Dokumen sudah terhubung ke unit lain dan tidak dapat dipindahkan melalui mapping biasa.']);
            }
            if ($error = $this->legacyLinkValidationError($unit, $document)) {
                throw ValidationException::withMessages(['inspection_id' => $error]);
            }

            $unit->loadMissing('form');
            $unitName = $unit->form?->name ?: $unit->name;
            $link = $document->unitLink()->create([
                'equipment_inspection_unit_id' => $unit->id,
                'link_source' => EquipmentInspectionUnitLink::SOURCE_ADMIN_LEGACY,
                'unit_code_snapshot' => $unit->asset_code,
                'unit_name_snapshot' => $unitName,
                'linked_by' => $actor->id,
                'linked_at' => now(),
            ]);
            $metadata = [
                'source' => EquipmentInspectionUnitLink::SOURCE_ADMIN_LEGACY,
                'unit_id' => (int) $unit->id,
                'unit_code' => $unit->asset_code,
                'reason' => filled($reason) ? trim((string) $reason) : null,
            ];
            $document->logs()->create([
                'actor_user_id' => $actor->id,
                'event' => 'unit_linked_legacy',
                'document_version' => $document->document_version,
                'lock_version' => $document->lock_version,
                'metadata' => $metadata,
                'created_at' => now(),
            ]);
            $this->audit($unit, $actor, 'inspection_linked_legacy', [
                'inspection_public_id' => $document->public_id,
                'document_no' => $document->document_no,
                'reason' => $metadata['reason'],
            ]);

            return $link;
        });
    }

    public function legacyLinkValidationError(EquipmentInspectionUnit $unit, EquipmentInspection $document): ?string
    {
        if ($document->trashed()) {
            return 'Dokumen yang sudah dihapus tidak dapat dihubungkan ke QR publik.';
        }
        if ($document->status !== EquipmentInspection::STATUS_APPROVED) {
            return 'Hanya dokumen approved yang dapat dihubungkan sebagai histori QR.';
        }
        $unit->loadMissing('form');
        if ($document->form_slug !== $unit->form?->slug) {
            return 'Master Peralatan dokumen tidak sesuai dengan unit tujuan.';
        }

        $signatures = ($document->relationLoaded('signatures')
            ? $document->signatures
                ->where('document_version', $document->document_version)
                ->whereIn('role_key', [EquipmentInspectionSignature::ROLE_INSPECTOR, EquipmentInspectionSignature::ROLE_MANAGER])
            : $document->signatures()
                ->where('document_version', $document->document_version)
                ->whereIn('role_key', [EquipmentInspectionSignature::ROLE_INSPECTOR, EquipmentInspectionSignature::ROLE_MANAGER])
                ->get())
            ->keyBy('role_key');
        $hashes = [];
        foreach ([EquipmentInspectionSignature::ROLE_INSPECTOR, EquipmentInspectionSignature::ROLE_MANAGER] as $role) {
            $signature = $signatures->get($role);
            if (! $signature || ! $signature->signed_at || ! is_string($signature->content_hash)
                || ! is_array($signature->signed_payload)
                || (int) ($signature->signed_payload['document_version'] ?? 0) !== (int) $document->document_version
                || ! hash_equals($signature->content_hash, EquipmentInspectionSnapshot::hash($signature->signed_payload))) {
                return 'Dokumen belum memiliki signature Inspector dan Manager yang valid untuk versi approved saat ini.';
            }
            $hashes[] = $signature->content_hash;
        }
        if (! hash_equals($hashes[0], $hashes[1])) {
            return 'Snapshot signature Inspector dan Manager tidak konsisten.';
        }

        return null;
    }

    private function uniquePublicCode(): string
    {
        do {
            $code = Str::lower(Str::random(32));
        } while (EquipmentInspectionUnit::query()->where('public_code', $code)->exists());

        return $code;
    }

    private function columns(array $data): array
    {
        return [
            'equipment_inspection_form_id' => (int) $data['equipment_inspection_form_id'],
            'asset_code' => $data['asset_code'],
            'name' => $data['name'],
            'location' => filled($data['location'] ?? null) ? $data['location'] : null,
            'description' => filled($data['description'] ?? null) ? $data['description'] : null,
            'lifecycle_status' => $data['lifecycle_status'],
            'sort_order' => (int) $data['sort_order'],
        ];
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

    private function emptyImageColumns(): array
    {
        return ['image_path' => null, 'image_mime_type' => null, 'image_size' => null, 'image_sha256' => null];
    }

    private function auditSnapshot(EquipmentInspectionUnit $unit): array
    {
        return [
            'form_id' => (int) $unit->equipment_inspection_form_id,
            'asset_code' => $unit->asset_code,
            'name' => $unit->name,
            'location' => $unit->location,
            'description' => $unit->description,
            'image_sha256' => $unit->image_sha256,
            'lifecycle_status' => $unit->lifecycle_status,
            'public_enabled' => (bool) $unit->public_enabled,
            'sort_order' => (int) $unit->sort_order,
        ];
    }

    private function audit(EquipmentInspectionUnit $unit, ?User $actor, string $event, array $metadata = []): void
    {
        $unit->logs()->create([
            'actor_user_id' => $actor?->id,
            'event' => $event,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
