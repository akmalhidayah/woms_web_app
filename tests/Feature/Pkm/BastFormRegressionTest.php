<?php

namespace Tests\Feature\Pkm;

use App\Http\Controllers\Pkm\LhppController;
use App\Models\FabricationConstructionContract;
use App\Models\Garansi;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\BastIndexTabs;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use setasign\Fpdi\Fpdi;
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
        $this->assertSame(1, $xpath->query('//*[@id="pkm-lhpp-create-form"]//input[@name="item_source"]')->length);
        $this->assertSame(2, $xpath->query('//*[@id="pkm-lhpp-create-form"]//input[@type="radio" and @x-model="itemSource"]')->length);
        $this->assertGreaterThan(0, $xpath->query('//*[@id="pkm-lhpp-create-form"]//*[contains(@x-for, "materialRows")]')->length);
        $this->assertGreaterThan(0, $xpath->query('//*[@id="pkm-lhpp-create-form"]//*[contains(@x-for, "serviceRows")]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="pkm-lhpp-create-form"]//button[@type="submit"]')->length);
        $this->assertStringContainsString('window.pkmLhppCreateForm = function', $html);
        $this->assertStringContainsString("itemSource: config.itemSource === 'manual'", $html);
        $this->assertSame(2, substr_count($html, 'getJenisOptions(row.jenis_item)'));
        $this->assertSame(2, substr_count($html, 'getKategoriOptions(row.jenis_item, row.kategori_item)'));
        $this->assertSame(2, substr_count($html, 'getNameOptions(row.jenis_item, row.kategori_item, row.name)'));
        $this->assertSame(6, substr_count($html, 'x-effect="$nextTick'));
        $this->assertStringContainsString("if (this.itemSource === 'hpp_snapshot')", $html);
        $this->assertStringContainsString('this.applyHppCalculation(order)', $html);
        $this->assertStringContainsString(':key="row._key"', $html);
        $this->assertStringContainsString('removeMaterialRow(index)', $html);
        $this->assertStringContainsString('removeServiceRow(index)', $html);
        $this->assertSame(1, $xpath->query('//*[@id="pkm-lhpp-create-form"]//input[@name="attachment_pdf" and @type="file"]')->length);
        $this->assertStringContainsString('Lampiran PDF BAST', $html);
        $this->assertStringContainsString('maksimal 10 MB', $html);
    }

    public function test_bast_can_store_a_readable_pdf_attachment(): void
    {
        Storage::fake('public');

        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeBastEligibleOrder($admin, 'BAST-PDF-ATTACHMENT', Hpp::STATUS_APPROVED);
        $contractItem = $this->makeContractItem('1000000.00');
        $attachment = $this->pdfUpload('lampiran-bast.pdf', 2);

        try {
            $this->actingAs($pkm)
                ->post(route('pkm.lhpp.store'), $this->bastPayload($order, $contractItem, [
                    'attachment_pdf' => $attachment,
                ]))
                ->assertRedirect(route('pkm.lhpp.index'));
        } finally {
            @unlink($attachment->getPathname());
        }

        $lhpp = LhppBast::query()
            ->where('order_id', $order->id)
            ->where('termin_type', 'termin_1')
            ->firstOrFail();

        $this->assertSame('lampiran-bast.pdf', $lhpp->attachment_pdf_original_name);
        $this->assertSame('application/pdf', $lhpp->attachment_pdf_mime_type);
        $this->assertNotNull($lhpp->attachment_pdf_size);
        Storage::disk('public')->assertExists($lhpp->attachment_pdf_path);
    }

    public function test_pkm_bast_pdf_appends_the_stored_pdf_attachment(): void
    {
        Storage::fake('public');

        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeOrder($admin, 'BAST-PDF-BUNDLE');
        $lhpp = LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => '2026-07-04',
            'approval_threshold' => 'under_250',
            'created_by' => $admin->id,
        ]);

        $baseline = $this->actingAs($pkm)
            ->get(route('pkm.lhpp.pdf', ['nomorOrder' => $order->nomor_order, 'termin' => 'termin-1']))
            ->assertOk()
            ->getContent();

        $attachmentPdf = $this->pdfOutput('Lampiran BAST', 2);
        $attachmentPath = 'lhpp-basts/'.$order->nomor_order.'/termin_1/attachments/lampiran.pdf';
        Storage::disk('public')->put($attachmentPath, $attachmentPdf);
        $lhpp->update([
            'attachment_pdf_path' => $attachmentPath,
            'attachment_pdf_original_name' => 'lampiran.pdf',
            'attachment_pdf_mime_type' => 'application/pdf',
            'attachment_pdf_size' => strlen($attachmentPdf),
        ]);

        $withAttachment = $this->actingAs($pkm)
            ->get(route('pkm.lhpp.pdf', ['nomorOrder' => $order->nomor_order, 'termin' => 'termin-1']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->getContent();

        $this->assertSame(
            $this->pdfPageCount($baseline) + 2,
            $this->pdfPageCount($withAttachment)
        );
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
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeBastEligibleOrder($admin, 'BAST-CALC-INCOMPLETE', Hpp::STATUS_APPROVED);

        $response = $this->actingAs($pkm)->postJson(route('pkm.lhpp.calculate'), [
            'nomor_order' => $order->nomor_order,
            'item_source' => LhppBast::ITEM_SOURCE_MANUAL,
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
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeBastEligibleOrder($admin, 'BAST-CALC-NO-WARRANTY', Hpp::STATUS_APPROVED);
        $contractItem = $this->makeContractItem('100000000.00');

        $this->actingAs($pkm)
            ->postJson(route('pkm.lhpp.calculate'), [
                'nomor_order' => $order->nomor_order,
                'item_source' => LhppBast::ITEM_SOURCE_MANUAL,
                'service_rows' => [[
                    'contract_item_id' => $contractItem->id,
                    'volume' => '1',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('totals.total_aktual_biaya', '100000000.00')
            ->assertJsonPath('totals.termin_1_nilai', '100000000.00')
            ->assertJsonPath('totals.termin_2_nilai', '0.00');
    }

    public function test_calculation_with_warranty_keeps_ninety_five_and_five_percent_split(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeBastEligibleOrder($admin, 'BAST-CALC-WARRANTY', Hpp::STATUS_APPROVED);
        Garansi::query()->where('order_id', $order->id)->update(['garansi_months' => 3]);
        $contractItem = $this->makeContractItem('100000000.00');

        $this->actingAs($pkm)
            ->postJson(route('pkm.lhpp.calculate'), [
                'nomor_order' => $order->nomor_order,
                'item_source' => LhppBast::ITEM_SOURCE_MANUAL,
                'service_rows' => [[
                    'contract_item_id' => $contractItem->id,
                    'volume' => '1',
                ]],
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

    public function test_frontend_sends_manual_source_and_order_without_trusting_warranty_flag(): void
    {
        $source = file_get_contents(resource_path('views/pkm/lhpp/create.blade.php'));

        $this->assertStringContainsString('nomor_order: this.selectedOrder', $source);
        $this->assertStringContainsString("item_source: 'manual'", $source);
        $this->assertStringNotContainsString('is_without_warranty: this.isWithoutWarranty', $source);
        $this->assertStringContainsString('const terminOne = this.isWithoutWarranty', $source);
        $this->assertStringContainsString('const terminTwo = this.isWithoutWarranty', $source);
        $this->assertStringNotContainsString('hppTotal > 0', $source);
        $this->assertStringContainsString('Ganti sumber item BAST?', $source);
        $this->assertStringContainsString('Seluruh item manual akan diganti dengan item dari HPP approved.', $source);
        $this->assertStringContainsString('Item dari HPP approved akan dilepas dan baris input manual akan dikosongkan.', $source);
    }

    public function test_hpp_snapshot_keeps_zero_value_item_without_catalog_lookup(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeBastEligibleOrder($admin, 'BAST-SNAPSHOT-ZERO', Hpp::STATUS_APPROVED);
        $hpp = $order->latestApprovedHpp()->firstOrFail();
        $hpp->forceFill([
            'item_groups' => [[
                'jenis_item' => 'MATERIAL KHUSUS',
                'items' => [[
                    'sub_jenis_item' => 'Legacy',
                    'kategori_item' => 'Tidak Ada di Katalog',
                    'nama_item' => 'Item HPP Bernilai Nol',
                    'jumlah_item' => '1 EA',
                    'qty' => '1',
                    'satuan' => 'EA',
                    'harga_satuan' => '0.00',
                    'harga_total' => '0.00',
                    'keterangan' => 'Snapshot harus dipertahankan',
                ]],
            ]],
            'total_keseluruhan' => '0.00',
        ])->save();
        $contractItem = $this->makeContractItem('999999.00');

        $this->actingAs($pkm)
            ->post(route('pkm.lhpp.store'), $this->bastPayload($order, $contractItem, [
                'item_source' => LhppBast::ITEM_SOURCE_HPP_SNAPSHOT,
                'material_rows' => 'payload-browser-diabaikan',
                'service_rows' => 'payload-browser-diabaikan',
            ]))
            ->assertRedirect(route('pkm.lhpp.index'));

        $bast = LhppBast::query()->where('order_id', $order->id)->where('termin_type', 'termin_1')->firstOrFail();
        $this->assertSame(LhppBast::ITEM_SOURCE_HPP_SNAPSHOT, $bast->item_source);
        $this->assertSame('0.00', $bast->total_aktual_biaya);
        $this->assertSame('Item HPP Bernilai Nol', $bast->material_items[0]['name']);
        $this->assertSame('0.00', $bast->material_items[0]['unit_price_raw']);
        $this->assertSame('0.00', $bast->material_items[0]['amount']);
        $this->assertSame('Snapshot harus dipertahankan', $bast->material_items[0]['keterangan']);
    }

    public function test_manual_zero_price_item_is_valid_and_uses_catalog_price(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeBastEligibleOrder($admin, 'BAST-MANUAL-ZERO', Hpp::STATUS_APPROVED);
        $contractItem = $this->makeContractItem('0.00');

        $payload = $this->bastPayload($order, $contractItem);
        $payload['service_rows'][0]['unit_price'] = '999999999';

        $this->actingAs($pkm)
            ->post(route('pkm.lhpp.store'), $payload)
            ->assertRedirect(route('pkm.lhpp.index'));

        $bast = LhppBast::query()->where('order_id', $order->id)->where('termin_type', 'termin_1')->firstOrFail();
        $this->assertSame(LhppBast::ITEM_SOURCE_MANUAL, $bast->item_source);
        $this->assertSame([], $bast->material_items);
        $this->assertSame('0.00', $bast->service_items[0]['unit_price_raw']);
        $this->assertSame('0', $bast->service_items[0]['unit_price']);
        $this->assertSame('0.00', $bast->service_items[0]['amount']);
    }

    public function test_manual_item_source_can_change_to_hpp_snapshot_before_quality_control_approval(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $manualOrder = $this->makeBastEligibleOrder($admin, 'BAST-MANUAL-TO-SNAPSHOT', Hpp::STATUS_APPROVED);
        $manualItem = $this->makeContractItem('500000.00');
        $snapshotHpp = $manualOrder->latestApprovedHpp()->firstOrFail();
        $snapshotHpp->forceFill([
            'item_groups' => [[
                'jenis_item' => 'MATERIAL',
                'items' => [[
                    'nama_item' => 'Snapshot Pengganti Manual',
                    'qty' => '2',
                    'satuan' => 'EA',
                    'harga_satuan' => '0.00',
                    'harga_total' => '0.00',
                ]],
            ]],
            'total_keseluruhan' => '750000.00',
        ])->save();

        $this->actingAs($pkm)->post(route('pkm.lhpp.store'), $this->bastPayload($manualOrder, $manualItem));
        $editableBast = LhppBast::query()
            ->where('order_id', $manualOrder->id)
            ->where('termin_type', 'termin_1')
            ->firstOrFail();

        $this->assertTrue($editableBast->canChangeItemSource());
        $this->actingAs($pkm)
            ->get(route('pkm.lhpp.edit', ['nomorOrder' => $manualOrder->nomor_order, 'termin' => 'termin-1']))
            ->assertOk()
            ->assertSee('itemSourceLocked: false', false);

        $this->actingAs($pkm)
            ->patch(route('pkm.lhpp.update', ['nomorOrder' => $manualOrder->nomor_order, 'termin' => 'termin-1']), $this->bastPayload($manualOrder, $manualItem, [
                'item_source' => LhppBast::ITEM_SOURCE_HPP_SNAPSHOT,
                'material_rows' => 'payload-browser-harus-diabaikan',
                'service_rows' => 'payload-browser-harus-diabaikan',
            ]))
            ->assertRedirect(route('pkm.lhpp.index'));

        $editableBast->refresh();
        $this->assertSame(LhppBast::ITEM_SOURCE_HPP_SNAPSHOT, $editableBast->item_source);
        $this->assertSame('Snapshot Pengganti Manual', $editableBast->material_items[0]['name']);
        $this->assertSame('0.00', $editableBast->material_items[0]['unit_price_raw']);
        $this->assertSame('0.00', $editableBast->material_items[0]['amount']);
        $this->assertSame([], $editableBast->service_items);
        $this->assertSame('750000.00', $editableBast->total_aktual_biaya);
    }

    public function test_hpp_snapshot_item_source_can_change_to_manual_before_approval_starts(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $manualItem = $this->makeContractItem('500000.00');

        $snapshotOrder = $this->makeBastEligibleOrder($admin, 'BAST-SNAPSHOT-TO-MANUAL', Hpp::STATUS_APPROVED);
        $snapshotHpp = $snapshotOrder->latestApprovedHpp()->firstOrFail();
        $snapshotHpp->forceFill([
            'item_groups' => [['jenis_item' => 'MATERIAL', 'items' => [[
                'nama_item' => 'Snapshot Immutable',
                'qty' => '1',
                'satuan' => 'EA',
                'harga_satuan' => '10.00',
                'harga_total' => '10.00',
            ]]]],
            'total_keseluruhan' => '10.00',
        ])->save();

        $snapshotPayload = $this->bastPayload($snapshotOrder, $manualItem, ['item_source' => LhppBast::ITEM_SOURCE_HPP_SNAPSHOT]);
        $this->actingAs($pkm)->post(route('pkm.lhpp.store'), $snapshotPayload);

        $this->actingAs($pkm)
            ->patch(route('pkm.lhpp.update', ['nomorOrder' => $snapshotOrder->nomor_order, 'termin' => 'termin-1']), $this->bastPayload($snapshotOrder, $manualItem, [
                'item_source' => LhppBast::ITEM_SOURCE_MANUAL,
            ]))
            ->assertRedirect(route('pkm.lhpp.index'));

        $snapshotBast = LhppBast::query()->where('order_id', $snapshotOrder->id)->where('termin_type', 'termin_1')->firstOrFail();
        $this->assertSame(LhppBast::ITEM_SOURCE_MANUAL, $snapshotBast->item_source);
        $this->assertSame([], $snapshotBast->material_items);
        $this->assertSame($manualItem->nama_item, $snapshotBast->service_items[0]['name']);
        $this->assertSame('500000.00', $snapshotBast->service_items[0]['unit_price_raw']);
        $this->assertSame('500000.00', $snapshotBast->total_aktual_biaya);
    }

    public function test_item_source_change_is_rejected_after_quality_control_leaves_pending_stage(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeBastEligibleOrder($admin, 'BAST-SOURCE-QC-LOCKED', Hpp::STATUS_APPROVED);
        $manualItem = $this->makeContractItem('250000.00');

        $this->actingAs($pkm)
            ->post(route('pkm.lhpp.store'), $this->bastPayload($order, $manualItem))
            ->assertRedirect(route('pkm.lhpp.index'));

        $bast = LhppBast::query()
            ->where('order_id', $order->id)
            ->where('termin_type', 'termin_1')
            ->firstOrFail();
        $bast->forceFill(['quality_control_status' => 'approved'])->save();

        $this->assertFalse($bast->fresh()->canChangeItemSource());
        $this->actingAs($pkm)
            ->patch(route('pkm.lhpp.update', ['nomorOrder' => $order->nomor_order, 'termin' => 'termin-1']), $this->bastPayload($order, $manualItem, [
                'item_source' => LhppBast::ITEM_SOURCE_HPP_SNAPSHOT,
            ]))
            ->assertForbidden();

        $this->assertSame(LhppBast::ITEM_SOURCE_MANUAL, $bast->fresh()->item_source);
    }

    public function test_item_source_cannot_change_after_approval_signature_becomes_pending(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $approver = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeBastEligibleOrder($admin, 'BAST-LEGACY-LOCKED', Hpp::STATUS_APPROVED);
        $originalItem = $this->makeContractItem('125000.00');

        $this->actingAs($pkm)
            ->post(route('pkm.lhpp.store'), $this->bastPayload($order, $originalItem))
            ->assertRedirect(route('pkm.lhpp.index'));

        $bast = LhppBast::query()
            ->where('order_id', $order->id)
            ->where('termin_type', 'termin_1')
            ->firstOrFail();
        $before = $bast->only([
            'item_source',
            'hpp_id',
            'document_no',
            'quality_control_status',
            'approval_status',
            'material_items',
            'service_items',
            'subtotal_material',
            'subtotal_jasa',
            'total_aktual_biaya',
            'termin_1_nilai',
            'termin_2_nilai',
        ]);
        $tabs = app(BastIndexTabs::class);
        $activeTab = collect(array_keys($tabs->options(BastIndexTabs::CONTEXT_PKM)))
            ->first(fn (string $tab): bool => $tabs->apply(
                LhppBast::query()->whereKey($bast->id),
                $tab,
                BastIndexTabs::CONTEXT_PKM,
            )->exists());

        $this->assertNotNull($activeTab);

        $this->actingAs($pkm)
            ->get(route('pkm.lhpp.index', ['tab' => $activeTab]))
            ->assertOk()
            ->assertSee('data-bast-action="edit"', false)
            ->assertSee('>Edit</span>', false);

        $this->actingAs($pkm)
            ->get(route('pkm.lhpp.edit', ['nomorOrder' => $order->nomor_order, 'termin' => 'termin-1']))
            ->assertOk()
            ->assertSee('Update')
            ->assertDontSee('BAST terkunci karena proses approval telah dimulai. Data hanya dapat dilihat.');

        LhppBastSignature::query()->create([
            'lhpp_bast_id' => $bast->id,
            'step_order' => 1,
            'role_key' => 'manager_pkm',
            'role_label' => 'Manager PKM',
            'signer_user_id' => $approver->id,
            'signer_name_snapshot' => $approver->name,
            'status' => LhppBastSignature::STATUS_PENDING,
        ]);
        $bast->unsetRelation('signatures');
        $this->assertFalse($bast->canChangeItemSource());
        $replacementItem = $this->makeContractItem('999999.00');

        $this->actingAs($pkm)
            ->get(route('pkm.lhpp.index', ['tab' => $activeTab]))
            ->assertOk()
            ->assertSee('data-bast-action="view"', false)
            ->assertSee('>Lihat</span>', false);

        $lockedForm = $this->actingAs($pkm)
            ->get(route('pkm.lhpp.edit', ['nomorOrder' => $order->nomor_order, 'termin' => 'termin-1']))
            ->assertOk()
            ->assertSee('BAST terkunci karena proses approval telah dimulai. Data hanya dapat dilihat.')
            ->assertSee('formLocked: true', false)
            ->assertDontSee('Tambah Baris')
            ->assertDontSee('Hapus baris material')
            ->assertDontSee('Hapus baris jasa');
        $lockedFormDocument = new DOMDocument;
        @$lockedFormDocument->loadHTML($lockedForm->getContent());
        $lockedFormXPath = new DOMXPath($lockedFormDocument);
        $lockedFormElement = $lockedFormXPath->query('//*[@id="pkm-lhpp-create-form"]')->item(0);

        $this->assertNotNull($lockedFormElement);
        $this->assertSame(1, $lockedFormXPath->query('.//fieldset[@disabled]', $lockedFormElement)->length);
        $this->assertSame(0, $lockedFormXPath->query('.//button[@type="submit"]', $lockedFormElement)->length);

        $this->actingAs($pkm)
            ->patch(
                route('pkm.lhpp.update', ['nomorOrder' => $order->nomor_order, 'termin' => 'termin-1']),
                $this->bastPayload($order, $replacementItem, [
                    'item_source' => LhppBast::ITEM_SOURCE_HPP_SNAPSHOT,
                ]),
            )
            ->assertForbidden();

        $bast->refresh();
        $this->assertSame(LhppBast::ITEM_SOURCE_MANUAL, $bast->item_source);
        $this->assertSame($before, $bast->only(array_keys($before)));
        $this->assertDatabaseHas('lhpp_bast_signatures', [
            'lhpp_bast_id' => $bast->id,
            'status' => LhppBastSignature::STATUS_PENDING,
        ]);
    }

    public function test_pdf_templates_keep_zero_values_and_canonical_item_price(): void
    {
        $bastPdf = file_get_contents(resource_path('views/pkm/lhpp/pdf.blade.php'));
        $hppPdf = file_get_contents(resource_path('views/admin/hpp/hpppdf.blade.php'));

        $this->assertStringContainsString('$item[\'unit_price_raw\'] ?? $item[\'unit_price\'] ?? null', $bastPdf);
        $this->assertStringContainsString('if ($value === null || $value === \'\')', $hppPdf);
        $this->assertStringNotContainsString('$amount > 0 ?', $hppPdf);
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
            'item_source' => LhppBast::ITEM_SOURCE_MANUAL,
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

    private function pdfUpload(string $filename, int $pages): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'woms-bast-');
        file_put_contents($path, $this->pdfOutput('Lampiran BAST', $pages));

        return new UploadedFile($path, $filename, 'application/pdf', null, true);
    }

    private function pdfOutput(string $text, int $pages): string
    {
        $pdf = new \FPDF;
        $pdf->SetCompression(false);

        for ($page = 1; $page <= $pages; $page++) {
            $pdf->AddPage();
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $text.' '.$page);
        }

        return $pdf->Output('S');
    }

    private function pdfPageCount(string $contents): int
    {
        $path = tempnam(sys_get_temp_dir(), 'woms-bast-pages-');
        file_put_contents($path, $contents);

        try {
            return (new Fpdi)->setSourceFile($path);
        } finally {
            @unlink($path);
        }
    }
}
