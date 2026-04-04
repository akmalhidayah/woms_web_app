<div id="oaEditModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/45 px-4 py-6">
    <div class="flex min-h-full items-start justify-center sm:items-center">
        <div class="my-4 w-full max-w-5xl overflow-hidden rounded-[1.75rem] bg-white shadow-2xl shadow-slate-900/20">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Edit Outline Agreement</h2>
                    <p class="mt-2 text-sm text-slate-500">Perubahan identitas master akan diperbarui langsung. Perubahan nilai atau periode aktif akan otomatis masuk ke histori OA.</p>
                </div>
                <button type="button" id="closeEditModal" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-50">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="max-h-[calc(92vh-6rem)] overflow-y-auto px-6 py-6">
                <form method="POST" id="editOutlineAgreementForm" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_edit_id" id="editAgreementId" value="{{ old('_edit_id') }}">
                    <input type="hidden" name="current_period_start" id="editCurrentPeriodStartHidden" value="{{ old('current_period_start') }}">
                    <input type="hidden" name="initial_value_preview" id="editInitialValueHidden" value="{{ old('initial_value_preview') }}">

                    @if ($errors->any() && old('_method') === 'PUT')
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            <div class="font-semibold">Data OA belum bisa diperbarui.</div>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Unit Kerja</label>
                            <select name="unit_work_id" id="editUnitWorkId" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                                <option value="">Pilih Unit Kerja</option>
                                @foreach ($unitWorks as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nomor OA</label>
                            <input type="text" name="nomor_oa" id="editNomorOa" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Jenis Kontrak</label>
                            <select name="jenis_kontrak" id="editJenisKontrak" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                                <option value="">Pilih Jenis Kontrak</option>
                                @foreach ($jenisKontrakOptions as $jenisLabel => $names)
                                    <option value="{{ $jenisLabel }}">{{ $jenisLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Kontrak</label>
                            <select name="nama_kontrak" id="editNamaKontrak" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                                <option value="">Pilih Nama Kontrak</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Nilai Awal OA</div>
                            <div id="editInitialValuePreview" class="mt-2 text-xl font-bold text-slate-900">Rp 0</div>
                            <p class="mt-1 text-sm text-slate-500">Nilai awal disimpan sebagai histori pertama dan tidak diubah oleh edit ini.</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nilai Aktif Kontrak</label>
                            <input type="number" step="0.01" min="0" name="current_total_nilai" id="editCurrentTotalNilai" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                            <p class="mt-2 text-xs text-slate-500">Jika nilainya berubah, sistem akan membuat histori OA baru secara otomatis.</p>
                        </div>
                    </div>

                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                        <h3 class="text-sm font-semibold text-slate-800">Periode Aktif</h3>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-white bg-white px-4 py-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Periode Mulai Aktif</div>
                                <div id="editPeriodStartPreview" class="mt-2 text-base font-semibold text-slate-900">-</div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Periode Akhir Aktif</label>
                                <input type="date" name="current_period_end" id="editCurrentPeriodEnd" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                                <p class="mt-2 text-xs text-slate-500">Jika tanggal akhir berubah, sistem menganggap ini sebagai extend atau revisi periode.</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50/70 p-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-emerald-800">Target Biaya Jasa Pemeliharaan</h3>
                                <p class="mt-1 text-xs text-emerald-700/80">Target tahunan bisa diperbarui langsung dari modal edit ini.</p>
                            </div>
                            <button type="button" id="editAddTargetRow" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">
                                <i data-lucide="plus" class="h-4 w-4"></i>
                                Tambah Target
                            </button>
                        </div>

                        <div id="editTargetsContainer" class="mt-4 space-y-3"></div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan Perubahan</label>
                        <textarea name="keterangan_perubahan" id="editKeteranganPerubahan" rows="4" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" placeholder="Opsional. Jelaskan alasan perubahan OA agar histori lebih jelas.">{{ old('keterangan_perubahan') }}</textarea>
                    </div>
                </form>
            </div>

            <div class="flex items-center justify-between border-t border-slate-200 bg-white px-6 py-4">
                <button type="button" id="cancelEditModal" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Batal
                </button>
                <button type="submit" form="editOutlineAgreementForm" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>
