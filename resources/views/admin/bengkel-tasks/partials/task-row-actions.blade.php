@php
    $attachmentPayload = $attachmentPayload ?? null;
    $isMobile = (bool) ($mobile ?? false);
    $badge = $badge ?? null;
    $progressOptions = \App\Models\OrderWorkshop::progressOptions();
    $currentProgress = $progressStatus ?? (
        method_exists($task, 'effectiveProgressStatus')
            ? $task->effectiveProgressStatus()
            : ($task->progress_status ?? \App\Models\OrderWorkshop::PROGRESS_MENUNGGU_JADWAL)
    );
    $currentProgressLabel = $progressLabel ?? (
        method_exists($task, 'effectiveProgressLabel')
            ? $task->effectiveProgressLabel()
            : ($progressOptions[$currentProgress] ?? 'Berjalan')
    );
@endphp

<div class="flex flex-col gap-2 {{ $isMobile ? 'w-full items-start' : 'items-end' }}">
    @if ($badge)
        <div class="flex max-w-[170px] flex-wrap items-center gap-1.5 {{ $isMobile ? 'justify-start' : 'justify-end' }}">
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $badge['class'] }}">
                {{ $badge['label'] }}
            </span>
            @include('admin.bengkel-tasks.partials.task-status-badge', [
                'isCompleted' => $isCompleted,
                'progressStatus' => $currentProgress,
                'progressLabel' => $currentProgressLabel,
            ])
        </div>
    @endif

    <div class="flex items-center gap-2">
        @if ($attachmentPayload)
            <button type="button" @click="openAttachment(@js($attachmentPayload))" title="Preview Lampiran" aria-label="Preview lampiran" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-blue-600 transition hover:bg-slate-50">
                <i data-lucide="{{ ($attachmentPayload['is_image'] ?? false) ? 'image' : 'file-text' }}" class="h-3.5 w-3.5"></i>
            </button>
        @endif

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
