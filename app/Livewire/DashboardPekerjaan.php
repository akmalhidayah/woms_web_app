<?php

namespace App\Livewire;

use App\Models\BengkelTask;
use Livewire\Component;

class DashboardPekerjaan extends Component
{
    public string $mode = 'admin';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $tasks = [];

    public int $pageSlide = 0;

    public int $perPageFabrikasi = 4;

    public int $perPageRefurbish = 2;

    public int $maxPages = 1;

    protected $listeners = ['nextSlide' => 'nextSlide'];

    public function mount(string $mode = 'admin'): void
    {
        $this->mode = $mode;
        $this->loadTasks();
    }

    public function loadTasks(): void
    {
        $tasks = BengkelTask::query()
            ->select([
                'id',
                'notification_number',
                'unit_work',
                'seksi',
                'job_name',
                'usage_plan_date',
                'person_in_charge',
                'person_in_charge_profiles',
                'catatan',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->get();

        $this->tasks = $tasks->map(static function (BengkelTask $task): array {
            return [
                'id' => $task->id,
                'notification_number' => $task->notification_number,
                'unit_work' => $task->unit_work,
                'seksi' => $task->seksi,
                'job_name' => $task->job_name,
                'usage_plan_date' => $task->usage_plan_date?->format('Y-m-d'),
                'person_in_charge' => is_array($task->person_in_charge) ? $task->person_in_charge : [],
                'person_in_charge_profiles' => is_array($task->person_in_charge_profiles) ? $task->person_in_charge_profiles : [],
                'catatan' => $task->catatan,
            ];
        })->all();

        $collection = collect($this->tasks);

        $fabrikasiCount = $collection
            ->filter(fn (array $row): bool => (($row['catatan'] ?? null) === 'Regu Fabrikasi') || empty($row['catatan']))
            ->count();

        $refurbishCount = $collection
            ->filter(fn (array $row): bool => ($row['catatan'] ?? null) === 'Regu Bengkel (Refurbish)')
            ->count();

        $fabrikasiPages = (int) ceil($fabrikasiCount / max(1, $this->perPageFabrikasi));
        $refurbishPages = (int) ceil($refurbishCount / max(1, $this->perPageRefurbish));

        $this->maxPages = max(1, $fabrikasiPages, $refurbishPages);
        $this->pageSlide = (int) ($this->pageSlide % $this->maxPages);
    }

    public function nextSlide(): void
    {
        if ($this->maxPages <= 1) {
            $this->pageSlide = 0;
            return;
        }

        $this->pageSlide = ($this->pageSlide + 1) % $this->maxPages;
    }

    public function render()
    {
        return view('livewire.dashboard-pekerjaan');
    }
}
