<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\AdminRoleMenuAccess;
use App\Models\BudgetVerification;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\OrderScopeOfWork;
use App\Models\OrderWorkshop;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\AdminMenuPermissions;
use App\Support\AdminMenuRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_search_exact_order_and_exact_match_has_priority(): void
    {
        $admin = $this->superAdmin();
        $prefix = $this->order(['nomor_order' => '1705035301']);
        $exact = $this->order(['nomor_order' => '17050353']);

        $response = $this->actingAs($admin)->getJson(route('admin.global-search.index', ['q' => '17050353']));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'results')
            ->assertJsonPath('results.0.id', $exact->id)
            ->assertJsonPath('results.1.id', $prefix->id);
    }

    public function test_search_supports_notification_number_and_job_name_keywords(): void
    {
        $admin = $this->superAdmin();
        $order = $this->order([
            'notifikasi' => '3000099988',
            'nama_pekerjaan' => 'Fabrikasi Support Live Ring',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.global-search.index', ['q' => '3000099988']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $order->id);

        $this->actingAs($admin)
            ->getJson(route('admin.global-search.index', ['q' => 'support ring']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $order->id);
    }

    public function test_search_returns_at_most_five_unique_orders(): void
    {
        $admin = $this->superAdmin();

        foreach (range(1, 8) as $index) {
            $this->order([
                'nomor_order' => '1705999'.$index,
                'notifikasi' => '3000999'.$index,
                'nama_pekerjaan' => 'Fabrikasi Pompa '.$index,
            ]);
        }

        $this->actingAs($admin)
            ->getJson(route('admin.global-search.index', ['q' => 'fabrikasi']))
            ->assertOk()
            ->assertJsonCount(5, 'results');
    }

    public function test_wildcards_do_not_expand_into_an_unbounded_search(): void
    {
        $admin = $this->superAdmin();
        $this->order(['nama_pekerjaan' => 'Pekerjaan Normal']);

        $this->actingAs($admin)
            ->getJson(route('admin.global-search.index', ['q' => '%%']))
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }

    public function test_validation_rejects_short_and_overlong_queries(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->getJson(route('admin.global-search.index', ['q' => '1']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');

        $this->actingAs($admin)
            ->getJson(route('admin.global-search.index', ['q' => str_repeat('a', 101)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_guest_and_non_admin_cannot_access_search_data(): void
    {
        $guestResponse = $this->getJson(route('admin.global-search.index', ['q' => '1705']));
        $this->assertContains($guestResponse->status(), [302, 401]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_USER]))
            ->getJson(route('admin.global-search.index', ['q' => '1705']))
            ->assertForbidden();
    }

    public function test_admin_without_order_permissions_receives_no_results(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);
        app(AdminMenuPermissions::class)->forget();
        $this->order();

        $this->actingAs($admin)
            ->getJson(route('admin.global-search.index', ['q' => '1705']))
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }

    public function test_split_order_permission_filters_inaccessible_workshop_orders(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);
        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_ORDER_JASA,
        ]);
        app(AdminMenuPermissions::class)->forget();

        $service = $this->order([
            'nomor_order' => '17051111',
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
        ]);
        $this->order([
            'nomor_order' => '17052222',
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.global-search.index', ['q' => '1705']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $service->id);
    }

    public function test_result_resolves_jasa_workshop_and_combined_paths(): void
    {
        $admin = $this->superAdmin();
        $service = $this->order([
            'nomor_order' => '17061001',
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
        ]);
        $workshop = $this->order([
            'nomor_order' => '17061002',
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
        ]);
        $combined = $this->order([
            'nomor_order' => '17061003',
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshopJasa->value,
        ]);

        foreach ([$workshop, $combined] as $order) {
            OrderWorkshop::query()->create([
                'order_id' => $order->id,
                'preparation_status' => OrderWorkshop::PREPARATION_WAITING_MATERIAL,
                'progress_status' => OrderWorkshop::PROGRESS_MENUNGGU_JADWAL,
            ]);
        }

        $response = $this->actingAs($admin)
            ->getJson(route('admin.global-search.index', ['q' => '170610']))
            ->assertOk();

        $results = collect($response->json('results'))->keyBy('id');
        $this->assertSame('Jasa', $results[$service->id]['type']);
        $this->assertSame('Bengkel', $results[$workshop->id]['type']);
        $this->assertSame('Gabungan', $results[$combined->id]['type']);
        $this->assertCount(2, $results[$combined->id]['paths']);
        $this->assertStringContainsString('Jasa:', $results[$combined->id]['status']);
        $this->assertStringContainsString('Bengkel:', $results[$combined->id]['status']);
    }

    public function test_resolver_reports_hpp_and_budget_progress_without_mutating_order(): void
    {
        $admin = $this->superAdmin();
        $order = $this->order(['nomor_order' => '17062001']);
        $originalUpdatedAt = $order->updated_at?->toISOString();
        $this->scopeOfWork($order, $admin);
        $hpp = $this->hpp($order, $admin, Hpp::STATUS_APPROVED);
        BudgetVerification::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'status_anggaran' => BudgetVerification::STATUS_WAITING,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.global-search.index', ['q' => '17062001']))
            ->assertOk()
            ->assertJsonPath('results.0.status', 'Menunggu Anggaran')
            ->assertJsonPath('results.0.action_label', 'Buka Verifikasi Anggaran');

        $this->assertSame($originalUpdatedAt, $order->fresh()->updated_at?->toISOString());
    }

    public function test_resolver_distinguishes_hpp_draft_review_rejected_and_inconsistent_statuses(): void
    {
        $admin = $this->superAdmin();
        $cases = [
            Hpp::STATUS_DRAFT => 'HPP Masih Draft',
            Hpp::STATUS_IN_REVIEW => 'HPP Dalam Proses Approval',
            Hpp::STATUS_REJECTED => 'HPP Ditolak',
            'unexpected_status' => 'Perlu Pemeriksaan',
        ];

        foreach ($cases as $status => $expected) {
            $order = $this->order();
            $this->scopeOfWork($order, $admin);
            $this->hpp($order, $admin, $status);

            $this->actingAs($admin)
                ->getJson(route('admin.global-search.index', ['q' => $order->nomor_order]))
                ->assertOk()
                ->assertJsonPath('results.0.status', $expected);
        }
    }

    public function test_resolver_reports_purchase_order_work_progress_and_destination(): void
    {
        $admin = $this->superAdmin();
        $order = $this->order(['nomor_order' => '17062501']);
        $this->scopeOfWork($order, $admin);
        $hpp = $this->hpp($order, $admin, Hpp::STATUS_APPROVED);
        BudgetVerification::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'status_anggaran' => BudgetVerification::STATUS_AVAILABLE,
            'kategori_item' => 'jasa',
            'kategori_biaya' => 'pemeliharaan',
            'cost_element' => '621000',
        ]);
        PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => 'PO-17062501',
            'approve_manager' => true,
            'progress_pekerjaan' => 45,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.global-search.index', ['q' => '17062501']))
            ->assertOk()
            ->assertJsonPath('results.0.status', 'Pekerjaan Berjalan 45%')
            ->assertJsonPath('results.0.action_label', 'Buka Progress Pekerjaan')
            ->assertJsonPath('results.0.url', route('admin.purchase-order.index', [
                'tab' => 'in_progress',
                'search' => '17062501',
            ]));
    }

    public function test_completed_legacy_workshop_is_reported_as_completed(): void
    {
        $admin = $this->superAdmin();
        $order = $this->order([
            'nomor_order' => '17063001',
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
        ]);
        $workshop = OrderWorkshop::query()->create([
            'order_id' => $order->id,
            'preparation_status' => OrderWorkshop::PREPARATION_COMPLETED,
            'progress_status' => OrderWorkshop::PROGRESS_DONE,
        ]);
        $workshop->legacy_completed_at = now();
        $workshop->save();

        $this->actingAs($admin)
            ->getJson(route('admin.global-search.index', ['q' => '17063001']))
            ->assertOk()
            ->assertJsonPath('results.0.type', 'Bengkel')
            ->assertJsonPath('results.0.status', 'Pekerjaan Selesai')
            ->assertJsonPath('results.0.tone', 'emerald');
    }

    public function test_database_text_is_returned_as_data_for_safe_frontend_rendering(): void
    {
        $admin = $this->superAdmin();
        $order = $this->order([
            'nama_pekerjaan' => '<img src=x onerror=alert(1)> Fabrikasi',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.global-search.index', ['q' => 'fabrikasi']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $order->id)
            ->assertJsonPath('results.0.job_name', '<img src=x onerror=alert(1)> Fabrikasi');

        $this->assertStringContainsString(
            'x-text="result.job_name"',
            file_get_contents(resource_path('views/admin/global-search/results.blade.php')),
        );
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function order(array $overrides = []): Order
    {
        static $sequence = 0;
        $sequence++;

        return Order::query()->create(array_merge([
            'nomor_order' => '170500'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
            'notifikasi' => '300000'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
            'nama_pekerjaan' => 'Pekerjaan Fabrikasi '.$sequence,
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
