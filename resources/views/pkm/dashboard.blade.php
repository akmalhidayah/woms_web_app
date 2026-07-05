@php
    $now = \Carbon\Carbon::now();
    $menuSummaries = collect($menuSummaries ?? []);
    $statusBreakdown = collect($statusBreakdown ?? []);
    $progressTrend = collect($progressTrend ?? []);
    $jobHighlights = collect($jobHighlights ?? []);

    $targets = collect($targetDates ?? [])->map(function ($item) use ($now) {
        $date = \Carbon\Carbon::parse($item['date']);
        $isDone = (bool) ($item['is_done'] ?? false);

        return [
            'label' => $item['description'] ?? '-',
            'date' => $date,
            'date_str' => $date->format('Y-m-d'),
            'nomor_order' => $item['nomor_order'] ?? null,
            'is_done' => $isDone,
            'source_menu' => $item['source_menu'] ?? null,
            'status_label' => $item['status_label'] ?? null,
            'is_overdue' => ! $isDone && $date->isPast() && ! $date->isToday(),
            'is_today' => ! $isDone && $date->isToday(),
            'days_left' => $now->copy()->startOfDay()->diffInDays($date->copy()->startOfDay(), false),
        ];
    })->sortBy([
        ['is_done', 'asc'],
        ['date_str', 'asc'],
    ])->values();

    $progressItems = collect($jobProgress ?? []);
    $totalPekerjaan = $totalPekerjaan ?? $progressItems->count();
    $pekerjaanSelesai = $pekerjaanSelesai ?? $progressItems->filter(fn ($value) => $value >= 100)->count();
    $pekerjaanMenunggu = $pekerjaanMenunggu ?? $progressItems->filter(fn ($value) => $value < 100)->count();
    $emergencyInitialWorkCount = $emergencyInitialWorkCount ?? 0;
    $totalProgress = $totalProgress ?? round($progressItems->avg() ?? 0, 2);
    $overdueCount = $overdueCount ?? $targets->where('is_overdue', true)->count();
    $todayCount = $todayCount ?? $targets->where('is_today', true)->count();
    $soonCount = $soonCount ?? $targets->filter(fn ($item) => ! $item['is_done'] && ! $item['is_overdue'] && ! $item['is_today'] && $item['days_left'] >= 0 && $item['days_left'] <= 7)->count();

    $calendarMonth = $now->copy()->startOfMonth();
    $start = $calendarMonth->copy()->startOfWeek();
    $end = $calendarMonth->copy()->endOfMonth()->endOfWeek();
    $cursor = $start->copy();

    $topCards = [
        [
            'label' => 'Total Pekerjaan',
            'value' => $totalPekerjaan,
            'description' => 'Semua pekerjaan vendor',
            'meta' => $menuSummaries->firstWhere('title', 'List Pekerjaan')['meta'] ?? 'Ringkasan order aktif',
            'icon' => 'briefcase-business',
            'color' => '#526f8f',
            'icon_tone' => 'bg-black/10 text-white',
            'accent' => 'text-white',
        ],
        [
            'label' => 'Emergency Initial Work',
            'value' => $emergencyInitialWorkCount,
            'description' => 'Emergency masuk melalui Initial Work',
            'meta' => $emergencyInitialWorkCount > 0 ? 'Perlu dipantau di List Pekerjaan' : 'Tidak ada emergency Initial Work',
            'icon' => 'file-warning',
            'color' => '#a96842',
            'icon_tone' => 'bg-black/10 text-white',
            'accent' => 'text-white',
        ],
        [
            'label' => 'Overdue',
            'value' => $overdueCount,
            'description' => 'Pekerjaan melewati target',
            'meta' => $overdueCount > 0 ? 'Perlu segera ditindaklanjuti' : 'Tidak ada target yang terlambat',
            'icon' => 'triangle-alert',
            'color' => '#9b5555',
            'icon_tone' => 'bg-black/10 text-white',
            'accent' => 'text-white',
        ],
        [
            'label' => 'Selesai',
            'value' => $pekerjaanSelesai,
            'description' => 'Pekerjaan final di menu Dokumen',
            'meta' => $totalPekerjaan > 0 ? intval(round(($pekerjaanSelesai / max($totalPekerjaan, 1)) * 100)).'% dari total' : 'Belum ada pekerjaan final',
            'icon' => 'badge-check',
            'color' => '#527a63',
            'icon_tone' => 'bg-black/10 text-white',
            'accent' => 'text-white',
        ],
    ];

    $priorityCards = [
        [
            'title' => $overdueCount.' pekerjaan overdue',
            'subtitle' => $overdueCount > 0 ? 'Segera tindak lanjuti' : 'Tidak ada pekerjaan overdue',
            'tone' => 'border-[#f4dddd] bg-white',
            'icon_tone' => 'bg-[#feeaea] text-[#db5c5c]',
            'icon' => 'triangle-alert',
        ],
        [
            'title' => $todayCount.' deadline hari ini',
            'subtitle' => $todayCount > 0 ? 'Perlu dipantau hari ini' : 'Tidak ada deadline hari ini',
            'tone' => 'border-[#ede2d5] bg-white',
            'icon_tone' => 'bg-[#f5e8db] text-[#b86c43]',
            'icon' => 'calendar-clock',
        ],
        [
            'title' => $soonCount.' pekerjaan 7 hari ke depan',
            'subtitle' => $soonCount > 0 ? 'Target mulai dekat' : 'Belum ada target dekat',
            'tone' => 'border-[#dbe8fb] bg-white',
            'icon_tone' => 'bg-[#e7efff] text-[#4c79dd]',
            'icon' => 'timer',
        ],
    ];

    $chartWidth = 520;
    $chartHeight = 188;
    $chartPaddingX = 28;
    $chartPaddingY = 18;
    $plotWidth = $chartWidth - ($chartPaddingX * 2);
    $plotHeight = $chartHeight - ($chartPaddingY * 2);
    $pointCount = max($progressTrend->count(), 1);
    $maxChartValue = max(100, (int) ($progressTrend->max('value') ?? 0));

    $chartPoints = $progressTrend->values()->map(function ($point, $index) use ($plotWidth, $plotHeight, $chartPaddingX, $chartPaddingY, $pointCount, $maxChartValue) {
        $x = $chartPaddingX + ($pointCount > 1 ? ($index * ($plotWidth / ($pointCount - 1))) : ($plotWidth / 2));
        $y = $chartPaddingY + $plotHeight - (($point['value'] / $maxChartValue) * $plotHeight);

        return [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'label' => $point['label'],
            'value' => $point['value'],
        ];
    });

    $polylinePoints = $chartPoints->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ');

    $donutRadius = 34;
    $donutCircumference = 2 * pi() * $donutRadius;
    $donutOffset = 0;
    $donutSegments = [];

    foreach ($statusBreakdown as $item) {
        $segmentLength = ((float) ($item['percentage'] ?? 0) / 100) * $donutCircumference;

        if ($segmentLength <= 0) {
            continue;
        }

        $donutSegments[] = [
            'color' => $item['color'],
            'length' => round($segmentLength, 2),
            'gap' => round($donutCircumference - $segmentLength, 2),
            'offset' => round(-$donutOffset, 2),
        ];

        $donutOffset += $segmentLength;
    }

    $statusToneClasses = [
        'selesai' => [
            'badge' => 'bg-emerald-100 text-emerald-700',
            'bar' => 'bg-[#38a169]',
            'button' => 'border-emerald-200 text-emerald-700 hover:bg-emerald-50',
        ],
        'overdue' => [
            'badge' => 'bg-rose-100 text-rose-700',
            'bar' => 'bg-[#db5c5c]',
            'button' => 'border-rose-200 text-rose-700 hover:bg-rose-50',
        ],
        'proses' => [
            'badge' => 'bg-blue-100 text-blue-700',
            'bar' => 'bg-[#4c79dd]',
            'button' => 'border-blue-200 text-blue-700 hover:bg-blue-50',
        ],
        'menunggu' => [
            'badge' => 'bg-amber-100 text-amber-700',
            'bar' => 'bg-[#d79a2b]',
            'button' => 'border-amber-200 text-amber-700 hover:bg-amber-50',
        ],
    ];

    $statusCardClasses = [
        'Selesai' => [
            'card' => 'border-emerald-100 bg-emerald-50/80',
            'icon' => 'badge-check',
            'icon_tone' => 'bg-emerald-100 text-emerald-700',
            'text' => 'text-emerald-700',
        ],
        'Proses' => [
            'card' => 'border-blue-100 bg-blue-50/80',
            'icon' => 'activity',
            'icon_tone' => 'bg-blue-100 text-blue-700',
            'text' => 'text-blue-700',
        ],
        'Menunggu' => [
            'card' => 'border-amber-100 bg-amber-50/80',
            'icon' => 'clock-3',
            'icon_tone' => 'bg-amber-100 text-amber-700',
            'text' => 'text-amber-700',
        ],
        'Overdue' => [
            'card' => 'border-rose-100 bg-rose-50/80',
            'icon' => 'triangle-alert',
            'icon_tone' => 'bg-rose-100 text-rose-700',
            'text' => 'text-rose-700',
        ],
    ];

    $jobHighlights = $jobHighlights->map(function ($job) use ($statusToneClasses) {
        $row = is_array($job) ? $job : (array) $job;
        $tone = $statusToneClasses[$row['status_key'] ?? 'menunggu'] ?? $statusToneClasses['menunggu'];

        return array_merge($row, [
            'label' => $row['label'] ?? '-',
            'status_label' => $row['status_label'] ?? '-',
            'date' => $row['date'] ?? '-',
            'status_text' => $row['status_text'] ?? '-',
            'progress_value' => (int) ($row['progress'] ?? 0),
            'action_url' => $row['action_url'] ?? route('pkm.jobwaiting'),
            'action_label' => $row['action_label'] ?? 'Detail',
            'tone_badge' => $tone['badge'],
            'tone_bar' => $tone['bar'],
            'tone_button' => $tone['button'],
        ]);
    })->values();
