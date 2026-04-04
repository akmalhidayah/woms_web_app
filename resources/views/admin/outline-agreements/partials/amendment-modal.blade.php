<div id="oaAmendmentModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/45 px-4 py-6">
    <div class="flex min-h-full items-start justify-center sm:items-center">
    <div class="my-4 w-full max-w-3xl overflow-hidden rounded-[1.75rem] bg-white shadow-2xl shadow-slate-900/20">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">Tambah Adendum OA</h2>
                <p id="oaAmendmentMeta" class="mt-2 text-sm text-slate-500">Perubahan akan disimpan sebagai histori baru tanpa merusak data OA sebelumnya.</p>
            </div>
            <button type="button" id="closeAmendmentModal" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-50">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <form method="POST" id="oaAmendmentForm" class="space-y-6 px-6 py-6">
            @csrf

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                <div><span class="font-semibold text-slate-800">OA:</span> <span id="oaAmendmentNumber">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">Unit Kerja:</span> <span id="oaAmendmentUnit">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">Periode Akhir Saat Ini:</span> <span id="oaAmendmentPeriod">-</span></div>
                <div class="mt-1"><span class="font-semibold text-slate-800">Nilai Aktif Saat Ini:</span> <span id="oaAmendmentValue">-</span></div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Tipe Perubahan</label>
                    <select name="tipe_perubahan" id="tipePerubahan" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                        <option value="">Pilih tipe perubahan</option>
                        @foreach ($amendmentTypeOptions as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="nilaiTambahanWrapper" class="hidden">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Nilai Tambahan</label>
                    <input type="number" step="0.01" min="0" name="nilai_tambahan" id="nilaiTambahan" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" placeholder="0">
                </div>

                <div id="periodeEndWrapper" class="hidden">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Periode Akhir Baru</label>
                    <input type="date" name="periode_end" id="periodeEnd" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Keterangan</label>
                    <textarea name="keterangan" rows="4" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" placeholder="Tambahkan catatan adendum jika diperlukan."></textarea>
                </div>
            </div>
        </form>

        <div class="flex items-center justify-between border-t border-slate-200 bg-white px-6 py-4">
            <button type="button" id="cancelAmendmentModal" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Batal
            </button>
            <button type="submit" form="oaAmendmentForm" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                <i data-lucide="save" class="h-4 w-4"></i>
                Simpan Adendum
            </button>
        </div>
    </div>
    </div>
</div>
