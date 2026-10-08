@props([
    'routeName',
    'activeTab',
    'tabOptions',
    'tabCounts',
    'search' => '',
])

<div class="space-y-3">
    <nav class="overflow-x-auto" aria-label="Status HPP">
        <div class="flex min-w-max gap-1.5 pb-1">
            @foreach ($tabOptions as $tabKey => $tabLabel)
                @php
                    $isActive = $activeTab === $tabKey;
                    $tabUrl = route($routeName, array_filter([
                        'tab' => $tabKey,
                        'search' => $search,
                    ], fn ($value): bool => $value !== null && $value !== ''));
                @endphp
                <a
                    href="{{ $tabUrl }}"
                    @if ($isActive) aria-current="page" @endif
                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-[10px] font-semibold transition {{ $isActive ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"
                >
                    <span>{{ $tabLabel }}</span>
                    <span class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[8px] {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                        {{ $tabCounts[$tabKey] ?? 0 }}
                    </span>
                </a>
            @endforeach
        </div>
    </nav>

    <form method="GET" action="{{ route($routeName) }}">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <label for="hpp-index-search-{{ str_replace('.', '-', $routeName) }}" class="sr-only">Pencarian</label>
        <div class="relative">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
            <input
                id="hpp-index-search-{{ str_replace('.', '-', $routeName) }}"
                name="search"
                type="search"
                value="{{ $search }}"
                placeholder="Cari nomor order / pekerjaan / area..."
                class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-[11px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none"
            >
        </div>
    </form>
</div>
