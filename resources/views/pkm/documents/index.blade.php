@php
    $documentRows = $documentRows ?? new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 10, 1, [
        'path' => url()->current(),
        'query' => request()->query(),
    ]);
    $documentSearch = $documentSearch ?? '';
    $documentStatus = $documentStatus ?? '';
@endphp

<div class="space-y-5">
    <section class="overflow-hidden rounded-[1.8rem] border border-[#f2dccb] bg-[linear-gradient(135deg,_#ffffff_0%,_#fff9f4_60%,_#fbe8da_100%)] px-5 py-5 text-slate-900 shadow-[0_20px_48px_-34px_rgba(222,119,59,0.34)]">
        <div class="flex items-start gap-4">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/85 text-[#ca642f] shadow-[0_10px_24px_-16px_rgba(202,100,47,0.65)] ring-1 ring-[#f1d5c2]">
                <i data-lucide="folder-kanban" class="h-5 w-5"></i>
            </span>

            <div>
                <h1 class="text-[2rem] font-black leading-none tracking-tight text-slate-900">Dokumen</h1>
                <p class="mt-2 text-[13px] text-slate-500">Tampilan compact untuk ringkasan dokumen pekerjaan, LPJ/PPL, pembayaran, dan garansi.</p>
            </div>
        </div>
    </section>

    <section class="rounded-[1.6rem] border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <div class="text-[15px] font-black text-slate-900">Laporan Dokumen</div>
                <p class="mt-1 text-[12px] text-slate-500">Ikon dokumen, dropdown termin, pembayaran, dan status garansi dalam satu tabel ringkas.</p>
            </div>

            <form method="GET" action="{{ route('pkm.laporan') }}" class="flex flex-wrap items-end gap-2">
                <div>
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Pencarian</label>
                    <input
                        name="notification_number"
                        value="{{ $documentSearch }}"
                        placeholder="No. Order / Notifikasi"
                        class="w-[210px] rounded-lg border border-slate-300 px-3 py-1.5 text-[11px] text-slate-700 focus:border-[#ca642f] focus:outline-none"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Status</label>
                    <select name="status" class="rounded-lg border border-slate-300 px-3 py-1.5 text-[11px] text-slate-700 focus:border-[#ca642f] focus:outline-none">
                        <option value="">Semua</option>
                        <option value="complete" @selected($documentStatus === 'complete')>Lengkap</option>
                        <option value="incomplete" @selected($documentStatus === 'incomplete')>Belum</option>
                    </select>
                </div>

                <button type="submit" class="inline-flex h-[32px] items-center gap-2 rounded-lg bg-[#ca642f] px-3 text-[11px] font-bold text-white transition hover:bg-[#b85b2b]">
                    <i data-lucide="filter" class="h-3.5 w-3.5"></i>
                    Filter
                </button>

                <a href="{{ route('pkm.laporan') }}" class="inline-flex h-[32px] items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50">
                    <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                    Reset
                </a>
            </form>
        </div>
    </section>

    <section class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[12px]">
                <thead class="bg-[#de773b]">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-white">No. Order</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-white">Deskripsi</th>
                        <th class="px-4 py-3 text-center text-[11px] font-semibold text-white">Dokumen</th>
                        <th class="px-4 py-3 text-center text-[11px] font-semibold text-white">LPJ / PPL</th>
                        <th class="px-4 py-3 text-center text-[11px] font-semibold text-white">Pembayaran</th>
                        <th class="px-4 py-3 text-center text-[11px] font-semibold text-white">Garansi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($documentRows as $row)
                        <tr class="transition hover:bg-[#fffaf6]">
                            <td class="px-4 py-4 align-top">
                                <div class="flex items-start gap-2">
                                    <div class="text-[13px] font-semibold text-slate-900">{{ $row['nomor_order'] }}</div>

                                    @if ($row['is_complete'])
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Lengkap</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Belum</span>
                                    @endif
                                </div>

                                @if (! empty($row['notification_number']))
                                    <div class="mt-1 text-[10px] text-slate-500">Notifikasi: {{ $row['notification_number'] }}</div>
                                @endif

                                <div class="mt-1 text-[10px] text-slate-400">{{ optional($row['created_at'])->format('Y-m-d') }}</div>
                            </td>

                            <td class="px-4 py-4 align-top text-[12px] text-slate-600">
                                <div class="max-w-[280px] font-medium text-slate-800">{{ $row['job_name'] ?: '-' }}</div>

                                @if (! empty($row['purchase_order_number']))
                                    <div class="mt-2 inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-medium text-slate-600">
                                        PO#: {{ $row['purchase_order_number'] }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-4 align-top text-center">
                                <div class="inline-flex items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2">
                                    <div class="flex flex-col items-center text-[10px]">
                                        @if (! empty($row['hpp_url']))
                                            <a href="{{ $row['hpp_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-slate-700 text-white shadow-sm">
                                                <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                            </a>
                                        @else
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-white text-slate-300 ring-1 ring-slate-200">
                                                <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                            </span>
                                        @endif
                                        <div class="mt-1 text-[10px] font-medium text-slate-600">HPP</div>
                                    </div>

                                    <div class="flex flex-col items-center text-[10px]">
                                        @if (! empty($row['po_url']))
                                            <a href="{{ $row['po_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-violet-600 text-white shadow-sm">
                                                <i data-lucide="receipt-text" class="h-3.5 w-3.5"></i>
                                            </a>
                                        @else
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-white text-slate-300 ring-1 ring-slate-200">
                                                <i data-lucide="receipt-text" class="h-3.5 w-3.5"></i>
                                            </span>
                                        @endif
                                        <div class="mt-1 text-[10px] font-medium text-slate-600">PO</div>
                                    </div>

                                    <div class="flex flex-col items-center text-[10px]">
                                        @if (! empty($row['bast_url']))
                                            <a href="{{ $row['bast_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                                                <i data-lucide="file-badge" class="h-3.5 w-3.5"></i>
                                            </a>
                                        @else
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-white text-slate-300 ring-1 ring-slate-200">
                                                <i data-lucide="file-badge" class="h-3.5 w-3.5"></i>
                                            </span>
                                        @endif
                                        <div class="mt-1 text-[10px] font-medium text-slate-600">BAST</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4 align-top text-center">
                                <div x-data="{ t: '1' }" class="inline-flex min-w-[150px] flex-col items-center rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2">
                                    <select x-model="t" class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-700">
                                        <option value="1">Termin 1</option>
                                        @unless ($row['is_without_warranty'] ?? false)
                                            <option value="2">Termin 2</option>
                                        @endunless
                                    </select>

                                    <div class="mt-2 flex items-center gap-1.5">
                                        <template x-if="t === '1'">
                                            <div class="flex items-center gap-1.5">
                                                @if (! empty($row['lpj_url_termin1']))
                                                    <a href="{{ $row['lpj_url_termin1'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm">
                                                        <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                                    </a>
                                                @else
                                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white text-slate-300 ring-1 ring-slate-200">
                                                        <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                                    </span>
                                                @endif

                                                @if (! empty($row['ppl_url_termin1']))
                                                    <a href="{{ $row['ppl_url_termin1'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-orange-600 text-white shadow-sm">
                                                        <i data-lucide="file-spreadsheet" class="h-3.5 w-3.5"></i>
                                                    </a>
                                                @else
                                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white text-slate-300 ring-1 ring-slate-200">
                                                        <i data-lucide="file-spreadsheet" class="h-3.5 w-3.5"></i>
                                                    </span>
                                                @endif
                                            </div>
                                        </template>

                                        @unless ($row['is_without_warranty'] ?? false)
                                        <template x-if="t === '2'">
                                            <div class="flex items-center gap-1.5">
                                                @if (! empty($row['lpj_url_termin2']))
                                                    <a href="{{ $row['lpj_url_termin2'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-500 text-white shadow-sm">
                                                        <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                                    </a>
                                                @else
                                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white text-slate-300 ring-1 ring-slate-200">
                                                        <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                                    </span>
                                                @endif

                                                @if (! empty($row['ppl_url_termin2']))
                                                    <a href="{{ $row['ppl_url_termin2'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-orange-500 text-white shadow-sm">
                                                        <i data-lucide="file-spreadsheet" class="h-3.5 w-3.5"></i>
                                                    </a>
                                                @else
                                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white text-slate-300 ring-1 ring-slate-200">
                                                        <i data-lucide="file-spreadsheet" class="h-3.5 w-3.5"></i>
                                                    </span>
                                                @endif
                                            </div>
                                        </template>
                                        @endunless
                                    </div>

                                    <div class="mt-2 flex items-center gap-4 text-[10px] font-medium text-slate-500">
                                        <span>LPJ</span>
                                        <span>PPL</span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4 align-top text-center">
                                <div class="mx-auto min-w-[150px] rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2 text-right">
                                    <div class="text-[10px] font-medium uppercase tracking-[0.12em] text-slate-400">Total</div>
                                    <div class="mt-1 text-[13px] font-bold text-slate-900">
                                        {{ $row['total_biaya'] > 0 ? 'Rp ' . number_format($row['total_biaya'], 0, ',', '.') : '-' }}
                                    </div>
                                    <div class="mt-2 text-[10px]">
                                        @if (($row['paid_percent'] ?? 0) > 0)
                                            <span class="inline-flex rounded-full bg-white px-2 py-1 font-medium text-slate-700 ring-1 ring-slate-200">
                                                {{ $row['paid_percent'] }}% - Rp {{ number_format($row['paid_amount'] ?? 0, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4 align-top text-center">
                                @php
                                    $startStr = ! empty($row['garansi_start']) ? \Illuminate\Support\Carbon::parse($row['garansi_start'])->format('Y-m-d') : null;
                                    $endStr = ! empty($row['garansi_end']) ? \Illuminate\Support\Carbon::parse($row['garansi_end'])->format('Y-m-d') : null;
                                @endphp
                                <div class="mx-auto min-w-[145px] rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2 text-[11px]">
                                    <div class="font-medium text-slate-600">{{ $startStr ?: '-' }}</div>
                                    <div class="mt-1 text-[10px] text-slate-400">{{ $endStr ?: '-' }}</div>
                                    <div class="mt-2">
                                        @if ($endStr)
                                            <span class="garansi-countdown inline-flex items-center rounded px-2 py-0.5 text-xs" data-end="{{ $endStr }}">
                                                Menghitung...
                                            </span>
                                        @elseif (($row['garansi_months'] ?? null) === 0)
                                            <span class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-[10px] text-slate-700">Tanpa Garansi</span>
                                        @else
                                            <span class="text-[10px] text-slate-400">Belum diatur</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-[12px] italic text-slate-500">Tidak ada data dokumen.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex justify-center border-t border-slate-200 px-4 py-4">
            {{ $documentRows->links('pagination::tailwind') }}
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function updateGaransiBadge(el) {
        if (!el) return;
        const endStr = el.getAttribute('data-end');
        if (!endStr) {
            el.className = 'text-slate-400 text-xs';
            el.textContent = '-';
            return;
        }

        const end = new Date(endStr + 'T00:00:00');
        const now = new Date();
        const msPerDay = 24 * 60 * 60 * 1000;
        const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const startOfEnd = new Date(end.getFullYear(), end.getMonth(), end.getDate());
        const diffDays = Math.floor((startOfEnd - startOfToday) / msPerDay);

        let text = '';
        let cls = 'inline-flex items-center rounded px-2 py-0.5 text-[10px] ';

        if (diffDays > 1) {
            text = diffDays + ' hari tersisa';
            cls += 'bg-emerald-50 text-emerald-700';
        } else if (diffDays === 1) {
            text = 'Besok';
            cls += 'bg-amber-50 text-amber-700';
        } else if (diffDays === 0) {
            text = 'Hari ini';
            cls += 'bg-amber-100 text-amber-800';
        } else {
            text = 'Habis';
            cls += 'bg-rose-50 text-rose-700';
        }

        el.className = cls;
        el.textContent = text;
    }

    document.querySelectorAll('.garansi-countdown').forEach(updateGaransiBadge);
});
</script>
