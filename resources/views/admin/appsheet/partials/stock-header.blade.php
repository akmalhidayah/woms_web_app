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
