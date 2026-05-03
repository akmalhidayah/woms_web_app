<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Hpp;
use App\Models\HppSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Admin Dashboard');
    }

    public function test_pkm_can_access_pkm_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PKM]);

        $response = $this->actingAs($user)->get('/pkm/dashboard');

        $response->assertOk();
        $response->assertSee('PKM Dashboard');
    }

    public function test_approver_dashboard_redirects_to_user_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_APPROVER]);

        $response = $this->actingAs($user)->get('/approver/dashboard');

        $response->assertRedirect('/user/dashboard');
    }

    public function test_approver_can_open_user_order_detail_from_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $order = Order::query()->create([
            'nomor_order' => 'ORD-APPROVER-DETAIL',
            'nama_pekerjaan' => 'Pekerjaan untuk approver',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Detail pekerjaan test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-05-01',
            'target_selesai' => '2026-05-10',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('user.orders.show', $order));

        $response->assertOk();
        $response->assertSee($order->nomor_order);
    }

    public function test_user_order_detail_shows_active_hpp_approval_token(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $signer = User::factory()->create([
            'role' => User::ROLE_APPROVER,
            'name' => 'Manager Approval Test',
        ]);
        $order = Order::query()->create([
            'nomor_order' => 'ORD-HPP-TOKEN',
            'nama_pekerjaan' => 'Pekerjaan HPP Token',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Detail pekerjaan test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-05-01',
            'target_selesai' => '2026-05-10',
            'created_by' => $user->id,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => '1500000.00',
            'status' => Hpp::STATUS_IN_REVIEW,
            'created_by' => $user->id,
        ]);
        $token = 'hpp-token-for-user-share';
        $signedToken = 'hpp-token-signed-for-user-share';

        HppSignature::query()->create([
            'hpp_id' => $hpp->id,
            'step_order' => 1,
            'role_key' => 'manager_peminta',
            'role_label' => 'Manager Peminta',
            'signer_user_id' => $signer->id,
            'signer_name_snapshot' => $signer->name,
            'signer_position_snapshot' => 'Manager Peminta',
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'token_expires_at' => now()->addDay(),
            'status' => HppSignature::STATUS_PENDING,
        ]);
        HppSignature::query()->create([
            'hpp_id' => $hpp->id,
            'step_order' => 2,
            'role_key' => 'sm_peminta',
            'role_label' => 'SM Peminta',
            'signer_user_id' => $signer->id,
            'signer_name_snapshot' => $signer->name,
            'signer_position_snapshot' => 'SM Peminta',
            'token' => $signedToken,
            'token_hash' => hash('sha256', $signedToken),
            'token_expires_at' => now()->addDay(),
            'status' => HppSignature::STATUS_SIGNED,
        ]);

        $response = $this->actingAs($user)->get(route('user.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Token TTD HPP');
        $response->assertSee('Manager Approval Test');
        $response->assertSee(route('approval.hpp.show', $token));
        $response->assertDontSee(route('approval.hpp.show', $signedToken));
    }
}
