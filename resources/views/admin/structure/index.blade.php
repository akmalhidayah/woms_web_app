<x-layouts.admin title="Struktur Organisasi">
    @php
        $activeFilters = [];
        if ($filters['q'] !== '') $activeFilters[] = 'Pencarian: "'.$filters['q'].'"';
        if ($filters['department'] !== '') {
            $departmentName = optional($departments->firstWhere('id', (int) $filters['department']))->name;
            if ($departmentName) $activeFilters[] = 'Departemen: '.$departmentName;
        }

        $modalSession = session('structure_modal', ['mode' => 'create', 'action' => route('admin.structure.store')]);
        $oldSections = collect(old('sections', []))->map(fn ($section, $index) => [
            'uid' => 'old-'.$index,
            'name' => (string) ($section['name'] ?? ''),
            'manager_id' => isset($section['manager_id']) ? (string) ($section['manager_id'] ?? '') : '',
        ])->values()->all();
        $initialModal = [
            'open' => $errors->any() && session()->has('structure_modal'),
            'mode' => $modalSession['mode'] ?? 'create',
            'action' => $modalSession['action'] ?? route('admin.structure.store'),
            'form' => [
                'department_id' => old('department_id') !== null ? (string) old('department_id') : '',
                'department_name_new' => (string) old('department_name_new', ''),
                'use_new_department' => old('department_name_new') ? true : false,
                'general_manager_id' => old('general_manager_id') !== null ? (string) old('general_manager_id') : '',
                'unit_name' => (string) old('unit_name', ''),
                'senior_manager_id' => old('senior_manager_id') !== null ? (string) old('senior_manager_id') : '',
                'sections' => $oldSections,
            ],
        ];

        $departmentModalSession = session('department_modal', ['action' => '']);
        $initialDepartmentModal = [
            'open' => $errors->any() && session()->has('department_modal'),
            'action' => $departmentModalSession['action'] ?? '',
            'name' => (string) old('name', ''),
            'general_manager_id' => old('general_manager_id') !== null ? (string) old('general_manager_id') : '',
        ];
    @endphp

    @if (session('success'))
        <div id="structure-success" data-message="{{ session('success') }}" class="hidden"></div>
    @endif

    <div class="space-y-5" x-data="structureOrgPage(@js($initialModal), @js($initialDepartmentModal))">
        <section class="rounded-[1.35rem] border border-blue-100 px-4 py-4 shadow-sm" style="background:linear-gradient(135deg,#eef4ff 0%,#f8fbff 48%,#e6f1ff 100%);">
            <div class="flex flex-col gap-3 pr-1 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                        <i data-lucide="network" class="h-4.5 w-4.5"></i>
                    </span>
                    <div>
                        <h1 class="text-[1.65rem] font-bold leading-none tracking-tight text-slate-900">Daftar Struktur Organisasi</h1>
                        <p class="mt-1.5 text-sm text-slate-500">Hierarki Departemen, Unit Kerja, dan Seksi</p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="openCreate()"
                    class="inline-flex min-w-[150px] shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    <span>Tambah Unit</span>
                </button>
            </div>
        </section>

        <section class="rounded-[1.35rem] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs text-slate-500">Menampilkan {{ $structureDepartments->count() }} departemen.</div>
                <div class="text-xs italic text-slate-400">{{ $activeFilters ? implode(' | ', $activeFilters) : 'Tidak ada filter' }}</div>
            </div>

            <form method="GET" action="{{ route('admin.structure.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="grid gap-2.5 lg:grid-cols-[1.2fr_0.9fr_auto] lg:items-end">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label for="q" class="mb-1 block text-[11px] font-semibold text-slate-700">Pencarian</label>
                            <div class="relative">
                                <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                                <input id="q" name="q" type="text" value="{{ $filters['q'] }}" placeholder="Cari departemen, unit, atau seksi..." class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-xs text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none">
                            </div>
                        </div>
                        <div>
                            <label for="department" class="mb-1 block text-[11px] font-semibold text-slate-700">Departemen</label>
                            <select id="department" name="department" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua departemen</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected((int) $filters['department'] === $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 lg:justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">
                            <i data-lucide="filter" class="h-3.5 w-3.5"></i>
                            Terapkan
                        </button>
                        <a href="{{ route('admin.structure.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Reset</a>
                    </div>
                </div>
            </form>

            @if ($structureDepartments->count())
                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                    @foreach ($structureDepartments as $department)
                        <div class="rounded-[1.1rem] border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-1 pb-4">
                                <div class="min-w-0 flex-1 pt-1">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-600">Departemen</p>
                                    <h2 class="mt-1.5 text-[1.2rem] font-bold leading-tight text-slate-900 break-words">{{ $department->name }}</h2>
                                    <div class="mt-3 inline-flex max-w-full items-center gap-2 rounded-xl bg-amber-50 px-3 py-2 text-sm text-slate-700 ring-1 ring-amber-100">
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-[10px] font-bold text-amber-700">GM</span>
                                        <div class="leading-tight">
                                            <div class="font-semibold">{{ $department->generalManager?->name ?? 'Belum dipilih' }}</div>
                                            <div class="mt-0.5 text-[11px] text-slate-500">General Manager</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-2 pr-1 pt-1">
                                    <button
                                        type="button"
                                        data-department="{{ rawurlencode(base64_encode(json_encode([
                                            'action' => route('admin.structure.departments.update', $department),
                                            'name' => $department->name,
                                            'general_manager_id' => $department->general_manager_id,
                                        ]))) }}"
                                        @click="openDepartmentEdit($el.dataset.department)"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition hover:bg-emerald-100"
                                        title="Edit Departemen"
                                    >
                                        <i data-lucide="pen-square" class="h-4 w-4"></i>
                                    </button>
                                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-[11px] font-semibold text-slate-600">{{ $department->units->count() }} unit</span>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3">
                                @forelse ($department->units as $unit)
                                    @php
                                        $editPayload = [
                                            'action' => route('admin.structure.update', $unit),
                                            'department_id' => $department->id,
                                            'general_manager_id' => $department->general_manager_id,
                                            'unit_name' => $unit->name,
                                            'senior_manager_id' => $unit->senior_manager_id,
                                            'sections' => $unit->sections->map(fn ($section) => [
                                                'name' => $section->name,
                                                'manager_id' => $section->manager_id,
                                            ])->values()->all(),
                                        ];
                                    @endphp

                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-sky-600">Unit Kerja</p>
                                                <h3 class="mt-1 text-[1rem] font-semibold leading-snug text-slate-900 break-words">{{ $unit->name }}</h3>
                                            </div>

                                            <div class="flex shrink-0 items-center gap-2">
                                                <button type="button" data-structure="{{ rawurlencode(base64_encode(json_encode($editPayload))) }}" @click="openEdit($el.dataset.structure)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition hover:bg-blue-100" title="Edit Struktur">
                                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                                </button>
                                                <form method="POST" action="{{ route('admin.structure.destroy', $unit) }}" class="delete-structure-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 transition hover:bg-rose-100" data-name="{{ $unit->name }}" title="Hapus Struktur">
                                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="mt-2.5">
                                            <div class="inline-flex max-w-full items-center gap-2 rounded-lg bg-white px-2.5 py-1.5 text-xs text-slate-700 ring-1 ring-slate-200">
                                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-sky-100 text-[10px] font-bold text-sky-700">SM</span>
                                                <div class="leading-tight">
                                                    <div class="font-semibold">{{ $unit->seniorManager?->name ?? 'Belum dipilih' }}</div>
                                                    <div class="mt-0.5 text-[10px] text-slate-500">Senior Manager</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-2.5 border-t border-slate-200 pt-2.5">
                                            <div class="mb-2 flex items-center justify-between gap-3">
                                                <span class="text-[11px] font-semibold text-slate-800">Seksi & Manager</span>
                                                <span class="rounded-full bg-white px-2 py-1 text-[10px] font-semibold text-slate-600 ring-1 ring-slate-200">{{ $unit->sections->count() }} seksi</span>
                                            </div>

                                            @if ($unit->sections->count())
                                                <div class="grid gap-2">
                                                    @foreach ($unit->sections as $section)
                                                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2.5">
                                                            <div class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold leading-snug text-indigo-700 whitespace-normal break-words">
                                                                <i data-lucide="sitemap" class="h-3 w-3 shrink-0"></i>
                                                                <span class="break-words">{{ $section->name }}</span>
                                                            </div>
                                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-700">
                                                                <span class="font-semibold">Manager:</span> {{ $section->manager?->name ?? 'Belum dipilih' }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="rounded-lg border border-dashed border-slate-200 bg-white px-3 py-3 text-xs italic text-slate-500">Belum ada seksi di unit kerja ini.</div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-xs italic text-slate-500">Departemen ini belum memiliki unit kerja.</div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-5 rounded-[1.4rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white text-blue-600 shadow-sm ring-1 ring-slate-200">
                        <i data-lucide="network" class="h-6 w-6"></i>
                    </div>
                    <h2 class="mt-4 text-xl font-semibold text-slate-900">Belum ada struktur organisasi</h2>
                    <p class="mt-2 text-sm text-slate-500">Tambahkan departemen dan unit kerja pertama dari modal.</p>
                    <button
                        type="button"
                        @click="openCreate()"
                        class="mt-5 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300"
                    >
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        <span>Tambah Unit</span>
                    </button>
                </div>
            @endif
        </section>

        <div x-show="showModal" x-transition.opacity x-cloak class="fixed inset-0 z-40 bg-slate-950/55" @click="closeModal()"></div>
        <div x-show="showModal" x-transition.opacity x-cloak class="fixed inset-0 z-50 overflow-y-auto p-4">
            <div class="flex min-h-full items-start justify-center py-10">
                <div class="w-full overflow-hidden rounded-[1.5rem] bg-white shadow-2xl" style="max-width: 920px;" @click.stop>
                    <form method="POST" :action="formAction" class="flex max-h-[88vh] flex-col">
                        @csrf
                        <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

                        <div class="overflow-y-auto px-6 pb-6 pt-6">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-5">
                                <div>
                                    <h2 class="text-[2rem] font-bold leading-tight text-slate-900" x-text="mode === 'create' ? 'Tambah Struktur Organisasi' : 'Edit Struktur Organisasi'"></h2>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-500">Departemen membawahi unit kerja, dan unit kerja membawahi seksi.</p>
                                </div>
                                <button type="button" @click="closeModal()" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                                    <i data-lucide="x" class="h-5 w-5"></i>
                                </button>
                            </div>

                            @if ($errors->any())
                                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                    <div class="font-semibold">Data belum bisa disimpan.</div>
                                    <ul class="mt-2 list-disc space-y-1 pl-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="mt-6 grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-800">Nama Unit Kerja <span class="text-rose-500">*</span></label>
                                    <input type="text" name="unit_name" x-model="form.unit_name" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none" required>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-800">Departemen <span class="text-rose-500">*</span></label>
                                    <template x-if="!form.use_new_department">
                                        <select name="department_id" x-model="form.department_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                            <option value="">Pilih Departemen</option>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                                            @endforeach
                                        </select>
                                    </template>
                                    <template x-if="form.use_new_department">
                                        <input type="text" name="department_name_new" x-model="form.department_name_new" placeholder="Masukkan nama departemen baru" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                    </template>
                                    <div class="mt-2 flex items-center gap-2">
                                        <button type="button" @click="toggleDepartmentMode()" class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">
                                            <i data-lucide="repeat" class="h-3.5 w-3.5"></i>
                                            <span x-text="form.use_new_department ? 'Pilih Departemen Lama' : 'Buat Departemen Baru'"></span>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-800">General Manager</label>
                                    <select name="general_manager_id" x-model="form.general_manager_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                        <option value="">Pilih General Manager</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-800">Senior Manager</label>
                                    <select name="senior_manager_id" x-model="form.senior_manager_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                        <option value="">Pilih Senior Manager</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6">
                                <div class="flex flex-wrap items-center gap-2">
                                    <label class="text-sm font-semibold text-slate-800">Daftar Seksi</label>
                                    <span class="text-sm text-slate-500">(opsional)</span>
                                </div>
                                <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-center">
                                    <input type="text" x-model="sectionDraft" @keydown.enter.prevent="addSection()" placeholder="Ketik nama seksi lalu Enter atau klik Tambah" class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="addSection()" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                            <i data-lucide="plus" class="h-4 w-4"></i>Tambah
                                        </button>
                                        <button type="button" @click="clearSections()" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">Kosongkan</button>
                                    </div>
                                </div>
                                <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                                    <template x-if="form.sections.length === 0">
                                        <p class="text-sm italic text-slate-500">Belum ada seksi. Tambahkan jika diperlukan.</p>
                                    </template>
                                    <div x-show="form.sections.length > 0" class="space-y-3">
                                        <template x-for="(section, index) in form.sections" :key="section.uid">
                                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                                <div class="grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
                                                    <div>
                                                        <label class="mb-1.5 block text-xs font-semibold text-slate-700">Nama Seksi</label>
                                                        <input type="text" :name="`sections[${index}][name]`" x-model="section.name" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1.5 block text-xs font-semibold text-slate-700">Manager Seksi</label>
                                                        <select :name="`sections[${index}][manager_id]`" x-model="section.manager_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                                            <option value="">Pilih Manager</option>
                                                            @foreach ($users as $user)
                                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <button type="button" @click="removeSection(index)" class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-600 transition hover:bg-rose-100" title="Hapus Seksi">
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="border-t border-slate-200 bg-white px-6 py-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <button type="button" @click="closeModal()" class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                                    <i data-lucide="arrow-left" class="h-4 w-4"></i>Kembali
                                </button>
                                <div class="flex items-center gap-3">
                                    <button type="button" @click="closeModal()" class="rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">Batal</button>
                                    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Simpan</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="showDepartmentModal" x-transition.opacity x-cloak class="fixed inset-0 z-40 bg-slate-950/55" @click="closeDepartmentModal()"></div>
        <div x-show="showDepartmentModal" x-transition.opacity x-cloak class="fixed inset-0 z-50 overflow-y-auto p-4">
            <div class="flex min-h-full items-start justify-center py-10">
                <div class="w-full max-w-2xl overflow-hidden rounded-[1.5rem] bg-white shadow-2xl" @click.stop>
                    <form method="POST" :action="departmentForm.action" class="flex max-h-[88vh] flex-col">
                        @csrf
                        <input type="hidden" name="_method" value="PUT">

                        <div class="overflow-y-auto px-6 pb-6 pt-6">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-5">
                                <div>
                                    <h2 class="text-[1.9rem] font-bold leading-tight text-slate-900">Edit Departemen</h2>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-500">Ubah nama departemen dan General Manager di level departemen.</p>
                                </div>
                                <button type="button" @click="closeDepartmentModal()" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                                    <i data-lucide="x" class="h-5 w-5"></i>
                                </button>
                            </div>

                            <div class="mt-6 grid gap-4">
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-800">Nama Departemen</label>
                                    <input type="text" name="name" x-model="departmentForm.name" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none" required>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-slate-800">General Manager</label>
                                    <select name="general_manager_id" x-model="departmentForm.general_manager_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                        <option value="">Pilih General Manager</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                        </div>

                        <div class="border-t border-slate-200 bg-white px-6 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" @click="closeDepartmentModal()" class="rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">Batal</button>
                                <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function structureOrgPage(initialState, initialDepartmentState) {
            return {
                showModal: initialState.open || false,
                showDepartmentModal: initialDepartmentState.open || false,
                mode: initialState.mode || 'create',
                formAction: initialState.action || '{{ route('admin.structure.store') }}',
                sectionDraft: '',
                form: {
                    department_id: initialState.form?.department_id || '',
                    department_name_new: initialState.form?.department_name_new || '',
                    use_new_department: initialState.form?.use_new_department || false,
                    general_manager_id: initialState.form?.general_manager_id || '',
                    unit_name: initialState.form?.unit_name || '',
                    senior_manager_id: initialState.form?.senior_manager_id || '',
                    sections: initialState.form?.sections || [],
                },
                departmentForm: {
                    action: initialDepartmentState.action || '',
                    name: initialDepartmentState.name || '',
                    general_manager_id: initialDepartmentState.general_manager_id || '',
                },
                resetForm() {
                    this.form = {
                        department_id: '',
                        department_name_new: '',
                        use_new_department: false,
                        general_manager_id: '',
                        unit_name: '',
                        senior_manager_id: '',
                        sections: [],
                    };
                    this.sectionDraft = '';
                    this.mode = 'create';
                    this.formAction = '{{ route('admin.structure.store') }}';
                },
                openCreate() {
                    this.resetForm();
                    this.showModal = true;
                },
                openEdit(payload) {
                    let raw = payload || '';
                    try {
                        raw = decodeURIComponent(raw);
                        const data = JSON.parse(atob(raw));
                        this.mode = 'edit';
                        this.formAction = data.action;
                        this.form = {
                            department_id: data.department_id ? String(data.department_id) : '',
                            department_name_new: '',
                            use_new_department: false,
                            general_manager_id: data.general_manager_id ? String(data.general_manager_id) : '',
                            unit_name: data.unit_name || '',
                            senior_manager_id: data.senior_manager_id ? String(data.senior_manager_id) : '',
                            sections: (data.sections || []).map((section, index) => ({
                                uid: `${Date.now()}-${index}`,
                                name: section.name || '',
                                manager_id: section.manager_id ? String(section.manager_id) : '',
                            })),
                        };
                        this.sectionDraft = '';
                        this.showModal = true;
                    } catch (error) {
                        console.error('Gagal membuka edit modal', error);
                        if (window.Swal) {
                            window.Swal.fire({ icon: 'error', title: 'Gagal', text: 'Data edit tidak bisa dibuka.' });
                        }
                    }
                },
                closeModal() {
                    this.showModal = false;
                },
                openDepartmentEdit(payload) {
                    try {
                        const data = JSON.parse(atob(decodeURIComponent(payload || '')));
                        this.departmentForm = {
                            action: data.action || '',
                            name: data.name || '',
                            general_manager_id: data.general_manager_id ? String(data.general_manager_id) : '',
                        };
                        this.showDepartmentModal = true;
                    } catch (error) {
                        console.error('Gagal membuka departemen modal', error);
                        if (window.Swal) {
                            window.Swal.fire({ icon: 'error', title: 'Gagal', text: 'Data departemen tidak bisa dibuka.' });
                        }
                    }
                },
                closeDepartmentModal() {
                    this.showDepartmentModal = false;
                },
                toggleDepartmentMode() {
                    this.form.use_new_department = !this.form.use_new_department;
                    if (this.form.use_new_department) {
                        this.form.department_id = '';
                    } else {
                        this.form.department_name_new = '';
                    }
                },
                addSection() {
                    const value = this.sectionDraft.trim();
                    if (!value) return;
                    if (this.form.sections.some((section) => section.name.toLowerCase() === value.toLowerCase())) {
                        this.sectionDraft = '';
                        return;
                    }
                    this.form.sections.push({ uid: `${Date.now()}-${Math.random()}`, name: value, manager_id: '' });
                    this.sectionDraft = '';
                },
                removeSection(index) {
                    this.form.sections.splice(index, 1);
                },
                clearSections() {
                    this.form.sections = [];
                },
            };
        }

        document.addEventListener('DOMContentLoaded', () => {
            const successFlash = document.getElementById('structure-success');
            if (successFlash?.dataset.message && window.Swal) {
                window.Swal.fire({ icon: 'success', title: 'Berhasil', text: successFlash.dataset.message, timer: 1800, showConfirmButton: false });
            }

            document.querySelectorAll('.delete-structure-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (!window.Swal) return form.submit();
                    const button = form.querySelector('button[type="submit"]');
                    const name = button?.dataset.name || 'struktur ini';
                    const result = await window.Swal.fire({
                        icon: 'warning',
                        title: 'Hapus struktur?',
                        html: `Yakin ingin menghapus <b>${name}</b>?`,
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#dc2626',
                    });
                    if (result.isConfirmed) form.submit();
                });
            });

            if (window.lucide) window.lucide.createIcons();
        });
    </script>
</x-layouts.admin>
