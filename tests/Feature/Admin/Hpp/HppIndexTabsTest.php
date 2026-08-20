<?php

namespace Tests\Feature\Admin\Hpp;

use App\Models\BudgetVerification;
use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\LhppBast;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\HppIndexTabs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HppIndexTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_default_and_in_approval_tabs_use_shared_status_classification(): void
    {
        $admin = $this->admin();
        $draft = $this->hpp($admin, Hpp::STATUS_DRAFT, 'TAB-DRAFT');
        $rejected = $this->hpp($admin, Hpp::STATUS_REJECTED, 'TAB-REJECTED');
        $inReview = $this->hpp($admin, Hpp::STATUS_IN_REVIEW, 'TAB-REVIEW');
        $approved = $this->hpp($admin, Hpp::STATUS_APPROVED, 'TAB-APPROVED');

        $actionResponse = $this->actingAs($admin)
            ->get(route('admin.hpp.index'))
            ->assertOk()
            ->assertDontSee('Semua Status')
            ->assertDontSee('Resend Semua');

        $this->assertEqualsCanonicalizing(
            [$draft->id, $rejected->id],
            collect($actionResponse->viewData('rows')->items())->pluck('id')->all(),
        );

        $approvalResponse = $this->actingAs($admin)
            ->get(route('admin.hpp.index', ['tab' => HppIndexTabs::IN_APPROVAL]))
            ->assertOk()
            ->assertSee('Resend Semua')
            ->assertSee(route('admin.hpp.approval.resend-all'), false);

        $this->assertSame(
            [$inReview->id],
            collect($approvalResponse->viewData('rows')->items())->pluck('id')->all(),
        );
    }

    public function test_approved_and_history_tabs_follow_downstream_relationships(): void
    {
        $admin = $this->admin();
        $approved = $this->hpp($admin, Hpp::STATUS_APPROVED, 'ONLY-APPROVED');
        $withBudget = $this->hpp($admin, Hpp::STATUS_APPROVED, 'WITH-BUDGET');
        $withPurchaseOrder = $this->hpp($admin, Hpp::STATUS_APPROVED, 'WITH-PO');
        $withLhpp = $this->hpp($admin, Hpp::STATUS_APPROVED, 'WITH-LHPP');

        BudgetVerification::query()->create([
            'order_id' => $withBudget->order_id,
            'hpp_id' => $withBudget->id,
        ]);
        PurchaseOrder::query()->create([
            'order_id' => $withPurchaseOrder->order_id,
            'hpp_id' => $withPurchaseOrder->id,
        ]);
        $this->lhpp($admin, $withLhpp);

        $this->assertSame([$approved->id], HppIndexTabs::apply(Hpp::query(), HppIndexTabs::APPROVED)->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$withBudget->id, $withPurchaseOrder->id, $withLhpp->id],
            HppIndexTabs::apply(Hpp::query(), HppIndexTabs::HISTORY)->pluck('id')->all(),
        );
    }

    public function test_pkm_uses_same_tabs_and_can_see_admin_draft(): void
    {
        $admin = $this->admin();
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $draft = $this->hpp($admin, Hpp::STATUS_DRAFT, 'ADMIN-DRAFT');
        $inReview = $this->hpp($admin, Hpp::STATUS_IN_REVIEW, 'ADMIN-REVIEW');

        $this->actingAs($pkm)
            ->get(route('pkm.hpp.index'))
            ->assertOk()
            ->assertSee($draft->nomor_order)
            ->assertDontSee($inReview->nomor_order);

        $this->actingAs($pkm)
            ->get(route('pkm.hpp.index', ['tab' => HppIndexTabs::IN_APPROVAL]))
            ->assertOk()
            ->assertSee($inReview->nomor_order)
            ->assertDontSee($draft->nomor_order);
    }

    public function test_search_only_filters_active_tab_and_invalid_tab_falls_back_to_action(): void
    {
        $admin = $this->admin();
        $matchingDraft = $this->hpp($admin, Hpp::STATUS_DRAFT, 'SEARCH-MATCH');
        $otherDraft = $this->hpp($admin, Hpp::STATUS_DRAFT, 'SEARCH-OTHER');
        $matchingReview = $this->hpp($admin, Hpp::STATUS_IN_REVIEW, 'SEARCH-MATCH-REVIEW');

        $this->actingAs($admin)
            ->get(route('admin.hpp.index', ['tab' => 'invalid', 'search' => 'SEARCH-MATCH']))
            ->assertOk()
            ->assertSee($matchingDraft->nomor_order)
            ->assertDontSee($otherDraft->nomor_order)
            ->assertDontSee($matchingReview->nomor_order);
    }

    public function test_admin_index_keeps_pkm_creator_label_and_paginates_ten_rows(): void
    {
        $admin = $this->admin();
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);

        foreach (range(1, 11) as $index) {
            $this->hpp($index === 11 ? $pkm : $admin, Hpp::STATUS_DRAFT, sprintf('PAGE-%02d', $index));
        }

        $response = $this->actingAs($admin)
            ->get(route('admin.hpp.index'))
            ->assertOk()
            ->assertSee('Dibuat oleh PKM');

        $this->assertCount(10, $response->viewData('rows')->items());
        $this->assertSame(11, $response->viewData('rows')->total());
    }

    public function test_latest_hpp_activity_is_ordered_by_updated_at_then_id(): void
    {
        $admin = $this->admin();
        $older = $this->hpp($admin, Hpp::STATUS_DRAFT, 'OLDER');
        $newer = $this->hpp($admin, Hpp::STATUS_DRAFT, 'NEWER');
        $older->forceFill(['updated_at' => now()->subHour()])->saveQuietly();
        $newer->forceFill(['updated_at' => now()])->saveQuietly();

        $rows = $this->actingAs($admin)
            ->get(route('admin.hpp.index'))
            ->viewData('rows');

        $this->assertSame([$newer->id, $older->id], collect($rows->items())->pluck('id')->all());
    }

    public function test_meaningful_signature_update_touches_hpp_but_opened_at_only_does_not(): void
    {
        $admin = $this->admin();
        $hpp = $this->hpp($admin, Hpp::STATUS_IN_REVIEW, 'SIGNATURE-TOUCH');
        $signature = $this->signature($hpp, $admin);
        $baseline = Carbon::parse('2026-07-31 10:00:00');
        $hpp->forceFill(['updated_at' => $baseline])->saveQuietly();

        Carbon::setTestNow($baseline->copy()->addMinute());
        $signature->update(['opened_at' => now()]);
        $this->assertTrue($hpp->fresh()->updated_at->equalTo($baseline));

        Carbon::setTestNow($baseline->copy()->addMinutes(2));
        $signature->update([
            'status' => HppSignature::STATUS_SIGNED,
            'signed_at' => now(),
        ]);
        $this->assertTrue($hpp->fresh()->updated_at->equalTo(now()));
        Carbon::setTestNow();
    }

    public function test_downstream_updates_touch_parent_hpp(): void
    {
        $admin = $this->admin();

        foreach (['budget', 'purchase_order', 'lhpp'] as $type) {
            $hpp = $this->hpp($admin, Hpp::STATUS_APPROVED, 'TOUCH-'.$type);
            $child = match ($type) {
                'budget' => BudgetVerification::query()->create([
                    'order_id' => $hpp->order_id,
                    'hpp_id' => $hpp->id,
                ]),
                'purchase_order' => PurchaseOrder::query()->create([
                    'order_id' => $hpp->order_id,
                    'hpp_id' => $hpp->id,
                ]),
                default => $this->lhpp($admin, $hpp),
            };

            $baseline = now()->subHour();
            $hpp->forceFill(['updated_at' => $baseline])->saveQuietly();
            Carbon::setTestNow(now()->addMinute());

            match ($type) {
                'budget' => $child->update(['catatan' => 'Diperbarui']),
                'purchase_order' => $child->update(['admin_note' => 'Diperbarui']),
                default => $child->update(['quality_control_status' => 'approved']),
            };

            $this->assertTrue($hpp->fresh()->updated_at->greaterThan($baseline));
            Carbon::setTestNow();
        }
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }

    private function hpp(User $creator, string $status, string $suffix): Hpp
    {
        $order = Order::query()->create([
            'nomor_order' => 'ORDER-'.$suffix,
            'nama_pekerjaan' => 'Pekerjaan '.$suffix,
            'deskripsi' => 'Deskripsi '.$suffix,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-07-01',
            'target_selesai' => '2026-07-10',
            'created_by' => $creator->id,
        ]);

        return Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'approval_flow' => [],
            'total_keseluruhan' => 1000000,
            'status' => $status,
            'created_by' => $creator->id,
        ]);
    }

    private function signature(Hpp $hpp, User $signer): HppSignature
    {
        return HppSignature::query()->create([
            'hpp_id' => $hpp->id,
            'step_order' => 1,
            'role_key' => 'manager_peminta',
            'role_label' => 'Manager Peminta',
            'signer_user_id' => $signer->id,
            'signer_name_snapshot' => $signer->name,
            'signer_position_snapshot' => 'Manager',
            'status' => HppSignature::STATUS_PENDING,
        ]);
    }

    private function lhpp(User $creator, Hpp $hpp): LhppBast
    {
        return LhppBast::query()->create([
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
            'nomor_order' => $hpp->nomor_order,
            'tanggal_bast' => '2026-07-31',
            'created_by' => $creator->id,
        ]);
    }
}
