<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Garansi;
use App\Models\LhppBast;
use App\Models\LhppBastImage;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GaransiController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $search = trim((string) $request->string('search'));

            $garansiList = $this->garansiEligibleOrders()
                ->with([
                    'garansi:id,order_id,lhpp_bast_id,garansi_months,start_date,end_date',
                    'lhppBasts' => fn ($query) => $query
                        ->select(['id', 'order_id', 'parent_lhpp_bast_id', 'termin_type'])
                        ->whereIn('termin_type', ['termin_1', 'termin_2']),
                    'lhppBasts.images:id,lhpp_bast_id,file_path,file_name,mime_type',
                ])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('nomor_order', 'like', "%{$search}%")
                            ->orWhere('notifikasi', 'like', "%{$search}%")
                            ->orWhere('unit_kerja', 'like', "%{$search}%")
                            ->orWhere('seksi', 'like', "%{$search}%");
                    });
                })
                ->latest('id')
                ->paginate(10)
                ->through(function (Order $order): array {
                    $garansi = $order->garansi;
                    $garansiMonths = $garansi?->garansi_months;
                    $startDate = $garansi?->start_date;
                    $endDate = $garansi?->end_date;
                    $status = match (true) {
                        $garansiMonths === null => 'Belum Diatur',
                        $garansiMonths === 0 => 'Tidak Memiliki Garansi',
                        $endDate && now()->startOfDay()->lte($endDate->copy()->startOfDay()) => 'Masih Berlaku',
                        $endDate !== null => 'Sudah Berakhir',
                        default => 'Belum Diatur',
                    };

                    $gambar = $order->lhppBasts
                        ->flatMap(fn (LhppBast $lhpp): iterable => $lhpp->images ?? [])
                        ->filter(fn ($image) => filled($image->file_path))
                        ->map(fn (LhppBastImage $image) => route('admin.garansi.image', ['image' => $image->id]))
                        ->unique()
                        ->values()
                        ->all();

                    return [
                        'order_id' => $order->id,
                        'order_number' => $order->nomor_order ?: '-',
                        'order_route_key' => $order->nomor_order,
                        'unit_kerja' => $order->seksi ?: $order->unit_kerja ?: '-',
                        'ttd_date' => $startDate?->format('d-m-Y') ?? '-',
                        'end_date' => $endDate?->format('d-m-Y') ?? '-',
                        'garansi_months' => $garansiMonths,
                        'garansi_label' => $garansiMonths !== null ? "{$garansiMonths} Bulan" : '',
                        'status' => $status,
                        'gambar' => $gambar,
                    ];
                })
                ->withQueryString();

            return view('admin.garansi.index', [
                'search' => $search,
                'garansiList' => $garansiList,
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to load admin garansi page.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat halaman Garansi admin.');
        }
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        try {
            abort_unless(
                $this->garansiEligibleOrders()->whereKey($order->id)->exists(),
                Response::HTTP_NOT_FOUND,
                'Order tidak ditemukan atau belum memenuhi syarat set garansi.'
            );

            $validated = $request->validate([
                'garansi_months' => ['required', 'integer', 'in:0,1,3,6,12'],
            ], [
                'garansi_months.required' => 'Durasi garansi wajib dipilih.',
                'garansi_months.in' => 'Durasi garansi tidak valid.',
            ]);

            $garansiMonths = (int) $validated['garansi_months'];

            if ($garansiMonths === 0 && $order->lhppBasts()->where('termin_type', 'termin_2')->exists()) {
                return back()->withErrors([
                    'garansi_months' => 'Order ini sudah memiliki BAST Termin 2, sehingga garansi tidak bisa diubah menjadi 0 bulan.',
                ]);
            }

            $startDate = Carbon::today();
            $endDate = $garansiMonths > 0
                ? $startDate->copy()->addMonthsNoOverflow($garansiMonths)
                : null;
            $terminOne = $order->lhppBasts()
                ->where('termin_type', 'termin_1')
                ->first();

            Garansi::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'lhpp_bast_id' => $terminOne?->id,
                    'garansi_months' => $garansiMonths,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'created_by' => $order->garansi?->created_by ?? $request->user()?->id,
                    'updated_by' => $request->user()?->id,
                ],
            );

            return redirect()
                ->route('admin.garansi.index', $request->only('search', 'page'))
                ->with('status', sprintf('Garansi untuk order %s berhasil diperbarui.', $order->nomor_order));
        } catch (Throwable $exception) {
            Log::error('Failed to update admin garansi.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return back()->withErrors([
                'garansi_months' => 'Terjadi kesalahan saat memperbarui data garansi.',
            ]);
        }
    }

    public function image(int $image): BinaryFileResponse
    {
        $imageRecord = LhppBastImage::query()->findOrFail($image);
        $path = storage_path('app/public/'.ltrim((string) $imageRecord->file_path, '/'));

        abort_unless(is_file($path), Response::HTTP_NOT_FOUND, 'Gambar tidak ditemukan.');

        return response()->file($path, [
            'Content-Type' => $imageRecord->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function garansiEligibleOrders()
    {
        return Order::query()
            ->where(function ($query): void {
                $query
                    ->whereHas('purchaseOrder', function ($purchaseOrderQuery): void {
                        $purchaseOrderQuery
                            ->whereNotNull('purchase_order_number')
                            ->whereRaw("TRIM(purchase_order_number) <> ''")
                            ->where('progress_pekerjaan', 100);
                    })
                    ->orWhereHas('initialWork', function ($initialWorkQuery): void {
                        $initialWorkQuery->where('progress_pekerjaan', 100);
                    });
            });
    }
}
