<div id="oaMonthlyRealizationModal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-950/45 px-4 py-6">
    <div class="my-4 w-full max-w-3xl overflow-hidden rounded-[1.75rem] bg-white shadow-2xl shadow-slate-900/20">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">Realisasi Biaya Bulanan</h2>
                <p id="monthlyRealizationAgreementInfo" class="mt-1 text-sm text-slate-500">-</p>
            </div>
            <button id="closeMonthlyRealizationModal" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-50" aria-label="Tutup modal realisasi biaya bulanan">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="max-h-[calc(90vh-6rem)] overflow-y-auto px-6 py-5">
            <form id="monthlyRealizationForm" method="POST" action="" class="space-y-4">
                @csrf
                <input id="monthlyRealizationOaId" type="hidden" name="_monthly_oa_id" value="{{ old('_monthly_oa_id') }}">
                <input id="monthlyRealizationId" type="hidden" name="realization_id" value="{{ old('realization_id') }}">

                @if ($errors->any() && old('_monthly_oa_id'))
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <div class="font-semibold">Realisasi biaya belum dapat disimpan.</div>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="monthlyRealizationYear" class="mb-2 block text-sm font-semibold text-slate-700">Tahun</label>
                        <input id="monthlyRealizationYear" type="number" name="year" min="1" max="9999" step="1" value="{{ old('year') }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-sky-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label for="monthlyRealizationMonth" class="mb-2 block text-sm font-semibold text-slate-700">Bulan</label>
                        <select id="monthlyRealizationMonth" name="month" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-sky-500 focus:outline-none" required>
                            <option value="">Pilih bulan</option>
                            @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $monthNumber => $monthName)
                                <option value="{{ $monthNumber }}" @selected((string) old('month') === (string) $monthNumber)>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="monthlyRealizationCategory" class="mb-2 block text-sm font-semibold text-slate-700">Kategori Biaya</label>
                        <select id="monthlyRealizationCategory" name="kategori_biaya" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-sky-500 focus:outline-none" required>
                            <option value="">Pilih kategori biaya</option>
                            @foreach ($monthlyRealizationCategoryOptions as $categoryValue => $categoryLabel)
                                <option value="{{ $categoryValue }}" @selected(old('kategori_biaya') === $categoryValue)>{{ $categoryLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="monthlyRealizationAmount" class="mb-2 block text-sm font-semibold text-slate-700">Nilai Realisasi</label>
                        <input id="monthlyRealizationAmount" type="text" name="amount" inputmode="numeric" value="{{ old('amount', '0') }}" placeholder="0" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-sky-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label for="monthlyRealizationUnitWork" class="mb-2 block text-sm font-semibold text-slate-700">Unit Kerja</label>
                        <select id="monthlyRealizationUnitWork" name="unit_kerja" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-sky-500 focus:outline-none" required>
                            <option value="">Pilih unit kerja</option>
                            @foreach ($unitWorks as $unit)
                                <option value="{{ $unit->name }}" data-sections="{{ $unit->sections->pluck('name')->values()->toJson() }}" @selected(old('unit_kerja') === $unit->name)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="monthlyRealizationSection" class="mb-2 block text-sm font-semibold text-slate-700">Seksi</label>
                        <select id="monthlyRealizationSection" name="seksi" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-sky-500 focus:outline-none" required disabled>
                            <option value="">Pilih unit kerja terlebih dahulu</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Simpan
                    </button>
                </div>
            </form>

            <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Periode</th>
                                <th class="px-4 py-3">Kategori Biaya</th>
                                <th class="px-4 py-3">Seksi / Unit Kerja</th>
                                <th class="px-4 py-3 text-right">Nilai Realisasi</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="monthlyRealizationRows" class="divide-y divide-slate-100 bg-white"></tbody>
                    </table>
                </div>
                <div id="monthlyRealizationEmpty" class="hidden px-4 py-8 text-center text-sm text-slate-500">
                    Belum ada realisasi biaya bulanan untuk OA ini.
                </div>
            </div>
        </div>
    </div>
</div>
