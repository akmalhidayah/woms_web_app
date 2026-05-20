<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Admin\Orders\OrderWorkshopQualityControlController;
use App\Http\Controllers\Controller;
use App\Models\QualityControlSignature;
use App\Models\User;
use App\Services\QualityControl\QualityControlSignatureService;
use App\Support\SignatureImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class QualityControlSignatureController extends Controller
{
    public function __construct(
        private readonly QualityControlSignatureService $signatureService,
    ) {
    }

    public function show(Request $request, string $token): View
    {
        $signature = $this->resolveSignatureByToken($token);
        $this->authorizeSigner($request, $signature);

        $signature->loadMissing([
            'qualityControlReport.order',
            'signer',
        ]);

        return view('approval.quality-control-signature', [
            'signature' => $signature,
            'token' => $token,
            'isExpired' => $signature->isPending() && $signature->tokenExpired(),
            'nextApprovalUrl' => session('next_approval_url'),
            'qualityControlPdfUrl' => route('approval.quality-control.pdf', $token),
        ]);
    }

    public function pdf(Request $request, string $token): Response
    {
        $signature = $this->resolveSignatureByToken($token);
        $this->authorizeSigner($request, $signature);

        $signature->loadMissing([
            'qualityControlReport.order',
            'qualityControlReport.files',
            'qualityControlReport.signatures',
            'signer',
        ]);

        $report = $signature->qualityControlReport;

        return app(OrderWorkshopQualityControlController::class)
            ->pdf($report->order, $report);
    }

    public function sign(Request $request, string $token): RedirectResponse
    {
        $signature = $this->resolveSignatureByToken($token);
        $this->authorizeSigner($request, $signature);

        if ($signature->isSigned()) {
            return redirect()
                ->route('approval.quality-control.show', $token)
                ->with('status', 'Dokumen QC ini sudah ditandatangani.');
        }

        abort_unless($signature->isPending(), 403, 'Tahap tanda tangan QC ini belum aktif.');
        abort_unless(! $signature->tokenExpired(), 403, 'Token approval QC sudah kedaluwarsa.');

        $validated = $request->validate([
            'signature_file' => ['nullable', 'file', 'mimetypes:image/png,image/jpeg', 'max:2048'],
            'signature_data' => ['nullable', 'string'],
        ]);

        $signaturePath = SignatureImageStorage::storeFromRequest(
            $request,
            'quality-control-signatures/'.$signature->quality_control_report_id,
            $signature->role_key,
        );

        $nextApprovalUrl = DB::transaction(function () use ($request, $signature, $signaturePath): ?string {
            $signature->update([
                'status' => QualityControlSignature::STATUS_SIGNED,
                'signature_data' => $signaturePath,
                'signed_at' => now(),
                'signed_ip' => $request->ip(),
                'signed_user_agent' => substr((string) $request->userAgent(), 0, 2000),
            ]);

            return $this->signatureService->activateNextSignature($signature);
        });

        $redirect = redirect()
            ->route('approval.quality-control.show', $token)
            ->with('approval_signed', true)
            ->with('status', $nextApprovalUrl
                ? 'Tanda tangan QC berhasil disimpan. Approval berikutnya sudah diaktifkan.'
                : 'Tanda tangan QC berhasil disimpan.');

        if ($nextApprovalUrl) {
            $redirect->with('next_approval_url', $nextApprovalUrl);
        }

        return $redirect;
    }

    private function resolveSignatureByToken(string $token): QualityControlSignature
    {
        return QualityControlSignature::query()
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();
    }

    private function authorizeSigner(Request $request, QualityControlSignature $signature): void
    {
        abort_unless(
            $request->user()?->role === User::ROLE_APPROVER
                && $signature->signer_user_id !== null
                && $request->user()?->id === $signature->signer_user_id,
            403,
            'Link approval QC ini hanya untuk penanda tangan yang ditetapkan.'
        );
    }

}
