<x-layouts.admin title="Koreksi Stok Inventory">
    <div class="admin-compact space-y-4">
        @include('admin.inventory.partials.header', ['icon' => 'scale', 'title' => 'Koreksi Stok', 'description' => 'Adjustment in/out dengan alasan yang dapat diaudit.'])
        @if(session('success'))<div class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.inventory.adjustments.store') }}" x-data="{ itemId:'{{ old('inventory_item_id') }}', type:'{{ old('adjustment_type','adjustment_in') }}', quantity:'{{ old('quantity') }}', items:@js($items->keyBy('id')), get item(){return this.items[this.itemId]||null}, get preview(){const q=Number.parseInt(this.quantity,10);if(!this.item||!Number.isInteger(q)||q<1)return '-';const value=this.type==='adjustment_in'?Number(this.item.current_stock)+q:Number(this.item.current_stock)-q;return value.toLocaleString('id-ID')+' '+this.item.unit} }" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            @csrf
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <label class="text-sm font-semibold">Barang<select name="inventory_item_id" x-model="itemId" required class="mt-1 w-full rounded-lg border-slate-300"><option value="">Pilih barang</option>@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->uid }} · {{ $item->name }} ({{ number_format((int)$item->current_stock,0,',','.') }} {{ $item->unit }})</option>@endforeach</select></label>
                <label class="text-sm font-semibold">Jenis Koreksi<select name="adjustment_type" x-model="type" class="mt-1 w-full rounded-lg border-slate-300"><option value="adjustment_in">Adjustment In</option><option value="adjustment_out">Adjustment Out</option></select></label>
                <label class="text-sm font-semibold">Jumlah<input type="number" name="quantity" x-model="quantity" min="1" step="1" inputmode="numeric" required class="mt-1 w-full rounded-lg border-slate-300"></label>
                <label class="text-sm font-semibold">Nomor Referensi<input name="reference_number" maxlength="100" value="{{ old('reference_number') }}" class="mt-1 w-full rounded-lg border-slate-300"></label>
                <label class="text-sm font-semibold md:col-span-2 xl:col-span-3">Alasan<textarea name="reason" required maxlength="2000" rows="2" class="mt-1 w-full rounded-lg border-slate-300">{{ old('reason') }}</textarea></label>
                <div class="rounded-lg bg-slate-50 p-3 text-sm"><span class="text-slate-500">Preview stok:</span><strong class="ml-2" x-text="preview"></strong></div>
            </div>
            <div class="mt-3 flex justify-end"><button class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white">Simpan Koreksi</button></div>
        </form>
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"><div class="border-b px-4 py-3"><h2 class="font-bold">Koreksi Terbaru</h2></div>@include('admin.inventory.partials.transaction-table')</section>
    </div>
</x-layouts.admin>
