<?php

namespace App\Services\Approvals;

use App\Models\HppSignature;
use App\Models\InitialWorkSignature;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\QualityControlSignature;
use App\Models\WorkshopHandover;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use App\Support\ApprovalRecipientRoleLabel;
use App\Support\BastDisplayLabel;
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
            (string) ($signature->initialWork?->nama_pekerjaan ?: $signature->initialWork?->order?->nama_pekerjaan),
            ApprovalRecipientRoleLabel::for($signature),
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
            (string) $signature->hpp?->nama_pekerjaan,
            ApprovalRecipientRoleLabel::for($signature),
            $signature->approvalUrl(),
            $signature->token_expires_at,
            $resend,
            [
                'hpp_signature_id' => $signature->id,
                'hpp_id' => $signature->hpp_id,
            ],
            'Amount',
            $this->moneyInt($signature->hpp?->total_keseluruhan),
        );
    }

    public function sendBast(LhppBastSignature $signature, bool $resend = false): bool
    {
        $signature->loadMissing(['signer', 'lhppBast.garansi']);
        $lhpp = $signature->lhppBast;
        $garansiMonths = $lhpp?->garansi?->garansi_months;
        $garansiMonths = $garansiMonths === null ? null : (int) $garansiMonths;
        $isSinglePayment = $lhpp?->termin_type === 'termin_1'
            && BastDisplayLabel::isWithoutWarranty($garansiMonths);
        $termin = $lhpp?->termin_type === 'termin_2' ? 'Termin 2' : 'Termin 1';
        $documentNumber = $isSinglePayment
            ? (string) $lhpp?->nomor_order
            : trim((string) $lhpp?->nomor_order.' '.$termin);

        return $this->send(
            $signature->signer,
            'BAST/LHPP',
            $documentNumber,
            (string) $lhpp?->deskripsi_pekerjaan,
            ApprovalRecipientRoleLabel::for($signature),
            $signature->approvalUrl(),
            $signature->token_expires_at,
            $resend,
            [
                'lhpp_bast_signature_id' => $signature->id,
                'lhpp_bast_id' => $signature->lhpp_bast_id,
            ],
            'Amount',
            $this->resolveBastAmount($lhpp, $isSinglePayment),
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
            (string) $report?->order?->nama_pekerjaan,
            ApprovalRecipientRoleLabel::for($signature),
            $signature->approvalUrl(),
            $signature->token_expires_at,
            $resend,
            [
                'quality_control_signature_id' => $signature->id,
                'quality_control_report_id' => $signature->quality_control_report_id,
            ],
        );
    }

    public function sendWorkshopHandover(WorkshopHandover $handover, bool $resend = false): bool
    {
        $handover->loadMissing(['recipient', 'order']);

        return $this->send(
            $handover->recipient,
            'Serah Terima Bengkel',
            (string) $handover->document_no,
            (string) $handover->job_name_snapshot,
            (string) $handover->recipient_position_snapshot,
            $handover->approvalUrl(),
            $handover->token_expires_at,
            $resend,
            [
                'workshop_handover_id' => $handover->id,
                'order_id' => $handover->order_id,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function send(
        ?User $recipient,
        string $documentType,
        string $documentNumber,
        string $documentDescription,
        string $roleLabel,
        ?string $approvalUrl,
        ?Carbon $expiresAt,
        bool $resend,
        array $context,
        ?string $documentAmountLabel = null,
        ?int $documentAmount = null,
    ): bool {
        $baseContext = [
            ...$context,
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'document_description' => $documentDescription,
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
                $documentDescription,
                $roleLabel,
                $approvalUrl,
                $expiresAt,
                $documentAmountLabel,
                $documentAmount,
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

    private function resolveBastAmount(?LhppBast $lhpp, bool $isSinglePayment): ?int
    {
        if (! $lhpp) {
            return null;
        }

        if ($lhpp->termin_type === 'termin_2') {
            return $this->moneyInt($lhpp->termin_2_nilai);
        }

        return $this->moneyInt(
            $isSinglePayment ? $lhpp->total_aktual_biaya : $lhpp->termin_1_nilai,
        );
    }

    private function moneyInt(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
