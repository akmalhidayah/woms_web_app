<x-layouts.admin title="Master Data Inventory">
    <div class="admin-compact space-y-4">
        @include('admin.inventory.partials.header', ['icon' => 'database', 'title' => 'Master Data', 'description' => 'Referensi kategori, subkategori, lokasi, dan jenis permintaan.'])
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['Kategori', $categories->count()], ['Subkategori', $subcategoryCount], ['Lokasi', $locations->count()], ['Jenis Permintaan', $requestTypes->count()]] as [$label, $count])
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-bold">{{ $count }}</p></article>
            @endforeach
        </section>
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><h2 class="font-bold">Kategori</h2><div class="mt-3 divide-y">@forelse ($categories as $category)<div class="flex justify-between py-2 text-sm"><span>{{ $category->name }}</span><span class="text-slate-500">{{ $category->subcategories_count }} subkategori</span></div>@empty<p class="py-4 text-sm text-slate-500">Belum ada data.</p>@endforelse</div></section>
            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><h2 class="font-bold">Lokasi</h2><div class="mt-3 divide-y">@forelse ($locations as $location)<div class="py-2 text-sm"><span class="font-semibold">{{ $location->code }}</span> · {{ $location->name }}</div>@empty<p class="py-4 text-sm text-slate-500">Belum ada data.</p>@endforelse</div></section>
            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><h2 class="font-bold">Jenis Permintaan</h2><div class="mt-3 divide-y">@forelse ($requestTypes as $requestType)<div class="py-2 text-sm"><p class="font-semibold">{{ $requestType->name }}</p><p class="text-slate-500">{{ $requestType->code }}</p></div>@empty<p class="py-4 text-sm text-slate-500">Belum ada data.</p>@endforelse</div></section>
        </div>
    </div>
</x-layouts.admin>
