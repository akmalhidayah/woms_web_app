<?php

namespace App\Services\Approvals;

use App\Models\HppSignature;
use App\Models\InitialWorkSignature;
use App\Models\LhppBastSignature;
use App\Models\QualityControlSignature;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApprovalSignatureReassignmentService
{
    public function __construct(
        private readonly ApprovalNotificationService $notificationService,
    ) {}

    public function reassign(
        InitialWorkSignature|HppSignature|LhppBastSignature|QualityControlSignature $signature,
        User $newSigner,
        User $delegatedBy,
        string $reason,
        bool $sendEmail = true,
    ): InitialWorkSignature|HppSignature|LhppBastSignature|QualityControlSignature {
        return DB::transaction(function () use ($signature, $newSigner, $delegatedBy, $reason, $sendEmail) {
            $lockedSignature = $signature::query()
                ->whereKey($signature->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertReassignable($lockedSignature);

            $previousSigner = $lockedSignature->signer;
            $status = $this->resolveStatusAfterReassign($lockedSignature);
            $actingAsLabel = 'PLT '.$lockedSignature->role_label;

            $lockedSignature->update([
                'acting_as_label' => $actingAsLabel,
                'signer_user_id' => $newSigner->id,
                ...$this->signerSnapshotAttributes($lockedSignature, $newSigner, $actingAsLabel),
                'delegated_from_user_id' => $previousSigner?->id,
                'delegated_from_name' => $this->signatureSignerName($lockedSignature) ?: $previousSigner?->name,
                'delegated_by_user_id' => $delegatedBy->id,
                'delegated_at' => now(),
                'delegation_reason' => $reason,
                'status' => $status,
                ...$this->emptyTokenAttributes($lockedSignature),
            ]);

            $freshSignature = $lockedSignature->fresh('signer');

            if ($this->isPending($freshSignature)) {
                $this->issueToken($freshSignature, $sendEmail);
                $freshSignature = $freshSignature->fresh('signer');
            }

            return $freshSignature;
        });
    }

    private function assertReassignable(Model $signature): void
    {
        if ($this->isSigned($signature) || $this->isSkipped($signature)) {
            throw ValidationException::withMessages([
                'signature' => 'Approver yang sudah TTD atau dilewati tidak dapat dialihkan.',
            ]);
        }
    }

    private function resolveStatusAfterReassign(Model $signature): string
    {
        if ($this->isPending($signature)) {
            return (string) $signature->status;
        }

        $previousUnsignedExists = $signature::query()
            ->where($this->parentKeyName($signature), $signature->{$this->parentKeyName($signature)})
            ->where('step_order', '<', $signature->step_order)
            ->whereNotIn('status', $this->completedStatuses($signature))
            ->exists();

        return $previousUnsignedExists
            ? $this->lockedStatus($signature)
            : $this->pendingStatus($signature);
    }

    /**
     * @return array<string, mixed>
     */
    private function signerSnapshotAttributes(Model $signature, User $newSigner, string $actingAsLabel): array
    {
        if ($signature instanceof InitialWorkSignature || $signature instanceof QualityControlSignature) {
            return [
                'signer_name' => $newSigner->name,
                'signer_position' => $actingAsLabel,
            ];
        }

        return [
            'signer_name_snapshot' => $newSigner->name,
            'signer_position_snapshot' => $actingAsLabel,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyTokenAttributes(Model $signature): array
    {
        if ($signature instanceof InitialWorkSignature || $signature instanceof QualityControlSignature) {
            return [
                'token_hash' => null,
                'token_encrypted' => null,
                'token_expires_at' => null,
            ];
        }

        return [
            'token' => null,
            'token_hash' => null,
            'token_expires_at' => null,
        ];
    }

    private function issueToken(Model $signature, bool $sendEmail): void
    {
        $token = Str::random(64);
        $expiresAt = now()->addDays($this->tokenTtlDays($signature));

        if ($signature instanceof InitialWorkSignature || $signature instanceof QualityControlSignature) {
            $signature->update([
                'token_hash' => hash('sha256', $token),
                'token_encrypted' => $token,
                'token_expires_at' => $expiresAt,
            ]);
        } else {
            $signature->update([
                'token' => $token,
                'token_hash' => hash('sha256', $token),
                'token_expires_at' => $expiresAt,
            ]);
        }

        if (! $sendEmail) {
            return;
        }

        DB::afterCommit(function () use ($signature): void {
            $freshSignature = $signature->fresh('signer');

            match (true) {
                $freshSignature instanceof InitialWorkSignature => $this->notificationService->sendInitialWork($freshSignature),
                $freshSignature instanceof QualityControlSignature => $this->notificationService->sendQualityControl($freshSignature),
                $freshSignature instanceof HppSignature => $this->notificationService->sendHpp($freshSignature),
                $freshSignature instanceof LhppBastSignature => $this->notificationService->sendBast($freshSignature),
                default => null,
            };
        });
    }

    private function signatureSignerName(Model $signature): ?string
    {
        if ($signature instanceof InitialWorkSignature || $signature instanceof QualityControlSignature) {
            return $signature->signer_name;
        }

        return $signature->signer_name_snapshot;
    }

    private function parentKeyName(Model $signature): string
    {
        return match (true) {
            $signature instanceof InitialWorkSignature => 'initial_work_id',
            $signature instanceof QualityControlSignature => 'quality_control_report_id',
            $signature instanceof HppSignature => 'hpp_id',
            $signature instanceof LhppBastSignature => 'lhpp_bast_id',
            default => throw new \InvalidArgumentException('Unsupported signature model.'),
        };
    }

    /**
     * @return list<string>
     */
    private function completedStatuses(Model $signature): array
    {
        return $signature instanceof HppSignature || $signature instanceof LhppBastSignature
            ? ['signed', 'skipped']
            : ['signed'];
    }

    private function pendingStatus(Model $signature): string
    {
        return match (true) {
            $signature instanceof InitialWorkSignature => InitialWorkSignature::STATUS_PENDING,
            $signature instanceof QualityControlSignature => QualityControlSignature::STATUS_PENDING,
            $signature instanceof HppSignature => HppSignature::STATUS_PENDING,
            $signature instanceof LhppBastSignature => LhppBastSignature::STATUS_PENDING,
            default => 'pending',
        };
    }

    private function lockedStatus(Model $signature): string
    {
        return match (true) {
            $signature instanceof InitialWorkSignature => InitialWorkSignature::STATUS_LOCKED,
            $signature instanceof QualityControlSignature => QualityControlSignature::STATUS_LOCKED,
            $signature instanceof HppSignature => HppSignature::STATUS_LOCKED,
            $signature instanceof LhppBastSignature => LhppBastSignature::STATUS_LOCKED,
            default => 'locked',
        };
    }

    private function tokenTtlDays(Model $signature): int
    {
        return $signature instanceof InitialWorkSignature || $signature instanceof QualityControlSignature ? 7 : 14;
    }

    private function isPending(Model $signature): bool
    {
        return method_exists($signature, 'isPending') && $signature->isPending();
    }

    private function isSigned(Model $signature): bool
    {
        return method_exists($signature, 'isSigned') && $signature->isSigned();
    }

    private function isSkipped(Model $signature): bool
    {
        return method_exists($signature, 'isSkipped') && $signature->isSkipped();
    }
}
