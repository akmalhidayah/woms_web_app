<x-layouts.admin title="Detail Inspeksi Peralatan">
    <div class="space-y-4">
        <header class="rounded-2xl border border-slate-200 bg-white p-5">
            <a href="{{ route('admin.inspections.index') }}" class="text-sm font-semibold text-blue-700">← Monitoring Inspeksi</a>
            <h1 class="mt-3 text-xl font-bold">{{ $inspection->form_name }}</h1>
            <p class="mt-2 text-sm">{{ $inspection->document_no ?? 'Belum diterbitkan' }} · Versi {{ $inspection->document_version }} · {{ $inspection->inspection_date->format('d/m/Y') }}</p>
            <p class="mt-2 font-semibold text-blue-900">{{ $inspection->statusLabel() }}</p>
            <a href="{{ route('admin.inspections.pdf', $inspection) }}" target="_blank" rel="noopener" class="mt-3 inline-block rounded-xl bg-blue-700 px-4 py-2 text-sm text-white">Preview PDF</a>
            <div class="mt-3"><x-inspections.delete-button :inspection="$inspection" route-name="admin.inspections.destroy" /></div>
        </header>
        <x-inspections.sweet-alerts :success="session('success')" :error-messages="$errors->all()" :warning="collect([$inspection->workflow_error, $inspection->archive_error])->filter()->implode(' ')" />
        @if ($inspection->status === \App\Models\EquipmentInspection::STATUS_LEADER)
            <form method="POST" action="{{ route('admin.inspections.recover', $inspection) }}" data-swal-confirm data-swal-title="Finalisasi laporan lama?" data-swal-text="Laporan akan difinalisasi memakai tanda tangan Inspektor dan Manager Workshop yang sudah tersimpan." data-swal-confirm-text="Ya, finalisasi">@csrf<p class="mb-2 text-sm">Tahap TPM / Leader Gugus dinonaktifkan. Finalisasi menggunakan tanda tangan Inspektor dan Manager Workshop yang sudah tersimpan.</p><button class="rounded-lg bg-blue-700 px-4 py-2 text-white">Finalisasi dengan Dua Tanda Tangan</button></form>
        @endif
        @if ($inspection->status === \App\Models\EquipmentInspection::STATUS_READY || ($inspection->status === \App\Models\EquipmentInspection::STATUS_APPROVED && !$inspection->final_pdf_path))
            <form method="POST" action="{{ route('admin.inspections.recover', $inspection) }}" data-swal-confirm data-swal-title="Proses pemulihan inspeksi?" data-swal-text="Sistem akan mencoba memulihkan alur approval atau arsip PDF sesuai status laporan saat ini." data-swal-confirm-text="Ya, proses">@csrf<button class="rounded-lg bg-blue-700 px-4 py-2 text-white">{{ $inspection->status === \App\Models\EquipmentInspection::STATUS_READY ? 'Inisialisasi / Pulihkan Approval' : 'Coba Arsipkan PDF Final' }}</button></form>
        @endif
        @if ($inspection->revision_note)<section class="rounded-xl border border-amber-500 bg-white p-4"><h2 class="font-bold">Catatan Pengembalian</h2><p class="mt-2 whitespace-pre-wrap text-sm">{{ $inspection->revision_note }}</p><p class="mt-2 text-xs text-slate-600">{{ $inspection->returned_by_name }} · {{ $inspection->returned_at?->format('d/m/Y H:i') }}</p></section>@endif
        <section class="grid gap-3 md:grid-cols-2" aria-label="Tahap approval aktif">
            @foreach (['inspector' => 'Inspektor', 'manager_workshop' => 'Manager Workshop'] as $role => $label)
                @php
                    $signature = $inspection->signatures->first(fn ($s) => $s->document_version === $inspection->document_version && $s->role_key === $role);
                    $approval = $inspection->approvals->first(fn ($a) => $a->document_version === $inspection->document_version && $a->role_key === $role);
                    $states = ['locked' => 'Belum menjadi giliran', 'pending' => 'Menunggu tanda tangan', 'approved' => 'Disetujui', 'returned' => 'Dikembalikan untuk revisi', 'cancelled' => 'Dibatalkan'];
                    $emailStates = ['not_sent' => 'Belum dikirim', 'sending' => 'Sedang diproses; hasil belum terkonfirmasi', 'sent' => 'Terkirim melalui transport mail', 'resent' => 'Dikirim ulang melalui transport mail', 'failed' => 'Gagal'];
                @endphp
                <article class="rounded-xl border border-slate-300 bg-white p-5"><h2 class="font-bold">{{ $loop->iteration }}. {{ $label }}</h2><p class="mt-2 text-sm">{{ $signature?->signer_name ?? $approval?->signer_name ?? ($role === 'inspector' ? $inspection->inspector_name : 'Belum ditetapkan') }}</p><p class="mt-2 text-sm font-semibold">{{ $signature ? 'Sudah ditandatangani' : ($states[$approval?->status] ?? 'Belum ditandatangani') }}</p><p class="mt-1 text-xs text-slate-600">{{ ($signature?->signed_at ?? $approval?->decided_at)?->format('d/m/Y H:i') }}</p>
                    @if ($approval)
                        <p class="mt-3 whitespace-pre-wrap text-sm">{{ $approval->decision_note }}</p><p class="mt-3 text-xs">Email: {{ $emailStates[$approval->email_status] ?? $approval->email_status }}<br>Percobaan: {{ $approval->email_attempted_at?->format('d/m/Y H:i') ?? '—' }}</p>
                        @if ($approval->status === 'pending' && $inspection->status === \App\Models\EquipmentInspection::STATUS_MANAGER)
                            <p class="mt-2 text-xs">Token: {{ $approval->token_expires_at?->isFuture() ? 'Aktif' : 'Kedaluwarsa' }}</p>
                            <form method="POST" action="{{ route('admin.inspections.resend', [$inspection, $approval]) }}" class="mt-3" data-swal-confirm data-swal-title="Kirim ulang email approval?" data-swal-text="Email akan dikirim ulang kepada approver yang sedang aktif." data-swal-confirm-text="Ya, kirim ulang">@csrf<button class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white">Kirim Ulang Email Approval</button></form>
                        @endif
                    @endif
                </article>
            @endforeach
        </section>
        <section class="rounded-xl border border-slate-300 bg-white p-5"><h2 class="font-bold">Histori Versi &amp; Tanda Tangan</h2><p class="mt-2 text-xs text-slate-500">Tanda tangan versi terdahulu hanya bukti historis; tidak menyetujui revisi aktif.</p><div class="mt-3 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr><th class="p-2">Versi</th><th class="p-2">Penandatangan</th><th class="p-2">Jabatan</th><th class="p-2">Waktu</th><th class="p-2">PDF</th></tr></thead><tbody>@foreach ($inspection->signatures as $signature)<tr class="border-t"><td class="p-2">{{ $signature->document_version }}</td><td class="p-2">{{ $signature->signer_name }}</td><td class="p-2">{{ $signature->signer_position }}</td><td class="p-2">{{ $signature->signed_at->format('d/m/Y H:i') }}</td><td class="p-2"><a class="text-blue-700 underline" href="{{ route('admin.inspections.pdf', [$inspection, 'version' => $signature->document_version]) }}" target="_blank" rel="noopener">PDF versi {{ $signature->document_version }}</a></td></tr>@endforeach</tbody></table></div></section>
        <section class="rounded-xl border border-slate-300 bg-white p-5">
            <h2 class="font-bold">Histori Keputusan Approval</h2>
            <div class="mt-3 space-y-3">@foreach ($inspection->approvals->whereNotNull('decided_at') as $decision)<article class="rounded-lg border border-slate-200 p-3 text-sm"><p class="font-semibold">Versi {{ $decision->document_version }} · {{ $decision->signer_name }} · {{ $decision->signer_position }}</p><p class="mt-1">{{ $decision->status === 'returned' ? 'Dikembalikan untuk revisi' : 'Disetujui' }} · {{ $decision->decided_at->format('d/m/Y H:i') }}</p><p class="mt-2 whitespace-pre-wrap">{{ $decision->decision_note ?: 'Tanpa catatan' }}</p></article>@endforeach</div>
        </section>
        <section class="rounded-xl border border-slate-300 bg-white p-5"><h2 class="font-bold">Audit</h2><div class="mt-3 space-y-2">@foreach ($logs as $log)<p class="border-b py-2 text-xs">{{ $log->created_at->format('d/m/Y H:i') }} · v{{ $log->document_version }} · {{ $log->event }}</p>@endforeach</div><div class="mt-3">{{ $logs->links() }}</div></section>
    </div>
</x-layouts.admin>
