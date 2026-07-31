<?php

namespace App\Http\Controllers\Pkm;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pkm\Hpp\SaveHppDraftRequest;
use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\Order;
use App\Services\Approvals\ApprovalNotificationService;
use App\Services\Pkm\HppDraftService;
use App\Support\HppIndexTabs;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class HppDraftController extends Controller
{
    public function __construct(
        private readonly HppDraftService $draftService,
        private readonly ApprovalNotificationService $approvalNotificationService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $activeTab = HppIndexTabs::fromRequest(
            $request->query('tab'),
            $request->query('status'),
        );
        $rowsQuery = Hpp::query()
            ->with([
                'order:id,notifikasi,seksi,unit_kerja',
                'creator:id,name,role',
                'signatures.signer:id,name,nomor_hp',
                'activeSignature.signer:id,name,nomor_hp',
                'budgetVerification',
                'purchaseOrder',
                'lhppBasts',
            ])
            ->search($search);

        $rows = HppIndexTabs::apply($rowsQuery, $activeTab)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $pendingHppOrders = $this->eligibleOrders()
            ->latest('id')
            ->get(['id', 'nomor_order', 'nama_pekerjaan'])
            ->map(fn (Order $order): array => [
                'nomor_order' => (string) $order->nomor_order,
                'nama_pekerjaan' => trim((string) $order->nama_pekerjaan),
            ]);

        return view('pkm.hpp.index', [
            'rows' => $rows,
            'search' => $search,
            'statusOptions' => Hpp::statusOptions(),
            'activeTab' => $activeTab,
            'tabOptions' => HppIndexTabs::options(),
            'tabCounts' => HppIndexTabs::counts(),
            'pendingHppOrders' => $pendingHppOrders,
        ]);
    }

    public function create(): View
    {
        return view('pkm.hpp.create');
    }

    public function store(SaveHppDraftRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $hpp = DB::transaction(function () use ($request, $validated): Hpp {
            $order = Order::query()->whereKey($validated['order_id'])->lockForUpdate()->firstOrFail();

            if ($order->hpps()->exists()) {
                throw ValidationException::withMessages([
                    'order_id' => 'Order ini sudah memiliki HPP. Silakan buka dan edit draft HPP yang sudah tersedia.',
                ]);
            }

            $hpp = new Hpp;
            $this->draftService->fillDraft($hpp, $validated, $validated);
            $hpp->created_by = $request->user()->id;
            $hpp->status = Hpp::STATUS_DRAFT;
            $hpp->submitted_at = null;
            $hpp->save();

            return $hpp;
        });

        return redirect()->route('pkm.hpp.index')
            ->with('status', "Draft HPP untuk order {$hpp->nomor_order} berhasil disimpan.");
    }

    public function edit(Hpp $hpp): View
    {
        abort_unless($hpp->status === Hpp::STATUS_DRAFT, Response::HTTP_FORBIDDEN);

        return view('pkm.hpp.edit', ['hpp' => $hpp]);
    }

    public function update(SaveHppDraftRequest $request, Hpp $hpp): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $hpp): void {
            $lockedHpp = Hpp::query()->whereKey($hpp->id)->lockForUpdate()->firstOrFail();

            if ($lockedHpp->status !== Hpp::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'hpp' => 'HPP sudah diajukan ke proses approval dan tidak dapat diperbarui dari panel PKM.',
                ]);
            }

            if ((string) $validated['hpp_updated_at'] !== (string) $lockedHpp->getRawOriginal('updated_at')) {
                throw ValidationException::withMessages([
                    'hpp_updated_at' => 'Draft HPP ini telah diperbarui oleh pengguna lain. Muat ulang halaman sebelum menyimpan kembali.',
                ]);
            }

            $this->draftService->fillDraft($lockedHpp, $validated, $validated);
            $lockedHpp->status = Hpp::STATUS_DRAFT;
            $lockedHpp->submitted_at = null;
            $lockedHpp->save();
        });

        return redirect()->route('pkm.hpp.index')
            ->with('status', "Draft HPP untuk order {$hpp->nomor_order} berhasil diperbarui.");
    }

    public function pdf(Hpp $hpp): Response
    {
        $hpp->loadMissing([
            'order',
            'outlineAgreement.unitWork.department',
            'creator',
            'signatures.signer:id,name,inisial',
        ]);
        $finalSignature = $hpp->finalSignedDocumentSignature();

        if ($finalSignature?->hasUploadedSignedDocument()) {
            abort_unless(Storage::disk('public')->exists($finalSignature->signed_document_path), Response::HTTP_NOT_FOUND);

            return response()->file(Storage::disk('public')->path($finalSignature->signed_document_path), [
                'Content-Type' => $finalSignature->signed_document_mime_type
                    ?: (Storage::disk('public')->mimeType($finalSignature->signed_document_path) ?: 'application/octet-stream'),
                'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $finalSignature->signed_document_original_name ?: basename($finalSignature->signed_document_path)).'"',
                ...$this->noCacheHeaders(),
            ]);
        }

        $response = Pdf::loadView('admin.hpp.hpppdf', ['hpp' => $hpp])
            ->setPaper('a4', 'landscape')
            ->stream('hpp-'.$hpp->nomor_order.'.pdf');

        foreach ($this->noCacheHeaders() as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    public function resendActiveApproval(Hpp $hpp): RedirectResponse
    {
        $hpp->loadMissing('signatures.signer');
        $signature = $hpp->signatures->first(
            fn (HppSignature $signature): bool => $signature->isPending()
        );

        abort_unless(
            $signature && ! $signature->tokenExpired() && $signature->approvalUrl(),
            Response::HTTP_CONFLICT,
            'Tidak ada link approval HPP aktif yang dapat dikirim ulang.'
        );

        if (! $this->approvalNotificationService->sendHpp($signature, true)) {
            abort(Response::HTTP_BAD_GATEWAY, 'Email approval HPP gagal dikirim.');
        }

        return back()->with('status', sprintf(
            'Link approval HPP berhasil dikirim ulang ke %s.',
            $signature->signer?->email ?: 'email approver',
        ));
    }

    private function eligibleOrders()
    {
        return Order::query()
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedJasa->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->whereHas('scopeOfWork')
            ->doesntHave('hpps');
    }

    /** @return array<string,string> */
    private function noCacheHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }
}
