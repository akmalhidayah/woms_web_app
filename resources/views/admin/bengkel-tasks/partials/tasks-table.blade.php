<section class="overflow-hidden rounded-[1.25rem] border border-slate-200 bg-white shadow-sm sm:rounded-[1.5rem]">
    <form id="bulk-delete-bengkel-tasks-form" action="{{ route('admin.bengkel-tasks.bulk-destroy', $indexQuery) }}" method="POST" class="border-b border-slate-200 bg-slate-50 px-3 py-3 sm:px-4">
        @csrf
        @method('DELETE')
        <div class="flex items-center justify-between gap-2">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                <input id="select-all-bengkel-tasks" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                Pilih Semua
            </label>

            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 sm:px-4">
                <i data-lucide="trash-2" class="h-4 w-4"></i>
                Hapus Terpilih
            </button>
        </div>
    </form>

    <div class="hidden overflow-x-auto lg:block">
        <table class="min-w-full text-sm text-slate-700">
            <thead class="bg-slate-100">
                <tr>
                    <th class="w-12 px-4 py-3 text-left font-semibold">Pilih</th>
                    <th class="px-4 py-3 text-left font-semibold">Nama Pekerjaan</th>
                    <th class="px-4 py-3 text-left font-semibold">Nomor Order</th>
                    <th class="px-4 py-3 text-left font-semibold">Regu</th>
                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                    <th class="px-4 py-3 text-left font-semibold">Penanggung Jawab</th>
                    <th class="px-4 py-3 text-left font-semibold">Target</th>
                    <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($tasks as $task)
                    @php
                        $badge = $reguBadge($task->catatan ?? null);
                        $profiles = is_array($task->person_in_charge_profiles) ? $task->person_in_charge_profiles : [];
                        $names = is_array($task->person_in_charge) ? $task->person_in_charge : [];
                        $progressStatus = $task->effectiveProgressStatus();
                        $progressLabel = $task->effectiveProgressLabel();
                        $isCompleted = (bool) $task->is_completed || $progressStatus === \App\Models\OrderWorkshop::PROGRESS_DONE;
                    @endphp
                    <tr class="{{ $isCompleted ? 'bg-emerald-50/70 hover:bg-emerald-50' : 'hover:bg-slate-50/80' }}">
                        <td class="px-4 py-3">
                            <input form="bulk-delete-bengkel-tasks-form" type="checkbox" name="task_ids[]" value="{{ $task->id }}" class="bengkel-task-checkbox h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        </td>

                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-900">{{ $task->job_name }}</div>
                            <div class="mt-1 text-xs leading-snug text-slate-600">
                                <div class="truncate">{{ $task->unit_work ?: '-' }}</div>
                                <div class="truncate text-[11px] text-slate-500">Seksi: {{ $task->seksi ?: '-' }}</div>
                            </div>
                        </td>

                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-900">{{ $task->notification_number ?: '-' }}</div>
                            @if ($task->notification_number)
                                <div class="text-[11px] text-slate-500">Notification</div>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge['class'] }}">
                                {{ $badge['label'] }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            @include('admin.bengkel-tasks.partials.task-status-badge', [
                                'isCompleted' => $isCompleted,
                                'progressStatus' => $progressStatus,
                                'progressLabel' => $progressLabel,
                            ])
                        </td>

                        <td class="px-4 py-3">
                            @include('admin.bengkel-tasks.partials.task-pic-list', [
                                'profiles' => $profiles,
                                'names' => $names,
                                'picInitials' => $picInitials,
                                'avatarObjectPosition' => $avatarObjectPosition,
                            ])
                        </td>

                        <td class="px-4 py-3">
                            {{ optional($task->usage_plan_date)->format('d-m-Y') ?: '-' }}
                        </td>

                        <td class="px-4 py-3 text-right">
                            @include('admin.bengkel-tasks.partials.task-row-actions', [
                                'task' => $task,
                                'indexQuery' => $indexQuery,
                                'isCompleted' => $isCompleted,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada pekerjaan bengkel.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="divide-y divide-slate-100 lg:hidden">
        @forelse ($tasks as $task)
            @php
                $badge = $reguBadge($task->catatan ?? null);
                $profiles = is_array($task->person_in_charge_profiles) ? $task->person_in_charge_profiles : [];
                $names = is_array($task->person_in_charge) ? $task->person_in_charge : [];
                $progressStatus = $task->effectiveProgressStatus();
                $progressLabel = $task->effectiveProgressLabel();
                $isCompleted = (bool) $task->is_completed || $progressStatus === \App\Models\OrderWorkshop::PROGRESS_DONE;
            @endphp

            <article class="{{ $isCompleted ? 'bg-emerald-50/60' : 'bg-white' }} px-4 py-4">
                <div class="flex items-start gap-3">
                    <input form="bulk-delete-bengkel-tasks-form" type="checkbox" name="task_ids[]" value="{{ $task->id }}" class="bengkel-task-checkbox mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-blue-600 focus:ring-blue-500">

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="break-words text-sm font-bold leading-snug text-slate-950">{{ $task->job_name }}</h3>
                                <div class="mt-1 text-[11px] leading-snug text-slate-600">
                                    <div>{{ $task->unit_work ?: '-' }}</div>
                                    <div class="text-slate-500">Seksi: {{ $task->seksi ?: '-' }}</div>
                                </div>
                            </div>

                            @include('admin.bengkel-tasks.partials.task-status-badge', [
                                'isCompleted' => $isCompleted,
                                'progressStatus' => $progressStatus,
                                'progressLabel' => $progressLabel,
                            ])
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-2 text-[11px]">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                <div class="font-semibold uppercase tracking-[0.12em] text-slate-400">Nomor</div>
                                <div class="mt-1 font-bold text-slate-900">{{ $task->notification_number ?: '-' }}</div>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                <div class="font-semibold uppercase tracking-[0.12em] text-slate-400">Target</div>
                                <div class="mt-1 font-bold text-slate-900">{{ optional($task->usage_plan_date)->format('d-m-Y') ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $badge['class'] }}">
                                {{ $badge['label'] }}
                            </span>
                        </div>

                        <div class="mt-3">
                            @include('admin.bengkel-tasks.partials.task-pic-list', [
                                'profiles' => $profiles,
                                'names' => $names,
                                'picInitials' => $picInitials,
                                'avatarObjectPosition' => $avatarObjectPosition,
                            ])
                        </div>

                        <div class="mt-3">
                            @include('admin.bengkel-tasks.partials.task-row-actions', [
                                'task' => $task,
                                'indexQuery' => $indexQuery,
                                'isCompleted' => $isCompleted,
                                'mobile' => true,
                            ])
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="px-4 py-8 text-center text-sm text-slate-500">Belum ada pekerjaan bengkel.</div>
        @endforelse
    </div>

    @if ($tasks->hasPages())
        <div class="border-t border-slate-200 px-4 py-4">
            {{ $tasks->links() }}
        </div>
    @endif
</section>
