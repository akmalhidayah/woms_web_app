@props([
    'icon',
    'label',
    'active' => false,
    'badge' => 0,
    'open' => false,
    'items' => [],
    'menuId' => null,
])

@php
    $badge = (int) $badge;
    $badgeLabel = fn (int $count): string => $count > 999 ? '999+' : (string) $count;
@endphp

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }">
    <button
        type="button"
        @click="if (!sidebarOpen) { sidebarOpen = true; open = true } else { open = !open }"
        :aria-expanded="(open && sidebarOpen).toString()"
        @if ($menuId) aria-controls="{{ $menuId }}" @endif
        :title="sidebarOpen ? '' : @js($label)"
        class="group relative flex w-full items-center gap-3 rounded-xl px-2.5 py-2 text-left transition {{ $active ? 'bg-white/15 text-white' : 'text-white/85 hover:bg-white/10 hover:text-white' }}"
    >
        <span class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition {{ $active ? 'bg-white text-blue-700' : 'bg-white/10 text-white group-hover:bg-white/20' }}">
            <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
            @if ($badge > 0)
                <span x-show="!sidebarOpen" x-cloak class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full bg-amber-400 ring-2 ring-blue-900"></span>
            @endif
        </span>
        <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="min-w-0 flex-1 truncate text-[13px] font-medium">{{ $label }}</span>
        @if ($badge > 0)
            <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="inline-flex min-w-5 shrink-0 items-center justify-center rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold leading-none text-slate-900">{{ $badgeLabel($badge) }}</span>
        @endif
        <i
            data-lucide="chevron-down"
            class="h-4 w-4 shrink-0 text-white/70 transition"
            :class="open ? 'rotate-180' : ''"
            x-show="sidebarOpen"
            x-transition.opacity.duration.200ms
            aria-hidden="true"
        ></i>
    </button>

    <div
        @if ($menuId) id="{{ $menuId }}" @endif
        x-show="open && sidebarOpen"
        x-transition.opacity.duration.200ms
        x-cloak
        class="ml-7 mt-1 space-y-1 border-l border-white/15 pl-3"
    >
        @foreach ($items as $item)
            @php
                $childActive = (bool) ($item['active'] ?? false);
                $childBadge = (int) ($item['badge_count'] ?? 0);
            @endphp
            <a
                href="{{ $item['href'] }}"
                @if ($childActive) aria-current="page" @endif
                class="relative flex items-center gap-2 px-2.5 py-1.5 text-[12.5px] transition {{ $childActive ? 'nav-active -mr-3 rounded-l-xl bg-white font-semibold text-blue-700' : 'rounded-lg text-white/80 hover:bg-white/10 hover:text-white' }}"
            >
                <i data-lucide="{{ $item['icon'] ?? 'circle' }}" class="h-4 w-4 shrink-0"></i>
                <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                @if ($childBadge > 0)
                    <span class="ml-1 inline-flex min-w-5 shrink-0 items-center justify-center rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold leading-none text-slate-900">{{ $badgeLabel($childBadge) }}</span>
                @endif
            </a>
        @endforeach
    </div>
</div>
