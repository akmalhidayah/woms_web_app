<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFabricationConstructionContractRequest;
use App\Models\FabricationConstructionContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FabricationConstructionContractController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $tahun = trim((string) $request->string('tahun'));

        $items = FabricationConstructionContract::query()
            ->with(['creator:id,name', 'updater:id,name'])
            ->search($search)
            ->when($tahun !== '', fn ($query) => $query->where('tahun', $tahun))
            ->orderByDesc('tahun')
            ->orderBy('jenis_item')
            ->orderBy('sub_jenis_item')
            ->orderBy('kategori_item')
            ->orderBy('nama_item')
            ->paginate(100)
            ->withQueryString();

        $groupedItems = $items->getCollection()
            ->groupBy([
                fn (FabricationConstructionContract $item) => $item->tahun,
                fn (FabricationConstructionContract $item) => $item->jenis_item,
                fn (FabricationConstructionContract $item) => $item->sub_jenis_item,
                fn (FabricationConstructionContract $item) => $item->kategori_item,
            ]);

        return view('admin.fabrication-construction-contracts.index', [
            'items' => $items,
            'groupedItems' => $groupedItems,
            'search' => $search,
            'tahun' => $tahun,
            'availableYears' => FabricationConstructionContract::query()
                ->select('tahun')
                ->distinct()
                ->orderByDesc('tahun')
                ->pluck('tahun'),
            'summary' => [
                'total_items' => FabricationConstructionContract::query()->count(),
                'total_jenis' => FabricationConstructionContract::query()->distinct('jenis_item')->count('jenis_item'),
                'total_sub_jenis' => FabricationConstructionContract::query()->distinct('sub_jenis_item')->count('sub_jenis_item'),
                'total_kategori' => FabricationConstructionContract::query()->distinct('kategori_item')->count('kategori_item'),
                'total_tahun' => FabricationConstructionContract::query()->distinct('tahun')->count('tahun'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.fabrication-construction-contracts.create', [
            'item' => new FabricationConstructionContract([
                'tahun' => (int) now()->format('Y'),
            ]),
            ...$this->formOptions(),
        ]);
    }

    public function edit(FabricationConstructionContract $contract): View
    {
        return view('admin.fabrication-construction-contracts.edit', [
            'item' => $contract,
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreFabricationConstructionContractRequest $request): RedirectResponse
    {
        $item = new FabricationConstructionContract();
        $this->fillItem($item, $request->validated());
        $item->created_by = $request->user()?->id;
        $item->updated_by = $request->user()?->id;
        $item->save();

        return redirect()
            ->route('admin.fabrication-construction-contracts.index')
            ->with('status', 'Master item kontrak berhasil disimpan.');
    }

    public function update(StoreFabricationConstructionContractRequest $request, FabricationConstructionContract $contract): RedirectResponse
    {
        $this->fillItem($contract, $request->validated());
        $contract->updated_by = $request->user()?->id;
        $contract->save();

        return redirect()
            ->route('admin.fabrication-construction-contracts.index')
            ->with('status', 'Master item kontrak berhasil diperbarui.');
    }

    public function destroy(FabricationConstructionContract $contract): RedirectResponse
    {
        $contract->delete();

        return redirect()
            ->route('admin.fabrication-construction-contracts.index')
            ->with('status', 'Master item kontrak berhasil dihapus.');
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function fillItem(FabricationConstructionContract $item, array $validated): void
    {
        $item->fill([
            'tahun' => $validated['tahun'],
            'jenis_item' => trim((string) $validated['jenis_item']),
            'sub_jenis_item' => filled($validated['sub_jenis_item'] ?? null) ? trim((string) $validated['sub_jenis_item']) : null,
            'kategori_item' => filled($validated['kategori_item'] ?? null) ? trim((string) $validated['kategori_item']) : null,
            'nama_item' => trim((string) $validated['nama_item']),
            'satuan' => trim((string) $validated['satuan']),
            'harga_satuan' => number_format((float) $validated['harga_satuan'], 2, '.', ''),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $hierarchyRows = FabricationConstructionContract::query()
            ->select('jenis_item', 'sub_jenis_item', 'kategori_item')
            ->orderBy('jenis_item')
            ->orderBy('sub_jenis_item')
            ->orderBy('kategori_item')
            ->get();

        return [
            'jenisItemOptions' => FabricationConstructionContract::query()
                ->select('jenis_item')
                ->distinct()
                ->orderBy('jenis_item')
                ->pluck('jenis_item'),
            'subJenisItemOptions' => FabricationConstructionContract::query()
                ->select('sub_jenis_item')
                ->whereNotNull('sub_jenis_item')
                ->distinct()
                ->orderBy('sub_jenis_item')
                ->pluck('sub_jenis_item'),
            'kategoriItemOptions' => FabricationConstructionContract::query()
                ->select('kategori_item')
                ->whereNotNull('kategori_item')
                ->distinct()
                ->orderBy('kategori_item')
                ->pluck('kategori_item'),
            'subJenisMap' => $hierarchyRows
                ->groupBy('jenis_item')
                ->map(fn ($rows) => $rows->pluck('sub_jenis_item')->filter()->unique()->values())
                ->toArray(),
            'kategoriMap' => $hierarchyRows
                ->groupBy(fn ($row) => sprintf('%s||%s', $row->jenis_item ?? '', $row->sub_jenis_item ?? ''))
                ->map(fn ($rows) => $rows->pluck('kategori_item')->filter()->unique()->values())
                ->toArray(),
            'satuanOptions' => FabricationConstructionContract::query()
                ->select('satuan')
                ->distinct()
                ->orderBy('satuan')
                ->pluck('satuan'),
        ];
    }
}
