<?php

namespace Tests\Feature\Pkm;

use App\Http\Controllers\Pkm\LhppController;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\User;
use App\Services\Approvals\ApprovalNotificationService;
use App\Support\BastApprovalSignatureBuilder;
use App\Support\BastApproverResolver;
use App\Support\BastEffectiveApprovalFlowResolver;
use ReflectionMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BastDuplicateManagerConsolidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_different_managers_are_not_consolidated(): void
    {
        $users = $this->approverUsers();
        $resolver = $this->effectiveResolver($users);
        $this->bindApproverResolver($users);
        $this->mockNotificationService();
        $bast = $this->makeBast([
            'Manager PKM',
            'Manager Peminta',
            'Manager Pengendali',
            'SM Pengendali',
            'GM Pengendali',
        ]);

        $labels = $resolver->effectiveFlowLabels($bast, $bast->approval_flow);

        $this->assertContains('Manager Peminta', $labels);
        $this->assertContains('Manager Pengendali', $labels);

        app(BastApprovalSignatureBuilder::class)->ensureSignatures($bast);
        $this->assertSame(
            ['manager_peminta', 'manager_pengendali'],
            $bast->signatures()
                ->whereIn('role_key', ['manager_peminta', 'manager_pengendali'])
                ->orderBy('step_order')
                ->pluck('role_key')
                ->all()
        );
    }

    public function test_same_managers_create_one_canonical_manager_signature_with_contiguous_steps(): void
    {
        $users = $this->approverUsers(managerSame: true);
        $this->bindApproverResolver($users);
        $this->mockNotificationService();
        $bast = $this->makeBast([
            'Manager PKM',
            'Manager Peminta',
            'Manager Pengendali',
            'SM Pengendali',
            'GM Pengendali',
            'DIROPS',
        ], 'over_250');

        app(BastApprovalSignatureBuilder::class)->ensureSignatures($bast);
        $signatures = $bast->signatures()->orderBy('step_order')->get();

        $this->assertSame(
            ['manager_pkm', 'manager_pengendali', 'sm_pengendali', 'gm_pengendali', 'dirops'],
            $signatures->pluck('role_key')->all()
        );
        $this->assertSame([1, 2, 3, 4, 5], $signatures->pluck('step_order')->all());
        $this->assertSame(1, $signatures->where('role_key', 'manager_pengendali')->count());
        $this->assertSame(0, $signatures->where('role_key', 'manager_peminta')->count());
        $this->assertSame('dirops', $signatures->last()->role_key);
    }

    public function test_only_the_manager_pair_is_consolidated_when_gm_uses_same_user(): void
    {
        $users = $this->approverUsers(managerSame: true);
        $users['GM Pengendali'] = $users['Manager Pengendali'];
        $this->bindApproverResolver($users);
        $this->mockNotificationService();
        $bast = $this->makeBast([
            'Manager PKM',
            'Manager Peminta',
            'Manager Pengendali',
            'GM Pengendali',
        ]);

        app(BastApprovalSignatureBuilder::class)->ensureSignatures($bast);
        $signatures = $bast->signatures()->orderBy('step_order')->get();

        $this->assertSame(
            ['manager_pkm', 'manager_pengendali', 'gm_pengendali'],
            $signatures->pluck('role_key')->all()
        );
        $this->assertSame(
            $signatures->firstWhere('role_key', 'manager_pengendali')->signer_user_id,
            $signatures->firstWhere('role_key', 'gm_pengendali')->signer_user_id
        );
    }

    public function test_same_manager_receives_one_token_and_then_flow_advances_to_sm(): void
    {
        $users = $this->approverUsers(managerSame: true);
        $this->bindApproverResolver($users);
        $this->mockNotificationService();
        $bast = $this->makeBast([
            'Manager PKM',
            'Manager Peminta',
            'Manager Pengendali',
            'SM Pengendali',
            'GM Pengendali',
        ]);
        $builder = app(BastApprovalSignatureBuilder::class);

        $builder->ensureSignatures($bast);
        $builder->activateFirstSignature($bast);
        $managerPkm = $bast->signatures()->where('role_key', 'manager_pkm')->firstOrFail();
        $managerPkm->update(['status' => LhppBastSignature::STATUS_SIGNED, 'signed_at' => now()]);
        $builder->activateNextSignature($managerPkm);

        $managerSignatures = $bast->signatures()
            ->whereIn('role_key', ['manager_peminta', 'manager_pengendali'])
            ->get();
        $this->assertCount(1, $managerSignatures);
        $this->assertTrue($managerSignatures->first()->isPending());
        $this->assertNotNull($managerSignatures->first()->token_hash);

        $manager = $managerSignatures->first();
        $manager->update(['status' => LhppBastSignature::STATUS_SIGNED, 'signed_at' => now()]);
        $builder->activateNextSignature($manager);

        $this->assertTrue($bast->signatures()->where('role_key', 'sm_pengendali')->firstOrFail()->isPending());
        $this->assertTrue($bast->signatures()->where('role_key', 'gm_pengendali')->firstOrFail()->isLocked());
    }

    public function test_server_normalizes_legacy_frontend_flow_for_same_manager(): void
    {
        $users = $this->approverUsers(managerSame: true);
        $this->bindApproverResolver($users);
        $context = new LhppBast(['termin_type' => 'termin_1', 'tipe_pekerjaan' => 'Pengerjaan Mesin']);
        $method = new ReflectionMethod(LhppController::class, 'resolveApprovalPayload');

        $payload = $method->invoke(
            app(LhppController::class),
            'termin_1',
            ['termin_1_nilai' => '100000000.00', 'termin_2_nilai' => '0.00'],
            ['Manager PKM', 'Manager Peminta', 'Manager Pengendali', 'SM Pengendali', 'GM Pengendali'],
            false,
            $context,
        );

        $this->assertSame(
            ['Manager PKM', 'Manager Pengendali', 'SM Pengendali', 'GM Pengendali'],
            $payload['approval_flow']
        );
    }

    /** @return array<string, User> */
    private function approverUsers(bool $managerSame = false): array
    {
        $managerPeminta = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $managerPengendali = $managerSame
            ? $managerPeminta
            : User::factory()->create(['role' => User::ROLE_APPROVER]);

        return [
            'Manager PKM' => User::factory()->create(['role' => User::ROLE_APPROVER]),
            'Manager Peminta' => $managerPeminta,
            'Manager Pengendali' => $managerPengendali,
            'SM Pengendali' => User::factory()->create(['role' => User::ROLE_APPROVER]),
            'GM Pengendali' => User::factory()->create(['role' => User::ROLE_APPROVER]),
            'DIROPS' => User::factory()->create(['role' => User::ROLE_APPROVER]),
        ];
    }

    private function effectiveResolver(array $users): BastEffectiveApprovalFlowResolver
    {
        return new BastEffectiveApprovalFlowResolver($this->approverResolverMock($users));
    }

    private function bindApproverResolver(array $users): void
    {
        $this->app->instance(BastApproverResolver::class, $this->approverResolverMock($users));
    }

    private function approverResolverMock(array $users): BastApproverResolver
    {
        $mock = Mockery::mock(BastApproverResolver::class);
        $mock->shouldReceive('resolveApprover')
            ->andReturnUsing(function (LhppBast $bast, string $label) use ($users): array {
                $user = $users[$label];
                $roleKey = match ($label) {
                    'Manager PKM' => 'manager_pkm',
                    'Manager Peminta' => 'manager_peminta',
                    'Manager Pengendali' => 'manager_pengendali',
                    'SM Pengendali' => 'sm_pengendali',
                    'GM Pengendali' => 'gm_pengendali',
                    'DIROPS' => 'dirops',
                };

                return [
                    'role_key' => $roleKey,
                    'role_label' => $label,
                    'user' => $user,
                    'position' => $label,
                    'department' => 'PMMS',
                    'unit' => 'Workshop',
                    'section' => 'Machine Workshop',
                ];
            });

        return $mock;
    }

    private function mockNotificationService(): void
    {
        $mock = Mockery::mock(ApprovalNotificationService::class);
        $mock->shouldReceive('sendBast')->zeroOrMoreTimes()->andReturnTrue();
        $this->app->instance(ApprovalNotificationService::class, $mock);
    }

    /** @param list<string> $flow */
    private function makeBast(array $flow, string $threshold = 'under_250'): LhppBast
    {
        $creator = User::factory()->create();
        $order = Order::query()->create([
            'nomor_order' => 'BAST-MANAGER-'.uniqid(),
            'nama_pekerjaan' => 'Konsolidasi Manager',
            'unit_kerja' => 'Workshop',
            'seksi' => 'Machine Workshop',
            'deskripsi' => 'Test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-07-01',
            'target_selesai' => '2026-07-10',
            'created_by' => $creator->id,
        ]);

        return LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => '2026-07-13',
            'tipe_pekerjaan' => 'Pengerjaan Mesin',
            'approval_threshold' => $threshold,
            'approval_flow' => $flow,
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'quality_control_status' => 'approved',
            'created_by' => $creator->id,
        ]);
    }
}
