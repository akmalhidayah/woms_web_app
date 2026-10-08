<x-layouts.inspector :title="$form['name'].' — Inspeksi Peralatan'">
    @php
        $ratings = [
            'A' => ['label' => 'Normal', 'classes' => 'peer-checked:border-emerald-700 peer-checked:bg-emerald-700 peer-checked:text-white'],
            'B' => ['label' => 'Kurang Normal', 'classes' => 'peer-checked:border-amber-600 peer-checked:bg-amber-600 peer-checked:text-white'],
            'C' => ['label' => 'Rusak', 'classes' => 'peer-checked:border-red-700 peer-checked:bg-red-700 peer-checked:text-white'],
        ];
        $mobileItemNumber = 0;
        $desktopItemNumber = 0;
    @endphp
    <div
        x-data="{
            answers: @js($emptyValues),
            remarks: @js($emptyValues),
            inspectionDate: @js($inspectionDate),
            initialDate: @js($inspectionDate),
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
            <p class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">Aktifkan JavaScript untuk mencoba pilihan dan reset simulasi.</p>
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
            <div class="p-5 sm:p-6">
                <div class="max-w-3xl">
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
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="checklist-title">
            <div class="border-b border-slate-200 p-5 sm:px-6">
                <h2 id="checklist-title" class="text-base font-bold text-slate-900">Checklist Pemeriksaan</h2>
                <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                    <span class="rounded-lg bg-emerald-100 px-3 py-1.5 text-emerald-800">A = Normal</span>
                    <span class="rounded-lg bg-amber-100 px-3 py-1.5 text-amber-800">B = Kurang Normal</span>
                    <span class="rounded-lg bg-red-100 px-3 py-1.5 text-red-800">C = Rusak</span>
                </div>
                <p id="checklist-help" class="mt-3 text-xs leading-5 text-slate-500">Pilih satu kondisi per item. Untuk B atau C, jelaskan temuan pada bagian keterangan.</p>
            </div>

            <div class="space-y-3 p-3 md:hidden" aria-label="Checklist pemeriksaan versi mobile">
                @foreach ($form['groups'] as $group)
                    @if ($group['name'])
                        <h3 class="rounded-xl bg-slate-100 px-4 py-3 text-sm font-bold text-red-800">{{ $group['name'] }}</h3>
                    @endif
                    @foreach ($group['items'] as $item)
                        @php($mobileItemNumber++)
                        <article class="rounded-xl border p-4 transition" :class="{ 'border-amber-300 bg-amber-50': answers['{{ $item['id'] }}'] === 'B', 'border-red-300 bg-red-50': answers['{{ $item['id'] }}'] === 'C', 'border-slate-200 bg-white': !hasFinding('{{ $item['id'] }}') }">
                            <div class="flex items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">{{ $mobileItemNumber }}</span>
                                <div class="min-w-0">
                                    <h4 class="text-sm font-bold leading-5 text-slate-900">{{ $item['label'] }}</h4>
                                    <p x-show="hasFinding('{{ $item['id'] }}')" x-cloak class="mt-1 text-xs font-semibold" :class="answers['{{ $item['id'] }}'] === 'C' ? 'text-red-800' : 'text-amber-800'" x-text="answers['{{ $item['id'] }}'] === 'C' ? 'C · Rusak — jelaskan temuan' : 'B · Kurang Normal — jelaskan temuan'"></p>
                                </div>
                            </div>
                            <fieldset class="mt-4">
                                <legend class="sr-only">Kondisi {{ $item['label'] }}</legend>
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach ($ratings as $value => $rating)
                                        <label for="mobile-{{ $item['id'] }}-{{ $value }}" class="cursor-pointer">
                                            <input id="mobile-{{ $item['id'] }}-{{ $value }}" type="radio" name="mobile-{{ $item['id'] }}" value="{{ $value }}" x-model="answers['{{ $item['id'] }}']" autocomplete="off" class="peer sr-only">
                                            <span class="flex min-h-14 flex-col items-center justify-center rounded-xl border border-slate-300 bg-white px-1 py-2 text-center text-xs font-bold text-slate-600 transition peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-red-700 {{ $rating['classes'] }}">
                                                <span class="text-base" x-text="answers['{{ $item['id'] }}'] === '{{ $value }}' ? '✓' : '{{ $value }}'">{{ $value }}</span>
                                                <span class="mt-0.5 text-[10px] font-semibold">{{ $rating['label'] }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <label for="mobile-remark-{{ $item['id'] }}" class="mt-4 block text-xs font-semibold text-slate-600" x-text="hasFinding('{{ $item['id'] }}') ? 'Penjelasan temuan' : 'Keterangan (opsional)'">Keterangan (opsional)</label>
                            <textarea id="mobile-remark-{{ $item['id'] }}" x-model="remarks['{{ $item['id'] }}']" rows="3" maxlength="2000" autocomplete="off" :placeholder="hasFinding('{{ $item['id'] }}') ? 'Jelaskan kondisi atau kerusakan yang ditemukan...' : 'Tambahkan keterangan bila perlu...'" class="mt-1.5 block w-full resize-y rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm leading-5 text-slate-700 placeholder:text-slate-400 focus:border-red-700 focus:outline-none focus:ring-2 focus:ring-red-100"></textarea>
                        </article>
                    @endforeach
                @endforeach
            </div>

            <div class="hidden overflow-x-auto focus-visible:outline-2 focus-visible:outline-red-700 md:block" tabindex="0" role="region" aria-label="Tabel checklist pemeriksaan" aria-describedby="checklist-help">
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
                                @php($desktopItemNumber++)
                                <tr :class="{ 'bg-amber-50': answers['{{ $item['id'] }}'] === 'B', 'bg-red-50': answers['{{ $item['id'] }}'] === 'C' }">
                                    <td class="border-b border-r border-slate-200 px-3 py-4 text-center font-semibold text-slate-500">{{ $desktopItemNumber }}</td>
                                    <th scope="row" class="border-b border-r border-slate-200 px-4 py-4 text-left font-semibold text-slate-800">
                                        {{ $item['label'] }}
                                        <span x-show="hasFinding('{{ $item['id'] }}')" x-cloak class="mt-2 block text-xs font-semibold" :class="answers['{{ $item['id'] }}'] === 'C' ? 'text-red-800' : 'text-amber-800'" x-text="answers['{{ $item['id'] }}'] === 'C' ? 'C · Rusak — jelaskan temuan' : 'B · Kurang Normal — jelaskan temuan'"></span>
                                    </th>
                                    @foreach ($ratings as $value => $rating)
                                        <td class="border-b border-r border-slate-200 px-2 py-4 text-center">
                                            <label for="desktop-{{ $item['id'] }}-{{ $value }}" class="inline-flex cursor-pointer" title="{{ $value }} — {{ $rating['label'] }}">
                                                <input id="desktop-{{ $item['id'] }}-{{ $value }}" type="radio" name="desktop-{{ $item['id'] }}" value="{{ $value }}" x-model="answers['{{ $item['id'] }}']" autocomplete="off" aria-label="{{ $desktopItemNumber }}. {{ $group['name'] ? $group['name'].' — ' : '' }}{{ $item['label'] }}: {{ $value }} — {{ $rating['label'] }}" class="peer sr-only">
                                                <span aria-hidden="true" class="flex h-11 w-11 items-center justify-center rounded-lg border border-slate-300 bg-white text-lg font-bold text-slate-300 transition peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-red-700 {{ $rating['classes'] }}" x-text="answers['{{ $item['id'] }}'] === '{{ $value }}' ? '✓' : '—'">—</span>
                                            </label>
                                        </td>
                                    @endforeach
                                    <td class="border-b border-slate-200 px-4 py-3">
                                        <label for="desktop-remark-{{ $item['id'] }}" class="mb-1.5 block text-xs font-semibold text-slate-600" x-text="hasFinding('{{ $item['id'] }}') ? 'Penjelasan temuan' : 'Keterangan (opsional)'">Keterangan (opsional)</label>
                                        <textarea id="desktop-remark-{{ $item['id'] }}" x-model="remarks['{{ $item['id'] }}']" rows="2" maxlength="2000" autocomplete="off" aria-label="Keterangan item {{ $desktopItemNumber }} — {{ $item['label'] }}" :placeholder="hasFinding('{{ $item['id'] }}') ? 'Jelaskan kondisi atau kerusakan yang ditemukan...' : 'Tambahkan keterangan bila perlu...'" class="block w-full min-w-56 resize-y rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs leading-5 text-slate-700 placeholder:text-slate-400 focus:border-red-700 focus:outline-none focus:ring-2 focus:ring-red-100"></textarea>
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
                <div class="grid gap-2 sm:flex sm:flex-wrap">
                    <button type="button" @click="reset()" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:w-auto">
                        <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                        Reset pilihan
                    </button>
                    <button type="button" disabled aria-describedby="future-actions-note" title="Tersedia pada tahap berikutnya" class="inline-flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400 sm:w-auto">
                        <i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i>
                        Simpan Draft
                    </button>
                    <button type="button" disabled aria-describedby="future-actions-note" title="Tersedia pada tahap berikutnya" class="inline-flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400 sm:w-auto">
                        <i data-lucide="pen-line" class="h-4 w-4" aria-hidden="true"></i>
                        Tanda Tangan
                    </button>
                </div>
                <p id="future-actions-note" class="mt-2 text-xs text-slate-500">Simpan Draft &amp; Tanda Tangan: Tersedia pada tahap berikutnya.</p>
            </div>
        </footer>
    </div>
</x-layouts.inspector>
