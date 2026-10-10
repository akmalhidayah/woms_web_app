<x-layouts.admin title="Monitoring Inspeksi Peralatan">
    <div class="order-list-compact space-y-4" x-data="{
        remarks: null, loading: false, error: '', requestId: 0,
        approvalModalOpen: false, approvalProgress: null, approvalLoading: false, approvalError: '', approvalRequestId: 0,
        async openApproval(url) {
            const id = ++this.approvalRequestId;
            this.approvalProgress = null; this.approvalError = ''; this.approvalLoading = true;
            this.approvalModalOpen = true;
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!response.ok) throw new Error('Progres approval tidak dapat dimuat. Muat ulang halaman dan coba lagi.');
                const data = await response.json();
                if (id === this.approvalRequestId) this.approvalProgress = data;
            } catch (error) {
                if (id === this.approvalRequestId) {
                    this.approvalError = error.message;
                    this.approvalModalOpen = false;
                    window.inspectionSweetAlert.error(error.message, 'Progres approval gagal dimuat');
                }
            }
            finally { if (id === this.approvalRequestId) this.approvalLoading = false; }
        },
        async openRemarks(url) {
            const id = ++this.requestId; this.remarks = null; this.error = ''; this.loading = true;
            this.$refs.remarksDialog.showModal();
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!response.ok) throw new Error('Remarks tidak dapat dimuat. Periksa hak akses atau coba lagi.');
                const data = await response.json(); if (id === this.requestId) this.remarks = data;
            } catch (error) {
                if (id === this.requestId) {
                    this.error = error.message;
                    this.$refs.remarksDialog.close();
                    window.inspectionSweetAlert.error(error.message, 'Remarks gagal dimuat');
                }
            }
            finally { if (id === this.requestId) this.loading = false; }
        }
    }">
        <section class="order-list-hero rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
            <div class="flex items-center gap-4">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                    <i data-lucide="clipboard-check" class="h-[18px] w-[18px]" aria-hidden="true"></i>
                </span>
                <h1 class="text-[1.3rem] font-bold leading-none tracking-tight text-slate-900">Monitoring Inspeksi Peralatan</h1>
            </div>
        </section>

        <x-inspections.sweet-alerts :success="session('success')" :error-messages="$errors->all()" />
        <section class="order-list-panel overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <x-inspections.index-tabs route-name="admin.inspections.index" :tab="$tab" :counts="$counts" :search="$filters['search'] ?? ''" />
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] table-fixed bg-white text-left text-[11px] text-slate-700">
                    <colgroup>
                        <col class="w-[4%]">
                        <col class="w-[14%]">
                        <col class="w-[13%]">
                        <col class="w-[10%]">
                        <col class="w-[15%]">
                        <col class="w-[9%]">
                        <col class="w-[25%]">
                        <col class="w-[10%]">
                    </colgroup>
                    <thead class="bg-slate-100 uppercase tracking-wide text-slate-700"><tr><th class="px-4 py-2 font-semibold">No.</th><th class="px-4 py-2 font-semibold">Peralatan / Jenis Form</th><th class="px-4 py-2 font-semibold">Tanggal Pemeriksaan</th><th class="px-4 py-2 font-semibold">Inspektor</th><th class="px-4 py-2 font-semibold">Nomor Dokumen</th><th class="px-4 py-2 font-semibold">Kondisi</th><th class="px-4 py-2 font-semibold">Posisi Approval</th><th class="px-4 py-2 text-center font-semibold">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($inspections as $inspection)
                            @php
                                $remarkCount = $inspection->answers->filter(fn ($a) => preg_match('/[^\s\p{Z}]/u', (string) $a->remark))->count();
                                $worst = $inspection->answers->pluck('rating')->filter()->sortDesc()->first();
                                $signedCount = $inspection->status === \App\Models\EquipmentInspection::STATUS_REVISION ? 0 : $inspection->signed_count;
                            @endphp
                            <tr class="transition duration-150 hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">{{ $inspections->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-3 align-top font-bold text-slate-900">{{ $inspection->form_name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 align-top">{{ $inspection->inspection_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 align-top">{{ $inspection->inspector_name }}</td>
                                <td class="break-words px-4 py-3 align-top">{{ $inspection->document_no ?? 'Belum diterbitkan' }}</td>
                                <td class="px-4 py-3 align-top">{{ ['A' => 'A · Normal', 'B' => 'B · Kurang Normal', 'C' => 'C · Rusak'][$worst] ?? 'Belum diperiksa' }}</td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex min-w-0 items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-2 py-1.5 shadow-sm">
                                        <span class="shrink-0 rounded-full bg-white px-1.5 py-0.5 text-[8px] font-bold text-blue-700 ring-1 ring-blue-100">{{ $signedCount }}/{{ count(\App\Models\EquipmentInspectionSignature::STEPS) }} TTD</span>
                                        <span class="truncate text-[9px] font-semibold text-slate-800" title="{{ $inspection->statusLabel() }}">{{ $inspection->statusLabel() }}</span>
                                        <button type="button" @click="openApproval(@js(route('admin.inspections.approval-progress', $inspection)))" title="Detail progres approval" aria-label="Detail progres approval {{ $inspection->form_name }}" class="ml-auto inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-blue-200 hover:bg-blue-100 hover:text-blue-700"><i data-lucide="info" class="h-3 w-3" aria-hidden="true"></i></button>
                                    </div>
                                    @if ($inspection->workflow_error || $inspection->archive_error)<p class="mt-1 font-semibold text-red-700">Perlu pemeriksaan Admin</p>@endif
                                </td>
                                <td class="px-4 py-3 align-top"><div class="flex flex-nowrap items-center justify-center gap-1.5">
                                    @if ($remarkCount > 0)<button type="button" @click="openRemarks(@js(route('admin.inspections.remarks', $inspection)))" title="Lihat {{ $remarkCount }} item dengan Remark" aria-label="Lihat {{ $remarkCount }} item dengan Remark" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"><i data-lucide="message-square-text" class="h-3 w-3" aria-hidden="true"></i></button>@endif
                                    <a href="{{ route('admin.inspections.pdf', $inspection) }}" target="_blank" rel="noopener" title="Lihat PDF inspeksi" aria-label="Lihat PDF {{ $inspection->form_name }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"><i data-lucide="file-text" class="h-3 w-3" aria-hidden="true"></i></a>
                                    <a href="{{ route('admin.inspections.show', $inspection) }}" title="Lihat inspeksi" aria-label="Lihat inspeksi {{ $inspection->form_name }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 transition hover:bg-blue-100"><i data-lucide="eye" class="h-3 w-3" aria-hidden="true"></i></a>
                                    <x-inspections.delete-button :inspection="$inspection" route-name="admin.inspections.destroy" />
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500">Tidak ada laporan yang sesuai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4">{{ $inspections->links() }}</div>
        </section>
        @include('admin.inspections._approval-modal')
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
