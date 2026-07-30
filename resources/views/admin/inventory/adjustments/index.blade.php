<x-layouts.admin title="Koreksi Stok Inventory">
    <div class="admin-compact space-y-4">
        @include('admin.inventory.partials.header', ['icon' => 'scale', 'title' => 'Koreksi Stok', 'description' => 'Persiapan adjustment in/out dengan alasan yang dapat diaudit.'])
        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-bold">Form Koreksi Stok</h2><p class="mt-1 text-sm text-slate-500">Aksi penyimpanan belum diaktifkan pada tahap UI ini.</p>
            <div class="mt-3 grid gap-3 md:grid-cols-4"><input disabled placeholder="Pilih barang" class="rounded-lg border-slate-300 bg-slate-50"><select disabled class="rounded-lg border-slate-300 bg-slate-50"><option>Adjustment In / Out</option></select><input disabled placeholder="Jumlah" class="rounded-lg border-slate-300 bg-slate-50"><input disabled placeholder="Alasan wajib" class="rounded-lg border-slate-300 bg-slate-50"></div>
        </section>
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"><div class="border-b px-4 py-3"><h2 class="font-bold">Koreksi Terbaru</h2></div>@include('admin.inventory.partials.transaction-table')</section>
    </div>
</x-layouts.admin>
