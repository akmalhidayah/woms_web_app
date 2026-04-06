<?php

namespace App\Livewire\Admin\Hpp;

use App\Models\Hpp;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Support\HppApprovalFlow;
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
        $orders = Order::query()
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
            'bucketOptions' => HppApprovalFlow::bucketOptions(),
            'flowMatrix' => HppApprovalFlow::flowMatrix(),
            'itemGroupPresets' => $this->resolveItemGroupPresets(),
            'initialState' => [
                'selectedOrder' => (string) old('order_id', $this->hpp?->order_id ?? ($orderOptions[0]['value'] ?? '')),
                'selectedOutlineAgreement' => (string) old('outline_agreement_id', $this->hpp?->outline_agreement_id ?? ($outlineAgreementOptions[0]['value'] ?? '')),
                'kategoriPekerjaan' => old('kategori_pekerjaan', $this->hpp?->kategori_pekerjaan ?? 'Fabrikasi'),
                'areaPekerjaan' => old('area_pekerjaan', $this->hpp?->area_pekerjaan ?? 'Dalam'),
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
}
