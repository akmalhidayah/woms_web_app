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
        .ticker { position: relative; background:#7f1d1d; color:#fff; overflow:hidden; white-space:nowrap; height:46px; }
        .ticker span { display:inline-block; padding:10px 24px; animation: ticker 15s linear infinite; font-size:16px; font-weight:700; }
        @keyframes ticker { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }
    </style>
</head>

<body class="bg-slate-100 min-h-screen overflow-hidden">
    <livewire:dashboard-pekerjaan mode="display" />

    @livewireScripts

    @once
    <script>
        document.addEventListener('livewire:init', () => {
            if (window.__dpSlideTimer) clearInterval(window.__dpSlideTimer);
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

            window.__dpSlideTimer = setInterval(() => {
                if (window.Livewire?.dispatch) {
                    window.Livewire.dispatch('nextSlide');
                }
            }, 20000);
        });
    </script>
    @endonce
</body>
</html>
