<x-layouts.admin title="Master Form Inspeksi — WOMS">
    <div class="space-y-4">
        <section class="flex flex-col gap-4 rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-700 ring-1 ring-blue-200"><i data-lucide="list-checks" class="h-5 w-5"></i></span>
                <div><h1 class="text-lg font-bold text-slate-900">Master Form Inspeksi</h1><p class="mt-1 text-sm text-slate-600">Kelola peralatan dan item checklist untuk inspeksi baru.</p></div>
            </div>
            <a href="{{ route('admin.inspection-forms.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white"><i data-lucide="plus" class="h-4 w-4"></i> Tambah Form</a>
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
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="bg-slate-100 text-xs uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Urutan</th><th class="px-4 py-3">Form / Peralatan</th><th class="px-4 py-3">Item</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-center">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($forms as $form)
                            @php
                                $statusClasses = match ($form->status) { 'active' => 'bg-emerald-100 text-emerald-800', 'inactive' => 'bg-slate-200 text-slate-700', default => 'bg-amber-100 text-amber-800' };
                                $statusLabel = match ($form->status) { 'active' => 'Aktif', 'inactive' => 'Nonaktif', default => 'Draft' };
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $form->sort_order }}</td>
                                <td class="px-4 py-3"><div class="flex items-center gap-3">
                                    @if ($form->image_path)<img src="{{ route('admin.inspection-forms.image', $form) }}" alt="Foto {{ $form->name }}" loading="lazy" class="h-12 w-16 rounded-lg object-cover ring-1 ring-slate-200">@else<div class="flex h-12 w-16 items-center justify-center rounded-lg bg-slate-100 px-1 text-center text-[10px] text-slate-500 ring-1 ring-slate-200">Foto belum tersedia</div>@endif
                                    <div><p class="font-bold text-slate-900">{{ $form->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $form->slug }}</p></div>
                                </div></td>
                                <td class="px-4 py-3">{{ $form->items_count }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ $statusLabel }}</span></td>
                                <td class="px-4 py-3"><div class="flex justify-center gap-1.5">
                                    <a href="{{ route('admin.inspection-forms.preview', $form) }}" title="Preview form" aria-label="Preview {{ $form->name }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700"><i data-lucide="eye" class="h-4 w-4"></i></a>
                                    <a href="{{ route('admin.inspection-forms.edit', $form) }}" title="Edit master form" aria-label="Edit {{ $form->name }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                                    @if ($form->status === \App\Models\EquipmentInspectionForm::STATUS_ACTIVE)
                                        <form method="POST" action="{{ route('admin.inspection-forms.deactivate', $form) }}" data-swal-confirm data-swal-title="Nonaktifkan form?" data-swal-text="Form tidak tersedia untuk inspeksi baru. Dokumen lama tetap aman." data-swal-confirm-text="Ya, nonaktifkan">@csrf @method('PATCH')<button title="Nonaktifkan" aria-label="Nonaktifkan {{ $form->name }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700"><i data-lucide="pause" class="h-4 w-4"></i></button></form>
                                    @else
                                        <form method="POST" action="{{ route('admin.inspection-forms.activate', $form) }}">@csrf @method('PATCH')<button title="Aktifkan" aria-label="Aktifkan {{ $form->name }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700"><i data-lucide="play" class="h-4 w-4"></i></button></form>
                                    @endif
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">Belum ada master form inspeksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $forms->links() }}</div>
        </section>
    </div>
</x-layouts.admin>
