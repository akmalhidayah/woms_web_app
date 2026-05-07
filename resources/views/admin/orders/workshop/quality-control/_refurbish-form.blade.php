@php
    $payload = $payload ?? [];
    $repairRows = old('repair_descriptions', $payload['repair_descriptions'] ?? []);
    $spareRows = old('spare_parts', $payload['spare_parts'] ?? []);
    $testRows = old('commissioning_tests', $payload['commissioning_tests'] ?? []);
    $notesBeforeRows = old('notes_before_rows', $payload['notes_before_rows'] ?? null);
    $notesAfterRows = old('notes_after_rows', $payload['notes_after_rows'] ?? null);

    if (! is_array($notesBeforeRows) || count($notesBeforeRows) === 0) {
        $legacyBefore = trim((string) ($payload['notes_before'] ?? ''));
        $notesBeforeRows = $legacyBefore !== ''
            ? collect(preg_split('/\r\n|\r|\n/', $legacyBefore))->map(fn ($note): array => ['note' => $note])->all()
            : [['note' => '']];
    }

    if (! is_array($notesAfterRows) || count($notesAfterRows) === 0) {
        $legacyAfter = trim((string) ($payload['notes_after'] ?? ''));
        $notesAfterRows = $legacyAfter !== ''
            ? collect(preg_split('/\r\n|\r|\n/', $legacyAfter))->map(fn ($note): array => ['note' => $note])->all()
            : [['note' => '']];
    }

    $unitWorkValue = $order->seksi ?: '';
    $sectionNumberPayload = (string) ($payload['section_number'] ?? '');
    $sectionNumberValue = $sectionNumberPayload === (string) $order->seksi ? '' : $sectionNumberPayload;
    $filesByCategory = $report->exists ? $report->files->groupBy('category') : collect();
@endphp

