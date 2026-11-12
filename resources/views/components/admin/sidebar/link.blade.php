@props([
    'href',
    'icon',
    'label',
    'active' => false,
    'badge' => 0,
])

@php
    $badge = (int) $badge;
    $badgeLabel = $badge > 999 ? '999+' : (string) $badge;
@endphp

{{-- Item menu rail: tab aktif berlekuk (lihat .sidebar-rail .nav-active di layout admin). --}}
<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    :title="sidebarOpen ? '' : @js($label)"
    class="group relative flex items-center gap-3 px-2.5 py-2 transition {{ $active ? 'nav-active -mr-3 rounded-l-2xl bg-white text-blue-800' : 'rounded-xl text-white/85 hover:bg-white/10 hover:text-white' }}"
>
    <span class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition {{ $active ? 'bg-blue-700 text-white shadow-md' : 'bg-white/10 text-white group-hover:bg-white/20' }}">
        <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
        @if ($badge > 0)
            <span x-show="!sidebarOpen" x-cloak class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full bg-amber-400 ring-2 {{ $active ? 'ring-white' : 'ring-blue-900' }}"></span>
        @endif
    </span>
    <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="min-w-0 flex-1 truncate text-[13px] font-medium">{{ $label }}</span>
    @if ($badge > 0)
        <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="inline-flex min-w-5 shrink-0 items-center justify-center rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold leading-none text-slate-900">{{ $badgeLabel }}</span>
    @endif
</a>
