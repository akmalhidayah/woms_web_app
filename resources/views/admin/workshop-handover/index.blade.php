@php
    $rows = $tab === 'history' ? $history : $waiting;
    $isHistory = $tab === 'history';
@endphp

<x-layouts.admin title="Serah Terima Bengkel">
    <div class="space-y-4">
        <section class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-5">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-900 text-white">
                    <i data-lucide="handshake" class="h-5 w-5"></i>
                </span>
                <div>
                    <h1 class="text-lg font-bold text-slate-900">Serah Terima</h1>
                    <p class="mt-0.5 text-xs text-slate-500">Monitoring proses penyerahan hasil pekerjaan bengkel.</p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap gap-2 border-b border-slate-200 px-4 pt-4">
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'waiting']) }}"
                    class="rounded-lg px-3 py-2 text-xs font-semibold {{ ! $isHistory ? 'bg-blue-700 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                    Menunggu Serah Terima <span class="ml-1 rounded-full bg-white/20 px-1.5 py-0.5">{{ $waitingCount }}</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'history']) }}"
                    class="rounded-lg px-3 py-2 text-xs font-semibold {{ $isHistory ? 'bg-blue-700 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                    Riwayat <span class="ml-1 rounded-full bg-white/20 px-1.5 py-0.5">{{ $historyCount }}</span>
                </a>
            </div>

            <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-200 p-4 sm:grid-cols-12 sm:items-end">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="sm:col-span-8">
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-500">Pencarian</label>
                    <input name="search" value="{{ $search }}" placeholder="Cari nomor order / pekerjaan / unit..." class="h-10 w-full rounded-lg border border-slate-300 px-3 text-xs">
                </div>
                <div class="sm:col-span-3">
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-500">Jalur</label>
                    <select name="path" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs">
                        <option value="">Semua Jalur</option>
                        <option value="Critical" @selected($path === 'Critical')>Critical</option>
                        <option value="Non-Critical" @selected($path === 'Non-Critical')>Non-Critical</option>
                    </select>
                </div>
                <button class="h-10 rounded-lg bg-blue-700 px-4 text-xs font-semibold text-white">Terapkan</button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-[850px] w-full text-left text-xs">
                    <thead class="bg-slate-100 text-[10px] uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3">Detail Pekerjaan</th>
                            <th class="px-4 py-3">Jalur</th>
                            <th class="px-4 py-3">Progress</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($rows as $row)
                            @php
                                $order = $isHistory ? $row->order : $row;
                                $number = $isHistory ? $row->order_no_snapshot : $order->nomor_order;
                                $jobName = $isHistory ? $row->job_name_snapshot : $order->nama_pekerjaan;
                                $unit = $isHistory ? $row->unit_snapshot : $order->unit_kerja;
                                $section = $isHistory ? $row->section_snapshot : $order->seksi;
                                $handover = ! $isHistory ? $order->workshopHandover : $row;
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    <p class="font-bold text-slate-900">{{ $number }}</p>
                                    <p class="text-[10px] text-blue-600">Notif: {{ $isHistory ? ($row->order?->notifikasi ?: '-') : ($order->notifikasi ?: '-') }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <p class="font-semibold text-slate-900">{{ $jobName }}</p>
                                    <p class="text-[10px] text-slate-500">Unit: {{ $unit ?: '-' }}</p>
                                    <p class="text-[10px] text-blue-600">Seksi: {{ $section ?: '-' }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-700">
                                        {{ $isHistory ? $row->path : $queue->path($order) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                                        {{ $isHistory ? '2/2' : ($handover?->progress() ?? '0/2') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @if ($isHistory)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Selesai</span>
                                    @elseif ($handover)
                                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">Menunggu tanda tangan Manager User</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">Menunggu Bukti Serah Terima</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                    {{ $isHistory ? 'Belum ada riwayat Serah Terima.' : 'Belum ada pekerjaan yang siap diserahterimakan.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3 text-[10px] text-slate-500">
                Menampilkan {{ $rows->count() }} {{ $isHistory ? 'riwayat' : 'antrean' }}.
            </div>
            @if ($rows->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $rows->links() }}
                </div>
            @endif
        </section>
    </div>
</x-layouts.admin>
