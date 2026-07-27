@php
    $isCompletedProgress = (int) $notification['progress'] >= 100;
    $formId = 'mobile-purchase-order-form-'.$notification['nomor_order'];
    $fileLabelId = 'mobile-po-file-label-'.$notification['nomor_order'];
@endphp

<article
    data-mobile-purchase-order-card="{{ $notification['nomor_order'] }}"
    class="overflow-hidden rounded-2xl border {{ $isCompletedProgress ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-white' }} shadow-sm"
>
    <form id="{{ $formId }}" method="POST" action="{{ $notification['update_url'] }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <input type="hidden" name="_filter_search" value="{{ $search }}">
        <input type="hidden" name="_filter_status" value="{{ $selectedStatus }}">
        <input type="hidden" name="_filter_unit" value="{{ $selectedUnit }}">
        <input type="hidden" name="_filter_from" value="{{ $selectedFrom }}">
        <input type="hidden" name="_filter_to" value="{{ $selectedTo }}">
        <input type="hidden" name="_filter_page" value="{{ $currentPage }}">

        <div class="border-b border-slate-100 bg-gradient-to-r from-blue-50 to-white p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="break-words text-base font-black leading-tight text-slate-900">{{ $notification['nomor_order'] }}</div>
                    <div class="mt-1 break-words text-xs font-semibold text-blue-600">Notif: {{ $notification['notifikasi'] ?: '-' }}</div>
                </div>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 {{ $isCompletedProgress ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-white text-blue-700 ring-blue-200' }}">
                    {{ $notification['progress'] }}%
                </span>
            </div>

            <div class="mt-3 text-sm font-bold leading-snug text-slate-800">{{ $notification['nama_pekerjaan'] }}</div>
            <div class="mt-2 grid grid-cols-1 gap-1 text-xs text-slate-500 sm:grid-cols-2">
                <div><span class="font-semibold text-slate-600">Unit:</span> {{ $notification['unit'] }}</div>
                <div><span class="font-semibold text-slate-600">Seksi:</span> {{ $notification['seksi'] ?: '-' }}</div>
            </div>

            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $notification['progress'] }}%;"></div>
            </div>
        </div>

        <div class="space-y-4 p-4">
            <section>
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Nomor Purchase Order</label>
                <input
                    type="text"
                    name="purchase_order_number"
                    form="{{ $formId }}"
                    value="{{ $notification['nomor_po'] }}"
                    placeholder="Masukkan nomor PO"
                    class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none"
                >
                @if ($notification['approval_note'])
                    <p class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-relaxed text-amber-700">
                        {{ $notification['approval_note'] }}
                    </p>
                @endif
            </section>

            <section class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Target Penyelesaian</label>
                    <input type="date" name="target_penyelesaian" value="{{ $notification['target_penyelesaian'] }}" class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Status Ajuan PKM</label>
                    <select name="approval_target" class="h-10 w-full rounded-xl border px-3 text-sm font-semibold focus:border-blue-500 focus:outline-none {{ $approvalBadgeClasses($notification['approval_target']) }}">
                        <option value="">Status Ajuan PKM</option>
                        <option value="setuju" @selected($notification['approval_target'] === 'setuju')>Setujui Tanggal</option>
                        <option value="tidak_setuju" @selected($notification['approval_target'] === 'tidak_setuju')>Tolak Tanggal</option>
                    </select>
                </div>
            </section>

            <section>
                <div class="mb-2 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Approval</div>
                <div class="grid grid-cols-1 gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-2">
                    <label class="flex min-h-9 items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                        <input type="checkbox" name="approve_manager" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-600" @checked($notification['approvals']['manager'])>
                        Manager
                    </label>
                    <label class="flex min-h-9 items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                        <input type="checkbox" name="approve_senior_manager" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-600" @checked($notification['approvals']['senior_manager'])>
                        Senior Manager
                    </label>
                    <label class="flex min-h-9 items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                        <input type="checkbox" name="approve_general_manager" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-600" @checked($notification['approvals']['general_manager'])>
                        General Manager
                    </label>
                    @if ($notification['requires_dirops'])
                        <label class="flex min-h-9 items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                            <input type="checkbox" name="approve_direktur_operasional" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-600" @checked($notification['approvals']['direktur_operasional'])>
                            Direktur Operasional
                        </label>
                    @endif
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Dokumen PO</div>
                        <div id="{{ $fileLabelId }}" class="mt-1 break-all text-xs font-semibold leading-relaxed text-slate-700">
                            {{ $notification['po_document_name'] ?: 'Belum ada file dipilih' }}
                        </div>
                        <div class="mt-1 text-[10px] leading-relaxed text-slate-400">{{ $purchaseOrderUploadHint }}</div>
                    </div>
                    <label class="inline-flex h-10 shrink-0 cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3 text-xs font-bold text-white transition hover:bg-emerald-700">
                        <i data-lucide="upload" class="h-4 w-4"></i>
                        Upload
                        <input
                            type="file"
                            name="po_document"
                            form="{{ $formId }}"
                            class="hidden purchase-order-file-input"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg"
                            data-label-id="{{ $fileLabelId }}"
                            data-form-id="{{ $formId }}"
                            data-order-number="{{ $notification['nomor_order'] }}"
                        >
                    </label>
                </div>
                @if ($notification['po_document_url'])
                    <a href="{{ $notification['po_document_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex h-9 items-center gap-2 rounded-lg bg-blue-50 px-3 text-xs font-bold text-blue-700 ring-1 ring-blue-200">
                        <i data-lucide="file-text" class="h-4 w-4"></i>
                        Lihat Dokumen
                    </a>
                @endif
            </section>

            <section>
                @if ($notification['vendor_note'])
                    <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 p-3">
                        <div class="text-[10px] font-bold uppercase tracking-[0.12em] text-amber-700">Catatan Vendor</div>
                        <div class="mt-1 text-xs leading-relaxed text-slate-700">{{ $notification['vendor_note'] }}</div>
                    </div>
                @endif
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Catatan untuk Vendor</label>
                <textarea name="admin_note" rows="3" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm leading-relaxed text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none" placeholder="Catatan untuk vendor...">{{ $notification['admin_note'] }}</textarea>
            </section>
        </div>

        <div class="flex justify-end border-t border-slate-100 bg-slate-50 px-4 py-3">
            <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-xs font-bold text-white transition hover:bg-blue-700">
                <i data-lucide="save" class="h-4 w-4"></i>
                Simpan Purchase Order
            </button>
        </div>
    </form>
</article>
