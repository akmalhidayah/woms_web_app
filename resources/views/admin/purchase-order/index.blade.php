<x-layouts.admin title="Purchase Order">
    @if (session('status'))
        <div id="purchase-order-status-alert" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    @php
        $approvalBadgeClasses = static fn (?string $value): string => match ($value) {
            'setuju' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'tidak_setuju' => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-slate-200 bg-slate-50 text-slate-500',
        };
    @endphp

    <div class="space-y-5">
        <section class="rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                        <i data-lucide="file-check" class="h-[18px] w-[18px]"></i>
                    </span>
                    <div>
                        <h1 class="text-[1.3rem] font-bold leading-none tracking-tight text-slate-900">Purchase Order</h1>
                        <p class="mt-1.5 text-[11px] text-slate-500">Pantau PO, target penyelesaian, approval, progress, dan dokumen vendor.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 overflow-x-auto">
                <form method="GET" action="{{ route('admin.purchase-order.index') }}" class="flex min-w-[980px] items-center gap-2">
                    <div class="relative min-w-0 flex-1">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-[12px] w-[12px] -translate-y-1/2 text-slate-400"></i>
                        <input id="search" type="text" name="search" value="{{ $search }}" placeholder="Nomor order atau nama pekerjaan" class="w-full rounded-lg border border-slate-300 px-8 py-1.5 text-[10px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none">
                    </div>

                    <select id="status" name="status" class="w-[150px] rounded-lg border border-slate-300 px-2.5 py-1.5 text-[10px] text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option value="">Semua Status</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <select id="unit" name="unit" class="w-[160px] rounded-lg border border-slate-300 px-2.5 py-1.5 text-[10px] text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option value="">Semua Unit</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit }}" @selected($selectedUnit === $unit)>{{ $unit }}</option>
                        @endforeach
                    </select>

                    <input id="from" type="date" name="from" value="{{ $selectedFrom }}" class="w-[130px] rounded-lg border border-slate-300 px-2.5 py-1.5 text-[10px] text-slate-700 focus:border-blue-500 focus:outline-none">
                    <input id="to" type="date" name="to" value="{{ $selectedTo }}" class="w-[130px] rounded-lg border border-slate-300 px-2.5 py-1.5 text-[10px] text-slate-700 focus:border-blue-500 focus:outline-none">

                    <div class="ml-auto flex items-center gap-2">
                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-700" title="Filter">
                            <i data-lucide="filter" class="h-[12px] w-[12px]"></i>
                        </button>
                        <a href="{{ route('admin.purchase-order.index') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50" title="Reset">
                            <i data-lucide="rotate-ccw" class="h-[12px] w-[12px]"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full table-fixed divide-y divide-slate-200 text-[10px] text-slate-700">
                    <colgroup>
                        <col class="w-[11%]">
                        <col class="w-[12%]">
                        <col class="w-[22%]">
                        <col class="w-[12%]">
                        <col class="w-[12%]">
                        <col class="w-[19%]">
                        <col class="w-[12%]">
                    </colgroup>
                    <thead class="bg-slate-200/80 text-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-[0.12em]">Order</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-[0.12em]">Nomor PO</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-[0.12em]">Target & Approval</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-[0.12em]">Progress</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-[0.12em]">Dokumen PO</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-[0.12em]">Catatan</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-[0.12em]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($notifications as $notification)
                            @php
                                $isCompletedProgress = (int) $notification['progress'] >= 100;
                            @endphp
                            <tr class="align-top {{ $isCompletedProgress ? 'bg-emerald-50/70 hover:bg-emerald-100/70' : 'hover:bg-slate-50' }}">
                                <td class="px-4 py-4 text-center">
                                    <form id="purchase-order-form-{{ $notification['nomor_order'] }}" method="POST" action="{{ $notification['update_url'] }}" enctype="multipart/form-data" class="hidden">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="_filter_search" value="{{ $search }}">
                                        <input type="hidden" name="_filter_status" value="{{ $selectedStatus }}">
                                        <input type="hidden" name="_filter_unit" value="{{ $selectedUnit }}">
                                        <input type="hidden" name="_filter_from" value="{{ $selectedFrom }}">
                                        <input type="hidden" name="_filter_to" value="{{ $selectedTo }}">
                                        <input type="hidden" name="_filter_page" value="{{ $notifications->currentPage() }}">
                                    </form>

                                    <div class="inline-flex min-w-[98px] items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-[14px] font-bold text-slate-900 shadow-sm">
                                        {{ $notification['nomor_order'] }}
                                    </div>
                                    <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 text-left text-[8px] text-slate-600 shadow-sm">
                                        <div>
                                            <span class="block leading-tight text-blue-700">{{ $notification['seksi'] }}</span>
                                        </div>
                                        <div class="mt-1 border-t border-slate-200 pt-1">
                                            <span class="block leading-tight">{{ $notification['unit'] }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <input type="text" name="purchase_order_number" form="purchase-order-form-{{ $notification['nomor_order'] }}" value="{{ $notification['nomor_po'] }}" placeholder="Nomor PO" class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-[10px] text-center text-slate-700 focus:border-blue-500 focus:outline-none purchase-order-number-input">
                                    @if ($notification['approval_note'])
                                        <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-2 py-1.5 text-[9px] text-amber-700">
                                            {{ $notification['approval_note'] }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <div class="space-y-2">
                                        <input type="date" name="target_penyelesaian" form="purchase-order-form-{{ $notification['nomor_order'] }}" value="{{ $notification['target_penyelesaian'] }}" class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-[10px] text-slate-700 focus:border-blue-500 focus:outline-none">

                                        <select name="approval_target" form="purchase-order-form-{{ $notification['nomor_order'] }}" class="w-full rounded-lg border px-2.5 py-2 text-[10px] font-medium focus:border-blue-500 focus:outline-none {{ $approvalBadgeClasses($notification['approval_target']) }}">
                                            <option value="">Status Ajuan Penyelesaian PKM</option>
                                            <option value="setuju" @selected($notification['approval_target'] === 'setuju')>Setujui Tanggal</option>
                                            <option value="tidak_setuju" @selected($notification['approval_target'] === 'tidak_setuju')>Tolak Tanggal</option>
                                        </select>

                                        <div class="grid gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                            <label class="flex items-center gap-2 text-[9px] text-slate-700">
                                                <input type="checkbox" name="approve_manager" form="purchase-order-form-{{ $notification['nomor_order'] }}" value="1" class="h-3 w-3 rounded border-slate-300 text-emerald-600" @checked($notification['approvals']['manager'])>
                                                Manager
                                            </label>
                                            <label class="flex items-center gap-2 text-[9px] text-slate-700">
                                                <input type="checkbox" name="approve_senior_manager" form="purchase-order-form-{{ $notification['nomor_order'] }}" value="1" class="h-3 w-3 rounded border-slate-300 text-emerald-600" @checked($notification['approvals']['senior_manager'])>
                                                Senior Manager
                                            </label>
                                            <label class="flex items-center gap-2 text-[9px] text-slate-700">
                                                <input type="checkbox" name="approve_general_manager" form="purchase-order-form-{{ $notification['nomor_order'] }}" value="1" class="h-3 w-3 rounded border-slate-300 text-emerald-600" @checked($notification['approvals']['general_manager'])>
                                                General Manager
                                            </label>
                                            <label class="flex items-center gap-2 text-[9px] text-slate-700">
                                                <input type="checkbox" name="approve_direktur_operasional" form="purchase-order-form-{{ $notification['nomor_order'] }}" value="1" class="h-3 w-3 rounded border-slate-300 text-emerald-600" @checked($notification['approvals']['direktur_operasional'])>
                                                Direktur Operasional
                                            </label>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm">
                                        <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                                            <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $notification['progress'] }}%;"></div>
                                        </div>
                                        <div class="mt-2 text-[11px] font-semibold text-slate-700">{{ $notification['progress'] }}%</div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <div class="space-y-2">
                                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-[10px] font-semibold text-white transition hover:bg-emerald-700">
                                            <i data-lucide="upload" class="h-[11px] w-[11px]"></i>
                                            Upload
                                            <input
                                                type="file"
                                                name="po_document"
                                                form="purchase-order-form-{{ $notification['nomor_order'] }}"
                                                class="hidden purchase-order-file-input"
                                                data-label-id="po-file-label-{{ $notification['nomor_order'] }}"
                                                data-form-id="purchase-order-form-{{ $notification['nomor_order'] }}"
                                                data-order-number="{{ $notification['nomor_order'] }}"
                                            >
                                        </label>
                                        <div id="po-file-label-{{ $notification['nomor_order'] }}" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-2 py-2 text-[9px] text-slate-500">
                                            {{ $notification['po_document_name'] ?: 'Belum ada file dipilih' }}
                                        </div>
                                        @if ($notification['po_document_url'])
                                            <a href="{{ $notification['po_document_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-[9px] font-medium text-blue-600 hover:underline">
                                                <i data-lucide="file-text" class="h-[11px] w-[11px]"></i>
                                                {{ $notification['po_document_name'] }}
                                            </a>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="space-y-2">
                                        @if ($notification['vendor_note'])
                                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2">
                                                <div class="text-[9px] font-semibold uppercase tracking-[0.1em] text-amber-700">Catatan Vendor</div>
                                                <div class="mt-1 text-[10px] text-slate-700">{{ $notification['vendor_note'] }}</div>
                                            </div>
                                        @endif

                                        <textarea name="admin_note" form="purchase-order-form-{{ $notification['nomor_order'] }}" rows="4" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-[10px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none" placeholder="Tambahkan catatan untuk vendor...">{{ $notification['admin_note'] }}</textarea>
                                    </div>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <button type="submit" form="purchase-order-form-{{ $notification['nomor_order'] }}" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-[10px] font-semibold text-white transition hover:bg-blue-700">
                                        <i data-lucide="save" class="h-[11px] w-[11px]"></i>
                                        Update
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-[11px] text-slate-500">Tidak ada data ditemukan.</td>
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
