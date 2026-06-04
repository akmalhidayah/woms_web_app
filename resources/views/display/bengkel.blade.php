<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light only">
    <title>Dashboard Pekerjaan Bengkel</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">
    <link rel="apple-touch-icon" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script defer src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root { color-scheme: light only; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: #f1f5f9;
        }
        .ticker { position: relative; background:#7f1d1d; color:#fff; overflow:hidden; height:46px; }
        .ticker-track {
            display: flex;
            width: max-content;
            min-width: 100%;
            will-change: transform;
            animation: ticker-scroll var(--ticker-duration, 18s) linear infinite;
        }
        .ticker-item {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            padding: 10px 24px;
            white-space: nowrap;
            font-size: 16px;
            font-weight: 700;
        }
        @keyframes ticker-scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .tv-board-header {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: 1rem;
            min-height: 104px;
            padding: 0.9rem 1.25rem;
        }
        .tv-logo-box {
            display: inline-flex;
            height: 58px;
            width: 58px;
            align-items: center;
            justify-content: center;
            border-radius: 0.85rem;
            background: #fff;
            box-shadow: 0 1px 6px rgba(15, 23, 42, 0.18);
        }
        .tv-board-title {
            font-size: clamp(1.45rem, 2vw, 2.05rem);
            line-height: 1.05;
            font-weight: 900;
        }
        .tv-board-date {
            margin-top: 0.45rem;
            font-size: clamp(0.7rem, 0.8vw, 0.95rem);
            font-weight: 700;
        }
        .tv-board-time {
            margin-top: 0.35rem;
            font-size: clamp(1.35rem, 2vw, 1.9rem);
            line-height: 1;
            font-weight: 900;
        }
        .tv-board-main {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(360px, 1fr);
            gap: 0.75rem;
            min-height: 0;
            flex: 1 1 0%;
        }
        .tv-regu-section {
            display: flex;
            min-height: 0;
            flex-direction: column;
            border: 1px solid #450a0a;
            background: #7f1d1d;
            padding: 0.75rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12);
        }
        .tv-regu-heading {
            position: relative;
            margin-bottom: 0.55rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 28px;
        }
        .tv-regu-title {
            text-align: center;
            font-size: clamp(0.92rem, 1.12vw, 1.18rem);
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #fff;
        }
        .tv-task-grid {
            display: grid;
            min-height: 0;
            flex: 1 1 0%;
            align-content: start;
            gap: 0.55rem;
            overflow: hidden;
        }
        .tv-task-grid-fabrikasi {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .tv-task-card {
            display: flex;
            min-height: 0;
            max-height: 100%;
            flex-direction: column;
            overflow: hidden;
            border-radius: 0.8rem;
            padding: 0.55rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
        }
        .tv-card-title {
            min-width: 0;
            font-size: clamp(0.72rem, 0.88vw, 1rem);
            line-height: 1.12;
            font-weight: 900;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .tv-card-meta {
            margin-top: 0.45rem;
            border-radius: 0.7rem;
            padding: 0.5rem 0.55rem;
        }
        .tv-card-meta-text {
            font-size: clamp(0.58rem, 0.64vw, 0.78rem);
            line-height: 1rem;
            font-weight: 800;
        }
        .tv-pic-wrap {
            margin-top: 0.5rem;
            min-height: 0;
            overflow: hidden;
            border-top: 1px solid #dbeafe;
            padding-top: 0.45rem;
        }
        .tv-pic-grid {
            display: grid;
            gap: 0.45rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .tv-pic-item {
            display: grid;
            min-height: 0;
            gap: 0.35rem;
            border-radius: 0.6rem;
            border: 1px solid #dbeafe;
            background: #fff;
            padding: 0.35rem;
            grid-template-columns: 52px minmax(0, 1fr);
        }
        .tv-pic-photo {
            height: 64px;
            overflow: hidden;
            border-radius: 0.45rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            text-align: center;
        }
        .tv-pic-img,
        .tv-pic-fallback {
            height: 48px;
        }
        .tv-pic-name {
            display: flex;
            height: 16px;
            align-items: center;
            justify-content: center;
            border-top: 1px solid #e2e8f0;
            background: #fff;
            padding: 0 0.15rem;
            font-size: 6.5px;
            font-weight: 900;
            line-height: 1;
            color: #1e293b;
        }
        .tv-pic-desc {
            min-width: 0;
            max-height: 64px;
            overflow: hidden;
            border-left: 1px solid #dbeafe;
            padding-left: 0.45rem;
        }
        .tv-pic-desc-list {
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
            padding-left: 0.9rem;
            font-size: clamp(0.52rem, 0.58vw, 0.68rem);
            font-weight: 700;
            line-height: 1.25;
            color: #334155;
        }
        @media (max-width: 900px) {
            .tv-board-main,
            .tv-task-grid-fabrikasi {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="bg-slate-100 min-h-screen overflow-hidden">
    <livewire:dashboard-pekerjaan mode="display" />

    @livewireScripts

    @once
    <script>
        function bootDashboardDisplay() {
            if (window.__dpClockTimer) clearInterval(window.__dpClockTimer);

            function updateDateTime() {
                const now = new Date();
                const optsDate = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const optsTime = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
                const dateEl = document.getElementById('dateDisplay');
                const timeEl = document.getElementById('timeDisplay');
                if (dateEl) dateEl.textContent = now.toLocaleDateString('id-ID', optsDate);
                if (timeEl) timeEl.textContent = now.toLocaleTimeString('id-ID', optsTime);
            }

            updateDateTime();
            window.__dpClockTimer = setInterval(updateDateTime, 1000);
        }

        if (window.Livewire) {
            bootDashboardDisplay();
        } else {
            document.addEventListener('livewire:init', bootDashboardDisplay, { once: true });
            document.addEventListener('livewire:initialized', bootDashboardDisplay, { once: true });
        }
    </script>
    @endonce
</body>
</html>
