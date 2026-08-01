<?php

namespace Tests\Feature\Admin;

use App\Models\Hpp;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DashboardTopTenHppCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_ten_counts_submitted_non_draft_hpps_by_trimmed_requester_section(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->createHpp($admin, 'TOPTEN-REVIEW', 'Seksi Kiln', 100000000, Hpp::STATUS_IN_REVIEW, '2026-01-05 08:00:00', [
            'seksi_pengendali' => 'Seksi Pengendali Lain',
        ]);
        $this->createHpp($admin, 'TOPTEN-APPROVED', ' Seksi Kiln ', 200000000, Hpp::STATUS_APPROVED, '2026-01-06 08:00:00');
        $this->createHpp($admin, 'TOPTEN-REJECTED', 'Seksi Crusher', 50000000, Hpp::STATUS_REJECTED, '2026-01-07 08:00:00');
        $this->createHpp($admin, 'TOPTEN-DRAFT', 'Seksi Crusher', 900000000, Hpp::STATUS_DRAFT, '2026-01-08 08:00:00');
        $this->createHpp($admin, 'TOPTEN-NOT-SUBMITTED', 'Seksi Raw Mill', 800000000, Hpp::STATUS_IN_REVIEW, null);
        $this->createHpp($admin, 'TOPTEN-EMPTY-SECTION', '   ', 700000000, Hpp::STATUS_APPROVED, '2026-01-09 08:00:00');

        $response = $this->topTenResponse($admin, 2026, 1, 2026, 1);

        $response->assertOk()
            ->assertJsonCount(2, 'top_ten')
            ->assertJsonPath('top_ten.0.section', 'Seksi Kiln')
            ->assertJsonPath('top_ten.0.amount', 300000000)
            ->assertJsonPath('top_ten.1.section', 'Seksi Crusher')
            ->assertJsonPath('top_ten.1.amount', 50000000)
            ->assertJsonMissing(['section' => 'Seksi Pengendali Lain'])
            ->assertJsonMissing(['section' => 'Seksi Raw Mill']);
    }

    public function test_only_latest_submitted_hpp_per_order_is_counted_with_id_as_tie_breaker(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $revisedOrder = $this->createOrder($admin, 'TOPTEN-REVISION', 'Seksi Finish Mill');
        $sameTimeOrder = $this->createOrder($admin, 'TOPTEN-SAME-TIME', 'Seksi Finish Mill');

        $this->createHppForOrder($revisedOrder, $admin, 100000000, Hpp::STATUS_REJECTED, '2026-02-01 08:00:00');
        $this->createHppForOrder($revisedOrder, $admin, 120000000, Hpp::STATUS_IN_REVIEW, '2026-02-05 08:00:00');
        $this->createHppForOrder($sameTimeOrder, $admin, 40000000, Hpp::STATUS_REJECTED, '2026-02-10 08:00:00');
        $this->createHppForOrder($sameTimeOrder, $admin, 50000000, Hpp::STATUS_APPROVED, '2026-02-10 08:00:00');

        $response = $this->topTenResponse($admin, 2026, 2, 2026, 2);

        $response->assertOk()
            ->assertJsonCount(1, 'top_ten')
            ->assertJsonPath('top_ten.0.section', 'Seksi Finish Mill')
            ->assertJsonPath('top_ten.0.amount', 170000000);
    }

    public function test_top_ten_uses_submitted_at_filter_orders_descending_and_limits_results(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        foreach (range(1, 12) as $index) {
            $this->createHpp(
                $admin,
                sprintf('TOPTEN-LIMIT-%02d', $index),
                sprintf('Seksi %02d', $index),
                $index * 1000000,
                Hpp::STATUS_APPROVED,
                '2026-03-15 08:00:00',
            );
        }

        $this->createHpp(
            $admin,
            'TOPTEN-OUTSIDE-PERIOD',
            'Seksi Di Luar Periode',
            999000000,
            Hpp::STATUS_APPROVED,
            '2026-04-01 00:00:00',
        );

        $response = $this->topTenResponse($admin, 2026, 3, 2026, 3);

        $response->assertOk()
            ->assertJsonCount(10, 'top_ten')
            ->assertJsonPath('top_ten.0.section', 'Seksi 12')
            ->assertJsonPath('top_ten.0.amount', 12000000)
            ->assertJsonPath('top_ten.9.section', 'Seksi 03')
            ->assertJsonMissing(['section' => 'Seksi Di Luar Periode']);
    }

    private function topTenResponse(
        User $admin,
        int $startYear,
        int $startMonth,
        int $endYear,
        int $endMonth,
    ): TestResponse {
        return $this->actingAs($admin)->getJson(route('admin.dashboard.realization-chart', [
            'startYear' => $startYear,
            'endYear' => $endYear,
            'startMonth' => $startMonth,
            'endMonth' => $endMonth,
            'includeTopTen' => 1,
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createHpp(
        User $admin,
        string $orderNumber,
        string $section,
        int $amount,
        string $status,
        ?string $submittedAt,
        array $attributes = [],
    ): Hpp {
        $order = $this->createOrder($admin, $orderNumber, $section);

        return $this->createHppForOrder($order, $admin, $amount, $status, $submittedAt, $attributes);
    }

    private function createOrder(User $admin, string $orderNumber, string $section): Order
    {
        return Order::query()->create([
            'nomor_order' => $orderNumber,
            'nama_pekerjaan' => 'Pekerjaan '.$orderNumber,
            'unit_kerja' => 'Unit Pengujian Dashboard',
            'seksi' => $section,
            'deskripsi' => 'Pekerjaan pengujian Top Ten Dashboard.',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-01-01',
            'target_selesai' => '2026-12-31',
            'created_by' => $admin->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createHppForOrder(
        Order $order,
        User $admin,
        int $amount,
        string $status,
        ?string $submittedAt,
        array $attributes = [],
    ): Hpp {
        return Hpp::query()->create(array_merge([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => $amount,
            'status' => $status,
            'submitted_at' => $submittedAt,
            'created_by' => $admin->id,
        ], $attributes));
    }
}
