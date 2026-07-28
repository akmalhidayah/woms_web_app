<?php

namespace Tests\Feature\Admin\Maintenance;

use App\Models\User;
use App\Services\Maintenance\MaintenanceScanService;
use App\Services\Maintenance\MaintenanceSnapshotRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class MaintenanceQuickScanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::clear();
    }

    public function test_quick_scan_runs_synchronously_stores_snapshot_and_releases_lock(): void
    {
        Queue::fake();
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post(route('admin.maintenance.scan.quick'))
            ->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('success', 'Pemeriksaan cepat berhasil diselesaikan.');

        Queue::assertNothingPushed();
        $repository = app(MaintenanceSnapshotRepository::class);
        $this->assertSame('completed', $repository->status()['status']);
        $this->assertSame('completed', $repository->snapshot('quick')['status']);

        $lock = Cache::lock(MaintenanceSnapshotRepository::LOCK_KEY, 10);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_quick_scan_failure_marks_failed_and_releases_lock(): void
    {
        Queue::fake();
        $admin = $this->superAdmin();
        $scanner = $this->mock(MaintenanceScanService::class);
        $scanner->shouldReceive('scan')
            ->once()
            ->with('quick')
            ->andThrow(new RuntimeException('internal detail'));

        $this->actingAs($admin)
            ->post(route('admin.maintenance.scan.quick'))
            ->assertRedirect()
            ->assertSessionHas('error', 'Pemeriksaan cepat gagal dijalankan. Silakan periksa log aplikasi.');

        Queue::assertNothingPushed();
        $this->assertSame('failed', app(MaintenanceSnapshotRepository::class)->status()['status']);

        $lock = Cache::lock(MaintenanceSnapshotRepository::LOCK_KEY, 10);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_existing_scan_lock_rejects_quick_scan(): void
    {
        Queue::fake();
        $lock = Cache::lock(MaintenanceSnapshotRepository::LOCK_KEY, 60);
        $this->assertTrue($lock->get());

        try {
            $this->actingAs($this->superAdmin())
                ->post(route('admin.maintenance.scan.quick'))
                ->assertRedirect()
                ->assertSessionHas('error', 'Pemeriksaan lain sedang berjalan. Tunggu hingga pemeriksaan tersebut selesai.');

            Queue::assertNothingPushed();
            $this->assertNull(app(MaintenanceSnapshotRepository::class)->snapshot('quick'));
        } finally {
            $lock->release();
        }
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }
}
