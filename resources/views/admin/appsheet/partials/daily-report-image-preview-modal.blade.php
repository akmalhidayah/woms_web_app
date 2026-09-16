<div
    x-cloak
    x-show="previewImageUrl"
    x-transition.opacity
    class="fixed inset-0 z-[160] flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="daily-report-image-preview-title"
    x-on:click.self="closePhoto()"
>
    <div
        x-show="previewImageUrl"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="scale-95 opacity-0"
        x-transition:enter-end="scale-100 opacity-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="scale-100 opacity-100"
        x-transition:leave-end="scale-95 opacity-0"
        class="flex h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl border border-white/15 bg-white shadow-2xl"
    >
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-4 py-3 sm:px-5">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-blue-600">Preview Foto Pekerjaan</p>
                <h2 id="daily-report-image-preview-title" class="mt-1 line-clamp-2 text-sm font-bold leading-relaxed text-slate-900" x-text="previewTitle"></h2>
                <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-[10px] text-slate-500">
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="calendar-clock" class="h-3.5 w-3.5 text-slate-400" aria-hidden="true"></i>
                        <span x-text="previewDate"></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="users" class="h-3.5 w-3.5 text-slate-400" aria-hidden="true"></i>
                        PIC: <span x-text="previewPic"></span>
                    </span>
                </div>
            </div>
            <button
                type="button"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                aria-label="Tutup preview foto"
                x-on:click="closePhoto()"
            >
                <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
            </button>
        </div>
        <div class="flex min-h-0 flex-1 items-center justify-center bg-slate-100 p-2 sm:p-3">
            <img
                x-bind:src="previewImageUrl || null"
                x-bind:alt="previewTitle"
                class="h-full w-full rounded-xl bg-white object-contain shadow-sm"
            >
        </div>
    </div>
</div>
