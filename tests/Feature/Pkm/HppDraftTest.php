<?php

namespace Tests\Feature\Pkm;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Department;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\OrderScopeOfWork;
use App\Models\OutlineAgreement;
use App\Models\UnitWork;
use App\Models\User;
use App\Support\HppApprovalFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HppDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_access_is_limited_to_pkm_and_super_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PKM]))
            ->get(route('pkm.hpp.index'))->assertOk();

        foreach ([User::ROLE_USER, User::ROLE_APPROVER] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('pkm.hpp.index'))->assertForbidden();
        }

        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]))->get(route('pkm.hpp.index'))->assertOk();
    }

    public function test_create_only_lists_eligible_orders_without_hpp(): void
    {
        [$pkm, $oa, $eligible] = $this->fixture();
        $notApproved = $this->makeOrder($pkm, 'ORD-NOT-APPROVED', OrderUserNoteStatus::Pending->value, true);
        $withoutScope = $this->makeOrder($pkm, 'ORD-NO-SCOPE', OrderUserNoteStatus::ApprovedJasa->value, false);
        $withHpp = $this->makeOrder($pkm, 'ORD-HAS-HPP', OrderUserNoteStatus::ApprovedJasa->value, true);
        $this->makeHpp($withHpp, $oa, $pkm);

        $this->actingAs($pkm)
            ->get(route('pkm.hpp.create'))
            ->assertOk()
            ->assertSee($eligible->nomor_order)
            ->assertDontSee($notApproved->nomor_order)
            ->assertDontSee($withoutScope->nomor_order)
            ->assertDontSee($withHpp->nomor_order);
    }

    public function test_pkm_store_is_always_draft_and_calculated_by_backend(): void
    {
        [$pkm, $oa, $order] = $this->fixture();

        $this->actingAs($pkm)
            ->post(route('pkm.hpp.store'), [
                ...$this->payload($order, $oa, 2, 150000000),
                'action' => 'submit',
                'status' => Hpp::STATUS_APPROVED,
                'submitted_at' => now(),
                'document_no' => 'FAKE',
                'nilai_hpp_bucket' => 'under',
                'total_keseluruhan' => 1,
                'approval_flow' => ['FAKE APPROVER'],
            ])
            ->assertRedirect(route('pkm.hpp.index'));

        $hpp = Hpp::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame(Hpp::STATUS_DRAFT, $hpp->status);
        $this->assertNull($hpp->submitted_at);
        $this->assertNull($hpp->document_no);
        $this->assertNull($hpp->document_sequence);
        $this->assertNull($hpp->document_year);
        $this->assertSame($pkm->id, $hpp->created_by);
        $this->assertSame(300000000.0, (float) $hpp->total_keseluruhan);
        $this->assertSame('over', $hpp->nilai_hpp_bucket);
        $this->assertSame('FAB-DALAM-OVER250', $hpp->approval_case);
        $this->assertSame('DIROPS', collect($hpp->approval_flow)->last());
        $this->assertSame(0, $hpp->signatures()->count());
    }

    public function test_one_order_can_only_have_one_hpp(): void
    {
        [$pkm, $oa, $order] = $this->fixture();
        $this->actingAs($pkm)->post(route('pkm.hpp.store'), $this->payload($order, $oa))->assertRedirect();

        $this->actingAs($pkm)
            ->from(route('pkm.hpp.create'))
            ->post(route('pkm.hpp.store'), $this->payload($order, $oa))
            ->assertSessionHasErrors('order_id');

        $this->assertSame(1, Hpp::query()->where('order_id', $order->id)->count());
    }

    public function test_admin_draft_is_visible_and_editable_by_pkm_without_changing_creator(): void
    {
        [$pkm, $oa, $order] = $this->fixture();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $hpp = $this->makeHpp($order, $oa, $admin);

        $this->actingAs($pkm)->get(route('pkm.hpp.index'))
            ->assertOk()->assertSee($order->nomor_order);

        $this->actingAs($pkm)
            ->put(route('pkm.hpp.update', $hpp), [
                ...$this->payload($order, $oa, 2, 500000),
                'hpp_updated_at' => $hpp->getRawOriginal('updated_at'),
            ])->assertRedirect(route('pkm.hpp.index'));

        $this->assertSame($admin->id, $hpp->fresh()->created_by);
        $this->assertSame(1000000.0, (float) $hpp->fresh()->total_keseluruhan);
    }

    public function test_non_draft_hpp_cannot_be_edited_or_updated_by_pkm(): void
    {
        foreach ([Hpp::STATUS_IN_REVIEW, Hpp::STATUS_APPROVED, Hpp::STATUS_REJECTED] as $status) {
            [$pkm, $oa, $order] = $this->fixture(uniqid());
            $hpp = $this->makeHpp($order, $oa, $pkm, ['status' => $status]);

            $this->actingAs($pkm)->get(route('pkm.hpp.edit', $hpp))->assertForbidden();
            $this->actingAs($pkm)
                ->put(route('pkm.hpp.update', $hpp), [
                    ...$this->payload($order, $oa),
                    'hpp_updated_at' => $hpp->getRawOriginal('updated_at'),
                ])->assertSessionHasErrors('hpp');
        }
    }

    public function test_stale_draft_update_is_rejected(): void
    {
        [$pkm, $oa, $order] = $this->fixture();
        $hpp = $this->makeHpp($order, $oa, $pkm);
        $oldTimestamp = $hpp->getRawOriginal('updated_at');
        $hpp->forceFill([
            'cost_centre' => 'UPDATED-ELSEWHERE',
            'updated_at' => now()->addSecond(),
        ])->saveQuietly();

        $this->actingAs($pkm)
            ->put(route('pkm.hpp.update', $hpp), [
                ...$this->payload($order, $oa),
                'hpp_updated_at' => $oldTimestamp,
            ])->assertSessionHasErrors('hpp_updated_at');
    }

    public function test_admin_index_marks_only_hpp_created_by_pkm(): void
    {
        [$pkm, $oa, $order] = $this->fixture();
        $this->makeHpp($order, $oa, $pkm);
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.hpp.index'));

        $response->assertOk()->assertSee($order->nomor_order)->assertSee('Dibuat oleh PKM');
    }

    public function test_pkm_submit_delete_duplicate_and_signature_routes_do_not_exist(): void
    {
        foreach (['pkm.hpp.submit', 'pkm.hpp.destroy', 'pkm.hpp.duplicate', 'pkm.hpp.signature'] as $name) {
            $this->assertFalse(\Illuminate\Support\Facades\Route::has($name));
        }
    }

    /** @return array{User,OutlineAgreement,Order} */
    private function fixture(string $suffix = ''): array
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $department = Department::query()->create(['name' => 'Department '.$suffix.uniqid()]);
        $unit = UnitWork::query()->create([
            'department_id' => $department->id,
            'name' => 'Unit Controller '.$suffix.uniqid(),
        ]);
        $oa = OutlineAgreement::query()->create([
            'nomor_oa' => 'OA-'.$suffix.uniqid(),
            'unit_work_id' => $unit->id,
            'jenis_kontrak' => 'Controller Section',
            'nama_kontrak' => 'Kontrak HPP',
            'nilai_kontrak_awal' => 1000000000,
            'periode_awal_start' => '2026-01-01',
            'periode_awal_end' => '2026-12-31',
            'current_total_nilai' => 1000000000,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-12-31',
            'status' => OutlineAgreement::STATUS_ACTIVE,
            'created_by' => $pkm->id,
        ]);
        $order = $this->makeOrder($pkm, 'ORD-PKM-'.$suffix.uniqid(), OrderUserNoteStatus::ApprovedJasa->value, true);

        return [$pkm, $oa, $order];
    }

    private function makeOrder(User $user, string $number, ?string $status, bool $scope): Order
    {
        $order = Order::query()->create([
            'nomor_order' => $number,
            'nama_pekerjaan' => 'Pekerjaan '.$number,
            'unit_kerja' => 'Unit Peminta',
            'seksi' => 'Seksi Peminta',
            'deskripsi' => 'Test HPP PKM',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-07-01',
            'target_selesai' => '2026-07-10',
            'catatan_status' => $status,
            'created_by' => $user->id,
        ]);

        if ($scope) {
            OrderScopeOfWork::query()->create([
                'order_id' => $order->id,
                'nama_penginput' => $user->name,
                'tanggal_dokumen' => '2026-07-01',
                'scope_items' => [['scope_pekerjaan' => 'Test', 'qty' => '1', 'satuan' => 'lot']],
                'tanda_tangan' => 'data:image/png;base64,test',
                'created_by' => $user->id,
            ]);
        }

        return $order;
    }

    private function makeHpp(Order $order, OutlineAgreement $oa, User $creator, array $attributes = []): Hpp
    {
        return Hpp::query()->create([
            'order_id' => $order->id,
            'outline_agreement_id' => $oa->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => HppApprovalFlow::displayArea('Dalam'),
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'approval_flow' => HppApprovalFlow::resolveApprovalFlow('Fabrikasi', 'Dalam', 'under'),
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_DRAFT,
            'created_by' => $creator->id,
            ...$attributes,
        ]);
    }

    private function payload(Order $order, OutlineAgreement $oa, int $qty = 1, int $price = 1000000): array
    {
        return [
            'order_id' => $order->id,
            'outline_agreement_id' => $oa->id,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => HppApprovalFlow::displayArea('Dalam'),
            'cost_centre' => 'CC-PKM',
            'jenis_label_visible' => [0 => 'Jasa'],
            'sub_jenis_item' => [0 => ['Subjenis']],
            'kategori_item' => [0 => ['Kategori']],
            'nama_item' => [0 => ['Pekerjaan draft']],
            'jumlah_item' => [0 => ['1 lot']],
            'qty' => [0 => [$qty]],
            'satuan' => [0 => ['Lot']],
            'harga_satuan' => [0 => [$price]],
            'keterangan' => [0 => ['Draft PKM']],
        ];
    }
}
