<?php

namespace Tests\Feature\Admin\Maintenance;

use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\User;
use App\Services\Maintenance\Evaluators\FileStorageHealthEvaluator;
use App\Services\Maintenance\MaintenanceSnapshotRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaintenanceDeepScanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::clear();
    }

    public function test_deep_scan_reports_missing_file_without_deleting_existing_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('orders/existing.pdf', 'pdf');
        $user = User::factory()->create();
        $order = Order::query()->create([
            'nomor_order' => 'MAINT-FILE-001',
            'nama_pekerjaan' => 'Maintenance File Test',
            'unit_kerja' => 'Workshop',
            'seksi' => 'Machine Workshop',
            'deskripsi' => 'Fixture maintenance',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addDays(10)->toDateString(),
            'created_by' => $user->id,
        ]);
        OrderDocument::query()->create([
            'order_id' => $order->id,
            'jenis_dokumen' => 'abnormalitas',
            'nama_file_asli' => 'missing.pdf',
            'path_file' => 'orders/missing.pdf',
            'uploaded_by' => $user->id,
            'uploaded_at' => now(),
        ]);

        $findings = app(FileStorageHealthEvaluator::class)->evaluate('deep');

        $this->assertContains('recorded_file_missing', collect($findings)->pluck('code')->all());
        $this->assertStringNotContainsString(storage_path(), json_encode($findings));
        Storage::disk('public')->assertExists('orders/existing.pdf');
    }

    public function test_deep_scan_runs_in_ordered_requests_without_queue_and_finalizes_snapshot(): void
    {
        Queue::fake();
        $admin = $this->superAdmin();

        $started = $this->actingAs($admin)
            ->postJson(route('admin.maintenance.scan.deep.start'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('current_step', 'system')
            ->json();

        $this->assertSame(48, strlen($started['scan_id']));
        $this->assertNotNull(app(MaintenanceSnapshotRepository::class)->deepContext($started['scan_id']));

        $step = $started['current_step'];
        do {
            $result = $this->actingAs($admin)
                ->postJson(route('admin.maintenance.scan.deep.step'), [
                    'scan_id' => $started['scan_id'],
                    'step' => $step,
                ])
                ->assertOk()
                ->assertJsonPath('success', true)
                ->json();
            $step = $result['next_step'];
        } while (! $result['finished']);

        $this->actingAs($admin)
            ->postJson(route('admin.maintenance.scan.deep.finalize'), [
                'scan_id' => $started['scan_id'],
            ])
            ->assertOk()
            ->assertJsonPath('progress', 100);

        Queue::assertNothingPushed();
        $repository = app(MaintenanceSnapshotRepository::class);
        $this->assertSame('completed', $repository->status()['status']);
        $this->assertSame('completed', $repository->snapshot('deep')['status']);
        $this->assertNull($repository->deepContext($started['scan_id']));

        $lock = Cache::lock(MaintenanceSnapshotRepository::LOCK_KEY, 10);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_deep_scan_rejects_skipped_step_and_different_owner(): void
    {
        $owner = $this->superAdmin();
        $otherAdmin = $this->superAdmin();
        $started = $this->actingAs($owner)
            ->postJson(route('admin.maintenance.scan.deep.start'))
            ->assertOk()
            ->json();

        $this->actingAs($owner)
            ->postJson(route('admin.maintenance.scan.deep.step'), [
                'scan_id' => $started['scan_id'],
                'step' => 'documents',
            ])
            ->assertUnprocessable();

        $this->actingAs($otherAdmin)
            ->postJson(route('admin.maintenance.scan.deep.step'), [
                'scan_id' => $started['scan_id'],
                'step' => 'system',
            ])
            ->assertForbidden();

        $this->actingAs($owner)
            ->postJson(route('admin.maintenance.scan.deep.cancel'), [
                'scan_id' => $started['scan_id'],
            ])
            ->assertOk();
    }

    public function test_deep_scan_cannot_be_finalized_before_all_steps_finish(): void
    {
        $admin = $this->superAdmin();
        $started = $this->actingAs($admin)
            ->postJson(route('admin.maintenance.scan.deep.start'))
            ->assertOk()
            ->json();

        $this->actingAs($admin)
            ->postJson(route('admin.maintenance.scan.deep.finalize'), [
                'scan_id' => $started['scan_id'],
            ])
            ->assertUnprocessable();

        $this->actingAs($admin)
            ->postJson(route('admin.maintenance.scan.deep.cancel'), [
                'scan_id' => $started['scan_id'],
            ])
            ->assertOk();
    }

    public function test_expired_deep_scan_context_is_handled_safely(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.maintenance.scan.deep.step'), [
                'scan_id' => str_repeat('a', 48),
                'step' => 'system',
            ])
            ->assertStatus(410)
            ->assertJsonPath('message', 'Sesi pemeriksaan sudah berakhir atau tidak ditemukan.');
    }

    public function test_cancel_releases_lock_and_second_active_scan_is_rejected(): void
    {
        Queue::fake();
        $admin = $this->superAdmin();
        $started = $this->actingAs($admin)
            ->postJson(route('admin.maintenance.scan.deep.start'))
            ->assertOk()
            ->json();

        $this->actingAs($admin)
            ->postJson(route('admin.maintenance.scan.deep.start'))
            ->assertStatus(409);

        $this->actingAs($admin)
            ->postJson(route('admin.maintenance.scan.deep.cancel'), [
                'scan_id' => $started['scan_id'],
            ])
            ->assertOk();

        Queue::assertNothingPushed();
        $this->assertSame('cancelled', app(MaintenanceSnapshotRepository::class)->status()['status']);

        $lock = Cache::lock(MaintenanceSnapshotRepository::LOCK_KEY, 10);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_regular_admin_cannot_use_deep_scan_endpoints(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.maintenance.scan.deep.start'))
            ->assertForbidden();
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }
}
