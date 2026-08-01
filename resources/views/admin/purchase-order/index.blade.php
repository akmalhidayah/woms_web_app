<x-layouts.admin title="Purchase Order">
    @if (session('status'))
        <div id="purchase-order-status-alert" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    @php
        $purchaseOrderUploadHint = 'Maks. 10 MB • Format: PDF, DOC, DOCX, JPG, JPEG';
        $approvalBadgeClasses = static fn (?string $value): string => match ($value) {
            'setuju' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'tidak_setuju' => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-slate-200 bg-slate-50 text-slate-500',
        };
    @endphp

    <div class="order-list-compact space-y-4">
        <section class="order-list-hero rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                        <i data-lucide="file-check" class="h-[18px] w-[18px]"></i>
                    </span>
                    <div>
                        <h1 class="text-[1.3rem] font-bold leading-none tracking-tight text-slate-900">Purchase Order</h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="order-list-panel overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-sm">
            <div class="space-y-3 border-b border-slate-200 px-5 py-4">
                <nav class="overflow-x-auto" aria-label="Status Purchase Order">
                    <div class="flex min-w-max items-center gap-2 pb-1">
                        @foreach ($tabOptions as $tabKey => $tabLabel)
                            @php
                                $tabQuery = ['tab' => $tabKey];

                                if ($search !== '') {
                                    $tabQuery['search'] = $search;
                                }
                            @endphp
                            <a
                                href="{{ route('admin.purchase-order.index', $tabQuery) }}"
                                @if ($activeTab === $tabKey) aria-current="page" @endif
                                class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-lg border px-3 text-[10px] font-semibold transition {{ $activeTab === $tabKey ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}"
                            >
                                <span>{{ $tabLabel }}</span>
                                <span class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[9px] {{ $activeTab === $tabKey ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $tabCounts[$tabKey] ?? 0 }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </nav>

                <form method="GET" action="{{ route('admin.purchase-order.index') }}">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <div class="relative min-w-0">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-[12px] w-[12px] -translate-y-1/2 text-slate-400"></i>
                        <input id="search" type="text" name="search" value="{{ $search }}" placeholder="Cari nomor order / nomor PO / pekerjaan / unit..." class="w-full rounded-lg border border-slate-300 px-8 py-1.5 text-[10px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none">
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto overscroll-x-contain">
                <div class="border-b border-slate-100 bg-slate-50 px-3 py-2 text-[9px] font-semibold text-slate-500 lg:hidden">
                    Geser tabel ke samping untuk melihat seluruh kolom.
                </div>
                <table class="purchase-order-responsive-table table-fixed divide-y divide-slate-200 text-[10px] text-slate-700 lg:min-w-full">
                    <colgroup>
                        <col class="w-[10%]">
                        <col class="w-[17%]">
                        <col class="w-[10%]">
                        <col class="w-[20%]">
                        <col class="w-[8%]">
                        <col class="w-[10%]">
                        <col class="w-[18%]">
                        <col class="w-[7%]">
                    </colgroup>
                    <thead class="bg-slate-200/80 text-slate-700">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.12em]">Order</th>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.12em]">Detail Pekerjaan</th>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.12em]">Nomor PO</th>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.12em]">Target & Approval</th>
                            <th class="px-4 py-2.5 text-center text-[9px] font-semibold uppercase tracking-[0.12em]">Progress</th>
                            <th class="px-4 py-2.5 text-center text-[9px] font-semibold uppercase tracking-[0.12em]">Dokumen PO</th>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.12em]">Catatan</th>
                            <th class="px-4 py-2.5 text-center text-[9px] font-semibold uppercase tracking-[0.12em]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($notifications as $notification)
                            @php
                                $isCompletedProgress = (int) $notification['progress'] >= 100;
                            @endphp
                            <tr class="align-top {{ $isCompletedProgress ? 'bg-emerald-50/70 hover:bg-emerald-100/70' : 'hover:bg-slate-50' }}">
                                <td class="px-4 py-3">
                                    <form id="purchase-order-form-{{ $notification['nomor_order'] }}" method="POST" action="{{ $notification['update_url'] }}" enctype="multipart/form-data" class="hidden">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="_filter_tab" value="{{ $activeTab }}">
                                        <input type="hidden" name="_filter_search" value="{{ $search }}">
                                        <input type="hidden" name="_filter_page" value="{{ $notifications->currentPage() }}">
                                    </form>

                                    <div class="text-[12px] font-bold tracking-[0.01em] text-slate-900">
                                        {{ $notification['nomor_order'] }}
                                    </div>
                                    <div class="mt-0.5 text-[8px] font-medium leading-3 text-blue-600">
                                        Notif: {{ $notification['notifikasi'] ?: '-' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="text-[10px] font-semibold leading-4 text-slate-800">{{ $notification['nama_pekerjaan'] }}</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[8.5px]">
                                        <span class="text-slate-500">Unit: <strong class="font-semibold text-slate-700">{{ $notification['unit'] }}</strong></span>
                                        <span class="text-slate-300">|</span>
                                        <span class="text-blue-500">Seksi: <strong class="font-semibold text-blue-700">{{ $notification['seksi'] ?: '-' }}</strong></span>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <input type="text" name="purchase_order_number" form="purchase-order-form-{{ $notification['nomor_order'] }}" value="{{ $notification['nomor_po'] }}" placeholder="Nomor PO" class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-[9px] text-center text-slate-700 focus:border-blue-500 focus:outline-none purchase-order-number-input">
                                    @if ($notification['approval_note'])
                                        <p class="mt-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2 py-1 text-[8px] leading-3 text-amber-700">
                                            {{ $notification['approval_note'] }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <div class="space-y-1.5">
                                        <div class="grid gap-1.5 lg:grid-cols-2">
                                            <input type="date" name="target_penyelesaian" form="purchase-order-form-{{ $notification['nomor_order'] }}" value="{{ $notification['target_penyelesaian'] }}" class="min-w-0 w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-[9px] text-slate-700 focus:border-blue-500 focus:outline-none">

                                            <select name="approval_target" form="purchase-order-form-{{ $notification['nomor_order'] }}" class="min-w-0 w-full rounded-lg border px-2 py-1.5 text-[9px] font-medium focus:border-blue-500 focus:outline-none {{ $approvalBadgeClasses($notification['approval_target']) }}">
                                                <option value="">Status Ajuan PKM</option>
                                                <option value="setuju" @selected($notification['approval_target'] === 'setuju')>Setujui Tanggal</option>
                                                <option value="tidak_setuju" @selected($notification['approval_target'] === 'tidak_setuju')>Tolak Tanggal</option>
                                            </select>
                                        </div>

                                        <div class="grid grid-cols-2 gap-x-2 gap-y-1 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5">
                                            <label class="flex items-center gap-1.5 text-[8px] text-slate-700">
                                                <input type="checkbox" name="approve_manager" form="purchase-order-form-{{ $notification['nomor_order'] }}" value="1" class="h-2.5 w-2.5 rounded border-slate-300 text-emerald-600" @checked($notification['approvals']['manager'])>
                                                Manager
                                            </label>
                                            <label class="flex items-center gap-1.5 text-[8px] text-slate-700">
                                                <input type="checkbox" name="approve_senior_manager" form="purchase-order-form-{{ $notification['nomor_order'] }}" value="1" class="h-2.5 w-2.5 rounded border-slate-300 text-emerald-600" @checked($notification['approvals']['senior_manager'])>
                                                Senior Manager
                                            </label>
                                            <label class="flex items-center gap-1.5 text-[8px] text-slate-700">
                                                <input type="checkbox" name="approve_general_manager" form="purchase-order-form-{{ $notification['nomor_order'] }}" value="1" class="h-2.5 w-2.5 rounded border-slate-300 text-emerald-600" @checked($notification['approvals']['general_manager'])>
                                                General Manager
                                            </label>
                                            @if ($notification['requires_dirops'])
                                                <label class="flex items-center gap-1.5 text-[8px] text-slate-700">
                                                    <input type="checkbox" name="approve_direktur_operasional" form="purchase-order-form-{{ $notification['nomor_order'] }}" value="1" class="h-2.5 w-2.5 rounded border-slate-300 text-emerald-600" @checked($notification['approvals']['direktur_operasional'])>
                                                    Direktur Operasional
                                                </label>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <div class="rounded-lg border border-slate-200 bg-white px-2 py-2 shadow-sm">
                                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                            <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $notification['progress'] }}%;"></div>
                                        </div>
                                        <div class="mt-1 text-[9px] font-semibold text-slate-700">{{ $notification['progress'] }}%</div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <div class="space-y-1.5">
                                        <label class="inline-flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg bg-emerald-600 text-white transition hover:bg-emerald-700" title="Upload dokumen PO" aria-label="Upload dokumen PO">
                                            <i data-lucide="upload" class="h-[11px] w-[11px]"></i>
                                            <input
                                                type="file"
                                                name="po_document"
                                                form="purchase-order-form-{{ $notification['nomor_order'] }}"
                                                class="hidden purchase-order-file-input"
                                                accept=".pdf,.doc,.docx,.jpg,.jpeg"
                                                data-label-id="po-file-label-{{ $notification['nomor_order'] }}"
                                                data-form-id="purchase-order-form-{{ $notification['nomor_order'] }}"
                                                data-order-number="{{ $notification['nomor_order'] }}"
                                            >
                                        </label>
                                        <div id="po-file-label-{{ $notification['nomor_order'] }}" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-2 py-1.5 text-[8px] leading-3 text-slate-500">
                                            {{ $notification['po_document_name'] ?: 'Belum ada file dipilih' }}
                                        </div>
                                        @if ($notification['po_document_url'])
                                            <a href="{{ $notification['po_document_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-[8px] font-medium text-blue-600 hover:underline" title="{{ $notification['po_document_name'] }}">
                                                <i data-lucide="file-text" class="h-[11px] w-[11px]"></i>
                                                Lihat
                                            </a>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="space-y-1.5">
                                        @if ($notification['vendor_note'])
                                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-2 py-1.5">
                                                <div class="text-[8px] font-semibold uppercase tracking-[0.1em] text-amber-700">Catatan Vendor</div>
                                                <div class="mt-0.5 text-[9px] leading-3 text-slate-700">{{ $notification['vendor_note'] }}</div>
                                            </div>
                                        @endif

                                        <textarea name="admin_note" form="purchase-order-form-{{ $notification['nomor_order'] }}" rows="3" class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-[9px] leading-4 text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none" placeholder="Catatan untuk vendor...">{{ $notification['admin_note'] }}</textarea>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <button type="submit" form="purchase-order-form-{{ $notification['nomor_order'] }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-700" title="Update Purchase Order" aria-label="Update Purchase Order">
                                        <i data-lucide="save" class="h-[11px] w-[11px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-[11px] text-slate-500">
                                    {{ match ($activeTab) {
                                        'ready' => 'Belum ada Purchase Order yang siap dikerjakan.',
                                        'in_progress' => 'Belum ada pekerjaan Purchase Order yang sedang berjalan.',
                                        'history' => 'Belum ada riwayat Purchase Order.',
                                        default => 'Belum ada Purchase Order yang perlu tindakan.',
                                    } }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($notifications->hasPages())
                <div class="border-t border-slate-200 px-4 py-4">
                    {{ $notifications->appends(request()->query())->links() }}
                </div>
            @endif
        </section>
    </div>

    <style>
        @media (max-width: 1023px) {
            .purchase-order-responsive-table {
                width: 1480px;
                min-width: 1480px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const statusAlert = document.getElementById('purchase-order-status-alert');

            if (statusAlert?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: statusAlert.dataset.message,
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            document.querySelectorAll('.purchase-order-file-input').forEach((input) => {
                input.addEventListener('change', () => {
                    const labelId = input.dataset.labelId;
                    const label = labelId ? document.getElementById(labelId) : null;
                    const formId = input.dataset.formId;
                    const orderNumber = input.dataset.orderNumber || 'DRAFT';
                    const purchaseOrderInput = formId
                        ? document.querySelector(`input[name="purchase_order_number"][form="${formId}"]`)
                        : null;

                    if (! label) {
                        return;
                    }

                    if (! input.files?.length) {
                        label.textContent = 'Belum ada file dipilih';
                        return;
                    }

                    const purchaseOrderNumber = (purchaseOrderInput?.value || orderNumber)
                        .trim()
                        .toUpperCase()
                        .replace(/[^A-Z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '') || 'DRAFT';
                    const originalName = input.files[0].name || '';
                    const extension = originalName.includes('.')
                        ? originalName.split('.').pop()?.toLowerCase()
                        : '';

                    label.textContent = `PO-${purchaseOrderNumber}${extension ? `.${extension}` : ''}`;
                });
            });
        });
    </script>
</x-layouts.admin>
