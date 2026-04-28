@php
    $task = $task ?? null;
    $indexQuery = request()->only(['q', 'regu', 'per_page', 'page']);
    $selectedPicIds = collect($selectedPicIds ?? old('pic_ids', []))
        ->map(fn ($value) => (int) $value)
        ->all();
    $rawPicAssignments = old('pic_assignments', $picAssignments ?? null);
    $picAssignmentsPayload = collect(is_array($rawPicAssignments) ? $rawPicAssignments : [])
        ->map(fn ($row) => [
            'pic_id' => (int) ($row['pic_id'] ?? 0),
            'descriptions' => collect($row['descriptions'] ?? [])
                ->map(fn ($description) => trim((string) $description))
                ->filter()
                ->values()
                ->all(),
        ])
        ->filter(fn ($row) => $row['pic_id'] > 0)
        ->values();

    if ($picAssignmentsPayload->isEmpty() && $selectedPicIds !== []) {
        $picAssignmentsPayload = collect($selectedPicIds)
            ->map(fn ($picId) => [
                'pic_id' => (int) $picId,
                'descriptions' => [],
            ])
            ->values();
    }

    if ($picAssignmentsPayload->isEmpty()) {
        $picAssignmentsPayload = collect([[
            'pic_id' => 0,
            'descriptions' => [],
        ]]);
    }

    $picOptionsPayload = $picOptions->map(fn ($pic) => [
        'id' => $pic->id,
        'name' => $pic->name,
        'avatar_url' => $pic->avatar_url,
        'avatar_object_position' => $pic->avatar_object_position,
        'initials' => collect(explode(' ', $pic->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?',
    ])->values()->all();
    $selectedUnit = old('unit_work', $task?->unit_work);
    $selectedSection = old('seksi', $task?->seksi);
    $unitsPayload = $units->map(fn ($unit) => [
        'name' => $unit->name,
        'sections' => $unit->sections->pluck('name')->values()->all(),
    ])->values()->all();
    $workshopOrdersPayload = collect($workshopOrders ?? [])->values()->all();
@endphp

<div class="space-y-6">
    <section class="rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
        <div class="flex items-center gap-4">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                <i data-lucide="monitor" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-[1.45rem] font-bold leading-none tracking-tight text-slate-900">{{ $title }}</h1>
                <p class="mt-1.5 text-[12px] text-slate-500">{{ $description }}</p>
            </div>
        </div>
    </section>

    <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-5 lg:grid-cols-2">
            <div class="space-y-4">
                <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-3">
                    <label for="workshop_order_source" class="mb-1.5 block text-[11px] font-semibold text-slate-700">Sumber Data Pekerjaan</label>
                    <select id="workshop_order_source" class="w-full rounded-xl border border-blue-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option value="">Manual / isi sendiri</option>
                        @foreach ($workshopOrdersPayload as $order)
                            <option value="{{ $order['id'] }}">
                                {{ $order['nomor_order'] }} - {{ \Illuminate\Support\Str::limit($order['nama_pekerjaan'], 80) }}
                            </option>
                        @endforeach
                    </select>
                    <div class="mt-1 text-[10px] text-slate-500">Pilih order bengkel untuk mengisi nama pekerjaan, nomor order, unit, seksi, dan target otomatis.</div>
                </div>

                <div>
                    <label for="job_name" class="mb-1.5 block text-[11px] font-semibold text-slate-700">Nama Pekerjaan</label>
                    <input id="job_name" type="text" name="job_name" value="{{ old('job_name', $task?->job_name) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none" required>
                </div>

                <div>
                    <label for="notification_number" class="mb-1.5 block text-[11px] font-semibold text-slate-700">Nomor Order / Notifikasi</label>
                    <input id="notification_number" type="text" name="notification_number" value="{{ old('notification_number', $task?->notification_number) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="unit_work" class="mb-1.5 block text-[11px] font-semibold text-slate-700">Unit Kerja</label>
                        <select id="unit_work" name="unit_work" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                            <option value="">Pilih unit kerja</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->name }}" @selected($selectedUnit === $unit->name)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="seksi" class="mb-1.5 block text-[11px] font-semibold text-slate-700">Seksi</label>
                        <select id="seksi" name="seksi" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                            <option value="">Pilih seksi</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="usage_plan_date" class="mb-1.5 block text-[11px] font-semibold text-slate-700">Target / Rencana Pakai</label>
                        <input id="usage_plan_date" type="date" name="usage_plan_date" value="{{ old('usage_plan_date', optional($task?->usage_plan_date)->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="catatan" class="mb-1.5 block text-[11px] font-semibold text-slate-700">Regu</label>
                        <select id="catatan" name="catatan" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                            <option value="">Pilih regu</option>
                            @foreach ($catatanOptions as $option)
                                <option value="{{ $option }}" @selected(old('catatan', $task?->catatan) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div x-data="bengkelTaskPicAssignments(@js($picAssignmentsPayload->all()), @js($picOptionsPayload))">
                <div class="mb-1.5 block text-[11px] font-semibold text-slate-700">Penanggung Jawab</div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    @if ($picOptions->isEmpty())
                        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                            Belum ada PIC. Tambahkan dulu dari menu master PIC bengkel.
                        </div>
                    @else
                        <div class="space-y-3">
                            <template x-for="(assignment, assignmentIndex) in assignments" :key="assignment.key">
                                <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                                        <div class="min-w-0 flex-1">
                                            <label class="mb-1.5 block text-[11px] font-semibold text-slate-700">Pilih PIC</label>
                                            <select
                                                x-model="assignment.pic_id"
                                                :name="`pic_assignments[${assignmentIndex}][pic_id]`"
                                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none"
                                            >
                                                <option value="">Pilih PIC</option>
                                                <template x-for="pic in picOptions" :key="pic.id">
                                                    <option :value="String(pic.id)" x-text="pic.name"></option>
                                                </template>
                                            </select>

                                            <div class="mt-2 flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2" x-show="selectedPic(assignment.pic_id)">
                                                <template x-if="selectedPic(assignment.pic_id)?.avatar_url">
                                                    <img :src="selectedPic(assignment.pic_id).avatar_url" alt="" class="h-9 w-9 rounded-full object-cover ring-1 ring-slate-200" :style="`object-position: ${selectedPic(assignment.pic_id).avatar_object_position};`">
                                                </template>
                                                <template x-if="! selectedPic(assignment.pic_id)?.avatar_url">
                                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-[11px] font-bold text-slate-700" x-text="selectedPic(assignment.pic_id)?.initials || '?'"></span>
                                                </template>
                                                <span class="min-w-0 text-sm font-semibold text-slate-700" x-text="selectedPic(assignment.pic_id)?.name"></span>
                                            </div>
                                        </div>

                                        <button type="button" x-show="assignments.length > 1" @click="removeAssignment(assignmentIndex)" class="inline-flex items-center justify-center gap-1 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            Hapus PIC
                                        </button>
                                    </div>

                                    <div class="mt-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-3">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <div>
                                                <div class="text-[11px] font-semibold text-slate-700">Uraian Pekerjaan PIC</div>
                                                <div class="text-[10px] text-slate-500">Tambahkan satu atau beberapa uraian yang dikerjakan PIC ini.</div>
                                            </div>
                                            <button type="button" @click="addDescription(assignmentIndex)" class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-[11px] font-semibold text-blue-700 transition hover:bg-blue-100">
                                                <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                                Tambah Uraian
                                            </button>
                                        </div>

                                        <div class="space-y-2">
                                            <template x-for="(description, descriptionIndex) in assignment.descriptions" :key="description.key">
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        type="text"
                                                        x-model="description.value"
                                                        :name="`pic_assignments[${assignmentIndex}][descriptions][${descriptionIndex}]`"
                                                        placeholder="Contoh: Potong material / pengelasan / finishing"
                                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none"
                                                    >
                                                    <button type="button" @click="removeDescription(assignmentIndex, descriptionIndex)" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100">
                                                        <i data-lucide="x" class="h-4 w-4"></i>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <button type="button" @click="addAssignment()" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                                <i data-lucide="user-plus" class="h-4 w-4"></i>
                                Tambah PIC
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('admin.bengkel-tasks.index', $indexQuery) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Kembali
            </a>

            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                <i data-lucide="save" class="h-4 w-4"></i>
                {{ $submitLabel }}
            </button>
        </div>
    </section>
</div>

@if ($errors->any())
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            window.Swal?.fire({
                icon: 'error',
                title: 'Gagal',
                text: @json($errors->first()),
                confirmButtonText: 'OK',
            });
        });
    </script>
