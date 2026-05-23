<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelPic;
use App\Models\BengkelTask;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BengkelDisplayManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_bengkel_task_edit_flow_preserves_current_list_query(): void
    {
        $user = $this->adminUser();
        $query = [
            'q' => 'Bucket',
            'regu' => 'fabrikasi',
            'per_page' => 1,
            'page' => 2,
        ];

        $pageTwoTask = BengkelTask::create([
            'job_name' => 'Bucket Repair Lama',
            'notification_number' => 'WO-001',
            'unit_work' => 'Workshop A',
            'seksi' => 'Mekanik',
            'usage_plan_date' => '2026-04-20',
            'catatan' => 'Regu Fabrikasi',
            'person_in_charge' => ['Budi'],
            'person_in_charge_profiles' => [],
        ]);

        BengkelTask::create([
            'job_name' => 'Bucket Repair Baru',
            'notification_number' => 'WO-002',
            'unit_work' => 'Workshop B',
            'seksi' => 'Las',
            'usage_plan_date' => '2026-04-21',
            'catatan' => 'Regu Fabrikasi',
            'person_in_charge' => ['Sari'],
            'person_in_charge_profiles' => [],
        ]);

        $editUrl = route('admin.bengkel-tasks.edit', array_merge(['bengkel_task' => $pageTwoTask], $query));
        $updateUrl = route('admin.bengkel-tasks.update', array_merge(['bengkel_task' => $pageTwoTask], $query));
        $indexUrl = route('admin.bengkel-tasks.index', $query);

        $this->actingAs($user)
            ->get($indexUrl)
            ->assertOk()
            ->assertSee('href="'.e($editUrl).'"', false);

        $this->actingAs($user)
            ->get($editUrl)
            ->assertOk()
            ->assertSee('action="'.e($updateUrl).'"', false)
            ->assertSee('href="'.e($indexUrl).'"', false);

        $this->actingAs($user)
            ->put($updateUrl, [
                'job_name' => 'Bucket Repair Lama Update',
                'notification_number' => 'WO-001-REV',
                'unit_work' => 'Workshop A',
                'seksi' => 'Mekanik',
                'usage_plan_date' => '2026-04-22',
                'catatan' => 'Regu Fabrikasi',
                'pic_ids' => [],
            ])
            ->assertRedirect($indexUrl)
            ->assertSessionHas('status', 'Pekerjaan bengkel diperbarui.');

        $this->assertDatabaseHas('bengkel_tasks', [
            'id' => $pageTwoTask->id,
            'job_name' => 'BUCKET REPAIR LAMA UPDATE',
            'notification_number' => 'WO-001-REV',
        ]);
    }

    public function test_bengkel_pic_update_replaces_avatar_and_changes_avatar_url(): void
    {
        Storage::fake('public');

        $user = $this->adminUser();
        $oldAvatar = UploadedFile::fake()->image('avatar-lama.jpg');
        $newAvatar = UploadedFile::fake()->image('avatar-baru.jpg');

        $pic = BengkelPic::create([
            'name' => 'Andi PIC',
            'avatar_path' => $oldAvatar->store('bengkel-pics', 'public'),
            'avatar_position_x' => 50,
            'avatar_position_y' => 50,
        ]);

        $oldAvatarPath = $pic->avatar_path;
        $oldAvatarUrl = $pic->avatar_url;
        $updateUrl = route('admin.bengkel-pics.update', [
            'bengkel_pic' => $pic,
            'page' => 2,
        ]);

        $this->actingAs($user)
            ->put($updateUrl, [
                'name' => 'Andi PIC',
                'avatar' => $newAvatar,
                'avatar_position_x' => 35,
                'avatar_position_y' => 40,
            ])
            ->assertRedirect(route('admin.bengkel-pics.index', ['page' => 2]))
            ->assertSessionHas('status', 'PIC berhasil diperbarui.');

        $pic->refresh();

        Storage::disk('public')->assertMissing($oldAvatarPath);
        Storage::disk('public')->assertExists($pic->avatar_path);

        $this->assertNotSame($oldAvatarPath, $pic->avatar_path);
        $this->assertNotNull($pic->avatar_url);
        $this->assertNotSame($oldAvatarUrl, $pic->avatar_url);
        $this->assertStringContainsString('?v=', $pic->avatar_url);
        $this->assertSame(35, $pic->avatar_position_x);
        $this->assertSame(40, $pic->avatar_position_y);
    }

    public function test_bengkel_task_edit_resolves_stale_pic_profile_id_by_name(): void
    {
        $user = $this->adminUser();
        $pic = BengkelPic::create([
            'name' => 'Akbar',
            'avatar_path' => 'bengkel-pics/akbar.jpg',
            'avatar_position_x' => 50,
            'avatar_position_y' => 50,
        ]);
        $task = BengkelTask::create([
            'job_name' => 'Repair Bucket Reject',
            'notification_number' => 'WO-003',
            'unit_work' => 'Machine Maintenance 2',
            'seksi' => 'Line 4/5 RM Machine Maint',
            'usage_plan_date' => '2026-05-26',
            'catatan' => 'Regu Fabrikasi',
            'person_in_charge' => ['Akbar'],
            'person_in_charge_profiles' => [
                [
                    'id' => 99999,
                    'name' => 'Akbar',
                    'avatar_path' => 'bengkel-pics/old-akbar.jpg',
                    'work_descriptions' => ['Las bucket'],
                ],
            ],
        ]);

        $editUrl = route('admin.bengkel-tasks.edit', $task);
        $updateUrl = route('admin.bengkel-tasks.update', $task);

        $this->actingAs($user)
            ->get($editUrl)
            ->assertOk()
            ->assertSee('\u0022pic_id\u0022:'.$pic->id, false)
            ->assertSee('<option value="'.$pic->id.'">Akbar</option>', false)
            ->assertDontSee('99999');

        $this->actingAs($user)
            ->put($updateUrl, [
                'job_name' => 'Repair Bucket Reject Update',
                'notification_number' => 'WO-003',
                'unit_work' => 'Machine Maintenance 2',
                'seksi' => 'Line 4/5 RM Machine Maint',
                'usage_plan_date' => '2026-05-26',
                'catatan' => 'Regu Fabrikasi',
                'pic_assignments' => [
                    [
                        'pic_id' => $pic->id,
                        'descriptions' => ['Las bucket'],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.bengkel-tasks.index'))
            ->assertSessionHas('status', 'Pekerjaan bengkel diperbarui.');

        $task->refresh();

        $this->assertSame(['Akbar'], $task->person_in_charge);
        $this->assertSame($pic->id, $task->person_in_charge_profiles[0]['id']);
        $this->assertSame(['Las bucket'], $task->person_in_charge_profiles[0]['work_descriptions']);
    }

    public function test_bengkel_task_update_without_pic_fields_preserves_existing_pic(): void
    {
        $user = $this->adminUser();
        $pic = BengkelPic::create([
            'name' => 'Dahlan',
            'avatar_path' => null,
            'avatar_position_x' => 50,
            'avatar_position_y' => 50,
        ]);
        $task = BengkelTask::create([
            'job_name' => 'Fabrikasi Air Slide',
            'notification_number' => 'WO-005',
            'unit_work' => 'Machine Maintenance 2',
            'seksi' => 'Line 4/5 FM Machine Maint',
            'usage_plan_date' => '2026-05-26',
            'catatan' => 'Regu Fabrikasi',
            'person_in_charge' => ['Dahlan'],
            'person_in_charge_profiles' => [
                [
                    'id' => $pic->id,
                    'name' => 'Dahlan',
                    'avatar_path' => null,
                    'work_descriptions' => ['Potong material'],
                ],
            ],
        ]);

        $this->actingAs($user)
            ->put(route('admin.bengkel-tasks.update', $task), [
                'job_name' => 'Fabrikasi Air Slide Update',
                'notification_number' => 'WO-005',
                'unit_work' => 'Machine Maintenance 2',
                'seksi' => 'Line 4/5 FM Machine Maint',
                'usage_plan_date' => '2026-05-26',
                'catatan' => 'Regu Fabrikasi',
            ])
            ->assertRedirect(route('admin.bengkel-tasks.index'))
            ->assertSessionHas('status', 'Pekerjaan bengkel diperbarui.');

        $task->refresh();

        $this->assertSame(['Dahlan'], $task->person_in_charge);
        $this->assertSame($pic->id, $task->person_in_charge_profiles[0]['id']);
        $this->assertSame(['Potong material'], $task->person_in_charge_profiles[0]['work_descriptions']);
    }

    public function test_bengkel_task_update_stores_attachment(): void
    {
        Storage::fake('public');

        $user = $this->adminUser();
        $task = BengkelTask::create([
            'job_name' => 'Repair Bucket',
            'notification_number' => 'WO-004',
            'unit_work' => 'Machine Maintenance 2',
            'seksi' => 'Line 4/5 RM Machine Maint',
            'usage_plan_date' => '2026-05-26',
            'catatan' => 'Regu Fabrikasi',
            'person_in_charge' => [],
            'person_in_charge_profiles' => [],
        ]);
        $file = UploadedFile::fake()->create('bukti-pekerjaan.pdf', 256, 'application/pdf');

        $this->actingAs($user)
            ->put(route('admin.bengkel-tasks.update', $task), [
                'job_name' => 'Repair Bucket Update',
                'notification_number' => 'WO-004',
                'unit_work' => 'Machine Maintenance 2',
                'seksi' => 'Line 4/5 RM Machine Maint',
                'usage_plan_date' => '2026-05-26',
                'catatan' => 'Regu Fabrikasi',
                'attachment' => $file,
            ])
            ->assertRedirect(route('admin.bengkel-tasks.index'))
            ->assertSessionHas('status', 'Pekerjaan bengkel diperbarui.');

        $task->refresh();

        $this->assertSame('bukti-pekerjaan.pdf', $task->attachment_original_name);
        $this->assertSame('application/pdf', $task->attachment_mime_type);
        $this->assertNotNull($task->attachment_path);
        Storage::disk('public')->assertExists($task->attachment_path);

        $this->actingAs($user)
            ->get(route('admin.bengkel-tasks.index'))
            ->assertOk()
            ->assertSee('Preview Lampiran')
            ->assertSee('bukti-pekerjaan.pdf');

        $this->actingAs($user)
            ->get(route('admin.bengkel-tasks.attachment', $task))
            ->assertOk();
    }

    public function test_bengkel_task_archive_creates_workshop_order_and_hides_task_from_display_admin(): void
    {
        $user = $this->adminUser();
        $task = BengkelTask::create([
            'job_name' => 'Fabrikasi Air Slide',
            'notification_number' => 'WO-ARCHIVE-001',
            'unit_work' => 'Machine Maintenance 2',
            'seksi' => 'Line 4/5 FM Machine Maint',
            'usage_plan_date' => '2026-05-26',
            'catatan' => 'Regu Fabrikasi',
            'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
            'person_in_charge' => ['Dahlan'],
            'person_in_charge_profiles' => [
                [
                    'name' => 'Dahlan',
                    'work_descriptions' => ['Potong material'],
                ],
            ],
        ]);

        $this->actingAs($user)
            ->patch(route('admin.bengkel-tasks.archive', $task))
            ->assertRedirect(route('admin.bengkel-tasks.index'))
            ->assertSessionHas('status', 'Pekerjaan bengkel diarsipkan ke Order Pekerjaan Bengkel.');

        $task->refresh();
        $order = Order::query()->findOrFail($task->archived_order_id);

        $this->assertNotNull($task->archived_at);
        $this->assertSame($order->id, $task->order_id);
        $this->assertSame('WO-ARCHIVE-001', $order->nomor_order);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'nama_pekerjaan' => 'FABRIKASI AIR SLIDE',
            'unit_kerja' => 'Machine Maintenance 2',
            'seksi' => 'Line 4/5 FM Machine Maint',
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
            'catatan' => 'Regu Fabrikasi',
        ]);

        $this->assertDatabaseHas('order_workshops', [
            'order_id' => $order->id,
            'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
            'catatan' => 'Regu Fabrikasi',
        ]);

        $this->actingAs($user)
            ->get(route('admin.bengkel-tasks.index'))
            ->assertOk()
            ->assertDontSee('FABRIKASI AIR SLIDE');

        $this->actingAs($user)
            ->get(route('admin.orders.workshop.index'))
            ->assertOk()
            ->assertSee('FABRIKASI AIR SLIDE');
    }

    public function test_bengkel_task_archive_copies_attachment_to_order_gambar_teknik(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $user = $this->adminUser();
        Storage::disk('public')->put('bengkel-task-attachments/manual.pdf', 'pdf-content');

        $task = BengkelTask::create([
            'job_name' => 'Repair Bucket',
            'notification_number' => null,
            'unit_work' => 'Machine Maintenance 2',
            'seksi' => 'Line 4/5 RM Machine Maint',
            'usage_plan_date' => '2026-05-26',
            'catatan' => 'Regu Bengkel (Refurbish)',
            'person_in_charge' => [],
            'person_in_charge_profiles' => [],
            'attachment_path' => 'bengkel-task-attachments/manual.pdf',
            'attachment_original_name' => 'gambar-teknik-awal.pdf',
            'attachment_mime_type' => 'application/pdf',
            'attachment_size' => 1024,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.bengkel-tasks.archive', $task))
            ->assertRedirect(route('admin.bengkel-tasks.index'));

        $task->refresh();
        $order = Order::query()->findOrFail($task->archived_order_id);
        $document = $order->documents()
            ->where('jenis_dokumen', OrderDocumentType::GambarTeknik->value)
            ->firstOrFail();

        $this->assertSame('gambar-teknik-awal.pdf', $document->nama_file_asli);
        $this->assertSame($user->id, $document->uploaded_by);
        Storage::disk('local')->assertExists($document->path_file);
    }

    public function test_archived_workshop_order_number_and_notification_can_be_completed_later(): void
    {
        $user = $this->adminUser();
        $task = BengkelTask::create([
            'job_name' => 'Manual Urgent Work',
            'notification_number' => null,
            'unit_work' => 'Machine Maintenance 2',
            'seksi' => 'Line 4/5 FM Machine Maint',
            'usage_plan_date' => '2026-05-26',
            'catatan' => 'Regu Fabrikasi',
            'person_in_charge' => [],
            'person_in_charge_profiles' => [],
        ]);

        $this->actingAs($user)
            ->patch(route('admin.bengkel-tasks.archive', $task))
            ->assertRedirect(route('admin.bengkel-tasks.index'));

        $order = Order::query()->findOrFail($task->fresh()->archived_order_id);

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), [
                'nomor_order' => '3000000001234567',
                'notifikasi' => '100000123',
                'nama_pekerjaan' => $order->nama_pekerjaan,
                'unit_kerja' => $order->unit_kerja,
                'seksi' => $order->seksi,
                'deskripsi' => $order->deskripsi,
                'prioritas' => $order->prioritas,
                'tanggal_order' => $order->tanggal_order->format('Y-m-d'),
                'target_selesai' => $order->target_selesai->format('Y-m-d'),
                'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
                'catatan' => $order->catatan,
            ])
            ->assertRedirect(route('admin.orders.show', '3000000001234567'))
            ->assertSessionHas('status', 'Order pekerjaan berhasil diperbarui.');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'nomor_order' => '3000000001234567',
            'notifikasi' => '100000123',
        ]);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }
}
