<x-layouts.admin title="Dashboard Gudang">
    <div class="admin-compact space-y-4">
        @include('admin.inventory.partials.header', [
            'icon' => 'warehouse',
            'title' => 'Inventory & Stock Gudang',
            'description' => 'Kelola consumable, peralatan, stok masuk, dan riwayat transaksi.',
        ])

        <div class="flex justify-end">
            <a href="{{ route('admin.inventory.stock-in.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                <i data-lucide="package-plus" class="h-4 w-4"></i> Tambah Stok
            </a>
        </div>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Master Barang', $summary['items'], 'package'],
                ['Consumable', $summary['consumables'], 'boxes'],
                ['Peralatan', $summary['equipment'], 'wrench'],
                ['Stok Menipis', $summary['low_stock'], 'triangle-alert'],
                ['Stok Habis', $summary['out_of_stock'], 'circle-x'],
                ['Transaksi Hari Ini', $summary['transactions_today'], 'arrow-left-right'],
            ] as [$label, $value, $icon])
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <i data-lucide="{{ $icon }}" class="h-4 w-4 text-blue-500"></i>
                    </div>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($value) }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Master Barang', 'admin.inventory.items.index', 'package-search'],
                ['Stok Masuk', 'admin.inventory.stock-in.index', 'package-plus'],
                ['Koreksi Stok', 'admin.inventory.adjustments.index', 'scale'],
                ['Riwayat Transaksi', 'admin.inventory.transactions.index', 'history'],
            ] as [$label, $routeName, $icon])
                <a href="{{ route($routeName) }}" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 font-semibold text-slate-700 shadow-sm transition hover:border-blue-200 hover:text-blue-700">
                    <i data-lucide="{{ $icon }}" class="h-5 w-5"></i><span>{{ $label }}</span>
                </a>
            @endforeach
        </section>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3"><h2 class="font-bold text-slate-900">Barang Perlu Perhatian</h2></div>
                <div class="divide-y divide-slate-100">
                    @forelse ($lowStockItems as $item)
                        <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                            <div><p class="font-semibold">{{ $item->name }}</p><p class="text-xs text-slate-500">{{ $item->uid }} · {{ $item->location?->name ?? '-' }}</p></div>
                            <span class="whitespace-nowrap font-bold text-rose-600">{{ $item->current_stock }} {{ $item->unit }}</span>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-slate-500">Tidak ada barang yang perlu perhatian.</p>
                    @endforelse
                </div>
            </section>
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3"><h2 class="font-bold text-slate-900">Transaksi Terbaru</h2></div>
                <div class="divide-y divide-slate-100">
                    @forelse ($recentTransactions as $transaction)
                        <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                            <div><p class="font-semibold">{{ $transaction->item_name_snapshot }}</p><p class="text-xs text-slate-500">{{ $transaction->transaction_number }}</p></div>
                            <div class="text-right"><p class="font-bold">{{ $transaction->quantity }} {{ $transaction->unit_snapshot }}</p><p class="text-xs text-slate-500">{{ str($transaction->transaction_type)->replace('_', ' ')->title() }}</p></div>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-slate-500">Belum ada transaksi.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-layouts.admin>
