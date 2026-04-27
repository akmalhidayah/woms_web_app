<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Admin\Hpp\HppController as AdminHppController;
use App\Http\Controllers\Admin\Orders\OrderDocumentController;
use App\Domain\Orders\Enums\OrderDocumentType;
use App\Http\Controllers\Controller;
use App\Models\Hpp;
use App\Models\HppSignature;
use App\Support\HppApprovalSignatureBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
                'nextApprovalUrl' => null,
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
            'nextApprovalUrl' => session('next_approval_url'),
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

        $approvalAction = (string) $request->input('approval_action', 'sign');

        if ($approvalAction === 'reject') {
            $validated = $request->validate([
                'approval_note' => ['required', 'string', 'max:2000'],
            ], [
                'approval_note.required' => 'Catatan reject wajib diisi.',
            ]);

            DB::transaction(function () use ($request, $signature, $validated): void {
                $signature->update([
                    'status' => HppSignature::STATUS_SKIPPED,
                    'opened_at' => $signature->opened_at ?: now(),
                    'approval_note' => $this->normalizeNullableString($validated['approval_note'] ?? null),
                    'signed_ip' => $request->ip(),
                    'signed_user_agent' => substr((string) $request->userAgent(), 0, 2000),
                ]);

                HppSignature::query()
                    ->where('hpp_id', $signature->hpp_id)
                    ->where('step_order', '>', $signature->step_order)
                    ->whereIn('status', [HppSignature::STATUS_LOCKED, HppSignature::STATUS_PENDING])
                    ->update([
                        'status' => HppSignature::STATUS_SKIPPED,
                        'token' => null,
                        'token_hash' => null,
                        'token_expires_at' => null,
                    ]);

                $signature->hpp()->update([
                    'status' => Hpp::STATUS_REJECTED,
                ]);
            });

            return redirect()
                ->route('approval.hpp.show', $token)
                ->with('status', 'Dokumen HPP berhasil ditolak.');
        }

        $validated = $request->validate([
            'signature_data' => ['required', 'string'],
            'approval_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->validateSignatureData((string) $validated['signature_data']);

        $nextApprovalUrl = DB::transaction(function () use ($request, $signature, $validated): ?string {
            $signature->update([
                'status' => HppSignature::STATUS_SIGNED,
                'opened_at' => $signature->opened_at ?: now(),
                'signed_at' => now(),
                'signature_data' => (string) $validated['signature_data'],
                'approval_note' => $this->normalizeNullableString($validated['approval_note'] ?? null),
                'signed_ip' => $request->ip(),
                'signed_user_agent' => substr((string) $request->userAgent(), 0, 2000),
            ]);

            return $this->signatureBuilder->activateNextSignature($signature);
        });

        $redirect = redirect()
            ->route('approval.hpp.show', $token)
            ->with('status', $nextApprovalUrl
                ? 'Tanda tangan HPP berhasil disimpan.'
                : 'Tanda tangan HPP berhasil disimpan. Approval HPP selesai.');

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
        abort_unless(
            $signature->signer_user_id !== null
                && $request->user()?->id === $signature->signer_user_id,
            403,
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

    private function validateSignatureData(string $signatureData): void
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
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
