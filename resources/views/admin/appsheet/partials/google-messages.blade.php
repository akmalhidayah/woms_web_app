@if (session('appsheet_google_success'))
    <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700">
        {{ session('appsheet_google_success') }}
    </div>
@endif
@if (session('appsheet_stock_success'))
    <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700">
        {{ session('appsheet_stock_success') }}
    </div>
@endif
@if (session('appsheet_stock_error'))
    <div role="alert" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700">
        {{ session('appsheet_stock_error') }}
    </div>
@endif
@if (session('appsheet_transaction_success'))
    <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700">
        {{ session('appsheet_transaction_success') }}
    </div>
@endif
@if (session('appsheet_transaction_error'))
    <div role="alert" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700">
        {{ session('appsheet_transaction_error') }}
    </div>
@endif
@if ($errors->any())
    <div role="alert" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700">
        {{ $errors->first() }}
    </div>
@endif
@if ($sheetError ?? null)
    <div role="alert" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
        {{ $sheetError }}
    </div>
@endif
@if ($googleError = session('appsheet_google_error') ?: $googleConnectionError)
    <div role="alert" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
        {{ $googleError }}
    </div>
@endif
