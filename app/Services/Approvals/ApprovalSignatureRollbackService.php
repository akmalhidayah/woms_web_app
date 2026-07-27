<?php

namespace App\Services\Approvals;

use App\Models\ApprovalSignatureRollback;
use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\User;
use App\Support\BastApprovalSignatureBuilder;
use App\Support\HppApprovalSignatureBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ApprovalSignatureRollbackService
{
    public function __construct(
        private readonly HppApprovalSignatureBuilder $hppSignatureBuilder,
        private readonly BastApprovalSignatureBuilder $bastSignatureBuilder,
        private readonly BastSmPengendaliSynchronizer $bastSmSynchronizer,
    ) {}

    public function rollbackHpp(
        Hpp $hpp,
        HppSignature $signature,
        User $rollbackBy,
        string $reason,
        bool $sendEmail = false,
    ): HppSignature {
        return DB::transaction(function () use ($hpp, $signature, $rollbackBy, $reason, $sendEmail): HppSignature {
            $lockedHpp = Hpp::query()
                ->whereKey($hpp->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSignature = HppSignature::query()
                ->where('hpp_id', $lockedHpp->id)
                ->whereKey($signature->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertRollbackableSignature($lockedSignature, 'HPP');
            $this->assertHppRollbackAllowed($lockedHpp);

            $affectedSignatures = HppSignature::query()
                ->where('hpp_id', $lockedHpp->id)
                ->where('step_order', '>=', $lockedSignature->step_order)
                ->orderBy('step_order')
                ->lockForUpdate()
                ->get();

            $this->createAudit(
                documentType: 'hpp',
                documentId: $lockedHpp->id,
                signature: $lockedSignature,
                rollbackBy: $rollbackBy,
                reason: $reason,
                affectedSignatures: $affectedSignatures,
            );

            $this->deleteSignatureFilesAfterCommit($affectedSignatures);
            $this->resetSignatures($affectedSignatures, HppSignature::STATUS_LOCKED);

            $lockedSignature = $lockedSignature->fresh();
            $lockedSignature->update([
                'status' => HppSignature::STATUS_PENDING,
            ]);

            $lockedHpp->update([
                'status' => Hpp::STATUS_IN_REVIEW,
            ]);

            $this->hppSignatureBuilder->issueToken($lockedSignature->fresh(), $sendEmail);

            return $lockedSignature->fresh('signer');
        });
    }

    public function rollbackBast(
        LhppBast $lhppBast,
        LhppBastSignature $signature,
        User $rollbackBy,
        string $reason,
        bool $sendEmail = false,
    ): LhppBastSignature {
        return DB::transaction(function () use ($lhppBast, $signature, $rollbackBy, $reason, $sendEmail): LhppBastSignature {
            $lockedBast = LhppBast::query()
                ->whereKey($lhppBast->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->bastSmSynchronizer->sync($lockedBast);

            $clickedSignature = LhppBastSignature::query()
                ->where('lhpp_bast_id', $lockedBast->id)
                ->whereKey($signature->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertRollbackableSignature($clickedSignature, 'BAST/LHPP');
            $this->assertBastRollbackAllowed($lockedBast);

            $allSignatures = LhppBastSignature::query()
                ->where('lhpp_bast_id', $lockedBast->id)
                ->orderBy('step_order')
                ->lockForUpdate()
                ->get();
            $effectiveStartSignature = $allSignatures
                ->where('step_order', '<=', $clickedSignature->step_order)
                ->first(fn (LhppBastSignature $item): bool => ! $item->isSigned() && ! $item->isSkipped())
                ?: $clickedSignature;

            $affectedSignatures = LhppBastSignature::query()
                ->where('lhpp_bast_id', $lockedBast->id)
                ->where('step_order', '>=', $effectiveStartSignature->step_order)
                ->orderBy('step_order')
                ->lockForUpdate()
                ->get();

            $this->createAudit(
                documentType: 'bast_lhpp',
                documentId: $lockedBast->id,
                signature: $clickedSignature,
                rollbackBy: $rollbackBy,
                reason: $reason,
                affectedSignatures: $affectedSignatures,
            );

            $this->deleteSignatureFilesAfterCommit($affectedSignatures);
            $this->resetSignatures($affectedSignatures, LhppBastSignature::STATUS_LOCKED);

            $effectiveStartSignature = $effectiveStartSignature->fresh();
            $effectiveStartSignature->update([
                'status' => LhppBastSignature::STATUS_PENDING,
            ]);

            $lockedBast->update([
                'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            ]);

            $this->bastSignatureBuilder->issueToken($effectiveStartSignature->fresh(), $sendEmail);

            return $effectiveStartSignature->fresh('signer');
        });
    }

    private function assertRollbackableSignature(Model $signature, string $documentLabel): void
    {
        if (! method_exists($signature, 'isSigned') || ! $signature->isSigned()) {
            throw ValidationException::withMessages([
                'signature' => "Rollback {$documentLabel} hanya bisa dilakukan pada step yang sudah OK/ditandatangani.",
            ]);
        }
    }

    private function assertHppRollbackAllowed(Hpp $hpp): void
    {
        if ($hpp->status === Hpp::STATUS_REJECTED) {
            throw ValidationException::withMessages([
                'signature' => 'HPP yang sudah rejected tidak dapat di-rollback.',
            ]);
        }

        if ($hpp->budgetVerification()->exists()) {
            throw ValidationException::withMessages([
                'signature' => 'HPP tidak dapat di-rollback karena sudah masuk verifikasi anggaran.',
            ]);
        }

        if ($hpp->purchaseOrder()->exists()) {
            throw ValidationException::withMessages([
                'signature' => 'HPP tidak dapat di-rollback karena sudah memiliki Purchase Order.',
            ]);
        }
    }

    private function assertBastRollbackAllowed(LhppBast $lhppBast): void
    {
        if ($lhppBast->approval_status === LhppBast::APPROVAL_REJECTED) {
            throw ValidationException::withMessages([
                'signature' => 'BAST/LHPP yang sudah rejected tidak dapat di-rollback.',
            ]);
        }

        if ($this->bastTerminPaid($lhppBast)) {
            throw ValidationException::withMessages([
                'signature' => 'BAST/LHPP tidak dapat di-rollback karena status termin sudah dibayar.',
            ]);
        }

        if ($this->bastHasLpjPpl($lhppBast)) {
            throw ValidationException::withMessages([
                'signature' => 'BAST/LHPP tidak dapat di-rollback karena LPJ/PPL sudah diproses.',
            ]);
        }
    }

    private function bastTerminPaid(LhppBast $lhppBast): bool
    {
        return $lhppBast->termin_type === 'termin_2'
            ? ($lhppBast->termin2_status ?? 'belum') === 'sudah'
            : ($lhppBast->termin1_status ?? 'belum') === 'sudah';
    }

    private function bastHasLpjPpl(LhppBast $lhppBast): bool
    {
        $lhppBast->loadMissing(['lpjPpl', 'parentLhppBast.lpjPpl']);

        $lpjPpl = $lhppBast->lpjPpl ?: $lhppBast->parentLhppBast?->lpjPpl;

        if (! $lpjPpl) {
            return false;
        }

        $fields = $lhppBast->termin_type === 'termin_2'
            ? ['lpj_number_termin2', 'ppl_number_termin2', 'lpj_document_path_termin2', 'ppl_document_path_termin2']
            : ['lpj_number_termin1', 'ppl_number_termin1', 'lpj_document_path_termin1', 'ppl_document_path_termin1'];

        foreach ($fields as $field) {
            if (filled($lpjPpl->{$field})) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, HppSignature|LhppBastSignature>  $signatures
     */
    private function resetSignatures(Collection $signatures, string $lockedStatus): void
    {
        foreach ($signatures as $signature) {
            $signature->update([
                'status' => $lockedStatus,
                'token' => null,
                'token_hash' => null,
                'token_expires_at' => null,
                'opened_at' => null,
                'signed_at' => null,
                'signature_data' => null,
                'signed_document_path' => null,
                'signed_document_original_name' => null,
                'signed_document_mime_type' => null,
                'signed_document_uploaded_at' => null,
                'approval_note' => null,
                'signed_ip' => null,
                'signed_user_agent' => null,
            ]);
        }
    }

    private function deleteSignatureFilesAfterCommit(Collection $signatures): void
    {
        $paths = $signatures->flatMap(fn (Model $signature): array => [
            $signature->getAttribute('signature_data'),
            $signature->getAttribute('signed_document_path'),
        ])->filter(fn (mixed $path): bool => is_string($path)
            && trim($path) !== ''
            && ! str_starts_with($path, 'data:')
            && ! str_starts_with($path, '/')
            && ! str_contains($path, '://')
            && ! str_contains($path, '..'))
            ->unique()->values()->all();

        DB::afterCommit(function () use ($paths): void {
            foreach ($paths as $path) {
                try {
                    Storage::disk('public')->delete($path);
                } catch (\Throwable $exception) {
                    Log::warning('Approval rollback file cleanup failed.', ['path' => $path, 'error' => $exception->getMessage()]);
                }
            }
        });
    }

    /**
     * @param  Collection<int, HppSignature|LhppBastSignature>  $affectedSignatures
     */
    private function createAudit(
        string $documentType,
        int $documentId,
        Model $signature,
        User $rollbackBy,
        string $reason,
        Collection $affectedSignatures,
    ): void {
        ApprovalSignatureRollback::query()->create([
            'document_type' => $documentType,
            'document_id' => $documentId,
            'signature_type' => $signature::class,
            'signature_id' => $signature->getKey(),
            'step_order' => $signature->step_order,
            'role_key' => $signature->role_key,
            'role_label' => $signature->role_label,
            'signer_user_id' => $signature->signer_user_id,
            'signer_name' => $signature->signer_name_snapshot,
            'rollback_by' => $rollbackBy->id,
            'rollback_reason' => $reason,
            'rolled_back_at' => now(),
            'affected_signature_ids' => $affectedSignatures->pluck('id')->values()->all(),
            'previous_payload' => $affectedSignatures
                ->map(fn (Model $affectedSignature): array => $this->snapshotSignature($affectedSignature))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotSignature(Model $signature): array
    {
        return [
            'id' => $signature->getKey(),
            'step_order' => $signature->getAttribute('step_order'),
            'role_key' => $signature->getAttribute('role_key'),
            'role_label' => $signature->getAttribute('role_label'),
            'signer_user_id' => $signature->getAttribute('signer_user_id'),
            'signer_name_snapshot' => $signature->getAttribute('signer_name_snapshot'),
            'status' => $signature->getAttribute('status'),
            'token_hash' => $signature->getAttribute('token_hash'),
            'token_expires_at' => optional($signature->getAttribute('token_expires_at'))?->toJSON(),
            'opened_at' => optional($signature->getAttribute('opened_at'))?->toJSON(),
            'signed_at' => optional($signature->getAttribute('signed_at'))?->toJSON(),
            'has_signature_data' => filled($signature->getAttribute('signature_data')),
            'signed_document_path' => $signature->getAttribute('signed_document_path'),
            'signed_document_original_name' => $signature->getAttribute('signed_document_original_name'),
            'signed_document_mime_type' => $signature->getAttribute('signed_document_mime_type'),
            'signed_document_uploaded_at' => optional($signature->getAttribute('signed_document_uploaded_at'))?->toJSON(),
            'approval_note' => $signature->getAttribute('approval_note'),
            'signed_ip' => $signature->getAttribute('signed_ip'),
            'signed_user_agent' => $signature->getAttribute('signed_user_agent'),
        ];
    }
}
