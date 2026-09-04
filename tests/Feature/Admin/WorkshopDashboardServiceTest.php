<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelTask;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\User;
use App\Models\WorkshopHandover;
use App\Services\Admin\WorkshopDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WorkshopDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_metrics_use_completed_handover_and_ignore_display_archive_state(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');
        $user = User::factory()->create();
        $inProgress = $this->workshopOrder($user, 'WD-001', '2026-09-01', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_IN_PROGRESS, 100);
        $this->workshopOrder($user, 'WD-002', '2026-09-02', Order::WORKSHOP_REGU_REFURBISH, OrderWorkshop::PROGRESS_QUALITY_CONTROL);
        $this->workshopOrder($user, 'WD-003', '2026-09-03', Order::WORKSHOP_REGU_ESTIMATOR, OrderWorkshop::PROGRESS_DONE, 200);
        $completed = $this->workshopOrder($user, 'WD-004', '2026-09-03', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_QUALITY_CONTROL, 300);
        $this->handover($completed, $user, WorkshopHandover::STATUS_COMPLETED, '2026-09-04 09:00:00');
        $this->workshopOrder($user, 'WD-005', '2026-09-04', null, OrderWorkshop::PROGRESS_MENUNGGU_JADWAL);
        $excluded = $this->workshopOrder($user, 'WD-006', '2026-09-04', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_IN_PROGRESS);
        $excluded->update(['catatan_status' => OrderUserNoteStatus::ApprovedJasa->value]);
        BengkelTask::query()->create([
            'order_id' => $inProgress->id,
            'job_name' => $inProgress->nama_pekerjaan,
            'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
            'archived_at' => now(),
        ]);

        $dashboard = app(WorkshopDashboardService::class)->resolve(2026, 'all');

        $this->assertSame(5, $dashboard['summary']['total']);
        $this->assertSame(2, $dashboard['summary']['in_progress']);
        $this->assertSame(1, $dashboard['summary']['completed']);
        $this->assertSame(4, $dashboard['summary']['incomplete']);
        $this->assertSame(20.0, $dashboard['summary']['completion_percentage']);
        $this->assertSame(600, $dashboard['summary']['total_cost']);
        $this->assertSame(1, $dashboard['unknown_regu_count']);
        $this->assertSame(WorkshopDashboardService::COMPLETION_TARGET, $dashboard['summary']['completion_target']);

        $fabrikasi = collect($dashboard['regu'])->firstWhere('name', Order::WORKSHOP_REGU_FABRIKASI);
        $this->assertSame(2, $fabrikasi['total']);
        $this->assertSame(1, $fabrikasi['in_progress']);
        $this->assertSame(1, $fabrikasi['completed']);
    }

    public function test_year_and_month_filters_use_order_date_consistently(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');
        $user = User::factory()->create();
        $this->workshopOrder($user, 'WD-FILTER-2025', '2025-09-10', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_IN_PROGRESS, 100);
        $this->workshopOrder($user, 'WD-FILTER-AUG', '2026-08-10', Order::WORKSHOP_REGU_REFURBISH, OrderWorkshop::PROGRESS_IN_PROGRESS, 200);
        $this->workshopOrder($user, 'WD-FILTER-SEP', '2026-09-10', Order::WORKSHOP_REGU_ESTIMATOR, OrderWorkshop::PROGRESS_DONE, 300);

        $september = app(WorkshopDashboardService::class)->resolve(2026, 9);

        $this->assertSame(['year' => 2026, 'month' => 9], $september['filters']);
        $this->assertSame(1, $september['summary']['total']);
        $this->assertSame(300, $september['summary']['total_cost']);
        $this->assertSame([2026, 2025], $september['available_years']);
        $this->assertCount(1, $september['monthly_costs']);
        $this->assertSame(9, $september['monthly_costs'][0]['month']);
        $this->assertSame(300, $september['monthly_costs'][0]['amount']);

        $defaultPeriod = app(WorkshopDashboardService::class)->resolve();
        $this->assertSame(['year' => 2026, 'month' => 9], $defaultPeriod['filters']);
        $this->assertSame(1, $defaultPeriod['summary']['total']);

        $fullYear = app(WorkshopDashboardService::class)->resolve(2026, 'all');
        $this->assertCount(12, $fullYear['monthly_costs']);
        $this->assertSame(500, collect($fullYear['monthly_costs'])->sum('amount'));
    }

    public function test_cumulative_trend_uses_user_signature_time_and_omits_future_months(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');
        $user = User::factory()->create();
        $january = $this->workshopOrder($user, 'WD-TREND-JAN', '2026-01-10', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_DONE);
        $this->handover($january, $user, WorkshopHandover::STATUS_COMPLETED, '2026-03-05 08:00:00');
        $february = $this->workshopOrder($user, 'WD-TREND-FEB', '2026-02-10', Order::WORKSHOP_REGU_REFURBISH, OrderWorkshop::PROGRESS_DONE);
        $this->handover($february, $user, WorkshopHandover::STATUS_COMPLETED, null);
        $april = $this->workshopOrder($user, 'WD-TREND-APR', '2026-04-10', Order::WORKSHOP_REGU_ESTIMATOR, OrderWorkshop::PROGRESS_DONE);
        $this->handover($april, $user, WorkshopHandover::STATUS_COMPLETED, '2026-10-01 08:00:00');

        $dashboard = app(WorkshopDashboardService::class)->resolve(2026, 'all');
        $trend = collect($dashboard['trend'])->keyBy('month');

        $this->assertCount(9, $dashboard['trend']);
        $this->assertSame(0.0, $trend[1]['percentage']);
        $this->assertSame(50.0, $trend[3]['percentage']);
        $this->assertSame(33.33, $trend[4]['percentage']);
        $this->assertSame(1, $trend[9]['completed']);

        $throughJuly = app(WorkshopDashboardService::class)->resolve(2026, 7);
        $this->assertCount(7, $throughJuly['trend']);
    }

    public function test_workshop_dashboard_route_loads_workshop_data_without_financial_payload(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->workshopOrder($admin, 'WD-ROUTE', '2026-09-04', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_IN_PROGRESS, 1500);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['dashboard' => 'bengkel']))
            ->assertOk()
            ->assertViewHas('activeDashboard', 'bengkel')
            ->assertViewHas('workshopDashboard', fn (array $data): bool => $data['summary']['total'] === 1)
            ->assertViewMissing('financialSummary')
            ->assertSee('DASHBOARD PEKERJAAN BENGKEL')
            ->assertSee('Biaya Order Bengkel Per Bulan')
            ->assertDontSee('GENERAL BIAYA JASA');
    }

    private function workshopOrder(
        User $user,
        string $number,
        string $orderDate,
        ?string $regu,
        string $progress,
        ?int $cost = null,
    ): Order {
        $order = Order::query()->create([
            'nomor_order' => $number,
            'nama_pekerjaan' => 'Pekerjaan '.$number,
            'unit_kerja' => 'Unit Workshop',
            'seksi' => 'Seksi Workshop',
            'deskripsi' => 'Pekerjaan dashboard workshop',
            'prioritas' => Order::PRIORITY_LOW,
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
            'tanggal_order' => $orderDate,
            'target_selesai' => $orderDate,
            'biaya' => $cost,
            'catatan' => $regu,
            'created_by' => $user->id,
        ]);
        OrderWorkshop::query()->create([
            'order_id' => $order->id,
            'progress_status' => $progress,
        ]);

        return $order;
    }

    private function handover(Order $order, User $user, string $status, ?string $signedAt): WorkshopHandover
    {
        return WorkshopHandover::query()->create([
            'order_id' => $order->id,
            'document_no' => 'STB-'.$order->nomor_order,
            'path' => WorkshopHandover::PATH_NON_CRITICAL,
            'status' => $status,
            'handed_over_at' => $signedAt ?? '2026-09-01 08:00:00',
            'order_no_snapshot' => $order->nomor_order,
            'job_name_snapshot' => $order->nama_pekerjaan,
            'unit_snapshot' => $order->unit_kerja,
            'section_snapshot' => $order->seksi,
            'admin_user_id' => $user->id,
            'admin_name_snapshot' => $user->name,
            'admin_position_snapshot' => 'Admin Workshop',
            'admin_signature_path' => 'signatures/admin.png',
            'admin_signed_at' => '2026-09-01 08:00:00',
            'recipient_user_id' => $user->id,
            'recipient_name_snapshot' => $user->name,
            'recipient_position_snapshot' => 'Manager User',
            'user_signature_path' => $signedAt === null ? null : 'signatures/user.png',
            'user_signed_at' => $signedAt,
            'photo_paths' => [],
        ]);
    }
}
