<x-layouts.admin title="Quality Control Bengkel">
    @php
        $statusClasses = [
            'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
            'violet' => 'bg-violet-50 text-violet-700 ring-violet-200',
            'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        ];
    @endphp

    <div class="space-y-4">
        <section class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-5">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-900 text-white shadow-sm">
                    <i data-lucide="clipboard-check" class="h-5 w-5"></i>
                </span>
                <div>
                    <h1 class="text-lg font-bold text-slate-900">Quality Control</h1>
                    <p class="mt-0.5 text-xs text-slate-500">Monitoring pemeriksaan kualitas pekerjaan bengkel.</p>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="grid grid-cols-1 gap-3 border-b border-slate-200 p-3 sm:grid-cols-12 sm:items-end sm:p-4">
                <div class="sm:col-span-6">
                    <label for="quality-control-search" class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-slate-500">Pencarian</label>
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input id="quality-control-search" type="search" placeholder="Cari nomor order / pekerjaan / unit..." class="h-10 w-full rounded-lg border border-slate-300 pl-9 pr-3 text-xs text-slate-700 focus:border-blue-500 focus:outline-none">
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <label for="quality-control-type" class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-slate-500">Jenis QC</label>
                    <select id="quality-control-type" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option>Semua Jenis</option>
                        <option>Fabrikasi</option>
                        <option>Refurbish</option>
                    </select>
                </div>
                <div class="sm:col-span-3">
                    <label for="quality-control-status" class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</label>
                    <select id="quality-control-status" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option>Semua Status</option>
                        <option>Perlu Pemeriksaan</option>
                        <option>Dalam Pemeriksaan</option>
                        <option>Menunggu Approval</option>
                        <option>Selesai</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[980px] w-full table-fixed text-left">
                    <colgroup>
                        <col class="w-[15%]">
                        <col class="w-[29%]">
                        <col class="w-[11%]">
                        <col class="w-[14%]">
                        <col class="w-[12%]">
                        <col class="w-[14%]">
                        <col class="w-[5%]">
                    </colgroup>
                    <thead class="bg-slate-100 text-[10px] uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Order</th>
                            <th class="px-4 py-3 font-semibold">Detail Pekerjaan</th>
                            <th class="px-4 py-3 font-semibold">Jenis QC</th>
                            <th class="px-4 py-3 font-semibold">Pemeriksa</th>
                            <th class="px-4 py-3 font-semibold">Tanggal</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-xs text-slate-700">
                        @foreach ($qualityControls as $qualityControl)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    <p class="font-bold text-slate-900">{{ $qualityControl['order_number'] }}</p>
                                    <p class="mt-1 text-[10px] text-blue-600">Notif: {{ $qualityControl['notification_number'] }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <p class="font-semibold leading-4 text-slate-900">{{ $qualityControl['work_name'] }}</p>
                                    <p class="mt-1 text-[10px] text-slate-500">Unit: {{ $qualityControl['unit'] }}</p>
                                    <p class="mt-0.5 text-[10px] text-blue-600">Seksi: {{ $qualityControl['section'] }}</p>
                                </td>
                                <td class="px-4 py-3 align-top font-medium">{{ $qualityControl['type'] }}</td>
                                <td class="px-4 py-3 align-top">{{ $qualityControl['inspector'] }}</td>
                                <td class="px-4 py-3 align-top">{{ $qualityControl['date'] }}</td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold ring-1 ring-inset {{ $statusClasses[$qualityControl['tone']] }}">
                                        {{ $qualityControl['status'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center align-top">
                                    <button type="button" title="Lihat detail" aria-label="Lihat detail Quality Control" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 transition hover:border-blue-300 hover:text-blue-700">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3 text-[10px] text-slate-500">
                Menampilkan {{ $qualityControls->count() }} data Quality Control.
            </div>
        </section>
    </div>
</x-layouts.admin>
