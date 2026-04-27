<?php

namespace Tests\Feature\Admin;

use App\Models\BengkelPic;
use App\Models\BengkelTask;
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
            'job_name' => 'Bucket Repair Lama Update',
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

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }
}
