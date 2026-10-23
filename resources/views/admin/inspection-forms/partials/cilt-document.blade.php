@php($cilt = $qrState['cilt'] ?? null)
<section class="mt-5 rounded-xl border border-slate-200 p-4" aria-labelledby="cilt-title-{{ $form->id }}">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 id="cilt-title-{{ $form->id }}" class="flex items-center gap-2 text-sm font-bold text-slate-900"><i data-lucide="book-open" class="h-5 w-5 text-blue-700"></i> Dokumen Standar CILT</h3>
        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ ($cilt['available'] ?? false) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ ($cilt['available'] ?? false) ? 'Tersedia' : 'Belum tersedia' }}</span>
    </div>
    <p class="mt-2 text-xs leading-5 text-slate-500">Cleaning, Inspection, Lubrication &amp; Tightening. Dokumen ini dapat dibaca melalui QR ketika akses publik unit diaktifkan.</p>

    @if ($cilt)
        <div class="mt-3 rounded-lg bg-slate-50 p-3">
            <p class="break-all text-sm font-semibold text-slate-800">{{ $cilt['name'] }}</p>
            @if ($cilt['updated_at'])<p class="mt-1 text-xs text-slate-500">Diperbarui {{ \Illuminate\Support\Carbon::parse($cilt['updated_at'])->timezone(config('app.timezone'))->translatedFormat('d F Y H:i') }}</p>@endif
            @unless ($cilt['available'])<p class="mt-2 text-sm text-amber-800">File dokumen tidak tersedia. Silakan unggah PDF kembali.</p>@endunless
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($cilt['available'])
                    <a href="{{ route('admin.inspection-units.cilt.preview', $qrUnit) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2.5 text-sm font-semibold text-blue-700"><i data-lucide="file-text" class="h-4 w-4"></i> Preview PDF</a>
                @endif
                <form method="POST" action="{{ route('admin.inspection-units.cilt.destroy', $qrUnit) }}" data-swal-confirm data-swal-title="Hapus PDF CILT?" data-swal-text="Dokumen Standar CILT tidak lagi tersedia melalui QR peralatan. Riwayat inspeksi tetap tersimpan." data-swal-confirm-text="Ya, hapus PDF">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="qr_form_id" value="{{ $form->id }}">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm font-semibold text-rose-700"><i data-lucide="trash-2" class="h-4 w-4"></i> Hapus PDF</button>
                </form>
            </div>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.inspection-units.cilt.store', $qrUnit) }}" class="mt-4 space-y-3">
        @csrf
        <input type="hidden" name="qr_form_id" value="{{ $form->id }}">
        <div>
            <label for="cilt-pdf-{{ $form->id }}" class="mb-2 block text-sm font-bold text-slate-800">{{ $cilt ? 'Ganti PDF CILT' : 'Upload PDF CILT' }}</label>
            <input id="cilt-pdf-{{ $form->id }}" type="file" name="cilt_pdf" accept="application/pdf,.pdf" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
            <p class="mt-2 text-xs text-slate-500">PDF saja, maksimal 10 MB. Satu dokumen aktif per unit.</p>
            @if ((int) old('qr_form_id') === (int) $form->id)
                @error('cilt_pdf')<p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
            @endif
        </div>
        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800"><i data-lucide="upload" class="h-4 w-4"></i> {{ $cilt ? 'Simpan PDF Pengganti' : 'Simpan PDF CILT' }}</button>
        </div>
    </form>
</section>
