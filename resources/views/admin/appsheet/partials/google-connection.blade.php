<div class="ml-auto flex shrink-0 flex-wrap items-center gap-2">
    @if ($googleConnected)
        <span class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
            <i data-lucide="check-circle-2" class="h-4 w-4" aria-hidden="true"></i>
            Google Terhubung
        </span>
        @if (auth()->user()?->isSuperAdmin())
            <a href="{{ route('admin.appsheet.google.connect', ['return_to' => $googleReturnTo]) }}"
               class="inline-flex items-center rounded-lg border border-blue-200 bg-white px-2 py-1 text-[11px] font-semibold text-blue-600 hover:bg-blue-50">
                Hubungkan Ulang
            </a>
        @endif
    @else
        <a href="{{ route('admin.appsheet.google.connect', ['return_to' => $googleReturnTo]) }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">
            <i data-lucide="link" class="h-4 w-4" aria-hidden="true"></i>
            Hubungkan Google
        </a>
    @endif
</div>
