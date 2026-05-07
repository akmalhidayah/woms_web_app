<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Http\Controllers\Controller;
use App\Models\BengkelTask;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\QualityControlReport;
use App\Models\QualityControlReportFile;
use App\Services\QualityControl\QualityControlSignatureService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderWorkshopQualityControlController extends Controller
{
    public function __construct(
        private readonly QualityControlSignatureService $signatureService,
    ) {
    }

    public function create(Order $order): View|RedirectResponse
    {
        $guard = $this->guardCanManageQualityControl($order);

        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        $type = $guard;
        $payload = $this->defaultPayload($order, $type);
        $report = new QualityControlReport([
            'type' => $type,
            'report_no' => $this->suggestReportNumber(),
            'report_date' => now(),
            'status' => QualityControlReport::STATUS_DRAFT,
            'payload' => $payload,
        ]);

        return view("admin.orders.workshop.quality-control.create-{$type}", [
            'order' => $order,
            'report' => $report,
            'payload' => $payload,
        ]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        $guard = $this->guardCanManageQualityControl($order);

        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        $type = $guard;
        $validated = $this->validateReport($request, $type);

        $report = QualityControlReport::create([
            'order_id' => $order->id,
            'bengkel_task_id' => BengkelTask::query()->where('order_id', $order->id)->latest('id')->value('id'),
            'type' => $type,
            'report_no' => $this->suggestReportNumber(),
            'report_date' => $validated['report_date'] ?? null,
            'status' => $validated['status'] ?? QualityControlReport::STATUS_DRAFT,
            'payload' => $this->payloadFromRequest($request, $type),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $this->storeUploadedFiles($request, $report, $type);
        $signatureResult = $this->signatureService->createSignatureChain($report->fresh('order'));

        $redirect = redirect()
            ->route('admin.orders.workshop.quality-control.edit', [$order, $report])
            ->with('status', $signatureResult['workshop_url']
                ? 'Form Quality Control berhasil disimpan. Token TTD Manager Bengkel sudah dibuat.'
                : 'Form Quality Control berhasil disimpan, tetapi penanda tangan Manager Bengkel belum ditemukan di struktur organisasi.');

        if ($signatureResult['workshop_url']) {
            $redirect
                ->with('quality_control_approval_url', $signatureResult['workshop_url'])
                ->with('quality_control_approval_name', $signatureResult['workshop_signature']?->signer_name)
                ->with('quality_control_approval_role', $signatureResult['workshop_signature']?->role_label);
        }

        return $redirect;
    }

    public function edit(Order $order, QualityControlReport $qualityControlReport): View|RedirectResponse
    {
        $redirect = $this->guardReportBelongsToOrder($order, $qualityControlReport);

        if ($redirect instanceof RedirectResponse) {
            return $redirect;
        }

        $type = $qualityControlReport->type;
        $qualityControlReport->load('files');

        return view("admin.orders.workshop.quality-control.edit-{$type}", [
            'order' => $order,
            'report' => $qualityControlReport,
            'payload' => $qualityControlReport->payload ?: $this->defaultPayload($order, $type),
        ]);
    }

    public function update(Request $request, Order $order, QualityControlReport $qualityControlReport): RedirectResponse
    {
        $redirect = $this->guardReportBelongsToOrder($order, $qualityControlReport);

        if ($redirect instanceof RedirectResponse) {
            return $redirect;
        }

        $type = $qualityControlReport->type;
        $validated = $this->validateReport($request, $type);

        $qualityControlReport->update([
            'report_no' => $this->reportNumberForExistingReport($qualityControlReport),
            'report_date' => $validated['report_date'] ?? null,
            'status' => $validated['status'] ?? QualityControlReport::STATUS_DRAFT,
            'payload' => $this->payloadFromRequest($request, $type),
            'updated_by' => $request->user()?->id,
        ]);

        $this->storeUploadedFiles($request, $qualityControlReport, $type);
        $signatureResult = $this->signatureService->rebuildIfUnsigned($qualityControlReport->refresh()->load('order'));

        $redirect = redirect()
            ->route('admin.orders.workshop.quality-control.edit', [$order, $qualityControlReport])
            ->with('status', $signatureResult['workshop_url']
                ? 'Form Quality Control berhasil diperbarui. Token TTD Manager Bengkel baru sudah dibuat.'
                : 'Form Quality Control berhasil diperbarui.');

        if ($signatureResult['workshop_url']) {
            $redirect
                ->with('quality_control_approval_url', $signatureResult['workshop_url'])
                ->with('quality_control_approval_name', $signatureResult['workshop_signature']?->signer_name)
                ->with('quality_control_approval_role', $signatureResult['workshop_signature']?->role_label);
        }

        return $redirect;
    }

    public function pdf(Order $order, QualityControlReport $qualityControlReport)
    {
        $redirect = $this->guardReportBelongsToOrder($order, $qualityControlReport);

        if ($redirect instanceof RedirectResponse) {
            return $redirect;
        }

        $qualityControlReport->load(['files', 'signatures']);
        $type = $qualityControlReport->type;
        $paper = $type === QualityControlReport::TYPE_REFURBISH ? 'landscape' : 'portrait';
        $filename = 'qc-'.$type.'-'.$order->nomor_order.'.pdf';

        return Pdf::loadView("admin.orders.workshop.quality-control.pdf.{$type}", [
            'order' => $order,
            'report' => $qualityControlReport,
            'payload' => $qualityControlReport->payload ?: $this->defaultPayload($order, $type),
            'filesByCategory' => $qualityControlReport->files->groupBy('category'),
        ])->setPaper('a4', $paper)->stream($filename);
    }

    public function destroyFile(QualityControlReport $qualityControlReport, QualityControlReportFile $file): RedirectResponse
    {
        if ((int) $file->quality_control_report_id !== (int) $qualityControlReport->id) {
            abort(404);
        }

        Storage::disk('public')->delete($file->file_path);
        $file->delete();

        return back()->with('status', 'Foto Quality Control berhasil dihapus.');
    }

    public function showFile(QualityControlReport $qualityControlReport, QualityControlReportFile $file)
    {
        if ((int) $file->quality_control_report_id !== (int) $qualityControlReport->id) {
            abort(404);
        }

        abort_unless(Storage::disk('public')->exists($file->file_path), 404);

        return response()->file(Storage::disk('public')->path($file->file_path));
    }

    private function guardCanManageQualityControl(Order $order): string|RedirectResponse
    {
        $order->loadMissing('orderWorkshop');

        if (! in_array($order->catatan_status?->value, [
            OrderUserNoteStatus::ApprovedWorkshop->value,
            OrderUserNoteStatus::ApprovedWorkshopJasa->value,
        ], true)) {
            return back()->withErrors(['quality_control' => 'Order ini tidak termasuk order pekerjaan bengkel.']);
        }

        if ($order->orderWorkshop?->progress_status !== OrderWorkshop::PROGRESS_QUALITY_CONTROL) {
            return back()->withErrors(['quality_control' => 'Quality Control hanya bisa dibuat saat progress Proses Quality Control.']);
        }

        $type = $this->typeForOrder($order);

        if (! $type) {
            return back()->withErrors(['quality_control' => 'Regu belum sesuai untuk membuat form QC.']);
        }

        return $type;
    }

    private function guardReportBelongsToOrder(Order $order, QualityControlReport $report): ?RedirectResponse
    {
        if ((int) $report->order_id !== (int) $order->id) {
            abort(404);
        }

        $expectedType = $this->typeForOrder($order);

        if ($expectedType && $report->type !== $expectedType) {
            return redirect()
                ->route('admin.orders.workshop.index')
                ->withErrors(['quality_control' => 'Jenis form QC tidak sesuai dengan regu order.']);
        }

        return null;
    }

    private function typeForOrder(Order $order): ?string
    {
        return match (trim((string) $order->catatan)) {
            'Regu Fabrikasi' => QualityControlReport::TYPE_FABRICATION,
            'Regu Bengkel (Refurbish)' => QualityControlReport::TYPE_REFURBISH,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function validateReport(Request $request, string $type): array
    {
        $rules = [
            'report_no' => ['nullable', 'string', 'max:191'],
            'report_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in([QualityControlReport::STATUS_DRAFT, QualityControlReport::STATUS_SUBMITTED])],
            'signature' => ['nullable', 'array'],
            'signature.signature_data' => ['nullable', 'string', 'max:500000'],
            'signature.signer_name' => ['nullable', 'string', 'max:191'],
            'signature.signed_at' => ['nullable', 'date'],
        ];

        foreach ($this->fileCategories($type) as $category) {
            $rules[$category] = ['nullable', 'array'];
            $rules[$category.'.*'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
        }

        return $request->validate($rules);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFromRequest(Request $request, string $type): array
    {
        return $type === QualityControlReport::TYPE_FABRICATION
            ? $this->fabricationPayloadFromRequest($request)
            : $this->refurbishPayloadFromRequest($request);
    }

    /**
     * @return array<string, mixed>
     */
    private function fabricationPayloadFromRequest(Request $request): array
    {
        return [
            'dimension_checks' => $this->rows($request->input('dimension_checks', []), [
                'item' => '',
                'status' => 'sesuai',
                'notes' => '',
            ], ['status' => ['sesuai', 'tidak_sesuai']]),
            'materials' => $this->rows($request->input('materials', []), [
                'material_work' => '',
                'material_type' => '',
                'notes' => '',
            ]),
            'welding' => $this->rows($request->input('welding', []), [
                'item' => '',
                'electrode' => '',
                'condition' => 'baik',
                'notes' => '',
            ], ['condition' => ['baik', 'perlu_perbaikan']]),
            'notes' => trim((string) $request->input('notes', '')),
            'signature' => $this->signaturePayloadFromRequest($request),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function refurbishPayloadFromRequest(Request $request): array
    {
        $notesBeforeRows = $this->rows($request->input('notes_before_rows', []), [
            'note' => '',
        ]);
        $notesAfterRows = $this->rows($request->input('notes_after_rows', []), [
            'note' => '',
        ]);

        return [
            'received_date' => $request->input('received_date'),
            'finished_date' => $request->input('finished_date'),
            'working_days' => trim((string) $request->input('working_days', '')),
            'notification_number' => trim((string) $request->input('notification_number', '')),
            'unit_work' => trim((string) $request->input('unit_work', '')),
            'section_number' => trim((string) $request->input('section_number', '')),
            'equipment_type' => trim((string) $request->input('equipment_type', '')),
            'plant' => trim((string) $request->input('plant', '')),
            'repair_descriptions' => $this->rows($request->input('repair_descriptions', []), [
                'description' => '',
            ]),
            'spare_parts' => $this->rows($request->input('spare_parts', []), [
                'name' => '',
                'received_date' => '',
                'install' => '',
            ]),
            'commissioning_tests' => $this->rows($request->input('commissioning_tests', []), [
                'item' => '',
                'date' => '',
                'condition' => '',
            ]),
            'notes_before_rows' => $notesBeforeRows,
            'notes_after_rows' => $notesAfterRows,
            'notes_before' => collect($notesBeforeRows)->pluck('note')->implode("\n"),
            'notes_after' => collect($notesAfterRows)->pluck('note')->implode("\n"),
            'user_notes' => trim((string) $request->input('user_notes', '')),
            'signature' => $this->signaturePayloadFromRequest($request),
        ];
    }

    /**
     * @param  array<int, mixed>  $rows
     * @param  array<string, string>  $defaults
     * @param  array<string, list<string>>  $allowedValues
     * @return list<array<string, string>>
     */
    private function rows(array $rows, array $defaults, array $allowedValues = []): array
    {
        return collect($rows)
            ->filter(fn ($row): bool => is_array($row))
            ->map(function (array $row) use ($defaults, $allowedValues): array {
                $normalized = [];

                foreach ($defaults as $key => $default) {
                    $value = trim((string) ($row[$key] ?? $default));

                    if (isset($allowedValues[$key]) && ! in_array($value, $allowedValues[$key], true)) {
                        $value = $default;
                    }

                    $normalized[$key] = mb_substr($value, 0, 1000);
                }

                return $normalized;
            })
            ->filter(fn (array $row): bool => collect($row)->contains(fn (string $value): bool => $value !== ''))
            ->values()
            ->all();
    }

    /**
     * @return array{signature_data: string, signer_name: string, signed_at: string}
     */
    private function signaturePayloadFromRequest(Request $request): array
    {
        $signatureData = trim((string) $request->input('signature.signature_data', ''));

        if ($signatureData !== '' && ! str_starts_with($signatureData, 'data:image/png;base64,')) {
            $signatureData = '';
        }

        $signerName = trim((string) $request->input('signature.signer_name', ''));

        if ($signerName === '') {
            $signerName = $request->user()?->name ?? '';
        }

        return [
            'signature_data' => $signatureData,
            'signer_name' => mb_substr($signerName, 0, 191),
            'signed_at' => (string) ($request->input('signature.signed_at') ?: now()->format('Y-m-d')),
        ];
    }

    private function storeUploadedFiles(Request $request, QualityControlReport $report, string $type): void
    {
        foreach ($this->fileCategories($type) as $category) {
            $files = $request->file($category, []);

            if (! is_array($files)) {
                continue;
            }

            $nextSortOrder = (int) $report->files()->where('category', $category)->max('sort_order') + 1;

            foreach ($files as $file) {
                $path = $file->store('quality-control/'.$report->id.'/'.$category, 'public');

                $report->files()->create([
                    'category' => $category,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'sort_order' => $nextSortOrder++,
                ]);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function fileCategories(string $type): array
    {
        return $type === QualityControlReport::TYPE_FABRICATION
            ? ['fabrication_before', 'fabrication_after']
            : ['refurbish_repair', 'refurbish_sparepart', 'refurbish_commissioning'];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultPayload(Order $order, string $type): array
    {
        if ($type === QualityControlReport::TYPE_FABRICATION) {
            return [
                'dimension_checks' => [
                    ['item' => 'Lebar Damper', 'status' => 'sesuai', 'notes' => ''],
                    ['item' => 'Tinggi Damper', 'status' => 'sesuai', 'notes' => ''],
                    ['item' => 'Tebal Damper', 'status' => 'sesuai', 'notes' => ''],
                    ['item' => 'Tinggi Anchor', 'status' => 'sesuai', 'notes' => ''],
                ],
                'materials' => [
                    ['material_work' => 'Plate Damper', 'material_type' => 'ASTM 310', 'notes' => 'Disiapkan dari bengkel Mesin'],
                    ['material_work' => 'Anchor', 'material_type' => 'ASTM 310', 'notes' => 'Disiapkan dari User'],
                ],
                'welding' => [
                    ['item' => 'Pengelasan plate damper', 'electrode' => 'AWS 310', 'condition' => 'baik', 'notes' => ''],
                    ['item' => 'Pengelasan Anchor', 'electrode' => 'AWS 310', 'condition' => 'baik', 'notes' => ''],
                ],
                'notes' => '',
                'signature' => [
                    'signature_data' => '',
                    'signer_name' => '',
                    'signed_at' => '',
                ],
            ];
        }

        return [
            'received_date' => optional($order->tanggal_order)->format('Y-m-d'),
            'finished_date' => optional($order->target_selesai)->format('Y-m-d'),
            'working_days' => '',
            'notification_number' => $order->notifikasi ?: $order->nomor_order,
            'unit_work' => $order->seksi,
            'section_number' => '',
            'equipment_type' => $order->nama_pekerjaan,
            'plant' => '',
            'repair_descriptions' => collect(range(1, 10))->map(fn (): array => ['description' => ''])->all(),
            'spare_parts' => [
                ['name' => '', 'received_date' => '', 'install' => ''],
            ],
            'commissioning_tests' => [
                ['item' => 'Vibrasi', 'date' => '', 'condition' => ''],
                ['item' => 'Suara', 'date' => '', 'condition' => ''],
                ['item' => 'Fungsi', 'date' => '', 'condition' => ''],
                ['item' => 'Temperatur', 'date' => '', 'condition' => ''],
            ],
            'notes_before_rows' => [
                ['note' => ''],
            ],
            'notes_after_rows' => [
                ['note' => ''],
            ],
            'notes_before' => '',
            'notes_after' => '',
            'user_notes' => '',
            'signature' => [
                'signature_data' => '',
                'signer_name' => '',
                'signed_at' => '',
            ],
        ];
    }

    private function suggestReportNumber(): string
    {
        $now = now();
        $sequence = QualityControlReport::query()
            ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->count() + 1;

        return $this->formatReportNumber($sequence, $now->format('m-Y'));
    }

    private function reportNumberForExistingReport(QualityControlReport $report): string
    {
        if ($this->isGeneratedReportNumber($report->report_no)) {
            return (string) $report->report_no;
        }

        $createdAt = $report->created_at ?: now();
        $sequence = QualityControlReport::query()
            ->whereBetween('created_at', [$createdAt->copy()->startOfMonth(), $createdAt->copy()->endOfMonth()])
            ->where(function ($query) use ($report, $createdAt): void {
                $query
                    ->where('created_at', '<', $createdAt)
                    ->orWhere(function ($sameTimeQuery) use ($report, $createdAt): void {
                        $sameTimeQuery
                            ->where('created_at', $createdAt)
                            ->where('id', '<=', $report->id);
                    });
            })
            ->count();

        return $this->formatReportNumber(max($sequence, 1), $createdAt->format('m-Y'));
    }

    private function isGeneratedReportNumber(?string $reportNo): bool
    {
        return (bool) preg_match('/^\d{3}\/QC\/25\.10\/\d{2}-\d{4}$/', (string) $reportNo);
    }

    private function formatReportNumber(int $sequence, string $period): string
    {
        return str_pad((string) $sequence, 3, '0', STR_PAD_LEFT).'/QC/25.10/'.$period;
    }
}
