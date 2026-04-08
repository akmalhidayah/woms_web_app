<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Http\Controllers\Controller;
use App\Models\LhppBast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
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
                    'order.documents:id,order_id,jenis_dokumen,nama_file_asli,path_file',
                    'garansi:id,lhpp_bast_id,garansi_months,start_date,end_date',
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

                    $gambar = collect($lhpp->order?->documents ?? [])
                        ->filter(function ($document): bool {
                            $type = $document->jenis_dokumen?->value ?? (string) $document->jenis_dokumen;
                            $extension = strtolower(pathinfo((string) $document->path_file, PATHINFO_EXTENSION));

                            return in_array($type, [
                                OrderDocumentType::Abnormalitas->value,
                                OrderDocumentType::GambarTeknik->value,
                                OrderDocumentType::Garansi->value,
                            ], true) && in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true);
                        })
                        ->map(fn ($document) => Storage::url($document->path_file))
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
}
