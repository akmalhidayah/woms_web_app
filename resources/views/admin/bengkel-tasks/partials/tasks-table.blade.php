<section
    x-data="{
        attachmentModal: false,
        attachment: { url: '', name: '', is_image: false, mime_type: '' },
        openAttachment(payload) {
            this.attachment = payload || { url: '', name: '', is_image: false, mime_type: '' };
            this.attachmentModal = true;
            this.$nextTick(() => window.lucide?.createIcons());
        },
        closeAttachment() {
            this.attachmentModal = false;
            this.attachment = { url: '', name: '', is_image: false, mime_type: '' };
        },
    }"
    @keydown.escape.window="closeAttachment()"
    class="bengkel-table-panel overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
>
    <form id="bulk-archive-bengkel-tasks-form" action="{{ route('admin.bengkel-tasks.bulk-archive', $indexQuery) }}" method="POST" class="border-b border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4">
        @csrf
        @method('PATCH')
        <div class="flex items-center justify-between gap-2">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">
                <input id="select-all-bengkel-tasks" type="checkbox" class="h-3.5 w-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                Pilih Semua di Halaman Ini
            </label>

            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-blue-700 transition hover:bg-slate-50 sm:px-4">
                <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                Arsipkan Terpilih
            </button>
        </div>
    </form>

    <div class="hidden overflow-x-auto lg:block">
        <table class="min-w-full text-xs text-slate-700">
            <thead class="bg-slate-100">
                <tr>
                    <th class="w-10 px-3 py-2.5 text-left font-semibold">Pilih</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Pekerjaan</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Nomor Order</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Penanggung Jawab</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Progress</th>
                    <th class="px-3 py-2.5 text-left font-semibold">Target &amp; Kelengkapan</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Regu &amp; Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($tasks as $task)
                    @php
                        $badge = $reguBadge($task->catatan ?? null);
                        $jobName = mb_strtoupper((string) $task->job_name);
                        $profiles = is_array($task->person_in_charge_profiles) ? $task->person_in_charge_profiles : [];
                        $names = is_array($task->person_in_charge) ? $task->person_in_charge : [];
                        $progressStatus = $task->effectiveProgressStatus();
                        $progressLabel = $task->effectiveProgressLabel();
                        $isCompleted = (bool) $task->is_completed || $progressStatus === \App\Models\OrderWorkshop::PROGRESS_DONE;
                        $readiness = $task->getAttribute('workshop_readiness');
                        $attachmentPayload = $task->attachment_url ? [
                            'url' => $task->attachment_url,
                            'name' => $task->attachment_display_name,
                            'is_image' => $task->attachment_is_image,
                            'mime_type' => $task->attachment_mime_type,
                        ] : null;
                    @endphp
                    <tr class="{{ $isCompleted ? 'bg-emerald-50/70 hover:bg-emerald-50' : 'hover:bg-slate-50/80' }}">
                        <td class="px-3 py-2.5">
                            <input form="bulk-archive-bengkel-tasks-form" type="checkbox" name="task_ids[]" value="{{ $task->id }}" class="bengkel-task-checkbox h-3.5 w-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        </td>

                        <td class="px-3 py-2.5">
                            <div class="font-semibold text-slate-900">{{ $jobName }}</div>
                            <div class="mt-1 text-[11px] leading-snug text-slate-600">
                                <div class="truncate">{{ $task->unit_work ?: '-' }}</div>
                                <div class="truncate text-[10px] text-slate-500">Seksi: {{ $task->seksi ?: '-' }}</div>
                            </div>
                            @if ($task->order?->isWorkshopOrder() && $task->order->workPackages->isNotEmpty())
                                <div class="mt-2 rounded-lg border border-blue-100 bg-blue-50/60 px-2 py-1.5 text-left">
                                    <div class="text-[9px] font-bold uppercase tracking-wide text-blue-700">Pembagian Pekerjaan ({{ $task->order->workPackages->count() }} paket)</div>
                                    <div class="mt-1 space-y-0.5 text-[10px] text-slate-700">
                                        @foreach ($task->order->workPackages as $package)
                                            <div class="rounded border border-blue-100 bg-white px-2 py-1.5">
                                                <div class="flex flex-wrap items-center justify-between gap-2"><span><span class="font-semibold text-blue-700">{{ $package->displayNumber() }}</span> — {{ $package->job_name }}</span>
                                                    <form method="POST" action="{{ route('admin.orders.work-packages.status.update', $package) }}" class="inline-flex items-center gap-1">@csrf @method('PATCH')<select name="status" class="rounded border border-slate-200 px-1 py-0.5 text-[10px]" @disabled($package->isLocked())>@foreach(\App\Models\WorkshopWorkPackage::statusOptions() as $value => $label)<option value="{{ $value }}" @selected($package->status === $value)>{{ $label }}</option>@endforeach</select><input name="pending_reason" value="{{ $package->pending_reason }}" placeholder="Alasan pending" class="w-28 rounded border border-slate-200 px-1 py-0.5 text-[10px]" @disabled($package->isLocked())>@if(! $package->isLocked())<button class="rounded bg-blue-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">Simpan</button>@endif</form>
                                                </div>
                                                <div class="mt-1 text-[10px] text-slate-500">PIC: {{ $package->assignments->pluck('pic_name_snapshot')->join(', ') ?: 'Belum ada PIC' }} @if($package->isPending() && filled($package->pending_reason)) · {{ $package->pending_reason }} @endif</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </td>

                        <td class="px-3 py-2.5">
                            <div class="font-semibold text-slate-900">{{ $task->order?->nomor_order ?: '-' }}</div>
                            @if ($task->order?->nomor_order)
                                <div class="text-[10px] text-slate-500">Order</div>
                            @endif
                        </td>

                        <td class="px-3 py-2.5 align-top">
                            @if ($profiles === [] && $names === [])
                                <span class="text-[11px] text-slate-400">Belum ada PIC</span>
                            @else
                                @include('admin.bengkel-tasks.partials.task-pic-list', [
                                    'profiles' => $profiles,
                                    'names' => $names,
                                    'picInitials' => $picInitials,
                                    'avatarObjectPosition' => $avatarObjectPosition,
                                ])
                            @endif
                        </td>

                        <td class="px-3 py-2.5 align-top">
                            <div class="flex flex-col items-start gap-1.5">
                                @include('admin.bengkel-tasks.partials.task-status-badge', [
                                    'isCompleted' => $isCompleted,
                                    'progressStatus' => $progressStatus,
                                    'progressLabel' => $progressLabel,
                                ])
                                @if ($progressStatus === \App\Models\OrderWorkshop::PROGRESS_PENDING && filled($task->pending_reason))
                                    <div class="max-w-[220px] rounded-lg border border-orange-100 bg-orange-50 px-2.5 py-1.5 text-left text-[10px] leading-snug text-orange-800">
                                        <span class="font-bold">Alasan:</span> {{ \Illuminate\Support\Str::limit($task->pending_reason, 120) }}
                                    </div>
                                @endif
                            </div>
                        </td>

                        <td class="px-3 py-2.5 align-top">
                            <div class="flex max-w-[230px] flex-col items-start gap-1.5">
                                <span class="font-semibold text-slate-800">{{ optional($task->usage_plan_date)->format('d-m-Y') ?: '-' }}</span>
                                @if (is_array($readiness))
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-semibold ring-1 ring-inset {{ $readiness['can_advance'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">{{ $readiness['label'] }}</span>
                                    @if (! $readiness['can_advance'] && auth()->user() && \App\Support\AdminMenuRegistry::canAccess(auth()->user(), \App\Support\AdminMenuRegistry::MENU_ORDER_BENGKEL) && $task->order)
                                        <a href="{{ route('admin.orders.workshop.index', ['search' => $task->order->nomor_order, 'readiness' => 'incomplete']) }}" class="text-[9px] font-semibold leading-4 text-blue-700 underline decoration-blue-300 underline-offset-2">Lengkapi di Order Pekerjaan Bengkel</a>
                                    @elseif (! $readiness['can_advance'])
                                        <span class="text-[9px] leading-4 text-slate-500">Harus dilengkapi admin Order Pekerjaan Bengkel.</span>
                                    @endif
                                @endif
                            </div>
                        </td>

                        <td class="px-3 py-2.5 text-right">
                            @include('admin.bengkel-tasks.partials.task-row-actions', [
                                'task' => $task,
                                'indexQuery' => $indexQuery,
                                'isCompleted' => $isCompleted,
                                'badge' => $badge,
                                'progressStatus' => $progressStatus,
                                'progressLabel' => $progressLabel,
                                'readiness' => $readiness,
                                'attachmentPayload' => $attachmentPayload,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-xs text-slate-500">
                            <i data-lucide="monitor-x" class="mx-auto mb-2 h-6 w-6 text-slate-300"></i>
                            Belum ada pekerjaan bengkel untuk ditampilkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="divide-y divide-slate-100 lg:hidden">
        @forelse ($tasks as $task)
            @php
                $badge = $reguBadge($task->catatan ?? null);
                $jobName = mb_strtoupper((string) $task->job_name);
                $profiles = is_array($task->person_in_charge_profiles) ? $task->person_in_charge_profiles : [];
                $names = is_array($task->person_in_charge) ? $task->person_in_charge : [];
                $progressStatus = $task->effectiveProgressStatus();
                $progressLabel = $task->effectiveProgressLabel();
                $isCompleted = (bool) $task->is_completed || $progressStatus === \App\Models\OrderWorkshop::PROGRESS_DONE;
                $readiness = $task->getAttribute('workshop_readiness');
                $attachmentPayload = $task->attachment_url ? [
                    'url' => $task->attachment_url,
                    'name' => $task->attachment_display_name,
                    'is_image' => $task->attachment_is_image,
                    'mime_type' => $task->attachment_mime_type,
                ] : null;
            @endphp

            <article class="{{ $isCompleted ? 'bg-emerald-50/60' : 'bg-white' }} px-4 py-3">
                <div class="flex items-start gap-3">
                    <input form="bulk-archive-bengkel-tasks-form" type="checkbox" name="task_ids[]" value="{{ $task->id }}" class="bengkel-task-checkbox mt-1 h-3.5 w-3.5 shrink-0 rounded border-slate-300 text-blue-600 focus:ring-blue-500">

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="break-words text-[13px] font-bold leading-snug text-slate-950">{{ $jobName }}</h3>
                                <div class="mt-1 text-[10px] leading-snug text-slate-600">
                                    <div>{{ $task->unit_work ?: '-' }}</div>
                                    <div class="text-slate-500">Seksi: {{ $task->seksi ?: '-' }}</div>
                                </div>
                                @if ($task->order?->isWorkshopOrder() && $task->order->workPackages->isNotEmpty())
                                    <div class="mt-2 rounded-lg border border-blue-100 bg-blue-50/60 px-2.5 py-2 text-[10px] text-slate-700">
                                        <div class="font-bold uppercase tracking-wide text-blue-700">Pembagian Pekerjaan</div>
                                        @foreach ($task->order->workPackages as $package)
                                            <div class="mt-1 rounded border border-blue-100 bg-white px-2 py-1.5"><div class="flex items-center justify-between gap-2"><span><span class="font-semibold text-blue-700">{{ $package->displayNumber() }}</span> — {{ $package->job_name }}</span><form method="POST" action="{{ route('admin.orders.work-packages.status.update', $package) }}" class="inline-flex gap-1">@csrf @method('PATCH')<select name="status" class="max-w-[90px] rounded border border-slate-200 px-1 py-0.5 text-[9px]" @disabled($package->isLocked())>@foreach(\App\Models\WorkshopWorkPackage::statusOptions() as $value => $label)<option value="{{ $value }}" @selected($package->status === $value)>{{ $label }}</option>@endforeach</select><input name="pending_reason" value="{{ $package->pending_reason }}" placeholder="Alasan" class="w-16 rounded border border-slate-200 px-1 py-0.5 text-[9px]" @disabled($package->isLocked())>@if(! $package->isLocked())<button class="rounded bg-blue-600 px-1 py-0.5 text-[9px] text-white">OK</button>@endif</form></div><div class="text-[9px] text-slate-500">PIC: {{ $package->assignments->pluck('pic_name_snapshot')->join(', ') ?: 'Belum ada PIC' }}</div></div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-2 text-[10px] sm:grid-cols-2">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <div class="font-semibold uppercase tracking-[0.12em] text-slate-400">Nomor</div>
                                <div class="mt-1 font-bold text-slate-900">{{ $task->order?->nomor_order ?: '-' }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <div class="font-semibold uppercase tracking-[0.12em] text-slate-400">Progress</div>
                                <div class="mt-1 flex flex-col items-start gap-1.5">
                                    @include('admin.bengkel-tasks.partials.task-status-badge', [
                                        'isCompleted' => $isCompleted,
                                        'progressStatus' => $progressStatus,
                                        'progressLabel' => $progressLabel,
                                    ])
                                    @if ($progressStatus === \App\Models\OrderWorkshop::PROGRESS_PENDING && filled($task->pending_reason))
                                        <div class="mt-1 rounded-lg border border-orange-100 bg-orange-50 px-2 py-1 text-[10px] leading-snug text-orange-800">
                                            <span class="font-bold">Alasan:</span> {{ \Illuminate\Support\Str::limit($task->pending_reason, 100) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px]">
                            <div class="font-semibold uppercase tracking-[0.12em] text-slate-400">Target &amp; Kelengkapan</div>
                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                <span class="font-bold text-slate-900">{{ optional($task->usage_plan_date)->format('d-m-Y') ?: '-' }}</span>
                                @if (is_array($readiness))
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-semibold ring-1 ring-inset {{ $readiness['can_advance'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">{{ $readiness['label'] }}</span>
                                @endif
                            </div>
                            @if (is_array($readiness) && ! $readiness['can_advance'])
                                @if (auth()->user() && \App\Support\AdminMenuRegistry::canAccess(auth()->user(), \App\Support\AdminMenuRegistry::MENU_ORDER_BENGKEL) && $task->order)
                                    <a href="{{ route('admin.orders.workshop.index', ['search' => $task->order->nomor_order, 'readiness' => 'incomplete']) }}" class="mt-1.5 inline-flex text-[10px] font-semibold text-blue-700 underline decoration-blue-300 underline-offset-2">Lengkapi di Order Pekerjaan Bengkel</a>
                                @else
                                    <p class="mt-1.5 text-[10px] leading-4 text-slate-500">Harus dilengkapi admin Order Pekerjaan Bengkel.</p>
                                @endif
                            @endif
                        </div>

                        <div class="mt-3">
                            <div class="mb-1 text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-400">Penanggung Jawab</div>
                            @if ($profiles === [] && $names === [])
                                <span class="text-[11px] text-slate-400">Belum ada PIC</span>
                            @else
                                @include('admin.bengkel-tasks.partials.task-pic-list', [
                                    'profiles' => $profiles,
                                    'names' => $names,
                                    'picInitials' => $picInitials,
                                    'avatarObjectPosition' => $avatarObjectPosition,
                                ])
                            @endif
                        </div>

                        <div class="mt-3">
                            @include('admin.bengkel-tasks.partials.task-row-actions', [
                                'task' => $task,
                                'indexQuery' => $indexQuery,
                                'isCompleted' => $isCompleted,
                                'badge' => $badge,
                                'progressStatus' => $progressStatus,
                                'progressLabel' => $progressLabel,
                                'readiness' => $readiness,
                                'attachmentPayload' => $attachmentPayload,
                                'mobile' => true,
                            ])
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="px-4 py-7 text-center text-xs text-slate-500">Belum ada pekerjaan bengkel.</div>
        @endforelse
    </div>

    @if ($tasks->hasPages())
        <div class="border-t border-slate-200 px-4 py-4">
            {{ $tasks->links() }}
        </div>
    @endif

    <div x-cloak x-show="attachmentModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6" @click.self="closeAttachment()">
        <div x-show="attachmentModal" x-transition class="flex max-h-[88vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                <div class="min-w-0">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-blue-600">Preview Lampiran</div>
                    <h2 class="mt-1 truncate text-base font-bold text-slate-900" x-text="attachment.name || 'Lampiran pekerjaan'"></h2>
                </div>
                <button type="button" @click="closeAttachment()" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-auto bg-slate-100 p-4">
                <template x-if="attachment.is_image">
                    <img :src="attachment.url" :alt="attachment.name || 'Lampiran pekerjaan'" class="mx-auto max-w-full rounded-xl bg-white object-contain shadow-sm" style="max-height: 72vh;">
                </template>
                <template x-if="! attachment.is_image">
                    <iframe :src="attachment.url" class="w-full rounded-xl border border-slate-200 bg-white shadow-sm" style="height: 72vh;"></iframe>
                </template>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" @click="closeAttachment()" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                    Tutup
                </button>
                <a :href="attachment.url" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">
                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                    Buka Tab
                </a>
            </div>
        </div>
    </div>
</section>
