<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use App\Models\User;
use App\Support\AdminMenuRegistry;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

final class AdminGlobalSearchService
{
    public const RESULT_LIMIT = 5;

    public function __construct(
        private readonly OrderProgressResolver $progressResolver,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $search, User $user): array
    {
        $search = preg_replace('/\s+/u', ' ', trim($search)) ?? '';

        if (mb_strlen($search) < 2 || mb_strlen($search) > 100) {
            return [];
        }

        $canAccessService = AdminMenuRegistry::canAccess($user, AdminMenuRegistry::MENU_ORDER_JASA);
        $canAccessWorkshop = AdminMenuRegistry::canAccess($user, AdminMenuRegistry::MENU_ORDER_BENGKEL);

        if (! $canAccessService && ! $canAccessWorkshop) {
            return [];
        }

        $escapedSearch = $this->escapeLike($search);
        $prefix = $escapedSearch.'%';
        $keywords = collect(preg_split('/\s+/u', $search) ?: [])
            ->map(fn (string $keyword): string => $this->escapeLike($keyword))
            ->filter(fn (string $keyword): bool => $keyword !== '')
            ->take(6)
            ->values()
            ->all();

        $orders = Order::query()
            ->select([
                'id',
                'nomor_order',
                'notifikasi',
                'nama_pekerjaan',
                'prioritas',
                'catatan_status',
            ])
            ->when(! $canAccessService || ! $canAccessWorkshop, function (Builder $query) use ($canAccessService): void {
                if ($canAccessService) {
                    $query->where('catatan_status', '!=', OrderUserNoteStatus::ApprovedWorkshop->value);

                    return;
                }

                $query->whereIn('catatan_status', [
                    OrderUserNoteStatus::ApprovedWorkshop->value,
                    OrderUserNoteStatus::ApprovedWorkshopJasa->value,
                ]);
            })
            ->where(function (Builder $query) use ($search, $prefix, $keywords): void {
                $query
                    ->where('nomor_order', $search)
                    ->orWhere('notifikasi', $search)
                    ->orWhere('nomor_order', 'like', $prefix)
                    ->orWhere('notifikasi', 'like', $prefix)
                    ->orWhere(function (Builder $nameQuery) use ($keywords): void {
                        foreach ($keywords as $keyword) {
                            $nameQuery->where('nama_pekerjaan', 'like', "%{$keyword}%");
                        }
                    });
            })
            ->orderByRaw(
                'CASE '
                .'WHEN nomor_order = ? THEN 0 '
                .'WHEN notifikasi = ? THEN 1 '
                .'WHEN nomor_order LIKE ? THEN 2 '
                .'WHEN notifikasi LIKE ? THEN 3 '
                .'ELSE 4 END',
                [$search, $search, $prefix, $prefix],
            )
            ->orderByDesc('id')
            ->limit(self::RESULT_LIMIT)
            ->with($this->progressRelations())
            ->get();

        return $orders
            ->map(function (Order $order) use ($user): array {
                try {
                    $progress = $this->progressResolver->resolve($order, $user);
                } catch (Throwable $exception) {
                    report($exception);
                    $progress = $this->fallbackProgress($order);
                }

                return [
                    'id' => (int) $order->getKey(),
                    'order_number' => (string) $order->nomor_order,
                    'notification_number' => filled($order->notifikasi) ? (string) $order->notifikasi : null,
                    'job_name' => (string) $order->nama_pekerjaan,
                    ...$progress,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function progressRelations(): array
    {
        return [
            'scopeOfWork:id,order_id',
            'initialWork:id,order_id,progress_pekerjaan',
            'latestHpp:hpps.id,hpps.order_id,hpps.nomor_order,hpps.status',
            'budgetVerification:id,order_id,hpp_id,status_anggaran,kategori_item,kategori_biaya,cost_element',
            'purchaseOrder:id,order_id,hpp_id,purchase_order_number,target_penyelesaian,approval_target,approve_manager,progress_pekerjaan',
            'garansi:id,order_id,lhpp_bast_id,garansi_months',
            'orderWorkshop:id,order_id,preparation_status,progress_status,legacy_completed_at',
            'workPackages:id,order_id,status',
            'latestQualityControlReport:quality_control_reports.id,quality_control_reports.order_id,quality_control_reports.status,quality_control_reports.payload',
            'latestQualityControlReport.signatures',
            'workshopHandover:id,order_id,status',
            'lhppBasts' => fn ($query) => $query
                ->select([
                    'id',
                    'order_id',
                    'parent_lhpp_bast_id',
                    'termin_type',
                    'approval_status',
                    'quality_control_status',
                    'termin1_status',
                    'termin2_status',
                ])
                ->with([
                    'garansi:id,order_id,lhpp_bast_id,garansi_months',
                    'lpjPpl:id,lhpp_bast_id,lpj_number_termin1,ppl_number_termin1,lpj_document_path_termin1,ppl_document_path_termin1,lpj_number_termin2,ppl_number_termin2,lpj_document_path_termin2,ppl_document_path_termin2',
                ])
                ->orderBy('id'),
        ];
    }

    /**
     * Keep a malformed legacy workflow record from failing the entire search response.
     *
     * @return array<string, mixed>
     */
    private function fallbackProgress(Order $order): array
    {
        $type = match ((string) $order->getRawOriginal('catatan_status')) {
            OrderUserNoteStatus::ApprovedWorkshop->value => 'Bengkel',
            OrderUserNoteStatus::ApprovedWorkshopJasa->value => 'Gabungan',
            default => 'Jasa',
        };

        return [
            'type' => $type,
            'status' => 'Perlu Pemeriksaan',
            'tone' => 'slate',
            'action_label' => 'Buka Detail Order',
            'url' => route('admin.orders.show', ['order' => $order]),
            'paths' => [],
        ];
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
