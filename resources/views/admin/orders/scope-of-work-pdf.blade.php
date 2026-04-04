<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scope of Work - {{ $order->nomor_order }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 40px; line-height: 1.5; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid black; padding: 6px 8px; }
        th { background: #f2f2f2; text-align: left; }
        .text-center { text-align: center; }
        .no-border td { border: none !important; }
        .logo { height: 65px; }
        .section-title { font-size: 14px; font-weight: bold; margin: 15px 0 8px 0; }
    </style>
</head>
<body>

    <table class="no-border" style="margin-bottom: 10px;">
        <tr>
            <td style="width:20%; text-align:left;">
                <img src="{{ public_path('assets/branding/logos/logo-sig.png') }}" class="logo" alt="SIG">
            </td>
            <td style="width:60%; text-align:center; vertical-align:middle;">
                <h2 style="margin:0; font-size:18px;">SCOPE OF WORK</h2>
            </td>
            <td style="width:20%; text-align:right;">
                <img src="{{ public_path('assets/branding/logos/logo-st2.png') }}" class="logo" alt="Semen Tonasa">
            </td>
        </tr>
    </table>

    <table>
        <tr><th style="width:35%;">Order No</th><td>{{ $order->nomor_order }}</td></tr>
        <tr><th>Nama Pekerjaan</th><td>{{ $order->nama_pekerjaan }}</td></tr>
        <tr><th>Unit Kerja</th><td>{{ $order->unit_kerja }}</td></tr>
        <tr><th>Tanggal Pemakaian</th><td>{{ optional($scopeOfWork->tanggal_pemakaian)->format('Y-m-d') ?: '-' }}</td></tr>
        <tr><th>Tanggal Dokumen</th><td>{{ optional($scopeOfWork->tanggal_dokumen)->format('Y-m-d') }}</td></tr>
    </table>

    <div class="section-title">Rincian Scope of Work</div>
    <table>
        <thead>
            <tr>
                <th style="width:5%;">No</th>
                <th style="width:40%;">Scope Pekerjaan</th>
                <th style="width:10%;">Qty</th>
                <th style="width:15%;">Satuan</th>
                <th style="width:30%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scopeItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item['scope_pekerjaan'] ?? '-' }}</td>
                    <td class="text-center">{{ $item['qty'] ?? '-' }}</td>
                    <td class="text-center">{{ $item['satuan'] ?? '-' }}</td>
                    <td>{{ $item['keterangan'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Catatan:</div>
    <p>{{ $scopeOfWork->catatan ?: '-' }}</p>

    <table class="no-border" style="margin-top:50px;">
        <tr>
            <td style="width:65%;"></td>
            <td style="width:35%; text-align:center;">
                <p>Yang Membuat,</p>
                @if(!empty($signaturePath) && file_exists($signaturePath))
                    <img src="{{ $signaturePath }}" style="max-height:80px; margin:15px 0;" alt="Tanda Tangan">
                @else
                    <div style="height:80px; margin:20px 0; border-bottom:1px solid #000;"></div>
                @endif
                <p style="margin:0; font-weight:bold;">{{ $scopeOfWork->nama_penginput }}</p>
                <p style="margin:0;">{{ $order->unit_kerja }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
