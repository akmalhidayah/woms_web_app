<?php

namespace App\Support;

use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\InitialWorkSignature;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\QualityControlSignature;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ApprovalDocumentInbox
{
    /**
     * @var array<string, string>
     */
    public const TYPE_LABELS = [
        'hpp' => 'HPP',
        'bast' => 'BAST',
        'initial_work' => 'Initial Work',
        'quality_control' => 'Quality Control',
    ];

    public static function hasPendingFor(?User $user): bool
    {
        if ($user?->role !== User::ROLE_APPROVER) {
            return false;
        }

        return self::pendingHppQuery($user)->exists()
            || self::pendingBastQuery($user)->exists()
            || self::pendingInitialWorkQuery($user)->exists()
            || self::pendingQualityControlQuery($user)->exists();
    }

    public static function pendingCountFor(?User $user): int
    {
        if ($user?->role !== User::ROLE_APPROVER) {
            return 0;
        }

        return self::pendingDocumentsFor($user, null)->count();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function pendingPreviewFor(?User $user, int $limit = 5): Collection
    {
        if ($user?->role !== User::ROLE_APPROVER) {
            return collect();
        }

        return self::pendingDocumentsFor($user, null)
            ->take($limit)
            ->map(fn (array $item): array => $item + [
                'open_url' => route('approval-documents.open', [
                    'type' => $item['type'],
                    'id' => $item['id'],
                ]),
            ])
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function pendingDocumentsFor(User $user, ?string $type): Collection
    {
        $documents = collect();

        if ($type === null || $type === 'hpp') {
            $documents = $documents->merge(self::pendingHpp($user));
        }

        if ($type === null || $type === 'bast') {
            $documents = $documents->merge(self::pendingBast($user));
        }

        if ($type === null || $type === 'initial_work') {
            $documents = $documents->merge(self::pendingInitialWork($user));
        }

        if ($type === null || $type === 'quality_control') {
            $documents = $documents->merge(self::pendingQualityControl($user));
        }

        return $documents
            ->sortByDesc(fn (array $item): int => $item['submitted_at']?->getTimestamp() ?? 0)
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $documents
     * @return array<string, string>
     */
    public static function availableTypeLabels(Collection $documents): array
    {
        $availableTypes = array_fill_keys($documents->pluck('type')->unique()->all(), true);

        return collect(self::TYPE_LABELS)
            ->filter(fn (string $label, string $type): bool => isset($availableTypes[$type]))
            ->all();
    }

    public static function normalizeType(?string $type): ?string
    {
        $type = trim((string) $type);

        return array_key_exists($type, self::TYPE_LABELS) ? $type : null;
    }

    public static function findPendingSignatureFor(string $type, int $id, User $user): ?Model
    {
        return match ($type) {
            'hpp' => self::pendingHppQuery($user)->whereKey($id)->first(),
            'bast' => self::pendingBastQuery($user)->whereKey($id)->first(),
            'initial_work' => self::pendingInitialWorkQuery($user)->whereKey($id)->first(),
            'quality_control' => self::pendingQualityControlQuery($user)->whereKey($id)->first(),
        };
    }

    public static function tokenFor(string $type, Model $signature): ?string
    {
        return match ($type) {
            'hpp', 'bast' => (string) $signature->token,
            'initial_work', 'quality_control' => (string) $signature->token_encrypted,
        };
    }

    public static function publicRouteName(string $type): string
    {
        return match ($type) {
            'hpp' => 'approval.hpp.show',
            'bast' => 'approval.bast.show',
            'initial_work' => 'approval.initial-work.show',
            'quality_control' => 'approval.quality-control.show',
        };
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function pendingHpp(User $user): Collection
    {
        return self::pendingHppQuery($user)
            ->with(['hpp'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (HppSignature $signature): array => [
                'type' => 'hpp',
                'type_label' => self::TYPE_LABELS['hpp'],
                'id' => $signature->id,
                'number' => $signature->hpp?->nomor_order ?: '-',
                'title' => $signature->hpp?->nama_pekerjaan ?: '-',
                'step' => $signature->displayRoleLabel(),
                'submitted_at' => $signature->hpp?->submitted_at ?: $signature->created_at,
                'status' => 'Menunggu Tanda Tangan',
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function pendingBast(User $user): Collection
    {
        return self::pendingBastQuery($user)
            ->with(['lhppBast.garansi'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (LhppBastSignature $signature): array => [
                'type' => 'bast',
                'type_label' => self::TYPE_LABELS['bast'],
                'id' => $signature->id,
                'number' => self::bastNumber($signature->lhppBast),
                'title' => $signature->lhppBast?->deskripsi_pekerjaan ?: '-',
                'step' => $signature->displayRoleLabel(),
                'submitted_at' => $signature->lhppBast?->updated_at ?: $signature->created_at,
                'status' => 'Menunggu Tanda Tangan',
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function pendingInitialWork(User $user): Collection
    {
        return self::pendingInitialWorkQuery($user)
            ->with(['initialWork.order'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (InitialWorkSignature $signature): array => [
                'type' => 'initial_work',
                'type_label' => self::TYPE_LABELS['initial_work'],
                'id' => $signature->id,
                'number' => $signature->initialWork?->nomor_initial_work ?: ($signature->initialWork?->nomor_order ?: '-'),
                'title' => $signature->initialWork?->nama_pekerjaan ?: ($signature->initialWork?->order?->nama_pekerjaan ?: '-'),
                'step' => $signature->displayRoleLabel(),
                'submitted_at' => $signature->initialWork?->tanggal_initial_work ?: $signature->created_at,
                'status' => 'Menunggu Tanda Tangan',
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function pendingQualityControl(User $user): Collection
    {
        return self::pendingQualityControlQuery($user)
            ->with(['qualityControlReport.order'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (QualityControlSignature $signature): array => [
                'type' => 'quality_control',
                'type_label' => self::TYPE_LABELS['quality_control'],
                'id' => $signature->id,
                'number' => $signature->qualityControlReport?->report_no ?: ($signature->qualityControlReport?->order?->nomor_order ?: '-'),
                'title' => $signature->qualityControlReport?->order?->nama_pekerjaan ?: '-',
                'step' => $signature->displayRoleLabel(),
                'submitted_at' => $signature->qualityControlReport?->report_date ?: $signature->created_at,
                'status' => 'Menunggu Tanda Tangan',
            ]);
    }

    private static function pendingHppQuery(User $user): Builder
    {
        return HppSignature::query()
            ->where('signer_user_id', $user->id)
            ->where('status', HppSignature::STATUS_PENDING)
            ->whereNotNull('token')
            ->where(function (Builder $query): void {
                $query->whereNull('token_expires_at')->orWhere('token_expires_at', '>', now());
            })
            ->whereHas('hpp', fn (Builder $query): Builder => $query->where('status', '!=', Hpp::STATUS_REJECTED));
    }

    private static function pendingBastQuery(User $user): Builder
    {
        return LhppBastSignature::query()
            ->where('signer_user_id', $user->id)
            ->where('status', LhppBastSignature::STATUS_PENDING)
            ->whereNotNull('token')
            ->where(function (Builder $query): void {
                $query->whereNull('token_expires_at')->orWhere('token_expires_at', '>', now());
            })
            ->whereHas('lhppBast', fn (Builder $query): Builder => $query->where('approval_status', '!=', LhppBast::APPROVAL_REJECTED));
    }

    private static function pendingInitialWorkQuery(User $user): Builder
    {
        return InitialWorkSignature::query()
            ->where('signer_user_id', $user->id)
            ->where('status', InitialWorkSignature::STATUS_PENDING)
            ->whereNotNull('token_encrypted')
            ->where(function (Builder $query): void {
                $query->whereNull('token_expires_at')->orWhere('token_expires_at', '>', now());
            });
    }

    private static function pendingQualityControlQuery(User $user): Builder
    {
        return QualityControlSignature::query()
            ->where('signer_user_id', $user->id)
            ->where('status', QualityControlSignature::STATUS_PENDING)
            ->whereNotNull('token_encrypted')
            ->where(function (Builder $query): void {
                $query->whereNull('token_expires_at')->orWhere('token_expires_at', '>', now());
            });
    }

    private static function bastNumber(?LhppBast $lhpp): string
    {
        $number = (string) ($lhpp?->nomor_order ?: '-');
        if (
            $lhpp?->termin_type === 'termin_1'
            && (int) ($lhpp->garansi?->garansi_months ?? -1) === 0
        ) {
            return $number;
        }

        $termin = match ($lhpp?->termin_type) {
            'termin_2' => 'Termin 2',
            'termin_1' => 'Termin 1',
            default => '',
        };

        return trim($number.' '.$termin);
    }
}
