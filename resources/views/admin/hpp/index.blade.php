<x-layouts.admin title="Create HPP">
    @php
        $formatRupiah = function ($value): string {
            $normalized = number_format((float) $value, 2, ',', '.');

            if (str_ends_with($normalized, ',00')) {
                return substr($normalized, 0, -3);
            }

            return rtrim(rtrim($normalized, '0'), ',');
        };
        $bucketLabels = \App\Support\HppApprovalFlow::bucketOptions();
        $displayArea = fn (?string $value): string => \App\Support\HppApprovalFlow::displayArea((string) $value);
        $pendingHppOrders = collect($pendingHppOrders ?? []);
    @endphp

    @if (session('status'))
        <div id="hpp-status-alert" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    <div class="space-y-6">
        <section class="rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                        <i data-lucide="file-text" class="h-[18px] w-[18px]"></i>
                    </span>
                    <div>
                        <h1 class="text-[1.3rem] font-bold leading-none tracking-tight text-slate-900">Create HPP</h1>
                        <p class="mt-1.5 text-[11px] text-slate-500">Daftar HPP dan snapshot approval yang sudah dibuat.</p>
                    </div>
                </div>

                <a href="{{ route('admin.hpp.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-[11px] font-semibold text-white transition hover:bg-blue-700">
                    <i data-lucide="plus-circle" class="h-[13px] w-[13px]"></i>
                    Buat HPP
                </a>
            </div>
        </section>

        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                @if ($pendingHppOrders->isNotEmpty())
                    <div class="mb-4 rounded-[1.2rem] border border-blue-200 bg-gradient-to-r from-blue-50 to-white px-3 py-3 text-slate-800 shadow-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                                        <i data-lucide="triangle-alert" class="h-3.5 w-3.5"></i>
                                    </span>
                                    <div class="text-[12px] font-black text-blue-950">Order Belum Dibuatkan HPP</div>
                                    <span class="inline-flex rounded-full border border-blue-200 bg-white px-2 py-0.5 text-[10px] font-bold text-blue-800">
                                        {{ $pendingHppOrders->count() }} order
                                    </span>
                                </div>
                                <p class="mt-1 pl-9 text-[10px] leading-5 text-blue-800">
                                    Sudah memenuhi syarat create HPP, tapi dokumen HPP-nya belum dibuat.
                                </p>
                            </div>
                        </div>

                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($pendingHppOrders as $pendingOrder)
                                <div class="min-w-[210px] rounded-xl border border-blue-200 bg-white px-2.5 py-2 text-[10px] shadow-sm">
                                    <div class="font-black text-slate-900">{{ $pendingOrder['nomor_order'] }}</div>
                                    <div class="mt-0.5 truncate text-slate-700">{{ $pendingOrder['nama_pekerjaan'] !== '' ? $pendingOrder['nama_pekerjaan'] : '-' }}</div>
                                    <div class="mt-0.5 truncate text-slate-500">
                                        {{ $pendingOrder['seksi'] !== '' ? $pendingOrder['seksi'] : ($pendingOrder['unit_kerja'] !== '' ? $pendingOrder['unit_kerja'] : '-') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="GET" action="{{ route('admin.hpp.index') }}" class="flex flex-col gap-2.5 xl:flex-row xl:items-end xl:justify-between">
                    <div class="grid flex-1 gap-2.5 md:grid-cols-2 xl:grid-cols-[1.2fr_0.6fr]">
                        <div class="flex flex-col">
                            <label for="search" class="mb-1.5 text-[10px] font-semibold text-slate-700">Pencarian</label>
                            <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Cari nomor order / pekerjaan / area..." class="rounded-lg border border-slate-300 px-3 py-2 text-[11px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none">
                        </div>
                        <div class="flex flex-col">
                            <label for="status" class="mb-1.5 text-[10px] font-semibold text-slate-700">Status</label>
                            <select id="status" name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-[11px] text-slate-700 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua Status</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-700" title="Filter">
                            <i data-lucide="filter" class="h-[13px] w-[13px]"></i>
                        </button>
                        <a href="{{ route('admin.hpp.index') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50" title="Reset">
                            <i data-lucide="rotate-ccw" class="h-[13px] w-[13px]"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full table-fixed divide-y divide-slate-200 text-[11px] text-slate-700">
                    <colgroup>
                        <col class="w-[15%]">
                        <col class="w-[33%]">
                        <col class="w-[14%]">
                        <col class="w-[11%]">
                        <col class="w-[10%]">
                        <col class="w-[10%]">
                        <col class="w-[7%]">
                    </colgroup>
                    <thead class="bg-slate-200/80 text-slate-700">
                        <tr>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Order</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Detail Pekerjaan</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Case</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Nilai HPP</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Status</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Proggress Approval</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($rows as $row)
                            <tr class="align-top hover:bg-slate-50">
                                <td class="px-5 py-4 text-[11px] font-semibold text-slate-800">
                                    <div class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-[16px] font-bold tracking-[0.04em] text-slate-900 shadow-sm">
                                        {{ $row->nomor_order }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-800">{{ $row->nama_pekerjaan }}</div>
                                    <div class="mt-2 text-[9px]">
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 font-semibold text-blue-700 ring-1 ring-blue-100">
                                            Seksi: {{ $row->order?->seksi ?: '-' }}
                                        </span>
                                    </div>
                                    <div class="mt-2 text-[9px]">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-600">
                                            Unit: {{ $row->unit_kerja }}
                                        </span>
                                    </div>
                                    <div class="mt-2 text-[9px] text-slate-400">Dibuat: {{ $row->created_at?->format('Y-m-d') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="inline-flex flex-col rounded-2xl bg-slate-100 px-3 py-2 text-[9px] text-slate-700">
                                        <span class="font-semibold">{{ $row->kategori_pekerjaan }} ({{ $displayArea($row->area_pekerjaan) }})</span>
                                        <span class="mt-1 text-slate-500">{{ $bucketLabels[$row->nilai_hpp_bucket] ?? ($row->approval_case ?: '-') }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-[11px] font-semibold text-slate-800">
                                    Rp {{ $formatRupiah($row->total_keseluruhan) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-[9px] font-semibold {{ $row->statusBadgeClasses() }}">
                                        {{ \App\Models\Hpp::statusOptions()[$row->status] ?? ucfirst(str_replace('_', ' ', $row->status)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-[11px] text-slate-700">
                                    {{ $row->currentStepLabel() }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.hpp.pdf', ['hpp' => $row->nomor_order]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50" title="Lihat PDF HPP">
                                            <i data-lucide="file-text" class="h-[14px] w-[14px]"></i>
                                        </a>

                                        @if ($row->isEditable())
                                            <a href="{{ route('admin.hpp.edit', ['hpp' => $row->nomor_order]) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-700 transition hover:bg-blue-100" title="Edit HPP">
                                                <i data-lucide="pencil" class="h-[14px] w-[14px]"></i>
                                            </a>
                                        @endif

                                        @if ($row->isDeletable())
                                            <form method="POST" action="{{ route('admin.hpp.destroy', $row) }}" class="delete-hpp-form" data-order="{{ $row->nomor_order }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100" title="Hapus HPP">
                                                    <i data-lucide="trash-2" class="h-[14px] w-[14px]"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-[12px] text-slate-500">Belum ada HPP yang dibuat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const statusAlert = document.getElementById('hpp-status-alert');

            if (statusAlert?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: statusAlert.dataset.message,
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            document.querySelectorAll('.delete-hpp-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    if (!window.Swal) {
                        form.submit();
                        return;
                    }

                    const orderNumber = form.dataset.order || 'HPP ini';
                    const result = await window.Swal.fire({
                        icon: 'warning',
                        title: 'Hapus HPP?',
                        html: `Yakin ingin menghapus HPP untuk order <b>${orderNumber}</b>?`,
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#dc2626',
                    });

                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
</x-layouts.admin>
