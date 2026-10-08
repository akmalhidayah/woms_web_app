<nav aria-label="Jenis persediaan" class="flex w-full gap-1.5 overflow-x-auto">
    @foreach ([
        'admin.appsheet.stock-consumable.index' => 'Stock Consumable BMS',
        'admin.appsheet.stock-consumable-gudang.index' => 'Stock Consumable Gudang',
        'admin.appsheet.stock-material-bms.index' => 'Stock Material BMS',
        'admin.appsheet.stock-material-gudang.index' => 'Stock Material Gudang',
    ] as $stockRoute => $stockLabel)
        <a href="{{ route($stockRoute) }}"
           @if (request()->routeIs($stockRoute)) aria-current="page" @endif
           class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-lg px-3 py-2 text-xs font-semibold transition {{ request()->routeIs($stockRoute) ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700' }}">
            {{ $stockLabel }}
        </a>
    @endforeach
</nav>
