<x-layouts.admin title="Master Data Inventory">
    @php($tab = request('tab','categories'))
    <div class="admin-compact space-y-4">
        @include('admin.inventory.partials.header', ['icon'=>'database','title'=>'Master Data','description'=>'Kategori, subkategori, lokasi, dan jenis permintaan Inventory.'])
        @if(session('success'))<div class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
        <nav class="flex flex-wrap gap-2">@foreach(['categories'=>'Kategori','subcategories'=>'Subkategori','locations'=>'Lokasi','request-types'=>'Jenis Permintaan'] as $key=>$label)<a href="{{ route('admin.inventory.master-data.index',['tab'=>$key]) }}" class="rounded-lg px-3 py-2 text-sm font-semibold {{ $tab===$key?'bg-blue-600 text-white':'border bg-white' }}">{{ $label }}</a>@endforeach</nav>
        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-bold">Tambah {{ ['categories'=>'Kategori','subcategories'=>'Subkategori','locations'=>'Lokasi','request-types'=>'Jenis Permintaan'][$tab] ?? 'Data' }}</h2>
            <form method="POST" action="{{ route('admin.inventory.master-data.store',$tab) }}" class="mt-3 grid gap-3 md:grid-cols-4">@csrf
                @if($tab==='subcategories')<select name="inventory_category_id" required class="rounded-lg border-slate-300"><option value="">Pilih kategori</option>@foreach($categories as $row)<option value="{{ $row->id }}">{{ $row->name }}</option>@endforeach</select>@endif
                <input name="code" placeholder="Kode {{ in_array($tab,['categories','subcategories'])?'(opsional)':'' }}" @required(!in_array($tab,['categories','subcategories'])) class="rounded-lg border-slate-300">
                <input name="name" placeholder="Nama" required class="rounded-lg border-slate-300">
                <input name="description" placeholder="Deskripsi (opsional)" class="rounded-lg border-slate-300">
                <label class="flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked> Aktif</label>
                @if($tab==='request-types')<label class="flex items-center gap-2"><input type="hidden" name="requires_damaged_photo" value="0"><input type="checkbox" name="requires_damaged_photo" value="1"> Wajib foto rusak</label><label class="flex items-center gap-2"><input type="hidden" name="requires_new_item_photo" value="0"><input type="checkbox" name="requires_new_item_photo" value="1"> Wajib foto baru</label>@endif
                <button class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white">Tambah</button>
            </form>
        </section>
        @php($rows = match($tab){'subcategories'=>$subcategories,'locations'=>$locations,'request-types'=>$requestTypes,default=>$categories})
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full divide-y text-sm"><thead class="bg-slate-50"><tr><th class="px-3 py-3 text-left">Kode</th><th class="px-3 py-3 text-left">Nama</th><th class="px-3 py-3 text-left">Keterangan</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Aksi</th></tr></thead><tbody class="divide-y">
            @forelse($rows as $row)<tr>
                <td class="px-3 py-3 font-semibold">{{ $row->code ?: '-' }}</td><td class="px-3 py-3">@if($tab==='subcategories')<span class="text-xs text-slate-500">{{ $row->category?->name }}</span><br>@endif{{ $row->name }}</td>
                <td class="px-3 py-3">{{ $row->description ?: '-' }}@if(isset($row->items_count))<span class="block text-xs text-slate-500">{{ $row->items_count }} barang</span>@elseif(isset($row->transactions_count))<span class="block text-xs text-slate-500">{{ $row->transactions_count }} transaksi</span>@endif
                    <details class="mt-2"><summary class="cursor-pointer text-xs font-semibold text-blue-700">Edit data</summary><form method="POST" action="{{ route('admin.inventory.master-data.update',[$tab,$row->id]) }}" class="mt-2 grid gap-2">@csrf @method('PUT')
                        @if($tab==='subcategories')<select name="inventory_category_id" class="rounded border-slate-300 text-xs">@foreach($categories as $category)<option value="{{ $category->id }}" @selected($row->inventory_category_id===$category->id)>{{ $category->name }}</option>@endforeach</select>@endif
                        <input name="code" value="{{ $row->code }}" placeholder="Kode" class="rounded border-slate-300 text-xs"><input name="name" value="{{ $row->name }}" required class="rounded border-slate-300 text-xs"><input name="description" value="{{ $row->description }}" placeholder="Deskripsi" class="rounded border-slate-300 text-xs">
                        <input type="hidden" name="is_active" value="{{ $row->is_active ? 1 : 0 }}">@if($tab==='request-types')<input type="hidden" name="requires_damaged_photo" value="{{ $row->requires_damaged_photo ? 1 : 0 }}"><input type="hidden" name="requires_new_item_photo" value="{{ $row->requires_new_item_photo ? 1 : 0 }}">@endif
                        <button class="rounded bg-blue-600 px-2 py-1 text-xs font-semibold text-white">Simpan Edit</button></form></details>
                </td>
                <td class="px-3 py-3 text-center">{{ $row->is_active?'Aktif':'Nonaktif' }}</td><td class="px-3 py-3"><div class="flex justify-center gap-1">
                    <form method="POST" action="{{ route('admin.inventory.master-data.status',[$tab,$row->id]) }}">@csrf @method('PATCH')<button class="rounded bg-amber-50 px-2 py-1 font-semibold text-amber-700">{{ $row->is_active?'Nonaktifkan':'Aktifkan' }}</button></form>
                    <form method="POST" action="{{ route('admin.inventory.master-data.destroy',[$tab,$row->id]) }}">@csrf @method('DELETE')<button class="rounded bg-rose-50 px-2 py-1 font-semibold text-rose-700">Hapus</button></form>
                </div></td>
            </tr>@empty<tr><td colspan="5" class="p-8 text-center text-slate-500">Belum ada data.</td></tr>@endforelse
        </tbody></table></div></section>
        <p class="text-xs text-slate-500">Data yang masih digunakan tidak dapat dihapus; nonaktifkan bila tidak lagi dipakai.</p>
    </div>
</x-layouts.admin>
