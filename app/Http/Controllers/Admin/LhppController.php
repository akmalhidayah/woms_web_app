<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\User;
use App\Services\Approvals\ApprovalNotificationService;
use App\Services\Approvals\ApprovalSignatureRollbackService;
use App\Services\Pkm\BastDeletionService;
use App\Support\BastApprovalSignatureBuilder;
use App\Support\BastEffectiveApprovalFlowResolver;
use App\Support\PdfMergeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LhppController extends Controller
{
    public function __construct(
        private readonly BastApprovalSignatureBuilder $signatureBuilder,
        private readonly ApprovalNotificationService $approvalNotificationService,
        private readonly ApprovalSignatureRollbackService $rollbackService,
        private readonly BastDeletionService $bastDeletionService,
        private readonly BastEffectiveApprovalFlowResolver $effectiveFlowResolver,
    ) {}

    public function destroy(Request $request, LhppBast $lhppBast): RedirectResponse
    {
        $auditContext = [
            'user_id' => $request->user()?->id,
            'bast_id' => $lhppBast->id,
            'order_id' => $lhppBast->order_id,
            'nomor_order' => $lhppBast->nomor_order,
            'termin_type' => $lhppBast->termin_type,
            'approval_status' => $lhppBast->approval_status,
        ];

        try {
            $this->bastDeletionService->delete($lhppBast);

            Log::warning('BAST deleted by admin for recreation.', $auditContext);

            return redirect()
                ->route('admin.lhpp.index', $request->only('search', 'page'))
                ->with('status', sprintf(
                    'BAST order %s berhasil dihapus seluruhnya. PKM dapat membuat BAST ulang.',
                    $auditContext['nomor_order'],
                ));
        } catch (Throwable $exception) {
            Log::error('Failed to delete BAST by admin.', array_merge($auditContext, [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]));

            return back()->withErrors([
                'delete_bast' => 'BAST gagal dihapus. Silakan coba kembali.',
            ]);
        }
    }

    public function updateQualityControl(Request $request, int $lhppId)
    {
        $lhpp = LhppBast::query()
            ->with('childLhppBasts')
            ->findOrFail($lhppId);

        $validated = $request->validate([
            'quality_control_status' => ['required', 'in:pending,approved,rejected'],
        ]);

        $approvalStartedDocument = collect([$lhpp])
            ->merge($lhpp->childLhppBasts)
            ->first(fn (LhppBast $document): bool => $document->hasApprovalStarted());

        if ($approvalStartedDocument) {
            Log::warning('Blocked BAST quality control status update after approval started.', [
                'status_code' => Response::HTTP_FORBIDDEN,
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhpp->id,
                'approval_started_lhpp_id' => $approvalStartedDocument->id,
                'nomor_order' => $lhpp->nomor_order,
                'termin_type' => $approvalStartedDocument->termin_type,
            ]);

            abort(
                Response::HTTP_FORBIDDEN,
                'Status Quality Control tidak dapat diubah setelah approval BAST dimulai.'
            );
        }

        try {
            $lhpp->quality_control_status = $validated['quality_control_status'];
            $lhpp->updated_by = $request->user()?->id;
            $lhpp->save();

            if ($validated['quality_control_status'] === 'approved') {
                $this->activateBastApprovalAfterQualityControl($lhpp);

                foreach ($lhpp->childLhppBasts as $childLhpp) {
                    $childLhpp->quality_control_status = 'approved';
                    $childLhpp->updated_by = $request->user()?->id;
                    $childLhpp->save();
                    $this->activateBastApprovalAfterQualityControl($childLhpp);
                }
            }

            return redirect()
                ->route('admin.lhpp.index', $request->only('search', 'page'))
                ->with('status', sprintf('Quality control untuk order %s berhasil diperbarui.', $lhpp->nomor_order));
        } catch (Throwable $exception) {
            Log::error('Failed to update admin BAST quality control.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhppId,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return back()->withErrors([
                'quality_control_status' => 'Terjadi kesalahan saat memperbarui quality control BAST.',
            ]);
        }
    }

    private function activateBastApprovalAfterQualityControl(LhppBast $lhpp): void
    {
        $lhpp->loadMissing('signatures');

        if ($lhpp->approval_status === LhppBast::APPROVAL_REJECTED || $lhpp->hasApprovalStarted()) {
            return;
        }

        $this->syncBastSignaturesBeforeApprovalStarts($lhpp);
        $this->signatureBuilder->ensureSignatures($lhpp);
        $this->signatureBuilder->activateFirstSignature($lhpp);
    }

    private function syncBastSignaturesBeforeApprovalStarts(LhppBast $lhpp): void
    {
        $lhpp->refresh();

        if ($lhpp->hasApprovalStarted()) {
            return;
        }

        $flow = is_array($lhpp->approval_flow)
            ? array_values(array_filter(
                array_map(static fn (mixed $role): string => trim((string) $role), $lhpp->approval_flow),
                static fn (string $role): bool => $role !== ''
            ))
            : [];

        if ($flow === []) {
            return;
        }

        $lockedSignatures = $lhpp->signatures()
            ->where('status', LhppBastSignature::STATUS_LOCKED)
            ->orderBy('step_order')
            ->get();
        $lockedFlow = $lockedSignatures->pluck('role_label')
            ->map(static fn (mixed $role): string => trim((string) $role))
            ->filter()->values()->all();

        if ($lockedFlow === []) {
            return;
        }

        $effectiveSteps = $this->effectiveFlowResolver->resolveSteps($lhpp, $flow);
        $effectiveFlow = collect($effectiveSteps)->pluck('flow_role_label')->values()->all();

        if ($effectiveFlow !== $flow) {
            return;
        }

        $expected = collect($effectiveSteps)
            ->map(fn (array $step, int $index): array => [
                'step_order' => $index + 1,
                'role_key' => $step['role_key'],
                'signer_user_id' => $step['user']->id,
            ]);
        $matches = $lockedSignatures->count() === $expected->count()
            && $lockedSignatures->values()->every(function (LhppBastSignature $signature, int $index) use ($expected): bool {
                $step = $expected[$index];

                return $signature->step_order === $step['step_order']
                    && $signature->role_key === $step['role_key']
                    && (int) $signature->signer_user_id === (int) $step['signer_user_id'];
            });

        if ($lockedFlow === $flow && $matches) {
            return;
        }

        $lhpp->signatures()
            ->where('status', LhppBastSignature::STATUS_LOCKED)
            ->delete();

        $lhpp->unsetRelation('signatures');
        $this->signatureBuilder->ensureSignatures($lhpp->fresh());
    }

    public function index(Request $request): View
    {
        try {
            $search = trim((string) $request->string('search'));

            $lhpps = LhppBast::query()
                ->with([
                    'order:id,nomor_order,notifikasi,nama_pekerjaan,unit_kerja,seksi',
                    'purchaseOrder:id,order_id,purchase_order_number',
                    'terminTwo.signatures.signer:id,name,nomor_hp',
                    'terminTwo.activeSignature.signer:id,name,nomor_hp',
                    'garansi',
                    'signatures.signer:id,name,nomor_hp',
                    'activeSignature.signer:id,name,nomor_hp',
                ])
                ->where('termin_type', 'termin_1')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('nomor_order', 'like', "%{$search}%")
                            ->orWhere('purchase_order_number', 'like', "%{$search}%")
                            ->orWhere('unit_kerja', 'like', "%{$search}%")
                            ->orWhere('seksi', 'like', "%{$search}%")
                            ->orWhere('deskripsi_pekerjaan', 'like', "%{$search}%");
                    });
                })
                ->latest('id')
                ->paginate(10)
                ->withQueryString();

            return view('admin.lhpp.index', [
                'search' => $search,
                'lhpps' => $lhpps,
                'approvalReassignmentUsers' => User::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'email', 'role', 'nomor_hp']),
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to load admin BAST index page.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat halaman BAST admin.');
        }
    }

    public function resendActiveApproval(int $lhppId)
    {
        $lhpp = LhppBast::query()->findOrFail($lhppId);
        $signature = $lhpp->signatures()
            ->where('status', LhppBastSignature::STATUS_PENDING)
            ->orderBy('step_order')
            ->first();

        abort_unless(
            $signature && ! $signature->tokenExpired() && $signature->approvalUrl(),
            Response::HTTP_CONFLICT,
            'Tidak ada link approval BAST/LHPP aktif yang dapat dikirim ulang.'
        );

        if (! $this->approvalNotificationService->sendBast($signature, true)) {
            abort(Response::HTTP_BAD_GATEWAY, 'Email approval BAST/LHPP gagal dikirim.');
        }

        return back()->with('status', sprintf(
            'Link approval BAST/LHPP berhasil dikirim ulang ke %s.',
            $signature->signer?->email ?: 'email approver',
        ));
    }

    public function rollbackSignature(Request $request, LhppBast $lhppBast, LhppBastSignature $signature): RedirectResponse
    {
        $validated = $request->validate([
            'rollback_reason' => ['required', 'string', 'min:5', 'max:2000'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $rolledBackSignature = $this->rollbackService->rollbackBast(
            $lhppBast,
            $signature,
            $request->user(),
            $validated['rollback_reason'],
            $request->boolean('send_email'),
        );

        return back()->with('status', sprintf(
            'Rollback TTD BAST/LHPP step %s berhasil. Step aktif kembali ke %s.',
            $rolledBackSignature->step_order,
            $rolledBackSignature->displayRoleLabel(),
        ));
    }

    public function pdf(Request $request, int $lhppId)
    {
        try {
            $lhpp = LhppBast::query()->findOrFail($lhppId);

            $lhpp->loadMissing([
                'images',
                'signatures',
                'parentLhppBast.images',
                'parentLhppBast.signatures',
                'parentLhppBast.purchaseOrder:id,order_id,purchase_order_number',
                'parentLhppBast.order.purchaseOrder:id,order_id,purchase_order_number',
                'hpp.order',
                'hpp.outlineAgreement.unitWork.department',
                'hpp.creator',
                'purchaseOrder:id,order_id,purchase_order_number',
                'order.purchaseOrder:id,order_id,purchase_order_number',
                'order.latestApprovedHpp.order',
                'order.latestApprovedHpp.outlineAgreement.unitWork.department',
                'order.latestApprovedHpp.creator',
            ]);

            $finalDocumentSignature = $lhpp->finalSignedDocumentSignature();

            if ($finalDocumentSignature?->hasUploadedSignedDocument()) {
                abort_unless(
                    Storage::disk('public')->exists($finalDocumentSignature->signed_document_path),
                    Response::HTTP_NOT_FOUND
                );

                return response()->file(
                    Storage::disk('public')->path($finalDocumentSignature->signed_document_path),
                    [
                        'Content-Type' => $finalDocumentSignature->signed_document_mime_type
                            ?: (Storage::disk('public')->mimeType($finalDocumentSignature->signed_document_path) ?: 'application/octet-stream'),
                        'Content-Disposition' => 'inline; filename="'.$this->safeFilename(
                            $finalDocumentSignature->signed_document_original_name
                                ?: basename($finalDocumentSignature->signed_document_path)
                        ).'"',
                    ],
                );
            }

            $bastPdf = Pdf::loadView('pkm.lhpp.pdf', [
                'lhpp' => $lhpp,
                'materialItems' => collect($lhpp->material_items ?? []),
                'serviceItems' => collect($lhpp->service_items ?? []),
            ])->setPaper('a4', 'portrait')->output();

            $terminSlug = $lhpp->termin_type === 'termin_2' ? 'termin-2' : 'termin-1';

            $attachedHpp = $lhpp->hpp ?: $lhpp->order?->latestApprovedHpp;
            $terminOnePdf = null;

            if ($lhpp->termin_type === 'termin_2' && $lhpp->parentLhppBast) {
                $terminOnePdf = Pdf::loadView('pkm.lhpp.pdf', [
                    'lhpp' => $lhpp->parentLhppBast,
                    'materialItems' => collect($lhpp->parentLhppBast->material_items ?? []),
                    'serviceItems' => collect($lhpp->parentLhppBast->service_items ?? []),
                ])->setPaper('a4', 'portrait')->output();
            }

            if (! $attachedHpp) {
                $pdfOutput = $terminOnePdf
                    ? $this->mergePdfOutputs([$bastPdf, $terminOnePdf])
                    : $bastPdf;

                return response($pdfOutput, Response::HTTP_OK, $this->pdfInlineHeaders(
                    'bast-'.$terminSlug.'-'.$lhpp->nomor_order.'.pdf'
                ));
            }

            $hppPdf = Pdf::loadView('admin.hpp.hpppdf', [
                'hpp' => $attachedHpp,
            ])->setPaper('a4', 'landscape')->output();

            $mergedPdf = $this->mergePdfOutputs(array_filter([$bastPdf, $terminOnePdf, $hppPdf]));

            Log::info('Admin BAST PDF merged successfully.', [
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhpp->id,
                'nomor_order' => $lhpp->nomor_order,
                'termin_type' => $lhpp->termin_type,
                'attached_hpp_id' => $attachedHpp->id,
                'bast_pdf_bytes' => strlen($bastPdf),
                'hpp_pdf_bytes' => strlen($hppPdf),
                'merged_pdf_bytes' => strlen($mergedPdf),
            ]);

            return response($mergedPdf, Response::HTTP_OK, $this->pdfInlineHeaders(
                'bast-'.$terminSlug.'-'.$lhpp->nomor_order.'.pdf'
            ));
        } catch (Throwable $exception) {
            Log::error('Failed to generate admin BAST PDF.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhppId,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat membuat PDF BAST admin.');
        }
    }

    public function pdfByOrder(Request $request, string $nomorOrder, string $termin)
    {
        $terminType = $termin === 'termin-2' ? 'termin_2' : 'termin_1';

        try {
            $lhpp = LhppBast::query()
                ->where('nomor_order', $nomorOrder)
                ->where('termin_type', $terminType)
                ->firstOrFail();

            return $this->pdf($request, $lhpp->id);
        } catch (Throwable $exception) {
            Log::error('Failed to generate admin BAST PDF by order.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'nomor_order' => $nomorOrder,
                'termin_type' => $terminType,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat membuat PDF BAST admin.');
        }
    }

    public function diropsSignedDocument(int $lhppId): Response
    {
        $lhpp = LhppBast::query()->findOrFail($lhppId);
        $signature = $this->resolveDiropsSignature($lhpp);

        abort_unless($signature?->hasUploadedSignedDocument(), Response::HTTP_NOT_FOUND);
        abort_unless(Storage::disk('public')->exists($signature->signed_document_path), Response::HTTP_NOT_FOUND);

        return response()->file(
            Storage::disk('public')->path($signature->signed_document_path),
            [
                'Content-Type' => $signature->signed_document_mime_type ?: (Storage::disk('public')->mimeType($signature->signed_document_path) ?: 'application/octet-stream'),
                'Content-Disposition' => 'inline; filename="'.$this->safeFilename(
                    $signature->signed_document_original_name ?: basename($signature->signed_document_path)
                ).'"',
            ],
        );
    }

    /**
     * @param  array<int, string>  $pdfOutputs
     */
    private function mergePdfOutputs(array $pdfOutputs): string
    {
        return app(PdfMergeService::class)->merge($pdfOutputs, context: [
            'controller' => static::class,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function pdfInlineHeaders(string $filename): array
    {
        return [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }

    private function resolveDiropsSignature(LhppBast $lhpp): ?LhppBastSignature
    {
        $lhpp->loadMissing('signatures');

        return $lhpp->signatures->first(function (LhppBastSignature $signature): bool {
            return $signature->role_key === 'dirops';
        });
    }

    private function safeFilename(?string $filename): string
    {
        $filename = trim((string) $filename);

        return $filename !== '' ? str_replace('"', '', $filename) : 'document';
    }
}
