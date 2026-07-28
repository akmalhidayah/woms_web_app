<?php

namespace Tests\Feature\Admin\Maintenance;

use App\Jobs\Maintenance\RunMaintenanceScan;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\User;
use App\Services\Maintenance\MaintenanceScanService;
use App\Services\Maintenance\MaintenanceSnapshotRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MaintenanceScanJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_job_detects_in_review_hpp_without_pending_signature_and_stores_snapshot(): void
    {
        config(['cache.default' => 'array']);
        $user = User::factory()->create();
        $order = $this->order($user);
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

        app(RunMaintenanceScan::class, ['mode' => 'quick'])->handle(
            app(MaintenanceScanService::class),
            app(MaintenanceSnapshotRepository::class)
        );

        $snapshot = app(MaintenanceSnapshotRepository::class)->snapshot('quick');

        $this->assertSame('completed', $snapshot['status']);
        $this->assertContains(
            'hpp_in_review_without_pending_signature',
            collect($snapshot['categories']['approval'])->pluck('code')->all()
        );
        $this->assertSame(Hpp::STATUS_IN_REVIEW, $hpp->fresh()->status);
        $this->assertSame('completed', app(MaintenanceSnapshotRepository::class)->status()['status']);
    }

    public function test_lock_prevents_second_scan(): void
    {
        config(['cache.default' => 'array']);
        $lock = Cache::lock(MaintenanceSnapshotRepository::LOCK_KEY, 60);
        $this->assertTrue($lock->get());

        try {
            app(RunMaintenanceScan::class, ['mode' => 'quick'])->handle(
                app(MaintenanceScanService::class),
                app(MaintenanceSnapshotRepository::class)
            );

            $this->assertSame('skipped', app(MaintenanceSnapshotRepository::class)->status()['status']);
        } finally {
            $lock->release();
        }
    }

    private function order(User $user): Order
    {
        return Order::query()->create([
            'nomor_order' => 'MAINT-'.uniqid(),
            'nama_pekerjaan' => 'Maintenance Test',
            'unit_kerja' => 'Workshop',
            'seksi' => 'Machine Workshop',
            'deskripsi' => 'Fixture maintenance',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addDays(10)->toDateString(),
            'created_by' => $user->id,
        ]);
    }
}
