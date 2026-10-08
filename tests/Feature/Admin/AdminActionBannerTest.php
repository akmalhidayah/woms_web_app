<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\AdminRoleMenuAccess;
use App\Models\Order;
use App\Models\OrderScopeOfWork;
use App\Models\User;
use App\Support\AdminMenuRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt as LivewireVolt;
use Tests\TestCase;

class AdminActionBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_sets_banner_flag_for_admin_only(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
            'email' => 'admin-banner@example.test',
        ]);

        LivewireVolt::test('auth.login')
            ->set('email', $admin->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertSessionHas('show_admin_action_summary_banner', true);

        auth()->logout();
        session()->flush();

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email' => 'user-banner@example.test',
        ]);

        LivewireVolt::test('auth.login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertSessionMissing('show_admin_action_summary_banner');
    }

    public function test_banner_is_rendered_once_on_first_dashboard_visit_when_action_exists(): void
    {
        $admin = $this->admin(User::ADMIN_ROLE_SUPER_ADMIN);
        $this->order($admin, 'BANNER-ONE-TIME');

        $this->actingAs($admin)
            ->withSession(['show_admin_action_summary_banner' => true])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-admin-action-summary-banner', false)
            ->assertSee('Ada 1 pekerjaan yang perlu ditindaklanjuti.');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('data-admin-action-summary-banner', false);
    }

    public function test_banner_is_hidden_without_pending_action_and_summary_respects_access_panel(): void
    {
        $adminWithoutActions = $this->admin(User::ADMIN_ROLE_SUPER_ADMIN);
        $this->actingAs($adminWithoutActions)
            ->withSession(['show_admin_action_summary_banner' => true])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('data-admin-action-summary-banner', false);

        $regularAdmin = $this->admin(User::ADMIN_ROLE_ADMIN);
        $this->order($regularAdmin, 'BANNER-HIDDEN-ORDER');
        $ready = $this->order($regularAdmin, 'BANNER-VISIBLE-HPP');
        OrderScopeOfWork::query()->create([
            'order_id' => $ready->id,
            'tanggal_dokumen' => '2026-08-01',
            'scope_items' => [['pekerjaan' => 'Scope test']],
            'created_by' => $regularAdmin->id,
        ]);
        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_CREATE_HPP,
        ]);

        $this->actingAs($regularAdmin)
            ->withSession(['show_admin_action_summary_banner' => true])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('HPP perlu dibuat: 1')
            ->assertDontSee('Order perlu dilengkapi');
    }

    private function admin(string $adminRole): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => $adminRole,
        ]);
    }

    private function order(User $creator, string $number): Order
    {
        return Order::query()->create([
            'nomor_order' => $number,
            'notifikasi' => 'NOTIF-'.$number,
            'nama_pekerjaan' => 'Pekerjaan '.$number,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Deskripsi test',
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-08-01',
            'target_selesai' => '2026-08-10',
            'created_by' => $creator->id,
        ]);
    }
}