@endphp

<div class="space-y-3">
    <section class="grid grid-cols-2 gap-2 sm:grid-cols-4">
        @foreach ($topCards as $card)
            <article class="min-w-0 rounded-xl border border-black/10 px-3 py-2.5 shadow-sm sm:px-3.5 sm:py-3" style="background-color: {{ $card['color'] }};">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="truncate text-[8px] font-bold uppercase tracking-[0.12em] text-white/80 sm:text-[9px]">{{ $card['label'] }}</div>
                        <div class="mt-1 text-[24px] font-black leading-none {{ $card['accent'] }} sm:text-[27px]">{{ $card['value'] }}</div>
                        <div class="mt-1 line-clamp-1 text-[9px] font-semibold text-white/75">{{ $card['meta'] }}</div>
                    </div>

                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $card['icon_tone'] }}">
                        <i data-lucide="{{ $card['icon'] }}" class="h-4 w-4"></i>
                    </span>
                </div>
            </article>
        @endforeach
    </section>

    <section class="space-y-2">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-[14px] font-black text-slate-900">Prioritas Hari Ini</h2>
            </div>
            <a href="{{ route('pkm.jobwaiting') }}" class="text-[12px] font-bold text-[#4c79dd] hover:text-[#395fb0]">Lihat Semua</a>
        </div>

        <div class="grid gap-2 sm:grid-cols-3">
            @foreach ($priorityCards as $priority)
                <article class="rounded-[0.9rem] border px-2.5 py-2 shadow-sm {{ $priority['tone'] }}">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $priority['icon_tone'] }}">
                            <i data-lucide="{{ $priority['icon'] }}" class="h-3.5 w-3.5"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="truncate text-[10px] font-bold text-slate-900">{{ $priority['title'] }}</div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="grid gap-2 lg:grid-cols-[1.4fr_1fr]">
        <article class="overflow-hidden rounded-[1.2rem] border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-3 py-2.5">
                <div>
                    <h2 class="text-[13px] font-black text-slate-900">Progress Terakhir</h2>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold text-slate-600">7 data</div>
            </div>

            <div class="p-2.5">
                @if ($chartPoints->isNotEmpty())
                    <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="h-[150px] w-full">
                        @for ($i = 0; $i <= 4; $i++)
                            @php
                                $y = $chartPaddingY + ($plotHeight / 4) * $i;
                                $labelValue = (int) round($maxChartValue - (($maxChartValue / 4) * $i));
                            @endphp
                            <line x1="{{ $chartPaddingX }}" y1="{{ $y }}" x2="{{ $chartWidth - $chartPaddingX }}" y2="{{ $y }}" stroke="#ebeff5" stroke-width="1" />
                            <text x="4" y="{{ $y + 4 }}" fill="#94a3b8" font-size="10">{{ $labelValue }}</text>
                        @endfor

                        <polyline fill="none" stroke="#5b88ff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="{{ $polylinePoints }}" />

                        @foreach ($chartPoints as $point)
                            <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" fill="#5b88ff" />
                            <text x="{{ $point['x'] }}" y="{{ $chartHeight - 4 }}" text-anchor="middle" fill="#64748b" font-size="10">{{ $point['label'] }}</text>
                        @endforeach
                    </svg>
                @else
                    <div class="flex h-[150px] items-center justify-center rounded-[1rem] border border-dashed border-slate-200 bg-slate-50 text-[11px] text-slate-500">
                        Belum ada data.
                    </div>
                @endif
            </div>
        </article>

        <article class="overflow-hidden rounded-[1.2rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-3 py-2.5">
                <h2 class="text-[13px] font-black text-slate-900">Status Pekerjaan</h2>
            </div>

            <div class="grid grid-cols-2 gap-2 p-2.5">
                @forelse ($statusBreakdown as $item)
                    @php($statusCard = $statusCardClasses[$item['label']] ?? $statusCardClasses['Menunggu'])
                    <div class="rounded-xl border px-3 py-3 shadow-sm {{ $statusCard['card'] }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="truncate text-[9px] font-black uppercase tracking-[0.14em] text-slate-500">{{ $item['label'] }}</div>
                                <div class="mt-1 text-[24px] font-black leading-none text-slate-900">{{ $item['count'] }}</div>
                            </div>
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $statusCard['icon_tone'] }}">
                                <i data-lucide="{{ $statusCard['icon'] }}" class="h-4 w-4"></i>
                            </span>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-2">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-white/80">
                                <div class="h-full rounded-full {{ $statusToneClasses[strtolower($item['label'])]['bar'] ?? 'bg-slate-300' }}" style="width: {{ max(0, min(100, $item['percentage'])) }}%"></div>
                            </div>
                            <span class="text-[10px] font-black {{ $statusCard['text'] }}">{{ $item['percentage'] }}%</span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 rounded-[1rem] border border-dashed border-slate-200 bg-slate-50 px-3 py-6 text-center text-[11px] text-slate-500">
                        Belum ada status.
                    </div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid gap-2 lg:grid-cols-[1.45fr_1fr]">
        <article class="overflow-hidden rounded-[1.2rem] border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-3 py-2.5">
                <div>
                    <h2 class="text-[13px] font-black text-slate-900">Daftar Pekerjaan</h2>
                </div>
                <a href="{{ route('pkm.jobwaiting') }}" class="text-[12px] font-bold text-[#4c79dd] hover:text-[#395fb0]">Lihat Semua</a>
            </div>

            <div class="space-y-2 p-2.5">
                @foreach ($jobHighlights as $jobRow)
                    <div class="rounded-[1rem] border border-slate-200 bg-[#fbfcfd] px-3 py-2.5">
                        <div class="flex flex-col gap-2.5 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-[12px] font-bold text-slate-900">{{ $jobRow['label'] }}</div>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-bold {{ $jobRow['tone_badge'] }}">{{ $jobRow['status_label'] }}</span>
                                </div>

                                <div class="mt-1.5 flex flex-wrap items-center gap-2 text-[10px] text-slate-600">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="calendar" class="h-3 w-3"></i>{{ $jobRow['date'] }}
                                    </span>
                                    <span class="text-slate-300">|</span>
                                    <span>{{ $jobRow['status_text'] }}</span>
                                </div>
                            </div>

                            <div class="flex min-w-[180px] items-center gap-2.5">
                                <div class="flex-1">
                                    <div class="mb-1 flex items-center justify-between text-[10px]">
                                        <span class="text-slate-500">Progress</span>
                                        <span class="font-bold text-slate-700">{{ $jobRow['progress_value'] }}%</span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full {{ $jobRow['tone_bar'] }}" style="width: {{ max(0, min(100, $jobRow['progress_value'])) }}%"></div>
                                    </div>
                                </div>

                                <a href="{{ $jobRow['action_url'] }}" class="inline-flex items-center justify-center rounded-lg border px-3 py-1.5 text-[10px] font-bold transition {{ $jobRow['tone_button'] }}">
                                    {{ $jobRow['action_label'] }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if ($jobHighlights->isEmpty())
                    <div class="rounded-[1rem] border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-[11px] text-slate-500">
                        Belum ada pekerjaan.
                    </div>
                @endif
            </div>
        </article>

        <article class="overflow-hidden rounded-[1.2rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-3 py-2.5">
                <h2 class="text-[13px] font-black text-slate-900">Kalender Target</h2>
                <p class="mt-0.5 text-[10px] text-slate-500">{{ $calendarMonth->translatedFormat('F Y') }}</p>
            </div>

            <div class="p-2.5">
                <table class="min-w-full border-separate border-spacing-2">
                    <thead>
                        <tr class="text-[10px] text-slate-500">
                            @foreach (['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $day)
                                <th class="px-1 py-1 text-center font-bold">{{ $day }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @while ($cursor <= $end)
                            <tr>
                                @for ($i = 0; $i < 7; $i++)
                                    @php
                                        $inMonth = $cursor->month === $calendarMonth->month;
                                        $dateKey = $cursor->format('Y-m-d');
                                        $targetForDate = $targets->first(fn ($item) => $item['date_str'] === $dateKey);
                                        $isTodayDate = $cursor->isToday();
                                        $dotClass = 'bg-[#7eb7b0]';

                                        if ($targetForDate) {
                                            if ($targetForDate['is_done']) {
                                                $dotClass = 'bg-[#38a169]';
                                            } elseif ($targetForDate['is_overdue']) {
                                                $dotClass = 'bg-[#db5c5c]';
                                            } elseif ($targetForDate['is_today']) {
                                                $dotClass = 'bg-[#b86c43]';
                                            } else {
                                                $dotClass = 'bg-[#d79a2b]';
                                            }
                                        }
                                    @endphp
                                    <td class="align-top">
                                        <div class="h-[48px] rounded-xl border px-2 py-1.5 {{ $inMonth ? 'border-slate-200 bg-white' : 'border-transparent bg-slate-50' }} {{ $isTodayDate ? 'ring-2 ring-[#ead7c6]' : '' }}">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[10px] font-bold {{ $inMonth ? 'text-slate-800' : 'text-slate-300' }}">{{ $inMonth ? $cursor->day : '' }}</span>
                                                @if ($targetForDate)
                                                    <span class="inline-flex h-2.5 w-2.5 rounded-full {{ $dotClass }}"></span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    @php $cursor->addDay(); @endphp
                                @endfor
                            </tr>
                        @endwhile
                    </tbody>
                </table>

                <div class="mt-2 flex flex-wrap items-center gap-3 text-[10px] text-slate-600">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#db5c5c]"></span>Deadline</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#d79a2b]"></span>Upcoming</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#38a169]"></span>Selesai</span>
                </div>
            </div>
        </article>
    </section>
</div>
