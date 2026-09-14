@php
    $currentPage = collect($pages[$pageSlide] ?? []);
    $pageNumber = min($pageSlide + 1, $maxPages);
    $tickerMessage = trim((string) ($tickerText ?? ''));
    $tickerMessage = $tickerMessage !== ''
        ? $tickerMessage
        : "Monitoring aktivitas dan progres pekerjaan bengkel dalam {$displayDays} hari terakhir";
    $tickerDuration = max(5, min(60, (int) ($tickerSpeedSeconds ?? 18)));
@endphp

<div wire:poll.keep-alive.5s="tickDisplay" class="daily-report-display-shell" style="color-scheme: light only;">
    <header class="tv-board-header daily-report-board-header">
        <div class="daily-report-brand-logos">
            <span class="tv-logo-box"><img src="{{ asset('assets/branding/logos/logo-sig.png') }}" alt="SIG"></span>
            <span class="tv-logo-box"><img src="{{ asset('assets/branding/logos/logo-st2.png') }}" alt="Semen Tonasa"></span>
        </div>

        <div class="daily-report-heading">
            <h1 class="tv-board-title">Laporan Harian</h1>
            <div id="dateDisplay" class="tv-board-date"></div>
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
            <div class="tv-header-clock">
                <span class="daily-report-clock-label">Jam</span>
                <div id="timeDisplay" class="tv-board-time"></div>
                <p>Halaman {{ $pageNumber }} / {{ $maxPages }}</p>
            </div>
        </div>
    </header>

    <div class="ticker daily-report-ticker" style="--ticker-duration: {{ $tickerDuration }}s;">
        <div class="ticker-track">
            <span class="ticker-item">{{ $tickerMessage }}</span>
            <span class="ticker-item">{{ $tickerMessage }}</span>
            <span class="ticker-item">{{ $tickerMessage }}</span>
            <span class="ticker-item">{{ $tickerMessage }}</span>
        </div>
    </div>

    <main class="daily-report-tv-grid">
        @forelse ($currentPage as $report)
            @php
                $picProfiles = collect($report['pic_profiles'] ?? []);
                $visiblePics = $picProfiles->take(2);
                $extraPics = max(0, $picProfiles->count() - $visiblePics->count());
            @endphp
            <article wire:key="daily-report-tv-{{ $report['id'] }}" class="daily-report-tv-card">
                <div class="daily-report-photo">
                    <div class="daily-report-photo-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <path d="m21 15-5-5L5 21"></path>
                        </svg>
                        <span>Foto belum tersedia</span>
                    </div>
                    @if ($report['photo_url'] ?? null)
                        <img src="{{ $report['photo_url'] }}" alt="Foto {{ $report['title'] }}" loading="lazy" onerror="this.style.display='none'">
                    @endif
                    <span class="daily-report-date-badge">
                        {{ $report['date'] }}
                        @if ($report['time'])
                            <span>•</span> {{ $report['time'] }}
                        @endif
                    </span>
                </div>

                <div class="daily-report-card-body">
                    <h2>{{ $report['title'] }}</h2>

                    <div class="daily-report-progress">
                        <span>Progress</span>
                        <p>{{ $report['progress'] ?: 'Belum ada pembaruan progress.' }}</p>
                    </div>

                    <div class="daily-report-card-footer">
                        <div class="daily-report-pics">
                            @forelse ($visiblePics as $profile)
                                <span class="daily-report-pic-chip">
                                    <span class="daily-report-pic-initials">{{ $profile['initials'] }}</span>
                                    <strong>{{ $profile['name'] }}</strong>
                                </span>
                            @empty
                                <span class="daily-report-no-pic">PIC belum tersedia</span>
                            @endforelse
                            @if ($extraPics > 0)
                                <span class="daily-report-extra-pic">+{{ $extraPics }} PIC</span>
                            @endif
                        </div>

                        @if ($report['order'] !== '')
                            <span class="daily-report-order">#{{ $report['order'] }}</span>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <section class="daily-report-tv-empty">
                <span class="daily-report-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14H4V6a2 2 0 0 1 2-2h2"></path>
                        <rect x="8" y="2" width="8" height="4" rx="1"></rect>
                        <path d="M9 12h6M9 16h4"></path>
                    </svg>
                </span>
                <h2>{{ $snapshotFailed ? 'Laporan harian belum dapat dimuat' : "Belum ada laporan dalam {$displayDays} hari terakhir" }}</h2>
                <p>{{ $snapshotFailed ? 'Display Pekerjaan Bengkel akan tetap dilanjutkan secara otomatis.' : 'Data lama tidak ditampilkan pada layar TV.' }}</p>
            </section>
        @endforelse
    </main>
</div>
