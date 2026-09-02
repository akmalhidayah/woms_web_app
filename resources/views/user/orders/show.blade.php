<x-layouts.user>
    @php
        $timelineToneClasses = [
            'done' => 'border-emerald-200 bg-emerald-50/80',
            'danger' => 'border-red-200 bg-red-50/80',
            'waiting' => 'border-stone-200 bg-stone-50',
        ];

        $timelineBadgeClasses = [
            'done' => 'bg-emerald-100 text-emerald-700',
            'danger' => 'bg-red-100 text-red-700',
            'waiting' => 'bg-stone-200 text-stone-600',
        ];
        $timelineDotClasses = [
            'done' => 'border-emerald-500 bg-emerald-500 text-white shadow-[0_0_0_6px_rgba(16,185,129,0.12)]',
            'danger' => 'border-red-500 bg-red-500 text-white shadow-[0_0_0_6px_rgba(239,68,68,0.12)]',
            'waiting' => 'border-stone-300 bg-white text-stone-500 shadow-[0_0_0_6px_rgba(231,229,228,0.7)]',
        ];
        $timelineLineClasses = [
            'done' => 'bg-emerald-300',
            'danger' => 'bg-red-200',
            'waiting' => 'bg-stone-200',
        ];
        $documentPreviewItems = collect($order['document_preview_items'] ?? []);
        $availableDocumentPreviewItems = $documentPreviewItems
            ->filter(fn (array $item) => filled($item['url'] ?? null))
            ->values();
        $activeDocumentPreview = $availableDocumentPreviewItems->first();
        $workshop = $order['workshop'] ?? [];
        $workshopPackages = collect($workshop['packages'] ?? []);
        $workshopPics = collect($workshop['pics'] ?? []);
        $targetDateLabel = $order['progress']['target'] ?: $order['target_selesai_order'] ?: '-';
        $targetRangeLabel = null;

        try {
            if (filled($order['tanggal_order']) && $order['tanggal_order'] !== '-' && $targetDateLabel !== '-') {
                $orderDateForRange = \Illuminate\Support\Carbon::createFromFormat('d/m/Y', $order['tanggal_order'])->startOfDay();
                $targetDateForRange = \Illuminate\Support\Carbon::createFromFormat('d/m/Y', $targetDateLabel)->startOfDay();
                $targetRangeDays = (int) $orderDateForRange->diffInDays($targetDateForRange, false);

                $targetRangeLabel = match (true) {
                    $targetRangeDays > 0 => $targetRangeDays . ' hari lagi',
                    $targetRangeDays < 0 => 'Lewat ' . abs($targetRangeDays) . ' hari',
                    default => 'Hari yang sama',
                };
            }
        } catch (\Throwable $exception) {
            $targetRangeLabel = null;
        }
    @endphp

    <div class="space-y-3 lg:-mx-2">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('user.dashboard', request()->query()) }}" class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-red-200 hover:text-red-800">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Kembali ke dashboard
            </a>
            <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-bold ring-1 {{ $order['prioritas_badge_classes'] }}">
                {{ $order['prioritas_label'] }}
            </span>
        </div>

        <section class="overflow-hidden rounded-[18px] border border-stone-200 bg-white shadow-sm">
            <div class="grid gap-0 lg:grid-cols-[1.1fr_0.9fr]">
                <div class="bg-white p-4 sm:p-5">
                    <div class="space-y-3.5">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#7f1017] text-white" title="Tracking Order" aria-label="Tracking Order">
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 5h14v14H5V5Z" stroke="currentColor" stroke-width="2" />
                                    <path d="M8 9h8M8 13h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                            </span>
                            <h1 class="min-w-0 text-xl font-black tracking-tight text-slate-900 sm:text-2xl lg:text-[1.65rem]">{{ $order['nama_pekerjaan'] }}</h1>
                        </div>

                        <div class="grid gap-2.5 sm:grid-cols-3">
                            <div class="rounded-xl border border-stone-200 bg-stone-50/60 p-3">
                                <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Order / Notifikasi</div>
                                <div class="mt-1.5 text-base font-bold text-slate-900">{{ $order['nomor_order'] }}</div>
                                <div class="mt-1 text-xs font-semibold text-slate-500">Notif: {{ $order['notifikasi'] ?: '-' }}</div>
                            </div>
                            <div class="rounded-xl border border-stone-200 bg-stone-50/60 p-3">
                                <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Unit Kerja</div>
                                <div class="mt-1.5 text-base font-bold text-slate-900">{{ $order['unit_kerja'] ?: '-' }}</div>
                            </div>
                            <div class="rounded-xl border border-stone-200 bg-stone-50/60 p-3">
                                <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Seksi</div>
                                <div class="mt-1.5 text-base font-bold text-slate-900">{{ $order['seksi'] ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 sm:p-5">
                    <div class="grid gap-2.5 sm:grid-cols-2">
                        <div class="rounded-xl border border-stone-200 bg-stone-50/70 p-3">
                            <div class="flex items-start gap-2.5">
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-[#7f1017] ring-1 ring-red-100" aria-hidden="true">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Tanggal Order</div>
                                    <div class="mt-1 text-base font-bold text-slate-900">{{ $order['tanggal_order'] ?: '-' }}</div>
                                    <div class="mt-2 text-sm font-semibold leading-5 text-slate-900">{{ $order['approval_label'] }}</div>
                                    <div class="mt-0.5 text-xs leading-5 text-slate-600">{{ $order['approval_note'] ?: '-' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-stone-200 bg-stone-50/70 p-3">
                            <div class="flex items-start gap-2.5">
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100" aria-hidden="true">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Target Selesai</div>
                                    <div class="mt-1 text-base font-bold text-slate-900">{{ $targetDateLabel }}</div>
                                    @if ($targetRangeLabel)
                                        <div class="mt-2 inline-flex rounded-full bg-white px-2 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-100">{{ $targetRangeLabel }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if ($order['is_workshop_routed'] ?? false)
            <section class="rounded-[18px] border border-stone-200 bg-white p-3.5 shadow-sm sm:p-4">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#7f1017] text-white" aria-hidden="true">
                        <i data-lucide="hard-hat" class="h-4 w-4"></i>
                    </span>
                    <h2 class="text-lg font-black text-slate-900">Informasi Pekerjaan Bengkel</h2>
                </div>

                <div class="mt-3 grid gap-2.5 sm:grid-cols-3">
                    <div class="rounded-xl border border-stone-200 bg-stone-50/70 p-3">
                        <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Regu</div>
                        <div class="mt-1 text-sm font-black leading-5 text-slate-900">{{ $workshop['regu'] ?: '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-stone-200 bg-stone-50/70 p-3">
                        <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Persiapan Order</div>
                        <div class="mt-1 text-sm font-black leading-5 text-slate-900">{{ $workshop['preparation_label'] ?: 'Belum Memilih Persiapan' }}</div>
                    </div>
                    <div class="rounded-xl border border-stone-200 bg-stone-50/70 p-3">
                        <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Progress Pekerjaan</div>
                        <div class="mt-1 text-sm font-black leading-5 text-slate-900">{{ $workshop['status'] ?: '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-stone-200 bg-stone-50/70 p-3 sm:col-span-3">
                        <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Catatan Persiapan</div>
                        <div class="mt-1 text-sm font-semibold leading-5 text-slate-700">{{ $workshop['preparation_note'] ?: '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-stone-200 bg-stone-50/70 p-3 sm:col-span-3">
                        <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Catatan Progress</div>
                        <div class="mt-1 text-sm font-semibold leading-5 text-slate-700">{{ $workshop['keterangan_progress'] ?: '-' }}</div>
                    </div>
                </div>

                @if ($workshopPackages->isEmpty())
                    <div class="mt-3 rounded-xl border border-stone-200 bg-white p-3">
                        <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">PIC Pekerjaan</div>
                        <div class="mt-2 flex flex-wrap gap-2.5">
                            @forelse ($workshopPics as $pic)
                                <div class="flex items-center gap-2 rounded-xl border border-stone-200 bg-stone-50 px-2.5 py-2">
                                    @if ($pic['avatar_url'])
                                        <img src="{{ $pic['avatar_url'] }}" alt="{{ $pic['name'] }}" class="h-8 w-8 rounded-full object-cover ring-1 ring-stone-200" style="object-position: {{ $pic['avatar_position'] }};" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                        <span style="display:none" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-[10px] font-black text-slate-600 ring-1 ring-stone-200">{{ $pic['initials'] }}</span>
                                    @else
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-[10px] font-black text-slate-600 ring-1 ring-stone-200">{{ $pic['initials'] }}</span>
                                    @endif
                                    <span class="text-sm font-bold text-slate-800">{{ $pic['name'] }}</span>
                                </div>
                            @empty
                                <span class="text-sm font-semibold text-slate-500">Belum ada PIC.</span>
                            @endforelse
                        </div>
                    </div>
                @endif
            </section>

            @if ($workshopPackages->isNotEmpty())
                <section class="rounded-[18px] border border-stone-200 bg-white p-3.5 shadow-sm sm:p-4">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#7f1017] text-white" aria-hidden="true">
                            <i data-lucide="boxes" class="h-4 w-4"></i>
                        </span>
                        <h2 class="text-lg font-black text-slate-900">Pekerjaan Paket</h2>
                    </div>

                    <div class="mt-3 grid gap-2.5 lg:grid-cols-2">
                        @foreach ($workshopPackages as $package)
                            <article class="rounded-xl border border-stone-200 bg-stone-50/70 p-3">
                                <div class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Nama Pekerjaan Paket</div>
                                <div class="mt-1 text-sm font-black leading-5 text-slate-900">{{ $package['name'] }}</div>

                                <div class="mt-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">PIC</div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @forelse ($package['pics'] as $pic)
                                        <div class="flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-2.5 py-2">
                                            @if ($pic['avatar_url'])
                                                <img src="{{ $pic['avatar_url'] }}" alt="{{ $pic['name'] }}" class="h-8 w-8 rounded-full object-cover ring-1 ring-stone-200" style="object-position: {{ $pic['avatar_position'] }};" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                                <span style="display:none" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-[10px] font-black text-slate-600 ring-1 ring-stone-200">{{ $pic['initials'] }}</span>
                                            @else
                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-[10px] font-black text-slate-600 ring-1 ring-stone-200">{{ $pic['initials'] }}</span>
                                            @endif
                                            <span class="text-sm font-bold text-slate-800">{{ $pic['name'] }}</span>
                                        </div>
                                    @empty
                                        <span class="text-sm font-semibold text-slate-500">Belum ada PIC.</span>
                                    @endforelse
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif

        <section class="rounded-[18px] border border-stone-200 bg-white p-3.5 shadow-sm sm:p-4">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#7f1017] text-white" aria-hidden="true">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path d="M4 6h4v4H4V6Zm6 2h10M4 14h4v4H4v-4Zm6 2h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <h2 class="text-lg font-black text-slate-900">Timeline Proses</h2>
            </div>

            <div class="mt-3 pb-1">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    @foreach ($order['timeline'] as $item)
                        <article class="relative rounded-xl border p-2.5 {{ $timelineToneClasses[$item['tone']] ?? $timelineToneClasses['waiting'] }}">
                            @unless ($loop->last || $loop->iteration % 3 === 0)
                                <div class="absolute left-full top-1/2 hidden h-[2px] w-3 -translate-y-1/2 {{ $timelineLineClasses[$item['tone']] ?? $timelineLineClasses['waiting'] }} md:block"></div>
                            @endunless

                            @php
                                $timelineModalPayload = $item['approval'] ?? $item['info'] ?? null;
                                $timelineModalType = isset($item['approval']) ? 'approval' : 'info';
                            @endphp

                            <div class="relative z-10 flex items-start justify-between gap-2.5">
                                <div class="flex min-w-0 items-start gap-2.5">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 {{ $timelineDotClasses[$item['tone']] ?? $timelineDotClasses['waiting'] }}">
                                        @if ($item['tone'] === 'done')
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        @elseif ($item['tone'] === 'danger')
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M12 8v5m0 4h.01M10.3 4.9 2.8 18a1.4 1.4 0 0 0 1.2 2h16a1.4 1.4 0 0 0 1.2-2L13.7 4.9a1.9 1.9 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        @else
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="2" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">{{ $item['label'] }}</div>
                                        <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ $timelineBadgeClasses[$item['tone']] ?? $timelineBadgeClasses['waiting'] }}">
                                            {{ $item['tone'] === 'done' ? 'Selesai' : ($item['tone'] === 'danger' ? 'Perhatian' : 'Pending') }}
                                        </span>
                                    </div>
                                </div>
                                @if (! empty($timelineModalPayload))
                                    <button
                                        type="button"
                                        class="{{ $timelineModalType === 'approval' ? 'approval-flow-trigger' : 'timeline-info-trigger' }} inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:text-[#7f1017] focus:outline-none focus:ring-4 focus:ring-red-100"
                                        data-payload='@json($timelineModalPayload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG)'
                                        aria-label="Lihat detail {{ $item['label'] }}"
                                        title="Lihat detail"
                                    >
                                        <i data-lucide="info" class="h-3.5 w-3.5"></i>
                                    </button>
                                @endif
                            </div>

                            <div class="mt-2.5 text-sm font-bold leading-5 text-slate-900">{{ $item['value'] }}</div>
                            @if (filled($item['detail'] ?? null))
                                <div class="mt-1 text-[11px] font-semibold leading-5 text-slate-500">{{ $item['detail'] }}</div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section>
            <div class="rounded-[18px] border border-stone-200 bg-white p-3 shadow-sm sm:p-4">
                <div class="flex flex-wrap items-center justify-between gap-3 px-1">
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Daftar Dokumen</h2>
                    </div>
                </div>

                <div class="mt-2">
                    <div class="overflow-hidden rounded-[20px] border border-stone-200 bg-white">
                        <div class="grid gap-3 border-b border-stone-200 px-3 py-3 sm:px-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                            <div class="min-w-0 md:max-w-md">
                                <label for="user-document-selector" class="text-[9px] font-bold uppercase tracking-[0.2em] text-slate-400">Pilih Dokumen</label>
                                <select
                                    id="user-document-selector"
                                    class="mt-1.5 w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 outline-none transition focus:border-red-300 focus:ring-4 focus:ring-red-100 disabled:cursor-not-allowed disabled:bg-stone-100 disabled:text-slate-400"
                                    @disabled($availableDocumentPreviewItems->isEmpty())
                                >
                                    @forelse ($availableDocumentPreviewItems as $item)
                                        <option
                                            value="{{ $item['url'] }}"
                                            data-document-title="{{ $item['title'] }}"
                                            data-document-label="{{ $item['label'] }}"
                                            data-document-url="{{ $item['url'] }}"
                                            data-document-available="1"
                                            @selected(($activeDocumentPreview['url'] ?? null) === ($item['url'] ?? null))
                                        >
                                            {{ $item['title'] }}
                                        </option>
                                    @empty
                                        <option value="">Belum ada dokumen tersedia</option>
                                    @endforelse
                                </select>
                            </div>

                            <div class="min-w-0">
                                <h3 id="user-document-preview-title" class="sr-only">
                                    {{ $activeDocumentPreview['title'] ?? 'Dokumen Belum Tersedia' }}
                                </h3>
                                <p id="user-document-preview-label" class="sr-only">
                                    {{ $activeDocumentPreview['label'] ?? 'Belum ada dokumen yang dapat dipreview.' }}
                                </p>
                            </div>
                        </div>

                        <div id="user-document-preview-panel" class="bg-stone-50">
                            <div
                                id="user-document-pdf-toolbar"
                                class="flex flex-wrap items-center gap-2 border-b border-stone-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-700 sm:px-4"
                            >
                                <span id="user-document-toolbar-title" class="min-w-0 flex-1 truncate text-sm font-black text-slate-900">
                                    {{ $activeDocumentPreview['title'] ?? 'Preview Dokumen' }}
                                </span>
                                <button
                                    type="button"
                                    id="user-document-prev-page"
                                    class="inline-flex items-center gap-1 rounded-lg border border-stone-200 bg-white px-2.5 py-1.5 transition hover:border-red-200 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                                    Prev
                                </button>
                                <span id="user-document-page-indicator" class="rounded-lg border border-stone-200 bg-stone-50 px-2.5 py-1.5 text-slate-600">
                                    Halaman 0 / 0
                                </span>
                                <button
                                    type="button"
                                    id="user-document-next-page"
                                    class="inline-flex items-center gap-1 rounded-lg border border-stone-200 bg-white px-2.5 py-1.5 transition hover:border-red-200 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Next
                                    <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                                </button>
                                <button
                                    type="button"
                                    id="user-document-zoom-out"
                                    class="inline-flex items-center rounded-lg border border-stone-200 bg-white px-2.5 py-1.5 transition hover:border-red-200 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Zoom -
                                </button>
                                <span id="user-document-zoom-label" class="rounded-lg border border-stone-200 bg-stone-50 px-2.5 py-1.5 text-slate-600">100%</span>
                                <button
                                    type="button"
                                    id="user-document-zoom-in"
                                    class="inline-flex items-center rounded-lg border border-stone-200 bg-white px-2.5 py-1.5 transition hover:border-red-200 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Zoom +
                                </button>
                                <button
                                    type="button"
                                    id="user-document-fit-width"
                                    class="inline-flex items-center gap-1 rounded-lg border border-stone-200 bg-white px-2.5 py-1.5 transition hover:border-red-200 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <i data-lucide="maximize" class="h-3.5 w-3.5"></i>
                                    Fit Width
                                </button>
                                <div
                                    id="user-document-action-buttons"
                                    class="{{ $activeDocumentPreview ? 'flex' : 'hidden' }} flex-wrap items-center gap-2"
                                >
                                    <a
                                        id="user-document-preview-link"
                                        href="{{ $activeDocumentPreview['url'] ?? '#' }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-stone-200 bg-stone-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-red-200 hover:bg-white hover:text-red-800"
                                    >
                                        <i data-lucide="file-search" class="h-3.5 w-3.5"></i>
                                        Buka Dokumen
                                    </a>
                                    <a
                                        id="user-document-preview-download-link"
                                        href="{{ $activeDocumentPreview['url'] ?? '#' }}"
                                        download
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-stone-200 bg-stone-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-red-200 hover:bg-white hover:text-red-800"
                                    >
                                        <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                        Download
                                    </a>
                                </div>
                            </div>

                            <div class="relative min-h-[520px] overflow-hidden bg-stone-100 lg:min-h-[720px]">
                                <div
                                    id="user-document-empty-state"
                                    class="hidden h-[520px] items-center justify-center px-6 text-center lg:h-[720px]"
                                >
                                    <div>
                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-stone-200 bg-white text-slate-400">
                                            <i data-lucide="files" class="h-5 w-5"></i>
                                        </div>
                                        <div class="mt-4 text-base font-semibold text-slate-700">Belum ada dokumen yang tersedia untuk ditampilkan.</div>
                                    </div>
                                </div>

                                <div
                                    id="user-document-loading-state"
                                    class="hidden h-[520px] items-center justify-center px-6 text-center text-sm font-semibold text-slate-600 lg:h-[720px]"
                                >
                                    Memuat dokumen...
                                </div>

                                <div
                                    id="user-document-error-state"
                                    class="hidden border-b border-stone-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800"
                                >
                                    Preview PDF tidak dapat dimuat. Silakan gunakan tombol Buka Dokumen.
                                </div>

                                <div id="user-document-canvas-wrapper" class="hidden h-[60vh] min-h-[520px] max-w-full overflow-auto p-3 lg:h-[75vh] lg:min-h-[720px] lg:p-5">
                                    <canvas id="user-document-pdf-canvas" class="mx-auto block max-w-full rounded-sm bg-white"></canvas>
                                </div>

                                <iframe
                                    id="user-document-preview-frame"
                                    src="{{ $activeDocumentPreview['url'] ?? 'about:blank' }}"
                                    class="hidden h-[60vh] min-h-[520px] w-full overflow-auto bg-stone-100 lg:h-[75vh] lg:min-h-[720px]"
                                    title="Preview dokumen order {{ $order['nomor_order'] }}"
                                ></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @include('user.orders.partials.approval-flow-modal')
    @include('user.orders.partials.timeline-info-modal')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const documentSelector = document.getElementById('user-document-selector');
            const previewFrame = document.getElementById('user-document-preview-frame');
            const previewTitle = document.getElementById('user-document-preview-title');
            const previewLabel = document.getElementById('user-document-preview-label');
            const previewOpenLink = document.getElementById('user-document-preview-link');
            const previewDownloadLink = document.getElementById('user-document-preview-download-link');
            const actionButtons = document.getElementById('user-document-action-buttons');
            const toolbar = document.getElementById('user-document-pdf-toolbar');
            const toolbarTitle = document.getElementById('user-document-toolbar-title');
            const prevButton = document.getElementById('user-document-prev-page');
            const nextButton = document.getElementById('user-document-next-page');
            const zoomOutButton = document.getElementById('user-document-zoom-out');
            const zoomInButton = document.getElementById('user-document-zoom-in');
            const fitWidthButton = document.getElementById('user-document-fit-width');
            const pageIndicator = document.getElementById('user-document-page-indicator');
            const zoomLabel = document.getElementById('user-document-zoom-label');
            const emptyState = document.getElementById('user-document-empty-state');
            const loadingState = document.getElementById('user-document-loading-state');
            const errorState = document.getElementById('user-document-error-state');
            const canvasWrapper = document.getElementById('user-document-canvas-wrapper');
            const canvas = document.getElementById('user-document-pdf-canvas');

            if (! documentSelector || ! previewFrame || ! canvas) {
                return;
            }

            const pdfjsLib = window.pdfjsLib;
            const canvasContext = canvas.getContext('2d');
            const controlButtons = [prevButton, nextButton, zoomOutButton, zoomInButton, fitWidthButton];
            let activeDocumentUrl = '';
            let activeRenderKey = 0;
            let loadingTask = null;
            let renderTask = null;
            let pdfDocument = null;
            let currentPage = 1;
            let currentScale = 1;

            if (pdfjsLib) {
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            }

            const showElement = (element, displayClass = 'block') => {
                if (! element) {
                    return;
                }

                element.classList.remove('hidden', 'flex', 'block');
                element.classList.add(displayClass);
            };

            const hideElement = (element) => {
                if (! element) {
                    return;
                }

                element.classList.add('hidden');
                element.classList.remove('flex', 'block');
            };

            const setControlsDisabled = (disabled) => {
                controlButtons.forEach((button) => {
                    if (button) {
                        button.disabled = disabled;
                    }
                });
            };

            const updatePageControls = () => {
                const totalPages = pdfDocument ? pdfDocument.numPages : 0;

                if (pageIndicator) {
                    pageIndicator.textContent = `Halaman ${totalPages ? currentPage : 0} / ${totalPages}`;
                }

                if (zoomLabel) {
                    zoomLabel.textContent = `${Math.round(currentScale * 100)}%`;
                }

                if (prevButton) {
                    prevButton.disabled = ! pdfDocument || currentPage <= 1;
                }

                if (nextButton) {
                    nextButton.disabled = ! pdfDocument || currentPage >= totalPages;
                }

                [zoomOutButton, zoomInButton, fitWidthButton].forEach((button) => {
                    if (button) {
                        button.disabled = ! pdfDocument;
                    }
                });
            };

            const resetCanvas = () => {
                if (! canvasContext) {
                    return;
                }

                canvasContext.clearRect(0, 0, canvas.width || 1, canvas.height || 1);
                canvas.width = 0;
                canvas.height = 0;
            };

            const cancelActiveRender = () => {
                activeRenderKey += 1;

                if (renderTask) {
                    renderTask.cancel();
                    renderTask = null;
                }

                if (loadingTask) {
                    loadingTask.destroy();
                    loadingTask = null;
                }

                pdfDocument = null;
            };

            const showEmptyState = () => {
                hideElement(toolbar);
                hideElement(loadingState);
                hideElement(errorState);
                hideElement(canvasWrapper);
                hideElement(previewFrame);
                showElement(emptyState, 'flex');
                resetCanvas();
                setControlsDisabled(true);
                updatePageControls();
            };

            const showLoadingState = () => {
                showElement(toolbar, 'flex');
                hideElement(emptyState);
                hideElement(errorState);
                hideElement(canvasWrapper);
                hideElement(previewFrame);
                showElement(loadingState, 'flex');
                setControlsDisabled(true);
            };

            const showCanvasState = () => {
                showElement(toolbar, 'flex');
                hideElement(emptyState);
                hideElement(loadingState);
                hideElement(errorState);
                hideElement(previewFrame);
                showElement(canvasWrapper, 'block');
            };

            const showFallbackFrame = (documentUrl) => {
                showElement(toolbar, 'flex');
                hideElement(emptyState);
                hideElement(loadingState);
                showElement(errorState, 'block');
                hideElement(canvasWrapper);
                previewFrame.src = documentUrl || 'about:blank';
                showElement(previewFrame, 'block');
                resetCanvas();
                setControlsDisabled(true);
                updatePageControls();
            };

            const renderPage = async (renderKey = activeRenderKey) => {
                if (! pdfDocument || renderKey !== activeRenderKey) {
                    return;
                }

                if (renderTask) {
                    renderTask.cancel();
                    renderTask = null;
                }

                const page = await pdfDocument.getPage(currentPage);

                if (renderKey !== activeRenderKey) {
                    return;
                }

                const viewport = page.getViewport({ scale: currentScale });
                const pixelRatio = window.devicePixelRatio || 1;

                canvas.width = Math.floor(viewport.width * pixelRatio);
                canvas.height = Math.floor(viewport.height * pixelRatio);
                canvas.style.width = `${Math.floor(viewport.width)}px`;
                canvas.style.height = `${Math.floor(viewport.height)}px`;

                const transform = pixelRatio !== 1 ? [pixelRatio, 0, 0, pixelRatio, 0, 0] : null;

                showCanvasState();
                updatePageControls();

                renderTask = page.render({
                    canvasContext,
                    viewport,
                    transform,
                });

                try {
                    await renderTask.promise;
                } catch (error) {
                    if (error?.name !== 'RenderingCancelledException' && renderKey === activeRenderKey) {
                        showFallbackFrame(activeDocumentUrl);
                    }
                } finally {
                    if (renderKey === activeRenderKey) {
                        renderTask = null;
                    }
                }
            };

            const fitToWidth = async () => {
                if (! pdfDocument || ! canvasWrapper) {
                    return;
                }

                const page = await pdfDocument.getPage(currentPage);
                const baseViewport = page.getViewport({ scale: 1 });
                const availableWidth = Math.max(canvasWrapper.clientWidth - 32, 240);
                currentScale = Math.min(Math.max(availableWidth / baseViewport.width, 0.5), 2.5);
                await renderPage();
            };

            const loadPdfDocument = async (documentUrl) => {
                const renderKey = activeRenderKey;

                if (! pdfjsLib) {
                    showFallbackFrame(documentUrl);
                    return;
                }

                showLoadingState();
                previewFrame.src = 'about:blank';

                try {
                    loadingTask = pdfjsLib.getDocument({
                        url: documentUrl,
                        withCredentials: true,
                    });
                    pdfDocument = await loadingTask.promise;

                    if (renderKey !== activeRenderKey) {
                        return;
                    }

                    currentPage = 1;
                    currentScale = 1;
                    await fitToWidth();
                } catch (error) {
                    if (renderKey === activeRenderKey) {
                        showFallbackFrame(documentUrl);
                    }
                } finally {
                    if (renderKey === activeRenderKey) {
                        loadingTask = null;
                    }
                }
            };

            const setActiveDocument = (option) => {
                cancelActiveRender();

                if (! option || option.dataset.documentAvailable !== '1') {
                    activeDocumentUrl = '';
                    [previewOpenLink, previewDownloadLink].forEach((link) => {
                        if (link) {
                            link.href = '#';
                            link.setAttribute('aria-disabled', 'true');
                        }
                    });
                    hideElement(actionButtons);
                    showEmptyState();
                    return;
                }

                const documentUrl = option.dataset.documentUrl || option.value || '';
                activeDocumentUrl = documentUrl;
                currentPage = 1;
                currentScale = 1;

                previewTitle.textContent = option.dataset.documentTitle || 'Preview Dokumen';
                previewLabel.textContent = option.dataset.documentLabel || '';

                if (toolbarTitle) {
                    toolbarTitle.textContent = option.dataset.documentTitle || 'Preview Dokumen';
                }

                [previewOpenLink, previewDownloadLink].forEach((link) => {
                    if (! link) {
                        return;
                    }

                    link.href = documentUrl || '#';
                    link.removeAttribute('aria-disabled');
                });
                showElement(actionButtons, 'flex');

                loadPdfDocument(documentUrl);
            };

            documentSelector.addEventListener('change', () => {
                setActiveDocument(documentSelector.selectedOptions[0]);
            });

            prevButton?.addEventListener('click', async () => {
                if (! pdfDocument || currentPage <= 1) {
                    return;
                }

                currentPage -= 1;
                await renderPage();
            });

            nextButton?.addEventListener('click', async () => {
                if (! pdfDocument || currentPage >= pdfDocument.numPages) {
                    return;
                }

                currentPage += 1;
                await renderPage();
            });

            zoomOutButton?.addEventListener('click', async () => {
                if (! pdfDocument) {
                    return;
                }

                currentScale = Math.max(currentScale - 0.15, 0.5);
                await renderPage();
            });

            zoomInButton?.addEventListener('click', async () => {
                if (! pdfDocument) {
                    return;
                }

                currentScale = Math.min(currentScale + 0.15, 3);
                await renderPage();
            });

            fitWidthButton?.addEventListener('click', fitToWidth);

            window.addEventListener('resize', () => {
                if (! pdfDocument) {
                    return;
                }

                window.clearTimeout(window.userDocumentPreviewResizeTimer);
                window.userDocumentPreviewResizeTimer = window.setTimeout(fitToWidth, 200);
            });

            const firstAvailableOption = Array.from(documentSelector.options)
                .find((option) => option.dataset.documentAvailable === '1');

            if (firstAvailableOption) {
                documentSelector.value = firstAvailableOption.value;
                setActiveDocument(firstAvailableOption);
            } else {
                showEmptyState();
            }
        });
    </script>
</x-layouts.user>
