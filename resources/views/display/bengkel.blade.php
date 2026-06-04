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
        .ticker { position: relative; background:#7f1d1d; color:#fff; overflow:hidden; height:38px; }
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
            padding: 8px 20px;
            white-space: nowrap;
            font-size: 14px;
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
            gap: 0.85rem;
            height: 92px;
            min-height: 0;
            padding: 0.45rem 1.15rem;
        }
        .tv-logo-box {
            display: inline-flex;
            height: 52px;
            width: 52px;
            align-items: center;
            justify-content: center;
            border-radius: 0.7rem;
            background: #fff;
            box-shadow: 0 1px 6px rgba(15, 23, 42, 0.18);
        }
        .tv-board-title {
            font-size: clamp(1.25rem, 1.75vw, 1.85rem);
            line-height: 1.05;
            font-weight: 900;
        }
        .tv-board-date {
            margin-top: 0.3rem;
            font-size: clamp(0.62rem, 0.72vw, 0.82rem);
            font-weight: 700;
        }
        .tv-board-time {
            margin-top: 0.25rem;
            font-size: clamp(1.1rem, 1.65vw, 1.55rem);
            line-height: 1;
            font-weight: 900;
        }
        .tv-board-main {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(360px, 1fr);
            gap: 0.55rem;
            min-height: 0;
            flex: 1 1 0%;
        }
        .tv-regu-section {
            display: flex;
            min-height: 0;
            flex-direction: column;
            border: 1px solid #450a0a;
            background: #7f1d1d;
            padding: 0.55rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12);
        }
        .tv-regu-heading {
            position: relative;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 24px;
        }
        .tv-regu-title {
            text-align: center;
            font-size: clamp(0.8rem, 0.95vw, 1rem);
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #fff;
        }
        .tv-task-grid {
            display: grid;
            min-height: 0;
            flex: 1 1 0%;
            align-content: stretch;
            gap: 0.45rem;
            overflow: hidden;
        }
        .tv-task-grid-fabrikasi {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            grid-template-rows: repeat(3, minmax(0, 1fr));
        }
        .tv-task-card {
            display: flex;
            min-height: 0;
            max-height: 100%;
            flex-direction: column;
            overflow: hidden;
            border-radius: 0.8rem;
            padding: 0.42rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
        }
        .tv-card-title {
            min-width: 0;
            font-size: clamp(0.66rem, 0.78vw, 0.9rem);
            line-height: 1.08;
            font-weight: 900;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .tv-card-meta {
            margin-top: 0.35rem;
            border-radius: 0.7rem;
            padding: 0.35rem 0.45rem;
        }
        .tv-card-meta-text {
            font-size: clamp(0.52rem, 0.58vw, 0.68rem);
            line-height: 0.82rem;
            font-weight: 800;
        }
        .tv-task-card .rounded-full {
            padding: 0.18rem 0.45rem !important;
            font-size: clamp(0.52rem, 0.58vw, 0.68rem) !important;
            line-height: 1 !important;
        }
        .tv-card-meta .border-t {
            padding-top: 0.28rem !important;
        }
        .tv-pic-wrap {
            flex: 1 1 0%;
            margin-top: 0.35rem;
            min-height: 0;
            overflow: hidden;
            border-top: 1px solid #dbeafe;
            padding-top: 0.32rem;
        }
        .tv-pic-grid {
            display: grid;
            height: 100%;
            gap: 0.32rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-content: start;
            grid-auto-rows: max-content;
        }
        .tv-pic-item {
            display: grid;
            min-height: 0;
            gap: 0.28rem;
            overflow: hidden;
            border-radius: 0.48rem;
            border: 1px solid #dbeafe;
            background: #fff;
            padding: 0.25rem;
            grid-template-columns: 56px minmax(0, 1fr);
        }
        .tv-pic-photo {
            height: 70px;
            overflow: hidden;
            border-radius: 0.38rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            text-align: center;
        }
        .tv-pic-img,
        .tv-pic-fallback {
            height: 54px;
        }
        .tv-pic-name {
            display: flex;
            height: 16px;
            align-items: center;
            justify-content: center;
            border-top: 1px solid #e2e8f0;
            background: #fff;
            padding: 0 0.15rem;
            font-size: 6.4px;
            font-weight: 900;
            line-height: 1;
            color: #1e293b;
        }
        .tv-pic-desc {
            min-width: 0;
            max-height: 70px;
            overflow: hidden;
            border-left: 1px solid #dbeafe;
            padding-left: 0.34rem;
        }
        .tv-pic-desc-list {
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
            padding-left: 0.78rem;
            font-size: clamp(0.56rem, 0.62vw, 0.72rem);
            font-weight: 800;
            line-height: 1.3;
            color: #334155;
        }
        .tv-task-grid-fabrikasi .tv-pic-item {
            min-height: 48px;
            padding: 0.16rem;
            grid-template-columns: 38px minmax(0, 1fr);
        }
        .tv-task-grid-fabrikasi .tv-pic-photo {
            height: 44px;
        }
        .tv-task-grid-fabrikasi .tv-pic-img,
        .tv-task-grid-fabrikasi .tv-pic-fallback {
            height: 31px;
        }
        .tv-task-grid-fabrikasi .tv-pic-name {
            height: 13px;
            font-size: 5.4px;
        }
        .tv-task-grid-fabrikasi .tv-pic-desc {
            max-height: 44px;
        }
        .tv-task-grid-fabrikasi .tv-pic-desc-list {
            -webkit-line-clamp: 3;
            font-size: clamp(0.46rem, 0.5vw, 0.58rem);
            line-height: 1.16;
        }
        .tv-task-grid-fabrikasi .tv-pic-wrap {
            margin-top: 0.22rem;
            padding-top: 0.2rem;
        }
        .tv-board-main > section:nth-child(2) .tv-pic-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        @media (max-height: 820px) {
            .ticker {
                height: 32px;
                margin-bottom: 0.35rem !important;
            }
            .ticker-item {
                padding-top: 6px;
                padding-bottom: 6px;
                font-size: 12px;
            }
            .tv-board-header {
                height: 78px;
                margin-bottom: 0.35rem !important;
                padding-top: 0.35rem;
                padding-bottom: 0.35rem;
            }
            .tv-logo-box {
                height: 44px;
                width: 44px;
            }
            .tv-board-title {
                font-size: clamp(1.05rem, 1.55vw, 1.55rem);
            }
            .tv-board-date {
                margin-top: 0.2rem;
                font-size: clamp(0.55rem, 0.65vw, 0.74rem);
            }
            .tv-board-time {
                font-size: clamp(1rem, 1.45vw, 1.35rem);
            }
            .tv-regu-section {
                padding: 0.45rem;
            }
            .tv-regu-heading {
                margin-bottom: 0.3rem;
                min-height: 20px;
            }
            .tv-regu-title {
                font-size: clamp(0.72rem, 0.85vw, 0.9rem);
            }
            .tv-task-card {
                padding: 0.32rem;
                border-radius: 0.65rem;
            }
            .tv-task-grid {
                gap: 0.35rem;
            }
            .tv-task-grid-fabrikasi .tv-card-title {
                font-size: clamp(0.58rem, 0.7vw, 0.82rem);
                -webkit-line-clamp: 2;
            }
            .tv-task-grid-fabrikasi .tv-card-meta {
                margin-top: 0.25rem;
                padding: 0.25rem 0.36rem;
            }
            .tv-task-grid-fabrikasi .tv-card-meta-text {
                font-size: clamp(0.46rem, 0.52vw, 0.58rem);
                line-height: 0.68rem;
            }
            .tv-task-grid-fabrikasi .tv-card-meta .border-t {
                padding-top: 0.18rem !important;
            }
            .tv-task-grid-fabrikasi .tv-pic-wrap {
                margin-top: 0.16rem;
                padding-top: 0.16rem;
            }
            .tv-task-grid-fabrikasi .tv-pic-item {
                min-height: 42px;
                padding: 0.14rem;
                grid-template-columns: 34px minmax(0, 1fr);
            }
            .tv-task-grid-fabrikasi .tv-pic-photo {
                height: 39px;
            }
            .tv-task-grid-fabrikasi .tv-pic-img,
            .tv-task-grid-fabrikasi .tv-pic-fallback {
                height: 27px;
            }
            .tv-task-grid-fabrikasi .tv-pic-name {
                height: 12px;
                font-size: 5px;
            }
            .tv-task-grid-fabrikasi .tv-pic-desc {
                max-height: 39px;
            }
            .tv-task-grid-fabrikasi .tv-pic-desc-list {
                -webkit-line-clamp: 2;
                font-size: clamp(0.42rem, 0.46vw, 0.52rem);
                line-height: 1.12;
            }
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
