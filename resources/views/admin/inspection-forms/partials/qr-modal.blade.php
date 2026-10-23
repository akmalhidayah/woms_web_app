@php
    $qrStatus = $qrState['status'];
    $qrUnit = $qrState['unit'] ?? null;
@endphp

<div
    x-show="qrModal === {{ $form->id }}"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-[120] overflow-y-auto bg-slate-950/60 px-4 py-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="qr-modal-title-{{ $form->id }}"
    @click.self="qrModal = null"
>
    <div x-show="qrModal === {{ $form->id }}" x-transition class="mx-auto flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-violet-700">
                    <i data-lucide="qr-code" class="h-4 w-4"></i>
                    Identitas Peralatan
                </div>
                <h2 id="qr-modal-title-{{ $form->id }}" class="mt-1 text-lg font-bold text-slate-900">Pengaturan QR Code Peralatan</h2>
            </div>
            <button type="button" @click="qrModal = null" aria-label="Tutup modal" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </header>

        <div class="max-h-[88vh] overflow-y-auto p-5 sm:p-6">
            <section class="flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                @if ($form->image_path)
                    <img src="{{ route('admin.inspection-forms.image', $form) }}" alt="Foto {{ $form->name }}" class="h-20 w-24 shrink-0 rounded-xl object-cover ring-1 ring-slate-200">
                @else
                    <div class="flex h-20 w-24 shrink-0 items-center justify-center rounded-xl bg-white px-2 text-center text-[11px] text-slate-500 ring-1 ring-slate-200">Foto belum tersedia</div>
                @endif
                <div class="min-w-0">
                    <p class="truncate text-base font-bold text-slate-900">{{ $form->name }}</p>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                        <span class="rounded-full bg-white px-2.5 py-1 ring-1 ring-slate-200">{{ $form->items_count }} item pemeriksaan</span>
                        @if ($qrStatus === 'ready')
                            <span class="rounded-full {{ $qrUnit->public_enabled ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }} px-2.5 py-1">QR publik {{ $qrUnit->public_enabled ? 'aktif' : 'nonaktif' }}</span>
                        @elseif ($qrStatus === 'conflict')
                            <span class="rounded-full bg-rose-100 px-2.5 py-1 text-rose-800">Konflik data unit</span>
                        @else
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-amber-800">QR belum diatur</span>
                        @endif
                    </div>
                </div>
            </section>

            @if ($qrStatus === 'conflict')
                <section class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4">
                    <div class="flex gap-3">
                        <i data-lucide="triangle-alert" class="mt-0.5 h-5 w-5 shrink-0 text-rose-700"></i>
                        <div>
                            <h3 class="font-bold text-rose-900">Ditemukan lebih dari satu data unit</h3>
                            <p class="mt-1 text-sm leading-6 text-rose-800">Pengelolaan QR sederhana dihentikan agar sistem tidak memilih unit yang salah. Data lama tidak dihapus atau digabungkan otomatis.</p>
                        </div>
                    </div>
                    <div class="mt-4 divide-y divide-rose-200 overflow-hidden rounded-lg border border-rose-200 bg-white">
                        @foreach ($qrState['units'] as $conflictingUnit)
                            <a href="{{ route('admin.inspection-units.show', $conflictingUnit) }}" class="flex items-center justify-between gap-3 px-4 py-3 text-sm hover:bg-rose-50">
                                <span><span class="font-bold text-slate-900">{{ $conflictingUnit->asset_code }}</span><span class="ml-2 text-slate-500">{{ ucfirst($conflictingUnit->lifecycle_status) }}</span></span>
                                <i data-lucide="arrow-up-right" class="h-4 w-4 text-rose-700"></i>
                            </a>
                        @endforeach
                    </div>
                </section>
                <div class="mt-5 flex justify-end"><button type="button" @click="qrModal = null" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Tutup</button></div>
            @elseif ($qrStatus === 'missing')
                <form method="POST" action="{{ route('admin.inspection-forms.qr.save', $form) }}" class="mt-5 space-y-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="qr_form_id" value="{{ $form->id }}">
                    <div>
                        <label for="asset-code-{{ $form->id }}" class="mb-2 block text-sm font-bold text-slate-800">Kode Alat <span class="text-rose-600">*</span></label>
                        <input id="asset-code-{{ $form->id }}" name="asset_code" value="{{ (int) old('qr_form_id') === (int) $form->id ? old('asset_code') : '' }}" required maxlength="100" autocomplete="off" placeholder="Contoh: RC 05" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm font-semibold text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="location-{{ $form->id }}" class="mb-2 block text-sm font-bold text-slate-800">Lokasi</label>
                        <input id="location-{{ $form->id }}" name="location" value="{{ (int) old('qr_form_id') === (int) $form->id ? old('location', \App\Services\Inspector\EquipmentInspectionUnitService::DEFAULT_LOCATION) : \App\Services\Inspector\EquipmentInspectionUnitService::DEFAULT_LOCATION }}" maxlength="255" autocomplete="off" placeholder="Workshop Machine" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm font-semibold text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <p class="mt-2 text-xs leading-5 text-slate-500">Lokasi akan ditampilkan pada halaman riwayat QR. QR permanen dibuat satu kali dan akses publik tetap nonaktif sampai Admin mengaktifkannya.</p>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
                        <button type="button" @click="qrModal = null" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800"><i data-lucide="qr-code" class="h-4 w-4"></i> Simpan &amp; Buat QR Code</button>
                    </div>
                </form>
            @else
                <div class="mt-5 grid gap-5 lg:grid-cols-3">
                    <section class="rounded-xl border border-slate-200 p-4 text-center lg:col-span-1">
                        @if ($qrState['qr_error'])
                            <div class="flex items-center justify-center rounded-xl bg-amber-50 p-6 text-sm leading-6 text-amber-800">{{ $qrState['qr_error'] }}</div>
                        @else
                            <img src="{{ route('admin.inspection-units.qr', $qrUnit) }}" alt="QR Code {{ $qrUnit->asset_code }}" class="mx-auto aspect-square w-full max-w-[260px]">
                            <p class="mt-3 break-all text-[11px] leading-5 text-slate-500">{{ $qrState['public_url'] }}</p>
                        @endif
                        <div class="mt-3 rounded-lg bg-slate-100 px-3 py-2 text-sm font-bold text-slate-800">{{ $qrUnit->asset_code }}</div>
                    </section>

                    <section class="space-y-4 lg:col-span-2">
                        @if ($qrUnit->lifecycle_status === \App\Models\EquipmentInspectionUnit::STATUS_RETIRED)
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Data ini berstatus retired sehingga tidak dapat dipilih untuk inspeksi baru atau dipublikasikan.</div>
                        @endif
                        <form method="POST" action="{{ route('admin.inspection-forms.qr.save', $form) }}" class="rounded-xl border border-slate-200 p-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="qr_form_id" value="{{ $form->id }}">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="asset-code-{{ $form->id }}" class="mb-2 block text-sm font-bold text-slate-800">Kode Alat <span class="text-rose-600">*</span></label>
                                    <input id="asset-code-{{ $form->id }}" name="asset_code" value="{{ (int) old('qr_form_id') === (int) $form->id ? old('asset_code', $qrUnit->asset_code) : $qrUnit->asset_code }}" required maxlength="100" autocomplete="off" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                </div>
                                <div>
                                    <label for="location-{{ $form->id }}" class="mb-2 block text-sm font-bold text-slate-800">Lokasi <span class="font-normal text-slate-500">(opsional)</span></label>
                                    <input id="location-{{ $form->id }}" name="location" value="{{ (int) old('qr_form_id') === (int) $form->id ? old('location', $qrUnit->location) : $qrUnit->location }}" maxlength="255" autocomplete="off" placeholder="Contoh: Workshop Mesin" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                </div>
                            </div>
                            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-xs text-slate-500">Perubahan kode alat atau lokasi tidak mengubah URL maupun QR permanen.</p>
                                <button type="submit" class="shrink-0 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Simpan Perubahan</button>
                            </div>
                        </form>

                        <div class="grid grid-cols-2 gap-2 text-sm">
                            @if (! $qrState['qr_error'])
                                <button type="button" @click="navigator.clipboard.writeText(@js($qrState['public_url'])).then(() => copiedQr = {{ $form->id }})" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-3 py-2.5 font-semibold text-slate-700"><i data-lucide="copy" class="h-4 w-4"></i><span x-text="copiedQr === {{ $form->id }} ? 'Link Tersalin' : 'Copy Link QR'"></span></button>
                                <a href="{{ route('admin.inspection-units.qr', [$qrUnit, 'download' => 1]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-3 py-2.5 font-semibold text-slate-700"><i data-lucide="download" class="h-4 w-4"></i> Download SVG</a>
                                <a href="{{ route('admin.inspection-units.label', $qrUnit) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-3 py-2.5 font-semibold text-slate-700"><i data-lucide="printer" class="h-4 w-4"></i> Cetak Label</a>
                                @if ($qrUnit->public_enabled)
                                    <a href="{{ $qrState['public_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-3 py-2.5 font-semibold text-slate-700"><i data-lucide="external-link" class="h-4 w-4"></i> Halaman Publik</a>
                                @endif
                            @endif
                            <a href="{{ route('admin.inspection-units.show', $qrUnit) }}" class="col-span-2 inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2.5 font-semibold text-blue-700"><i data-lucide="history" class="h-4 w-4"></i> Lihat Histori Inspeksi ({{ $qrUnit->approved_history_count ?? 0 }})</a>
                        </div>

                        @if ($qrUnit->lifecycle_status !== \App\Models\EquipmentInspectionUnit::STATUS_RETIRED)
                            <form method="POST" action="{{ route('admin.inspection-units.public-access', $qrUnit) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="enabled" value="{{ $qrUnit->public_enabled ? 0 : 1 }}">
                                <button type="submit" class="w-full rounded-xl px-4 py-2.5 text-sm font-bold {{ $qrUnit->public_enabled ? 'border border-amber-300 bg-amber-50 text-amber-800' : 'bg-emerald-700 text-white hover:bg-emerald-800' }}">{{ $qrUnit->public_enabled ? 'Nonaktifkan QR Publik' : 'Aktifkan QR Publik' }}</button>
                            </form>
                        @endif
                    </section>
                </div>

                @include('admin.inspection-forms.partials.cilt-document')

                <details class="mt-3 rounded-xl border border-slate-200">
                    <summary class="cursor-pointer list-none px-4 py-3 text-sm font-bold text-slate-700">Aksi lanjutan</summary>
                    <div class="border-t border-slate-200 p-4">
                        <form method="POST" action="{{ route('admin.inspection-units.rotate', $qrUnit) }}" data-swal-confirm data-swal-title="Rotasi QR permanen?" data-swal-text="URL dan label lama langsung tidak berlaku. Histori tetap utuh, tetapi label fisik wajib diganti." data-swal-confirm-text="Ya, rotasi QR">
                            @csrf
                            <button type="submit" class="rounded-xl border border-rose-300 px-4 py-2.5 text-sm font-bold text-rose-700">Rotasi Public Code</button>
                        </form>
                    </div>
                </details>

                <div class="mt-5 flex justify-end border-t border-slate-200 pt-4"><button type="button" @click="qrModal = null" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Tutup</button></div>
            @endif
        </div>
    </div>
</div>
