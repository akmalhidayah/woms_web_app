<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Admin\Orders\InitialWorkController as AdminInitialWorkController;
use App\Http\Controllers\Admin\Orders\OrderDocumentController;
use App\Http\Controllers\Controller;
use App\Models\InitialWorkSignature;
use App\Services\InitialWorks\InitialWorkSignatureService;
use App\Support\SignatureImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InitialWorkSignatureController extends Controller
{
    public function __construct(
        private readonly InitialWorkSignatureService $signatureService,
    ) {}

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
            'signature_file' => ['nullable', 'file', 'mimetypes:image/png,image/jpeg', 'max:2048'],
            'signature_data' => ['nullable', 'string'],
        ]);

        $signaturePath = SignatureImageStorage::storeFromRequest(
            $request,
            'initial-work-signatures/'.$signature->initial_work_id,
            $signature->role_key,
        );

        try {
            $result = DB::transaction(function () use ($request, $signature, $signaturePath): array {
                $lockedSignature = InitialWorkSignature::query()
                    ->whereKey($signature->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->authorizeSigner($request, $lockedSignature);

                if ($lockedSignature->isSigned()) {
                    return ['processed' => false, 'next_approval_url' => null];
                }

                abort_unless($lockedSignature->isPending(), 403, 'Tahap tanda tangan ini belum aktif.');
                abort_unless(! $lockedSignature->tokenExpired(), 403, 'Token approval sudah kedaluwarsa.');

                $lockedSignature->update([
                    'status' => InitialWorkSignature::STATUS_SIGNED,
                    'signature_path' => $signaturePath,
                    'signed_at' => now(),
                    'signed_ip' => $request->ip(),
                    'signed_user_agent' => substr((string) $request->userAgent(), 0, 2000),
                ]);

                return [
                    'processed' => true,
                    'next_approval_url' => $this->signatureService->activateNextSignature($lockedSignature),
                ];
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($signaturePath);

            throw $exception;
        }

        if (! $result['processed']) {
            Storage::disk('public')->delete($signaturePath);

            return redirect()
                ->route('approval.initial-work.show', $token)
                ->with('status', 'Dokumen ini sudah ditandatangani.');
        }

        $nextApprovalUrl = $result['next_approval_url'];
        $redirect = redirect()
            ->route('approval.initial-work.show', $token)
            ->with('approval_signed', true)
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
        $authenticatedUserId = $request->user()?->id;
        $expectedSignerUserId = $signature->signer_user_id;
        $authorized = $expectedSignerUserId !== null
            && (int) $authenticatedUserId === (int) $expectedSignerUserId;

        if (! $authorized) {
            Log::warning('Initial Work approval signer authorization denied.', [
                'status_code' => Response::HTTP_FORBIDDEN,
                'initial_work_signature_id' => $signature->id,
                'initial_work_id' => $signature->initial_work_id,
                'role_key' => $signature->role_key,
                'role_label' => $signature->role_label,
                'authenticated_user_id' => $authenticatedUserId,
                'authenticated_user_role' => $request->user()?->role,
                'expected_signer_user_id' => $expectedSignerUserId,
            ]);
        }

        abort_unless(
            $authorized,
            Response::HTTP_FORBIDDEN,
            'Link approval ini hanya untuk penanda tangan yang ditetapkan.'
        );
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
