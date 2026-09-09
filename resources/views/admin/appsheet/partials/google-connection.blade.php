<div class="ml-auto shrink-0">
    @if ($googleConnected)
        <span class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
            <i data-lucide="check-circle-2" class="h-4 w-4" aria-hidden="true"></i>
            Google Terhubung
        </span>
    @else
        <a href="{{ route('admin.appsheet.google.connect', ['return_to' => $googleReturnTo]) }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">
            <i data-lucide="link" class="h-4 w-4" aria-hidden="true"></i>
            Hubungkan Google
        </a>
    @endif
</div>
