<template x-teleport="body">
    <div
        x-cloak
        x-show="consumableInfoOpen"
        x-transition.opacity
        class="fixed inset-0 z-[160] flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="stock-consumable-info-title"
        x-on:click.self="closeConsumableInfo()"
    >
        <div
            x-show="consumableInfoOpen"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="scale-95 opacity-0"
            x-transition:enter-end="scale-100 opacity-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="scale-100 opacity-100"
            x-transition:leave-end="scale-95 opacity-0"
            class="w-full max-w-lg overflow-hidden rounded-2xl border border-white/15 bg-white shadow-2xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-blue-600">Detail Consumable</p>
                    <h2 id="stock-consumable-info-title" class="mt-1 text-base font-bold leading-snug text-slate-900" x-text="consumableInfo.title"></h2>
                </div>
                <button
                    type="button"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    aria-label="Tutup detail consumable"
                    x-on:click="closeConsumableInfo()"
                >
                    <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
                </button>
            </div>

            <dl class="grid gap-3 p-5 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 sm:col-span-2">
                    <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Consumable</dt>
                    <dd class="mt-1 break-words text-sm font-semibold leading-relaxed text-slate-800" x-text="consumableInfo.name"></dd>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 sm:col-span-2">
                    <dt class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Jenis Consumable</dt>
                    <dd class="mt-1 break-words text-sm font-semibold text-slate-800" x-text="consumableInfo.type"></dd>
                </div>
            </dl>
        </div>
    </div>
</template>
