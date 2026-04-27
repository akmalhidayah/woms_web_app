<?php

namespace Tests\Feature\Livewire;

use App\Livewire\DashboardPekerjaan;
use App\Models\BengkelTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Perbaikan Bucket Awal')
            ->assertDontSee('Perbaikan Bucket Baru');

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
            ->assertSee('Perbaikan Bucket Baru');
    }

    public function test_display_mode_uses_keep_alive_polling(): void
    {
        Livewire::test(DashboardPekerjaan::class, ['mode' => 'display'])
            ->assertSeeHtml('wire:poll.keep-alive.10s="refreshBoard"');
    }
}
