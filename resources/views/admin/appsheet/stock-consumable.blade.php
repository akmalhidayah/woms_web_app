<x-layouts.admin title="Stock Consumable">
    <div class="min-w-0 space-y-4">
        <section class="rounded-[1.35rem] border border-blue-100 bg-blue-50 px-5 py-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-4">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                    <i data-lucide="package" class="h-5 w-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h1 class="text-[1.3rem] font-bold leading-tight tracking-tight text-slate-900">Stock Consumable</h1>
                    <p class="mt-1 text-xs text-slate-500">Monitoring stok consumable dari AppSheet.</p>
                </div>
                @include('admin.appsheet.partials.google-connection', ['googleReturnTo' => 'stock'])
            </div>
        </section>

        @include('admin.appsheet.partials.google-messages')

        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="Filter Stock Consumable">
            <fieldset disabled class="grid gap-3 md:grid-cols-3">
                <legend class="sr-only">Filter Stock Consumable</legend>
                <div>
                    <label for="stock-consumable-search" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Search</label>
                    <input id="stock-consumable-search" type="search" placeholder="Cari consumable..." class="block w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                </div>
                <div>
                    <label for="stock-consumable-type" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Jenis Consumable</label>
                    <select id="stock-consumable-type" class="block w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                        <option>Semua jenis consumable</option>
                    </select>
                </div>
                <div>
                    <label for="stock-consumable-status" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Status Stok</label>
                    <select id="stock-consumable-status" class="block w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                        <option>Semua status stok</option>
                    </select>
                </div>
            </fieldset>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <caption class="sr-only">Stock Consumable</caption>
                    <thead class="border-b border-slate-200 bg-slate-50 text-[10px] uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">No Material</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Jenis Consumable</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Consumable</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Deskripsi</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-semibold">Qty Konsinyasi</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-semibold">Qty Non Konsinyasi</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Unit</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Updated By</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Updated Date</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-semibold">Minimum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="10" class="px-5 py-14 text-center">
                                <i data-lucide="package" class="mx-auto h-8 w-8 text-slate-300" aria-hidden="true"></i>
                                <p class="mt-3 text-xs text-slate-500">Data Stock Consumable belum tersedia.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
