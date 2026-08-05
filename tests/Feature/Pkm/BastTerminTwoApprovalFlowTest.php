<?php

namespace Tests\Feature\Pkm;

use App\Http\Controllers\Pkm\LhppController;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\User;
use App\Services\Approvals\ApprovalNotificationService;
use App\Services\Approvals\BastSmPengendaliSynchronizer;
use App\Support\BastApprovalFlow;
use App\Support\BastApprovalSignatureBuilder;
use App\Support\BastApproverResolver;
use App\Support\BastEffectiveApprovalFlowResolver;
use App\Support\BastPdfSignatureLayoutResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class BastTerminTwoApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    private const TERMIN_ONE_UNDER = [
        'Manager PKM',
        'Manager Peminta',
        'Manager Pengendali',
        'SM Pengendali',
        'GM Pengendali',
    ];

    private const TERMIN_TWO_UNDER = [
        'Manager PKM',
        'Manager Pengendali',
        'SM Pengendali',
        'GM Pengendali',
    ];

    public function test_flow_source_is_termin_aware_and_backward_compatible(): void
    {
        $terminOneOver = [...self::TERMIN_ONE_UNDER, 'DIROPS'];
        $terminTwoOver = [...self::TERMIN_TWO_UNDER, 'DIROPS'];

        $this->assertSame(self::TERMIN_ONE_UNDER, BastApprovalFlow::resolveApprovalFlow('under_250'));
        $this->assertSame($terminOneOver, BastApprovalFlow::resolveApprovalFlow('over_250'));
        $this->assertSame(self::TERMIN_ONE_UNDER, BastApprovalFlow::resolveApprovalFlow('under_250', 'termin_1'));
        $this->assertSame($terminOneOver, BastApprovalFlow::resolveApprovalFlow('over_250', 'termin_1'));
        $this->assertSame(self::TERMIN_TWO_UNDER, BastApprovalFlow::resolveApprovalFlow('under_250', 'termin_2'));
        $this->assertSame($terminTwoOver, BastApprovalFlow::resolveApprovalFlow('over_250', 'termin_2'));
        $this->assertSame(self::TERMIN_TWO_UNDER, BastApprovalFlow::resolveApprovalFlow('under_250', 'termin-2'));
        $this->assertSame(self::TERMIN_TWO_UNDER, BastApprovalFlow::resolveApprovalFlow('under_250', '2'));
        $this->assertSame(self::TERMIN_ONE_UNDER, BastApprovalFlow::flowMatrix('termin_1')['under_250']);
        $this->assertSame(self::TERMIN_TWO_UNDER, BastApprovalFlow::flowMatrix('termin_2')['under_250']);
        $this->assertSame([], BastApprovalFlow::resolveApprovalFlow('unknown', 'termin_2'));

        $this->assertSame('BAST-T1-UNDER250', BastApprovalFlow::resolveApprovalCase('termin_1', 'under_250'));
        $this->assertSame('BAST-T1-OVER250', BastApprovalFlow::resolveApprovalCase('termin_1', 'over_250'));
        $this->assertSame('BAST-T2-UNDER250', BastApprovalFlow::resolveApprovalCase('termin_2', 'under_250'));
        $this->assertSame('BAST-T2-OVER250', BastApprovalFlow::resolveApprovalCase('termin-2', 'over_250'));
    }

    public function test_controller_builds_canonical_termin_two_payload(): void
    {
        $payload = $this->resolveApprovalPayload('termin_2', []);

        $this->assertSame('under_250', $payload['approval_threshold']);
        $this->assertSame('BAST-T2-UNDER250', $payload['approval_case']);
        $this->assertSame(self::TERMIN_TWO_UNDER, $payload['approval_flow']);
        $this->assertNotContains('Manager Peminta', $payload['approval_flow']);
    }

    #[DataProvider('requesterManagerAliases')]
    public function test_controller_rejects_requester_manager_aliases_in_raw_termin_two_flow(string $forbiddenRole): void
    {
        $submittedFlow = self::TERMIN_TWO_UNDER;
        array_splice($submittedFlow, 1, 0, [$forbiddenRole]);

        try {
            $this->resolveApprovalPayload('termin_2', $submittedFlow);
            $this->fail('Flow Termin 2 yang memuat Manager Peminta seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Manager Peminta tidak termasuk approval BAST Termin 2.',
                $exception->errors()['approval_flow'][0],
            );
        }
    }

    /** @return array<string, array{string}> */
    public static function requesterManagerAliases(): array
    {
        return [
            'display label' => ['Manager Peminta'],
            'legacy label' => ['Manager User'],
            'role key' => ['manager_peminta'],
        ];
    }

    public function test_termin_two_rejects_invalid_dirops_and_controller_order(): void
    {
        foreach ([
            ['Manager PKM', 'DIROPS', 'Manager Pengendali', 'SM Pengendali', 'GM Pengendali'],
            ['Manager PKM', 'SM Pengendali', 'Manager Pengendali', 'GM Pengendali', 'DIROPS'],
        ] as $submittedFlow) {
            try {
                $this->resolveApprovalPayload('termin_2', $submittedFlow, '300000000.00');
                $this->fail('Urutan flow Termin 2 manipulatif seharusnya ditolak.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('approval_flow', $exception->errors());
            }
        }
    }

    public function test_termin_two_under_creates_four_signatures_and_advances_directly_to_controller_manager(): void
    {
        $users = $this->approverUsers();
        $this->bindApproverResolver($users);
        $this->mockNotificationService();
        $bast = $this->makeBast('termin_2', 'under_250', []);
        $builder = app(BastApprovalSignatureBuilder::class);

        $builder->ensureSignatures($bast);
        $builder->activateFirstSignature($bast);
        $signatures = $bast->signatures()->orderBy('step_order')->get();

        $this->assertSame(
            ['manager_pkm', 'manager_pengendali', 'sm_pengendali', 'gm_pengendali'],
            $signatures->pluck('role_key')->all(),
        );
        $this->assertSame([1, 2, 3, 4], $signatures->pluck('step_order')->all());
        $this->assertSame(LhppBastSignature::STATUS_PENDING, $signatures->first()->status);
        $this->assertSame('manager_pkm', $signatures->first()->role_key);
        $this->assertFalse($signatures->contains('role_key', 'manager_peminta'));

        $managerPkm = $signatures->first();
        $managerPkm->update([
            'status' => LhppBastSignature::STATUS_SIGNED,
            'signed_at' => now(),
        ]);
        $builder->activateNextSignature($managerPkm);

        $this->assertTrue($bast->signatures()->where('role_key', 'manager_pengendali')->firstOrFail()->isPending());
        $this->assertFalse($bast->signatures()->where('role_key', 'manager_peminta')->exists());
        $this->assertFalse(LhppBastSignature::query()
            ->where('signer_user_id', $users['Manager Peminta']->id)
            ->exists());

        $bast->unsetRelation('signatures');
        $this->assertSame(4, $bast->approvalStepCount());
        $this->assertSame(1, $bast->approvalSignedCount());
        $this->assertSame(25, $bast->approvalProgressPercent());
    }

    public function test_termin_two_over_creates_five_signatures_with_dirops_last(): void
    {
        $this->bindApproverResolver($this->approverUsers());
        $this->mockNotificationService();
        $bast = $this->makeBast('termin_2', 'over_250', []);

        app(BastApprovalSignatureBuilder::class)->ensureSignatures($bast);
        $signatures = $bast->signatures()->orderBy('step_order')->get();

        $this->assertSame(
            ['manager_pkm', 'manager_pengendali', 'sm_pengendali', 'gm_pengendali', 'dirops'],
            $signatures->pluck('role_key')->all(),
        );
        $this->assertSame(5, $signatures->count());
        $this->assertSame('dirops', $signatures->last()->role_key);
        $this->assertFalse($signatures->contains('role_key', 'manager_peminta'));
    }

    public function test_effective_flow_keeps_termin_two_controller_manager_when_requester_manager_is_same_user(): void
    {
        $users = $this->approverUsers(managerSame: true);
        $resolver = new BastEffectiveApprovalFlowResolver($this->approverResolverMock($users));
        $bast = $this->makeBast('termin_2', 'under_250', self::TERMIN_TWO_UNDER);

        $this->assertSame(
            self::TERMIN_TWO_UNDER,
            $resolver->effectiveFlowLabels($bast, self::TERMIN_TWO_UNDER),
        );
        $this->assertSame(
            ['manager_pkm', 'manager_pengendali', 'sm_pengendali', 'gm_pengendali'],
            collect($resolver->resolveSteps($bast, self::TERMIN_TWO_UNDER))->pluck('role_key')->all(),
        );
    }

    public function test_sm_synchronizer_inserts_sm_without_reintroducing_requester_manager_and_is_idempotent(): void
    {
        $users = $this->approverUsers();
        $this->bindApproverResolver($users);
        $this->mockNotificationService();
        $bast = $this->makeBast('termin_2', 'under_250', [
            'Manager PKM',
            'Manager Pengendali',
            'GM Pengendali',
        ]);
        $this->signature($bast, 1, 'manager_pkm', 'Manager PKM', $users['Manager PKM']);
        $this->signature($bast, 2, 'manager_pengendali', 'Manager Pengendali', $users['Manager Pengendali']);
        $this->signature($bast, 3, 'gm_pengendali', 'GM Pengendali', $users['GM Pengendali']);

        $synchronizer = app(BastSmPengendaliSynchronizer::class);
        $first = $synchronizer->sync($bast);
        $second = $synchronizer->sync($bast->fresh());

        $this->assertTrue($first['sm_added']);
        $this->assertSame('unchanged', $second['status']);
        $this->assertSame(self::TERMIN_TWO_UNDER, $bast->fresh()->approval_flow);
        $this->assertSame(
            ['manager_pkm', 'manager_pengendali', 'sm_pengendali', 'gm_pengendali'],
            $bast->signatures()->orderBy('step_order')->pluck('role_key')->all(),
        );
        $this->assertFalse($bast->signatures()->where('role_key', 'manager_peminta')->exists());
    }

    public function test_pdf_layout_uses_termin_two_snapshot_without_empty_requester_manager_cell(): void
    {
        $this->bindApproverResolver($this->approverUsers());
        $this->mockNotificationService();
        $bast = $this->makeBast('termin_2', 'under_250', self::TERMIN_TWO_UNDER);
        app(BastApprovalSignatureBuilder::class)->ensureSignatures($bast);
        $bast->unsetRelation('signatures');

        $layout = app(BastPdfSignatureLayoutResolver::class)->resolve($bast);

        $this->assertSame(
            ['gm_pengendali', 'sm_pengendali', 'manager_pengendali'],
            collect($layout['approval_cells'])->pluck('role_key')->all(),
        );
        $this->assertNotContains('manager_peminta', collect($layout['approval_cells'])->pluck('role_key')->all());
        $this->assertNotNull($layout['manager_pkm_signature']);
    }

    public function test_pdf_layout_preserves_historical_termin_two_requester_manager_snapshot(): void
    {
        $users = $this->approverUsers();
        $bast = $this->makeBast('termin_2', 'under_250', self::TERMIN_ONE_UNDER);
        $this->signature($bast, 1, 'manager_pkm', 'Manager PKM', $users['Manager PKM']);
        $this->signature($bast, 2, 'manager_peminta', 'Manager Peminta', $users['Manager Peminta']);
        $this->signature($bast, 3, 'manager_pengendali', 'Manager Pengendali', $users['Manager Pengendali']);
        $this->signature($bast, 4, 'sm_pengendali', 'SM Pengendali', $users['SM Pengendali']);
        $this->signature($bast, 5, 'gm_pengendali', 'GM Pengendali', $users['GM Pengendali']);

        $layout = app(BastPdfSignatureLayoutResolver::class)->resolve($bast);

        $this->assertContains('manager_peminta', collect($layout['approval_cells'])->pluck('role_key')->all());
        $this->assertSame(5, $bast->signatures()->count());
    }

    /**
     * @return array{approval_threshold: string, approval_case: ?string, approval_flow: list<string>}
     */
    private function resolveApprovalPayload(
        string $terminType,
        array $submittedFlow,
        string $terminValue = '100000000.00',
    ): array {
        $method = new ReflectionMethod(LhppController::class, 'resolveApprovalPayload');
        $context = new LhppBast([
            'termin_type' => $terminType,
            'tipe_pekerjaan' => 'Pengerjaan Mesin',
        ]);

        return $method->invoke(
            app(LhppController::class),
            $terminType,
            [
                'termin_1_nilai' => $terminType === 'termin_1' ? $terminValue : '0.00',
                'termin_2_nilai' => $terminType === 'termin_2' ? $terminValue : '0.00',
            ],
            $submittedFlow,
            false,
            $context,
        );
    }

    /** @return array<string, User> */
    private function approverUsers(bool $managerSame = false): array
    {
        $managerPeminta = User::factory()->create(['role' => User::ROLE_APPROVER]);

        return [
            'Manager PKM' => User::factory()->create(['role' => User::ROLE_APPROVER]),
            'Manager Peminta' => $managerPeminta,
            'Manager Pengendali' => $managerSame
                ? $managerPeminta
                : User::factory()->create(['role' => User::ROLE_APPROVER]),
            'SM Pengendali' => User::factory()->create(['role' => User::ROLE_APPROVER]),
            'GM Pengendali' => User::factory()->create(['role' => User::ROLE_APPROVER]),
            'DIROPS' => User::factory()->create(['role' => User::ROLE_APPROVER]),
        ];
    }

    /** @param array<string, User> $users */
    private function bindApproverResolver(array $users): void
    {
        $this->app->instance(BastApproverResolver::class, $this->approverResolverMock($users));
    }

    /** @param array<string, User> $users */
    private function approverResolverMock(array $users): BastApproverResolver
    {
        $mock = Mockery::mock(BastApproverResolver::class);
        $mock->shouldReceive('resolveApprover')
            ->andReturnUsing(function (LhppBast $bast, string $label) use ($users): array {
                $user = $users[$label];

                return [
                    'role_key' => match ($label) {
                        'Manager PKM' => 'manager_pkm',
                        'Manager Peminta' => 'manager_peminta',
                        'Manager Pengendali' => 'manager_pengendali',
                        'SM Pengendali' => 'sm_pengendali',
                        'GM Pengendali' => 'gm_pengendali',
                        'DIROPS' => 'dirops',
                    },
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
    private function makeBast(string $terminType, string $threshold, array $flow): LhppBast
    {
        $creator = User::factory()->create();
        $order = Order::query()->create([
            'nomor_order' => 'BAST-T2-'.uniqid(),
            'nama_pekerjaan' => 'Approval Termin 2',
            'unit_kerja' => 'Workshop',
            'seksi' => 'Machine Workshop',
            'deskripsi' => 'Regression flow Termin 2',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-08-01',
            'target_selesai' => '2026-08-10',
            'created_by' => $creator->id,
        ]);

        return LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => $terminType,
            'nomor_order' => $order->nomor_order,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => '2026-08-01',
            'tipe_pekerjaan' => 'Pengerjaan Mesin',
            'approval_threshold' => $threshold,
            'approval_flow' => $flow,
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'quality_control_status' => 'approved',
            'created_by' => $creator->id,
        ]);
    }

    private function signature(
        LhppBast $bast,
        int $stepOrder,
        string $roleKey,
        string $roleLabel,
        User $signer,
    ): LhppBastSignature {
        return $bast->signatures()->create([
            'step_order' => $stepOrder,
            'role_key' => $roleKey,
            'role_label' => $roleLabel,
            'signer_user_id' => $signer->id,
            'signer_name_snapshot' => $signer->name,
            'signer_position_snapshot' => $roleLabel,
            'signer_department_snapshot' => 'PMMS',
            'signer_unit_snapshot' => 'Workshop',
            'signer_section_snapshot' => 'Machine Workshop',
            'status' => LhppBastSignature::STATUS_LOCKED,
        ]);
    }
}
