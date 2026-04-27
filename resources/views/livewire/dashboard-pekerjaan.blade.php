@php
    $allTasks = collect($tasks ?? []);

    $fabrikasiTasks = $allTasks
        ->filter(fn ($row) => (($row['catatan'] ?? null) === 'Regu Fabrikasi') || empty($row['catatan']))
        ->values();

    $refurbishTasks = $allTasks
        ->filter(fn ($row) => ($row['catatan'] ?? null) === 'Regu Bengkel (Refurbish)')
        ->values();

    $fabrikasiChunks = $fabrikasiTasks->chunk(max(1, (int) $perPageFabrikasi));
    $refurbishChunks = $refurbishTasks->chunk(max(1, (int) $perPageRefurbish));

    $fabrikasiSlideCount = $fabrikasiChunks->count();
    $refurbishSlideCount = $refurbishChunks->count();

    $fabrikasiSlideIndex = $fabrikasiSlideCount > 0 ? ((int) $pageSlide % $fabrikasiSlideCount) : 0;
    $refurbishSlideIndex = $refurbishSlideCount > 0 ? ((int) $pageSlide % $refurbishSlideCount) : 0;

    $fabrikasiPage = $fabrikasiChunks->get($fabrikasiSlideIndex, collect())->values();
    $refurbishPage = $refurbishChunks->get($refurbishSlideIndex, collect())->values();

    $tickerMessage = trim((string) ($tickerText ?? ''));

    if ($tickerMessage === '') {
        $tickerMessage = 'Monitoring pekerjaan bengkel aktif | Regu Fabrikasi: '.$fabrikasiTasks->count().' item | Regu Bengkel (Refurbish): '.$refurbishTasks->count().' item';
    }

    $tickerDuration = max(5, min(60, (int) ($tickerSpeedSeconds ?? 18)));

    $initials = function (?string $name): string {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $parts = array_slice(array_values(array_filter($parts)), 0, 2);
        $result = '';

        foreach ($parts as $part) {
            $result .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $result !== '' ? $result : '?';
    };

    $avatarObjectPosition = function ($profile): string {
        $x = max(0, min(100, (int) (is_array($profile) ? ($profile['avatar_position_x'] ?? 50) : 50)));
        $y = max(0, min(100, (int) (is_array($profile) ? ($profile['avatar_position_y'] ?? 50) : 50)));

        return "{$x}% {$y}%";
    };

    $targetStatus = function (?string $date): array {
        if (! filled($date)) {
            return [
                'badge_text' => null,
                'badge_class' => 'border-slate-200 bg-slate-50 text-slate-500',
            ];
        }

        try {
            $today = now()->startOfDay();
            $targetDate = \Carbon\Carbon::parse($date)->startOfDay();
            $daysLeft = $today->diffInDays($targetDate, false);
        } catch (\Throwable $exception) {
            return [
                'badge_text' => null,
                'badge_class' => 'border-slate-200 bg-slate-50 text-slate-500',
            ];
        }

        if ($daysLeft < 0) {
            return [
                'badge_text' => 'Lewat '.abs($daysLeft).' hari',
                'badge_class' => 'border-red-200 bg-red-50 text-red-700',
            ];
        }

        if ($daysLeft === 0) {
            return [
                'badge_text' => 'Hari ini',
                'badge_class' => 'border-red-200 bg-red-50 text-red-700',
            ];
        }

        if ($daysLeft <= 3) {
            return [
                'badge_text' => $daysLeft.' hari lagi',
                'badge_class' => 'border-red-200 bg-red-50 text-red-700',
            ];
        }

        return [
            'badge_text' => $daysLeft.' hari lagi',
            'badge_class' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        ];
    };
@endphp

<div>
    @if (($mode ?? 'admin') === 'display')
        <div wire:poll.keep-alive.5s="tickDisplay" class="mx-auto flex h-screen max-w-[1920px] flex-col overflow-hidden px-4 py-4">
            <div class="mb-3 grid grid-cols-[auto_1fr_auto] items-center gap-4 rounded-[1.5rem] border border-slate-300 bg-white px-6 py-4 shadow-sm">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('assets/branding/logos/logo-sig.png') }}" alt="SIG" class="h-16 w-auto object-contain">
                    <img src="{{ asset('assets/branding/logos/logo-st2.png') }}" alt="ST" class="h-16 w-auto object-contain">
                </div>

                <div class="text-center">
                    <h1 class="mt-2 text-[2.2rem] font-black tracking-tight text-slate-900">Dashboard Pekerjaan Bengkel</h1>
                    <div id="dateDisplay" class="mt-2 text-[1rem] font-semibold text-slate-600"></div>
                </div>

                <div class="text-right">
                    <div class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Jam</div>
                    <div id="timeDisplay" class="mt-2 text-[2rem] font-black tracking-tight text-slate-900"></div>
                    <div class="mt-2 text-[11px] font-semibold text-slate-500">
                        Fabrikasi {{ $fabrikasiSlideCount > 0 ? ($fabrikasiSlideIndex + 1) : 0 }} / {{ $fabrikasiSlideCount }}
                        | Refurbish {{ $refurbishSlideCount > 0 ? ($refurbishSlideIndex + 1) : 0 }} / {{ $refurbishSlideCount }}
                    </div>
                </div>
            </div>

            <div class="ticker mb-3 rounded-2xl shadow-sm" style="--ticker-duration: {{ $tickerDuration }}s;">
                <div class="ticker-track">
                    <span class="ticker-item">{{ $tickerMessage }}</span>
                    <span class="ticker-item">{{ $tickerMessage }}</span>
                    <span class="ticker-item">{{ $tickerMessage }}</span>
                    <span class="ticker-item">{{ $tickerMessage }}</span>
                </div>
            </div>

            <div class="grid min-h-0 flex-1 gap-4 xl:grid-cols-[1.45fr_1fr]">
                <section class="flex min-h-0 flex-col rounded-[1.4rem] border border-blue-200 bg-blue-50 p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="text-[1.05rem] font-black text-blue-900">Regu Fabrikasi</div>
                        <span class="rounded-full bg-blue-100 px-3 py-1 text-[11px] font-semibold text-blue-700">{{ $fabrikasiTasks->count() }} item</span>
                    </div>

                    <div class="grid min-h-0 flex-1 content-start gap-3 xl:grid-cols-2">
                        @forelse ($fabrikasiPage as $task)
                            @php
                                $profiles = collect($task['person_in_charge_profiles'] ?? []);
                                $targetMeta = $targetStatus($task['usage_plan_date'] ?? null);
                            @endphp
                            <article wire:key="fabrikasi-display-{{ $task['id'] }}" class="flex h-fit min-h-[138px] flex-col rounded-[1.1rem] border border-blue-100 bg-white p-3 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 text-[1.1rem] font-black leading-[1.15] tracking-[-0.03em] text-slate-950 drop-shadow-[0_1px_0_rgba(255,255,255,0.7)]"
                                         style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        {{ $task['job_name'] ?? '-' }}
                                    </div>

                                    <span class="inline-flex shrink-0 items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-[10px] font-extrabold tracking-[0.08em] text-blue-700 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.65)]">
                                        {{ $task['notification_number'] ?: '-' }}
                                    </span>
                                </div>

                                <div class="mt-2.5 rounded-[1rem] border border-blue-100 bg-white px-3 py-2.5 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)]">
                                    <div class="space-y-1.5 text-[12px] leading-[1.2rem] text-slate-700">
                                        <div class="flex items-start gap-1.5">
                                            <span class="shrink-0 text-[12px] font-black text-blue-700">Seksi :</span>
                                            <span class="text-[12px] font-semibold"
                                                  style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                                {{ $task['seksi'] ?: '-' }}
                                            </span>
                                        </div>

                                        <div class="flex items-start gap-1.5 border-t border-blue-100 pt-1.5">
                                            <div class="flex min-w-0 items-start gap-1.5">
                                                <span class="shrink-0 text-[12px] font-black text-blue-700">Target :</span>
                                                <span class="text-[12px] font-black text-blue-900">{{ $task['usage_plan_date'] ?: '-' }}</span>
                                            </div>

                                            @if ($targetMeta['badge_text'])
                                                <span class="ml-auto inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[9px] font-black {{ $targetMeta['badge_class'] }}">
                                                    {{ $targetMeta['badge_text'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 border-t border-slate-100 pt-2">
                                    @if ($profiles->isNotEmpty())
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($profiles as $profile)
                                                @php
                                                    $name = is_array($profile) ? ($profile['name'] ?? '') : '';
                                                    $avatar = is_array($profile) ? ($profile['avatar_url'] ?? null) : null;
                                                @endphp
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2.5 py-1 ring-1 ring-slate-200">
                                                    @if ($avatar)
                                                        <img src="{{ $avatar }}" alt="" class="h-10 w-10 rounded-full object-cover" style="object-position: {{ $avatarObjectPosition($profile) }};" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                                        <span style="display:none" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                                    @else
                                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                                    @endif
                                                    <span class="text-[10px] font-semibold text-slate-700">{{ $name }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="rounded-xl bg-slate-50 px-3 py-2 text-[10px] font-medium text-slate-500">
                                            Belum dipilih di data pekerjaan ini.
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="flex min-h-[220px] items-center justify-center rounded-[1.35rem] border border-dashed border-blue-200 bg-white/80 px-4 text-center text-base text-slate-500 md:col-span-2">
                                Belum ada data regu fabrikasi.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="flex min-h-0 flex-col rounded-[1.4rem] border border-amber-200 bg-amber-50 p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="text-[1rem] font-black text-amber-900">Regu Bengkel (Refurbish)</div>
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-semibold text-amber-700">{{ $refurbishTasks->count() }} item</span>
                    </div>

                    <div class="grid min-h-0 flex-1 content-start gap-3 xl:grid-cols-2">
                        @forelse ($refurbishPage as $task)
                            @php
                                $profiles = collect($task['person_in_charge_profiles'] ?? []);
                                $targetMeta = $targetStatus($task['usage_plan_date'] ?? null);
                            @endphp
                            <article wire:key="refurbish-display-{{ $task['id'] }}" class="flex h-fit min-h-[124px] flex-col rounded-[1.1rem] border border-amber-100 bg-white p-2.5 shadow-sm">
                                <div class="flex items-start justify-between gap-2.5">
                                    <div class="min-w-0 text-[1rem] font-black leading-[1.15] tracking-[-0.03em] text-slate-950 drop-shadow-[0_1px_0_rgba(255,255,255,0.7)]"
                                         style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        {{ $task['job_name'] ?? '-' }}
                                    </div>

                                    <span class="inline-flex shrink-0 items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[9px] font-extrabold tracking-[0.08em] text-amber-700 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.65)]">
                                        {{ $task['notification_number'] ?: '-' }}
                                    </span>
                                </div>

                                <div class="mt-2.5 rounded-[0.95rem] border border-amber-100 bg-white px-2.5 py-2 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)]">
                                    <div class="space-y-1.5 text-[11px] leading-[1.15rem] text-slate-700">
                                        <div class="flex items-start gap-1.5">
                                            <span class="shrink-0 text-[11px] font-black text-amber-700">Seksi :</span>
                                            <span class="text-[11px] font-semibold"
                                                  style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                                {{ $task['seksi'] ?: '-' }}
                                            </span>
                                        </div>

                                        <div class="flex items-start gap-1.5 border-t border-amber-100 pt-1.5">
                                            <div class="flex min-w-0 items-start gap-1.5">
                                                <span class="shrink-0 text-[11px] font-black text-amber-700">Target :</span>
                                                <span class="text-[11px] font-black text-amber-900">{{ $task['usage_plan_date'] ?: '-' }}</span>
                                            </div>

                                            @if ($targetMeta['badge_text'])
                                                <span class="ml-auto inline-flex shrink-0 items-center rounded-full border px-1.5 py-0.5 text-[8px] font-black {{ $targetMeta['badge_class'] }}">
                                                    {{ $targetMeta['badge_text'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2.5 border-t border-slate-100 pt-1.5">
                                    @if ($profiles->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($profiles as $profile)
                                                @php
                                                    $name = is_array($profile) ? ($profile['name'] ?? '') : '';
                                                    $avatar = is_array($profile) ? ($profile['avatar_url'] ?? null) : null;
                                                @endphp
                                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-50 px-1.5 py-0.5 ring-1 ring-slate-200 whitespace-nowrap">
                                                    @if ($avatar)
                                                        <img src="{{ $avatar }}" alt="" class="h-8 w-8 rounded-full object-cover" style="object-position: {{ $avatarObjectPosition($profile) }};" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                                        <span style="display:none" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-[9px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                                    @else
                                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-[9px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                                    @endif
                                                    <span class="text-[9px] font-semibold leading-none text-slate-700">{{ $name }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="rounded-xl bg-slate-50 px-2.5 py-1.5 text-[9px] font-medium text-slate-500">
                                            Belum dipilih di data pekerjaan ini.
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="flex min-h-[220px] items-center justify-center rounded-[1.35rem] border border-dashed border-amber-200 bg-white/80 px-4 text-center text-base text-slate-500">
                                Belum ada data regu refurbish.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    @else
        <div wire:poll.keep-alive.5s="refreshBoard" class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-[1.1rem] font-bold text-slate-900">Preview Display Bengkel</h2>
                    <p class="text-[11px] text-slate-500">
                        Fabrikasi {{ $fabrikasiSlideCount > 0 ? ($fabrikasiSlideIndex + 1) : 0 }} / {{ $fabrikasiSlideCount }}
                        | Refurbish {{ $refurbishSlideCount > 0 ? ($refurbishSlideIndex + 1) : 0 }} / {{ $refurbishSlideCount }}
                    </p>
                </div>

                <button type="button" wire:click="nextSlide" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                    <i data-lucide="chevrons-right" class="h-4 w-4"></i>
                    Geser
                </button>
            </div>

            <div class="grid gap-4 xl:grid-cols-[1.4fr_1fr]">
                <section class="rounded-[1.25rem] border border-blue-200 bg-blue-50 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="text-sm font-bold text-blue-900">Regu Fabrikasi</div>
                        <span class="rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-semibold text-blue-700">{{ $fabrikasiTasks->count() }} item</span>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        @forelse ($fabrikasiPage as $task)
                            @php
                                $profiles = collect($task['person_in_charge_profiles'] ?? []);
                                $targetMeta = $targetStatus($task['usage_plan_date'] ?? null);
                            @endphp
                            <article wire:key="fabrikasi-admin-{{ $task['id'] }}" class="rounded-[1.1rem] border border-blue-100 bg-white p-3 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 text-[15px] font-black leading-[1.15] tracking-[-0.03em] text-slate-950"
                                         style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        {{ $task['job_name'] ?? '-' }}
                                    </div>

                                    <span class="inline-flex shrink-0 items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-[10px] font-extrabold tracking-[0.08em] text-blue-700 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.65)]">
                                        {{ $task['notification_number'] ?: '-' }}
                                    </span>
                                </div>

                                <div class="mt-3 rounded-[1rem] border border-blue-100 bg-white px-3 py-2.5 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)]">
                                    <div class="space-y-1.5 text-[12px] leading-[1.2rem] text-slate-700">
                                        <div class="flex items-start gap-1.5">
                                            <span class="shrink-0 text-[12px] font-black text-blue-700">Seksi :</span>
                                            <span class="text-[12px] font-semibold">{{ $task['seksi'] ?: '-' }}</span>
                                        </div>

                                        <div class="flex items-start gap-1.5 border-t border-blue-100 pt-1.5">
                                            <div class="flex min-w-0 items-start gap-1.5">
                                                <span class="shrink-0 text-[12px] font-black text-blue-700">Target :</span>
                                                <span class="text-[12px] font-black text-blue-900">{{ $task['usage_plan_date'] ?: '-' }}</span>
                                            </div>

                                            @if ($targetMeta['badge_text'])
                                                <span class="ml-auto inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[9px] font-black {{ $targetMeta['badge_class'] }}">
                                                    {{ $targetMeta['badge_text'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($profiles as $profile)
                                        @php
                                            $name = is_array($profile) ? ($profile['name'] ?? '') : '';
                                            $avatar = is_array($profile) ? ($profile['avatar_url'] ?? null) : null;
                                        @endphp
                                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-2 py-1 ring-1 ring-slate-200">
                                            @if ($avatar)
                                                <img src="{{ $avatar }}" alt="" class="h-10 w-10 rounded-full object-cover" style="object-position: {{ $avatarObjectPosition($profile) }};" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                                <span style="display:none" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                            @else
                                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                            @endif
                                            <span class="text-[11px] font-medium text-slate-700">{{ $name }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1.1rem] border border-dashed border-blue-200 bg-white/80 px-4 py-10 text-center text-sm text-slate-500 md:col-span-2">
                                Belum ada data regu fabrikasi.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-[1.25rem] border border-amber-200 bg-amber-50 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="text-sm font-bold text-amber-900">Regu Bengkel (Refurbish)</div>
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-semibold text-amber-700">{{ $refurbishTasks->count() }} item</span>
                    </div>

                    <div class="space-y-3">
                        @forelse ($refurbishPage as $task)
                            @php
                                $profiles = collect($task['person_in_charge_profiles'] ?? []);
                                $targetMeta = $targetStatus($task['usage_plan_date'] ?? null);
                            @endphp
                            <article wire:key="refurbish-admin-{{ $task['id'] }}" class="rounded-[1.1rem] border border-amber-100 bg-white p-3 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 text-[15px] font-black leading-[1.15] tracking-[-0.03em] text-slate-950"
                                         style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        {{ $task['job_name'] ?? '-' }}
                                    </div>

                                    <span class="inline-flex shrink-0 items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[10px] font-extrabold tracking-[0.08em] text-amber-700 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.65)]">
                                        {{ $task['notification_number'] ?: '-' }}
                                    </span>
                                </div>

                                <div class="mt-3 rounded-[1rem] border border-amber-100 bg-white px-3 py-2.5 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)]">
                                    <div class="space-y-1.5 text-[12px] leading-[1.2rem] text-slate-700">
                                        <div class="flex items-start gap-1.5">
                                            <span class="shrink-0 text-[12px] font-black text-amber-700">Seksi :</span>
                                            <span class="text-[12px] font-semibold">{{ $task['seksi'] ?: '-' }}</span>
                                        </div>

                                        <div class="flex items-start gap-1.5 border-t border-amber-100 pt-1.5">
                                            <div class="flex min-w-0 items-start gap-1.5">
                                                <span class="shrink-0 text-[12px] font-black text-amber-700">Target :</span>
                                                <span class="text-[12px] font-black text-amber-900">{{ $task['usage_plan_date'] ?: '-' }}</span>
                                            </div>

                                            @if ($targetMeta['badge_text'])
                                                <span class="ml-auto inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[9px] font-black {{ $targetMeta['badge_class'] }}">
                                                    {{ $targetMeta['badge_text'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($profiles as $profile)
                                        @php
                                            $name = is_array($profile) ? ($profile['name'] ?? '') : '';
                                            $avatar = is_array($profile) ? ($profile['avatar_url'] ?? null) : null;
                                        @endphp
                                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-2 py-1 ring-1 ring-slate-200">
                                            @if ($avatar)
                                                <img src="{{ $avatar }}" alt="" class="h-8 w-8 rounded-full object-cover" style="object-position: {{ $avatarObjectPosition($profile) }};" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                                <span style="display:none" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                            @else
                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                            @endif
                                            <span class="text-[11px] font-medium text-slate-700">{{ $name }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1.1rem] border border-dashed border-amber-200 bg-white/80 px-4 py-10 text-center text-sm text-slate-500">
                                Belum ada data regu refurbish.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    @endif
</div>
