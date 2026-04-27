<x-layouts.admin title="Create HPP">
    @php
        $formatRupiah = function ($value): string {
            $normalized = number_format((float) $value, 2, ',', '.');

            if (str_ends_with($normalized, ',00')) {
                return substr($normalized, 0, -3);
            }

            return rtrim(rtrim($normalized, '0'), ',');
        };
        $bucketLabels = \App\Support\HppApprovalFlow::bucketOptions();
        $displayArea = fn (?string $value): string => \App\Support\HppApprovalFlow::displayArea((string) $value);
        $pendingHppOrders = collect($pendingHppOrders ?? []);
    @endphp

    @if (session('status'))
        <div id="hpp-status-alert" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    <div class="space-y-6">
        <section class="rounded-[1.35rem] border border-blue-100 bg-blue-50 px-5 py-4 shadow-sm">
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

        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                @if ($pendingHppOrders->isNotEmpty())
                    <div class="mb-4 rounded-[1.2rem] border border-blue-200 bg-blue-50 px-3 py-3 text-slate-800 shadow-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                                        <i data-lucide="triangle-alert" class="h-3.5 w-3.5"></i>
                                    </span>
                                    <div class="text-[12px] font-black text-blue-950">Order Belum Dibuatkan HPP</div>
                                    <span class="inline-flex rounded-full border border-blue-200 bg-white px-2 py-0.5 text-[10px] font-bold text-blue-800">
                                        {{ $pendingHppOrders->count() }} order
                                    </span>
                                </div>
                                <p class="mt-1 pl-9 text-[10px] leading-5 text-blue-800">
                                    Sudah memenuhi syarat create HPP, tapi dokumen HPP-nya belum dibuat.
                                </p>
                            </div>
                        </div>

                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($pendingHppOrders as $pendingOrder)
                                <div class="min-w-[210px] rounded-xl border border-blue-200 bg-white px-2.5 py-2 text-[10px] shadow-sm">
                                    <div class="font-black text-slate-900">{{ $pendingOrder['nomor_order'] }}</div>
                                    <div class="mt-0.5 truncate text-slate-700">{{ $pendingOrder['nama_pekerjaan'] !== '' ? $pendingOrder['nama_pekerjaan'] : '-' }}</div>
                                    <div class="mt-0.5 truncate text-slate-500">
                                        {{ $pendingOrder['seksi'] !== '' ? $pendingOrder['seksi'] : ($pendingOrder['unit_kerja'] !== '' ? $pendingOrder['unit_kerja'] : '-') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="GET" action="{{ route('admin.hpp.index') }}" class="flex flex-col gap-2.5 xl:flex-row xl:items-end xl:justify-between">
                    <div class="grid flex-1 gap-2.5 md:grid-cols-2 xl:grid-cols-[1.2fr_0.6fr]">
                        <div class="flex flex-col">
                            <label for="search" class="mb-1.5 text-[10px] font-semibold text-slate-700">Pencarian</label>
                            <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Cari nomor order / pekerjaan / area..." class="rounded-lg border border-slate-300 px-3 py-2 text-[11px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none">
                        </div>
                        <div class="flex flex-col">
                            <label for="status" class="mb-1.5 text-[10px] font-semibold text-slate-700">Status</label>
                            <select id="status" name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-[11px] text-slate-700 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua Status</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
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
                        <col class="w-[14%]">
                        <col class="w-[25%]">
                        <col class="w-[13%]">
                        <col class="w-[10%]">
                        <col class="w-[10%]">
                        <col class="w-[20%]">
                        <col class="w-[8%]">
                    </colgroup>
                    <thead class="bg-slate-200/80 text-slate-700">
                        <tr>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Order</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Detail Pekerjaan</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Case</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Nilai HPP</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600">Status</th>
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
                                $approvalSummaryCaption = $isApprovalComplete ? 'Approval selesai' : 'Sudah sampai TTD';
                                $approvalSummaryLabel = $isApprovalComplete
                                    ? 'Semua approver selesai'
                                    : ($currentSignerLabel ?: 'Menunggu approver aktif');
                                $rejectedSignature = $row->signatures->first(function (\App\Models\HppSignature $signature): bool {
                                    return $signature->status === \App\Models\HppSignature::STATUS_SKIPPED
                                        && filled(trim((string) $signature->approval_note));
                                });
                                $approvalChecklist = $row->signatures->map(function (\App\Models\HppSignature $signature): array {
                                    return [
                                        'label' => $signature->role_label,
                                        'name' => $signature->signer_name_snapshot ?: '-',
                                        'status' => $signature->status,
                                    ];
                                })->values();
                            @endphp
                            <tr class="align-top hover:bg-slate-50">
                                <td class="px-5 py-4 text-[11px] font-semibold text-slate-800">
                                    <div class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-[16px] font-bold tracking-[0.04em] text-slate-900 shadow-sm">
                                        {{ $row->nomor_order }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-800">{{ $row->nama_pekerjaan }}</div>
                                    <div class="mt-2 text-[9px]">
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 font-semibold text-blue-700 ring-1 ring-blue-100">
                                            Seksi: {{ $row->order?->seksi ?: '-' }}
                                        </span>
                                    </div>
                                    <div class="mt-2 text-[9px]">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-600">
                                            Unit: {{ $row->unit_kerja }}
                                        </span>
                                    </div>
                                    <div class="mt-2 text-[9px] text-slate-400">Dibuat: {{ $row->created_at?->format('Y-m-d') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="inline-flex flex-col rounded-2xl bg-slate-100 px-3 py-2 text-[9px] text-slate-700">
                                        <span class="font-semibold">{{ $row->kategori_pekerjaan }} ({{ $displayArea($row->area_pekerjaan) }})</span>
                                        <span class="mt-1 text-slate-500">{{ $bucketLabels[$row->nilai_hpp_bucket] ?? ($row->approval_case ?: '-') }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-[11px] font-semibold text-slate-800">
                                    Rp {{ $formatRupiah($row->total_keseluruhan) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-[9px] font-semibold {{ $row->statusBadgeClasses() }}">
                                        {{ \App\Models\Hpp::statusOptions()[$row->status] ?? ucfirst(str_replace('_', ' ', $row->status)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-[11px] text-slate-700">
                                    @if ($row->status === \App\Models\Hpp::STATUS_DRAFT)
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                                            <div class="text-[9px] font-bold uppercase tracking-[0.16em] text-slate-400">Draft</div>
                                            <div class="mt-1 text-[10px] font-semibold text-slate-700">Belum submit approval</div>
                                        </div>
                                    @elseif ($row->status === \App\Models\Hpp::STATUS_REJECTED)
                                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 shadow-sm">
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
                                                >
                                                    Detail
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
                                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2.5">
                                            <div class="text-[9px] font-bold uppercase tracking-[0.16em] text-amber-500">Approval</div>
                                            <div class="mt-1 text-[10px] font-semibold text-amber-800">Signature belum dibuat</div>
                                        </div>
                                    @else
                                        <div class="rounded-xl border border-blue-100 bg-blue-50 px-2.5 py-2 shadow-sm">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="inline-flex rounded-full bg-white px-1.5 py-0.5 text-[8px] font-bold text-blue-700 ring-1 ring-blue-100">
                                                        {{ $signedCount }}/{{ $totalSteps }} TTD
                                                    </span>
                                                    @if ($isApprovalComplete)
                                                        <span class="inline-flex rounded-full bg-emerald-100 px-1.5 py-0.5 text-[8px] font-bold text-emerald-700">
                                                            Complete
                                                        </span>
                                                    @else
                                                        <span class="inline-flex rounded-full bg-blue-100 px-1.5 py-0.5 text-[8px] font-bold text-blue-700">
                                                            Berjalan
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
                                                >
                                                    Detail
                                                </button>
                                            </div>

                                            <div class="mt-2">
                                                <div class="text-[8px] font-bold uppercase tracking-[0.14em] text-slate-400">
                                                    {{ $approvalSummaryCaption }}
                                                </div>
                                                <div class="mt-0.5 truncate text-[9px] font-semibold text-slate-800" title="{{ $approvalSummaryLabel }}">
                                                    {{ $approvalSummaryLabel }}
                                                </div>
                                            </div>

                                            @if ($activeApprovalLink || $isActiveApprovalExpired || $isDiropsPending || $diropsSignedDocumentUrl)
                                                <div class="mt-2 border-t border-blue-100 pt-2">
                                                    <div class="mb-1 text-[8px] font-bold uppercase tracking-[0.14em] text-slate-400">Aksi Approval</div>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        @if ($activeApprovalLink)
                                                            <button
                                                                type="button"
                                                                class="copy-hpp-approval-link inline-flex items-center gap-1 rounded-full border border-blue-200 bg-white px-2 py-1 text-[8px] font-semibold text-blue-700 transition hover:bg-blue-100"
                                                                title="Salin link approval aktif"
                                                                data-approval-url="{{ $activeApprovalLink }}"
                                                            >
                                                                <i data-lucide="copy" class="h-2.5 w-2.5"></i>
                                                                Salin Link
                                                            </button>
                                                        @endif

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
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap items-center gap-1.5">
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
                                <td colspan="7" class="px-4 py-10 text-center text-[12px] text-slate-500">Belum ada HPP yang dibuat.</td>
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

                    <div class="rounded-xl border border-blue-100 bg-blue-50 px-3 py-2">
                        <div id="hppApprovalFlowModalCaption" class="text-[10px] font-semibold uppercase tracking-[0.16em] text-blue-600">Sudah sampai TTD</div>
                        <div id="hppApprovalFlowModalSummary" class="mt-1 text-[13px] font-semibold text-slate-900">-</div>
                        <div id="hppApprovalFlowModalName" class="mt-1 text-[11px] text-slate-500"></div>
                    </div>

                    <div id="hppApprovalFlowModalChecklist" class="space-y-2"></div>
                </div>
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
            const approvalFlowModalCaption = document.getElementById('hppApprovalFlowModalCaption');
            const approvalFlowModalSummary = document.getElementById('hppApprovalFlowModalSummary');
            const approvalFlowModalName = document.getElementById('hppApprovalFlowModalName');
            const approvalFlowModalClose = document.getElementById('hppApprovalFlowModalClose');
            const diropsUploadModal = document.getElementById('diropsUploadModal');
            const diropsUploadModalTitle = document.getElementById('diropsUploadModalTitle');
            const diropsUploadModalClose = document.getElementById('diropsUploadModalClose');
            const diropsUploadCancel = document.getElementById('diropsUploadCancel');
            const diropsUploadForm = document.getElementById('diropsUploadForm');
            const diropsUploadOrder = document.getElementById('diropsUploadOrder');
            const diropsUploadRouteTemplate = @json(route('admin.hpp.dirops-document.upload', ['hpp' => '__ORDER__']));

            if (statusAlert?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: statusAlert.dataset.message,
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

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

            const syncBodyScrollLock = () => {
                const shouldLock = [approvalFlowModal, diropsUploadModal].some((modal) => modal && !modal.classList.contains('hidden'));
                document.body.classList.toggle('overflow-hidden', shouldLock);
            };

            const approvalStatusConfig = {
                signed: {
                    label: 'OK',
                    badgeClass: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                },
                pending: {
                    label: 'Aktif',
                    badgeClass: 'border-blue-200 bg-blue-50 text-blue-700',
                },
                locked: {
                    label: 'Menunggu',
                    badgeClass: 'border-slate-200 bg-slate-100 text-slate-500',
                },
                skipped: {
                    label: 'Skip',
                    badgeClass: 'border-amber-200 bg-amber-50 text-amber-700',
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
                const caption = button.dataset.caption || 'Sudah sampai TTD';
                const summary = button.dataset.summary || '-';
                const currentName = button.dataset.currentName || '-';

                approvalFlowModalTitle.textContent = button.dataset.title || '-';
                approvalFlowModalCount.textContent = `${signedCount}/${totalSteps} TTD`;
                approvalFlowModalPercent.textContent = `${progress}%`;
                approvalFlowModalCaption.textContent = caption;
                approvalFlowModalSummary.textContent = summary;
                approvalFlowModalName.textContent = currentName && currentName !== '-' ? currentName : '';

                approvalFlowModalChecklist.innerHTML = checklist.map((item) => {
                    const config = approvalStatusConfig[item.status] || approvalStatusConfig.locked;

                    return `
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <div class="min-w-0">
                                <div class="truncate text-[13px] font-medium text-slate-800">${escapeHtml(item.label || '-')}</div>
                                <div class="mt-1 truncate text-[11px] text-slate-500">${escapeHtml(item.name || '-')}</div>
                            </div>
                            <span class="inline-flex shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold ${config.badgeClass}">
                                ${config.label}
                            </span>
                        </div>
                    `;
                }).join('');

                approvalFlowModal.classList.remove('hidden');
                approvalFlowModal.setAttribute('aria-hidden', 'false');
                syncBodyScrollLock();
            };

            const closeApprovalFlowModal = () => {
                if (!approvalFlowModal) {
                    return;
                }

                approvalFlowModal.classList.add('hidden');
                approvalFlowModal.setAttribute('aria-hidden', 'true');
                syncBodyScrollLock();
            };

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

            document.querySelectorAll('.copy-hpp-approval-link').forEach((button) => {
                button.addEventListener('click', async () => {
                    const url = button.dataset.approvalUrl;

                    if (!url) {
                        return;
                    }

                    await copyToClipboard(url);

                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'success',
                            title: 'Link approval disalin',
                            text: 'Kirim link ini ke approver aktif.',
                            timer: 1600,
                            showConfirmButton: false,
                        });
                    }
                });
            });

            document.querySelectorAll('.hpp-approval-flow-trigger').forEach((button) => {
                button.addEventListener('click', () => openApprovalFlowModal(button));
            });

            approvalFlowModalClose?.addEventListener('click', closeApprovalFlowModal);
            approvalFlowModal?.addEventListener('click', (event) => {
                if (!event.target.closest('[data-hpp-approval-panel]')) {
                    closeApprovalFlowModal();
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

