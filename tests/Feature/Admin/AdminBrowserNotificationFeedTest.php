<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\AdminRoleMenuAccess;
use App\Models\Order;
use App\Models\OrderScopeOfWork;
use App\Models\User;
use App\Support\AdminMenuRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBrowserNotificationFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_only_exposes_safe_fields_and_is_limited_to_twenty_actions(): void
    {
        $admin = $this->admin(User::ADMIN_ROLE_SUPER_ADMIN);

        foreach (range(1, 25) as $index) {
            $this->order($admin, 'FEED-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT));
        }

        $response = $this->actingAs($admin)->getJson(route('admin.notifications.action-feed'));

        $response->assertOk()->assertJsonCount(20, 'actions');
        $this->assertSame(25, $response->json('pending_count'));
        $this->assertSame(
            ['key', 'title', 'message', 'url', 'overdue_level'],
            array_keys($response->json('actions.0')),
        );
    }

    public function test_feed_filters_access_panel_and_non_admin_is_forbidden(): void
    {
        $admin = $this->admin(User::ADMIN_ROLE_ADMIN);
        $incomplete = $this->order($admin, 'FEED-INCOMPLETE');
        $ready = $this->order($admin, 'FEED-CREATE-HPP');
        OrderScopeOfWork::query()->create([
            'order_id' => $ready->id,
            'tanggal_dokumen' => '2026-08-01',
            'scope_items' => [['pekerjaan' => 'Scope test']],
            'created_by' => $admin->id,
        ]);
        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_CREATE_HPP,
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.notifications.action-feed'));
        $response->assertOk()->assertJsonPath('pending_count', 1);
        $this->assertSame(['create-hpp:'.$ready->id], collect($response->json('actions'))->pluck('key')->all());
        $this->assertNotContains('order-sow:'.$incomplete->id, collect($response->json('actions'))->pluck('key')->all());

        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user)->getJson(route('admin.notifications.action-feed'))->assertForbidden();
    }

    public function test_admin_layout_contains_two_sections_browser_opt_in_and_no_inbox_link(): void
    {
        $admin = $this->admin(User::ADMIN_ROLE_SUPER_ADMIN);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Perlu Tindakan')
            ->assertSee('Informasi Terbaru')
            ->assertSee('Aktifkan notifikasi browser untuk pekerjaan penting.')
            ->assertSee('woms_admin_seen_action_keys', false)
            ->assertDontSee('Lihat Inbox')
            ->assertDontSee('Approval Inbox');
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
