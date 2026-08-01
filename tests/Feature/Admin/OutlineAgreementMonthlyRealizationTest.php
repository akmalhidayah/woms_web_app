<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementMonthlyRealization;
use App\Models\UnitWork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutlineAgreementMonthlyRealizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_and_update_one_realization_per_period(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-MONTHLY-001');

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 1,
                'pr_po_amount' => 'Rp 100.000.000',
                'urgent_amount' => '20.000.000',
            ])
            ->assertRedirect(route('admin.outline-agreements.index'));

        $this->assertDatabaseHas('outline_agreement_monthly_realizations', [
            'outline_agreement_id' => $agreement->id,
            'year' => 2026,
            'month' => 1,
            'pr_po_amount' => 100000000,
            'urgent_amount' => 20000000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 1,
                'pr_po_amount' => 125000000,
                'urgent_amount' => 25000000,
            ])
            ->assertRedirect(route('admin.outline-agreements.index'));

        $this->assertSame(1, OutlineAgreementMonthlyRealization::query()->count());
        $this->assertDatabaseHas('outline_agreement_monthly_realizations', [
            'outline_agreement_id' => $agreement->id,
            'year' => 2026,
            'month' => 1,
            'pr_po_amount' => 125000000,
            'urgent_amount' => 25000000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.outline-agreements.index'))
            ->assertOk()
            ->assertSee('Realisasi Bulanan')
            ->assertSee('Realisasi Biaya Bulanan');
    }

    public function test_different_agreements_can_use_the_same_period_and_large_amounts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $firstAgreement = $this->createAgreement($admin, 'OA-MONTHLY-002');
        $secondAgreement = $this->createAgreement($admin, 'OA-MONTHLY-003');

        foreach ([$firstAgreement, $secondAgreement] as $agreement) {
            $this->actingAs($admin)
                ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                    'year' => 2026,
                    'month' => 2,
                    'pr_po_amount' => 47950426696,
                    'urgent_amount' => 0,
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(2, OutlineAgreementMonthlyRealization::query()->count());
        $this->assertDatabaseHas('outline_agreement_monthly_realizations', [
            'outline_agreement_id' => $firstAgreement->id,
            'pr_po_amount' => 47950426696,
        ]);
    }

    public function test_invalid_amount_month_and_period_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-MONTHLY-004');

        $this->actingAs($admin)
            ->from(route('admin.outline-agreements.index'))
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 13,
                'pr_po_amount' => -1,
                'urgent_amount' => 0,
            ])
            ->assertRedirect(route('admin.outline-agreements.index'))
            ->assertSessionHasErrors(['month', 'pr_po_amount']);

        $this->actingAs($admin)
            ->from(route('admin.outline-agreements.index'))
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2027,
                'month' => 1,
                'pr_po_amount' => 1,
                'urgent_amount' => 0,
            ])
            ->assertRedirect(route('admin.outline-agreements.index'))
            ->assertSessionHasErrors(['month']);

        $this->assertDatabaseCount('outline_agreement_monthly_realizations', 0);
    }

    public function test_admin_can_delete_only_a_realization_owned_by_the_agreement(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $firstAgreement = $this->createAgreement($admin, 'OA-MONTHLY-005');
        $secondAgreement = $this->createAgreement($admin, 'OA-MONTHLY-006');
        $realization = $firstAgreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 3,
            'pr_po_amount' => 1000,
            'urgent_amount' => 500,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.outline-agreements.monthly-realizations.destroy', [$secondAgreement, $realization]))
            ->assertNotFound();

        $this->assertDatabaseHas('outline_agreement_monthly_realizations', ['id' => $realization->id]);

        $this->actingAs($admin)
            ->delete(route('admin.outline-agreements.monthly-realizations.destroy', [$firstAgreement, $realization]))
            ->assertRedirect(route('admin.outline-agreements.index'));

        $this->assertDatabaseMissing('outline_agreement_monthly_realizations', ['id' => $realization->id]);
        $this->assertDatabaseHas('outline_agreements', ['id' => $firstAgreement->id]);
    }

    public function test_non_admin_cannot_store_or_delete_monthly_realization(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $agreement = $this->createAgreement($admin, 'OA-MONTHLY-007');
        $realization = $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 4,
            'pr_po_amount' => 1000,
            'urgent_amount' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 5,
                'pr_po_amount' => 1000,
                'urgent_amount' => 0,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin.outline-agreements.monthly-realizations.destroy', [$agreement, $realization]))
            ->assertForbidden();

        $this->assertDatabaseCount('outline_agreement_monthly_realizations', 1);
    }

    private function createAgreement(User $admin, string $number): OutlineAgreement
    {
        $department = Department::query()->firstOrCreate(['name' => 'Departemen Monthly Realization']);
        $unitWork = UnitWork::query()->firstOrCreate([
            'department_id' => $department->id,
            'name' => 'Unit Monthly Realization',
        ]);

        return OutlineAgreement::query()->create([
            'nomor_oa' => $number,
            'unit_work_id' => $unitWork->id,
            'jenis_kontrak' => 'Fabrikasi',
            'nama_kontrak' => 'Kontrak '.$number,
            'nilai_kontrak_awal' => '100000000000.00',
            'periode_awal_start' => '2026-01-01',
            'periode_awal_end' => '2026-12-31',
            'current_total_nilai' => '100000000000.00',
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-12-31',
            'status' => OutlineAgreement::STATUS_ACTIVE,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
