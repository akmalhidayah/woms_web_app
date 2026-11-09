<?php

namespace Tests\Feature\Admin\Maintenance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MaintenanceOnlineUsersTest extends TestCase
{
    use RefreshDatabase;

    private const CHROME_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    private const SAFARI_IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.driver' => 'database']);
    }

    public function test_only_super_admin_can_open_online_users_tab(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_ADMIN]);
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);

        $this->get(route('admin.maintenance.index', ['tab' => 'online_users']))->assertRedirect(route('login'));
        $this->actingAs($pkm)->get(route('admin.maintenance.index', ['tab' => 'online_users']))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.maintenance.index', ['tab' => 'online_users']))->assertForbidden();
    }

    public function test_tab_lists_recent_sessions_grouped_per_user_without_ip_address(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN, 'name' => 'Super Satu']);
        $pkm = User::factory()->create(['role' => User::ROLE_PKM, 'name' => 'Vendor Dua']);
        $stale = User::factory()->create(['role' => User::ROLE_USER, 'name' => 'User Lama']);

        $this->insertSession('s1', $pkm->id, self::CHROME_WINDOWS, now()->subMinutes(1)->getTimestamp(), '10.1.2.3');
        $this->insertSession('s2', $pkm->id, self::SAFARI_IPHONE, now()->subMinutes(3)->getTimestamp(), '10.1.2.4');
        $this->insertSession('s3', $stale->id, self::CHROME_WINDOWS, now()->subMinutes(30)->getTimestamp(), '10.9.9.9');
        $this->insertSession('s4', null, self::CHROME_WINDOWS, now()->getTimestamp(), '10.8.8.8');

        $response = $this->actingAs($superAdmin)
            ->get(route('admin.maintenance.index', ['tab' => 'online_users']))
            ->assertOk()
            ->assertSee('Vendor Dua')
            ->assertSee('Chrome · Windows')
            ->assertSee('Safari · iOS')
            ->assertDontSee('User Lama')
            ->assertDontSee('10.1.2.3')
            ->assertDontSee('10.9.9.9');

        $this->assertSame(1, substr_count($response->getContent(), 'Vendor Dua'));
    }

    public function test_hidden_category_tabs_fall_back_to_summary(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);

        foreach (['approval', 'documents', 'files', 'users_structure', 'queue_scheduler'] as $hiddenTab) {
            $this->actingAs($superAdmin)
                ->get(route('admin.maintenance.index', ['tab' => $hiddenTab]))
                ->assertOk()
                ->assertSee('Pengguna Online')
                ->assertDontSee('Queue &amp; Scheduler', false);
        }
    }

    public function test_tab_explains_requirement_when_session_driver_is_not_database(): void
    {
        config(['session.driver' => 'array']);
        $superAdmin = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);

        $this->actingAs($superAdmin)
            ->get(route('admin.maintenance.index', ['tab' => 'online_users']))
            ->assertOk()
            ->assertSee('SESSION_DRIVER=database');
    }

    private function insertSession(string $id, ?int $userId, string $userAgent, int $lastActivity, string $ip): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'payload' => '',
            'last_activity' => $lastActivity,
        ]);
    }
}
