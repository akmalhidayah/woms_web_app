<?php

namespace Tests\Feature\Approval;

use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HppApprovalMarkUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_hpp_roles_use_initial_ui(): void
    {
        $user = User::factory()->create();

        foreach (['manager_peminta', 'manager_pengendali', 'manager_counter_part'] as $roleKey) {
            $token = 'initial-'.$roleKey;
            $this->createApproval($user, $roleKey, $token);

            $this->actingAs($user)
                ->get(route('approval.hpp.show', $token))
                ->assertOk()
                ->assertSee('Paraf Digital')
                ->assertSee('Area Paraf')
                ->assertSee('Simpan Paraf')
                ->assertDontSee('Simpan Tanda Tangan');
        }
    }

    public function test_workshop_manager_hpp_role_keeps_full_signature_ui(): void
    {
        $user = User::factory()->create();
        $token = 'full-workshop-manager';
        $this->createApproval($user, 'workshop_manager_pengendali', $token);

        $this->actingAs($user)
            ->get(route('approval.hpp.show', $token))
            ->assertOk()
            ->assertSee('Tanda Tangan Digital')
            ->assertSee('Area Penandatanganan')
            ->assertSee('Simpan Tanda Tangan')
            ->assertDontSee('Simpan Paraf');
    }

    private function createApproval(User $user, string $roleKey, string $token): HppSignature
    {
        $order = Order::query()->create([
            'nomor_order' => 'MARK-'.uniqid(),
            'nama_pekerjaan' => 'Approval mark UI test',
            'unit_kerja' => 'Workshop',
            'seksi' => 'Machine Workshop',
            'deskripsi' => 'Test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
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
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_IN_REVIEW,
            'created_by' => $user->id,
        ]);

        return $hpp->signatures()->create([
            'step_order' => 1,
            'role_key' => $roleKey,
            'role_label' => $roleKey,
            'signer_user_id' => $user->id,
            'signer_name_snapshot' => $user->name,
            'signer_position_snapshot' => $roleKey,
            'status' => HppSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', $token),
            'token_encrypted' => $token,
            'token_expires_at' => now()->addDay(),
        ]);
    }
}
