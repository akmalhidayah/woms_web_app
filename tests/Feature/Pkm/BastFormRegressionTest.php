<?php

namespace Tests\Feature\Pkm;

use App\Http\Controllers\Pkm\LhppController;
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
