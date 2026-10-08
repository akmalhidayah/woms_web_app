<x-layouts.inspector :title="$form['name'].' — Inspeksi Peralatan'">
    @php
        $ratings = [
            'A' => ['label' => 'Normal', 'classes' => 'peer-checked:border-emerald-700 peer-checked:bg-emerald-700 peer-checked:text-white'],
            'B' => ['label' => 'Kurang Normal', 'classes' => 'peer-checked:border-amber-600 peer-checked:bg-amber-600 peer-checked:text-white'],
            'C' => ['label' => 'Rusak', 'classes' => 'peer-checked:border-red-700 peer-checked:bg-red-700 peer-checked:text-white'],
        ];
        $itemNumber = 0;
    @endphp
    <div
        x-data="{
            answers: @js($emptyValues),
            remarks: @js($emptyValues),
            inspectionDate: @js($inspectionDate),
            initialDate: @js($inspectionDate),
            get summary() {
                const values = Object.values(this.answers);
                return {
                    total: values.length,
                    A: values.filter(value => value === 'A').length,
                    B: values.filter(value => value === 'B').length,
                    C: values.filter(value => value === 'C').length,
                    empty: values.filter(value => value === '').length,
                };
            },
            get progress() {
                return this.summary.total ? Math.round((this.summary.total - this.summary.empty) / this.summary.total * 100) : 0;
            },
            get formattedDate() {
                if (!this.inspectionDate) return 'Belum dipilih';
                const date = new Date(this.inspectionDate + 'T00:00:00');
                return Number.isNaN(date.getTime()) ? 'Tanggal tidak valid' : date.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            },
            hasFinding(id) { return ['B', 'C'].includes(this.answers[id]); },
            reset() {
                if (!window.confirm('Reset seluruh pilihan, keterangan, dan tanggal simulasi ini?')) return;
                this.answers = Object.fromEntries(Object.keys(this.answers).map(id => [id, '']));
                this.remarks = Object.fromEntries(Object.keys(this.remarks).map(id => [id, '']));
                this.inspectionDate = this.initialDate;
            },
        }"
        class="mt-3 space-y-4 sm:mt-2 lg:mt-3"
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('inspector.equipment-forms.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                Kembali ke Daftar Form
            </a>
            <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-900">
                <i data-lucide="flask-conical" class="h-4 w-4" aria-hidden="true"></i>
                Simulasi / Belum Disimpan
            </span>
        </div>

        <noscript>
            <p class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">Aktifkan JavaScript untuk mencoba pilihan, ringkasan, dan reset simulasi.</p>
        </noscript>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="inspection-title">
            <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-700 ring-1 ring-red-100">
                        <i data-lucide="{{ $form['icon'] }}" class="h-6 w-6" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h1 id="inspection-title" class="text-lg font-black tracking-tight text-slate-900 sm:text-xl">FORM INSPEKSI PERALATAN</h1>
                        <p class="mt-1 text-base font-bold text-red-800 sm:text-lg">{{ $form['name'] }}</p>
                    </div>
                </div>
            </div>
            <div class="grid gap-6 p-5 sm:p-6 xl:grid-cols-2">
                <div>
                    <dl class="grid gap-x-5 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Document No</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-700">Belum diterbitkan</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Process</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-700">Inspection &amp; List</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Seksi</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-700">Bengkel Mesin</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unit</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-700">Bengkel</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama Pelaksana</dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4">
                        <label for="inspection-date" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Hari/Tanggal Pemeriksaan</label>
                        <input id="inspection-date" type="date" x-model="inspectionDate" value="{{ $inspectionDate }}" aria-describedby="inspection-date-description" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:outline-none focus:ring-2 focus:ring-red-100 sm:max-w-xs">
                        <p id="inspection-date-description" class="mt-1.5 text-xs text-slate-500" x-text="formattedDate"></p>
                    </div>
                </div>

                <aside aria-label="Placeholder approval kanan atas" class="min-w-0">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="flex flex-col rounded-xl border border-slate-200 bg-slate-50 p-4 text-center">
                            <h2 class="min-h-10 text-xs font-bold leading-5 text-slate-700">Approve<br>Manager Workshop</h2>
                            <div class="my-4 flex min-h-20 flex-1 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white p-3 text-xs text-slate-400">Area tanda tangan<br>Belum tersedia</div>
                            <p class="text-xs text-slate-500">Tahap berikutnya</p>
                        </div>
                        <div class="flex flex-col rounded-xl border border-slate-200 bg-slate-50 p-4 text-center">
                            <h2 class="min-h-10 text-xs font-bold leading-5 text-slate-700">Approve<br>Leader Gugus / Senior Manager TPM</h2>
                            <div class="my-4 flex min-h-20 flex-1 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white p-3 text-xs text-slate-400">Area tanda tangan<br>Belum tersedia</div>
                            <p class="text-xs text-slate-500">Tahap berikutnya</p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-500">Placeholder posisi tanda tangan saja. Belum ada pengajuan atau proses approval.</p>
                </aside>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="inspection-summary-title">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 id="inspection-summary-title" class="text-sm font-bold text-slate-900">Ringkasan Pemeriksaan</h2>
                <span class="text-xs text-slate-500">Pilihan awal kosong · Satu kondisi per item</span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5" aria-live="polite" aria-atomic="true">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold text-slate-600">Total item</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $form['item_count'] }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                    <p class="text-xs font-semibold text-emerald-800">A · Normal</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-800" x-text="summary.A">0</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3">
                    <p class="text-xs font-semibold text-amber-800">B · Kurang Normal</p>
                    <p class="mt-1 text-2xl font-bold text-amber-800" x-text="summary.B">0</p>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50 p-3">
                    <p class="text-xs font-semibold text-red-800">C · Rusak</p>
                    <p class="mt-1 text-2xl font-bold text-red-800" x-text="summary.C">0</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold text-slate-600">Belum diperiksa</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900" x-text="summary.empty">{{ $form['item_count'] }}</p>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between gap-3 text-xs font-semibold text-slate-600">
                <span x-text="(summary.total - summary.empty) + ' dari ' + summary.total + ' item telah dipilih'">0 dari {{ $form['item_count'] }} item telah dipilih</span>
                <span x-text="progress + '%'">0%</span>
            </div>
            <div role="progressbar" aria-label="Progress pengisian simulasi" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="progress" class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-[#7f1017] transition-all" :style="{ width: progress + '%' }"></div>
            </div>
            <p class="mt-2 text-xs text-slate-500">Progress menunjukkan pilihan kondisi, bukan status penyimpanan atau kelayakan peralatan.</p>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="checklist-title">
            <div class="border-b border-slate-200 p-5 sm:px-6">
                <h2 id="checklist-title" class="text-base font-bold text-slate-900">Checklist Pemeriksaan</h2>
                <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                    <span class="rounded-lg bg-emerald-100 px-3 py-1.5 text-emerald-800">A = Normal</span>
                    <span class="rounded-lg bg-amber-100 px-3 py-1.5 text-amber-800">B = Kurang Normal</span>
                    <span class="rounded-lg bg-red-100 px-3 py-1.5 text-red-800">C = Rusak</span>
                </div>
                <p id="checklist-help" class="mt-3 text-xs leading-5 text-slate-500">Pilih satu kondisi per baris. Untuk B atau C, jelaskan temuan pada kolom REMARK. Geser tabel ke samping pada layar kecil.</p>
            </div>
            <div class="overflow-x-auto focus-visible:outline-2 focus-visible:outline-red-700" tabindex="0" role="region" aria-label="Tabel checklist yang dapat digeser" aria-describedby="checklist-help">
                <table class="w-full min-w-[760px] border-collapse text-sm">
                    <caption class="sr-only">Checklist {{ $form['name'] }}. A: Normal, B: Kurang Normal, C: Rusak.</caption>
                    <thead class="bg-slate-100 text-left text-xs font-bold text-slate-600">
                        <tr>
                            <th scope="col" class="w-14 border-b border-r border-slate-200 px-3 py-3 text-center">NO</th>
                            <th scope="col" class="border-b border-r border-slate-200 px-4 py-3">ACTIVITY / PEMERIKSAAN</th>
                            @foreach ($ratings as $value => $rating)
                                <th scope="col" class="w-16 border-b border-r border-slate-200 px-2 py-3 text-center" title="{{ $rating['label'] }}">{{ $value }}</th>
                            @endforeach
                            <th scope="col" class="w-80 border-b border-slate-200 px-4 py-3">REMARK</th>
                        </tr>
                    </thead>
                    @foreach ($form['groups'] as $group)
                        <tbody>
                            @if ($group['name'])
                                <tr>
                                    <th colspan="6" scope="rowgroup" class="border-b border-slate-200 bg-slate-100 px-4 py-3 text-left text-sm font-bold text-red-800">{{ $group['name'] }}</th>
                                </tr>
                            @endif
                            @foreach ($group['items'] as $item)
                                @php($itemNumber++)
                                <tr :class="{ 'bg-amber-50': answers['{{ $item['id'] }}'] === 'B', 'bg-red-50': answers['{{ $item['id'] }}'] === 'C' }">
                                    <td class="border-b border-r border-slate-200 px-3 py-4 text-center font-semibold text-slate-500">{{ $itemNumber }}</td>
                                    <th scope="row" class="border-b border-r border-slate-200 px-4 py-4 text-left font-semibold text-slate-800">
                                        {{ $item['label'] }}
                                        <span x-show="hasFinding('{{ $item['id'] }}')" x-cloak class="mt-2 block text-xs font-semibold" :class="answers['{{ $item['id'] }}'] === 'C' ? 'text-red-800' : 'text-amber-800'" x-text="answers['{{ $item['id'] }}'] === 'C' ? 'C · Rusak — jelaskan temuan' : 'B · Kurang Normal — jelaskan temuan'"></span>
                                    </th>
                                    @foreach ($ratings as $value => $rating)
                                        <td class="border-b border-r border-slate-200 px-2 py-4 text-center">
                                            <label for="{{ $item['id'] }}-{{ $value }}" class="inline-flex cursor-pointer" title="{{ $value }} — {{ $rating['label'] }}">
                                                <input id="{{ $item['id'] }}-{{ $value }}" type="radio" name="{{ $item['id'] }}" value="{{ $value }}" x-model="answers['{{ $item['id'] }}']" autocomplete="off" aria-label="{{ $itemNumber }}. {{ $group['name'] ? $group['name'].' — ' : '' }}{{ $item['label'] }}: {{ $value }} — {{ $rating['label'] }}" class="peer sr-only">
                                                <span aria-hidden="true" class="flex h-11 w-11 items-center justify-center rounded-lg border border-slate-300 bg-white text-lg font-bold text-slate-300 transition peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-red-700 {{ $rating['classes'] }}" x-text="answers['{{ $item['id'] }}'] === '{{ $value }}' ? '✓' : '—'">—</span>
                                            </label>
                                        </td>
                                    @endforeach
                                    <td class="border-b border-slate-200 px-4 py-3">
                                        <label for="remark-{{ $item['id'] }}" class="mb-1.5 block text-xs font-semibold text-slate-600" x-text="hasFinding('{{ $item['id'] }}') ? 'Penjelasan temuan' : 'Keterangan (opsional)'">Keterangan (opsional)</label>
                                        <textarea id="remark-{{ $item['id'] }}" x-model="remarks['{{ $item['id'] }}']" rows="2" maxlength="2000" autocomplete="off" aria-label="Keterangan item {{ $itemNumber }} — {{ $item['label'] }}" :placeholder="hasFinding('{{ $item['id'] }}') ? 'Jelaskan kondisi atau kerusakan yang ditemukan...' : 'Tambahkan keterangan bila perlu...'" class="block w-full min-w-56 resize-y rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs leading-5 text-slate-700 placeholder:text-slate-400 focus:border-red-700 focus:outline-none focus:ring-2 focus:ring-red-100"></textarea>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="inspector-signature-title">
            <h2 id="inspector-signature-title" class="text-sm font-bold text-slate-900">Penanggung Jawab / Inspektor</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Inspektor</p>
                    <p class="mt-3 break-words text-sm font-bold text-slate-900">{{ auth()->user()->name }}</p>
                </div>
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Paraf</p>
                    <div class="flex min-h-16 items-center justify-center text-xs text-slate-400">Area paraf · Belum tersedia</div>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hari / Tanggal</p>
                    <p class="mt-3 text-sm font-semibold text-slate-700" x-text="formattedDate"></p>
                </div>
            </div>
        </section>

        <footer class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-xl">
                <p class="text-sm font-semibold text-slate-800">Simulasi saja — tidak ada data inspeksi yang disimpan.</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">Pilihan dan keterangan hilang saat memuat ulang atau meninggalkan halaman. Tidak ada dokumen atau permintaan approval yang dikirim.</p>
            </div>
            <div class="shrink-0">
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="reset()" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                        <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                        Reset pilihan
                    </button>
                    <button type="button" disabled aria-describedby="future-actions-note" title="Tersedia pada tahap berikutnya" class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400">
                        <i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i>
                        Simpan Draft
                    </button>
                    <button type="button" disabled aria-describedby="future-actions-note" title="Tersedia pada tahap berikutnya" class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400">
                        <i data-lucide="pen-line" class="h-4 w-4" aria-hidden="true"></i>
                        Tanda Tangan
                    </button>
                </div>
                <p id="future-actions-note" class="mt-2 text-xs text-slate-500">Simpan Draft &amp; Tanda Tangan: Tersedia pada tahap berikutnya.</p>
            </div>
        </footer>
    </div>
</x-layouts.inspector>
