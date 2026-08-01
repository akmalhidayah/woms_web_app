<?php

namespace Tests\Feature\Admin;

use App\Models\BudgetVerification;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetVerificationAutoSaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_json_update_saves_status_anggaran(): void
    {
        [$admin, $hpp] = $this->context();

        $this->actingAs($admin)
            ->patchJson(route('admin.budget-verification.update', $hpp), [
                'status_anggaran' => 'Tersedia',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status_anggaran', 'Tersedia')
            ->assertJsonPath('updated_fields.status_anggaran', 'Tersedia');

        $this->assertDatabaseHas('budget_verifications', [
            'hpp_id' => $hpp->id,
            'status_anggaran' => 'Tersedia',
        ]);
    }

    public function test_partial_dropdown_update_does_not_clear_other_fields(): void
    {
        [$admin, $hpp, $verification] = $this->contextWithVerification();

        $this->actingAs($admin)
            ->patchJson(route('admin.budget-verification.update', $hpp), [
                'kategori_biaya' => 'capex',
            ])
            ->assertOk();

        $verification->refresh();

        $this->assertSame('Tersedia', $verification->status_anggaran);
        $this->assertSame('jasa', $verification->kategori_item);
        $this->assertSame('capex', $verification->kategori_biaya);
        $this->assertSame('65340001', $verification->cost_element);
        $this->assertSame('Catatan lama', $verification->catatan);
    }

    public function test_approved_hpp_is_not_ready_for_purchase_order_until_required_budget_fields_are_complete(): void
    {
        [$admin, $hpp] = $this->context();

        $this->actingAs($admin)
            ->patchJson(route('admin.budget-verification.update', $hpp), [
                'status_anggaran' => 'Tersedia',
            ])
            ->assertOk()
            ->assertJsonPath('data.is_purchase_order_eligible', false);

        $this->actingAs($admin)
            ->get(route('admin.budget-verification.index'))
            ->assertOk()
            ->assertSee('data-purchase-order-eligible="false"', false);

        $this->actingAs($admin)
            ->get(route('admin.purchase-order.index'))
            ->assertOk()
            ->assertDontSee($hpp->nomor_order);
    }

    public function test_complete_available_budget_is_listed_as_ready_and_available_for_purchase_order(): void
    {
        [$admin, $hpp, $verification] = $this->contextWithVerification();
        $verification->update(['catatan' => null]);

        $this->assertTrue($verification->fresh()->isReadyForPurchaseOrder());

        $this->actingAs($admin)
            ->get(route('admin.budget-verification.index', ['tab' => 'ready_po']))
            ->assertOk()
            ->assertSee('data-purchase-order-eligible="true"', false)
            ->assertDontSee('bg-emerald-50/70', false);

        $this->actingAs($admin)
            ->get(route('admin.purchase-order.index'))
            ->assertOk()
            ->assertSee($hpp->nomor_order);
    }

    public function test_valid_kategori_item_is_saved(): void
    {
        [$admin, $hpp] = $this->context();

        $this->actingAs($admin)
            ->patchJson(route('admin.budget-verification.update', $hpp), ['kategori_item' => 'spare part'])
            ->assertOk()
            ->assertJsonPath('data.kategori_item', 'spare part');
    }

    public function test_valid_kategori_biaya_is_saved(): void
    {
        [$admin, $hpp] = $this->context();

        $this->actingAs($admin)
            ->patchJson(route('admin.budget-verification.update', $hpp), ['kategori_biaya' => 'non pemeliharaan'])
            ->assertOk()
            ->assertJsonPath('data.kategori_biaya', 'non pemeliharaan');
    }

    public function test_invalid_dropdown_value_returns_validation_error(): void
    {
        [$admin, $hpp] = $this->context();

        $this->actingAs($admin)
            ->patchJson(route('admin.budget-verification.update', $hpp), ['status_anggaran' => 'invalid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status_anggaran');

        $this->assertDatabaseMissing('budget_verifications', ['hpp_id' => $hpp->id]);
    }

    public function test_empty_dropdown_value_is_saved_as_null(): void
    {
        [$admin, $hpp, $verification] = $this->contextWithVerification();

        $this->actingAs($admin)
            ->patchJson(route('admin.budget-verification.update', $hpp), ['kategori_item' => ''])
            ->assertOk()
            ->assertJsonPath('data.kategori_item', null);

        $this->assertNull($verification->refresh()->kategori_item);
    }

    public function test_partial_update_creates_budget_verification_when_missing(): void
    {
        [$admin, $hpp] = $this->context();

        $this->actingAs($admin)
            ->patchJson(route('admin.budget-verification.update', $hpp), ['kategori_biaya' => 'pemeliharaan'])
            ->assertOk();

        $this->assertDatabaseHas('budget_verifications', [
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
            'kategori_biaya' => 'pemeliharaan',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    public function test_regular_form_submit_still_redirects_and_saves_all_fields(): void
    {
        [$admin, $hpp] = $this->context();

        $this->actingAs($admin)
            ->patch(route('admin.budget-verification.update', $hpp), [
                'status_anggaran' => 'Menunggu',
                'kategori_item' => 'jasa',
                'kategori_biaya' => 'pemeliharaan',
                'cost_element' => '65340002',
                'catatan' => 'Catatan baru',
                '_filter_tab' => 'action',
                '_filter_search' => 'ORDER',
                '_filter_page' => 2,
            ])
            ->assertRedirect(route('admin.budget-verification.index', [
                'tab' => 'action',
                'search' => 'ORDER',
                'page' => 2,
            ]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('budget_verifications', [
            'hpp_id' => $hpp->id,
            'status_anggaran' => 'Menunggu',
            'kategori_item' => 'jasa',
            'kategori_biaya' => 'pemeliharaan',
            'cost_element' => '65340002',
            'catatan' => 'Catatan baru',
        ]);
    }

    public function test_non_admin_user_cannot_auto_save_budget_verification(): void
    {
        [, $hpp] = $this->context();
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->patchJson(route('admin.budget-verification.update', $hpp), ['status_anggaran' => 'Tersedia'])
            ->assertForbidden();

        $this->assertDatabaseMissing('budget_verifications', ['hpp_id' => $hpp->id]);
    }

    /**
     * @return array{User, Hpp}
     */
    private function context(): array
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $order = Order::query()->create([
            'nomor_order' => 'ORDER-BUDGET-'.uniqid(),
            'nama_pekerjaan' => 'Pekerjaan verifikasi anggaran',
            'deskripsi' => 'Pekerjaan verifikasi anggaran',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-07-01',
            'target_selesai' => '2026-07-10',
            'created_by' => $admin->id,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_APPROVED,
            'created_by' => $admin->id,
        ]);

        return [$admin, $hpp];
    }

    /**
     * @return array{User, Hpp, BudgetVerification}
     */
    private function contextWithVerification(): array
    {
        [$admin, $hpp] = $this->context();
        $verification = BudgetVerification::query()->create([
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
            'status_anggaran' => 'Tersedia',
            'kategori_item' => 'jasa',
            'kategori_biaya' => 'pemeliharaan',
            'cost_element' => '65340001',
            'catatan' => 'Catatan lama',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        return [$admin, $hpp, $verification];
    }
}
