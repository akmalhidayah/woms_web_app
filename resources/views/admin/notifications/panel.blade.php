@php
    $notificationToneClasses = [
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-100',
    ];
    $actionWaitingClasses = [
        'normal' => 'text-slate-500',
        'warning' => 'text-amber-700',
        'danger' => 'font-semibold text-rose-700',
    ];
    $actionWaitingBadgeClasses = [
        'normal' => '',
        'warning' => 'rounded-full bg-amber-50 px-1.5 py-0.5 ring-1 ring-amber-100',
        'danger' => 'rounded-full bg-rose-50 px-1.5 py-0.5 ring-1 ring-rose-100',
    ];
@endphp

<section data-admin-action-section>
    <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-slate-50/95 px-3 py-2 backdrop-blur sm:px-4">
        <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-600">Perlu Tindakan</span>
        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700">{{ $adminPendingActionCount }}</span>
    </div>

    @if ($adminActions->isEmpty())
        <div class="px-4 py-6 text-center text-xs text-slate-500">Tidak ada pekerjaan yang perlu ditindaklanjuti.</div>
    @else
        @foreach ($adminActions as $action)
        @php($toneClass = $notificationToneClasses[$action['tone'] ?? 'blue'] ?? $notificationToneClasses['blue'])
        @php($waitingClass = $actionWaitingClasses[$action['overdue_level'] ?? 'normal'] ?? $actionWaitingClasses['normal'])
        @php($waitingBadgeClass = $actionWaitingBadgeClasses[$action['overdue_level'] ?? 'normal'] ?? '')
        <a href="{{ $action['url'] }}" class="group flex gap-2.5 border-b border-slate-100 px-3 py-2.5 text-left transition hover:bg-slate-50 sm:gap-3 sm:px-4 sm:py-3">
            <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl ring-1 sm:h-9 sm:w-9 {{ $toneClass }}">
                <i data-lucide="{{ $action['icon'] }}" class="h-4 w-4"></i>
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">{{ $action['type'] }}</span>
                <span class="mt-1 block text-[12px] font-semibold leading-4 text-slate-900 sm:text-sm sm:leading-5">{{ $action['message'] }}</span>
                <span class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[10px] sm:text-[11px]">
                    <span class="font-semibold text-blue-700">{{ $action['action_label'] }}</span>
                    @if ($action['meta'])
                        <span class="text-slate-300">/</span>
                        <span class="max-w-full truncate text-slate-500">{{ $action['meta'] }}</span>
                    @endif
                    <span class="text-slate-300">/</span>
                    <span class="{{ $waitingClass }} {{ $waitingBadgeClass }}">{{ $action['waiting_text'] }}</span>
                </span>
            </span>
            <span class="mt-1 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-400 transition group-hover:text-blue-600">
                <i data-lucide="arrow-right" class="h-4 w-4"></i>
            </span>
        </a>
        @endforeach
    @endif
</section>

<section>
    <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-slate-50/95 px-3 py-2 backdrop-blur sm:px-4">
        <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-600">Informasi Terbaru</span>
        <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-700">{{ $adminUnreadInformationCount }}</span>
    </div>

    @forelse ($adminInformationNotifications as $notification)
        @php($toneClass = $notificationToneClasses[$notification['tone'] ?? 'blue'] ?? $notificationToneClasses['blue'])
        <form method="POST" action="{{ route('admin.notifications.read') }}" class="border-b border-slate-100 last:border-b-0">
        @csrf
        <input type="hidden" name="notification_key" value="{{ $notification['key'] }}">
        <input type="hidden" name="redirect_url" value="{{ $notification['url'] }}">
        <button type="submit" class="group flex w-full gap-2.5 px-3 py-2.5 text-left transition hover:bg-slate-50 sm:gap-3 sm:px-4 sm:py-3">
            <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl ring-1 sm:h-9 sm:w-9 {{ $toneClass }}">
                <i data-lucide="{{ $notification['icon'] }}" class="h-4 w-4"></i>
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400 sm:text-xs">{{ $notification['type'] }}</span>
                <span class="mt-1 block text-[12px] font-semibold leading-4 text-slate-900 sm:text-sm sm:leading-5">{{ $notification['message'] }}</span>
                <span class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                    <span>{{ $notification['meta'] ?: '-' }}</span>
                    <span class="text-slate-300">/</span>
                    <span>{{ optional($notification['signed_at'])->diffForHumans() }}</span>
                </span>
            </span>
            <span class="mt-1 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-400 transition group-hover:text-slate-600">
                <i data-lucide="arrow-right" class="h-4 w-4"></i>
            </span>
        </button>
        </form>
    @empty
        <div class="px-4 py-6 text-center text-xs text-slate-500">Belum ada informasi terbaru.</div>
    @endforelse
</section>
