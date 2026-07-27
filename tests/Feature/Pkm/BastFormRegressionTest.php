<?php

namespace Tests\Feature\Pkm;

use App\Http\Controllers\Pkm\LhppController;
use App\Models\FabricationConstructionContract;
use App\Models\Garansi;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

class BastFormRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_hpp_feature_is_not_available(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $routeName = 'admin.hpp.'.'duplicate';
        $formSelector = 'duplicate'.'-hpp-form';
        $featureLabel = 'Duplicate '.'HPP';
        $confirmationLabel = 'Ya, '.'duplicate';

        $this->assertFalse(Route::has($routeName));

        $this->actingAs($admin)
            ->get(route('admin.hpp.index'))
            ->assertOk()
            ->assertDontSee($formSelector)
            ->assertDontSee($featureLabel)
            ->assertDontSee($confirmationLabel);
    }

    public function test_latest_approved_hpp_ignores_newer_draft_hpp(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeOrder($admin, 'BAST-LATEST-APPROVED');
        $approvedHpp = $this->makeHpp($admin, $order, Hpp::STATUS_APPROVED, '100000000.00');
        $this->makeHpp($admin, $order, Hpp::STATUS_DRAFT, '120000000.00');

        $this->assertSame($approvedHpp->id, $order->fresh()->latestApprovedHpp?->id);
    }

    public function test_only_orders_with_approved_hpp_are_available_for_bast(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $orders = collect([
            Hpp::STATUS_DRAFT => $this->makeBastEligibleOrder($admin, 'BAST-HPP-DRAFT', Hpp::STATUS_DRAFT),
            Hpp::STATUS_IN_REVIEW => $this->makeBastEligibleOrder($admin, 'BAST-HPP-REVIEW', Hpp::STATUS_IN_REVIEW),
            Hpp::STATUS_REJECTED => $this->makeBastEligibleOrder($admin, 'BAST-HPP-REJECTED', Hpp::STATUS_REJECTED),
            Hpp::STATUS_APPROVED => $this->makeBastEligibleOrder($admin, 'BAST-HPP-APPROVED', Hpp::STATUS_APPROVED),
        ]);

        $method = new ReflectionMethod(LhppController::class, 'eligibleOrders');
        $eligibleOrderIds = $method->invoke(app(LhppController::class))->pluck('id');

        $this->assertFalse($eligibleOrderIds->contains($orders[Hpp::STATUS_DRAFT]->id));
        $this->assertFalse($eligibleOrderIds->contains($orders[Hpp::STATUS_IN_REVIEW]->id));
        $this->assertFalse($eligibleOrderIds->contains($orders[Hpp::STATUS_REJECTED]->id));
        $this->assertTrue($eligibleOrderIds->contains($orders[Hpp::STATUS_APPROVED]->id));
    }

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
        $this->assertStringContainsString('if (this.hppValueMatchesBast)', $html);
        $this->assertStringContainsString('this.applyHppCalculation(order)', $html);
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

    public function test_calculation_endpoint_preserves_incomplete_manual_dropdown_state(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);

        $response = $this->actingAs($pkm)->postJson(route('pkm.lhpp.calculate'), [
            'material_rows' => [[
                'contract_item_id' => null,
                'jenis_item' => 'MATERIAL',
                'kategori_item' => 'Material Utama',
                'name' => '',
                'volume' => '',
                'unit' => '',
                'unit_price' => '',
            ]],
            'service_rows' => [[
                'contract_item_id' => null,
                'jenis_item' => 'JASA',
                'kategori_item' => 'Jasa Fabrikasi',
                'name' => '',
                'volume' => '',
                'unit' => '',
                'unit_price' => '',
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('material_rows.0.jenis_item', 'MATERIAL')
            ->assertJsonPath('material_rows.0.kategori_item', 'Material Utama')
            ->assertJsonPath('service_rows.0.jenis_item', 'JASA')
            ->assertJsonPath('service_rows.0.kategori_item', 'Jasa Fabrikasi');
    }

    public function test_calculation_without_warranty_uses_full_value_for_termin_one(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $contractItem = $this->makeContractItem('100000000.00');

        $this->actingAs($pkm)
            ->postJson(route('pkm.lhpp.calculate'), [
                'service_rows' => [[
                    'contract_item_id' => $contractItem->id,
                    'volume' => '1',
                ]],
                'is_without_warranty' => true,
            ])
            ->assertOk()
            ->assertJsonPath('totals.total_aktual_biaya', '100000000.00')
            ->assertJsonPath('totals.termin_1_nilai', '100000000.00')
            ->assertJsonPath('totals.termin_2_nilai', '0.00');
    }

    public function test_calculation_with_warranty_keeps_ninety_five_and_five_percent_split(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $contractItem = $this->makeContractItem('100000000.00');

        $this->actingAs($pkm)
            ->postJson(route('pkm.lhpp.calculate'), [
                'service_rows' => [[
                    'contract_item_id' => $contractItem->id,
                    'volume' => '1',
                ]],
                'is_without_warranty' => false,
            ])
            ->assertOk()
            ->assertJsonPath('totals.total_aktual_biaya', '100000000.00')
            ->assertJsonPath('totals.termin_1_nilai', '95000000.00')
            ->assertJsonPath('totals.termin_2_nilai', '5000000.00');
    }

    public function test_bast_termin_one_stores_approved_hpp_and_full_payment_without_warranty(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeBastEligibleOrder($admin, 'BAST-STORE-APPROVED', Hpp::STATUS_APPROVED);
        $approvedHpp = $order->latestApprovedHpp()->firstOrFail();
        $contractItem = $this->makeContractItem('100000000.00');

        $this->actingAs($pkm)
            ->post(route('pkm.lhpp.store'), $this->bastPayload($order, $contractItem))
            ->assertRedirect(route('pkm.lhpp.index'));

        $lhpp = LhppBast::query()
            ->where('order_id', $order->id)
            ->where('termin_type', 'termin_1')
            ->firstOrFail();

        $this->assertSame($approvedHpp->id, $lhpp->hpp_id);
        $this->assertSame('100000000.00', $lhpp->nilai_hpp);
        $this->assertSame('100000000.00', $lhpp->total_aktual_biaya);
        $this->assertSame('100000000.00', $lhpp->termin_1_nilai);
        $this->assertSame('0.00', $lhpp->termin_2_nilai);
    }

    public function test_termin_two_without_warranty_remains_unavailable(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeBastEligibleOrder($admin, 'BAST-NO-WARRANTY-T2', Hpp::STATUS_APPROVED);
        $contractItem = $this->makeContractItem('100000000.00');

        $this->actingAs($pkm)
            ->post(route('pkm.lhpp.store'), $this->bastPayload($order, $contractItem))
            ->assertRedirect(route('pkm.lhpp.index'));

        $terminOne = LhppBast::query()
            ->where('order_id', $order->id)
            ->where('termin_type', 'termin_1')
            ->firstOrFail();
        $terminOne->forceFill(['termin1_status' => 'sudah'])->save();
        Garansi::query()->where('order_id', $order->id)->update(['lhpp_bast_id' => $terminOne->id]);

        $this->actingAs($pkm)
            ->get(route('pkm.lhpp.termin2.create', ['nomorOrder' => $order->nomor_order]))
            ->assertBadRequest();

        $this->actingAs($pkm)
            ->post(route('pkm.lhpp.store'), $this->bastPayload($order, $contractItem, [
                'termin_type' => 'termin_2',
            ]))
            ->assertNotFound();
    }

    public function test_frontend_sends_warranty_flag_and_calculates_full_payment(): void
    {
        $source = file_get_contents(resource_path('views/pkm/lhpp/create.blade.php'));

        $this->assertStringContainsString('is_without_warranty: this.isWithoutWarranty', $source);
        $this->assertStringContainsString('const terminOne = this.isWithoutWarranty', $source);
        $this->assertStringContainsString('const terminTwo = this.isWithoutWarranty', $source);
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

    private function makeOrder(User $admin, string $nomorOrder): Order
    {
        return Order::query()->create([
            'nomor_order' => $nomorOrder,
            'notifikasi' => 'NOTIF-'.$nomorOrder,
            'nama_pekerjaan' => 'Pekerjaan '.$nomorOrder,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Deskripsi test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-07-01',
            'target_selesai' => '2026-07-10',
            'created_by' => $admin->id,
        ]);
    }

    private function makeHpp(User $admin, Order $order, string $status, string $total = '100000000.00'): Hpp
    {
        return Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'total_keseluruhan' => $total,
            'status' => $status,
            'created_by' => $admin->id,
        ]);
    }

    private function makeBastEligibleOrder(User $admin, string $nomorOrder, string $hppStatus): Order
    {
        $order = $this->makeOrder($admin, $nomorOrder);
        $hpp = $this->makeHpp($admin, $order, $hppStatus);

        PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => 'PO-'.$nomorOrder,
            'progress_pekerjaan' => 100,
            'tanggal_mulai_pekerjaan' => '2026-07-02',
            'tanggal_selesai_pekerjaan' => '2026-07-03',
            'created_by' => $admin->id,
        ]);

        $garansiSeed = LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'garansi_seed',
            'hpp_id' => $hpp->id,
            'nomor_order' => $order->nomor_order,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => '2026-07-03',
            'created_by' => $admin->id,
        ]);

        Garansi::query()->create([
            'order_id' => $order->id,
            'lhpp_bast_id' => $garansiSeed->id,
            'garansi_months' => 0,
            'start_date' => '2026-07-03',
            'created_by' => $admin->id,
        ]);

        return $order;
    }

    private function makeContractItem(string $unitPrice): FabricationConstructionContract
    {
        return FabricationConstructionContract::query()->create([
            'tahun' => 2026,
            'jenis_item' => 'JASA FABRIKASI',
            'nama_item' => 'Pekerjaan BAST test '.uniqid(),
            'satuan' => 'Lot',
            'harga_satuan' => $unitPrice,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function bastPayload(Order $order, FabricationConstructionContract $contractItem, array $overrides = []): array
    {
        return array_replace_recursive([
            'termin_type' => 'termin_1',
            'tanggal_bast' => '2026-07-04',
            'nomor_order' => $order->nomor_order,
            'approval_threshold' => 'under_250',
            'tipe_pekerjaan' => 'pekerjaan_fabrikasi',
            'tanggal_mulai_pekerjaan' => '2026-07-02',
            'tanggal_selesai_pekerjaan' => '2026-07-03',
            'material_rows' => [],
            'service_rows' => [[
                'contract_item_id' => $contractItem->id,
                'volume' => '1',
            ]],
        ], $overrides);
    }
}
