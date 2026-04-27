<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Admin\Orders\InitialWorkController as AdminInitialWorkController;
use App\Http\Controllers\Admin\Orders\OrderDocumentController;
use App\Http\Controllers\Controller;
use App\Models\InitialWorkSignature;
use App\Models\User;
use App\Services\InitialWorks\InitialWorkSignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InitialWorkSignatureController extends Controller
{
    public function __construct(
        private readonly InitialWorkSignatureService $signatureService,
    ) {
    }

    public function show(Request $request, string $token): View
    {
        $signature = $this->resolveSignatureByToken($token);
        $this->authorizeSigner($request, $signature);

        $signature->loadMissing([
            'initialWork.order.documents',
            'initialWork.outlineAgreement.unitWork',
            'signer',
        ]);

        return view('approval.initial-work-signature', [
            'signature' => $signature,
            'token' => $token,
            'isExpired' => $signature->isPending() && $signature->tokenExpired(),
            'nextApprovalUrl' => session('next_approval_url'),
            'abnormalitasUrl' => route('approval.initial-work.abnormalitas', $token),
            'gambarTeknikUrl' => route('approval.initial-work.gambar-teknik', $token),
            'initialWorkPdfUrl' => route('approval.initial-work.pdf', $token),
        ]);
    }

    public function pdf(Request $request, string $token): Response
    {
        $signature = $this->resolveSignatureByToken($token);
        $this->authorizeSigner($request, $signature);

        $signature->loadMissing([
            'initialWork.order',
            'initialWork.outlineAgreement.unitWork',
            'signer',
        ]);

        return app(AdminInitialWorkController::class)
            ->pdf($signature->initialWork->order, $signature->initialWork);
    }

    public function sign(Request $request, string $token): RedirectResponse
    {
        $signature = $this->resolveSignatureByToken($token);
        $this->authorizeSigner($request, $signature);

        if ($signature->isSigned()) {
            return redirect()
                ->route('approval.initial-work.show', $token)
                ->with('status', 'Dokumen ini sudah ditandatangani.');
        }

        abort_unless($signature->isPending(), 403, 'Tahap tanda tangan ini belum aktif.');
        abort_unless(! $signature->tokenExpired(), 403, 'Token approval sudah kedaluwarsa.');

        $validated = $request->validate([
            'signature_data' => ['required', 'string'],
        ]);

        $signaturePath = $this->storeSignatureImage((string) $validated['signature_data'], $signature);

        $signature->update([
            'status' => InitialWorkSignature::STATUS_SIGNED,
            'signature_path' => $signaturePath,
            'signed_at' => now(),
            'signed_ip' => $request->ip(),
            'signed_user_agent' => substr((string) $request->userAgent(), 0, 2000),
        ]);

        $nextApprovalUrl = $this->signatureService->activateNextSignature($signature);

        $redirect = redirect()
            ->route('approval.initial-work.show', $token)
            ->with('status', $nextApprovalUrl
                ? 'Tanda tangan berhasil disimpan. Approval berikutnya sudah diaktifkan.'
                : 'Tanda tangan berhasil disimpan.');

        if ($nextApprovalUrl) {
            $redirect->with('next_approval_url', $nextApprovalUrl);
        }

        return $redirect;
    }

    private function resolveSignatureByToken(string $token): InitialWorkSignature
    {
        return InitialWorkSignature::query()
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();
    }

    private function authorizeSigner(Request $request, InitialWorkSignature $signature): void
    {
        abort_unless(
            $request->user()?->role === User::ROLE_APPROVER
                && $signature->signer_user_id !== null
                && $request->user()?->id === $signature->signer_user_id,
            403,
            'Link approval ini hanya untuk penanda tangan yang ditetapkan.'
        );
    }

    private function storeSignatureImage(string $signatureData, InitialWorkSignature $signature): string
    {
        if (! str_starts_with($signatureData, 'data:image/png;base64,')) {
            throw ValidationException::withMessages([
                'signature_data' => 'Format tanda tangan tidak valid.',
            ]);
        }

        $base64 = substr($signatureData, strlen('data:image/png;base64,'));
        $binary = base64_decode($base64, true);

        if ($binary === false || strlen($binary) < 100) {
            throw ValidationException::withMessages([
                'signature_data' => 'Tanda tangan belum terbaca. Silakan tanda tangani ulang.',
            ]);
        }

        if (strlen($binary) > 1024 * 1024) {
            throw ValidationException::withMessages([
                'signature_data' => 'Ukuran tanda tangan terlalu besar.',
            ]);
        }

        $path = sprintf(
            'initial-work-signatures/%s/%s-%s.png',
            $signature->initial_work_id,
            $signature->role_key,
            now()->format('YmdHis')
        );

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public function previewAbnormalitas(Request $request, string $token): Response
    {
        $signature = $this->resolveSignatureByToken($token);
        $this->authorizeSigner($request, $signature);

        $signature->loadMissing(['initialWork.order.documents']);

        $order = $signature->initialWork->order;
        $document = $order->documents
            ->firstWhere('jenis_dokumen.value', 'abnormalitas');

        abort_unless($document, 404, 'Dokumen abnormalitas tidak ditemukan.');

        return app(OrderDocumentController::class)
            ->preview($order, $document);
    }

    public function previewGambarTeknik(Request $request, string $token): Response
    {
        $signature = $this->resolveSignatureByToken($token);
        $this->authorizeSigner($request, $signature);

        $signature->loadMissing(['initialWork.order.documents']);

        $order = $signature->initialWork->order;
        $document = $order->documents
            ->firstWhere('jenis_dokumen.value', 'gambar_teknik');

        abort_unless($document, 404, 'Dokumen gambar teknik tidak ditemukan.');

        return app(OrderDocumentController::class)
            ->preview($order, $document);
    }
}
