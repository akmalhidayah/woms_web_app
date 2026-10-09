<x-layouts.inspector title="Inspeksi Saya — WOMS">
    <div class="space-y-4">
        <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div><h1 class="text-xl font-black text-slate-900">Inspeksi Saya</h1><p class="mt-1 text-sm text-slate-500">Riwayat pemeriksaan dan draft Anda.</p></div>
            <a href="{{ route('inspector.equipment-forms.index') }}" class="rounded-xl bg-[#7f1017] px-4 py-2.5 text-center text-sm font-bold text-white">Buat Inspeksi Baru</a>
        </section>
        @if ($errors->any())<p role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</p>@endif
        @if (session('success'))<p role="status" class="rounded-xl border border-green-600 bg-white p-4 text-sm text-green-800">{{ session('success') }}</p>@endif
        <section class="rounded-2xl border border-slate-200 bg-white">
            <x-inspections.index-tabs route-name="inspector.inspections.index" :tab="$tab" :counts="$counts" :search="$filters['search'] ?? ''" />
        </section>
        <section class="space-y-3" aria-label="Riwayat inspeksi">
            @forelse ($inspections as $inspection)
                <article class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[1fr_1.3fr_1fr_auto] lg:items-center">
                    <div><p class="text-xs text-slate-500">Tanggal Pemeriksaan</p><p class="mt-1 text-sm font-bold">{{ $inspection->inspection_date->translatedFormat('d F Y') }}</p><p class="mt-1 text-xs text-slate-500">Dibuat {{ $inspection->created_at->format('d/m/Y H:i') }}</p></div>
                    <div><h2 class="text-sm font-bold">{{ $inspection->form_name }}</h2><p class="mt-1 break-words text-xs text-slate-600">{{ $inspection->document_no ?? 'Belum diterbitkan' }}</p></div>
                    <div><span class="inline-block rounded-full px-3 py-1 text-xs font-bold {{ $inspection->isDraft() ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $inspection->statusLabel() }}</span><p class="mt-2 text-xs text-slate-500">{{ $inspection->filled_answers_count }} / {{ $inspection->answers_count }} item terisi</p></div>
                    <div class="grid gap-2 sm:flex lg:flex-col">
                        @if ($inspection->status === \App\Models\EquipmentInspection::STATUS_REVISION)
                            <form method="POST" action="{{ route('inspector.inspections.revise', $inspection) }}">@csrf<input type="hidden" name="document_version" value="{{ $inspection->document_version }}"><button class="w-full rounded-xl bg-amber-500 px-3 py-2 text-xs font-bold text-slate-950">Perbaiki Laporan</button></form>
                        @endif
                        <a href="{{ route('inspector.inspections.show', $inspection) }}" class="rounded-xl bg-[#7f1017] px-3 py-2 text-center text-xs font-bold text-white">{{ $inspection->isDraft() ? 'Lanjutkan Draft' : 'Lihat Detail' }}</a>
                        <a href="{{ route('inspector.inspections.pdf', $inspection) }}" target="_blank" rel="noopener" class="rounded-xl border border-slate-300 px-3 py-2 text-center text-xs font-semibold">Preview PDF</a>
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
