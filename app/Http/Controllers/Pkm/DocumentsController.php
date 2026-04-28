<?php

namespace App\Http\Controllers\Pkm;

use App\Http\Controllers\Controller;
use App\Models\LhppBast;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DocumentsController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('notification_number'));
        $status = trim((string) $request->string('status'));

        $rows = Order::query()
            ->with([
                'latestHpp' => fn ($query) => $query->select([
                    'hpps.id',
                    'hpps.order_id',
                    'hpps.nomor_order',
                    'hpps.total_keseluruhan',
                ]),
                'latestPurchaseOrder' => fn ($query) => $query->select([
                    'purchase_orders.id',
                    'purchase_orders.order_id',
                    'purchase_orders.purchase_order_number',
                    'purchase_orders.po_document_path',
                ]),
                'lhppBasts' => fn ($query) => $query
                    ->select([
                        'id',
                        'order_id',
                        'termin_type',
                        'nomor_order',
                        'deskripsi_pekerjaan',
                        'purchase_order_number',
                        'total_aktual_biaya',
                        'termin_1_nilai',
                        'termin_2_nilai',
                        'termin1_status',
                        'termin2_status',
                    ])
                    ->where('termin_type', 'termin_1')
                    ->with([
                        'lpjPpl:id,lhpp_bast_id,lpj_document_path_termin1,ppl_document_path_termin1,lpj_document_path_termin2,ppl_document_path_termin2',
                        'garansi:id,lhpp_bast_id,start_date,end_date,garansi_months',
                    ]),
            ])
            ->whereHas('purchaseOrder', function (Builder $query): void {
                $query
                    ->where('approve_manager', true)
                    ->whereNotNull('purchase_order_number')
                    ->whereRaw("TRIM(purchase_order_number) <> ''");
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('nomor_order', 'like', "%{$search}%")
                        ->orWhere('notifikasi', 'like', "%{$search}%")
                        ->orWhere('nama_pekerjaan', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->through(function (Order $order): array {
                /** @var LhppBast|null $terminOne */
                $terminOne = $order->lhppBasts->first();
                $lpjPpl = $terminOne?->lpjPpl;
                $garansi = $terminOne?->garansi;

                $hasHpp = (bool) $order->latestHpp;
                $hasPo = (bool) $order->latestPurchaseOrder?->po_document_path;
                $hasBast = (bool) $terminOne;
                $hasLpjPpl = (bool) ($lpjPpl?->lpj_document_path_termin1 && $lpjPpl?->ppl_document_path_termin1);
                $isWithoutWarranty = (int) ($garansi?->garansi_months ?? -1) === 0;

                $isComplete = $hasHpp && $hasPo && $hasBast && $hasLpjPpl;

                $paidPercent = 0;
                $paidAmount = 0;

                if ($isWithoutWarranty && ($terminOne?->termin1_status ?? 'belum') === 'sudah') {
                    $paidPercent = 100;
                    $paidAmount = (float) ($terminOne?->total_aktual_biaya ?? 0);
                } elseif (($terminOne?->termin2_status ?? 'belum') === 'sudah') {
                    $paidPercent = 100;
                    $paidAmount = (float) ($terminOne?->total_aktual_biaya ?? 0);
                } elseif (($terminOne?->termin1_status ?? 'belum') === 'sudah') {
                    $paidPercent = 95;
                    $paidAmount = (float) ($terminOne?->termin_1_nilai ?? 0);
                }

                return [
                    'id' => $order->id,
                    'nomor_order' => $order->nomor_order,
                    'notification_number' => $order->notifikasi,
                    'created_at' => $order->created_at,
                    'job_name' => $order->nama_pekerjaan,
                    'purchase_order_number' => $order->latestPurchaseOrder?->purchase_order_number,
                    'hpp_url' => $hasHpp ? route('pkm.jobwaiting.hpp.pdf', ['hpp' => $order->latestHpp->nomor_order]) : null,
                    'po_url' => ($hasHpp && $hasPo)
                        ? route('pkm.jobwaiting.purchase-order.document', ['hpp' => $order->latestHpp->nomor_order])
                        : null,
                    'bast_url' => $hasBast
                        ? route('pkm.lhpp.pdf', ['nomorOrder' => $terminOne->nomor_order, 'termin' => 'termin-1'])
                        : null,
                    'lpj_url_termin1' => ($lpjPpl?->lpj_document_path_termin1)
                        ? route('pkm.laporan.preview', ['nomorOrder' => $terminOne->nomor_order, 'kind' => 'lpj', 'termin' => 1])
                        : null,
                    'ppl_url_termin1' => ($lpjPpl?->ppl_document_path_termin1)
                        ? route('pkm.laporan.preview', ['nomorOrder' => $terminOne->nomor_order, 'kind' => 'ppl', 'termin' => 1])
                        : null,
                    'lpj_url_termin2' => (! $isWithoutWarranty && $lpjPpl?->lpj_document_path_termin2)
                        ? route('pkm.laporan.preview', ['nomorOrder' => $terminOne->nomor_order, 'kind' => 'lpj', 'termin' => 2])
                        : null,
                    'ppl_url_termin2' => (! $isWithoutWarranty && $lpjPpl?->ppl_document_path_termin2)
                        ? route('pkm.laporan.preview', ['nomorOrder' => $terminOne->nomor_order, 'kind' => 'ppl', 'termin' => 2])
                        : null,
                    'total_biaya' => (float) ($terminOne?->total_aktual_biaya ?? 0),
                    'paid_percent' => $paidPercent,
                    'paid_amount' => $paidAmount,
                    'garansi_start' => $garansi?->start_date,
                    'garansi_end' => $garansi?->end_date,
                    'garansi_months' => $garansi?->garansi_months,
                    'is_without_warranty' => $isWithoutWarranty,
                    'is_complete' => $isComplete,
                ];
            })
            ->withQueryString();

        if ($status !== '') {
            $filtered = collect($rows->items())->filter(function (array $row) use ($status): bool {
                return match ($status) {
                    'complete' => (bool) $row['is_complete'],
                    'incomplete' => ! $row['is_complete'],
                    default => true,
                };
            })->values();

            $rows->setCollection($filtered);
        }

        return view('dashboards.pkm', [
            'pageTitle' => 'Dokumen',
            'pageDescription' => 'Ringkasan compact dokumen pekerjaan, LPJ/PPL, pembayaran, dan garansi.',
            'documentRows' => $rows,
            'documentSearch' => $search,
            'documentStatus' => $status,
        ]);
    }

    public function previewLpjPpl(string $nomorOrder, string $kind, int $termin): BinaryFileResponse
    {
        abort_unless(in_array($kind, ['lpj', 'ppl'], true), Response::HTTP_NOT_FOUND);
        abort_unless(in_array($termin, [1, 2], true), Response::HTTP_NOT_FOUND);

        $lhppBast = LhppBast::query()
            ->with('lpjPpl')
            ->where('termin_type', 'termin_1')
            ->where('nomor_order', $nomorOrder)
            ->firstOrFail();

        $lpjPpl = $lhppBast->lpjPpl;

        abort_if(! $lpjPpl, Response::HTTP_NOT_FOUND, 'Dokumen LPJ/PPL tidak ditemukan.');

        $path = match ([$kind, $termin]) {
            ['lpj', 1] => $lpjPpl->lpj_document_path_termin1,
            ['ppl', 1] => $lpjPpl->ppl_document_path_termin1,
            ['lpj', 2] => $lpjPpl->lpj_document_path_termin2,
            ['ppl', 2] => $lpjPpl->ppl_document_path_termin2,
        };

        abort_if(blank($path), Response::HTTP_NOT_FOUND, 'Dokumen LPJ/PPL tidak ditemukan.');

        $absolutePath = storage_path('app/public/'.ltrim((string) $path, '/'));

        abort_unless(is_file($absolutePath), Response::HTTP_NOT_FOUND, 'File dokumen tidak ditemukan.');

        return response()->file($absolutePath);
    }
}
