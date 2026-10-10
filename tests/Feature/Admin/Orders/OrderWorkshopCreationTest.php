<?php

namespace Tests\Feature\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Http\Controllers\Admin\Orders\OrderWorkshopController;
use App\Http\Requests\Admin\Orders\StoreOrderRequest;
use App\Models\Department;
use App\Models\Order;
use App\Models\UnitWork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class OrderWorkshopCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $department = Department::query()->create(['name' => 'Department Create Bengkel']);
        $unit = UnitWork::query()->create(['department_id' => $department->id, 'name' => 'Unit Create Bengkel']);
        $section = $unit->sections()->create(['name' => 'Seksi Create Bengkel']);
        $this->payload = [
            'nomor_order' => 'WORKSHOP-CREATE-001',
            'notifikasi' => 'NOTIF-CREATE-001',
            'nama_pekerjaan' => 'Pekerjaan Bengkel',
            'unit_kerja' => $unit->name,
            'seksi' => $section->name,
            'deskripsi' => 'Order pekerjaan bengkel',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => '2026-10-10',
            'target_selesai' => '2026-10-20',
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
            'catatan' => Order::WORKSHOP_REGU_FABRIKASI,
        ];
        $this->actingAs($this->admin);
    }

    public function test_optional_pic_is_saved_for_workshop_without_assigning_workshop_workers(): void
    {
        $this->post(route('admin.orders.workshop.store'), [...$this->payload, 'pic_user' => 'PIC Peminta'])
            ->assertRedirect(route('admin.orders.workshop.index', ['search' => $this->payload['nomor_order']]))
            ->assertSessionHas('work_package_order');

        $order = Order::query()->where('nomor_order', $this->payload['nomor_order'])->firstOrFail();
        $this->assertSame('PIC Peminta', $order->orderWorkshop->pic_user);
        $this->assertSame([], $order->bengkelTasks()->firstOrFail()->person_in_charge);

        $this->get(route('admin.orders.workshop.index'))->assertOk()
            ->assertSee('id="createPicUser"', false)
            ->assertSee('id="editPicUser"', false)
            ->assertSee('data-pic-user="PIC Peminta"', false);
        $this->get(route('admin.orders.index'))->assertOk()->assertDontSee('name="pic_user"', false);
    }

    public function test_optional_pic_does_not_expand_workshop_create_access(): void
    {
        $this->app['auth']->forgetGuards();
        $this->post(route('admin.orders.workshop.store'), [...$this->payload, 'pic_user' => 'PIC'])
            ->assertRedirect(route('login'));

        foreach ([User::ROLE_USER, User::ROLE_APPROVER, User::ROLE_PKM, User::ROLE_INSPECTOR] as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $this->actingAs($actor)->post(route('admin.orders.workshop.store'), [...$this->payload, 'pic_user' => 'PIC'])
                ->assertForbidden();
        }
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_pic_can_be_omitted_updated_preserved_and_cleared(): void
    {
        $this->post(route('admin.orders.workshop.store'), $this->payload)->assertSessionHasNoErrors();
        $order = Order::query()->where('nomor_order', $this->payload['nomor_order'])->firstOrFail();
        $this->assertNull($order->orderWorkshop->pic_user);

        $this->put(route('admin.orders.update', $order), [...$this->payload, 'pic_user' => 'PIC Baru'])
            ->assertSessionHasNoErrors();
        $this->assertSame('PIC Baru', $order->fresh()->orderWorkshop->pic_user);

        $this->put(route('admin.orders.update', $order), $this->payload)->assertSessionHasNoErrors();
        $this->assertSame('PIC Baru', $order->fresh()->orderWorkshop->pic_user);

        $this->put(route('admin.orders.update', $order), [...$this->payload, 'pic_user' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($order->fresh()->orderWorkshop->pic_user);
    }

    public function test_pic_is_rejected_on_service_order_create_and_update(): void
    {
        $servicePayload = [...$this->payload, 'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value, 'catatan' => null];
        $this->post(route('admin.orders.store'), [...$servicePayload, 'pic_user' => 'PIC Tidak Sah'])
            ->assertSessionHasErrors('pic_user');
        $this->assertDatabaseCount('orders', 0);

        $this->post(route('admin.orders.store'), $servicePayload)->assertSessionHasNoErrors();
        $order = Order::query()->firstOrFail();
        $this->put(route('admin.orders.update', $order), [...$servicePayload, 'pic_user' => 'PIC Tidak Sah'])
            ->assertSessionHasErrors('pic_user');
        $this->assertDatabaseCount('order_workshops', 0);
    }

    public function test_legacy_workshop_without_lifecycle_can_save_optional_pic(): void
    {
        $order = Order::query()->create([...$this->payload, 'created_by' => $this->admin->id]);
        $this->assertNull($order->orderWorkshop);

        $this->put(route('admin.orders.update', $order), [...$this->payload, 'pic_user' => 'PIC Legacy'])
            ->assertSessionHasNoErrors();
        $this->assertSame('PIC Legacy', $order->fresh()->orderWorkshop->pic_user);
    }

    public function test_pic_length_type_and_workshop_regu_are_validated(): void
    {
        foreach ([str_repeat('x', 256), ['not a name']] as $pic) {
            $this->post(route('admin.orders.workshop.store'), [...$this->payload, 'pic_user' => $pic])
                ->assertSessionHasErrors('pic_user');
        }
        foreach ([null, 'Regu tidak terdaftar'] as $regu) {
            $this->post(route('admin.orders.workshop.store'), [...$this->payload, 'catatan' => $regu])
                ->assertSessionHasErrors('catatan');
        }
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_uniqueness_conflicts_after_validation_return_field_errors_without_partial_records(): void
    {
        Order::query()->create([...$this->payload, 'created_by' => $this->admin->id]);

        // Bypass the already-completed validation to model another request committing first.
        foreach ([
            'nomor_order' => [...$this->payload, 'notifikasi' => 'NOTIF-OTHER'],
            'notifikasi' => [...$this->payload, 'nomor_order' => 'WORKSHOP-OTHER'],
        ] as $field => $validated) {
            $request = Mockery::mock(StoreOrderRequest::class);
            $request->shouldReceive('validated')->once()->andReturn($validated);
            $request->shouldReceive('user')->once()->andReturn($this->admin);

            try {
                app(OrderWorkshopController::class)->store($request);
                $this->fail('Expected a uniqueness validation error.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey($field, $exception->errors());
            }
        }

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_workshops', 0);
        $this->assertDatabaseCount('bengkel_tasks', 0);
    }
}
