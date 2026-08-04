<?php

namespace Tests\Unit;

use App\Models\Hpp;
use App\Models\LhppBast;
use App\Services\Pkm\BastItemSnapshotService;
use Tests\TestCase;

class BastItemSnapshotServiceTest extends TestCase
{
    public function test_snapshot_preserves_zero_values_order_and_metadata(): void
    {
        $hpp = new Hpp;
        $hpp->forceFill([
            'id' => 10,
            'nomor_order' => 'SNAPSHOT-001',
            'total_keseluruhan' => '0.00',
            'item_groups' => [[
                'jenis_item' => 'MATERIAL',
                'items' => [[
                    'nama_item' => 'Material Nol Pertama',
                    'sub_jenis_item' => 'Sub A',
                    'kategori_item' => 'Kategori A',
                    'jumlah_item' => '1 EA',
                    'qty' => '1',
                    'satuan' => 'EA',
                    'harga_satuan' => '0.00',
                    'harga_total' => '0.00',
                    'keterangan' => 'Tetap tersimpan',
                ], [
                    'nama_item' => 'Material Nol Kedua',
                    'qty' => '2',
                    'satuan' => 'EA',
                    'harga_satuan' => '0.00',
                    'harga_total' => '0.00',
                ]],
            ]],
        ]);

        $result = app(BastItemSnapshotService::class)->fromApprovedHpp($hpp, true);

        $this->assertSame(['Material Nol Pertama', 'Material Nol Kedua'], array_column($result['material_rows'], 'name'));
        $this->assertSame('0.00', $result['material_rows'][0]['unit_price_raw']);
        $this->assertSame('0.00', $result['material_rows'][0]['amount']);
        $this->assertSame('Sub A', $result['material_rows'][0]['sub_jenis_item']);
        $this->assertSame('1 EA', $result['material_rows'][0]['jumlah_item']);
        $this->assertSame('Tetap tersimpan', $result['material_rows'][0]['keterangan']);
        $this->assertSame('0.00', $result['totals']['total_aktual_biaya']);
        $this->assertSame('0.00', $result['totals']['termin_1_nilai']);
        $this->assertSame('0.00', $result['totals']['termin_2_nilai']);
    }

    public function test_termin_two_copies_parent_arrays_and_totals_exactly(): void
    {
        $parent = new LhppBast;
        $parent->forceFill([
            'item_source' => LhppBast::ITEM_SOURCE_HPP_SNAPSHOT,
            'material_items' => [['name' => 'Legacy', 'unit_price' => '110.435', 'amount' => '0.00']],
            'service_items' => [],
            'subtotal_material' => '0.00',
            'subtotal_jasa' => '0.00',
            'total_aktual_biaya' => '0.00',
            'termin_1_nilai' => '0.00',
            'termin_2_nilai' => '0.00',
        ]);

        $result = app(BastItemSnapshotService::class)->fromParentBast($parent);

        $this->assertSame($parent->material_items, $result['material_rows']);
        $this->assertSame($parent->service_items, $result['service_rows']);
        $this->assertSame((string) $parent->total_aktual_biaya, $result['totals']['total_aktual_biaya']);
        $this->assertSame((string) $parent->termin_1_nilai, $result['totals']['termin_1_nilai']);
        $this->assertSame((string) $parent->termin_2_nilai, $result['totals']['termin_2_nilai']);
    }

    public function test_warranty_split_uses_termin_two_as_exact_remainder(): void
    {
        $hpp = new Hpp;
        $hpp->forceFill([
            'id' => 11,
            'nomor_order' => 'SNAPSHOT-ROUNDING',
            'total_keseluruhan' => '100.01',
            'item_groups' => [],
        ]);

        $result = app(BastItemSnapshotService::class)->fromApprovedHpp($hpp, false);

        $this->assertSame('100.01', $result['totals']['total_aktual_biaya']);
        $this->assertSame('95.01', $result['totals']['termin_1_nilai']);
        $this->assertSame('5.00', $result['totals']['termin_2_nilai']);
    }
}
