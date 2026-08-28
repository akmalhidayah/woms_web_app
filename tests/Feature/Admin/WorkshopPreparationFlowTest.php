<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelTask;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\User;
use App\Support\WorkshopReadiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkshopPreparationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_exposes_only_the_new_preparation_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('order_workshops', 'preparation_status'));
        $this->assertTrue(Schema::hasColumn('order_workshops', 'preparation_note'));

        foreach ([
            'konfirmasi_anggaran',
            'keterangan_konfirmasi',
            'status_anggaran',
            'keterangan_anggaran',
            'status_material',
            'keterangan_material',
            'nomor_e_korin',
            'status_e_korin',
        ] as $column) {
            $this->assertFalse(Schema::hasColumn('order_workshops', $column), $column.' masih tersedia.');
        }
    }

    public function test_all_preparation_values_can_be_saved_and_display_reads_the_same_row(): void
    {
        $admin = $this->superAdmin();
        [$order, $task] = $this->workshopOrder($admin);

        foreach (array_keys(OrderWorkshop::preparationOptions()) as $status) {
            $this->actingAs($admin)
                ->patchJson(route('admin.orders.workshop.update', $order), [
                    'preparation_status' => $status,
                ])
                ->assertOk();

            $this->assertDatabaseHas('order_workshops', [
                'order_id' => $order->id,
                'preparation_status' => $status,
            ]);
        }

        $this->actingAs($admin)
            ->patch(route('admin.bengkel-tasks.preparation.update', $task), [
                'preparation_status' => OrderWorkshop::PREPARATION_WAITING_MATERIAL,
            ])
            ->assertRedirect(route('admin.bengkel-tasks.index'));

        $this->assertDatabaseHas('order_workshops', [
            'order_id' => $order->id,
            'preparation_status' => OrderWorkshop::PREPARATION_WAITING_MATERIAL,
        ]);
    }

    public function test_qc_and_done_require_completed_preparation_but_sementara_proses_does_not(): void
    {
        $admin = $this->superAdmin();
        [$order] = $this->workshopOrder($admin, OrderWorkshop::PROGRESS_IN_PROGRESS);
        $this->assertFalse(app(WorkshopReadiness::class)->canAdvance($order->orderWorkshop));

        foreach ([OrderWorkshop::PROGRESS_QUALITY_CONTROL, OrderWorkshop::PROGRESS_DONE] as $progress) {
            $this->actingAs($admin)
                ->patchJson(route('admin.orders.workshop.update', $order), [
                    'progress_status' => $progress,
                ])
                ->assertStatus(422)
                ->assertJsonPath('errors.progress_status.0', 'Persiapan Order harus diselesaikan sebelum progress dapat dilanjutkan ke Quality Control atau Selesai.');
        }

        $this->actingAs($admin)
            ->patchJson(route('admin.orders.workshop.update', $order), [
                'preparation_status' => OrderWorkshop::PREPARATION_COMPLETED,
                'progress_status' => OrderWorkshop::PROGRESS_QUALITY_CONTROL,
            ])
            ->assertOk();
    }

    public function test_preparation_is_locked_after_qc_progress(): void
    {
        $admin = $this->superAdmin();
        [$order] = $this->workshopOrder($admin, OrderWorkshop::PROGRESS_QUALITY_CONTROL, true);

        $this->actingAs($admin)
            ->patchJson(route('admin.orders.workshop.update', $order), [
                'preparation_status' => OrderWorkshop::PREPARATION_WAITING_MATERIAL,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('preparation_status');

        $this->assertDatabaseHas('order_workshops', [
            'order_id' => $order->id,
            'preparation_status' => OrderWorkshop::PREPARATION_COMPLETED,
        ]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }

    /** @return array{Order, BengkelTask} */
    private function workshopOrder(User $admin, string $progress = OrderWorkshop::PROGRESS_IN_PROGRESS, bool $completed = false): array
    {
        $order = Order::query()->create([
            'nomor_order' => 'PREPARATION-'.uniqid(),
            'nama_pekerjaan' => 'Pekerjaan Bengkel Test',
            'unit_kerja' => 'Workshop',
            'seksi' => 'Machine Workshop',
            'deskripsi' => 'Test',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addDay()->toDateString(),
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
            'catatan' => 'Regu Fabrikasi',
            'created_by' => $admin->id,
        ]);
        $order->orderWorkshop()->create([
            'progress_status' => $progress,
            'preparation_status' => $completed ? OrderWorkshop::PREPARATION_COMPLETED : null,
        ]);
        $task = BengkelTask::query()->create([
            'order_id' => $order->id,
            'job_name' => $order->nama_pekerjaan,
            'progress_status' => $progress,
            'is_completed' => $progress === OrderWorkshop::PROGRESS_DONE,
        ]);

        return [$order->fresh('orderWorkshop'), $task];
    }
}
