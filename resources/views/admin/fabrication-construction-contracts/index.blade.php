<x-layouts.admin title="Kontrak Jasa Fabrikasi Konstruksi">
    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-[13px] text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-[1.75rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4">
                    <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                        <i data-lucide="file-stack" class="h-6 w-6"></i>
                    </span>
                    <div>
                        <h1 class="text-[2rem] font-bold leading-none tracking-tight text-slate-900">Kontrak Jasa Fabrikasi Konstruksi</h1>
                        <p class="mt-2 max-w-3xl text-sm text-slate-500">
                            Master item harga per jenis dan sub jenis item. Data ini nantinya bisa dipakai langsung di form HPP tanpa input manual.
                        </p>
                    </div>
                </div>

                <a href="{{ route('admin.fabrication-construction-contracts.create') }}" class="inline-flex items-center gap-2 rounded-2xl bg-orange-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700">
                    <i data-lucide="plus-circle" class="h-4 w-4"></i>
                    Tambah Item
                </a>
            </div>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.fabrication-construction-contracts.index') }}" class="grid gap-3 md:grid-cols-[1.2fr_220px_auto_auto] md:items-end">
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pencarian</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari jenis, sub jenis, kategori, nama item, atau satuan..." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-orange-500 focus:outline-none">
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tahun</label>
                    <select name="tahun" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-orange-500 focus:outline-none">
                        <option value="">Semua tahun</option>
                        @foreach ($availableYears as $yearOption)
                            <option value="{{ $yearOption }}" @selected($tahun === (string) $yearOption)>{{ $yearOption }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Filter
                </button>

                <a href="{{ route('admin.fabrication-construction-contracts.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    Reset
                </a>
            </form>
        </section>

        <section class="space-y-6">
            @forelse ($groupedItems as $tahunKey => $jenisGroups)
                <article class="rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex rounded-full bg-orange-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-orange-700">
                                {{ $tahunKey }}
                            </span>
                            <h2 class="text-lg font-bold text-slate-900">Master Harga Kontrak {{ $tahunKey }}</h2>
                        </div>
                    </div>

                    <div class="space-y-5 px-5 py-5">
                        @foreach ($jenisGroups as $jenisItem => $subJenisGroups)
                            <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <div class="mb-4">
                                    <h3 class="text-base font-bold text-slate-900">{{ $jenisItem }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ collect($subJenisGroups)->flatten(2)->count() }} item harga</p>
                                </div>

                                <div class="space-y-4">
                                    @foreach ($subJenisGroups as $subJenisItem => $kategoriGroups)
                                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                                                <h4 class="text-sm font-semibold text-slate-900">{{ $subJenisItem !== '' ? $subJenisItem : 'Tanpa Sub Jenis' }}</h4>
                                            </div>

                                            <div class="space-y-4 p-4">
                                                @foreach ($kategoriGroups as $kategoriItem => $subItems)
                                                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                                                            <h5 class="text-sm font-semibold text-slate-900">{{ $kategoriItem !== '' ? $kategoriItem : 'Tanpa Kategori' }}</h5>
                                                        </div>

                                                        <div class="overflow-x-auto">
                                                            <table class="min-w-full text-sm">
                                                                <thead class="bg-white text-slate-500">
                                                                    <tr>
                                                                        <th class="px-4 py-3 text-left font-semibold">Nama Item</th>
                                                                        <th class="px-4 py-3 text-left font-semibold">Satuan</th>
                                                                        <th class="px-4 py-3 text-right font-semibold">Harga Satuan</th>
                                                                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-slate-100">
                                                                    @foreach ($subItems as $item)
                                                                        <tr class="hover:bg-slate-50/80">
                                                                            <td class="px-4 py-3 text-slate-800">{{ $item->nama_item }}</td>
                                                                            <td class="px-4 py-3 text-slate-600">{{ $item->satuan }}</td>
                                                                            <td class="px-4 py-3 text-right font-semibold text-slate-900">Rp {{ number_format((float) $item->harga_satuan, 2, ',', '.') }}</td>
                                                                            <td class="px-4 py-3">
                                                                                <div class="flex justify-end gap-2">
                                                                                    <a href="{{ route('admin.fabrication-construction-contracts.edit', $item) }}" class="inline-flex items-center rounded-xl bg-orange-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-orange-700">
                                                                                        Edit
                                                                                    </a>
                                                                                    <form method="POST" action="{{ route('admin.fabrication-construction-contracts.destroy', $item) }}" onsubmit="return confirm('Hapus item master ini?')">
                                                                                        @csrf
                                                                                        @method('DELETE')
                                                                                        <button type="submit" class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                                                                                            Hapus
                                                                                        </button>
                                                                                    </form>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                        <i data-lucide="file-stack" class="h-6 w-6"></i>
                    </div>
                    <h3 class="mt-5 text-xl font-semibold text-slate-900">Belum ada master item kontrak</h3>
                    <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500">
                        Tambahkan item harga satu per satu. Nanti item ini bisa langsung dipilih di form HPP tanpa perlu ketik manual lagi.
                    </p>
                    <a href="{{ route('admin.fabrication-construction-contracts.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-orange-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700">
                        <i data-lucide="plus-circle" class="h-4 w-4"></i>
                        Tambah Item
                    </a>
                </div>
            @endforelse

            @if ($items->hasPages())
                <div class="pt-2">
                    {{ $items->links() }}
                </div>
            @endif
        </section>
    </div>
</x-layouts.admin>
