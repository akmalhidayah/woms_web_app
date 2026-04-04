<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOutlineAgreementAmendmentRequest;
use App\Http\Requests\Admin\StoreOutlineAgreementRequest;
use App\Http\Requests\Admin\UpdateOutlineAgreementRequest;
use App\Models\OutlineAgreement;
use App\Models\UnitWork;
use App\Services\OutlineAgreementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutlineAgreementController extends Controller
{
    public function __construct(
        private readonly OutlineAgreementService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));

        $agreements = OutlineAgreement::query()
            ->with([
                'unitWork.department',
                'latestHistory',
                'histories.creator',
                'yearlyTargets',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('nomor_oa', 'like', "%{$search}%")
                        ->orWhere('nama_kontrak', 'like', "%{$search}%")
                        ->orWhere('jenis_kontrak', 'like', "%{$search}%")
                        ->orWhereHas('unitWork', fn ($unitQuery) => $unitQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(8)
            ->withQueryString();

        $summaryQuery = OutlineAgreement::query();

        return view('admin.outline-agreements.index', [
            'agreements' => $agreements,
            'unitWorks' => UnitWork::query()->with('department')->orderBy('name')->get(),
            'search' => $search,
            'status' => $status,
            'statusOptions' => OutlineAgreement::statusOptions(),
            'jenisKontrakOptions' => OutlineAgreement::jenisKontrakOptions(),
            'amendmentTypeOptions' => OutlineAgreement::amendmentTypeOptions(),
            'summary' => [
                'active_count' => (clone $summaryQuery)->where('status', OutlineAgreement::STATUS_ACTIVE)->count(),
                'expiring_count' => (clone $summaryQuery)
                    ->where('status', OutlineAgreement::STATUS_ACTIVE)
                    ->whereDate('current_period_end', '<=', now()->addDays(60))
                    ->count(),
                'expired_count' => (clone $summaryQuery)->where('status', OutlineAgreement::STATUS_EXPIRED)->count(),
                'active_total' => (float) ((clone $summaryQuery)->sum('current_total_nilai') ?: 0),
            ],
        ]);
    }

    public function store(StoreOutlineAgreementRequest $request): RedirectResponse
    {
        $this->service->createAgreement($request->validated(), $request->user());

        return redirect()
            ->route('admin.outline-agreements.index')
            ->with('success', 'Outline Agreement berhasil dibuat.');
    }

    public function addAmendment(StoreOutlineAgreementAmendmentRequest $request, OutlineAgreement $outlineAgreement): RedirectResponse
    {
        $this->service->addAmendment($outlineAgreement, $request->validated(), $request->user());

        return redirect()
            ->route('admin.outline-agreements.index')
            ->with('success', 'Histori adendum OA berhasil ditambahkan.');
    }

    public function update(UpdateOutlineAgreementRequest $request, OutlineAgreement $outlineAgreement): RedirectResponse
    {
        $this->service->updateAgreement($outlineAgreement, $request->validated(), $request->user());

        return redirect()
            ->route('admin.outline-agreements.index')
            ->with('success', 'Outline Agreement berhasil diperbarui.');
    }
}
