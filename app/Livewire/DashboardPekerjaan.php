<?php

namespace App\Livewire;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelDisplaySetting;
use App\Models\BengkelPic;
use App\Models\BengkelTask;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Services\BengkelTasks\WorkshopOrderTaskSyncer;
use Illuminate\Support\Facades\Cache;
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

    public int $perPageRefurbish = 3;

    public int $maxPages = 1;

    public int $displayPollCounter = 0;

    public string $tickerText = '';

    public int $tickerSpeedSeconds = 18;

    /**
     * @var array{total_workshop: int, total_service: int, processed_workshop: int, processed_service: int}
     */
    public array $orderSummary = [
        'total_workshop' => 0,
        'total_service' => 0,
        'processed_workshop' => 0,
        'processed_service' => 0,
    ];

    protected $listeners = [
        'nextSlide' => 'nextSlide',
        'forceRefreshBoard' => 'refreshBoard',
    ];

    public function mount(string $mode = 'admin'): void
    {
        $this->mode = $mode;
        $this->loadTasks();
    }

    public function refreshBoard(): void
    {
        $this->loadTasks();
    }

    public function tickDisplay(): void
    {
        $this->loadTasks();

        if ($this->mode !== 'display') {
            return;
        }

        $this->displayPollCounter++;

        if ($this->displayPollCounter >= 6) {
            $this->displayPollCounter = 0;
            $this->nextSlide();
        }
    }

    public function loadTasks(): void
    {
        if (Cache::add('bengkel_tasks:auto_sync_display', true, now()->addSeconds(20))) {
            app(WorkshopOrderTaskSyncer::class)->syncOpenWorkshopOrders();
        }

        $this->loadDisplaySettings();
        $this->loadOrderSummary();

        $pics = BengkelPic::query()
            ->get(['id', 'name', 'avatar_path', 'avatar_position_x', 'avatar_position_y']);

        $picDirectory = $pics->keyBy('id');
        $picDirectoryByName = $pics->keyBy(fn (BengkelPic $pic) => mb_strtolower(trim($pic->name)));
        $picDirectoryByPath = $pics
            ->filter(fn (BengkelPic $pic): bool => filled($pic->avatar_path))
            ->keyBy('avatar_path');

        $tasks = BengkelTask::query()
            ->with('order.orderWorkshop')
            ->whereNull('archived_at')
            ->where(function ($builder): void {
                $builder
                    ->whereNull('order_id')
                    ->orWhereNotIn('order_id', BengkelTask::query()
                        ->whereNotNull('order_id')
                        ->whereNotNull('archived_at')
                        ->select('order_id'));
            })
            ->select([
                'id',
                'order_id',
                'notification_number',
                'unit_work',
                'seksi',
                'job_name',
                'usage_plan_date',
                'person_in_charge',
                'person_in_charge_profiles',
                'catatan',
                'is_completed',
                'progress_status',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->get();

        $this->tasks = $tasks->map(static function (BengkelTask $task) use ($picDirectory, $picDirectoryByName, $picDirectoryByPath): array {
            $names = collect(is_array($task->person_in_charge) ? $task->person_in_charge : [])
                ->filter(fn ($name) => filled($name))
                ->values();

            $profiles = collect(is_array($task->person_in_charge_profiles) ? $task->person_in_charge_profiles : [])
                ->map(function ($profile) use ($picDirectory, $picDirectoryByName, $picDirectoryByPath): array {
                    if (! is_array($profile)) {
                        return [];
                    }

                    $picId = isset($profile['id']) ? (int) $profile['id'] : null;
                    $currentPic = $picId ? $picDirectory->get($picId) : null;

                    if (! $currentPic && filled($profile['name'] ?? null)) {
                        $currentPic = $picDirectoryByName->get(mb_strtolower(trim((string) $profile['name'])));
                    }

                    if (! $currentPic && filled($profile['avatar_path'] ?? null)) {
                        $currentPic = $picDirectoryByPath->get(trim((string) $profile['avatar_path']));
                    }

                    return [
                        'id' => $currentPic?->id ?? $picId,
                        'name' => $currentPic?->name ?: ($profile['name'] ?? ''),
                        'avatar_path' => $currentPic?->avatar_path ?: ($profile['avatar_path'] ?? null),
                        'avatar_url' => $currentPic?->avatar_url,
                        'avatar_position_x' => $currentPic?->avatar_position_x ?? (int) ($profile['avatar_position_x'] ?? 50),
                        'avatar_position_y' => $currentPic?->avatar_position_y ?? (int) ($profile['avatar_position_y'] ?? 50),
                        'work_descriptions' => self::normalizeWorkDescriptions($profile['work_descriptions'] ?? []),
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
                            'avatar_position_x' => $currentPic?->avatar_position_x ?? 50,
                            'avatar_position_y' => $currentPic?->avatar_position_y ?? 50,
                            'work_descriptions' => [],
                        ];
                    })
                    ->filter(fn (array $profile): bool => filled($profile['name']))
                    ->values();
            }

            $progressStatus = $task->effectiveProgressStatus() ?: OrderWorkshop::PROGRESS_MENUNGGU_JADWAL;
            $isCompleted = $progressStatus === OrderWorkshop::PROGRESS_DONE || (bool) $task->is_completed;

            return [
                'id' => $task->id,
                'order_id' => $task->order_id,
                'notification_number' => $task->notification_number,
                'unit_work' => $task->unit_work,
                'seksi' => $task->seksi,
                'job_name' => mb_strtoupper((string) $task->job_name),
                'usage_plan_date' => $task->usage_plan_date?->format('Y-m-d'),
                'person_in_charge' => $names->all(),
                'person_in_charge_profiles' => $profiles->all(),
                'catatan' => $task->catatan,
                'is_completed' => $isCompleted,
                'progress_status' => $progressStatus,
                'progress_label' => OrderWorkshop::progressOptions()[$progressStatus] ?? 'Menunggu Jadwal',
            ];
        })->all();

        $collection = collect($this->tasks);

        $fabrikasiRows = $collection
            ->filter(fn (array $row): bool => (($row['catatan'] ?? null) === 'Regu Fabrikasi') || empty($row['catatan']))
            ->values();

        $refurbishRows = $collection
            ->filter(fn (array $row): bool => ($row['catatan'] ?? null) === 'Regu Bengkel (Refurbish)')
            ->values();

        $fabrikasiPerPage = $this->mode === 'display'
            ? 3
            : ($fabrikasiRows->contains(fn (array $row): bool => count($row['person_in_charge_profiles'] ?? []) > 2)
                ? 4
                : $this->perPageFabrikasi);

        $refurbishPerPage = $this->mode === 'display'
            ? 3
            : ($refurbishRows->contains(fn (array $row): bool => count($row['person_in_charge_profiles'] ?? []) > 2)
                ? 2
                : $this->perPageRefurbish);

        $fabrikasiPages = (int) ceil($fabrikasiRows->count() / max(1, $fabrikasiPerPage));
        $refurbishPages = (int) ceil($refurbishRows->count() / max(1, $refurbishPerPage));

        $this->maxPages = max(1, $fabrikasiPages, $refurbishPages);
        $this->pageSlide = (int) ($this->pageSlide % $this->maxPages);
    }

    private function loadOrderSummary(): void
    {
        $orders = Order::query()
            ->with([
                'orderWorkshop:id,order_id,progress_status',
                'purchaseOrder:id,order_id,progress_pekerjaan',
                'initialWork:id,order_id,progress_pekerjaan',
            ])
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedWorkshop->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
                OrderUserNoteStatus::ApprovedJasa->value,
            ])
            ->get(['id', 'prioritas', 'catatan_status']);

        $workshopOrders = $orders->filter(
            fn (Order $order): bool => in_array($order->catatan_status, [
                OrderUserNoteStatus::ApprovedWorkshop,
                OrderUserNoteStatus::ApprovedWorkshopJasa,
            ], true)
        );

        $serviceOrders = $orders->filter(
            fn (Order $order): bool => $order->catatan_status === OrderUserNoteStatus::ApprovedJasa
        );

        $completedWorkshop = $workshopOrders->filter(
            fn (Order $order): bool => $order->orderWorkshop?->progress_status === OrderWorkshop::PROGRESS_DONE
        )->count();

        $completedService = $serviceOrders->filter(function (Order $order): bool {
            $progress = Order::priorityPrimaryFor($order->prioritas) === 'emergency'
                ? $order->initialWork?->progress_pekerjaan
                : $order->purchaseOrder?->progress_pekerjaan;

            return (int) $progress >= 100;
        })->count();

        $this->orderSummary = [
            'total_workshop' => $workshopOrders->count(),
            'total_service' => $serviceOrders->count(),
            'processed_workshop' => max(0, $workshopOrders->count() - $completedWorkshop),
            'processed_service' => max(0, $serviceOrders->count() - $completedService),
        ];
    }

    public function loadDisplaySettings(): void
    {
        $setting = BengkelDisplaySetting::current();

        $this->tickerText = trim((string) ($setting->ticker_text ?? ''));
        $this->tickerSpeedSeconds = max(5, min(60, (int) ($setting->ticker_speed_seconds ?? 18)));
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

    /**
     * @param  mixed  $descriptions
     * @return list<string>
     */
    private static function normalizeWorkDescriptions(mixed $descriptions): array
    {
        if (! is_array($descriptions)) {
            return [];
        }

        return collect($descriptions)
            ->map(fn ($description): string => trim((string) $description))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
