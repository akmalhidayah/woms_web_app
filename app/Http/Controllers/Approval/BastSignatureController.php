<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Admin\LhppController as AdminLhppController;
use App\Http\Controllers\Controller;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Support\BastApprovalSignatureBuilder;
use App\Support\SignatureImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BastSignatureController extends Controller
{
    public function __construct(
        private readonly BastApprovalSignatureBuilder $signatureBuilder,
    ) {
    }

    public function show(Request $request, string $token): View
    {
        $signature = $this->resolveSignatureByToken($token);

        if (! $signature) {
            return view('approval.bast-signature', [
                'signature' => null,
                'token' => $token,
                'isExpired' => false,
                'bastPdfUrl' => null,
                'progressPercent' => 0,
                'signedCount' => 0,
                'totalSteps' => 0,
            ]);
        }

        $this->authorizeSigner($request, $signature);

        $signature->loadMissing([
            'lhppBast.signatures',
            'lhppBast.order',
            'signer',
        ]);

        if ($signature->isPending() && ! $signature->opened_at) {
            $signature->update(['opened_at' => now()]);
        }

        return view('approval.bast-signature', [
            'signature' => $signature,
            'token' => $token,
            'isExpired' => $signature->isPending() && $signature->tokenExpired(),
            'bastPdfUrl' => route('approval.bast.pdf', $token),
            'progressPercent' => $signature->lhppBast->approvalProgressPercent(),
            'signedCount' => $signature->lhppBast->approvalSignedCount(),
            'totalSteps' => $signature->lhppBast->approvalStepCount(),
        ]);
    }

    public function pdf(Request $request, string $token): Response
    {
        $signature = $this->resolveSignatureByToken($token);
        abort_unless($signature, 404, 'Token approval BAST tidak valid.');
        $this->authorizeSigner($request, $signature);

        return app(AdminLhppController::class)->pdf($request, $signature->lhpp_bast_id);
    }

    public function sign(Request $request, string $token): RedirectResponse
    {
        $signature = $this->resolveSignatureByToken($token);
        abort_unless($signature, 404, 'Token approval BAST tidak valid.');
        $this->authorizeSigner($request, $signature);

        if ($signature->isSigned()) {
            return redirect()
                ->route('approval.bast.show', $token)
                ->with('status', 'Dokumen BAST ini sudah ditandatangani.');
        }

        if ($signature->lhppBast?->approval_status === LhppBast::APPROVAL_REJECTED) {
            return redirect()
                ->route('approval.bast.show', $token)
                ->with('status', 'Dokumen BAST ini sudah ditolak.');
        }

        abort_unless($signature->isPending(), 403, 'Tahap tanda tangan BAST ini belum aktif.');
        abort_unless(! $signature->tokenExpired(), 403, 'Token approval BAST sudah kedaluwarsa.');

        $approvalAction = (string) $request->input('approval_action', 'sign');

        if ($approvalAction === 'reject') {
            $validated = $request->validate([
                'approval_note' => ['required', 'string', 'max:2000'],
            ], [
                'approval_note.required' => 'Catatan reject wajib diisi.',
            ]);

            $result = DB::transaction(function () use ($request, $signature, $validated): string {
                $lockedSignature = LhppBastSignature::query()
                    ->whereKey($signature->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->authorizeSigner($request, $lockedSignature);

                if ($lockedSignature->isSigned()) {
                    return 'signed';
                }

                $lhpp = LhppBast::query()
                    ->whereKey($lockedSignature->lhpp_bast_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lhpp->approval_status === LhppBast::APPROVAL_REJECTED || $lockedSignature->isSkipped()) {
                    return 'rejected';
                }

                abort_unless($lockedSignature->isPending(), 403, 'Tahap tanda tangan BAST ini belum aktif.');
                abort_unless(! $lockedSignature->tokenExpired(), 403, 'Token approval BAST sudah kedaluwarsa.');

                $lockedSignature->update([
                    'status' => LhppBastSignature::STATUS_SKIPPED,
                    'opened_at' => $lockedSignature->opened_at ?: now(),
                    'approval_note' => $this->normalizeNullableString($validated['approval_note'] ?? null),
                    'signed_ip' => $request->ip(),
                    'signed_user_agent' => substr((string) $request->userAgent(), 0, 2000),
                ]);

                LhppBastSignature::query()
                    ->where('lhpp_bast_id', $lockedSignature->lhpp_bast_id)
                    ->where('step_order', '>', $lockedSignature->step_order)
                    ->whereIn('status', [LhppBastSignature::STATUS_LOCKED, LhppBastSignature::STATUS_PENDING])
                    ->update([
                        'status' => LhppBastSignature::STATUS_SKIPPED,
                        'token' => null,
                        'token_hash' => null,
                        'token_expires_at' => null,
                    ]);

                $lhpp->update([
                    'approval_status' => LhppBast::APPROVAL_REJECTED,
                ]);

                return 'rejected_now';
            });

            $message = match ($result) {
                'signed' => 'Dokumen BAST ini sudah ditandatangani.',
                'rejected' => 'Dokumen BAST ini sudah ditolak.',
                default => 'Dokumen BAST berhasil ditolak.',
            };

            return redirect()
                ->route('approval.bast.show', $token)
                ->with('status', $message);
        }

        if ($signature->role_key === 'dirops') {
            return redirect()
                ->route('approval.bast.show', $token)
                ->with('status', 'Tahap DIROPS BAST diselesaikan melalui upload dokumen final oleh PKM.');
        }

        $validated = $request->validate([
            'signature_file' => ['nullable', 'file', 'mimetypes:image/png,image/jpeg', 'max:2048'],
            'signature_data' => ['nullable', 'string'],
            'approval_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $signaturePath = SignatureImageStorage::storeFromRequest(
            $request,
            'bast-signatures/'.$signature->lhpp_bast_id,
            $signature->role_key,
        );

        try {
            $result = DB::transaction(function () use ($request, $signature, $validated, $signaturePath): array {
                $lockedSignature = LhppBastSignature::query()
                    ->whereKey($signature->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->authorizeSigner($request, $lockedSignature);

                if ($lockedSignature->isSigned()) {
                    return ['processed' => false, 'state' => 'signed'];
                }

                $lhpp = LhppBast::query()
                    ->whereKey($lockedSignature->lhpp_bast_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lhpp->approval_status === LhppBast::APPROVAL_REJECTED || $lockedSignature->isSkipped()) {
                    return ['processed' => false, 'state' => 'rejected'];
                }

                abort_unless($lockedSignature->isPending(), 403, 'Tahap tanda tangan BAST ini belum aktif.');
                abort_unless(! $lockedSignature->tokenExpired(), 403, 'Token approval BAST sudah kedaluwarsa.');

                $lockedSignature->update([
                    'status' => LhppBastSignature::STATUS_SIGNED,
                    'opened_at' => $lockedSignature->opened_at ?: now(),
                    'signed_at' => now(),
                    'signature_data' => $signaturePath,
                    'approval_note' => $this->normalizeNullableString($validated['approval_note'] ?? null),
                    'signed_ip' => $request->ip(),
                    'signed_user_agent' => substr((string) $request->userAgent(), 0, 2000),
                ]);

                $this->signatureBuilder->activateNextSignature($lockedSignature);

                return ['processed' => true, 'state' => 'signed_now'];
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($signaturePath);

            throw $exception;
        }

        if (! $result['processed']) {
            Storage::disk('public')->delete($signaturePath);

            return redirect()
                ->route('approval.bast.show', $token)
                ->with('status', $result['state'] === 'rejected'
                    ? 'Dokumen BAST ini sudah ditolak.'
                    : 'Dokumen BAST ini sudah ditandatangani.');
        }

        return redirect()
            ->route('approval.bast.show', $token)
            ->with('approval_signed', true)
            ->with('status', $signature->fresh()->lhppBast?->approval_status === LhppBast::APPROVAL_APPROVED
                ? 'Tanda tangan BAST berhasil disimpan. Approval BAST selesai.'
                : 'Tanda tangan BAST berhasil disimpan.');
    }

    private function resolveSignatureByToken(string $token): ?LhppBastSignature
    {
        return LhppBastSignature::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }

    private function authorizeSigner(Request $request, LhppBastSignature $signature): void
    {
        $authenticatedUserId = $request->user()?->id;
        $expectedSignerUserId = $signature->signer_user_id;
        $authorized = $expectedSignerUserId !== null
            && (int) $authenticatedUserId === (int) $expectedSignerUserId;

        if (! $authorized) {
            Log::warning('BAST approval signer authorization denied.', [
                'status_code' => Response::HTTP_FORBIDDEN,
                'lhpp_bast_signature_id' => $signature->id,
                'lhpp_bast_id' => $signature->lhpp_bast_id,
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
            'Link approval BAST ini hanya untuk penanda tangan yang ditetapkan.'
        );
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
