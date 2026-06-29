<x-layouts.admin title="Order">
    <style>
        .orders-index-filter {
            display: grid;
            gap: 0.5rem;
        }

        @media (min-width: 768px) {
            .orders-index-filter {
                grid-template-columns: minmax(0, 1.7fr) minmax(220px, 0.8fr) auto;
                align-items: end;
            }
        }

        .order-form-modal-panel {
            max-height: calc(100vh - 1rem);
            overflow: hidden;
        }

        .order-form-modal-form {
            max-height: calc(100vh - 1rem);
            overflow-y: auto;
            padding: 0.9rem;
        }

        .order-form-modal-header {
            position: sticky;
            top: 0;
            z-index: 2;
            margin: -0.9rem -0.9rem 0;
            padding: 0.85rem 0.9rem;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
        }

        .order-form-modal-grid {
            margin-top: 0.9rem;
            gap: 0.75rem;
        }

        .order-form-modal-grid label {
            margin-bottom: 0.35rem !important;
            font-size: 0.78rem !important;
            line-height: 1rem;
        }

        .order-form-modal-grid input:not([type="checkbox"]):not([type="radio"]),
        .order-form-modal-grid select,
        .order-form-modal-grid textarea {
            min-height: 2.35rem;
            border-color: #cbd5e1 !important;
            border-radius: 0.65rem !important;
            padding: 0.5rem 0.75rem !important;
            font-size: 16px !important;
        }

        .order-form-modal-grid textarea {
            min-height: 4.75rem;
        }

        .order-form-modal-actions {
            position: sticky;
            bottom: 0;
            z-index: 2;
            margin: 0.9rem -0.9rem -0.9rem;
            padding: 0.75rem 0.9rem;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
        }

        @media (min-width: 640px) {
            .order-form-modal-panel,
            .order-form-modal-form {
                max-height: calc(100vh - 2rem);
            }

            .order-form-modal-form {
                padding: 1.25rem;
            }

            .order-form-modal-header {
                margin: -1.25rem -1.25rem 0;
                padding: 1rem 1.25rem;
            }

            .order-form-modal-grid {
                margin-top: 1.25rem;
                gap: 1rem;
            }

            .order-form-modal-actions {
                margin: 1.25rem -1.25rem -1.25rem;
                padding: 1rem 1.25rem;
            }
        }
    </style>

    @php
        $today = now()->format('Y-m-d');
    @endphp

    @if (session('status'))
        <div id="flash-success" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    @if ($errors->any())
        <div id="flash-error" data-message="{{ implode(' • ', $errors->all()) }}" class="hidden"></div>
    @endif

    <div class="order-list-compact orders-index-compact space-y-4">
        <div class="space-y-4">
            <section
                class="order-list-hero rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm"
                style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                            <i data-lucide="inbox" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <h1 class="text-[1.3rem] font-bold leading-none tracking-tight text-slate-900">Order</h1>
                            <p class="mt-1.5 text-[13px] text-slate-500">Pantau order pekerjaan dan kawat las dengan filter cepat.</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        id="openCreateOrderModal"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-500 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-600"
                    >
                        <i data-lucide="rocket" class="h-[13px] w-[13px]"></i>
                        Buat Order
                    </button>
                </div>
            </section>

            <section class="order-list-panel overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <form method="GET" action="{{ route('admin.orders.index') }}" class="orders-index-filter">
                            <div class="flex min-w-0 flex-col">
                                <label for="search" class="mb-1 text-[10px] font-semibold text-slate-700">Pencarian</label>
                                <input
                                    id="search"
                                    name="search"
                                    type="text"
                                    value="{{ $search }}"
                                    placeholder="Cari nomor order atau pekerjaan..."
                                    class="h-9 w-full rounded-lg border border-slate-300 px-3 text-[12px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none"
                                >
                            </div>

                            <div class="flex min-w-0 flex-col">
                                <label for="document_status" class="mb-1 text-[10px] font-semibold text-slate-700">Kelengkapan Dokumen</label>
                                <select
                                    id="document_status"
                                    name="document_status"
                                    class="h-9 w-full rounded-lg border border-slate-300 px-3 text-[12px] text-slate-700 focus:border-blue-500 focus:outline-none"
                                >
                                    <option value="">Semua Dokumen</option>
                                    <option value="complete" @selected($selectedDocumentStatus === 'complete')>Dokumen Lengkap</option>
                                    <option value="incomplete" @selected($selectedDocumentStatus === 'incomplete')>Dokumen Belum Lengkap</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-1.5 md:pb-px">
                                <button type="submit" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-3 text-[11px] font-semibold text-white transition hover:bg-blue-700" title="Cari dan filter">
                                    <i data-lucide="search" class="h-[13px] w-[13px]"></i>
                                    Cari
                                </button>

                                <a href="{{ route('admin.orders.index') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50" title="Reset">
                                    <i data-lucide="rotate-ccw" class="h-[13px] w-[13px]"></i>
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-[13px] text-slate-700">
                            <thead class="bg-slate-200/80 text-slate-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase">Nomor Order</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase">Detail Pekerjaan</th>
                                    <th class="w-[300px] px-4 py-3 text-left text-[11px] font-semibold uppercase">Status & Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @forelse ($orders as $index => $order)
                                    @php
                                        $documentByType = $order->documents->keyBy(fn ($document) => $document->jenis_dokumen->value);
                                        $abnormalDocument = $documentByType->get('abnormalitas');
                                        $gambarDocument = $documentByType->get('gambar_teknik');
                                        $hasAbnormal = $abnormalDocument !== null;
                                        $hasGambar = $gambarDocument !== null;
                                        $hasScope = $order->scopeOfWork !== null;
                                        $currentNoteStatus = $order->catatan_status?->value ?? 'pending';
$activeInitialWorkSignature = $order->initialWork?->signatures
    ?->firstWhere('status', \App\Models\InitialWorkSignature::STATUS_PENDING);

$activeInitialWorkApprovalUrl = $activeInitialWorkSignature?->approvalUrl();
$activeInitialWorkWhatsappUrl = $activeInitialWorkApprovalUrl ? \App\Support\ApprovalWhatsappLink::forInitialWork($activeInitialWorkSignature) : null;
$activeInitialWorkRoleLabel = $activeInitialWorkSignature?->displayRoleLabel();
$activeInitialWorkDisplayLabel = $activeInitialWorkRoleLabel ?: match ($activeInitialWorkSignature?->role_key) {
    \App\Models\InitialWorkSignature::ROLE_MANAGER => 'Manager',
    \App\Models\InitialWorkSignature::ROLE_SENIOR_MANAGER => 'Senior Manager',
    default => 'Approval',
};
$initialWorkManagerSignature = $order->initialWork?->signatures
    ?->firstWhere('role_key', \App\Models\InitialWorkSignature::ROLE_MANAGER);

