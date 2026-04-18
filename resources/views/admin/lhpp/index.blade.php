<x-layouts.admin title="BAST">
    @if (session('status'))
        <div id="admin-bast-status-alert" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    <div class="space-y-5">
        <section class="rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
            <div class="flex items-center gap-4">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                    <i data-lucide="file-text" class="h-[18px] w-[18px]"></i>
                </span>
                <div>
                    <h1 class="text-[1.3rem] font-bold leading-none tracking-tight text-slate-900">BAST</h1>
                    <p class="mt-1.5 text-[11px] text-slate-500">Cari dokumen berdasarkan nomor order, PO, unit kerja, dan progres dokumen BAST.</p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 overflow-x-auto">
                <form method="GET" action="{{ route('admin.lhpp.index') }}" class="flex min-w-[640px] items-center gap-2">
                    <div class="relative min-w-0 flex-1">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-[12px] w-[12px] -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari dokumen" class="w-full rounded-lg border border-slate-300 px-8 py-1.5 text-[10px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none">
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-blue-600 px-3 text-[10px] font-semibold text-white transition hover:bg-blue-700">
                            <i data-lucide="filter" class="h-[12px] w-[12px]"></i>
                            Terapkan
                        </button>
                        <a href="{{ route('admin.lhpp.index') }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-[10px] font-semibold text-slate-700 transition hover:bg-slate-50">
                            <i data-lucide="rotate-ccw" class="h-[12px] w-[12px]"></i>
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full table-fixed bg-white text-[11px] text-slate-700">
                    <colgroup>
                        <col class="w-[9%]">
                        <col class="w-[8%]">
                        <col class="w-[15%]">
                        <col class="w-[9%]">
                        <col class="w-[7%]">
                        <col class="w-[10%]">
                        <col class="w-[18%]">
                        <col class="w-[24%]">
                    </colgroup>
                    <thead class="bg-slate-100 text-slate-700 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold">Nomor Order</th>
                            <th class="px-4 py-2 text-left font-semibold">Nomor PO</th>
                            <th class="px-4 py-2 text-left font-semibold">Unit Kerja</th>
                            <th class="px-4 py-2 text-left font-semibold">Tanggal Selesai</th>
                            <th class="px-4 py-2 text-left font-semibold">Waktu</th>
                            <th class="px-4 py-2 text-right font-semibold">Total Biaya</th>
                            <th class="px-4 py-2 text-center font-semibold">Garansi</th>
                            <th class="px-4 py-2 text-center font-semibold">Dokumen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($lhpps as $lhpp)
                            @php
                                $nomorOrder = $lhpp->nomor_order ?: ($lhpp->order?->nomor_order ?? '-');
                                $nomorPo = $lhpp->purchase_order_number ?: ($lhpp->purchaseOrder?->purchase_order_number ?? '-');
                                $terminTwo = $lhpp->terminTwo;
                                $pdfRefreshToken = now()->timestamp;
                                $seksi = $lhpp->seksi ?: ($lhpp->order?->seksi ?? '-');
                                $unitKerja = $lhpp->unit_kerja ?: ($lhpp->order?->unit_kerja ?? '-');
                                $tanggalSelesai = $lhpp->tanggal_selesai_pekerjaan
                                    ? $lhpp->tanggal_selesai_pekerjaan->format('d-m-Y')
                                    : '-';
                                $waktuPengerjaan = ($lhpp->tanggal_mulai_pekerjaan && $lhpp->tanggal_selesai_pekerjaan)
                                    ? ($lhpp->tanggal_mulai_pekerjaan->diffInDays($lhpp->tanggal_selesai_pekerjaan) + 1).' Hari'
                                    : '-';
                                $totalBiaya = (float) ($lhpp->total_aktual_biaya ?? 0);
                                $garansiMonths = [0, 1, 3, 6, 12];
                                $garansiValue = $lhpp->garansi?->garansi_months;
                                $qualityControlStatus = $lhpp->quality_control_status ?: 'pending';
                                $qualityControlSelectClass = match ($qualityControlStatus) {
                                    'approved' => 'border-emerald-300 bg-emerald-50 text-emerald-700',
                                    'rejected' => 'border-rose-300 bg-rose-50 text-rose-700',
                                    default => 'border-slate-300 bg-white text-slate-700',
                                };
                                $qualityControlHelper = match ($qualityControlStatus) {
                                    'approved' => 'Dokumen lolos quality control.',
                                    'rejected' => 'Dokumen ditolak dan perlu revisi.',
                                    default => 'Pilih approve atau reject untuk quality control.',
                                };
                                $qualityControlHelperClass = match ($qualityControlStatus) {
                                    'approved' => 'text-emerald-600',
                                    'rejected' => 'text-rose-600',
                                    default => 'text-slate-500',
                                };
                                $termin1Paid = ($lhpp->termin1_status ?? 'belum') === 'sudah';
                                $termin2Paid = ($lhpp->termin2_status ?? 'belum') === 'sudah';
                                $termin1Amount = $termin1Paid
                                    ? (float) ($lhpp->termin_1_nilai ?? round($totalBiaya * 0.95))
                                    : null;
                                $termin2Amount = $termin2Paid
                                    ? (float) ($lhpp->termin_2_nilai ?? round($totalBiaya * 0.05))
                                    : null;
                                $hasTerminTwo = filled($terminTwo?->id);
                            @endphp

                            <tr class="transition duration-150 hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    <div class="inline-flex min-w-[92px] items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-[13px] font-bold text-slate-900 shadow-sm">
                                        {{ $nomorOrder }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-[11px] font-semibold text-slate-700 shadow-sm">
                                        {{ $nomorPo }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-[10px] text-slate-600 shadow-sm">
                                        <div class="font-semibold leading-snug text-slate-800">{{ $seksi }}</div>
                                        <div class="mt-1 border-t border-slate-200 pt-1.5 leading-snug">{{ $unitKerja }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top font-medium">{{ $tanggalSelesai }}</td>
                                <td class="px-4 py-3 align-top font-medium">{{ $waktuPengerjaan }}</td>
                                <td class="px-4 py-3 align-top text-right">
                                    <div class="font-semibold text-slate-900">Rp{{ number_format($totalBiaya, 0, ',', '.') }}</div>
                                    @if (!is_null($termin1Amount))
                                        <div class="mt-1 text-[10px] font-medium text-emerald-600">
                                            Termin 1: Rp{{ number_format($termin1Amount, 0, ',', '.') }}
                                        </div>
                                    @endif
                                    @if (!is_null($termin2Amount))
                                        <div class="mt-1 text-[10px] font-medium text-sky-600">
                                            Termin 2: Rp{{ number_format($termin2Amount, 0, ',', '.') }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="mx-auto flex max-w-[180px] flex-col gap-2">
                                        <form method="POST" action="{{ route('admin.lhpp.garansi', ['lhppId' => $lhpp->id]) }}" class="flex flex-col gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="search" value="{{ $search }}">
                                            <input type="hidden" name="page" value="{{ $lhpps->currentPage() }}">
                                            <select name="garansi_months" class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-[11px] text-slate-700">
                                                <option value="">-- Garansi (Bulan) --</option>
                                                @foreach ($garansiMonths as $month)
                                                    <option value="{{ $month }}" @selected((string) $garansiValue === (string) $month)>{{ $month }} Bulan</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-indigo-700">
                                                Simpan
                                            </button>
                                        </form>
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-wrap items-start justify-center gap-3">
                                        <form method="POST" action="{{ route('admin.lhpp.quality-control', ['lhppId' => $lhpp->id]) }}" class="w-[190px] space-y-1.5">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="search" value="{{ $search }}">
                                            <input type="hidden" name="page" value="{{ $lhpps->currentPage() }}">
                                            <select name="quality_control_status" onchange="this.form.submit()" class="w-full rounded-lg border px-2.5 py-1.5 text-[11px] font-semibold focus:outline-none {{ $qualityControlSelectClass }}">
                                                <option value="pending" @selected($qualityControlStatus === 'pending')>Pilih Aksi</option>
                                                <option value="approved" @selected($qualityControlStatus === 'approved')>Setujui</option>
                                                <option value="rejected" @selected($qualityControlStatus === 'rejected')>Tolak</option>
                                            </select>
                                            <p class="text-[10px] leading-snug {{ $qualityControlHelperClass }}">{{ $qualityControlHelper }}</p>
                                        </form>

                                        <div class="flex flex-wrap items-center justify-center gap-1.5">
                                            <a href="{{ route('admin.lhpp.pdf', ['nomorOrder' => $lhpp->nomor_order, 'termin' => 'termin-1']) }}?refresh={{ $pdfRefreshToken }}"
                                               target="_blank"
                                               rel="noopener"
                                               title="Lihat BAST Termin 1 (PDF)"
                                               aria-label="Lihat BAST Termin 1 PDF"
                                               class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-[9px] font-semibold text-rose-700 shadow-sm transition hover:bg-rose-100">
                                                <i data-lucide="file-text" class="h-3 w-3"></i>
                                                BAST Termin 1
                                            </a>

                                            @if ($hasTerminTwo)
                                                <a href="{{ route('admin.lhpp.pdf', ['nomorOrder' => $terminTwo->nomor_order, 'termin' => 'termin-2']) }}?refresh={{ $pdfRefreshToken }}"
                                                   target="_blank"
                                                   rel="noopener"
                                                   title="Lihat BAST Termin 2 (PDF)"
                                                   aria-label="Lihat BAST Termin 2 PDF"
                                                   class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1 text-[9px] font-semibold text-sky-700 shadow-sm transition hover:bg-sky-100">
                                                    <i data-lucide="file-text" class="h-3 w-3"></i>
                                                    BAST Termin 2
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-[11px] text-slate-500">Belum ada data BAST yang tersedia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($lhpps->hasPages())
                <div class="mt-4 border-t border-slate-200 px-4 py-4">
                    {{ $lhpps->links() }}
                </div>
            @endif
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const statusAlert = document.getElementById('admin-bast-status-alert');

            if (statusAlert?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: statusAlert.dataset.message,
                    timer: 1800,
                    showConfirmButton: false,
                });
            }
        });
    </script>
</x-layouts.admin>
