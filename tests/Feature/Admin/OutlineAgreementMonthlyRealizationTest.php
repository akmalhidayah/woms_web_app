<?php

namespace Tests\Feature\Admin;

use App\Models\BudgetVerification;
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

    public function test_admin_can_store_formatted_realization_and_update_the_same_period_category(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-MONTHLY-001');

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 1,
                'kategori_biaya' => 'pemeliharaan',
                'amount' => 'Rp 100.000.000',
            ])
            ->assertRedirect(route('admin.outline-agreements.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('outline_agreement_monthly_realizations', [
            'outline_agreement_id' => $agreement->id,
            'year' => 2026,
            'month' => 1,
            'kategori_biaya' => 'pemeliharaan',
            'amount' => 100000000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 1,
                'kategori_biaya' => 'pemeliharaan',
                'amount' => 125000000,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, OutlineAgreementMonthlyRealization::query()->count());
        $this->assertDatabaseHas('outline_agreement_monthly_realizations', [
            'outline_agreement_id' => $agreement->id,
            'year' => 2026,
            'month' => 1,
            'kategori_biaya' => 'pemeliharaan',
            'amount' => 125000000,
        ]);
    }

    public function test_same_period_accepts_multiple_categories_and_different_agreements(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $firstAgreement = $this->createAgreement($admin, 'OA-MONTHLY-002');
        $secondAgreement = $this->createAgreement($admin, 'OA-MONTHLY-003');

        foreach (['pemeliharaan', 'capex'] as $category) {
            $this->actingAs($admin)
                ->post(route('admin.outline-agreements.monthly-realizations.store', $firstAgreement), [
                    'year' => 2026,
                    'month' => 2,
                    'kategori_biaya' => $category,
                    'amount' => 1000,
                ])
                ->assertSessionHasNoErrors();
        }

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $secondAgreement), [
                'year' => 2026,
                'month' => 2,
                'kategori_biaya' => 'pemeliharaan',
                'amount' => 2000,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('outline_agreement_monthly_realizations', 3);
    }

    public function test_large_amount_is_stored_without_overflow(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-MONTHLY-LARGE');

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 2,
                'kategori_biaya' => 'capex',
                'amount' => '47.950.426.696',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('outline_agreement_monthly_realizations', [
            'outline_agreement_id' => $agreement->id,
            'kategori_biaya' => 'capex',
            'amount' => 47950426696,
        ]);
    }

    public function test_invalid_category_amount_month_and_period_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-MONTHLY-004');

        $this->actingAs($admin)
            ->from(route('admin.outline-agreements.index'))
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 13,
                'kategori_biaya' => OutlineAgreementMonthlyRealization::CATEGORY_UNCATEGORIZED,
                'amount' => -1,
            ])
            ->assertRedirect(route('admin.outline-agreements.index'))
            ->assertSessionHasErrors(['month', 'kategori_biaya', 'amount']);

        $this->actingAs($admin)
            ->from(route('admin.outline-agreements.index'))
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2027,
                'month' => 1,
                'kategori_biaya' => 'pemeliharaan',
                'amount' => 1,
            ])
            ->assertRedirect(route('admin.outline-agreements.index'))
            ->assertSessionHasErrors(['month']);

        $this->assertDatabaseCount('outline_agreement_monthly_realizations', 0);
    }

    public function test_edit_updates_the_selected_record_and_can_change_its_category(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-MONTHLY-EDIT');
        $realization = $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 3,
            'kategori_biaya' => 'pemeliharaan',
            'amount' => 1000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'realization_id' => $realization->id,
                'year' => 2026,
                'month' => 4,
                'kategori_biaya' => 'capex',
                'amount' => 2500,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('outline_agreement_monthly_realizations', 1);
        $this->assertDatabaseHas('outline_agreement_monthly_realizations', [
            'id' => $realization->id,
            'year' => 2026,
            'month' => 4,
            'kategori_biaya' => 'capex',
            'amount' => 2500,
        ]);
    }

    public function test_edit_to_an_existing_period_category_returns_validation_error(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-MONTHLY-CONFLICT');
        $source = $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 5,
            'kategori_biaya' => 'pemeliharaan',
            'amount' => 1000,
        ]);
        $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 5,
            'kategori_biaya' => 'capex',
            'amount' => 2000,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.outline-agreements.index'))
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'realization_id' => $source->id,
                'year' => 2026,
                'month' => 5,
                'kategori_biaya' => 'capex',
                'amount' => 3000,
            ])
            ->assertRedirect(route('admin.outline-agreements.index'))
            ->assertSessionHasErrors(['kategori_biaya']);

        $this->assertDatabaseHas('outline_agreement_monthly_realizations', [
            'id' => $source->id,
            'kategori_biaya' => 'pemeliharaan',
            'amount' => 1000,
        ]);
    }

    public function test_admin_can_edit_or_delete_only_a_realization_owned_by_the_agreement(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $firstAgreement = $this->createAgreement($admin, 'OA-MONTHLY-005');
        $secondAgreement = $this->createAgreement($admin, 'OA-MONTHLY-006');
        $realization = $firstAgreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 3,
            'kategori_biaya' => 'pemeliharaan',
            'amount' => 1500,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $secondAgreement), [
                'realization_id' => $realization->id,
                'year' => 2026,
                'month' => 3,
                'kategori_biaya' => 'capex',
                'amount' => 2000,
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->delete(route('admin.outline-agreements.monthly-realizations.destroy', [$secondAgreement, $realization]))
            ->assertNotFound();

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
            'kategori_biaya' => 'pemeliharaan',
            'amount' => 1000,
        ]);

        $this->actingAs($user)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 5,
                'kategori_biaya' => 'capex',
                'amount' => 1000,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin.outline-agreements.monthly-realizations.destroy', [$agreement, $realization]))
            ->assertForbidden();

        $this->assertDatabaseCount('outline_agreement_monthly_realizations', 1);
    }

    public function test_modal_uses_budget_verification_categories_and_new_field_names(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->createAgreement($admin, 'OA-MONTHLY-UI');

        $response = $this->actingAs($admin)->get(route('admin.outline-agreements.index'));

        $response->assertOk()
            ->assertSee('Kategori Biaya')
            ->assertSee('Nilai Realisasi')
            ->assertSee('name="kategori_biaya"', false)
            ->assertSee('name="amount"', false)
            ->assertDontSee('name="pr_po_amount"', false)
            ->assertDontSee('name="urgent_amount"', false);

        foreach (BudgetVerification::kategoriBiayaOptions() as $value => $label) {
            $response->assertSee('value="'.$value.'"', false)->assertSee($label);
        }
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
