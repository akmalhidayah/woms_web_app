@php
    $editing = $form !== null;
    $initialItems = old('items', $editing ? $form->items->map(fn ($item) => [
        'id' => $item->id, 'item_key' => $item->item_key, 'group_key' => $item->group_key,
        'group_name' => $item->group_name ?? '', 'label' => $item->label,
    ])->values()->all() : [['id' => null, 'item_key' => null, 'group_key' => null, 'group_name' => '', 'label' => '']]);
@endphp
<x-layouts.admin :title="($editing ? 'Edit' : 'Tambah').' Master Peralatan — WOMS'">
    <div class="space-y-4" x-data="{
        items: @js($initialItems).map((item, index) => ({ ...item, client_key: item.id ? `existing-${item.id}` : `initial-${index}` })),
        pics: @js($picOptions),
        picId: @js((string) old('bengkel_pic_id', $form?->bengkel_pic_id ?? '')),
        selectedPic() { return this.pics.find(pic => String(pic.id) === String(this.picId)) || null; },
        picInitials() { return (this.selectedPic()?.name || 'PIC').split(/\s+/).filter(Boolean).slice(0, 2).map(word => word[0]).join('').toUpperCase(); },
        add() { this.items.push({ id: null, item_key: null, group_key: null, group_name: '', label: '', client_key: `new-${Date.now()}-${Math.random()}` }); },
        remove(index) { if (this.items.length > 1) this.items.splice(index, 1); },
        move(index, direction) { const target = index + direction; if (target < 0 || target >= this.items.length) return; [this.items[index], this.items[target]] = [this.items[target], this.items[index]]; }
    }">
        <section class="flex flex-col gap-4 rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div><a href="{{ route('admin.inspection-forms.index') }}" class="text-sm font-semibold text-blue-700">← Master Peralatan</a><h1 class="mt-2 text-lg font-bold text-slate-900">{{ $editing ? 'Edit '.$form->name : 'Tambah Peralatan' }}</h1></div>
            @if ($editing)<a href="{{ route('admin.inspection-forms.preview', $form) }}" class="rounded-xl border border-blue-200 bg-white px-3 py-2 text-center text-xs font-semibold text-blue-700">Preview</a>@endif
        </section>

        <x-inspections.sweet-alerts :success="session('success')" :error-messages="$errors->all()" />

        <form method="POST" action="{{ $editing ? route('admin.inspection-forms.update', $form) : route('admin.inspection-forms.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf @if ($editing) @method('PUT') @endif
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-900">Identitas Form</h2>
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <label class="text-sm font-semibold text-slate-700 lg:col-span-2">Nama peralatan *<input name="name" value="{{ old('name', $form?->name) }}" required maxlength="150" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 font-normal"></label>
                    <label class="text-sm font-semibold text-slate-700 lg:col-span-2">Deskripsi<textarea name="description" rows="3" maxlength="2000" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 font-normal">{{ old('description', $form?->description) }}</textarea></label>
                    <div><label class="text-sm font-semibold text-slate-700">Foto peralatan <span class="font-normal text-slate-500">(wajib saat form baru diaktifkan)</span><input name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"></label><p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WebP; maksimal 2 MB. Gambar disimpan privat dan dinormalisasi oleh server.</p>
                        @if ($editing && $form->image_path)<img src="{{ route('admin.inspection-forms.image', $form) }}" alt="Foto {{ $form->name }}" class="mt-3 aspect-[4/3] w-full max-w-xs rounded-xl object-cover ring-1 ring-slate-200">@elseif ($editing && $form->is_legacy)<p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">Form awal tetap aktif dengan placeholder sampai foto asli diunggah.</p>@endif
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">PIC peralatan
                            <select name="bengkel_pic_id" x-model="picId" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 font-normal">
                                <option value="">Belum ditentukan</option>
                                @foreach ($picOptions as $picOption)<option value="{{ $picOption['id'] }}">{{ $picOption['name'] }}</option>@endforeach
                            </select>
                        </label>
                        <p class="mt-1 text-xs text-slate-500">Nama dan foto mengikuti data Master Bengkel PIC.</p>
                        <div x-show="selectedPic()" x-cloak class="mt-3 flex max-w-xs items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <template x-if="selectedPic()?.avatar_url"><img :src="selectedPic().avatar_url" :alt="`Foto ${selectedPic().name}`" class="h-16 w-16 rounded-full object-cover ring-2 ring-white" :style="`object-position: ${selectedPic().avatar_position}`"></template>
                            <template x-if="selectedPic() && ! selectedPic().avatar_url"><span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 font-black text-blue-700" x-text="picInitials()"></span></template>
                            <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-wider text-blue-700">PIC Peralatan</p><p class="mt-1 truncate text-sm font-semibold text-slate-900" x-text="selectedPic()?.name"></p></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div><h2 class="font-bold text-slate-900">Item Pemeriksaan</h2><p class="mt-1 text-xs text-slate-500">Susunan item menjadi urutan checklist.</p></div>
                <div class="mt-4 space-y-3">
                    <template x-for="(item, index) in items" :key="item.client_key">
                        <div class="grid grid-cols-[2rem_minmax(0,1fr)] items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-[2rem_minmax(0,1fr)_auto]">
                            <div class="text-center text-sm font-bold text-slate-500" x-text="index + 1"></div>
                            <input type="hidden" :name="`items[${index}][id]`" x-model="item.id"><input type="hidden" :name="`items[${index}][item_key]`" x-model="item.item_key"><input type="hidden" :name="`items[${index}][group_key]`" x-model="item.group_key"><input type="hidden" :name="`items[${index}][group_name]`" x-model="item.group_name">
                            <label class="min-w-0"><span class="sr-only">Item pemeriksaan</span><input :name="`items[${index}][label]`" x-model="item.label" required maxlength="255" placeholder="Nama item pemeriksaan" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal"></label>
                            <div class="col-start-2 flex gap-1 sm:col-start-auto"><button type="button" @click="move(index, -1)" :disabled="index === 0" title="Naik" aria-label="Naikkan item" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white disabled:opacity-30"><i data-lucide="arrow-up" class="h-4 w-4"></i></button><button type="button" @click="move(index, 1)" :disabled="index === items.length - 1" title="Turun" aria-label="Turunkan item" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white disabled:opacity-30"><i data-lucide="arrow-down" class="h-4 w-4"></i></button><button type="button" @click="remove(index)" :disabled="items.length === 1" title="Hapus item" aria-label="Hapus item" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 disabled:opacity-30"><i data-lucide="trash-2" class="h-4 w-4"></i></button></div>
                        </div>
                    </template>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" @click="add()" class="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-3 py-2 text-sm font-semibold text-white"><i data-lucide="plus" class="h-4 w-4"></i> Tambah Item</button>
                </div>
            </section>

            <section class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:justify-end">
                <a href="{{ route('admin.inspection-forms.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700">Batal</a>
                @if (! $editing || $form->status !== \App\Models\EquipmentInspectionForm::STATUS_ACTIVE)<button type="submit" name="action" value="save_draft" class="rounded-xl border border-blue-300 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700">Simpan Draft</button>@endif
                <button type="submit" name="action" value="publish" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">{{ $editing && $form->status === \App\Models\EquipmentInspectionForm::STATUS_ACTIVE ? 'Simpan Perubahan' : 'Simpan & Aktifkan' }}</button>
            </section>
        </form>

        @if ($editing && $form->logs->isNotEmpty())
            <details class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><summary class="cursor-pointer font-bold">Riwayat Perubahan Master</summary><div class="mt-4 space-y-2">@foreach ($form->logs->take(20) as $log)<div class="rounded-lg border border-slate-200 p-3 text-xs"><p class="font-semibold">{{ str_replace('_', ' ', ucfirst($log->event)) }} · Versi {{ $log->form_version }}</p><p class="mt-1 text-slate-500">{{ $log->actor?->name ?? 'Sistem' }} · {{ $log->created_at?->format('d/m/Y H:i') }}</p></div>@endforeach</div></details>
        @endif
    </div>
</x-layouts.admin>