<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
    @endif

    @if (session('quality_control_approval_url'))
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            <div class="font-semibold">Token TTD QC dibuat untuk {{ session('quality_control_approval_name') ?: 'Manager Bengkel' }}</div>
            <a href="{{ session('quality_control_approval_url') }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">
                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                Buka Link TTD {{ session('quality_control_approval_role') ?: 'Manager Bengkel' }}
            </a>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <div class="font-semibold">Periksa kembali data QC.</div>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-[1.35rem] border border-emerald-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #ecfdf5 0%, #f8fffc 48%, #e8f5ff 100%);">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-emerald-600 shadow-sm ring-1 ring-emerald-200">
                    <i data-lucide="clipboard-list" class="h-5 w-5"></i>
                </span>
                <div>
                    <h1 class="text-[1.45rem] font-bold leading-tight text-slate-900">{{ $formTitle }}</h1>
                    <p class="mt-1 text-[12px] text-slate-500">{{ $order->nomor_order }} - {{ $order->nama_pekerjaan }}</p>
                </div>
            </div>
            @if ($report->exists)
                <a href="{{ route('admin.orders.workshop.quality-control.pdf', [$order, $report]) }}" target="_blank" class="inline-flex w-fit items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    <i data-lucide="file-text" class="h-4 w-4"></i>
                    PDF QC
                </a>
            @endif
        </div>
    </section>

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid gap-4 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Report No.</label>
                    <input type="text" name="report_no" value="{{ old('report_no', $report->report_no) }}" readonly class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm font-semibold text-slate-700 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Tanggal</label>
                    <input type="date" name="report_date" value="{{ old('report_date', optional($report->report_date)->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Status</label>
                    <select name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="draft" @selected(old('status', $report->status ?: 'draft') === 'draft')>Draft</option>
                        <option value="submitted" @selected(old('status', $report->status) === 'submitted')>Submitted</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">No Notifikasi</label>
                    <input type="text" name="notification_number" value="{{ old('notification_number', $payload['notification_number'] ?? ($order->notifikasi ?: $order->nomor_order)) }}" readonly class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm font-semibold text-slate-700 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Tanggal diterima</label>
                    <input type="date" name="received_date" value="{{ old('received_date', $payload['received_date'] ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Tanggal selesai</label>
                    <input type="date" name="finished_date" value="{{ old('finished_date', $payload['finished_date'] ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Working days</label>
                    <input type="text" name="working_days" value="{{ old('working_days', $payload['working_days'] ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Plant</label>
                    <input type="text" name="plant" value="{{ old('plant', $payload['plant'] ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Unit kerja</label>
                    <input type="text" name="unit_work" value="{{ old('unit_work', $unitWorkValue) }}" readonly class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm font-semibold text-slate-700 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">No section</label>
                    <input type="text" name="section_number" value="{{ old('section_number', $sectionNumberValue) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Jenis peralatan</label>
                    <input type="text" name="equipment_type" value="{{ old('equipment_type', $payload['equipment_type'] ?? $order->nama_pekerjaan) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Deskripsi Perbaikan</h2>
                    <button type="button" data-add-row="repair" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                        Tambah Baris
                    </button>
                </div>
                <div class="space-y-2" id="repairRows">
                    @foreach ($repairRows as $index => $row)
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">{{ $index + 1 }}</span>
                            <input name="repair_descriptions[{{ $index }}][description]" value="{{ $row['description'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <button type="button" data-remove-row class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600"><i data-lucide="x" class="h-3.5 w-3.5"></i></button>
                        </div>
                    @endforeach
                </div>
            </div>
            @include('admin.orders.workshop.quality-control._photo-upload', [
                'name' => 'refurbish_repair',
                'label' => 'Foto Perbaikan',
                'files' => $filesByCategory->get('refurbish_repair', collect()),
            ])
        </section>

        <section class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Spare Part</h2>
                    <button type="button" data-add-row="spare" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                        Tambah Baris
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th class="px-3 py-2 text-left">Spare part</th>
                                <th class="px-3 py-2 text-left">Tanggal diterima</th>
                                <th class="px-3 py-2 text-left">Install</th>
                                <th class="w-12"></th>
                            </tr>
                        </thead>
                        <tbody id="spareRows" class="divide-y divide-slate-100">
                            @foreach ($spareRows as $index => $row)
                                <tr>
                                    <td class="px-3 py-2"><input name="spare_parts[{{ $index }}][name]" value="{{ $row['name'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td>
                                    <td class="px-3 py-2"><input type="date" name="spare_parts[{{ $index }}][received_date]" value="{{ $row['received_date'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td>
                                    <td class="px-3 py-2"><input name="spare_parts[{{ $index }}][install]" value="{{ $row['install'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td>
                                    <td class="px-3 py-2"><button type="button" data-remove-row class="rounded-lg bg-rose-50 p-2 text-rose-600"><i data-lucide="x" class="h-3.5 w-3.5"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @include('admin.orders.workshop.quality-control._photo-upload', [
                'name' => 'refurbish_sparepart',
                'label' => 'Foto Sparepart',
                'files' => $filesByCategory->get('refurbish_sparepart', collect()),
            ])
        </section>

        <section class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Commissioning Test</h2>
                    <button type="button" data-add-row="test" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                        Tambah Baris
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th class="px-3 py-2 text-left">Item test</th>
                                <th class="px-3 py-2 text-left">Tanggal</th>
                                <th class="px-3 py-2 text-left">Kondisi</th>
                                <th class="w-12"></th>
                            </tr>
                        </thead>
                        <tbody id="testRows" class="divide-y divide-slate-100">
                            @foreach ($testRows as $index => $row)
                                <tr>
                                    <td class="px-3 py-2"><input name="commissioning_tests[{{ $index }}][item]" value="{{ $row['item'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td>
                                    <td class="px-3 py-2"><input type="date" name="commissioning_tests[{{ $index }}][date]" value="{{ $row['date'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td>
                                    <td class="px-3 py-2"><input name="commissioning_tests[{{ $index }}][condition]" value="{{ $row['condition'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td>
                                    <td class="px-3 py-2"><button type="button" data-remove-row class="rounded-lg bg-rose-50 p-2 text-rose-600"><i data-lucide="x" class="h-3.5 w-3.5"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @include('admin.orders.workshop.quality-control._photo-upload', [
                'name' => 'refurbish_commissioning',
                'label' => 'Foto Commissioning Test',
                'files' => $filesByCategory->get('refurbish_commissioning', collect()),
            ])
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <input type="hidden" name="user_notes" value="{{ old('user_notes', $payload['user_notes'] ?? '') }}">

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Catatan standar / sebelum</h2>
                    <button type="button" data-add-row="notesBefore" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                        Tambah Baris
                    </button>
                </div>
                <div class="space-y-2" id="notesBeforeRows">
                    @foreach ($notesBeforeRows as $index => $row)
                        <div class="flex items-start gap-2">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">{{ $index + 1 }}</span>
                            <textarea name="notes_before_rows[{{ $index }}][note]" rows="2" class="w-full resize-y rounded-xl border border-slate-300 px-3 py-2 text-sm">{{ $row['note'] ?? '' }}</textarea>
                            <button type="button" data-remove-row class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600"><i data-lucide="x" class="h-3.5 w-3.5"></i></button>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Setelah penyetelan</h2>
                    <button type="button" data-add-row="notesAfter" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                        Tambah Baris
                    </button>
                </div>
                <div class="space-y-2" id="notesAfterRows">
                    @foreach ($notesAfterRows as $index => $row)
                        <div class="flex items-start gap-2">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">{{ $index + 1 }}</span>
                            <textarea name="notes_after_rows[{{ $index }}][note]" rows="2" class="w-full resize-y rounded-xl border border-slate-300 px-3 py-2 text-sm">{{ $row['note'] ?? '' }}</textarea>
                            <button type="button" data-remove-row class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600"><i data-lucide="x" class="h-3.5 w-3.5"></i></button>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        @include('admin.orders.workshop.quality-control._signature-pad', [
            'payload' => $payload,
            'roleLabel' => 'Diperiksa oleh Supervisor Of Refurbish',
            'theme' => 'emerald',
        ])

        <div class="flex flex-col-reverse gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('admin.orders.workshop.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Kembali
            </a>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white">
                <i data-lucide="save" class="h-4 w-4"></i>
                {{ $submitLabel }}
            </button>
        </div>
    </form>

    @foreach ($filesByCategory as $categoryFiles)
        @foreach ($categoryFiles as $file)
            <form id="delete-qc-file-{{ $file->id }}" method="POST" action="{{ route('admin.quality-control.files.destroy', [$report, $file]) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endforeach
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const removeButton = '<button type="button" data-remove-row class="rounded-lg bg-rose-50 p-2 text-rose-600"><i data-lucide="x" class="h-3.5 w-3.5"></i></button>';
        const nextIndex = (el) => el.children.length;
        const templates = {
            repair: (index) => `<div class="flex items-center gap-2"><span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">${index + 1}</span><input name="repair_descriptions[${index}][description]" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"><button type="button" data-remove-row class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600"><i data-lucide="x" class="h-3.5 w-3.5"></i></button></div>`,
            spare: (index) => `<tr><td class="px-3 py-2"><input name="spare_parts[${index}][name]" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td><td class="px-3 py-2"><input type="date" name="spare_parts[${index}][received_date]" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td><td class="px-3 py-2"><input name="spare_parts[${index}][install]" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td><td class="px-3 py-2">${removeButton}</td></tr>`,
            test: (index) => `<tr><td class="px-3 py-2"><input name="commissioning_tests[${index}][item]" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td><td class="px-3 py-2"><input type="date" name="commissioning_tests[${index}][date]" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td><td class="px-3 py-2"><input name="commissioning_tests[${index}][condition]" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td><td class="px-3 py-2">${removeButton}</td></tr>`,
            notesBefore: (index) => `<div class="flex items-start gap-2"><span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">${index + 1}</span><textarea name="notes_before_rows[${index}][note]" rows="2" class="w-full resize-y rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea><button type="button" data-remove-row class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600"><i data-lucide="x" class="h-3.5 w-3.5"></i></button></div>`,
            notesAfter: (index) => `<div class="flex items-start gap-2"><span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">${index + 1}</span><textarea name="notes_after_rows[${index}][note]" rows="2" class="w-full resize-y rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea><button type="button" data-remove-row class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600"><i data-lucide="x" class="h-3.5 w-3.5"></i></button></div>`,
        };
        const targets = { repair: 'repairRows', spare: 'spareRows', test: 'testRows', notesBefore: 'notesBeforeRows', notesAfter: 'notesAfterRows' };

        document.querySelectorAll('[data-add-row]').forEach((button) => {
            button.addEventListener('click', () => {
                const key = button.dataset.addRow;
                const target = document.getElementById(targets[key]);
                target.insertAdjacentHTML('beforeend', templates[key](nextIndex(target)));
                window.lucide?.createIcons();
            });
        });

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-row]');
            if (!button) return;
            button.closest('tr, .flex')?.remove();
        });

        document.querySelectorAll('[data-qc-photo-input]').forEach((input) => {
            input.addEventListener('change', () => {
                const target = document.getElementById(input.dataset.previewTarget);
                if (!target) return;

                target.innerHTML = '';
                const files = Array.from(input.files || []);
                target.classList.toggle('hidden', files.length === 0);

                files.forEach((file) => {
                    const url = URL.createObjectURL(file);
                    const card = document.createElement('div');
                    card.className = 'overflow-hidden rounded-xl border border-emerald-100 bg-white shadow-sm';
                    card.innerHTML = `
                        <img src="${url}" alt="" class="h-32 w-full object-cover">
                        <div class="truncate px-3 py-2 text-[11px] font-medium text-slate-600">${file.name}</div>
                    `;
                    target.appendChild(card);
                });
            });
        });

        document.querySelectorAll('[data-qc-signature-pad]').forEach((pad) => {
            const canvas = pad.querySelector('[data-qc-signature-canvas]');
            const dataInput = pad.querySelector('[data-qc-signature-data]');
            const signerInput = pad.closest('section')?.querySelector('[data-qc-signature-signer]');
            const dateInput = pad.closest('section')?.querySelector('[data-qc-signature-date]');
            const clearButton = pad.querySelector('[data-qc-signature-clear]');
            if (!canvas || !dataInput) return;

            const context = canvas.getContext('2d');
            let drawing = false;

            const resizeCanvas = () => {
                const ratio = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                const existing = dataInput.value;
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                context.setTransform(ratio, 0, 0, ratio, 0, 0);
                context.lineWidth = 2;
                context.lineCap = 'round';
                context.strokeStyle = '#0f172a';

                if (existing) {
                    const image = new Image();
                    image.onload = () => {
                        context.clearRect(0, 0, rect.width, rect.height);
                        context.drawImage(image, 0, 0, rect.width, rect.height);
                    };
                    image.src = existing;
                }
            };

            const point = (event) => {
                const rect = canvas.getBoundingClientRect();
                const source = event.touches?.[0] || event;
                return { x: source.clientX - rect.left, y: source.clientY - rect.top };
            };

            const syncSignatureMeta = () => {
                if (signerInput) signerInput.value = pad.dataset.currentSigner || signerInput.value || '';
                if (dateInput) dateInput.value = pad.dataset.currentDate || dateInput.value || '';
            };

            const start = (event) => {
                event.preventDefault();
                drawing = true;
                syncSignatureMeta();
                const current = point(event);
                context.beginPath();
                context.moveTo(current.x, current.y);
            };

            const move = (event) => {
                if (!drawing) return;
                event.preventDefault();
                const current = point(event);
                context.lineTo(current.x, current.y);
                context.stroke();
                dataInput.value = canvas.toDataURL('image/png');
            };

            const stop = () => {
                if (!drawing) return;
                drawing = false;
                dataInput.value = canvas.toDataURL('image/png');
            };

            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);
            canvas.addEventListener('mousedown', start);
            canvas.addEventListener('mousemove', move);
            window.addEventListener('mouseup', stop);
            canvas.addEventListener('touchstart', start, { passive: false });
            canvas.addEventListener('touchmove', move, { passive: false });
            window.addEventListener('touchend', stop);
            clearButton?.addEventListener('click', () => {
                const rect = canvas.getBoundingClientRect();
                context.clearRect(0, 0, rect.width, rect.height);
                dataInput.value = '';
            });
        });
    });
</script>
