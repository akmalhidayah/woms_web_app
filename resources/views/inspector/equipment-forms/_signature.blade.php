<section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="inspector-signature-title">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 id="inspector-signature-title" class="text-sm font-bold">Penanggung Jawab / Inspektor</h2>
            <p class="mt-1 text-xs text-slate-500">{{ $inspection?->inspector_name ?? auth()->user()->name }}</p>
        </div>

        @if ($inspectorSignature)
            <div class="w-full rounded-xl border border-slate-200 p-4 sm:max-w-sm">
                <img src="{{ route('inspector.inspections.signature', $inspection) }}" alt="Tanda tangan Inspektor" class="mx-auto max-h-24 max-w-full">
                <p class="mt-3 text-center text-sm font-bold">{{ $inspectorSignature->signer_name }}</p>
                <p class="mt-1 text-center text-xs text-slate-500">Ditandatangani {{ $inspectorSignature->signed_at->format('d/m/Y H:i') }} · Versi {{ $inspectorSignature->document_version }}</p>
            </div>
        @elseif (! $readOnly)
            <div class="w-full space-y-3 sm:max-w-sm">
                <input type="hidden" name="document_version" value="{{ $inspection?->document_version ?? 1 }}">
                <input type="hidden" name="signature_data" :value="signatureData">

                <div class="flex min-h-28 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3">
                    <img x-show="signatureData" x-cloak :src="signatureData || null" alt="Tanda tangan baru" class="max-h-24 max-w-full">
                    <p x-show="!signatureData" class="text-center text-sm text-slate-500">Belum ditandatangani</p>
                </div>

                <button type="button" @click="openSignature()" :disabled="!canSign" class="w-full rounded-xl border border-blue-600 px-4 py-2.5 text-sm font-bold text-blue-700 disabled:border-slate-300 disabled:text-slate-400">
                    <span x-text="signatureData ? 'Ubah Tanda Tangan' : 'Tanda Tangan'">Tanda Tangan</span>
                </button>

                <label class="flex items-start gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="confirmed" value="1" x-model="signatureConfirmed" class="mt-0.5">
                    <span>Saya mengonfirmasi isi pemeriksaan dan tanda tangan ini.</span>
                </label>
            </div>

            <div x-show="signatureModalOpen" x-cloak @keydown.escape.window="signatureModalOpen = false" class="fixed inset-0 z-[100] flex items-end justify-center p-0 sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="signature-modal-title">
                <button type="button" @click="signatureModalOpen = false" class="absolute inset-0 bg-slate-950/60" aria-label="Tutup modal tanda tangan"></button>
                <div class="relative w-full rounded-t-2xl bg-white p-5 sm:max-w-2xl sm:rounded-2xl sm:p-6">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h3 id="signature-modal-title" class="font-bold text-slate-900">Tanda Tangan Inspektor</h3>
                            <p class="mt-1 text-xs text-slate-500">Gambar tanda tangan pada kotak di bawah.</p>
                        </div>
                        <button type="button" @click="signatureModalOpen = false" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Tutup</button>
                    </div>

                    <canvas x-ref="signatureCanvas" width="1000" height="320" @pointerdown.prevent="startStroke($event)" @pointermove.prevent="drawStroke($event)" @pointerup="endStroke($event)" @pointercancel="endStroke($event)" @lostpointercapture="pointerId = null" class="block h-48 w-full touch-none cursor-crosshair rounded-xl border border-slate-300 bg-white sm:h-56" aria-label="Kanvas tanda tangan menggunakan mouse atau sentuhan">Browser tidak mendukung kanvas tanda tangan.</canvas>

                    <div class="mt-4 grid grid-cols-2 gap-2 sm:flex sm:justify-end">
                        <button type="button" @click="clearSignature()" :disabled="signing" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold">Hapus</button>
                        <button type="button" @click="acceptSignature()" :disabled="!hasInk || signing" class="rounded-xl bg-[#7f1017] px-4 py-2.5 text-sm font-bold text-white disabled:opacity-40">Gunakan TTD</button>
                    </div>
                </div>
            </div>
        @else
            <p class="text-sm text-slate-500">Tanda tangan belum tersedia.</p>
        @endif
    </div>
</section>
