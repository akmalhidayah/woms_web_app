        @php
            $baseSel = 'min-h-[26px] text-[10px] leading-[1.3] px-2 pr-9 rounded-[6px] appearance-none focus:ring-1 truncate';
            $baseBtn = 'min-h-[26px] text-[10px] leading-[1.3] px-3 rounded-[6px]';

            $selOrange = $baseSel.' bg-orange-100 text-orange-800 border border-orange-300 focus:ring-orange-400 focus:border-orange-400';
            $selBlue = $baseSel.' bg-sky-100 text-sky-800 border border-sky-300 focus:ring-sky-400 focus:border-sky-400';
            $selSlate = $baseSel.' bg-slate-100 text-slate-800 border border-slate-300 focus:ring-slate-400 focus:border-slate-400';
            $btnPrimary = $baseBtn.' bg-[#ca642f] text-white hover:bg-[#b85b2b]';
            $btnGhost = $baseBtn.' border border-slate-300 text-slate-700 hover:bg-slate-50';

            $filters = $filters ?? [
                'search' => '',
                'unit_kerja' => '',
                'purchase_order_number' => '',
                'termin_status' => 'all',
            ];
            $units = collect($units ?? []);
            $pos = collect($pos ?? []);
            $lhpps = $lhpps ?? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 8, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
            $pendingTerminOneOrders = collect($pendingTerminOneOrders ?? []);
            $activeTokens = collect($activeTokens ?? []);
        @endphp

        <div class="space-y-5">
            <section class="overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white px-5 py-5 text-slate-900 shadow-sm">
                <h1 class="text-[2rem] font-black leading-none tracking-tight text-slate-900">BAST / LHPP</h1>
            </section>

            <div class="rounded-[1.6rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-[13px] font-bold text-slate-900">Daftar LHPP Kontrak PKM</h2>
                        <p class="mt-1 text-[11px] text-slate-500">Monitoring laporan hasil pekerjaan per notifikasi dan kontrak PKM.</p>
                    </div>

                    <a href="{{ route('pkm.lhpp.create') }}"
                        class="{{ $btnPrimary }} inline-flex items-center gap-2 rounded-md px-3 py-2 text-[12px] font-semibold shadow-sm transition">
                        <i data-lucide="plus-circle" class="h-3.5 w-3.5"></i>
                        Buat BAST Termin 1
                    </a>
                </div>

                @if ($pendingTerminOneOrders->isNotEmpty())
                    <div class="mb-3 rounded-[1.2rem] border border-amber-200 bg-gradient-to-r from-amber-50 to-white px-3 py-3 text-slate-800 shadow-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                        <i data-lucide="triangle-alert" class="h-3.5 w-3.5"></i>
                                    </span>
                                    <div class="text-[12px] font-black text-amber-950">Order Belum Dibuatkan BAST Termin 1</div>
                                    <span class="inline-flex rounded-full border border-amber-200 bg-white px-2 py-0.5 text-[10px] font-bold text-amber-800">
                                        {{ $pendingTerminOneOrders->count() }} order
                                    </span>
                                </div>
                                <p class="mt-1 pl-9 text-[10px] leading-5 text-amber-800">
                                    Sudah memenuhi syarat BAST, tapi Termin 1-nya belum dibuat.
                                </p>
                            </div>
                        </div>

                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($pendingTerminOneOrders as $pendingOrder)
                                <div class="min-w-[210px] rounded-xl border border-amber-200 bg-white px-2.5 py-2 text-[10px] shadow-sm">
                                    <div class="font-black text-slate-900">{{ $pendingOrder['nomor_order'] }}</div>
                                    <div class="mt-0.5 text-slate-600">
                                        {{ $pendingOrder['purchase_order_number'] !== '' ? 'PO: '.$pendingOrder['purchase_order_number'] : 'PO belum terbaca' }}
                                    </div>
                                    <div class="mt-0.5 truncate text-slate-500">
                                        {{ $pendingOrder['seksi'] !== '' ? $pendingOrder['seksi'] : ($pendingOrder['unit_kerja'] !== '' ? $pendingOrder['unit_kerja'] : '-') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form action="{{ route('pkm.lhpp.index') }}" method="GET" class="flex flex-wrap items-center gap-2 overflow-x-auto whitespace-nowrap">
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-2 top-1/2 h-3 w-3 -translate-y-1/2 text-orange-500"></i>
                        <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Cari Nomor Notif / PO / Unit..." class="{{ $selOrange }} w-64 pl-6" />
                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-orange-600">⌕</span>
                    </div>

                    <div class="relative">
                        <select name="unit_kerja" class="{{ $selBlue }} w-48">
                            <option value="">Semua Unit Kerja</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit }}" @selected($filters['unit_kerja'] === $unit)>{{ \Illuminate\Support\Str::limit($unit, 40) }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-sky-700">▾</span>
                    </div>

                    <div class="relative">
                        <select name="purchase_order_number" class="{{ $selSlate }} w-52">
                            <option value="">Semua Nomor PO</option>
                            @foreach ($pos as $po)
                                <option value="{{ $po }}" @selected($filters['purchase_order_number'] === $po)>{{ $po }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-700">▾</span>
                    </div>

                    <div class="relative">
                        <select name="termin_status" class="{{ $selSlate }} w-52">
                            <option value="all" @selected($filters['termin_status'] === 'all')>Semua Status Termin</option>
                            <option value="t1_paid" @selected($filters['termin_status'] === 't1_paid')>Termin 1 - Sudah</option>
                            <option value="t1_unpaid" @selected($filters['termin_status'] === 't1_unpaid')>Termin 1 - Belum</option>
                            <option value="t2_paid" @selected($filters['termin_status'] === 't2_paid')>Termin 2 - Sudah</option>
                            <option value="t2_unpaid" @selected($filters['termin_status'] === 't2_unpaid')>Termin 2 - Belum</option>
                        </select>
                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-700">▾</span>
                    </div>

                    <button type="submit" class="{{ $btnPrimary }} ml-auto inline-flex items-center rounded-md">
                        <i data-lucide="filter" class="mr-1 h-3 w-3"></i>
                        Terapkan
                    </button>
                    <a href="{{ route('pkm.lhpp.index') }}" class="{{ $btnGhost }} inline-flex items-center rounded-md">
                        <i data-lucide="rotate-ccw" class="mr-1 h-3 w-3"></i>
                        Reset
                    </a>
                </form>
            </div>

            <div class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-slate-200 text-[11px] text-slate-800">
                        <thead class="border-b border-slate-200 bg-slate-50 uppercase text-slate-600">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Order / PO</th>
                                <th class="px-3 py-2 text-left font-semibold">Unit Kerja</th>
                                <th class="px-3 py-2 text-left font-semibold">Tanggal Selesai</th>
                                <th class="px-3 py-2 text-right font-semibold">Total Biaya</th>
                                <th class="px-3 py-2 text-left font-semibold">Status LHPP</th>
                                <th class="px-3 py-2 text-left font-semibold">Status Payment</th>
                                <th class="px-3 py-2 text-center font-semibold w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($lhpps as $row)
                                @php
                                    $t1 = $row->termin1_status ?? null;
                                    $t2 = $row->termin2_status ?? null;
                                    $terminTwo = $row->terminTwo;

                                    $hasUserSign = ! empty($row->manager_signature_requesting) || ! empty($row->manager_signature_requesting_user_id);
                                    $hasWsSign = ! empty($row->manager_signature) || ! empty($row->manager_signature_user_id);
                                    $hasPkmSign = ! empty($row->manager_pkm_signature) || ! empty($row->manager_pkm_signature_user_id);

                                    if (! $hasUserSign && ! $hasWsSign && ! $hasPkmSign) {
                                        $signStage = 'waiting_user';
                                    } elseif ($hasUserSign && ! $hasWsSign && ! $hasPkmSign) {
                                        $signStage = 'waiting_workshop';
                                    } elseif ($hasUserSign && $hasWsSign && ! $hasPkmSign) {
                                        $signStage = 'waiting_pkm';
                                    } elseif ($hasUserSign && $hasWsSign && $hasPkmSign) {
                                        $signStage = 'completed';
                                    } else {
                                        $signStage = 'partial';
                                    }

                                    $signLabel = match ($signStage) {
                                        'waiting_user' => 'Menunggu TTD Manager User',
                                        'waiting_workshop' => 'Menunggu TTD Manager Workshop',
                                        'waiting_pkm' => 'Menunggu TTD Manager PKM',
                                        'completed' => 'Dokumen Telah di Tandatangani',
                                        'partial' => 'Proses Tanda Tangan',
                                        default => 'Proses Tanda Tangan',
                                    };

                                    $signClr = match ($signStage) {
                                        'waiting_user' => 'bg-slate-100 text-slate-800 ring-slate-200',
                                        'waiting_workshop' => 'bg-amber-100 text-amber-800 ring-amber-200',
                                        'waiting_pkm' => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
                                        'completed' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
                                        'partial' => 'bg-sky-100 text-sky-800 ring-sky-200',
                                        default => 'bg-slate-100 text-slate-800 ring-slate-200',
                                    };

                                    $key = (string) $row->nomor_order;
                                    $tok = $activeTokens->get($key);
                                    $hasTok = (bool) $tok;
                                    $isExpired = $hasTok && $tok->expires_at && $tok->expires_at->isPast();

                                    $waktuPengerjaan = null;
                                    if ($row->tanggal_mulai_pekerjaan && $row->tanggal_selesai_pekerjaan) {
                                        $waktuPengerjaan = \Carbon\Carbon::parse($row->tanggal_mulai_pekerjaan)->diffInDays(
                                            \Carbon\Carbon::parse($row->tanggal_selesai_pekerjaan)
                                        ) + 1;
                                    }
                                    $totalBiaya = (float) ($row->total_aktual_biaya ?? 0);
                                    $termin1Paid = $t1 === 'sudah';
                                    $termin2Paid = $t2 === 'sudah';
                                    $termin1Amount = $termin1Paid
                                        ? (float) ($row->termin_1_nilai ?? round($totalBiaya * 0.95))
                                        : null;
                                    $termin2Amount = $termin2Paid
                                        ? (float) ($row->termin_2_nilai ?? round($totalBiaya * 0.05))
                                        : null;
                                    $terminTwoExists = filled($terminTwo?->id);
                                @endphp

                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-3 py-2">
                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-[10px] text-slate-600 shadow-sm">
                                            <div class="font-semibold leading-tight text-slate-900">{{ $row->nomor_order }}</div>
                                            @if (filled($row->order?->notifikasi))
                                                <div class="mt-1 leading-tight text-slate-900">{{ $row->order?->notifikasi }}</div>
                                            @endif
                                            <div class="mt-1 border-t border-slate-200 pt-1 leading-tight">
                                                <span class="font-medium text-slate-700">{{ $row->purchase_order_number ?? '-' }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-3 py-2">
                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-[10px] text-slate-600 shadow-sm">
                                            <div class="font-semibold leading-tight text-slate-700">{{ $row->seksi ?: '-' }}</div>
                                            <div class="mt-1 border-t border-slate-200 pt-1 leading-tight">{{ $row->unit_kerja ?: '-' }}</div>
                                        </div>
                                    </td>

                                    <td class="px-3 py-2">
                                        @if ($row->tanggal_selesai_pekerjaan)
                                            {{ \Carbon\Carbon::parse($row->tanggal_selesai_pekerjaan)->format('d-m-Y') }}
                                            ({{ $waktuPengerjaan ? $waktuPengerjaan.' Hari' : '-' }})
                                        @else
                                            <span class="text-[10px] text-slate-400">-</span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2 text-right">
                                        <div class="font-semibold">Rp {{ number_format($totalBiaya, 2, ',', '.') }}</div>
                                        @if (! is_null($termin1Amount))
                                            <div class="mt-1 text-[10px] font-medium text-emerald-600">
                                                Termin 1: Rp {{ number_format($termin1Amount, 0, ',', '.') }}
                                            </div>
                                        @endif
                                        @if (! is_null($termin2Amount))
                                            <div class="mt-1 text-[10px] font-medium text-sky-600">
                                                Termin 2: Rp {{ number_format($termin2Amount, 0, ',', '.') }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2">
                                        <div class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] ring-1 {{ $signClr }}">
                                            <i data-lucide="pen-tool" class="h-3 w-3"></i>
                                            {{ $signLabel }}
                                        </div>

                                        @if ($hasTok && $signStage !== 'completed')
                                            @if (! $isExpired)
                                                <div class="mt-1 flex items-center gap-2 text-[10px]">
                                                    <button type="button" class="copy-next-link inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-slate-700 ring-1 ring-slate-200 hover:bg-slate-200" data-link="{{ route('pkm.lhpp.index') }}">
                                                        <i data-lucide="copy" class="h-3 w-3"></i> Salin Link Approve
                                                    </button>
                                                    <span class="font-medium text-slate-700">kadaluarsa: {{ $tok->expires_at?->format('d/m H:i') }}</span>
                                                </div>
                                            @else
                                                <div class="mt-1 inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-0.5 text-[10px] text-amber-800 ring-1 ring-amber-200">
                                                    <i data-lucide="clock-3" class="h-3 w-3"></i> Token kedaluwarsa
                                                </div>
                                            @endif
                                        @endif
                                    </td>

                                    <td class="px-3 py-2">
                                        <div class="flex flex-col gap-1">
                                            <div>
                                                <span class="text-[10px] text-slate-600">Termin 1:</span>
                                                @if ($t1 === 'sudah')
                                                    <span class="ml-1 inline-block rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] text-emerald-800">Sudah Dibayar</span>
                                                @else
                                                    <span class="ml-1 inline-block rounded-md bg-amber-100 px-2 py-0.5 text-[10px] text-amber-800">Belum Dibayar</span>
                                                @endif
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-slate-600">Termin 2:</span>
                                                @if ($t2 === 'sudah')
                                                    <span class="ml-1 inline-block rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] text-emerald-800">Sudah Dibayar</span>
                                                @else
                                                    <span class="ml-1 inline-block rounded-md bg-amber-100 px-2 py-0.5 text-[10px] text-amber-800">Belum Dibayar</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-3 py-2 text-center">
                                        <div x-data="{ selectedTerm: 'termin_1' }" class="flex flex-col items-center gap-2">
                                            <div class="relative w-[118px]">
                                                <select x-model="selectedTerm" class="w-full appearance-none rounded-md border border-slate-300 bg-white py-1.5 pl-2 pr-7 text-[10px] font-semibold text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                                    <option value="termin_1">Termin 1</option>
                                                    <option value="termin_2">Termin 2</option>
                                                </select>
                                                <i data-lucide="chevron-down" class="pointer-events-none absolute right-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-500"></i>
                                            </div>

                                            <div x-show="selectedTerm === 'termin_1'" class="flex items-center justify-center gap-1">
                                                <a href="{{ route('pkm.lhpp.edit', ['nomorOrder' => $row->nomor_order, 'termin' => 'termin-1']) }}" class="pkm-lhpp-action-btn bg-emerald-500 hover:bg-emerald-600" title="Edit LHPP">
                                                    <i data-lucide="square-pen" class="h-3.5 w-3.5"></i>
                                                </a>
                                                <a href="{{ route('pkm.lhpp.pdf', ['nomorOrder' => $row->nomor_order, 'termin' => 'termin-1']) }}" target="_blank" rel="noopener noreferrer" class="pkm-lhpp-action-btn bg-blue-500 hover:bg-blue-600" title="Download PDF LHPP">
                                                    <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                                </a>
                                                <form action="{{ route('pkm.lhpp.destroy', ['nomorOrder' => $row->nomor_order, 'termin' => 'termin-1']) }}" method="POST" class="inline-block pkm-lhpp-delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="pkm-lhpp-action-btn bg-red-500 hover:bg-red-600 pkm-lhpp-delete-button" title="Hapus LHPP">
                                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                                    </button>
                                                </form>
                                            </div>

                                            <div x-show="selectedTerm === 'termin_2'" class="w-full">
                                                @if ($terminTwoExists)
                                                    <div class="flex items-center justify-center gap-1">
                                                        <a href="{{ route('pkm.lhpp.edit', ['nomorOrder' => $row->nomor_order, 'termin' => 'termin-2']) }}" class="pkm-lhpp-action-btn bg-emerald-500 hover:bg-emerald-600" title="Edit BAST Termin 2">
                                                            <i data-lucide="square-pen" class="h-3.5 w-3.5"></i>
                                                        </a>
                                                        <a href="{{ route('pkm.lhpp.pdf', ['nomorOrder' => $row->nomor_order, 'termin' => 'termin-2']) }}" target="_blank" rel="noopener noreferrer" class="pkm-lhpp-action-btn bg-blue-500 hover:bg-blue-600" title="Download PDF BAST Termin 2">
                                                            <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                                        </a>
                                                        <form action="{{ route('pkm.lhpp.destroy', ['nomorOrder' => $row->nomor_order, 'termin' => 'termin-2']) }}" method="POST" class="inline-block pkm-lhpp-delete-form">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="pkm-lhpp-action-btn bg-red-500 hover:bg-red-600 pkm-lhpp-delete-button" title="Hapus BAST Termin 2">
                                                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @elseif ($termin1Paid)
                                                    <a href="{{ route('pkm.lhpp.termin2.create', ['nomorOrder' => $row->nomor_order]) }}" class="block w-full rounded-md bg-[#ca642f] px-3 py-1.5 text-center text-[10px] font-bold text-white transition hover:bg-[#b85b2b]">
                                                        Buat BAST Termin 2
                                                    </a>
                                                @else
                                                    <button type="button" class="w-full cursor-not-allowed rounded-md bg-slate-200 px-3 py-1.5 text-[10px] font-bold text-slate-500">
                                                        Termin 1 Belum Dibayar
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-[11px] text-slate-500">
                                        Belum ada data LHPP.
                                        <a href="{{ route('pkm.lhpp.create') }}" class="text-[#ca642f] underline">Buat LHPP baru</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 px-4 pb-4 text-center text-[10px]">
                    {{ $lhpps->appends(request()->query())->links() }}
                </div>
            </div>
        </div>

        <style>
            .pkm-lhpp-action-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 26px;
                height: 26px;
                border-radius: 6px;
                color: white;
                transition: .2s;
            }

            .pkm-lhpp-table th,
            .pkm-lhpp-table td {
                white-space: nowrap;
            }
        </style>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                function copyTextToClipboard(text) {
                    if (navigator.clipboard && window.isSecureContext) {
                        return navigator.clipboard.writeText(text);
                    }

                    const temp = document.createElement('textarea');
                    temp.value = text;
                    temp.setAttribute('readonly', '');
                    temp.style.position = 'absolute';
                    temp.style.left = '-9999px';
                    document.body.appendChild(temp);
                    temp.select();
                    temp.setSelectionRange(0, temp.value.length);
                    const ok = document.execCommand('copy');
                    document.body.removeChild(temp);

                    return ok ? Promise.resolve() : Promise.reject();
                }

                document.querySelectorAll('.copy-next-link').forEach((button) => {
                    button.addEventListener('click', (event) => {
                        const link = event.currentTarget.getAttribute('data-link');

                        if (! link) {
                            return;
                        }

                        copyTextToClipboard(link).then(() => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Tersalin',
                                text: 'Link approval LHPP disalin',
                                timer: 1500,
                                showConfirmButton: false,
                            });
                        }).catch(() => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: 'Tidak dapat menyalin link',
                            });
                        });
                    });
                });

                document.querySelectorAll('.pkm-lhpp-delete-button').forEach((button) => {
                    button.addEventListener('click', (event) => {
                        event.preventDefault();
                        const form = button.closest('.pkm-lhpp-delete-form');
                        Swal.fire({
                            title: 'Hapus LHPP ini?',
                            text: 'Data BAST / LHPP ini akan dihapus permanen.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#dc2626',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: 'Ya, Hapus',
                            cancelButtonText: 'Batal',
                        }).then((result) => {
                            if (result.isConfirmed && form) {
                                form.submit();
                            }
                        });
                    });
                });

            });
        </script>
