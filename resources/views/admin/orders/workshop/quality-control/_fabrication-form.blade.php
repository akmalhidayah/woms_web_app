@php
    $payload = $payload ?? [];
    $dimensionRows = old('dimension_checks', $payload['dimension_checks'] ?? []);
    $materialRows = old('materials', $payload['materials'] ?? []);
    $weldingRows = old('welding', $payload['welding'] ?? []);
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

    <section class="rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                    <i data-lucide="clipboard-check" class="h-5 w-5"></i>
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
                    <input type="date" name="report_date" value="{{ old('report_date', optional($report->report_date)->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Status</label>
                    <select name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        <option value="draft" @selected(old('status', $report->status ?: 'draft') === 'draft')>Draft</option>
                        <option value="submitted" @selected(old('status', $report->status) === 'submitted')>Submitted</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Unit</label>
                    <input type="text" value="{{ $order->seksi ?: '-' }}" readonly class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm text-slate-600">
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-base font-bold text-slate-900">Jenis Pengecekan / Dimensi Fabrikasi</h2>
                <button type="button" data-add-row="dimension" class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                    Tambah Baris
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="w-12 px-3 py-2 text-left">No</th>
                            <th class="px-3 py-2 text-left">Jenis ukuran Pekerjaan</th>
                            <th class="w-44 px-3 py-2 text-left">Status</th>
                            <th class="px-3 py-2 text-left">Keterangan</th>
                            <th class="w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="dimensionRows" class="divide-y divide-slate-100">
                        @foreach ($dimensionRows as $index => $row)
                            <tr>
                                <td class="px-3 py-2 font-semibold">{{ $index + 1 }}</td>
                                <td class="px-3 py-2"><input name="dimension_checks[{{ $index }}][item]" value="{{ $row['item'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td>
                                <td class="px-3 py-2">
                                    <select name="dimension_checks[{{ $index }}][status]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                                        <option value="sesuai" @selected(($row['status'] ?? 'sesuai') === 'sesuai')>Sesuai</option>
                                        <option value="tidak_sesuai" @selected(($row['status'] ?? '') === 'tidak_sesuai')>Tidak Sesuai</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2"><input name="dimension_checks[{{ $index }}][notes]" value="{{ $row['notes'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td>
                                <td class="px-3 py-2"><button type="button" data-remove-row class="rounded-lg bg-rose-50 p-2 text-rose-600"><i data-lucide="x" class="h-3.5 w-3.5"></i></button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-base font-bold text-slate-900">Jenis Material Yang Digunakan</h2>
                <button type="button" data-add-row="material" class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                    Tambah Baris
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="w-12 px-3 py-2 text-left">No</th>
                            <th class="px-3 py-2 text-left">Material Pekerjaan</th>
                            <th class="px-3 py-2 text-left">Jenis Material</th>
                            <th class="px-3 py-2 text-left">Keterangan</th>
                            <th class="w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="materialRows" class="divide-y divide-slate-100">
                        @foreach ($materialRows as $index => $row)
                            <tr>
                                <td class="px-3 py-2 font-semibold">{{ $index + 1 }}</td>
                                <td class="px-3 py-2"><input name="materials[{{ $index }}][material_work]" value="{{ $row['material_work'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td>
                                <td class="px-3 py-2"><input name="materials[{{ $index }}][material_type]" value="{{ $row['material_type'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td>
                                <td class="px-3 py-2"><input name="materials[{{ $index }}][notes]" value="{{ $row['notes'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td>
                                <td class="px-3 py-2"><button type="button" data-remove-row class="rounded-lg bg-rose-50 p-2 text-rose-600"><i data-lucide="x" class="h-3.5 w-3.5"></i></button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-base font-bold text-slate-900">Pengelasan</h2>
                <button type="button" data-add-row="welding" class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                    Tambah Baris
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="w-12 px-3 py-2 text-left">No</th>
                            <th class="px-3 py-2 text-left">Item Pengelasan</th>
                            <th class="px-3 py-2 text-left">Jenis Elektroda</th>
                            <th class="w-44 px-3 py-2 text-left">Kondisi</th>
                            <th class="px-3 py-2 text-left">Keterangan</th>
                            <th class="w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="weldingRows" class="divide-y divide-slate-100">
                        @foreach ($weldingRows as $index => $row)
                            <tr>
                                <td class="px-3 py-2 font-semibold">{{ $index + 1 }}</td>
                                <td class="px-3 py-2"><input name="welding[{{ $index }}][item]" value="{{ $row['item'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td>
                                <td class="px-3 py-2"><input name="welding[{{ $index }}][electrode]" value="{{ $row['electrode'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td>
                                <td class="px-3 py-2">
                                    <select name="welding[{{ $index }}][condition]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                                        <option value="baik" @selected(($row['condition'] ?? 'baik') === 'baik')>Baik</option>
                                        <option value="perlu_perbaikan" @selected(($row['condition'] ?? '') === 'perlu_perbaikan')>Perlu perbaikan</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2"><input name="welding[{{ $index }}][notes]" value="{{ $row['notes'] ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td>
                                <td class="px-3 py-2"><button type="button" data-remove-row class="rounded-lg bg-rose-50 p-2 text-rose-600"><i data-lucide="x" class="h-3.5 w-3.5"></i></button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Catatan</label>
            <textarea name="notes" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">{{ old('notes', $payload['notes'] ?? '') }}</textarea>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            @include('admin.orders.workshop.quality-control._photo-upload', [
                'name' => 'fabrication_before',
                'label' => 'Gambar teknik Pekerjaan Fabrikasi / Barang Sebelum Repair',
                'files' => $filesByCategory->get('fabrication_before', collect()),
            ])
            @include('admin.orders.workshop.quality-control._photo-upload', [
                'name' => 'fabrication_after',
                'label' => 'Bukti Pendukung Foto Setelah Fabrikasi / Repair dan QC',
                'files' => $filesByCategory->get('fabrication_after', collect()),
            ])
        </section>

        @include('admin.orders.workshop.quality-control._signature-pad', [
            'payload' => $payload,
            'roleLabel' => 'Inspector',
            'theme' => 'blue',
        ])

        <div class="flex flex-col-reverse gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('admin.orders.workshop.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Kembali
            </a>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">
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
        const nextIndex = (tbody) => tbody.querySelectorAll('tr').length;
        const renumber = (tbody) => {
            tbody.querySelectorAll('tr').forEach((row, index) => {
                row.querySelector('td').textContent = index + 1;
            });
        };
        const removeButton = '<button type="button" data-remove-row class="rounded-lg bg-rose-50 p-2 text-rose-600"><i data-lucide="x" class="h-3.5 w-3.5"></i></button>';
        const templates = {
            dimension: (index) => `<tr><td class="px-3 py-2 font-semibold">${index + 1}</td><td class="px-3 py-2"><input name="dimension_checks[${index}][item]" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td><td class="px-3 py-2"><select name="dimension_checks[${index}][status]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="sesuai">Sesuai</option><option value="tidak_sesuai">Tidak Sesuai</option></select></td><td class="px-3 py-2"><input name="dimension_checks[${index}][notes]" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td><td class="px-3 py-2">${removeButton}</td></tr>`,
            material: (index) => `<tr><td class="px-3 py-2 font-semibold">${index + 1}</td><td class="px-3 py-2"><input name="materials[${index}][material_work]" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td><td class="px-3 py-2"><input name="materials[${index}][material_type]" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td><td class="px-3 py-2"><input name="materials[${index}][notes]" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td><td class="px-3 py-2">${removeButton}</td></tr>`,
            welding: (index) => `<tr><td class="px-3 py-2 font-semibold">${index + 1}</td><td class="px-3 py-2"><input name="welding[${index}][item]" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td><td class="px-3 py-2"><input name="welding[${index}][electrode]" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td><td class="px-3 py-2"><select name="welding[${index}][condition]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="baik">Baik</option><option value="perlu_perbaikan">Perlu perbaikan</option></select></td><td class="px-3 py-2"><input name="welding[${index}][notes]" class="w-full rounded-lg border border-slate-300 px-3 py-2"></td><td class="px-3 py-2">${removeButton}</td></tr>`,
        };
        const targets = { dimension: 'dimensionRows', material: 'materialRows', welding: 'weldingRows' };

        document.querySelectorAll('[data-add-row]').forEach((button) => {
            button.addEventListener('click', () => {
                const key = button.dataset.addRow;
                const tbody = document.getElementById(targets[key]);
                tbody.insertAdjacentHTML('beforeend', templates[key](nextIndex(tbody)));
                window.lucide?.createIcons();
            });
        });

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-row]');
            if (!button) return;
            const tbody = button.closest('tbody');
            button.closest('tr')?.remove();
            renumber(tbody);
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
                    card.className = 'overflow-hidden rounded-xl border border-blue-100 bg-white shadow-sm';
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
            let hasStroke = false;

            const resizeCanvas = () => {
                const ratio = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                const existing = pad.dataset.existingSignature;
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

            const syncSignatureFile = async () => {
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
                if (!blob) return;

                const transfer = new DataTransfer();
                transfer.items.add(new File([blob], 'qc-maker-signature.png', { type: 'image/png' }));
                dataInput.files = transfer.files;
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
                hasStroke = true;
            };

            const stop = async () => {
                if (!drawing) return;
                drawing = false;
                if (hasStroke) {
                    await syncSignatureFile();
                }
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
                hasStroke = false;
                dataInput.value = '';
            });
        });
    });
</script>
