<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Http\Controllers\Controller;
use App\Models\BengkelTask;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\QualityControlReport;
use App\Models\QualityControlReportFile;
use App\Models\QualityControlSignature;
use App\Services\Approvals\ApprovalNotificationService;
use App\Services\BengkelTasks\WorkshopWorkPackagePresenter;
use App\Services\BengkelTasks\WorkshopWorkPackageService;
use App\Services\QualityControl\QualityControlSignatureService;
use App\Support\SignatureImageStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class OrderWorkshopQualityControlController extends Controller
{
    public function __construct(
        private readonly QualityControlSignatureService $signatureService,
        private readonly ApprovalNotificationService $approvalNotificationService,
        private readonly WorkshopWorkPackageService $workPackageService,
        private readonly WorkshopWorkPackagePresenter $workPackagePresenter,
    ) {}

    public function create(Order $order): View|RedirectResponse
    {
        $guard = $this->guardCanManageQualityControl($order);

        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        $type = $guard;
        $order->loadMissing('workPackages.assignments');
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
            'workPackages' => $order->workPackages,
        ]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        $guard = $this->guardCanManageQualityControl($order);

        if ($guard instanceof RedirectResponse) {
            return $guard;
        }

        $type = $guard;
        $intent = $this->resolveIntent($request);
        $isSubmit = $intent === 'submit';
        $validated = $this->validateReport($request, $type);

        if ($order->qualityControlReports()->exists()) {
            return back()->withErrors([
                'quality_control' => 'Order ini sudah mempunyai laporan Quality Control aktif.',
            ])->withInput();
        }

        $payload = [];
        $storedFilePaths = [];

        try {
            $payload = $this->payloadFromRequest($request, $type, $isSubmit);

            if ($isSubmit) {
                $this->assertSubmissionReady($order, $type, $payload);
            }

            $result = DB::transaction(function () use ($order, $type, $validated, $request, $payload, $isSubmit, &$storedFilePaths): array {
                $lockedOrder = Order::query()->findOrFail($order->id);
                $workshop = $lockedOrder->orderWorkshop()->lockForUpdate()->first();
                abort_unless($workshop !== null, Response::HTTP_NOT_FOUND);
                $lockedOrder->load(['orderWorkshop', 'workPackages.assignments', 'qualityControlReports']);

                if ($lockedOrder->qualityControlReports()->exists()) {
                    throw ValidationException::withMessages([
                        'quality_control' => 'Order ini sudah mempunyai laporan Quality Control aktif.',
                    ]);
                }

                if ($isSubmit) {
                    $this->workPackageService->assertParentMayAdvance($lockedOrder);
                }

                if ($isSubmit) {
                    $payload['work_packages_snapshot'] = $this->workPackagePresenter->snapshotForOrder($lockedOrder);
                }

                $report = QualityControlReport::create([
                    'order_id' => $lockedOrder->id,
                    'bengkel_task_id' => BengkelTask::query()->where('order_id', $lockedOrder->id)->latest('id')->value('id'),
                    'type' => $type,
                    'report_no' => $this->suggestReportNumber(),
                    'report_date' => $validated['report_date'] ?? null,
                    'status' => $isSubmit ? QualityControlReport::STATUS_SUBMITTED : QualityControlReport::STATUS_DRAFT,
                    'payload' => $payload,
                    'created_by' => $request->user()?->id,
                    'updated_by' => $request->user()?->id,
                ]);

                $storedFilePaths = $this->storeUploadedFiles($request, $report, $type);
                $signatureResult = $isSubmit
                    ? $this->signatureService->createSignatureChain($report->fresh('order'))
                    : ['workshop_url' => null, 'workshop_signature' => null, 'user_signature' => null];

                return [$report, $signatureResult];
            });

            [$report, $signatureResult] = $result;
        } catch (\Throwable $exception) {
            $this->cleanupStoredPaths($storedFilePaths);
            $this->cleanupNewMakerSignature($payload, []);

            throw $exception;
        }

        $redirect = redirect()
            ->route('admin.orders.workshop.quality-control.edit', [$order, $report])
            ->with('status', $isSubmit
                ? 'Quality Control berhasil disubmit dan approval Manager Workshop dimulai.'
                : 'Draft Quality Control berhasil disimpan.');

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
        $order->loadMissing('workPackages.assignments');

        return view("admin.orders.workshop.quality-control.edit-{$type}", [
            'order' => $order,
            'report' => $qualityControlReport,
            'payload' => $qualityControlReport->payload ?: $this->defaultPayload($order, $type),
            'workPackages' => $order->workPackages,
        ]);
    }

    public function update(Request $request, Order $order, QualityControlReport $qualityControlReport): RedirectResponse
    {
        $redirect = $this->guardReportBelongsToOrder($order, $qualityControlReport);

        if ($redirect instanceof RedirectResponse) {
            return $redirect;
        }

        if ($qualityControlReport->status === QualityControlReport::STATUS_SUBMITTED || $qualityControlReport->hasApprovalStarted()) {
            Log::warning('Blocked update to signed Quality Control report.', [
                'status_code' => Response::HTTP_FORBIDDEN,
                'user_id' => $request->user()?->id,
                'order_id' => $order->id,
                'quality_control_report_id' => $qualityControlReport->id,
                'report_no' => $qualityControlReport->report_no,
            ]);

            abort(Response::HTTP_FORBIDDEN, 'Quality Control tidak dapat diubah setelah proses tanda tangan dimulai.');
        }

        $type = $qualityControlReport->type;
        $intent = $this->resolveIntent($request);
        $isSubmit = $intent === 'submit';
        $validated = $this->validateReport($request, $type);
        $existingMakerSignature = $qualityControlReport->makerSignature();
        $payload = [];
        $storedFilePaths = [];

        try {
            $payload = $this->payloadFromRequest(
                $request,
                $type,
                $isSubmit,
                $existingMakerSignature,
            );

            if ($isSubmit) {
                $this->assertSubmissionReady($order, $type, $payload);
            }

            $signatureResult = DB::transaction(function () use ($request, $order, $type, $validated, $payload, $isSubmit, $qualityControlReport, &$storedFilePaths): array {
                $order->orderWorkshop()->lockForUpdate()->firstOrFail();
                $order->load(['workPackages.assignments']);
                if ($isSubmit) {
                    $this->workPackageService->assertParentMayAdvance($order);
                    $payload['work_packages_snapshot'] = $this->workPackagePresenter->snapshotForOrder($order);
                }
                $qualityControlReport->update([
                    'report_no' => $this->reportNumberForExistingReport($qualityControlReport),
                    'report_date' => $validated['report_date'] ?? null,
                    'status' => $isSubmit ? QualityControlReport::STATUS_SUBMITTED : QualityControlReport::STATUS_DRAFT,
                    'payload' => $payload,
                    'updated_by' => $request->user()?->id,
                ]);

                $storedFilePaths = $this->storeUploadedFiles($request, $qualityControlReport, $type);

                return $isSubmit
                    ? $this->signatureService->createSignatureChain($qualityControlReport->refresh()->load('order'))
                    : ['workshop_url' => null, 'workshop_signature' => null, 'user_signature' => null];
            });
        } catch (\Throwable $exception) {
            $this->cleanupStoredPaths($storedFilePaths);
            $this->cleanupNewMakerSignature($payload, $existingMakerSignature);

            throw $exception;
        }

        $redirect = redirect()
            ->route('admin.orders.workshop.quality-control.edit', [$order, $qualityControlReport])
            ->with('status', $signatureResult['workshop_url']
                ? 'Form Quality Control berhasil diperbarui.'
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
        $order->loadMissing('workPackages.assignments');
        $type = $qualityControlReport->type;
        $paper = $type === QualityControlReport::TYPE_REFURBISH ? 'landscape' : 'portrait';
        $filename = 'qc-'.$type.'-'.$order->nomor_order.'.pdf';

        return Pdf::loadView("admin.orders.workshop.quality-control.pdf.{$type}", [
            'order' => $order,
            'report' => $qualityControlReport,
            'payload' => $qualityControlReport->payload ?: $this->defaultPayload($order, $type),
            'filesByCategory' => $qualityControlReport->files->groupBy('category'),
            'workPackages' => $qualityControlReport->status === QualityControlReport::STATUS_SUBMITTED
                && ! empty($qualityControlReport->payload['work_packages_snapshot'] ?? null)
                ? $qualityControlReport->payload['work_packages_snapshot']
                : $this->workPackagePresenter->snapshotForOrder($order),
        ])->setPaper('a4', $paper)->stream($filename);
    }

    public function resendApproval(Request $request, Order $order, QualityControlReport $qualityControlReport): RedirectResponse
    {
        abort_unless(
            (int) $qualityControlReport->order_id === (int) $order->id,
            Response::HTTP_NOT_FOUND
        );

        $signature = $qualityControlReport->signatures()
            ->where('status', QualityControlSignature::STATUS_PENDING)
            ->orderBy('step_order')
            ->first();

        abort_unless(
            $signature && ! $signature->tokenExpired() && $signature->approvalUrl(),
            Response::HTTP_CONFLICT,
            'Tidak ada link approval Quality Control aktif yang dapat dikirim ulang.'
        );

        if (! $this->approvalNotificationService->sendQualityControl($signature, true)) {
            abort(Response::HTTP_BAD_GATEWAY, 'Email approval Quality Control gagal dikirim.');
        }

        return back()->with('status', sprintf(
            'Link approval Quality Control berhasil dikirim ulang ke %s.',
            $signature->signer?->email ?: 'email approver',
        ));
    }

    public function regenerateApprovalToken(
        Request $request,
        Order $order,
        QualityControlReport $qualityControlReport
    ): RedirectResponse {
        abort_unless(
            (int) $qualityControlReport->order_id === (int) $order->id,
            Response::HTTP_NOT_FOUND
        );

        $signature = $qualityControlReport->signatures()
            ->where('status', QualityControlSignature::STATUS_PENDING)
            ->orderBy('step_order')
            ->first();

        abort_unless(
            $signature && $signature->tokenExpired(),
            Response::HTTP_CONFLICT,
            'Tidak ada token approval Quality Control kedaluwarsa yang dapat dibuat ulang.'
        );

        $this->signatureService->regenerateExpiredToken($signature);

        return back()->with('status', sprintf(
            'Token approval Quality Control untuk %s berhasil dibuat ulang dan email baru telah dikirim.',
            $signature->role_label ?: $signature->signer_name ?: 'approver',
        ));
    }

    public function destroyFile(Request $request, QualityControlReport $qualityControlReport, QualityControlReportFile $file): RedirectResponse
    {
        if ((int) $file->quality_control_report_id !== (int) $qualityControlReport->id) {
            abort(404);
        }

        if ($qualityControlReport->status === QualityControlReport::STATUS_SUBMITTED || $qualityControlReport->hasApprovalStarted()) {
            Log::warning('Blocked file deletion from signed Quality Control report.', [
                'status_code' => Response::HTTP_FORBIDDEN,
                'user_id' => $request->user()?->id,
                'quality_control_report_id' => $qualityControlReport->id,
                'quality_control_report_file_id' => $file->id,
                'report_no' => $qualityControlReport->report_no,
            ]);

            abort(Response::HTTP_FORBIDDEN, 'Lampiran Quality Control tidak dapat dihapus setelah proses tanda tangan dimulai.');
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

        $this->workPackageService->assertParentMayAdvance($order);

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
            'intent' => ['nullable', Rule::in(['draft', 'submit'])],
            'signature' => ['nullable', 'array'],
            'signature.signature_data' => ['nullable', 'string', 'max:500000'],
            'signature.signature_existing' => ['nullable', 'string', 'max:500000'],
            'signature.signature_file' => ['nullable', 'file', 'mimetypes:image/png,image/jpeg', 'max:2048'],
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
    private function payloadFromRequest(
        Request $request,
        string $type,
        bool $isSubmit,
        array $existingSignature = [],
    ): array {
        return $type === QualityControlReport::TYPE_FABRICATION
            ? $this->fabricationPayloadFromRequest($request, $isSubmit, $existingSignature)
            : $this->refurbishPayloadFromRequest($request, $isSubmit, $existingSignature);
    }

    /**
     * @return array<string, mixed>
     */
    private function fabricationPayloadFromRequest(Request $request, bool $isSubmit, array $existingSignature = []): array
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
            'signature' => $this->signaturePayloadFromRequest($request, $isSubmit, $existingSignature),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function refurbishPayloadFromRequest(Request $request, bool $isSubmit, array $existingSignature = []): array
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
            'signature' => $this->signaturePayloadFromRequest($request, $isSubmit, $existingSignature),
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
     * @return array{signature_data: string, signer_name: string, signed_at: string, signer_user_id: ?int}
     */
    private function signaturePayloadFromRequest(
        Request $request,
        bool $isSubmit,
        array $existingSignature = [],
    ): array {
        $signatureData = '';
        $order = $request->route('order');
        $orderId = $order instanceof Order ? $order->id : 'manual';

        if ($request->hasFile('signature.signature_file')) {
            $signatureData = SignatureImageStorage::storeUploadedFile(
                $request->file('signature.signature_file'),
                'quality-control-maker-signatures/'.$orderId,
                'maker',
            );
        } else {
            $legacySignatureData = trim((string) $request->input('signature.signature_data', ''));

            if ($legacySignatureData !== '') {
                $signatureData = str_starts_with($legacySignatureData, 'data:image/png;base64,')
                    ? SignatureImageStorage::storeDataUri($legacySignatureData, 'quality-control-maker-signatures/'.$orderId, 'maker')
                    : '';
            } elseif (filled($existingSignature['signature_data'] ?? null)) {
                $signatureData = trim((string) $existingSignature['signature_data']);
            } elseif (! $isSubmit) {
                $signatureData = trim((string) $request->input('signature.signature_existing', ''));
            }
        }

        $signerName = $isSubmit
            ? trim((string) ($request->user()?->name ?? ''))
            : trim((string) $request->input('signature.signer_name', ''));

        if ($signerName === '') {
            $signerName = $request->user()?->name ?? '';
        }

        return [
            'signature_data' => $signatureData,
            'signer_name' => mb_substr($signerName, 0, 191),
            'signed_at' => $isSubmit
                ? now()->format('Y-m-d')
                : (string) ($request->input('signature.signed_at') ?: now()->format('Y-m-d')),
            'signer_user_id' => $isSubmit
                ? $request->user()?->id
                : (isset($existingSignature['signer_user_id']) ? (int) $existingSignature['signer_user_id'] : null),
        ];
    }

    private function resolveIntent(Request $request): string
    {
        $intent = trim((string) $request->input('intent', 'draft'));

        return $intent === 'submit' ? 'submit' : 'draft';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertSubmissionReady(Order $order, string $type, array $payload): void
    {
        $probe = new QualityControlReport([
            'order_id' => $order->id,
            'type' => $type,
            'status' => QualityControlReport::STATUS_SUBMITTED,
            'payload' => $payload,
        ]);
        $probe->setRelation('order', $order);

        if (! $probe->hasValidMakerSignature()) {
            throw ValidationException::withMessages([
                'signature.signature_data' => 'Tanda tangan Pembuat QC wajib diisi dengan gambar yang valid sebelum submit.',
            ]);
        }

        $this->signatureService->assertApprovalReady($probe);
    }

    /**
     * @return list<string>
     */
    private function storeUploadedFiles(Request $request, QualityControlReport $report, string $type): array
    {
        $storedPaths = [];

        foreach ($this->fileCategories($type) as $category) {
            $files = $request->file($category, []);

            if (! is_array($files)) {
                continue;
            }

            $nextSortOrder = (int) $report->files()->where('category', $category)->max('sort_order') + 1;

            foreach ($files as $file) {
                $path = $file->store('quality-control/'.$report->id.'/'.$category, 'public');
                $storedPaths[] = $path;

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

        return $storedPaths;
    }

    /**
     * @param  list<string>  $paths
     */
    private function cleanupStoredPaths(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
            }
        }
    }

    /**
     * Delete only a newly persisted maker signature. Existing draft signatures
     * must remain intact when a later update fails.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $existingSignature
     */
    private function cleanupNewMakerSignature(array $payload, array $existingSignature): void
    {
        $path = trim((string) ($payload['signature']['signature_data'] ?? ''));
        $existingPath = trim((string) ($existingSignature['signature_data'] ?? ''));

        if ($path === '' || $path === $existingPath || ! str_starts_with($path, 'quality-control-maker-signatures/')) {
            return;
        }

        Storage::disk('public')->delete($path);
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
