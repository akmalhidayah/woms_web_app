@php
    $isMobile = (bool) ($mobile ?? false);
    $buttonWidth = $isMobile ? 'flex-1 justify-center' : '';
@endphp

<div class="flex flex-wrap items-center {{ $isMobile ? 'gap-2' : 'justify-end gap-2' }}">
    @unless ($isCompleted)
        <form action="{{ route('admin.bengkel-tasks.complete', array_merge(['bengkel_task' => $task], $indexQuery)) }}" method="POST" class="{{ $isMobile ? 'flex flex-1' : 'inline-block' }} complete-bengkel-task-form">
            @csrf
            @method('PATCH')
            <button type="submit" class="inline-flex {{ $buttonWidth }} items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                <i data-lucide="check" class="h-3.5 w-3.5"></i>
                Selesai
            </button>
        </form>
    @endunless

    <a href="{{ route('admin.bengkel-tasks.edit', array_merge(['bengkel_task' => $task], $indexQuery)) }}" class="inline-flex {{ $buttonWidth }} items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">
        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
        Edit
    </a>

    <form action="{{ route('admin.bengkel-tasks.destroy', array_merge(['bengkel_task' => $task], $indexQuery)) }}" method="POST" class="{{ $isMobile ? 'flex flex-1' : 'inline-block' }} delete-bengkel-task-form">
        @csrf
        @method('DELETE')
        <button type="submit" class="inline-flex {{ $buttonWidth }} items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
            Hapus
        </button>
    </form>
</div>
