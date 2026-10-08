<?php

namespace Tests\Feature\Admin;

use App\Models\Garansi;
use App\Models\LhppBast;
use App\Models\LpjPpl;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LpjPplIndexFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabs_classify_the_active_stage_by_document_completeness_and_payment(): void
    {
        $admin = $this->admin();
        $action = $this->makeWithoutWarrantyBast($admin, 'LPJ-ACTION');
        $lpjComplete = $this->makeWithoutWarrantyBast($admin, 'LPJ-COMPLETE');
        $documentsComplete = $this->makeWithoutWarrantyBast($admin, 'DOCUMENTS-COMPLETE');
        $completed = $this->makeWithoutWarrantyBast($admin, 'LPJ-FINISHED', ['termin1_status' => 'sudah']);

        $this->documents($lpjComplete, [
            'lpj_number_termin1' => 'LPJ-001',
            'lpj_document_path_termin1' => 'lpj/lpj-001.pdf',
        ]);
        $this->documents($documentsComplete, $this->completeTerminOneDocuments('002'));
        $this->documents($completed, $this->completeTerminOneDocuments('003'));

        $this->actingAs($admin)
            ->get(route('admin.lpj.index', ['tab' => 'action']))
            ->assertOk()
            ->assertSee('id="lpj-row-'.$action->id.'"', false)
            ->assertDontSee('id="lpj-row-'.$lpjComplete->id.'"', false)
            ->assertDontSee('id="lpj-row-'.$documentsComplete->id.'"', false)
            ->assertDontSee('id="lpj-row-'.$completed->id.'"', false);

        $this->actingAs($admin)
            ->get(route('admin.lpj.index', ['tab' => 'lpj_complete']))
            ->assertOk()
            ->assertSee('id="lpj-row-'.$lpjComplete->id.'"', false)
            ->assertDontSee('id="lpj-row-'.$action->id.'"', false);

        $this->actingAs($admin)
            ->get(route('admin.lpj.index', ['tab' => 'documents_complete']))
            ->assertOk()
            ->assertSee('id="lpj-row-'.$documentsComplete->id.'"', false)
            ->assertDontSee('id="lpj-row-'.$completed->id.'"', false);

        $this->actingAs($admin)
            ->get(route('admin.lpj.index', ['tab' => 'completed']))
            ->assertOk()
            ->assertSee('id="lpj-row-'.$completed->id.'"', false)
            ->assertDontSee('id="lpj-row-'.$documentsComplete->id.'"', false);
    }

    public function test_stage_filter_separates_payment_termin_one_and_eligible_termin_two(): void
    {
        $admin = $this->admin();
        $single = $this->makeWithoutWarrantyBast($admin, 'SINGLE-PAYMENT');
        $terminOne = $this->makeBast($admin, 'WARRANTY-T1');
        $this->warranty($admin, $terminOne, 6);

        $terminTwoParent = $this->makeBast($admin, 'WARRANTY-T2', ['termin1_status' => 'sudah']);
        $this->warranty($admin, $terminTwoParent, 6);
        $this->documents($terminTwoParent, $this->completeTerminOneDocuments('T2-PARENT'));
        $this->makeBast($admin, 'WARRANTY-T2', [
            'termin_type' => 'termin_2',
            'parent_lhpp_bast_id' => $terminTwoParent->id,
        ], $terminTwoParent->order);

        $this->actingAs($admin)
            ->get(route('admin.lpj.index', ['stage' => 'payment']))
            ->assertOk()
            ->assertSee('id="lpj-row-'.$single->id.'"', false)
            ->assertDontSee('id="lpj-row-'.$terminOne->id.'"', false)
            ->assertDontSee('id="lpj-row-'.$terminTwoParent->id.'"', false);

        $this->actingAs($admin)
            ->get(route('admin.lpj.index', ['stage' => 'termin_1']))
            ->assertOk()
            ->assertSee('id="lpj-row-'.$terminOne->id.'"', false)
            ->assertDontSee('id="lpj-row-'.$single->id.'"', false)
            ->assertDontSee('id="lpj-row-'.$terminTwoParent->id.'"', false);

        $this->actingAs($admin)
            ->get(route('admin.lpj.index', ['stage' => 'termin_2']))
            ->assertOk()
            ->assertSee('id="lpj-row-'.$terminTwoParent->id.'"', false)
            ->assertDontSee('id="lpj-row-'.$single->id.'"', false)
            ->assertDontSee('id="lpj-row-'.$terminOne->id.'"', false);

        $this->assertSame($single->id, $single->fresh()->id);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function makeWithoutWarrantyBast(User $admin, string $number, array $attributes = []): LhppBast
    {
        $bast = $this->makeBast($admin, $number, $attributes);
        $this->warranty($admin, $bast, 0);

        return $bast;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeBast(User $admin, string $number, array $attributes = [], ?Order $order = null): LhppBast
    {
        $order ??= Order::query()->create([
            'nomor_order' => $number,
            'nama_pekerjaan' => 'Pekerjaan '.$number,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Deskripsi',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-08-01',
            'target_selesai' => '2026-08-10',
            'created_by' => $admin->id,
        ]);

        return LhppBast::query()->create(array_merge([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $number,
            'purchase_order_number' => 'PO-'.$number,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => '2026-08-05',
            'tanggal_mulai_pekerjaan' => '2026-08-01',
            'tanggal_selesai_pekerjaan' => '2026-08-05',
            'total_aktual_biaya' => '1000000.00',
            'approval_status' => LhppBast::APPROVAL_APPROVED,
            'termin1_status' => 'belum',
            'termin2_status' => 'belum',
            'created_by' => $admin->id,
        ], $attributes));
    }

    private function warranty(User $admin, LhppBast $bast, int $months): void
    {
        Garansi::query()->create([
            'order_id' => $bast->order_id,
            'lhpp_bast_id' => $bast->id,
            'garansi_months' => $months,
            'start_date' => '2026-08-05',
            'created_by' => $admin->id,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function documents(LhppBast $bast, array $attributes): void
    {
        LpjPpl::query()->create(array_merge(['lhpp_bast_id' => $bast->id], $attributes));
    }

    /** @return array<string, string> */
    private function completeTerminOneDocuments(string $suffix): array
    {
        return [
            'lpj_number_termin1' => 'LPJ-'.$suffix,
            'lpj_document_path_termin1' => 'lpj/'.$suffix.'.pdf',
            'ppl_number_termin1' => 'PPL-'.$suffix,
            'ppl_document_path_termin1' => 'ppl/'.$suffix.'.pdf',
        ];
    }
}
