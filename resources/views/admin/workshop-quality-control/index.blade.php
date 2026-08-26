<x-layouts.admin title="Quality Control Bengkel">
    @php
        $statusClasses = [
            'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
            'violet' => 'bg-violet-50 text-violet-700 ring-violet-200',
            'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        ];
    @endphp

    <div class="space-y-4">
        <section class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-5">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-900 text-white"><i data-lucide="clipboard-check" class="h-5 w-5"></i></span>
                <div><h1 class="text-lg font-bold text-slate-900">Quality Control</h1><p class="mt-0.5 text-xs text-slate-500">Monitoring pemeriksaan kualitas pekerjaan bengkel.</p></div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap gap-2 border-b border-slate-200 px-4 pt-4">
                @foreach ([
                    'action' => 'Perlu Tindakan',
                    'process' => 'Dalam Proses',
                    'history' => 'Riwayat',
                ] as $key => $label)
                    <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="rounded-lg px-3 py-2 text-xs font-semibold {{ $tab === $key ? 'bg-blue-700 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                        {{ $label }} <span class="ml-1 rounded-full bg-white/20 px-1.5 py-0.5">{{ $tabCounts[$key] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>
            <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-200 p-4 sm:grid-cols-12 sm:items-end">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="sm:col-span-5"><label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-500">Pencarian</label><input name="search" value="{{ $search }}" placeholder="Cari nomor order / pekerjaan / unit..." class="h-10 w-full rounded-lg border border-slate-300 px-3 text-xs"></div>
                <div class="sm:col-span-2"><label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-500">Jenis QC</label><select name="type" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs"><option value="">Semua Jenis</option><option value="fabrication" @selected($selectedType === 'fabrication')>Fabrikasi</option><option value="refurbish" @selected($selectedType === 'refurbish')>Refurbish</option></select></div>
                <button class="h-10 rounded-lg bg-blue-700 px-4 text-xs font-semibold text-white">Terapkan</button>
            </form>
            <div class="overflow-x-auto"><table class="min-w-[1100px] w-full text-left text-xs"><thead class="bg-slate-100 text-[10px] uppercase tracking-wider text-slate-600"><tr><th class="px-4 py-3">Order</th><th class="px-4 py-3">Detail Pekerjaan</th><th class="px-4 py-3">Jenis QC</th><th class="px-4 py-3">Report</th><th class="px-4 py-3">Progress Approval</th><th class="px-4 py-3 text-center">Aksi</th></tr></thead><tbody class="divide-y divide-slate-200">
                @forelse ($orders as $order)
                    @php
                        $state = $queue->status($order);
                        $report = $order->latestQualityControlReport;
                        $progressText = $report && $report->status === \App\Models\QualityControlReport::STATUS_SUBMITTED
                            ? $report->approvalSignedCount().'/'.$report->approvalStepCount().' TTD'
                            : '-';
                        $activeSignature = $report?->signatures?->first(fn ($signature) => $signature->isPending());
                        $managerSignatures = $report?->signatures?->keyBy('role_key') ?? collect();
                        $checklist = $report ? collect([
                            [
                                'step' => 1,
                                'role' => 'Pembuat QC',
                                'name' => $report->makerSignature()['signer_name'] ?? '-',
                                'status' => $report->status === \App\Models\QualityControlReport::STATUS_SUBMITTED && $report->hasValidMakerSignature() ? 'signed' : 'pending',
                                'status_label' => $report->status === \App\Models\QualityControlReport::STATUS_SUBMITTED && $report->hasValidMakerSignature() ? 'Sudah TTD' : 'Belum TTD',
                                'signed_at' => $report->makerSignature()['signed_at'] ?? null,
                                'is_active' => false,
                            ],
                            ...collect([
                                [\App\Models\QualityControlSignature::ROLE_WORKSHOP_MANAGER, 'Manager Workshop'],
                                [\App\Models\QualityControlSignature::ROLE_USER_MANAGER, 'Manager User'],
                            ])->map(function (array $role) use ($managerSignatures): array {
                                $signature = $managerSignatures->get($role[0]);

                                return [
                                    'step' => $role[0] === \App\Models\QualityControlSignature::ROLE_WORKSHOP_MANAGER ? 2 : 3,
                                    'role' => $signature?->displayRoleLabel() ?: $role[1],
                                    'name' => $signature?->signer_name ?: '-',
                                    'status' => $signature?->status ?: \App\Models\QualityControlSignature::STATUS_MISSING,
                                    'status_label' => ! $signature
                                        ? 'Signer belum lengkap'
                                        : match ($signature->status) {
                                            \App\Models\QualityControlSignature::STATUS_SIGNED => 'Sudah TTD',
                                            \App\Models\QualityControlSignature::STATUS_PENDING => $signature->tokenExpired() ? 'Token kedaluwarsa' : 'Menunggu TTD',
                                            \App\Models\QualityControlSignature::STATUS_LOCKED => 'Belum aktif',
                                            default => 'Signer belum lengkap',
                                        },
                                    'signed_at' => $signature?->signed_at?->format('d/m/Y H:i'),
                                    'is_active' => $signature?->isPending() ?? false,
                                    'can_reassign' => $signature?->isPending() ?? false,
                                    'reassign_url' => $signature ? route('admin.orders.approval-signatures.quality-control.reassign', $signature) : null,
                                    'signer_user_id' => $signature?->signer_user_id,
                                    'original_role' => $signature?->role_label ?: $role[1],
                                ];
                            })->all(),
                        ])->values()->all() : [];
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 align-top"><p class="font-bold text-slate-900">{{ $order->nomor_order }}</p><p class="text-[10px] text-blue-600">Notif: {{ $order->notifikasi ?: '-' }}</p></td>
                        <td class="px-4 py-3 align-top"><p class="font-semibold text-slate-900">{{ $order->nama_pekerjaan }}</p><p class="text-[10px] text-slate-500">Unit: {{ $order->unit_kerja ?: '-' }}</p><p class="text-[10px] text-blue-600">Seksi: {{ $order->seksi ?: '-' }}</p></td>
                        <td class="px-4 py-3 align-top">{{ $report?->type === \App\Models\QualityControlReport::TYPE_REFURBISH ? 'Refurbish' : ($report ? 'Fabrikasi' : '-') }}</td>
                        <td class="px-4 py-3 align-top"><span class="font-semibold text-slate-800">{{ $report?->report_no ?: 'Belum dibuat' }}</span><p class="text-[10px] text-slate-500">{{ $report?->status === \App\Models\QualityControlReport::STATUS_SUBMITTED ? 'Submitted' : ($report ? 'Draft' : '-') }}</p></td>
                        <td class="px-4 py-3 align-top">
                            @if ($report)
                                <div class="flex items-center gap-2"><span class="rounded-full bg-blue-50 px-2 py-1 text-[10px] font-bold text-blue-700">{{ $progressText }}</span><span class="font-semibold text-slate-700">{{ $activeSignature?->displayRoleLabel() ?: $state['label'] }}</span></div>
                                <div class="mt-1 h-1.5 w-44 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ min(100, $report->approvalProgressPercent()) }}%"></div></div>
                            @else
                                <span class="text-slate-400">Belum ada report</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center align-top">
                            <div class="flex flex-wrap justify-center gap-1.5">
                                @if ($report)
                                    <button type="button" class="approval-signature-info-trigger inline-flex h-8 items-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-3 font-semibold text-blue-700" data-title="Approval QC - {{ $order->nomor_order }}" data-summary="{{ $progressText }}" data-checklist='@json($checklist)' data-approval-url="{{ $activeSignature?->approvalUrl() }}" data-whatsapp-url="{{ $activeSignature && $activeSignature->approvalUrl() ? \App\Support\ApprovalWhatsappLink::forQualityControl($activeSignature) : '' }}" data-resend-url="{{ $activeSignature && $activeSignature->approvalUrl() ? route('admin.orders.workshop.quality-control.approval.resend', [$order, $report]) : '' }}" data-regenerate-url="{{ $activeSignature && $activeSignature->tokenExpired() ? route('admin.orders.workshop.quality-control.approval.regenerate', [$order, $report]) : '' }}" data-expiry="{{ $activeSignature?->token_expires_at?->format('d/m/Y H:i') }}"><i data-lucide="list-checks" class="h-3.5 w-3.5"></i>Detail</button>
                                    <a href="{{ route('admin.orders.workshop.quality-control.edit', [$order, $report]) }}" class="inline-flex h-8 items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 font-semibold text-slate-700"><i data-lucide="{{ $report->status === \App\Models\QualityControlReport::STATUS_SUBMITTED ? 'eye' : 'pencil' }}" class="h-3.5 w-3.5"></i>{{ $report->status === \App\Models\QualityControlReport::STATUS_SUBMITTED ? 'Lihat' : 'Periksa' }}</a>
                                    <a href="{{ route('admin.orders.workshop.quality-control.pdf', [$order, $report]) }}" target="_blank" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700" title="PDF"><i data-lucide="file-text" class="h-3.5 w-3.5"></i></a>
                                @else
                                    <a href="{{ route('admin.orders.workshop.quality-control.create', $order) }}" class="inline-flex h-8 items-center gap-1 rounded-lg bg-blue-700 px-3 font-semibold text-white"><i data-lucide="clipboard-plus" class="h-3.5 w-3.5"></i>Periksa</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ $tab === 'action' ? 'Tidak ada QC yang memerlukan tindakan.' : ($tab === 'process' ? 'Tidak ada QC yang sedang menunggu tanda tangan.' : 'Belum ada riwayat QC selesai.') }}</td></tr>
                @endforelse
            </tbody></table></div>
            <div class="border-t border-slate-200 px-4 py-3 text-[10px] text-slate-500">Menampilkan {{ $orders->count() }} pekerjaan Quality Control.</div>
            @if ($orders->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">{{ $orders->links() }}</div>
            @endif
        </section>
    </div>

    @include('admin.orders.partials.approval-signature-modal', ['approvalReassignmentUsers' => $approvalReassignmentUsers])
</x-layouts.admin>
