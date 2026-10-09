<!DOCTYPE html>
<html lang="id">
<head>@include('partials.head', ['title' => 'Approval Inspeksi Peralatan'])</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<main class="mx-auto max-w-7xl space-y-4 p-3 sm:p-6">
    <header class="rounded-2xl bg-[#5b0f1b] p-6 text-white">
        <p class="text-sm">Inspeksi Peralatan · Dokumen Approval</p>
        <h1 class="mt-2 text-2xl font-bold">{{ $inspection->form_name }}</h1>
        <p class="mt-2">{{ $inspection->document_no }} · Versi {{ $inspection->document_version }} · {{ $inspection->inspection_date->format('d/m/Y') }}</p>
        <p class="mt-2 text-sm">{{ $approval->signer_name }} — {{ $approval->signer_position }} · Tahap {{ $approval->step_order }} dari 3</p>
        <a class="mt-3 inline-block underline" href="{{ route('approval-documents.index') }}">Kembali ke Dokumen Approval</a>
    </header>
    @if ($errors->any())<p role="alert" class="rounded-xl bg-red-100 p-4 text-red-900">{{ $errors->first() }}</p>@endif
    <p class="rounded-xl border border-slate-300 bg-white p-4 text-sm">Persetujuan mencatat review laporan, bukan pernyataan alat layak operasi. Kondisi C/Rusak tetap tercatat dan boleh disetujui. Catatan persetujuan opsional; alasan revisi wajib.</p>
    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-300 bg-white p-4">
            <a href="{{ route('approval.equipment-inspection.pdf', $token) }}" target="_blank" rel="noopener" class="mb-3 inline-block rounded-lg bg-blue-700 px-4 py-2 text-white">Buka PDF</a>
            <iframe src="{{ route('approval.equipment-inspection.pdf', $token) }}" title="Preview laporan inspeksi" class="h-[650px] w-full border border-slate-200"></iframe>
        </section>
        <section class="space-y-4">
            <form id="signatureForm" method="POST" enctype="multipart/form-data" action="{{ route('approval.equipment-inspection.decide', $token) }}" class="rounded-2xl border border-slate-300 bg-white p-5">
                @csrf
                <input type="hidden" name="decision" value="approve">
                <input type="hidden" name="document_version" value="{{ $inspection->document_version }}">
                <input type="file" name="signature_file" id="signatureFile" accept="image/png" class="hidden">
                <h2 class="font-bold">Setujui dan Tanda Tangani</h2>
                <div id="signaturePadShell" class="relative mt-3 overflow-hidden rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-3">
                    <div class="absolute right-3 top-3 z-20 flex gap-2">
                        @if ($recentSignatureDataUrl)<button type="button" id="useRecentSignature" data-signature-src="{{ $recentSignatureDataUrl }}" class="rounded-lg border bg-white px-3 py-2 text-xs">Pakai TTD Terakhir</button>@endif
                        <button type="button" id="clearSignature" class="rounded-lg border bg-white px-3 py-2 text-xs">Clear</button>
                    </div>
                    <canvas id="signatureCanvas" width="620" height="260" class="relative z-10 h-56 w-full touch-none"></canvas>
                    <div id="signaturePadPlaceholder" class="pointer-events-none absolute inset-3 flex items-center justify-center text-slate-500">Tanda tangan di sini</div>
                </div>
                <p id="signaturePadReadyState" class="mt-2 hidden text-sm text-green-800">Tanda tangan siap disimpan</p>
                <p id="signaturePadErrorState" class="mt-2 hidden text-sm text-red-800"></p>
                <label class="mt-3 block text-sm">Catatan persetujuan (opsional)<textarea name="decision_note" maxlength="2000" class="mt-1 w-full rounded-lg border border-slate-300 p-2">{{ old('decision_note') }}</textarea></label>
                <label class="my-3 flex items-start gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required>Saya sudah meninjau laporan versi ini dan menyetujuinya.</label>
                <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white">Simpan Tanda Tangan</button>
            </form>
            <form method="POST" action="{{ route('approval.equipment-inspection.decide', $token) }}" class="rounded-2xl border border-amber-500 bg-white p-5" onsubmit="return confirm('Kembalikan laporan ini untuk revisi dan ulangi alur tanda tangan?')">
                @csrf
                <input type="hidden" name="decision" value="return">
                <input type="hidden" name="document_version" value="{{ $inspection->document_version }}">
                <input type="hidden" name="confirmed" value="1">
                <label class="block text-sm font-semibold">Alasan revisi (wajib)<textarea name="decision_note" required maxlength="2000" class="mt-2 w-full rounded-lg border border-slate-300 p-2">{{ old('decision_note') }}</textarea></label>
                <button class="mt-3 rounded-xl bg-amber-500 px-4 py-3 font-semibold text-slate-950">Kembalikan untuk Revisi</button>
            </form>
        </section>
    </div>
</main>
@include('approval.partials.signature-pad-visuals')
@include('approval.partials.equipment-inspection-signature-script')
</body>
</html>
