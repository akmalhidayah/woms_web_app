<?php

namespace Tests\Feature\Livewire;

use App\Livewire\DashboardPekerjaan;
use App\Models\BengkelPic;
use App\Models\BengkelTask;
use App\Models\OrderWorkshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardPekerjaanTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_display_uses_one_combined_grid_for_both_teams_and_six_cards_per_page(): void
    {
        foreach (range(1, 7) as $number) {
            BengkelTask::create([
                'job_name' => 'Pekerjaan Gabungan '.$number,
                'notification_number' => 'WO-'.$number,
                'unit_work' => 'Workshop',
                'seksi' => 'Seksi '.$number,
                'usage_plan_date' => '2026-04-20',
                'catatan' => $number % 2 === 0 ? 'Regu Bengkel (Refurbish)' : 'Regu Fabrikasi',
                'person_in_charge' => [],
                'person_in_charge_profiles' => [],
            ]);
        }

        $component = Livewire::test(DashboardPekerjaan::class, ['mode' => 'display']);

        $this->assertSame(2, $component->get('maxPages'));
        $this->assertLessThanOrEqual(6, substr_count($component->html(), 'data-testid="display-task-card"'));
        $component->assertSee('FABRIKASI')->assertSee('REFURBISH');
    }

    public function test_public_display_slide_is_safe_when_page_count_changes(): void
    {
        foreach (range(1, 7) as $number) {
            BengkelTask::create([
                'job_name' => 'Pekerjaan Slide '.$number,
                'notification_number' => 'SLIDE-'.$number,
                'unit_work' => 'Workshop',
                'seksi' => 'Seksi',
                'usage_plan_date' => '2026-04-20',
                'catatan' => 'Regu Fabrikasi',
                'person_in_charge' => [],
                'person_in_charge_profiles' => [],
            ]);
        }

        $component = Livewire::test(DashboardPekerjaan::class, ['mode' => 'display'])
            ->call('nextSlide');

        $this->assertSame(1, $component->get('pageSlide'));

        BengkelTask::query()->where('job_name', 'like', 'Pekerjaan Slide%')->get()->take(2)->each->delete();
        $component->call('refreshBoard');

        $this->assertSame(0, $component->get('pageSlide'));
        $this->assertSame(1, $component->get('maxPages'));
    }

    public function test_public_display_limits_pic_rows_and_keeps_each_description_with_its_pic(): void
    {
        BengkelTask::create([
            'job_name' => 'Pekerjaan PIC Vertikal',
            'notification_number' => 'PIC-001',
            'unit_work' => 'Workshop',
            'seksi' => 'Seksi',
            'usage_plan_date' => '2026-04-20',
            'catatan' => 'Regu Fabrikasi',
            'person_in_charge' => [],
            'person_in_charge_profiles' => [
                ['name' => 'PIC Satu', 'work_descriptions' => ['Uraian satu']],
                ['name' => 'PIC Dua', 'work_descriptions' => ['Uraian dua']],
                ['name' => 'PIC Tiga', 'work_descriptions' => []],
                ['name' => 'PIC Empat', 'work_descriptions' => ['Uraian empat']],
            ],
        ]);

        $component = Livewire::test(DashboardPekerjaan::class, ['mode' => 'display']);

        $this->assertSame(3, substr_count($component->html(), 'data-testid="display-pic-row"'));
        $component
            ->assertSee('Uraian satu')
            ->assertSee('Uraian dua')
            ->assertSee('Uraian belum diisi.')
            ->assertSee('+1 PIC lainnya')
            ->assertDontSee('Uraian empat');
    }

    public function test_public_display_empty_state_is_single_and_page_count_is_one(): void
    {
        $component = Livewire::test(DashboardPekerjaan::class, ['mode' => 'display']);

        $this->assertSame(1, $component->get('maxPages'));
        $component->assertSee('Belum ada pekerjaan bengkel yang ditampilkan.');
    }

    #[DataProvider('displayPageCountCases')]
    public function test_public_display_page_count_matches_six_card_capacity(int $taskCount, int $expectedPages): void
    {
        foreach (range(1, $taskCount) as $number) {
            BengkelTask::create([
                'job_name' => 'Pekerjaan Kapasitas '.$number,
                'notification_number' => 'CAP-'.$number,
                'unit_work' => 'Workshop',
                'seksi' => 'Seksi',
                'usage_plan_date' => '2026-04-20',
                'catatan' => 'Regu Fabrikasi',
                'person_in_charge' => [],
                'person_in_charge_profiles' => [],
            ]);
        }

        $component = Livewire::test(DashboardPekerjaan::class, ['mode' => 'display']);

        $this->assertSame($expectedPages, $component->get('maxPages'));
    }

    /** @return array<string, array{int, int}> */
    public static function displayPageCountCases(): array
    {
        return [
            'six tasks' => [6, 1],
            'seven tasks' => [7, 2],
            'ten tasks' => [10, 2],
            'twelve tasks' => [12, 2],
        ];
    }

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
