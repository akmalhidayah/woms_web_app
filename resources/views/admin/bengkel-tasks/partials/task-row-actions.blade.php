@php
    $isMobile = (bool) ($mobile ?? false);
    $progressOptions = \App\Models\OrderWorkshop::progressOptions();
    $currentProgress = method_exists($task, 'effectiveProgressStatus')
        ? $task->effectiveProgressStatus()
        : ($task->progress_status ?? \App\Models\OrderWorkshop::PROGRESS_MENUNGGU_JADWAL);
    $progressLabel = method_exists($task, 'effectiveProgressLabel')
        ? $task->effectiveProgressLabel()
        : ($progressOptions[$currentProgress] ?? 'Berjalan');
@endphp

<div class="flex flex-col gap-2 {{ $isMobile ? 'w-full' : 'items-end' }}">
    <div class="{{ $isMobile ? 'w-full' : 'w-[150px]' }} flex {{ $isMobile ? 'justify-start' : 'justify-end' }}">
        @include('admin.bengkel-tasks.partials.task-status-badge', [
            'isCompleted' => $isCompleted,
            'progressStatus' => $currentProgress,
            'progressLabel' => $progressLabel,
        ])
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('admin.bengkel-tasks.edit', array_merge(['bengkel_task' => $task], $indexQuery)) }}" title="Edit" aria-label="Edit pekerjaan" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50">
            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
        </a>

        <form action="{{ route('admin.bengkel-tasks.destroy', array_merge(['bengkel_task' => $task], $indexQuery)) }}" method="POST" class="delete-bengkel-task-form">
            @csrf
            @method('DELETE')
            <button type="submit" title="Hapus" aria-label="Hapus pekerjaan" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-rose-600 transition hover:bg-slate-50">
                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
            </button>
        </form>
    </div>
</div>
