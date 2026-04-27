<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pekerjaan Bengkel</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/logos/logo-bms2.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script defer src="https://unpkg.com/lucide@latest"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif; }
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
