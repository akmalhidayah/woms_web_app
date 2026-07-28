<?php

namespace Tests\Feature\Admin\Maintenance;

use App\Jobs\Maintenance\RunMaintenanceScan;
use App\Models\Hpp;
use App\Models\User;
use App\Services\Maintenance\MaintenanceSnapshotRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class MaintenancePageReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_reads_cached_snapshot_and_does_not_dispatch_scan(): void
    {
        Bus::fake();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $before = Hpp::query()->count();
        app(MaintenanceSnapshotRepository::class)->storeSnapshot('quick', $this->snapshot());

        $response = $this->actingAs($admin)
            ->get(route('admin.maintenance.index'))
            ->assertOk()
            ->assertSee('Maintenance Sistem')
            ->assertSee('Masalah Kritis')
            ->assertSee('Perlu Diperiksa')
            ->assertSee('File &amp; Storage', false)
            ->assertDontSee('setInterval', false)
            ->assertDontSee('wire:poll', false);

        $this->assertSame($before, Hpp::query()->count());
        Bus::assertNotDispatched(RunMaintenanceScan::class);
        $this->assertSame(1, substr_count($response->getContent(), 'data-maintenance-page'));
    }

    public function test_scan_buttons_only_queue_jobs(): void
    {
        Bus::fake();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.maintenance.scan.quick'))
            ->assertRedirect();

        Bus::assertDispatched(
            RunMaintenanceScan::class,
            fn (RunMaintenanceScan $job): bool => $job->mode === 'quick' && $job->triggeredBy === $admin->id
        );
    }

    private function snapshot(): array
    {
        return [
            'status' => 'completed',
            'mode' => 'quick',
            'started_at' => now()->toIso8601String(),
            'completed_at' => now()->toIso8601String(),
            'duration_ms' => 1,
            'summary' => ['critical' => 0, 'warning' => 0, 'info' => 0, 'total' => 0],
            'categories' => [
                'system' => [], 'approval' => [], 'documents' => [], 'files' => [],
                'users_structure' => [], 'queue_scheduler' => [],
            ],
        ];
    }
}
