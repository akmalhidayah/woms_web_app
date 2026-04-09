<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LhppBast;
use App\Models\LhppBastImage;
use Illuminate\Http\Request;
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

            $garansiList = LhppBast::query()
                ->with([
                    'order:id,nomor_order',
                    'garansi:id,lhpp_bast_id,garansi_months,start_date,end_date',
                    'images:id,lhpp_bast_id,file_path,file_name,mime_type',
                    'terminTwo:id,parent_lhpp_bast_id',
                    'terminTwo.images:id,lhpp_bast_id,file_path,file_name,mime_type',
                ])
                ->where('termin_type', 'termin_1')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('nomor_order', 'like', "%{$search}%")
                            ->orWhere('purchase_order_number', 'like', "%{$search}%")
                            ->orWhere('unit_kerja', 'like', "%{$search}%")
                            ->orWhere('seksi', 'like', "%{$search}%");
                    });
                })
                ->latest('id')
                ->paginate(10)
                ->through(function (LhppBast $lhpp): array {
                    $garansi = $lhpp->garansi;
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

                    $gambar = collect($lhpp->images ?? [])
                        ->concat(collect($lhpp->terminTwo?->images ?? []))
                        ->filter(fn ($image) => filled($image->file_path))
                        ->map(fn (LhppBastImage $image) => route('admin.garansi.image', ['image' => $image->id]))
                        ->unique()
                        ->values()
                        ->all();

                    return [
                        'order_number' => $lhpp->nomor_order ?: ($lhpp->order?->nomor_order ?? '-'),
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
}
