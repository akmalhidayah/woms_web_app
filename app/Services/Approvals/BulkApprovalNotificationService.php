<?php

namespace App\Services\Approvals;

use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Support\BastApprovalSignatureBuilder;
use App\Support\HppApprovalSignatureBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BulkApprovalNotificationService
{
    public function __construct(
        private readonly ApprovalNotificationService $notificationService,
        private readonly HppApprovalSignatureBuilder $hppSignatureBuilder,
        private readonly BastApprovalSignatureBuilder $bastSignatureBuilder,
    ) {}

    /**
     * @return array{sent: int, failed: int, skipped: int}
     */
    public function resendActiveHppApprovals(): array
    {
        $result = $this->emptyResult();

        HppSignature::query()
            ->with(['signer', 'hpp'])
            ->where('status', HppSignature::STATUS_PENDING)
            ->whereHas('hpp', fn ($query) => $query->where('status', Hpp::STATUS_IN_REVIEW))
            ->chunkById(100, function ($signatures) use (&$result): void {
                foreach ($signatures as $signature) {
                    $this->resend(
                        $result,
                        $signature,
                        fn (): bool => $this->notificationService->sendHpp($signature, true),
                    );
                }
            });

        return $result;
    }

    /**
     * @return array{sent: int, failed: int, skipped: int}
     */
    public function resendActiveBastApprovals(): array
    {
        $result = $this->emptyResult();

        LhppBastSignature::query()
            ->with(['signer', 'lhppBast.garansi'])
            ->where('status', LhppBastSignature::STATUS_PENDING)
            ->whereHas('lhppBast', fn ($query) => $query
                ->where('approval_status', LhppBast::APPROVAL_IN_REVIEW))
            ->chunkById(100, function ($signatures) use (&$result): void {
                foreach ($signatures as $signature) {
                    $this->resend(
                        $result,
                        $signature,
                        fn (): bool => $this->notificationService->sendBast($signature, true),
                    );
                }
            });

        return $result;
    }

    /**
     * @return array{regenerated: int, failed: int, skipped: int}
     */
    public function regenerateExpiredHppApprovals(): array
    {
        $result = $this->emptyRegenerationResult();

        HppSignature::query()
            ->where('status', HppSignature::STATUS_PENDING)
            ->where('role_key', '!=', 'dirops')
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<', now())
            ->whereHas('hpp', fn ($query) => $query->where('status', Hpp::STATUS_IN_REVIEW))
            ->select('id')
            ->chunkById(100, function ($signatures) use (&$result): void {
                foreach ($signatures as $candidate) {
                    try {
                        DB::transaction(function () use ($candidate, &$result): void {
                            $signature = HppSignature::query()
                                ->with('hpp')
                                ->lockForUpdate()
                                ->find($candidate->id);

                            if (! $signature
                                || ! $signature->isPending()
                                || $signature->role_key === 'dirops'
                                || ! $signature->tokenExpired()
                                || $signature->hpp?->status !== Hpp::STATUS_IN_REVIEW) {
                                $result['skipped']++;

                                return;
                            }

                            $this->hppSignatureBuilder->issueToken($signature);
                            $result['regenerated']++;
                        });
                    } catch (Throwable $exception) {
                        $result['failed']++;
                        Log::error('Failed to regenerate an expired HPP approval token in bulk.', [
                            'hpp_signature_id' => $candidate->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        return $result;
    }

    /**
     * @return array{regenerated: int, failed: int, skipped: int}
     */
    public function regenerateExpiredBastApprovals(): array
    {
        $result = $this->emptyRegenerationResult();

        LhppBastSignature::query()
            ->where('status', LhppBastSignature::STATUS_PENDING)
            ->where('role_key', '!=', 'dirops')
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<', now())
            ->whereHas('lhppBast', fn ($query) => $query
                ->where('approval_status', LhppBast::APPROVAL_IN_REVIEW))
            ->select('id')
            ->chunkById(100, function ($signatures) use (&$result): void {
                foreach ($signatures as $candidate) {
                    try {
                        DB::transaction(function () use ($candidate, &$result): void {
                            $signature = LhppBastSignature::query()
                                ->with('lhppBast')
                                ->lockForUpdate()
                                ->find($candidate->id);

                            if (! $signature
                                || ! $signature->isPending()
                                || $signature->role_key === 'dirops'
                                || ! $signature->tokenExpired()
                                || $signature->lhppBast?->approval_status !== LhppBast::APPROVAL_IN_REVIEW) {
                                $result['skipped']++;

                                return;
                            }

                            $this->bastSignatureBuilder->issueToken($signature);
                            $result['regenerated']++;
                        });
                    } catch (Throwable $exception) {
                        $result['failed']++;
                        Log::error('Failed to regenerate an expired BAST approval token in bulk.', [
                            'lhpp_bast_signature_id' => $candidate->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        return $result;
    }

    /**
     * @param  array{sent: int, failed: int, skipped: int}  $result
     */
    public function resultMessage(string $documentType, array $result): string
    {
        return sprintf(
            'Resend semua approval aktif %s selesai: %d email berhasil dikirim, %d gagal, dan %d dilewati karena link tidak tersedia, kedaluwarsa, atau tahap DIROPS.',
            $documentType,
            $result['sent'],
            $result['failed'],
            $result['skipped'],
        );
    }

    /**
     * @param  array{regenerated: int, failed: int, skipped: int}  $result
     */
    public function regenerationResultMessage(string $documentType, array $result): string
    {
        return sprintf(
            'Regenerate token kedaluwarsa %s selesai: %d token berhasil diperbarui, %d gagal, dan %d dilewati karena status approval telah berubah.',
            $documentType,
            $result['regenerated'],
            $result['failed'],
            $result['skipped'],
        );
    }

    /**
     * @param  array{sent: int, failed: int, skipped: int}  $result
     */
    private function resend(array &$result, HppSignature|LhppBastSignature $signature, callable $send): void
    {
        if ($signature->role_key === 'dirops' || $signature->tokenExpired() || ! $signature->approvalUrl()) {
            $result['skipped']++;

            return;
        }

        if ($send()) {
            $result['sent']++;

            return;
        }

        $result['failed']++;
    }

    /**
     * @return array{sent: int, failed: int, skipped: int}
     */
    private function emptyResult(): array
    {
        return [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];
    }

    /**
     * @return array{regenerated: int, failed: int, skipped: int}
     */
    private function emptyRegenerationResult(): array
    {
        return [
            'regenerated' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];
    }
}
