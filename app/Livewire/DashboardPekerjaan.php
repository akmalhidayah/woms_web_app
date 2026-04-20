<?php

namespace App\Livewire;

use App\Models\BengkelPic;
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

    public int $perPageFabrikasi = 6;

    public int $perPageRefurbish = 4;

    public int $maxPages = 1;

    protected $listeners = ['nextSlide' => 'nextSlide'];

    public function mount(string $mode = 'admin'): void
    {
        $this->mode = $mode;
        $this->loadTasks();
    }

    public function refreshBoard(): void
    {
        $this->loadTasks();
    }

    public function loadTasks(): void
    {
        $picDirectory = BengkelPic::query()
            ->get(['id', 'name', 'avatar_path'])
            ->keyBy('id');
        $picDirectoryByName = BengkelPic::query()
            ->get(['id', 'name', 'avatar_path'])
            ->keyBy(fn (BengkelPic $pic) => mb_strtolower(trim($pic->name)));

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

        $this->tasks = $tasks->map(static function (BengkelTask $task) use ($picDirectory, $picDirectoryByName): array {
            $names = collect(is_array($task->person_in_charge) ? $task->person_in_charge : [])
                ->filter(fn ($name) => filled($name))
                ->values();

            $profiles = collect(is_array($task->person_in_charge_profiles) ? $task->person_in_charge_profiles : [])
                ->map(function ($profile) use ($picDirectory): array {
                    if (! is_array($profile)) {
                        return [];
                    }

                    $picId = isset($profile['id']) ? (int) $profile['id'] : null;
                    $currentPic = $picId ? $picDirectory->get($picId) : null;

                    return [
                        'id' => $picId,
                        'name' => $currentPic?->name ?: ($profile['name'] ?? ''),
                        'avatar_path' => $currentPic?->avatar_path ?: ($profile['avatar_path'] ?? null),
                        'avatar_url' => $currentPic?->avatar_url,
                    ];
                })
                ->filter(fn (array $profile): bool => filled($profile['name']))
                ->values();

            if ($profiles->isEmpty() && $names->isNotEmpty()) {
                $profiles = $names
                    ->map(function (string $name) use ($picDirectoryByName): array {
                        $currentPic = $picDirectoryByName->get(mb_strtolower(trim($name)));

                        return [
                            'id' => $currentPic?->id,
                            'name' => $currentPic?->name ?: $name,
                            'avatar_path' => $currentPic?->avatar_path,
                            'avatar_url' => $currentPic?->avatar_url,
                        ];
                    })
                    ->filter(fn (array $profile): bool => filled($profile['name']))
                    ->values();
            }

            return [
                'id' => $task->id,
                'notification_number' => $task->notification_number,
                'unit_work' => $task->unit_work,
                'seksi' => $task->seksi,
                'job_name' => $task->job_name,
                'usage_plan_date' => $task->usage_plan_date?->format('Y-m-d'),
                'person_in_charge' => $names->all(),
                'person_in_charge_profiles' => $profiles->all(),
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
