<?php

namespace Tests\Feature\Livewire;

use App\Livewire\DashboardPekerjaan;
use App\Models\BengkelPic;
use App\Models\BengkelTask;
use App\Models\OrderWorkshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardPekerjaanTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_board_reloads_latest_tasks(): void
    {
        BengkelTask::create([
            'job_name' => 'Perbaikan Bucket Awal',
            'notification_number' => 'WO-001',
            'unit_work' => 'Workshop A',
            'seksi' => 'Mekanik',
            'usage_plan_date' => '2026-04-20',
            'catatan' => 'Regu Fabrikasi',
            'person_in_charge' => ['Budi'],
            'person_in_charge_profiles' => [],
        ]);

        $component = Livewire::test(DashboardPekerjaan::class, ['mode' => 'display'])
            ->assertSee('PERBAIKAN BUCKET AWAL')
            ->assertDontSee('PERBAIKAN BUCKET BARU');

        BengkelTask::create([
            'job_name' => 'Perbaikan Bucket Baru',
            'notification_number' => 'WO-002',
            'unit_work' => 'Workshop B',
            'seksi' => 'Las',
            'usage_plan_date' => '2026-04-21',
            'catatan' => 'Regu Bengkel (Refurbish)',
            'person_in_charge' => ['Sari'],
            'person_in_charge_profiles' => [],
        ]);

        $component
            ->call('refreshBoard')
            ->assertSee('PERBAIKAN BUCKET BARU');
    }

    public function test_display_mode_uses_keep_alive_polling(): void
    {
        Livewire::test(DashboardPekerjaan::class, ['mode' => 'display'])
            ->assertSeeHtml('wire:poll.keep-alive.5s="tickDisplay"');
    }

    public function test_display_resolves_pic_avatar_by_name_when_profile_id_is_stale(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bengkel-pics/akbar.jpg', 'fake-image');

        $pic = BengkelPic::create([
            'name' => 'Akbar',
            'avatar_path' => 'bengkel-pics/akbar.jpg',
            'avatar_position_x' => 50,
            'avatar_position_y' => 50,
        ]);

        BengkelTask::create([
            'job_name' => 'Perbaikan Display Avatar',
            'notification_number' => 'WO-AVATAR',
            'unit_work' => 'Workshop A',
            'seksi' => 'Mekanik',
            'usage_plan_date' => '2026-04-20',
            'catatan' => 'Regu Fabrikasi',
            'person_in_charge' => ['Akbar'],
            'person_in_charge_profiles' => [
                [
                    'id' => 99999,
                    'name' => 'Akbar',
                    'avatar_path' => 'bengkel-pics/old-akbar.jpg',
                    'work_descriptions' => [],
                ],
            ],
        ]);

        Livewire::test(DashboardPekerjaan::class, ['mode' => 'display'])
            ->assertSeeHtml(route('bengkel-pics.avatar', ['bengkel_pic' => $pic], false));
    }

    public function test_display_does_not_show_archived_tasks(): void
    {
        BengkelTask::create([
            'job_name' => 'Pekerjaan Aktif',
            'notification_number' => 'WO-ACTIVE',
            'unit_work' => 'Workshop A',
            'seksi' => 'Mekanik',
            'usage_plan_date' => '2026-04-20',
            'catatan' => 'Regu Fabrikasi',
            'person_in_charge' => [],
            'person_in_charge_profiles' => [],
        ]);

        BengkelTask::create([
            'job_name' => 'Pekerjaan Arsip',
            'notification_number' => 'WO-ARCHIVED',
            'unit_work' => 'Workshop B',
            'seksi' => 'Las',
            'usage_plan_date' => '2026-04-21',
            'catatan' => 'Regu Fabrikasi',
            'person_in_charge' => [],
            'person_in_charge_profiles' => [],
            'archived_at' => now(),
        ]);

        Livewire::test(DashboardPekerjaan::class, ['mode' => 'display'])
            ->assertSee('PEKERJAAN AKTIF')
            ->assertDontSee('PEKERJAAN ARSIP');
    }

    public function test_display_shows_pending_status_without_pending_reason(): void
    {
        BengkelTask::create([
            'job_name' => 'Pekerjaan Pending',
            'notification_number' => 'WO-PENDING',
            'unit_work' => 'Workshop A',
            'seksi' => 'Mekanik',
            'usage_plan_date' => '2026-04-20',
            'catatan' => 'Regu Fabrikasi',
            'progress_status' => OrderWorkshop::PROGRESS_PENDING,
            'pending_reason' => 'Menunggu spare part rahasia.',
            'person_in_charge' => [],
            'person_in_charge_profiles' => [],
        ]);

        Livewire::test(DashboardPekerjaan::class, ['mode' => 'display'])
            ->assertSee('PEKERJAAN PENDING')
            ->assertSee('Pending')
            ->assertDontSee('Menunggu spare part rahasia.');
    }
}
