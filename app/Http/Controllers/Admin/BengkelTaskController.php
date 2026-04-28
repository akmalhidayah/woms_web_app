<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelDisplaySetting;
use App\Models\BengkelPic;
use App\Models\BengkelTask;
use App\Models\Order;
use App\Models\UnitWork;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BengkelTaskController extends Controller
{
    private const CATATAN_REGU_ALLOWED = [
        'Regu Fabrikasi',
        'Regu Bengkel (Refurbish)',
    ];

    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $regu = trim((string) $request->get('regu', ''));
        $perPage = (int) $request->get('per_page', 10);

        if ($perPage <= 0) {
            $perPage = 10;
        }

        if ($perPage > 100) {
            $perPage = 100;
        }

        $query = BengkelTask::query()->latest();

        if ($q !== '') {
            $query->where(function ($sub) use ($q): void {
                $sub->where('job_name', 'like', "%{$q}%")
                    ->orWhere('notification_number', 'like', "%{$q}%")
                    ->orWhere('unit_work', 'like', "%{$q}%")
                    ->orWhere('seksi', 'like', "%{$q}%")
                    ->orWhere('catatan', 'like', "%{$q}%");
            });
        }

        if ($regu === 'fabrikasi') {
            $query->where(function ($sub): void {
                $sub->whereNull('catatan')
                    ->orWhere('catatan', '')
                    ->orWhere('catatan', 'Regu Fabrikasi');
            });
        } elseif ($regu === 'refurbish') {
            $query->where('catatan', 'Regu Bengkel (Refurbish)');
        }

        $tasks = $query->paginate($perPage)->withQueryString();
        $displaySetting = BengkelDisplaySetting::current();

        $pics = BengkelPic::query()
            ->orderBy('name')
            ->get(['id', 'name', 'avatar_path', 'avatar_position_x', 'avatar_position_y']);

        $picsById = $pics->keyBy('id');
        $picsByName = $pics->keyBy(static fn (BengkelPic $pic): string => mb_strtolower(trim($pic->name)));

        $tasks->setCollection(
            $tasks->getCollection()->map(function (BengkelTask $task) use ($picsById, $picsByName): BengkelTask {
                $profiles = collect(is_array($task->person_in_charge_profiles) ? $task->person_in_charge_profiles : [])
                    ->map(function ($profile) use ($picsById, $picsByName): ?array {
                        if (! is_array($profile)) {
                            return null;
                        }

                        $currentPic = null;

                        if (! empty($profile['id'])) {
                            $currentPic = $picsById->get((int) $profile['id']);
                        }

                        if (! $currentPic && ! empty($profile['name'])) {
                            $currentPic = $picsByName->get(mb_strtolower(trim((string) $profile['name'])));
                        }

                        $name = $currentPic?->name ?? trim((string) ($profile['name'] ?? ''));

                        if ($name === '') {
                            return null;
                        }

                        return [
                            'id' => $currentPic?->id ?? ($profile['id'] ?? null),
                            'name' => $name,
                            'avatar_path' => $currentPic?->avatar_path ?? ($profile['avatar_path'] ?? null),
                            'avatar_url' => $currentPic?->avatar_url,
                            'avatar_position_x' => $currentPic?->avatar_position_x ?? (int) ($profile['avatar_position_x'] ?? 50),
                            'avatar_position_y' => $currentPic?->avatar_position_y ?? (int) ($profile['avatar_position_y'] ?? 50),
                            'work_descriptions' => $this->normalizeWorkDescriptions($profile['work_descriptions'] ?? []),
                        ];
                    })
                    ->filter()
                    ->values();

                if ($profiles->isEmpty()) {
                    $profiles = collect(is_array($task->person_in_charge) ? $task->person_in_charge : [])
                        ->map(function ($name) use ($picsByName): ?array {
                            $cleanName = trim((string) $name);

                            if ($cleanName === '') {
                                return null;
                            }

                            $currentPic = $picsByName->get(mb_strtolower($cleanName));

                            return [
                                'id' => $currentPic?->id,
                                'name' => $currentPic?->name ?? $cleanName,
                                'avatar_path' => $currentPic?->avatar_path,
                                'avatar_url' => $currentPic?->avatar_url,
                                'avatar_position_x' => $currentPic?->avatar_position_x ?? 50,
                                'avatar_position_y' => $currentPic?->avatar_position_y ?? 50,
                                'work_descriptions' => [],
                            ];
                        })
                        ->filter()
                        ->values();
                }

                $task->setAttribute('person_in_charge_profiles', $profiles->all());

                return $task;
            })
        );

        return view('admin.bengkel-tasks.index', compact('tasks', 'q', 'regu', 'perPage', 'displaySetting'));
    }

    public function create(): View
    {
        $picOptions = BengkelPic::query()->orderBy('name')->get();
        $catatanOptions = self::CATATAN_REGU_ALLOWED;
        $units = UnitWork::with('sections')->orderBy('name')->get();
        $workshopOrders = $this->workshopOrderOptions();

        return view('admin.bengkel-tasks.create', compact(
            'picOptions',
            'catatanOptions',
            'units',
            'workshopOrders',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        BengkelTask::create($data);

        return redirect()
            ->route('admin.bengkel-tasks.index', $this->indexQuery($request))
            ->with('status', 'Pekerjaan bengkel ditambahkan.');
    }

    public function edit(BengkelTask $bengkel_task): View
    {
        $picOptions = BengkelPic::query()->orderBy('name')->get();
        $catatanOptions = self::CATATAN_REGU_ALLOWED;
        $units = UnitWork::with('sections')->orderBy('name')->get();
        $workshopOrders = $this->workshopOrderOptions();

        $selectedPicIds = collect($bengkel_task->person_in_charge_profiles ?? [])
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        $picAssignments = collect($bengkel_task->person_in_charge_profiles ?? [])
            ->filter(fn ($profile): bool => is_array($profile) && ! empty($profile['id']))
            ->map(fn (array $profile): array => [
                'pic_id' => (int) $profile['id'],
                'descriptions' => $this->normalizeWorkDescriptions($profile['work_descriptions'] ?? []),
            ])
            ->values()
            ->all();

        if ($selectedPicIds === []) {
            $names = collect($bengkel_task->person_in_charge ?? [])->filter()->values();

            if ($names->isNotEmpty()) {
                $selectedPicIds = BengkelPic::query()
                    ->whereIn('name', $names->all())
                    ->pluck('id')
                    ->values()
                    ->all();

                $picAssignments = collect($selectedPicIds)
                    ->map(fn ($picId): array => [
                        'pic_id' => (int) $picId,
                        'descriptions' => [],
                    ])
                    ->values()
                    ->all();
            }
        }

        return view('admin.bengkel-tasks.edit', compact(
            'bengkel_task',
            'picOptions',
            'selectedPicIds',
            'picAssignments',
            'catatanOptions',
            'units',
            'workshopOrders',
        ));
    }

    public function update(Request $request, BengkelTask $bengkel_task): RedirectResponse
    {
        $data = $this->validateData($request);

        $bengkel_task->update($data);

        return redirect()
            ->route('admin.bengkel-tasks.index', $this->indexQuery($request))
            ->with('status', 'Pekerjaan bengkel diperbarui.');
    }

    public function destroy(Request $request, BengkelTask $bengkel_task): RedirectResponse
    {
        $bengkel_task->delete();

        return redirect()
            ->route('admin.bengkel-tasks.index', $this->indexQuery($request))
            ->with('status', 'Pekerjaan bengkel dihapus.');
    }

    public function complete(Request $request, BengkelTask $bengkel_task): RedirectResponse
    {
        $bengkel_task->update([
            'is_completed' => true,
        ]);

        return redirect()
            ->route('admin.bengkel-tasks.index', $this->indexQuery($request))
            ->with('status', 'Pekerjaan bengkel ditandai selesai.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'exists:bengkel_tasks,id'],
        ]);

        $deleted = BengkelTask::query()
            ->whereIn('id', collect($validated['task_ids'])->map(fn ($id): int => (int) $id)->all())
            ->delete();

        return redirect()
            ->route('admin.bengkel-tasks.index', $this->indexQuery($request))
            ->with('status', $deleted.' pekerjaan bengkel dihapus.');
    }

    public function updateDisplaySettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ticker_text' => ['nullable', 'string', 'max:2000'],
            'ticker_speed_seconds' => ['required', 'integer', 'between:5,60'],
        ]);

        $displaySetting = BengkelDisplaySetting::current();
        $displaySetting->update([
            'ticker_text' => trim((string) ($validated['ticker_text'] ?? '')),
            'ticker_speed_seconds' => (int) $validated['ticker_speed_seconds'],
        ]);

        return redirect()
            ->route('admin.bengkel-tasks.index', $this->indexQuery($request))
            ->with('status', 'Running text display berhasil diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateData(Request $request): array
    {
        $validated = $request->validate([
            'job_name' => ['required', 'string', 'max:255'],
            'notification_number' => ['nullable', 'string', 'max:50'],
            'unit_work' => ['nullable', 'string', 'max:255'],
            'seksi' => ['nullable', 'string', 'max:255'],
            'usage_plan_date' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string', 'in:'.implode(',', self::CATATAN_REGU_ALLOWED)],
            'pic_ids' => ['nullable', 'array'],
            'pic_ids.*' => ['nullable', 'integer', 'exists:bengkel_pics,id'],
            'pic_assignments' => ['nullable', 'array'],
            'pic_assignments.*.pic_id' => ['nullable', 'integer', 'exists:bengkel_pics,id', 'distinct'],
            'pic_assignments.*.descriptions' => ['nullable', 'array'],
            'pic_assignments.*.descriptions.*' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('catatan', $validated)) {
            $catatan = trim((string) ($validated['catatan'] ?? ''));
            $validated['catatan'] = $catatan === '' ? null : $catatan;
        }

        $assignments = collect($validated['pic_assignments'] ?? [])
            ->filter(fn ($row): bool => is_array($row) && ! empty($row['pic_id']))
            ->map(fn (array $row): array => [
                'pic_id' => (int) $row['pic_id'],
                'descriptions' => $this->normalizeWorkDescriptions($row['descriptions'] ?? []),
            ])
            ->unique('pic_id')
            ->values();

        $picIds = $assignments->pluck('pic_id');

        if ($picIds->isEmpty()) {
            $picIds = collect($validated['pic_ids'] ?? [])
                ->filter()
                ->map(static fn ($value): int => (int) $value)
                ->unique()
                ->values();

            $assignments = $picIds
                ->map(fn (int $picId): array => [
                    'pic_id' => $picId,
                    'descriptions' => [],
                ])
                ->values();
        }

        if ($picIds->isNotEmpty()) {
            $pics = BengkelPic::query()
                ->whereIn('id', $picIds->all())
                ->get(['id', 'name', 'avatar_path', 'avatar_position_x', 'avatar_position_y']);

            $picsById = $pics->keyBy('id');

            $profiles = $assignments
                ->map(function (array $assignment) use ($picsById): ?array {
                    $pic = $picsById->get($assignment['pic_id']);

                    if (! $pic) {
                        return null;
                    }

                    return [
                        'id' => $pic->id,
                        'name' => $pic->name,
                        'avatar_path' => $pic->avatar_path,
                        'avatar_position_x' => $pic->avatar_position_x,
                        'avatar_position_y' => $pic->avatar_position_y,
                        'work_descriptions' => $assignment['descriptions'],
                    ];
                })
                ->filter()
                ->values();

            $validated['person_in_charge'] = $profiles->pluck('name')->values()->all();
            $validated['person_in_charge_profiles'] = $profiles->all();
        } else {
            $validated['person_in_charge'] = [];
            $validated['person_in_charge_profiles'] = [];
        }

        unset($validated['pic_ids']);
        unset($validated['pic_assignments']);

        return $validated;
    }

    /**
     * @param  mixed  $descriptions
     * @return list<string>
     */
    private function normalizeWorkDescriptions(mixed $descriptions): array
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

    /**
     * @return array<string, scalar>
     */
    private function indexQuery(Request $request): array
    {
        return collect($request->only('q', 'regu', 'per_page', 'page'))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function workshopOrderOptions()
    {
        return Order::query()
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedWorkshop->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->orderByDesc('id')
            ->get(['id', 'nomor_order', 'notifikasi', 'nama_pekerjaan', 'unit_kerja', 'seksi', 'target_selesai'])
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'nomor_order' => $order->nomor_order,
                'notifikasi' => $order->notifikasi,
                'nama_pekerjaan' => $order->nama_pekerjaan,
                'unit_kerja' => $order->unit_kerja,
                'seksi' => $order->seksi,
                'target_selesai' => optional($order->target_selesai)->format('Y-m-d'),
                'label' => trim($order->nomor_order.' - '.$order->nama_pekerjaan),
            ])
            ->values();
    }
}
