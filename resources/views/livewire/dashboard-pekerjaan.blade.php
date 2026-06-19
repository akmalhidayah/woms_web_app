@php
    $allTasks = collect($tasks ?? []);

    $fabrikasiTasks = $allTasks
        ->filter(fn ($row) => (($row['catatan'] ?? null) === 'Regu Fabrikasi') || empty($row['catatan']))
        ->values();

    $refurbishTasks = $allTasks
        ->filter(fn ($row) => ($row['catatan'] ?? null) === 'Regu Bengkel (Refurbish)')
        ->values();

    $isDisplayMode = ($mode ?? 'admin') === 'display';

    $fabrikasiPerPage = $isDisplayMode
        ? 3
        : ($fabrikasiTasks->contains(fn ($row) => count($row['person_in_charge_profiles'] ?? []) > 2)
            ? 4
            : max(1, (int) $perPageFabrikasi));

    $refurbishPerPage = $isDisplayMode
        ? 3
        : ($refurbishTasks->contains(fn ($row) => count($row['person_in_charge_profiles'] ?? []) > 2)
            ? 2
            : max(1, (int) $perPageRefurbish));

    $fabrikasiChunks = $fabrikasiTasks->chunk($fabrikasiPerPage);
    $refurbishChunks = $refurbishTasks->chunk($refurbishPerPage);

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

    $progressBadge = function (?string $status, ?string $label = null): array {
        return match ($status) {
            \App\Models\OrderWorkshop::PROGRESS_DONE => [
                'label' => 'Selesai',
                'class' => 'border-emerald-200 bg-emerald-100 text-emerald-800',
            ],
            \App\Models\OrderWorkshop::PROGRESS_QUALITY_CONTROL => [
                'label' => 'Quality Control',
                'class' => 'border-violet-200 bg-violet-100 text-violet-800',
            ],
            \App\Models\OrderWorkshop::PROGRESS_PENDING => [
                'label' => 'Pending',
                'class' => 'border-orange-200 bg-orange-100 text-orange-800',
            ],
            \App\Models\OrderWorkshop::PROGRESS_IN_PROGRESS => [
                'label' => 'Sementara Proses',
                'class' => 'border-blue-200 bg-blue-100 text-blue-800',
            ],
            \App\Models\OrderWorkshop::PROGRESS_MENUNGGU_JADWAL => [
                'label' => 'Menunggu Jadwal',
                'class' => 'border-amber-200 bg-amber-100 text-amber-800',
            ],
            default => [
                'label' => $label ?: 'Menunggu Jadwal',
                'class' => 'border-slate-200 bg-slate-100 text-slate-700',
            ],
        };
    };
    $progressShortLabel = function (?string $status, string $label): string {
    return match ($status) {
        \App\Models\OrderWorkshop::PROGRESS_IN_PROGRESS => 'Proses',
        \App\Models\OrderWorkshop::PROGRESS_MENUNGGU_JADWAL => 'Jadwal',
        \App\Models\OrderWorkshop::PROGRESS_QUALITY_CONTROL => 'QC',
        \App\Models\OrderWorkshop::PROGRESS_PENDING => 'Pending',
        \App\Models\OrderWorkshop::PROGRESS_DONE => 'Selesai',
        default => $label,
    };
};
@endphp

