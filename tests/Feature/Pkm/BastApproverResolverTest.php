<?php

namespace Tests\Feature\Pkm;

use App\Models\LhppBast;
use App\Models\Order;
use App\Models\User;
use App\Models\VendorWorkType;
use App\Models\VendorWorkTypeSection;
use App\Support\BastApprovalSignatureBuilder;
use App\Support\BastApproverResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BastApproverResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_pkm_resolves_from_selected_vendor_section(): void
    {
        [$section, $manager] = $this->sectionAndManager();
        $lhpp = new LhppBast(['tipe_pekerjaan' => $section->name]);
        $resolved = app(BastApproverResolver::class)->resolveApprover($lhpp, 'Manager PKM');

        $this->assertSame($manager->id, $resolved['user']->id);
        $this->assertSame('manager_pkm', $resolved['role_key']);
        $this->assertSame('Manager PKM', $resolved['role_label']);
        $this->assertSame('Pengerjaan Mesin', $resolved['section']);
        $this->assertSame('Manager Pengerjaan Mesin', $resolved['position']);
    }

    public function test_changing_section_manager_does_not_change_existing_signature_snapshot(): void
    {
        [$section, $firstManager] = $this->sectionAndManager();
        $order = Order::query()->create([
            'nomor_order' => 'BAST-MANAGER-SNAPSHOT', 'nama_pekerjaan' => 'Test', 'unit_kerja' => 'Unit', 'seksi' => 'Seksi',
            'deskripsi' => 'Test', 'prioritas' => Order::PRIORITY_MEDIUM, 'tanggal_order' => '2026-07-01', 'target_selesai' => '2026-07-10',
            'created_by' => $firstManager->id,
        ]);
        $lhpp = LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1', 'nomor_order' => 'BAST-MANAGER-SNAPSHOT', 'deskripsi_pekerjaan' => 'Test',
            'unit_kerja' => 'Unit', 'seksi' => 'Seksi', 'tanggal_bast' => '2026-07-13', 'tipe_pekerjaan' => $section->name,
            'approval_threshold' => 'under_250', 'approval_flow' => ['Manager PKM'], 'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'quality_control_status' => 'approved', 'created_by' => $firstManager->id,
        ]);
        app(BastApprovalSignatureBuilder::class)->ensureSignatures($lhpp);

        $secondManager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $section->update(['manager_id' => $secondManager->id]);
        $signature = $lhpp->signatures()->firstOrFail();

        $this->assertSame($firstManager->id, $signature->signer_user_id);
        $this->assertSame($firstManager->name, $signature->signer_name_snapshot);
    }

    private function sectionAndManager(): array
    {
        $manager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $vendor = VendorWorkType::query()->firstOrCreate(['name' => VendorWorkType::FIXED_VENDOR_NAME]);
        $section = VendorWorkTypeSection::query()->create(['vendor_work_type_id' => $vendor->id, 'name' => 'Pengerjaan Mesin', 'manager_id' => $manager->id]);

        return [$section, $manager];
    }
}
