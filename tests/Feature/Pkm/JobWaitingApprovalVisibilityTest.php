<?php

namespace Tests\Feature\Pkm;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\LpjPpl;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\PkmJobWaitingQuery;
use App\Support\PkmNotificationCenter;
use App\Support\PkmSidebarBadgeCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class JobWaitingApprovalVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_remains_until_termin_one_signature_becomes_active(): void
    {
        [$pkm, $order] = $this->eligibleOrder('JW-APPROVAL-LIFECYCLE');

        $this->assertJobWaitingContains($pkm, $order, true);

        $bast = $this->makeBast($pkm, $order, [
            'quality_control_status' => 'pending',
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
        ]);

        $this->assertJobWaitingContains($pkm, $order, true);

        $firstSignature = $this->makeSignature($bast, $pkm, LhppBastSignature::STATUS_LOCKED, 1);
        $this->makeSignature($bast, $pkm, LhppBastSignature::STATUS_LOCKED, 2);

        $this->assertJobWaitingContains($pkm, $order, true);

        $firstSignature->update(['status' => LhppBastSignature::STATUS_PENDING]);
        $this->assertJobWaitingContains($pkm, $order, false);

        $firstSignature->update([
            'status' => LhppBastSignature::STATUS_SIGNED,
            'signed_at' => now(),
        ]);
        $this->assertJobWaitingContains($pkm, $order, false);

        $firstSignature->update(['status' => LhppBastSignature::STATUS_SKIPPED]);
        $bast->update(['approval_status' => LhppBast::APPROVAL_REJECTED]);
        $this->assertJobWaitingContains($pkm, $order, false);

        $firstSignature->update(['status' => LhppBastSignature::STATUS_SIGNED]);
        $bast->update(['approval_status' => LhppBast::APPROVAL_APPROVED]);
        $this->assertJobWaitingContains($pkm, $order, false);

        $bast->delete();
        $this->assertJobWaitingContains($pkm, $order, true);
    }

    public function test_pending_termin_two_signature_does_not_hide_job_waiting_card(): void
    {
        [$pkm, $order] = $this->eligibleOrder('JW-TERMIN-TWO');
        $terminTwo = $this->makeBast($pkm, $order, ['termin_type' => 'termin_2']);
        $this->makeSignature($terminTwo, $pkm, LhppBastSignature::STATUS_PENDING);

        $this->assertJobWaitingContains($pkm, $order, true);
        $this->assertSame(1, $this->jobWaitingBadge());
    }

    public function test_approved_termin_one_quality_control_hides_card_even_if_signature_activation_is_incomplete(): void
    {
        [$pkm, $order] = $this->eligibleOrder('JW-QC-APPROVED');
        $bast = $this->makeBast($pkm, $order, ['quality_control_status' => 'pending']);

        $this->makeSignature($bast, $pkm, LhppBastSignature::STATUS_LOCKED);

        $this->assertJobWaitingContains($pkm, $order, true);
        $this->assertSame(1, $this->jobWaitingBadge());

        $bast->update(['quality_control_status' => 'approved']);

        $this->assertJobWaitingContains($pkm, $order, false);
        $this->assertSame(0, $this->jobWaitingBadge());
    }

    public function test_pkm_dashboard_list_and_update_action_follow_job_waiting_visibility(): void
    {
        [$pkm, $order] = $this->eligibleOrder('JW-PKM-DASHBOARD');
        $bast = $this->makeBast($pkm, $order, ['quality_control_status' => 'pending']);

        $this->assertDashboardJobHighlight($pkm, $order, true);

        $bast->update(['quality_control_status' => 'approved']);

        $this->assertDashboardJobHighlight($pkm, $order, false);
    }

    public function test_lpj_ppl_completeness_no_longer_controls_job_waiting_visibility(): void
    {
        [$pkm, $order] = $this->eligibleOrder('JW-LPJ-PPL');
        $bast = $this->makeBast($pkm, $order);

        LpjPpl::query()->create([
            'lhpp_bast_id' => $bast->id,
            'lpj_document_path_termin1' => 'lpj/complete.pdf',
            'ppl_document_path_termin1' => 'ppl/complete.pdf',
        ]);

        $this->assertJobWaitingContains($pkm, $order, true);

        $this->makeSignature($bast, $pkm, LhppBastSignature::STATUS_PENDING);
        $bast->lpjPpl()->delete();

        $this->assertJobWaitingContains($pkm, $order, false);
    }

    public function test_sidebar_badge_uses_the_same_approval_started_rule_as_the_list(): void
    {
        [$pkm, $order] = $this->eligibleOrder('JW-BADGE');
        $bast = $this->makeBast($pkm, $order, ['quality_control_status' => 'pending']);

        $this->assertSame(1, PkmJobWaitingQuery::query()->count());
        $this->assertSame(1, $this->jobWaitingBadge());

        $signature = $this->makeSignature($bast, $pkm, LhppBastSignature::STATUS_PENDING);

        $this->assertSame(0, PkmJobWaitingQuery::query()->count());
        $this->assertSame(0, $this->jobWaitingBadge());

        $signature->delete();
        $bast->delete();

        $this->assertSame(1, PkmJobWaitingQuery::query()->count());
        $this->assertSame(1, $this->jobWaitingBadge());
    }

    public function test_job_waiting_notification_stops_when_termin_one_approval_starts(): void
    {
        [$pkm, $order, $purchaseOrder] = $this->eligibleOrder('JW-NOTIFICATION');
        $purchaseOrder->update(['progress_pekerjaan' => 0]);
        $bast = $this->makeBast($pkm, $order);

        $this->assertTrue(
            PkmNotificationCenter::unreadNotificationKeys($pkm)->contains('pkm-job-po:'.$purchaseOrder->id)
        );
        $this->assertTrue(
            PkmNotificationCenter::unreadNotificationKeys($pkm)->contains(
                'pkm-target-approved:'.$purchaseOrder->id.':'.$purchaseOrder->target_penyelesaian?->toDateString()
            )
        );

        $signature = $this->makeSignature($bast, $pkm, LhppBastSignature::STATUS_SIGNED);

        $keys = PkmNotificationCenter::unreadNotificationKeys($pkm);

        $this->assertFalse($keys->contains('pkm-job-po:'.$purchaseOrder->id));
        $this->assertFalse($keys->contains(
            'pkm-target-approved:'.$purchaseOrder->id.':'.$purchaseOrder->target_penyelesaian?->toDateString()
        ));
        $this->assertTrue($keys->contains('pkm-bast-signature:'.$signature->id));
    }

    public function test_approval_started_scope_matches_model_semantics(): void
    {
        [$pkm, $order] = $this->eligibleOrder('JW-SCOPE');
        $bast = $this->makeBast($pkm, $order);

        $this->assertFalse($bast->hasApprovalStarted());
        $this->assertFalse(LhppBast::query()->approvalStarted()->whereKey($bast->getKey())->exists());

        $signature = $this->makeSignature($bast, $pkm, LhppBastSignature::STATUS_LOCKED);
        $this->assertFalse($bast->fresh()->hasApprovalStarted());
        $this->assertFalse(LhppBast::query()->approvalStarted()->whereKey($bast->getKey())->exists());

        foreach (LhppBast::approvalStartedSignatureStatuses() as $status) {
            $signature->update([
                'status' => $status,
                'signed_at' => $status === LhppBastSignature::STATUS_SIGNED ? now() : null,
            ]);

            $this->assertTrue($bast->fresh()->hasApprovalStarted());
            $this->assertTrue(LhppBast::query()->approvalStarted()->whereKey($bast->getKey())->exists());
        }
    }

    /**
     * @return array{User, Order, PurchaseOrder}
     */
    private function eligibleOrder(string $number): array
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $order = Order::query()->create([
            'nomor_order' => $number,
            'notifikasi' => 'NOTIF-'.$number,
            'nama_pekerjaan' => 'Pekerjaan '.$number,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Pekerjaan Job Waiting',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'created_by' => $pkm->id,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_APPROVED,
            'created_by' => $pkm->id,
        ]);
        $purchaseOrder = PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => 'PO-'.$number,
            'approve_manager' => true,
            'approval_target' => 'setuju',
            'target_penyelesaian' => now()->addWeek()->toDateString(),
            'progress_pekerjaan' => 100,
            'created_by' => $pkm->id,
            'updated_by' => $pkm->id,
        ]);

        return [$pkm, $order, $purchaseOrder];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeBast(User $user, Order $order, array $attributes = []): LhppBast
    {
        return LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => now()->toDateString(),
            'quality_control_status' => 'pending',
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'created_by' => $user->id,
            ...$attributes,
        ]);
    }

    private function makeSignature(
        LhppBast $bast,
        User $signer,
        string $status,
        int $step = 1,
    ): LhppBastSignature {
        return $bast->signatures()->create([
            'step_order' => $step,
            'role_key' => 'manager_pkm_'.$step,
            'role_label' => 'Manager PKM',
            'signer_user_id' => $signer->id,
            'signer_name_snapshot' => $signer->name,
            'status' => $status,
            'signed_at' => $status === LhppBastSignature::STATUS_SIGNED ? now() : null,
        ]);
    }

    private function assertJobWaitingContains(User $pkm, Order $order, bool $expected): void
    {
        $response = $this->actingAs($pkm)
            ->get(route('pkm.jobwaiting', ['search' => $order->nomor_order]))
            ->assertOk();

        $response->assertViewHas('notifications', function (LengthAwarePaginator $notifications) use ($order, $expected): bool {
            $contains = $notifications->getCollection()->contains(
                fn (array $notification): bool => $notification['nomor_order'] === $order->nomor_order
            );

            return $contains === $expected;
        });
    }

    private function assertDashboardJobHighlight(User $pkm, Order $order, bool $expected): void
    {
        $this->actingAs($pkm)
            ->get(route('pkm.dashboard'))
            ->assertOk()
            ->assertViewHas('jobHighlights', function (array $items) use ($order, $expected): bool {
                $contains = collect($items)->contains(
                    fn (array $item): bool => $item['nomor_order'] === $order->nomor_order
                );

                return $contains === $expected;
            })
            ->assertViewHas('pekerjaanMenunggu', $expected ? 1 : 0);
    }

    private function jobWaitingBadge(): int
    {
        return app(PkmSidebarBadgeCounter::class)->counts()['jobwaiting'];
    }
}
