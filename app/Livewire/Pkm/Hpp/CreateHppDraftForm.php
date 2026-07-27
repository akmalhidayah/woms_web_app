<?php

namespace App\Livewire\Pkm\Hpp;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Hpp;
use App\Models\FabricationConstructionContract;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Support\HppApprovalFlow;
use Livewire\Component;

class CreateHppDraftForm extends Component
{
    public ?Hpp $hpp = null;

    public function mount(?Hpp $hpp = null): void
    {
        $this->hpp = $hpp;
    }

    public function render()
    {
        $orders = Order::query()
            ->where(function ($query): void {
                if ($this->hpp?->exists) {
                    $query->whereKey($this->hpp->order_id);

                    return;
                }

                $query
                    ->whereIn('catatan_status', [
                        OrderUserNoteStatus::ApprovedJasa->value,
                        OrderUserNoteStatus::ApprovedWorkshopJasa->value,
                    ])
                    ->whereHas('scopeOfWork')
                    ->doesntHave('hpps');
            })
            ->orderByDesc('tanggal_order')
            ->get(['id', 'nomor_order', 'nama_pekerjaan', 'unit_kerja', 'seksi']);
        $agreements = OutlineAgreement::query()
            ->with('unitWork:id,name')
            ->where('status', OutlineAgreement::STATUS_ACTIVE)
            ->orderByDesc('current_period_end')
            ->get();

        $groups = session()->getOldInput() !== []
            ? $this->oldGroups()
            : collect($this->hpp?->item_groups ?? [])
                ->map(fn (array $group): array => [
                    'title' => $group['jenis_item'] ?? 'Material/Jasa',
                    'items' => array_values($group['items'] ?? []),
                ])->values()->all();

        if ($groups === []) {
            $groups = [[
                'title' => 'Material/Jasa',
                'items' => [[
                    'sub_jenis_item' => '',
                    'kategori_item' => '',
                    'nama_item' => '',
                    'jumlah_item' => '',
                    'qty' => '1',
                    'satuan' => '',
                    'harga_satuan' => '0',
                    'keterangan' => '',
                ]],
            ]];
        }

        return view('livewire.pkm.hpp.create-hpp-draft-form', [
            'orders' => $orders,
            'agreements' => $agreements,
            'groups' => $groups,
            'kategoriOptions' => HppApprovalFlow::kategoriOptions(),
            'areaOptions' => HppApprovalFlow::areaOptions(),
            'flowMatrix' => HppApprovalFlow::flowMatrix(),
            'contractCatalog' => FabricationConstructionContract::query()
                ->orderBy('jenis_item')
                ->orderBy('sub_jenis_item')
                ->orderBy('kategori_item')
                ->orderBy('nama_item')
                ->get(['jenis_item', 'sub_jenis_item', 'kategori_item', 'nama_item', 'satuan', 'harga_satuan'])
                ->map(fn (FabricationConstructionContract $item): array => [
                    'jenis_item' => (string) $item->jenis_item,
                    'sub_jenis_item' => (string) $item->sub_jenis_item,
                    'kategori_item' => (string) $item->kategori_item,
                    'nama_item' => (string) $item->nama_item,
                    'satuan' => (string) $item->satuan,
                    'harga_satuan' => (string) $item->harga_satuan,
                ])->values()->all(),
            'isEdit' => (bool) $this->hpp?->exists,
            'submitRoute' => $this->hpp?->exists
                ? route('pkm.hpp.update', $this->hpp)
                : route('pkm.hpp.store'),
        ]);
    }

    /** @return list<array<string,mixed>> */
    private function oldGroups(): array
    {
        $groups = [];

        foreach ((array) old('jenis_label_visible', []) as $groupIndex => $title) {
            $items = [];

            foreach ((array) old("nama_item.{$groupIndex}", []) as $itemIndex => $name) {
                $items[] = [
                    'sub_jenis_item' => old("sub_jenis_item.{$groupIndex}.{$itemIndex}", ''),
                    'kategori_item' => old("kategori_item.{$groupIndex}.{$itemIndex}", ''),
                    'nama_item' => $name,
                    'jumlah_item' => old("jumlah_item.{$groupIndex}.{$itemIndex}", ''),
                    'qty' => old("qty.{$groupIndex}.{$itemIndex}", ''),
                    'satuan' => old("satuan.{$groupIndex}.{$itemIndex}", ''),
                    'harga_satuan' => old("harga_satuan.{$groupIndex}.{$itemIndex}", ''),
                    'keterangan' => old("keterangan.{$groupIndex}.{$itemIndex}", ''),
                ];
            }

            $groups[] = ['title' => $title, 'items' => $items];
        }

        return $groups;
    }
}
