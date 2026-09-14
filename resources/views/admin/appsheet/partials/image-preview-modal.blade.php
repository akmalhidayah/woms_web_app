<div
    x-cloak
    x-show="previewImageUrl"
    x-transition.opacity
    class="fixed inset-0 z-[160] flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="appsheet-image-preview-title"
    x-on:click.self="closeImagePreview()"
>
    <div
        x-show="previewImageUrl"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="scale-95 opacity-0"
        x-transition:enter-end="scale-100 opacity-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="scale-100 opacity-100"
        x-transition:leave-end="scale-95 opacity-0"
        class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-white/15 bg-white shadow-2xl"
    >
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-4 py-3 sm:px-5">
            <div class="min-w-0">
                <p id="appsheet-image-preview-title" class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Preview Foto</p>
                <p class="mt-1 truncate text-sm font-semibold text-slate-900" x-text="previewImageAlt"></p>
            </div>
            <button
                type="button"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                aria-label="Tutup preview foto"
                x-on:click="closeImagePreview()"
            >
                <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
            </button>
        </div>
        <div class="flex min-h-0 flex-1 items-center justify-center bg-slate-100 p-3 sm:p-5">
            <img
                x-bind:src="previewImageUrl"
                x-bind:alt="previewImageAlt"
                class="max-h-[calc(90vh-5.5rem)] max-w-full rounded-xl bg-white object-contain shadow-sm"
            >
        </div>
    </div>
</div>
