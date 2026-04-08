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
                    'lpjPpl:id,lhpp_bast_id,lpj_number_termin1,ppl_number_termin1,lpj_document_path_termin1,ppl_document_path_termin1,lpj_number_termin2,ppl_number_termin2,lpj_document_path_termin2,ppl_document_path_termin2',
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
                    $lpj = $lhpp->lpjPpl;
                    $lpjPresent = filled($lpj?->lpj_number_termin1)
                        || filled($lpj?->ppl_number_termin1)
                        || filled($lpj?->lpj_document_path_termin1)
                        || filled($lpj?->ppl_document_path_termin1)
                        || filled($lpj?->lpj_number_termin2)
                        || filled($lpj?->ppl_number_termin2)
                        || filled($lpj?->lpj_document_path_termin2)
                        || filled($lpj?->ppl_document_path_termin2);

                    $has3Ttd = ($lhpp->quality_control_status ?? 'pending') === 'approved';
                    $garansiMonths = match ($lhpp->approval_threshold) {
                        'over_250' => 6,
                        'under_250' => 3,
                        default => null,
                    };

                    $startDate = $has3Ttd && $lhpp->tanggal_bast
                        ? $lhpp->tanggal_bast->copy()
                        : null;

                    $endDate = ($startDate && $garansiMonths !== null)
                        ? $startDate->copy()->addMonthsNoOverflow($garansiMonths)
                        : null;

                    $status = null;
                    if ($startDate && $endDate) {
                        $status = now()->startOfDay()->lte($endDate->copy()->startOfDay())
                            ? 'Masih Berlaku'
                            : 'Habis';
                    }

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
                        'garansi_label' => $garansiMonths !== null ? "Dummy {$garansiMonths} Bulan" : '',
                        'status' => $status,
                        'has_3_ttd' => $has3Ttd,
                        'lpj_present' => $lpjPresent,
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
