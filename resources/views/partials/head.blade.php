<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name', 'Workshop Order Management System') }}</title>

<link rel="icon" type="image/png" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">
<link rel="shortcut icon" type="image/png" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">
<link rel="apple-touch-icon" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">

@unless (request()->routeIs('login'))
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet" />
@endunless

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
