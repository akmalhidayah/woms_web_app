<x-layouts.admin title="Create HPP">
    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-[13px] font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-[1.5rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                        <i data-lucide="file-text" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h1 class="text-[1.4rem] font-bold leading-none tracking-tight text-slate-900">Create HPP</h1>
                        <p class="mt-2 text-[12px] text-slate-500">Daftar HPP dan snapshot approval yang sudah dibuat.</p>
                    </div>
                </div>

                <a href="{{ route('admin.hpp.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-[12px] font-semibold text-white transition hover:bg-blue-700">
                    <i data-lucide="plus-circle" class="h-[14px] w-[14px]"></i>
                    Buat HPP
                </a>
            </div>
        </section>

        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <form method="GET" action="{{ route('admin.hpp.index') }}" class="flex flex-col gap-2.5 xl:flex-row xl:items-end xl:justify-between">
                    <div class="grid flex-1 gap-2.5 md:grid-cols-2 xl:grid-cols-[1.2fr_0.6fr]">
                        <div class="flex flex-col">
                            <label for="search" class="mb-1.5 text-[10px] font-semibold text-slate-700">Pencarian</label>
                            <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Cari nomor order / pekerjaan / area..." class="rounded-lg border border-slate-300 px-3 py-2 text-[12px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none">
                        </div>
                        <div class="flex flex-col">
                            <label for="status" class="mb-1.5 text-[10px] font-semibold text-slate-700">Status</label>
                            <select id="status" name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-[12px] text-slate-700 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua Status</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-700" title="Filter">
                            <i data-lucide="filter" class="h-[14px] w-[14px]"></i>
                        </button>
                        <a href="{{ route('admin.hpp.index') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50" title="Reset">
                            <i data-lucide="rotate-ccw" class="h-[14px] w-[14px]"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-[12px] text-slate-700">
                    <thead class="bg-slate-200/80 text-slate-700">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase">Order</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase">Detail Pekerjaan</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase">Case</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase">Nilai HPP</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase">Status</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase">Step Aktif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($rows as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-[12px] font-semibold text-slate-800">
                                    <div>{{ $row->nomor_order }}</div>
                                    <div class="text-[10px] text-slate-400">HPP-{{ str_pad((string) $row->id, 4, '0', STR_PAD_LEFT) }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">{{ $row->nama_pekerjaan }}</div>
                                    <div class="mt-1 text-[10px] text-slate-500">{{ $row->kategori_pekerjaan }} - {{ $row->area_pekerjaan }} - {{ $row->unit_kerja }}</div>
                                    <div class="mt-2 text-[10px] text-slate-400">Dibuat: {{ $row->created_at?->format('Y-m-d') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-semibold text-slate-700">{{ $row->approval_case ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-[12px] font-semibold text-slate-800">
                                    Rp {{ number_format((float) $row->total_keseluruhan, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-3 py-1 text-[10px] font-semibold {{ $row->statusBadgeClasses() }}">
                                        {{ \App\Models\Hpp::statusOptions()[$row->status] ?? ucfirst(str_replace('_', ' ', $row->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-[12px] text-slate-700">
                                    {{ $row->currentStepLabel() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-[12px] text-slate-500">Belum ada HPP yang dibuat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
