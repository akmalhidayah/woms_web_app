<?php

namespace App\Http\Controllers\Pkm;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LhppController extends Controller
{
    public function create(Request $request): View
    {
        try {
            $orders = Order::query()
                ->with([
                    'latestHpp' => fn ($query) => $query->select([
                        'hpps.id',
                        'hpps.order_id',
                        'hpps.total_keseluruhan',
                    ]),
                    'purchaseOrder:id,order_id,purchase_order_number',
                ])
                ->whereHas('purchaseOrder', function (Builder $query): void {
                    $query
                        ->whereNotNull('purchase_order_number')
                        ->whereRaw("TRIM(purchase_order_number) <> ''");
                })
                ->latest('id')
                ->get([
                    'id',
                    'nomor_order',
                    'nama_pekerjaan',
                    'unit_kerja',
                    'seksi',
                ]);

            $orderOptions = $orders
                ->map(fn (Order $order): array => [
                    'nomor_order' => (string) $order->nomor_order,
                    'deskripsi_pekerjaan' => (string) ($order->nama_pekerjaan ?? ''),
                    'unit_kerja_peminta' => (string) ($order->seksi ?: $order->unit_kerja ?: ''),
                    'unit_kerja' => (string) ($order->unit_kerja ?? ''),
                    'seksi' => (string) ($order->seksi ?? ''),
                    'purchase_order_number' => (string) ($order->purchaseOrder?->purchase_order_number ?? ''),
                    'nilai_ece' => (float) ($order->latestHpp?->total_keseluruhan ?? 0),
                ])
                ->values();

            $selectedOrder = trim((string) $request->string('order'));

            if ($selectedOrder === '' || ! $orderOptions->firstWhere('nomor_order', $selectedOrder)) {
                $selectedOrder = (string) ($orderOptions->first()['nomor_order'] ?? '');
            }

            return view('dashboards.pkm', [
                'pageTitle' => 'Form LHPP',
                'pageDescription' => 'Form pembuatan BAST termin 1 PKM.',
                'bastOrderOptions' => $orderOptions,
                'selectedBastOrder' => $selectedOrder,
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to load PKM LHPP create form.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat form LHPP PKM.');
        }
    }
}
