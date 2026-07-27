<?php

namespace Tests\Feature\Approval;

use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\InitialWork;
use App\Models\InitialWorkSignature;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\OrderScopeOfWork;
use App\Models\QualityControlReport;
use App\Models\QualityControlSignature;
use App\Models\User;
use App\Support\RecentApprovalSignatureResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecentApprovalSignatureResolverTest extends TestCase
{
    use RefreshDatabase;

    private const PARAF_DATA = 'data:image/png;base64,UEFSQUY=';

    private const FULL_SIGNATURE_DATA = 'data:image/png;base64,RlVMTA==';

    private const WORKSHOP_SIGNATURE_DATA = 'data:image/png;base64,V09SS1NIT1A=';

    private const QC_SIGNATURE_DATA = 'data:image/png;base64,UUM=';

    public function test_initial_history_ignores_newer_full_bast_signature(): void
    {
        [$user, $order] = $this->context();
        $this->hppSignature($order, $user, 'manager_pengendali', self::PARAF_DATA, '2026-07-27 09:00:00');
        $this->bastSignature($order, $user, self::FULL_SIGNATURE_DATA, '2026-07-27 10:00:00');

        $this->assertSame(
            self::PARAF_DATA,
            app(RecentApprovalSignatureResolver::class)->latestInitialForHppManager($user)
        );
    }

    public function test_full_history_ignores_newer_hpp_initial_and_generic_alias_is_safe(): void
    {
        [$user, $order] = $this->context();
        $this->bastSignature($order, $user, self::FULL_SIGNATURE_DATA, '2026-07-27 09:00:00');
        $this->hppSignature($order, $user, 'manager_pengendali', self::PARAF_DATA, '2026-07-27 10:00:00');
        $resolver = app(RecentApprovalSignatureResolver::class);

        $this->assertSame(self::FULL_SIGNATURE_DATA, $resolver->latestFullSignatureForUser($user));
        $this->assertSame(self::FULL_SIGNATURE_DATA, $resolver->latestDataUrlForUser($user));
    }

    public function test_workshop_manager_context_uses_full_signature_history(): void
    {
        [$user, $order] = $this->context();
        $this->hppSignature($order, $user, 'manager_pengendali', self::PARAF_DATA, '2026-07-27 10:00:00');
        $workshopHistory = $this->hppSignature(
            $order,
            $user,
            'workshop_manager_pengendali',
            self::WORKSHOP_SIGNATURE_DATA,
            '2026-07-27 09:00:00'
        );
        $pendingWorkshop = new HppSignature(['role_key' => 'workshop_manager_pengendali']);

        $this->assertSame(
            self::WORKSHOP_SIGNATURE_DATA,
            app(RecentApprovalSignatureResolver::class)->latestForHppSignature($user, $pendingWorkshop)
        );
        $this->assertSame('workshop_manager_pengendali', $workshopHistory->role_key);
    }

    public function test_latest_full_signature_is_selected_across_all_full_document_sources(): void
    {
        [$user, $order] = $this->context();
        $this->hppSignature($order, $user, 'workshop_manager_pengendali', self::WORKSHOP_SIGNATURE_DATA, '2026-07-27 08:00:00');
        $this->bastSignature($order, $user, self::FULL_SIGNATURE_DATA, '2026-07-27 09:00:00');
        $this->initialWorkSignature($order, $user, self::WORKSHOP_SIGNATURE_DATA, '2026-07-27 10:00:00');
        $this->qualityControlSignature($order, $user, self::QC_SIGNATURE_DATA, '2026-07-27 11:00:00');

        $this->assertSame(
            self::QC_SIGNATURE_DATA,
            app(RecentApprovalSignatureResolver::class)->latestFullSignatureForUser($user)
        );
    }

    public function test_scope_of_work_signature_is_available_as_full_signature_history(): void
    {
        [$user, $order] = $this->context();
        $this->hppSignature($order, $user, 'manager_pengendali', self::PARAF_DATA, '2026-07-27 10:00:00');
        OrderScopeOfWork::query()->create([
            'order_id' => $order->id,
            'nama_penginput' => $user->name,
            'tanggal_dokumen' => '2026-07-27',
            'scope_items' => [['scope_pekerjaan' => 'Scope test', 'qty' => '1', 'satuan' => 'Lot']],
            'tanda_tangan' => self::FULL_SIGNATURE_DATA,
            'created_by' => $user->id,
        ]);

        $this->assertSame(
            self::FULL_SIGNATURE_DATA,
            app(RecentApprovalSignatureResolver::class)->latestFullSignatureForUser($user)
        );
    }

    public function test_initial_and_full_role_filters_are_strict(): void
    {
        [$user, $order] = $this->context();
        foreach (['workshop_manager_pengendali', 'sm_pengendali', 'gm_pengendali', 'dirops'] as $index => $role) {
            $this->hppSignature($order, $user, $role, self::FULL_SIGNATURE_DATA, "2026-07-27 0{$index}:00:00");
        }

        $this->assertNull(app(RecentApprovalSignatureResolver::class)->latestInitialForHppManager($user));

        foreach (['manager_peminta', 'manager_pengendali', 'manager_counter_part'] as $index => $role) {
            $this->hppSignature($order, $user, $role, self::PARAF_DATA, "2026-07-28 0{$index}:00:00");
        }

        $this->assertSame(
            self::FULL_SIGNATURE_DATA,
            app(RecentApprovalSignatureResolver::class)->latestFullSignatureForUser($user)
        );
    }

    public function test_other_user_unsigned_and_empty_values_are_ignored(): void
    {
        [$user, $order] = $this->context();
        $other = User::factory()->create();
        $this->hppSignature($order, $other, 'manager_pengendali', self::PARAF_DATA, '2026-07-27 12:00:00');
        $this->hppSignature($order, $user, 'manager_pengendali', self::PARAF_DATA, '2026-07-27 11:00:00', HppSignature::STATUS_PENDING);
        $this->hppSignature($order, $user, 'manager_pengendali', '', '2026-07-27 10:00:00');

        $this->assertNull(app(RecentApprovalSignatureResolver::class)->latestInitialForHppManager($user));
    }

    public function test_missing_latest_file_falls_back_to_older_data_uri(): void
    {
        [$user, $order] = $this->context();
        $this->bastSignature($order, $user, self::FULL_SIGNATURE_DATA, '2026-07-27 09:00:00');
        $this->hppSignature(
            $order,
            $user,
            'workshop_manager_pengendali',
            'missing/signature-file.png',
            '2026-07-27 10:00:00'
        );

        $this->assertSame(
            self::FULL_SIGNATURE_DATA,
            app(RecentApprovalSignatureResolver::class)->latestFullSignatureForUser($user)
        );
    }

    /** @return array{User, Order} */
    private function context(): array
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'nomor_order' => 'RECENT-'.uniqid(),
            'nama_pekerjaan' => 'Recent signature test',
            'unit_kerja' => 'Unit',
            'seksi' => 'Section',
            'deskripsi' => 'Test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-07-27',
            'target_selesai' => '2026-08-01',
            'created_by' => $user->id,
        ]);

        return [$user, $order];
    }

    private function hppSignature(
        Order $order,
        User $user,
        string $roleKey,
        string $data,
        string $signedAt,
        string $status = HppSignature::STATUS_SIGNED
    ): HppSignature {
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_IN_REVIEW,
            'created_by' => $user->id,
        ]);

        return $hpp->signatures()->create([
            'step_order' => 1,
            'role_key' => $roleKey,
            'role_label' => $roleKey,
            'signer_user_id' => $user->id,
            'signer_name_snapshot' => $user->name,
            'signer_position_snapshot' => $roleKey,
            'status' => $status,
            'signature_data' => $data,
            'signed_at' => $signedAt,
        ]);
    }

    private function bastSignature(Order $order, User $user, string $data, string $signedAt): LhppBastSignature
    {
        $bast = LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_'.uniqid(),
            'nomor_order' => $order->nomor_order,
            'tanggal_bast' => '2026-07-27',
            'created_by' => $user->id,
        ]);

        return $bast->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager_pkm',
            'role_label' => 'Manager PKM',
            'signer_user_id' => $user->id,
            'status' => LhppBastSignature::STATUS_SIGNED,
            'signature_data' => $data,
            'signed_at' => $signedAt,
        ]);
    }

    private function initialWorkSignature(Order $order, User $user, string $data, string $signedAt): InitialWorkSignature
    {
        $initialWork = InitialWork::query()->create([
            'order_id' => $order->id,
            'nomor_initial_work' => 'IW-'.uniqid(),
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'perihal' => 'Recent signature test',
            'tanggal_initial_work' => '2026-07-27',
            'functional_location' => ['FL-01'],
            'scope_pekerjaan' => ['Scope'],
            'qty' => ['1'],
            'stn' => ['Lot'],
            'created_by' => $user->id,
        ]);

        return $initialWork->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager',
            'role_label' => 'Manager',
            'signer_user_id' => $user->id,
            'status' => InitialWorkSignature::STATUS_SIGNED,
            'signature_path' => $data,
            'signed_at' => $signedAt,
        ]);
    }

    private function qualityControlSignature(Order $order, User $user, string $data, string $signedAt): QualityControlSignature
    {
        $report = QualityControlReport::query()->create([
            'order_id' => $order->id,
            'type' => QualityControlReport::TYPE_FABRICATION,
            'report_no' => 'QC-'.uniqid(),
            'report_date' => '2026-07-27',
            'status' => QualityControlReport::STATUS_SUBMITTED,
            'payload' => [],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return $report->signatures()->create([
            'step_order' => 1,
            'role_key' => 'workshop_manager',
            'role_label' => 'Manager Workshop',
            'signer_user_id' => $user->id,
            'status' => QualityControlSignature::STATUS_SIGNED,
            'signature_data' => $data,
            'signed_at' => $signedAt,
        ]);
    }
}
