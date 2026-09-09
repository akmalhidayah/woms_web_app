@if (session('appsheet_google_success'))
    <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700">
        {{ session('appsheet_google_success') }}
    </div>
@endif
@if ($googleError = session('appsheet_google_error') ?: $googleConnectionError)
    <div role="alert" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
        {{ $googleError }}
    </div>
@endif
