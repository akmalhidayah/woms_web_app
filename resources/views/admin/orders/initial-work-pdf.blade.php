<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Initial Work - {{ $initialWork->nomor_initial_work }}</title>

    <style>
        @page { margin: 10mm; }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            padding: 4px;
            vertical-align: top;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }

        .border {
            border: 1px solid #000;
        }

        .no-border td,
        .no-border th {
            border: none !important;
            padding: 0;
        }

        .letter-meta {
            font-size: 10px;
            line-height: 1.45;
        }

        .letter-meta td {
            padding: 2px 0;
        }

        .meta-label {
            width: 18%;
        }

        .meta-colon {
            width: 4%;
            text-align: center;
        }

        .perihal-text {
            font-weight: bold;
            text-decoration: underline;
        }

        .top-date {
            font-size: 11px;
            text-align: right;
            margin-top: 8px;
        }

        .letterhead {
            margin-bottom: 28px;
        }

        .letterhead td {
            vertical-align: top;
        }

        .logo-sig {
            height: 56px;
        }

        .logo-st {
            height: 88px;
        }

        .recipient-block {
            font-size: 10px;
            line-height: 1.45;
        }

        .recipient-name {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .section-note {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .doc-info {
            width: 46%;
        }

        .doc-info td {
            padding: 2px 0;
        }

        .signature-wrap {
            width: 72%;
            margin-left: auto;
        }

        .signature-head {
            border: 1px solid #000;
            border-bottom: none;
            padding: 6px 8px;
            text-align: center;
            font-weight: bold;
        }

        .signature-table td {
            border: 1px solid #000;
            height: 118px;
            vertical-align: bottom;
            padding: 10px 8px;
        }

        .signature-date {
            text-align: right;
            font-size: 9px;
            margin-bottom: 8px;
        }

        .signature-image {
            height: 54px;
            text-align: center;
            margin-bottom: 4px;
        }

        .signature-image img {
            max-height: 54px;
            max-width: 150px;
        }

        .signature-placeholder {
            height: 58px;
        }

        .signature-name {
            width: 78%;
            margin: 0 auto 4px;
            border-bottom: 1px dotted #000;
            min-height: 14px;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
        }

        .signature-role {
            text-align: center;
            font-size: 9px;
            line-height: 1.3;
        }
    </style>
</head>

@php
    $logoSig = public_path('assets/branding/logos/logo-sig.png');
    $logoSt = public_path('assets/branding/logos/logo-st2.png');
    $documentDate = $initialWork->tanggal_initial_work
        ? \Carbon\Carbon::parse($initialWork->tanggal_initial_work)->translatedFormat('d F Y')
        : now()->translatedFormat('d F Y');
    $perihal = $initialWork->perihal ?: 'Surat Inisiasi Kerja';
    $signatures = $initialWork->signatures ?? collect();
    $managerSignature = $signatures->firstWhere('role_key', \App\Models\InitialWorkSignature::ROLE_MANAGER);
    $seniorSignature = $signatures->firstWhere('role_key', \App\Models\InitialWorkSignature::ROLE_SENIOR_MANAGER);
    $signaturePath = function ($signature) {
        if (! $signature?->isSigned() || ! $signature->signature_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->exists($signature->signature_path)
            ? \Illuminate\Support\Facades\Storage::disk('public')->path($signature->signature_path)
            : null;
    };
    $signatureDate = fn ($signature) => $signature?->signed_at
        ? \Carbon\Carbon::parse($signature->signed_at)->format('d/m/Y H:i')
        : '....................';
    $signatureRole = function ($signature, string $fallbackRole, string $fallbackScope): string {
        $scope = trim((string) ($fallbackScope ?: 'Workshop'));

        if ($signature?->role_key === \App\Models\InitialWorkSignature::ROLE_MANAGER) {
            $scope = trim((string) ($signature->source_section ?: $scope));

            return 'Manager of '.$scope;
        }

        if ($signature?->role_key === \App\Models\InitialWorkSignature::ROLE_SENIOR_MANAGER) {
            $scope = trim((string) ($signature->source_unit ?: $scope));

            return 'Senior Manager of '.$scope;
        }

        return trim($fallbackRole.' of '.$scope);
    };
    $unitSignatureTitle = 'PT. SEMEN TONASA - UNIT OF WORKSHOP';
@endphp

<body>


<table class="no-border letterhead">
    <tr>
        <td style="width:22%; text-align:left; vertical-align:middle;">
            @if (file_exists($logoSig))
                <img src="{{ $logoSig }}" class="logo-sig" alt="SIG">
            @endif
        </td>
        <td style="width:56%;">&nbsp;</td>
        <td style="width:22%; text-align:right; vertical-align:top;">
            @if (file_exists($logoSt))
                <img src="{{ $logoSt }}" class="logo-st" alt="Semen Tonasa">
            @endif
            <div class="top-date">Pangkep, {{ $documentDate }}</div>
        </td>
    </tr>
</table>

<table class="no-border letter-meta" style="width:78%;">
    <tr>
        <td class="meta-label">Nomor</td>
        <td class="meta-colon">:</td>
        <td>{{ $initialWork->nomor_initial_work }}</td>
    </tr>
    <tr>
        <td class="meta-label">Lampiran</td>
        <td class="meta-colon">:</td>
        <td>-</td>
    </tr>
    <tr>
        <td class="meta-label">Perihal</td>
        <td class="meta-colon">:</td>
        <td class="perihal-text">{{ $perihal }}</td>
    </tr>
</table>

<br>

<div class="recipient-block">
    <div class="bold">Yth.</div>
    <div class="recipient-name">{{ $initialWork->kepada_yth ?: 'PT. PRIMA KARYA MANUNGGAL' }}</div>
    <br>
    <div>di -</div>
    <div>tempat</div>
</div>

<br><br>

<table class="no-border doc-info">
    <tr>
        <td class="meta-label">Nomor Order</td>
        <td class="meta-colon">:</td>
        <td>{{ $initialWork->nomor_order }}</td>
    </tr>
    <tr>
        <td class="meta-label">Tanggal Dokumen</td>
        <td class="meta-colon">:</td>
        <td>{{ optional($initialWork->tanggal_initial_work)->format('d/m/Y') ?: '-' }}</td>
    </tr>
</table>

<br>

<table class="border">
    <thead>
        <tr class="bold text-center">
            <th class="border" style="width:5%;">No</th>
            <th class="border" style="width:22%;">Functional Location</th>
            <th class="border" style="width:35%;">Scope Pekerjaan</th>
            <th class="border" style="width:8%;">Qty</th>
            <th class="border" style="width:8%;">Stn</th>
            <th class="border" style="width:22%;">Keterangan</th>
        </tr>
    </thead>
    <tbody>
    @foreach (($initialWork->functional_location ?? []) as $i => $loc)
        <tr>
            <td class="border text-center">{{ $i + 1 }}</td>
            <td class="border">{{ $loc ?: '-' }}</td>
            <td class="border">{{ $initialWork->scope_pekerjaan[$i] ?? '-' }}</td>
            <td class="border text-center">{{ $initialWork->qty[$i] ?? '-' }}</td>
            <td class="border text-center">{{ $initialWork->stn[$i] ?? '-' }}</td>
            <td class="border">{{ $initialWork->keterangan[$i] ?? '-' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<br>

<div class="section-note">Keterangan Pekerjaan / Urgensi:</div>
{{ $initialWork->keterangan_pekerjaan ?: '-' }}

<br><br>

<div class="signature-wrap">
    <div class="signature-head">{{ $unitSignatureTitle }}</div>
    <table class="signature-table" style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:50%;">
                <div class="signature-date">{{ $signatureDate($seniorSignature) }}</div>
                @if ($signaturePath($seniorSignature))
                    <div class="signature-image"><img src="{{ $signaturePath($seniorSignature) }}" alt="Tanda Tangan Senior Manager"></div>
                @else
                    <div class="signature-placeholder"></div>
                @endif
                <div class="signature-name">{{ $seniorSignature?->isSigned() ? $seniorSignature->signer_name : '' }}</div>
                <div class="signature-role">{{ $signatureRole($seniorSignature, 'Senior Manager', $initialWork->unitWork?->name ?: 'Workshop') }}</div>
            </td>
            <td style="width:50%;">
                <div class="signature-date">{{ $signatureDate($managerSignature) }}</div>
                @if ($signaturePath($managerSignature))
                    <div class="signature-image"><img src="{{ $signaturePath($managerSignature) }}" alt="Tanda Tangan Manager"></div>
                @else
                    <div class="signature-placeholder"></div>
                @endif
                <div class="signature-name">{{ $managerSignature?->isSigned() ? $managerSignature->signer_name : '' }}</div>
                <div class="signature-role">{{ $signatureRole($managerSignature, 'Manager', $initialWork->unitWorkSection?->name ?: 'Workshop') }}</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
