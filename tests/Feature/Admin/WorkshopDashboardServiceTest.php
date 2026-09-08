<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelTask;
use App\Models\Hpp;
use App\Models\InitialWork;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\PurchaseOrder;
use App\Models\QualityControlReport;
use App\Models\QualityControlSignature;
use App\Models\User;
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

    public function test_metrics_use_done_for_non_critical_and_complete_approval_for_critical_orders(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');
        $user = User::factory()->create();
        $inProgress = $this->workshopOrder($user, 'WD-001', '2026-09-01', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_IN_PROGRESS, 100);
        $criticalIncomplete = $this->workshopOrder($user, 'WD-002', '2026-09-02', Order::WORKSHOP_REGU_REFURBISH, OrderWorkshop::PROGRESS_QUALITY_CONTROL);
        $incompleteReport = $this->completeQualityControl($criticalIncomplete, $user, '2026-09-04 07:00:00');
        $incompleteReport->signatures()
            ->where('role_key', QualityControlSignature::ROLE_USER_MANAGER)
            ->update(['status' => QualityControlSignature::STATUS_PENDING, 'signed_at' => null]);
        $legacyCompleted = $this->workshopOrder($user, 'WD-003', '2026-09-03', Order::WORKSHOP_REGU_ESTIMATOR, OrderWorkshop::PROGRESS_DONE, 200);
        $legacyCompleted->orderWorkshop->forceFill(['legacy_completed_at' => '2026-09-04 08:00:00'])->save();
        $this->workshopOrder($user, 'WD-004', '2026-09-03', Order::WORKSHOP_REGU_ESTIMATOR, OrderWorkshop::PROGRESS_DONE, 300);
        $criticalCompleted = $this->workshopOrder($user, 'WD-005', '2026-09-03', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_QUALITY_CONTROL, 400);
        $this->completeQualityControl($criticalCompleted, $user, '2026-09-04 09:00:00');
        $this->workshopOrder($user, 'WD-006', '2026-09-04', null, OrderWorkshop::PROGRESS_MENUNGGU_JADWAL);
        $excluded = $this->workshopOrder($user, 'WD-007', '2026-09-04', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_IN_PROGRESS);
        $excluded->update(['catatan_status' => OrderUserNoteStatus::ApprovedJasa->value]);
        BengkelTask::query()->create([
            'order_id' => $inProgress->id,
            'job_name' => $inProgress->nama_pekerjaan,
            'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
            'archived_at' => now(),
        ]);

        $dashboard = app(WorkshopDashboardService::class)->resolve(2026, 'all');

        $this->assertSame(6, $dashboard['summary']['total']);
        $this->assertSame(2, $dashboard['summary']['in_progress']);
        $this->assertSame(3, $dashboard['summary']['completed']);
        $this->assertSame(3, $dashboard['summary']['incomplete']);
        $this->assertSame(50.0, $dashboard['summary']['completion_percentage']);
        $this->assertSame(1000, $dashboard['summary']['total_cost']);
        $this->assertSame(1, $dashboard['unknown_regu_count']);
        $this->assertSame(WorkshopDashboardService::COMPLETION_TARGET, $dashboard['summary']['completion_target']);

        $fabrikasi = collect($dashboard['regu'])->firstWhere('name', Order::WORKSHOP_REGU_FABRIKASI);
        $this->assertSame(2, $fabrikasi['total']);
        $this->assertSame(1, $fabrikasi['in_progress']);
        $this->assertSame(1, $fabrikasi['completed']);

        $estimator = collect($dashboard['regu'])->firstWhere('name', Order::WORKSHOP_REGU_ESTIMATOR);
        $this->assertSame(2, $estimator['completed']);
    }

    public function test_period_filters_include_carry_over_and_use_actual_completion_month(): void
    {
        Carbon::setTestNow('2026-09-30 10:00:00');
        $user = User::factory()->create();
        $this->workshopOrder($user, 'WD-FILTER-2025', '2025-09-10', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_IN_PROGRESS, 100);
        $this->workshopOrder($user, 'WD-FILTER-AUG', '2026-08-10', Order::WORKSHOP_REGU_REFURBISH, OrderWorkshop::PROGRESS_IN_PROGRESS, 200);
        $augustCompleted = $this->workshopOrder($user, 'WD-FILTER-AUG-DONE', '2026-08-12', Order::WORKSHOP_REGU_REFURBISH, OrderWorkshop::PROGRESS_DONE, 250);
        $this->setWorkshopCompletionAt($augustCompleted, '2026-08-20 08:00:00');
        $septemberCompleted = $this->workshopOrder($user, 'WD-FILTER-SEP-DONE', '2026-07-10', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_DONE, 300);
        $this->setWorkshopCompletionAt($septemberCompleted, '2026-09-18 08:00:00');
        $this->workshopOrder($user, 'WD-FILTER-SEP', '2026-09-10', Order::WORKSHOP_REGU_ESTIMATOR, OrderWorkshop::PROGRESS_IN_PROGRESS, 400);

        $september = app(WorkshopDashboardService::class)->resolve(2026, 9);

        $this->assertSame(['year' => 2026, 'month' => 9], $september['filters']);
        $this->assertSame(4, $september['summary']['total']);
        $this->assertSame(1, $september['summary']['completed']);
        $this->assertSame(3, $september['summary']['in_progress']);
        $this->assertSame(3, $september['summary']['incomplete']);
        $this->assertSame(25.0, $september['summary']['completion_percentage']);
        $this->assertSame(400, $september['summary']['total_cost']);
        $this->assertSame([2026, 2025], $september['available_years']);
        $this->assertCount(1, $september['monthly_work_values']);
        $this->assertSame(9, $september['monthly_work_values'][0]['month']);
        $this->assertSame([
            Order::WORKSHOP_REGU_FABRIKASI => 0.0,
            Order::WORKSHOP_REGU_REFURBISH => 0.0,
            Order::WORKSHOP_REGU_ESTIMATOR => 400.0,
        ], $september['monthly_work_values'][0]['regu']);

        $defaultPeriod = app(WorkshopDashboardService::class)->resolve();
        $this->assertSame(['year' => 2026, 'month' => null], $defaultPeriod['filters']);
        $this->assertSame(5, $defaultPeriod['summary']['total']);
        $this->assertSame(2, $defaultPeriod['summary']['completed']);

        $fullYear = app(WorkshopDashboardService::class)->resolve(2026, 'all');
        $this->assertCount(9, $fullYear['monthly_work_values']);
        $this->assertSame(1150.0, collect($fullYear['monthly_work_values'])->sum(fn (array $row): float => array_sum($row['regu'])));

        $historicalYear = app(WorkshopDashboardService::class)->resolve(2025, 'all');
        $this->assertCount(12, $historicalYear['monthly_work_values']);
        $this->assertSame(100.0, $historicalYear['monthly_work_values'][8]['regu'][Order::WORKSHOP_REGU_FABRIKASI]);
    }

    public function test_dashboard_counts_eligible_pure_service_orders_without_double_counting_hybrid(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');
        $user = User::factory()->create();
        $this->workshopOrder(
            $user,
            'WD-ESTIMATOR',
            '2026-09-01',
            Order::WORKSHOP_REGU_ESTIMATOR,
            OrderWorkshop::PROGRESS_DONE,
        );
        $hybrid = $this->jobWaitingServiceOrder($user, 'JW-HYBRID', 100);
        $hybrid->update([
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            'catatan' => Order::WORKSHOP_REGU_REFURBISH,
        ]);
        OrderWorkshop::query()->create([
            'order_id' => $hybrid->id,
            'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
            'started_at' => '2026-09-03 08:00:00',
        ]);
        $this->jobWaitingServiceOrder($user, 'JW-NOT-STARTED', 0);
        $this->jobWaitingServiceOrder($user, 'JW-IN-PROGRESS', 50);
        $this->jobWaitingServiceOrder($user, 'JW-COMPLETED', 100);
        $this->jobWaitingServiceOrder($user, 'JW-NOT-ELIGIBLE', 50, false);

        $dashboard = app(WorkshopDashboardService::class)->resolve(2026, 9);
        $estimator = collect($dashboard['regu'])->firstWhere('name', Order::WORKSHOP_REGU_ESTIMATOR);
        $refurbish = collect($dashboard['regu'])->firstWhere('name', Order::WORKSHOP_REGU_REFURBISH);

        $this->assertSame(5, $dashboard['summary']['total']);
        $this->assertSame(2, $dashboard['summary']['in_progress']);
        $this->assertSame(2, $dashboard['summary']['completed']);
        $this->assertSame(3, $dashboard['summary']['incomplete']);
        $this->assertSame(40.0, $dashboard['summary']['completion_percentage']);
        $this->assertSame(3, $dashboard['summary']['outsourced']);
        $this->assertSame(4, $estimator['total']);
        $this->assertSame(1, $estimator['in_progress']);
        $this->assertSame(2, $estimator['completed']);
        $this->assertSame(2, $estimator['incomplete']);
        $this->assertSame(1, $refurbish['total']);
        $this->assertSame(1, $refurbish['in_progress']);
        $this->assertSame(0, $refurbish['completed']);
        $this->assertSame(
            $dashboard['summary']['total'],
            collect($dashboard['regu'])->sum('total') + $dashboard['unknown_regu_count'],
        );
    }

    public function test_monthly_regu_trend_uses_workload_and_completion_timestamps_and_omits_future_months(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');
        $user = User::factory()->create();
        $january = $this->workshopOrder($user, 'WD-TREND-JAN', '2026-01-10', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_DONE);
        $this->setWorkshopCompletionAt($january, '2026-03-05 08:00:00');
        $legacyFebruary = $this->workshopOrder($user, 'WD-TREND-FEB', '2026-02-10', Order::WORKSHOP_REGU_REFURBISH, OrderWorkshop::PROGRESS_DONE);
        $legacyFebruary->orderWorkshop->forceFill(['legacy_completed_at' => '2026-02-20 08:00:00'])->save();
        $april = $this->workshopOrder($user, 'WD-TREND-APR', '2026-04-10', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_QUALITY_CONTROL);
        $this->completeQualityControl($april, $user, '2026-10-01 08:00:00');

        $dashboard = app(WorkshopDashboardService::class)->resolve(2026, 'all');
        $trend = collect($dashboard['trend'])->keyBy('month');

        $this->assertCount(9, $dashboard['trend']);
        $this->assertSame(0.0, $trend[1]['percentage']);
        $this->assertSame(50.0, $trend[2]['percentage']);
        $this->assertSame(100.0, $trend[3]['percentage']);
        $this->assertSame(0.0, $trend[4]['percentage']);
        $this->assertSame(0, $trend[9]['completed']);
        $this->assertSame('September 2026', $trend[9]['period_label']);
        $this->assertSame(1, $trend[2]['regu'][Order::WORKSHOP_REGU_FABRIKASI]['total']);
        $this->assertSame(0, $trend[2]['regu'][Order::WORKSHOP_REGU_FABRIKASI]['completed']);
        $this->assertSame(100.0, $trend[2]['regu'][Order::WORKSHOP_REGU_REFURBISH]['completion_percentage']);
        $this->assertSame(100.0, $trend[3]['regu'][Order::WORKSHOP_REGU_FABRIKASI]['completion_percentage']);

        $throughJuly = app(WorkshopDashboardService::class)->resolve(2026, 7);
        $this->assertCount(7, $throughJuly['trend']);
        $julyFabrikasi = collect($throughJuly['regu'])->firstWhere('name', Order::WORKSHOP_REGU_FABRIKASI);
        $this->assertSame(
            $julyFabrikasi['completion_percentage'],
            collect($throughJuly['trend'])->firstWhere('month', 7)['regu'][Order::WORKSHOP_REGU_FABRIKASI]['completion_percentage'],
        );
    }

    public function test_pure_service_workload_starts_at_job_waiting_entry_and_carries_until_service_completion(): void
    {
        Carbon::setTestNow('2026-10-31 10:00:00');
        $user = User::factory()->create();
        $this->jobWaitingServiceOrder(
            $user,
            'JW-CARRY-OVER',
            100,
            true,
            '2026-07-01',
            '2026-08-10 08:00:00',
            '2026-08-15',
            '2026-09-20',
        );

        $july = app(WorkshopDashboardService::class)->resolve(2026, 7);
        $august = app(WorkshopDashboardService::class)->resolve(2026, 8);
        $september = app(WorkshopDashboardService::class)->resolve(2026, 9);
        $october = app(WorkshopDashboardService::class)->resolve(2026, 10);

        $this->assertSame(0, $july['summary']['total']);
        $this->assertSame(1, $august['summary']['total']);
        $this->assertSame(1, $august['summary']['in_progress']);
        $this->assertSame(0, $august['summary']['completed']);
        $this->assertSame(1, $august['summary']['outsourced']);
        $this->assertSame(1, $september['summary']['total']);
        $this->assertSame(1, $september['summary']['completed']);
        $this->assertSame(0, $september['summary']['incomplete']);
        $this->assertSame(1, $september['summary']['outsourced']);
        $this->assertSame(0, $october['summary']['total']);
        $this->assertSame(0, $october['summary']['outsourced']);
        $this->assertFalse($july['work_values_has_data']);
        $this->assertTrue($august['work_values_has_data']);
        $this->assertSame(1000000.0, $august['monthly_work_values'][0]['regu'][Order::WORKSHOP_REGU_ESTIMATOR]);
        $this->assertFalse($september['work_values_has_data']);
        $this->assertFalse($october['work_values_has_data']);
    }

    public function test_work_values_use_entry_month_and_exclude_hybrid_service_nominals(): void
    {
        Carbon::setTestNow('2026-09-30 10:00:00');
        $user = User::factory()->create();
        $workshop = $this->workshopOrder($user, 'VALUE-AUG', '2026-08-10', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_DONE, 20000000);
        $this->setWorkshopCompletionAt($workshop, '2026-09-15 08:00:00');

        foreach ([
            Order::WORKSHOP_REGU_FABRIKASI => 10000000,
            Order::WORKSHOP_REGU_REFURBISH => 30000000,
            Order::WORKSHOP_REGU_ESTIMATOR => 25000000,
        ] as $regu => $cost) {
            $hybrid = $this->jobWaitingServiceOrder($user, 'VALUE-HYBRID-'.$cost, 100, value: '200000000.00');
            $hybrid->update([
                'catatan_status' => OrderUserNoteStatus::ApprovedWorkshopJasa->value,
                'catatan' => $regu,
                'biaya' => $cost,
            ]);
            OrderWorkshop::query()->create([
                'order_id' => $hybrid->id,
                'progress_status' => OrderWorkshop::PROGRESS_MENUNGGU_JADWAL,
            ]);
        }

        $this->jobWaitingServiceOrder($user, 'VALUE-JASA', 0, orderDate: '2026-07-01', value: '150000000.35');
        $this->jobWaitingServiceOrder($user, 'VALUE-JASA-CENTS', 50, value: '0.25');
        $this->jobWaitingServiceOrder($user, 'VALUE-INELIGIBLE', 0, false, value: '999000000.00');
        $this->workshopOrder($user, 'VALUE-NULL', '2026-09-02', Order::WORKSHOP_REGU_REFURBISH, OrderWorkshop::PROGRESS_MENUNGGU_JADWAL);

        $dashboard = app(WorkshopDashboardService::class)->resolve(2026, 'all');
        $values = collect($dashboard['monthly_work_values'])->keyBy('month');

        $this->assertTrue($dashboard['work_values_has_data']);
        $this->assertSame(0.0, $values[7]['regu'][Order::WORKSHOP_REGU_ESTIMATOR]);
        $this->assertSame(20000000.0, $values[8]['regu'][Order::WORKSHOP_REGU_FABRIKASI]);
        $this->assertSame('September 2026', $values[9]['period_label']);
        $this->assertSame([
            Order::WORKSHOP_REGU_FABRIKASI => 10000000.0,
            Order::WORKSHOP_REGU_REFURBISH => 30000000.0,
            Order::WORKSHOP_REGU_ESTIMATOR => 175000000.6,
        ], $values[9]['regu']);
        $this->assertSame(7, $dashboard['summary']['total']);
        $this->assertSame(2, $dashboard['summary']['outsourced']);
        $this->assertSame(85000000, $dashboard['summary']['total_cost']);
    }

    public function test_initial_work_value_uses_approved_hpp_and_retains_its_entry_month_after_po(): void
    {
        Carbon::setTestNow('2026-09-30 10:00:00');
        $user = User::factory()->create();
        $order = Order::query()->create([
            'nomor_order' => 'VALUE-IW',
            'nama_pekerjaan' => 'Pekerjaan Initial Work',
            'unit_kerja' => 'Unit Jasa',
            'seksi' => 'Seksi Jasa',
            'deskripsi' => 'Emergency untuk estimator',
            'prioritas' => Order::PRIORITY_URGENT,
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
            'tanggal_order' => '2026-07-01',
            'target_selesai' => '2026-09-30',
            'created_by' => $user->id,
        ]);
        $initialWork = InitialWork::query()->create([
            'order_id' => $order->id,
            'nomor_initial_work' => 'IW-VALUE',
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'perihal' => 'Emergency',
            'tanggal_initial_work' => '2026-08-10',
            'target_penyelesaian' => '2026-09-30',
            'functional_location' => ['FL-VALUE'],
            'scope_pekerjaan' => ['Pekerjaan emergency'],
            'qty' => [1],
            'stn' => ['Lot'],
            'created_by' => $user->id,
        ]);
        $initialWork->timestamps = false;
        $initialWork->forceFill(['created_at' => Carbon::parse('2026-08-10 08:00:00')])->saveQuietly();
        $withoutHpp = app(WorkshopDashboardService::class)->resolve(2026, 8);
        $this->assertSame(1, $withoutHpp['summary']['total']);
        $this->assertFalse($withoutHpp['work_values_has_data']);

        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => '75000000.45',
            'status' => Hpp::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);
        $this->assertFalse(app(WorkshopDashboardService::class)->resolve(2026, 8)['work_values_has_data']);

        $hpp->update(['status' => Hpp::STATUS_APPROVED]);
        $approved = app(WorkshopDashboardService::class)->resolve(2026, 8);
        $this->assertTrue($approved['work_values_has_data']);
        $this->assertSame(75000000.45, $approved['monthly_work_values'][0]['regu'][Order::WORKSHOP_REGU_ESTIMATOR]);

        PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => 'PO-VALUE-IW',
            'approve_manager' => true,
            'created_by' => $user->id,
        ]);
        $afterPo = app(WorkshopDashboardService::class)->resolve(2026, 'all');
        $this->assertSame(1, $afterPo['summary']['total']);
        $this->assertSame(75000000.45, $afterPo['monthly_work_values'][7]['regu'][Order::WORKSHOP_REGU_ESTIMATOR]);
        $this->assertSame(0.0, $afterPo['monthly_work_values'][8]['regu'][Order::WORKSHOP_REGU_ESTIMATOR]);
    }

    public function test_work_value_empty_state_depends_on_value_not_order_count(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->workshopOrder($admin, 'VALUE-EMPTY', '2026-09-02', Order::WORKSHOP_REGU_FABRIKASI, OrderWorkshop::PROGRESS_MENUNGGU_JADWAL);
        $this->workshopOrder($admin, 'VALUE-ZERO', '2026-09-02', Order::WORKSHOP_REGU_REFURBISH, OrderWorkshop::PROGRESS_MENUNGGU_JADWAL, 0);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['dashboard' => 'bengkel']))
            ->assertOk()
            ->assertViewHas('workshopDashboard', fn (array $data): bool => $data['summary']['total'] === 2 && ! $data['work_values_has_data'])
            ->assertSee('Belum ada data nilai pekerjaan pada periode ini.')
            ->assertDontSee('<canvas id="workshopWorkValueChart"', false);

        $this->jobWaitingServiceOrder($admin, 'VALUE-ONLY-JASA', 0);
        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['dashboard' => 'bengkel']))
            ->assertOk()
            ->assertViewHas('workshopDashboard', fn (array $data): bool => $data['summary']['total'] === 3 && $data['work_values_has_data'])
            ->assertSee('<canvas id="workshopWorkValueChart"', false)
            ->assertDontSee('Belum ada data nilai pekerjaan pada periode ini.');
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
            ->assertSee('Order Dijasakan')
            ->assertSee('Nilai Pekerjaan Per Regu')
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
            'started_at' => $progress === OrderWorkshop::PROGRESS_MENUNGGU_JADWAL
                ? null
                : Carbon::parse($orderDate)->startOfDay(),
        ]);

        return $order;
    }

    private function completeQualityControl(Order $order, User $user, string $completedAt): QualityControlReport
    {
        $report = QualityControlReport::query()->create([
            'order_id' => $order->id,
            'type' => QualityControlReport::TYPE_FABRICATION,
            'status' => QualityControlReport::STATUS_SUBMITTED,
            'payload' => [
                'signature' => [
                    'signature_data' => 'data:image/png;base64,maker-signature',
                ],
            ],
            'created_by' => $user->id,
        ]);

        $report->signatures()->createMany([
            [
                'step_order' => 1,
                'role_key' => QualityControlSignature::ROLE_WORKSHOP_MANAGER,
                'role_label' => 'Manager Workshop',
                'signer_user_id' => $user->id,
                'signer_name' => $user->name,
                'status' => QualityControlSignature::STATUS_SIGNED,
                'signature_data' => 'signatures/workshop-manager.png',
                'signed_at' => Carbon::parse($completedAt)->subMinute(),
            ],
            [
                'step_order' => 2,
                'role_key' => QualityControlSignature::ROLE_USER_MANAGER,
                'role_label' => 'Manager User',
                'signer_user_id' => $user->id,
                'signer_name' => $user->name,
                'status' => QualityControlSignature::STATUS_SIGNED,
                'signature_data' => 'signatures/user-manager.png',
                'signed_at' => $completedAt,
            ],
        ]);

        return $report;
    }

    private function setWorkshopCompletionAt(Order $order, string $completedAt): void
    {
        $workshop = $order->orderWorkshop;
        $workshop->timestamps = false;
        $workshop->forceFill(['updated_at' => Carbon::parse($completedAt)])->saveQuietly();
    }

    private function jobWaitingServiceOrder(
        User $user,
        string $number,
        int $progress,
        bool $eligible = true,
        string $orderDate = '2026-09-02',
        string $entryAt = '2026-09-02 08:00:00',
        ?string $startedAt = null,
        ?string $completedAt = null,
        string $value = '1000000.00',
    ): Order {
        $startedAt ??= $progress >= 11 ? Carbon::parse($entryAt)->toDateString() : null;
        $completedAt ??= $progress >= 100 ? Carbon::parse($entryAt)->addDays(2)->toDateString() : null;
        $order = Order::query()->create([
            'nomor_order' => $number,
            'nama_pekerjaan' => 'Pekerjaan '.$number,
            'unit_kerja' => 'Unit Jasa',
            'seksi' => 'Seksi Jasa',
            'deskripsi' => 'Pekerjaan jasa untuk estimator',
            'prioritas' => Order::PRIORITY_LOW,
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
            'tanggal_order' => $orderDate,
            'target_selesai' => '2026-09-30',
            'catatan' => 'Jasa Fabrikasi',
            'created_by' => $user->id,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $number,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => $value,
            'status' => Hpp::STATUS_APPROVED,
            'created_by' => $user->id,
        ]);
        $purchaseOrder = PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => $eligible ? 'PO-'.$number : null,
            'approve_manager' => $eligible,
            'progress_pekerjaan' => $progress,
            'tanggal_mulai_pekerjaan' => $startedAt,
            'tanggal_selesai_pekerjaan' => $completedAt,
            'created_by' => $user->id,
        ]);
        $purchaseOrder->timestamps = false;
        $purchaseOrder->forceFill([
            'created_at' => Carbon::parse($entryAt),
            'updated_at' => Carbon::parse($entryAt),
        ])->saveQuietly();

        return $order;
    }
}
