<x-layouts.inspector :title="$form['name'].' — Inspeksi Peralatan'">
    @php
        $ratings = [
            'A' => ['label' => 'Normal', 'classes' => 'peer-checked:border-emerald-700 peer-checked:bg-emerald-700 peer-checked:text-white'],
            'B' => ['label' => 'Kurang Normal', 'classes' => 'peer-checked:border-amber-600 peer-checked:bg-amber-600 peer-checked:text-white'],
            'C' => ['label' => 'Rusak', 'classes' => 'peer-checked:border-red-700 peer-checked:bg-red-700 peer-checked:text-white'],
        ];
        $initialAnswers = $values;
        if (! $readOnly) {
            foreach ($initialAnswers as $key => &$answer) {
                $oldAnswer = old('answers.'.$key, $answer);
                if (is_array($oldAnswer)) {
                    $answer = ['rating' => in_array($oldAnswer['rating'] ?? '', ['A', 'B', 'C'], true) ? $oldAnswer['rating'] : '',
                        'remark' => is_string($oldAnswer['remark'] ?? null) ? $oldAnswer['remark'] : ''];
                }
            }
            unset($answer);
        }
        $pageConfig = [
            'answers' => $initialAnswers, 'savedAnswers' => $values,
            'date' => $readOnly ? $inspectionDate : old('inspection_date', $inspectionDate),
            'savedDate' => $inspectionDate, 'today' => $today, 'persisted' => $inspection !== null,
            'readOnly' => (bool) $readOnly, 'conflict' => $errors->has('lock_version'),
            'recentSignatureDataUrl' => $recentSignatureDataUrl,
        ];
        $itemNumber = 0;
    @endphp
    @include('inspector.equipment-forms._script')
    <div x-data="equipmentInspectionPage(@js($pageConfig))" @beforeunload.window="if (dirty && !saving && !signing) { $event.preventDefault(); $event.returnValue = ''; }" class="mt-3 min-w-0 space-y-4 sm:mt-2 lg:mt-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('inspector.equipment-forms.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Daftar Form</a>
                <a href="{{ route('inspector.inspections.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Inspeksi Saya</a>
                @if ($inspection)
                    <a href="{{ route('inspector.inspections.pdf', $inspection) }}" target="_blank" rel="noopener" class="rounded-xl bg-[#7f1017] px-4 py-2.5 text-sm font-semibold text-white">Preview PDF</a>
                @endif
            </div>
            <span class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-900">{{ $inspection?->statusLabel() ?? 'Pemeriksaan Baru' }}</span>
        </div>

        @if (session('success'))
            <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <p class="font-bold">Periksa kembali isian berikut:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                @if ($errors->has('lock_version'))
                    <a href="{{ url()->current() }}" class="mt-3 inline-block font-bold underline">Muat ulang versi terbaru</a>
                @endif
                <p class="mt-2">Foto baru yang belum tersimpan perlu dipilih ulang.</p>
            </div>
        @endif
        @if ($readOnly)
            <p class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700">{{ $inspection->statusLabel() }} · Versi {{ $inspection->document_version }}. Laporan terkunci; status ini tidak menyatakan alat layak operasi.</p>
        @endif
        @if ($inspection?->revision_note && in_array($inspection->status, [\App\Models\EquipmentInspection::STATUS_REVISION, \App\Models\EquipmentInspection::STATUS_DRAFT], true))
            <section class="rounded-xl border border-amber-500 bg-white p-4 text-sm"><h2 class="font-bold">Catatan Revisi</h2><p class="mt-2 whitespace-pre-wrap">{{ $inspection->revision_note }}</p><p class="mt-2 text-xs">{{ $inspection->returned_by_name }} · {{ $inspection->returned_at?->format('d/m/Y H:i') }}</p>
                @if ($inspection->status === \App\Models\EquipmentInspection::STATUS_REVISION)
                    <form method="POST" action="{{ route('inspector.inspections.revise', $inspection) }}" class="mt-3">@csrf<input type="hidden" name="document_version" value="{{ $inspection->document_version }}"><button class="rounded-lg bg-amber-500 px-4 py-2 font-bold text-slate-950">Perbaiki Laporan</button></form>
                @endif
            </section>
        @endif
        @if ($inspection?->archive_error)<p class="rounded-xl border border-red-500 bg-white p-4 text-sm text-red-800">Approval selesai, tetapi arsip PDF belum tersedia. Hubungi Admin untuk pemulihan arsip.</p>@endif
        @if ($inspection && $inspection->document_version > 1)
            <details class="rounded-xl border border-slate-300 bg-white p-4 text-sm"><summary class="cursor-pointer font-semibold">Histori PDF versi terdahulu</summary><div class="mt-3 flex flex-wrap gap-3">@foreach ($inspection->signatures->where('role_key', 'inspector') as $oldSignature)@if ($oldSignature->document_version < $inspection->document_version)<a href="{{ route('inspector.inspections.pdf', [$inspection, 'version' => $oldSignature->document_version]) }}" target="_blank" rel="noopener" class="text-blue-700 underline">Versi {{ $oldSignature->document_version }}</a>@endif @endforeach</div></details>
        @endif
        <noscript><p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Aktifkan JavaScript untuk mengisi checklist dan menggambar tanda tangan.</p></noscript>

        <form id="equipment-inspection-form" method="POST" action="{{ $inspection ? route('inspector.inspections.update', $inspection) : route('inspector.inspections.store', $form['id']) }}" enctype="multipart/form-data" @submit="submitInspection($event)" class="space-y-4">
            @csrf
            @if ($inspection)
                @method('PUT')
                <input type="hidden" name="lock_version" value="{{ old('lock_version', $inspection->lock_version) }}">
            @endif
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="inspection-title">
                <div class="flex items-start gap-3 border-b border-slate-200 p-5 sm:px-6">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-700 ring-1 ring-red-100"><i data-lucide="{{ $form['icon'] }}" class="h-6 w-6" aria-hidden="true"></i></span>
                    <div class="min-w-0">
                        <h1 id="inspection-title" class="text-lg font-black tracking-tight text-slate-900 sm:text-xl">FORM INSPEKSI PERALATAN</h1>
                        <p class="mt-1 break-words text-base font-bold text-red-800 sm:text-lg">{{ $form['name'] }}</p>
                    </div>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-3">
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Document No</p><p class="mt-1 break-words text-sm font-bold">{{ $inspection?->document_no ?? 'Belum diterbitkan' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Process</p><p class="mt-1 text-sm font-semibold">Inspection &amp; List</p></div>
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Seksi / Unit</p><p class="mt-1 text-sm font-semibold">Bengkel Mesin / Bengkel</p></div>
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Nama Pelaksana</p><p class="mt-1 break-words text-sm font-semibold">{{ $inspection?->inspector_name ?? auth()->user()->name }}</p></div>
                    <div>
                        <label for="inspection-date" class="block text-xs font-semibold uppercase text-slate-500">Tanggal Pemeriksaan</label>
                        <input id="inspection-date" name="inspection_date" type="date" x-model="inspectionDate" max="{{ $today }}" required @disabled($readOnly) class="mt-1 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-base disabled:bg-slate-50 sm:text-sm">
                        <p class="mt-1 text-xs text-slate-500" x-text="formattedDate"></p>
                    </div>
                    @if ($inspection)
                        <div><p class="text-xs font-semibold uppercase text-slate-500">Draft dibuat / Versi dokumen</p><p class="mt-1 text-sm">{{ $inspection->created_at->format('d/m/Y H:i') }} / {{ $inspection->document_version }}</p></div>
                    @endif
                </div>
            </section>

            <section aria-label="Ringkasan pemeriksaan" class="flex flex-wrap gap-x-5 gap-y-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs font-semibold" aria-live="polite">
                <span>Total: <span x-text="summary.total"></span></span><span>Terisi: <span x-text="summary.filled"></span></span>
                <span class="text-emerald-800">A / Normal: <span x-text="summary.A"></span></span>
                <span class="text-amber-800">B / Kurang Normal: <span x-text="summary.B"></span></span>
                <span class="text-red-800">C / Rusak: <span x-text="summary.C"></span></span>
                <span>Belum diperiksa: <span x-text="summary.empty"></span></span>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="checklist-title">
                <div class="border-b border-slate-200 p-5 sm:px-6">
                    <h2 id="checklist-title" class="font-bold">Checklist Pemeriksaan</h2>
                </div>
                <div class="hidden grid-cols-[2.5rem_minmax(0,1fr)_12rem_minmax(0,1.2fr)] gap-4 border-b border-slate-200 bg-slate-100 px-4 py-3 text-xs font-bold text-slate-600 lg:grid" aria-hidden="true">
                    <span>NO</span><span>ACTIVITY / PEMERIKSAAN</span><span class="text-center">A / B / C</span><span>REMARK / FOTO</span>
                </div>
                <div class="space-y-3 p-3 lg:space-y-0 lg:p-0">
                    @foreach ($form['groups'] as $group)
                        @if ($group['name'])<h3 class="rounded-xl bg-slate-100 px-4 py-3 text-sm font-bold text-red-800 lg:rounded-none">{{ $group['name'] }}</h3>@endif
                        @foreach ($group['items'] as $item)
                            @php($itemNumber++)
                            @include('inspector.equipment-forms._item')
                        @endforeach
                    @endforeach
                </div>
            </section>

            @include('inspector.equipment-forms._signature')
            @unless ($readOnly)
                <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5" aria-labelledby="inspection-action-title">
                    <div>
                        <h2 id="inspection-action-title" class="text-sm font-bold">Action Form</h2>
                        <p class="mt-1 text-xs text-slate-600" x-text="complete ? 'Simpan sebagai draft atau tanda tangani lalu submit pemeriksaan.' : 'Draft dapat disimpan sekarang. Submit tersedia setelah checklist lengkap dan sudah ditandatangani.'"></p>
                    </div>
                    <div class="grid gap-2 sm:flex">
                        <button type="submit" :disabled="saving || signing || conflict" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40" x-text="saving ? 'Menyimpan...' : 'Simpan Draft'">Simpan Draft</button>
                        <button type="submit"
                            data-action="sign"
                            formaction="{{ $inspection ? route('inspector.inspections.update-and-sign', $inspection) : route('inspector.inspections.store-and-sign', $form['id']) }}"
                            :disabled="!canSign || !signatureData || !signatureConfirmed || signing"
                            class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-40"
                            x-text="signing ? 'Mengirim...' : 'Submit Pemeriksaan'">Submit Pemeriksaan</button>
                    </div>
                </section>
            @endunless
        </form>
    </div>
</x-layouts.inspector>
