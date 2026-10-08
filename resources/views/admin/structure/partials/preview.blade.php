@php
    $unitCount = $departments->sum(fn ($department) => $department->units->count());
    $sectionCount = $departments->sum(fn ($department) => $department->units->sum(fn ($unit) => $unit->sections->count()));
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-center gap-2 text-[11px] font-semibold text-slate-600">
        <span class="rounded-full bg-emerald-50 px-3 py-1.5 ring-1 ring-emerald-100">{{ $departments->count() }} Departemen</span>
        <span class="rounded-full bg-sky-50 px-3 py-1.5 ring-1 ring-sky-100">{{ $unitCount }} Unit Kerja</span>
        <span class="rounded-full bg-amber-50 px-3 py-1.5 ring-1 ring-amber-100">{{ $sectionCount }} Seksi</span>
    </div>

    <details open data-structure-root>
        <summary class="relative mx-auto block w-full max-w-sm cursor-pointer list-none rounded-2xl outline-none focus-visible:ring-4 focus-visible:ring-violet-200 [&::-webkit-details-marker]:hidden">
            <div class="rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-700 p-[1px] shadow-lg shadow-violet-200/70">
                <div class="rounded-[calc(1rem-1px)] bg-white px-5 py-4 pr-12">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-violet-100 text-sm font-black text-violet-700 ring-4 ring-violet-50">
                            {{ $dirops?->initials() ?? '—' }}
                        </span>
                        <div class="min-w-0">
                            <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-violet-600">Direktur Operasi</div>
                            <div class="mt-1 truncate text-base font-extrabold text-slate-900">{{ $dirops?->name ?? 'DIROPS belum dikonfigurasi' }}</div>
                            <div class="mt-0.5 text-xs text-slate-500">Pimpinan struktur pekerjaan</div>
                        </div>
                    </div>
                </div>
            </div>
            <span class="structure-node-chevron absolute -bottom-3 left-1/2 inline-flex h-7 w-7 -translate-x-1/2 items-center justify-center rounded-full bg-violet-600 text-white shadow-md ring-4 ring-white" title="Buka atau tutup seluruh struktur">
                <i data-lucide="chevron-down" class="h-4 w-4"></i>
            </span>
        </summary>

        @if ($departments->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                    <i data-lucide="network" class="h-5 w-5"></i>
                </span>
                <div class="mt-3 text-sm font-bold text-slate-800">Struktur organisasi belum tersedia</div>
                <div class="mt-1 text-xs text-slate-500">Tambahkan departemen dan unit melalui halaman pengelolaan struktur.</div>
            </div>
        @else
            <div class="mx-auto h-8 w-px bg-gradient-to-b from-violet-400 to-emerald-300"></div>

            <div class="structure-chart-scrollbar overscroll-x-contain overflow-x-scroll pb-5" data-structure-chart-scroller>
                <div class="relative flex w-max min-w-full items-start justify-center gap-7 px-4 pt-8">
                    @if ($departments->count() > 1)
                        <div class="absolute left-[12.5rem] right-[12.5rem] top-0 h-px bg-emerald-300"></div>
                    @endif

                    @foreach ($departments as $department)
                        <details open data-structure-department class="group relative w-[25rem] shrink-0 before:absolute before:-top-8 before:left-1/2 before:h-8 before:w-px before:-translate-x-1/2 before:bg-emerald-300">
                            <summary class="relative cursor-pointer list-none rounded-2xl outline-none transition focus-visible:ring-4 focus-visible:ring-emerald-200 [&::-webkit-details-marker]:hidden">
                                <div class="rounded-2xl border border-emerald-300 bg-gradient-to-br from-emerald-50 to-white p-4 pr-12 shadow-sm transition group-open:shadow-md group-open:shadow-emerald-100/70">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-xs font-black text-white ring-4 ring-white">
                                            {{ $department->generalManager?->initials() ?? '—' }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-[9px] font-bold uppercase tracking-[0.18em] text-emerald-700">Departemen</div>
                                            <div class="mt-0.5 text-base font-black leading-tight text-slate-900">{{ $department->name }}</div>
                                            <div class="mt-1.5 truncate text-xs font-bold text-emerald-800">General Manager · {{ $department->generalManager?->name ?? 'Belum ditentukan' }}</div>
                                        </div>
                                    </div>
                                </div>
                                <span class="structure-node-chevron absolute -bottom-3 left-1/2 inline-flex h-7 w-7 -translate-x-1/2 items-center justify-center rounded-full bg-emerald-600 text-white shadow-md ring-4 ring-white" title="Buka atau tutup unit kerja">
                                    <i data-lucide="chevron-down" class="h-4 w-4"></i>
                                </span>
                            </summary>

                        <div class="mx-auto h-8 w-px bg-sky-300"></div>

                        <div class="space-y-3 border-l border-sky-200 pl-4">
                            @forelse ($department->units as $unit)
                                <details open data-structure-unit class="relative before:absolute before:-left-4 before:top-7 before:h-px before:w-4 before:bg-sky-200">
                                    <summary class="relative cursor-pointer list-none rounded-2xl outline-none focus-visible:ring-4 focus-visible:ring-sky-200 [&::-webkit-details-marker]:hidden">
                                        <div class="rounded-2xl border border-sky-200 bg-white p-3.5 pr-12 shadow-sm">
                                            <div class="flex items-center gap-3">
                                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-sky-100 text-[11px] font-black text-sky-700 ring-2 ring-sky-50">
                                                    {{ $unit->seniorManager?->initials() ?? '—' }}
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="text-[9px] font-bold uppercase tracking-[0.16em] text-sky-600">Unit Kerja</div>
                                                    <div class="text-[15px] font-extrabold leading-tight text-slate-900">{{ $unit->name }}</div>
                                                    <div class="mt-1 truncate text-xs font-semibold text-sky-800">Senior Manager · {{ $unit->seniorManager?->name ?? 'Belum ditentukan' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="structure-node-chevron absolute -bottom-3 left-1/2 inline-flex h-7 w-7 -translate-x-1/2 items-center justify-center rounded-full bg-sky-500 text-white shadow-sm ring-4 ring-white" title="Buka atau tutup seksi">
                                            <i data-lucide="chevron-down" class="h-4 w-4"></i>
                                        </span>
                                    </summary>

                                    @if ($unit->sections->isNotEmpty())
                                        <div class="ml-5 mt-7 space-y-2 border-l border-amber-200 pl-4">
                                            @foreach ($unit->sections as $section)
                                                <div class="relative flex items-center gap-2.5 rounded-xl bg-amber-50/80 px-3 py-2.5 ring-1 ring-amber-100 before:absolute before:-left-4 before:top-1/2 before:h-px before:w-4 before:bg-amber-200">
                                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-[9px] font-black text-amber-700">
                                                        {{ $section->manager?->initials() ?? '—' }}
                                                    </span>
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-extrabold leading-tight text-slate-900">{{ $section->name }}</div>
                                                        <div class="mt-1 truncate text-[11px] font-semibold text-amber-700">Manager · {{ $section->manager?->name ?? 'Belum ditentukan' }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="mt-7 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-center text-[10px] italic text-slate-400">Belum memiliki seksi</div>
                                    @endif
                                </details>
                            @empty
                                <div class="rounded-xl border border-dashed border-emerald-200 bg-white/70 px-3 py-5 text-center text-[11px] italic text-slate-500">Belum memiliki unit kerja</div>
                            @endforelse
                        </div>
                        </details>
                    @endforeach
                </div>
            </div>
        @endif
    </details>

    <div class="flex flex-wrap items-center justify-center gap-4 border-t border-slate-100 pt-4 text-[10px] font-semibold text-slate-500">
        <span class="inline-flex items-center gap-1.5"><i data-lucide="circle" class="h-2.5 w-2.5 fill-violet-500 text-violet-500"></i> DIROPS</span>
        <span class="inline-flex items-center gap-1.5"><i data-lucide="circle" class="h-2.5 w-2.5 fill-emerald-500 text-emerald-500"></i> General Manager</span>
        <span class="inline-flex items-center gap-1.5"><i data-lucide="circle" class="h-2.5 w-2.5 fill-sky-500 text-sky-500"></i> Senior Manager</span>
        <span class="inline-flex items-center gap-1.5"><i data-lucide="circle" class="h-2.5 w-2.5 fill-amber-400 text-amber-400"></i> Manager Seksi</span>
    </div>
</div>