<div>
    @if (($mode ?? 'admin') === 'display')
        <div wire:poll.keep-alive.5s="tickDisplay" class="flex h-screen w-screen flex-col overflow-hidden bg-slate-100 text-slate-950" style="color-scheme: light only;">
            <div class="tv-board-header mb-2 border border-red-950 bg-red-900 text-white shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="tv-logo-box">
                        <img src="{{ asset('assets/branding/logos/logo-sig.png') }}" alt="SIG" class="h-8 w-auto object-contain">
                    </span>
                    <span class="tv-logo-box">
                        <img src="{{ asset('assets/branding/logos/logo-st2.png') }}" alt="ST" class="h-9 w-auto object-contain">
                    </span>
                </div>

                <div class="text-left">
                    <h1 class="tv-board-title text-white">Pekerjaan Bengkel</h1>
                    <div id="dateDisplay" class="tv-board-date text-slate-300"></div>
                </div>

                <div class="tv-header-right">
                    <div class="tv-summary-card">
                        <div class="tv-summary-title">Total Order</div>
                        <div class="tv-summary-values">
                            <div><span>Bengkel</span><strong>{{ $orderSummary['total_workshop'] ?? 0 }}</strong></div>
                            <div><span>Jasa</span><strong>{{ $orderSummary['total_service'] ?? 0 }}</strong></div>
                        </div>
                    </div>

                    <div class="tv-summary-card">
                        <div class="tv-summary-title">Diproses</div>
                        <div class="tv-summary-values">
                            <div><span>Bengkel</span><strong>{{ $orderSummary['processed_workshop'] ?? 0 }}</strong></div>
                            <div><span>Jasa</span><strong>{{ $orderSummary['processed_service'] ?? 0 }}</strong></div>
                        </div>
                    </div>

                    <div class="tv-header-clock text-right">
                        <div class="text-[9px] font-bold uppercase tracking-[0.22em] text-slate-300">Jam</div>
                        <div id="timeDisplay" class="tv-board-time tracking-tight text-white"></div>
                        <div class="mt-1 text-[9px] font-semibold text-slate-300">
                            Fabrikasi {{ $fabrikasiSlideCount > 0 ? ($fabrikasiSlideIndex + 1) : 0 }} / {{ $fabrikasiSlideCount }}
                            | Refurbish {{ $refurbishSlideCount > 0 ? ($refurbishSlideIndex + 1) : 0 }} / {{ $refurbishSlideCount }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="ticker mb-2 border border-red-950 bg-red-900 text-white shadow-sm" style="--ticker-duration: {{ $tickerDuration }}s;">
                <div class="ticker-track">
                    <span class="ticker-item">{{ $tickerMessage }}</span>
                    <span class="ticker-item">{{ $tickerMessage }}</span>
                    <span class="ticker-item">{{ $tickerMessage }}</span>
                    <span class="ticker-item">{{ $tickerMessage }}</span>
                </div>
            </div>

            <div class="tv-board-main">
                <section class="tv-regu-section">
                    <div class="tv-regu-heading">
                        <div class="tv-regu-title drop-shadow-[0_1px_0_rgba(0,0,0,0.25)]">Regu Fabrikasi</div>
                        <span class="absolute right-0 rounded-full bg-white px-3 py-1 text-[11px] font-bold text-blue-800 ring-1 ring-blue-200">{{ $fabrikasiTasks->count() }} item</span>
                    </div>

                    <div class="tv-task-grid tv-task-grid-fabrikasi">
                        @forelse ($fabrikasiPage as $task)
                            @php
                                $profiles = collect($task['person_in_charge_profiles'] ?? []);
                                $targetMeta = $targetStatus($task['usage_plan_date'] ?? null);
                                $isCompleted = (bool) ($task['is_completed'] ?? false);
                                $progressMeta = $progressBadge($task['progress_status'] ?? null, $task['progress_label'] ?? null);
                            @endphp
                            <article wire:key="fabrikasi-display-{{ $task['id'] }}" class="tv-task-card border {{ $isCompleted ? 'border-emerald-300 bg-emerald-50' : 'border-blue-200 bg-white' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="tv-card-title tracking-[-0.03em] text-slate-950 drop-shadow-[0_1px_0_rgba(255,255,255,0.7)]">
                                        {{ $task['job_name'] ?? '-' }}
                                    </div>

                        <div class="flex shrink-0 items-center gap-1">
    <span class="inline-flex items-center rounded-full border px-1.5 py-0.5 text-[12px] font-black uppercase tracking-[0.02em] leading-none {{ $progressMeta['class'] }}">
        {{ $progressShortLabel($task['progress_status'] ?? null, $progressMeta['label']) }}
    </span>

    <span class="inline-flex shrink-0 items-center rounded-full border px-2.5 py-1 text-[10px] font-extrabold tracking-[0.08em] shadow-[inset_0_0_0_1px_rgba(255,255,255,0.65)] {{ $isCompleted ? 'border-emerald-200 bg-white text-emerald-700' : 'border-blue-200 bg-blue-50 text-blue-800' }}">
        {{ $task['notification_number'] ?: '-' }}
    </span>
</div>
                                </div>

                                

                                <div class="tv-card-meta border shadow-[inset_0_1px_0_rgba(255,255,255,0.8)] {{ $isCompleted ? 'border-emerald-100 bg-white/85' : 'border-blue-100 bg-blue-50' }}">
                                    <div class="tv-card-meta-text space-y-1 text-slate-800">
                                        <div class="flex items-start gap-1.5">
                                            <span class="shrink-0 font-black text-blue-800">Seksi :</span>
                                            <span class="font-bold"
                                                  style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                                {{ $task['seksi'] ?: '-' }}
                                            </span>
                                        </div>

                                        <div class="flex items-start gap-1.5 border-t border-blue-100 pt-1.5">
                                            <div class="flex min-w-0 items-start gap-1.5">
                                                <span class="shrink-0 font-black text-blue-800">Target :</span>
                                                <span class="font-black text-blue-950">{{ $task['usage_plan_date'] ?: '-' }}</span>
                                            </div>

                                            @if ($targetMeta['badge_text'])
                                                <span class="ml-auto inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[9px] font-black {{ $targetMeta['badge_class'] }}">
                                                    {{ $targetMeta['badge_text'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="tv-pic-wrap">
                                    @if ($profiles->isNotEmpty())
                                        <div class="tv-pic-grid">
                                            @foreach ($profiles as $profile)
                                                @php
                                                    $name = is_array($profile) ? ($profile['name'] ?? '') : '';
                                                    $avatar = is_array($profile) ? ($profile['avatar_url'] ?? null) : null;
                                                    $descriptions = collect(is_array($profile) ? ($profile['work_descriptions'] ?? []) : [])->filter()->values();
                                                @endphp
                                                <div class="tv-pic-item shadow-[0_1px_4px_rgba(30,64,175,0.08)]">
                                                    <div class="tv-pic-photo">
                                                        @if ($avatar)
                                                            <img src="{{ $avatar }}" alt="" class="tv-pic-img w-full bg-slate-100 object-contain" style="object-position: {{ $avatarObjectPosition($profile) }};" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                            <span style="display:none;" class="tv-pic-fallback w-full items-center justify-center bg-slate-200 text-[12px] font-black text-slate-700">{{ $initials($name) }}</span>
                                                        @else
                                                            <span class="tv-pic-fallback flex w-full items-center justify-center bg-slate-200 text-[12px] font-black text-slate-700">{{ $initials($name) }}</span>
                                                        @endif
                                                        <div class="tv-pic-name">{{ $name }}</div>
                                                    </div>
                                                    <div class="tv-pic-desc">
                                                        @if ($descriptions->isNotEmpty())
                                                        <ul class="tv-pic-desc-list list-disc">
                                                            @foreach ($descriptions as $description)
                                                                <li>{{ $description }}</li>
                                                            @endforeach
                                                        </ul>
                                                        @else
                                                            <div class="text-[9px] font-medium text-slate-400">Belum ada uraian.</div>
                                                        @endif
                                                    </div>
                                                </div>
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

                <section class="tv-regu-section">
                    <div class="tv-regu-heading">
                        <div class="tv-regu-title drop-shadow-[0_1px_0_rgba(0,0,0,0.25)]">Regu Bengkel (Refurbish)</div>
                        <span class="absolute right-0 rounded-full bg-white px-2.5 py-1 text-[10px] font-bold text-blue-800 ring-1 ring-blue-200">{{ $refurbishTasks->count() }} item</span>
                    </div>

                    <div class="tv-task-grid">
                        @forelse ($refurbishPage as $task)
                            @php
                                $profiles = collect($task['person_in_charge_profiles'] ?? []);
                                $targetMeta = $targetStatus($task['usage_plan_date'] ?? null);
                                $isCompleted = (bool) ($task['is_completed'] ?? false);
                                $progressMeta = $progressBadge($task['progress_status'] ?? null, $task['progress_label'] ?? null);
                            @endphp
                            <article wire:key="refurbish-display-{{ $task['id'] }}" class="tv-task-card border {{ $isCompleted ? 'border-emerald-300 bg-emerald-50' : 'border-blue-200 bg-white' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="tv-card-title tracking-[-0.03em] text-slate-950 drop-shadow-[0_1px_0_rgba(255,255,255,0.7)]">
                                        {{ $task['job_name'] ?? '-' }}
                                    </div>

               <div class="flex shrink-0 items-center gap-1">
    <span class="inline-flex items-center rounded-full border px-1.5 py-0.5 text-[7px] font-black uppercase tracking-[0.02em] leading-none {{ $progressMeta['class'] }}">
        {{ $progressShortLabel($task['progress_status'] ?? null, $progressMeta['label']) }}
    </span>

    <span class="inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[9px] font-extrabold tracking-[0.08em] shadow-[inset_0_0_0_1px_rgba(255,255,255,0.65)] {{ $isCompleted ? 'border-emerald-200 bg-white text-emerald-700' : 'border-blue-200 bg-blue-50 text-blue-800' }}">
        {{ $task['notification_number'] ?: '-' }}
    </span>
</div>
                                </div>

                        

                                <div class="tv-card-meta border shadow-[inset_0_1px_0_rgba(255,255,255,0.8)] {{ $isCompleted ? 'border-emerald-100 bg-white/85' : 'border-blue-100 bg-blue-50' }}">
                                    <div class="tv-card-meta-text space-y-1 text-slate-800">
                                        <div class="flex items-start gap-1.5">
                                            <span class="shrink-0 font-black text-blue-800">Seksi :</span>
                                            <span class="font-bold"
                                                  style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                                {{ $task['seksi'] ?: '-' }}
                                            </span>
                                        </div>

                                        <div class="flex items-start gap-1.5 border-t border-blue-100 pt-1.5">
                                            <div class="flex min-w-0 items-start gap-1.5">
                                                <span class="shrink-0 font-black text-blue-800">Target :</span>
                                                <span class="font-black text-blue-950">{{ $task['usage_plan_date'] ?: '-' }}</span>
                                            </div>

                                            @if ($targetMeta['badge_text'])
                                                <span class="ml-auto inline-flex shrink-0 items-center rounded-full border px-1.5 py-0.5 text-[8px] font-black {{ $targetMeta['badge_class'] }}">
                                                    {{ $targetMeta['badge_text'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="tv-pic-wrap">
                                    @if ($profiles->isNotEmpty())
                                        <div class="tv-pic-grid">
                                            @foreach ($profiles as $profile)
                                                @php
                                                    $name = is_array($profile) ? ($profile['name'] ?? '') : '';
                                                    $avatar = is_array($profile) ? ($profile['avatar_url'] ?? null) : null;
                                                    $descriptions = collect(is_array($profile) ? ($profile['work_descriptions'] ?? []) : [])->filter()->values();
                                                @endphp
                                                <div class="tv-pic-item shadow-[0_1px_4px_rgba(30,64,175,0.08)]">
                                                    <div class="tv-pic-photo">
                                                        @if ($avatar)
                                                            <img src="{{ $avatar }}" alt="" class="tv-pic-img w-full bg-slate-100 object-contain" style="object-position: {{ $avatarObjectPosition($profile) }};" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                            <span style="display:none;" class="tv-pic-fallback w-full items-center justify-center bg-slate-200 text-[12px] font-black text-slate-700">{{ $initials($name) }}</span>
                                                        @else
                                                            <span class="tv-pic-fallback flex w-full items-center justify-center bg-slate-200 text-[12px] font-black text-slate-700">{{ $initials($name) }}</span>
                                                        @endif
                                                        <div class="tv-pic-name">{{ $name }}</div>
                                                    </div>
                                                    <div class="tv-pic-desc">
                                                        @if ($descriptions->isNotEmpty())
                                                        <ul class="tv-pic-desc-list list-disc">
                                                            @foreach ($descriptions as $description)
                                                                <li>{{ $description }}</li>
                                                            @endforeach
                                                        </ul>
                                                        @else
                                                            <div class="text-[9px] font-medium text-slate-400">Belum ada uraian.</div>
                                                        @endif
                                                    </div>
                                                </div>
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
        <div wire:poll.keep-alive.5s="refreshBoard" class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-[0.98rem] font-bold text-slate-900">Preview Display Bengkel</h2>
                    <p class="text-[10px] text-slate-500">
                        Fabrikasi {{ $fabrikasiSlideCount > 0 ? ($fabrikasiSlideIndex + 1) : 0 }} / {{ $fabrikasiSlideCount }}
                        | Refurbish {{ $refurbishSlideCount > 0 ? ($refurbishSlideIndex + 1) : 0 }} / {{ $refurbishSlideCount }}
                    </p>
                </div>

                <button type="button" wire:click="nextSlide" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50">
                    <i data-lucide="chevrons-right" class="h-3.5 w-3.5"></i>
                    Geser
                </button>
            </div>

            <div class="grid gap-3 xl:grid-cols-[1.4fr_1fr]">
                <section class="rounded-xl border border-sky-200 bg-sky-50 p-3">
                    <div class="mb-2.5 flex items-center justify-between">
                        <div class="text-xs font-bold text-sky-950">Regu Fabrikasi</div>
                        <span class="rounded-full bg-white px-2 py-0.5 text-[9px] font-semibold text-sky-700 ring-1 ring-sky-200">{{ $fabrikasiTasks->count() }} item</span>
                    </div>

                    <div class="grid gap-2.5 md:grid-cols-2">
                        @forelse ($fabrikasiPage as $task)
                            @php
                                $profiles = collect($task['person_in_charge_profiles'] ?? []);
                                $targetMeta = $targetStatus($task['usage_plan_date'] ?? null);
                                $isCompleted = (bool) ($task['is_completed'] ?? false);
                                $progressMeta = $progressBadge($task['progress_status'] ?? null, $task['progress_label'] ?? null);
                            @endphp
                            <article wire:key="fabrikasi-admin-{{ $task['id'] }}" class="rounded-xl border p-2.5 shadow-sm {{ $isCompleted ? 'border-emerald-300 bg-emerald-50' : 'border-sky-100 bg-white' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 text-[13px] font-black leading-[1.15] text-slate-950"
                                         style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        {{ $task['job_name'] ?? '-' }}
                                    </div>

                                    <span class="inline-flex shrink-0 items-center rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-[9px] font-extrabold tracking-[0.04em] text-sky-700 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.65)]">
                                        {{ $task['notification_number'] ?: '-' }}
                                    </span>
                                </div>

                                <div class="mt-1.5 flex justify-end">
                                    <span class="inline-flex w-fit items-center rounded-full border px-2 py-0.5 text-[8px] font-black uppercase tracking-[0.04em] {{ $progressMeta['class'] }}">
                                        {{ $progressMeta['label'] }}
                                    </span>
                                </div>

                                <div class="mt-2 rounded-lg border border-sky-100 bg-sky-50/50 px-2.5 py-2 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)]">
                                    <div class="space-y-1 text-[11px] leading-[1rem] text-slate-700">
                                        <div class="flex items-start gap-1.5">
                                            <span class="shrink-0 text-[11px] font-black text-sky-700">Seksi :</span>
                                            <span class="text-[11px] font-semibold">{{ $task['seksi'] ?: '-' }}</span>
                                        </div>

                                        <div class="flex items-start gap-1.5 border-t border-sky-100 pt-1">
                                            <div class="flex min-w-0 items-start gap-1.5">
                                                <span class="shrink-0 text-[11px] font-black text-sky-700">Target :</span>
                                                <span class="text-[11px] font-black text-sky-950">{{ $task['usage_plan_date'] ?: '-' }}</span>
                                            </div>

                                            @if ($targetMeta['badge_text'])
                                                <span class="ml-auto inline-flex shrink-0 items-center rounded-full border px-1.5 py-0.5 text-[8px] font-black {{ $targetMeta['badge_class'] }}">
                                                    {{ $targetMeta['badge_text'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2.5 flex flex-wrap gap-2">
                                    @foreach ($profiles as $profile)
                                        @php
                                            $name = is_array($profile) ? ($profile['name'] ?? '') : '';
                                            $avatar = is_array($profile) ? ($profile['avatar_url'] ?? null) : null;
                                            $descriptions = collect(is_array($profile) ? ($profile['work_descriptions'] ?? []) : [])->filter()->values();
                                        @endphp
                                        <div class="grid min-w-[190px] grid-cols-[58px_1fr] gap-2 rounded-lg bg-slate-50 px-2 py-1.5 ring-1 ring-slate-200">
                                            <div class="h-[76px] overflow-hidden rounded-md border border-slate-200 bg-white text-center">
                                                @if ($avatar)
                                                    <img src="{{ $avatar }}" alt="" class="h-[56px] w-full object-cover" style="object-position: {{ $avatarObjectPosition($profile) }};" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <span style="display:none" class="h-[56px] w-full items-center justify-center bg-slate-200 text-[12px] font-black text-slate-700">{{ $initials($name) }}</span>
                                                @else
                                                    <span class="flex h-[56px] w-full items-center justify-center bg-slate-200 text-[12px] font-black text-slate-700">{{ $initials($name) }}</span>
                                                @endif
                                                <div class="flex h-[20px] items-center justify-center border-t border-slate-200 bg-white px-1 text-[7.5px] font-black leading-tight text-slate-800">{{ $name }}</div>
                                            </div>
                                            <div class="min-w-0 border-l border-slate-200 pl-2">
                                                @if ($descriptions->isNotEmpty())
                                                <ul class="list-disc space-y-0.5 pl-4 text-[10px] font-semibold leading-tight text-slate-700">
                                                    @foreach ($descriptions as $description)
                                                        <li>{{ $description }}</li>
                                                    @endforeach
                                                </ul>
                                                @else
                                                    <div class="text-[10px] font-medium text-slate-400">Belum ada uraian.</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </article>
                        @empty
                            <div class="rounded-xl border border-dashed border-blue-200 bg-white/80 px-4 py-8 text-center text-xs text-slate-500 md:col-span-2">
                                Belum ada data regu fabrikasi.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-xl border border-orange-200 bg-orange-50 p-3">
                    <div class="mb-2.5 flex items-center justify-between">
                        <div class="text-xs font-bold text-orange-950">Regu Bengkel (Refurbish)</div>
                        <span class="rounded-full bg-white px-2 py-0.5 text-[9px] font-semibold text-orange-700 ring-1 ring-orange-200">{{ $refurbishTasks->count() }} item</span>
                    </div>

                    <div class="space-y-2.5">
                        @forelse ($refurbishPage as $task)
                            @php
                                $profiles = collect($task['person_in_charge_profiles'] ?? []);
                                $targetMeta = $targetStatus($task['usage_plan_date'] ?? null);
                                $isCompleted = (bool) ($task['is_completed'] ?? false);
                                $progressMeta = $progressBadge($task['progress_status'] ?? null, $task['progress_label'] ?? null);
                            @endphp
                            <article wire:key="refurbish-admin-{{ $task['id'] }}" class="rounded-xl border p-2.5 shadow-sm {{ $isCompleted ? 'border-emerald-300 bg-emerald-50' : 'border-orange-100 bg-white' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 text-[13px] font-black leading-[1.15] text-slate-950"
                                         style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        {{ $task['job_name'] ?? '-' }}
                                    </div>

                                    <span class="inline-flex shrink-0 items-center rounded-full border border-orange-200 bg-orange-50 px-2 py-0.5 text-[9px] font-extrabold tracking-[0.04em] text-orange-700 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.65)]">
                                        {{ $task['notification_number'] ?: '-' }}
                                    </span>
                                </div>

                                <div class="mt-1.5 flex justify-end">
                                    <span class="inline-flex w-fit items-center rounded-full border px-2 py-0.5 text-[8px] font-black uppercase tracking-[0.04em] {{ $progressMeta['class'] }}">
                                        {{ $progressMeta['label'] }}
                                    </span>
                                </div>

                                <div class="mt-2 rounded-lg border border-orange-100 bg-orange-50/50 px-2.5 py-2 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)]">
                                    <div class="space-y-1 text-[11px] leading-[1rem] text-slate-700">
                                        <div class="flex items-start gap-1.5">
                                            <span class="shrink-0 text-[11px] font-black text-orange-700">Seksi :</span>
                                            <span class="text-[11px] font-semibold">{{ $task['seksi'] ?: '-' }}</span>
                                        </div>

                                        <div class="flex items-start gap-1.5 border-t border-orange-100 pt-1">
                                            <div class="flex min-w-0 items-start gap-1.5">
                                                <span class="shrink-0 text-[11px] font-black text-orange-700">Target :</span>
                                                <span class="text-[11px] font-black text-orange-950">{{ $task['usage_plan_date'] ?: '-' }}</span>
                                            </div>

                                            @if ($targetMeta['badge_text'])
                                                <span class="ml-auto inline-flex shrink-0 items-center rounded-full border px-1.5 py-0.5 text-[8px] font-black {{ $targetMeta['badge_class'] }}">
                                                    {{ $targetMeta['badge_text'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2.5 flex flex-wrap gap-2">
                                    @foreach ($profiles as $profile)
                                        @php
                                            $name = is_array($profile) ? ($profile['name'] ?? '') : '';
                                            $avatar = is_array($profile) ? ($profile['avatar_url'] ?? null) : null;
                                            $descriptions = collect(is_array($profile) ? ($profile['work_descriptions'] ?? []) : [])->filter()->values();
                                        @endphp
                                        <div class="grid min-w-[180px] grid-cols-[56px_1fr] gap-2 rounded-lg bg-slate-50 px-2 py-1.5 ring-1 ring-slate-200">
                                            <div class="h-[74px] overflow-hidden rounded-md border border-slate-200 bg-white text-center">
                                                @if ($avatar)
                                                    <img src="{{ $avatar }}" alt="" class="h-[54px] w-full object-cover" style="object-position: {{ $avatarObjectPosition($profile) }};" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <span style="display:none" class="h-[54px] w-full items-center justify-center bg-slate-200 text-[11px] font-black text-slate-700">{{ $initials($name) }}</span>
                                                @else
                                                    <span class="flex h-[54px] w-full items-center justify-center bg-slate-200 text-[11px] font-black text-slate-700">{{ $initials($name) }}</span>
                                                @endif
                                                <div class="flex h-[20px] items-center justify-center border-t border-slate-200 bg-white px-1 text-[7.5px] font-black leading-tight text-slate-800">{{ $name }}</div>
                                            </div>
                                            <div class="min-w-0 border-l border-slate-200 pl-2">
                                                @if ($descriptions->isNotEmpty())
                                                <ul class="list-disc space-y-0.5 pl-4 text-[10px] font-semibold leading-tight text-slate-700">
                                                    @foreach ($descriptions as $description)
                                                        <li>{{ $description }}</li>
                                                    @endforeach
                                                </ul>
                                                @else
                                                    <div class="text-[10px] font-medium text-slate-400">Belum ada uraian.</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </article>
                        @empty
                            <div class="rounded-xl border border-dashed border-amber-200 bg-white/80 px-4 py-8 text-center text-xs text-slate-500">
                                Belum ada data regu refurbish.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    @endif
</div>
