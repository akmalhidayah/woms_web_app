<x-layouts.inspector title="Inspeksi Saya — WOMS">
    @php
        $activeFilterCount = collect(['equipment', 'date_from', 'date_to', 'status'])
            ->filter(fn (string $key): bool => filled($filters[$key] ?? null))
            ->count();
    @endphp
    <div x-data="{ filterOpen: false }" @keydown.escape.window="filterOpen = false" class="space-y-4 pb-16">
        <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div><h1 class="text-xl font-black text-slate-900">Inspeksi Saya</h1><p class="mt-1 text-sm text-slate-500">Riwayat pemeriksaan dan draft Anda.</p></div>
            <a href="{{ route('inspector.equipment-forms.index') }}" class="rounded-xl bg-[#7f1017] px-4 py-2.5 text-center text-sm font-bold text-white">Buat Inspeksi Baru</a>
        </section>
        <x-inspections.sweet-alerts :success="session('success')" :error-messages="$errors->all()" />

        <button type="button" @click="filterOpen = true; $nextTick(() => $refs.firstFilter.focus())" aria-controls="inspection-filter-dialog" :aria-expanded="filterOpen" title="Buka filter inspeksi" class="fixed bottom-5 right-4 z-40 inline-flex h-12 w-12 items-center justify-center rounded-full bg-[#7f1017] text-white shadow-xl ring-4 ring-white transition hover:bg-red-900 focus-visible:outline-none focus-visible:ring-red-200 sm:bottom-6 sm:right-6">
            <i data-lucide="sliders-horizontal" class="h-5 w-5" aria-hidden="true"></i>
            <span class="sr-only">Buka filter inspeksi</span>
            @if ($activeFilterCount > 0)
                <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-400 px-1 text-[10px] font-black text-slate-900 ring-2 ring-white">{{ $activeFilterCount }}</span>
            @endif
        </button>

        <div id="inspection-filter-dialog" x-show="filterOpen" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="inspection-filter-title" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/55 p-3 sm:items-center sm:p-6" @click.self="filterOpen = false">
            <section class="w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 id="inspection-filter-title" class="text-lg font-black text-slate-900">Filter Inspeksi</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Saring riwayat berdasarkan peralatan, tanggal, atau status.</p>
                    </div>
                    <button type="button" @click="filterOpen = false" aria-label="Tutup filter" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50">
                        <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
                    </button>
                </div>
                <form method="GET" action="{{ route('inspector.inspections.index') }}" class="grid gap-4 p-5 sm:grid-cols-2">
                    <label class="text-xs font-semibold text-slate-600">Peralatan<input x-ref="firstFilter" type="search" name="equipment" value="{{ $filters['equipment'] ?? '' }}" placeholder="Nama peralatan..." class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-base sm:text-sm"></label>
                    <label class="text-xs font-semibold text-slate-600">Status<select name="status" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-base sm:text-sm"><option value="">Semua status</option>@foreach (\App\Models\EquipmentInspection::STATUS_LABELS as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label>
                    <label class="text-xs font-semibold text-slate-600">Tanggal dari<input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 block w-full min-w-0 rounded-xl border border-slate-300 px-3 py-2.5 text-base sm:text-sm"></label>
                    <label class="text-xs font-semibold text-slate-600">Tanggal sampai<input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 block w-full min-w-0 rounded-xl border border-slate-300 px-3 py-2.5 text-base sm:text-sm"></label>
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:col-span-2 sm:flex-row sm:justify-end">
                        <a href="{{ route('inspector.inspections.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Reset Filter</a>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#7f1017] px-4 py-2.5 text-sm font-bold text-white">
                            <i data-lucide="search" class="h-4 w-4" aria-hidden="true"></i>
                            Terapkan Filter
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <section class="space-y-3" aria-label="Riwayat inspeksi">
            @forelse ($inspections as $inspection)
                <article role="link" tabindex="0" data-href="{{ route('inspector.inspections.show', $inspection) }}" aria-label="{{ $inspection->isDraft() ? 'Edit draft' : 'Lihat inspeksi' }} {{ $inspection->form_name }}" @click="if (!$event.target.closest('[data-card-action]')) window.location.href = $el.dataset.href" @keydown.enter.self="window.location.href = $el.dataset.href" @keydown.space.prevent.self="window.location.href = $el.dataset.href" class="grid cursor-pointer gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-red-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-700 focus-visible:ring-offset-2 lg:grid-cols-[1fr_1.3fr_1fr_auto] lg:items-center">
                    <div><p class="text-xs text-slate-500">Tanggal Pemeriksaan</p><p class="mt-1 text-sm font-bold">{{ $inspection->inspection_date->translatedFormat('d F Y') }}</p><p class="mt-1 text-xs text-slate-500">Dibuat {{ $inspection->created_at->format('d/m/Y H:i') }}</p></div>
                    <div><h2 class="text-sm font-bold">{{ $inspection->form_name }}</h2>@if ($inspection->unitLink)<p class="mt-1 text-xs font-bold text-blue-700">{{ $inspection->unitLink->unit_code_snapshot }} — {{ $inspection->unitLink->unit_name_snapshot }}</p>@endif<p class="mt-1 break-words text-xs text-slate-600">{{ $inspection->document_no ?? 'Belum diterbitkan' }}</p></div>
                    <div><span class="inline-block rounded-full px-3 py-1 text-xs font-bold {{ $inspection->isDraft() ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $inspection->statusLabel() }}</span><p class="mt-2 text-xs text-slate-500">{{ $inspection->filled_answers_count }} / {{ $inspection->answers_count }} item terisi</p></div>
                    <div data-card-action class="flex flex-wrap items-center justify-end gap-2 lg:justify-start">
                        @if ($inspection->status === \App\Models\EquipmentInspection::STATUS_REVISION)
                            <form method="POST" action="{{ route('inspector.inspections.revise', $inspection) }}" data-swal-confirm data-swal-title="Mulai revisi laporan?" data-swal-text="Versi dokumen baru akan dibuka. Nomor dokumen tetap dan laporan harus ditandatangani ulang." data-swal-confirm-text="Ya, mulai revisi">@csrf<input type="hidden" name="document_version" value="{{ $inspection->document_version }}"><button title="Perbaiki laporan" aria-label="Perbaiki laporan {{ $inspection->form_name }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"><i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i></button></form>
                        @endif
                        <a href="{{ route('inspector.inspections.pdf', $inspection) }}" target="_blank" rel="noopener" title="Buka PDF inspeksi" aria-label="Buka PDF {{ $inspection->form_name }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 transition hover:bg-blue-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"><i data-lucide="file-text" class="h-4 w-4" aria-hidden="true"></i></a>
                        <x-inspections.delete-button :inspection="$inspection" route-name="inspector.inspections.destroy" size="md" />
                    </div>
                    @if ($inspection->status === \App\Models\EquipmentInspection::STATUS_REVISION)
                        <div class="rounded-lg border border-amber-500 p-3 text-sm lg:col-span-4"><p class="font-bold">Perlu revisi · Versi {{ $inspection->document_version }}</p><p class="mt-1 whitespace-pre-wrap">{{ $inspection->revision_note }}</p><p class="mt-2 text-xs">Dikembalikan oleh {{ $inspection->returned_by_name }} · {{ $inspection->returned_at?->format('d/m/Y H:i') }}</p></div>
                    @endif
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">Belum ada inspeksi yang sesuai. Pilih form untuk memulai pemeriksaan baru.</p>
            @endforelse
        </section>
        {{ $inspections->links() }}
    </div>
</x-layouts.inspector>
