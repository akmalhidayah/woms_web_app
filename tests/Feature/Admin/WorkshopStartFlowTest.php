<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelTask;
use App\Models\Department;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkshopStartFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private UnitWork $unit;

    private UnitWorkSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $department = Department::query()->create(['name' => 'Department Start Bengkel']);
        $this->unit = UnitWork::query()->create([
            'department_id' => $department->id,
            'name' => 'Unit Start Bengkel',
        ]);
        $this->section = UnitWorkSection::query()->create([
            'unit_work_id' => $this->unit->id,
            'name' => 'Seksi Start Bengkel',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_new_workshop_order_waits_for_start_without_timestamp(): void
    {
        $this->assertTrue(Schema::hasColumn('order_workshops', 'started_at'));

        $this->actingAs($this->admin)
            ->post(route('admin.orders.workshop.store'), $this->storePayload())
            ->assertRedirect();

        $order = Order::query()->where('nomor_order', 'WORKSHOP-START-NEW-001')->firstOrFail();

        $this->assertDatabaseHas('order_workshops', [
            'order_id' => $order->id,
            'progress_status' => OrderWorkshop::PROGRESS_MENUNGGU_JADWAL,
            'started_at' => null,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.workshop.index', ['search' => $order->nomor_order]))
            ->assertOk()
            ->assertSee('Start Pekerjaan')
            ->assertSee('data-start-url="'.route('admin.orders.workshop.start', $order).'"', false)
            ->assertSee('data-field="progress_status" disabled', false);
    }

    public function test_start_records_first_timestamp_changes_progress_and_is_idempotent(): void
    {
        [$order, $workshop, $task] = $this->workshopOrder();
        Carbon::setTestNow('2026-09-04 08:47:00');

        $this->actingAs($this->admin)
            ->patchJson(route('admin.orders.workshop.start', $order))
            ->assertOk()
            ->assertJsonPath('message', 'Pekerjaan berhasil dimulai.');

        $workshop->refresh();
        $this->assertSame(OrderWorkshop::PROGRESS_IN_PROGRESS, $workshop->progress_status);
        $this->assertSame('2026-09-04 08:47:00', $workshop->started_at?->format('Y-m-d H:i:s'));
        $this->assertSame(OrderWorkshop::PROGRESS_IN_PROGRESS, $task->refresh()->progress_status);

        Carbon::setTestNow('2026-09-04 09:30:00');

        $this->actingAs($this->admin)
            ->patchJson(route('admin.orders.workshop.start', $order))
            ->assertOk()
            ->assertJsonPath('message', 'Pekerjaan sudah dimulai.');

        $this->assertSame('2026-09-04 08:47:00', $workshop->refresh()->started_at?->format('Y-m-d H:i:s'));

        $this->actingAs($this->admin)
            ->get(route('admin.orders.workshop.index', ['search' => $order->nomor_order]))
            ->assertOk()
            ->assertSee('Mulai: 04-09-2026 08:47')
            ->assertDontSee('data-start-url="'.route('admin.orders.workshop.start', $order).'"', false);
    }

    public function test_regular_order_progress_update_cannot_bypass_or_reverse_start(): void
    {
        [$order, $workshop] = $this->workshopOrder();

        $this->actingAs($this->admin)
            ->patchJson(route('admin.orders.workshop.update', $order), [
                'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('progress_status');

        $this->assertSame(OrderWorkshop::PROGRESS_MENUNGGU_JADWAL, $workshop->refresh()->progress_status);
        $this->assertNull($workshop->started_at);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.orders.workshop.start', $order))
            ->assertOk();

        $this->actingAs($this->admin)
            ->patchJson(route('admin.orders.workshop.update', $order), [
                'progress_status' => OrderWorkshop::PROGRESS_MENUNGGU_JADWAL,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('progress_status');

        $this->assertSame(OrderWorkshop::PROGRESS_IN_PROGRESS, $workshop->refresh()->progress_status);
    }

    public function test_display_start_records_timestamp_and_blocks_other_pre_start_progress(): void
    {
        [$order, $workshop, $task] = $this->workshopOrder();
        Carbon::setTestNow('2026-09-04 10:15:00');

        $this->actingAs($this->admin)
            ->patch(route('admin.bengkel-tasks.progress.update', $task), [
                'progress_status' => OrderWorkshop::PROGRESS_PENDING,
                'pending_reason' => 'Menunggu material',
            ])
            ->assertSessionHasErrors('progress_status');

        $this->assertNull($workshop->refresh()->started_at);

        $this->actingAs($this->admin)
            ->patch(route('admin.bengkel-tasks.progress.update', $task), [
                'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
            ])
            ->assertSessionDoesntHaveErrors();

        $workshop->refresh();
        $this->assertSame(OrderWorkshop::PROGRESS_IN_PROGRESS, $workshop->progress_status);
        $this->assertSame('2026-09-04 10:15:00', $workshop->started_at?->format('Y-m-d H:i:s'));
    }

    public function test_new_manual_display_progress_gets_timestamp_but_legacy_progress_stays_null(): void
    {
        Carbon::setTestNow('2026-09-04 11:20:00');

        $this->actingAs($this->admin)
            ->post(route('admin.bengkel-tasks.store'), [
                'nomor_order' => 'WORKSHOP-DISPLAY-START-001',
                'job_name' => 'Pekerjaan Display Berjalan',
                'progress_status' => OrderWorkshop::PROGRESS_PENDING,
                'pending_reason' => 'Menunggu alat',
            ])
            ->assertRedirect();

        $manualOrder = Order::query()->where('nomor_order', 'WORKSHOP-DISPLAY-START-001')->firstOrFail();
        $this->assertSame('2026-09-04 11:20:00', $manualOrder->orderWorkshop?->started_at?->format('Y-m-d H:i:s'));

        [$legacyOrder, $legacyWorkshop] = $this->workshopOrder(
            OrderWorkshop::PROGRESS_IN_PROGRESS,
            null,
            'WORKSHOP-START-LEGACY-001',
        );

        $this->actingAs($this->admin)
            ->get(route('admin.orders.workshop.index', ['search' => $legacyOrder->nomor_order]))
            ->assertOk()
            ->assertSee('Waktu mulai belum tercatat')
            ->assertDontSee('data-start-url="'.route('admin.orders.workshop.start', $legacyOrder).'"', false);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.orders.workshop.start', $legacyOrder))
            ->assertUnprocessable();

        $this->assertNull($legacyWorkshop->refresh()->started_at);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.orders.workshop.update', $legacyOrder), [
                'progress_status' => OrderWorkshop::PROGRESS_PENDING,
            ])
            ->assertOk();

        $this->assertNull($legacyWorkshop->refresh()->started_at);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.orders.workshop.update', $legacyOrder), [
                'progress_status' => OrderWorkshop::PROGRESS_MENUNGGU_JADWAL,
            ])
            ->assertUnprocessable();

        $this->assertNull($legacyWorkshop->refresh()->started_at);
    }

    public function test_start_rejects_non_workshop_order_and_missing_workshop_data(): void
    {
        [$nonWorkshopOrder] = $this->workshopOrder();
        $nonWorkshopOrder->update(['catatan_status' => OrderUserNoteStatus::ApprovedJasa->value]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.orders.workshop.start', $nonWorkshopOrder))
            ->assertUnprocessable();

        $missingWorkshopOrder = $this->baseOrder('WORKSHOP-START-MISSING-001');

        $this->actingAs($this->admin)
            ->patchJson(route('admin.orders.workshop.start', $missingWorkshopOrder))
            ->assertUnprocessable();
    }

    /**
     * @return array{Order, OrderWorkshop, BengkelTask}
     */
    private function workshopOrder(
        string $progress = OrderWorkshop::PROGRESS_MENUNGGU_JADWAL,
        ?string $startedAt = null,
        string $number = 'WORKSHOP-START-001',
    ): array {
        $order = $this->baseOrder($number);
        $workshop = $order->orderWorkshop()->create([
            'progress_status' => $progress,
            'started_at' => $startedAt,
        ]);
        $task = BengkelTask::query()->create([
            'order_id' => $order->id,
            'job_name' => $order->nama_pekerjaan,
            'progress_status' => $progress,
            'is_completed' => $progress === OrderWorkshop::PROGRESS_DONE,
        ]);

        return [$order->fresh('orderWorkshop'), $workshop, $task];
    }

    private function baseOrder(string $number): Order
    {
        return Order::query()->create([
            'nomor_order' => $number,
            'nama_pekerjaan' => 'Pekerjaan Start Bengkel',
            'unit_kerja' => $this->unit->name,
            'seksi' => $this->section->name,
            'deskripsi' => 'Pengujian Start Pekerjaan Bengkel',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => '2026-09-04',
            'target_selesai' => '2026-09-10',
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
            'catatan' => Order::WORKSHOP_REGU_FABRIKASI,
            'created_by' => $this->admin->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function storePayload(): array
    {
        return [
            'nomor_order' => 'WORKSHOP-START-NEW-001',
            'notifikasi' => 'NOTIF-START-NEW-001',
            'nama_pekerjaan' => 'Pekerjaan Baru Menunggu Start',
            'unit_kerja' => $this->unit->name,
            'seksi' => $this->section->name,
            'deskripsi' => 'Order pekerjaan bengkel',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => '2026-09-04',
            'target_selesai' => '2026-09-10',
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
            'catatan' => Order::WORKSHOP_REGU_FABRIKASI,
            'biaya' => null,
        ];
    }
}
