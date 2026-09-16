@once
    <style>
        .appsheet-stock-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 0.75rem;
        }

        .appsheet-stock-filters > .appsheet-stock-filter-field {
            flex: 1 1 10rem;
            min-width: 0;
        }

        .appsheet-stock-filters > .appsheet-stock-filter-search {
            flex: 2 1 16rem;
        }

        .appsheet-stock-filters :is(input, select) {
            width: 100%;
            min-width: 0;
            height: 2.5rem;
            box-sizing: border-box;
        }

        .appsheet-stock-filters > .appsheet-stock-filter-actions {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 0.5rem;
            height: 2.5rem;
        }

        @media (min-width: 1200px) {
            .appsheet-stock-filters {
                flex-wrap: nowrap;
            }

            .appsheet-stock-filters > .appsheet-stock-filter-field {
                flex-basis: 0;
            }
        }

        @media (max-width: 639px) {
            .appsheet-stock-filters > .appsheet-stock-filter-field,
            .appsheet-stock-filters > .appsheet-stock-filter-actions {
                flex-basis: 100%;
            }
        }
    </style>
@endonce

<section class="overflow-hidden rounded-[1.35rem] border border-blue-100 bg-gradient-to-r from-blue-50 via-white to-cyan-50 px-5 py-5 shadow-sm">
    <div class="flex flex-wrap items-center gap-4">
        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-200/70">
            <i data-lucide="package-open" class="h-5 w-5" aria-hidden="true"></i>
        </span>
        <h1 class="text-[1.3rem] font-bold leading-tight tracking-tight text-slate-900">STOCK</h1>
        @include('admin.appsheet.partials.google-connection', ['googleReturnTo' => 'stock'])
    </div>
</section>

<nav aria-label="Jenis persediaan" class="flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
    @foreach ([
        'admin.appsheet.stock-consumable.index' => 'Stock Consumable BMS',
        'admin.appsheet.stock-consumable-gudang.index' => 'Stock Consumable Gudang',
        'admin.appsheet.stock-material-bms.index' => 'Stock Material BMS',
        'admin.appsheet.stock-material-gudang.index' => 'Stock Material Gudang',
    ] as $stockRoute => $stockLabel)
        <a href="{{ route($stockRoute) }}"
           @if (request()->routeIs($stockRoute)) aria-current="page" @endif
           class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-xl px-4 py-3 text-xs font-semibold transition {{ request()->routeIs($stockRoute) ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700' }}">
            {{ $stockLabel }}
        </a>
    @endforeach
</nav>
