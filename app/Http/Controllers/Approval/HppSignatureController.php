<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Admin\Hpp\HppController as AdminHppController;
use App\Http\Controllers\Admin\Orders\OrderDocumentController;
use App\Domain\Orders\Enums\OrderDocumentType;
use App\Http\Controllers\Controller;
use App\Models\Hpp;
use App\Models\HppSignature;
use App\Support\HppApprovalSignatureBuilder;
use App\Support\SignatureImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class HppSignatureController extends Controller
{
    public function __construct(
        private readonly HppApprovalSignatureBuilder $signatureBuilder,
    ) {
    }

    public function show(Request $request, string $token): View
    {
        $signature = $this->resolveSignatureByToken($token);

        if (! $signature) {
            return view('approval.hpp-signature', [
                'signature' => null,
                'token' => $token,
                'isExpired' => false,
                'hppPdfUrl' => null,
                'abnormalitasUrl' => null,
                'gambarTeknikUrl' => null,
                'progressPercent' => 0,
                'signedCount' => 0,
                'totalSteps' => 0,
            ]);
        }

        $this->authorizeSigner($request, $signature);

        $signature->loadMissing([
            'hpp.order.documents',
            'hpp.signatures',
            'signer',
        ]);

        if ($signature->isPending() && ! $signature->opened_at) {
            $signature->update(['opened_at' => now()]);
        }

        return view('approval.hpp-signature', [
            'signature' => $signature,
            'token' => $token,
            'isExpired' => $signature->isPending() && $signature->tokenExpired(),
            'hppPdfUrl' => route('approval.hpp.pdf', $token),
            'abnormalitasUrl' => route('approval.hpp.abnormalitas', $token),
            'gambarTeknikUrl' => route('approval.hpp.gambar-teknik', $token),
            'progressPercent' => $signature->hpp->approvalProgressPercent(),
            'signedCount' => $signature->hpp->approvalSignedCount(),
            'totalSteps' => $signature->hpp->approvalStepCount(),
        ]);
    }

    public function pdf(Request $request, string $token): Response
    {
        $signature = $this->resolveSignatureByToken($token);
        abort_unless($signature, 404, 'Token approval HPP tidak valid.');
        $this->authorizeSigner($request, $signature);

        $signature->loadMissing('hpp');

        return app(AdminHppController::class)->pdf($signature->hpp);
    }

    public function previewAbnormalitas(Request $request, string $token): Response
    {
        return $this->previewOrderDocument($request, $token, OrderDocumentType::Abnormalitas);
    }

    public function previewGambarTeknik(Request $request, string $token): Response
    {
        return $this->previewOrderDocument($request, $token, OrderDocumentType::GambarTeknik);
    }

    public function sign(Request $request, string $token): RedirectResponse
    {
        $signature = $this->resolveSignatureByToken($token);
        abort_unless($signature, 404, 'Token approval HPP tidak valid.');
        $this->authorizeSigner($request, $signature);

        if ($signature->isSigned()) {
            return redirect()
                ->route('approval.hpp.show', $token)
                ->with('status', 'Dokumen HPP ini sudah ditandatangani.');
        }

        if ($signature->hpp?->status === Hpp::STATUS_REJECTED) {
            return redirect()
                ->route('approval.hpp.show', $token)
                ->with('status', 'Dokumen HPP ini sudah ditolak.');
        }

        abort_unless($signature->isPending(), 403, 'Tahap tanda tangan HPP ini belum aktif.');
        abort_unless(! $signature->tokenExpired(), 403, 'Token approval HPP sudah kedaluwarsa.');

        if ($signature->role_key === 'dirops') {
            return redirect()
                ->route('approval.hpp.show', $token)
                ->with('status', 'Tahap DIROPS HPP diselesaikan melalui upload dokumen final oleh admin.');
        }

        $approvalAction = (string) $request->input('approval_action', 'sign');

        if ($approvalAction === 'reject') {
            $validated = $request->validate([
                'approval_note' => ['required', 'string', 'max:2000'],
            ], [
                'approval_note.required' => 'Catatan reject wajib diisi.',
            ]);

            $result = DB::transaction(function () use ($request, $signature, $validated): string {
                $lockedSignature = HppSignature::query()
                    ->whereKey($signature->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->authorizeSigner($request, $lockedSignature);

                if ($lockedSignature->isSigned()) {
                    return 'signed';
                }

                $hpp = Hpp::query()
                    ->whereKey($lockedSignature->hpp_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($hpp->status === Hpp::STATUS_REJECTED || $lockedSignature->isSkipped()) {
                    return 'rejected';
                }

                abort_unless($lockedSignature->isPending(), 403, 'Tahap tanda tangan HPP ini belum aktif.');
                abort_unless(! $lockedSignature->tokenExpired(), 403, 'Token approval HPP sudah kedaluwarsa.');

                $lockedSignature->update([
                    'status' => HppSignature::STATUS_SKIPPED,
                    'opened_at' => $lockedSignature->opened_at ?: now(),
                    'approval_note' => $this->normalizeNullableString($validated['approval_note'] ?? null),
                    'signed_ip' => $request->ip(),
                    'signed_user_agent' => substr((string) $request->userAgent(), 0, 2000),
                ]);

                HppSignature::query()
                    ->where('hpp_id', $lockedSignature->hpp_id)
                    ->where('step_order', '>', $lockedSignature->step_order)
                    ->whereIn('status', [HppSignature::STATUS_LOCKED, HppSignature::STATUS_PENDING])
                    ->update([
                        'status' => HppSignature::STATUS_SKIPPED,
                        'token' => null,
                        'token_hash' => null,
                        'token_expires_at' => null,
                    ]);

                $hpp->update([
                    'status' => Hpp::STATUS_REJECTED,
                ]);

                return 'rejected_now';
            });

            $message = match ($result) {
                'signed' => 'Dokumen HPP ini sudah ditandatangani.',
                'rejected' => 'Dokumen HPP ini sudah ditolak.',
                default => 'Dokumen HPP berhasil ditolak.',
            };

            return redirect()
                ->route('approval.hpp.show', $token)
                ->with('status', $message);
        }

        $validated = $request->validate([
            'signature_file' => ['nullable', 'file', 'mimetypes:image/png,image/jpeg', 'max:2048'],
            'signature_data' => ['nullable', 'string'],
            'approval_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $signaturePath = SignatureImageStorage::storeFromRequest(
            $request,
            'hpp-signatures/'.$signature->hpp_id,
            $signature->role_key,
        );

        try {
            $result = DB::transaction(function () use ($request, $signature, $validated, $signaturePath): array {
                $lockedSignature = HppSignature::query()
                    ->whereKey($signature->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->authorizeSigner($request, $lockedSignature);

                if ($lockedSignature->isSigned()) {
                    return ['processed' => false, 'state' => 'signed'];
                }

                $hpp = Hpp::query()
                    ->whereKey($lockedSignature->hpp_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($hpp->status === Hpp::STATUS_REJECTED || $lockedSignature->isSkipped()) {
                    return ['processed' => false, 'state' => 'rejected'];
                }

                abort_unless($lockedSignature->isPending(), 403, 'Tahap tanda tangan HPP ini belum aktif.');
                abort_unless(! $lockedSignature->tokenExpired(), 403, 'Token approval HPP sudah kedaluwarsa.');

                $lockedSignature->update([
                    'status' => HppSignature::STATUS_SIGNED,
                    'opened_at' => $lockedSignature->opened_at ?: now(),
                    'signed_at' => now(),
                    'signature_data' => $signaturePath,
                    'approval_note' => $this->normalizeNullableString($validated['approval_note'] ?? null),
                    'signed_ip' => $request->ip(),
                    'signed_user_agent' => substr((string) $request->userAgent(), 0, 2000),
                ]);

                $this->signatureBuilder->activateNextSignature($lockedSignature);

                return [
                    'processed' => true,
                    'state' => 'signed_now',
                ];
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($signaturePath);

            throw $exception;
        }

        if (! $result['processed']) {
            Storage::disk('public')->delete($signaturePath);

            return redirect()
                ->route('approval.hpp.show', $token)
                ->with('status', $result['state'] === 'rejected'
                    ? 'Dokumen HPP ini sudah ditolak.'
                    : 'Dokumen HPP ini sudah ditandatangani.');
        }

        $redirect = redirect()
            ->route('approval.hpp.show', $token)
            ->with('approval_signed', true)
            ->with('status', 'Tanda tangan HPP berhasil disimpan.');

        return $redirect;
    }

    private function resolveSignatureByToken(string $token): ?HppSignature
    {
        return HppSignature::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }

    private function authorizeSigner(Request $request, HppSignature $signature): void
    {
        $authenticatedUserId = $request->user()?->id;
        $expectedSignerUserId = $signature->signer_user_id;
        $authorized = $expectedSignerUserId !== null
            && (int) $authenticatedUserId === (int) $expectedSignerUserId;

        if (! $authorized) {
            Log::warning('HPP approval signer authorization denied.', [
                'status_code' => Response::HTTP_FORBIDDEN,
                'hpp_signature_id' => $signature->id,
                'hpp_id' => $signature->hpp_id,
                'role_key' => $signature->role_key,
                'role_label' => $signature->role_label,
                'authenticated_user_id' => $authenticatedUserId,
                'expected_signer_user_id' => $expectedSignerUserId,
            ]);
        }

        abort_unless(
            $authorized,
            Response::HTTP_FORBIDDEN,
            'Link approval HPP ini hanya untuk penanda tangan yang ditetapkan.'
        );
    }

    private function previewOrderDocument(Request $request, string $token, OrderDocumentType $type): Response
    {
        $signature = $this->resolveSignatureByToken($token);
        abort_unless($signature, 404, 'Token approval HPP tidak valid.');
        $this->authorizeSigner($request, $signature);

        $signature->loadMissing(['hpp.order.documents']);

        $order = $signature->hpp->order;
        abort_unless($order, 404, 'Order sumber HPP tidak ditemukan.');

        $document = $order->documents->first(function ($document) use ($type): bool {
            $documentType = $document->jenis_dokumen;

            return $documentType instanceof OrderDocumentType
                ? $documentType === $type
                : (string) $documentType === $type->value;
        });

        abort_unless($document, 404, 'Dokumen '.$type->label().' tidak ditemukan.');

        return app(OrderDocumentController::class)->preview($order, $document);
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
