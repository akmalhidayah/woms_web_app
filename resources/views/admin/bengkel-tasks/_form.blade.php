@php
    $task = $task ?? null;
    $indexQuery = request()->only(['q', 'regu', 'per_page', 'page']);
    $selectedPicIds = collect($selectedPicIds ?? old('pic_ids', []))
        ->map(fn ($value) => (int) $value)
        ->all();
    $selectedUnit = old('unit_work', $task?->unit_work);
    $selectedSection = old('seksi', $task?->seksi);
    $unitsPayload = $units->map(fn ($unit) => [
        'name' => $unit->name,
        'sections' => $unit->sections->pluck('name')->values()->all(),
    ])->values()->all();
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

            <div>
                <div class="mb-1.5 block text-[11px] font-semibold text-slate-700">Penanggung Jawab</div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    @if ($picOptions->isEmpty())
                        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                            Belum ada PIC. Tambahkan dulu dari menu master PIC bengkel.
                        </div>
                    @else
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($picOptions as $pic)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 transition hover:border-blue-300 hover:bg-blue-50/40">
                                    <input type="checkbox" name="pic_ids[]" value="{{ $pic->id }}" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(in_array($pic->id, $selectedPicIds, true))>
                                    @if ($pic->avatar_url)
                                        <img src="{{ $pic->avatar_url }}" alt="{{ $pic->name }}" class="h-10 w-10 rounded-full object-cover ring-1 ring-slate-200" style="object-position: {{ $pic->avatar_object_position }};">
                                    @else
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-[11px] font-bold text-slate-700">
                                            {{ collect(explode(' ', $pic->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?' }}
                                        </span>
                                    @endif
                                    <span class="min-w-0 text-sm font-medium text-slate-700">{{ $pic->name }}</span>
                                </label>
                            @endforeach
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
    window.addEventListener('DOMContentLoaded', () => {
        const unitSelect = document.getElementById('unit_work');
        const sectionSelect = document.getElementById('seksi');
        const units = @json($unitsPayload);
        const selectedSection = @json($selectedSection);

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
    });
</script>
