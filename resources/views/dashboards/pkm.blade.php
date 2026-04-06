<x-layouts.pkm :title="$pageTitle . ' - PKM'">
    @php
        $isDashboardPage = request()->routeIs('pkm.dashboard');
        $isJobWaitingPage = request()->routeIs('pkm.jobwaiting');
        $isLhppIndexPage = request()->routeIs('pkm.lhpp.index');
        $isLhppCreatePage = request()->routeIs('pkm.lhpp.create');
    @endphp

    @if (session('status'))
        <div id="pkm-jobwaiting-status-alert" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    @if ($isDashboardPage)
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
    @elseif ($isJobWaitingPage)
        @php
            $selectedPriority = trim((string) ($selectedPriority ?? request('priority')));
            $search = trim((string) ($search ?? request('search')));

            if (! isset($notifications)) {
                $notificationsCollection = collect();
                $notifications = new \Illuminate\Pagination\LengthAwarePaginator(
                    $notificationsCollection,
                    0,
                    8,
                    1,
                    ['path' => url()->current(), 'query' => request()->query()]
                );
            }

            $priorityBadgeClasses = static fn (string $priority): string => match ($priority) {
                'Urgently' => 'bg-red-700 text-white animate-pulse',
                'Hard' => 'bg-amber-300 text-amber-950',
                'Medium' => 'bg-blue-200 text-blue-900',
                'Low' => 'bg-emerald-200 text-emerald-900',
                default => 'bg-slate-200 text-slate-700',
            };

            $approvalLabel = static fn (?string $status): array => match ($status) {
                'setuju' => ['label' => 'Disetujui Bengkel', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                'tidak_setuju' => ['label' => 'Target Ditolak', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
                default => ['label' => 'Menunggu Persetujuan', 'class' => 'bg-slate-50 text-slate-600 border-slate-200'],
            };

            $documentTone = static fn (string $tone, bool $ready): string => match ($tone) {
                'rose' => $ready ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-slate-200 bg-slate-50 text-slate-400',
                'emerald' => $ready ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-400',
                'blue' => $ready ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-slate-50 text-slate-400',
                'orange' => $ready ? 'border-orange-200 bg-orange-50 text-orange-700' : 'border-slate-200 bg-slate-50 text-slate-400',
                'indigo' => $ready ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-slate-50 text-slate-400',
                'violet' => $ready ? 'border-violet-200 bg-violet-50 text-violet-700' : 'border-slate-200 bg-slate-50 text-slate-400',
                default => 'border-slate-200 bg-slate-50 text-slate-400',
            };
        @endphp

        <div class="space-y-5">
            <section class="overflow-hidden rounded-[1.8rem] border border-[#f2dccb] bg-[linear-gradient(135deg,_#ffffff_0%,_#fff9f4_60%,_#fbe8da_100%)] px-5 py-5 text-slate-900 shadow-[0_20px_48px_-34px_rgba(222,119,59,0.34)]">
                <div>
                    <h1 class="text-[2rem] font-black leading-none tracking-tight text-slate-900">List Pekerjaan</h1>
                </div>
            </section>

            <section class="rounded-[1.6rem] border border-slate-200 bg-white p-4 shadow-sm">
                <form method="GET" action="{{ route('pkm.jobwaiting') }}" class="flex flex-col gap-3 xl:flex-row xl:items-center">
                    <div class="flex flex-1 flex-col gap-3 md:flex-row md:items-center">
                        <div class="w-full md:w-[220px]">
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Prioritas</label>
                            <select name="priority" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                <option value="">Semua Prioritas</option>
                                <option value="Urgently" @selected($selectedPriority === 'Urgently')>Urgently</option>
                                <option value="Hard" @selected($selectedPriority === 'Hard')>Hard</option>
                                <option value="Medium" @selected($selectedPriority === 'Medium')>Medium</option>
                                <option value="Low" @selected($selectedPriority === 'Low')>Low</option>
                            </select>
                        </div>

                        <div class="min-w-0 flex-1">
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Pencarian</label>
                            <div class="relative">
                                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nomor order, nama pekerjaan, atau unit..." class="w-full rounded-xl border border-slate-300 py-2 pl-10 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-[#ca642f] focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 xl:justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#ca642f] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#b85b2b]">
                            <i data-lucide="filter" class="h-4 w-4"></i>
                            Filter
                        </button>
                        <a href="{{ route('pkm.jobwaiting') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="text-[12px] text-slate-500">
                    Menampilkan {{ $notifications->firstItem() ?? 0 }} - {{ $notifications->lastItem() ?? 0 }} dari {{ $notifications->total() }} pekerjaan
                </div>
                <div>
                    {{ $notifications->appends(request()->query())->links() }}
                </div>
            </div>

            @if ($notifications->count())
                <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @foreach ($notifications as $notification)
                        @php
                            $approval = $approvalLabel($notification['approval_target']);
                            $started = $notification['progress'] >= 11;
                        @endphp

                        <article class="flex h-full flex-col overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                            <div class="bg-gradient-to-r from-[#ca642f] to-[#e18e4d] px-4 py-3 text-white">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-white/80">Nomor Order</div>
                                        <div class="mt-1 truncate text-[15px] font-black">
                                            <i data-lucide="bell-ring" class="mr-1 inline h-4 w-4"></i>{{ $notification['notification_number'] }}
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-white/80">Prioritas</div>
                                        <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] {{ $priorityBadgeClasses($notification['priority']) }}">
                                            {{ $notification['priority'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-1 flex-col p-4 text-[12px] text-slate-700">
                                <div class="mb-3 flex items-start gap-2">
                                    <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                                        <i data-lucide="pin" class="h-4 w-4"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-[14px] font-bold leading-5 text-slate-900">{{ $notification['job_name'] }}</div>
                                        <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] text-slate-600">
                                            <div class="font-semibold text-slate-700">{{ $notification['seksi'] ?: '-' }}</div>
                                            <div class="mt-1 border-t border-slate-200 pt-1">{{ $notification['unit'] ?: '-' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3 grid grid-cols-2 gap-2">
                                    @foreach ($notification['documents'] as $document)
                                        @if ($document['ready'] && $document['url'])
                                            <a href="{{ $document['url'] }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 rounded-xl border px-3 py-2 text-left text-[11px] font-medium {{ $documentTone($document['tone'], $document['ready']) }}">
                                                <i data-lucide="{{ $document['icon'] }}" class="h-3.5 w-3.5 shrink-0"></i>
                                                <span class="truncate">{{ $document['label'] }}</span>
                                            </a>
                                        @else
                                            <button type="button" class="flex items-center gap-2 rounded-xl border px-3 py-2 text-left text-[11px] font-medium {{ $documentTone($document['tone'], $document['ready']) }}">
                                                <i data-lucide="{{ $document['icon'] }}" class="h-3.5 w-3.5 shrink-0"></i>
                                                <span class="truncate">{{ $document['label'] }} -</span>
                                            </button>
                                        @endif
                                    @endforeach
                                </div>

                                <div class="mb-3 rounded-xl border px-3 py-2 text-[11px] {{ $approval['class'] }}">
                                    {{ $approval['label'] }}
                                </div>

                                <div class="mb-3">
                                    <form method="POST" action="{{ route('pkm.jobwaiting.update', ['order' => $notification['notification_number']]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="start_progress" value="1">
                                        <input type="hidden" name="_filter_priority" value="{{ $selectedPriority }}">
                                        <input type="hidden" name="_filter_search" value="{{ $search }}">
                                        <input type="hidden" name="_filter_page" value="{{ $notifications->currentPage() }}">
                                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-amber-500 px-3 py-2 text-[11px] font-bold text-white transition hover:bg-amber-600 {{ $started ? 'opacity-50' : '' }}" @disabled($started)>
                                            {{ $started ? 'Dimulai' : 'Start' }}
                                        </button>
                                    </form>
                                </div>

                                <button type="button" class="pkm-jobwaiting-toggle inline-flex items-center gap-2 text-[11px] font-bold text-[#ca642f]" data-target="details-{{ $notification['notification_number'] }}">
                                    Show details
                                    <i data-lucide="chevron-down" class="h-3.5 w-3.5"></i>
                                </button>

                                <form id="details-{{ $notification['notification_number'] }}" method="POST" action="{{ route('pkm.jobwaiting.update', ['order' => $notification['notification_number']]) }}" class="mt-3 hidden space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="progress_pekerjaan" value="{{ $notification['progress'] }}" class="pkm-progress-hidden">
                                    <input type="hidden" name="_filter_priority" value="{{ $selectedPriority }}">
                                    <input type="hidden" name="_filter_search" value="{{ $search }}">
                                    <input type="hidden" name="_filter_page" value="{{ $notifications->currentPage() }}">

                                    <div>
                                        <div class="mb-1 flex items-center justify-between text-[11px] text-slate-500">
                                            <span>Progress</span>
                                            <span id="slider-value-{{ $notification['notification_number'] }}" class="font-bold text-slate-700">{{ $notification['progress'] }}%</span>
                                        </div>
                                        <input type="range" min="0" max="100" step="1" value="{{ $notification['progress'] }}" class="pkm-range w-full accent-[#ca642f]" data-value-target="slider-value-{{ $notification['notification_number'] }}" @disabled(! $started)>
                                        @unless ($started)
                                            <div class="mt-1 text-[10px] font-medium text-amber-700">Klik Start dulu supaya progress bisa digeser.</div>
                                        @endunless
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-[11px] font-semibold text-slate-500">Target Penyelesaian</label>
                                        <input type="date" name="target_penyelesaian" value="{{ $notification['target_penyelesaian'] }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-[11px] font-semibold text-slate-500">Catatan Anda</label>
                                        <textarea name="catatan" rows="2" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none" placeholder="Catatan...">{{ $notification['catatan'] }}</textarea>
                                    </div>

                                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
                                        <div class="text-[11px] font-semibold text-slate-500">Catatan dari Admin Bengkel</div>
                                        <div class="mt-1 text-[12px] text-slate-700">{{ $notification['catatan_admin'] }}</div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button type="submit" class="rounded-xl bg-[#ca642f] px-3 py-2 text-[11px] font-bold text-white transition hover:bg-[#b85b2b]">Update</button>
                                        <button type="button" class="pkm-jobwaiting-toggle rounded-xl border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50" data-target="details-{{ $notification['notification_number'] }}">Close</button>
                                    </div>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </section>
            @else
                <section class="rounded-[1.6rem] border border-slate-200 bg-white px-6 py-10 text-center shadow-sm">
                    <div class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                        <i data-lucide="inbox" class="h-5 w-5"></i>
                    </div>
                    <div class="mt-3 text-[15px] font-black text-slate-900">Tidak ada pekerjaan yang menunggu</div>
                    <div class="mt-2 text-[13px] text-slate-500">Coba ubah filter prioritas atau kata kunci pencarian.</div>
                </section>
            @endif

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="text-[12px] text-slate-500">
                    Menampilkan {{ $notifications->firstItem() ?? 0 }} - {{ $notifications->lastItem() ?? 0 }} dari {{ $notifications->total() }} pekerjaan
                </div>
                <div>
                    {{ $notifications->appends(request()->query())->links() }}
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('click', function (event) {
                const toggleButton = event.target.closest('.pkm-jobwaiting-toggle');

                if (! toggleButton) {
                    return;
                }

                const targetId = toggleButton.dataset.target;
                const target = targetId ? document.getElementById(targetId) : null;

                if (! target) {
                    return;
                }

                target.classList.toggle('hidden');
            });

            document.addEventListener('input', function (event) {
                if (! event.target.classList.contains('pkm-range')) {
                    return;
                }

                const valueTarget = event.target.dataset.valueTarget;
                const valueElement = valueTarget ? document.getElementById(valueTarget) : null;
                const form = event.target.closest('form');
                const hiddenInput = form ? form.querySelector('.pkm-progress-hidden') : null;

                if (valueElement) {
                    valueElement.textContent = `${event.target.value}%`;
                }

                if (hiddenInput) {
                    hiddenInput.value = event.target.value;
                }
            });

            document.addEventListener('DOMContentLoaded', function () {
                const statusAlert = document.getElementById('pkm-jobwaiting-status-alert');

                if (statusAlert?.dataset.message && window.Swal) {
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: statusAlert.dataset.message,
                        timer: 1800,
                        showConfirmButton: false,
                    });
                }
            });
        </script>
    @elseif ($isLhppIndexPage)
        @php
            $baseSel = 'min-h-[26px] text-[10px] leading-[1.3] px-2 pr-9 rounded-[6px] appearance-none focus:ring-1 truncate';
            $baseBtn = 'min-h-[26px] text-[10px] leading-[1.3] px-3 rounded-[6px]';

            $selOrange = $baseSel.' bg-orange-100 text-orange-800 border border-orange-300 focus:ring-orange-400 focus:border-orange-400';
            $selBlue = $baseSel.' bg-sky-100 text-sky-800 border border-sky-300 focus:ring-sky-400 focus:border-sky-400';
            $selSlate = $baseSel.' bg-slate-100 text-slate-800 border border-slate-300 focus:ring-slate-400 focus:border-slate-400';
            $btnPrimary = $baseBtn.' bg-[#ca642f] text-white hover:bg-[#b85b2b]';
            $btnGhost = $baseBtn.' border border-slate-300 text-slate-700 hover:bg-slate-50';

            $filters = [
                'search' => trim((string) request('search')),
                'unit_kerja' => trim((string) request('unit_kerja')),
                'purchase_order_number' => trim((string) request('purchase_order_number')),
                'termin_status' => trim((string) request('termin_status', 'all')),
            ];

            $sampleRows = collect([
                (object) [
                    'notification_number' => '17409873',
                    'purchase_order_number' => 'PO-0328239',
                    'unit_kerja' => 'Unit of Elins Maintenance 1',
                    'seksi' => 'Section of Crusher Elins Maintenance',
                    'tanggal_selesai' => '2026-04-01',
                    'waktu_pengerjaan' => 8,
                    'total_biaya' => 3400000,
                    'termin1' => 'sudah',
                    'termin2' => 'belum',
                    'manager_signature_requesting' => 'signed',
                    'manager_signature_requesting_user_id' => 7,
                    'manager_signature' => 'signed',
                    'manager_signature_user_id' => 2,
                    'manager_pkm_signature' => null,
                    'manager_pkm_signature_user_id' => null,
                ],
                (object) [
                    'notification_number' => '1743535',
                    'purchase_order_number' => 'PO-0328240',
                    'unit_kerja' => 'Unit of Cement Production',
                    'seksi' => 'Section of Bulk Cement Operation',
                    'tanggal_selesai' => '2026-04-03',
                    'waktu_pengerjaan' => 4,
                    'total_biaya' => 7850000,
                    'termin1' => 'belum',
                    'termin2' => 'belum',
                    'manager_signature_requesting' => 'signed',
                    'manager_signature_requesting_user_id' => 9,
                    'manager_signature' => null,
                    'manager_signature_user_id' => null,
                    'manager_pkm_signature' => null,
                    'manager_pkm_signature_user_id' => null,
                ],
                (object) [
                    'notification_number' => '17410201',
                    'purchase_order_number' => 'PO-0328247',
                    'unit_kerja' => 'Packing Plant',
                    'seksi' => 'Section of Packing Plant Operation',
                    'tanggal_selesai' => '2026-04-05',
                    'waktu_pengerjaan' => 6,
                    'total_biaya' => 12800000,
                    'termin1' => 'sudah',
                    'termin2' => 'sudah',
                    'manager_signature_requesting' => 'signed',
                    'manager_signature_requesting_user_id' => 5,
                    'manager_signature' => 'signed',
                    'manager_signature_user_id' => 3,
                    'manager_pkm_signature' => 'signed',
                    'manager_pkm_signature_user_id' => 4,
                ],
            ]);

            $filteredRows = $sampleRows
                ->filter(function ($row) use ($filters) {
                    $searchHaystack = strtolower(implode(' ', [
                        $row->notification_number,
                        $row->purchase_order_number,
                        $row->unit_kerja,
                        $row->seksi,
                    ]));

                    if ($filters['search'] !== '' && ! str_contains($searchHaystack, strtolower($filters['search']))) {
                        return false;
                    }

                    if ($filters['unit_kerja'] !== '' && $row->unit_kerja !== $filters['unit_kerja']) {
                        return false;
                    }

                    if ($filters['purchase_order_number'] !== '' && $row->purchase_order_number !== $filters['purchase_order_number']) {
                        return false;
                    }

                    return match ($filters['termin_status']) {
                        't1_paid' => $row->termin1 === 'sudah',
                        't1_unpaid' => $row->termin1 !== 'sudah',
                        't2_paid' => $row->termin2 === 'sudah',
                        't2_unpaid' => $row->termin2 !== 'sudah',
                        default => true,
                    };
                })
                ->values();

            $units = $sampleRows->pluck('unit_kerja')->unique()->values();
            $pos = $sampleRows->pluck('purchase_order_number')->unique()->values();

            $perPage = 8;
            $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
            $currentItems = $filteredRows->slice(($currentPage - 1) * $perPage, $perPage)->values();
            $lhpps = new \Illuminate\Pagination\LengthAwarePaginator(
                $currentItems,
                $filteredRows->count(),
                $perPage,
                $currentPage,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );

            $activeTokens = collect();
        @endphp

        <div class="space-y-5">
            <section class="overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white px-5 py-5 text-slate-900 shadow-sm">
                <h1 class="text-[2rem] font-black leading-none tracking-tight text-slate-900">BAST / LHPP</h1>
            </section>

            <div class="rounded-[1.6rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-[13px] font-bold text-slate-900">Daftar LHPP Kontrak PKM</h2>
                        <p class="mt-1 text-[11px] text-slate-500">Monitoring laporan hasil pekerjaan per notifikasi dan kontrak PKM.</p>
                    </div>

                    <a href="{{ route('pkm.lhpp.create') }}"
                        class="{{ $btnPrimary }} inline-flex items-center gap-2 rounded-md px-3 py-2 text-[12px] font-semibold shadow-sm transition">
                        <i data-lucide="plus-circle" class="h-3.5 w-3.5"></i>
                        Buat BAST Termin 1
                    </a>
                </div>

                <form action="{{ route('pkm.lhpp.index') }}" method="GET" class="flex flex-wrap items-center gap-2 overflow-x-auto whitespace-nowrap">
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-2 top-1/2 h-3 w-3 -translate-y-1/2 text-orange-500"></i>
                        <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Cari Nomor Notif / PO / Unit..." class="{{ $selOrange }} w-64 pl-6" />
                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-orange-600">⌕</span>
                    </div>

                    <div class="relative">
                        <select name="unit_kerja" class="{{ $selBlue }} w-48">
                            <option value="">Semua Unit Kerja</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit }}" @selected($filters['unit_kerja'] === $unit)>{{ \Illuminate\Support\Str::limit($unit, 40) }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-sky-700">▾</span>
                    </div>

                    <div class="relative">
                        <select name="purchase_order_number" class="{{ $selSlate }} w-52">
                            <option value="">Semua Nomor PO</option>
                            @foreach ($pos as $po)
                                <option value="{{ $po }}" @selected($filters['purchase_order_number'] === $po)>{{ $po }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-700">▾</span>
                    </div>

                    <div class="relative">
                        <select name="termin_status" class="{{ $selSlate }} w-52">
                            <option value="all" @selected($filters['termin_status'] === 'all')>Semua Status Termin</option>
                            <option value="t1_paid" @selected($filters['termin_status'] === 't1_paid')>Termin 1 - Sudah</option>
                            <option value="t1_unpaid" @selected($filters['termin_status'] === 't1_unpaid')>Termin 1 - Belum</option>
                            <option value="t2_paid" @selected($filters['termin_status'] === 't2_paid')>Termin 2 - Sudah</option>
                            <option value="t2_unpaid" @selected($filters['termin_status'] === 't2_unpaid')>Termin 2 - Belum</option>
                        </select>
                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-700">▾</span>
                    </div>

                    <button type="submit" class="{{ $btnPrimary }} ml-auto inline-flex items-center rounded-md">
                        <i data-lucide="filter" class="mr-1 h-3 w-3"></i>
                        Terapkan
                    </button>
                    <a href="{{ route('pkm.lhpp.index') }}" class="{{ $btnGhost }} inline-flex items-center rounded-md">
                        <i data-lucide="rotate-ccw" class="mr-1 h-3 w-3"></i>
                        Reset
                    </a>
                </form>
            </div>

            <div class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-slate-200 text-[11px] text-slate-800">
                        <thead class="border-b border-slate-200 bg-slate-50 uppercase text-slate-600">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Order / PO</th>
                                <th class="px-3 py-2 text-left font-semibold">Unit Kerja</th>
                                <th class="px-3 py-2 text-left font-semibold">Tanggal Selesai</th>
                                <th class="px-3 py-2 text-right font-semibold">Total Biaya</th>
                                <th class="px-3 py-2 text-left font-semibold">Status LHPP</th>
                                <th class="px-3 py-2 text-left font-semibold">Status Payment</th>
                                <th class="px-3 py-2 text-center font-semibold w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($lhpps as $row)
                                @php
                                    $t1 = $row->termin1 ?? null;
                                    $t2 = $row->termin2 ?? null;

                                    $hasUserSign = ! empty($row->manager_signature_requesting) || ! empty($row->manager_signature_requesting_user_id);
                                    $hasWsSign = ! empty($row->manager_signature) || ! empty($row->manager_signature_user_id);
                                    $hasPkmSign = ! empty($row->manager_pkm_signature) || ! empty($row->manager_pkm_signature_user_id);

                                    if (! $hasUserSign && ! $hasWsSign && ! $hasPkmSign) {
                                        $signStage = 'waiting_user';
                                    } elseif ($hasUserSign && ! $hasWsSign && ! $hasPkmSign) {
                                        $signStage = 'waiting_workshop';
                                    } elseif ($hasUserSign && $hasWsSign && ! $hasPkmSign) {
                                        $signStage = 'waiting_pkm';
                                    } elseif ($hasUserSign && $hasWsSign && $hasPkmSign) {
                                        $signStage = 'completed';
                                    } else {
                                        $signStage = 'partial';
                                    }

                                    $signLabel = match ($signStage) {
                                        'waiting_user' => 'Menunggu TTD Manager User',
                                        'waiting_workshop' => 'Menunggu TTD Manager Workshop',
                                        'waiting_pkm' => 'Menunggu TTD Manager PKM',
                                        'completed' => 'Dokumen Telah di Tandatangani',
                                        'partial' => 'Proses Tanda Tangan',
                                        default => 'Proses Tanda Tangan',
                                    };

                                    $signClr = match ($signStage) {
                                        'waiting_user' => 'bg-slate-100 text-slate-800 ring-slate-200',
                                        'waiting_workshop' => 'bg-amber-100 text-amber-800 ring-amber-200',
                                        'waiting_pkm' => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
                                        'completed' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
                                        'partial' => 'bg-sky-100 text-sky-800 ring-sky-200',
                                        default => 'bg-slate-100 text-slate-800 ring-slate-200',
                                    };

                                    $key = (string) $row->notification_number;
                                    $tok = $activeTokens->get($key);
                                    $hasTok = (bool) $tok;
                                    $isExpired = $hasTok && $tok->expires_at && $tok->expires_at->isPast();
                                @endphp

                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-3 py-2">
                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-[10px] text-slate-600 shadow-sm">
                                            <div class="font-semibold leading-tight text-slate-900">{{ $row->notification_number }}</div>
                                            <div class="mt-1 border-t border-slate-200 pt-1 leading-tight">
                                                <span class="font-medium text-slate-700">{{ $row->purchase_order_number ?? '-' }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-3 py-2">
                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-[10px] text-slate-600 shadow-sm">
                                            <div class="font-semibold leading-tight text-slate-700">{{ $row->seksi ?: '-' }}</div>
                                            <div class="mt-1 border-t border-slate-200 pt-1 leading-tight">{{ $row->unit_kerja ?: '-' }}</div>
                                        </div>
                                    </td>

                                    <td class="px-3 py-2">
                                        @if ($row->tanggal_selesai)
                                            {{ \Carbon\Carbon::parse($row->tanggal_selesai)->format('d-m-Y') }}
                                            ({{ $row->waktu_pengerjaan ? $row->waktu_pengerjaan.' Hari' : '-' }})
                                        @else
                                            <span class="text-[10px] text-slate-400">-</span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2 text-right">
                                        <div class="font-semibold">Rp {{ number_format($row->total_biaya ?? 0, 2, ',', '.') }}</div>
                                    </td>

                                    <td class="px-3 py-2">
                                        <div class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] ring-1 {{ $signClr }}">
                                            <i data-lucide="pen-tool" class="h-3 w-3"></i>
                                            {{ $signLabel }}
                                        </div>

                                        @if ($hasTok && $signStage !== 'completed')
                                            @if (! $isExpired)
                                                <div class="mt-1 flex items-center gap-2 text-[10px]">
                                                    <button type="button" class="copy-next-link inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-slate-700 ring-1 ring-slate-200 hover:bg-slate-200" data-link="{{ route('pkm.lhpp.index') }}">
                                                        <i data-lucide="copy" class="h-3 w-3"></i> Salin Link Approve
                                                    </button>
                                                    <span class="font-medium text-slate-700">kadaluarsa: {{ $tok->expires_at?->format('d/m H:i') }}</span>
                                                </div>
                                            @else
                                                <div class="mt-1 inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-0.5 text-[10px] text-amber-800 ring-1 ring-amber-200">
                                                    <i data-lucide="clock-3" class="h-3 w-3"></i> Token kedaluwarsa
                                                </div>
                                            @endif
                                        @endif
                                    </td>

                                    <td class="px-3 py-2">
                                        <div class="flex flex-col gap-1">
                                            <div>
                                                <span class="text-[10px] text-slate-600">Termin 1:</span>
                                                @if ($t1 === 'sudah')
                                                    <span class="ml-1 inline-block rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] text-emerald-800">Sudah Dibayar</span>
                                                @else
                                                    <span class="ml-1 inline-block rounded-md bg-amber-100 px-2 py-0.5 text-[10px] text-amber-800">Belum Dibayar</span>
                                                @endif
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-slate-600">Termin 2:</span>
                                                @if ($t2 === 'sudah')
                                                    <span class="ml-1 inline-block rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] text-emerald-800">Sudah Dibayar</span>
                                                @else
                                                    <span class="ml-1 inline-block rounded-md bg-amber-100 px-2 py-0.5 text-[10px] text-amber-800">Belum Dibayar</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-3 py-2 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button type="button" class="pkm-lhpp-action-btn bg-emerald-500 hover:bg-emerald-600" title="Edit LHPP">
                                                <i data-lucide="square-pen" class="h-3.5 w-3.5"></i>
                                            </button>
                                            <button type="button" class="pkm-lhpp-action-btn bg-blue-500 hover:bg-blue-600" title="Download PDF LHPP">
                                                <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                            </button>
                                            <button type="button" class="pkm-lhpp-action-btn bg-red-500 hover:bg-red-600 pkm-lhpp-delete-button" title="Hapus LHPP">
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-[11px] text-slate-500">
                                        Belum ada data LHPP.
                                        <a href="{{ route('pkm.lhpp.create') }}" class="text-[#ca642f] underline">Buat LHPP baru</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 px-4 pb-4 text-center text-[10px]">
                    {{ $lhpps->appends(request()->query())->links() }}
                </div>
            </div>
        </div>

        <style>
            .pkm-lhpp-action-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 26px;
                height: 26px;
                border-radius: 6px;
                color: white;
                transition: .2s;
            }

            .pkm-lhpp-table th,
            .pkm-lhpp-table td {
                white-space: nowrap;
            }
        </style>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                function copyTextToClipboard(text) {
                    if (navigator.clipboard && window.isSecureContext) {
                        return navigator.clipboard.writeText(text);
                    }

                    const temp = document.createElement('textarea');
                    temp.value = text;
                    temp.setAttribute('readonly', '');
                    temp.style.position = 'absolute';
                    temp.style.left = '-9999px';
                    document.body.appendChild(temp);
                    temp.select();
                    temp.setSelectionRange(0, temp.value.length);
                    const ok = document.execCommand('copy');
                    document.body.removeChild(temp);

                    return ok ? Promise.resolve() : Promise.reject();
                }

                document.querySelectorAll('.copy-next-link').forEach((button) => {
                    button.addEventListener('click', (event) => {
                        const link = event.currentTarget.getAttribute('data-link');

                        if (! link) {
                            return;
                        }

                        copyTextToClipboard(link).then(() => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Tersalin',
                                text: 'Link approval LHPP disalin',
                                timer: 1500,
                                showConfirmButton: false,
                            });
                        }).catch(() => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: 'Tidak dapat menyalin link',
                            });
                        });
                    });
                });

                document.querySelectorAll('.pkm-lhpp-delete-button').forEach((button) => {
                    button.addEventListener('click', (event) => {
                        event.preventDefault();
                        Swal.fire({
                            title: 'Hapus LHPP ini?',
                            text: 'Front-end only dulu, aksi hapus belum dihubungkan.',
                            icon: 'warning',
                            confirmButtonText: 'OK',
                        });
                    });
                });
            });
        </script>
    @elseif ($isLhppCreatePage)
        @php
            $bastDate = now()->format('Y-m-d');
            $bastOrderOptions = collect($bastOrderOptions ?? []);
            $selectedBastOrder = (string) ($selectedBastOrder ?? '');
            $materialRows = collect([
                ['name' => 'Plate liner chute', 'volume' => '', 'unit_price' => '', 'amount' => '0'],
                ['name' => 'Support frame fabrikasi', 'volume' => '', 'unit_price' => '', 'amount' => '0'],
                ['name' => '', 'volume' => '', 'unit_price' => '', 'amount' => '0'],
            ]);
            $serviceRows = collect([
                ['name' => 'Jasa instalasi support frame', 'volume' => '', 'unit_price' => '', 'amount' => '0'],
                ['name' => 'Jasa alignment chute', 'volume' => '', 'unit_price' => '', 'amount' => '0'],
                ['name' => '', 'volume' => '', 'unit_price' => '', 'amount' => '0'],
            ]);
        @endphp

        <div class="space-y-5">
            <section class="overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white px-5 py-5 text-slate-900 shadow-sm">
                <h1 class="text-[2rem] font-black leading-none tracking-tight text-slate-900">Buat BAST Termin 1</h1>
            </section>

            <section
                x-data="{
                    approvalThreshold: 'under_250',
                    orderOptions: @js($bastOrderOptions->values()->all()),
                    selectedOrder: @js($selectedBastOrder),
                    materialRows: @js($materialRows->values()->all()),
                    serviceRows: @js($serviceRows->values()->all()),
                    currentOrder() {
                        return this.orderOptions.find((item) => item.nomor_order === this.selectedOrder) ?? {
                            nomor_order: '',
                            deskripsi_pekerjaan: '',
                            unit_kerja_peminta: '',
                            unit_kerja: '',
                            seksi: '',
                            purchase_order_number: '',
                            nilai_ece: 0,
                        };
                    },
                    formatCurrency(value) {
                        const amount = Number(value || 0);
                        return new Intl.NumberFormat('id-ID').format(amount);
                    },
                    addMaterialRow() {
                        this.materialRows.push({ name: '', volume: '', unit_price: '', amount: '0' });
                    },
                    addServiceRow() {
                        this.serviceRows.push({ name: '', volume: '', unit_price: '', amount: '0' });
                    }
                }"
                class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4">
                    <div>
                        <h2 class="text-[16px] font-black text-slate-900">Form BAST Termin 1</h2>
                        <p class="mt-1 text-[12px] text-slate-500">Versi front-end ini mengikuti struktur dokumen asli, tapi saya buat lebih nyaman untuk input di web.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <select x-model="approvalThreshold" class="appearance-none rounded-xl border border-slate-300 bg-white py-2 pl-4 pr-10 text-[12px] font-bold text-slate-700 transition focus:border-[#ca642f] focus:outline-none">
                                <option value="under_250">Dibawah 250 JT</option>
                                <option value="over_250">Diatas 250 JT</option>
                            </select>
                            <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                        </div>
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#ca642f] px-4 py-2 text-[12px] font-bold text-white transition hover:bg-[#b85b2b]">
                            <i data-lucide="save" class="h-4 w-4"></i>
                            Simpan
                        </button>
                    </div>
                </div>

                <form class="mt-5 space-y-5">
                    <div class="grid gap-4 xl:grid-cols-[1.42fr_0.58fr]">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="grid gap-3 sm:grid-cols-[190px_16px_minmax(0,1fr)]">
                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Tanggal BAST</label>
                                <div aria-hidden="true"></div>
                                <input type="date" value="{{ $bastDate }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Nomor Order</label>
                                <div aria-hidden="true"></div>
                                <div class="relative">
                                    <select x-model="selectedOrder" class="w-full appearance-none rounded-xl border border-slate-300 bg-white px-3 py-2 pr-10 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                        <option value="">Pilih Nomor Order</option>
                                        <template x-for="order in orderOptions" :key="order.nomor_order">
                                            <option :value="order.nomor_order" x-text="order.nomor_order"></option>
                                        </template>
                                    </select>
                                    <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                                </div>

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Deskripsi Pekerjaan</label>
                                <div aria-hidden="true"></div>
                                <input type="text" x-bind:value="currentOrder().deskripsi_pekerjaan" readonly class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Unit Kerja Peminta (User)</label>
                                <div aria-hidden="true"></div>
                                <input type="text" x-bind:value="currentOrder().unit_kerja_peminta" readonly class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Purchasing Order (P.O)</label>
                                <div aria-hidden="true"></div>
                                <input type="text" x-bind:value="currentOrder().purchase_order_number" readonly class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Tanggal Dimulainya Pekerjaan</label>
                                <div aria-hidden="true"></div>
                                <input type="date" value="2026-03-29" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Tanggal Selesainya Pekerjaan</label>
                                <div aria-hidden="true"></div>
                                <input type="date" value="2026-04-06" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                            <div class="rounded-2xl border border-slate-300 bg-slate-50 p-3">
                                <div class="text-center text-[11px] font-black uppercase tracking-[0.14em] text-slate-700">Nilai ECE (Rupiah)</div>
                                <div class="mt-3 rounded-xl border border-slate-300 bg-white px-3 py-3 text-right text-[16px] font-black text-slate-900" x-text="formatCurrency(currentOrder().nilai_ece)"></div>
                            </div>

                            <div class="mt-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500">Flow Approval</div>
                                        <div class="mt-1 text-[13px] font-black text-slate-900">BAST Termin 1</div>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-1 text-[10px] font-bold text-[#ca642f] ring-1 ring-orange-200" x-text="approvalThreshold === 'over_250' ? 'Diatas 250 JT' : 'Dibawah 250 JT'"></span>
                                </div>

                                <div class="mt-3 space-y-2.5">
                                    <div class="flex items-start gap-2.5">
                                        <div class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[10px] font-black text-slate-700">1</div>
                                        <div class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                            <div class="text-[11px] font-bold text-slate-900">Manager Peminta</div>
                                        </div>
                                    </div>

                                    <div class="ml-3 h-3 w-px bg-slate-300"></div>

                                    <div class="flex items-start gap-2.5">
                                        <div class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[10px] font-black text-slate-700">2</div>
                                        <div class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                            <div class="text-[11px] font-bold text-slate-900">Manager Pengendali</div>
                                        </div>
                                    </div>

                                    <div class="ml-3 h-3 w-px bg-slate-300"></div>

                                    <div class="flex items-start gap-2.5">
                                        <div class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[10px] font-black text-slate-700">3</div>
                                        <div class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                            <div class="text-[11px] font-bold text-slate-900">GM Pengendali</div>
                                        </div>
                                    </div>

                                    <template x-if="approvalThreshold === 'over_250'">
                                        <div>
                                            <div class="ml-3 h-3 w-px bg-slate-300"></div>
                                            <div class="flex items-start gap-2.5">
                                                <div class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#fde9db] text-[10px] font-black text-[#ca642f]">4</div>
                                                <div class="min-w-0 flex-1 rounded-xl border border-orange-200 bg-orange-50 px-3 py-2">
                                                    <div class="text-[11px] font-bold text-[#9a4f28]">Dirops</div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                            <div class="text-[13px] font-bold text-slate-900">Aktual Pemakaian Material</div>
                            <button type="button" @click="addMaterialRow()" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50">
                                <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                Tambah Baris
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse text-[11px] text-slate-800">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="w-[52px] border border-slate-300 px-2 py-2 text-center font-bold">No.</th>
                                        <th class="border border-slate-300 px-2 py-2 text-left font-bold">A. Aktual Pemakaian Material</th>
                                        <th class="w-[180px] border border-slate-300 px-2 py-2 text-center font-bold">Total Durasi / Volume / Luasan Pekerjaan<br><span class="font-medium">(Jam/Kg/M2/CM3/Liter)</span></th>
                                        <th class="w-[150px] border border-slate-300 px-2 py-2 text-center font-bold">Harga Satuan<br><span class="font-medium">(Rp)</span></th>
                                        <th class="w-[170px] border border-slate-300 px-2 py-2 text-center font-bold">Jumlah<br><span class="font-medium">(Rp)</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, index) in materialRows" :key="`material-${index}`">
                                        <tr>
                                            <td class="border border-slate-300 px-2 py-2 text-center align-top font-semibold" x-text="index + 1"></td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" x-model="row.name" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" x-model="row.volume" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-right text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" x-model="row.unit_price" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-right text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" x-model="row.amount" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-right text-sm font-semibold text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                            </td>
                                        </tr>
                                    </template>
                                    <tr class="bg-[#fff7df]">
                                        <td colspan="4" class="border border-slate-300 px-2 py-2 font-bold">SUB TOTAL ( A )</td>
                                        <td class="border border-slate-300 px-2 py-2 text-right font-black">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                            <div class="text-[13px] font-bold text-slate-900">Aktual Biaya Jasa</div>
                            <button type="button" @click="addServiceRow()" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50">
                                <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                Tambah Baris
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse text-[11px] text-slate-800">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="w-[52px] border border-slate-300 px-2 py-2 text-center font-bold">No.</th>
                                        <th class="border border-slate-300 px-2 py-2 text-left font-bold">B. Aktual Biaya Jasa</th>
                                        <th class="w-[180px] border border-slate-300 px-2 py-2 text-center font-bold">Total Durasi / Volume / Luasan Pekerjaan<br><span class="font-medium">(Jam/Kg/M2/CM3/Liter)</span></th>
                                        <th class="w-[150px] border border-slate-300 px-2 py-2 text-center font-bold">Harga Satuan<br><span class="font-medium">(Rp)</span></th>
                                        <th class="w-[170px] border border-slate-300 px-2 py-2 text-center font-bold">Jumlah<br><span class="font-medium">(Rp)</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, index) in serviceRows" :key="`service-${index}`">
                                        <tr>
                                            <td class="border border-slate-300 px-2 py-2 text-center align-top font-semibold" x-text="index + 1"></td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" x-model="row.name" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" x-model="row.volume" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-right text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" x-model="row.unit_price" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-right text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" x-model="row.amount" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-right text-sm font-semibold text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                            </td>
                                        </tr>
                                    </template>
                                    <tr class="bg-[#fff7df]">
                                        <td colspan="4" class="border border-slate-300 px-2 py-2 font-bold">SUB TOTAL ( B )</td>
                                        <td class="border border-slate-300 px-2 py-2 text-right font-black">-</td>
                                    </tr>
                                    <tr class="bg-slate-100">
                                        <td colspan="4" class="border border-slate-300 px-2 py-2 font-black">TOTAL AKTUAL BIAYA ( A + B )</td>
                                        <td class="border border-slate-300 px-2 py-2 text-right font-black">-</td>
                                    </tr>
                                    <tr class="bg-slate-200">
                                        <td colspan="4" class="border border-slate-300 px-2 py-2 font-black">TERMIN 2 (5% x Total Actual Biaya)</td>
                                        <td class="border border-slate-300 px-2 py-2 text-right font-black">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    @else
        <div class="space-y-5">
            <section class="overflow-hidden rounded-[1.8rem] border border-[#f2dccb] bg-[linear-gradient(135deg,_#ffffff_0%,_#fff9f4_60%,_#fbe8da_100%)] px-5 py-5 text-slate-900 shadow-[0_20px_48px_-34px_rgba(222,119,59,0.34)]">
                <h1 class="text-[2rem] font-black leading-none tracking-tight text-slate-900">{{ $pageTitle }}</h1>
            </section>

            <section class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                <article class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-[16px] font-black text-slate-900">Ringkasan Halaman</h2>
                    <p class="mt-2 text-[13px] leading-6 text-slate-600">Halaman ini masih front-end placeholder dan siap dipecah jadi modul PKM yang lebih spesifik saat backend mulai disambungkan.</p>
                </article>
                <article class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-[16px] font-black text-slate-900">Arah Lanjut</h2>
                    <div class="mt-4 rounded-2xl border border-[#f4ddcb] bg-[#fff7f0] px-4 py-3 text-[13px] leading-6 text-slate-600">Kalau tampilan ini sudah cocok, berikutnya kita bisa lanjut bikin backend dan halaman PKM satu per satu.</div>
                </article>
            </section>
        </div>
    @endif
</x-layouts.pkm>
