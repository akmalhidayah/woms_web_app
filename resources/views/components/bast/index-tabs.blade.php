@props([
    'routeName',
    'activeTab',
    'tabOptions' => [],
    'tabCounts' => [],
    'search' => '',
    'theme' => 'blue',
])

<nav class="overflow-x-auto" aria-label="Status BAST">
    <div class="flex min-w-max items-center gap-2 pb-1">
        @foreach ($tabOptions as $tabKey => $tabLabel)
            @php
                $isActive = $activeTab === $tabKey;
                $query = ['tab' => $tabKey];

                if (trim((string) $search) !== '') {
                    $query['search'] = $search;
                }

                $activeClass = $theme === 'orange'
                    ? 'border-orange-600 bg-orange-600 text-white'
                    : 'border-blue-600 bg-blue-600 text-white';
            @endphp

            <a
                href="{{ route($routeName, $query) }}"
                @if ($isActive) aria-current="page" @endif
                class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-lg border px-3 text-[10px] font-semibold transition {{ $isActive ? $activeClass : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}"
            >
                <span>{{ $tabLabel }}</span>
                <span class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[9px] {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                    {{ $tabCounts[$tabKey] ?? 0 }}
                </span>
            </a>
        @endforeach
    </div>
</nav>
