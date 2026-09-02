<?php

namespace App\Http\Controllers\Approval;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Http\Controllers\Admin\Hpp\HppController as AdminHppController;
use App\Http\Controllers\Admin\Orders\OrderDocumentController;
use App\Http\Controllers\Controller;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Services\Pkm\BastPdfAttachmentService;
use App\Support\BastApprovalSignatureBuilder;
use App\Support\PdfMergeService;
use App\Support\RecentApprovalSignatureResolver;
use App\Support\SignatureImageStorage;
use Barryvdh\DomPDF\Facade\Pdf;
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
        private readonly RecentApprovalSignatureResolver $recentSignatureResolver,
        private readonly BastPdfAttachmentService $bastPdfAttachmentService,
    ) {}

    public function show(Request $request, string $token): View
    {
        $signature = $this->resolveSignatureByToken($token);

        if (! $signature) {
            return view('approval.bast-signature', [
                'signature' => null,
                'token' => $token,
                'isExpired' => false,
                'bastPdfUrl' => null,
                'hppPdfUrl' => null,
                'abnormalitasUrl' => null,
                'terminOneBastPdfUrl' => null,
                'progressPercent' => 0,
                'signedCount' => 0,
                'totalSteps' => 0,
                'recentSignatureDataUrl' => null,
            ]);
        }

        $this->authorizeSigner($request, $signature);

        $signature->loadMissing([
            'lhppBast.signatures',
            'lhppBast.order.documents',
            'lhppBast.order.latestApprovedHpp',
            'lhppBast.hpp',
            'lhppBast.parentLhppBast',
            'signer',
        ]);

        $lhpp = $signature->lhppBast;
        $attachedHpp = $this->resolveAttachedHpp($lhpp);
        $terminOne = $this->resolveTerminOneBast($lhpp);
        $hasAbnormalitas = $this->hasOrderDocument(
            $lhpp->order?->documents ?? collect(),
            OrderDocumentType::Abnormalitas,
        );

        if ($signature->isPending() && ! $signature->opened_at) {
            $signature->update(['opened_at' => now()]);
        }

        return view('approval.bast-signature', [
            'signature' => $signature,
            'token' => $token,
            'isExpired' => $signature->isPending() && $signature->tokenExpired(),
            'bastPdfUrl' => route('approval.bast.pdf', $token),
            'hppPdfUrl' => $attachedHpp ? route('approval.bast.hpp', $token) : null,
            'abnormalitasUrl' => $hasAbnormalitas ? route('approval.bast.abnormalitas', $token) : null,
            'terminOneBastPdfUrl' => $lhpp->termin_type === 'termin_2' && $terminOne
                ? route('approval.bast.termin-one', $token)
                : null,
            'progressPercent' => $signature->lhppBast->approvalProgressPercent(),
            'signedCount' => $signature->lhppBast->approvalSignedCount(),
            'totalSteps' => $signature->lhppBast->approvalStepCount(),
            'recentSignatureDataUrl' => $this->recentSignatureResolver->latestFullSignatureForUser($request->user()),
        ]);
    }

    public function pdf(Request $request, string $token): Response
    {
        $signature = $this->resolveSignatureByToken($token);
        abort_unless($signature, 404, 'Token approval BAST tidak valid.');
        $this->authorizeSigner($request, $signature);

        $lhpp = LhppBast::query()
            ->with([
                'images',
                'garansi',
                'signatures',
                'purchaseOrder:id,order_id,purchase_order_number',
                'order.purchaseOrder:id,order_id,purchase_order_number',
            ])
            ->findOrFail($signature->lhpp_bast_id);

        $pdf = Pdf::loadView('pkm.lhpp.pdf', [
            'lhpp' => $lhpp,
            'materialItems' => collect($lhpp->material_items ?? []),
            'serviceItems' => collect($lhpp->service_items ?? []),
        ])->setPaper('a4', 'portrait')->output();
        $attachmentPdf = $this->bastPdfAttachmentService->pdfOutput($lhpp);
        $pdfOutputs = array_filter([$pdf, $attachmentPdf]);
        $pdfOutput = count($pdfOutputs) > 1
            ? app(PdfMergeService::class)->merge($pdfOutputs, context: ['controller' => static::class])
            : $pdf;

        return response($pdfOutput, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf(
                'inline; filename="bast-%s-%s.pdf"',
                $lhpp->termin_type === 'termin_2' ? 'termin-2' : 'termin-1',
                $lhpp->nomor_order,
            ),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function previewHpp(Request $request, string $token): Response
    {
        $signature = $this->resolveSignatureByToken($token);
        abort_unless($signature, 404, 'Token approval BAST tidak valid.');
        $this->authorizeSigner($request, $signature);

        $signature->loadMissing([
            'lhppBast.hpp',
            'lhppBast.order.latestApprovedHpp',
        ]);

        $hpp = $this->resolveAttachedHpp($signature->lhppBast);
        abort_unless($hpp, 404, 'HPP sumber BAST tidak ditemukan.');

        return app(AdminHppController::class)->pdf($hpp);
    }

    public function previewAbnormalitas(Request $request, string $token): Response
    {
        return $this->previewOrderDocument($request, $token, OrderDocumentType::Abnormalitas);
    }

    public function previewTerminOne(Request $request, string $token): Response
    {
        $signature = $this->resolveSignatureByToken($token);
        abort_unless($signature, 404, 'Token approval BAST tidak valid.');
        $this->authorizeSigner($request, $signature);

        $signature->loadMissing([
            'lhppBast.parentLhppBast',
        ]);

        $lhpp = $signature->lhppBast;
        abort_unless($lhpp->termin_type === 'termin_2', 404, 'BAST Termin 1 tidak tersedia.');

        $terminOne = $this->resolveTerminOneBast($lhpp);
        abort_unless(
            $terminOne && (int) $terminOne->order_id === (int) $lhpp->order_id,
            404,
            'BAST Termin 1 tidak ditemukan.',
        );

        $terminOne->loadMissing([
            'images',
            'garansi',
            'signatures',
            'purchaseOrder:id,order_id,purchase_order_number',
            'order.purchaseOrder:id,order_id,purchase_order_number',
        ]);

        $finalDocumentSignature = $terminOne->finalSignedDocumentSignature();

        if ($finalDocumentSignature?->hasUploadedSignedDocument()) {
            abort_unless(
                Storage::disk('public')->exists($finalDocumentSignature->signed_document_path),
                Response::HTTP_NOT_FOUND,
            );

            return response()->file(
                Storage::disk('public')->path($finalDocumentSignature->signed_document_path),
                [
                    'Content-Type' => $finalDocumentSignature->signed_document_mime_type
                        ?: (Storage::disk('public')->mimeType($finalDocumentSignature->signed_document_path) ?: 'application/octet-stream'),
                    'Content-Disposition' => sprintf(
                        'inline; filename="%s"',
                        $finalDocumentSignature->signed_document_original_name
                            ?: basename($finalDocumentSignature->signed_document_path),
                    ),
                    'Cache-Control' => 'private, no-store, max-age=0',
                ],
            );
        }

        $pdf = Pdf::loadView('pkm.lhpp.pdf', [
            'lhpp' => $terminOne,
            'materialItems' => collect($terminOne->material_items ?? []),
            'serviceItems' => collect($terminOne->service_items ?? []),
        ])->setPaper('a4', 'portrait')->output();
        $attachmentPdf = $this->bastPdfAttachmentService->pdfOutput($terminOne);
        $pdfOutputs = array_filter([$pdf, $attachmentPdf]);
        $pdfOutput = count($pdfOutputs) > 1
            ? app(PdfMergeService::class)->merge($pdfOutputs, context: ['controller' => static::class])
            : $pdf;

        return response($pdfOutput, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="bast-termin-1-%s.pdf"', $terminOne->nomor_order),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
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

            DB::transaction(function () use ($request, $signature, $validated): void {
                $lockedSignature = LhppBastSignature::query()
                    ->whereKey($signature->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->authorizeSigner($request, $lockedSignature);

                $lhpp = LhppBast::query()
                    ->whereKey($lockedSignature->lhpp_bast_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_unless($lockedSignature->isPending(), 403, 'Tahap tanda tangan BAST ini belum aktif.');
                abort_unless(! $lockedSignature->tokenExpired(), 403, 'Token approval BAST sudah kedaluwarsa.');

                $approvalNote = $this->normalizeNullableString($validated['approval_note'] ?? null);

                $lockedSignature->update([
                    'status' => LhppBastSignature::STATUS_SKIPPED,
                    'opened_at' => $lockedSignature->opened_at ?: now(),
                    'signed_at' => null,
                    'approval_note' => $approvalNote,
                    'token' => null,
                    'token_hash' => null,
                    'token_expires_at' => null,
                    'signed_ip' => $request->ip(),
                    'signed_user_agent' => substr((string) $request->userAgent(), 0, 2000),
                ]);

                LhppBastSignature::query()
                    ->where('lhpp_bast_id', $lhpp->id)
                    ->where('step_order', '>', $lockedSignature->step_order)
                    ->whereIn('status', [LhppBastSignature::STATUS_LOCKED, LhppBastSignature::STATUS_PENDING])
                    ->update([
                        'status' => LhppBastSignature::STATUS_SKIPPED,
                        'token' => null,
                        'token_hash' => null,
                        'token_expires_at' => null,
                    ]);

                $lhpp->update(['approval_status' => LhppBast::APPROVAL_REJECTED]);

                Log::warning('BAST rejected by approver', [
                    'bast_id' => $lhpp->id,
                    'order_id' => $lhpp->order_id,
                    'termin_type' => $lhpp->termin_type,
                    'signature_id' => $lockedSignature->id,
                    'signer_user_id' => $request->user()?->id,
                    'approval_note' => $approvalNote,
                ]);
            });

            return redirect()
                ->route('approver.dashboard')
                ->with('status', 'BAST berhasil ditolak. Dokumen tetap tersimpan dan harus dihapus oleh PKM sebelum dibuat ulang.');
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

    private function resolveAttachedHpp(LhppBast $lhpp): ?Hpp
    {
        return $lhpp->hpp ?: $lhpp->order?->latestApprovedHpp;
    }

    private function resolveTerminOneBast(LhppBast $lhpp): ?LhppBast
    {
        if ($lhpp->termin_type !== 'termin_2') {
            return null;
        }

        $parent = $lhpp->parentLhppBast;

        if ($parent && (int) $parent->order_id === (int) $lhpp->order_id) {
            return $parent;
        }

        return LhppBast::query()
            ->where('order_id', $lhpp->order_id)
            ->where('termin_type', 'termin_1')
            ->first();
    }

    private function previewOrderDocument(Request $request, string $token, OrderDocumentType $type): Response
    {
        $signature = $this->resolveSignatureByToken($token);
        abort_unless($signature, 404, 'Token approval BAST tidak valid.');
        $this->authorizeSigner($request, $signature);

        $signature->loadMissing(['lhppBast.order.documents']);

        $order = $signature->lhppBast->order;
        abort_unless($order, 404, 'Order sumber BAST tidak ditemukan.');

        $document = $order->documents->first(fn ($document): bool => $this->documentMatchesType($document, $type));
        abort_unless($document, 404, 'Dokumen '.$type->label().' tidak ditemukan.');

        return app(OrderDocumentController::class)->preview($order, $document);
    }

    private function hasOrderDocument(iterable $documents, OrderDocumentType $type): bool
    {
        foreach ($documents as $document) {
            if ($this->documentMatchesType($document, $type)) {
                return true;
            }
        }

        return false;
    }

    private function documentMatchesType(mixed $document, OrderDocumentType $type): bool
    {
        $documentType = $document->jenis_dokumen;

        return $documentType instanceof OrderDocumentType
            ? $documentType === $type
            : (string) $documentType === $type->value;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
