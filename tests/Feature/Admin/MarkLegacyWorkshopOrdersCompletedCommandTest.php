<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\User;
use App\Services\BengkelTasks\WorkshopHandoverQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MarkLegacyWorkshopOrdersCompletedCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_command_is_dry_run_by_default_and_apply_only_marks_eligible_orders_before_cutoff(): void
    {
        Carbon::setTestNow('2026-09-04 10:15:00');
        $user = User::factory()->create();
        $legacy = $this->workshopOrder($user, 'LEGACY-001', '2026-08-01');
        $recent = $this->workshopOrder($user, 'RECENT-001', '2026-09-01');

        $this->artisan('workshop:mark-legacy-completed', ['--before' => '2026-08-31'])
            ->assertSuccessful();

        $this->assertNull($legacy->orderWorkshop->refresh()->legacy_completed_at);

        $this->artisan('workshop:mark-legacy-completed', [
            '--before' => '2026-08-31',
            '--apply' => true,
        ])->assertSuccessful();

        $legacyCompletedAt = $legacy->orderWorkshop->refresh()->legacy_completed_at;

        $this->assertSame('2026-09-04 10:15:00', $legacyCompletedAt?->format('Y-m-d H:i:s'));
        $this->assertNull($recent->orderWorkshop->refresh()->legacy_completed_at);
        $this->assertFalse(app(WorkshopHandoverQueue::class)->query()->whereKey($legacy->id)->exists());
        $this->assertTrue(app(WorkshopHandoverQueue::class)->query()->whereKey($recent->id)->exists());
        $this->assertDatabaseCount('workshop_handovers', 0);

        $this->artisan('workshop:mark-legacy-completed', [
            '--before' => '2026-08-31',
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertSame(
            $legacyCompletedAt?->format('Y-m-d H:i:s'),
            $legacy->orderWorkshop->refresh()->legacy_completed_at?->format('Y-m-d H:i:s'),
        );
    }

    public function test_command_requires_a_valid_cutoff(): void
    {
        $this->artisan('workshop:mark-legacy-completed')->assertFailed();
        $this->artisan('workshop:mark-legacy-completed', ['--before' => 'bukan-tanggal'])->assertFailed();
    }

    private function workshopOrder(User $user, string $number, string $orderDate): Order
    {
        $order = Order::query()->create([
            'nomor_order' => $number,
            'nama_pekerjaan' => 'Pekerjaan '.$number,
            'unit_kerja' => 'Unit Workshop',
            'seksi' => 'Seksi Workshop',
            'deskripsi' => 'Pekerjaan legacy workshop',
            'prioritas' => Order::PRIORITY_LOW,
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
            'tanggal_order' => $orderDate,
            'target_selesai' => $orderDate,
            'catatan' => Order::WORKSHOP_REGU_FABRIKASI,
            'created_by' => $user->id,
        ]);
        $order->orderWorkshop()->create([
            'progress_status' => OrderWorkshop::PROGRESS_DONE,
        ]);

        return $order->fresh('orderWorkshop');
    }
}
