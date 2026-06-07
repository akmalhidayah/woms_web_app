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

        <div class="space-y-4">
            <section class="overflow-hidden rounded-[1.2rem] border border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm">
                <h1 class="text-[1.15rem] font-black leading-none tracking-tight text-slate-900">BAST / LHPP</h1>
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
                    <div class="mb-3 overflow-hidden rounded-lg border border-amber-200 bg-white">
                        <div class="flex items-center justify-between gap-3 border-b border-amber-200 bg-amber-50 px-3 py-2">
                            <div class="text-[10px] font-bold text-amber-900">Belum Dibuatkan BAST T1</div>
                            <span class="rounded-full bg-white px-2 py-0.5 text-[9px] font-bold text-amber-800 ring-1 ring-amber-200">
                                {{ $pendingTerminOneOrders->count() }} order
                            </span>
                        </div>
                        <div class="grid grid-cols-[150px_minmax(0,1fr)] bg-slate-100 px-3 py-1.5 text-[9px] font-semibold uppercase tracking-[0.08em] text-slate-600">
                            <div>Order</div>
                            <div>Detail Pekerjaan</div>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @foreach ($pendingTerminOneOrders as $pendingOrder)
                                <div class="grid grid-cols-[150px_minmax(0,1fr)] gap-3 px-3 py-2 text-[9px]">
                                    <div class="space-y-0.5">
                                        <div class="font-black text-slate-900">{{ $pendingOrder['nomor_order'] }}</div>
                                        <div class="text-blue-600">Notif : {{ $pendingOrder['notifikasi'] !== '' ? $pendingOrder['notifikasi'] : '-' }}</div>
                                        <div class="text-blue-600">PO : {{ $pendingOrder['purchase_order_number'] !== '' ? $pendingOrder['purchase_order_number'] : '-' }}</div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-[10px] font-bold text-slate-900">
                                            {{ $pendingOrder['deskripsi_pekerjaan'] !== '' ? $pendingOrder['deskripsi_pekerjaan'] : '-' }}
                                        </div>
                                        <div class="mt-0.5 text-slate-500">Unit: {{ $pendingOrder['unit_kerja'] !== '' ? $pendingOrder['unit_kerja'] : '-' }}</div>
                                        <div class="mt-0.5 truncate text-blue-600">Seksi: {{ $pendingOrder['seksi'] !== '' ? $pendingOrder['seksi'] : '-' }}</div>
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
                    <table class="min-w-full table-fixed border border-slate-200 text-[11px] text-slate-800">
                        <colgroup>
                            <col class="w-[14%]">
                            <col class="w-[18%]">
                            <col class="w-[11%]">
                            <col class="w-[12%]">
                            <col class="w-[20%]">
                            <col class="w-[14%]">
                            <col class="w-[11%]">
                        </colgroup>
                        <thead class="border-b border-slate-200 bg-slate-50 uppercase text-slate-600">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Order</th>
                                <th class="px-3 py-2 text-left font-semibold">Detail Pekerjaan</th>
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
                                    $isWithoutWarranty = (int) ($row->garansi?->garansi_months ?? -1) === 0;

                                    $activeSignature = $row->activeSignature ?: $row->signatures->first(fn (\App\Models\LhppBastSignature $signature): bool => $signature->isPending());
                                    $activeApprovalLink = $activeSignature?->approvalUrl();
                                    $isExpired = $activeSignature?->tokenExpired() ?? false;
                                    $approvalStatus = $row->approval_status ?? \App\Models\LhppBast::APPROVAL_IN_REVIEW;
                                    $isDiropsPending = $activeSignature?->role_key === 'dirops' && ! $isExpired;
                                    $qualityControlStatus = $row->quality_control_status ?: 'pending';
                                    $diropsSignedDocumentSignature = $row->signatures->first(
                                        fn (\App\Models\LhppBastSignature $signature): bool => $signature->role_key === 'dirops' && $signature->hasUploadedSignedDocument()
                                    );
                                    $diropsSignedDocumentUrl = $diropsSignedDocumentSignature
                                        ? route('pkm.lhpp.dirops-document.show', ['lhppId' => $row->id])
                                        : null;

                                    $terminTwoActiveSignature = $terminTwo?->activeSignature ?: ($terminTwo?->signatures?->first(fn (\App\Models\LhppBastSignature $signature): bool => $signature->isPending()));
                                    $terminTwoActiveApprovalLink = $terminTwoActiveSignature?->approvalUrl();
                                    $terminTwoIsExpired = $terminTwoActiveSignature?->tokenExpired() ?? false;
                                    $terminTwoIsDiropsPending = $terminTwoActiveSignature?->role_key === 'dirops' && ! $terminTwoIsExpired;
                                    $terminTwoApprovalStatus = $terminTwo?->approval_status ?? \App\Models\LhppBast::APPROVAL_IN_REVIEW;
                                    $terminTwoDiropsSignedDocumentSignature = $terminTwo?->signatures?->first(
                                        fn (\App\Models\LhppBastSignature $signature): bool => $signature->role_key === 'dirops' && $signature->hasUploadedSignedDocument()
                                    );
                                    $terminTwoDiropsSignedDocumentUrl = $terminTwoDiropsSignedDocumentSignature
                                        ? route('pkm.lhpp.dirops-document.show', ['lhppId' => $terminTwo->id])
                                        : null;

                                    $signLabel = match ($approvalStatus) {
                                        \App\Models\LhppBast::APPROVAL_APPROVED => 'Dokumen Telah di Tandatangani',
                                        \App\Models\LhppBast::APPROVAL_REJECTED => 'Dokumen Ditolak',
                                        default => $activeSignature
                                            ? 'Menunggu TTD '.$activeSignature->role_label
                                            : ($qualityControlStatus === 'approved' ? 'Proses Tanda Tangan' : 'Menunggu QC Admin'),
                                    };

                                    $signClr = match ($approvalStatus) {
                                        \App\Models\LhppBast::APPROVAL_APPROVED => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
                                        \App\Models\LhppBast::APPROVAL_REJECTED => 'bg-rose-100 text-rose-800 ring-rose-200',
                                        default => $activeSignature?->role_key === 'dirops'
                                            ? 'bg-orange-100 text-orange-800 ring-orange-200'
                                            : 'bg-sky-100 text-sky-800 ring-sky-200',
                                    };

                                    $waktuPengerjaan = null;
                                    if ($row->tanggal_mulai_pekerjaan && $row->tanggal_selesai_pekerjaan) {
                                        $waktuPengerjaan = \Carbon\Carbon::parse($row->tanggal_mulai_pekerjaan)->diffInDays(
                                            \Carbon\Carbon::parse($row->tanggal_selesai_pekerjaan)
                                        ) + 1;
                                    }
                                    $totalBiaya = (float) ($row->total_aktual_biaya ?? 0);
                                    $termin1Paid = $t1 === 'sudah';
                                    $termin2Paid = ! $isWithoutWarranty && $t2 === 'sudah';
                                    $termin1Amount = $termin1Paid
                                        ? (float) ($isWithoutWarranty ? $totalBiaya : ($row->termin_1_nilai ?? round($totalBiaya * 0.95)))
                                        : null;
                                    $termin2Amount = $termin2Paid
                                        ? (float) ($row->termin_2_nilai ?? round($totalBiaya * 0.05))
                                        : null;
                                    $terminTwoExists = ! $isWithoutWarranty && filled($terminTwo?->id);
                                    $approvalProgress = $row->approvalProgressPercent();
                                    $signedCount = $row->approvalSignedCount();
                                    $totalSteps = $row->approvalStepCount();
                                    $isApprovalComplete = $row->approvalCompleted();
                                    $approvalSummaryLabel = $isApprovalComplete
                                        ? 'Semua approver selesai'
                                        : ($activeSignature?->role_label ? 'Menunggu '.$activeSignature->role_label : ($qualityControlStatus === 'approved' ? 'Menunggu approval' : 'Menunggu QC Admin'));
                                    $approvalChecklist = $row->signatures->map(function (\App\Models\LhppBastSignature $signature): array {
                                        return [
                                            'label' => $signature->role_label,
                                            'name' => $signature->signer_name_snapshot ?: '-',
                                            'status' => $signature->status,
                                        ];
                                    })->values();
                                    $activeApprovalModalActions = [
                                        'link' => $activeApprovalLink && $activeSignature?->role_key !== 'dirops' && ! $isExpired ? $activeApprovalLink : '',
                                        'resend_url' => $activeApprovalLink && $activeSignature?->role_key !== 'dirops' && ! $isExpired ? route('pkm.lhpp.approval.resend', ['lhppId' => $row->id]) : '',
                                    ];
                                    $terminTwoProgress = $terminTwo?->approvalProgressPercent() ?? 0;
                                    $terminTwoSignedCount = $terminTwo?->approvalSignedCount() ?? 0;
                                    $terminTwoTotalSteps = $terminTwo?->approvalStepCount() ?? 0;
                                    $terminTwoSummaryLabel = $terminTwo
                                        ? ($terminTwo->approvalCompleted()
                                            ? 'Semua approver T2 selesai'
                                            : ($terminTwoActiveSignature?->role_label ? 'Menunggu '.$terminTwoActiveSignature->role_label : 'Menunggu approval T2'))
                                        : '';
                                    $terminTwoChecklist = $terminTwo
                                        ? $terminTwo->signatures->map(function (\App\Models\LhppBastSignature $signature): array {
                                            return [
                                                'label' => $signature->role_label,
                                                'name' => $signature->signer_name_snapshot ?: '-',
                                                'status' => $signature->status,
                                            ];
                                        })->values()
                                        : collect();
                                    $terminTwoApprovalModalActions = [
                                        'link' => $terminTwoActiveApprovalLink && $terminTwoActiveSignature?->role_key !== 'dirops' && ! $terminTwoIsExpired ? $terminTwoActiveApprovalLink : '',
                                        'resend_url' => $terminTwoActiveApprovalLink && $terminTwoActiveSignature?->role_key !== 'dirops' && ! $terminTwoIsExpired ? route('pkm.lhpp.approval.resend', ['lhppId' => $terminTwo->id]) : '',
                                    ];
                                @endphp

                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-3 py-2">
                                        <div class="space-y-0.5 text-[9px] leading-tight">
                                            <div class="text-[11px] font-black text-slate-900">{{ $row->nomor_order }}</div>
                                            <div class="font-medium text-blue-600">Notif : {{ $row->order?->notifikasi ?: '-' }}</div>
                                            <div class="font-medium text-blue-600">PO : {{ $row->purchase_order_number ?: '-' }}</div>
                                        </div>
                                    </td>

                                    <td class="px-3 py-2">
                                        <div class="space-y-1 text-[9px] leading-snug text-slate-600">
                                            <div class="text-[10px] font-bold text-slate-900">{{ $row->deskripsi_pekerjaan ?: '-' }}</div>
                                            <div>Unit: {{ $row->unit_kerja ?: '-' }}</div>
                                            <div class="text-blue-600">Seksi: {{ $row->seksi ?: '-' }}</div>
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
                                                {{ $isWithoutWarranty ? 'Total Dibayar' : 'Termin 1' }}: Rp {{ number_format($termin1Amount, 0, ',', '.') }}
                                            </div>
                                        @endif
                                        @if (! is_null($termin2Amount))
                                            <div class="mt-1 text-[10px] font-medium text-sky-600">
                                                Termin 2: Rp {{ number_format($termin2Amount, 0, ',', '.') }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2">
                                        <div class="rounded-xl border border-blue-100 bg-blue-50 px-2 py-1.5 shadow-sm">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex min-w-0 items-center gap-1.5">
                                                    <span class="inline-flex shrink-0 rounded-full bg-white px-1.5 py-0.5 text-[8px] font-bold text-blue-700 ring-1 ring-blue-100">
                                                        {{ $signedCount }}/{{ $totalSteps }} TTD
                                                    </span>
                                                    <span class="truncate text-[9px] font-semibold text-slate-800" title="{{ $approvalSummaryLabel }}">
                                                        {{ $approvalSummaryLabel }}
                                                    </span>
                                                </div>
                                                <button
                                                    type="button"
                                                    class="bast-approval-flow-trigger inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-blue-200 hover:bg-blue-100 hover:text-blue-700"
                                                    data-title="{{ $row->nomor_order }} - T1"
                                                    data-progress="{{ $approvalProgress }}"
                                                    data-signed-count="{{ $signedCount }}"
                                                    data-total-steps="{{ $totalSteps }}"
                                                    data-checklist='@json($approvalChecklist)'
                                                    data-actions='@json($activeApprovalModalActions)'
                                                    title="Detail approval Termin 1"
                                                >
                                                    <i data-lucide="info" class="h-3 w-3"></i>
                                                </button>
                                            </div>
                                            @if ($isExpired && $activeSignature && $approvalStatus !== \App\Models\LhppBast::APPROVAL_APPROVED)
                                                <form action="{{ route('pkm.lhpp.approval-token.regenerate', ['lhppId' => $row->id]) }}" method="POST" class="mt-1">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-[8px] font-semibold text-amber-800 ring-1 ring-amber-200 hover:bg-amber-50">
                                                        <i data-lucide="refresh-cw" class="h-2.5 w-2.5"></i> Token Baru
                                                    </button>
                                                </form>
                                            @endif
                                        </div>

                                        @if ($terminTwoExists)
                                            <div class="mt-2 rounded-xl border border-sky-100 bg-sky-50 px-2 py-1.5 shadow-sm">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="flex min-w-0 items-center gap-1.5">
                                                        <span class="inline-flex shrink-0 rounded-full bg-white px-1.5 py-0.5 text-[8px] font-bold text-sky-700 ring-1 ring-sky-100">
                                                            {{ $terminTwoSignedCount }}/{{ $terminTwoTotalSteps }} TTD
                                                        </span>
                                                        <span class="truncate text-[9px] font-semibold text-slate-800" title="{{ $terminTwoSummaryLabel }}">
                                                            {{ $terminTwoSummaryLabel }}
                                                        </span>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        class="bast-approval-flow-trigger inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-sky-200 hover:bg-sky-100 hover:text-sky-700"
                                                        data-title="{{ $row->nomor_order }} - T2"
                                                        data-progress="{{ $terminTwoProgress }}"
                                                        data-signed-count="{{ $terminTwoSignedCount }}"
                                                        data-total-steps="{{ $terminTwoTotalSteps }}"
                                                        data-checklist='@json($terminTwoChecklist)'
                                                        data-actions='@json($terminTwoApprovalModalActions)'
                                                        title="Detail approval Termin 2"
                                                    >
                                                        <i data-lucide="info" class="h-3 w-3"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($isDiropsPending)
                                            <form action="{{ route('pkm.lhpp.dirops-document.upload', ['lhppId' => $row->id]) }}" method="POST" enctype="multipart/form-data" class="mt-2 w-[190px] space-y-1 rounded-lg border border-orange-200 bg-orange-50 p-2">
                                                @csrf
                                                <input type="file" name="signed_document" accept=".pdf,.png,.jpg,.jpeg" class="w-full text-[9px] text-orange-700">
                                                <button type="submit" class="inline-flex w-full items-center justify-center gap-1 rounded-md bg-orange-600 px-2 py-1 text-[9px] font-semibold text-white transition hover:bg-orange-700">
                                                    <i data-lucide="upload" class="h-3 w-3"></i>
                                                    Upload Final DIROPS
                                                </button>
                                            </form>
                                        @endif

                                        @if ($diropsSignedDocumentUrl)
                                            <a href="{{ $diropsSignedDocumentUrl }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800 ring-1 ring-emerald-200 hover:bg-emerald-200">
                                                <i data-lucide="file-check-2" class="h-3 w-3"></i> Final DIROPS
                                            </a>
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
                                            @unless ($isWithoutWarranty)
                                            <div>
                                                <span class="text-[10px] text-slate-600">Termin 2:</span>
                                                @if ($t2 === 'sudah')
                                                    <span class="ml-1 inline-block rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] text-emerald-800">Sudah Dibayar</span>
                                                @else
                                                    <span class="ml-1 inline-block rounded-md bg-amber-100 px-2 py-0.5 text-[10px] text-amber-800">Belum Dibayar</span>
                                                @endif
                                            </div>
                                            @endunless
                                        </div>
                                    </td>

                                    <td class="px-3 py-2 text-center">
                                        <div x-data="{ selectedTerm: 'termin_1' }" class="flex flex-col items-center gap-2">
                                            <div class="relative w-[118px]">
                                                <select x-model="selectedTerm" class="w-full appearance-none rounded-md border border-slate-300 bg-white py-1.5 pl-2 pr-7 text-[10px] font-semibold text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                                    <option value="termin_1">Termin 1</option>
                                                    @unless ($isWithoutWarranty)
                                                        <option value="termin_2">Termin 2</option>
                                                    @endunless
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

                                            @unless ($isWithoutWarranty)
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
                                                    @if ($terminTwoIsExpired && $terminTwoActiveSignature)
                                                        <form action="{{ route('pkm.lhpp.approval-token.regenerate', ['lhppId' => $terminTwo->id]) }}" method="POST" class="mt-1">
                                                            @csrf
                                                            <button type="submit" class="inline-flex w-full items-center justify-center gap-1 rounded-md bg-amber-100 px-2 py-1 text-[10px] font-semibold text-amber-800 ring-1 ring-amber-200 hover:bg-amber-200">
                                                                <i data-lucide="refresh-cw" class="h-3 w-3"></i> Token Baru T2
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if ($terminTwoIsDiropsPending)
                                                        <form action="{{ route('pkm.lhpp.dirops-document.upload', ['lhppId' => $terminTwo->id]) }}" method="POST" enctype="multipart/form-data" class="mt-2 space-y-1 rounded-lg border border-orange-200 bg-orange-50 p-2">
                                                            @csrf
                                                            <input type="file" name="signed_document" accept=".pdf,.png,.jpg,.jpeg" class="w-full text-[9px] text-orange-700">
                                                            <button type="submit" class="inline-flex w-full items-center justify-center gap-1 rounded-md bg-orange-600 px-2 py-1 text-[9px] font-semibold text-white transition hover:bg-orange-700">
                                                                <i data-lucide="upload" class="h-3 w-3"></i>
                                                                Upload DIROPS T2
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if ($terminTwoDiropsSignedDocumentUrl)
                                                        <a href="{{ $terminTwoDiropsSignedDocumentUrl }}" target="_blank" rel="noopener" class="mt-1 inline-flex w-full items-center justify-center gap-1 rounded-md bg-emerald-100 px-2 py-1 text-[10px] font-semibold text-emerald-800 ring-1 ring-emerald-200 hover:bg-emerald-200">
                                                            <i data-lucide="file-check-2" class="h-3 w-3"></i> Final DIROPS T2
                                                        </a>
                                                    @endif
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
                                            @endunless
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

        <div id="bastApprovalFlowModal" class="fixed inset-0 z-[120] hidden overflow-y-auto" aria-hidden="true">
            <div class="absolute inset-0 bg-slate-900/45"></div>
            <div class="relative flex min-h-full items-start justify-center px-4 pb-6 pt-28 sm:pb-8 sm:pt-32">
                <div data-bast-approval-panel class="my-2 w-full max-w-md overflow-hidden rounded-[1.2rem] border border-slate-200 bg-white shadow-2xl">
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">Detail Approval BAST</div>
                            <h2 id="bastApprovalFlowModalTitle" class="mt-1.5 text-[1.2rem] font-bold leading-none tracking-tight text-slate-900">-</h2>
                            <p class="mt-2 text-[11px] text-slate-500">Progress tanda tangan BAST yang sedang berjalan.</p>
                        </div>
                        <button
                            type="button"
                            id="bastApprovalFlowModalClose"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                            aria-label="Tutup detail approval BAST"
                        >
                            <i data-lucide="x" class="h-3.5 w-3.5"></i>
                        </button>
                    </div>

                    <div class="max-h-[58vh] space-y-3 overflow-y-auto px-4 py-3.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span id="bastApprovalFlowModalCount" class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold text-blue-700 ring-1 ring-blue-100">0/0 TTD</span>
                            <span id="bastApprovalFlowModalPercent" class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600">0%</span>
                        </div>

                        <div id="bastApprovalFlowModalChecklist" class="space-y-2"></div>
                    </div>
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

                const approvalFlowModal = document.getElementById('bastApprovalFlowModal');
                const approvalFlowModalTitle = document.getElementById('bastApprovalFlowModalTitle');
                const approvalFlowModalCount = document.getElementById('bastApprovalFlowModalCount');
                const approvalFlowModalPercent = document.getElementById('bastApprovalFlowModalPercent');
                const approvalFlowModalChecklist = document.getElementById('bastApprovalFlowModalChecklist');
                const approvalFlowModalClose = document.getElementById('bastApprovalFlowModalClose');
                const escapeHtml = (value) => String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
                const parseArrayData = (value) => {
                    try {
                        const parsed = JSON.parse(value || '[]');
                        return Array.isArray(parsed) ? parsed : [];
                    } catch (error) {
                        return [];
                    }
                };
                const parseObjectData = (value) => {
                    try {
                        const parsed = JSON.parse(value || '{}');
                        return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
                    } catch (error) {
                        return {};
                    }
                };
                const approvalStatusConfig = {
                    signed: { label: 'OK', badgeClass: 'border-emerald-200 bg-emerald-50 text-emerald-700', rowClass: 'border-emerald-200 bg-emerald-50' },
                    pending: { label: 'Aktif', badgeClass: 'border-blue-200 bg-blue-50 text-blue-700', rowClass: 'border-blue-200 bg-blue-50' },
                    locked: { label: 'Menunggu', badgeClass: 'border-slate-200 bg-slate-100 text-slate-500', rowClass: 'border-slate-200 bg-slate-50' },
                    skipped: { label: 'Skip', badgeClass: 'border-amber-200 bg-amber-50 text-amber-700', rowClass: 'border-amber-200 bg-amber-50' },
                };

                const openApprovalFlowModal = (button) => {
                    if (!approvalFlowModal) {
                        return;
                    }

                    const checklist = parseArrayData(button.dataset.checklist);
                    const actions = parseObjectData(button.dataset.actions);
                    const approvalLink = actions.link || '';
                    const resendUrl = actions.resend_url || '';
                    const signedCount = button.dataset.signedCount || '0';
                    const totalSteps = button.dataset.totalSteps || '0';
                    const progress = button.dataset.progress || '0';

                    approvalFlowModalTitle.textContent = button.dataset.title || '-';
                    approvalFlowModalCount.textContent = `${signedCount}/${totalSteps} TTD`;
                    approvalFlowModalPercent.textContent = `${progress}%`;
                    approvalFlowModalChecklist.innerHTML = checklist.map((item) => {
                        const config = approvalStatusConfig[item.status] || approvalStatusConfig.locked;
                        const isActive = item.status === 'pending' && approvalLink;
                        const actionButtons = isActive
                            ? `
                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    <button type="button" class="bast-modal-copy-link inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-blue-700 transition hover:bg-blue-100" data-link="${escapeHtml(approvalLink)}">
                                        <i data-lucide="copy" class="h-3 w-3"></i>
                                        Salin Link
                                    </button>
                                    ${resendUrl ? `
                                        <form method="POST" action="${escapeHtml(resendUrl)}" class="inline-block">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-sky-700 transition hover:bg-sky-100">
                                                <i data-lucide="send" class="h-3 w-3"></i>
                                                Resend
                                            </button>
                                        </form>
                                    ` : ''}
                                </div>
                            `
                            : '';

                        return `
                            <div class="rounded-xl border px-3 py-2.5 ${config.rowClass}">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-[13px] font-medium text-slate-800">${escapeHtml(item.label || '-')}</div>
                                        <div class="mt-1 truncate text-[11px] text-slate-500">${escapeHtml(item.name || '-')}</div>
                                    </div>
                                    <span class="inline-flex shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold ${config.badgeClass}">
                                        ${config.label}
                                    </span>
                                </div>
                                ${actionButtons}
                            </div>
                        `;
                    }).join('');

                    approvalFlowModal.classList.remove('hidden');
                    approvalFlowModal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('overflow-hidden');
                    window.lucide?.createIcons();
                };

                const closeApprovalFlowModal = () => {
                    approvalFlowModal?.classList.add('hidden');
                    approvalFlowModal?.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('overflow-hidden');
                };

                document.querySelectorAll('.bast-approval-flow-trigger').forEach((button) => {
                    button.addEventListener('click', () => openApprovalFlowModal(button));
                });
                approvalFlowModalClose?.addEventListener('click', closeApprovalFlowModal);
                approvalFlowModal?.addEventListener('click', (event) => {
                    if (!event.target.closest('[data-bast-approval-panel]')) {
                        closeApprovalFlowModal();
                    }
                });
                approvalFlowModalChecklist?.addEventListener('click', (event) => {
                    const copyButton = event.target.closest('.bast-modal-copy-link');

                    if (!copyButton) {
                        return;
                    }

                    copyTextToClipboard(copyButton.dataset.link || '').then(() => {
                        copyButton.innerHTML = '<i data-lucide="check" class="h-3 w-3"></i> Disalin';
                        window.lucide?.createIcons();
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
