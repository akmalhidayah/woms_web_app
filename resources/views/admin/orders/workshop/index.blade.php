<x-layouts.admin title="Order Pekerjaan Bengkel">
    @php
        $today = now()->format('Y-m-d');
    @endphp

    @if (session('status'))
        <div id="flash-success" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    @if ($errors->any())
        <div id="flash-error" data-message="{{ implode(' | ', $errors->all()) }}" class="hidden"></div>
    @endif

    <div class="space-y-6">
        <section class="rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                        <i data-lucide="factory" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h1 class="text-[1.65rem] font-bold leading-none tracking-tight text-slate-900">Order Pekerjaan Bengkel</h1>
                        <p class="mt-1.5 text-[13px] text-slate-500">Order yang diarahkan ke bengkel dari status workshop dan workshop + jasa.</p>
                    </div>
                </div>

                <button
                    type="button"
                    id="openCreateOrderModal"
                    class="inline-flex w-fit items-center gap-2 rounded-xl bg-blue-500 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-600"
                >
                    <i data-lucide="rocket" class="h-[13px] w-[13px]"></i>
                    Buat Order
                </button>
            </div>
        </section>

        <section class="overflow-hidden rounded-[1.5rem] border border-blue-900/20 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-white px-5 py-4">
                @php
                    $reguToggleOptions = [
                        '' => 'Semua Regu',
                        'Regu Fabrikasi' => 'Regu Fabrikasi',
                        'Regu Bengkel (Refurbish)' => 'Refurbish',
                    ];
                @endphp
                <form method="GET" action="{{ route('admin.orders.workshop.index') }}" class="space-y-3">
                    <input type="hidden" id="reguToggleInput" name="regu" value="{{ $selectedRegu }}">

                    <div class="flex flex-col gap-2.5 xl:flex-row xl:items-end xl:justify-between">
                        <div class="grid flex-1 gap-2.5 md:grid-cols-3 xl:grid-cols-[1.25fr_0.95fr_0.8fr]">
                            <div class="flex flex-col">
                                <label for="search" class="mb-1.5 text-[10px] font-semibold text-slate-700">Pencarian</label>
                                <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Cari nomor / pekerjaan / unit..." class="rounded-lg border border-blue-300 bg-white px-3 py-2 text-[13px] text-slate-900 placeholder:text-slate-500 shadow-sm focus:border-blue-500 focus:outline-none">
                            </div>
                            <div class="flex flex-col">
                                <label for="progress" class="mb-1.5 text-[10px] font-semibold text-slate-700">Progress</label>
                                <select id="progress" name="progress" class="rounded-lg border border-blue-300 bg-white px-3 py-2 text-[13px] font-medium text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none">
                                    <option value="">Semua Progress</option>
                                    @foreach ($progressOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($selectedProgress === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col">
                                <label for="perPage" class="mb-1.5 text-[10px] font-semibold text-slate-700">Per Halaman</label>
                                <select id="perPage" name="perPage" class="rounded-lg border border-blue-300 bg-white px-3 py-2 text-[13px] font-medium text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none">
                                    @foreach ([10, 25, 50] as $option)
                                        <option value="{{ $option }}" @selected($selectedPerPage === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-white shadow-sm transition hover:bg-blue-500" title="Filter">
                                <i data-lucide="filter" class="h-[13px] w-[13px]"></i>
                            </button>
                            <a href="{{ route('admin.orders.workshop.index') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50" title="Reset">
                                <i data-lucide="rotate-ccw" class="h-[13px] w-[13px]"></i>
                            </a>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Filter Regu</div>
                            <div class="mt-0.5 text-[11px] text-slate-500">Pilih regu untuk langsung memfilter tabel.</div>
                        </div>
                        <div class="inline-flex w-full rounded-xl border border-slate-200 bg-slate-50 p-1 shadow-sm sm:w-auto">
                            @foreach ($reguToggleOptions as $value => $label)
                                <button
                                    type="button"
                                    data-regu-toggle="{{ $value }}"
                                    class="inline-flex flex-1 items-center justify-center rounded-lg px-3 py-1.5 text-[11px] font-semibold transition sm:flex-none {{ $selectedRegu === $value ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-700' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-[10px] text-slate-700 order-workshop-table">
                    <thead class="border-y border-slate-200 bg-slate-200 text-slate-600">
                        <tr>
                            <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-600">Nomor Order</th>
                            <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-600">Pekerjaan</th>
                            <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-600">Unit / Seksi</th>
                            <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-600">Konfirmasi Anggaran</th>
                            <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-600">Status Material</th>
                            <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-600">Progress Pekerjaan</th>
                            <th class="px-3 py-3 text-right text-[10px] font-semibold uppercase tracking-wide text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($orders as $order)
                            @php
                                $abnormalDocument = $order->documents->firstWhere('jenis_dokumen.value', 'abnormalitas');
                                $gambarDocument = $order->documents->firstWhere('jenis_dokumen.value', 'gambar_teknik');
                                $workshop = $order->orderWorkshop;
                                $konfirmasi = $workshop?->konfirmasi_anggaran;
                                $showMaterial = $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_READY;
                                $showProgress = in_array($konfirmasi, [
                                    \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_READY,
                                    \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY,
                                ], true);
                                $showEkorin = $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY;
                                $workshopSummary = match (true) {
                                    filled($workshop?->progress_status) => $progressOptions[$workshop?->progress_status] ?? 'Progress Bengkel',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_READY => 'Material Ready',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY => 'E-Korin',
                                    default => 'Belum Konfirmasi',
                                };
                                $workshopSummaryClasses = match (true) {
                                    $workshop?->progress_status === \App\Models\OrderWorkshop::PROGRESS_DONE => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                    $workshop?->progress_status === \App\Models\OrderWorkshop::PROGRESS_QUALITY_CONTROL => 'border-violet-200 bg-violet-50 text-violet-700',
                                    $workshop?->progress_status === \App\Models\OrderWorkshop::PROGRESS_IN_PROGRESS => 'border-blue-200 bg-blue-50 text-blue-700',
                                    $workshop?->progress_status === \App\Models\OrderWorkshop::PROGRESS_PENDING => 'border-orange-200 bg-orange-50 text-orange-700',
                                    $workshop?->progress_status === \App\Models\OrderWorkshop::PROGRESS_MENUNGGU_JADWAL => 'border-amber-200 bg-amber-50 text-amber-700',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_READY => 'border-sky-200 bg-sky-50 text-sky-700',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY => 'border-amber-200 bg-amber-50 text-amber-700',
                                    default => 'border-slate-200 bg-slate-50 text-slate-500',
                                };
                                $workshopNextStep = match (true) {
                                    blank($konfirmasi) => 'Pilih konfirmasi anggaran/material.',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_READY && blank($workshop?->status_material) => 'Isi status material.',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY && blank($workshop?->nomor_e_korin) => 'Isi nomor E-Korin.',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY && blank($workshop?->status_anggaran) => 'Isi status anggaran.',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY && blank($workshop?->status_e_korin) => 'Isi status E-Korin.',
                                    $showProgress && blank($workshop?->progress_status) => 'Update progress bengkel.',
                                    $workshop?->progress_status === \App\Models\OrderWorkshop::PROGRESS_DONE => 'Pekerjaan bengkel selesai.',
                                    default => 'Pantau catatan dan progress bengkel.',
                                };
                                $workshopFlowChecklist = [
                                    ['label' => 'Konfirmasi', 'value' => $konfirmasi ?: '-', 'ready' => filled($konfirmasi)],
                                    ['label' => 'E-Korin', 'value' => $workshop?->nomor_e_korin ?: ($showEkorin ? '-' : 'N/A'), 'ready' => ! $showEkorin || filled($workshop?->nomor_e_korin)],
                                    ['label' => 'Status Anggaran', 'value' => $workshop?->status_anggaran ?: ($showEkorin ? '-' : 'N/A'), 'ready' => ! $showEkorin || filled($workshop?->status_anggaran)],
                                    ['label' => 'Status Material', 'value' => $workshop?->status_material ?: ($showMaterial ? '-' : 'N/A'), 'ready' => ! $showMaterial || filled($workshop?->status_material)],
                                    ['label' => 'Progress', 'value' => $progressOptions[$workshop?->progress_status] ?? '-', 'ready' => filled($workshop?->progress_status)],
                                ];
                                $detailDocuments = collect([
                                    $abnormalDocument ? [
                                        'label' => 'Abnormalitas',
                                        'url' => route('admin.orders.documents.preview', [$order, $abnormalDocument]),
                                    ] : null,
                                    $gambarDocument ? [
                                        'label' => 'Gambar Teknik',
                                        'url' => route('admin.orders.documents.preview', [$order, $gambarDocument]),
                                    ] : null,
                                    $order->scopeOfWork ? [
                                        'label' => 'Scope of Work',
                                        'url' => route('admin.orders.scope-of-work.pdf', [$order, $order->scopeOfWork]),
                                    ] : null,
                                ])->filter()->values();
                                $qcReport = $order->latestQualityControlReport;
                                if ($qcReport) {
                                    $detailDocuments->push([
                                        'label' => 'PDF Quality Control',
                                        'url' => route('admin.orders.workshop.quality-control.pdf', [$order, $qcReport]),
                                    ]);
                                }
                                $showQcActions = $workshop?->progress_status === \App\Models\OrderWorkshop::PROGRESS_QUALITY_CONTROL;
                                $activeQcSignature = $qcReport?->signatures
                                    ?->firstWhere('status', \App\Models\QualityControlSignature::STATUS_PENDING);
                                $activeQcApprovalUrl = $activeQcSignature?->approvalUrl();
                                $activeQcRoleLabel = $activeQcSignature?->role_label ?: match ($activeQcSignature?->role_key) {
                                    \App\Models\QualityControlSignature::ROLE_WORKSHOP_MANAGER => 'Manager Bengkel',
                                    \App\Models\QualityControlSignature::ROLE_USER_MANAGER => 'Manager Unit',
                                    default => 'Approval QC',
                                };
                                $qcWorkshopSignature = $qcReport?->signatures
                                    ?->firstWhere('role_key', \App\Models\QualityControlSignature::ROLE_WORKSHOP_MANAGER);
                                $qcUserSignature = $qcReport?->signatures
                                    ?->firstWhere('role_key', \App\Models\QualityControlSignature::ROLE_USER_MANAGER);
                                $qcSignaturePayload = collect($qcReport?->payload['signature'] ?? []);
                                $qcMakerName = trim((string) $qcSignaturePayload->get('signer_name', ''));
                                $qcMakerDate = trim((string) $qcSignaturePayload->get('signed_at', ''));
                                $qcFlowItems = $qcReport ? collect([
                                    [
                                        'step' => 1,
                                        'role' => 'Pembuat QC',
                                        'name' => $qcMakerName !== '' ? $qcMakerName : '-',
                                        'position' => 'Inspector / Pengisi Form QC',
                                        'scope' => 'Form Quality Control',
                                        'status' => $qcMakerName !== '' ? 'signed' : 'missing',
                                        'status_label' => $qcMakerName !== '' ? 'Sudah TTD' : 'Belum TTD',
                                        'signed_at' => $qcMakerDate,
                                        'is_active' => false,
                                    ],
                                    [
                                        'step' => 2,
                                        'role' => $qcWorkshopSignature?->role_label ?: 'Manager Bengkel',
                                        'name' => $qcWorkshopSignature?->signer_name ?: '-',
                                        'position' => $qcWorkshopSignature?->signer_position ?: $qcWorkshopSignature?->role_label ?: 'Manager Bengkel',
                                        'scope' => trim(collect([$qcWorkshopSignature?->source_unit, $qcWorkshopSignature?->source_section])->filter()->implode(' / ')),
                                        'status' => $qcWorkshopSignature?->status ?: 'missing',
                                        'status_label' => match ($qcWorkshopSignature?->status) {
                                            \App\Models\QualityControlSignature::STATUS_SIGNED => 'Sudah TTD',
                                            \App\Models\QualityControlSignature::STATUS_PENDING => 'Menunggu TTD',
                                            \App\Models\QualityControlSignature::STATUS_LOCKED => 'Belum aktif',
                                            \App\Models\QualityControlSignature::STATUS_MISSING => 'Signer belum lengkap',
                                            default => 'Belum dibuat',
                                        },
                                        'signed_at' => $qcWorkshopSignature?->signed_at?->format('d/m/Y H:i') ?: '',
                                        'is_active' => $qcWorkshopSignature?->isPending() && ! $qcWorkshopSignature?->tokenExpired(),
                                    ],
                                    [
                                        'step' => 3,
                                        'role' => $qcUserSignature?->role_label ?: 'Manager Unit Terkait',
                                        'name' => $qcUserSignature?->signer_name ?: '-',
                                        'position' => $qcUserSignature?->signer_position ?: $qcUserSignature?->role_label ?: 'Manager Unit Terkait',
                                        'scope' => trim(collect([$qcUserSignature?->source_unit, $qcUserSignature?->source_section])->filter()->implode(' / ')),
                                        'status' => $qcUserSignature?->status ?: 'missing',
                                        'status_label' => match ($qcUserSignature?->status) {
                                            \App\Models\QualityControlSignature::STATUS_SIGNED => 'Sudah TTD',
                                            \App\Models\QualityControlSignature::STATUS_PENDING => 'Menunggu TTD',
                                            \App\Models\QualityControlSignature::STATUS_LOCKED => 'Belum aktif',
                                            \App\Models\QualityControlSignature::STATUS_MISSING => 'Signer belum lengkap',
                                            default => 'Belum dibuat',
                                        },
                                        'signed_at' => $qcUserSignature?->signed_at?->format('d/m/Y H:i') ?: '',
                                        'is_active' => $qcUserSignature?->isPending() && ! $qcUserSignature?->tokenExpired(),
                                    ],
                                ])->values() : collect();
                                $qcFlowSummary = match (true) {
                                    ! $qcReport => 'QC belum dibuat.',
                                    $qcWorkshopSignature?->isSigned() && $qcUserSignature?->isSigned() => 'Approval QC selesai.',
                                    $activeQcSignature !== null => 'Menunggu TTD '.$activeQcRoleLabel.'.',
                                    $qcWorkshopSignature?->status === \App\Models\QualityControlSignature::STATUS_MISSING
                                        || $qcUserSignature?->status === \App\Models\QualityControlSignature::STATUS_MISSING => 'Signer QC belum lengkap.',
                                    default => 'Approval QC belum aktif.',
                                };
                            @endphp
                            <tr class="align-top odd:bg-white even:bg-slate-50/80 hover:bg-blue-50/70">
                                <td class="px-3 py-3">
                                    <div class="font-semibold text-slate-800">{{ $order->nomor_order }}</div>
                                    <div class="mt-1 text-[9px] text-slate-400">Tanggal: {{ optional($order->tanggal_order)->format('d-m-Y') ?: '-' }}</div>
                                    <button
                                        type="button"
                                        class="workshop-flow-trigger mt-2 inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[9px] font-semibold transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 {{ $workshopSummaryClasses }}"
                                        data-title="{{ $order->nomor_order }}"
                                        data-summary="{{ $workshopSummary }}"
                                        data-next="{{ $workshopNextStep }}"
                                        data-checklist='@json($workshopFlowChecklist)'
                                    >
                                        {{ $workshopSummary }}
                                    </button>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="font-semibold text-slate-800">{{ \Illuminate\Support\Str::limit($order->nama_pekerjaan, 180) }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="font-medium text-slate-800">{{ $order->unit_kerja }}</div>
                                    <div class="mt-1 text-[9px] text-slate-400">{{ $order->seksi }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <input type="hidden" class="workshop-order-key" value="{{ $order->getRouteKey() }}">
                                    <div class="space-y-2">
                                        <div class="relative">
                                            <select name="konfirmasi_anggaran" class="auto-save-select block w-full rounded-md border border-blue-900/25 bg-white px-2.5 py-2 pr-8 text-[10px] font-semibold text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none" data-field="konfirmasi_anggaran">
                                                <option value="">Pilih Status Konfirmasi</option>
                                                @foreach ($konfirmasiOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected(($workshop?->konfirmasi_anggaran ?? '') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <div class="save-indicator absolute right-2 top-2 hidden text-[9px] text-slate-400">...</div>
                                        </div>

                                        <div class="flex items-start gap-2">
                                            <textarea name="keterangan_konfirmasi" class="note-textarea h-10 flex-1 resize-none rounded-md border border-blue-900/25 bg-white px-2 py-1 text-[10px] text-slate-900 placeholder:text-slate-500 focus:border-blue-600 focus:outline-none" placeholder="Keterangan konfirmasi...">{{ $workshop?->keterangan_konfirmasi }}</textarea>
                                            <button type="button" class="save-note-btn inline-flex h-7 w-7 items-center justify-center rounded-md border border-indigo-200 bg-indigo-50 text-indigo-700 shadow-sm transition hover:bg-indigo-100" data-field="keterangan_konfirmasi">
                                                <i data-lucide="save" class="h-3 w-3"></i>
                                            </button>
                                        </div>

                                        @if ($showEkorin)
                                            <div class="rounded-md border border-slate-200 bg-slate-50 p-2.5 text-[9px] text-slate-700 shadow-sm">
                                                <div class="mb-2 font-semibold text-slate-800">E-Korin</div>
                                                <div class="space-y-2">
                                                    <input type="text" name="nomor_e_korin" value="{{ $workshop?->nomor_e_korin }}" class="w-full rounded-md border border-blue-900/25 px-2 py-1.5 text-[10px] focus:border-blue-600 focus:outline-none" placeholder="Nomor E-Korin">
                                                    <select name="status_anggaran" class="auto-save-select block w-full rounded-md border border-blue-900/25 bg-white px-2.5 py-2 text-[10px] font-semibold text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none" data-field="status_anggaran">
                                                        <option value="">Pilih status anggaran</option>
                                                        @foreach ($statusAnggaranOptions as $value => $label)
                                                            <option value="{{ $value }}" @selected(($workshop?->status_anggaran ?? '') === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="status_e_korin" class="auto-save-select block w-full rounded-md border border-blue-900/25 bg-white px-2.5 py-2 text-[10px] font-semibold text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none" data-field="status_e_korin">
                                                        <option value="">Pilih status E-Korin</option>
                                                        @foreach ($eKorinStatusOptions as $value => $label)
                                                            <option value="{{ $value }}" @selected(($workshop?->status_e_korin ?? '') === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="save-note-btn inline-flex h-7 items-center justify-center rounded-md border border-blue-200 bg-blue-50 px-3 text-[10px] font-semibold text-blue-700 shadow-sm transition hover:bg-blue-100" data-field="nomor_e_korin">Simpan No. E-Korin</button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    @if ($showMaterial)
                                        <div class="space-y-2">
                                            <div class="relative">
                                                <select name="status_material" class="auto-save-select block w-full rounded-md border border-blue-900/25 bg-white px-2.5 py-2 pr-8 text-[10px] font-semibold text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none" data-field="status_material">
                                                    <option value="">Pilih status material</option>
                                                    @foreach ($materialOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected(($workshop?->status_material ?? '') === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="save-indicator absolute right-2 top-2 hidden text-[9px] text-slate-400">...</div>
                                            </div>
                                            <div class="flex items-start gap-2">
                                                <textarea name="keterangan_material" class="note-textarea h-10 flex-1 resize-none rounded-md border border-blue-900/25 bg-white px-2 py-1 text-[10px] text-slate-900 placeholder:text-slate-500 focus:border-blue-600 focus:outline-none" placeholder="Catatan material...">{{ $workshop?->keterangan_material }}</textarea>
                                                <button type="button" class="save-note-btn inline-flex h-7 w-7 items-center justify-center rounded-md border border-cyan-200 bg-cyan-50 text-cyan-700 shadow-sm transition hover:bg-cyan-100" data-field="keterangan_material">
                                                    <i data-lucide="save" class="h-3 w-3"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        @if ($qcReport)
                                            <a href="{{ route('admin.orders.workshop.quality-control.pdf', [$order, $qcReport]) }}" target="_blank" title="PDF QC" aria-label="PDF QC" class="inline-flex items-center justify-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[10px] font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                                <i data-lucide="file-text" class="h-3 w-3"></i>
                                                PDF QC
                                            </a>
                                        @else
                                            <div class="italic text-slate-400">-</div>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($showProgress)
                                        <div class="space-y-2">
                                            <div class="relative">
                                                <select name="progress_status" class="auto-save-select block w-full rounded-md border border-blue-900/25 bg-white px-2.5 py-2 pr-8 text-[10px] font-semibold text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none" data-field="progress_status">
                                                    <option value="">Pilih progress</option>
                                                    @foreach ($progressOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected(($workshop?->progress_status ?? '') === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="save-indicator absolute right-2 top-2 hidden text-[9px] text-slate-400">...</div>
                                            </div>
                                            <div class="flex items-start gap-2">
                                                <textarea name="keterangan_progress" class="note-textarea h-10 flex-1 resize-none rounded-md border border-blue-900/25 bg-white px-2 py-1 text-[10px] text-slate-900 placeholder:text-slate-500 focus:border-blue-600 focus:outline-none" placeholder="Catatan progress...">{{ $workshop?->keterangan_progress }}</textarea>
                                                <button type="button" class="save-note-btn inline-flex h-7 w-7 items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm transition hover:bg-emerald-100" data-field="keterangan_progress">
                                                    <i data-lucide="save" class="h-3 w-3"></i>
                                                </button>
                                            </div>
                                            @if ($showQcActions)
                                                <div class="flex items-center gap-1.5">
                                                    @if ($qcReport)
                                                        <a href="{{ route('admin.orders.workshop.quality-control.edit', [$order, $qcReport]) }}" title="Edit QC" aria-label="Edit QC" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-violet-200 bg-violet-50 text-violet-700 transition hover:bg-violet-100">
                                                            <i data-lucide="clipboard-pen" class="h-3 w-3"></i>
                                                        </a>
                                                        <a href="{{ route('admin.orders.workshop.quality-control.pdf', [$order, $qcReport]) }}" target="_blank" title="PDF QC" aria-label="PDF QC" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100">
                                                            <i data-lucide="file-text" class="h-3 w-3"></i>
                                                        </a>
                                                        @if ($activeQcApprovalUrl)
                                                            <button type="button" title="Salin link TTD {{ $activeQcRoleLabel }}" aria-label="Salin link TTD {{ $activeQcRoleLabel }}" class="copy-qc-approval inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 transition hover:bg-blue-100" data-link="{{ $activeQcApprovalUrl }}" data-role-label="{{ $activeQcRoleLabel }}">
                                                                <i data-lucide="copy" class="h-3 w-3"></i>
                                                            </button>
                                                        @elseif (
                                                            $qcWorkshopSignature?->status === \App\Models\QualityControlSignature::STATUS_MISSING
                                                            || $qcUserSignature?->status === \App\Models\QualityControlSignature::STATUS_MISSING
                                                        )
                                                            <span class="inline-flex w-full items-center justify-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-[10px] font-semibold text-amber-700" title="Token belum dibuat: Manager Bengkel/Manager Unit belum lengkap di Struktur Organisasi.">
                                                                <i data-lucide="user-x" class="h-3 w-3"></i>
                                                                Signer QC belum lengkap
                                                            </span>
                                                        @elseif ($qcWorkshopSignature?->isSigned() && $qcUserSignature?->isSigned())
                                                            <span class="inline-flex w-full items-center justify-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[10px] font-semibold text-emerald-700">
                                                                <i data-lucide="check-check" class="h-3 w-3"></i>
                                                                Approval QC selesai
                                                            </span>
                                                        @endif
                                                    @else
                                                        <a href="{{ route('admin.orders.workshop.quality-control.create', $order) }}" class="inline-flex w-full items-center justify-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1.5 text-[10px] font-semibold text-violet-700 transition hover:bg-violet-100">
                                                            <i data-lucide="clipboard-plus" class="h-3 w-3"></i>
                                                            Tambah QC
                                                        </a>
                                                    @endif
                                                </div>
                                            @elseif ($qcReport)
                                                <div class="flex items-center gap-1.5">
                                                    <a href="{{ route('admin.orders.workshop.quality-control.pdf', [$order, $qcReport]) }}" target="_blank" title="PDF QC" aria-label="PDF QC" class="inline-flex items-center justify-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[10px] font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                                        <i data-lucide="file-text" class="h-3 w-3"></i>
                                                        PDF QC
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="italic text-slate-400">-</div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            title="Detail"
                                            aria-label="Detail order"
                                            class="workshop-detail-trigger inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 shadow-sm transition hover:bg-blue-100"
                                            data-title="{{ $order->nomor_order }}"
                                            data-job="{{ $order->nama_pekerjaan }}"
                                            data-unit="{{ $order->unit_kerja }}"
                                            data-seksi="{{ $order->seksi }}"
                                            data-catatan="{{ $workshop?->catatan ?: ($order->catatan ?: '-') }}"
                                            data-documents='@json($detailDocuments)'
                                            data-qc-flow-summary="{{ $qcFlowSummary }}"
                                            data-qc-flow='@json($qcFlowItems)'
                                        >
                                            <i data-lucide="info" class="h-3.5 w-3.5"></i>
                                        </button>
                                        <a href="{{ route('admin.orders.documents.index', $order) }}" title="Lengkapi dokumen" aria-label="Lengkapi dokumen" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-700 shadow-sm transition hover:bg-sky-100">
                                            <i data-lucide="file-plus-2" class="h-3.5 w-3.5"></i>
                                        </a>
                                        <div class="row-action-menu relative">
                                            <button type="button" class="row-action-menu-trigger inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50" title="Menu lainnya" aria-label="Menu lainnya">
                                                <i data-lucide="more-vertical" class="h-3.5 w-3.5"></i>
                                            </button>
                                            <div class="row-action-menu-panel absolute right-0 z-30 mt-2 hidden w-44 overflow-hidden rounded-xl border border-slate-200 bg-white p-1 text-left shadow-lg">
                                                <button
                                                    type="button"
                                                    class="edit-order-trigger flex w-full items-center gap-2 rounded-lg px-3 py-2 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50"
                                                    data-action="{{ route('admin.orders.update', $order) }}"
                                                    data-order-key="{{ $order->getRouteKey() }}"
                                                    data-nomor-order="{{ $order->nomor_order }}"
                                                    data-notifikasi="{{ $order->notifikasi }}"
                                                    data-nama-pekerjaan="{{ $order->nama_pekerjaan }}"
                                                    data-unit-kerja="{{ $order->unit_kerja }}"
                                                    data-prioritas="{{ $order->prioritas }}"
                                                    data-target-selesai="{{ optional($order->target_selesai)->format('Y-m-d') }}"
                                                    data-seksi="{{ $order->seksi }}"
                                                    data-catatan-status="{{ $order->catatan_status?->value ?? \App\Domain\Orders\Enums\OrderUserNoteStatus::ApprovedWorkshop->value }}"
                                                    data-catatan="{{ $order->catatan }}"
                                                    data-tanggal-order="{{ optional($order->tanggal_order)->format('Y-m-d') }}"
                                                >
                                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                                    Edit Order
                                                </button>
                                                <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" class="delete-order-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-50">
                                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                                        Hapus Order
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500">Tidak ada order bengkel untuk ditampilkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="flex items-center justify-between border-t border-slate-200 px-4 py-4">
                    <div class="text-[11px] text-slate-500">Menampilkan <strong>{{ $orders->firstItem() ?: 0 }}</strong> - <strong>{{ $orders->lastItem() ?: 0 }}</strong> dari <strong>{{ $orders->total() }}</strong></div>
                    <div>{{ $orders->links() }}</div>
                </div>
            @endif
        </section>
    </div>

    <div id="orderModalOverlay" class="fixed inset-0 z-40 hidden bg-slate-950/55"></div>

    <div id="createOrderModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="max-h-[92vh] overflow-y-auto rounded-3xl bg-white shadow-2xl" style="width:min(100%, 860px);">
            <form method="POST" action="{{ route('admin.orders.store') }}" class="p-6">
                @csrf
                <input type="hidden" name="form_context" value="create">
                <input type="hidden" name="tanggal_order" id="createTanggalOrder" value="{{ old('form_context') === 'create' ? old('tanggal_order', $today) : $today }}">
                <input type="hidden" name="deskripsi" id="createDeskripsi" value="{{ old('form_context') === 'create' ? old('deskripsi', 'Order pekerjaan bengkel') : 'Order pekerjaan bengkel' }}">

                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-2xl font-semibold text-slate-900">Order</h2>
                    <button type="button" data-close-order-modal class="text-2xl text-slate-500 transition hover:text-slate-700">&times;</button>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nomor Order</label>
                        <input id="createNomorOrder" name="nomor_order" type="text" value="{{ old('form_context') === 'create' ? old('nomor_order') : '' }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                        @if (old('form_context') === 'create')
                            @error('nomor_order')
                                <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Notifikasi</label>
                        <input id="createNotifikasi" name="notifikasi" type="text" value="{{ old('form_context') === 'create' ? old('notifikasi') : '' }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                        @if (old('form_context') === 'create')
                            @error('notifikasi')
                                <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nama Pekerjaan</label>
                        <input id="createNamaPekerjaan" name="nama_pekerjaan" type="text" value="{{ old('form_context') === 'create' ? old('nama_pekerjaan') : '' }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Prioritas</label>
                        <input
                            type="hidden"
                            id="createPrioritas"
                            name="prioritas"
                            value="{{ old('form_context') === 'create' ? old('prioritas', \App\Models\Order::PRIORITY_LOW) : \App\Models\Order::PRIORITY_LOW }}"
                        >
                        <div class="space-y-2">
                            <select id="createPrioritasPrimary" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                                @foreach (\App\Models\Order::priorityPrimaryOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('form_context') === 'create' ? \App\Models\Order::priorityPrimaryFor(old('prioritas', \App\Models\Order::PRIORITY_LOW)) === $value : $value === 'medium')>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select id="createPrioritasEmergency" class="hidden w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                                @foreach (\App\Models\Order::priorityEmergencyOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('form_context') === 'create' && \App\Models\Order::priorityEmergencyFor(old('prioritas', \App\Models\Order::PRIORITY_LOW)) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Unit Kerja</label>
                        <select id="createUnitKerja" name="unit_kerja" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            <option value="">Pilih Unit Kerja</option>
                            @foreach ($structureUnitOptions as $unitWork)
                                <option
                                    value="{{ $unitWork->name }}"
                                    @selected(old('form_context') === 'create' && old('unit_kerja') === $unitWork->name)
                                    data-seksi='@json($unitWork->sections->pluck('name')->values())'
                                >
                                    {{ $unitWork->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Seksi</label>
                        <select id="createSeksi" name="seksi" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            <option value="">Pilih seksi</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Status Catatan</label>
                        <select id="createCatatanStatus" name="catatan_status" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            @foreach ($userNoteStatusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('form_context') === 'create' ? old('catatan_status', \App\Domain\Orders\Enums\OrderUserNoteStatus::ApprovedWorkshop->value) === $value : $value === \App\Domain\Orders\Enums\OrderUserNoteStatus::ApprovedWorkshop->value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if (old('form_context') === 'create')
                            @error('catatan_status')
                                <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Rencana Pemakaian</label>
                        <input id="createTargetSelesai" name="target_selesai" type="date" value="{{ old('form_context') === 'create' ? old('target_selesai', $today) : $today }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm text-slate-700">Detail Catatan</label>
                        <div class="space-y-2">
                            <select id="createCatatanSelect" class="hidden w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none"></select>
                            <textarea id="createCatatanTextarea" rows="3" placeholder="Catatan (opsional)" class="hidden w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none"></textarea>
                            <input type="hidden" name="catatan" id="createCatatan" value="{{ old('form_context') === 'create' ? old('catatan') : '' }}">
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">Submit</button>
                    <button type="button" data-close-order-modal class="rounded-lg bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editOrderModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="max-h-[92vh] overflow-y-auto rounded-3xl bg-white shadow-2xl" style="width:min(100%, 860px);">
            <form method="POST" id="editOrderForm" action="#" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="form_context" value="edit">
                <input type="hidden" name="edit_original_order" id="editOriginalOrder" value="{{ old('edit_original_order') }}">
                <input type="hidden" name="tanggal_order" id="editTanggalOrder" value="{{ old('form_context') === 'edit' ? old('tanggal_order', $today) : $today }}">
                <input type="hidden" name="deskripsi" id="editDeskripsi" value="{{ old('form_context') === 'edit' ? old('deskripsi', 'Order pekerjaan bengkel') : 'Order pekerjaan bengkel' }}">

                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-2xl font-semibold text-slate-900">Edit Order</h2>
                    <button type="button" data-close-order-modal class="text-2xl text-slate-500 transition hover:text-slate-700">&times;</button>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nomor Order</label>
                        <input id="editNomorOrder" name="nomor_order" type="text" value="{{ old('form_context') === 'edit' ? old('nomor_order') : '' }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                        @if (old('form_context') === 'edit')
                            @error('nomor_order')
                                <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Notifikasi</label>
                        <input id="editNotifikasi" name="notifikasi" type="text" value="{{ old('form_context') === 'edit' ? old('notifikasi') : '' }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                        @if (old('form_context') === 'edit')
                            @error('notifikasi')
                                <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nama Pekerjaan</label>
                        <input id="editNamaPekerjaan" name="nama_pekerjaan" type="text" value="{{ old('form_context') === 'edit' ? old('nama_pekerjaan') : '' }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Prioritas</label>
                        <input
                            type="hidden"
                            id="editPrioritas"
                            name="prioritas"
                            value="{{ old('form_context') === 'edit' ? old('prioritas', \App\Models\Order::PRIORITY_LOW) : \App\Models\Order::PRIORITY_LOW }}"
                        >
                        <div class="space-y-2">
                            <select id="editPrioritasPrimary" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                                @foreach (\App\Models\Order::priorityPrimaryOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('form_context') === 'edit' && \App\Models\Order::priorityPrimaryFor(old('prioritas', \App\Models\Order::PRIORITY_LOW)) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select id="editPrioritasEmergency" class="hidden w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                                @foreach (\App\Models\Order::priorityEmergencyOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('form_context') === 'edit' && \App\Models\Order::priorityEmergencyFor(old('prioritas', \App\Models\Order::PRIORITY_LOW)) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Unit Kerja</label>
                        <select id="editUnitKerja" name="unit_kerja" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            <option value="">Pilih Unit Kerja</option>
                            @foreach ($structureUnitOptions as $unitWork)
                                <option
                                    value="{{ $unitWork->name }}"
                                    @selected(old('form_context') === 'edit' && old('unit_kerja') === $unitWork->name)
                                    data-seksi='@json($unitWork->sections->pluck('name')->values())'
                                >
                                    {{ $unitWork->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Seksi</label>
                        <select id="editSeksi" name="seksi" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            <option value="">Pilih seksi</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Status Catatan</label>
                        <select id="editCatatanStatus" name="catatan_status" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            @foreach ($userNoteStatusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('form_context') === 'edit' ? old('catatan_status', \App\Domain\Orders\Enums\OrderUserNoteStatus::ApprovedWorkshop->value) === $value : $value === \App\Domain\Orders\Enums\OrderUserNoteStatus::ApprovedWorkshop->value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if (old('form_context') === 'edit')
                            @error('catatan_status')
                                <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Rencana Pemakaian</label>
                        <input id="editTargetSelesai" name="target_selesai" type="date" value="{{ old('form_context') === 'edit' ? old('target_selesai') : '' }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm text-slate-700">Detail Catatan</label>
                        <div class="space-y-2">
                            <select id="editCatatanSelect" class="hidden w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none"></select>
                            <textarea id="editCatatanTextarea" rows="3" placeholder="Catatan (opsional)" class="hidden w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none"></textarea>
                            <input type="hidden" name="catatan" id="editCatatan" value="{{ old('form_context') === 'edit' ? old('catatan') : '' }}">
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">Submit</button>
                    <button type="button" data-close-order-modal class="rounded-lg bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="workshopDetailOverlay" class="fixed inset-0 z-40 hidden bg-slate-950/55"></div>
    <div id="workshopDetailModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="max-h-[88vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-500">Detail Order</div>
                    <h2 id="workshopDetailTitle" class="mt-1 text-lg font-bold text-slate-900">Order</h2>
                    <p id="workshopDetailJob" class="mt-1 text-[12px] font-semibold leading-5 text-slate-600">-</p>
                </div>
                <button type="button" data-close-workshop-detail class="text-2xl leading-none text-slate-400 transition hover:text-slate-700">&times;</button>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
                <div class="space-y-3">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <div class="text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-400">Unit / Seksi</div>
                            <div id="workshopDetailUnit" class="mt-1 text-[12px] font-semibold text-slate-800">-</div>
                            <div id="workshopDetailSeksi" class="mt-0.5 text-[11px] text-slate-500">-</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <div class="text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-400">Catatan / Regu</div>
                            <div id="workshopDetailCatatan" class="mt-1 text-[12px] font-semibold text-slate-800">-</div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-3">
                        <div class="mb-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Dokumen</div>
                        <div id="workshopDetailDocuments" class="grid gap-2"></div>
                    </div>
                </div>

                <div class="rounded-xl border border-blue-100 bg-blue-50/40 p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-blue-500">Flow Approval QC</div>
                            <div id="workshopDetailQcSummary" class="mt-1 text-[11px] font-semibold text-slate-600">-</div>
                        </div>
                        <span id="workshopDetailQcCount" class="inline-flex rounded-full bg-white px-2 py-0.5 text-[9px] font-bold text-blue-700 ring-1 ring-blue-100">0/3</span>
                    </div>
                    <div id="workshopDetailQcFlow" class="mt-3 space-y-2"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="workshopFlowOverlay" class="fixed inset-0 z-40 hidden bg-slate-950/55"></div>
    <div id="workshopFlowModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="w-full max-w-md rounded-3xl bg-white p-5 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-500">Alur Bengkel</div>
                    <h2 id="workshopFlowTitle" class="mt-1 text-lg font-bold text-slate-900">Order</h2>
                    <p id="workshopFlowSummary" class="mt-1 text-[11px] font-semibold text-slate-500">-</p>
                </div>
                <button type="button" data-close-workshop-flow class="text-2xl leading-none text-slate-400 transition hover:text-slate-700">&times;</button>
            </div>

            <div id="workshopFlowChecklist" class="mt-4 space-y-1.5 text-[11px]"></div>

            <div class="mt-4 rounded-2xl border border-blue-100 bg-blue-50 px-3 py-2.5">
                <div class="text-[9px] font-semibold uppercase tracking-[0.14em] text-blue-500">Next Step</div>
                <div id="workshopFlowNext" class="mt-1 text-[11px] font-semibold leading-5 text-slate-700">-</div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const swal = window.Swal;
            const updateUrlTemplate = @json(route('admin.orders.workshop.update', '__ORDER__'));
            const workshopDetailOverlay = document.getElementById('workshopDetailOverlay');
            const workshopDetailModal = document.getElementById('workshopDetailModal');
            const workshopFlowOverlay = document.getElementById('workshopFlowOverlay');
            const workshopFlowModal = document.getElementById('workshopFlowModal');
            const orderModalOverlay = document.getElementById('orderModalOverlay');
            const createModal = document.getElementById('createOrderModal');
            const editModal = document.getElementById('editOrderModal');
            const editForm = document.getElementById('editOrderForm');
            const createUnitKerja = document.getElementById('createUnitKerja');
            const createSeksi = document.getElementById('createSeksi');
            const createPrioritas = document.getElementById('createPrioritas');
            const createPrioritasPrimary = document.getElementById('createPrioritasPrimary');
            const createPrioritasEmergency = document.getElementById('createPrioritasEmergency');
            const createCatatanStatus = document.getElementById('createCatatanStatus');
            const editUnitKerja = document.getElementById('editUnitKerja');
            const editSeksi = document.getElementById('editSeksi');
            const editCatatanStatus = document.getElementById('editCatatanStatus');
            const oldFormContext = @json(old('form_context'));
            const oldEditOrderKey = @json(old('edit_original_order'));
            const userNoteDetailOptions = @json($userNoteDetailOptions);
            const priorityUrgent = @json(\App\Models\Order::PRIORITY_URGENT);
            const priorityHigh = @json(\App\Models\Order::PRIORITY_HIGH);
            const priorityMedium = @json(\App\Models\Order::PRIORITY_MEDIUM);
            const priorityLow = @json(\App\Models\Order::PRIORITY_LOW);
            const defaultWorkshopStatus = @json(\App\Domain\Orders\Enums\OrderUserNoteStatus::ApprovedWorkshop->value);
            const reguToggleInput = document.getElementById('reguToggleInput');

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const parseSeksiOptions = (select) => {
                const selectedOption = select?.options?.[select.selectedIndex];
                const raw = selectedOption?.dataset?.seksi;

                if (!raw) {
                    return [];
                }

                try {
                    const parsed = JSON.parse(raw);
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            };

            const syncSeksiSelect = (unitSelect, seksiSelect, selectedValue = '') => {
                if (!unitSelect || !seksiSelect) {
                    return;
                }

                const seksiOptions = parseSeksiOptions(unitSelect);
                const normalizedValue = selectedValue === 'General' ? 'Tidak ada seksi' : selectedValue;
                const normalizedOptions = seksiOptions.length > 0 ? seksiOptions : ['Tidak ada seksi'];
                const fallbackValue = seksiOptions.length > 0
                    ? (normalizedOptions.includes(normalizedValue) ? normalizedValue : normalizedOptions[0])
                    : (normalizedValue || 'Tidak ada seksi');

                seksiSelect.innerHTML = '';

                normalizedOptions.forEach((optionValue) => {
                    const option = document.createElement('option');
                    option.value = optionValue;
                    option.textContent = optionValue;
                    if (optionValue === fallbackValue) {
                        option.selected = true;
                    }
                    seksiSelect.appendChild(option);
                });
            };

            const syncModalNoteField = (context, selectedStatus = defaultWorkshopStatus, currentNote = '') => {
                const detailSelect = document.getElementById(`${context}CatatanSelect`);
                const detailTextarea = document.getElementById(`${context}CatatanTextarea`);
                const hiddenInput = document.getElementById(`${context}Catatan`);

                if (!detailSelect || !detailTextarea || !hiddenInput) {
                    return;
                }

                const detailOptions = userNoteDetailOptions[selectedStatus] || [];
                const useSelect = detailOptions.length > 0;

                detailSelect.innerHTML = '';

                if (useSelect) {
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = selectedStatus === 'approved_workshop'
                        ? '- Pilih regu workshop (opsional) -'
                        : selectedStatus === 'approved_jasa'
                            ? '- Pilih jenis jasa (opsional) -'
                            : '- Pilih (opsional) -';
                    detailSelect.appendChild(placeholder);

                    detailOptions.forEach((optionValue) => {
                        const option = document.createElement('option');
                        option.value = optionValue;
                        option.textContent = optionValue;
                        option.selected = currentNote === optionValue;
                        detailSelect.appendChild(option);
                    });

                    detailSelect.classList.remove('hidden');
                    detailSelect.disabled = false;
                    detailTextarea.classList.add('hidden');
                    detailTextarea.disabled = true;
                    detailTextarea.value = '';
                    detailSelect.value = currentNote;
                    hiddenInput.value = detailSelect.value || '';
                    return;
                }

                detailSelect.classList.add('hidden');
                detailSelect.disabled = true;
                detailTextarea.classList.remove('hidden');
                detailTextarea.disabled = false;
                detailTextarea.value = currentNote;
                hiddenInput.value = detailTextarea.value || '';
            };

            const bindModalNoteField = (context) => {
                const statusSelect = document.getElementById(`${context}CatatanStatus`);
                const detailSelect = document.getElementById(`${context}CatatanSelect`);
                const detailTextarea = document.getElementById(`${context}CatatanTextarea`);
                const hiddenInput = document.getElementById(`${context}Catatan`);

                statusSelect?.addEventListener('change', () => {
                    syncModalNoteField(context, statusSelect.value, '');
                });

                detailSelect?.addEventListener('change', () => {
                    if (hiddenInput) {
                        hiddenInput.value = detailSelect.value || '';
                    }
                });

                detailTextarea?.addEventListener('input', () => {
                    if (hiddenInput) {
                        hiddenInput.value = detailTextarea.value || '';
                    }
                });
            };

            const syncPriorityField = (context, currentPriority = priorityLow) => {
                const hiddenInput = document.getElementById(`${context}Prioritas`);
                const primarySelect = document.getElementById(`${context}PrioritasPrimary`);
                const emergencySelect = document.getElementById(`${context}PrioritasEmergency`);

                if (!hiddenInput || !primarySelect || !emergencySelect) {
                    return;
                }

                const primaryValue = currentPriority === priorityUrgent || currentPriority === priorityHigh
                    ? 'emergency'
                    : currentPriority === priorityMedium
                        ? 'high'
                        : 'medium';

                primarySelect.value = primaryValue;

                if (primaryValue === 'emergency') {
                    emergencySelect.classList.remove('hidden');
                    emergencySelect.disabled = false;
                    emergencySelect.value = currentPriority === priorityUrgent ? priorityUrgent : priorityHigh;
                    hiddenInput.value = emergencySelect.value;
                    return;
                }

                emergencySelect.classList.add('hidden');
                emergencySelect.disabled = true;
                emergencySelect.value = priorityHigh;
                hiddenInput.value = primaryValue === 'high' ? priorityMedium : priorityLow;
            };

            const bindPriorityField = (context) => {
                const hiddenInput = document.getElementById(`${context}Prioritas`);
                const primarySelect = document.getElementById(`${context}PrioritasPrimary`);
                const emergencySelect = document.getElementById(`${context}PrioritasEmergency`);

                if (!hiddenInput || !primarySelect || !emergencySelect) {
                    return;
                }

                primarySelect.addEventListener('change', () => {
                    if (primarySelect.value === 'emergency') {
                        emergencySelect.classList.remove('hidden');
                        emergencySelect.disabled = false;
                        if (!emergencySelect.value) {
                            emergencySelect.value = priorityHigh;
                        }
                        hiddenInput.value = emergencySelect.value || priorityHigh;
                        return;
                    }

                    emergencySelect.classList.add('hidden');
                    emergencySelect.disabled = true;
                    hiddenInput.value = primarySelect.value === 'high' ? priorityMedium : priorityLow;
                });

                emergencySelect.addEventListener('change', () => {
                    hiddenInput.value = emergencySelect.value || priorityHigh;
                });
            };

            const openWorkshopFlow = () => {
                workshopFlowOverlay?.classList.remove('hidden');
                workshopFlowModal?.classList.remove('hidden');
                workshopFlowModal?.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            const openWorkshopDetail = () => {
                workshopDetailOverlay?.classList.remove('hidden');
                workshopDetailModal?.classList.remove('hidden');
                workshopDetailModal?.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            const closeWorkshopFlow = () => {
                workshopFlowOverlay?.classList.add('hidden');
                workshopFlowModal?.classList.add('hidden');
                workshopFlowModal?.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            const closeWorkshopDetail = () => {
                workshopDetailOverlay?.classList.add('hidden');
                workshopDetailModal?.classList.add('hidden');
                workshopDetailModal?.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            const openCreateOrderModal = () => {
                orderModalOverlay?.classList.remove('hidden');
                editModal?.classList.add('hidden');
                editModal?.classList.remove('flex');
                createModal?.classList.remove('hidden');
                createModal?.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            const openEditOrderModal = () => {
                orderModalOverlay?.classList.remove('hidden');
                createModal?.classList.add('hidden');
                createModal?.classList.remove('flex');
                editModal?.classList.remove('hidden');
                editModal?.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            const closeOrderModals = () => {
                orderModalOverlay?.classList.add('hidden');
                createModal?.classList.add('hidden');
                createModal?.classList.remove('flex');
                editModal?.classList.add('hidden');
                editModal?.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            const closeRowActionMenus = () => {
                document.querySelectorAll('.row-action-menu-panel').forEach((panel) => {
                    panel.classList.add('hidden');
                });
            };

            const showToast = (message, icon = 'success') => {
                if (swal) {
                    swal.fire({
                        toast: true,
                        position: 'bottom-end',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true,
                        icon,
                        title: message,
                    });
                    return;
                }

                alert(message);
            };

            const showAlert = (options) => {
                if (swal) {
                    swal.fire(options);
                    return;
                }

                alert(options.text || options.title || 'Terjadi kesalahan.');
            };

            const setIndicator = (element, visible) => {
                const indicator = element.closest('.relative')?.querySelector('.save-indicator');
                if (indicator) indicator.classList.toggle('hidden', !visible);
            };

            const setRowDisabled = (element, disabled) => {
                const row = element.closest('tr');
                row?.querySelectorAll('select, textarea, button, input[type="text"]').forEach((field) => {
                    field.disabled = disabled;
                });
            };

            const buildUrl = (orderKey) => updateUrlTemplate.replace('__ORDER__', encodeURIComponent(orderKey));

            const sendPatch = async (url, payload, indicatorElement = null) => {
                if (indicatorElement) {
                    setIndicator(indicatorElement, true);
                    setRowDisabled(indicatorElement, true);
                }

                try {
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                        credentials: 'same-origin',
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(data?.error || data?.message || 'Gagal menyimpan data order bengkel.');
                    }

                    showToast(data?.message || 'Status order bengkel berhasil diperbarui.');
                    return data;
                } catch (error) {
                    showToast(error.message || 'Terjadi kesalahan saat menyimpan.', 'error');
                    return null;
                } finally {
                    if (indicatorElement) {
                        setIndicator(indicatorElement, false);
                        setRowDisabled(indicatorElement, false);
                    }
                }
            };

            bindModalNoteField('create');
            bindModalNoteField('edit');
            bindPriorityField('create');
            bindPriorityField('edit');

            document.querySelectorAll('[data-regu-toggle]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!reguToggleInput) {
                        return;
                    }

                    reguToggleInput.value = button.dataset.reguToggle || '';
                    button.closest('form')?.requestSubmit();
                });
            });

            document.getElementById('openCreateOrderModal')?.addEventListener('click', () => {
                document.getElementById('createNomorOrder').value = '';
                document.getElementById('createNotifikasi').value = '';
                document.getElementById('createNamaPekerjaan').value = '';
                createUnitKerja.value = '';
                createSeksi.innerHTML = '<option value="">Pilih seksi</option>';
                syncPriorityField('create', priorityLow);
                createCatatanStatus.value = defaultWorkshopStatus;
                document.getElementById('createTargetSelesai').value = '{{ $today }}';
                document.getElementById('createTanggalOrder').value = '{{ $today }}';
                document.getElementById('createDeskripsi').value = 'Order pekerjaan bengkel';
                document.getElementById('createCatatan').value = '';
                syncModalNoteField('create', defaultWorkshopStatus, '');
                openCreateOrderModal();
            });

            createUnitKerja?.addEventListener('change', () => {
                syncSeksiSelect(createUnitKerja, createSeksi);
            });

            editUnitKerja?.addEventListener('change', () => {
                syncSeksiSelect(editUnitKerja, editSeksi);
            });

            document.querySelectorAll('.row-action-menu-trigger').forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.stopPropagation();

                    const panel = button.closest('.row-action-menu')?.querySelector('.row-action-menu-panel');
                    const shouldOpen = panel?.classList.contains('hidden');

                    closeRowActionMenus();

                    if (panel && shouldOpen) {
                        panel.classList.remove('hidden');
                    }
                });
            });

            document.querySelectorAll('.row-action-menu-panel').forEach((panel) => {
                panel.addEventListener('click', (event) => {
                    event.stopPropagation();
                });
            });

            document.querySelectorAll('.edit-order-trigger').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!editForm) {
                        return;
                    }

                    editForm.action = button.dataset.action || '#';
                    document.getElementById('editOriginalOrder').value = button.dataset.orderKey || '';
                    document.getElementById('editNomorOrder').value = button.dataset.nomorOrder || '';
                    document.getElementById('editNotifikasi').value = button.dataset.notifikasi || '';
                    document.getElementById('editNamaPekerjaan').value = button.dataset.namaPekerjaan || '';
                    editUnitKerja.value = button.dataset.unitKerja || '';
                    syncPriorityField('edit', button.dataset.prioritas || priorityLow);
                    editCatatanStatus.value = button.dataset.catatanStatus || defaultWorkshopStatus;
                    document.getElementById('editTargetSelesai').value = button.dataset.targetSelesai || '{{ $today }}';
                    document.getElementById('editTanggalOrder').value = button.dataset.tanggalOrder || button.dataset.targetSelesai || '{{ $today }}';
                    document.getElementById('editCatatan').value = button.dataset.catatan || '';
                    document.getElementById('editDeskripsi').value = 'Order pekerjaan bengkel';
                    syncSeksiSelect(editUnitKerja, editSeksi, button.dataset.seksi || 'Tidak ada seksi');
                    syncModalNoteField('edit', button.dataset.catatanStatus || defaultWorkshopStatus, button.dataset.catatan || '');

                    closeRowActionMenus();
                    openEditOrderModal();
                });
            });

            if (oldFormContext === 'create') {
                syncSeksiSelect(createUnitKerja, createSeksi, @json(old('seksi')));
                syncPriorityField('create', @json(old('prioritas', \App\Models\Order::PRIORITY_LOW)));
                syncModalNoteField('create', @json(old('catatan_status', \App\Domain\Orders\Enums\OrderUserNoteStatus::ApprovedWorkshop->value)), @json(old('catatan', '')));
                openCreateOrderModal();
            } else {
                syncModalNoteField('create', defaultWorkshopStatus, '');
            }

            if (oldFormContext === 'edit') {
                const editTrigger = oldEditOrderKey
                    ? document.querySelector(`.edit-order-trigger[data-order-key="${oldEditOrderKey}"]`)
                    : null;

                if (editTrigger && editForm) {
                    editForm.action = editTrigger.dataset.action || '#';
                    document.getElementById('editOriginalOrder').value = oldEditOrderKey || '';
                    document.getElementById('editTanggalOrder').value = @json(old('tanggal_order', $today));
                    document.getElementById('editCatatan').value = @json(old('catatan', ''));
                    document.getElementById('editDeskripsi').value = @json(old('deskripsi', 'Order pekerjaan bengkel'));
                    editCatatanStatus.value = @json(old('catatan_status', \App\Domain\Orders\Enums\OrderUserNoteStatus::ApprovedWorkshop->value));
                    syncPriorityField('edit', @json(old('prioritas', \App\Models\Order::PRIORITY_LOW)));
                    syncSeksiSelect(editUnitKerja, editSeksi, @json(old('seksi')));
                    syncModalNoteField('edit', @json(old('catatan_status', \App\Domain\Orders\Enums\OrderUserNoteStatus::ApprovedWorkshop->value)), @json(old('catatan', '')));
                    openEditOrderModal();
                }
            }

            document.querySelectorAll('.auto-save-select').forEach((select) => {
                select.addEventListener('change', async () => {
                    const orderKey = select.closest('tr')?.querySelector('.workshop-order-key')?.value;
                    if (!orderKey) return;

                    await sendPatch(buildUrl(orderKey), {
                        [select.dataset.field || select.name]: select.value,
                    }, select);

                    if (select.name === 'konfirmasi_anggaran' || select.name === 'progress_status') {
                        setTimeout(() => window.location.reload(), 500);
                    }
                });
            });

            document.querySelectorAll('.save-note-btn').forEach((button) => {
                button.addEventListener('click', async () => {
                    const row = button.closest('tr');
                    const orderKey = row?.querySelector('.workshop-order-key')?.value;
                    const field = button.dataset.field;
                    if (!row || !orderKey || !field) return;

                    const source = row.querySelector(`[name="${field}"]`);
                    if (!source) return;

                    await sendPatch(buildUrl(orderKey), {
                        [field]: source.value,
                    }, button);
                });
            });

            document.querySelectorAll('.workshop-detail-trigger').forEach((button) => {
                button.addEventListener('click', () => {
                    const documents = JSON.parse(button.dataset.documents || '[]');
                    const qcFlow = JSON.parse(button.dataset.qcFlow || '[]');
                    const signedCount = qcFlow.filter((item) => item.status === 'signed').length;

                    document.getElementById('workshopDetailTitle').textContent = button.dataset.title || 'Order';
                    document.getElementById('workshopDetailJob').textContent = button.dataset.job || '-';
                    document.getElementById('workshopDetailUnit').textContent = button.dataset.unit || '-';
                    document.getElementById('workshopDetailSeksi').textContent = button.dataset.seksi || '-';
                    document.getElementById('workshopDetailCatatan').textContent = button.dataset.catatan || '-';
                    document.getElementById('workshopDetailQcSummary').textContent = button.dataset.qcFlowSummary || 'QC belum dibuat.';
                    document.getElementById('workshopDetailQcCount').textContent = `${signedCount}/${qcFlow.length || 3}`;
                    document.getElementById('workshopDetailDocuments').innerHTML = documents.length
                        ? documents.map((document) => `
                            <a href="${escapeHtml(document.url || '#')}" target="_blank" rel="noopener" class="inline-flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-[11px] font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                <span>${escapeHtml(document.label || 'Dokumen')}</span>
                                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                            </a>
                        `).join('')
                        : '<div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-center text-[11px] text-slate-500 sm:col-span-2">Belum ada dokumen.</div>';
                    document.getElementById('workshopDetailQcFlow').innerHTML = qcFlow.length
                        ? qcFlow.map((item) => {
                            const tone = item.status === 'signed'
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                : item.status === 'pending'
                                    ? 'border-blue-200 bg-blue-50 text-blue-700'
                                    : item.status === 'missing'
                                        ? 'border-amber-200 bg-amber-50 text-amber-700'
                                        : 'border-slate-200 bg-white text-slate-500';
                            const dot = item.status === 'signed'
                                ? 'bg-emerald-500'
                                : item.status === 'pending'
                                    ? 'bg-blue-500'
                                    : item.status === 'missing'
                                        ? 'bg-amber-500'
                                        : 'bg-slate-300';

                            return `
                                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <div class="flex items-start gap-2">
                                        <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full ${dot}"></span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <div class="text-[11px] font-bold text-slate-900">Step ${escapeHtml(item.step || '-')} - ${escapeHtml(item.role || '-')}</div>
                                                <span class="inline-flex rounded-full border px-2 py-0.5 text-[9px] font-bold ${tone}">${escapeHtml(item.status_label || '-')}</span>
                                            </div>
                                            <div class="mt-1 text-[11px] font-semibold text-slate-700">${escapeHtml(item.name || '-')}</div>
                                            <div class="mt-0.5 text-[10px] leading-4 text-slate-500">${escapeHtml(item.position || '-')}</div>
                                            ${item.scope ? `<div class="mt-0.5 text-[10px] leading-4 text-slate-400">${escapeHtml(item.scope)}</div>` : ''}
                                            ${item.signed_at ? `<div class="mt-1 text-[10px] font-semibold text-emerald-700">TTD: ${escapeHtml(item.signed_at)}</div>` : ''}
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('')
                        : '<div class="rounded-xl border border-dashed border-blue-100 bg-white px-3 py-4 text-center text-[11px] text-slate-500">QC belum dibuat atau flow approval belum tersedia.</div>';

                    window.lucide?.createIcons();
                    openWorkshopDetail();
                });
            });

            document.querySelectorAll('.workshop-flow-trigger').forEach((button) => {
                button.addEventListener('click', () => {
                    const checklist = JSON.parse(button.dataset.checklist || '[]');
                    document.getElementById('workshopFlowTitle').textContent = button.dataset.title || 'Order';
                    document.getElementById('workshopFlowSummary').textContent = button.dataset.summary || '-';
                    document.getElementById('workshopFlowNext').textContent = button.dataset.next || '-';
                    document.getElementById('workshopFlowChecklist').innerHTML = checklist.map((item) => `
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                            <div class="min-w-0">
                                <div class="font-medium text-slate-700">${escapeHtml(item.label || '-')}</div>
                                <div class="mt-0.5 truncate text-[10px] text-slate-400">${escapeHtml(item.value || '-')}</div>
                            </div>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-semibold ${item.ready ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500'}">${item.ready ? 'OK' : 'Belum'}</span>
                        </div>
                    `).join('');
                    openWorkshopFlow();
                });
            });

            workshopFlowOverlay?.addEventListener('click', closeWorkshopFlow);
            workshopDetailOverlay?.addEventListener('click', closeWorkshopDetail);
            orderModalOverlay?.addEventListener('click', closeOrderModals);
            document.addEventListener('click', (event) => {
                if (!event.target.closest('.row-action-menu')) {
                    closeRowActionMenus();
                }
            });
            document.querySelectorAll('[data-close-order-modal]').forEach((button) => {
                button.addEventListener('click', closeOrderModals);
            });
            document.querySelectorAll('[data-close-workshop-detail]').forEach((button) => {
                button.addEventListener('click', closeWorkshopDetail);
            });
            document.querySelectorAll('[data-close-workshop-flow]').forEach((button) => {
                button.addEventListener('click', closeWorkshopFlow);
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeOrderModals();
                    closeWorkshopDetail();
                    closeWorkshopFlow();
                    closeRowActionMenus();
                }
            });

            document.querySelectorAll('.copy-qc-approval').forEach((button) => {
                button.addEventListener('click', async () => {
                    const link = button.dataset.link || '';
                    const roleLabel = button.dataset.roleLabel || 'Approval QC';
                    const fallbackCopy = () => {
                        const input = document.createElement('textarea');
                        input.value = link;
                        input.setAttribute('readonly', 'readonly');
                        input.style.position = 'fixed';
                        input.style.left = '-9999px';
                        document.body.appendChild(input);
                        input.select();
                        const copied = document.execCommand('copy');
                        input.remove();

                        return copied;
                    };

                    try {
                        if (navigator.clipboard?.writeText) {
                            await navigator.clipboard.writeText(link);
                        } else if (!fallbackCopy()) {
                            throw new Error('Clipboard tidak tersedia.');
                        }

                        showToast(`Link TTD ${roleLabel} disalin.`);
                    } catch (error) {
                        if (fallbackCopy()) {
                            showToast(`Link TTD ${roleLabel} disalin.`);
                            return;
                        }

                        showToast('Browser memblokir akses clipboard.', 'error');
                    }
                });
            });

            const successFlash = document.getElementById('flash-success');
            const errorFlash = document.getElementById('flash-error');

            if (successFlash?.dataset.message) {
                showAlert({
                    icon: 'success',
                    title: 'Berhasil',
                    text: successFlash.dataset.message,
                    timer: 1600,
                    showConfirmButton: false,
                });
            }

            if (errorFlash?.dataset.message) {
                showAlert({
                    icon: 'error',
                    title: 'Gagal',
                    text: errorFlash.dataset.message,
                });
            }

            document.querySelectorAll('.delete-order-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    closeRowActionMenus();

                    if (!swal) {
                        if (confirm('Hapus order ini?')) {
                            form.submit();
                        }
                        return;
                    }

                    const result = await swal.fire({
                        icon: 'warning',
                        title: 'Hapus order?',
                        text: 'Data order akan dihapus permanen.',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#e11d48',
                    });

                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
</x-layouts.admin>
