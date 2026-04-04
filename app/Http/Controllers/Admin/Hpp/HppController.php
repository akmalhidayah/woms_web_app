<?php

namespace App\Http\Controllers\Admin\Hpp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hpp\StoreHppRequest;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Support\HppApprovalFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HppController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));

        $rows = Hpp::query()
            ->with(['outlineAgreement:id,nomor_oa', 'unitWork:id,name'])
            ->search($search)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('admin.hpp.index', [
            'rows' => $rows,
            'search' => $search,
            'status' => $status,
            'statusOptions' => Hpp::statusOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.hpp.create');
    }

    public function store(StoreHppRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $order = Order::query()->findOrFail($validated['order_id']);
        $outlineAgreement = OutlineAgreement::query()
            ->with('unitWork:id,name')
            ->findOrFail($validated['outline_agreement_id']);
        $itemGroups = $this->buildItemGroups($request->all());
        $totalKeseluruhan = collect($itemGroups)->sum('subtotal');

        if ($validated['action'] === 'submit' && $totalKeseluruhan <= 0) {
            return back()
                ->withErrors([
                    'items' => 'Minimal satu item dengan nilai total lebih dari 0 wajib diisi saat submit HPP.',
                ])
                ->withInput();
        }

        $approvalCase = HppApprovalFlow::resolvePreviewCase(
            $validated['kategori_pekerjaan'],
            $validated['area_pekerjaan'],
            $validated['nilai_hpp_bucket'],
        );

        $approvalFlow = HppApprovalFlow::resolveApprovalFlow(
            $validated['kategori_pekerjaan'],
            $validated['area_pekerjaan'],
            $validated['nilai_hpp_bucket'],
        );

        $hpp = Hpp::create([
            'order_id' => $order->id,
            'outline_agreement_id' => $outlineAgreement->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'unit_work_id' => $outlineAgreement->unit_work_id,
            'cost_centre' => $validated['cost_centre'] ?: null,
            'kategori_pekerjaan' => $validated['kategori_pekerjaan'],
            'area_pekerjaan' => $validated['area_pekerjaan'],
            'nilai_hpp_bucket' => $validated['nilai_hpp_bucket'],
            'unit_kerja_pengendali' => $outlineAgreement->unitWork?->name,
            'outline_agreement' => $outlineAgreement->nomor_oa,
            'periode_outline_agreement' => $this->formatOutlineAgreementPeriod($outlineAgreement),
            'approval_case' => $approvalCase,
            'approval_flow' => $approvalFlow,
            'item_groups' => $itemGroups,
            'total_keseluruhan' => $totalKeseluruhan,
            'status' => $validated['action'] === 'submit' ? Hpp::STATUS_IN_REVIEW : Hpp::STATUS_DRAFT,
            'submitted_at' => $validated['action'] === 'submit' ? now() : null,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('admin.hpp.index')
            ->with('status', sprintf(
                'HPP untuk order %s berhasil disimpan sebagai %s.',
                $hpp->nomor_order,
                $hpp->status === Hpp::STATUS_DRAFT ? 'draft' : 'pengajuan',
            ));
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<array<string, mixed>>
     */
    private function buildItemGroups(array $payload): array
    {
        $groupLabels = $payload['jenis_label_visible'] ?? [];
        $namaItems = $payload['nama_item'] ?? [];
        $jumlahItems = $payload['jumlah_item'] ?? [];
        $qtyItems = $payload['qty'] ?? [];
        $satuanItems = $payload['satuan'] ?? [];
        $hargaSatuanItems = $payload['harga_satuan'] ?? [];
        $keteranganItems = $payload['keterangan'] ?? [];

        $result = [];

        foreach ($groupLabels as $groupIndex => $groupLabel) {
            $items = [];
            $subtotal = 0.0;

            foreach (($namaItems[$groupIndex] ?? []) as $itemIndex => $namaItem) {
                $jumlahItem = (string) ($jumlahItems[$groupIndex][$itemIndex] ?? '');
                $qty = (float) ($qtyItems[$groupIndex][$itemIndex] ?? 0);
                $satuan = (string) ($satuanItems[$groupIndex][$itemIndex] ?? '');
                $hargaSatuan = (float) ($hargaSatuanItems[$groupIndex][$itemIndex] ?? 0);
                $keterangan = (string) ($keteranganItems[$groupIndex][$itemIndex] ?? '');
                $hargaTotal = round($qty * $hargaSatuan, 2);

                if (
                    trim((string) $namaItem) === ''
                    && trim($jumlahItem) === ''
                    && $qty === 0.0
                    && trim($satuan) === ''
                    && $hargaSatuan === 0.0
                    && trim($keterangan) === ''
                ) {
                    continue;
                }

                $items[] = [
                    'nama_item' => trim((string) $namaItem),
                    'jumlah_item' => trim($jumlahItem),
                    'qty' => $qty,
                    'satuan' => trim($satuan),
                    'harga_satuan' => $hargaSatuan,
                    'harga_total' => $hargaTotal,
                    'keterangan' => trim($keterangan),
                ];

                $subtotal += $hargaTotal;
            }

            if ($items === []) {
                continue;
            }

            $result[] = [
                'jenis_item' => trim((string) $groupLabel) !== '' ? trim((string) $groupLabel) : 'Material/Jasa',
                'subtotal' => round($subtotal, 2),
                'items' => $items,
            ];
        }

        return $result;
    }

    private function formatOutlineAgreementPeriod(OutlineAgreement $outlineAgreement): ?string
    {
        $start = $outlineAgreement->current_period_start?->format('d/m/Y');
        $end = $outlineAgreement->current_period_end?->format('d/m/Y');

        if (! $start && ! $end) {
            return null;
        }

        return trim(sprintf('%s - %s', $start ?: '-', $end ?: '-'));
    }
}
