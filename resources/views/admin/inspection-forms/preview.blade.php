<x-layouts.admin :title="'Preview '.$form['name'].' — WOMS'">
    <div class="space-y-4">
        <section class="flex flex-col gap-4 rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div><a href="{{ route('admin.inspection-forms.index') }}" class="text-sm font-semibold text-blue-700">← Master Peralatan</a><h1 class="mt-2 text-lg font-bold text-slate-900">Preview {{ $form['name'] }}</h1><p class="mt-1 text-sm text-slate-600">Tampilan struktur checklist yang digunakan untuk inspeksi baru.</p></div>
            <div class="flex gap-2"><span class="rounded-full bg-white px-3 py-2 text-xs font-semibold ring-1 ring-slate-200">Versi {{ $form['version'] }} · {{ ucfirst($formModel->status) }}</span><a href="{{ route('admin.inspection-forms.edit', $formModel) }}" class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Edit</a></div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-start gap-4 border-b border-slate-200 p-5">
                @if ($formModel->image_path)<img src="{{ route('admin.inspection-forms.image', $formModel) }}" alt="Foto {{ $form['name'] }}" class="aspect-[4/3] w-32 rounded-xl object-cover ring-1 ring-slate-200">@else<div class="flex aspect-[4/3] w-32 items-center justify-center rounded-xl bg-slate-100 text-center text-xs text-slate-500">Foto belum tersedia</div>@endif
                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Form Inspeksi Peralatan</p><h2 class="mt-1 text-xl font-bold text-slate-900">{{ $form['name'] }}</h2><p class="mt-2 text-sm text-slate-600">{{ $form['description'] ?: 'Tanpa deskripsi.' }}</p><p class="mt-2 text-xs font-semibold text-blue-700">{{ $form['item_count'] }} item pemeriksaan</p></div>
            </div>
            <div class="divide-y divide-slate-200">
                @php($number = 0)
                @foreach ($form['groups'] as $group)
                    @if ($group['name'])<h3 class="bg-slate-100 px-5 py-3 text-sm font-bold text-blue-900">{{ $group['name'] }}</h3>@endif
                    @foreach ($group['items'] as $item)
                        @php($number++)
                        <div class="grid gap-3 px-5 py-4 sm:grid-cols-[2rem_minmax(0,1fr)_12rem] sm:items-center"><span class="font-bold text-slate-500">{{ $number }}</span><span class="font-semibold text-slate-900">{{ $item['label'] }}</span><div class="grid grid-cols-3 gap-1 text-center text-xs font-semibold"><span class="rounded-lg border border-emerald-300 px-2 py-2 text-emerald-800">A</span><span class="rounded-lg border border-amber-300 px-2 py-2 text-amber-800">B</span><span class="rounded-lg border border-red-300 px-2 py-2 text-red-800">C</span></div></div>
                    @endforeach
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.admin>
