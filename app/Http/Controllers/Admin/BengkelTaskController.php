<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BengkelDisplaySetting;
use App\Models\BengkelPic;
use App\Models\BengkelTask;
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

        return view('admin.bengkel-tasks.create', compact(
            'picOptions',
            'catatanOptions',
            'units',
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

        $selectedPicIds = collect($bengkel_task->person_in_charge_profiles ?? [])
            ->pluck('id')
            ->filter()
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
            }
        }

        return view('admin.bengkel-tasks.edit', compact(
            'bengkel_task',
            'picOptions',
            'selectedPicIds',
            'catatanOptions',
            'units',
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
        ]);

        if (array_key_exists('catatan', $validated)) {
            $catatan = trim((string) ($validated['catatan'] ?? ''));
            $validated['catatan'] = $catatan === '' ? null : $catatan;
        }

        $picIds = collect($validated['pic_ids'] ?? [])
            ->filter()
            ->map(static fn ($value): int => (int) $value)
            ->unique()
            ->values();

        if ($picIds->isNotEmpty()) {
            $pics = BengkelPic::query()
                ->whereIn('id', $picIds->all())
                ->orderBy('name')
                ->get(['id', 'name', 'avatar_path', 'avatar_position_x', 'avatar_position_y']);

            $validated['person_in_charge'] = $pics->pluck('name')->values()->all();
            $validated['person_in_charge_profiles'] = $pics->map(static fn (BengkelPic $pic): array => [
                'id' => $pic->id,
                'name' => $pic->name,
                'avatar_path' => $pic->avatar_path,
                'avatar_position_x' => $pic->avatar_position_x,
                'avatar_position_y' => $pic->avatar_position_y,
            ])->values()->all();
        } else {
            $validated['person_in_charge'] = [];
            $validated['person_in_charge_profiles'] = [];
        }

        unset($validated['pic_ids']);

        return $validated;
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
}
