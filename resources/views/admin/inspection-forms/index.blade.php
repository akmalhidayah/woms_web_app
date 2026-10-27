<x-layouts.admin title="Master Peralatan — WOMS">
    <div
        class="space-y-4"
        x-data="{ qrModal: @js((int) old('qr_form_id', 0)), copiedQr: null, imagePreview: null }"
        @keydown.escape.window="qrModal = null; imagePreview = null"
    >
        <section class="flex flex-col gap-4 rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-700 ring-1 ring-blue-200"><i data-lucide="list-checks" class="h-5 w-5"></i></span>
                <h1 class="text-lg font-bold text-slate-900">Master Peralatan</h1>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('admin.inspection-units.labels.active') }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700"><i data-lucide="printer" class="h-4 w-4"></i> Cetak Semua QR Aktif</a>
                <a href="{{ route('admin.inspection-forms.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white"><i data-lucide="plus" class="h-4 w-4"></i> Tambah Peralatan</a>
            </div>
        </section>

        <x-inspections.sweet-alerts :success="session('success')" :error-messages="$errors->all()" />

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('admin.inspection-forms.index') }}" class="flex flex-col gap-2 border-b border-slate-200 p-4 sm:flex-row">
                <label for="master-form-search" class="sr-only">Cari nama peralatan</label>
                <input id="master-form-search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama peralatan..." class="min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                <button class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">Cari</button>
                @if (filled($filters['search'] ?? null))<a href="{{ route('admin.inspection-forms.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold">Reset</a>@endif
            </form>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="bg-slate-100 text-xs uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Peralatan</th><th class="px-4 py-3">Item</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-center">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($forms as $form)
                            @php
                                $statusClasses = match ($form->status) { 'active' => 'bg-emerald-100 text-emerald-800', 'inactive' => 'bg-slate-200 text-slate-700', default => 'bg-amber-100 text-amber-800' };
                                $statusLabel = match ($form->status) { 'active' => 'Aktif', 'inactive' => 'Nonaktif', default => 'Draft' };
                                $qrState = $qrStates[$form->id] ?? ['status' => 'missing', 'unit' => null];
                                $assetCode = ($qrState['unit'] ?? null)?->asset_code;
                                $qrTitle = match ($qrState['status']) { 'ready' => 'Kelola QR Code', 'conflict' => 'Periksa konflik QR Code', default => 'Atur QR Code' };
                            @endphp
                            <tr>
                                <td class="px-4 py-3"><div class="flex items-center gap-3">
                                    @if ($form->image_path)
                                        <button
                                            type="button"
                                            @click="imagePreview = { src: @js(route('admin.inspection-forms.image', $form)), name: @js($form->name) }"
                                            title="Preview foto {{ $form->name }}"
                                            aria-label="Preview foto {{ $form->name }}"
                                            class="group relative h-12 w-16 shrink-0 overflow-hidden rounded-lg ring-1 ring-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                            <img src="{{ route('admin.inspection-forms.image', $form) }}" alt="Foto {{ $form->name }}" loading="lazy" class="h-full w-full object-cover transition duration-200 group-hover:scale-105">
                                            <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 text-white opacity-0 transition group-hover:bg-slate-950/40 group-hover:opacity-100 group-focus:bg-slate-950/40 group-focus:opacity-100" aria-hidden="true">
                                                <i data-lucide="maximize-2" class="h-4 w-4"></i>
                                            </span>
                                        </button>
                                    @else
                                        <div class="flex h-12 w-16 shrink-0 items-center justify-center rounded-lg bg-slate-100 px-1 text-center text-[10px] text-slate-500 ring-1 ring-slate-200">Foto belum tersedia</div>
                                    @endif
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-bold text-slate-900">{{ $form->name }}</p>
                                            @if (filled($assetCode))
                                                <span class="rounded-md bg-blue-50 px-2 py-0.5 text-xs font-bold text-blue-700 ring-1 ring-inset ring-blue-200">{{ $assetCode }}</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">{{ $form->slug }}</p>
                                    </div>
                                </div></td>
                                <td class="px-4 py-3">{{ $form->items_count }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ $statusLabel }}</span></td>
                                <td class="px-4 py-3"><div class="flex justify-center gap-1.5">
                                    <a href="{{ route('admin.inspection-forms.preview', $form) }}" title="Preview form" aria-label="Preview {{ $form->name }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700"><i data-lucide="eye" class="h-4 w-4"></i></a>
                                    <a href="{{ route('admin.inspection-forms.edit', $form) }}" title="Edit master peralatan" aria-label="Edit {{ $form->name }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                                    <button type="button" @click="qrModal = {{ $form->id }}; copiedQr = null" title="{{ $qrTitle }}" aria-label="{{ $qrTitle }} {{ $form->name }}" class="relative inline-flex h-8 w-8 items-center justify-center rounded-lg border {{ $qrState['status'] === 'conflict' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-violet-200 bg-violet-50 text-violet-700' }}">
                                        <i data-lucide="qr-code" class="h-4 w-4"></i>
                                        @if ($qrState['status'] === 'missing')<span class="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full border-2 border-white bg-amber-400" aria-hidden="true"></span>@endif
                                    </button>
                                    @if ($form->status === \App\Models\EquipmentInspectionForm::STATUS_ACTIVE)
                                        <form method="POST" action="{{ route('admin.inspection-forms.deactivate', $form) }}" data-swal-confirm data-swal-title="Nonaktifkan form?" data-swal-text="Form tidak tersedia untuk inspeksi baru. Dokumen lama tetap aman." data-swal-confirm-text="Ya, nonaktifkan">@csrf @method('PATCH')<button title="Nonaktifkan" aria-label="Nonaktifkan {{ $form->name }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700"><i data-lucide="pause" class="h-4 w-4"></i></button></form>
                                    @else
                                        <form method="POST" action="{{ route('admin.inspection-forms.activate', $form) }}">@csrf @method('PATCH')<button title="Aktifkan" aria-label="Aktifkan {{ $form->name }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700"><i data-lucide="play" class="h-4 w-4"></i></button></form>
                                    @endif
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-12 text-center text-slate-500">Belum ada master peralatan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $forms->links() }}</div>
        </section>

        @foreach ($forms as $form)
            @include('admin.inspection-forms.partials.qr-modal', [
                'form' => $form,
                'qrState' => $qrStates[$form->id] ?? ['status' => 'missing', 'unit' => null],
            ])
        @endforeach

        <div
            x-show="imagePreview"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[130] flex items-center justify-center bg-slate-950/70 px-4 py-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="equipment-image-preview-title"
            @click.self="imagePreview = null"
        >
            <div x-show="imagePreview" x-transition class="flex max-h-[88vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-blue-700">
                            <i data-lucide="image" class="h-4 w-4"></i>
                            Preview Foto Peralatan
                        </div>
                        <h2 id="equipment-image-preview-title" class="mt-1 truncate text-lg font-bold text-slate-900" x-text="imagePreview ? imagePreview.name : ''"></h2>
                    </div>
                    <button type="button" @click="imagePreview = null" aria-label="Tutup preview foto" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </header>

                <div class="flex min-h-0 flex-1 items-center justify-center bg-slate-100 p-4 sm:p-6">
                    <img
                        :src="imagePreview ? imagePreview.src : ''"
                        :alt="imagePreview ? 'Foto ' + imagePreview.name : 'Preview foto peralatan'"
                        class="max-h-[72vh] max-w-full rounded-xl bg-white object-contain shadow-sm"
                    >
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
