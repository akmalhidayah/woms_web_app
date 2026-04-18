<?php

namespace App\Http\Controllers\Pkm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pkm\StoreLhppBastRequest;
use App\Models\FabricationConstructionContract;
use App\Models\LhppBast;
use App\Models\LhppBastImage;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LhppController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $filters = [
                'search' => trim((string) $request->string('search')),
                'unit_kerja' => trim((string) $request->string('unit_kerja')),
                'purchase_order_number' => trim((string) $request->string('purchase_order_number')),
                'termin_status' => trim((string) $request->string('termin_status', 'all')),
            ];

            $baseQuery = LhppBast::query()
                ->with([
                    'terminTwo',
                    'order:id,nomor_order,notifikasi',
                ])
                ->where('termin_type', 'termin_1')
                ->when($filters['search'] !== '', function ($query) use ($filters): void {
                    $needle = $filters['search'];

                    $query->where(function ($builder) use ($needle): void {
                        $builder
                            ->where('nomor_order', 'like', "%{$needle}%")
                            ->orWhere('purchase_order_number', 'like', "%{$needle}%")
                            ->orWhere('unit_kerja', 'like', "%{$needle}%")
                            ->orWhere('seksi', 'like', "%{$needle}%")
                            ->orWhereHas('order', function ($orderQuery) use ($needle): void {
                                $orderQuery->where('notifikasi', 'like', "%{$needle}%");
                            });
                    });
                })
                ->when($filters['unit_kerja'] !== '', fn ($query) => $query->where('unit_kerja', $filters['unit_kerja']))
                ->when($filters['purchase_order_number'] !== '', fn ($query) => $query->where('purchase_order_number', $filters['purchase_order_number']))
                ->when($filters['termin_status'] !== 'all', function ($query) use ($filters): void {
                    match ($filters['termin_status']) {
                        't1_paid' => $query->where('termin1_status', 'sudah'),
                        't1_unpaid' => $query->where('termin1_status', '!=', 'sudah'),
                        't2_paid' => $query->where('termin2_status', 'sudah'),
                        't2_unpaid' => $query->where('termin2_status', '!=', 'sudah'),
                        default => null,
                    };
                });

            $units = LhppBast::query()
                ->where('termin_type', 'termin_1')
                ->whereNotNull('unit_kerja')
                ->whereRaw("TRIM(unit_kerja) <> ''")
                ->orderBy('unit_kerja')
                ->pluck('unit_kerja')
                ->unique()
                ->values();

            $pos = LhppBast::query()
                ->where('termin_type', 'termin_1')
                ->whereNotNull('purchase_order_number')
                ->whereRaw("TRIM(purchase_order_number) <> ''")
                ->orderBy('purchase_order_number')
                ->pluck('purchase_order_number')
                ->unique()
                ->values();

            $lhpps = $baseQuery
                ->latest('id')
                ->paginate(8)
                ->withQueryString();

            $pendingTerminOneOrders = $this->eligibleOrders()
                ->map(fn (Order $order): array => [
                    'nomor_order' => (string) $order->nomor_order,
                    'notifikasi' => (string) ($order->notifikasi ?? ''),
                    'deskripsi_pekerjaan' => (string) ($order->nama_pekerjaan ?? ''),
                    'purchase_order_number' => (string) ($order->purchaseOrder?->purchase_order_number ?? ''),
                    'unit_kerja' => (string) ($order->unit_kerja ?? ''),
                    'seksi' => (string) ($order->seksi ?? ''),
                ])
                ->values();

            return view('dashboards.pkm', [
                'pageTitle' => 'BAST / LHPP',
                'pageDescription' => 'Monitoring laporan hasil pekerjaan dan dokumen BAST/LHPP PKM.',
                'lhpps' => $lhpps,
                'filters' => $filters,
                'units' => $units,
                'pos' => $pos,
                'pendingTerminOneOrders' => $pendingTerminOneOrders,
                'activeTokens' => collect(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to load PKM LHPP index page.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat daftar LHPP PKM.');
        }
    }

    public function create(Request $request): View
    {
        try {
            return $this->buildFormView($request, null, [
                'pageTitle' => 'Form LHPP',
                'pageDescription' => 'Form pembuatan BAST termin 1 PKM.',
                'formTitle' => 'Buat BAST Termin 1',
                'formAction' => route('pkm.lhpp.store'),
                'formMethod' => 'POST',
                'submitLabel' => 'Simpan',
            ], 'termin_1');
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

    public function createTerminTwo(Request $request, string $nomorOrder): View|RedirectResponse
    {
        try {
            $terminOne = $this->resolveLhppByOrderAndTermin($nomorOrder, 'termin_1');

            abort_if(! $terminOne, Response::HTTP_NOT_FOUND, 'BAST Termin 1 tidak ditemukan.');
            abort_if(($terminOne->termin1_status ?? 'belum') !== 'sudah', Response::HTTP_BAD_REQUEST, 'BAST Termin 2 hanya bisa dibuat setelah Termin 1 sudah dibayar.');

            if ($terminOne->terminTwo) {
                return redirect()->route('pkm.lhpp.edit', [
                    'nomorOrder' => $terminOne->nomor_order,
                    'termin' => 'termin-2',
                ]);
            }

            return $this->buildFormView($request, null, [
                'pageTitle' => 'Form LHPP',
                'pageDescription' => 'Form pembuatan BAST termin 2 PKM.',
                'formTitle' => 'Buat BAST Termin 2',
                'formAction' => route('pkm.lhpp.store'),
                'formMethod' => 'POST',
                'submitLabel' => 'Simpan',
            ], 'termin_2', $terminOne);
        } catch (Throwable $exception) {
            Log::error('Failed to load PKM LHPP termin 2 create form.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'nomor_order' => $nomorOrder,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat form BAST Termin 2 PKM.');
        }
    }

    public function edit(Request $request, string $nomorOrder, string $termin): View
    {
        $terminType = $this->normalizeTerminType($termin);

        try {
            $lhpp = $this->resolveLhppByOrderAndTermin($nomorOrder, $terminType);

            abort_if(! $lhpp, Response::HTTP_NOT_FOUND, 'Data BAST tidak ditemukan.');

            return $this->buildFormView($request, $lhpp, [
                'pageTitle' => 'Edit LHPP',
                'pageDescription' => sprintf('Pembaruan data BAST %s PKM.', $this->terminLabel($terminType)),
                'formTitle' => sprintf('Edit BAST %s', $this->terminLabel($terminType)),
                'formAction' => route('pkm.lhpp.update', [
                    'nomorOrder' => $lhpp->nomor_order,
                    'termin' => $this->terminSlug($terminType),
                ]),
                'formMethod' => 'PATCH',
                'submitLabel' => 'Update',
            ], $terminType, $lhpp->parentLhppBast);
        } catch (Throwable $exception) {
            Log::error('Failed to load PKM LHPP edit form.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'nomor_order' => $nomorOrder,
                'termin_type' => $terminType,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat form edit LHPP PKM.');
        }
    }

    public function calculate(Request $request): JsonResponse
    {
        $calculation = $this->calculateRows(
            $request->input('material_rows', []),
            $request->input('service_rows', []),
            true,
        );

        return response()->json($calculation);
    }

    public function store(StoreLhppBastRequest $request): RedirectResponse
    {
        $terminType = $this->normalizeTerminType($request->string('termin_type')->toString());

        try {
            [$order, $parentLhpp] = $this->resolveStoreContext(
                trim((string) $request->input('nomor_order')),
                $terminType,
            );

            abort_if(! $order, Response::HTTP_NOT_FOUND, 'Order tidak ditemukan atau belum memenuhi syarat BAST.');

            $purchaseOrder = $order->purchaseOrder;
            $latestHpp = $order->latestHpp;
            [$materialRowsPayload, $serviceRowsPayload] = $this->resolveActualRowsPayload(
                $terminType,
                $parentLhpp,
                $request->input('material_rows', []),
                $request->input('service_rows', []),
            );
            $calculation = $this->calculateRows(
                $materialRowsPayload,
                $serviceRowsPayload,
            );
            $qualityControlStatus = $terminType === 'termin_2'
                ? ($parentLhpp?->quality_control_status ?? 'pending')
                : 'pending';

            $lhpp = LhppBast::query()->updateOrCreate(
                [
                    'order_id' => $order->id,
                    'termin_type' => $terminType,
                ],
                [
                    'parent_lhpp_bast_id' => $terminType === 'termin_2' ? $parentLhpp?->id : null,
                    'hpp_id' => $latestHpp?->id,
                    'purchase_order_id' => $purchaseOrder?->id,
                    'nomor_order' => $order->nomor_order,
                    'notifikasi' => $order->notifikasi,
                    'purchase_order_number' => $purchaseOrder?->purchase_order_number,
                    'deskripsi_pekerjaan' => $order->nama_pekerjaan,
                    'unit_kerja' => $order->unit_kerja,
                    'seksi' => $order->seksi,
                    'tanggal_bast' => $request->date('tanggal_bast'),
                    'tanggal_mulai_pekerjaan' => $request->date('tanggal_mulai_pekerjaan'),
                    'tanggal_selesai_pekerjaan' => $request->date('tanggal_selesai_pekerjaan'),
                    'approval_threshold' => $this->resolveThresholdFromTotals($terminType, $calculation['totals']),
                    'nilai_hpp' => $latestHpp?->total_keseluruhan ?? 0,
                    'material_items' => $calculation['material_rows'],
                    'service_items' => $calculation['service_rows'],
                    'subtotal_material' => $calculation['totals']['subtotal_material'],
                    'subtotal_jasa' => $calculation['totals']['subtotal_jasa'],
                    'total_aktual_biaya' => $calculation['totals']['total_aktual_biaya'],
                    'termin_1_nilai' => $calculation['totals']['termin_1_nilai'],
                    'termin_2_nilai' => $calculation['totals']['termin_2_nilai'],
                    'quality_control_status' => $qualityControlStatus,
                    'updated_by' => $request->user()?->id,
                    'created_by' => $request->user()?->id,
                ]
            );

            $this->storeUploadedImages($request, $lhpp);

            return redirect()
                ->route('pkm.lhpp.index')
                ->with('status', sprintf(
                    'BAST %s untuk order %s berhasil disimpan. Total aktual biaya Rp %s.',
                    $this->terminLabel($terminType),
                    $lhpp->nomor_order,
                    number_format((float) $lhpp->total_aktual_biaya, 0, ',', '.'),
                ));
        } catch (Throwable $exception) {
            Log::error('Failed to store PKM LHPP form.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'termin_type' => $terminType,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'form' => sprintf('Terjadi kesalahan saat menyimpan BAST %s.', $this->terminLabel($terminType)),
                ]);
        }
    }

    public function update(StoreLhppBastRequest $request, string $nomorOrder, string $termin): RedirectResponse
    {
        $terminType = $this->normalizeTerminType($termin);

        try {
            $lhpp = $this->resolveLhppByOrderAndTermin($nomorOrder, $terminType);

            abort_if(! $lhpp, Response::HTTP_NOT_FOUND, 'Data BAST tidak ditemukan.');

            [$order, $parentLhpp] = $this->resolveUpdateContext($lhpp, $terminType);

            $purchaseOrder = $order->purchaseOrder;
            $latestHpp = $order->latestHpp;
            [$materialRowsPayload, $serviceRowsPayload] = $this->resolveActualRowsPayload(
                $terminType,
                $parentLhpp,
                $request->input('material_rows', []),
                $request->input('service_rows', []),
            );
            $calculation = $this->calculateRows(
                $materialRowsPayload,
                $serviceRowsPayload,
            );

            $lhpp->fill([
                'order_id' => $order->id,
                'termin_type' => $terminType,
                'parent_lhpp_bast_id' => $terminType === 'termin_2' ? $parentLhpp?->id : null,
                'hpp_id' => $latestHpp?->id,
                'purchase_order_id' => $purchaseOrder?->id,
                'nomor_order' => $order->nomor_order,
                'notifikasi' => $order->notifikasi,
                'purchase_order_number' => $purchaseOrder?->purchase_order_number,
                'deskripsi_pekerjaan' => $order->nama_pekerjaan,
                'unit_kerja' => $order->unit_kerja,
                'seksi' => $order->seksi,
                'tanggal_bast' => $request->date('tanggal_bast'),
                'tanggal_mulai_pekerjaan' => $request->date('tanggal_mulai_pekerjaan'),
                'tanggal_selesai_pekerjaan' => $request->date('tanggal_selesai_pekerjaan'),
                'approval_threshold' => $this->resolveThresholdFromTotals($terminType, $calculation['totals']),
                'nilai_hpp' => $latestHpp?->total_keseluruhan ?? 0,
                'material_items' => $calculation['material_rows'],
                'service_items' => $calculation['service_rows'],
                'subtotal_material' => $calculation['totals']['subtotal_material'],
                'subtotal_jasa' => $calculation['totals']['subtotal_jasa'],
                'total_aktual_biaya' => $calculation['totals']['total_aktual_biaya'],
                'termin_1_nilai' => $calculation['totals']['termin_1_nilai'],
                'termin_2_nilai' => $calculation['totals']['termin_2_nilai'],
                'quality_control_status' => $terminType === 'termin_2'
                    ? ($parentLhpp?->quality_control_status ?? $lhpp->quality_control_status)
                    : $lhpp->quality_control_status,
                'updated_by' => $request->user()?->id,
            ]);

            $lhpp->save();
            $this->storeUploadedImages($request, $lhpp);

            return redirect()
                ->route('pkm.lhpp.index')
                ->with('status', sprintf(
                    'BAST %s untuk order %s berhasil diperbarui.',
                    $this->terminLabel($terminType),
                    $lhpp->nomor_order,
                ));
        } catch (Throwable $exception) {
            Log::error('Failed to update PKM LHPP form.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'nomor_order' => $nomorOrder,
                'termin_type' => $terminType,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'form' => sprintf('Terjadi kesalahan saat memperbarui BAST %s.', $this->terminLabel($terminType)),
                ]);
        }
    }

    public function destroy(Request $request, string $nomorOrder, string $termin): RedirectResponse
    {
        $terminType = $this->normalizeTerminType($termin);

        try {
            $lhpp = $this->resolveLhppByOrderAndTermin($nomorOrder, $terminType);

            abort_if(! $lhpp, Response::HTTP_NOT_FOUND, 'Data BAST tidak ditemukan.');

            if ($terminType === 'termin_1') {
                $lhpp->childLhppBasts()->delete();
            }

            $lhpp->loadMissing('images');
            foreach ($lhpp->images as $image) {
                if ($image->file_path) {
                    Storage::disk('public')->delete($image->file_path);
                }
            }

            $nomorOrder = $lhpp->nomor_order;
            $termLabel = $this->terminLabel($terminType);
            $lhpp->delete();

            return redirect()
                ->route('pkm.lhpp.index')
                ->with('status', sprintf('BAST %s untuk order %s berhasil dihapus.', $termLabel, $nomorOrder));
        } catch (Throwable $exception) {
            Log::error('Failed to delete PKM LHPP.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'nomor_order' => $nomorOrder,
                'termin_type' => $terminType,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return back()->withErrors([
                'form' => 'Terjadi kesalahan saat menghapus data LHPP.',
            ]);
        }
    }

    public function pdf(Request $request, string $nomorOrder, string $termin)
    {
        $terminType = $this->normalizeTerminType($termin);

        try {
            $lhpp = $this->resolveLhppByOrderAndTermin($nomorOrder, $terminType);

            abort_if(! $lhpp, Response::HTTP_NOT_FOUND, 'Data BAST tidak ditemukan.');

            $lhpp->loadMissing([
                'images',
                'parentLhppBast.images',
                'hpp.order',
                'hpp.outlineAgreement.unitWork.department',
                'hpp.creator',
                'order.latestHpp.order',
                'order.latestHpp.outlineAgreement.unitWork.department',
                'order.latestHpp.creator',
            ]);

            $bastPdf = Pdf::loadView('pkm.lhpp.pdf', [
                'lhpp' => $lhpp,
                'materialItems' => collect($lhpp->material_items ?? []),
                'serviceItems' => collect($lhpp->service_items ?? []),
            ])->setPaper('a4', 'portrait')->output();

            $attachedHpp = $lhpp->hpp ?: $lhpp->order?->latestHpp;

            if (! $attachedHpp) {
                return response($bastPdf, Response::HTTP_OK, $this->pdfInlineHeaders(
                    sprintf('bast-%s-%s.pdf', $this->terminSlug($terminType), $lhpp->nomor_order)
                ));
            }

            $hppPdf = Pdf::loadView('admin.hpp.hpppdf', [
                'hpp' => $attachedHpp,
            ])->setPaper('a4', 'landscape')->output();

            $mergedPdf = $this->mergePdfOutputs([$bastPdf, $hppPdf]);

            return response($mergedPdf, Response::HTTP_OK, $this->pdfInlineHeaders(
                sprintf('bast-%s-%s.pdf', $this->terminSlug($terminType), $lhpp->nomor_order)
            ));
        } catch (Throwable $exception) {
            Log::error('Failed to generate PKM LHPP PDF.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'nomor_order' => $nomorOrder,
                'termin_type' => $terminType,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat membuat PDF LHPP PKM.');
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

    private function eligibleOrders(?int $exceptOrderId = null): Collection
    {
        $existingOrderIds = LhppBast::query()
            ->where('termin_type', 'termin_1')
            ->when($exceptOrderId !== null, fn ($query) => $query->where('order_id', '!=', $exceptOrderId))
            ->pluck('order_id');

        return Order::query()
            ->with([
                'latestHpp' => fn ($query) => $query->select([
                    'hpps.id',
                    'hpps.order_id',
                    'hpps.total_keseluruhan',
                ]),
                'purchaseOrder:id,order_id,hpp_id,purchase_order_number,target_penyelesaian,progress_pekerjaan',
            ])
            ->whereHas('purchaseOrder', function ($query): void {
                $query
                    ->whereNotNull('purchase_order_number')
                    ->whereRaw("TRIM(purchase_order_number) <> ''")
                    ->where('progress_pekerjaan', 100);
            })
            ->whereHas('latestHpp')
            ->when($existingOrderIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $existingOrderIds))
            ->latest('id')
            ->get([
                'id',
                'nomor_order',
                'notifikasi',
                'nama_pekerjaan',
                'unit_kerja',
                'seksi',
            ]);
    }

    private function resolveEligibleOrder(mixed $nomorOrder, ?int $exceptOrderId = null): ?Order
    {
        return $this->eligibleOrders($exceptOrderId)->firstWhere('nomor_order', trim((string) $nomorOrder));
    }

    /**
     * @return array{0: Order|null, 1: LhppBast|null}
     */
    private function resolveStoreContext(string $nomorOrder, string $terminType): array
    {
        if ($terminType === 'termin_2') {
            $parentLhpp = $this->resolveLhppByOrderAndTermin($nomorOrder, 'termin_1');

            if (! $parentLhpp || ($parentLhpp->termin1_status ?? 'belum') !== 'sudah') {
                return [null, null];
            }

            return [$this->loadOrderWithRelations($parentLhpp->order), $parentLhpp];
        }

        return [$this->resolveEligibleOrder($nomorOrder), null];
    }

    /**
     * @return array{0: Order, 1: LhppBast|null}
     */
    private function resolveUpdateContext(LhppBast $lhpp, string $terminType): array
    {
        if ($terminType === 'termin_2') {
            $parentLhpp = $lhpp->parentLhppBast ?: $this->resolveLhppByOrderAndTermin($lhpp->nomor_order, 'termin_1');

            abort_if(! $parentLhpp, Response::HTTP_NOT_FOUND, 'BAST Termin 1 tidak ditemukan.');

            return [$this->loadOrderWithRelations($parentLhpp->order), $parentLhpp];
        }

        return [$this->loadOrderWithRelations($lhpp->order), null];
    }

    private function loadOrderWithRelations(?Order $order): ?Order
    {
        if (! $order) {
            return null;
        }

        return $order->loadMissing([
            'latestHpp' => fn ($query) => $query->select([
                'hpps.id',
                'hpps.order_id',
                'hpps.total_keseluruhan',
            ]),
            'purchaseOrder:id,order_id,hpp_id,purchase_order_number,target_penyelesaian,progress_pekerjaan',
        ]);
    }

    private function resolveLhppByOrderAndTermin(string $nomorOrder, string $terminType): ?LhppBast
    {
        return LhppBast::query()
            ->with(['order', 'parentLhppBast.images', 'terminTwo', 'images'])
            ->where('nomor_order', $nomorOrder)
            ->where('termin_type', $terminType)
            ->first();
    }

    private function mapOrderOption(Order $order): array
    {
        return [
            'nomor_order' => (string) $order->nomor_order,
            'notifikasi' => (string) ($order->notifikasi ?? ''),
            'deskripsi_pekerjaan' => (string) ($order->nama_pekerjaan ?? ''),
            'unit_kerja_peminta' => (string) ($order->seksi ?: $order->unit_kerja ?: ''),
            'unit_kerja' => (string) ($order->unit_kerja ?? ''),
            'seksi' => (string) ($order->seksi ?? ''),
            'purchase_order_number' => (string) ($order->purchaseOrder?->purchase_order_number ?? ''),
            'nilai_ece' => (float) ($order->latestHpp?->total_keseluruhan ?? 0),
        ];
    }

    /**
     * @param array<string, string> $meta
     */
    private function buildFormView(Request $request, ?LhppBast $lhpp, array $meta, string $terminType, ?LhppBast $parentLhpp = null): View
    {
        $lhpp?->loadMissing(['images', 'parentLhppBast.images']);
        $parentLhpp?->loadMissing('images');

        $sourceLhpp = $lhpp ?? $parentLhpp;
        $currentOrder = $sourceLhpp?->order ? $this->loadOrderWithRelations($sourceLhpp->order) : null;
        $orders = $currentOrder ? collect([$currentOrder]) : $this->eligibleOrders();

        $orderOptions = $orders
            ->filter()
            ->unique('id')
            ->map(fn (Order $order): array => $this->mapOrderOption($order))
            ->values();

        $selectedOrder = trim((string) old(
            'nomor_order',
            $currentOrder?->nomor_order
                ?? ($request->string('order')->toString() !== '' ? $request->string('order')->toString() : '')
        ));

        if ($selectedOrder === '' || ! $orderOptions->firstWhere('nomor_order', $selectedOrder)) {
            $selectedOrder = (string) ($orderOptions->first()['nomor_order'] ?? '');
        }

        $contractCatalog = $this->resolveContractCatalog();

        $materialRows = collect(old('material_rows', $lhpp?->material_items ?? $parentLhpp?->material_items ?? [
            ['jenis_item' => '', 'kategori_item' => '', 'name' => '', 'volume' => '', 'unit' => '', 'unit_price' => '', 'amount' => '0', 'amount_display' => '0'],
        ]))
            ->map(fn (array $row): array => $this->enrichLhppItemRow($row, $contractCatalog))
            ->values();
        $serviceRows = collect(old('service_rows', $lhpp?->service_items ?? $parentLhpp?->service_items ?? [
            ['jenis_item' => '', 'kategori_item' => '', 'name' => '', 'volume' => '', 'unit' => '', 'unit_price' => '', 'amount' => '0', 'amount_display' => '0'],
        ]))
            ->map(fn (array $row): array => $this->enrichLhppItemRow($row, $contractCatalog))
            ->values();

        $calculation = $this->calculateRows(
            $materialRows->all(),
            $serviceRows->all(),
        );

        return view('dashboards.pkm', [
            'pageTitle' => $meta['pageTitle'],
            'pageDescription' => $meta['pageDescription'],
            'bastOrderOptions' => $orderOptions,
            'selectedBastOrder' => $selectedOrder,
            'selectedThreshold' => old('approval_threshold', $lhpp?->approval_threshold ?? $this->resolveThresholdFromTotals($terminType, $calculation['totals'])),
            'bastDate' => old('tanggal_bast', optional($lhpp?->tanggal_bast)->format('Y-m-d') ?? now()->format('Y-m-d')),
            'tanggalMulaiPekerjaan' => old('tanggal_mulai_pekerjaan', optional($lhpp?->tanggal_mulai_pekerjaan)->format('Y-m-d') ?? optional($parentLhpp?->tanggal_mulai_pekerjaan)->format('Y-m-d')),
            'tanggalSelesaiPekerjaan' => old('tanggal_selesai_pekerjaan', optional($lhpp?->tanggal_selesai_pekerjaan)->format('Y-m-d') ?? optional($parentLhpp?->tanggal_selesai_pekerjaan)->format('Y-m-d')),
            'existingImages' => $this->buildExistingImageList($lhpp, $parentLhpp)->all(),
            'initialMaterialRows' => $calculation['material_rows'],
            'initialServiceRows' => $calculation['service_rows'],
            'initialCalculation' => $calculation['totals'],
            'contractCatalog' => $contractCatalog,
            'formTitle' => $meta['formTitle'],
            'formAction' => $meta['formAction'],
            'formMethod' => $meta['formMethod'],
            'submitLabel' => $meta['submitLabel'],
            'terminType' => $terminType,
            'terminLabel' => $this->terminLabel($terminType),
        ]);
    }

    private function buildExistingImageList(?LhppBast $lhpp, ?LhppBast $parentLhpp = null): Collection
    {
        $parentImages = collect($parentLhpp?->images ?? [])
            ->map(fn (LhppBastImage $image): array => [
                'name' => $image->file_name ?: basename((string) $image->file_path),
                'url' => $image->file_path ? Storage::disk('public')->url($image->file_path) : null,
                'source' => 'Termin 1',
            ]);

        $ownImages = collect($lhpp?->images ?? [])
            ->map(fn (LhppBastImage $image): array => [
                'name' => $image->file_name ?: basename((string) $image->file_path),
                'url' => $image->file_path ? Storage::disk('public')->url($image->file_path) : null,
                'source' => ($lhpp?->termin_type === 'termin_2') ? 'Tambahan Termin 2' : 'Termin 1',
            ]);

        return $parentImages
            ->concat($ownImages)
            ->unique(fn (array $image): string => (string) ($image['url'] ?? $image['name']))
            ->values();
    }

    /**
     * @param array<string, string> $totals
     */
    private function resolveThresholdFromTotals(string $terminType, array $totals): string
    {
        $amount = $terminType === 'termin_2'
            ? (float) ($totals['termin_2_nilai'] ?? 0)
            : (float) ($totals['termin_1_nilai'] ?? 0);

        return $amount > 250000000 ? 'over_250' : 'under_250';
    }

    private function normalizeTerminType(string $termin): string
    {
        return match ($termin) {
            'termin_2', 'termin-2', '2' => 'termin_2',
            default => 'termin_1',
        };
    }

    private function terminSlug(string $terminType): string
    {
        return $terminType === 'termin_2' ? 'termin-2' : 'termin-1';
    }

    private function terminLabel(string $terminType): string
    {
        return $terminType === 'termin_2' ? 'Termin 2' : 'Termin 1';
    }

    private function storeUploadedImages(StoreLhppBastRequest $request, LhppBast $lhpp): void
    {
        $files = $request->file('gambar', []);

        if (! is_array($files) || $files === []) {
            return;
        }

        foreach ($files as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $path = $file->store(
                sprintf('lhpp-basts/%s/%s', $lhpp->nomor_order, $lhpp->termin_type),
                'public'
            );

            $lhpp->images()->create([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'created_by' => $request->user()?->id,
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $materialRows
     * @param array<int, array<string, mixed>> $serviceRows
     * @return array<string, mixed>
     */
    private function calculateRows(array $materialRows, array $serviceRows, bool $preserveEmptyRows = false): array
    {
        $normalizedMaterialRows = $this->normalizeItemRows($materialRows, $preserveEmptyRows);
        $normalizedServiceRows = $this->normalizeItemRows($serviceRows, $preserveEmptyRows);

        $subtotalMaterial = $this->sumAmounts($normalizedMaterialRows);
        $subtotalJasa = $this->sumAmounts($normalizedServiceRows);
        $totalAktualBiaya = $this->addCurrencyDecimals($subtotalMaterial, $subtotalJasa);
        $termin1Nilai = $this->multiplyCurrencyDecimal($totalAktualBiaya, '0.95');
        $termin2Nilai = $this->multiplyCurrencyDecimal($totalAktualBiaya, '0.05');

        return [
            'material_rows' => $normalizedMaterialRows !== [] ? $normalizedMaterialRows : [[
                'jenis_item' => '',
                'kategori_item' => '',
                'name' => '',
                'volume' => '',
                'unit' => '',
                'unit_price' => '',
                'amount' => '0.00',
                'amount_display' => '0',
            ]],
            'service_rows' => $normalizedServiceRows !== [] ? $normalizedServiceRows : [[
                'jenis_item' => '',
                'kategori_item' => '',
                'name' => '',
                'volume' => '',
                'unit' => '',
                'unit_price' => '',
                'amount' => '0.00',
                'amount_display' => '0',
            ]],
            'totals' => [
                'subtotal_material' => $subtotalMaterial,
                'subtotal_jasa' => $subtotalJasa,
                'total_aktual_biaya' => $totalAktualBiaya,
                'termin_1_nilai' => $termin1Nilai,
                'termin_2_nilai' => $termin2Nilai,
                'subtotal_material_display' => $this->displayCurrency($subtotalMaterial),
                'subtotal_jasa_display' => $this->displayCurrency($subtotalJasa),
                'total_aktual_biaya_display' => $this->displayCurrency($totalAktualBiaya),
                'termin_1_nilai_display' => $this->displayCurrency($termin1Nilai),
                'termin_2_nilai_display' => $this->displayCurrency($termin2Nilai),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, string>>
     */
    private function normalizeItemRows(array $rows, bool $preserveEmptyRows = false): array
    {
        $normalizedRows = [];

        foreach ($rows as $row) {
            $jenisItem = trim((string) ($row['jenis_item'] ?? ''));
            $kategoriItem = trim((string) ($row['kategori_item'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $volume = $this->normalizeNumericString($row['volume'] ?? '');
            $unit = trim((string) ($row['unit'] ?? 'Jam'));
            $unitPrice = $this->normalizeCurrencyDecimal($row['unit_price'] ?? '');
            $amount = $this->multiplyCurrencyDecimal($volume, $unitPrice);

            if ($name === '' && $this->isZeroNumericString($volume) && $this->isZeroCurrency($unitPrice)) {
                if (! $preserveEmptyRows) {
                    continue;
                }

                $normalizedRows[] = [
                    'jenis_item' => $jenisItem,
                    'kategori_item' => $kategoriItem,
                    'name' => '',
                    'volume' => '',
                    'unit' => $unit !== 'Jam' ? $unit : '',
                    'unit_price' => '',
                    'amount' => '0.00',
                    'amount_display' => '0',
                ];

                continue;
            }

            $normalizedRows[] = [
                'jenis_item' => $jenisItem,
                'kategori_item' => $kategoriItem,
                'name' => $name,
                'volume' => $volume === '0' ? '' : $volume,
                'unit' => $unit !== '' ? $unit : 'Jam',
                'unit_price' => $unitPrice === '0.00' ? '' : $this->displayEditableCurrency($unitPrice),
                'amount' => $amount,
                'amount_display' => $this->displayCurrency($amount),
            ];
        }

        return $normalizedRows;
    }

    /**
     * @return list<array<string, string>>
     */
    private function resolveContractCatalog(): array
    {
        return FabricationConstructionContract::query()
            ->orderBy('jenis_item')
            ->orderBy('kategori_item')
            ->orderBy('nama_item')
            ->get()
            ->map(fn (FabricationConstructionContract $item): array => [
                'jenis_item' => trim((string) $item->jenis_item),
                'kategori_item' => trim((string) ($item->kategori_item ?? '')),
                'nama_item' => trim((string) $item->nama_item),
                'satuan' => trim((string) $item->satuan),
                'harga_satuan' => $this->displayEditableCurrency((string) $item->harga_satuan),
            ])
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $row
     * @param list<array<string, string>> $contractCatalog
     * @return array<string, mixed>
     */
    private function enrichLhppItemRow(array $row, array $contractCatalog): array
    {
        $enriched = [
            'jenis_item' => trim((string) ($row['jenis_item'] ?? '')),
            'kategori_item' => trim((string) ($row['kategori_item'] ?? '')),
            'name' => trim((string) ($row['name'] ?? '')),
            'volume' => $row['volume'] ?? '',
            'unit' => trim((string) ($row['unit'] ?? '')),
            'unit_price' => $row['unit_price'] ?? '',
            'amount' => $row['amount'] ?? '0.00',
            'amount_display' => $row['amount_display'] ?? '0',
        ];

        if ($enriched['name'] === '') {
            return $enriched;
        }

        $matchedItem = collect($contractCatalog)
            ->first(function (array $item) use ($enriched): bool {
                if (($item['nama_item'] ?? '') !== $enriched['name']) {
                    return false;
                }

                if ($enriched['jenis_item'] !== '' && ($item['jenis_item'] ?? '') !== $enriched['jenis_item']) {
                    return false;
                }

                if ($enriched['kategori_item'] !== '' && ($item['kategori_item'] ?? '') !== $enriched['kategori_item']) {
                    return false;
                }

                return true;
            });

        if (! $matchedItem) {
            $matchedItem = collect($contractCatalog)
                ->first(fn (array $item): bool => ($item['nama_item'] ?? '') === $enriched['name']);
        }

        if (! $matchedItem) {
            return $enriched;
        }

        $enriched['jenis_item'] = $enriched['jenis_item'] !== '' ? $enriched['jenis_item'] : (string) ($matchedItem['jenis_item'] ?? '');
        $enriched['kategori_item'] = $enriched['kategori_item'] !== '' ? $enriched['kategori_item'] : (string) ($matchedItem['kategori_item'] ?? '');
        $enriched['unit'] = $enriched['unit'] !== '' ? $enriched['unit'] : (string) ($matchedItem['satuan'] ?? '');
        $enriched['unit_price'] = $enriched['unit_price'] !== '' ? $enriched['unit_price'] : (string) ($matchedItem['harga_satuan'] ?? '');

        return $enriched;
    }

    /**
     * @param array<int, array<string, mixed>> $materialRows
     * @param array<int, array<string, mixed>> $serviceRows
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function resolveActualRowsPayload(string $terminType, ?LhppBast $parentLhpp, array $materialRows, array $serviceRows): array
    {
        if ($terminType !== 'termin_2' || ! $parentLhpp) {
            return [$materialRows, $serviceRows];
        }

        return [
            is_array($parentLhpp->material_items) ? $parentLhpp->material_items : [],
            is_array($parentLhpp->service_items) ? $parentLhpp->service_items : [],
        ];
    }

    /**
     * @param array<int, array<string, string>> $rows
     */
    private function sumAmounts(array $rows): string
    {
        $total = '0.00';

        foreach ($rows as $row) {
            $total = $this->addCurrencyDecimals($total, $row['amount'] ?? '0.00');
        }

        return $total;
    }

    private function normalizeNumericString(mixed $value): string
    {
        $normalized = preg_replace('/[^0-9,.\-]/', '', trim((string) $value)) ?? '';

        if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === ',') {
            return '0';
        }

        $isNegative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '-');

        if (str_contains($unsigned, ',') && str_contains($unsigned, '.')) {
            $lastComma = strrpos($unsigned, ',');
            $lastDot = strrpos($unsigned, '.');

            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $unsigned = str_replace('.', '', $unsigned);
                $unsigned = str_replace(',', '.', $unsigned);
            } else {
                $unsigned = str_replace(',', '', $unsigned);
            }
        } elseif (str_contains($unsigned, ',')) {
            $unsigned = str_replace(',', '.', $unsigned);
        } elseif (substr_count($unsigned, '.') > 1) {
            $unsigned = str_replace('.', '', $unsigned);
        } elseif (str_contains($unsigned, '.')) {
            [, $decimal] = array_pad(explode('.', $unsigned, 2), 2, '');

            if (strlen($decimal) === 3) {
                $unsigned = str_replace('.', '', $unsigned);
            }
        }

        if (str_contains($unsigned, '.')) {
            [$integer, $decimal] = array_pad(explode('.', $unsigned, 2), 2, '');
            $integer = ltrim($integer, '0');
            $decimal = rtrim($decimal, '0');

            $normalizedNumber = ($integer === '' ? '0' : $integer).($decimal !== '' ? ".{$decimal}" : '');

            return $isNegative ? "-{$normalizedNumber}" : $normalizedNumber;
        }

        $integer = ltrim($unsigned, '0');
        $normalizedNumber = $integer === '' ? '0' : $integer;

        return $isNegative ? "-{$normalizedNumber}" : $normalizedNumber;
    }

    private function normalizeCurrencyDecimal(mixed $value): string
    {
        $normalized = $this->normalizeNumericString($value);

        if (! str_contains($normalized, '.')) {
            return $normalized.'.00';
        }

        [$integer, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');
        $decimal = substr(str_pad($decimal, 2, '0'), 0, 2);

        return "{$integer}.{$decimal}";
    }

    private function multiplyCurrencyDecimal(string $left, string $right): string
    {
        return number_format(
            (float) $this->normalizeNumericString($left) * (float) $this->normalizeCurrencyDecimal($right),
            2,
            '.',
            ''
        );
    }

    private function addCurrencyDecimals(string $left, string $right): string
    {
        return number_format(
            (float) $this->normalizeCurrencyDecimal($left) + (float) $this->normalizeCurrencyDecimal($right),
            2,
            '.',
            ''
        );
    }

    private function isZeroNumericString(string $value): bool
    {
        return (float) $this->normalizeNumericString($value) === 0.0;
    }

    private function isZeroCurrency(string $value): bool
    {
        return (float) $this->normalizeCurrencyDecimal($value) === 0.0;
    }

    private function displayCurrency(string $value): string
    {
        return number_format((float) $this->normalizeCurrencyDecimal($value), 0, ',', '.');
    }

    private function displayEditableCurrency(string $value): string
    {
        return number_format((float) $this->normalizeCurrencyDecimal($value), 0, ',', '.');
    }
}
