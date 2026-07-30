<x-layouts.admin title="Riwayat Transaksi Inventory">
    <div class="admin-compact space-y-4">
        @include('admin.inventory.partials.header', ['icon' => 'history', 'title' => 'Riwayat Transaksi', 'description' => 'History immutable seluruh pergerakan stok Inventory.'])
        <form method="GET" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-6">
            <input name="search" value="{{ request('search') }}" placeholder="Nomor transaksi / UID / barang" class="md:col-span-2 rounded-lg border-slate-300">
            <select name="type" class="rounded-lg border-slate-300"><option value="">Semua jenis</option>@foreach (['opening_balance','stock_in','stock_out','adjustment_in','adjustment_out'] as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ str($type)->replace('_', ' ')->title() }}</option>@endforeach</select>
            <select name="source" class="rounded-lg border-slate-300"><option value="">Semua sumber</option>@foreach (['flutter','woms_admin','import','seeder','system'] as $source)<option value="{{ $source }}" @selected(request('source') === $source)>{{ str($source)->replace('_', ' ')->title() }}</option>@endforeach</select>
            <input type="date" name="date" value="{{ request('date') }}" class="rounded-lg border-slate-300">
            <button class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white">Filter</button>
        </form>
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">@include('admin.inventory.partials.transaction-table')@if (method_exists($transactions, 'links'))<div class="border-t border-slate-200 px-4 py-3">{{ $transactions->links() }}</div>@endif</section>
    </div>
</x-layouts.admin>