$initialWorkSeniorSignature = $order->initialWork?->signatures
    ?->firstWhere('role_key', \App\Models\InitialWorkSignature::ROLE_SENIOR_MANAGER);
$initialWorkFlowItems = collect([$initialWorkManagerSignature, $initialWorkSeniorSignature])
    ->filter()
    ->map(fn ($signature) => [
        'role' => $signature->displayRoleLabel() ?: $signature->role_key,
        'original_role' => $signature->role_label ?: $signature->role_key,
        'name' => $signature->signer_name ?: '-',
        'signer_user_id' => $signature->signer_user_id,
        'status' => $signature->status,
        'status_label' => match ($signature->status) {
            \App\Models\InitialWorkSignature::STATUS_SIGNED => 'Sudah TTD',
            \App\Models\InitialWorkSignature::STATUS_PENDING => $signature->tokenExpired() ? 'Token kedaluwarsa' : 'Menunggu TTD',
            \App\Models\InitialWorkSignature::STATUS_LOCKED => 'Belum aktif',
            \App\Models\InitialWorkSignature::STATUS_MISSING => 'Signer belum lengkap',
            default => 'Belum dibuat',
        },
        'delegated_from_name' => $signature->delegated_from_name ?: '',
        'delegation_reason' => $signature->delegation_reason ?: '',
        'can_reassign' => ! in_array($signature->status, [\App\Models\InitialWorkSignature::STATUS_SIGNED], true),
        'reassign_url' => route('admin.orders.approval-signatures.initial-work.reassign', $signature),
        'signed_at' => $signature->signed_at?->format('d/m/Y H:i') ?: '',
        'is_active' => $activeInitialWorkSignature?->is($signature) ?? false,
    ])->values();
