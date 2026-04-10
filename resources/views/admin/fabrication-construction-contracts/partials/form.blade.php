<div class="space-y-6">
    @php
        $selectedJenisItem = old('jenis_item', $item->jenis_item);
        $selectedSubJenisItem = old('sub_jenis_item', $item->sub_jenis_item);
        $selectedKategoriItem = old('kategori_item', $item->kategori_item);
        $selectedNamaItem = old('nama_item', $item->nama_item);
        $selectedSatuan = old('satuan', $item->satuan);
        $selectedHargaSatuan = old('harga_satuan', $item->harga_satuan);
    @endphp

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-[13px] text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-[13px] text-rose-700">
            <div class="font-semibold">Data master item belum bisa disimpan.</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-[1.75rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
        <div class="flex items-start gap-4">
            <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                <i data-lucide="file-stack" class="h-6 w-6"></i>
            </span>
            <div>
                <h1 class="text-[1.9rem] font-bold leading-none tracking-tight text-slate-900">{{ $title }}</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-500">{{ $subtitle }}</p>
            </div>
        </div>
    </section>

    <form method="POST" action="{{ $submitRoute }}" class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
            <div class="mb-5">
                <h2 class="text-[15px] font-semibold text-slate-900">Master Item Harga</h2>
                <p class="mt-1 text-[13px] text-slate-500">Simpan setiap item sebagai satu baris data agar nanti bisa langsung dipanggil di form HPP.</p>
            </div>

            <div class="mb-5 rounded-2xl border border-orange-100 bg-orange-50/70 px-4 py-3 text-[12px] text-slate-700">
                <div class="font-semibold text-slate-900">Cara isi</div>
                <div class="mt-1">Setiap tombol simpan hanya membuat 1 baris item harga.</div>
                <div class="mt-1">Kalau ingin menambah `jenis_item`, `sub_jenis_item`, atau `kategori_item` baru, langsung ketik nama barunya di field terkait.</div>
                <div class="mt-1">Untuk data seperti `C. MATERIAL`, `sub_jenis_item` dan `kategori_item` boleh dikosongkan.</div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-1.5">
                    <label for="tahun" class="text-[12px] font-semibold text-slate-700">Tahun</label>
                    <input
                        id="tahun"
                        type="number"
                        name="tahun"
                        value="{{ old('tahun', $item->tahun) }}"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[13px] text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100"
                        placeholder="2026"
                    >
                    <p class="text-[11px] text-slate-500">Tahun master harga yang sedang dipakai.</p>
                </div>

                <div class="space-y-1.5">
                    <label for="satuan" class="text-[12px] font-semibold text-slate-700">Satuan</label>
                    <input
                        id="satuan"
                        type="text"
                        name="satuan"
                        list="satuan-options"
                        value="{{ old('satuan', $item->satuan) }}"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[13px] text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100"
                        placeholder="Kg / M2 / Jam / Cm3"
                    >
                    <datalist id="satuan-options">
                        @foreach ($satuanOptions as $satuanOption)
                            <option value="{{ $satuanOption }}"></option>
                        @endforeach
                    </datalist>
                    <p class="text-[11px] text-slate-500">Bisa pilih dari saran atau ketik satuan baru.</p>
                </div>
            </div>

            <div class="mt-4 grid gap-4">
                <input type="hidden" name="jenis_item" id="jenis_item" value="{{ $selectedJenisItem }}">
                <input type="hidden" name="sub_jenis_item" id="sub_jenis_item" value="{{ $selectedSubJenisItem }}">
                <input type="hidden" name="kategori_item" id="kategori_item" value="{{ $selectedKategoriItem }}">

                <div class="space-y-2 rounded-2xl border border-slate-200 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <label for="jenis_item_select" class="text-[12px] font-semibold text-slate-700">Jenis Item</label>
                        <button type="button" id="toggle-jenis-item" class="inline-flex items-center rounded-xl border border-orange-200 bg-orange-50 px-3 py-1.5 text-[11px] font-semibold text-orange-700 transition hover:bg-orange-100">
                            Tambah Jenis
                        </button>
                    </div>
                    <select
                        id="jenis_item_select"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[13px] text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100"
                    >
                        <option value="">Pilih jenis item</option>
                        @foreach ($jenisItemOptions as $jenisItemOption)
                            <option value="{{ $jenisItemOption }}" @selected($selectedJenisItem === $jenisItemOption)>{{ $jenisItemOption }}</option>
                        @endforeach
                    </select>
                    <input
                        id="jenis_item_custom"
                        type="text"
                        value="{{ $selectedJenisItem }}"
                        class="hidden w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[13px] text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100"
                        placeholder="Ketik jenis item baru"
                    >
                    <p class="text-[11px] text-slate-500">Pilih dari data lama atau klik `Tambah Jenis` untuk mengetik jenis item baru.</p>
                </div>

                <div class="space-y-2 rounded-2xl border border-slate-200 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <label for="sub_jenis_item_select" class="text-[12px] font-semibold text-slate-700">Sub Jenis Item <span class="text-slate-400">(opsional)</span></label>
                        <button type="button" id="toggle-sub-jenis-item" class="inline-flex items-center rounded-xl border border-orange-200 bg-orange-50 px-3 py-1.5 text-[11px] font-semibold text-orange-700 transition hover:bg-orange-100">
                            Tambah Sub Jenis
                        </button>
                    </div>
                    <select
                        id="sub_jenis_item_select"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[13px] text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100"
                    >
                        <option value="">Tanpa sub jenis</option>
                    </select>
                    <input
                        id="sub_jenis_item_custom"
                        type="text"
                        value="{{ $selectedSubJenisItem }}"
                        class="hidden w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[13px] text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100"
                        placeholder="Ketik sub jenis item baru"
                    >
                    <p class="text-[11px] text-slate-500">Field ini bisa dikosongkan untuk data seperti `C. MATERIAL`.</p>
                </div>

                <div class="space-y-2 rounded-2xl border border-slate-200 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <label for="kategori_item_select" class="text-[12px] font-semibold text-slate-700">Kategori Item <span class="text-slate-400">(opsional)</span></label>
                        <button type="button" id="toggle-kategori-item" class="inline-flex items-center rounded-xl border border-orange-200 bg-orange-50 px-3 py-1.5 text-[11px] font-semibold text-orange-700 transition hover:bg-orange-100">
                            Tambah Kategori
                        </button>
                    </div>
                    <select
                        id="kategori_item_select"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[13px] text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100"
                    >
                        <option value="">Tanpa kategori</option>
                    </select>
                    <input
                        id="kategori_item_custom"
                        type="text"
                        value="{{ $selectedKategoriItem }}"
                        class="hidden w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[13px] text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100"
                        placeholder="Ketik kategori item baru"
                    >
                    <p class="text-[11px] text-slate-500">Pakai kalau ada grup turunan seperti `< 12 Meter` atau `> 12 Meter`.</p>
                </div>

                <div class="space-y-1.5">
                    <label for="nama_item" class="text-[12px] font-semibold text-slate-700">Nama Item</label>
                    <input
                        id="nama_item"
                        type="text"
                        name="nama_item"
                        value="{{ $selectedNamaItem }}"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[13px] text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100"
                        placeholder="Contoh: Pekerjaan Fabrikasi menggunakan Plate Steel ASTM A-36/SS400"
                    >
                    <p class="text-[11px] text-slate-500">Ini nama item final yang nanti dipilih di form HPP.</p>
                </div>

                <div class="space-y-1.5">
                    <label for="harga_satuan" class="text-[12px] font-semibold text-slate-700">Harga Satuan</label>
                    <input
                        id="harga_satuan"
                        type="number"
                        step="0.01"
                        min="0"
                        name="harga_satuan"
                        value="{{ $selectedHargaSatuan }}"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[13px] text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100"
                        placeholder="0.00"
                    >
                    <p class="text-[11px] text-slate-500">Isi harga satuan sesuai item yang dipilih atau master baru yang ingin ditambahkan.</p>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.fabrication-construction-contracts.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-[13px] font-semibold text-slate-600 transition hover:bg-slate-50">
                Kembali
            </a>
            <button type="submit" class="inline-flex items-center rounded-xl bg-orange-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-orange-700">
                {{ $isEdit ? 'Update Item' : 'Simpan Item' }}
            </button>
        </div>
    </form>
</div>

<script>
    (function () {
        const subJenisMap = @json($subJenisMap);
        const kategoriMap = @json($kategoriMap);
        const initialValues = {
            jenis: @json($selectedJenisItem),
            subJenis: @json($selectedSubJenisItem),
            kategori: @json($selectedKategoriItem),
        };

        const fields = {
            jenis: {
                hidden: document.getElementById('jenis_item'),
                select: document.getElementById('jenis_item_select'),
                custom: document.getElementById('jenis_item_custom'),
                toggle: document.getElementById('toggle-jenis-item'),
                customMode: false,
            },
            subJenis: {
                hidden: document.getElementById('sub_jenis_item'),
                select: document.getElementById('sub_jenis_item_select'),
                custom: document.getElementById('sub_jenis_item_custom'),
                toggle: document.getElementById('toggle-sub-jenis-item'),
                customMode: false,
            },
            kategori: {
                hidden: document.getElementById('kategori_item'),
                select: document.getElementById('kategori_item_select'),
                custom: document.getElementById('kategori_item_custom'),
                toggle: document.getElementById('toggle-kategori-item'),
                customMode: false,
            },
        };

        function normalize(value) {
            return typeof value === 'string' ? value.trim() : '';
        }

        function setMode(field, customMode) {
            field.customMode = customMode;
            field.select.classList.toggle('hidden', customMode);
            field.custom.classList.toggle('hidden', ! customMode);
        }

        function syncField(field) {
            field.hidden.value = field.customMode ? normalize(field.custom.value) : normalize(field.select.value);
        }

        function setOptions(select, values, placeholder, selectedValue) {
            const normalizedSelected = normalize(selectedValue);
            select.innerHTML = '';

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = placeholder;
            select.appendChild(defaultOption);

            values.forEach((value) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                if (value === normalizedSelected) {
                    option.selected = true;
                }
                select.appendChild(option);
            });

            if (! values.includes(normalizedSelected)) {
                select.value = '';
            }
        }

        function currentJenisValue() {
            return fields.jenis.customMode ? normalize(fields.jenis.custom.value) : normalize(fields.jenis.select.value);
        }

        function currentSubJenisValue() {
            return fields.subJenis.customMode ? normalize(fields.subJenis.custom.value) : normalize(fields.subJenis.select.value);
        }

        function refreshSubJenisOptions(selectedValue = '') {
            const jenisValue = currentJenisValue();
            const options = subJenisMap[jenisValue] ?? [];
            setOptions(fields.subJenis.select, options, 'Tanpa sub jenis', selectedValue);
            syncField(fields.subJenis);
        }

        function refreshKategoriOptions(selectedValue = '') {
            const key = `${currentJenisValue()}||${currentSubJenisValue()}`;
            const options = kategoriMap[key] ?? [];
            setOptions(fields.kategori.select, options, 'Tanpa kategori', selectedValue);
            syncField(fields.kategori);
        }

        function initializeField(field, initialValue, options = []) {
            const normalizedInitial = normalize(initialValue);
            const hasOption = options.includes(normalizedInitial);

            if (normalizedInitial !== '' && ! hasOption) {
                setMode(field, true);
                field.custom.value = normalizedInitial;
            } else {
                setMode(field, false);
                field.select.value = normalizedInitial;
            }

            syncField(field);
        }

        initializeField(fields.jenis, initialValues.jenis, @json($jenisItemOptions->values()));
        refreshSubJenisOptions(initialValues.subJenis);
        initializeField(fields.subJenis, initialValues.subJenis, subJenisMap[currentJenisValue()] ?? []);
        refreshKategoriOptions(initialValues.kategori);
        initializeField(fields.kategori, initialValues.kategori, kategoriMap[`${currentJenisValue()}||${currentSubJenisValue()}`] ?? []);

        fields.jenis.toggle.addEventListener('click', () => {
            setMode(fields.jenis, ! fields.jenis.customMode);
            if (! fields.jenis.customMode) {
                fields.jenis.select.value = '';
            }
            syncField(fields.jenis);
            refreshSubJenisOptions('');
            refreshKategoriOptions('');
        });

        fields.subJenis.toggle.addEventListener('click', () => {
            setMode(fields.subJenis, ! fields.subJenis.customMode);
            if (! fields.subJenis.customMode) {
                fields.subJenis.select.value = '';
            }
            syncField(fields.subJenis);
            refreshKategoriOptions('');
        });

        fields.kategori.toggle.addEventListener('click', () => {
            setMode(fields.kategori, ! fields.kategori.customMode);
            if (! fields.kategori.customMode) {
                fields.kategori.select.value = '';
            }
            syncField(fields.kategori);
        });

        fields.jenis.select.addEventListener('change', () => {
            syncField(fields.jenis);
            refreshSubJenisOptions('');
            refreshKategoriOptions('');
        });

        fields.jenis.custom.addEventListener('input', () => {
            syncField(fields.jenis);
            refreshSubJenisOptions('');
            refreshKategoriOptions('');
        });

        fields.subJenis.select.addEventListener('change', () => {
            syncField(fields.subJenis);
            refreshKategoriOptions('');
        });

        fields.subJenis.custom.addEventListener('input', () => {
            syncField(fields.subJenis);
            refreshKategoriOptions('');
        });

        fields.kategori.select.addEventListener('change', () => syncField(fields.kategori));
        fields.kategori.custom.addEventListener('input', () => syncField(fields.kategori));
    })();
</script>
