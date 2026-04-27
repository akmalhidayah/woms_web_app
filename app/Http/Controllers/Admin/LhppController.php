<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Garansi;
use App\Models\LhppBast;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LhppController extends Controller
{
    public function updateQualityControl(Request $request, int $lhppId)
    {
        try {
            $lhpp = LhppBast::query()->findOrFail($lhppId);

            $validated = $request->validate([
                'quality_control_status' => ['required', 'in:pending,approved,rejected'],
            ]);

            $lhpp->quality_control_status = $validated['quality_control_status'];
            $lhpp->updated_by = $request->user()?->id;
            $lhpp->save();

            $lhpp->childLhppBasts()->update([
                'quality_control_status' => $validated['quality_control_status'],
                'updated_by' => $request->user()?->id,
            ]);

            return redirect()
                ->route('admin.lhpp.index', $request->only('search', 'page'))
                ->with('status', sprintf('Quality control untuk order %s berhasil diperbarui.', $lhpp->nomor_order));
        } catch (Throwable $exception) {
            Log::error('Failed to update admin BAST quality control.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhpp->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return back()->withErrors([
                'quality_control_status' => 'Terjadi kesalahan saat memperbarui quality control BAST.',
            ]);
        }
    }

    public function index(Request $request): View
    {
        try {
            $search = trim((string) $request->string('search'));

            $lhpps = LhppBast::query()
                ->with([
                    'order:id,nomor_order,unit_kerja,seksi',
                    'purchaseOrder:id,order_id,purchase_order_number',
                    'terminTwo',
                    'garansi',
                ])
                ->where('termin_type', 'termin_1')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('nomor_order', 'like', "%{$search}%")
                            ->orWhere('purchase_order_number', 'like', "%{$search}%")
                            ->orWhere('unit_kerja', 'like', "%{$search}%")
                            ->orWhere('seksi', 'like', "%{$search}%")
                            ->orWhere('deskripsi_pekerjaan', 'like', "%{$search}%");
                    });
                })
                ->latest('id')
                ->paginate(10)
                ->withQueryString();

            return view('admin.lhpp.index', [
                'search' => $search,
                'lhpps' => $lhpps,
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to load admin BAST index page.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat halaman BAST admin.');
        }
    }

    public function pdf(Request $request, int $lhppId)
    {
        try {
            $lhpp = LhppBast::query()->findOrFail($lhppId);

            $lhpp->loadMissing([
                'images',
                'parentLhppBast.images',
                'parentLhppBast.purchaseOrder:id,order_id,purchase_order_number',
                'parentLhppBast.order.purchaseOrder:id,order_id,purchase_order_number',
                'hpp.order',
                'hpp.outlineAgreement.unitWork.department',
                'hpp.creator',
                'purchaseOrder:id,order_id,purchase_order_number',
                'order.purchaseOrder:id,order_id,purchase_order_number',
                'order.latestHpp.order',
                'order.latestHpp.outlineAgreement.unitWork.department',
                'order.latestHpp.creator',
            ]);

            $bastPdf = Pdf::loadView('pkm.lhpp.pdf', [
                'lhpp' => $lhpp,
                'materialItems' => collect($lhpp->material_items ?? []),
                'serviceItems' => collect($lhpp->service_items ?? []),
            ])->setPaper('a4', 'portrait')->output();

            $terminSlug = $lhpp->termin_type === 'termin_2' ? 'termin-2' : 'termin-1';

            $attachedHpp = $lhpp->hpp ?: $lhpp->order?->latestHpp;
            $terminOnePdf = null;

            if ($lhpp->termin_type === 'termin_2' && $lhpp->parentLhppBast) {
                $terminOnePdf = Pdf::loadView('pkm.lhpp.pdf', [
                    'lhpp' => $lhpp->parentLhppBast,
                    'materialItems' => collect($lhpp->parentLhppBast->material_items ?? []),
                    'serviceItems' => collect($lhpp->parentLhppBast->service_items ?? []),
                ])->setPaper('a4', 'portrait')->output();
            }

            if (! $attachedHpp) {
                $pdfOutput = $terminOnePdf
                    ? $this->mergePdfOutputs([$bastPdf, $terminOnePdf])
                    : $bastPdf;

                return response($pdfOutput, Response::HTTP_OK, $this->pdfInlineHeaders(
                    'bast-'.$terminSlug.'-'.$lhpp->nomor_order.'.pdf'
                ));
            }

            $hppPdf = Pdf::loadView('admin.hpp.hpppdf', [
                'hpp' => $attachedHpp,
            ])->setPaper('a4', 'landscape')->output();

            $mergedPdf = $this->mergePdfOutputs(array_filter([$bastPdf, $terminOnePdf, $hppPdf]));

            Log::info('Admin BAST PDF merged successfully.', [
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhpp->id,
                'nomor_order' => $lhpp->nomor_order,
                'termin_type' => $lhpp->termin_type,
                'attached_hpp_id' => $attachedHpp->id,
                'bast_pdf_bytes' => strlen($bastPdf),
                'hpp_pdf_bytes' => strlen($hppPdf),
                'merged_pdf_bytes' => strlen($mergedPdf),
            ]);

            return response($mergedPdf, Response::HTTP_OK, $this->pdfInlineHeaders(
                'bast-'.$terminSlug.'-'.$lhpp->nomor_order.'.pdf'
            ));
        } catch (Throwable $exception) {
            Log::error('Failed to generate admin BAST PDF.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhppId,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat membuat PDF BAST admin.');
        }
    }

    public function pdfByOrder(Request $request, string $nomorOrder, string $termin)
    {
        $terminType = $termin === 'termin-2' ? 'termin_2' : 'termin_1';

        try {
            $lhpp = LhppBast::query()
                ->where('nomor_order', $nomorOrder)
                ->where('termin_type', $terminType)
                ->firstOrFail();

            return $this->pdf($request, $lhpp->id);
        } catch (Throwable $exception) {
            Log::error('Failed to generate admin BAST PDF by order.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'nomor_order' => $nomorOrder,
                'termin_type' => $terminType,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat membuat PDF BAST admin.');
        }
    }

    /**
     * @param array<int, string> $pdfOutputs
     */
    private function mergePdfOutputs(array $pdfOutputs): string
    {
        $pdfOutputs = array_values(array_filter(
            $pdfOutputs,
            static fn ($pdfOutput): bool => is_string($pdfOutput) && trim($pdfOutput) !== ''
        ));

        if ($pdfOutputs === []) {
            return '';
        }

        if (! class_exists(Fpdi::class)) {
            Log::warning('FPDI package is unavailable. Returning the first PDF output without merge.', [
                'controller' => static::class,
                'pdf_count' => count($pdfOutputs),
            ]);

            return $pdfOutputs[0];
        }

        $fpdi = new Fpdi();
        $temporaryFiles = [];

        try {
            foreach ($pdfOutputs as $pdfOutput) {
                if (! is_string($pdfOutput) || trim($pdfOutput) === '') {
                    continue;
                }

                $temporaryFile = tempnam(sys_get_temp_dir(), 'woms-pdf-');

                if ($temporaryFile === false) {
                    continue;
                }

                file_put_contents($temporaryFile, $pdfOutput);
                $temporaryFiles[] = $temporaryFile;

                $pageCount = $fpdi->setSourceFile($temporaryFile);

                for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                    $templateId = $fpdi->importPage($pageNumber);
                    $templateSize = $fpdi->getTemplateSize($templateId);
                    $orientation = $templateSize['width'] > $templateSize['height'] ? 'L' : 'P';

                    $fpdi->AddPage($orientation, [$templateSize['width'], $templateSize['height']]);
                    $fpdi->useTemplate($templateId);
                }
            }

            return $fpdi->Output('S');
        } finally {
            foreach ($temporaryFiles as $temporaryFile) {
                if (is_string($temporaryFile) && is_file($temporaryFile)) {
                    @unlink($temporaryFile);
                }
            }
        }
    }

    public function updateGaransi(Request $request, int $lhppId)
    {
        try {
            $lhpp = LhppBast::query()
                ->where('termin_type', 'termin_1')
                ->findOrFail($lhppId);

            $validated = $request->validate([
                'garansi_months' => ['required', 'integer', 'in:0,1,3,6,12'],
            ], [
                'garansi_months.required' => 'Durasi garansi wajib dipilih.',
                'garansi_months.in' => 'Durasi garansi tidak valid.',
            ]);

            $garansiMonths = (int) $validated['garansi_months'];
            $startDate = Carbon::today();
            $endDate = $garansiMonths > 0
                ? $startDate->copy()->addMonthsNoOverflow($garansiMonths)
                : null;

            Garansi::query()->updateOrCreate(
                ['lhpp_bast_id' => $lhpp->id],
                [
                    'garansi_months' => $garansiMonths,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'created_by' => $lhpp->garansi?->created_by ?? $request->user()?->id,
                    'updated_by' => $request->user()?->id,
                ],
            );

            return redirect()
                ->route('admin.lhpp.index', $request->only('search', 'page'))
                ->with('status', sprintf('Garansi untuk order %s berhasil diperbarui.', $lhpp->nomor_order));
        } catch (Throwable $exception) {
            Log::error('Failed to update admin BAST garansi.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhppId,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return back()->withErrors([
                'garansi_months' => 'Terjadi kesalahan saat memperbarui data garansi.',
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function pdfInlineHeaders(string $filename): array
    {
        return [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }
}
