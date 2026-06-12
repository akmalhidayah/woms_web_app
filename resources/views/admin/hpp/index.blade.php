<x-layouts.admin title="Create HPP">
    <style>
        .hpp-index-filter {
            display: grid;
            gap: 0.5rem;
        }

        @media (min-width: 640px) {
            .hpp-index-filter {
                grid-template-columns: minmax(0, 1.25fr) minmax(180px, 0.65fr) auto;
                align-items: end;
            }
        }
    </style>

    @php
        $formatRupiah = function ($value): string {
            $normalized = number_format((float) $value, 2, ',', '.');

            if (str_ends_with($normalized, ',00')) {
                return substr($normalized, 0, -3);
            }

            return rtrim(rtrim($normalized, '0'), ',');
        };
        $pendingHppOrders = collect($pendingHppOrders ?? []);
    @endphp

    @if (session('status'))
        <div id="hpp-status-alert" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    <div class="order-list-compact space-y-4">
        <section class="order-list-hero rounded-[1.35rem] border border-blue-100 bg-blue-50 px-5 py-4 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                        <i data-lucide="file-text" class="h-[18px] w-[18px]"></i>
                    </span>
                    <div>
                        <h1 class="text-[1.3rem] font-bold leading-none tracking-tight text-slate-900">Create HPP</h1>
                        <p class="mt-1.5 text-[11px] text-slate-500">Daftar HPP dan snapshot approval yang sudah dibuat.</p>
                    </div>
                </div>

                <a href="{{ route('admin.hpp.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-[11px] font-semibold text-white transition hover:bg-blue-700">
                    <i data-lucide="plus-circle" class="h-[13px] w-[13px]"></i>
                    Buat HPP
                </a>
            </div>
        </section>

        <section class="order-list-panel overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                @if ($pendingHppOrders->isNotEmpty())
                    <div class="mb-3 border-b border-blue-100 pb-2.5 text-slate-700">
                        <div class="flex flex-wrap items-center gap-1.5 text-[10px]">
                            <i data-lucide="triangle-alert" class="h-3 w-3 text-blue-600"></i>
                            <span class="font-bold text-blue-900">Order belum dibuatkan HPP</span>
                            <span class="text-blue-600">({{ $pendingHppOrders->count() }})</span>
                        </div>

                        <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1">
                            @foreach ($pendingHppOrders as $pendingOrder)
                                <div class="flex min-w-0 items-center gap-1.5 text-[9px]">
                                    <span class="font-bold text-slate-800">{{ $pendingOrder['nomor_order'] }}</span>
                                    <span class="text-slate-400">-</span>
                                    <span class="max-w-[260px] truncate text-slate-600">{{ $pendingOrder['nama_pekerjaan'] !== '' ? $pendingOrder['nama_pekerjaan'] : '-' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="GET" action="{{ route('admin.hpp.index') }}" class="hpp-index-filter">
                    <div class="flex min-w-0 flex-col">
                        <label for="search" class="mb-1.5 text-[10px] font-semibold text-slate-700">Pencarian</label>
                        <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Cari nomor order / pekerjaan / area..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-[11px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div class="flex min-w-0 flex-col">
                        <label for="status" class="mb-1.5 text-[10px] font-semibold text-slate-700">Status</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-[11px] text-slate-700 focus:border-blue-500 focus:outline-none">
                            <option value="">Semua Status</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-700" title="Filter">
                            <i data-lucide="filter" class="h-[13px] w-[13px]"></i>
                        </button>
                        <a href="{{ route('admin.hpp.index') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50" title="Reset">
                            <i data-lucide="rotate-ccw" class="h-[13px] w-[13px]"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full table-fixed divide-y divide-slate-200 text-[11px] text-slate-700">
                    <colgroup>
                        <col class="w-[17%]">
                        <col class="w-[29%]">
                        <col class="w-[15%]">
                        <col class="w-[30%]">
                        <col class="w-[9%]">
                    </colgroup>
                    <thead class="bg-slate-200/80 text-slate-700">
                        <tr>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Order</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Detail Pekerjaan</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Nilai HPP / Status</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Progress Approval</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($rows as $row)
                            @php
                                $approvalProgress = $row->approvalProgressPercent();
                                $signedCount = $row->approvalSignedCount();
                                $totalSteps = $row->approvalStepCount();
                                $activeSignature = $row->activeSignature ?: $row->signatures->first(fn (\App\Models\HppSignature $signature): bool => $signature->isPending());
                                $isActiveApprovalExpired = $activeSignature?->isPending() && $activeSignature->tokenExpired();
                                $activeApprovalLink = $isActiveApprovalExpired ? null : $row->latestActiveApprovalLink();
                                $activeApprovalWhatsappUrl = $activeApprovalLink ? \App\Support\ApprovalWhatsappLink::forHpp($activeSignature) : null;
                                $isDiropsPending = $activeSignature?->role_key === 'dirops';
                                $diropsSignedDocumentSignature = $row->signatures->first(
                                    fn (\App\Models\HppSignature $signature): bool => $signature->role_key === 'dirops' && $signature->hasUploadedSignedDocument()
                                );
                                $diropsSignedDocumentUrl = $diropsSignedDocumentSignature
                                    ? route('admin.hpp.dirops-document.show', ['hpp' => $row->nomor_order])
                                    : null;
                                $currentSignerName = $row->currentApprovalSignerName();
                                $currentSignerLabel = $row->currentApprovalSignerLabel();
                                $isApprovalComplete = $row->approvalCompleted();
                                $approvalSummaryCaption = $isApprovalComplete ? 'Approval selesai' : 'Approval berjalan';
                                $approvalSummaryLabel = $isApprovalComplete
                                    ? 'Semua approver selesai'
                                    : ($currentSignerLabel ?: 'Menunggu approver aktif');
                                $rejectedSignature = $row->signatures->first(function (\App\Models\HppSignature $signature): bool {
                                    return $signature->status === \App\Models\HppSignature::STATUS_SKIPPED
                                        && filled(trim((string) $signature->approval_note));
                                });
                                $approvalChecklist = $row->signatures->map(function (\App\Models\HppSignature $signature): array {
                                    return [
                                        'label' => $signature->displayRoleLabel(),
                                        'original_label' => $signature->role_label,
                                        'name' => $signature->signer_name_snapshot ?: '-',
                                        'signer_user_id' => $signature->signer_user_id,
                                        'status' => $signature->status,
                                        'delegated_from_name' => $signature->delegated_from_name ?: '',
                                        'delegation_reason' => $signature->delegation_reason ?: '',
                                        'can_reassign' => ! in_array($signature->status, [\App\Models\HppSignature::STATUS_SIGNED, \App\Models\HppSignature::STATUS_SKIPPED], true),
                                        'reassign_url' => route('admin.hpp.approval-signatures.reassign', $signature),
                                    ];
                                })->values();
                                $activeApprovalModalActions = [
                                    'link' => $activeApprovalLink && ! $isDiropsPending ? $activeApprovalLink : '',
                                    'whatsapp_url' => $activeApprovalLink && ! $isDiropsPending ? $activeApprovalWhatsappUrl : '',
                                    'resend_url' => $activeApprovalLink && ! $isDiropsPending ? route('admin.hpp.approval.resend', ['hpp' => $row->nomor_order]) : '',
                                    'role_label' => $activeSignature?->displayRoleLabel() ?: '',
                                    'signer_name' => $activeSignature?->signer_name_snapshot ?: '',
                                ];
                            @endphp
                            <tr class="align-top hover:bg-slate-50">
                                <td class="px-5 py-3 text-[10px] text-slate-800">
                                    <div class="font-bold text-slate-900">{{ $row->nomor_order }}</div>
                                    <div class="mt-0.5 text-[9px] font-medium text-blue-600">
                                        Notif: {{ $row->order?->notifikasi ?: '-' }}
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="font-semibold leading-4 text-slate-800">{{ $row->nama_pekerjaan }}</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[9px]">
                                        <span class="text-slate-500">Unit: <strong class="font-semibold text-slate-700">{{ $row->unit_kerja }}</strong></span>
                                        <span class="text-slate-300">|</span>
                                        <span class="text-blue-500">Seksi: <strong class="font-semibold text-blue-700">{{ $row->order?->seksi ?: '-' }}</strong></span>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="text-[10px] font-bold text-slate-800">Rp {{ $formatRupiah($row->total_keseluruhan) }}</div>
                                    <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[8px] font-semibold {{ $row->statusBadgeClasses() }}">
                                        {{ \App\Models\Hpp::statusOptions()[$row->status] ?? ucfirst(str_replace('_', ' ', $row->status)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-[11px] text-slate-700">
                                    @if ($row->status === \App\Models\Hpp::STATUS_DRAFT)
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-2">
                                            <div class="text-[9px] font-bold uppercase tracking-[0.16em] text-slate-400">Draft</div>
                                            <div class="mt-0.5 text-[9px] font-semibold text-slate-700">Belum submit approval</div>
                                        </div>
                                    @elseif ($row->status === \App\Models\Hpp::STATUS_REJECTED)
                                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-2.5 py-2 shadow-sm">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="inline-flex rounded-full bg-white px-2 py-0.5 text-[8px] font-bold text-rose-700 ring-1 ring-rose-200">
                                                    Rejected
                                                </span>
                                                <button
                                                    type="button"
                                                    class="hpp-approval-flow-trigger inline-flex items-center gap-1 rounded-full border border-rose-200 bg-white px-1.5 py-0.5 text-[8px] font-semibold text-rose-500 transition hover:bg-rose-100 hover:text-rose-700"
                                                    data-title="{{ $row->nomor_order }}"
                                                    data-progress="{{ $approvalProgress }}"
                                                    data-signed-count="{{ $signedCount }}"
                                                    data-total-steps="{{ $totalSteps }}"
                                                    data-caption="Approval ditolak"
                                                    data-summary="{{ $rejectedSignature?->role_label ?: 'Approver menolak HPP' }}"
                                                    data-current-name="{{ $rejectedSignature?->signer_name_snapshot ?: '-' }}"
                                                    data-checklist='@json($approvalChecklist)'
                                                    data-actions='@json([])'
                                                    title="Detail approval"
                                                >
                                                    <i data-lucide="info" class="h-3 w-3"></i>
                                                </button>
                                            </div>
                                            <div class="mt-2 text-[8px] font-bold uppercase tracking-[0.14em] text-rose-500">
                                                Warning
                                            </div>
                                            <div class="mt-0.5 text-[9px] font-semibold text-rose-800">
                                                HPP ditolak dan harus dihapus lalu dibuat ulang.
                                            </div>
                                            @if ($rejectedSignature?->approval_note)
                                                <div class="mt-2 rounded-lg border border-rose-200 bg-white px-2 py-1.5 text-[9px] leading-4 text-slate-600">
                                                    {{ $rejectedSignature->approval_note }}
                                                </div>
                                            @endif
                                        </div>
                                    @elseif ($totalSteps === 0)
                                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-2.5 py-2">
                                            <div class="text-[9px] font-bold uppercase tracking-[0.16em] text-amber-500">Approval</div>
                                            <div class="mt-0.5 text-[9px] font-semibold text-amber-800">Signature belum dibuat</div>
                                        </div>
                                    @else
                                        <div class="rounded-xl border border-blue-100 bg-blue-50 px-2 py-1.5 shadow-sm">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="inline-flex rounded-full bg-white px-1.5 py-0.5 text-[8px] font-bold text-blue-700 ring-1 ring-blue-100">
                                                        {{ $signedCount }}/{{ $totalSteps }} TTD
                                                    </span>
                                                    <span class="max-w-[130px] truncate text-[9px] font-semibold text-slate-800" title="{{ $approvalSummaryLabel }}">
                                                        {{ $approvalSummaryLabel }}
                                                    </span>
                                                    @if ($isApprovalComplete)
                                                        <span class="inline-flex rounded-full bg-emerald-100 px-1.5 py-0.5 text-[8px] font-bold text-emerald-700">
                                                            Complete
                                                        </span>
                                                    @endif
                                                    @if ($isActiveApprovalExpired)
                                                        <span class="inline-flex rounded-full bg-amber-100 px-1.5 py-0.5 text-[8px] font-bold text-amber-700">
                                                            Link Expired
                                                        </span>
                                                    @endif
                                                </div>
                                                <button
                                                    type="button"
                                                    class="hpp-approval-flow-trigger inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-1.5 py-0.5 text-[8px] font-semibold text-slate-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                                    data-title="{{ $row->nomor_order }}"
                                                    data-progress="{{ $approvalProgress }}"
                                                    data-signed-count="{{ $signedCount }}"
                                                    data-total-steps="{{ $totalSteps }}"
                                                    data-caption="{{ $approvalSummaryCaption }}"
                                                    data-summary="{{ $approvalSummaryLabel }}"
                                                    data-current-name="{{ $currentSignerName ?: '-' }}"
                                                    data-checklist='@json($approvalChecklist)'
                                                    data-actions='@json($activeApprovalModalActions)'
                                                    title="Detail approval"
                                                >
                                                    <i data-lucide="info" class="h-3 w-3"></i>
                                                </button>
                                            </div>

                                            @if ($isActiveApprovalExpired || $isDiropsPending || $diropsSignedDocumentUrl)
                                                <div class="mt-2 border-t border-blue-100 pt-2">
                                                    <div class="mb-1 text-[8px] font-bold uppercase tracking-[0.14em] text-slate-400">Aksi Approval</div>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        @if ($isActiveApprovalExpired)
                                                            <form method="POST" action="{{ route('admin.hpp.approval-token.regenerate', ['hpp' => $row->nomor_order]) }}">
                                                                @csrf
                                                                <button
                                                                    type="submit"
                                                                    class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-white px-2 py-1 text-[8px] font-semibold text-amber-700 transition hover:bg-amber-100"
                                                                    title="Perbarui token approval yang sudah expired"
                                                                >
                                                                    <i data-lucide="refresh-cw" class="h-2.5 w-2.5"></i>
                                                                    Regenerate Token
                                                                </button>
                                                            </form>
                                                        @endif

                                                        @if ($isDiropsPending)
                                                            <a href="{{ route('admin.hpp.pdf', ['hpp' => $row->nomor_order]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-white px-2 py-1 text-[8px] font-semibold text-amber-700 transition hover:bg-amber-100" title="Print HPP untuk tanda tangan DIROPS">
                                                                <i data-lucide="printer" class="h-2.5 w-2.5"></i>
                                                                Print
                                                            </a>

                                                            <button
                                                                type="button"
                                                                class="dirops-upload-trigger inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-white px-2 py-1 text-[8px] font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                                                title="Upload dokumen tanda tangan DIROPS"
                                                                data-order="{{ $row->nomor_order }}"
                                                                data-upload-action="{{ route('admin.hpp.dirops-document.upload', ['hpp' => $row->nomor_order]) }}"
                                                            >
                                                                <i data-lucide="upload" class="h-2.5 w-2.5"></i>
                                                                Upload Final
                                                            </button>
                                                        @endif

                                                        @if ($diropsSignedDocumentUrl)
                                                            <a href="{{ $diropsSignedDocumentUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-white px-2 py-1 text-[8px] font-semibold text-emerald-700 transition hover:bg-emerald-100" title="Lihat dokumen final DIROPS">
                                                                <i data-lucide="file-check-2" class="h-2.5 w-2.5"></i>
                                                                Dokumen Final
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-nowrap items-center gap-1">
                                        <a href="{{ route('admin.hpp.pdf', ['hpp' => $row->nomor_order]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50" title="Lihat PDF HPP">
                                            <i data-lucide="file-text" class="h-3 w-3"></i>
                                        </a>

                                        @if ($row->isEditable())
                                            <a href="{{ route('admin.hpp.edit', ['hpp' => $row->nomor_order]) }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 transition hover:bg-blue-100" title="Edit HPP">
                                                <i data-lucide="pencil" class="h-3 w-3"></i>
                                            </a>
                                        @endif

                                        @if ($row->isDeletable())
                                            <form method="POST" action="{{ route('admin.hpp.destroy', $row) }}" class="delete-hpp-form" data-order="{{ $row->nomor_order }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100" title="Hapus HPP">
                                                    <i data-lucide="trash-2" class="h-3 w-3"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-[12px] text-slate-500">Belum ada HPP yang dibuat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div id="hppApprovalFlowModal" class="fixed inset-0 z-[120] hidden overflow-y-auto" aria-hidden="true">
        <div class="absolute inset-0 bg-slate-900/45"></div>
        <div class="relative flex min-h-full items-start justify-center px-4 pb-6 pt-28 sm:pb-8 sm:pt-32">
            <div data-hpp-approval-panel class="my-2 w-full max-w-md overflow-hidden rounded-[1.2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">Status Alur</div>
                        <h2 id="hppApprovalFlowModalTitle" class="mt-1.5 text-[1.2rem] font-bold leading-none tracking-tight text-slate-900">-</h2>
                        <p class="mt-2 text-[11px] text-slate-500">Progress tanda tangan HPP yang sedang berjalan.</p>
                    </div>
                    <button
                        type="button"
                        id="hppApprovalFlowModalClose"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Tutup detail approval HPP"
                    >
                        <i data-lucide="x" class="h-3.5 w-3.5"></i>
                    </button>
                </div>

                <div class="max-h-[58vh] space-y-3 overflow-y-auto px-4 py-3.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span id="hppApprovalFlowModalCount" class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold text-blue-700 ring-1 ring-blue-100">0/0 TTD</span>
                        <span id="hppApprovalFlowModalPercent" class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600">0%</span>
                    </div>

                    <div id="hppApprovalFlowModalChecklist" class="space-y-2"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="hppApprovalReassignmentModal" class="fixed inset-0 z-[130] hidden overflow-y-auto" aria-hidden="true">
        <div class="absolute inset-0 bg-slate-900/50"></div>
        <div class="relative flex min-h-full items-start justify-center px-4 pb-6 pt-28 sm:pb-8 sm:pt-32">
            <div data-hpp-reassignment-panel class="my-2 w-full max-w-md overflow-hidden rounded-[1.2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-orange-600">Alihkan Approver</div>
                        <h2 id="hppApprovalReassignmentTitle" class="mt-1.5 text-[1.1rem] font-bold leading-tight text-slate-900">-</h2>
                        <p id="hppApprovalReassignmentCurrent" class="mt-2 text-[11px] text-slate-500">-</p>
                    </div>
                    <button type="button" id="hppApprovalReassignmentClose" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup alih approver HPP">
                        <i data-lucide="x" class="h-3.5 w-3.5"></i>
                    </button>
                </div>

                <form id="hppApprovalReassignmentForm" method="POST" action="#" class="space-y-3 px-4 py-3.5">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="hppApprovalReassignmentSigner" class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Approver PLT</label>
                        <select id="hppApprovalReassignmentSigner" name="signer_user_id" required class="block h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-[12px] font-semibold text-slate-800 focus:border-blue-500 focus:outline-none">
                            <option value="">Pilih user</option>
                        </select>
                    </div>

                    <div>
                        <label for="hppApprovalReassignmentReason" class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Alasan</label>
                        <textarea id="hppApprovalReassignmentReason" name="delegation_reason" required rows="3" class="block w-full resize-none rounded-lg border border-slate-300 bg-white px-3 py-2 text-[12px] text-slate-800 focus:border-blue-500 focus:outline-none" placeholder="Contoh: pejabat definitif sedang cuti/dinas, approval dialihkan ke PLT."></textarea>
                    </div>

                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] font-semibold text-slate-600">
                        <input type="checkbox" name="send_email" value="1" checked class="rounded border-slate-300 text-blue-600">
                        Kirim email approval setelah dialihkan
                    </label>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button type="button" id="hppApprovalReassignmentCancel" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50">Batal</button>
                        <button type="submit" class="inline-flex items-center rounded-lg bg-orange-600 px-3 py-2 text-[11px] font-semibold text-white transition hover:bg-orange-700">Simpan Alih</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="diropsUploadModal" class="fixed inset-0 z-[125] hidden overflow-y-auto" aria-hidden="true">
        <div class="absolute inset-0 bg-slate-900/45"></div>
        <div class="relative flex min-h-full items-start justify-center px-4 pb-6 pt-24 sm:pb-8 sm:pt-28">
            <div data-dirops-upload-panel class="my-2 w-full max-w-sm overflow-hidden rounded-[1.15rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-600">Upload Final DIROPS</div>
                        <h2 id="diropsUploadModalTitle" class="mt-1 text-[1.05rem] font-bold leading-tight text-slate-900">-</h2>
                        <p class="mt-1.5 text-[11px] leading-5 text-slate-500">
                            Upload dokumen yang sudah ditandatangani DIROPS. Setelah tersimpan, approval HPP dianggap selesai.
                        </p>
                    </div>
                    <button
                        type="button"
                        id="diropsUploadModalClose"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Tutup upload dokumen DIROPS"
                    >
                        <i data-lucide="x" class="h-3.5 w-3.5"></i>
                    </button>
                </div>

                <form id="diropsUploadForm" method="POST" enctype="multipart/form-data" class="space-y-3 px-4 py-3.5">
                    @csrf
                    <input type="hidden" name="hpp_order" id="diropsUploadOrder" value="{{ old('hpp_order') }}">

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-[10px] leading-5 text-slate-600">
                        Gunakan file `PDF`, `PNG`, atau `JPG` maksimal `10 MB`.
                    </div>

                    <div>
                        <label for="signed_document" class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Dokumen Final</label>
                        <input
                            id="signed_document"
                            name="signed_document"
                            type="file"
                            accept=".pdf,.png,.jpg,.jpeg"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-[11px] text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-2.5 file:py-1.5 file:text-[11px] file:font-semibold file:text-emerald-700 focus:border-emerald-500 focus:outline-none"
                        >
                        @error('signed_document')
                            <div class="mt-1.5 text-[10px] font-medium text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button type="button" id="diropsUploadCancel" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-[11px] font-semibold text-white transition hover:bg-emerald-700">
                            Upload Dokumen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const statusAlert = document.getElementById('hpp-status-alert');
            const approvalFlowModal = document.getElementById('hppApprovalFlowModal');
            const approvalFlowModalTitle = document.getElementById('hppApprovalFlowModalTitle');
            const approvalFlowModalCount = document.getElementById('hppApprovalFlowModalCount');
            const approvalFlowModalPercent = document.getElementById('hppApprovalFlowModalPercent');
            const approvalFlowModalChecklist = document.getElementById('hppApprovalFlowModalChecklist');
            const approvalFlowModalClose = document.getElementById('hppApprovalFlowModalClose');
            const approvalReassignmentModal = document.getElementById('hppApprovalReassignmentModal');
            const approvalReassignmentForm = document.getElementById('hppApprovalReassignmentForm');
            const approvalReassignmentTitle = document.getElementById('hppApprovalReassignmentTitle');
            const approvalReassignmentCurrent = document.getElementById('hppApprovalReassignmentCurrent');
            const approvalReassignmentSigner = document.getElementById('hppApprovalReassignmentSigner');
            const approvalReassignmentReason = document.getElementById('hppApprovalReassignmentReason');
            const approvalReassignmentClose = document.getElementById('hppApprovalReassignmentClose');
            const approvalReassignmentCancel = document.getElementById('hppApprovalReassignmentCancel');
            const diropsUploadModal = document.getElementById('diropsUploadModal');
            const diropsUploadModalTitle = document.getElementById('diropsUploadModalTitle');
            const diropsUploadModalClose = document.getElementById('diropsUploadModalClose');
            const diropsUploadCancel = document.getElementById('diropsUploadCancel');
            const diropsUploadForm = document.getElementById('diropsUploadForm');
            const diropsUploadOrder = document.getElementById('diropsUploadOrder');
            const diropsUploadRouteTemplate = @json(route('admin.hpp.dirops-document.upload', ['hpp' => '__ORDER__']));
            const reassignmentUsers = @json(($approvalReassignmentUsers ?? collect())->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'nomor_hp' => $user->nomor_hp,
            ])->values());

            if (statusAlert?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: statusAlert.dataset.message,
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

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

            const parseObjectData = (rawValue) => {
                if (!rawValue) {
                    return {};
                }

                try {
                    const parsed = JSON.parse(rawValue);
                    return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
                } catch (error) {
                    return {};
                }
            };

            const copyToClipboard = async (text) => {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(text);
                    return;
                }

                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', 'readonly');
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            };

            const syncBodyScrollLock = () => {
                const shouldLock = [approvalFlowModal, approvalReassignmentModal, diropsUploadModal].some((modal) => modal && !modal.classList.contains('hidden'));
                document.body.classList.toggle('overflow-hidden', shouldLock);
            };

            const approvalStatusConfig = {
                signed: {
                    label: 'OK',
                    badgeClass: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                    rowClass: 'border-emerald-200 bg-emerald-50',
                },
                pending: {
                    label: 'Aktif',
                    badgeClass: 'border-blue-200 bg-blue-50 text-blue-700',
                    rowClass: 'border-blue-200 bg-blue-50',
                },
                locked: {
                    label: 'Menunggu',
                    badgeClass: 'border-slate-200 bg-slate-100 text-slate-500',
                    rowClass: 'border-slate-200 bg-slate-50',
                },
                skipped: {
                    label: 'Skip',
                    badgeClass: 'border-amber-200 bg-amber-50 text-amber-700',
                    rowClass: 'border-amber-200 bg-amber-50',
                },
            };

            const openApprovalFlowModal = (button) => {
                if (!approvalFlowModal) {
                    return;
                }

                const checklist = parseArrayData(button.dataset.checklist);
                const progress = button.dataset.progress || '0';
                const signedCount = button.dataset.signedCount || '0';
                const totalSteps = button.dataset.totalSteps || '0';
                const actions = parseObjectData(button.dataset.actions);
                const approvalLink = actions.link || '';
                const whatsappUrl = actions.whatsapp_url || '';
                const resendUrl = actions.resend_url || '';

                approvalFlowModalTitle.textContent = button.dataset.title || '-';
                approvalFlowModalCount.textContent = `${signedCount}/${totalSteps} TTD`;
                approvalFlowModalPercent.textContent = `${progress}%`;

                approvalFlowModalChecklist.innerHTML = checklist.map((item) => {
                    const config = approvalStatusConfig[item.status] || approvalStatusConfig.locked;
                    const isActive = item.status === 'pending' && approvalLink;
                    const canReassign = Boolean(item.can_reassign && item.reassign_url);
                    const actionButtons = isActive
                        ? `
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <button
                                    type="button"
                                    class="hpp-modal-copy-link inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-blue-700 transition hover:bg-blue-100"
                                    data-link="${escapeHtml(approvalLink)}"
                                >
                                    <i data-lucide="copy" class="h-3 w-3"></i>
                                    Salin Link
                                </button>
                                ${whatsappUrl ? `
                                    <a
                                        href="${escapeHtml(whatsappUrl)}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                    >
                                        <i data-lucide="message-circle" class="h-3 w-3"></i>
                                        WhatsApp
                                    </a>
                                ` : `
                                    <span
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-slate-400"
                                        title="Nomor WhatsApp approver belum tersedia di user panel"
                                    >
                                        <i data-lucide="message-circle-off" class="h-3 w-3"></i>
                                        No WA
                                    </span>
                                `}
                                ${resendUrl ? `
                                    <form method="POST" action="${escapeHtml(resendUrl)}" class="inline-block">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-sky-700 transition hover:bg-sky-100"
                                        >
                                            <i data-lucide="send" class="h-3 w-3"></i>
                                            Resend
                                        </button>
                                    </form>
                                ` : ''}
                            </div>
                        `
                        : '';
                    const reassignButton = canReassign
                        ? `
                            <button
                                type="button"
                                class="hpp-modal-reassign inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-orange-700 transition hover:bg-orange-100"
                                data-item='${escapeHtml(JSON.stringify(item))}'
                            >
                                <i data-lucide="user-cog" class="h-3 w-3"></i>
                                Alihkan
                            </button>
                        `
                        : '';

                    return `
                        <div class="rounded-xl border px-3 py-2.5 ${config.rowClass}">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-[13px] font-medium text-slate-800">${escapeHtml(item.label || '-')}</div>
                                    <div class="mt-1 truncate text-[11px] text-slate-500">${escapeHtml(item.name || '-')}</div>
                                    ${item.delegated_from_name ? `<div class="mt-0.5 text-[9px] text-slate-500">Dialihkan dari ${escapeHtml(item.delegated_from_name)}</div>` : ''}
                                    ${item.delegation_reason ? `<div class="mt-0.5 text-[9px] text-slate-500">Alasan: ${escapeHtml(item.delegation_reason)}</div>` : ''}
                                </div>
                                <span class="inline-flex shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold ${config.badgeClass}">
                                    ${config.label}
                                </span>
                            </div>
                            ${actionButtons}
                            ${reassignButton ? `<div class="mt-2 flex flex-wrap items-center gap-1.5">${reassignButton}</div>` : ''}
                        </div>
                    `;
                }).join('');

                approvalFlowModal.classList.remove('hidden');
                approvalFlowModal.setAttribute('aria-hidden', 'false');
                syncBodyScrollLock();

                if (window.lucide) {
                    window.lucide.createIcons();
                }
            };

            const closeApprovalFlowModal = () => {
                if (!approvalFlowModal) {
                    return;
                }

                approvalFlowModal.classList.add('hidden');
                approvalFlowModal.setAttribute('aria-hidden', 'true');
                syncBodyScrollLock();
            };

            const openApprovalReassignmentModal = (item) => {
                if (!approvalReassignmentModal || !approvalReassignmentForm || !approvalReassignmentSigner) {
                    return;
                }

                approvalReassignmentForm.action = item.reassign_url || '#';
                approvalReassignmentTitle.textContent = `PLT ${item.original_label || item.label || '-'}`;
                approvalReassignmentCurrent.textContent = `Saat ini: ${item.name || '-'}${item.delegated_from_name ? ` (dialihkan dari ${item.delegated_from_name})` : ''}`;
                approvalReassignmentReason.value = '';
                approvalReassignmentSigner.innerHTML = '<option value="">Pilih user</option>' + reassignmentUsers.map((user) => `
                    <option value="${escapeHtml(user.id)}" ${String(user.id) === String(item.signer_user_id || '') ? 'disabled' : ''}>
                        ${escapeHtml(user.name || '-')} - ${escapeHtml(user.email || '-')} (${escapeHtml(user.role || '-')})
                    </option>
                `).join('');
                approvalReassignmentModal.classList.remove('hidden');
                approvalReassignmentModal.setAttribute('aria-hidden', 'false');
                syncBodyScrollLock();
            };

            const closeApprovalReassignmentModal = () => {
                approvalReassignmentModal?.classList.add('hidden');
                approvalReassignmentModal?.setAttribute('aria-hidden', 'true');
                syncBodyScrollLock();
            };

            approvalFlowModalChecklist?.addEventListener('click', async (event) => {
                const copyButton = event.target.closest('.hpp-modal-copy-link');
                const reassignButton = event.target.closest('.hpp-modal-reassign');

                if (reassignButton) {
                    try {
                        openApprovalReassignmentModal(JSON.parse(reassignButton.dataset.item || '{}'));
                    } catch (error) {
                        openApprovalReassignmentModal({});
                    }
                    return;
                }

                if (!copyButton) {
                    return;
                }

                const link = copyButton.dataset.link || '';

                if (!link) {
                    return;
                }

                try {
                    await copyToClipboard(link);
                    copyButton.innerHTML = '<i data-lucide="check" class="h-3 w-3"></i> Disalin';
                    window.lucide?.createIcons();
                    setTimeout(() => {
                        copyButton.innerHTML = '<i data-lucide="copy" class="h-3 w-3"></i> Salin Link';
                        window.lucide?.createIcons();
                    }, 1400);
                } catch (error) {
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'error',
                            title: 'Gagal menyalin',
                            text: 'Browser memblokir akses clipboard.',
                        });
                    }
                }
            });

            const openDiropsUploadModal = (order, action) => {
                if (!diropsUploadModal || !diropsUploadForm) {
                    return;
                }

                diropsUploadModalTitle.textContent = order || '-';
                diropsUploadForm.action = action || diropsUploadRouteTemplate.replace('__ORDER__', encodeURIComponent(order || ''));
                diropsUploadOrder.value = order || '';
                diropsUploadModal.classList.remove('hidden');
                diropsUploadModal.setAttribute('aria-hidden', 'false');
                syncBodyScrollLock();
            };

            const closeDiropsUploadModal = () => {
                if (!diropsUploadModal) {
                    return;
                }

                diropsUploadModal.classList.add('hidden');
                diropsUploadModal.setAttribute('aria-hidden', 'true');
                syncBodyScrollLock();
            };

            document.querySelectorAll('.hpp-approval-flow-trigger').forEach((button) => {
                button.addEventListener('click', () => openApprovalFlowModal(button));
            });

            approvalFlowModalClose?.addEventListener('click', closeApprovalFlowModal);
            approvalReassignmentClose?.addEventListener('click', closeApprovalReassignmentModal);
            approvalReassignmentCancel?.addEventListener('click', closeApprovalReassignmentModal);
            approvalFlowModal?.addEventListener('click', (event) => {
                if (!event.target.closest('[data-hpp-approval-panel]')) {
                    closeApprovalFlowModal();
                }
            });
            approvalReassignmentModal?.addEventListener('click', (event) => {
                if (!event.target.closest('[data-hpp-reassignment-panel]')) {
                    closeApprovalReassignmentModal();
                }
            });

            document.querySelectorAll('.dirops-upload-trigger').forEach((button) => {
                button.addEventListener('click', () => {
                    openDiropsUploadModal(button.dataset.order || '', button.dataset.uploadAction || '');
                });
            });

            diropsUploadModalClose?.addEventListener('click', closeDiropsUploadModal);
            diropsUploadCancel?.addEventListener('click', closeDiropsUploadModal);
            diropsUploadModal?.addEventListener('click', (event) => {
                if (!event.target.closest('[data-dirops-upload-panel]')) {
                    closeDiropsUploadModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && approvalFlowModal && !approvalFlowModal.classList.contains('hidden')) {
                    closeApprovalFlowModal();
                }

                if (event.key === 'Escape' && diropsUploadModal && !diropsUploadModal.classList.contains('hidden')) {
                    closeDiropsUploadModal();
                }
            });

            document.querySelectorAll('.delete-hpp-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    if (!window.Swal) {
                        form.submit();
                        return;
                    }

                    const orderNumber = form.dataset.order || 'HPP ini';
                    const result = await window.Swal.fire({
                        icon: 'warning',
                        title: 'Hapus HPP?',
                        html: `Yakin ingin menghapus HPP untuk order <b>${orderNumber}</b>?`,
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#dc2626',
                    });

                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            if (diropsUploadModal && diropsUploadForm && diropsUploadOrder?.value) {
                openDiropsUploadModal(
                    diropsUploadOrder.value,
                    diropsUploadRouteTemplate.replace('__ORDER__', encodeURIComponent(diropsUploadOrder.value))
                );
            }
        });
    </script>
</x-layouts.admin>

