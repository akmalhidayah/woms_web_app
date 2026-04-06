<div
    x-cloak
    x-show="createOpen"
    class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/45 px-4 py-6"
    @keydown.escape.window="createOpen = false"
>
    <div class="flex min-h-full items-start justify-center sm:items-center">
    <div class="my-4 w-full max-w-5xl overflow-hidden rounded-[1.75rem] bg-white shadow-2xl shadow-slate-900/20">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">Buat Outline Agreement</h2>
                <p class="mt-2 text-sm text-slate-500">Master OA baru akan menyimpan histori awal otomatis dan target biaya tahunan jika diisi.</p>
            </div>
            <button type="button" @click="createOpen = false" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-50">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="max-h-[calc(92vh-6rem)] overflow-y-auto px-6 py-6">
            <form method="POST" action="{{ route('admin.outline-agreements.store') }}" id="createOutlineAgreementForm" class="space-y-6">
                @csrf

                @if ($errors->any())
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <div class="font-semibold">Data OA belum bisa disimpan.</div>
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
                        <select name="unit_work_id" id="createUnitWorkId" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                            <option value="">Pilih Unit Kerja</option>
                            @foreach ($unitWorks as $unit)
                                <option
                                    value="{{ $unit->id }}"
                                    data-sections='@json($unit->sections->pluck('name')->values())'
                                    @selected((string) old('unit_work_id') === (string) $unit->id)
                                >{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nomor OA</label>
                        <input type="text" name="nomor_oa" value="{{ old('nomor_oa') }}" placeholder="Masukkan nomor OA" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Jenis Kontrak</label>
                        <select name="jenis_kontrak" id="jenisKontrak" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                            <option value="">Pilih seksi unit kerja</option>
                        </select>
                        <p class="mt-2 text-xs text-slate-500">Daftar jenis kontrak mengikuti seksi pada unit kerja yang dipilih.</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Kontrak</label>
                        <input type="text" name="nama_kontrak" id="namaKontrak" value="{{ old('nama_kontrak') }}" placeholder="Masukkan nama kontrak" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nilai Kontrak Awal</label>
                        <input type="number" step="0.01" min="0" name="nilai_kontrak_awal" value="{{ old('nilai_kontrak_awal') }}" placeholder="0" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Total Aktif Awal</div>
                        <div class="mt-2 text-xl font-bold text-slate-900">Sama dengan nilai kontrak awal</div>
                        <p class="mt-1 text-sm text-slate-500">Tambahan nilai kontrak dicatat melalui histori adendum setelah OA dibuat.</p>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                    <h3 class="text-sm font-semibold text-slate-800">Periode Kontrak Awal</h3>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Mulai</label>
                            <input type="date" name="periode_awal_start" value="{{ old('periode_awal_start') }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Selesai</label>
                            <input type="date" name="periode_awal_end" value="{{ old('periode_awal_end') }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" required>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50/70 p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-emerald-800">Target Biaya Jasa Pemeliharaan</h3>
                            <p class="mt-1 text-xs text-emerald-700/80">Opsional. Terpisah dari nilai kontrak aktif OA.</p>
                        </div>
                        <button type="button" id="addTargetRow" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            Tambah Target
                        </button>
                    </div>

                    <div id="targetsContainer" class="mt-4 space-y-3"></div>
                </div>
            </form>
        </div>

        <div class="flex items-center justify-between border-t border-slate-200 bg-white px-6 py-4">
            <button type="button" @click="createOpen = false" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Batal
            </button>
            <button type="submit" form="createOutlineAgreementForm" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                <i data-lucide="save" class="h-4 w-4"></i>
                Simpan OA
            </button>
        </div>
    </div>
    </div>
</div>
