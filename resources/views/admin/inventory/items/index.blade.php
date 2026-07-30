<x-layouts.admin title="Master Barang Inventory">
    <div class="admin-compact space-y-4">
        @include('admin.inventory.partials.header', ['icon' => 'package-search', 'title' => 'Master Barang', 'description' => 'Daftar consumable dan peralatan gudang.'])
        <div class="flex flex-wrap justify-end gap-2">
            <button type="button" disabled title="CRUD barang manual belum tersedia" class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-500"><i data-lucide="plus" class="h-4 w-4"></i> Tambah Barang</button>
            <a href="{{ route('admin.inventory.import-appsheet.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700"><i data-lucide="file-up" class="h-4 w-4"></i> Import AppSheet</a>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.inventory.items.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold {{ request('item_type') ? 'bg-white text-slate-600 ring-1 ring-slate-200' : 'bg-blue-600 text-white' }}">Semua</a>
            <a href="{{ route('admin.inventory.items.index', ['item_type' => 'consumable']) }}" class="rounded-lg px-3 py-2 text-sm font-semibold {{ request('item_type') === 'consumable' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}">Consumable</a>
            <a href="{{ route('admin.inventory.items.index', ['item_type' => 'equipment']) }}" class="rounded-lg px-3 py-2 text-sm font-semibold {{ request('item_type') === 'equipment' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}">Peralatan</a>
        </div>
        <form method="GET" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-6">
            <input name="search" value="{{ request('search') }}" placeholder="Cari UID atau nama barang" class="md:col-span-2 rounded-lg border-slate-300">
            <select name="item_type" class="rounded-lg border-slate-300"><option value="">Semua tipe</option><option value="consumable" @selected(request('item_type') === 'consumable')>Consumable</option><option value="equipment" @selected(request('item_type') === 'equipment')>Peralatan</option></select>
            <select name="category" class="rounded-lg border-slate-300"><option value="">Semua kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>
            <select name="location" class="rounded-lg border-slate-300"><option value="">Semua lokasi</option>@foreach ($locations as $location)<option value="{{ $location->id }}" @selected((string) request('location') === (string) $location->id)>{{ $location->code }} · {{ $location->name }}</option>@endforeach</select>
            <button class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white">Filter</button>
        </form>
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50 text-left uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3">Foto</th><th class="px-4 py-3">UID</th><th class="px-4 py-3">Nama Barang</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3">Kategori</th><th class="px-4 py-3">Lokasi</th><th class="px-4 py-3 text-right">Stok</th><th class="px-4 py-3">Satuan</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        @php
                            $stockStatus = ! $item->is_active ? 'Nonaktif' : ((float) $item->current_stock <= 0 ? 'Habis' : ((float) $item->current_stock <= (float) $item->minimum_stock ? 'Menipis' : 'Tersedia'));
                            $statusClass = match ($stockStatus) {
                                'Tersedia' => 'bg-emerald-100 text-emerald-700',
                                'Menipis' => 'bg-amber-100 text-amber-700',
                                'Habis' => 'bg-rose-100 text-rose-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <tr><td class="px-4 py-3"><span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100"><i data-lucide="image" class="h-4 w-4 text-slate-400"></i></span></td><td class="px-4 py-3 font-semibold">{{ $item->uid }}</td><td class="px-4 py-3 font-semibold">{{ $item->name }}</td><td class="px-4 py-3">{{ str($item->item_type)->title() }}</td><td class="px-4 py-3">{{ $item->category?->name ?? '-' }}<div class="text-slate-500">{{ $item->subcategory?->name }}</div></td><td class="px-4 py-3">{{ $item->location?->code ?? '-' }} · {{ $item->location?->name ?? '-' }}</td><td class="whitespace-nowrap px-4 py-3 text-right font-bold">{{ $item->current_stock }}</td><td class="px-4 py-3">{{ $item->unit }}</td><td class="px-4 py-3"><span class="rounded-full px-2 py-1 {{ $statusClass }}">{{ $stockStatus }}</span></td><td class="px-4 py-3">@if ($item->is_active)<a href="{{ route('admin.inventory.stock-in.create', ['item_id' => $item->id]) }}" class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg bg-blue-50 px-3 py-2 font-semibold text-blue-700"><i data-lucide="package-plus" class="h-4 w-4"></i> Tambah Stok</a>@else<span class="text-slate-400">-</span>@endif</td></tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-8 text-center text-slate-500">Belum ada master barang.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            @if (method_exists($items, 'links'))<div class="border-t border-slate-200 px-4 py-3">{{ $items->links() }}</div>@endif
        </section>
    </div>
</x-layouts.admin>
