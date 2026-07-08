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
        $activeDocumentPreview = $documentPreviewItems->first(fn (array $item) => filled($item['url']));
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
            <div class="rounded-[22px] border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-black text-slate-900">Daftar Dokumen</h2>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="rounded-[20px] border border-stone-200 bg-stone-50/80 p-4">
                        <label for="user-document-selector" class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500">Pilih Dokumen</label>
                        <select
                            id="user-document-selector"
                            class="mt-2 w-full rounded-2xl border border-stone-200 bg-white px-3 py-3 text-sm font-semibold text-slate-800 shadow-sm outline-none transition focus:border-red-300 focus:ring-4 focus:ring-red-100 disabled:cursor-not-allowed disabled:bg-stone-100 disabled:text-slate-400"
                            @disabled($documentPreviewItems->isEmpty())
                        >
                            @forelse ($documentPreviewItems as $item)
                                <option
                                    value="{{ $item['url'] }}"
                                    data-document-title="{{ $item['title'] }}"
                                    data-document-label="{{ $item['label'] }}"
                                    data-document-url="{{ $item['url'] }}"
                                    data-document-available="{{ filled($item['url']) ? '1' : '0' }}"
                                    @selected(($activeDocumentPreview['url'] ?? null) === ($item['url'] ?? null))
                                    @disabled(blank($item['url']))
                                >
                                    {{ $item['title'] }} - {{ filled($item['url']) ? $item['label'] : 'Belum tersedia' }}
                                </option>
                            @empty
                                <option value="">Belum ada dokumen</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="overflow-visible rounded-[20px] border border-stone-200 bg-white">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-stone-200 px-4 py-3 sm:px-5">
                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">Preview Dokumen</div>
                                <h3 id="user-document-preview-title" class="mt-1 text-lg font-black text-slate-900">
                                    {{ $activeDocumentPreview['title'] ?? 'Dokumen Belum Tersedia' }}
                                </h3>
                                <p id="user-document-preview-label" class="mt-1 text-sm text-slate-500">
                                    {{ $activeDocumentPreview['label'] ?? 'Belum ada dokumen yang dapat dipreview.' }}
                                </p>
                            </div>
                            @if ($activeDocumentPreview)
                                <div class="flex flex-wrap items-center gap-2">
                                    <a
                                        id="user-document-preview-link"
                                        href="{{ $activeDocumentPreview['url'] }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-red-200 hover:bg-white hover:text-red-800"
                                    >
                                        <i data-lucide="file-search" class="h-3.5 w-3.5"></i>
                                        Buka Dokumen
                                    </a>
                                    <a
                                        id="user-document-preview-download-link"
                                        href="{{ $activeDocumentPreview['url'] }}"
                                        download
                                        class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-red-200 hover:bg-white hover:text-red-800"
                                    >
                                        <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                        Download
                                    </a>
                                </div>
                            @endif
                        </div>

                        @if ($activeDocumentPreview)
                            <iframe
                                id="user-document-preview-frame"
                                src="{{ $activeDocumentPreview['url'] }}"
                                class="h-[60vh] min-h-[520px] w-full overflow-auto bg-stone-100 lg:h-[75vh] lg:min-h-[720px]"
                                title="Preview dokumen order {{ $order['nomor_order'] }}"
                            ></iframe>
                        @else
                            <div class="flex h-[420px] items-center justify-center bg-stone-50 px-6 text-center">
                                <div>
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-stone-200 bg-white text-slate-400">
                                        <i data-lucide="files" class="h-5 w-5"></i>
                                    </div>
                                    <div class="mt-4 text-base font-semibold text-slate-700">Belum ada dokumen yang dapat ditampilkan</div>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">Dokumen akan muncul di panel ini setelah file order mulai diunggah.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>

    @include('user.orders.partials.approval-flow-modal')
    @include('user.orders.partials.timeline-info-modal')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const documentSelector = document.getElementById('user-document-selector');
            const previewFrame = document.getElementById('user-document-preview-frame');
            const previewTitle = document.getElementById('user-document-preview-title');
            const previewLabel = document.getElementById('user-document-preview-label');
            const previewOpenLink = document.getElementById('user-document-preview-link');
            const previewDownloadLink = document.getElementById('user-document-preview-download-link');

            if (! documentSelector || ! previewFrame) {
                return;
            }

            const setActiveDocument = (option) => {
                if (! option || option.dataset.documentAvailable !== '1') {
                    return;
                }

                const documentUrl = option.dataset.documentUrl || option.value || '';

                previewTitle.textContent = option.dataset.documentTitle || 'Preview Dokumen';
                previewLabel.textContent = option.dataset.documentLabel || '';
                previewFrame.src = documentUrl;

                [previewOpenLink, previewDownloadLink].forEach((link) => {
                    if (! link) {
                        return;
                    }

                    link.href = documentUrl || '#';
                });
            };

            documentSelector.addEventListener('change', () => {
                setActiveDocument(documentSelector.selectedOptions[0]);
            });

            const firstAvailableOption = Array.from(documentSelector.options)
                .find((option) => option.dataset.documentAvailable === '1');

            if (firstAvailableOption) {
                documentSelector.value = firstAvailableOption.value;
                setActiveDocument(firstAvailableOption);
            }
        });
    </script>
</x-layouts.user>
