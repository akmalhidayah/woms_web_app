<x-layouts.pkm title="Create HPP">
    <style>
        .hpp-index-filter {
            display: grid;
            gap: 0.5rem;
        }

        @media (min-width: 640px) {
            .hpp-index-filter {
                grid-template-columns: minmax(0, 1.25fr) minmax(180px, 0.65fr) auto;
                align-items: end;
            }
        }
    </style>

    @php
        $formatRupiah = function ($value): string {
            $normalized = number_format((float) $value, 2, ',', '.');

            if (str_ends_with($normalized, ',00')) {
                return substr($normalized, 0, -3);
            }

            return rtrim(rtrim($normalized, '0'), ',');
        };
        $pendingHppOrders = collect($pendingHppOrders ?? []);
    @endphp

    <div class="order-list-compact space-y-4">
        <section class="order-list-hero rounded-[1.35rem] border border-blue-100 bg-blue-50 px-5 py-4 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                        <i data-lucide="file-text" class="h-[18px] w-[18px]"></i>
                    </span>
                    <h1 class="text-[1.3rem] font-bold leading-none tracking-tight text-slate-900">Create HPP</h1>
                </div>

                <a href="{{ route('pkm.hpp.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-[11px] font-semibold text-white transition hover:bg-blue-700">
                    <i data-lucide="plus-circle" class="h-[13px] w-[13px]"></i>
                    Buat HPP
                </a>
            </div>
        </section>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <section class="order-list-panel overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                @if ($pendingHppOrders->isNotEmpty())
                    <div class="mb-3 border-b border-blue-100 pb-2.5 text-slate-700">
                        <div class="flex flex-wrap items-center gap-1.5 text-[10px]">
                            <i data-lucide="triangle-alert" class="h-3 w-3 text-blue-600"></i>
                            <span class="font-bold text-blue-900">Order belum dibuatkan HPP</span>
                            <span class="text-blue-600">({{ $pendingHppOrders->count() }})</span>
                        </div>

                        <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1">
                            @foreach ($pendingHppOrders as $pendingOrder)
                                <div class="flex min-w-0 items-center gap-1.5 text-[9px]">
                                    <span class="font-bold text-slate-800">{{ $pendingOrder['nomor_order'] }}</span>
                                    <span class="text-slate-400">-</span>
                                    <span class="max-w-[260px] truncate text-slate-600">{{ $pendingOrder['nama_pekerjaan'] !== '' ? $pendingOrder['nama_pekerjaan'] : '-' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="GET" action="{{ route('pkm.hpp.index') }}" class="hpp-index-filter">
                    <div class="flex min-w-0 flex-col">
                        <label for="search" class="mb-1.5 text-[10px] font-semibold text-slate-700">Pencarian</label>
                        <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Cari nomor order / pekerjaan / area..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-[11px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div class="flex min-w-0 flex-col">
                        <label for="status" class="mb-1.5 text-[10px] font-semibold text-slate-700">Status</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-[11px] text-slate-700 focus:border-blue-500 focus:outline-none">
                            <option value="">Semua Status</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-700" title="Filter">
                            <i data-lucide="filter" class="h-[13px] w-[13px]"></i>
                        </button>
                        <a href="{{ route('pkm.hpp.index') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50" title="Reset">
                            <i data-lucide="rotate-ccw" class="h-[13px] w-[13px]"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full table-fixed divide-y divide-slate-200 text-[11px] text-slate-700">
                    <colgroup>
                        <col class="w-[17%]">
                        <col class="w-[29%]">
                        <col class="w-[15%]">
                        <col class="w-[30%]">
                        <col class="w-[9%]">
                    </colgroup>
                    <thead class="bg-slate-200/80 text-slate-700">
                        <tr>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Order</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Detail Pekerjaan</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Nilai HPP / Status</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Progress Approval</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($rows as $row)
                            @php
                                $signedCount = $row->approvalSignedCount();
                                $totalSteps = $row->approvalStepCount();
                                $isApprovalComplete = $row->approvalCompleted();
                                $currentSignerLabel = $row->currentApprovalSignerLabel();
                                $approvalSummaryLabel = $isApprovalComplete
                                    ? 'Semua approver selesai'
                                    : ($currentSignerLabel ?: 'Menunggu approver aktif');
                            @endphp
                            <tr class="align-top hover:bg-slate-50">
                                <td class="px-5 py-3 text-[10px] text-slate-800">
                                    <div class="font-bold text-slate-900">{{ $row->nomor_order }}</div>
                                    <div class="mt-0.5 text-[9px] font-medium text-blue-600">Notif: {{ $row->order?->notifikasi ?: '-' }}</div>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="font-semibold leading-4 text-slate-800">{{ $row->nama_pekerjaan }}</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[9px]">
                                        <span class="text-slate-500">Unit: <strong class="font-semibold text-slate-700">{{ $row->unit_kerja }}</strong></span>
                                        <span class="text-slate-300">|</span>
                                        <span class="text-blue-500">Seksi: <strong class="font-semibold text-blue-700">{{ $row->order?->seksi ?: '-' }}</strong></span>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="text-[10px] font-bold text-slate-800">Rp {{ $formatRupiah($row->total_keseluruhan) }}</div>
                                    <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[8px] font-semibold {{ $row->statusBadgeClasses() }}">
                                        {{ $statusOptions[$row->status] ?? ucfirst(str_replace('_', ' ', $row->status)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-[11px] text-slate-700">
                                    @if ($row->status === \App\Models\Hpp::STATUS_DRAFT)
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-2">
                                            <div class="text-[9px] font-bold uppercase tracking-[0.16em] text-slate-400">Draft</div>
                                            <div class="mt-0.5 text-[9px] font-semibold text-slate-700">Belum submit approval</div>
                                        </div>
                                    @elseif ($row->status === \App\Models\Hpp::STATUS_REJECTED)
                                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-2.5 py-2 shadow-sm">
                                            <span class="inline-flex rounded-full bg-white px-2 py-0.5 text-[8px] font-bold text-rose-700 ring-1 ring-rose-200">Rejected</span>
                                            <div class="mt-1 text-[9px] font-semibold text-rose-800">HPP ditolak.</div>
                                        </div>
                                    @elseif ($totalSteps === 0)
                                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-2.5 py-2">
                                            <div class="text-[9px] font-bold uppercase tracking-[0.16em] text-amber-500">Approval</div>
                                            <div class="mt-0.5 text-[9px] font-semibold text-amber-800">Signature belum dibuat</div>
                                        </div>
                                    @else
                                        <div class="rounded-xl border border-blue-100 bg-blue-50 px-2 py-1.5 shadow-sm">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex min-w-0 items-center gap-1.5">
                                                    <span class="inline-flex shrink-0 rounded-full bg-white px-1.5 py-0.5 text-[8px] font-bold text-blue-700 ring-1 ring-blue-100">
                                                        {{ $signedCount }}/{{ $totalSteps }} TTD
                                                    </span>
                                                    <span class="truncate text-[9px] font-semibold text-slate-800" title="{{ $approvalSummaryLabel }}">{{ $approvalSummaryLabel }}</span>
                                                    @if ($isApprovalComplete)
                                                        <span class="inline-flex shrink-0 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[8px] font-bold text-emerald-700">Complete</span>
                                                    @endif
                                                </div>
                                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400" title="Status approval">
                                                    <i data-lucide="info" class="h-3 w-3"></i>
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <a target="_blank" rel="noopener noreferrer" href="{{ route('pkm.hpp.pdf', $row) }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700" title="Lihat PDF">
                                            <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                        </a>
                                        @if ($row->status === \App\Models\Hpp::STATUS_DRAFT)
                                            <a href="{{ route('pkm.hpp.edit', $row) }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100" title="Edit Draft">
                                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">Belum ada data HPP.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($rows->hasPages())
                <div class="border-t border-slate-200 px-5 py-3">{{ $rows->links() }}</div>
            @endif
        </section>
    </div>
</x-layouts.pkm>
