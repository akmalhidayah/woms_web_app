<?php

namespace Tests\Feature\Admin;

use App\Models\AdminRoleMenuAccess;
use App\Models\HppApprovalSetting;
use App\Models\User;
use App\Support\AdminActionCenter;
use App\Support\AdminMenuPermissions;
use App\Support\AdminMenuRegistry;
use App\Support\AdminNotificationCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class AdminPresentationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_does_not_query_action_or_information_lists(): void
    {
        $admin = $this->admin();
        $this->mock(AdminActionCenter::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sidebarCounts')->once()->andReturn([]);
            $mock->shouldReceive('pendingActionCount')->once()->andReturn(0);
            $mock->shouldReceive('summary')->once()->andReturn([]);
            $mock->shouldNotReceive('actions');
        });
        $this->mock(AdminNotificationCenter::class, function (MockInterface $mock): void {
            $mock->shouldReceive('unreadInformationCount')->once()->andReturn(0);
            $mock->shouldNotReceive('informationNotifications');
        });

        $this->actingAs($admin)->get(route('admin.profile.edit'))
            ->assertOk()->assertSee('notificationsHtml', false);
    }

    public function test_panel_cache_is_per_user_permission_set_and_expires(): void
    {
        $first = $this->admin();
        $second = $this->admin();
        $this->mock(AdminActionCenter::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sidebarCounts')->times(4)->andReturn([]);
            $mock->shouldReceive('pendingActionCount')->times(4)->andReturn(0);
            $mock->shouldReceive('summary')->times(4)->andReturn([]);
            $mock->shouldReceive('actions')->times(4)->andReturn(collect());
        });
        $this->mock(AdminNotificationCenter::class, function (MockInterface $mock): void {
            $mock->shouldReceive('unreadInformationCount')->times(4)->andReturn(0);
            $mock->shouldReceive('informationNotifications')->times(4)->andReturn(collect());
        });

        $this->actingAs($first)->getJson(route('admin.notifications.panel'))->assertOk();
        $this->getJson(route('admin.notifications.panel'))->assertOk();
        $this->actingAs($second)->getJson(route('admin.notifications.panel'))->assertOk();

        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_CREATE_HPP,
        ]);
        $this->actingAs($first)->getJson(route('admin.notifications.panel'))->assertOk();
        $this->travel(46)->seconds();
        $this->getJson(route('admin.notifications.panel'))->assertOk();
    }

    public function test_menu_permissions_are_read_once_and_model_writes_invalidate_them(): void
    {
        $admin = $this->admin();
        app(AdminMenuPermissions::class)->forget();
        DB::enableQueryLog();
        $before = count(DB::getQueryLog());
        $this->assertFalse($admin->hasAdminMenuAccess(AdminMenuRegistry::MENU_CREATE_HPP));
        $this->assertFalse($admin->hasAdminMenuAccess(AdminMenuRegistry::MENU_PURCHASE_ORDER));
        $this->assertFalse($admin->hasAdminMenuAccess(AdminMenuRegistry::MENU_ORDER_JASA));
        $this->assertCount($before + 1, DB::getQueryLog());
        DB::disableQueryLog();

        $permission = AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_CREATE_HPP,
        ]);
        $this->assertTrue($admin->hasAdminMenuAccess(AdminMenuRegistry::MENU_CREATE_HPP));
        $permission->delete();
        $this->assertFalse($admin->hasAdminMenuAccess(AdminMenuRegistry::MENU_CREATE_HPP));
    }

    public function test_lazy_endpoints_require_login_role_and_structure_permission(): void
    {
        $this->getJson(route('admin.notifications.panel'))->assertUnauthorized();
        $this->getJson(route('admin.structure.user-options'))->assertUnauthorized();

        foreach ([User::ROLE_USER, User::ROLE_PKM, User::ROLE_APPROVER, User::ROLE_INSPECTOR] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->getJson(route('admin.notifications.panel'))->assertForbidden();
            $this->getJson(route('admin.structure.user-options'))->assertForbidden();
        }

        $this->actingAs($this->admin());
        $this->getJson(route('admin.structure.user-options'))->assertForbidden();
        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_STRUKTUR_ORGANISASI,
        ]);
        $response = $this->getJson(route('admin.structure.user-options'))->assertOk();
        $this->assertSame(['id', 'name'], array_keys($response->json('users.0')));
    }

    public function test_structure_index_omits_user_options_and_does_not_create_settings(): void
    {
        $admin = $this->admin(User::ADMIN_ROLE_SUPER_ADMIN);
        $count = HppApprovalSetting::query()->count();
        $response = $this->actingAs($admin)->get(route('admin.structure.index'))->assertOk();
        $response->assertViewMissing('users')->assertViewMissing('sectionOptions');
        $this->assertSame($count, HppApprovalSetting::query()->count());
    }

    private function admin(string $role = User::ADMIN_ROLE_ADMIN): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => $role]);
    }
}
