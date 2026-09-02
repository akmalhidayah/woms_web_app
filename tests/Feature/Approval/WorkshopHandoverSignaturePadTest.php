<?php

namespace Tests\Feature\Approval;

use App\Models\Order;
use App\Models\User;
use App\Models\WorkshopHandover;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopHandoverSignaturePadTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_workshop_handover_uses_the_stable_pointer_signature_pad(): void
    {
        $admin = User::factory()->create();
        $recipient = User::factory()->create(['role' => User::ROLE_USER]);
        $order = Order::query()->create([
            'nomor_order' => 'HANDOVER-PAD-'.uniqid(),
            'nama_pekerjaan' => 'Pekerjaan serah terima',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Pengujian canvas tanda tangan serah terima',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'created_by' => $admin->id,
        ]);
        $token = 'workshop-handover-pad-token';

        WorkshopHandover::query()->create([
            'order_id' => $order->id,
            'document_no' => 'STB-PAD-'.uniqid(),
            'path' => WorkshopHandover::PATH_NON_CRITICAL,
            'status' => WorkshopHandover::STATUS_WAITING_USER_SIGNATURE,
            'handed_over_at' => now(),
            'order_no_snapshot' => $order->nomor_order,
            'job_name_snapshot' => $order->nama_pekerjaan,
            'unit_snapshot' => $order->unit_kerja,
            'section_snapshot' => $order->seksi,
            'admin_user_id' => $admin->id,
            'admin_name_snapshot' => $admin->name,
            'admin_position_snapshot' => 'Admin Workshop',
            'admin_signature_path' => 'workshop-handovers/admin-signature.png',
            'admin_signed_at' => now(),
            'recipient_user_id' => $recipient->id,
            'recipient_name_snapshot' => $recipient->name,
            'recipient_position_snapshot' => 'Manager User',
            'photo_paths' => [],
            'token_hash' => hash('sha256', $token),
            'token_encrypted' => $token,
            'token_expires_at' => now()->addDay(),
        ]);

        $this->actingAs($recipient)
            ->get(route('approval.workshop-handover.show', $token))
            ->assertOk()
            ->assertSee('workshop-handover-user-signature', false)
            ->assertSee('touch-none', false)
            ->assertSee("canvas.addEventListener('pointerdown'", false)
            ->assertSee("'lostpointercapture'", false)
            ->assertSee('canvas.setPointerCapture?.(event.pointerId);', false)
            ->assertSee('let activePointerId = null;', false)
            ->assertSee('const scheduleResize = () =>', false);
    }
}
