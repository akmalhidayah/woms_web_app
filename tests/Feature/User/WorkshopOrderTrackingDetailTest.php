<?php

namespace Tests\Feature\User;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelPic;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\User;
use App\Models\WorkshopHandover;
use App\Models\WorkshopWorkPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkshopOrderTrackingDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_workshop_detail_shows_packages_and_handover_without_a_non_critical_qc_step(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $order = Order::query()->create([
            'nomor_order' => 'TRACK-WORKSHOP-001',
            'notifikasi' => 'NOTIF-WORKSHOP-001',
            'nama_pekerjaan' => 'Fabrikasi Cover Kabel Motor Kiln 4',
            'unit_kerja' => 'Elins Maintenance 2',
            'seksi' => 'Line 4/5 RKC Electrical Maint',
            'deskripsi' => 'Pekerjaan bengkel untuk tracking user.',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addDay()->toDateString(),
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
            'catatan' => 'Regu Fabrikasi',
            'created_by' => $user->id,
        ]);
        $order->orderWorkshop()->create([
            'preparation_status' => OrderWorkshop::PREPARATION_COMPLETED,
            'progress_status' => OrderWorkshop::PROGRESS_DONE,
            'preparation_note' => 'Persiapan sudah diverifikasi.',
            'keterangan_progress' => 'Pekerjaan bengkel selesai.',
        ]);

        Storage::disk('public')->put('bengkel-pics/dahlan-paket.jpg', 'avatar');
        $pic = BengkelPic::query()->create([
            'name' => 'Dahlan Paket',
            'avatar_path' => 'bengkel-pics/dahlan-paket.jpg',
            'avatar_position_x' => 50,
            'avatar_position_y' => 50,
        ]);
        $package = $order->workPackages()->create([
            'sequence' => 1,
            'display_no' => 'TRACK-WORKSHOP-001-01',
            'job_name' => 'Fabrikasi Cover Kabel',
            'status' => WorkshopWorkPackage::STATUS_COMPLETED,
        ]);
        $package->assignments()->create([
            'bengkel_pic_id' => $pic->id,
            'pic_name_snapshot' => $pic->name,
            'pic_avatar_path_snapshot' => $pic->avatar_path,
            'avatar_position_x' => 50,
            'avatar_position_y' => 50,
            'work_descriptions' => ['Fabrikasi cover kabel'],
            'sort_order' => 0,
        ]);
        WorkshopHandover::query()->create([
            'order_id' => $order->id,
            'document_no' => 'STB-TRACK-WORKSHOP-001',
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
            'admin_signature_path' => 'workshop-handovers/test/admin.png',
            'admin_signed_at' => now(),
            'recipient_user_id' => $user->id,
            'recipient_name_snapshot' => $user->name,
            'recipient_position_snapshot' => 'Manager User',
            'photo_paths' => [],
        ]);

        $this->actingAs($user)
            ->get(route('user.orders.show', $order))
            ->assertOk()
            ->assertSee('Persiapan Selesai')
            ->assertSee('Pekerjaan Selesai')
            ->assertSee('Pekerjaan Paket')
            ->assertSee('Nama Pekerjaan Paket')
            ->assertSee('Fabrikasi Cover Kabel')
            ->assertSee('Dahlan Paket')
            ->assertSee('Menunggu Tanda Tangan Manager User')
            ->assertSee('STB-TRACK-WORKSHOP-001')
            ->assertDontSee('Quality Control');

        $this->actingAs($user)
            ->get(route('user.orders.workshop-handover.pdf', $order))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs(User::factory()->create(['role' => User::ROLE_USER]))
            ->get(route('user.orders.workshop-handover.pdf', $order))
            ->assertForbidden();
    }
}
