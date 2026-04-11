<?php

namespace App\Http\Controllers\Admin\Hpp;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Domain\Orders\Enums\OrderUserNoteStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\Hpp;
use App\Http\Requests\Admin\Hpp\StoreHppRequest;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Support\HppApprovalFlow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class HppController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));

        $rows = Hpp::query()
            ->with(['order:id,seksi', 'outlineAgreement:id,nomor_oa', 'unitWork:id,name'])
            ->search($search)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->get();

        $pendingHppOrders = Order::query()
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedJasa->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->whereHas('documents', fn (Builder $documentQuery) => $documentQuery->where('jenis_dokumen', OrderDocumentType::Abnormalitas->value))
            ->whereHas('documents', fn (Builder $documentQuery) => $documentQuery->where('jenis_dokumen', OrderDocumentType::GambarTeknik->value))
            ->whereHas('scopeOfWork')
            ->doesntHave('hpps')
            ->orderByDesc('tanggal_order')
            ->orderByDesc('id')
            ->get(['id', 'nomor_order', 'nama_pekerjaan', 'unit_kerja', 'seksi'])
            ->map(fn (Order $order): array => [
                'nomor_order' => (string) $order->nomor_order,
                'nama_pekerjaan' => (string) ($order->nama_pekerjaan ?? ''),
                'unit_kerja' => (string) ($order->unit_kerja ?? ''),
                'seksi' => (string) ($order->seksi ?? ''),
            ])
            ->values();

        return view('admin.hpp.index', [
            'rows' => $rows,
            'search' => $search,
            'status' => $status,
            'statusOptions' => Hpp::statusOptions(),
            'pendingHppOrders' => $pendingHppOrders,
        ]);
    }

    public function create(): View
    {
        return view('admin.hpp.create');
    }

    public function edit(Hpp $hpp): View
    {
        abort_unless($hpp->isEditable(), 403);

        return view('admin.hpp.edit', [
            'hpp' => $hpp,
        ]);
    }

    public function pdf(Hpp $hpp): Response
    {
        $hpp->loadMissing([
            'order',
            'outlineAgreement',
            'creator',
        ]);

        $pdf = Pdf::loadView('admin.hpp.hpppdf', [
            'hpp' => $hpp,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('hpp-'.$hpp->nomor_order.'.pdf');
    }

    public function store(StoreHppRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $hpp = new Hpp();

        $this->fillHppFromRequest($hpp, $validated, $request->all());
        $hpp->created_by = $request->user()?->id;
        $hpp->save();

        return redirect()
            ->route('admin.hpp.index')
            ->with('status', sprintf(
                'HPP untuk order %s berhasil disimpan sebagai %s.',
                $hpp->nomor_order,
                $hpp->status === Hpp::STATUS_DRAFT ? 'draft' : 'pengajuan',
            ));
    }

    public function update(StoreHppRequest $request, Hpp $hpp): RedirectResponse
    {
        abort_unless($hpp->isEditable(), 403);

        $validated = $request->validated();

        $this->fillHppFromRequest($hpp, $validated, $request->all());
        $hpp->save();

        return redirect()
            ->route('admin.hpp.index')
            ->with('status', sprintf(
                'HPP untuk order %s berhasil diperbarui sebagai %s.',
                $hpp->nomor_order,
                $hpp->status === Hpp::STATUS_DRAFT ? 'draft' : 'pengajuan',
            ));
    }

    public function destroy(Hpp $hpp): RedirectResponse
    {
        abort_unless($hpp->isDeletable(), 403);

        $nomorOrder = $hpp->nomor_order;
        $hpp->delete();

        return redirect()
            ->route('admin.hpp.index')
            ->with('status', sprintf(
                'HPP untuk order %s berhasil dihapus.',
                $nomorOrder,
            ));
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<array<string, mixed>>
     */
    private function buildItemGroups(array $payload): array
    {
        $groupLabels = $payload['jenis_label_visible'] ?? [];
        $subJenisItems = $payload['sub_jenis_item'] ?? [];
        $kategoriItems = $payload['kategori_item'] ?? [];
        $namaItems = $payload['nama_item'] ?? [];
        $jumlahItems = $payload['jumlah_item'] ?? [];
        $qtyItems = $payload['qty'] ?? [];
        $satuanItems = $payload['satuan'] ?? [];
        $hargaSatuanItems = $payload['harga_satuan'] ?? [];
        $hargaTotalItems = $payload['harga_total'] ?? [];
        $keteranganItems = $payload['keterangan'] ?? [];

        $result = [];

        foreach ($groupLabels as $groupIndex => $groupLabel) {
            $items = [];
            $subtotal = '0.00';

            foreach (($namaItems[$groupIndex] ?? []) as $itemIndex => $namaItem) {
                $subJenisItem = trim((string) ($subJenisItems[$groupIndex][$itemIndex] ?? ''));
                $kategoriItem = trim((string) ($kategoriItems[$groupIndex][$itemIndex] ?? ''));
                $jumlahItem = (string) ($jumlahItems[$groupIndex][$itemIndex] ?? '');
                $qty = $this->normalizeNumericString($qtyItems[$groupIndex][$itemIndex] ?? '');
                $satuan = (string) ($satuanItems[$groupIndex][$itemIndex] ?? '');
                $hargaSatuan = $this->normalizeNumericString($hargaSatuanItems[$groupIndex][$itemIndex] ?? '');
                $keterangan = (string) ($keteranganItems[$groupIndex][$itemIndex] ?? '');
                $hargaTotal = $this->normalizeCurrencyDecimal(
                    $hargaTotalItems[$groupIndex][$itemIndex]
                        ?? $this->multiplyCurrencyDecimal($qty, $hargaSatuan),
                );

                if (
                    $subJenisItem === ''
                    && $kategoriItem === ''
                    && trim((string) $namaItem) === ''
                    && trim($jumlahItem) === ''
                    && $this->isZeroNumericString($qty)
                    && trim($satuan) === ''
                    && $this->isZeroNumericString($hargaSatuan)
                    && trim($keterangan) === ''
                ) {
                    continue;
                }

                $items[] = [
                    'sub_jenis_item' => $subJenisItem !== '' ? $subJenisItem : null,
                    'kategori_item' => $kategoriItem !== '' ? $kategoriItem : null,
                    'nama_item' => trim((string) $namaItem),
                    'jumlah_item' => trim($jumlahItem),
                    'qty' => $qty,
                    'satuan' => trim($satuan),
                    'harga_satuan' => $hargaSatuan,
                    'harga_total' => $hargaTotal,
                    'keterangan' => trim($keterangan),
                ];

                $subtotal = $this->addCurrencyDecimals($subtotal, $hargaTotal);
            }

            if ($items === []) {
                continue;
            }

            $result[] = [
                'jenis_item' => trim((string) $groupLabel) !== '' ? trim((string) $groupLabel) : 'Material/Jasa',
                'subtotal' => $subtotal,
                'items' => $items,
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $validated
     * @param array<string, mixed> $payload
     */
    private function fillHppFromRequest(Hpp $hpp, array $validated, array $payload): void
    {
        $order = Order::query()->findOrFail($validated['order_id']);
        $outlineAgreement = OutlineAgreement::query()
            ->with('unitWork:id,name')
            ->findOrFail($validated['outline_agreement_id']);
        $itemGroups = $this->buildItemGroups($payload);
        $totalKeseluruhan = $this->sumItemGroupSubtotals($itemGroups);

        if ($validated['action'] === 'submit' && $this->isZeroOrNegativeCurrency($totalKeseluruhan)) {
            throw ValidationException::withMessages([
                'items' => 'Minimal satu item dengan nilai total lebih dari 0 wajib diisi saat submit HPP.',
            ]);
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

        $hpp->fill([
            'order_id' => $order->id,
            'outline_agreement_id' => $outlineAgreement->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'unit_work_id' => $outlineAgreement->unit_work_id,
            'cost_centre' => $validated['cost_centre'] ?: null,
            'kategori_pekerjaan' => $validated['kategori_pekerjaan'],
            'area_pekerjaan' => HppApprovalFlow::displayArea($validated['area_pekerjaan']),
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
        ]);
    }

    /**
     * @param list<array<string, mixed>> $itemGroups
     */
    private function sumItemGroupSubtotals(array $itemGroups): string
    {
        $total = '0.00';

        foreach ($itemGroups as $group) {
            $total = $this->addCurrencyDecimals(
                $total,
                $this->normalizeCurrencyDecimal($group['subtotal'] ?? '0'),
            );
        }

        return $total;
    }

    private function normalizeNumericString(mixed $value): string
    {
        $normalized = preg_replace('/[^0-9.\-]/', '', trim((string) $value)) ?? '';

        if ($normalized === '' || $normalized === '-' || $normalized === '.') {
            return '0';
        }

        if (str_contains($normalized, '.')) {
            [$integer, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');
            $integer = ltrim($integer, '0');
            $decimal = rtrim($decimal, '0');

            return ($integer === '' ? '0' : $integer).($decimal !== '' ? ".{$decimal}" : '');
        }

        $integer = ltrim($normalized, '0');

        return $integer === '' ? '0' : $integer;
    }

    private function normalizeCurrencyDecimal(mixed $value): string
    {
        $normalized = $this->normalizeNumericString($value);

        if (! str_contains($normalized, '.')) {
            return $normalized.'.00';
        }

        [$integer, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');
        $decimal = substr(str_pad($decimal, 2, '0'), 0, 2);

        return "{$integer}.{$decimal}";
    }

    private function isZeroNumericString(string $value): bool
    {
        return (float) $this->normalizeNumericString($value) === 0.0;
    }

    private function isZeroOrNegativeCurrency(string $value): bool
    {
        return (float) $this->normalizeCurrencyDecimal($value) <= 0.0;
    }

    private function multiplyCurrencyDecimal(string $left, string $right): string
    {
        return number_format(
            (float) $this->normalizeNumericString($left) * (float) $this->normalizeNumericString($right),
            2,
            '.',
            '',
        );
    }

    private function addCurrencyDecimals(string $left, string $right): string
    {
        return number_format(
            (float) $this->normalizeCurrencyDecimal($left) + (float) $this->normalizeCurrencyDecimal($right),
            2,
            '.',
            '',
        );
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
