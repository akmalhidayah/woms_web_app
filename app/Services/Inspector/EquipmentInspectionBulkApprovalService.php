<?php

namespace App\Services\Inspector;

use App\Models\EquipmentInspectionApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Pengiriman massal approval Manager Inspeksi Peralatan.
 *
 * Setiap approval diproses lewat EquipmentInspectionWorkflow::sendEmail() sehingga lock, cooldown,
 * pembaruan token kedaluwarsa, audit, dan status email tetap memakai satu jalur yang sama dengan Resend per dokumen.
 */
final class EquipmentInspectionBulkApprovalService
{
    public function __construct(private readonly EquipmentInspectionWorkflow $workflow) {}

    /**
     * Kirim ulang email ke approver yang tokennya masih berlaku.
     *
     * @return array{sent: int, failed: int, skipped: int}
     */
    public function resendActive(User $admin): array
    {
        return $this->run($admin, EquipmentInspectionApproval::query()->active());
    }

    /**
     * Terbitkan token baru (dan kirim email) untuk approval yang tokennya sudah kedaluwarsa.
     *
     * @return array{sent: int, failed: int, skipped: int}
     */
    public function regenerateExpired(User $admin): array
    {
        return $this->run($admin, EquipmentInspectionApproval::query()
            ->active(true)
            ->where(fn (Builder $query) => $query->whereNull('token_expires_at')->orWhere('token_expires_at', '<=', now())));
    }

    /**
     * @param  Builder<EquipmentInspectionApproval>  $query
     * @return array{sent: int, failed: int, skipped: int}
     */
    private function run(User $admin, Builder $query): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        $query->select('equipment_inspection_approvals.id')->chunkById(50, function ($approvals) use ($admin, &$result): void {
            foreach ($approvals as $candidate) {
                try {
                    $this->workflow->sendEmail($candidate->id, $admin);
                } catch (ValidationException|HttpExceptionInterface) {
                    // Cooldown pengiriman, atau approval sudah tidak aktif saat dikunci.
                    $result['skipped']++;

                    continue;
                } catch (Throwable $exception) {
                    $result['failed']++;
                    Log::warning('Bulk equipment inspection approval email failed.', [
                        'approval_id' => $candidate->id,
                        'exception_type' => $exception::class,
                    ]);

                    continue;
                }

                $status = EquipmentInspectionApproval::query()->whereKey($candidate->id)->value('email_status');
                match ($status) {
                    'resent' => $result['sent']++,
                    'failed' => $result['failed']++,
                    default => $result['skipped']++,
                };
            }
        });

        return $result;
    }

    /**
     * @param  array{sent: int, failed: int, skipped: int}  $result
     */
    public static function resendMessage(array $result): string
    {
        return sprintf(
            'Resend semua approval Inspeksi Peralatan selesai: %d email berhasil dikirim, %d gagal, dan %d dilewati karena baru dikirim, link tidak tersedia, atau status approval berubah.',
            $result['sent'],
            $result['failed'],
            $result['skipped'],
        );
    }

    /**
     * @param  array{sent: int, failed: int, skipped: int}  $result
     */
    public static function regenerateMessage(array $result): string
    {
        return sprintf(
            'Regenerate token kedaluwarsa Inspeksi Peralatan selesai: %d token diperbarui dan email dikirim, %d gagal, dan %d dilewati karena status approval telah berubah atau baru dikirim.',
            $result['sent'],
            $result['failed'],
            $result['skipped'],
        );
    }
}
