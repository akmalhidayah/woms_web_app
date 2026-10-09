@props(['routeName', 'tab', 'counts', 'search' => ''])

<div class="space-y-3">
    <nav class="overflow-x-auto" aria-label="Status inspeksi">
        <div class="flex min-w-max items-center gap-2 pb-1">
            @foreach (\App\Support\Inspector\EquipmentInspectionIndexTabs::options() as $key => $label)
                @php($active = $tab === $key)
                <a href="{{ route($routeName, array_filter(['tab' => $key, 'search' => $search])) }}"
                    @if ($active) aria-current="page" @endif
                    class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-lg border px-3 text-[10px] font-semibold transition {{ $active ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                    <span class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[9px] {{ $active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $counts[$key] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
    </nav>
    <form method="GET" action="{{ route($routeName) }}">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <label for="inspection-search" class="sr-only">Cari nomor dokumen, peralatan, atau inspektor</label>
        <div class="relative">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-3 w-3 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
            <input id="inspection-search" name="search" type="search" value="{{ $search }}" placeholder="Cari nomor dokumen / peralatan / inspektor..."
                class="w-full rounded-lg border border-slate-300 px-8 py-1.5 text-[10px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none">
        </div>
    </form>
</div>
