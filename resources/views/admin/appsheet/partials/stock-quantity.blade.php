<p class="text-lg font-bold text-slate-900">{{ \App\Support\AppSheet\StockData::displayQuantity($quantity) }}</p>
@if ($unit !== '')
    <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ $unit }}</p>
@endif
