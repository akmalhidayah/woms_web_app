<x-layouts.admin title="Monitoring Inspeksi Peralatan">
    <div class="space-y-4" x-data="{
        remarks: null, loading: false, error: '', requestId: 0,
        async openRemarks(url) {
            const id = ++this.requestId; this.remarks = null; this.error = ''; this.loading = true;
            this.$refs.remarksDialog.showModal();
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!response.ok) throw new Error('Remarks tidak dapat dimuat. Periksa hak akses atau coba lagi.');
                const data = await response.json(); if (id === this.requestId) this.remarks = data;
            } catch (error) { if (id === this.requestId) this.error = error.message; }
            finally { if (id === this.requestId) this.loading = false; }
        }
    }">
        <header class="rounded-2xl border border-blue-200 bg-white p-5">
            <h1 class="flex items-center gap-3 text-xl font-bold"><i data-lucide="clipboard-check" class="h-6 w-6 text-blue-700"></i>Monitoring Inspeksi Peralatan</h1>
            <p class="mt-2 text-sm text-slate-600">Monitoring laporan, versi, dan pengiriman email. Persetujuan dilakukan melalui Dokumen Approval.</p>
        </header>
        @if ($errors->any())<p role="alert" class="rounded-xl border border-red-500 bg-white p-4 text-red-800">{{ $errors->first() }}</p>@endif
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <nav class="flex flex-wrap gap-2 border-b border-slate-200 p-4" aria-label="Tab monitoring">
                @foreach (\App\Support\Inspector\EquipmentInspectionIndexTabs::options() as $key => $label)
                    <a href="{{ route('admin.inspections.index', ['tab' => $key]) }}" @if ($tab === $key) aria-current="page" @endif class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold {{ $tab === $key ? 'bg-blue-700 text-white' : 'bg-slate-100 text-slate-700' }}">
                        {{ $label }}<span class="rounded-full bg-red-600 px-2 text-xs text-white">{{ $counts[$key] }}</span>
                    </a>
                @endforeach
            </nav>
            <form method="GET" class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                <input type="hidden" name="tab" value="{{ $tab }}">
                @foreach (['document_no' => 'Nomor Dokumen', 'equipment' => 'Nama Peralatan / Form', 'inspector' => 'Nama Inspektor'] as $key => $label)
                    <label class="text-xs font-semibold">{{ $label }}<input name="{{ $key }}" value="{{ $filters[$key] ?? '' }}" type="search" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                @endforeach
                <label class="text-xs font-semibold">Status<select name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Semua status</option>@foreach (\App\Models\EquipmentInspection::STATUS_LABELS as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label>
                <label class="text-xs font-semibold">Tanggal dari<input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 w-full min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                <label class="text-xs font-semibold">Tanggal sampai<input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 w-full min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                <label class="text-xs font-semibold">Memiliki hasil<select name="rating" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Semua kondisi</option>@foreach (['A' => 'A · Normal', 'B' => 'B · Kurang Normal', 'C' => 'C · Rusak'] as $key => $label)<option value="{{ $key }}" @selected(($filters['rating'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label>
                <div class="flex items-end gap-2"><button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Terapkan</button><a href="{{ route('admin.inspections.index', ['tab' => $tab]) }}" class="rounded-lg border px-4 py-2 text-sm">Reset</a></div>
            </form>
            <p class="px-4 pb-3 text-xs text-slate-500">Jumlah tab adalah total sebelum filter. Baru Masuk dihitung per Admin dan versi kiriman.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-700"><tr><th class="p-3">No.</th><th class="p-3">Peralatan / Jenis Form</th><th class="p-3">Tanggal Pemeriksaan</th><th class="p-3">Inspektor</th><th class="p-3">Nomor Dokumen</th><th class="p-3">Kondisi</th><th class="p-3">Posisi Approval</th><th class="p-3">Action</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($inspections as $inspection)
                            @php
                                $remarkCount = $inspection->answers->filter(fn ($a) => preg_match('/[^\s\p{Z}]/u', (string) $a->remark))->count();
                                $worst = $inspection->answers->pluck('rating')->filter()->sortDesc()->first();
                            @endphp
                            <tr>
                                <td class="p-3">{{ $inspections->firstItem() + $loop->index }}</td>
                                <td class="max-w-xs p-3 font-semibold">{{ $inspection->form_name }}</td>
                                <td class="whitespace-nowrap p-3">{{ $inspection->inspection_date->format('d/m/Y') }}</td>
                                <td class="p-3">{{ $inspection->inspector_name }}</td>
                                <td class="p-3">{{ $inspection->document_no ?? 'Belum diterbitkan' }}<p class="mt-1 text-slate-500">Versi {{ $inspection->document_version }}</p></td>
                                <td class="p-3">{{ ['A' => 'A · Normal', 'B' => 'B · Kurang Normal', 'C' => 'C · Rusak'][$worst] ?? 'Belum diperiksa' }}</td>
                                <td class="p-3"><span class="inline-block rounded-lg border border-blue-600 px-2 py-1 text-blue-900">{{ $inspection->statusLabel() }}</span>@if ($inspection->workflow_error || $inspection->archive_error)<p class="mt-1 font-semibold text-red-700">Perlu pemeriksaan Admin</p>@endif</td>
                                <td class="p-3"><div class="flex items-center gap-2">
                                    @if ($remarkCount > 0)<button type="button" @click="openRemarks(@js(route('admin.inspections.remarks', $inspection)))" title="Lihat semua Remarks" aria-label="Lihat {{ $remarkCount }} item dengan Remark" class="inline-flex items-center gap-1 rounded-lg bg-yellow-400 px-2 py-2 font-bold text-slate-950"><span aria-hidden="true">!</span>{{ $remarkCount }}</button>@endif
                                    <a href="{{ route('admin.inspections.pdf', $inspection) }}" target="_blank" rel="noopener" class="rounded-lg bg-blue-700 px-3 py-2 font-semibold text-white">PDF</a>
                                    <a href="{{ route('admin.inspections.show', $inspection) }}" class="rounded-lg border border-slate-300 px-3 py-2">Detail</a>
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="p-10 text-center text-sm text-slate-500">Tidak ada laporan yang sesuai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $inspections->links() }}</div>
        </section>
        <dialog x-ref="remarksDialog" aria-labelledby="remarks-title" class="w-[calc(100%-2rem)] max-w-3xl rounded-2xl border border-slate-300 p-0 backdrop:bg-black/60">
            <div class="flex max-h-[85vh] flex-col">
                <header class="flex items-start justify-between gap-3 border-b p-5"><div><h2 id="remarks-title" class="text-lg font-bold" x-text="'Remarks ' + (remarks?.number ?? '')"></h2><p class="mt-1 text-sm text-slate-600" x-text="remarks ? remarks.equipment + ' · ' + remarks.date : ''"></p></div><button type="button" @click="$refs.remarksDialog.close()" aria-label="Tutup Remarks" class="rounded-lg border px-3 py-1">✕</button></header>
                <div class="space-y-4 overflow-y-auto p-5">
                    <p x-show="loading" role="status">Memuat Remarks…</p><p x-show="error" x-text="error" role="alert" class="text-red-700"></p>
                    <template x-for="item in (remarks?.items ?? [])" :key="item.position">
                        <article class="rounded-xl border border-slate-300 p-4"><h3 class="font-bold" x-text="item.position + '. ' + item.label"></h3><span class="my-2 inline-block rounded-md bg-slate-800 px-2 py-1 text-xs text-white" x-text="'Hasil ' + (item.rating || 'belum dipilih')"></span><p class="whitespace-pre-wrap break-words text-sm" x-text="item.remark"></p><div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3"><template x-for="photo in item.photos" :key="photo"><a :href="photo" target="_blank" rel="noopener"><img :src="photo" :alt="'Foto temuan item ' + item.position" loading="lazy" class="h-32 w-full rounded-lg border object-contain"></a></template></div></article>
                    </template>
                    <p x-show="remarks && !remarks.items.length" class="text-sm text-slate-500">Tidak ada Remark.</p>
                </div>
                <footer class="border-t p-4 text-right"><button type="button" @click="$refs.remarksDialog.close()" class="rounded-lg bg-slate-900 px-4 py-2 text-white">Tutup</button></footer>
            </div>
        </dialog>
    </div>
</x-layouts.admin>
