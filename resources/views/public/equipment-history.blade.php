<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="referrer" content="no-referrer">
    <title>{{ $unit['asset_code'] }} — Riwayat Inspeksi WOMS</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">
    <style>
        :root { color-scheme: light; --blue: #173f91; --ink: #172033; --muted: #64748b; --line: #dbe3ef; --bg: #f4f7fb; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink); font-family: Arial, Helvetica, sans-serif; }
        [x-cloak] { display: none !important; }
        .equipment-history { width: min(760px, 100%); margin: auto; padding: 16px; }
        .equipment-history .top { display: flex; align-items: center; gap: 10px; padding: 14px; border-radius: 18px; background: var(--blue); color: white; box-shadow: 0 12px 28px #173f9126; }
        .equipment-history .logo { width: 40px; height: 40px; flex: none; border-radius: 10px; background: white; padding: 5px; object-fit: contain; }
        .equipment-history .top strong { font-size: 14px; line-height: 1.4; }
        .equipment-history .top small { display: block; margin-top: 3px; color: #c9d8ff; font-size: 11px; }
        .equipment-history .card { margin-top: 14px; border: 1px solid var(--line); border-radius: 18px; background: white; box-shadow: 0 8px 24px #0f172a0b; overflow: hidden; }
        .equipment-history .equipment { display: grid; gap: 16px; padding: 16px; }
        .equipment-history .equipment-details { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: start; gap: 14px; min-width: 0; }
        .equipment-history .equipment-copy { min-width: 0; }
        .equipment-history .photo { width: 100%; aspect-ratio: 16/10; border-radius: 14px; background: #eef2f7; object-fit: cover; }
        .equipment-history .placeholder { display: grid; place-items: center; color: var(--muted); font-weight: 700; }
        .equipment-history .title { margin: 0; font-size: 25px; font-weight: 800; line-height: 1.2; overflow-wrap: anywhere; }
        .equipment-history .description { margin: 10px 0 0; color: var(--muted); font-size: 13px; line-height: 1.55; overflow-wrap: anywhere; }
        .equipment-history .equipment-pic { width: 96px; text-align: center; }
        .equipment-history .pic-photo, .equipment-history .pic-fallback { width: 72px; height: 72px; margin-left: auto; border-radius: 50%; object-fit: cover; box-shadow: 0 0 0 3px white, 0 0 0 4px #dbe3ef; }
        .equipment-history .pic-fallback { display: grid; place-items: center; background: #eaf1ff; color: var(--blue); font-size: 18px; font-weight: 900; }
        .equipment-history .pic-label { margin: 9px 0 0; color: var(--blue); font-size: 9px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .equipment-history .pic-name { margin: 3px 0 0; font-size: 11px; font-weight: 800; line-height: 1.3; overflow-wrap: anywhere; }
        .equipment-history .tabs { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 9px; margin-top: 16px; }
        .equipment-history .tab { display: flex; align-items: center; flex-direction: column; gap: 7px; min-height: 76px; padding: 12px 4px; border: 1px solid var(--line); border-radius: 14px; background: white; color: var(--muted); cursor: pointer; font: inherit; font-size: 13px; font-weight: 700; }
        .equipment-history .tab.selected { border-color: #74a6f2; color: var(--blue); background: #eaf2ff; box-shadow: 0 3px 10px #173f9112; }
        .equipment-history svg { width: 22px; height: 22px; flex: none; }
        .equipment-history :is(button, a, summary):focus-visible { outline: 3px solid #60a5fa; outline-offset: 3px; }
        .equipment-history .panel { min-height: 260px; }
        .equipment-history .section-head { padding: 15px 16px; border-bottom: 1px solid var(--line); }
        .equipment-history h2 { margin: 0; font-size: 17px; line-height: 1.4; }
        .equipment-history .section-head p { margin: 7px 0 0; color: var(--muted); font-size: 13px; line-height: 1.5; overflow-wrap: anywhere; }
        .equipment-history .latest-head { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px; align-items: start; }
        .equipment-history .latest-side { min-width: 0; border-left: 1px solid var(--line); padding-left: 14px; }
        .equipment-history .meta-label { display: block; margin-bottom: 5px; color: var(--muted); font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .equipment-history h2 + .meta-value { margin-top: 10px; }
        .equipment-history .meta-value { display: block; color: var(--ink); font-size: 12px; font-weight: 800; line-height: 1.4; overflow-wrap: anywhere; }
        .equipment-history .meta-date { display: block; margin-top: 4px; color: var(--muted); font-size: 11px; line-height: 1.4; }
        .equipment-history .latest-side .approved, .equipment-history .latest-side .pending { margin-top: 9px; }
        .equipment-history .body { padding: 16px; }
        .equipment-history .counts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
        .equipment-history .count { padding: 10px 4px; border-radius: 12px; text-align: center; font-size: 11px; font-weight: 700; line-height: 1.5; }
        .equipment-history .count strong { display: block; font-size: 22px; }
        .equipment-history .a { background: #e9f9ef; color: #166534; }
        .equipment-history .b { background: #fff7db; color: #92400e; }
        .equipment-history .c { background: #feecec; color: #991b1b; }
        .equipment-history .summary { margin: 12px 0; padding: 12px; border-radius: 12px; background: #f8fafc; font-size: 13px; line-height: 1.6; }
        .equipment-history .checklist { margin-top: 12px; border: 1px solid var(--line); border-radius: 13px; overflow: hidden; }
        .equipment-history .item { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 12px; border-top: 1px solid var(--line); font-size: 13px; line-height: 1.5; }
        .equipment-history .item:first-child { border-top: 0; }
        .equipment-history .item-copy { min-width: 0; overflow-wrap: anywhere; }
        .equipment-history .item-group { display: block; margin-bottom: 4px; color: var(--muted); font-size: 11px; }
        .equipment-history .remark { margin: 6px 0 0; font-size: 12px; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; }
        .equipment-history .rating { min-width: 32px; flex: none; border-radius: 9px; padding: 5px; text-align: center; font-weight: 900; }
        .equipment-history .approved { display: inline-block; border-radius: 999px; background: #e9f9ef; color: #166534; padding: 5px 8px; font-size: 11px; font-weight: 800; }
        .equipment-history .pending { display: inline-block; border-radius: 999px; background: #fff7db; color: #92400e; padding: 5px 8px; font-size: 11px; font-weight: 800; }
        .equipment-history .history { padding: 0 14px 14px; }
        .equipment-history .entry { margin-top: 12px; padding: 14px; border: 1px solid var(--line); border-radius: 14px; }
        .equipment-history .entry h3 { margin: 8px 0 5px; font-size: 15px; overflow-wrap: anywhere; }
        .equipment-history .date { margin: 0 0 12px; color: var(--muted); font-size: 12px; }
        .equipment-history summary { cursor: pointer; padding: 12px 0; color: var(--blue); font-size: 13px; font-weight: 700; }
        .equipment-history .document-link { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 44px; padding: 10px 14px; border: 1px solid #c4d8f9; border-radius: 11px; background: #eef5ff; color: var(--blue); font-size: 13px; font-weight: 700; text-decoration: none; }
        .equipment-history .document-link svg { width: 19px; height: 19px; }
        .equipment-history .document-link.primary { margin-top: 14px; width: 100%; background: var(--blue); color: white; border-color: var(--blue); }
        .equipment-history .empty { padding: 40px 18px; text-align: center; color: var(--muted); font-size: 14px; line-height: 1.6; }
        .equipment-history .cilt-document { padding: 28px 18px; text-align: center; }
        .equipment-history .cilt-document > svg { width: 48px; height: 48px; color: var(--blue); margin-bottom: 14px; }
        .equipment-history .cilt-document h3 { margin: 0; font-size: 17px; overflow-wrap: anywhere; }
        .equipment-history .cilt-document p { font-size: 13px; line-height: 1.6; color: var(--muted); }
        .equipment-history .notice { margin: 14px 0 4px; padding: 13px; border-radius: 14px; border: 1px solid #f0cf7a; background: #fffbeb; color: #744210; font-size: 12px; line-height: 1.6; }
        .equipment-history .pagination { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 14px; color: var(--muted); font-size: 12px; }
        .equipment-history .page-link { border: 1px solid var(--line); border-radius: 10px; background: white; color: var(--blue); padding: 12px 8px; font-weight: 700; text-decoration: none; }
        .equipment-history .page-link.disabled { color: #94a3b8; }
        @media (min-width: 620px) { .equipment-history { padding: 24px; } .equipment-history .equipment { grid-template-columns: 220px 1fr; align-items: center; } .equipment-history .photo { aspect-ratio: 4/3; } }
    </style>
</head>
<body>
    <main class="equipment-history" x-data="{
        activeTab: @js(request('tab') === 'history' || $inspections->currentPage() > 1 ? 'history' : 'inspection'),
        tabs: ['inspection', 'history', 'cilt'],
        moveTab(event) {
            const index = this.tabs.indexOf(this.activeTab);
            const next = event.key === 'Home' ? 0 : event.key === 'End' ? 2 : (index + (event.key === 'ArrowRight' ? 1 : 2)) % 3;
            this.activeTab = this.tabs[next];
            this.$nextTick(() => document.getElementById('tab-' + this.activeTab).focus());
        }
    }">
        <header class="top">
            <img class="logo" src="{{ asset('assets/branding/logos/logo-sig.png') }}" alt="SIG">
            <img class="logo" src="{{ asset('assets/branding/logos/logo-st2.png') }}" alt="Semen Tonasa">
            <div><strong>WOMS EQUIPMENT HISTORY</strong><small>Riwayat Pemeriksaan Peralatan</small></div>
        </header>

        <section class="card equipment" aria-label="Identitas peralatan">
            @if ($unit['has_image'])
                <img class="photo" src="{{ route('public.equipment-history.image', $unit['public_code']) }}" alt="Foto {{ $unit['name'] }}">
            @else
                <div class="photo placeholder">Foto belum tersedia</div>
            @endif
            <div class="equipment-details">
                <div class="equipment-copy">
                    <h1 class="title">{{ $unit['name'] }}</h1>
                    <p class="description">{{ $unit['description'] ?: 'Deskripsi peralatan belum tersedia.' }}</p>
                </div>
                @if ($unit['pic'])
                    <aside class="equipment-pic" aria-label="PIC peralatan">
                        @if ($unit['pic']['has_photo'])
                            <img class="pic-photo" src="{{ route('public.equipment-history.pic-photo', $unit['public_code']) }}" alt="Foto {{ $unit['pic']['name'] }}">
                        @else
                            <div class="pic-fallback" aria-hidden="true">{{ collect(preg_split('/\s+/', trim($unit['pic']['name'])))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('') ?: 'PIC' }}</div>
                        @endif
                        <p class="pic-label">PIC Peralatan</p>
                        <p class="pic-name">{{ $unit['pic']['name'] }}</p>
                    </aside>
                @endif
            </div>
        </section>

        <nav class="tabs" role="tablist" aria-label="Informasi peralatan"
            @keydown.arrow-right.prevent="moveTab($event)" @keydown.arrow-left.prevent="moveTab($event)"
            @keydown.home.prevent="moveTab($event)" @keydown.end.prevent="moveTab($event)">
            @foreach (['inspection' => 'Inspeksi', 'history' => 'Riwayat', 'cilt' => 'CILT'] as $tab => $label)
                <button type="button" id="tab-{{ $tab }}" role="tab" class="tab"
                    :class="{ selected: activeTab === '{{ $tab }}' }"
                    :aria-selected="activeTab === '{{ $tab }}'" :tabindex="activeTab === '{{ $tab }}' ? 0 : -1"
                    aria-controls="panel-{{ $tab }}" @click="activeTab = '{{ $tab }}'">
                    @include('public.partials.equipment-icon', ['icon' => $tab])
                    <span>{{ $label }}</span>
                </button>
            @endforeach
        </nav>

        <section id="panel-inspection" role="tabpanel" aria-labelledby="tab-inspection" tabindex="0" class="card panel" x-show="activeTab === 'inspection'" x-cloak>
            <div class="section-head latest-head">
                <div><h2>Hasil Pemeriksaan Terakhir</h2>
                    @if ($latest)
                        <span class="meta-value">{{ $latest['document_no'] ?: 'Nomor tidak tersedia' }}</span>
                        <span class="meta-date">{{ \Illuminate\Support\Carbon::parse($latest['inspection_date'])->translatedFormat('d F Y') }}</span>
                    @endif
                </div>
                @if ($latest)
                    <div class="latest-side">
                        <span class="meta-label">Inspektor</span>
                        <span class="meta-value">{{ $latest['inspector_name'] ?: 'Tidak dicantumkan' }}</span>
                        <span class="{{ $latest['is_approved'] ? 'approved' : 'pending' }}">{{ $latest['status'] }}</span>
                    </div>
                @endif
            </div>
            @if ($latest)
                <div class="body">
                    @include('public.partials.equipment-counts', ['inspection' => $latest])
                    <p class="summary">{{ $latest['summary'] }}</p>
                    @include('public.partials.equipment-checklist', ['inspection' => $latest])
                    @if ($latest['pdf_available'])
                        <a class="document-link primary" href="{{ route('public.equipment-history.pdf', [$unit['public_code'], $latest['public_id']]) }}" target="_blank" rel="noopener noreferrer">
                            @include('public.partials.equipment-icon', ['icon' => 'pdf']) Buka PDF Inspeksi
                        </a>
                    @endif
                </div>
            @else
                <div class="empty">Belum ada hasil pemeriksaan yang dapat ditampilkan.</div>
            @endif
        </section>

        <section id="panel-history" role="tabpanel" aria-labelledby="tab-history" tabindex="0" class="card panel" x-show="activeTab === 'history'" x-cloak>
            <div class="section-head"><h2>Riwayat Pemeriksaan</h2><p>Pemeriksaan dalam proses approval dan yang telah disetujui, dari terbaru ke terlama.</p></div>
            <div class="history">
                @forelse ($inspections as $inspection)
                    <article class="entry">
                        <span class="{{ $inspection['is_approved'] ? 'approved' : 'pending' }}">{{ $inspection['status'] }}</span>
                        <h3>{{ $inspection['document_no'] ?: 'Nomor tidak tersedia' }}</h3>
                        <p class="date">{{ \Illuminate\Support\Carbon::parse($inspection['inspection_date'])->translatedFormat('d F Y') }}</p>
                        @include('public.partials.equipment-counts')
                        <p class="summary">{{ $inspection['summary'] }}</p>
                        <details>
                            <summary>Lihat Detail Checklist &amp; Remarks</summary>
                            @include('public.partials.equipment-checklist')
                        </details>
                        @if ($inspection['pdf_available'])
                            <a class="document-link" href="{{ route('public.equipment-history.pdf', [$unit['public_code'], $inspection['public_id']]) }}" target="_blank" rel="noopener noreferrer">
                                @include('public.partials.equipment-icon', ['icon' => 'pdf']) Buka PDF
                            </a>
                        @endif
                    </article>
                @empty
                    <div class="empty">Belum ada riwayat pemeriksaan yang dapat ditampilkan.</div>
                @endforelse
                @if ($inspections->hasPages())
                    @php($inspections->appends(['tab' => 'history']))
                    <nav class="pagination" aria-label="Navigasi riwayat">
                        @if ($inspections->onFirstPage())<span class="page-link disabled" aria-disabled="true">← Sebelumnya</span>
                        @else<a class="page-link" href="{{ $inspections->previousPageUrl() }}">← Sebelumnya</a>@endif
                        <span>Halaman {{ $inspections->currentPage() }}</span>
                        @if ($inspections->hasMorePages())<a class="page-link" href="{{ $inspections->nextPageUrl() }}">Berikutnya →</a>
                        @else<span class="page-link disabled" aria-disabled="true">Berikutnya →</span>@endif
                    </nav>
                @endif
            </div>
        </section>

        <section id="panel-cilt" role="tabpanel" aria-labelledby="tab-cilt" tabindex="0" class="card panel" x-show="activeTab === 'cilt'" x-cloak>
            <div class="section-head"><h2>Standar CILT Peralatan</h2><p>Cleaning, Inspection, Lubrication &amp; Tightening</p></div>
            @if ($cilt && $cilt['available'])
                <div class="cilt-document">
                    @include('public.partials.equipment-icon', ['icon' => 'pdf'])
                    <h3>{{ $cilt['name'] }}</h3>
                    <p>Dokumen standar perawatan {{ $unit['name'] }}.</p>
                    @if ($cilt['updated_at'])<p>Diperbarui {{ \Illuminate\Support\Carbon::parse($cilt['updated_at'])->timezone(config('app.timezone'))->translatedFormat('d F Y H:i') }}</p>@endif
                    <a class="document-link primary" href="{{ route('public.equipment-history.cilt', $unit['public_code']) }}" target="_blank" rel="noopener noreferrer">
                        @include('public.partials.equipment-icon', ['icon' => 'pdf']) Buka Dokumen CILT
                    </a>
                </div>
            @else
                <div class="empty">Dokumen Standar CILT belum tersedia untuk peralatan ini.</div>
            @endif
        </section>

        <noscript><p class="notice">Aktifkan JavaScript untuk membuka tab informasi peralatan.</p></noscript>
    </main>
    {{-- Livewire supplies the existing local Alpine runtime; no second Alpine instance. --}}
    @livewireScripts
</body>
</html>
