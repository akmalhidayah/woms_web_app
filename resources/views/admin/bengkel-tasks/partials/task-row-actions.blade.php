<div class="flex {{ ($mobile ?? false) ? 'w-full justify-start' : 'justify-end' }}">
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
