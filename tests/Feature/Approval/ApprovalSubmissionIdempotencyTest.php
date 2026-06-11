<?php

namespace Tests\Feature\Approval;

use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\InitialWork;
use App\Models\InitialWorkSignature;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\QualityControlReport;
use App\Models\QualityControlSignature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApprovalSubmissionIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_signature_submission_does_not_replace_the_first_signature(): void
    {
        Storage::fake('public');

        $approver = User::factory()->create(['role' => User::ROLE_APPROVER]);

        $cases = [
            $this->initialWorkCase($approver),
            $this->qualityControlCase($approver),
            $this->hppCase($approver),
            $this->bastCase($approver),
        ];

        foreach ($cases as $case) {
            $this->actingAs($approver)
                ->post($case['url'], ['signature_data' => $this->signatureData()])
                ->assertRedirect($case['show_url']);

            $firstPath = $case['signature']->fresh()->{$case['path_column']};

            $this->assertNotNull($firstPath, $case['label']);
            Storage::disk('public')->assertExists($firstPath);

            $this->actingAs($approver)
                ->post($case['url'], ['signature_data' => $this->signatureData('second')])
                ->assertRedirect($case['show_url']);

            $this->assertSame(
                $firstPath,
                $case['signature']->fresh()->{$case['path_column']},
                $case['label'],
            );
            $this->assertCount(
                1,
                Storage::disk('public')->allFiles($case['directory']),
                $case['label'],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function initialWorkCase(User $approver): array
    {
        $order = $this->createOrder($approver, 'ORD-IDEMPOTENT-IW');
        $initialWork = InitialWork::query()->create([
            'order_id' => $order->id,
            'nomor_initial_work' => 'IW-IDEMPOTENT',
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'perihal' => 'Idempotency test',
            'tanggal_initial_work' => now()->toDateString(),
            'functional_location' => ['FL-01'],
            'scope_pekerjaan' => ['Scope'],
            'qty' => ['1'],
            'stn' => ['Lot'],
            'created_by' => $approver->id,
        ]);
        $token = 'idempotent-initial-work';
        $signature = $initialWork->signatures()->create([
            'step_order' => 2,
            'role_key' => InitialWorkSignature::ROLE_SENIOR_MANAGER,
            'role_label' => 'Senior Manager',
            'signer_user_id' => $approver->id,
            'signer_name' => $approver->name,
            'status' => InitialWorkSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', $token),
            'token_encrypted' => $token,
            'token_expires_at' => now()->addDay(),
        ]);

        return [
            'label' => 'Initial Work',
            'signature' => $signature,
            'url' => route('approval.initial-work.sign', $token),
            'show_url' => route('approval.initial-work.show', $token),
            'path_column' => 'signature_path',
            'directory' => 'initial-work-signatures/'.$initialWork->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function qualityControlCase(User $approver): array
    {
        $order = $this->createOrder($approver, 'ORD-IDEMPOTENT-QC');
        $report = QualityControlReport::query()->create([
            'order_id' => $order->id,
            'type' => QualityControlReport::TYPE_FABRICATION,
            'report_no' => 'QC-IDEMPOTENT',
            'report_date' => now()->toDateString(),
            'status' => QualityControlReport::STATUS_SUBMITTED,
            'payload' => [],
            'created_by' => $approver->id,
            'updated_by' => $approver->id,
        ]);
        $token = 'idempotent-quality-control';
        $signature = $report->signatures()->create([
            'step_order' => 2,
            'role_key' => QualityControlSignature::ROLE_USER_MANAGER,
            'role_label' => 'Manager User',
            'signer_user_id' => $approver->id,
            'signer_name' => $approver->name,
            'status' => QualityControlSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', $token),
            'token_encrypted' => $token,
            'token_expires_at' => now()->addDay(),
        ]);

        return [
            'label' => 'Quality Control',
            'signature' => $signature,
            'url' => route('approval.quality-control.sign', $token),
            'show_url' => route('approval.quality-control.show', $token),
            'path_column' => 'signature_data',
            'directory' => 'quality-control-signatures/'.$report->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function hppCase(User $approver): array
    {
        $order = $this->createOrder($approver, 'ORD-IDEMPOTENT-HPP');
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'approval_flow' => ['Manager Pengendali'],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_IN_REVIEW,
            'created_by' => $approver->id,
        ]);
        $token = 'idempotent-hpp';
        $signature = $hpp->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager_pengendali',
            'role_label' => 'Manager Pengendali',
            'signer_user_id' => $approver->id,
            'signer_name_snapshot' => $approver->name,
            'signer_position_snapshot' => 'Manager Pengendali',
            'status' => HppSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', $token),
            'token' => $token,
            'token_expires_at' => now()->addDay(),
        ]);

        return [
            'label' => 'HPP',
            'signature' => $signature,
            'url' => route('approval.hpp.sign', $token),
            'show_url' => route('approval.hpp.show', $token),
            'path_column' => 'signature_data',
            'directory' => 'hpp-signatures/'.$hpp->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bastCase(User $approver): array
    {
        $order = $this->createOrder($approver, 'ORD-IDEMPOTENT-BAST');
        $lhpp = LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => now()->toDateString(),
            'approval_threshold' => 'under_250',
            'approval_flow' => ['Manager PKM'],
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'quality_control_status' => 'approved',
            'created_by' => $approver->id,
            'updated_by' => $approver->id,
        ]);
        $token = 'idempotent-bast';
        $signature = $lhpp->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager_pkm',
            'role_label' => 'Manager PKM',
            'signer_user_id' => $approver->id,
            'signer_name_snapshot' => $approver->name,
            'signer_position_snapshot' => 'Manager PKM',
            'status' => LhppBastSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', $token),
            'token' => $token,
            'token_expires_at' => now()->addDay(),
        ]);

        return [
            'label' => 'BAST',
            'signature' => $signature,
            'url' => route('approval.bast.sign', $token),
            'show_url' => route('approval.bast.show', $token),
            'path_column' => 'signature_data',
            'directory' => 'bast-signatures/'.$lhpp->id,
        ];
    }

    private function createOrder(User $creator, string $number): Order
    {
        return Order::query()->create([
            'nomor_order' => $number,
            'nama_pekerjaan' => 'Approval idempotency test',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Section Test',
            'deskripsi' => 'Approval idempotency test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'created_by' => $creator->id,
        ]);
    }

    private function signatureData(string $suffix = 'first'): string
    {
        return 'data:image/png;base64,'.base64_encode(str_repeat('signature-'.$suffix, 20));
    }
}
