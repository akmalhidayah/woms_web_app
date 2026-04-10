<?php

namespace App\Livewire\Admin\Hpp;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\FabricationConstructionContract;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Support\HppApprovalFlow;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class CreateHppForm extends Component
{
    public ?Hpp $hpp = null;

    public function mount(?Hpp $hpp = null): void
    {
        $this->hpp = $hpp;
    }

    public function render()
    {
        $itemGroupPresets = $this->resolveItemGroupPresets();

        $orders = Order::query()
            ->when(
                $this->hpp?->exists,
                fn ($query) => $query->where(function ($builder) {
                    $builder
                        ->whereKey($this->hpp?->order_id)
                        ->orWhere(function (Builder $eligibleQuery) {
                            $this->applyEligibleOrderConstraints($eligibleQuery);
                            $eligibleQuery->doesntHave('hpps');
                        });
                }),
                fn ($query) => $this->applyEligibleOrderConstraints($query)->doesntHave('hpps'),
            )
            ->orderByDesc('tanggal_order')
            ->orderByDesc('id')
            ->get(['id', 'nomor_order', 'nama_pekerjaan', 'unit_kerja', 'seksi']);

        $orderOptions = $orders
            ->map(fn (Order $order) => [
                'value' => (string) $order->id,
                'label' => "{$order->nomor_order} - {$order->nama_pekerjaan}",
                'nomor_order' => $order->nomor_order,
                'nama_pekerjaan' => $order->nama_pekerjaan,
                'unit_kerja' => $order->unit_kerja,
                'seksi' => $order->seksi,
            ])
            ->values()
            ->all();

        $outlineAgreementOptions = OutlineAgreement::query()
            ->with(['unitWork:id,name'])
            ->where('status', OutlineAgreement::STATUS_ACTIVE)
            ->orderByDesc('current_period_end')
            ->orderByDesc('id')
            ->get()
            ->map(fn (OutlineAgreement $agreement) => [
                'value' => (string) $agreement->id,
                'label' => "{$agreement->nomor_oa} - {$agreement->nama_kontrak}",
                'nomor_oa' => $agreement->nomor_oa,
                'unit_kerja_pengendali' => $agreement->unitWork?->name ?? '',
                'seksi_pengendali' => trim((string) $agreement->jenis_kontrak) !== ''
                    ? trim((string) $agreement->jenis_kontrak)
                    : 'Tidak ada seksi',
                'periode_outline_agreement' => trim(sprintf(
                    '%s - %s',
                    $agreement->current_period_start?->format('d/m/Y') ?? '-',
                    $agreement->current_period_end?->format('d/m/Y') ?? '-',
                )),
            ])
            ->values()
            ->all();

        return view('livewire.admin.hpp.create-hpp-form', [
            'orderOptions' => $orderOptions,
            'outlineAgreementOptions' => $outlineAgreementOptions,
            'kategoriOptions' => HppApprovalFlow::kategoriOptions(),
            'areaOptions' => HppApprovalFlow::areaOptions(),
            'areaKeysByLabel' => HppApprovalFlow::areaKeysByLabel(),
            'bucketOptions' => HppApprovalFlow::bucketOptions(),
            'flowMatrix' => HppApprovalFlow::flowMatrix(),
            'itemGroupPresets' => $itemGroupPresets,
            'contractCatalog' => $this->resolveContractCatalog($itemGroupPresets),
            'initialState' => [
                'selectedOrder' => (string) old('order_id', $this->hpp?->order_id ?? ($orderOptions[0]['value'] ?? '')),
                'selectedOutlineAgreement' => (string) old('outline_agreement_id', $this->hpp?->outline_agreement_id ?? ($outlineAgreementOptions[0]['value'] ?? '')),
                'kategoriPekerjaan' => old('kategori_pekerjaan', $this->hpp?->kategori_pekerjaan ?? 'Fabrikasi'),
                'areaPekerjaan' => HppApprovalFlow::displayArea((string) old('area_pekerjaan', $this->hpp?->area_pekerjaan ?? 'Dalam')),
                'nilaiBucket' => old('nilai_hpp_bucket', $this->hpp?->nilai_hpp_bucket ?? 'under'),
                'costCentre' => old('cost_centre', $this->hpp?->cost_centre ?? ''),
            ],
            'isEdit' => $this->hpp?->exists ?? false,
            'submitRoute' => $this->hpp?->exists ? route('admin.hpp.update', $this->hpp) : route('admin.hpp.store'),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function oldItemGroupPresets(): array
    {
        $groupLabels = old('jenis_label_visible', []);
        $subJenisItems = old('sub_jenis_item', []);
        $kategoriItems = old('kategori_item', []);
        $namaItems = old('nama_item', []);
        $jumlahItems = old('jumlah_item', []);
        $qtyItems = old('qty', []);
        $satuanItems = old('satuan', []);
        $hargaSatuanItems = old('harga_satuan', []);
        $keteranganItems = old('keterangan', []);

        $presets = [];

        foreach ($groupLabels as $groupIndex => $groupLabel) {
            $items = [];

            foreach (($namaItems[$groupIndex] ?? []) as $itemIndex => $namaItem) {
                $items[] = [
                    'sub_jenis_item' => $subJenisItems[$groupIndex][$itemIndex] ?? '',
                    'kategori_item' => $kategoriItems[$groupIndex][$itemIndex] ?? '',
                    'nama_item' => $namaItem,
                    'jumlah_item' => $jumlahItems[$groupIndex][$itemIndex] ?? '',
                    'qty' => $qtyItems[$groupIndex][$itemIndex] ?? '',
                    'satuan' => $satuanItems[$groupIndex][$itemIndex] ?? '',
                    'harga_satuan' => $hargaSatuanItems[$groupIndex][$itemIndex] ?? '',
                    'keterangan' => $keteranganItems[$groupIndex][$itemIndex] ?? '',
                ];
            }

            $presets[] = [
                'title' => $groupLabel,
                'items' => $items,
            ];
        }

        return $presets;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveItemGroupPresets(): array
    {
        if (session()->getOldInput() !== []) {
            return $this->oldItemGroupPresets();
        }

        if (! $this->hpp || ! is_array($this->hpp->item_groups)) {
            return [];
        }

        return collect($this->hpp->item_groups)
            ->map(function (array $group): array {
                return [
                    'title' => $group['jenis_item'] ?? 'Material/Jasa',
                    'items' => collect($group['items'] ?? [])
                        ->map(fn (array $item): array => [
                            'sub_jenis_item' => $item['sub_jenis_item'] ?? '',
                            'kategori_item' => $item['kategori_item'] ?? '',
                            'nama_item' => $item['nama_item'] ?? '',
                            'jumlah_item' => $item['jumlah_item'] ?? '',
                            'qty' => $item['qty'] ?? '',
                            'satuan' => $item['satuan'] ?? '',
                            'harga_satuan' => $item['harga_satuan'] ?? '',
                            'harga_total' => $item['harga_total'] ?? '',
                            'keterangan' => $item['keterangan'] ?? '',
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $itemGroupPresets
     * @return list<array<string, string|null>>
     */
    private function resolveContractCatalog(array $itemGroupPresets): array
    {
        $catalog = FabricationConstructionContract::query()
            ->orderBy('tahun')
            ->orderBy('jenis_item')
            ->orderBy('sub_jenis_item')
            ->orderBy('kategori_item')
            ->orderBy('nama_item')
            ->get(['jenis_item', 'sub_jenis_item', 'kategori_item', 'nama_item', 'satuan', 'harga_satuan'])
            ->map(fn (FabricationConstructionContract $item): array => [
                'jenis_item' => $item->jenis_item,
                'sub_jenis_item' => $item->sub_jenis_item,
                'kategori_item' => $item->kategori_item,
                'nama_item' => $item->nama_item,
                'satuan' => $item->satuan,
                'harga_satuan' => (string) $item->harga_satuan,
            ]);

        $presetCatalog = collect($itemGroupPresets)
            ->flatMap(function (array $group): array {
                $jenisItem = trim((string) ($group['title'] ?? ''));

                return collect($group['items'] ?? [])
                    ->map(function (array $item) use ($jenisItem): array {
                        return [
                            'jenis_item' => $jenisItem !== '' ? $jenisItem : null,
                            'sub_jenis_item' => filled($item['sub_jenis_item'] ?? null) ? trim((string) $item['sub_jenis_item']) : null,
                            'kategori_item' => filled($item['kategori_item'] ?? null) ? trim((string) $item['kategori_item']) : null,
                            'nama_item' => filled($item['nama_item'] ?? null) ? trim((string) $item['nama_item']) : null,
                            'satuan' => filled($item['satuan'] ?? null) ? trim((string) $item['satuan']) : null,
                            'harga_satuan' => filled($item['harga_satuan'] ?? null) ? (string) $item['harga_satuan'] : '0',
                        ];
                    })
                    ->filter(fn (array $item): bool => filled($item['jenis_item']) && filled($item['nama_item']))
                    ->values()
                    ->all();
            });

        return $catalog
            ->concat($presetCatalog)
            ->unique(fn (array $item): string => implode('||', [
                trim((string) ($item['jenis_item'] ?? '')),
                trim((string) ($item['sub_jenis_item'] ?? '')),
                trim((string) ($item['kategori_item'] ?? '')),
                trim((string) ($item['nama_item'] ?? '')),
                trim((string) ($item['satuan'] ?? '')),
                trim((string) ($item['harga_satuan'] ?? '0')),
            ]))
            ->values()
            ->all();
    }

    private function applyEligibleOrderConstraints(Builder $query): Builder
    {
        return $query
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedJasa->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->whereHas('documents', fn (Builder $documentQuery) => $documentQuery->where('jenis_dokumen', OrderDocumentType::Abnormalitas->value))
            ->whereHas('documents', fn (Builder $documentQuery) => $documentQuery->where('jenis_dokumen', OrderDocumentType::GambarTeknik->value))
            ->whereHas('scopeOfWork');
    }
}
