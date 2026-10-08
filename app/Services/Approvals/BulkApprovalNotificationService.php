<?php

namespace App\Services\Approvals;

use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;

class BulkApprovalNotificationService
{
    public function __construct(
        private readonly ApprovalNotificationService $notificationService,
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
}
