<x-layouts.inspector title="Inspeksi Saya — WOMS">
    <div class="space-y-4">
        <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div><h1 class="text-xl font-black text-slate-900">Inspeksi Saya</h1><p class="mt-1 text-sm text-slate-500">Riwayat pemeriksaan dan draft Anda.</p></div>
            <a href="{{ route('inspector.equipment-forms.index') }}" class="rounded-xl bg-[#7f1017] px-4 py-2.5 text-center text-sm font-bold text-white">Buat Inspeksi Baru</a>
        </section>
        @if ($errors->any())<p role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</p>@endif
        @if (session('success'))<p role="status" class="rounded-xl border border-green-600 bg-white p-4 text-sm text-green-800">{{ session('success') }}</p>@endif
        <form method="GET" action="{{ route('inspector.inspections.index') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-5">
            <label class="text-xs font-semibold text-slate-600">Peralatan<input type="search" name="equipment" value="{{ $filters['equipment'] ?? '' }}" placeholder="Nama peralatan..." class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-base sm:text-sm"></label>
            <label class="text-xs font-semibold text-slate-600">Tanggal dari<input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 block w-full min-w-0 rounded-xl border border-slate-300 px-3 py-2.5 text-base sm:text-sm"></label>
            <label class="text-xs font-semibold text-slate-600">Tanggal sampai<input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 block w-full min-w-0 rounded-xl border border-slate-300 px-3 py-2.5 text-base sm:text-sm"></label>
            <label class="text-xs font-semibold text-slate-600">Status<select name="status" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-base sm:text-sm"><option value="">Semua status</option>@foreach (\App\Models\EquipmentInspection::STATUS_LABELS as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label>
            <div class="flex items-end gap-2"><button type="submit" class="rounded-xl bg-[#7f1017] px-4 py-2.5 text-sm font-bold text-white">Terapkan</button><a href="{{ route('inspector.inspections.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">Reset Filter</a></div>
        </form>
        <section class="space-y-3" aria-label="Riwayat inspeksi">
            @forelse ($inspections as $inspection)
                <article class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[1fr_1.3fr_1fr_auto] lg:items-center">
                    <div><p class="text-xs text-slate-500">Tanggal Pemeriksaan</p><p class="mt-1 text-sm font-bold">{{ $inspection->inspection_date->translatedFormat('d F Y') }}</p><p class="mt-1 text-xs text-slate-500">Dibuat {{ $inspection->created_at->format('d/m/Y H:i') }}</p></div>
                    <div><h2 class="text-sm font-bold">{{ $inspection->form_name }}</h2><p class="mt-1 break-words text-xs text-slate-600">{{ $inspection->document_no ?? 'Belum diterbitkan' }}</p></div>
                    <div><span class="inline-block rounded-full px-3 py-1 text-xs font-bold {{ $inspection->isDraft() ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $inspection->statusLabel() }}</span><p class="mt-2 text-xs text-slate-500">{{ $inspection->filled_answers_count }} / {{ $inspection->answers_count }} item terisi</p></div>
                    <div class="flex flex-wrap items-center gap-1">
                        @if ($inspection->status === \App\Models\EquipmentInspection::STATUS_REVISION)
                            <form method="POST" action="{{ route('inspector.inspections.revise', $inspection) }}">@csrf<input type="hidden" name="document_version" value="{{ $inspection->document_version }}"><button title="Perbaiki laporan" aria-label="Perbaiki laporan {{ $inspection->form_name }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"><i data-lucide="rotate-ccw" class="h-3 w-3" aria-hidden="true"></i></button></form>
                        @endif
                        <a href="{{ route('inspector.inspections.show', $inspection) }}" title="{{ $inspection->isDraft() ? 'Edit draft' : 'Lihat inspeksi' }}" aria-label="{{ $inspection->isDraft() ? 'Edit draft' : 'Lihat inspeksi' }} {{ $inspection->form_name }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 transition hover:bg-blue-100"><i data-lucide="{{ $inspection->isDraft() ? 'pencil' : 'eye' }}" class="h-3 w-3" aria-hidden="true"></i></a>
                        <a href="{{ route('inspector.inspections.pdf', $inspection) }}" target="_blank" rel="noopener" title="Preview PDF" aria-label="Preview PDF {{ $inspection->form_name }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"><i data-lucide="file-text" class="h-3 w-3" aria-hidden="true"></i></a>
                        <x-inspections.delete-button :inspection="$inspection" route-name="inspector.inspections.destroy" />
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
