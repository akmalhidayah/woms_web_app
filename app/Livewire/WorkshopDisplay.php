<?php

namespace App\Livewire;

use Livewire\Component;

class WorkshopDisplay extends Component
{
    public const SECTION_WORKSHOP = 'workshop';

    public const SECTION_DAILY_REPORT = 'daily-report';

    public string $activeSection = self::SECTION_WORKSHOP;

    public int $cycle = 0;

    protected $listeners = [
        'workshop-display-cycle-completed' => 'showDailyReport',
        'daily-report-display-cycle-completed' => 'showWorkshop',
    ];

    public function showDailyReport(): void
    {
        $this->activeSection = self::SECTION_DAILY_REPORT;
        $this->cycle++;
    }

    public function showWorkshop(): void
    {
        $this->activeSection = self::SECTION_WORKSHOP;
        $this->cycle++;
    }

    public function render()
    {
        return view('livewire.workshop-display');
    }
}
