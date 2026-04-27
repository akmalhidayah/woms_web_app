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
        $activeDocumentPreviewType = $activeDocumentPreview['preview_type'] ?? 'file';
        $documentToneClasses = [
            'blue' => 'border-sky-200 bg-sky-50 text-sky-700',
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'violet' => 'border-violet-200 bg-violet-50 text-violet-700',
            'orange' => 'border-amber-200 bg-amber-50 text-amber-700',
            'rose' => 'border-rose-200 bg-rose-50 text-rose-700',
            'slate' => 'border-stone-200 bg-stone-100 text-slate-600',
        ];
    @endphp

    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('user.dashboard') }}" class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-red-200 hover:text-red-800">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Kembali ke dashboard
            </a>
            <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-bold ring-1 {{ $order['prioritas_badge_classes'] }}">
                {{ $order['prioritas_label'] }}
            </span>
        </div>

        <section class="overflow-hidden rounded-[24px] border border-stone-200 bg-white shadow-sm">
            <div class="grid gap-0 lg:grid-cols-[1.1fr_0.9fr]">
                <div class="border-b border-stone-200 bg-[linear-gradient(180deg,#fff_0%,#fafaf9_100%)] p-5 sm:p-6 lg:border-b-0 lg:border-r">
                    <div class="space-y-4">
                        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-red-800 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-white">
                            Tracking Order
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-slate-500">Nomor Order {{ $order['nomor_order'] }}</div>
                            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900">{{ $order['nama_pekerjaan'] }}</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ $order['deskripsi'] ?: 'Deskripsi order belum ditambahkan.' }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Notifikasi</div>
                                <div class="mt-2 text-lg font-bold text-slate-900">{{ $order['notifikasi'] ?: '-' }}</div>
                            </div>
                            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Tanggal Order</div>
                                <div class="mt-2 text-lg font-bold text-slate-900">{{ $order['tanggal_order'] ?: '-' }}</div>
                            </div>
                            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Unit Kerja</div>
                                <div class="mt-2 text-base font-bold text-slate-900">{{ $order['unit_kerja'] ?: '-' }}</div>
                            </div>
                            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Seksi</div>
                                <div class="mt-2 text-base font-bold text-slate-900">{{ $order['seksi'] ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-stone-50/70 p-5 sm:p-6">
                    <div class="rounded-[22px] border border-red-100 bg-white p-5 shadow-sm">
                        <div class="grid gap-3 sm:grid-cols-[0.85fr_1.15fr]">
                            <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-4">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Target Selesai</div>
                                <div class="mt-2 text-base font-semibold text-slate-900">{{ $order['progress']['target'] ?: $order['target_selesai_order'] ?: '-' }}</div>
                            </div>

                            <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-4">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Approval Awal</div>
                                <div class="mt-2 text-base font-semibold text-slate-900">{{ $order['approval_label'] }}</div>

                                <div class="mt-4 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Catatan</div>
                                <div class="mt-2 text-sm leading-6 text-slate-700">{{ $order['approval_note'] ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-[22px] border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
            <div>
                <h2 class="text-xl font-black text-slate-900">Timeline Proses</h2>
                <p class="mt-1 text-sm text-slate-500">Semua tahapan order diringkas dalam satu alur yang mudah dipantau.</p>
            </div>

            <div class="mt-5 overflow-x-auto pb-2">
                <div class="flex min-w-max items-start gap-0 pr-4">
                    @foreach ($order['timeline'] as $item)
                        <article class="relative w-[220px] shrink-0 pr-5 {{ $loop->last ? 'pr-0' : '' }}">
                            @unless ($loop->last)
                                <div class="absolute left-[60px] top-5 h-[2px] w-[calc(100%-68px)] {{ $timelineLineClasses[$item['tone']] ?? $timelineLineClasses['waiting'] }}"></div>
                            @endunless

                            <div class="relative z-10 flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 text-sm font-black {{ $timelineDotClasses[$item['tone']] ?? $timelineDotClasses['waiting'] }}">
                                    {{ $loop->iteration }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">{{ $item['label'] }}</div>
                                    <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold {{ $timelineBadgeClasses[$item['tone']] ?? $timelineBadgeClasses['waiting'] }}">
                                        {{ $item['tone'] === 'done' ? 'Selesai' : ($item['tone'] === 'danger' ? 'Perhatian' : 'Pending') }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-4 rounded-[20px] border p-4 {{ $timelineToneClasses[$item['tone']] ?? $timelineToneClasses['waiting'] }}">
                                <div class="text-sm font-bold leading-6 text-slate-900">{{ $item['value'] }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
            <div class="rounded-[22px] border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-black text-slate-900">Pusat Dokumen</h2>
                        <p class="mt-1 text-sm text-slate-500">Pilih dokumen dari panel kiri lalu preview langsung tanpa pindah halaman.</p>
                    </div>
                    @if ($activeDocumentPreview)
                        <div class="flex flex-wrap items-center gap-2">
                            <a
                                id="user-document-open-link"
                                href="{{ $activeDocumentPreview['url'] }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-red-200 hover:text-red-800"
                            >
                                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                Buka Dokumen
                            </a>
                            <a
                                id="user-document-download-link"
                                href="{{ $activeDocumentPreview['url'] }}"
                                download
                                class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-red-200 hover:text-red-800"
                            >
                                <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                Download
                            </a>
                        </div>
                    @endif
                </div>

                <div class="mt-5 grid gap-4 xl:grid-cols-[270px_minmax(0,1fr)]">
                    <div class="rounded-[20px] border border-stone-200 bg-stone-50/80 p-3">
                        <div class="px-1">
                            <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">Panel Toggle</div>
                            <div class="mt-1 text-sm text-slate-500">Dokumen HPP final DIROPS akan otomatis diprioritaskan bila tersedia.</div>
                        </div>

                        <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                            @foreach ($documentPreviewItems as $item)
                                @php
                                    $iconClasses = $documentToneClasses[$item['tone'] ?? 'slate'] ?? $documentToneClasses['slate'];
                                @endphp
                                <button
                                    type="button"
                                    data-document-tab
                                    data-document-title="{{ $item['title'] }}"
                                    data-document-label="{{ $item['label'] }}"
                                    data-document-url="{{ $item['url'] }}"
                                    data-document-preview-type="{{ $item['preview_type'] ?? 'file' }}"
                                    data-document-available="{{ filled($item['url']) ? '1' : '0' }}"
                                    @disabled(blank($item['url']))
                                    class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-white px-3 py-2.5 text-left transition {{ filled($item['url']) ? 'hover:border-red-200 hover:bg-red-50/30' : 'cursor-not-allowed opacity-50' }}"
                                >
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border {{ $iconClasses }}">
                                        <i data-lucide="{{ $item['icon'] }}" class="h-3.5 w-3.5"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $item['title'] }}</span>
                                        <span class="mt-0.5 block truncate text-[11px] text-slate-500">
                                            {{ filled($item['url']) ? $item['label'] : 'Belum tersedia' }}
                                        </span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[20px] border border-stone-200 bg-white">
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
                                        Buka di Tab Baru
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
                            <div id="user-document-preview-embed-wrapper" class="{{ $activeDocumentPreviewType === 'pdf' ? '' : 'hidden' }}">
                                <iframe
                                    id="user-document-preview-frame"
                                    src="{{ $activeDocumentPreviewType === 'pdf' ? $activeDocumentPreview['url'] : '' }}"
                                    class="h-[720px] w-full bg-stone-100"
                                    title="Preview dokumen order {{ $order['nomor_order'] }}"
                                ></iframe>
                            </div>
                            <div id="user-document-preview-image-wrapper" class="{{ $activeDocumentPreviewType === 'image' ? '' : 'hidden' }} bg-stone-50">
                                <div class="flex min-h-[420px] items-center justify-center p-4">
                                    <img
                                        id="user-document-preview-image"
                                        src="{{ $activeDocumentPreviewType === 'image' ? $activeDocumentPreview['url'] : '' }}"
                                        alt="Preview dokumen {{ $activeDocumentPreview['title'] ?? 'order' }}"
                                        class="max-h-[760px] w-auto max-w-full rounded-2xl border border-stone-200 bg-white object-contain shadow-sm"
                                    >
                                </div>
                            </div>
                            <div id="user-document-preview-mobile-fallback" class="{{ $activeDocumentPreviewType === 'file' ? 'flex' : 'hidden' }} min-h-[320px] items-center justify-center bg-stone-50 px-6 py-10 text-center">
                                <div class="max-w-md">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-stone-200 bg-white text-slate-400">
                                        <i data-lucide="file-stack" class="h-5 w-5"></i>
                                    </div>
                                    <div id="user-document-preview-fallback-title" class="mt-4 text-base font-semibold text-slate-800">
                                        {{ $activeDocumentPreviewType === 'pdf' ? 'Preview langsung dibatasi di browser mobile' : 'Dokumen ini tidak mendukung preview langsung' }}
                                    </div>
                                    <p id="user-document-preview-fallback-message" class="mt-2 text-sm leading-6 text-slate-500">
                                        {{ $activeDocumentPreviewType === 'pdf'
                                            ? 'Chrome Android sering tidak menampilkan PDF atau dokumen inline dengan stabil. Gunakan tombol buka atau download untuk melihat file dengan lebih aman.'
                                            : 'File jenis Office atau dokumen non-gambar sebaiknya dibuka di tab baru atau diunduh terlebih dahulu.' }}
                                    </p>
                                    <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                                        <a
                                            id="user-document-mobile-open-link"
                                            href="{{ $activeDocumentPreview['url'] }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-red-200 hover:text-red-800"
                                        >
                                            <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                            Buka Dokumen
                                        </a>
                                        <a
                                            id="user-document-mobile-download-link"
                                            href="{{ $activeDocumentPreview['url'] }}"
                                            download
                                            class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-red-200 hover:text-red-800"
                                        >
                                            <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                            Download
                                        </a>
                                    </div>
                                </div>
                            </div>
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

            <div class="space-y-5">
                <section class="rounded-[22px] border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-xl font-black text-slate-900">Ringkasan HPP & Anggaran</h2>
                    <div class="mt-5 space-y-3">
                        <div class="rounded-2xl border border-red-100 bg-red-50/40 p-4">
                            <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-700">Status HPP</div>
                            <div class="mt-2 text-base font-bold text-slate-900">{{ $order['hpp']['status'] }}</div>
                            <div class="mt-1 text-sm text-slate-600">
                                {{ $order['hpp']['total'] !== null ? 'Rp '.number_format((float) $order['hpp']['total'], 2, ',', '.') : 'Nilai belum tersedia' }}
                            </div>
                        </div>
                        <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-4">
                            <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-700">Verifikasi Anggaran</div>
                            <div class="mt-2 text-base font-bold text-slate-900">{{ $order['budget']['status'] }}</div>
                            <div class="mt-3 space-y-1 text-sm text-slate-600">
                                <div>Kategori item: {{ $order['budget']['kategori_item'] ?: '-' }}</div>
                                <div>Kategori biaya: {{ $order['budget']['kategori_biaya'] ?: '-' }}</div>
                                <div>Cost element: {{ $order['budget']['cost_element'] ?: '-' }}</div>
                            </div>
                            @if ($order['budget']['catatan'])
                                <div class="mt-3 rounded-2xl border border-amber-200 bg-white px-4 py-3 text-sm leading-6 text-slate-700">
                                    {{ $order['budget']['catatan'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="rounded-[22px] border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-xl font-black text-slate-900">PO & Garansi</h2>
                    <div class="mt-5 space-y-3">
                        <div class="rounded-2xl border border-sky-200 bg-sky-50/40 p-4">
                            <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-700">Nomor PO</div>
                            <div class="mt-2 text-base font-bold text-slate-900">{{ $order['purchase_order']['number'] ?: '-' }}</div>
                            <div class="mt-1 text-sm text-slate-600">Target selesai: {{ $order['purchase_order']['target'] ?: '-' }}</div>
                            @if ($order['purchase_order']['admin_note'])
                                <div class="mt-3 rounded-2xl border border-sky-200 bg-white px-4 py-3 text-sm leading-6 text-slate-700">
                                    {{ $order['purchase_order']['admin_note'] }}
                                </div>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-violet-200 bg-violet-50/40 p-4">
                            <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-700">Garansi</div>
                            @if ($order['garansi'])
                                <div class="mt-2 text-base font-bold text-slate-900">{{ $order['garansi']['months'] }} bulan</div>
                                <div class="mt-1 text-sm text-slate-600">Mulai {{ $order['garansi']['start'] ?: '-' }} • Berakhir {{ $order['garansi']['end'] ?: '-' }}</div>
                            @else
                                <div class="mt-2 inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500">Data garansi belum tersedia</div>
                            @endif
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const previewFrame = document.getElementById('user-document-preview-frame');
            const previewTitle = document.getElementById('user-document-preview-title');
            const previewLabel = document.getElementById('user-document-preview-label');
            const previewOpenLink = document.getElementById('user-document-preview-link');
            const primaryOpenLink = document.getElementById('user-document-open-link');
            const previewDownloadLink = document.getElementById('user-document-preview-download-link');
            const primaryDownloadLink = document.getElementById('user-document-download-link');
            const mobileOpenLink = document.getElementById('user-document-mobile-open-link');
            const mobileDownloadLink = document.getElementById('user-document-mobile-download-link');
            const embedWrapper = document.getElementById('user-document-preview-embed-wrapper');
            const imageWrapper = document.getElementById('user-document-preview-image-wrapper');
            const previewImage = document.getElementById('user-document-preview-image');
            const mobileFallback = document.getElementById('user-document-preview-mobile-fallback');
            const fallbackTitle = document.getElementById('user-document-preview-fallback-title');
            const fallbackMessage = document.getElementById('user-document-preview-fallback-message');
            const tabs = Array.from(document.querySelectorAll('[data-document-tab]'));
            const isMobileBrowser = window.matchMedia('(max-width: 1024px)').matches
                || /Android|iPhone|iPad|iPod|Mobile/i.test(window.navigator.userAgent);

            if (! previewFrame || tabs.length === 0) {
                return;
            }

            const setPreviewMode = (previewType, documentUrl) => {
                const type = previewType || 'file';
                const showPdfEmbed = type === 'pdf' && ! isMobileBrowser;
                const showImagePreview = type === 'image';
                const showFallback = ! showPdfEmbed && ! showImagePreview;

                if (embedWrapper) {
                    embedWrapper.classList.toggle('hidden', ! showPdfEmbed);
                }

                if (imageWrapper) {
                    imageWrapper.classList.toggle('hidden', ! showImagePreview);
                }

                if (mobileFallback) {
                    mobileFallback.classList.toggle('hidden', ! showFallback);
                    mobileFallback.classList.toggle('flex', showFallback);
                }

                if (previewFrame) {
                    previewFrame.src = showPdfEmbed ? documentUrl : '';
                }

                if (previewImage) {
                    previewImage.src = showImagePreview ? documentUrl : '';
                }

                if (fallbackTitle && fallbackMessage) {
                    const fallbackIsPdf = type === 'pdf';

                    fallbackTitle.textContent = fallbackIsPdf
                        ? 'Preview langsung dibatasi di browser mobile'
                        : 'Dokumen ini tidak mendukung preview langsung';

                    fallbackMessage.textContent = fallbackIsPdf
                        ? 'Chrome Android sering tidak menampilkan PDF atau dokumen inline dengan stabil. Gunakan tombol buka atau download untuk melihat file dengan lebih aman.'
                        : 'File jenis Office atau dokumen non-gambar sebaiknya dibuka di tab baru atau diunduh terlebih dahulu.';
                }
            };

            const setActiveTab = (tab) => {
                if (! tab || tab.dataset.documentAvailable !== '1') {
                    return;
                }

                tabs.forEach((button) => {
                    const isActive = button === tab;

                    button.classList.toggle('border-red-200', isActive);
                    button.classList.toggle('bg-red-50', isActive);
                    button.classList.toggle('shadow-sm', isActive);
                    button.classList.toggle('border-stone-200', ! isActive);
                    button.classList.toggle('bg-white', ! isActive);
                });

                previewFrame.src = tab.dataset.documentUrl || '';
                previewTitle.textContent = tab.dataset.documentTitle || 'Preview Dokumen';
                previewLabel.textContent = tab.dataset.documentLabel || '';

                [previewOpenLink, primaryOpenLink, mobileOpenLink].forEach((link) => {
                    if (! link) {
                        return;
                    }

                    link.href = tab.dataset.documentUrl || '#';
                });

                [previewDownloadLink, primaryDownloadLink, mobileDownloadLink].forEach((link) => {
                    if (! link) {
                        return;
                    }

                    link.href = tab.dataset.documentUrl || '#';
                });

                setPreviewMode(tab.dataset.documentPreviewType || 'file', tab.dataset.documentUrl || '');
            };

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => setActiveTab(tab));
            });

            const firstAvailableTab = tabs.find((tab) => tab.dataset.documentAvailable === '1');

            if (firstAvailableTab) {
                setActiveTab(firstAvailableTab);
            }
        });
    </script>
</x-layouts.user>
