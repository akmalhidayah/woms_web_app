<?php

namespace App\Http\Controllers\Pkm;

use App\Http\Controllers\Controller;
use App\Models\LhppBast;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with([
                'latestHpp' => fn ($query) => $query->select([
                    'hpps.id',
                    'hpps.order_id',
                    'hpps.nomor_order',
                ]),
                'latestPurchaseOrder' => fn ($query) => $query->select([
                    'purchase_orders.id',
                    'purchase_orders.order_id',
                    'purchase_orders.purchase_order_number',
                    'purchase_orders.target_penyelesaian',
                    'purchase_orders.progress_pekerjaan',
                ]),
                'lhppBasts' => fn ($query) => $query
                    ->select([
                        'id',
                        'order_id',
                        'termin_type',
                        'nomor_order',
                        'termin1_status',
                        'termin2_status',
                    ])
                    ->where('termin_type', 'termin_1')
                    ->with([
                        'lpjPpl:id,lhpp_bast_id,lpj_document_path_termin1,ppl_document_path_termin1',
                        'garansi:id,lhpp_bast_id,start_date,end_date,garansi_months',
                        'terminTwo:id,parent_lhpp_bast_id,termin_type',
                    ]),
            ])
            ->whereHas('purchaseOrder', function (Builder $query): void {
                $query
                    ->where('approve_manager', true)
                    ->whereNotNull('purchase_order_number')
                    ->whereRaw("TRIM(purchase_order_number) <> ''");
            })
            ->latest('id')
            ->get();

        $today = Carbon::today();

        $dashboardItems = $orders->map(function (Order $order) use ($today): array {
            /** @var LhppBast|null $terminOne */
            $terminOne = $order->lhppBasts->first();
            $lpjPpl = $terminOne?->lpjPpl;
            $garansi = $terminOne?->garansi;
            $targetDate = $order->latestPurchaseOrder?->target_penyelesaian;
            $progress = (int) ($order->latestPurchaseOrder?->progress_pekerjaan ?? 0);

            $hasHpp = (bool) $order->latestHpp;
            $hasPo = filled($order->latestPurchaseOrder?->purchase_order_number);
            $hasBast = (bool) $terminOne;
            $hasTerminTwo = (bool) $terminOne?->terminTwo;
            $hasLpjPpl = (bool) ($lpjPpl?->lpj_document_path_termin1 && $lpjPpl?->ppl_document_path_termin1);
            $isCompleteDocument = $hasHpp && $hasPo && $hasBast && $hasLpjPpl;
            $hasGaransi = (bool) $garansi;
            $isDone = $isCompleteDocument && $hasGaransi;
            $garansiAktif = $garansi
                && (int) ($garansi->garansi_months ?? 0) > 0
                && $garansi->end_date?->gte($today);

            $isOverdue = ! $isDone && $targetDate && $targetDate->isPast() && ! $targetDate->isToday();
            $isToday = ! $isDone && $targetDate && $targetDate->isToday();
            $isSoon = ! $isDone && $targetDate && $targetDate->gte($today) && $today->diffInDays($targetDate) <= 7;

            $sourceMenu = $isDone ? 'Dokumen' : ($hasBast ? 'BAST / LHPP' : 'List Pekerjaan');

            $statusKey = match (true) {
                $isDone => 'selesai',
                $isOverdue => 'overdue',
                $progress >= 11 || $hasBast => 'proses',
                default => 'menunggu',
            };

            $statusLabel = match ($statusKey) {
                'selesai' => 'Selesai',
                'overdue' => 'Overdue',
                'proses' => 'Proses',
                default => 'Menunggu',
            };

            $statusText = match (true) {
                $isDone => 'Pekerjaan telah selesai',
                $isOverdue => abs($today->diffInDays($targetDate, false)).' hari terlambat',
                $isToday => 'Deadline hari ini',
                $isSoon => $today->diffInDays($targetDate).' hari lagi',
                $targetDate !== null => 'Target belum dekat',
                default => 'Belum ada target',
            };

            $statusTone = match ($statusKey) {
                'selesai' => 'emerald',
                'overdue' => 'rose',
                'proses' => 'blue',
                default => 'amber',
            };

            $actionUrl = match (true) {
                $isDone => route('pkm.laporan', ['notification_number' => $order->nomor_order, 'status' => 'complete']),
                $hasBast => route('pkm.lhpp.index', ['search' => $order->nomor_order]),
                default => route('pkm.jobwaiting', ['search' => $order->nomor_order]),
            };

            $actionLabel = $isDone ? 'Detail' : 'Update';

            return [
                'order' => $order,
                'progress' => $progress,
                'target_date' => $targetDate,
                'target_date_string' => $targetDate?->toDateString(),
                'has_bast' => $hasBast,
                'has_termin_two' => $hasTerminTwo,
                'is_complete_document' => $isCompleteDocument,
                'has_garansi' => $hasGaransi,
                'garansi_aktif' => (bool) $garansiAktif,
                'show_in_jobwaiting' => ! $isDone,
                'is_done' => $isDone,
                'is_overdue' => $isOverdue,
                'is_today' => $isToday,
                'is_soon' => $isSoon,
                'status_key' => $statusKey,
                'status_label' => $statusLabel,
                'status_text' => $statusText,
                'status_tone' => $statusTone,
                'source_menu' => $sourceMenu,
                'action_url' => $actionUrl,
                'action_label' => $actionLabel,
            ];
        });

        $progressItems = $dashboardItems->pluck('progress')->values();
        $totalPekerjaan = $orders->count();
        $listPekerjaanCount = $dashboardItems->where('show_in_jobwaiting', true)->count();
        $listBerjalanCount = $dashboardItems->filter(fn (array $item) => $item['show_in_jobwaiting'] && $item['progress'] >= 11)->count();
        $listSiapMulaiCount = $dashboardItems->filter(fn (array $item) => $item['show_in_jobwaiting'] && $item['progress'] < 11)->count();
        $bastTerminOneCount = $dashboardItems->where('has_bast', true)->count();
        $bastTerminTwoCount = $dashboardItems->where('has_termin_two', true)->count();
        $dokumenLengkapCount = $dashboardItems->where('is_complete_document', true)->count();
        $dokumenFinalCount = $dashboardItems->where('is_done', true)->count();
        $garansiAktifCount = $dashboardItems->where('garansi_aktif', true)->count();
        $overdueCount = $dashboardItems->where('is_overdue', true)->count();
        $todayCount = $dashboardItems->where('is_today', true)->count();
        $soonCount = $dashboardItems->where('is_soon', true)->count();
        $prosesCount = $dashboardItems->where('status_key', 'proses')->count();
        $menungguCount = $dashboardItems->where('status_key', 'menunggu')->count();
        $totalProgress = round($progressItems->avg() ?? 0, 2);

        $menuSummaries = [
            [
                'title' => 'List Pekerjaan',
                'value' => $listPekerjaanCount,
                'description' => 'Order yang masih perlu dipantau di menu List Pekerjaan.',
                'meta' => "{$listBerjalanCount} berjalan | {$listSiapMulaiCount} siap mulai",
                'icon' => 'clipboard-list',
                'tone' => 'border-[#efe2bf] bg-[#fff9ec] text-[#a97415]',
                'icon_tone' => 'bg-[#f7ebc9] text-[#a97415]',
            ],
            [
                'title' => 'BAST / LHPP',
                'value' => $bastTerminOneCount,
                'description' => 'Order yang sudah masuk proses BAST / LHPP.',
                'meta' => "Termin 1: {$bastTerminOneCount} | Termin 2: {$bastTerminTwoCount}",
                'icon' => 'file-badge',
                'tone' => 'border-[#d9e6e5] bg-[#f7fbfb] text-[#34736f]',
                'icon_tone' => 'bg-[#deefed] text-[#34736f]',
            ],
            [
                'title' => 'Dokumen',
                'value' => $dokumenLengkapCount,
                'description' => 'Order yang sudah rapi di menu Dokumen PKM.',
                'meta' => "Final: {$dokumenFinalCount} | Garansi aktif: {$garansiAktifCount}",
                'icon' => 'folder-kanban',
                'tone' => 'border-[#eadfd2] bg-[#fffaf5] text-[#b86c43]',
                'icon_tone' => 'bg-[#f3e5d8] text-[#b86c43]',
            ],
        ];

        $statusBreakdown = collect([
            [
                'label' => 'Selesai',
                'count' => $dokumenFinalCount,
                'color' => '#3fb37c',
                'class' => 'bg-emerald-100',
            ],
            [
                'label' => 'Proses',
                'count' => $prosesCount,
                'color' => '#5b88ff',
                'class' => 'bg-blue-100',
            ],
            [
                'label' => 'Menunggu',
                'count' => $menungguCount,
                'color' => '#f3c05f',
                'class' => 'bg-amber-100',
            ],
            [
                'label' => 'Overdue',
                'count' => $overdueCount,
                'color' => '#ef6666',
                'class' => 'bg-rose-100',
            ],
        ])->map(function (array $item) use ($totalPekerjaan): array {
            $percentage = $totalPekerjaan > 0 ? (int) round(($item['count'] / $totalPekerjaan) * 100) : 0;
            $item['percentage'] = $percentage;

            return $item;
        })->all();

        $recentTrend = $dashboardItems
            ->sortByDesc(fn (array $item) => $item['order']->created_at?->timestamp ?? 0)
            ->take(7)
            ->reverse()
            ->values()
            ->map(function (array $item): array {
                /** @var Order $order */
                $order = $item['order'];

                return [
                    'label' => optional($order->created_at)->format('d M') ?? $order->nomor_order,
                    'value' => (int) $item['progress'],
                    'order_label' => $order->nomor_order,
                ];
            })
            ->all();

        $jobHighlights = $dashboardItems
            ->filter(fn (array $item) => $item['target_date'] !== null || $item['is_done'])
            ->sortBy(fn (array $item) => sprintf(
                '%02d_%s',
                match ($item['status_key']) {
                    'overdue' => 0,
                    'menunggu' => 1,
                    'proses' => 2,
                    default => 3,
                },
                $item['target_date_string'] ?? '9999-12-31'
            ))
            ->take(6)
            ->values()
            ->map(function (array $item): array {
                /** @var Order $order */
                $order = $item['order'];

                return [
                    'label' => $order->nama_pekerjaan ?: ('Order '.$order->nomor_order),
                    'nomor_order' => $order->nomor_order,
                    'date' => $item['target_date']?->format('d M Y') ?? '-',
                    'progress' => $item['progress'],
                    'status_label' => $item['status_label'],
                    'status_text' => $item['status_text'],
                    'status_key' => $item['status_key'],
                    'status_tone' => $item['status_tone'],
                    'source_menu' => $item['source_menu'],
                    'action_url' => $item['action_url'],
                    'action_label' => $item['action_label'],
                ];
            })
            ->all();

        $targets = $dashboardItems
            ->filter(fn (array $item) => $item['target_date'] !== null || $item['is_done'])
            ->map(function (array $item) use ($today): array {
                /** @var Order $order */
                $order = $item['order'];
                $targetDate = $item['target_date'] ?? $today;

                return [
                    'date' => $targetDate->toDateString(),
                    'description' => $order->nama_pekerjaan ?: ('Order '.$order->nomor_order),
                    'nomor_order' => $order->nomor_order,
                    'is_done' => $item['is_done'],
                    'source_menu' => $item['source_menu'],
                    'status_label' => $item['status_label'],
                ];
            })
            ->sortBy([
                ['is_done', 'asc'],
                ['date', 'asc'],
            ])
            ->values()
            ->all();

        return view('dashboards.pkm', [
            'pageTitle' => 'Dashboard',
            'pageDescription' => 'Ringkasan utama aktivitas vendor PKM dan status pekerjaan yang sedang berjalan.',
            'targetDates' => $targets,
            'jobProgress' => $progressItems->all(),
            'totalPekerjaan' => $totalPekerjaan,
            'pekerjaanSelesai' => $dokumenFinalCount,
            'pekerjaanMenunggu' => $listPekerjaanCount,
            'totalProgress' => $totalProgress,
            'overdueCount' => $overdueCount,
            'todayCount' => $todayCount,
            'soonCount' => $soonCount,
            'menuSummaries' => $menuSummaries,
            'statusBreakdown' => $statusBreakdown,
            'progressTrend' => $recentTrend,
            'jobHighlights' => $jobHighlights,
        ]);
    }
}
