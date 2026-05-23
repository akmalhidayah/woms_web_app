<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Orders\Enums\OrderDocumentType;
use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelDisplaySetting;
use App\Models\BengkelPic;
use App\Models\BengkelTask;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\UnitWork;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BengkelTaskController extends Controller
{
    private const ATTACHMENT_DISK = 'public';
    private const ATTACHMENT_DIRECTORY = 'bengkel-task-attachments';
    private const ARCHIVE_ORDER_PREFIX = 'MANUAL-BENGKEL-';

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

        $query = BengkelTask::query()
            ->with('order.orderWorkshop')
            ->whereNull('archived_at')
            ->latest('created_at')
            ->latest('id');

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
                $this->syncTaskCompletionFromWorkshop($task);
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
        $progressOptions = OrderWorkshop::progressOptions();

        return view('admin.bengkel-tasks.create', compact(
            'picOptions',
            'catatanOptions',
            'units',
            'workshopOrders',
            'progressOptions',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        if (! empty($data['order_id']) && in_array((int) $data['order_id'], $this->unavailableWorkshopOrderIds(), true)) {
            return back()
                ->withErrors(['order_id' => 'Order ini sudah tampil di display atau sudah selesai.'])
                ->withInput();
        }

        $data = $this->mergeUploadedAttachment($request, $data);

        $task = BengkelTask::create($data);
        $this->syncWorkshopProgressFromTask($task);

        return redirect()
            ->route('admin.bengkel-tasks.index', $this->indexQuery($request))
            ->with('status', 'Pekerjaan bengkel ditambahkan.');
    }

    public function edit(BengkelTask $bengkel_task): View
    {
        $picOptions = BengkelPic::query()->orderBy('name')->get();
        $catatanOptions = self::CATATAN_REGU_ALLOWED;
        $units = UnitWork::with('sections')->orderBy('name')->get();
        $workshopOrders = $this->workshopOrderOptions($bengkel_task->order_id);
        $progressOptions = OrderWorkshop::progressOptions();

        $picsById = $picOptions->keyBy('id');
        $picsByName = $picOptions->keyBy(static fn (BengkelPic $pic): string => mb_strtolower(trim($pic->name)));
        $picsByPath = $picOptions
            ->filter(fn (BengkelPic $pic): bool => filled($pic->avatar_path))
            ->keyBy('avatar_path');

        $picAssignments = collect($bengkel_task->person_in_charge_profiles ?? [])
            ->filter(fn ($profile): bool => is_array($profile))
            ->map(function (array $profile) use ($picsById, $picsByName, $picsByPath): ?array {
                $pic = ! empty($profile['id']) ? $picsById->get((int) $profile['id']) : null;

                if (! $pic && filled($profile['name'] ?? null)) {
                    $pic = $picsByName->get(mb_strtolower(trim((string) $profile['name'])));
                }

                if (! $pic && filled($profile['avatar_path'] ?? null)) {
                    $pic = $picsByPath->get(trim((string) $profile['avatar_path']));
                }

                if (! $pic) {
                    return null;
                }

                return [
                    'pic_id' => $pic->id,
                    'descriptions' => $this->normalizeWorkDescriptions($profile['work_descriptions'] ?? []),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $selectedPicIds = collect($picAssignments)
            ->pluck('pic_id')
            ->values()
            ->all();

        if ($picAssignments === []) {
            $names = collect($bengkel_task->person_in_charge ?? [])->filter()->values();

            if ($names->isNotEmpty()) {
                $matchedPics = $names
                    ->map(fn ($name) => $picsByName->get(mb_strtolower(trim((string) $name))))
                    ->filter()
                    ->values()
                    ->all();

                $picAssignments = collect($matchedPics)
                    ->map(fn (BengkelPic $pic): array => [
                        'pic_id' => $pic->id,
                        'descriptions' => [],
                    ])
                    ->values()
                    ->all();

                $selectedPicIds = collect($picAssignments)
                    ->pluck('pic_id')
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
            'progressOptions',
        ));
    }

    public function update(Request $request, BengkelTask $bengkel_task): RedirectResponse
    {
        $hasPicInput = $request->exists('pic_assignments') || $request->exists('pic_ids');
        $data = $this->validateData($request);

        if (! empty($data['order_id']) && in_array((int) $data['order_id'], $this->unavailableWorkshopOrderIds($bengkel_task->order_id), true)) {
            return back()
                ->withErrors(['order_id' => 'Order ini sudah tampil di display atau sudah selesai.'])
                ->withInput();
        }

        if (! $hasPicInput) {
            unset($data['person_in_charge'], $data['person_in_charge_profiles']);
        }

        $data = $this->mergeUploadedAttachment($request, $data, $bengkel_task);

        $bengkel_task->update($data);
        $this->syncWorkshopProgressFromTask($bengkel_task->fresh('order.orderWorkshop'));

        return redirect()
            ->route('admin.bengkel-tasks.index', $this->indexQuery($request))
            ->with('status', 'Pekerjaan bengkel diperbarui.');
    }

    public function destroy(Request $request, BengkelTask $bengkel_task): RedirectResponse
    {
        $this->deleteAttachment($bengkel_task->attachment_path);
        $bengkel_task->delete();

        return redirect()
            ->route('admin.bengkel-tasks.index', $this->indexQuery($request))
            ->with('status', 'Pekerjaan bengkel dihapus.');
    }

    public function complete(Request $request, BengkelTask $bengkel_task): RedirectResponse
    {
        $bengkel_task->update([
            'is_completed' => true,
            'progress_status' => OrderWorkshop::PROGRESS_DONE,
        ]);
        $this->syncWorkshopProgressFromTask($bengkel_task->fresh('order.orderWorkshop'));

        return redirect()
            ->route('admin.bengkel-tasks.index', $this->indexQuery($request))
            ->with('status', 'Pekerjaan bengkel ditandai selesai.');
    }

    public function updateProgress(Request $request, BengkelTask $bengkel_task): RedirectResponse
    {
        $validated = $request->validate([
            'progress_status' => ['required', 'string', 'in:'.implode(',', array_keys(OrderWorkshop::progressOptions()))],
        ]);

        $progressStatus = $validated['progress_status'];

        $bengkel_task->update([
            'progress_status' => $progressStatus,
            'is_completed' => $progressStatus === OrderWorkshop::PROGRESS_DONE,
        ]);
        $this->syncWorkshopProgressFromTask($bengkel_task->fresh('order.orderWorkshop'));

        return redirect()
            ->route('admin.bengkel-tasks.index', $this->indexQuery($request))
            ->with('status', 'Status pekerjaan bengkel diperbarui.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'exists:bengkel_tasks,id'],
        ]);

        $tasks = BengkelTask::query()
            ->whereIn('id', collect($validated['task_ids'])->map(fn ($id): int => (int) $id)->all())
            ->get(['id', 'attachment_path']);

        $tasks->each(fn (BengkelTask $task) => $this->deleteAttachment($task->attachment_path));

        $deleted = BengkelTask::query()
            ->whereIn('id', $tasks->pluck('id')->all())
            ->delete();

        return redirect()
            ->route('admin.bengkel-tasks.index', $this->indexQuery($request))
            ->with('status', $deleted.' pekerjaan bengkel dihapus.');
    }

    public function archive(Request $request, BengkelTask $bengkel_task): RedirectResponse
    {
        $this->archiveTaskToWorkshopOrder($bengkel_task, $request->user()?->id);

        return redirect()
            ->route('admin.bengkel-tasks.index', $this->indexQuery($request))
            ->with('status', 'Pekerjaan bengkel diarsipkan ke Order Pekerjaan Bengkel.');
    }

    public function bulkArchive(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'exists:bengkel_tasks,id'],
        ]);

        $tasks = BengkelTask::query()
            ->whereNull('archived_at')
            ->whereIn('id', collect($validated['task_ids'])->map(fn ($id): int => (int) $id)->all())
            ->orderBy('id')
            ->get();

        $tasks->each(fn (BengkelTask $task) => $this->archiveTaskToWorkshopOrder($task, $request->user()?->id));

        return redirect()
            ->route('admin.bengkel-tasks.index', $this->indexQuery($request))
            ->with('status', $tasks->count().' pekerjaan bengkel diarsipkan ke Order Pekerjaan Bengkel.');
    }

    private function archiveTaskToWorkshopOrder(BengkelTask $task, ?int $userId): Order
    {
        return DB::transaction(function () use ($task, $userId): Order {
            $lockedTask = BengkelTask::query()
                ->with('order.orderWorkshop')
                ->lockForUpdate()
                ->findOrFail($task->id);

            if ($lockedTask->archived_order_id) {
                return $lockedTask->archivedOrder ?: Order::query()->findOrFail($lockedTask->archived_order_id);
            }

            $order = $lockedTask->order;
            $tanggalOrder = optional($lockedTask->created_at)->format('Y-m-d') ?: now()->toDateString();
            $targetSelesai = optional($lockedTask->usage_plan_date)->format('Y-m-d') ?: $tanggalOrder;

            if ($targetSelesai < $tanggalOrder) {
                $tanggalOrder = $targetSelesai;
            }

            $orderData = [
                'nama_pekerjaan' => mb_strtoupper(trim((string) $lockedTask->job_name)),
                'unit_kerja' => filled($lockedTask->unit_work) ? $lockedTask->unit_work : '-',
                'seksi' => filled($lockedTask->seksi) ? $lockedTask->seksi : '-',
                'deskripsi' => $this->archiveDescription($lockedTask),
                'prioritas' => Order::PRIORITY_LOW,
                'tanggal_order' => $tanggalOrder,
                'target_selesai' => $targetSelesai,
                'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
                'catatan' => $this->archiveRegu($lockedTask),
            ];

            if (! $order) {
                $nomorOrder = $this->archiveOrderNumber($lockedTask);

                $order = Order::create([
                    ...$orderData,
                    'nomor_order' => $nomorOrder,
                    'notifikasi' => $this->archiveNotificationNumber($lockedTask, $nomorOrder),
                    'created_by' => $userId,
                ]);
            } else {
                $order->update($orderData);
            }

            $workshop = $order->orderWorkshop()->firstOrNew();
            $workshop->fill([
                'progress_status' => $lockedTask->progress_status ?: OrderWorkshop::PROGRESS_MENUNGGU_JADWAL,
                'catatan' => $this->archiveRegu($lockedTask),
            ]);
            $order->orderWorkshop()->save($workshop);

            $this->copyTaskAttachmentToOrderGambarTeknik($lockedTask, $order, $userId);

            $lockedTask->forceFill([
                'order_id' => $order->id,
                'archived_order_id' => $order->id,
                'archived_at' => now(),
            ])->save();

            return $order;
        });
    }

    private function archiveOrderNumber(BengkelTask $task): string
    {
        $candidate = trim((string) $task->notification_number);

        if ($candidate !== '' && ! Order::query()->where('nomor_order', $candidate)->exists()) {
            return $candidate;
        }

        $base = self::ARCHIVE_ORDER_PREFIX.str_pad((string) $task->id, 6, '0', STR_PAD_LEFT);

        if (! Order::query()->where('nomor_order', $base)->exists()) {
            return $base;
        }

        for ($counter = 2; $counter < 100; $counter++) {
            $number = $base.'-'.$counter;

            if (! Order::query()->where('nomor_order', $number)->exists()) {
                return $number;
            }
        }

        return $base.'-'.now()->format('YmdHis');
    }

    private function archiveNotificationNumber(BengkelTask $task, string $nomorOrder): ?string
    {
        $candidate = trim((string) $task->notification_number);

        if ($candidate === '' || $candidate === $nomorOrder) {
            return null;
        }

        if (Order::query()->where('notifikasi', $candidate)->exists()) {
            return null;
        }

        return $candidate;
    }

    private function archiveRegu(BengkelTask $task): string
    {
        $regu = trim((string) ($task->catatan ?? ''));

        return $regu !== '' ? $regu : 'Regu Fabrikasi';
    }

    private function archiveDescription(BengkelTask $task): string
    {
        $lines = ['Arsip dari Display Pekerjaan Bengkel.'];
        $profiles = collect(is_array($task->person_in_charge_profiles) ? $task->person_in_charge_profiles : [])
            ->filter(fn ($profile): bool => is_array($profile) && trim((string) ($profile['name'] ?? '')) !== '')
            ->map(function (array $profile): string {
                $descriptions = $this->normalizeWorkDescriptions($profile['work_descriptions'] ?? []);
                $descriptionText = $descriptions !== [] ? ' - '.implode(', ', $descriptions) : '';

                return trim((string) $profile['name']).$descriptionText;
            })
            ->values();

        if ($profiles->isNotEmpty()) {
            $lines[] = 'PIC: '.$profiles->implode('; ');
        }

        return implode("\n", $lines);
    }

    private function copyTaskAttachmentToOrderGambarTeknik(BengkelTask $task, Order $order, ?int $userId): void
    {
        if (! $task->attachment_path || ! Storage::disk(self::ATTACHMENT_DISK)->exists($task->attachment_path)) {
            return;
        }

        if ($order->documents()->where('jenis_dokumen', OrderDocumentType::GambarTeknik->value)->exists()) {
            return;
        }

        $sourcePath = $task->attachment_path;
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $filename = uniqid('gambar-teknik-', true).($extension ? '.'.$extension : '');
        $targetPath = 'orders/'.$order->id.'/documents/'.OrderDocumentType::GambarTeknik->value.'/'.$filename;

        Storage::disk('local')->put($targetPath, Storage::disk(self::ATTACHMENT_DISK)->get($sourcePath));

        $order->documents()->create([
            'jenis_dokumen' => OrderDocumentType::GambarTeknik->value,
            'nama_file_asli' => $task->attachment_original_name ?: basename($sourcePath),
            'path_file' => $targetPath,
            'uploaded_by' => $userId,
            'uploaded_at' => now(),
        ]);
    }

    public function attachment(BengkelTask $bengkel_task): Response
    {
        abort_unless(
            $bengkel_task->attachment_path && Storage::disk(self::ATTACHMENT_DISK)->exists($bengkel_task->attachment_path),
            404
        );

        $filename = str_replace('"', '', $bengkel_task->attachment_display_name ?: basename($bengkel_task->attachment_path));

        return response()->file(
            Storage::disk(self::ATTACHMENT_DISK)->path($bengkel_task->attachment_path),
            [
                'Content-Type' => $bengkel_task->attachment_mime_type ?: (Storage::disk(self::ATTACHMENT_DISK)->mimeType($bengkel_task->attachment_path) ?: 'application/octet-stream'),
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
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
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'notification_number' => ['nullable', 'string', 'max:50'],
            'unit_work' => ['nullable', 'string', 'max:255'],
            'seksi' => ['nullable', 'string', 'max:255'],
            'usage_plan_date' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string', 'in:'.implode(',', self::CATATAN_REGU_ALLOWED)],
            'progress_status' => ['nullable', 'string', 'in:'.implode(',', array_keys(OrderWorkshop::progressOptions()))],
            'pic_ids' => ['nullable', 'array'],
            'pic_ids.*' => ['nullable', 'integer', 'exists:bengkel_pics,id'],
            'pic_assignments' => ['nullable', 'array'],
            'pic_assignments.*.pic_id' => ['nullable', 'integer', 'exists:bengkel_pics,id', 'distinct'],
            'pic_assignments.*.descriptions' => ['nullable', 'array'],
            'pic_assignments.*.descriptions.*' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if (array_key_exists('catatan', $validated)) {
            $catatan = trim((string) ($validated['catatan'] ?? ''));
            $validated['catatan'] = $catatan === '' ? null : $catatan;
        }

        $validated['job_name'] = mb_strtoupper(trim((string) $validated['job_name']));
        $validated['progress_status'] = ($validated['progress_status'] ?? null)
            ?: OrderWorkshop::PROGRESS_MENUNGGU_JADWAL;
        $validated['is_completed'] = $validated['progress_status'] === OrderWorkshop::PROGRESS_DONE;

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
        unset($validated['attachment']);

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergeUploadedAttachment(Request $request, array $data, ?BengkelTask $task = null): array
    {
        if (! $request->hasFile('attachment')) {
            return $data;
        }

        $file = $request->file('attachment');

        if ($task?->attachment_path) {
            $this->deleteAttachment($task->attachment_path);
        }

        $data['attachment_path'] = $file->store(self::ATTACHMENT_DIRECTORY, self::ATTACHMENT_DISK);
        $data['attachment_original_name'] = $file->getClientOriginalName();
        $data['attachment_mime_type'] = $file->getClientMimeType();
        $data['attachment_size'] = $file->getSize();

        return $data;
    }

    private function deleteAttachment(?string $path): void
    {
        if ($path) {
            Storage::disk(self::ATTACHMENT_DISK)->delete($path);
        }
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
    private function workshopOrderOptions(?int $currentOrderId = null)
    {
        $unavailableOrderIds = $this->unavailableWorkshopOrderIds($currentOrderId);

        return Order::query()
            ->with('orderWorkshop:id,order_id,progress_status')
            ->whereIn('catatan_status', [
                OrderUserNoteStatus::ApprovedWorkshop->value,
                OrderUserNoteStatus::ApprovedWorkshopJasa->value,
            ])
            ->when($unavailableOrderIds !== [], function ($query) use ($unavailableOrderIds): void {
                $query->whereNotIn('id', $unavailableOrderIds);
            })
            ->where(function ($query) use ($currentOrderId): void {
                $query
                    ->whereDoesntHave('orderWorkshop')
                    ->orWhereHas('orderWorkshop', fn ($builder) => $builder->where(function ($progress): void {
                        $progress
                            ->whereNull('progress_status')
                            ->orWhere('progress_status', '!=', OrderWorkshop::PROGRESS_DONE);
                    }));

                if ($currentOrderId) {
                    $query->orWhere('id', $currentOrderId);
                }
            })
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
                'progress_status' => $order->orderWorkshop?->progress_status ?: OrderWorkshop::PROGRESS_MENUNGGU_JADWAL,
                'label' => trim($order->nomor_order.' - '.$order->nama_pekerjaan),
            ])
            ->values();
    }

    /**
     * @return list<int>
     */
    private function unavailableWorkshopOrderIds(?int $currentOrderId = null): array
    {
        return BengkelTask::query()
            ->whereNotNull('order_id')
            ->get(['order_id', 'is_completed', 'progress_status', 'person_in_charge', 'person_in_charge_profiles'])
            ->filter(function (BengkelTask $task): bool {
                return (bool) $task->is_completed
                    || $task->progress_status === OrderWorkshop::PROGRESS_DONE
                    || $this->taskHasAssignedPic($task);
            })
            ->pluck('order_id')
            ->map(fn ($orderId): int => (int) $orderId)
            ->reject(fn (int $orderId): bool => $currentOrderId !== null && $orderId === (int) $currentOrderId)
            ->unique()
            ->values()
            ->all();
    }

    private function taskHasAssignedPic(BengkelTask $task): bool
    {
        $profiles = collect(is_array($task->person_in_charge_profiles) ? $task->person_in_charge_profiles : [])
            ->filter(fn ($profile): bool => is_array($profile) && (! empty($profile['id']) || trim((string) ($profile['name'] ?? '')) !== ''));

        if ($profiles->isNotEmpty()) {
            return true;
        }

        return collect(is_array($task->person_in_charge) ? $task->person_in_charge : [])
            ->filter(fn ($name): bool => trim((string) $name) !== '')
            ->isNotEmpty();
    }

    private function syncWorkshopProgressFromTask(?BengkelTask $task): void
    {
        if (! $task?->order_id) {
            return;
        }

        $progressStatus = $task->progress_status ?: (
            $task->is_completed ? OrderWorkshop::PROGRESS_DONE : OrderWorkshop::PROGRESS_MENUNGGU_JADWAL
        );

        $workshop = $task->order?->orderWorkshop ?: $task->order?->orderWorkshop()->firstOrNew();

        if (! $workshop) {
            return;
        }

        $workshop->progress_status = $progressStatus;
        $workshop->save();
    }

    private function syncTaskCompletionFromWorkshop(BengkelTask $task): void
    {
        $workshopProgress = $task->order?->orderWorkshop?->progress_status;

        if (! $workshopProgress) {
            return;
        }

        $shouldBeCompleted = $workshopProgress === OrderWorkshop::PROGRESS_DONE;

        if ($task->progress_status === $workshopProgress && (bool) $task->is_completed === $shouldBeCompleted) {
            return;
        }

        $task->forceFill([
            'progress_status' => $workshopProgress,
            'is_completed' => $shouldBeCompleted,
        ])->save();
    }
}
