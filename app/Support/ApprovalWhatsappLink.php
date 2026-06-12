<?php

namespace App\Support;

use App\Models\HppSignature;
use App\Models\InitialWorkSignature;
use App\Models\LhppBastSignature;
use App\Models\QualityControlSignature;
use App\Models\User;
use Illuminate\Support\Carbon;

class ApprovalWhatsappLink
{
    public static function forInitialWork(?InitialWorkSignature $signature): ?string
    {
        if (! $signature) {
            return null;
        }

        $signature->loadMissing(['signer', 'initialWork.order']);

        return self::build(
            $signature->signer,
            'Initial Work',
            (string) ($signature->initialWork?->nomor_initial_work ?: $signature->initialWork?->nomor_order),
            $signature->displayRoleLabel(),
            $signature->approvalUrl(),
            $signature->token_expires_at,
        );
    }

    public static function forHpp(?HppSignature $signature): ?string
    {
        if (! $signature) {
            return null;
        }

        $signature->loadMissing(['signer', 'hpp']);

        return self::build(
            $signature->signer,
            'HPP',
            (string) $signature->hpp?->nomor_order,
            $signature->displayRoleLabel(),
            $signature->approvalUrl(),
            $signature->token_expires_at,
        );
    }

    public static function forBast(?LhppBastSignature $signature): ?string
    {
        if (! $signature) {
            return null;
        }

        $signature->loadMissing(['signer', 'lhppBast']);
        $termin = $signature->lhppBast?->termin_type === 'termin_2' ? 'Termin 2' : 'Termin 1';

        return self::build(
            $signature->signer,
            'BAST/LHPP',
            trim((string) $signature->lhppBast?->nomor_order.' '.$termin),
            $signature->displayRoleLabel(),
            $signature->approvalUrl(),
            $signature->token_expires_at,
        );
    }

    public static function forQualityControl(?QualityControlSignature $signature): ?string
    {
        if (! $signature) {
            return null;
        }

        $signature->loadMissing(['signer', 'qualityControlReport.order']);
        $report = $signature->qualityControlReport;

        return self::build(
            $signature->signer,
            'Quality Control',
            (string) ($report?->report_no ?: $report?->order?->nomor_order),
            $signature->displayRoleLabel(),
            $signature->approvalUrl(),
            $signature->token_expires_at,
        );
    }

    public static function build(
        ?User $recipient,
        string $documentType,
        string $documentNumber,
        string $roleLabel,
        ?string $approvalUrl,
        ?Carbon $expiresAt,
    ): ?string {
        $phone = self::normalizePhone($recipient?->nomor_hp);

        if (! $recipient || blank($phone) || blank($approvalUrl)) {
            return null;
        }

        $message = self::message(
            $recipient->name ?: 'Pengguna',
            $documentType,
            $documentNumber,
            $roleLabel,
            $approvalUrl,
            $expiresAt,
        );

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return strlen($digits) >= 8 ? $digits : null;
    }

    public static function message(
        string $userName,
        string $documentType,
        string $documentNumber,
        string $roleLabel,
        string $approvalUrl,
        ?Carbon $expiresAt,
    ): string {
        $lines = [
            'Halo '.$userName,
            '',
            'Anda ditetapkan sebagai '.$roleLabel.' untuk melakukan approval dokumen berikut:',
            '',
            'Dokumen        : '.$documentType,
            'Nomor Dokumen : '.$documentNumber,
            'Role Approval : '.$roleLabel,
            '',
            'Silakan buka halaman approval melalui link berikut:',
            $approvalUrl,
            '',
        ];

        if ($expiresAt) {
            $lines[] = 'Link berlaku sampai '.$expiresAt->format('d/m/Y H:i').'.';
            $lines[] = '';
        }

        $lines[] = 'Link hanya dapat digunakan oleh akun approval yang ditetapkan.';
        $lines[] = '';
        $lines[] = 'Pesan ini dikirim otomatis oleh sistem WOMS. Mohon tidak membalas pesan ini.';

        return implode("\n", $lines);
    }
}
