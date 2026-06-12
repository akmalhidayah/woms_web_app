<?php

namespace App\Services\Approvals;

use App\Models\HppSignature;
use App\Models\InitialWorkSignature;
use App\Models\LhppBastSignature;
use App\Models\QualityControlSignature;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApprovalNotificationService
{
    public function sendInitialWork(InitialWorkSignature $signature, bool $resend = false): bool
    {
        $signature->loadMissing(['signer', 'initialWork.order']);

        return $this->send(
            $signature->signer,
            'Initial Work',
            (string) ($signature->initialWork?->nomor_initial_work ?: $signature->initialWork?->nomor_order),
            $signature->displayRoleLabel(),
            $signature->approvalUrl(),
            $signature->token_expires_at,
            $resend,
            [
                'initial_work_signature_id' => $signature->id,
                'initial_work_id' => $signature->initial_work_id,
            ],
        );
    }

    public function sendHpp(HppSignature $signature, bool $resend = false): bool
    {
        $signature->loadMissing(['signer', 'hpp']);

        return $this->send(
            $signature->signer,
            'HPP',
            (string) $signature->hpp?->nomor_order,
            $signature->displayRoleLabel(),
            $signature->approvalUrl(),
            $signature->token_expires_at,
            $resend,
            [
                'hpp_signature_id' => $signature->id,
                'hpp_id' => $signature->hpp_id,
            ],
        );
    }

    public function sendBast(LhppBastSignature $signature, bool $resend = false): bool
    {
        $signature->loadMissing(['signer', 'lhppBast']);
        $termin = $signature->lhppBast?->termin_type === 'termin_2' ? 'Termin 2' : 'Termin 1';

        return $this->send(
            $signature->signer,
            'BAST/LHPP',
            trim((string) $signature->lhppBast?->nomor_order.' '.$termin),
            $signature->displayRoleLabel(),
            $signature->approvalUrl(),
            $signature->token_expires_at,
            $resend,
            [
                'lhpp_bast_signature_id' => $signature->id,
                'lhpp_bast_id' => $signature->lhpp_bast_id,
            ],
        );
    }

    public function sendQualityControl(QualityControlSignature $signature, bool $resend = false): bool
    {
        $signature->loadMissing(['signer', 'qualityControlReport.order']);
        $report = $signature->qualityControlReport;

        return $this->send(
            $signature->signer,
            'Quality Control',
            (string) ($report?->report_no ?: $report?->order?->nomor_order),
            $signature->displayRoleLabel(),
            $signature->approvalUrl(),
            $signature->token_expires_at,
            $resend,
            [
                'quality_control_signature_id' => $signature->id,
                'quality_control_report_id' => $signature->quality_control_report_id,
            ],
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function send(
        ?User $recipient,
        string $documentType,
        string $documentNumber,
        string $roleLabel,
        ?string $approvalUrl,
        ?Carbon $expiresAt,
        bool $resend,
        array $context,
    ): bool {
        $baseContext = [
            ...$context,
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'role_label' => $roleLabel,
            'recipient_user_id' => $recipient?->id,
            'recipient_email' => $recipient?->email,
            'is_resend' => $resend,
        ];

        if (! $recipient || blank($recipient->email) || blank($approvalUrl)) {
            Log::warning('Approval email was not sent because recipient or active link is unavailable.', [
                ...$baseContext,
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            ]);

            return false;
        }

        try {
            $recipient->notify(new ApprovalRequestedNotification(
                $documentType,
                $documentNumber,
                $roleLabel,
                $approvalUrl,
                $expiresAt,
            ));

            Log::info('Approval email sent.', [
                ...$baseContext,
                'status_code' => Response::HTTP_OK,
            ]);

            return true;
        } catch (Throwable $exception) {
            Log::error('Failed to send approval email.', [
                ...$baseContext,
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return false;
        }
    }
}
