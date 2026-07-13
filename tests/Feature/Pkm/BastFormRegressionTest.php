<?php

namespace Tests\Feature\Pkm;

use App\Http\Controllers\Pkm\LhppController;
use App\Models\Hpp;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class BastFormRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_keeps_dynamic_sections_and_submit_inside_single_form(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $response = $this->actingAs($pkm)->get(route('pkm.lhpp.create'))->assertOk();
        $html = $response->getContent();

        $document = new DOMDocument;
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);

        $this->assertSame(1, $xpath->query('//*[@id="pkm-lhpp-create-form"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="pkm-lhpp-create-form"]//*[@x-model="hppValueMatchesBast"]')->length);
        $this->assertGreaterThan(0, $xpath->query('//*[@id="pkm-lhpp-create-form"]//*[contains(@x-for, "materialRows")]')->length);
        $this->assertGreaterThan(0, $xpath->query('//*[@id="pkm-lhpp-create-form"]//*[contains(@x-for, "serviceRows")]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="pkm-lhpp-create-form"]//button[@type="submit"]')->length);
        $this->assertStringContainsString('window.pkmLhppCreateForm = function', $html);
        $this->assertStringContainsString('hppValueMatchesBast:', $html);
        $this->assertSame(2, substr_count($html, 'getJenisOptions(row.jenis_item)'));
        $this->assertSame(2, substr_count($html, 'getKategoriOptions(row.jenis_item, row.kategori_item)'));
        $this->assertSame(2, substr_count($html, 'getNameOptions(row.jenis_item, row.kategori_item, row.name)'));
        $this->assertSame(6, substr_count($html, 'x-effect="$nextTick'));
    }

    public function test_hpp_preview_supports_legacy_item_snapshot_keys(): void
    {
        $hpp = new Hpp;
        $hpp->item_groups = [[
            'jenis' => 'Material Utama',
            'items' => [[
                'name' => 'Plat Lama',
                'volume' => '2',
                'unit' => 'Lembar',
                'unit_price' => '150000',
                'amount' => '300000',
            ]],
        ]];

        $method = new ReflectionMethod(LhppController::class, 'buildRowsFromHpp');
        $rows = $method->invoke(app(LhppController::class), $hpp, 'material');

        $this->assertSame('Material Utama', $rows[0]['jenis_item']);
        $this->assertSame('Plat Lama', $rows[0]['name']);
        $this->assertSame('2', $rows[0]['volume']);
        $this->assertSame('Lembar', $rows[0]['unit']);
        $this->assertSame('150.000', $rows[0]['unit_price']);
    }

    public function test_hpp_preview_reads_item_level_type_when_group_label_is_missing(): void
    {
        $hpp = new Hpp;
        $hpp->item_groups = [[
            'items' => [[
                'jenis_item' => 'Biaya Jasa',
                'kategori' => 'Mekanik',
                'item_name' => 'Jasa Bubut',
                'qty' => '8',
                'satuan' => 'Jam',
                'harga_satuan' => '100000',
                'harga_total' => '800000',
            ]],
        ]];

        $method = new ReflectionMethod(LhppController::class, 'buildRowsFromHpp');
        $rows = $method->invoke(app(LhppController::class), $hpp, 'service');

        $this->assertSame('Biaya Jasa', $rows[0]['jenis_item']);
        $this->assertSame('Mekanik', $rows[0]['kategori_item']);
        $this->assertSame('Jasa Bubut', $rows[0]['name']);
    }

    public function test_threshold_uses_total_actual_for_termin_one_without_warranty(): void
    {
        $totals = [
            'total_aktual_biaya' => '260000000.00',
            'termin_1_nilai' => '247000000.00',
            'termin_2_nilai' => '13000000.00',
        ];

        $this->assertSame('under_250', $this->resolveThreshold('termin_1', $totals, true));
        $this->assertSame('under_250', $this->resolveThreshold('termin_1', $totals, false));
        $this->assertSame('under_250', $this->resolveThreshold('termin_2', $totals, false));
    }

    private function resolveThreshold(string $terminType, array $totals, bool $withoutWarranty): string
    {
        $method = new ReflectionMethod(LhppController::class, 'resolveThresholdFromTotals');

        return $method->invoke(app(LhppController::class), $terminType, $totals, $withoutWarranty);
    }
}
