<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        return view('admin.bengkel-tasks.index', compact('tasks', 'q', 'regu', 'perPage'));
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
            ->route('admin.bengkel-tasks.index')
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
            ->route('admin.bengkel-tasks.index')
            ->with('status', 'Pekerjaan bengkel diperbarui.');
    }

    public function destroy(BengkelTask $bengkel_task): RedirectResponse
    {
        $bengkel_task->delete();

        return redirect()
            ->route('admin.bengkel-tasks.index')
            ->with('status', 'Pekerjaan bengkel dihapus.');
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
                ->get(['id', 'name', 'avatar_path']);

            $validated['person_in_charge'] = $pics->pluck('name')->values()->all();
            $validated['person_in_charge_profiles'] = $pics->map(static fn (BengkelPic $pic): array => [
                'id' => $pic->id,
                'name' => $pic->name,
                'avatar_path' => $pic->avatar_path,
            ])->values()->all();
        } else {
            $validated['person_in_charge'] = [];
            $validated['person_in_charge_profiles'] = [];
        }

        unset($validated['pic_ids']);

        return $validated;
    }
}