$initialWorkApprovalExpired = $activeInitialWorkSignature?->tokenExpired() ?? false;
$initialWorkFlowSummary = match (true) {
    $initialWorkManagerSignature?->isSigned() && $initialWorkSeniorSignature?->isSigned() => 'Semua approval Initial Work sudah selesai.',
    $initialWorkApprovalExpired => 'Token '.$activeInitialWorkDisplayLabel.' kedaluwarsa dan harus dibuat ulang.',
    $activeInitialWorkSignature !== null => 'Menunggu TTD '.$activeInitialWorkDisplayLabel.'.',
    $initialWorkManagerSignature?->status === \App\Models\InitialWorkSignature::STATUS_MISSING
        || $initialWorkSeniorSignature?->status === \App\Models\InitialWorkSignature::STATUS_MISSING => 'Signer Initial Work belum lengkap.',
    default => 'Approval Initial Work belum aktif.',
};
                                        $noteDetailOptions = $userNoteDetailOptions[$currentNoteStatus] ?? [];
                                        $noteStatusClasses = match ($currentNoteStatus) {
                                            'approved_jasa' => 'bg-amber-100 text-amber-700',
                                            'approved_workshop' => 'bg-blue-100 text-blue-700',
                                            'approved_workshop_jasa' => 'bg-emerald-100 text-emerald-700',
                                            'rejected' => 'bg-rose-100 text-rose-700',
                                            default => 'bg-slate-100 text-slate-600',
                                        };
                                        $priorityGroup = \App\Models\Order::priorityPrimaryFor($order->prioritas);
                                        $priorityLabel = match ($priorityGroup) {
                                            'emergency' => 'Emergency',
                                            'high' => 'High',
                                            default => 'Medium',
                                        };
                                        $priorityTextClasses = match ($priorityGroup) {
                                            'emergency' => 'text-rose-600',
                                            'high' => 'text-amber-600',
                                            default => 'text-emerald-600',
                                        };
                                        $canManageInitialWork = $priorityGroup === 'emergency' || $order->initialWork !== null;
                                        $routesToHpp = in_array($currentNoteStatus, ['approved_jasa', 'approved_workshop_jasa'], true);
                                        $routesToWorkshop = in_array($currentNoteStatus, ['approved_workshop', 'approved_workshop_jasa'], true);
                                        $needsInitialWork = $priorityGroup === 'emergency' && $order->initialWork === null;
                                        $hppMissingCount = collect([$routesToHpp, $hasAbnormal, $hasGambar, $hasScope])
                                            ->filter(fn ($ready) => ! $ready)
                                            ->count();
                                        $routeLabel = match (true) {
                                            $routesToHpp && $routesToWorkshop => 'HPP + Bengkel',
                                            $routesToHpp => 'HPP',
                                            $routesToWorkshop => 'Bengkel',
                                            $currentNoteStatus === 'reject' => 'Ditolak',
                                            default => 'Belum Arah',
                                        };
                                        $routeBadgeClasses = match (true) {
                                            $routesToHpp && $routesToWorkshop => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                            $routesToHpp => 'border-amber-200 bg-amber-50 text-amber-700',
                                            $routesToWorkshop => 'border-blue-200 bg-blue-50 text-blue-700',
                                            $currentNoteStatus === 'reject' => 'border-rose-200 bg-rose-50 text-rose-700',
                                            default => 'border-slate-200 bg-slate-50 text-slate-500',
                                        };
                                        $hppBadgeLabel = $routesToHpp
                                            ? ($hppMissingCount === 0 ? 'Siap HPP' : 'HPP -'.$hppMissingCount)
                                            : 'HPP Off';
                                        $hppBadgeClasses = $routesToHpp && $hppMissingCount === 0
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                            : ($routesToHpp ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-slate-200 bg-slate-50 text-slate-500');
                                        $initialWorkBadgeLabel = $priorityGroup === 'emergency'
                                            ? ($order->initialWork ? 'IW Ada' : 'IW Belum')
                                            : 'IW N/A';
                                        $initialWorkBadgeClasses = $priorityGroup === 'emergency'
                                            ? ($order->initialWork ? 'border-orange-200 bg-orange-50 text-orange-700' : 'border-rose-200 bg-rose-50 text-rose-700')
                                            : 'border-slate-200 bg-slate-50 text-slate-500';
                                        $flowChecklist = [
                                            ['label' => 'Arah ke HPP/Jasa', 'ready' => $routesToHpp],
                                            ['label' => 'Abnormalitas', 'ready' => $hasAbnormal],
                                            ['label' => 'Gambar Teknik', 'ready' => $hasGambar],
                                            ['label' => 'Scope of Work', 'ready' => $hasScope],
                                            ['label' => 'Initial Work Emergency', 'ready' => $priorityGroup !== 'emergency' || $order->initialWork !== null],
                                        ];
                                        $flowNextStep = match (true) {
                                            $needsInitialWork => 'Buat Initial Work untuk jalur emergency.',
                                            ! $routesToHpp && ! $routesToWorkshop && $currentNoteStatus !== 'reject' => 'Tentukan status catatan order.',
                                            $routesToHpp && ! $hasAbnormal => 'Upload dokumen Abnormalitas.',
                                            $routesToHpp && ! $hasGambar => 'Upload Gambar Teknik.',
                                            $routesToHpp && ! $hasScope => 'Buat Scope of Work.',
                                            $routesToHpp && $hppMissingCount === 0 => 'Siap masuk Create HPP.',
                                            $routesToWorkshop => 'Pantau detail di Order Pekerjaan Bengkel.',
                                            $currentNoteStatus === 'reject' => 'Order ditolak.',
                                            default => 'Tidak ada aksi lanjutan.',
                                        };
                                        $documentIndicators = [
                                            [
                                                'label' => 'Abnormalitas',
                                                'available' => $hasAbnormal,
                                                'icon' => 'file',
                                                'url' => $hasAbnormal ? route('admin.orders.documents.preview', [$order, $abnormalDocument]) : null,
                                                'classes' => $hasAbnormal
                                                    ? 'border-red-200 bg-red-50 text-red-700'
                                                    : 'border-slate-200 bg-slate-50 text-slate-500',
                                            ],
                                            [
                                                'label' => 'Gambar Teknik',
                                                'available' => $hasGambar,
                                                'icon' => 'image',
                                                'url' => $hasGambar ? route('admin.orders.documents.preview', [$order, $gambarDocument]) : null,
                                                'classes' => $hasGambar
                                                    ? 'border-blue-200 bg-blue-50 text-blue-700'
                                                    : 'border-slate-200 bg-slate-50 text-slate-500',
                                            ],
                                            [
                                                'label' => 'Scope of Work',
                                                'available' => $hasScope,
                                                'icon' => 'file-text',
                                                'url' => $hasScope ? route('admin.orders.scope-of-work.pdf', [$order, $order->scopeOfWork]) : null,
                                                'classes' => $hasScope
                                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                    : 'border-slate-200 bg-slate-50 text-slate-500',
                                            ],
                                        ];
                                    @endphp

                                    <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' }} align-top transition hover:bg-slate-50">
                                        <td class="px-3 py-2 text-[11px] font-semibold text-slate-600">
                                            <div class="min-w-[150px] rounded-xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm">
                                                <div class="text-[12px] font-bold tracking-[0.01em] text-slate-900">{{ $order->nomor_order }}</div>
                                                @if ($order->notifikasi)
                                                    <div class="mt-0.5 text-[10px] font-medium text-blue-600">Notif: {{ $order->notifikasi }}</div>
                                                @endif
                                                <div class="mt-1.5 border-t border-slate-100 pt-1.5">
                                                    <div class="text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-400">Tanggal Order</div>
                                                    <div class="mt-0.5 text-[10px] font-semibold text-slate-700">{{ $order->tanggal_order->format('Y-m-d') }}</div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-3 py-2">
                                            <div class="grid gap-2">
                                                <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="min-w-0">
                                                            <div class="text-[13px] font-semibold leading-snug text-slate-900">{{ $order->nama_pekerjaan }}</div>
                                                            @if ($order->catatan)
                                                                <span class="mt-1.5 inline-flex max-w-[260px] truncate rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-medium text-slate-600" title="{{ $order->catatan }}">
                                                                    {{ $order->catatan }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="flex shrink-0 flex-col items-end gap-1">
                                                            <span class="text-[10px] font-bold uppercase tracking-[0.14em] {{ $priorityTextClasses }}">
                                                                {{ $priorityLabel }}
                                                            </span>
                                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-semibold {{ $noteStatusClasses }}">
                                                                {{ $order->catatan_status?->label() ?? 'Pending' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2 grid gap-2 border-t border-slate-100 pt-2 md:grid-cols-2">
                                                        <div>
                                                            <div class="text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-400">Unit Kerja</div>
                                                            <div class="mt-0.5 text-[11px] font-medium leading-4 text-slate-700">{{ $order->unit_kerja }}</div>
                                                        </div>

                                                        <div>
                                                            <div class="text-[9px] font-semibold uppercase tracking-[0.12em] text-blue-400">Seksi</div>
                                                            <div class="mt-0.5 text-[11px] font-medium leading-4 text-blue-700">{{ $order->seksi }}</div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </td>

                                        <td class="px-3 py-2">
                                            <div class="rounded-xl border border-slate-200 bg-white px-2 py-2 shadow-sm">
                                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                                    @if ($canManageInitialWork)
                                                        @if ($order->initialWork)
                                                            <div class="mr-auto inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-1.5 py-1">
                                                                <span class="text-[8px] font-semibold uppercase tracking-[0.1em] text-orange-700">
                                                                    IW
                                                                </span>

                                                                <a
                                                                    href="{{ route('admin.orders.initial-work.pdf', [$order, $order->initialWork]) }}"
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    class="inline-flex h-6 items-center gap-1 rounded-md border border-orange-200 bg-white px-1.5 text-[9px] font-semibold text-orange-700 transition hover:bg-orange-100"
                                                                    title="Lihat Initial Work PDF"
                                                                >
                                                                    <i data-lucide="file-text" class="h-2.5 w-2.5"></i>
                                                                    <span>PDF</span>
                                                                </a>

                                                                <button
                                                                    type="button"
                                                                    class="edit-initial-work-trigger inline-flex h-6 items-center gap-1 rounded-md border border-orange-200 bg-white px-1.5 text-[9px] font-semibold text-orange-700 transition hover:bg-orange-100"
                                                                    data-mode="edit"
                                                                    data-action="{{ route('admin.orders.initial-work.update', [$order, $order->initialWork]) }}"
                                                                    data-order-key="{{ $order->getRouteKey() }}"
                                                                    data-outline-agreement-id="{{ $order->initialWork->outline_agreement_id }}"
                                                                    data-nomor-order="{{ $order->nomor_order }}"
                                                                    data-notifikasi="{{ $order->notifikasi }}"
                                                                    data-unit-kerja="{{ $order->unit_kerja }}"
                                                                    data-seksi="{{ $order->seksi }}"
                                                                    data-nama-pekerjaan="{{ $order->nama_pekerjaan }}"
                                                                    data-document-number="{{ $order->initialWork->nomor_initial_work }}"
                                                                    data-kepada-yth="{{ $order->initialWork->kepada_yth }}"
                                                                    data-perihal="{{ $order->initialWork->perihal }}"
                                                                    data-tanggal="{{ optional($order->initialWork->tanggal_initial_work)->format('Y-m-d') }}"
                                                                    data-keterangan-pekerjaan="{{ $order->initialWork->keterangan_pekerjaan }}"
                                                                    data-functional-location='@json($order->initialWork->functional_location ?? [])'
                                                                    data-scope-pekerjaan='@json($order->initialWork->scope_pekerjaan ?? [])'
                                                                    data-qty='@json($order->initialWork->qty ?? [])'
                                                                    data-stn='@json($order->initialWork->stn ?? [])'
                                                                    data-keterangan='@json($order->initialWork->keterangan ?? [])'
                                                                    title="Edit Initial Work"
                                                                >
                                                                    <i data-lucide="clipboard-pen-line" class="h-2.5 w-2.5"></i>
                                                                    <span>Edit</span>
                                                                </button>

                                                                <button
                                                                    type="button"
                                                                    class="approval-signature-info-trigger inline-flex h-6 w-6 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 transition hover:bg-blue-100"
                                                                    title="Informasi approval Initial Work"
                                                                    aria-label="Informasi approval Initial Work"
                                                                    data-title="Approval Initial Work"
                                                                    data-summary="{{ $initialWorkFlowSummary }}"
                                                                    data-checklist='@json($initialWorkFlowItems)'
                                                                    data-active-role="{{ $activeInitialWorkSignature ? $activeInitialWorkDisplayLabel : '' }}"
                                                                    data-active-signer="{{ $activeInitialWorkSignature?->signer_name ?: '' }}"
                                                                    data-expiry="{{ $activeInitialWorkSignature?->token_expires_at ? ($initialWorkApprovalExpired ? 'Kedaluwarsa: ' : 'Berlaku sampai: ').$activeInitialWorkSignature->token_expires_at->format('d/m/Y H:i') : '' }}"
                                                                    data-approval-url="{{ $activeInitialWorkApprovalUrl ?: '' }}"
                                                                    data-whatsapp-url="{{ $activeInitialWorkWhatsappUrl ?: '' }}"
                                                                    data-resend-url="{{ $activeInitialWorkApprovalUrl ? route('admin.orders.initial-work.approval.resend', [$order, $order->initialWork]) : '' }}"
                                                                    data-regenerate-url="{{ $initialWorkApprovalExpired ? route('admin.orders.initial-work.approval.regenerate', [$order, $order->initialWork]) : '' }}"
                                                                >
                                                                    <i data-lucide="info" class="h-3 w-3"></i>
                                                                </button>

                                                                @if (
                                                                    $initialWorkManagerSignature?->status === \App\Models\InitialWorkSignature::STATUS_MISSING
                                                                    || $initialWorkSeniorSignature?->status === \App\Models\InitialWorkSignature::STATUS_MISSING
                                                                )
                                                                    <span
                                                                        class="inline-flex h-6 items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-1.5 text-[9px] font-semibold text-amber-700"
                                                                        title="Token belum dibuat: Manager/Senior Manager belum terisi di Struktur Organisasi untuk OA ini."
                                                                    >
                                                                        <i data-lucide="user-x" class="h-2.5 w-2.5"></i>
                                                                        <span>Signer</span>
                                                                    </span>
                                                                @elseif ($initialWorkManagerSignature?->isSigned() && $initialWorkSeniorSignature?->isSigned())
                                                                    <span
                                                                        class="inline-flex h-6 items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-1.5 text-[9px] font-semibold text-emerald-700"
                                                                        title="Semua approval Initial Work sudah selesai"
                                                                    >
                                                                        <i data-lucide="check-check" class="h-2.5 w-2.5"></i>
                                                                        <span>OK</span>
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <button
                                                                type="button"
                                                                class="create-initial-work-trigger mr-auto inline-flex h-7 items-center justify-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2 text-[9px] font-semibold text-orange-700 transition hover:bg-orange-100"
                                                                data-mode="create"
                                                                data-action="{{ route('admin.orders.initial-work.store', $order) }}"
                                                                data-order-key="{{ $order->getRouteKey() }}"
                                                                data-outline-agreement-id="{{ $order->latestHpp?->outline_agreement_id }}"
                                                                data-nomor-order="{{ $order->nomor_order }}"
                                                                data-notifikasi="{{ $order->notifikasi }}"
                                                                data-unit-kerja="{{ $order->unit_kerja }}"
                                                                data-seksi="{{ $order->seksi }}"
                                                                data-nama-pekerjaan="{{ $order->nama_pekerjaan }}"
                                                                data-document-number="{{ $initialWorkPreviewNumber }}"
                                                                title="Buat Initial Work"
                                                            >
                                                                <i data-lucide="clipboard-plus" class="h-2.5 w-2.5"></i>
                                                                <span>Buat IW</span>
                                                            </button>
                                                        @endif
                                                    @endif

                                                    <button
                                                        type="button"
                                                        class="order-flow-trigger inline-flex h-7 w-7 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 transition hover:bg-blue-100"
                                                        data-title="{{ $order->nomor_order }}"
                                                        data-route="{{ $routeLabel }}"
                                                        data-next="{{ $flowNextStep }}"
                                                        data-checklist='@json($flowChecklist)'
                                                        title="Detail alur"
                                                    >
                                                        <i data-lucide="info" class="h-3 w-3"></i>
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="edit-order-trigger inline-flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm transition hover:bg-emerald-700"
                                                        data-action="{{ route('admin.orders.update', $order) }}"
                                                        data-order-key="{{ $order->getRouteKey() }}"
                                                        data-nomor-order="{{ $order->nomor_order }}"
                                                        data-notifikasi="{{ $order->notifikasi }}"
                                                        data-nama-pekerjaan="{{ $order->nama_pekerjaan }}"
                                                        data-unit-kerja="{{ $order->unit_kerja }}"
                                                        data-prioritas="{{ $order->prioritas }}"
                                                        data-target-selesai="{{ optional($order->target_selesai)->format('Y-m-d') }}"
                                                        data-seksi="{{ $order->seksi }}"
                                                        data-catatan-status="{{ $currentNoteStatus }}"
                                                        data-catatan="{{ $order->catatan }}"
                                                        data-tanggal-order="{{ optional($order->tanggal_order)->format('Y-m-d') }}"
                                                        title="Edit"
                                                    >
                                                        <i data-lucide="pencil" class="h-3 w-3"></i>
                                                    </button>

                                                    <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" class="delete-order-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="submit"
                                                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-rose-600 text-white shadow-sm transition hover:bg-rose-700"
                                                            title="Hapus"
                                                        >
                                                            <i data-lucide="trash-2" class="h-3 w-3"></i>
                                                        </button>
                                                    </form>
                                                </div>

                                                <div class="mt-1.5 flex flex-wrap items-center justify-end gap-1 border-t border-slate-100 pt-1.5">
                                                    @foreach ($documentIndicators as $indicator)
                                                        @php
                                                            $shortDocumentLabel = match ($indicator['label']) {
                                                                'Abnormalitas' => 'Abn',
                                                                'Gambar Teknik' => 'Gambar',
                                                                'Scope of Work' => 'SOW',
                                                                default => $indicator['label'],
                                                            };
                                                        @endphp

                                                        @if ($indicator['available'] && $indicator['url'])
                                                            <a
                                                                href="{{ $indicator['url'] }}"
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                class="inline-flex h-6 items-center gap-1 rounded-md border px-1.5 text-[9px] font-semibold transition hover:bg-white {{ $indicator['classes'] }}"
                                                                title="Buka {{ $indicator['label'] }}"
                                                            >
                                                                <i data-lucide="{{ $indicator['icon'] }}" class="h-2.5 w-2.5"></i>
                                                                <span>{{ $shortDocumentLabel }}</span>
                                                            </a>
                                                        @else
                                                            <span
                                                                class="inline-flex h-6 items-center gap-1 rounded-md border px-1.5 text-[9px] font-semibold {{ $indicator['classes'] }}"
                                                                title="{{ $indicator['label'] }} belum tersedia"
                                                            >
                                                                <i data-lucide="{{ $indicator['icon'] }}" class="h-2.5 w-2.5"></i>
                                                                <span>{{ $shortDocumentLabel }}</span>
                                                            </span>
                                                        @endif
                                                    @endforeach

                                                    <a
                                                        href="{{ route('admin.orders.show', $order) }}"
                                                        class="inline-flex h-6 items-center gap-1 rounded-md border border-slate-300 bg-white px-1.5 text-[9px] font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                                        title="Edit Dokumen"
                                                    >
                                                        <i data-lucide="folder-open" class="h-2.5 w-2.5"></i>
                                                        <span>Detail</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-14 text-center">
                                            <div class="mx-auto max-w-md space-y-2">
                                                <div class="text-base font-semibold text-slate-900">Belum ada data order.</div>
                                                <div class="text-sm text-slate-500">Gunakan tombol Buat Order untuk menambahkan order pertama.</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($orders->hasPages())
                        <div class="border-t border-slate-200 px-4 py-4">
                            {{ $orders->links() }}
                        </div>
                    @endif
            </section>
        </div>

    </div>

    <div id="orderModalOverlay" class="fixed inset-0 z-40 hidden bg-slate-950/55"></div>

    <div id="orderFlowModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="w-full max-w-md rounded-3xl bg-white p-5 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-500">Status Alur</div>
                    <h2 id="orderFlowTitle" class="mt-1 text-lg font-bold text-slate-900">Order</h2>
                    <p id="orderFlowRoute" class="mt-1 text-[11px] font-semibold text-slate-500">-</p>
                </div>
                <button type="button" data-close-order-modal class="text-2xl leading-none text-slate-400 transition hover:text-slate-700">&times;</button>
            </div>

            <div id="orderFlowChecklist" class="mt-4 space-y-1.5 text-[11px]"></div>

            <div class="mt-4 rounded-2xl border border-blue-100 bg-blue-50 px-3 py-2.5">
                <div class="text-[9px] font-semibold uppercase tracking-[0.14em] text-blue-500">Next Step</div>
                <div id="orderFlowNext" class="mt-1 text-[11px] font-semibold leading-5 text-slate-700">-</div>
            </div>
        </div>
    </div>

    <div id="createOrderModal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto p-2 sm:items-center sm:p-4">
        <div class="order-form-modal-panel w-full max-w-[860px] rounded-2xl bg-white shadow-2xl sm:rounded-3xl">
            <form method="POST" action="{{ route('admin.orders.store') }}" class="order-form-modal-form">
                @csrf
                <input type="hidden" name="form_context" value="create">
                <input type="hidden" name="tanggal_order" id="createTanggalOrder" value="{{ $today }}">
                <input type="hidden" name="deskripsi" id="createDeskripsi" value="Order pekerjaan jasa">

                <div class="order-form-modal-header flex items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-slate-900 sm:text-xl">Buat Order Jasa</h2>
                    <button type="button" data-close-order-modal class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-2xl leading-none text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">&times;</button>
                </div>

                <div class="order-form-modal-grid grid md:grid-cols-2">
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
                                <option value="{{ $value }}" @selected(old('form_context') === 'create' ? old('catatan_status', 'pending') === $value : $value === 'pending')>{{ $label }}</option>
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

                <div class="order-form-modal-actions flex justify-end gap-2 sm:gap-3">
                    <button type="button" data-close-order-modal class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Cancel</button>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editOrderModal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto p-2 sm:items-center sm:p-4">
        <div class="order-form-modal-panel w-full max-w-[860px] rounded-2xl bg-white shadow-2xl sm:rounded-3xl">
            <form method="POST" id="editOrderForm" action="#" class="order-form-modal-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="form_context" value="edit">
                <input type="hidden" name="edit_original_order" id="editOriginalOrder" value="{{ old('edit_original_order') }}">
                <input type="hidden" name="tanggal_order" id="editTanggalOrder" value="{{ $today }}">
                <input type="hidden" name="deskripsi" id="editDeskripsi" value="Order pekerjaan jasa">

                <div class="order-form-modal-header flex items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-slate-900 sm:text-xl">Edit Order Jasa</h2>
                    <button type="button" data-close-order-modal class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-2xl leading-none text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">&times;</button>
                </div>

                <div class="order-form-modal-grid grid md:grid-cols-2">
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
                                <option value="{{ $value }}" @selected(old('form_context') === 'edit' && old('catatan_status', 'pending') === $value)>{{ $label }}</option>
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

                <div class="order-form-modal-actions flex justify-end gap-2 sm:gap-3">
                    <button type="button" data-close-order-modal class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Cancel</button>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <div id="initialWorkModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="max-h-[92vh] overflow-y-auto rounded-3xl bg-white shadow-2xl" style="width:min(100%, 1080px);">
            <form method="POST" id="initialWorkForm" action="#" class="p-6">
                @csrf
                <input type="hidden" id="initialWorkMethod" name="_method" value="PUT" disabled>
                <input type="hidden" id="initialWorkFormContext" name="initial_work_form_context" value="create">
                <input type="hidden" id="initialWorkOrderKey" name="initial_work_order_key" value="{{ old('initial_work_order_key') }}">

                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 id="initialWorkModalTitle" class="text-2xl font-semibold text-slate-900">Buat Initial Work</h2>
                        <p class="mt-1 text-sm text-slate-500">Dokumen initial work khusus order prioritas emergency.</p>
                    </div>
                    <button type="button" data-close-order-modal class="text-2xl text-slate-500 transition hover:text-slate-700">&times;</button>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Kepada Yth</label>
                        <input id="initialWorkKepadaYth" name="kepada_yth" type="text" value="{{ old('kepada_yth') }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-orange-500 focus:outline-none" placeholder="PT. PRIMA KARYA MANUNGGAL">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Perihal</label>
                        <input id="initialWorkPerihal" name="perihal" type="text" value="{{ old('perihal') }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-orange-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nomor Initial Work</label>
                        <input id="initialWorkNumber" type="text" value="{{ $initialWorkPreviewNumber }}" readonly class="w-full rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
                        <p class="mt-1 text-[11px] text-slate-500">Nomor digenerate otomatis dan dipastikan ulang di server saat simpan.</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Tanggal Dokumen</label>
                        <input id="initialWorkTanggal" name="tanggal_initial_work" type="date" value="{{ old('tanggal_initial_work', $today) }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-orange-500 focus:outline-none" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm text-slate-700">Outline Agreement (Sumber Unit Pengendali)</label>
                        <select id="initialWorkOutlineAgreement" name="outline_agreement_id" class="w-full rounded-lg border border-slate-400 bg-white px-4 py-3 text-sm text-slate-700 focus:border-orange-500 focus:outline-none" required>
                            <option value="">Pilih OA untuk menentukan Manager dan Senior Manager</option>
                            @foreach ($initialWorkOutlineAgreementOptions as $agreement)
                                <option
                                    value="{{ $agreement->id }}"
                                    data-unit="{{ $agreement->unitWork?->name }}"
                                    data-section="{{ $agreement->jenis_kontrak }}"
                                    data-department="{{ $agreement->unitWork?->department?->name }}"
                                    @selected((string) old('outline_agreement_id') === (string) $agreement->id)
                                >
                                    {{ $agreement->nomor_oa }} - {{ $agreement->nama_kontrak }} ({{ $agreement->jenis_kontrak }} - {{ $agreement->unitWork?->name ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-500">Manager diambil dari seksi OA, Senior Manager dari unit OA pada Struktur Organisasi.</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nomor Order</label>
                        <input id="initialWorkOrderNumber" type="text" readonly class="w-full rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Notifikasi</label>
                        <input id="initialWorkNotifikasi" type="text" readonly class="w-full rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Unit Kerja Peminta</label>
                        <input id="initialWorkUnitKerja" type="text" readonly class="w-full rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Seksi Peminta</label>
                        <input id="initialWorkSeksi" type="text" readonly class="w-full rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">Tabel Initial Work</div>
                            <div class="mt-1 text-xs text-slate-500">Isi functional location, scope pekerjaan, qty, satuan, dan keterangan untuk kebutuhan dokumen awal.</div>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" id="addInitialWorkRowBtn" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">Tambah Baris</button>
                            <button type="button" id="removeInitialWorkRowBtn" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-700">Hapus Baris</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                        <table class="min-w-full text-xs text-slate-700">
                            <thead class="bg-slate-100 text-slate-700">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold">Functional Location</th>
                                    <th class="px-3 py-2 text-left font-semibold">Scope Pekerjaan</th>
                                    <th class="px-3 py-2 text-left font-semibold">Qty</th>
                                    <th class="px-3 py-2 text-left font-semibold">Stn</th>
                                    <th class="px-3 py-2 text-left font-semibold">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody id="initialWorkRows"></tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="mb-2 block text-sm text-slate-700">Keterangan Pekerjaan / Urgensi</label>
                    <textarea id="initialWorkUrgency" name="keterangan_pekerjaan" rows="3" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-orange-500 focus:outline-none" placeholder="Tambahkan keterangan urgensi pekerjaan">{{ old('keterangan_pekerjaan') }}</textarea>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="submit" class="rounded-lg bg-orange-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-700">Simpan Initial Work</button>
                    <button type="button" data-close-order-modal class="rounded-lg bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    @include('admin.orders.partials.approval-signature-modal')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const overlay = document.getElementById('orderModalOverlay');
            const createModal = document.getElementById('createOrderModal');
            const editModal = document.getElementById('editOrderModal');
            const initialWorkModal = document.getElementById('initialWorkModal');
            const orderFlowModal = document.getElementById('orderFlowModal');
            const editForm = document.getElementById('editOrderForm');
            const initialWorkForm = document.getElementById('initialWorkForm');
            const initialWorkMethod = document.getElementById('initialWorkMethod');
            const swal = window.Swal;
            const createUnitKerja = document.getElementById('createUnitKerja');
            const createSeksi = document.getElementById('createSeksi');
            const editUnitKerja = document.getElementById('editUnitKerja');
            const editSeksi = document.getElementById('editSeksi');
            const createPrioritas = document.getElementById('createPrioritas');
            const createPrioritasPrimary = document.getElementById('createPrioritasPrimary');
            const createPrioritasEmergency = document.getElementById('createPrioritasEmergency');
            const editPrioritas = document.getElementById('editPrioritas');
            const editPrioritasPrimary = document.getElementById('editPrioritasPrimary');
            const editPrioritasEmergency = document.getElementById('editPrioritasEmergency');
            const createCatatanStatus = document.getElementById('createCatatanStatus');
            const editCatatanStatus = document.getElementById('editCatatanStatus');
            const oldFormContext = @json(old('form_context'));
            const oldEditOrderKey = @json(old('edit_original_order'));
            const oldInitialWorkFormContext = @json(old('initial_work_form_context'));
            const oldInitialWorkOrderKey = @json(old('initial_work_order_key'));
            const initialWorkOutlineAgreement = document.getElementById('initialWorkOutlineAgreement');
            const userNoteDetailOptions = @json($userNoteDetailOptions);
            const priorityUrgent = @json(\App\Models\Order::PRIORITY_URGENT);
            const priorityHigh = @json(\App\Models\Order::PRIORITY_HIGH);
            const priorityMedium = @json(\App\Models\Order::PRIORITY_MEDIUM);
            const priorityLow = @json(\App\Models\Order::PRIORITY_LOW);
            const initialWorkRowsContainer = document.getElementById('initialWorkRows');
            const initialWorkDefaultNumber = @json($initialWorkPreviewNumber);
            const initialWorkDefaultRows = @js([['functional_location' => '', 'scope_pekerjaan' => '', 'qty' => '', 'stn' => '', 'keterangan' => '']]);

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

                if (seksiOptions.length === 0 && !normalizedOptions.includes(fallbackValue) && fallbackValue) {
                    const fallbackOption = document.createElement('option');
                    fallbackOption.value = fallbackValue;
                    fallbackOption.textContent = fallbackValue;
                    fallbackOption.selected = true;
                    seksiSelect.appendChild(fallbackOption);
                }
            };

            const showAlert = (options) => {
                if (swal) {
                    swal.fire(options);
                    return;
                }

                alert(options.text || options.title || 'Terjadi kesalahan.');
            };

            const showToast = (message, icon = 'success') => {
                if (swal) {
                    swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon,
                        title: message,
                        showConfirmButton: false,
                        timer: 1800,
                        timerProgressBar: true,
                    });
                    return;
                }

                alert(message);
            };

            const syncModalNoteField = (context, selectedStatus = 'pending', currentNote = '') => {
                const statusSelect = document.getElementById(`${context}CatatanStatus`);
                const detailSelect = document.getElementById(`${context}CatatanSelect`);
                const detailTextarea = document.getElementById(`${context}CatatanTextarea`);
                const hiddenInput = document.getElementById(`${context}Catatan`);

                if (!statusSelect || !detailSelect || !detailTextarea || !hiddenInput) {
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

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const parseArrayData = (rawValue) => {
                if (!rawValue) {
                    return [];
                }

                try {
                    const parsed = JSON.parse(rawValue);
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            };

            const renderInitialWorkRows = (rows = initialWorkDefaultRows) => {
                if (!initialWorkRowsContainer) {
                    return;
                }

                const normalizedRows = Array.isArray(rows) && rows.length > 0 ? rows : initialWorkDefaultRows;

                initialWorkRowsContainer.innerHTML = normalizedRows.map((row, index) => `
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-2">
                            <input name="functional_location[]" type="text" value="${escapeHtml(row.functional_location)}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-orange-500 focus:outline-none" required>
                        </td>
                        <td class="px-3 py-2">
                            <input name="scope_pekerjaan[]" type="text" value="${escapeHtml(row.scope_pekerjaan)}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-orange-500 focus:outline-none" required>
                        </td>
                        <td class="px-3 py-2">
                            <input name="qty[]" type="text" value="${escapeHtml(row.qty)}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-orange-500 focus:outline-none" required>
                        </td>
                        <td class="px-3 py-2">
                            <input name="stn[]" type="text" value="${escapeHtml(row.stn)}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-orange-500 focus:outline-none" required>
                        </td>
                        <td class="px-3 py-2">
                            <input name="keterangan[]" type="text" value="${escapeHtml(row.keterangan)}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-orange-500 focus:outline-none">
                        </td>
                    </tr>
                `).join('');
            };

            const addInitialWorkRow = () => {
                const rows = Array.from(initialWorkRowsContainer?.querySelectorAll('tr') || []).map((row) => ({
                    functional_location: row.querySelector('[name="functional_location[]"]')?.value || '',
                    scope_pekerjaan: row.querySelector('[name="scope_pekerjaan[]"]')?.value || '',
                    qty: row.querySelector('[name="qty[]"]')?.value || '',
                    stn: row.querySelector('[name="stn[]"]')?.value || '',
                    keterangan: row.querySelector('[name="keterangan[]"]')?.value || '',
                }));

                rows.push({ functional_location: '', scope_pekerjaan: '', qty: '', stn: '', keterangan: '' });
                renderInitialWorkRows(rows);
            };

            const removeInitialWorkRow = () => {
                const rows = Array.from(initialWorkRowsContainer?.querySelectorAll('tr') || []).map((row) => ({
                    functional_location: row.querySelector('[name="functional_location[]"]')?.value || '',
                    scope_pekerjaan: row.querySelector('[name="scope_pekerjaan[]"]')?.value || '',
                    qty: row.querySelector('[name="qty[]"]')?.value || '',
                    stn: row.querySelector('[name="stn[]"]')?.value || '',
                    keterangan: row.querySelector('[name="keterangan[]"]')?.value || '',
                }));

                if (rows.length <= 1) {
                    return;
                }

                rows.pop();
                renderInitialWorkRows(rows);
            };

            const setInitialWorkModalState = (button, mode = 'create', rows = null) => {
                if (!initialWorkForm || !button) {
                    return;
                }

                const isEdit = mode === 'edit';
                initialWorkForm.action = button.dataset.action || '#';
                initialWorkMethod.disabled = !isEdit;
                initialWorkMethod.value = 'PUT';
                document.getElementById('initialWorkFormContext').value = isEdit ? 'edit' : 'create';
                document.getElementById('initialWorkOrderKey').value = button.dataset.orderKey || '';
                document.getElementById('initialWorkModalTitle').textContent = isEdit ? 'Edit Initial Work' : 'Buat Initial Work';
                document.getElementById('initialWorkNumber').value = button.dataset.documentNumber || initialWorkDefaultNumber;
                if (initialWorkOutlineAgreement) {
                    initialWorkOutlineAgreement.value = button.dataset.outlineAgreementId || '';
                }
                document.getElementById('initialWorkOrderNumber').value = button.dataset.nomorOrder || '';
                document.getElementById('initialWorkNotifikasi').value = button.dataset.notifikasi || '-';
                document.getElementById('initialWorkUnitKerja').value = button.dataset.unitKerja || '-';
                document.getElementById('initialWorkSeksi').value = button.dataset.seksi || '-';
                document.getElementById('initialWorkKepadaYth').value = button.dataset.kepadaYth || 'PT. PRIMA KARYA MANUNGGAL';
                document.getElementById('initialWorkPerihal').value = button.dataset.perihal || `Initial Work - ${button.dataset.namaPekerjaan || button.dataset.nomorOrder || ''}`;
                document.getElementById('initialWorkTanggal').value = button.dataset.tanggal || '{{ $today }}';
                document.getElementById('initialWorkUrgency').value = button.dataset.keteranganPekerjaan || '';

                if (rows) {
                    renderInitialWorkRows(rows);
                    return;
                }

                if (isEdit) {
                    const functionalLocations = parseArrayData(button.dataset.functionalLocation);
                    const scopePekerjaan = parseArrayData(button.dataset.scopePekerjaan);
                    const qty = parseArrayData(button.dataset.qty);
                    const stn = parseArrayData(button.dataset.stn);
                    const keterangan = parseArrayData(button.dataset.keterangan);
                    const totalRows = Math.max(functionalLocations.length, scopePekerjaan.length, qty.length, stn.length, keterangan.length, 1);

                    renderInitialWorkRows(Array.from({ length: totalRows }, (_, index) => ({
                        functional_location: functionalLocations[index] || '',
                        scope_pekerjaan: scopePekerjaan[index] || '',
                        qty: qty[index] || '',
                        stn: stn[index] || '',
                        keterangan: keterangan[index] || '',
                    })));

                    return;
                }

                renderInitialWorkRows(initialWorkDefaultRows);
            };

            bindModalNoteField('create');
            bindModalNoteField('edit');
            bindPriorityField('create');
            bindPriorityField('edit');

            const openModal = (modal) => {
                overlay?.classList.remove('hidden');
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            const closeModals = () => {
                overlay?.classList.add('hidden');
                [createModal, editModal, initialWorkModal, orderFlowModal].forEach((modal) => {
                    modal?.classList.add('hidden');
                    modal?.classList.remove('flex');
                });
                document.body.classList.remove('overflow-hidden');
            };

            document.querySelectorAll('.order-flow-trigger').forEach((button) => {
                button.addEventListener('click', () => {
                    const checklist = JSON.parse(button.dataset.checklist || '[]');
                    document.getElementById('orderFlowTitle').textContent = button.dataset.title || 'Order';
                    document.getElementById('orderFlowRoute').textContent = button.dataset.route || '-';
                    document.getElementById('orderFlowNext').textContent = button.dataset.next || '-';
                    document.getElementById('orderFlowChecklist').innerHTML = checklist.map((item) => `
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                            <span class="font-medium text-slate-700">${escapeHtml(item.label || '-')}</span>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-semibold ${item.ready ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500'}">${item.ready ? 'OK' : 'Belum'}</span>
                        </div>
                    `).join('');
                    openModal(orderFlowModal);
                });
            });

            document.getElementById('openCreateOrderModal')?.addEventListener('click', () => {
                document.getElementById('createNomorOrder').value = '';
                document.getElementById('createNotifikasi').value = '';
                document.getElementById('createNamaPekerjaan').value = '';
                createUnitKerja.value = '';
                    syncPriorityField('create', priorityLow);
                    createCatatanStatus.value = 'pending';
                document.getElementById('createTargetSelesai').value = '{{ $today }}';
                document.getElementById('createTanggalOrder').value = '{{ $today }}';
                document.getElementById('createDeskripsi').value = 'Order pekerjaan jasa';
                document.getElementById('createCatatan').value = '';
                createSeksi.innerHTML = '<option value="">Pilih seksi</option>';
                syncModalNoteField('create', 'pending', '');
                openModal(createModal);
            });

            createUnitKerja?.addEventListener('change', () => {
                syncSeksiSelect(createUnitKerja, createSeksi);
            });

            editUnitKerja?.addEventListener('change', () => {
                syncSeksiSelect(editUnitKerja, editSeksi);
            });

            document.querySelectorAll('.edit-order-trigger').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!editForm) return;

                    editForm.action = button.dataset.action || '#';
                    document.getElementById('editOriginalOrder').value = button.dataset.orderKey || '';
                    document.getElementById('editNomorOrder').value = button.dataset.nomorOrder || '';
                    document.getElementById('editNotifikasi').value = button.dataset.notifikasi || '';
                    document.getElementById('editNamaPekerjaan').value = button.dataset.namaPekerjaan || '';
                    editUnitKerja.value = button.dataset.unitKerja || '';
                    syncPriorityField('edit', button.dataset.prioritas || priorityLow);
                    editCatatanStatus.value = button.dataset.catatanStatus || 'pending';
                    document.getElementById('editTargetSelesai').value = button.dataset.targetSelesai || '{{ $today }}';
                    document.getElementById('editTanggalOrder').value = button.dataset.tanggalOrder || button.dataset.targetSelesai || '{{ $today }}';
                    document.getElementById('editCatatan').value = button.dataset.catatan || '';
                    document.getElementById('editDeskripsi').value = button.dataset.namaPekerjaan || 'Order pekerjaan jasa';
                    syncSeksiSelect(editUnitKerja, editSeksi, button.dataset.seksi || 'Tidak ada seksi');
                    syncModalNoteField('edit', button.dataset.catatanStatus || 'pending', button.dataset.catatan || '');

                    openModal(editModal);
                });
            });

            document.getElementById('addInitialWorkRowBtn')?.addEventListener('click', addInitialWorkRow);
            document.getElementById('removeInitialWorkRowBtn')?.addEventListener('click', removeInitialWorkRow);

            document.querySelectorAll('.create-initial-work-trigger').forEach((button) => {
                button.addEventListener('click', () => {
                    setInitialWorkModalState(button, 'create');
                    openModal(initialWorkModal);
                });
            });

            document.querySelectorAll('.edit-initial-work-trigger').forEach((button) => {
                button.addEventListener('click', () => {
                    setInitialWorkModalState(button, 'edit');
                    openModal(initialWorkModal);
                });
            });

            if (oldFormContext === 'create') {
                syncSeksiSelect(createUnitKerja, createSeksi, @json(old('seksi')));
                syncPriorityField('create', @json(old('prioritas', \App\Models\Order::PRIORITY_LOW)));
                syncModalNoteField('create', @json(old('catatan_status', 'pending')), @json(old('catatan', '')));
                openModal(createModal);
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
                    document.getElementById('editDeskripsi').value = @json(old('deskripsi', 'Order pekerjaan jasa'));
                    editCatatanStatus.value = @json(old('catatan_status', 'pending'));
                    syncPriorityField('edit', @json(old('prioritas', \App\Models\Order::PRIORITY_LOW)));
                    syncSeksiSelect(editUnitKerja, editSeksi, @json(old('seksi')));
                    syncModalNoteField('edit', @json(old('catatan_status', 'pending')), @json(old('catatan', '')));
                    openModal(editModal);
                }
            }

            if (oldInitialWorkFormContext === 'create' || oldInitialWorkFormContext === 'edit') {
                const triggerSelector = oldInitialWorkFormContext === 'edit'
                    ? `.edit-initial-work-trigger[data-order-key="${oldInitialWorkOrderKey}"]`
                    : `.create-initial-work-trigger[data-order-key="${oldInitialWorkOrderKey}"]`;
                const initialWorkTrigger = oldInitialWorkOrderKey ? document.querySelector(triggerSelector) : null;

                if (initialWorkTrigger) {
                    const functionalLocations = @json(old('functional_location', []));
                    const scopePekerjaan = @json(old('scope_pekerjaan', []));
                    const qty = @json(old('qty', []));
                    const stn = @json(old('stn', []));
                    const keterangan = @json(old('keterangan', []));
                    const totalRows = Math.max(functionalLocations.length, scopePekerjaan.length, qty.length, stn.length, keterangan.length, 1);
                    const oldRows = Array.from({ length: totalRows }, (_, index) => ({
                        functional_location: functionalLocations[index] || '',
                        scope_pekerjaan: scopePekerjaan[index] || '',
                        qty: qty[index] || '',
                        stn: stn[index] || '',
                        keterangan: keterangan[index] || '',
                    }));

                    setInitialWorkModalState(initialWorkTrigger, oldInitialWorkFormContext, oldRows);
                    document.getElementById('initialWorkKepadaYth').value = @json(old('kepada_yth', 'PT. PRIMA KARYA MANUNGGAL'));
                    document.getElementById('initialWorkPerihal').value = @json(old('perihal', ''));
                    document.getElementById('initialWorkTanggal').value = @json(old('tanggal_initial_work', $today));
                    if (initialWorkOutlineAgreement) {
                        initialWorkOutlineAgreement.value = @json((string) old('outline_agreement_id', ''));
                    }
                    document.getElementById('initialWorkUrgency').value = @json(old('keterangan_pekerjaan', ''));
                    openModal(initialWorkModal);
                }
            }

            overlay?.addEventListener('click', closeModals);

            document.querySelectorAll('[data-close-order-modal]').forEach((button) => {
                button.addEventListener('click', closeModals);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModals();
                }
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
