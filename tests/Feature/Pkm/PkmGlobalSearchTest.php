<?php

namespace Tests\Feature\Pkm;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\OrderScopeOfWork;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PkmGlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_pkm_and_super_admin_can_search(): void
    {
        $this->getJson(route('pkm.global-search.index', ['q' => 'abc']))->assertUnauthorized();

        foreach ([User::ROLE_USER, User::ROLE_APPROVER] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->getJson(route('pkm.global-search.index', ['q' => 'abc']))
                ->assertForbidden();
        }

        $this->actingAs($this->pkm())->getJson(route('pkm.global-search.index', ['q' => 'abc']))->assertOk();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]))
            ->getJson(route('pkm.global-search.index', ['q' => 'abc']))
            ->assertOk();
    }

    public function test_query_must_be_between_two_and_one_hundred_characters(): void
    {
        $pkm = $this->pkm();

        $this->actingAs($pkm)->getJson(route('pkm.global-search.index', ['q' => 'a']))->assertUnprocessable();
        $this->actingAs($pkm)->getJson(route('pkm.global-search.index', ['q' => str_repeat('a', 101)]))->assertUnprocessable();
    }

    public function test_order_with_scope_of_work_and_no_hpp_points_to_buat_hpp(): void
    {
        $pkm = $this->pkm();
        $order = $this->order(['nomor_order' => '1705777001', 'notifikasi' => '3000777001']);
        $this->scopeOfWork($order, $pkm);

        $this->actingAs($pkm)
            ->getJson(route('pkm.global-search.index', ['q' => '3000777001']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $order->id)
            ->assertJsonPath('results.0.menu', 'Buat HPP')
            ->assertJsonPath('results.0.status', 'HPP belum dibuat')
            ->assertJsonPath('results.0.url', route('pkm.hpp.index'));
    }

    public function test_draft_hpp_links_to_the_tab_that_contains_it_with_search_prefilled(): void
    {
        $pkm = $this->pkm();
        $order = $this->order(['nomor_order' => '1705777002']);
        $this->scopeOfWork($order, $pkm);
        $this->hpp($order, $pkm, Hpp::STATUS_DRAFT);

        $this->actingAs($pkm)
            ->getJson(route('pkm.global-search.index', ['q' => '1705777002']))
            ->assertOk()
            ->assertJsonPath('results.0.menu', 'Buat HPP')
            ->assertJsonPath('results.0.url', route('pkm.hpp.index', ['tab' => 'action', 'search' => '1705777002']));
    }

    public function test_order_in_job_waiting_is_found_there_with_progress(): void
    {
        $pkm = $this->pkm();
        $order = $this->order(['nomor_order' => '1705777003']);
        $this->scopeOfWork($order, $pkm);
        $hpp = $this->hpp($order, $pkm, Hpp::STATUS_APPROVED);
        PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => 'PO-1705777003',
            'approve_manager' => true,
            'approval_target' => 'setuju',
            'target_penyelesaian' => now()->addWeek()->toDateString(),
            'progress_pekerjaan' => 40,
            'created_by' => $pkm->id,
            'updated_by' => $pkm->id,
        ]);

        $response = $this->actingAs($pkm)
            ->getJson(route('pkm.global-search.index', ['q' => '1705777003']))
            ->assertOk()
            ->assertJsonPath('results.0.menu', 'List Pekerjaan')
            ->assertJsonPath('results.0.status', 'Progress pekerjaan 40%')
            ->assertJsonPath('results.0.url', route('pkm.jobwaiting', ['search' => '1705777003']));

        $menus = collect($response->json('results.0.locations'))->pluck('menu')->all();
        $this->assertContains('Buat HPP', $menus);
        $this->assertContains('List Pekerjaan', $menus);
    }

    public function test_workshop_only_and_pending_orders_are_not_exposed(): void
    {
        $pkm = $this->pkm();
        $this->order(['nomor_order' => '1705777004', 'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value]);
        $this->order(['nomor_order' => '1705777005', 'catatan_status' => OrderUserNoteStatus::Pending->value]);
        // Status jasa tetapi belum punya SOW/HPP/PO: tidak muncul di menu PKM mana pun.
        $this->order(['nomor_order' => '1705777006']);

        foreach (['1705777004', '1705777005', '1705777006'] as $number) {
            $this->actingAs($pkm)
                ->getJson(route('pkm.global-search.index', ['q' => $number]))
                ->assertOk()
                ->assertJsonCount(0, 'results');
        }
    }

    public function test_results_are_limited_to_five_orders(): void
    {
        $pkm = $this->pkm();

        foreach (range(1, 8) as $index) {
            $order = $this->order(['nomor_order' => '17057780'.$index, 'nama_pekerjaan' => 'Fabrikasi Batch Uji']);
            $this->scopeOfWork($order, $pkm);
        }

        $this->actingAs($pkm)
            ->getJson(route('pkm.global-search.index', ['q' => 'Fabrikasi Batch']))
            ->assertOk()
            ->assertJsonCount(5, 'results');
    }

    public function test_pkm_layout_renders_the_search_box(): void
    {
        $this->actingAs($this->pkm())
            ->get(route('pkm.dashboard'))
            ->assertOk()
            ->assertSee('pkm-global-search', false)
            ->assertSee(route('pkm.global-search.index'), false);
    }

    private function pkm(): User
    {
        return User::factory()->create(['role' => User::ROLE_PKM]);
    }

    private function order(array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'nomor_order' => '1705770000',
            'notifikasi' => null,
            'nama_pekerjaan' => 'Pekerjaan Fabrikasi',
            'unit_kerja' => 'Workshop',
            'seksi' => 'Workshop Machine',
            'deskripsi' => 'Deskripsi pekerjaan',
            'prioritas' => Order::PRIORITY_LOW,
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addDays(7)->toDateString(),
        ], $overrides));
    }

    private function scopeOfWork(Order $order, User $creator): OrderScopeOfWork
    {
        return OrderScopeOfWork::query()->create([
            'order_id' => $order->id,
            'nama_penginput' => $creator->name,
            'tanggal_dokumen' => now()->toDateString(),
            'scope_items' => [['description' => 'Pekerjaan']],
            'created_by' => $creator->id,
        ]);
    }

    private function hpp(Order $order, User $creator, string $status): Hpp
    {
        return Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'status' => $status,
            'created_by' => $creator->id,
        ]);
    }
}