@endif

<script>
    window.bengkelTaskPicAssignments = (initialAssignments, picOptions) => ({
        assignments: [],
        picOptions: picOptions || [],

        init() {
            const rows = Array.isArray(initialAssignments) && initialAssignments.length
                ? initialAssignments
                : [{ pic_id: '', descriptions: [] }];

            this.assignments = rows.map((row) => this.normalizeAssignment(row));
            this.refreshIcons();
        },

        normalizeAssignment(row = {}) {
            const descriptions = Array.isArray(row.descriptions) && row.descriptions.length
                ? row.descriptions
                : [''];

            return {
                key: window.crypto?.randomUUID ? window.crypto.randomUUID() : `${Date.now()}-${Math.random()}`,
                pic_id: row.pic_id ? String(row.pic_id) : '',
                descriptions: descriptions.map((value) => ({
                    key: window.crypto?.randomUUID ? window.crypto.randomUUID() : `${Date.now()}-${Math.random()}`,
                    value: value || '',
                })),
            };
        },

        selectedPic(picId) {
            const id = Number(picId);

            if (! id) {
                return null;
            }

            return this.picOptions.find((pic) => Number(pic.id) === id) || null;
        },

        addAssignment() {
            this.assignments.push(this.normalizeAssignment());
            this.refreshIcons();
        },

        removeAssignment(index) {
            this.assignments.splice(index, 1);

            if (this.assignments.length === 0) {
                this.addAssignment();
            }

            this.refreshIcons();
        },

        addDescription(assignmentIndex) {
            this.assignments[assignmentIndex].descriptions.push({
                key: window.crypto?.randomUUID ? window.crypto.randomUUID() : `${Date.now()}-${Math.random()}`,
                value: '',
            });
            this.refreshIcons();
        },

        removeDescription(assignmentIndex, descriptionIndex) {
            this.assignments[assignmentIndex].descriptions.splice(descriptionIndex, 1);

            if (this.assignments[assignmentIndex].descriptions.length === 0) {
                this.addDescription(assignmentIndex);
            }

            this.refreshIcons();
        },

        refreshIcons() {
            this.$nextTick(() => window.lucide?.createIcons());
        },
    });

    window.addEventListener('DOMContentLoaded', () => {
        const unitSelect = document.getElementById('unit_work');
        const sectionSelect = document.getElementById('seksi');
        const units = @json($unitsPayload);
        const selectedSection = @json($selectedSection);
        const workshopOrderSelect = document.getElementById('workshop_order_source');
        const workshopOrders = @json($workshopOrdersPayload);
        const jobNameInput = document.getElementById('job_name');
        const notificationInput = document.getElementById('notification_number');
        const usagePlanInput = document.getElementById('usage_plan_date');

        if (! unitSelect || ! sectionSelect) {
            return;
        }

        const renderSections = (unitName, preferredSection = '') => {
            const unit = units.find((row) => row.name === unitName);
            const sections = unit?.sections || [];
            sectionSelect.innerHTML = '<option value="">Pilih seksi</option>';

            sections.forEach((sectionName) => {
                const option = document.createElement('option');
                option.value = sectionName;
                option.textContent = sectionName;

                if (preferredSection && preferredSection === sectionName) {
                    option.selected = true;
                }

                sectionSelect.appendChild(option);
            });

            if (! sections.includes(preferredSection)) {
                sectionSelect.value = '';
            }
        };

        renderSections(unitSelect.value, selectedSection);

        unitSelect.addEventListener('change', () => {
            renderSections(unitSelect.value, '');
        });

        workshopOrderSelect?.addEventListener('change', () => {
            const selected = workshopOrders.find((order) => String(order.id) === workshopOrderSelect.value);

            if (! selected) {
                return;
            }

            if (jobNameInput) {
                jobNameInput.value = selected.nama_pekerjaan || '';
            }

            if (notificationInput) {
                notificationInput.value = selected.nomor_order || selected.notifikasi || '';
            }

            if (unitSelect) {
                const unitExists = units.some((unit) => unit.name === selected.unit_kerja);
                unitSelect.value = unitExists ? selected.unit_kerja : '';
            }

            renderSections(unitSelect.value, selected.seksi || '');

            if (selected.seksi && ! Array.from(sectionSelect.options).some((option) => option.value === selected.seksi)) {
                const option = document.createElement('option');
                option.value = selected.seksi;
                option.textContent = selected.seksi;
                option.selected = true;
                sectionSelect.appendChild(option);
            }

            if (usagePlanInput) {
                usagePlanInput.value = selected.target_selesai || '';
            }
        });
    });
</script>
