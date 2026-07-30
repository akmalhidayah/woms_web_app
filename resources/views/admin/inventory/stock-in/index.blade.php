<x-layouts.admin title="Stok Masuk Inventory">
    <div class="admin-compact space-y-4">
        @include('admin.inventory.partials.header', ['icon' => 'package-plus', 'title' => 'Stok Masuk', 'description' => 'Persiapan pencatatan penerimaan stok oleh admin gudang.'])
        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-bold">Form Stok Masuk</h2><p class="mt-1 text-sm text-slate-500">Aksi penyimpanan belum diaktifkan pada tahap UI ini.</p>
            <div class="mt-3 grid gap-3 md:grid-cols-4"><input disabled placeholder="Pilih barang" class="rounded-lg border-slate-300 bg-slate-50"><input disabled placeholder="Jumlah" class="rounded-lg border-slate-300 bg-slate-50"><input disabled placeholder="Referensi" class="rounded-lg border-slate-300 bg-slate-50"><button disabled class="rounded-lg bg-slate-200 px-4 py-2 font-semibold text-slate-500">Simpan</button></div>
        </section>
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"><div class="border-b px-4 py-3"><h2 class="font-bold">Stok Masuk Terbaru</h2></div>@include('admin.inventory.partials.transaction-table')</section>
    </div>
</x-layouts.admin>
