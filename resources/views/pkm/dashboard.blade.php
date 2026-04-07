        @php
            $now = \Carbon\Carbon::now();
            $seedTargets = $targetDates ?? [
                ['date' => $now->copy()->subDays(3)->toDateString(), 'description' => 'Finalisasi fabrikasi support frame cyclone'],
                ['date' => $now->copy()->toDateString(), 'description' => 'Upload dokumen inspeksi vendor ke PKM'],
                ['date' => $now->copy()->addDays(2)->toDateString(), 'description' => 'Target machining shaft cooler line 2'],
                ['date' => $now->copy()->addDays(5)->toDateString(), 'description' => 'Penyelesaian revisi bracket transfer chute'],
                ['date' => $now->copy()->addDays(9)->toDateString(), 'description' => 'Persiapan material overhaul bucket elevator'],
            ];

            $targets = collect($seedTargets)->map(function ($item) use ($now) {
                $date = \Carbon\Carbon::parse($item['date']);

                return [
                    'label' => $item['description'] ?? '-',
                    'date' => $date,
                    'date_str' => $date->format('Y-m-d'),
                    'is_overdue' => $date->isPast() && ! $date->isToday(),
                    'is_today' => $date->isToday(),
                    'days_left' => $now->copy()->startOfDay()->diffInDays($date->copy()->startOfDay(), false),
                ];
            })->sortBy('date')->values();

            $progressItems = collect($jobProgress ?? [100, 82, 76, 65, 42]);
            $totalPekerjaan = $totalPekerjaan ?? $progressItems->count();
            $pekerjaanSelesai = $pekerjaanSelesai ?? $progressItems->filter(fn ($value) => $value >= 100)->count();
            $pekerjaanMenunggu = $pekerjaanMenunggu ?? $progressItems->filter(fn ($value) => $value < 100)->count();
            $totalProgress = $totalProgress ?? round($progressItems->avg() ?? 0, 2);
            $overdueCount = $targets->where('is_overdue', true)->count();
            $todayCount = $targets->where('is_today', true)->count();
            $soonCount = $targets->filter(fn ($item) => ! $item['is_overdue'] && ! $item['is_today'] && $item['days_left'] >= 0 && $item['days_left'] <= 7)->count();

            $calendarMonth = $now->copy()->startOfMonth();
            $start = $calendarMonth->copy()->startOfWeek();
            $end = $calendarMonth->copy()->endOfMonth()->endOfWeek();
            $cursor = $start->copy();
        @endphp

        <div class="space-y-5">
            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[1.6rem] border border-[#f1dccb] bg-gradient-to-br from-[#fff8f3] via-white to-[#fffaf6] p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Total Pekerjaan</div>
                            <div class="mt-2 text-[30px] font-black leading-none text-slate-900">{{ $totalPekerjaan }}</div>
                            <div class="mt-2 text-[12px] leading-5 text-slate-600">Semua pekerjaan yang sedang ditangani vendor.</div>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[#f8e6d9] text-[#c46f45]"><i data-lucide="layers-3" class="h-5 w-5"></i></span>
                    </div>
                </article>

                <article class="rounded-[1.6rem] border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-amber-50 p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Menunggu</div>
                            <div class="mt-2 text-[30px] font-black leading-none text-slate-900">{{ $pekerjaanMenunggu }}</div>
                            <div class="mt-2 text-[12px] leading-5 text-slate-600">Masih butuh update atau tindak lanjut.</div>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-700"><i data-lucide="hourglass" class="h-5 w-5"></i></span>
                    </div>
                </article>

                <article class="rounded-[1.6rem] border border-[#f1dccb] bg-gradient-to-br from-[#fff8f3] via-white to-[#fffaf6] p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Total Progress</div>
                            <div class="mt-2 text-[30px] font-black leading-none text-slate-900">{{ round($totalProgress, 2) }}%</div>
                            <div class="mt-2 text-[12px] leading-5 text-slate-600">Rata-rata progres seluruh pekerjaan.</div>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[#f8e6d9] text-[#c46f45]"><i data-lucide="activity" class="h-5 w-5"></i></span>
                    </div>
                    <div class="mt-4 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-[#d88452] to-[#efbc7d]" style="width: {{ max(0, min(100, $totalProgress)) }}%"></div>
                    </div>
                </article>

                <article class="rounded-[1.6rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-emerald-50 p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Selesai</div>
                            <div class="mt-2 text-[30px] font-black leading-none text-slate-900">{{ $pekerjaanSelesai }}</div>
                            <div class="mt-2 text-[12px] leading-5 text-slate-600">Pekerjaan yang sudah 100% progress.</div>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700"><i data-lucide="badge-check" class="h-5 w-5"></i></span>
                    </div>
                </article>
            </section>

            <section class="grid gap-3 lg:grid-cols-3">
                <article class="rounded-[1.5rem] border border-red-100 bg-gradient-to-r from-red-50 to-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-red-100 text-red-700"><i data-lucide="alert-triangle" class="h-4 w-4"></i></span>
                            <div><div class="text-[13px] font-bold text-slate-900">Overdue</div><div class="text-[11px] text-slate-600">Melewati target</div></div>
                        </div>
                        <div class="text-[24px] font-black text-slate-900">{{ $overdueCount }}</div>
                    </div>
                </article>

                <article class="rounded-[1.5rem] border border-[#f1dccb] bg-gradient-to-r from-[#fff8f3] to-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-[#f8e6d9] text-[#c46f45]"><i data-lucide="calendar-days" class="h-4 w-4"></i></span>
                            <div><div class="text-[13px] font-bold text-slate-900">Hari Ini</div><div class="text-[11px] text-slate-600">Jatuh tempo hari ini</div></div>
                        </div>
                        <div class="text-[24px] font-black text-slate-900">{{ $todayCount }}</div>
                    </div>
                </article>

                <article class="rounded-[1.5rem] border border-amber-100 bg-gradient-to-r from-amber-50 to-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-amber-700"><i data-lucide="timer" class="h-4 w-4"></i></span>
                            <div><div class="text-[13px] font-bold text-slate-900">7 Hari ke Depan</div><div class="text-[11px] text-slate-600">Target mulai dekat</div></div>
                        </div>
                        <div class="text-[24px] font-black text-slate-900">{{ $soonCount }}</div>
                    </div>
                </article>
            </section>

            <section class="grid gap-3 xl:grid-cols-[1.9fr_1.05fr]">
                <article class="overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                        <div>
                            <h2 class="text-[15px] font-black text-slate-900">Target Pekerjaan</h2>
                            <p class="mt-1 text-[12px] text-slate-500">Urutan target paling dekat agar prioritas cepat terlihat.</p>
                        </div>
                        <a href="{{ route('pkm.jobwaiting') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#fff5ed] px-3 py-2 text-[11px] font-bold text-[#c46f45] transition hover:bg-[#f8e6d9]">
                            <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                            Detail
                        </a>
                    </div>
                    <div class="grid gap-3 p-4 lg:grid-cols-2">
                        @foreach ($targets as $target)
                            @php
                                $badge = $target['is_overdue'] ? ['text' => 'Overdue', 'class' => 'bg-red-100 text-red-800 ring-red-200'] : ($target['is_today'] ? ['text' => 'Hari Ini', 'class' => 'bg-orange-100 text-orange-800 ring-orange-200'] : ($target['days_left'] >= 0 && $target['days_left'] <= 7 ? ['text' => 'Soon', 'class' => 'bg-amber-100 text-amber-800 ring-amber-200'] : ['text' => 'Upcoming', 'class' => 'bg-slate-100 text-slate-700 ring-slate-200']));
                            @endphp
                            <div class="rounded-[1.4rem] border border-slate-200 bg-slate-50/65 p-4 transition hover:border-[#f1dccb] hover:bg-[#fff8f3]">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-[13px] font-bold leading-5 text-slate-900">{{ $target['label'] }}</div>
                                        <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-slate-600">
                                            <span class="inline-flex items-center gap-1.5"><i data-lucide="calendar" class="h-3.5 w-3.5"></i>{{ $target['date']->format('d M Y') }}</span>
                                            <span class="text-slate-300">•</span>
                                            <span class="font-medium">
                                                @if ($target['is_overdue'])
                                                    {{ abs($target['days_left']) }} hari terlambat
                                                @elseif ($target['is_today'])
                                                    jatuh tempo hari ini
                                                @else
                                                    {{ $target['days_left'] }} hari lagi
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 {{ $badge['class'] }}">{{ $badge['text'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>

                <div class="grid gap-3">
                    <article class="overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h2 class="text-[15px] font-black text-slate-900">Kalender Target</h2>
                            <p class="mt-1 text-[12px] text-slate-500">{{ $calendarMonth->translatedFormat('F Y') }}</p>
                        </div>
                        <div class="overflow-x-auto p-3">
                            <table class="min-w-full border-separate border-spacing-1.5">
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
                                                    $hasTarget = $targets->contains(fn ($item) => $item['date_str'] === $dateKey);
                                                    $isTodayDate = $cursor->isToday();
                                                @endphp
                                                <td class="align-top">
                                                    <div class="h-[72px] rounded-2xl border px-2 py-2 {{ $inMonth ? 'border-slate-200 bg-white' : 'border-transparent bg-slate-50' }} {{ $isTodayDate ? 'ring-2 ring-amber-300' : '' }}">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-[10px] font-bold {{ $inMonth ? 'text-slate-800' : 'text-slate-300' }}">{{ $inMonth ? $cursor->day : '' }}</span>
                                                            @if ($hasTarget)
                                                                <span class="inline-flex h-2.5 w-2.5 rounded-full {{ $isTodayDate ? 'bg-[#efbc7d]' : 'bg-[#d88452]' }}"></span>
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
                        </div>
                    </article>

                </div>
            </section>
        </div>
