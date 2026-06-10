<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\StoreInitialWorkRequest;
use App\Http\Requests\Admin\Orders\UpdateInitialWorkRequest;
use App\Models\InitialWork;
use App\Models\InitialWorkSignature;
use App\Models\OutlineAgreement;
use App\Models\Order;
use App\Services\Approvals\ApprovalNotificationService;
use App\Services\InitialWorks\InitialWorkSignatureService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class InitialWorkController extends Controller
{
    public function __construct(
        private readonly InitialWorkSignatureService $signatureService,
        private readonly ApprovalNotificationService $approvalNotificationService,
    ) {
    }

    /**
     * Store a newly created initial work.
     */
    public function store(StoreInitialWorkRequest $request, Order $order): RedirectResponse
    {
        abort_unless(Order::priorityPrimaryFor($order->prioritas) === 'emergency', 403);

        if ($order->initialWork) {
            return redirect()
                ->route('admin.orders.index')
                ->with('status', 'Initial Work untuk order ini sudah tersedia.');
        }

        $outlineAgreement = $this->resolveOutlineAgreement($request->validated('outline_agreement_id'));
        $signatureUnit = $this->signatureService->resolveUnitForOutlineAgreement($outlineAgreement);
        $signatureSection = $this->signatureService->resolveSectionForOutlineAgreement($outlineAgreement);

        $initialWork = DB::transaction(function () use ($request, $order, $outlineAgreement, $signatureUnit, $signatureSection): InitialWork {
            return $order->initialWork()->create([
                'outline_agreement_id' => $outlineAgreement->id,
                'unit_work_id' => $signatureUnit?->id ?: $outlineAgreement->unit_work_id,
                'unit_work_section_id' => $signatureSection?->id,
                'nomor_initial_work' => $this->nextDocumentNumber(),
                'nomor_order' => $order->nomor_order,
                'notifikasi' => $order->notifikasi,
                'nama_pekerjaan' => $order->nama_pekerjaan,
                'unit_kerja' => $order->unit_kerja,
                'seksi' => $order->seksi,
                'kepada_yth' => $request->validated('kepada_yth') ?: 'PT. PRIMA KARYA MANUNGGAL',
                'perihal' => $request->validated('perihal'),
                'tanggal_initial_work' => $request->validated('tanggal_initial_work'),
                'functional_location' => $this->normalizeRowValues($request->validated('functional_location')),
                'scope_pekerjaan' => $this->normalizeRowValues($request->validated('scope_pekerjaan')),
                'qty' => $this->normalizeRowValues($request->validated('qty')),
                'stn' => $this->normalizeRowValues($request->validated('stn')),
                'keterangan' => $this->normalizeOptionalRowValues($request->validated('keterangan', []), count($request->validated('functional_location'))),
                'keterangan_pekerjaan' => $request->validated('keterangan_pekerjaan') ?: null,
                'created_by' => $request->user()?->id,
            ]);
        });

        $signatureResult = $this->signatureService->createSignatureChain($initialWork);
        $managerSignature = $signatureResult['manager_signature'];

        $redirect = redirect()
            ->route('admin.orders.index')
            ->with('status', $signatureResult['manager_url']
                ? sprintf('Initial Work untuk order %s berhasil dibuat. Token TTD Manager sudah dibuat.', $initialWork->nomor_order)
                : sprintf('Initial Work untuk order %s berhasil dibuat, tetapi penanda tangan Manager belum ditemukan di struktur organisasi.', $initialWork->nomor_order));

        if ($signatureResult['manager_url']) {
            $redirect
                ->with('initial_work_manager_approval_url', $signatureResult['manager_url'])
                ->with('initial_work_manager_name', $managerSignature?->signer_name)
                ->with('initial_work_manager_role', $managerSignature?->role_label);
        }

        return $redirect;
    }

    /**
     * Update the specified initial work.
     */
    public function update(UpdateInitialWorkRequest $request, Order $order, InitialWork $initialWork): RedirectResponse
    {
        abort_unless((int) $initialWork->order_id === (int) $order->getKey(), 404);

        if ($initialWork->hasSignedApproval()) {
            Log::warning('Blocked update to signed Initial Work document.', [
                'status_code' => Response::HTTP_FORBIDDEN,
                'user_id' => $request->user()?->id,
                'order_id' => $order->id,
                'initial_work_id' => $initialWork->id,
                'nomor_order' => $initialWork->nomor_order,
            ]);

            abort(Response::HTTP_FORBIDDEN, 'Initial Work tidak dapat diubah setelah proses tanda tangan dimulai.');
        }

        $outlineAgreement = $this->resolveOutlineAgreement($request->validated('outline_agreement_id'));
        $signatureUnit = $this->signatureService->resolveUnitForOutlineAgreement($outlineAgreement);
        $signatureSection = $this->signatureService->resolveSectionForOutlineAgreement($outlineAgreement);

        $initialWork->update([
            'outline_agreement_id' => $outlineAgreement->id,
            'unit_work_id' => $signatureUnit?->id ?: $outlineAgreement->unit_work_id,
            'unit_work_section_id' => $signatureSection?->id,
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'kepada_yth' => $request->validated('kepada_yth') ?: 'PT. PRIMA KARYA MANUNGGAL',
            'perihal' => $request->validated('perihal'),
            'tanggal_initial_work' => $request->validated('tanggal_initial_work'),
            'functional_location' => $this->normalizeRowValues($request->validated('functional_location')),
            'scope_pekerjaan' => $this->normalizeRowValues($request->validated('scope_pekerjaan')),
            'qty' => $this->normalizeRowValues($request->validated('qty')),
            'stn' => $this->normalizeRowValues($request->validated('stn')),
            'keterangan' => $this->normalizeOptionalRowValues($request->validated('keterangan', []), count($request->validated('functional_location'))),
            'keterangan_pekerjaan' => $request->validated('keterangan_pekerjaan') ?: null,
        ]);

        $signatureResult = $this->signatureService->rebuildIfUnsigned($initialWork->refresh());

        $redirect = redirect()
            ->route('admin.orders.index')
            ->with('status', $signatureResult['manager_url']
                ? sprintf('Initial Work untuk order %s berhasil diperbarui. Token TTD Manager baru sudah dibuat.', $initialWork->nomor_order)
                : sprintf('Initial Work untuk order %s berhasil diperbarui.', $initialWork->nomor_order));

        if ($signatureResult['manager_url']) {
            $redirect
                ->with('initial_work_manager_approval_url', $signatureResult['manager_url'])
                ->with('initial_work_manager_name', $signatureResult['manager_signature']?->signer_name)
                ->with('initial_work_manager_role', $signatureResult['manager_signature']?->role_label);
        }

        return $redirect;
    }

    /**
     * Stream the initial work as PDF.
     */
    public function pdf(Order $order, InitialWork $initialWork): Response
    {
        abort_unless((int) $initialWork->order_id === (int) $order->getKey(), 404);

        $initialWork->loadMissing(['signatures', 'unitWork']);

        $pdf = Pdf::loadView('admin.orders.initial-work-pdf', [
            'order' => $order,
            'initialWork' => $initialWork,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('initial-work-'.$order->nomor_order.'.pdf');
    }

    public function resendApproval(Request $request, Order $order, InitialWork $initialWork): RedirectResponse
    {
        abort_unless((int) $initialWork->order_id === (int) $order->getKey(), Response::HTTP_NOT_FOUND);

        $signature = $initialWork->signatures()
            ->where('status', InitialWorkSignature::STATUS_PENDING)
            ->orderBy('step_order')
            ->first();

        abort_unless(
            $signature && ! $signature->tokenExpired() && $signature->approvalUrl(),
            Response::HTTP_CONFLICT,
            'Tidak ada link approval Initial Work aktif yang dapat dikirim ulang.'
        );

        if (! $this->approvalNotificationService->sendInitialWork($signature, true)) {
            abort(Response::HTTP_BAD_GATEWAY, 'Email approval Initial Work gagal dikirim.');
        }

        return back()->with('status', sprintf(
            'Link approval Initial Work berhasil dikirim ulang ke %s.',
            $signature->signer?->email ?: 'email approver',
        ));
    }

    public function regenerateApprovalToken(Request $request, Order $order, InitialWork $initialWork): RedirectResponse
    {
        abort_unless((int) $initialWork->order_id === (int) $order->getKey(), Response::HTTP_NOT_FOUND);

        $signature = $initialWork->signatures()
            ->where('status', InitialWorkSignature::STATUS_PENDING)
            ->orderBy('step_order')
            ->first();

        abort_unless(
            $signature && $signature->tokenExpired(),
            Response::HTTP_CONFLICT,
            'Tidak ada token approval Initial Work kedaluwarsa yang dapat dibuat ulang.'
        );

        $this->signatureService->regenerateExpiredToken($signature);

        return back()->with('status', sprintf(
            'Token approval Initial Work untuk %s berhasil dibuat ulang dan email baru telah dikirim.',
            $signature->role_label ?: $signature->signer_name ?: 'approver',
        ));
    }

    /**
     * Build the next document number preview.
     */
    public static function previewDocumentNumber(): string
    {
        return app(self::class)->nextDocumentNumber();
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private function normalizeRowValues(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->values()
            ->all();
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private function normalizeOptionalRowValues(array $values, int $targetCount): array
    {
        return collect(range(0, max(0, $targetCount - 1)))
            ->map(fn ($index) => trim((string) ($values[$index] ?? '')))
            ->values()
            ->all();
    }

    /**
     * Generate the next Initial Work document number.
     */
    private function nextDocumentNumber(): string
    {
        $now = now();
        $month = $now->format('m');
        $year = $now->format('Y');

        $lastInitialWork = InitialWork::query()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->latest('created_at')
            ->first();

        $nextNumber = '001';

        if ($lastInitialWork && preg_match('/^(\d{3})\//', $lastInitialWork->nomor_initial_work, $matches)) {
            $nextNumber = str_pad(((int) $matches[1]) + 1, 3, '0', STR_PAD_LEFT);
        }

        return sprintf('%s/IW/25.10/%s-%s', $nextNumber, $month, $year);
    }

    private function resolveOutlineAgreement(int|string $outlineAgreementId): OutlineAgreement
    {
        return OutlineAgreement::query()
            ->with(['unitWork.department', 'unitWork.seniorManager', 'unitWork.sections.manager'])
            ->findOrFail((int) $outlineAgreementId);
    }
}
