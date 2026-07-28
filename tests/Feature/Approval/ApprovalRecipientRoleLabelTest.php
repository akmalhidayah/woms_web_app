<?php

namespace Tests\Feature\Approval;

use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\Order;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use App\Services\Approvals\ApprovalNotificationService;
use App\Support\ApprovalRecipientRoleLabel;
use App\Support\ApprovalWhatsappLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApprovalRecipientRoleLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_hierarchical_roles_use_their_organization_snapshot(): void
    {
        $manager = new HppSignature([
            'role_key' => 'manager_peminta',
            'role_label' => 'Manager Peminta',
            'signer_section_snapshot' => 'Machine Workshop',
        ]);
        $seniorManager = new HppSignature([
            'role_key' => 'sm_pengendali',
            'role_label' => 'SM Pengendali',
            'signer_unit_snapshot' => 'Workshop',
        ]);
        $generalManager = new HppSignature([
            'role_key' => 'gm_peminta',
            'role_label' => 'GM Peminta',
            'signer_department_snapshot' => 'Project Management & Main Support',
        ]);

        $this->assertSame('Manager Machine Workshop', ApprovalRecipientRoleLabel::for($manager));
        $this->assertSame('SM Workshop', ApprovalRecipientRoleLabel::for($seniorManager));
        $this->assertSame(
            'GM Project Management & Main Support',
            ApprovalRecipientRoleLabel::for($generalManager)
        );
    }

    public function test_missing_snapshot_uses_generic_hierarchy_role_and_other_roles_keep_label(): void
    {
        $manager = new HppSignature([
            'role_key' => 'manager_pengendali',
            'role_label' => 'Manager Pengendali',
        ]);
        $planner = new HppSignature([
            'role_key' => 'planner_control',
            'role_label' => 'Planner Control',
        ]);

        $this->assertSame('Manager', ApprovalRecipientRoleLabel::for($manager));
        $this->assertSame('Planner Control', ApprovalRecipientRoleLabel::for($planner));
    }

    public function test_email_and_whatsapp_use_the_same_contextual_role_label(): void
    {
        Notification::fake();
        [$user, $signature] = $this->hppSignature();

        $this->assertTrue(app(ApprovalNotificationService::class)->sendHpp($signature));

        Notification::assertSentTo(
            $user,
            ApprovalRequestedNotification::class,
            fn (ApprovalRequestedNotification $notification): bool => $notification->roleLabel === 'Manager Machine Workshop'
        );

        $whatsappUrl = ApprovalWhatsappLink::forHpp($signature);
        $this->assertNotNull($whatsappUrl);

        parse_str((string) parse_url($whatsappUrl, PHP_URL_QUERY), $query);
        $message = (string) ($query['text'] ?? '');

        $this->assertStringContainsString(
            'Anda ditetapkan sebagai Manager Machine Workshop',
            $message
        );
        $this->assertStringContainsString('Role Approval : Manager Machine Workshop', $message);
        $this->assertStringNotContainsString('Manager Peminta', $message);
    }

    /**
     * @return array{User, HppSignature}
     */
    private function hppSignature(): array
    {
        $user = User::factory()->create([
            'nomor_hp' => '081234567890',
        ]);
        $order = Order::query()->create([
            'nomor_order' => 'ORDER-LABEL-001',
            'nama_pekerjaan' => 'Pekerjaan label approval',
            'unit_kerja' => 'Workshop',
            'seksi' => 'Machine Workshop',
            'deskripsi' => 'Test',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => '2026-07-28',
            'target_selesai' => '2026-08-01',
            'created_by' => $user->id,
        ]);
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
        $signature = $hpp->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager_peminta',
            'role_label' => 'Manager Peminta',
            'signer_user_id' => $user->id,
            'signer_name_snapshot' => $user->name,
            'signer_position_snapshot' => 'Manager Machine Workshop',
            'signer_department_snapshot' => 'Project Management & Main Support',
            'signer_unit_snapshot' => 'Workshop',
            'signer_section_snapshot' => 'Machine Workshop',
            'status' => HppSignature::STATUS_PENDING,
            'token' => 'approval-label-token',
            'token_hash' => hash('sha256', 'approval-label-token'),
            'token_expires_at' => now()->addDay(),
        ]);

        return [$user, $signature];
    }
}
