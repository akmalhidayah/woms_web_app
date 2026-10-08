@php($hasActiveDailyReportFilters = collect($filters)->contains(fn (string $value): bool => $value !== ''))

<button
    type="button"
    class="fixed bottom-5 right-4 z-40 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-xl shadow-blue-900/25 ring-1 ring-blue-500 transition active:scale-95 lg:hidden"
    aria-label="Buka filter laporan"
    aria-controls="daily-report-mobile-filter"
    x-bind:aria-expanded="filterMobileOpen.toString()"
    x-on:click="filterMobileOpen = true"
>
    <i data-lucide="sliders-horizontal" class="h-5 w-5" aria-hidden="true"></i>
    @if ($hasActiveDailyReportFilters)
        <span class="absolute right-2 top-2 h-2.5 w-2.5 rounded-full border-2 border-blue-600 bg-amber-400" aria-hidden="true"></span>
    @endif
</button>

<div
    id="daily-report-mobile-filter"
    x-cloak
    x-show="filterMobileOpen"
    class="fixed inset-0 z-[150] lg:hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="daily-report-mobile-filter-title"
>
    <div x-show="filterMobileOpen" x-transition.opacity class="absolute inset-0 bg-slate-950/55 backdrop-blur-[2px]" x-on:click="filterMobileOpen = false"></div>
    <div
        x-show="filterMobileOpen"
        x-transition:enter="transition duration-300 ease-out"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition duration-200 ease-in"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="absolute inset-x-0 bottom-0 max-h-[88vh] overflow-y-auto rounded-t-[1.75rem] bg-white px-4 pb-6 pt-3 shadow-2xl"
    >
        <span class="mx-auto block h-1 w-10 rounded-full bg-slate-200" aria-hidden="true"></span>
        <div class="mt-4 flex items-center justify-between gap-3">
            <div>
                <h2 id="daily-report-mobile-filter-title" class="text-base font-bold text-slate-900">Filter Laporan</h2>
                <p class="mt-0.5 text-[11px] text-slate-500">Pilih filter untuk mempersempit laporan.</p>
            </div>
            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500" aria-label="Tutup filter" x-on:click="filterMobileOpen = false">
                <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
            </button>
        </div>

        <form method="GET" action="{{ route('admin.daily-report.index') }}" class="mt-5 space-y-4">
            <div>
                <label for="daily-report-mobile-search" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Search</label>
                <input id="daily-report-mobile-search" name="search" value="{{ $filters['search'] }}" type="search" placeholder="Cari order, pekerjaan, progress, atau PIC..." autocomplete="off" enterkeyhint="search" data-preserve-mobile-size class="block h-12 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 text-base text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="daily-report-mobile-pic" class="mb-1.5 block text-[11px] font-semibold text-slate-600">PIC</label>
                    <select id="daily-report-mobile-pic" name="pic" class="block h-11 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-xs text-slate-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                        <option value="">Semua PIC</option>
                        @foreach ($picOptions as $pic)
                            <option value="{{ $pic }}" @selected($filters['pic'] === $pic)>{{ $pic }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="daily-report-mobile-year" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Tahun</label>
                    <select id="daily-report-mobile-year" name="year" class="block h-11 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-xs text-slate-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                        <option value="">Semua Tahun</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected($filters['year'] === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label for="daily-report-mobile-date" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Tanggal</label>
                <input id="daily-report-mobile-date" name="date" value="{{ $filters['date'] }}" type="date" class="block h-11 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-sm text-slate-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
            </div>
            <div class="flex items-center gap-2 pt-1">
                <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm">
                    <i data-lucide="search" class="h-4 w-4" aria-hidden="true"></i>
                    Terapkan Filter
                </button>
                <a href="{{ route('admin.daily-report.index') }}" class="inline-flex h-11 items-center justify-center gap-1.5 rounded-xl px-3 text-xs font-semibold text-slate-500">
                    <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                    Reset
                </a>
            </div>
        </form>
    </div>
</div>
