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
                            $isFinished = (bool) ($notification['is_finished'] ?? false);
                        @endphp

                        <article class="flex h-full flex-col overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                            <div class="bg-gradient-to-r from-[#ca642f] to-[#e18e4d] px-4 py-3 text-white">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-[8px] font-semibold uppercase tracking-[0.14em] text-white/80">Order / Notifikasi</div>
                                        <div class="mt-1 truncate text-[13px] font-black">
                                            <i data-lucide="file-text" class="mr-1 inline h-4 w-4"></i>{{ $notification['nomor_order'] }}
                                        </div>
                                        @if (! empty($notification['notification_number']))
                                            <div class="mt-1 truncate text-[15px] font-black">
                                                <i data-lucide="bell-ring" class="mr-1 inline h-4 w-4"></i>{{ $notification['notification_number'] }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="text-right">
                                        <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-white/80">Prioritas</div>
                                        <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] {{ $priorityBadgeClasses($notification['priority']) }}">
                                            {{ $notification['priority'] }}
                                        </span>
                                        @if (! empty($notification['jobwaiting_since']))
                                            <div class="mt-3 text-[10px] font-medium text-white/80">
                                                {{ $notification['jobwaiting_since'] }}
                                            </div>
                                        @endif
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
                                    <form method="POST" action="{{ route('pkm.jobwaiting.update', ['order' => $notification['nomor_order']]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="start_progress" value="1">
                                        <input type="hidden" name="_filter_priority" value="{{ $selectedPriority }}">
                                        <input type="hidden" name="_filter_search" value="{{ $search }}">
                                        <input type="hidden" name="_filter_page" value="{{ $notifications->currentPage() }}">
                                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-amber-500 px-3 py-2 text-[11px] font-bold text-white transition hover:bg-amber-600 {{ ($started || $isFinished) ? 'opacity-50' : '' }}" @disabled($started || $isFinished)>
                                            {{ $isFinished ? 'Selesai' : ($started ? 'Dimulai' : 'Start') }}
                                        </button>
                                    </form>
                                </div>

                                <button type="button" class="pkm-jobwaiting-toggle inline-flex items-center gap-2 text-[11px] font-bold text-[#ca642f]" data-target="details-{{ $notification['nomor_order'] }}">
                                    Show details
                                    <i data-lucide="chevron-down" class="h-3.5 w-3.5"></i>
                                </button>

                                <form id="details-{{ $notification['nomor_order'] }}" method="POST" action="{{ route('pkm.jobwaiting.update', ['order' => $notification['nomor_order']]) }}" class="mt-3 hidden space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="progress_pekerjaan" value="{{ $notification['progress'] }}" class="pkm-progress-hidden">
                                    <input type="hidden" name="_filter_priority" value="{{ $selectedPriority }}">
                                    <input type="hidden" name="_filter_search" value="{{ $search }}">
                                    <input type="hidden" name="_filter_page" value="{{ $notifications->currentPage() }}">

                                    <div>
                                        <div class="mb-1 flex items-center justify-between text-[11px] text-slate-500">
                                            <span>Progress</span>
                                            <span id="slider-value-{{ $notification['nomor_order'] }}" class="font-bold text-slate-700">{{ $notification['progress'] }}%</span>
                                        </div>
                                        <input type="range" min="0" max="100" step="1" value="{{ $notification['progress'] }}" class="pkm-range w-full accent-[#ca642f]" data-value-target="slider-value-{{ $notification['nomor_order'] }}" @disabled(! $started)>
                                        @unless ($started)
                                            <div class="mt-1 text-[10px] font-medium text-amber-700">Klik Start dulu supaya progress bisa digeser.</div>
                                        @endunless
                                    </div>

                                    <div>
                                        <div class="mb-1 flex items-center justify-between gap-3">
                                            <label class="block text-[11px] font-semibold text-slate-500">Estimasi Penyelesaian</label>
                                            <span
                                                class="pkm-estimasi-total inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-semibold text-amber-700"
                                                data-start-date="{{ $notification['jobwaiting_since_raw'] ?? '' }}"
                                                data-target-date="{{ $notification['target_penyelesaian'] ?? '' }}"
                                            >
                                                -
                                            </span>
                                        </div>
                                        <input
                                            type="date"
                                            name="target_penyelesaian"
                                            value="{{ $notification['target_penyelesaian'] }}"
                                            class="pkm-estimasi-date w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none"
                                        >
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
                                        <button type="button" class="pkm-jobwaiting-toggle rounded-xl border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50" data-target="details-{{ $notification['nomor_order'] }}">Close</button>
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

            const updateEstimasiTotal = (form) => {
                if (!form) {
                    return;
                }

                const totalBadge = form.querySelector('.pkm-estimasi-total');
                const dateInput = form.querySelector('.pkm-estimasi-date');

                if (!totalBadge || !dateInput) {
                    return;
                }

                const startDate = totalBadge.dataset.startDate;
                const targetDate = dateInput.value || totalBadge.dataset.targetDate;

                if (!startDate || !targetDate) {
                    totalBadge.textContent = '-';
                    return;
                }

                const start = new Date(`${startDate}T00:00:00`);
                const target = new Date(`${targetDate}T00:00:00`);

                if (Number.isNaN(start.getTime()) || Number.isNaN(target.getTime())) {
                    totalBadge.textContent = '-';
                    return;
                }

                const msPerDay = 24 * 60 * 60 * 1000;
                const diffDays = Math.floor((target - start) / msPerDay) + 1;

                totalBadge.textContent = diffDays > 0 ? `Total ${diffDays} hari` : 'Tanggal tidak valid';
            };

            document.addEventListener('input', function (event) {
                if (! event.target.classList.contains('pkm-estimasi-date')) {
                    return;
                }

                const form = event.target.closest('form');

                if (!form) {
                    return;
                }

                updateEstimasiTotal(form);
            });

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('form[id^="details-"]').forEach((form) => {
                    updateEstimasiTotal(form);
                });

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
