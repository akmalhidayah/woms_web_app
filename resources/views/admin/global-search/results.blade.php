@php
    $listId = $listId ?? 'admin-global-search-results';
@endphp

<div
    id="{{ $listId }}"
    role="listbox"
    class="max-h-[min(70vh,28rem)] overflow-y-auto overscroll-contain"
    :aria-busy="searchLoading"
>
    <div x-show="searchLoading" x-cloak class="flex items-center justify-center gap-2 px-4 py-6 text-xs font-semibold text-slate-500" role="status">
        <i data-lucide="loader-circle" class="h-4 w-4 animate-spin text-blue-600"></i>
        Mencari pekerjaan…
    </div>

    <div x-show="!searchLoading && searchError" x-cloak class="px-4 py-6 text-center">
        <span class="mx-auto inline-flex h-9 w-9 items-center justify-center rounded-full bg-rose-50 text-rose-600">
            <i data-lucide="triangle-alert" class="h-4 w-4"></i>
        </span>
        <p class="mt-2 text-xs font-semibold text-slate-600" x-text="searchError"></p>
    </div>

    <div x-show="!searchLoading && !searchError && searchPerformed && results.length === 0" x-cloak class="px-4 py-7 text-center">
        <span class="mx-auto inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500">
            <i data-lucide="search-x" class="h-4 w-4"></i>
        </span>
        <p class="mt-2 text-xs font-bold text-slate-700">Pekerjaan tidak ditemukan</p>
        <p class="mt-1 text-[11px] text-slate-500">Cari nomor order, nomor notifikasi SAP, atau nama pekerjaan.</p>
    </div>

    <template x-for="(result, index) in results" :key="result.id">
        <button
            type="button"
            role="option"
            :id="'{{ $listId }}-' + index"
            :aria-selected="selectedIndex === index"
            @mouseenter="selectedIndex = index"
            @click="openResult(result)"
            class="block w-full border-b border-slate-100 px-4 py-3 text-left transition last:border-b-0"
            :class="selectedIndex === index ? 'bg-blue-50' : 'bg-white hover:bg-slate-50'"
        >
            <span class="flex items-start justify-between gap-3">
                <span class="min-w-0">
                    <span class="block truncate text-xs font-black text-slate-900" x-text="result.order_number"></span>
                    <span class="mt-0.5 block text-xs font-semibold leading-4 text-slate-800" x-text="result.job_name"></span>
                </span>
                <span
                    class="shrink-0 rounded-full px-2 py-1 text-[9px] font-black uppercase tracking-wide"
                    :class="typeBadgeClasses(result.type)"
                    x-text="result.type"
                ></span>
            </span>

            <span class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] text-slate-500">
                <span x-show="result.notification_number">
                    Notifikasi: <span class="font-bold text-slate-700" x-text="result.notification_number"></span>
                </span>
                <span class="inline-flex items-center gap-1 font-bold" :class="toneTextClasses(result.tone)">
                    <span class="h-1.5 w-1.5 rounded-full" :class="toneDotClasses(result.tone)"></span>
                    <span x-text="result.status"></span>
                </span>
            </span>

            <span x-show="result.paths?.length > 1" class="mt-2 flex flex-wrap gap-1.5">
                <template x-for="path in result.paths" :key="path.label">
                    <span class="rounded-md bg-slate-100 px-2 py-1 text-[9px] font-semibold text-slate-600">
                        <span class="font-black" x-text="path.label + ':'"></span>
                        <span x-text="path.status"></span>
                    </span>
                </template>
            </span>

            <span class="mt-2 inline-flex items-center gap-1 text-[10px] font-black text-blue-700">
                <span x-text="result.action_label"></span>
                <i data-lucide="arrow-up-right" class="h-3 w-3"></i>
            </span>
        </button>
    </template>
</div>
