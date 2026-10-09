<section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="inspector-signature-title">
    <h2 id="inspector-signature-title" class="text-sm font-bold">Penanggung Jawab / Inspektor</h2>
    @if ($inspectorSignature)
        <img src="{{ route('inspector.inspections.signature', $inspection) }}" alt="Tanda tangan Inspektor" class="my-3 max-h-28 max-w-full">
        <p class="text-sm font-bold">{{ $inspectorSignature->signer_name }}</p>
        <p class="mt-1 text-xs text-slate-500">Ditandatangani {{ $inspectorSignature->signed_at->format('d/m/Y H:i') }} · Versi {{ $inspectorSignature->document_version }}</p>
    @elseif ($inspection)
        <p class="mt-2 text-xs leading-5 text-slate-500">Lengkapi seluruh A/B/C, keterangan B/C, dan simpan perubahan untuk membuka tanda tangan. Setelah ditandatangani, laporan dan foto terkunci.</p>
        <button type="button" @click="openSignature()" :disabled="!canSign" class="mt-4 w-full rounded-xl bg-[#7f1017] px-4 py-3 text-sm font-bold text-white disabled:opacity-40 sm:w-auto">Tanda Tangani Pemeriksaan</button>
        <form x-show="signOpen" x-cloak method="POST" action="{{ route('inspector.inspections.sign', $inspection) }}" @submit="submitSignature($event)" class="mt-5 max-w-2xl space-y-3">
            @csrf
            <input type="hidden" name="lock_version" value="{{ $inspection->lock_version }}">
            <input type="hidden" name="document_version" value="{{ $inspection->document_version }}">
            <input type="hidden" name="signature_data" :value="signatureData">
            <p class="text-sm font-semibold">Gambar tanda tangan baru Anda</p>
            <canvas x-ref="signatureCanvas" width="1000" height="320" @pointerdown.prevent="startStroke($event)" @pointermove.prevent="drawStroke($event)" @pointerup="endStroke($event)" @pointercancel="endStroke($event)" @lostpointercapture="pointerId = null" class="block w-full touch-none rounded-xl border border-slate-300 bg-white" aria-label="Kanvas tanda tangan menggunakan mouse atau sentuhan">Browser tidak mendukung kanvas tanda tangan.</canvas>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="clearSignature()" :disabled="signing" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Hapus coretan</button>
                <button type="button" @click="previewSignature()" :disabled="!hasInk || signing" class="rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:opacity-40">Preview tanda tangan</button>
            </div>
            <div x-show="signatureData" x-cloak class="space-y-3">
                <img :src="signatureData || null" alt="Preview tanda tangan baru" class="max-h-28 max-w-full rounded border border-slate-200">
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required class="mt-1">Saya mengonfirmasi isi pemeriksaan dan tanda tangan ini. Laporan akan dikunci dan nomor dokumen diterbitkan.</label>
                <button type="submit" :disabled="!canSign || !signatureData" class="w-full rounded-xl bg-[#7f1017] px-4 py-3 text-sm font-bold text-white disabled:opacity-40" x-text="signing ? 'Memproses tanda tangan...' : 'Konfirmasi Tanda Tangan'">Konfirmasi Tanda Tangan</button>
            </div>
        </form>
    @else
        <p class="mt-2 text-sm text-slate-500">Simpan Draft terlebih dahulu untuk melanjutkan ke tanda tangan.</p>
    @endif
</section>
