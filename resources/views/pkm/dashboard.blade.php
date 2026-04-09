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
            'tone' => 'border-[#dbe8fb] bg-[#f8fbff]',
            'icon_tone' => 'bg-[#e7efff] text-[#4c79dd]',
            'accent' => 'text-[#4c79dd]',
        ],
        [
            'label' => 'Total Progress',
            'value' => round($totalProgress, 2).'%',
            'description' => 'Rata-rata progres seluruh pekerjaan',
            'meta' => $pekerjaanMenunggu > 0 ? "{$pekerjaanMenunggu} pekerjaan masih aktif" : 'Semua pekerjaan sudah tertangani',
            'icon' => 'activity',
            'tone' => 'border-[#e4ddfb] bg-[#faf8ff]',
            'icon_tone' => 'bg-[#ede7ff] text-[#6f59d9]',
            'accent' => 'text-[#6f59d9]',
        ],
        [
            'label' => 'Overdue',
            'value' => $overdueCount,
            'description' => 'Pekerjaan melewati target',
            'meta' => $overdueCount > 0 ? 'Perlu segera ditindaklanjuti' : 'Tidak ada target yang terlambat',
            'icon' => 'triangle-alert',
            'tone' => 'border-[#f4dddd] bg-[#fff8f8]',
            'icon_tone' => 'bg-[#feeaea] text-[#db5c5c]',
            'accent' => 'text-[#db5c5c]',
        ],
        [
            'label' => 'Selesai',
            'value' => $pekerjaanSelesai,
            'description' => 'Pekerjaan final di menu Dokumen',
            'meta' => $totalPekerjaan > 0 ? intval(round(($pekerjaanSelesai / max($totalPekerjaan, 1)) * 100)).'% dari total' : 'Belum ada pekerjaan final',
            'icon' => 'badge-check',
            'tone' => 'border-[#dcecdc] bg-[#f7fcf8]',
            'icon_tone' => 'bg-[#e3f4e5] text-[#2f8b57]',
            'accent' => 'text-[#2f8b57]',
        ],
    ];

    $priorityCards = [
        [
            'title' => $overdueCount.' pekerjaan overdue',
            'subtitle' => $overdueCount > 0 ? 'Segera tindak lanjuti' : 'Tidak ada pekerjaan overdue',
            'tone' => 'border-[#f4dddd] bg-[#fff8f8]',
            'icon_tone' => 'bg-[#feeaea] text-[#db5c5c]',
            'icon' => 'triangle-alert',
        ],
        [
            'title' => $todayCount.' deadline hari ini',
            'subtitle' => $todayCount > 0 ? 'Perlu dipantau hari ini' : 'Tidak ada deadline hari ini',
            'tone' => 'border-[#ede2d5] bg-[#fffaf5]',
            'icon_tone' => 'bg-[#f5e8db] text-[#b86c43]',
            'icon' => 'calendar-clock',
        ],
        [
            'title' => $soonCount.' pekerjaan 7 hari ke depan',
            'subtitle' => $soonCount > 0 ? 'Target mulai dekat' : 'Belum ada target dekat',
            'tone' => 'border-[#dbe8fb] bg-[#f8fbff]',
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

    $currentAngle = 0;
    $gradientSegments = [];
    foreach ($statusBreakdown as $item) {
        $angle = (float) ($item['percentage'] ?? 0) * 3.6;
        if ($angle <= 0) {
            continue;
        }

        $startAngle = round($currentAngle, 2);
        $currentAngle += $angle;
        $endAngle = round($currentAngle, 2);
        $gradientSegments[] = "{$item['color']} {$startAngle}deg {$endAngle}deg";
    }

    $donutStyle = count($gradientSegments) > 0
        ? 'background: conic-gradient('.implode(', ', $gradientSegments).');'
        : 'background: conic-gradient(#e5e7eb 0deg 360deg);';

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
@endphp

<div class="space-y-5">
    <section class="rounded-[1.7rem] border border-[#eadfd2] bg-white px-5 py-4 shadow-sm">
        <div class="min-w-0">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-[#b86c43]">Dashboard PKM</p>
            <h1 class="mt-2 text-[1.75rem] font-black leading-none tracking-tight text-slate-900">Selamat Datang, Admin PKM</h1>
        </div>
    </section>

    <section class="grid gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($topCards as $card)
            <article class="rounded-[1.35rem] border p-3.5 shadow-sm {{ $card['tone'] }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">{{ $card['label'] }}</div>
                        <div class="mt-2 text-[26px] font-black leading-none {{ $card['accent'] }}">{{ $card['value'] }}</div>
                        <div class="mt-2 text-[11px] leading-5 text-slate-700">{{ $card['description'] }}</div>
                        <div class="mt-3 text-[10px] font-medium text-slate-500">{{ $card['meta'] }}</div>
                    </div>

                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl {{ $card['icon_tone'] }}">
                        <i data-lucide="{{ $card['icon'] }}" class="h-4.5 w-4.5"></i>
                    </span>
                </div>

                @if ($card['label'] === 'Total Progress')
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/90">
                        <div class="h-full rounded-full bg-[#6f59d9]" style="width: {{ max(0, min(100, $totalProgress)) }}%"></div>
                    </div>
                @endif
            </article>
        @endforeach
    </section>

    <section class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-[18px] font-black text-slate-900">Prioritas Hari Ini</h2>
                <p class="mt-1 text-[12px] text-slate-500">Fokus cepat untuk pekerjaan yang perlu perhatian segera.</p>
            </div>
            <a href="{{ route('pkm.jobwaiting') }}" class="text-[12px] font-bold text-[#4c79dd] hover:text-[#395fb0]">Lihat Semua</a>
        </div>

        <div class="grid gap-2.5 lg:grid-cols-3">
            @foreach ($priorityCards as $priority)
                <article class="rounded-[1.2rem] border px-3.5 py-3 shadow-sm {{ $priority['tone'] }}">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl {{ $priority['icon_tone'] }}">
                            <i data-lucide="{{ $priority['icon'] }}" class="h-4 w-4"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="text-[12px] font-bold text-slate-900">{{ $priority['title'] }}</div>
                            <div class="mt-1 text-[11px] text-slate-600">{{ $priority['subtitle'] }}</div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="grid gap-2.5 xl:grid-cols-[1.4fr_1fr]">
        <article class="overflow-hidden rounded-[1.55rem] border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3.5">
                <div>
                    <h2 class="text-[14px] font-black text-slate-900">Progress 7 Data Terakhir</h2>
                    <p class="mt-1 text-[11px] text-slate-500">Gambaran cepat progres order terbaru yang sedang dipantau.</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] font-semibold text-slate-600">7 data</div>
            </div>

            <div class="p-3.5">
                @if ($chartPoints->isNotEmpty())
                    <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="h-[228px] w-full">
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
                    <div class="flex h-[228px] items-center justify-center rounded-[1.4rem] border border-dashed border-slate-200 bg-slate-50 text-[12px] text-slate-500">
                        Belum ada data progress untuk divisualisasikan.
                    </div>
                @endif
            </div>
        </article>

        <article class="overflow-hidden rounded-[1.55rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3.5">
                <h2 class="text-[14px] font-black text-slate-900">Status Pekerjaan</h2>
                <p class="mt-1 text-[11px] text-slate-500">Distribusi status dari seluruh pekerjaan PKM saat ini.</p>
            </div>

            <div class="grid gap-3 p-3.5 lg:grid-cols-[150px_1fr] lg:items-center">
                <div class="flex justify-center">
                    <div class="relative h-32 w-32 rounded-full" style="{{ $donutStyle }}">
                        <div class="absolute inset-[16px] flex flex-col items-center justify-center rounded-full bg-white text-center">
                            <div class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Total</div>
                            <div class="mt-1 text-[22px] font-black text-slate-900">{{ $totalPekerjaan }}</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse ($statusBreakdown as $item)
                        <div class="flex items-center justify-between gap-3 rounded-[1rem] border border-slate-100 bg-slate-50 px-3 py-2">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-3 w-3 rounded-full {{ $item['class'] }}"></span>
                                <span class="text-[11px] font-medium text-slate-700">{{ $item['label'] }}</span>
                            </div>
                            <div class="text-right">
                                <div class="text-[11px] font-bold text-slate-900">{{ $item['count'] }}</div>
                                <div class="text-[10px] text-slate-500">{{ $item['percentage'] }}%</div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.2rem] border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-[12px] text-slate-500">
                            Belum ada status pekerjaan.
                        </div>
                    @endforelse
                </div>
            </div>
        </article>
    </section>

    <section class="grid gap-2.5 xl:grid-cols-[1.45fr_1fr]">
        <article class="overflow-hidden rounded-[1.55rem] border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3.5">
                <div>
                    <h2 class="text-[14px] font-black text-slate-900">Daftar Pekerjaan</h2>
                    <p class="mt-1 text-[11px] text-slate-500">Ringkasan dari tiga menu utama PKM dengan status terkininya.</p>
                </div>
                <a href="{{ route('pkm.jobwaiting') }}" class="text-[12px] font-bold text-[#4c79dd] hover:text-[#395fb0]">Lihat Semua</a>
            </div>

            <div class="space-y-2.5 p-3.5">
                @forelse ($jobHighlights as $job)
                    @php
                        $tone = $statusToneClasses[$job['status_key']] ?? $statusToneClasses['menunggu'];
                    @endphp
                    <div class="rounded-[1.15rem] border border-slate-200 bg-[#fbfcfd] px-3.5 py-3">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-[13px] font-bold text-slate-900">{{ $job['label'] }}</div>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold {{ $tone['badge'] }}">{{ $job['status_label'] }}</span>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-slate-600">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="calendar" class="h-3.5 w-3.5"></i>{{ $job['date'] }}
                                    </span>
                                    <span class="text-slate-300">|</span>
                                    <span>{{ $job['status_text'] }}</span>
                                </div>

                                <div class="mt-1 text-[10px] font-medium text-slate-500">Sumber: {{ $job['source_menu'] }}</div>
                            </div>

                            <div class="flex min-w-[200px] items-center gap-3">
                                <div class="flex-1">
                                    <div class="mb-1 flex items-center justify-between text-[11px]">
                                        <span class="text-slate-500">Progress</span>
                                        <span class="font-bold text-slate-700">{{ $job['progress'] }}%</span>
                                    </div>
                                    <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full {{ $tone['bar'] }}" style="width: {{ max(0, min(100, $job['progress'])) }}%"></div>
                                    </div>
                                </div>

                                <a href="{{ $job['action_url'] }}" class="inline-flex items-center justify-center rounded-xl border px-4 py-1.5 text-[11px] font-bold transition {{ $tone['button'] }}">
                                    {{ $job['action_label'] }}
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-[1.35rem] border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-[12px] text-slate-500">
                        Belum ada pekerjaan untuk ditampilkan.
                    </div>
                @endforelse
            </div>
        </article>

        <article class="overflow-hidden rounded-[1.55rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3.5">
                <h2 class="text-[14px] font-black text-slate-900">Kalender Target</h2>
                <p class="mt-1 text-[11px] text-slate-500">{{ $calendarMonth->translatedFormat('F Y') }}</p>
            </div>

            <div class="p-3.5">
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
                                        <div class="h-[78px] rounded-2xl border px-2 py-2 {{ $inMonth ? 'border-slate-200 bg-white' : 'border-transparent bg-slate-50' }} {{ $isTodayDate ? 'ring-2 ring-[#ead7c6]' : '' }}">
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

                <div class="mt-4 flex flex-wrap items-center gap-4 text-[10px] text-slate-600">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#db5c5c]"></span>Deadline</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#d79a2b]"></span>Upcoming</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#38a169]"></span>Selesai</span>
                </div>
            </div>
        </article>
    </section>
</div>
