<?php

namespace Tests\Feature\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderUniquenessCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_existing_order_number_and_notification_number(): void
    {
        $admin = $this->superAdmin();
        $this->order(['nomor_order' => '17050441', 'notifikasi' => '300001999744']);

        $this->actingAs($admin)
            ->getJson(route('admin.orders.check-unique', ['field' => 'nomor_order', 'value' => '17050441']))
            ->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('message', 'Nomor order ini sudah dibuat.');

        $this->actingAs($admin)
            ->getJson(route('admin.orders.check-unique', ['field' => 'notifikasi', 'value' => '300001999744']))
            ->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('message', 'Nomor notifikasi ini sudah dipakai pada order 17050441.');
    }

    public function test_reports_available_numbers_as_not_existing(): void
    {
        $admin = $this->superAdmin();
        $this->order(['nomor_order' => '17050441']);

        $this->actingAs($admin)
            ->getJson(route('admin.orders.check-unique', ['field' => 'nomor_order', 'value' => '17050442']))
            ->assertOk()
            ->assertJsonPath('exists', false)
            ->assertJsonPath('message', null);
    }

    public function test_value_is_trimmed_before_comparison(): void
    {
        $admin = $this->superAdmin();
        $this->order(['nomor_order' => '17050441']);

        $this->actingAs($admin)
            ->getJson(route('admin.orders.check-unique', ['field' => 'nomor_order', 'value' => '  17050441  ']))
            ->assertOk()
            ->assertJsonPath('exists', true);
    }

    public function test_editing_an_order_does_not_flag_its_own_numbers(): void
    {
        $admin = $this->superAdmin();
        $this->order(['nomor_order' => '17050441', 'notifikasi' => '300001999744']);
        $this->order(['nomor_order' => '17050442', 'notifikasi' => '300001999745']);

        $this->actingAs($admin)
            ->getJson(route('admin.orders.check-unique', ['field' => 'notifikasi', 'value' => '300001999744', 'ignore' => '17050441']))
            ->assertOk()
            ->assertJsonPath('exists', false);

        $this->actingAs($admin)
            ->getJson(route('admin.orders.check-unique', ['field' => 'notifikasi', 'value' => '300001999745', 'ignore' => '17050441']))
            ->assertOk()
            ->assertJsonPath('exists', true);
    }

    public function test_rejects_unknown_fields_and_missing_values(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->getJson(route('admin.orders.check-unique', ['field' => 'catatan_status', 'value' => 'x']))
            ->assertUnprocessable();
        $this->actingAs($admin)
            ->getJson(route('admin.orders.check-unique', ['field' => 'nomor_order']))
            ->assertUnprocessable();
    }

    public function test_only_admins_can_check(): void
    {
        $this->getJson(route('admin.orders.check-unique', ['field' => 'nomor_order', 'value' => '1']))->assertUnauthorized();

        foreach ([User::ROLE_USER, User::ROLE_PKM, User::ROLE_APPROVER] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->getJson(route('admin.orders.check-unique', ['field' => 'nomor_order', 'value' => '1']))
                ->assertForbidden();
        }
    }

    public function test_order_modals_include_the_check_script(): void
    {
        $admin = $this->superAdmin();

        foreach ([route('admin.orders.index'), route('admin.orders.workshop.index')] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()->assertSee(route('admin.orders.check-unique'), false);
        }
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);
    }

    private function order(array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'nomor_order' => '17050000',
            'notifikasi' => null,
            'nama_pekerjaan' => 'Pekerjaan Uji',
            'unit_kerja' => 'Workshop',
            'seksi' => 'Workshop Machine',
            'deskripsi' => 'Deskripsi',
            'prioritas' => Order::PRIORITY_LOW,
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addDays(7)->toDateString(),
        ], $overrides));
    }
}
