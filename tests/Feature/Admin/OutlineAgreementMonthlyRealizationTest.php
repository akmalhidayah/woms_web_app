<?php

namespace Tests\Feature\Admin;

use App\Models\BudgetVerification;
use App\Models\Department;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementMonthlyRealization;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
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
                'unit_kerja' => 'Unit Monthly Realization',
                'seksi' => 'Tidak ada seksi',
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
                'unit_kerja' => 'Unit Monthly Realization',
                'seksi' => 'Tidak ada seksi',
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
                    'unit_kerja' => 'Unit Monthly Realization',
                    'seksi' => 'Tidak ada seksi',
                    'amount' => 1000,
                ])
                ->assertSessionHasNoErrors();
        }

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $secondAgreement), [
                'year' => 2026,
                'month' => 2,
                'kategori_biaya' => 'pemeliharaan',
                'unit_kerja' => 'Unit Monthly Realization',
                'seksi' => 'Tidak ada seksi',
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
                'unit_kerja' => 'Unit Monthly Realization',
                'seksi' => 'Tidak ada seksi',
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
                'unit_kerja' => 'Unit Monthly Realization',
                'seksi' => 'Tidak ada seksi',
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
                'unit_kerja' => 'Unit Monthly Realization',
                'seksi' => 'Tidak ada seksi',
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
            'unit_kerja' => 'Unit Monthly Realization',
            'seksi' => 'Tidak ada seksi',
            'amount' => 1000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'realization_id' => $realization->id,
                'year' => 2026,
                'month' => 4,
                'kategori_biaya' => 'capex',
                'unit_kerja' => 'Unit Monthly Realization',
                'seksi' => 'Tidak ada seksi',
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
            'unit_kerja' => 'Unit Monthly Realization',
            'seksi' => 'Tidak ada seksi',
            'amount' => 1000,
        ]);
        $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 5,
            'kategori_biaya' => 'capex',
            'unit_kerja' => 'Unit Monthly Realization',
            'seksi' => 'Tidak ada seksi',
            'amount' => 2000,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.outline-agreements.index'))
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'realization_id' => $source->id,
                'year' => 2026,
                'month' => 5,
                'kategori_biaya' => 'capex',
                'unit_kerja' => 'Unit Monthly Realization',
                'seksi' => 'Tidak ada seksi',
                'amount' => 3000,
            ])
            ->assertRedirect(route('admin.outline-agreements.index'))
            ->assertSessionHasErrors(['seksi']);

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
            'unit_kerja' => 'Unit Monthly Realization',
            'seksi' => 'Tidak ada seksi',
            'amount' => 1500,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $secondAgreement), [
                'realization_id' => $realization->id,
                'year' => 2026,
                'month' => 3,
                'kategori_biaya' => 'capex',
                'unit_kerja' => 'Unit Monthly Realization',
                'seksi' => 'Tidak ada seksi',
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
            'unit_kerja' => 'Unit Monthly Realization',
            'seksi' => 'Tidak ada seksi',
            'amount' => 1000,
        ]);

        $this->actingAs($user)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 5,
                'kategori_biaya' => 'capex',
                'unit_kerja' => 'Unit Monthly Realization',
                'seksi' => 'Tidak ada seksi',
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
            ->assertSee('name="unit_kerja"', false)
            ->assertSee('name="seksi"', false)
            ->assertDontSee('name="pr_po_amount"', false)
            ->assertDontSee('name="urgent_amount"', false);

        foreach (BudgetVerification::kategoriBiayaOptions() as $value => $label) {
            $response->assertSee('value="'.$value.'"', false)->assertSee($label);
        }
    }

    public function test_structure_pair_is_required_and_section_must_belong_to_selected_unit(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-MONTHLY-STRUCTURE');
        $unit = UnitWork::query()->where('name', 'Unit Monthly Realization')->firstOrFail();
        $section = UnitWorkSection::query()->create([
            'unit_work_id' => $unit->id,
            'name' => 'Seksi Monthly A',
        ]);
        $otherUnit = UnitWork::query()->create([
            'department_id' => $unit->department_id,
            'name' => 'Unit Monthly Other',
        ]);
        UnitWorkSection::query()->create([
            'unit_work_id' => $otherUnit->id,
            'name' => 'Seksi Monthly Other',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 8,
                'kategori_biaya' => 'pemeliharaan',
                'amount' => 100,
            ])
            ->assertSessionHasErrors(['unit_kerja', 'seksi']);

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 8,
                'kategori_biaya' => 'pemeliharaan',
                'unit_kerja' => 'Unit Tidak Terdaftar',
                'seksi' => 'Seksi Monthly A',
                'amount' => 100,
            ])
            ->assertSessionHasErrors(['unit_kerja']);

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 8,
                'kategori_biaya' => 'pemeliharaan',
                'unit_kerja' => $unit->name,
                'seksi' => 'Seksi Monthly Other',
                'amount' => 100,
            ])
            ->assertSessionHasErrors(['seksi']);

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 8,
                'kategori_biaya' => 'pemeliharaan',
                'unit_kerja' => ' '.$unit->name.' ',
                'seksi' => ' '.$section->name.' ',
                'amount' => 100,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('outline_agreement_monthly_realizations', [
            'outline_agreement_id' => $agreement->id,
            'unit_kerja' => $unit->name,
            'seksi' => $section->name,
        ]);
    }

    public function test_same_period_and_category_accepts_multiple_sections_and_upserts_exact_identity(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-MONTHLY-SECTIONS');
        $unit = UnitWork::query()->where('name', 'Unit Monthly Realization')->firstOrFail();

        foreach (['Seksi Monthly A', 'Seksi Monthly B'] as $sectionName) {
            UnitWorkSection::query()->create([
                'unit_work_id' => $unit->id,
                'name' => $sectionName,
            ]);

            $this->actingAs($admin)
                ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                    'year' => 2026,
                    'month' => 9,
                    'kategori_biaya' => 'pemeliharaan',
                    'unit_kerja' => $unit->name,
                    'seksi' => $sectionName,
                    'amount' => 100,
                ])
                ->assertSessionHasNoErrors();
        }

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'year' => 2026,
                'month' => 9,
                'kategori_biaya' => 'pemeliharaan',
                'unit_kerja' => $unit->name,
                'seksi' => 'Seksi Monthly A',
                'amount' => 250,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $agreement->monthlyRealizations()->count());
        $this->assertDatabaseHas('outline_agreement_monthly_realizations', [
            'seksi' => 'Seksi Monthly A',
            'amount' => 250,
        ]);
    }

    public function test_edit_changes_structure_on_the_same_record_and_rejects_full_identity_conflict(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-MONTHLY-EDIT-STRUCTURE');
        $baseUnit = UnitWork::query()->where('name', 'Unit Monthly Realization')->firstOrFail();
        $otherUnit = UnitWork::query()->create([
            'department_id' => $baseUnit->department_id,
            'name' => 'Unit Monthly Edit',
        ]);
        foreach (['Seksi Edit A', 'Seksi Edit B'] as $sectionName) {
            UnitWorkSection::query()->create([
                'unit_work_id' => $otherUnit->id,
                'name' => $sectionName,
            ]);
        }

        $source = $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 10,
            'kategori_biaya' => 'pemeliharaan',
            'unit_kerja' => $baseUnit->name,
            'seksi' => 'Tidak ada seksi',
            'amount' => 100,
        ]);
        $conflict = $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 10,
            'kategori_biaya' => 'pemeliharaan',
            'unit_kerja' => $otherUnit->name,
            'seksi' => 'Seksi Edit B',
            'amount' => 200,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'realization_id' => $source->id,
                'year' => 2026,
                'month' => 10,
                'kategori_biaya' => 'pemeliharaan',
                'unit_kerja' => $otherUnit->name,
                'seksi' => 'Seksi Edit A',
                'amount' => 300,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('outline_agreement_monthly_realizations', 2);
        $this->assertDatabaseHas('outline_agreement_monthly_realizations', [
            'id' => $source->id,
            'unit_kerja' => $otherUnit->name,
            'seksi' => 'Seksi Edit A',
            'amount' => 300,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.outline-agreements.monthly-realizations.store', $agreement), [
                'realization_id' => $source->id,
                'year' => 2026,
                'month' => 10,
                'kategori_biaya' => 'pemeliharaan',
                'unit_kerja' => $otherUnit->name,
                'seksi' => 'Seksi Edit B',
                'amount' => 400,
            ])
            ->assertSessionHasErrors(['seksi']);

        $this->assertDatabaseHas('outline_agreement_monthly_realizations', [
            'id' => $source->id,
            'seksi' => 'Seksi Edit A',
            'amount' => 300,
        ]);
        $this->assertDatabaseHas('outline_agreement_monthly_realizations', [
            'id' => $conflict->id,
            'seksi' => 'Seksi Edit B',
            'amount' => 200,
        ]);
    }

    public function test_legacy_realization_without_structure_remains_readable(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-MONTHLY-LEGACY');
        $legacy = $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 11,
            'kategori_biaya' => 'pemeliharaan',
            'unit_kerja' => null,
            'seksi' => null,
            'amount' => 500,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.outline-agreements.index'))
            ->assertOk()
            ->assertSee('Belum ditentukan');

        $this->assertDatabaseHas('outline_agreement_monthly_realizations', [
            'id' => $legacy->id,
            'unit_kerja' => null,
            'seksi' => null,
            'amount' => 500,
        ]);
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
