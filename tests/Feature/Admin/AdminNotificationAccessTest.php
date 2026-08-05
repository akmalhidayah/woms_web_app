<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\AdminNotificationRead;
use App\Models\AdminRoleMenuAccess;
use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\Order;
use App\Models\User;
use App\Support\AdminActionCenter;
use App\Support\AdminMenuRegistry;
use App\Support\AdminNotificationCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminNotificationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_information_is_filtered_by_access_panel(): void
    {
        $regularAdmin = $this->admin(User::ADMIN_ROLE_ADMIN);
        $superAdmin = $this->admin(User::ADMIN_ROLE_SUPER_ADMIN);
        $this->signedHppInformation($superAdmin, 'INFO-HPP');

        $this->assertCount(0, app(AdminNotificationCenter::class)->informationNotifications($regularAdmin));

        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_CREATE_HPP,
        ]);

        $this->assertCount(1, app(AdminNotificationCenter::class)->informationNotifications($regularAdmin));
        $this->assertCount(1, app(AdminNotificationCenter::class)->informationNotifications($superAdmin));
        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tandai semua dibaca');
    }

    public function test_reading_information_does_not_remove_business_action(): void
    {
        $admin = $this->admin(User::ADMIN_ROLE_SUPER_ADMIN);
        $this->signedHppInformation($admin, 'INFO-READ');
        $order = $this->order($admin, 'ACTION-READ');
        $actionKey = 'order-sow:'.$order->id;
        $information = app(AdminNotificationCenter::class)->informationNotifications($admin)->firstOrFail();

        $this->actingAs($admin)->post(route('admin.notifications.read'), [
            'notification_key' => $information['key'],
            'redirect_url' => route('admin.dashboard'),
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertTrue(app(AdminActionCenter::class)->actions($admin, 20)->contains('key', $actionKey));
        $this->assertDatabaseHas('admin_notification_reads', [
            'user_id' => $admin->id,
            'notification_key' => $information['key'],
        ]);
        $this->assertDatabaseMissing('admin_notification_reads', [
            'user_id' => $admin->id,
            'notification_key' => $actionKey,
        ]);
    }

    public function test_read_all_marks_only_information_keys(): void
    {
        $admin = $this->admin(User::ADMIN_ROLE_SUPER_ADMIN);
        $this->signedHppInformation($admin, 'INFO-ALL');
        $order = $this->order($admin, 'ACTION-ALL');

        $this->actingAs($admin)->post(route('admin.notifications.read-all'))->assertRedirect();

        $informationKeys = AdminNotificationRead::query()->where('user_id', $admin->id)->pluck('notification_key');
        $this->assertCount(1, $informationKeys);
        $this->assertStringStartsWith('hpp-signature:', $informationKeys->first());
        $this->assertFalse($informationKeys->contains('order-sow:'.$order->id));
    }

    private function admin(string $adminRole): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => $adminRole,
        ]);
    }

    private function signedHppInformation(User $creator, string $number): void
    {
        $order = $this->order($creator, $number);
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
            'created_by' => $creator->id,
        ]);
        $token = Str::random(48);

        HppSignature::query()->create([
            'hpp_id' => $hpp->id,
            'step_order' => 1,
            'role_key' => 'manager_peminta',
            'role_label' => 'Manager Peminta',
            'signer_user_id' => $creator->id,
            'signer_name_snapshot' => $creator->name,
            'signer_position_snapshot' => 'Manager Peminta',
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'token_expires_at' => now()->addDay(),
            'status' => HppSignature::STATUS_SIGNED,
            'signed_at' => now(),
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
