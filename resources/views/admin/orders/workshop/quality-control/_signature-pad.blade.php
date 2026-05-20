@php
    $payload = $payload ?? [];
    $signature = $payload['signature'] ?? [];
    $theme = $theme ?? 'blue';
    $roleLabel = $roleLabel ?? 'Tanda tangan';
    $signatureData = old('signature.signature_data', $signature['signature_data'] ?? '');
    $signaturePreview = $signatureData;

    if ($signaturePreview && ! str_starts_with($signaturePreview, 'data:image')) {
        $signaturePreview = \Illuminate\Support\Facades\Storage::disk('public')->exists($signaturePreview)
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($signaturePreview)
            : '';
    }

    $signerName = old('signature.signer_name', $signature['signer_name'] ?? auth()->user()?->name ?? '');
    $signedAt = old('signature.signed_at', $signature['signed_at'] ?? now()->format('Y-m-d'));
    $themeClasses = $theme === 'emerald'
        ? [
            'border' => 'border-emerald-100',
            'ring' => 'ring-emerald-100',
            'text' => 'text-emerald-700',
            'button' => 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
        ]
        : [
            'border' => 'border-blue-100',
            'ring' => 'ring-blue-100',
            'text' => 'text-blue-700',
            'button' => 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100',
        ];
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <h2 class="text-base font-bold text-slate-900">{{ $roleLabel }}</h2>
            <p class="mt-1 text-[12px] text-slate-500">Tanda tangan akan tampil di PDF pada area {{ $roleLabel }}.</p>
        </div>
        <div class="md:w-44">
            <label class="mb-1.5 block text-[11px] font-semibold text-slate-600">Tanggal TTD</label>
            <input
                type="date"
                name="signature[signed_at]"
                value="{{ $signedAt }}"
                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"
                data-qc-signature-date
            >
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1fr_0.55fr]">
        <div
            class="rounded-2xl border {{ $themeClasses['border'] }} bg-slate-50 p-3"
            data-qc-signature-pad
            data-current-signer="{{ auth()->user()?->name ?? '' }}"
            data-current-date="{{ now()->format('Y-m-d') }}"
            data-existing-signature="{{ $signaturePreview }}"
        >
            <canvas class="h-44 w-full rounded-xl bg-white ring-1 {{ $themeClasses['ring'] }}" data-qc-signature-canvas></canvas>
            <input type="hidden" name="signature[signature_existing]" value="{{ $signatureData }}" data-qc-signature-existing>
            <input type="file" name="signature[signature_file]" accept="image/png,image/jpeg" class="hidden" data-qc-signature-data>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                <div class="text-[11px] font-medium text-slate-500">Gunakan mouse/touchpad untuk tanda tangan.</div>
                <button type="button" class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition {{ $themeClasses['button'] }}" data-qc-signature-clear>
                    Hapus TTD
                </button>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <label class="mb-1.5 block text-[11px] font-semibold text-slate-600">Nama Penandatangan</label>
            <input
                type="text"
                name="signature[signer_name]"
                value="{{ $signerName }}"
                readonly
                class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700"
                data-qc-signature-signer
            >
            <div class="mt-3 rounded-xl border {{ $themeClasses['border'] }} bg-slate-50 px-3 py-2 text-[12px] leading-5 text-slate-500">
                Nama otomatis mengikuti user yang sedang login saat tanda tangan dibuat.
            </div>
        </div>
    </div>
</section>
