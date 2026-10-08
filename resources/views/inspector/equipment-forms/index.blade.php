<x-layouts.inspector title="Pemeriksaan Peralatan — WOMS">
    <div x-data="{ search: '', names: @js(array_column($forms, 'name')), matches(name) { return name.toLocaleLowerCase('id-ID').includes(this.search.trim().toLocaleLowerCase('id-ID')); } }" class="mt-3 space-y-4 sm:mt-2 lg:mt-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3 sm:gap-4">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-700 ring-1 ring-red-100">
                        <i data-lucide="clipboard-check" class="h-6 w-6" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h1 class="text-xl font-black tracking-tight text-slate-900 sm:text-2xl">PEMERIKSAAN PERALATAN</h1>
                        <p class="mt-1 text-sm text-slate-500">Pilih form pemeriksaan peralatan workshop.</p>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">{{ count($forms) }} form peralatan</span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">{{ array_sum(array_column($forms, 'item_count')) }} item pemeriksaan</span>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-800">Tahap 1 · Simulasi UI</span>
                        </div>
                    </div>
                </div>
                <div class="w-full lg:w-80 lg:shrink-0">
                    <label for="equipment-search" class="mb-2 block text-xs font-semibold text-slate-700">Cari peralatan</label>
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                        <input id="equipment-search" type="search" x-model="search" placeholder="Nama mesin atau peralatan..." autocomplete="off" class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-red-700 focus:outline-none focus:ring-2 focus:ring-red-100">
                    </div>
                </div>
            </div>
        </section>

        <p class="flex items-start gap-2 text-xs leading-5 text-slate-600">
            <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0 text-red-700" aria-hidden="true"></i>
            Pilihan dan keterangan hanya untuk simulasi. Data tidak disimpan dan akan hilang saat halaman dimuat ulang atau ditinggalkan.
        </p>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" aria-label="Daftar form peralatan">
            @foreach ($forms as $form)
                <article x-show="matches($el.dataset.name)" data-name="{{ $form['name'] }}" class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-red-200">
                    <div class="flex items-center justify-between gap-3">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 text-red-700 ring-1 ring-red-100">
                            <i data-lucide="{{ $form['icon'] }}" class="h-5 w-5" aria-hidden="true"></i>
                        </span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $form['item_count'] }} item</span>
                    </div>
                    <h2 class="mt-4 text-base font-bold leading-6 text-slate-900">{{ $form['name'] }}</h2>
                    <p class="mb-5 mt-2 text-sm leading-6 text-slate-500">{{ $form['description'] }}</p>
                    <a href="{{ route('inspector.equipment-forms.show', ['equipmentForm' => $form['id']]) }}" aria-label="Pilih Form {{ $form['name'] }}" class="mt-auto inline-flex items-center justify-center gap-2 rounded-xl bg-[#7f1017] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                        Pilih Form
                        <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i>
                    </a>
                </article>
            @endforeach
        </section>
        <div x-show="!names.some(name => matches(name))" x-cloak role="status" class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <i data-lucide="search-x" class="mx-auto h-8 w-8 text-slate-400" aria-hidden="true"></i>
            <p class="mt-3 font-semibold text-slate-800">Peralatan tidak ditemukan</p>
            <p class="mt-1 text-sm text-slate-500">Coba nama peralatan lain atau kosongkan pencarian.</p>
            <button type="button" @click="search = ''; $nextTick(() => document.getElementById('equipment-search').focus())" class="mt-4 rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-red-800">Reset pencarian</button>
        </div>
    </div>
</x-layouts.inspector>
