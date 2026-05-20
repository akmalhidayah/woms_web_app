@php
    $loginUrl = \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login');
    $logoSigPath = public_path('assets/branding/logos/logo-sig.png');
    $logoStPath = public_path('assets/branding/logos/logo-st2.png');
    $logoSig = file_exists($logoSigPath) ? asset('assets/branding/logos/logo-sig.png') : null;
    $logoSt = file_exists($logoStPath) ? asset('assets/branding/logos/logo-st2.png') : null;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - PMMS Workshop Overhaul</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #172033;
            background: linear-gradient(135deg, #f8fafc 0%, #eef4f8 52%, #f7f9ec 100%);
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .error-card {
            width: min(100%, 520px);
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid #dbe4ea;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.12);
            padding: 32px;
            text-align: center;
        }
        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            min-height: 48px;
            margin-bottom: 24px;
        }
        .brand img {
            max-height: 44px;
            max-width: 150px;
            object-fit: contain;
        }
        .eyebrow {
            margin: 0 0 10px;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        h1 {
            margin: 0;
            color: #0f172a;
            font-size: clamp(44px, 12vw, 76px);
            line-height: 1;
            font-weight: 800;
        }
        h2 {
            margin: 14px 0 0;
            color: #16213a;
            font-size: 24px;
            line-height: 1.2;
            font-weight: 750;
        }
        p {
            margin: 12px auto 0;
            max-width: 390px;
            color: #475569;
            font-size: 15px;
            line-height: 1.6;
        }
        .actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 28px;
            flex-wrap: wrap;
        }
        .button {
            appearance: none;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 11px 16px;
            min-width: 132px;
            background: #ffffff;
            color: #1e293b;
            cursor: pointer;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
        }
        .button.primary {
            border-color: #1d4f91;
            background: #1d4f91;
            color: #ffffff;
        }
        .footer {
            margin-top: 22px;
            color: #64748b;
            font-size: 12px;
            font-weight: 650;
        }
        @media (max-width: 420px) {
            .error-card { padding: 26px 20px; }
            .actions { flex-direction: column; }
            .button { width: 100%; }
        }
    </style>
</head>
<body>
    <main class="error-card">
        @if ($logoSig || $logoSt)
            <div class="brand">
                @if ($logoSig)
                    <img src="{{ $logoSig }}" alt="SIG">
                @endif
                @if ($logoSt)
                    <img src="{{ $logoSt }}" alt="Semen Tonasa">
                @endif
            </div>
        @endif

        <p class="eyebrow">PMMS / Workshop / Overhaul</p>
        <h1>{{ $code }}</h1>
        <h2>{{ $title }}</h2>
        <p>{{ $message }}</p>

        <div class="actions">
            <button class="button" type="button" onclick="history.back()">Kembali</button>
            <a class="button primary" href="{{ $loginUrl }}">Ke Halaman Login</a>
        </div>

        <div class="footer">Dept PMMS - Semen Tonasa</div>
    </main>
</body>
</html>
