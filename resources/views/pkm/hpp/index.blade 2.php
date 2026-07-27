<x-layouts.pkm title="Create HPP">
    <div class="space-y-4 p-4">
        <section class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4">
            <div>
                <h1 class="text-lg font-bold text-slate-900">Create HPP</h1>
                <p class="mt-1 text-xs text-slate-500">{{ $eligibleOrderCount }} order belum memiliki HPP.</p>
            </div>
            <a href="{{ route('pkm.hpp.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Buat HPP</a>
        </section>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <form method="GET" class="flex flex-wrap gap-2 border-b border-slate-200 p-3">
                <input name="search" value="{{ $search }}" placeholder="Cari order, pekerjaan, unit..." class="min-w-56 flex-1 rounded-lg border-slate-300 text-sm">
                <select name="status" class="rounded-lg border-slate-300 text-sm">
                    <option value="">Semua Status</option>
                    @foreach ($statusOptions as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach
                </select>
                <button class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-semibold text-white">Filter</button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr>
                            <th class="px-3 py-2">Order</th><th class="px-3 py-2">Pekerjaan</th>
                            <th class="px-3 py-2">Nilai / Status</th><th class="px-3 py-2">Pembuat</th><th class="px-3 py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-3 py-3">
                                    <div class="font-bold text-slate-900">{{ $row->nomor_order }}</div>
                                    <div class="text-slate-500">Notif: {{ $row->order?->notifikasi ?: '-' }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="font-semibold">{{ $row->nama_pekerjaan }}</div>
                                    <div class="text-slate-500">{{ $row->unit_kerja }} · {{ $row->order?->seksi ?: '-' }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div>Rp {{ number_format((float) $row->total_keseluruhan, 0, ',', '.') }}</div>
                                    <span class="mt-1 inline-flex rounded-full px-2 py-0.5 font-semibold {{ $row->statusBadgeClasses() }}">{{ $statusOptions[$row->status] ?? $row->status }}</span>
                                </td>
                                <td class="px-3 py-3">{{ $row->creator?->name ?: '-' }}<div class="text-slate-500">{{ strtoupper($row->creator?->role ?: '-') }}</div></td>
                                <td class="px-3 py-3">
                                    <div class="flex gap-2">
                                        @if ($row->status === \App\Models\Hpp::STATUS_DRAFT)
                                            <a href="{{ route('pkm.hpp.edit', $row) }}" class="rounded bg-emerald-600 px-2 py-1 font-semibold text-white">Edit Draft</a>
                                        @endif
                                        <a target="_blank" href="{{ route('pkm.hpp.pdf', $row) }}" class="rounded bg-blue-600 px-2 py-1 font-semibold text-white">Lihat PDF</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-8 text-center text-slate-500">Belum ada data HPP.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $rows->links() }}</div>
        </section>
    </div>
</x-layouts.pkm>
