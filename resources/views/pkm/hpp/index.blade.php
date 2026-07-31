<x-layouts.pkm title="Create HPP">
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

    <div class="order-list-compact space-y-4">
        <section class="order-list-hero rounded-[1.35rem] border border-blue-100 bg-blue-50 px-5 py-4 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                        <i data-lucide="file-text" class="h-[18px] w-[18px]"></i>
                    </span>
                    <h1 class="text-[1.3rem] font-bold leading-none tracking-tight text-slate-900">Create HPP</h1>
                </div>

                <a href="{{ route('pkm.hpp.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-[11px] font-semibold text-white transition hover:bg-blue-700">
                    <i data-lucide="plus-circle" class="h-[13px] w-[13px]"></i>
                    Buat HPP
                </a>
            </div>
        </section>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

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

                <x-hpp.index-tabs
                    route-name="pkm.hpp.index"
                    :active-tab="$activeTab"
                    :tab-options="$tabOptions"
                    :tab-counts="$tabCounts"
                    :search="$search"
                />
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
                                $signedCount = $row->approvalSignedCount();
                                $totalSteps = $row->approvalStepCount();
                                $isApprovalComplete = $row->approvalCompleted();
                                $currentSignerLabel = $row->currentApprovalSignerLabel();
                                $approvalSummaryLabel = $isApprovalComplete
                                    ? 'Semua approver selesai'
                                    : ($currentSignerLabel ?: 'Menunggu approver aktif');
                                $activeSignature = $row->activeSignature
                                    ?: $row->signatures->first(fn (\App\Models\HppSignature $signature): bool => $signature->isPending());
                                $activeApprovalLink = $activeSignature?->isPending()
                                    && ! $activeSignature->tokenExpired()
                                    ? $activeSignature->approvalUrl()
                                    : null;
                                $approvalChecklist = $row->signatures
                                    ->map(fn (\App\Models\HppSignature $signature): array => [
                                        'label' => $signature->displayRoleLabel(),
                                        'name' => $signature->signer_name_snapshot ?: '-',
                                        'status' => $signature->status,
                                        'delegated_from_name' => $signature->delegated_from_name ?: '',
                                        'delegation_reason' => $signature->delegation_reason ?: '',
                                    ])
                                    ->values();
                                $activeApprovalActions = [
                                    'whatsapp_url' => $activeApprovalLink
                                        ? (\App\Support\ApprovalWhatsappLink::forHpp($activeSignature) ?: '')
                                        : '',
                                    'resend_url' => $activeApprovalLink
                                        ? route('pkm.hpp.approval.resend', $row)
                                        : '',
                                ];
                            @endphp
                            <tr class="align-top hover:bg-slate-50">
                                <td class="px-5 py-3 text-[10px] text-slate-800">
                                    <div class="font-bold text-slate-900">{{ $row->nomor_order }}</div>
                                    <div class="mt-0.5 text-[9px] font-medium text-blue-600">Notif: {{ $row->order?->notifikasi ?: '-' }}</div>
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
                                        {{ $statusOptions[$row->status] ?? ucfirst(str_replace('_', ' ', $row->status)) }}
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
                                            <span class="inline-flex rounded-full bg-white px-2 py-0.5 text-[8px] font-bold text-rose-700 ring-1 ring-rose-200">Rejected</span>
                                            <div class="mt-1 text-[9px] font-semibold text-rose-800">HPP ditolak.</div>
                                        </div>
                                    @elseif ($totalSteps === 0)
                                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-2.5 py-2">
                                            <div class="text-[9px] font-bold uppercase tracking-[0.16em] text-amber-500">Approval</div>
                                            <div class="mt-0.5 text-[9px] font-semibold text-amber-800">Signature belum dibuat</div>
                                        </div>
                                    @else
                                        <div class="rounded-xl border border-blue-100 bg-blue-50 px-2 py-1.5 shadow-sm">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex min-w-0 items-center gap-1.5">
                                                    <span class="inline-flex shrink-0 rounded-full bg-white px-1.5 py-0.5 text-[8px] font-bold text-blue-700 ring-1 ring-blue-100">
                                                        {{ $signedCount }}/{{ $totalSteps }} TTD
                                                    </span>
                                                    <span class="truncate text-[9px] font-semibold text-slate-800" title="{{ $approvalSummaryLabel }}">{{ $approvalSummaryLabel }}</span>
                                                    @if ($isApprovalComplete)
                                                        <span class="inline-flex shrink-0 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[8px] font-bold text-emerald-700">Complete</span>
                                                    @endif
                                                </div>
                                                <button
                                                    type="button"
                                                    class="hpp-pkm-approval-trigger inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 transition hover:border-blue-200 hover:text-blue-700"
                                                    data-title="{{ $row->nomor_order }}"
                                                    data-progress="{{ $row->approvalProgressPercent() }}"
                                                    data-signed-count="{{ $signedCount }}"
                                                    data-total-steps="{{ $totalSteps }}"
                                                    data-checklist='@json($approvalChecklist)'
                                                    data-actions='@json($activeApprovalActions)'
                                                    title="Detail approval"
                                                >
                                                    <i data-lucide="info" class="h-3 w-3"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="mt-1.5 flex min-w-0 items-center gap-1 text-[8px] text-slate-400">
                                        <i data-lucide="clock-3" class="h-2.5 w-2.5 shrink-0"></i>
                                        <span class="truncate">
                                            {{ $row->activityLabel() }} · {{ $row->updated_at?->locale('id')->diffForHumans() ?? '-' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <a target="_blank" rel="noopener noreferrer" href="{{ route('pkm.hpp.pdf', $row) }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700" title="Lihat PDF">
                                            <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                        </a>
                                        @if ($row->status === \App\Models\Hpp::STATUS_DRAFT)
                                            <a href="{{ route('pkm.hpp.edit', $row) }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100" title="Edit Draft">
                                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">{{ \App\Support\HppIndexTabs::emptyMessage($activeTab) }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($rows->hasPages())
                <div class="border-t border-slate-200 px-5 py-3">{{ $rows->links() }}</div>
            @endif
        </section>
    </div>

    <div id="hppPkmApprovalModal" class="fixed inset-0 z-[120] hidden overflow-y-auto" aria-hidden="true">
        <div class="absolute inset-0 bg-slate-900/45"></div>
        <div class="relative flex min-h-full items-start justify-center px-4 pb-6 pt-28 sm:pb-8 sm:pt-32">
            <div data-hpp-pkm-approval-panel class="my-2 w-full max-w-md overflow-hidden rounded-[1.2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">Status Alur</div>
                        <h2 id="hppPkmApprovalTitle" class="mt-1.5 text-[1.2rem] font-bold leading-none tracking-tight text-slate-900">-</h2>
                        <p class="mt-2 text-[11px] text-slate-500">Progress tanda tangan HPP yang sedang berjalan.</p>
                    </div>
                    <button type="button" id="hppPkmApprovalClose" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup detail approval HPP">
                        <i data-lucide="x" class="h-3.5 w-3.5"></i>
                    </button>
                </div>

                <div class="max-h-[58vh] space-y-3 overflow-y-auto px-4 py-3.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span id="hppPkmApprovalCount" class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold text-blue-700 ring-1 ring-blue-100">0/0 TTD</span>
                        <span id="hppPkmApprovalPercent" class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600">0%</span>
                    </div>
                    <div id="hppPkmApprovalChecklist" class="space-y-2"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('hppPkmApprovalModal');
            const title = document.getElementById('hppPkmApprovalTitle');
            const count = document.getElementById('hppPkmApprovalCount');
            const percent = document.getElementById('hppPkmApprovalPercent');
            const checklistContainer = document.getElementById('hppPkmApprovalChecklist');
            const closeButton = document.getElementById('hppPkmApprovalClose');

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const parseData = (value, fallback) => {
                try {
                    return JSON.parse(value || '') ?? fallback;
                } catch (error) {
                    return fallback;
                }
            };

            const statusConfig = {
                signed: ['OK', 'border-emerald-200 bg-emerald-50 text-emerald-700', 'border-emerald-200 bg-emerald-50'],
                pending: ['Aktif', 'border-blue-200 bg-blue-50 text-blue-700', 'border-blue-200 bg-blue-50'],
                locked: ['Menunggu', 'border-slate-200 bg-slate-100 text-slate-500', 'border-slate-200 bg-slate-50'],
                skipped: ['Skip', 'border-amber-200 bg-amber-50 text-amber-700', 'border-amber-200 bg-amber-50'],
            };

            const openModal = (button) => {
                const checklist = parseData(button.dataset.checklist, []);
                const actions = parseData(button.dataset.actions, {});
                const whatsappUrl = actions.whatsapp_url || '';
                const resendUrl = actions.resend_url || '';

                title.textContent = button.dataset.title || '-';
                count.textContent = `${button.dataset.signedCount || 0}/${button.dataset.totalSteps || 0} TTD`;
                percent.textContent = `${button.dataset.progress || 0}%`;
                checklistContainer.innerHTML = checklist.map((item) => {
                    const config = statusConfig[item.status] || statusConfig.locked;
                    const activeActions = item.status === 'pending'
                        ? `
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                ${whatsappUrl ? `
                                    <a href="${escapeHtml(whatsappUrl)}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                        <i data-lucide="message-circle" class="h-3 w-3"></i>
                                        WhatsApp
                                    </a>
                                ` : `
                                    <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-slate-400" title="Nomor WhatsApp approver belum tersedia">
                                        <i data-lucide="message-circle-off" class="h-3 w-3"></i>
                                        No WA
                                    </span>
                                `}
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
                        <div class="rounded-xl border px-3 py-2.5 ${config[2]}">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-[13px] font-medium text-slate-800">${escapeHtml(item.label || '-')}</div>
                                    <div class="mt-1 truncate text-[11px] text-slate-500">${escapeHtml(item.name || '-')}</div>
                                    ${item.delegated_from_name ? `<div class="mt-0.5 text-[9px] text-slate-500">Dialihkan dari ${escapeHtml(item.delegated_from_name)}</div>` : ''}
                                    ${item.delegation_reason ? `<div class="mt-0.5 text-[9px] text-slate-500">Alasan: ${escapeHtml(item.delegation_reason)}</div>` : ''}
                                </div>
                                <span class="inline-flex shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold ${config[1]}">${config[0]}</span>
                            </div>
                            ${activeActions}
                        </div>
                    `;
                }).join('');

                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
                window.lucide?.createIcons();
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            };

            document.querySelectorAll('.hpp-pkm-approval-trigger').forEach((button) => {
                button.addEventListener('click', () => openModal(button));
            });
            closeButton?.addEventListener('click', closeModal);
            modal?.addEventListener('click', (event) => {
                if (! event.target.closest('[data-hpp-pkm-approval-panel]')) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && ! modal?.classList.contains('hidden')) {
                    closeModal();
                }
            });
        });
    </script>
</x-layouts.pkm>
