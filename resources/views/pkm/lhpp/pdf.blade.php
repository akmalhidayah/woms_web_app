<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAST {{ $lhpp->termin_type === 'termin_2' ? 'Termin 2' : 'Termin 1' }} - {{ $lhpp->nomor_order }}</title>
    <style>
        @page {
            margin: 6mm;
            size: A4 portrait;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .page {
            width: 100%;
        }

        .title {
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 2px 4px;
            vertical-align: top;
            font-size: 9px;
        }

        .meta-label {
            width: 220px;
            font-weight: 400;
            text-transform: uppercase;
        }

        .meta-separator {
            width: 12px;
            text-align: center;
            font-weight: 700;
        }

        .meta-value {
            font-weight: 700;
        }

        .spacer {
            height: 8px;
        }

        .value-box {
            width: 33%;
            margin-left: auto;
            border: 1px solid #222;
        }

        .value-box th,
        .value-box td {
            border: 1px solid #222;
            padding: 4px 8px;
            font-size: 9px;
        }

        .value-box th {
            text-align: center;
            font-size: 10px;
            font-weight: 700;
        }

        .value-box td {
            background: #d9d9d9;
            text-align: right;
            font-weight: 700;
            height: 22px;
        }

        .detail-table th,
        .detail-table td {
            border: 1px solid #222;
            padding: 4px 4px;
            font-size: 9px;
            vertical-align: top;
        }

        .detail-table {
            table-layout: fixed;
        }

        .detail-table th {
            background: #e9e9e9;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
            line-height: 1.1;
        }

        .detail-left-title {
            text-align: left !important;
        }

        .detail-header-unit {
            font-size: 6px;
            font-weight: 400;
        }

        .detail-no {
            width: 30px;
            text-align: center;
        }

        .detail-name {
            width: 50%;
        }

        .detail-volume {
            width: 15%;
            text-align: center;
            font-size: 8px !important;
            line-height: 1.05;
        }

        .detail-price,
        .detail-total {
            width: 14%;
            text-align: center;
        }

        .row-text-right {
            text-align: right;
        }

        .subtotal-row td {
            background: #fff2cc;
            font-weight: 700;
        }

        .summary-row td {
            font-weight: 700;
            font-size: 10px;
        }

        .summary-row-gray td {
            background: #d9d9d9;
        }

        .summary-label {
            text-align: left;
        }

        .summary-value {
            text-align: right;
            width: 19%;
        }

        .qc-table {
            width: 235px;
            margin-top: 18px;
        }

        .qc-table th,
        .qc-table td {
            border: 1px solid #222;
            padding: 6px 10px;
            font-size: 9px;
            font-weight: 700;
        }

        .qc-table th {
            background: #e9e9e9;
            text-align: left;
        }

        .qc-mark {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 16px;
            font-weight: 700;
            text-align: center;
            line-height: 1;
        }

        .signature-wrapper {
            margin-top: 16px;
        }

        .signature-main {
            width: 72%;
            float: left;
        }

        .signature-pkm {
            width: 19%;
            float: right;
        }

        .signature-table td,
        .signature-table th,
        .signature-pkm-table td,
        .signature-pkm-table th {
            border: 1px solid #222;
            padding: 4px 6px;
            font-size: 9px;
        }

        .signature-table,
        .signature-pkm-table {
            table-layout: fixed;
        }

        .signature-table .signed-header {
            text-align: center;
            font-size: 9px;
            font-weight: 400;
        }

        .signature-table .date-row td,
        .signature-pkm-table .date-row td {
            height: 18px;
            font-size: 8px;
        }

        .signature-role {
            text-align: center;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            height: 42px;
            line-height: 1.2;
        }

        .signature-space {
            height: 110px;
            vertical-align: bottom;
            text-align: center;
        }

        .signature-name {
            font-weight: 700;
            font-size: 8px;
            text-align: center;
        }

        .signature-initial {
            height: 16px;
            display: inline-block;
            margin-right: 4px;
            vertical-align: middle;
        }

        .signature-fallback {
            font-size: 10px;
            font-weight: 700;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        .page-break {
            page-break-before: always;
        }

        .image-page-title {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .image-table td {
            width: 50%;
            border: 1px solid #222;
            padding: 8px;
            vertical-align: top;
        }

        .image-card-title {
            font-size: 9px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .image-preview {
            width: 100%;
            height: 300px;
            object-fit: contain;
        }

        .image-empty {
            height: 300px;
            text-align: center;
            font-size: 10px;
            color: #777;
            line-height: 300px;
        }
    </style>
</head>
<body>
@php
    $isOver250 = $lhpp->approval_threshold === 'over_250';
    $isTerminTwo = $lhpp->termin_type === 'termin_2';
    $title = $isOver250
        ? 'BERITA ACARA SERAH TERIMA (BAST) PEKERJAAN'
        : 'BERITA ACARA SERAH TERIMA (BAST) REALISASI PEKERJAAN';

    $materialItems = collect($materialItems ?? []);
    $serviceItems = collect($serviceItems ?? []);

    $formatMoney = static fn ($value) => number_format((float) $value, 0, ',', '.');
    $formatItemMoney = static function ($value) {
        $normalized = preg_replace('/[^\d\-]/', '', (string) $value);
        return number_format((float) ($normalized !== '' ? $normalized : 0), 0, ',', '.');
    };
    $formatDate = static fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->translatedFormat('d F Y') : '';
    $currentPurchaseOrderNumber = $lhpp->order?->purchaseOrder?->purchase_order_number
        ?: $lhpp->purchaseOrder?->purchase_order_number
        ?: $lhpp->purchase_order_number;
    $resolveImageSource = static function ($relativePath) {
        if (! $relativePath) {
            return null;
        }

        $normalized = ltrim((string) $relativePath, '/');
        $candidates = [
            storage_path('app/public/'.$normalized),
            public_path('storage/'.$normalized),
            public_path($normalized),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $mimeType = mime_content_type($candidate) ?: 'image/png';
                $contents = @file_get_contents($candidate);

                if ($contents !== false) {
                    return 'data:'.$mimeType.';base64,'.base64_encode($contents);
                }
            }
        }

        return null;
    };

    $isWithoutWarranty = ! $isTerminTwo && (int) ($lhpp->garansi?->garansi_months ?? -1) === 0;

    $terminDisplayLabel = $isWithoutWarranty
        ? 'TOTAL DIBAYAR'
        : ($isTerminTwo
        ? 'TERMIN 2 (5% x Total Actual Biaya)'
        : 'TERMIN 1 (95% x Total Actual Biaya)');

    $terminDisplayValue = $isWithoutWarranty
        ? (float) $lhpp->total_aktual_biaya
        : ($isTerminTwo
        ? (float) $lhpp->termin_2_nilai
        : (float) $lhpp->termin_1_nilai);
    $qualityControlStatus = $lhpp->quality_control_status ?? 'pending';
    $rawImageItems = collect($lhpp->images ?? []);

    if ($isTerminTwo && $lhpp->parentLhppBast) {
        $rawImageItems = collect($lhpp->parentLhppBast->images ?? [])->concat($rawImageItems);
    }

    $imageItems = $rawImageItems
        ->map(function ($image) use ($resolveImageSource) {
            return [
                'name' => $image->file_name ?: basename((string) $image->file_path),
                'src' => $resolveImageSource($image->file_path),
            ];
        })
        ->unique(fn (array $image): string => (string) ($image['name'].'|'.$image['src']))
        ->values();

    $approvalRoles = $isOver250
        ? [
            'DIRECTOR OF OPERATION',
            'GM OF PROJECT MANAG. & MAINT.SUPPORT',
            'MGR.OF MACHINE WORKSHOP',
            'MANAGER OF (USER)',
        ]
        : [
            'GM OF PROJECT MANAG. & MAINT.SUPPORT',
            'MGR.OF MACHINE WORKSHOP',
            'MANAGER OF (USER)',
        ];

    $signatureNames = $isOver250
        ? [
            null,
            null,
            $lhpp->manager_signature ?: null,
            $lhpp->manager_signature_requesting ?: null,
        ]
        : [
            null,
            $lhpp->manager_signature ?: null,
            $lhpp->manager_signature_requesting ?: null,
        ];

    $renderSignature = static function ($value) use ($resolveImageSource) {
        if (! $value) {
            return '';
        }

        if (preg_match('/\.(png|jpg|jpeg|webp)$/i', (string) $value)) {
            $src = $resolveImageSource($value);

            if ($src) {
                return '<img src="'.$src.'" class="signature-initial" alt="ttd">';
            }
        }

        return '';
    };
@endphp

    <div class="page">
        <div class="title">{{ $title }}</div>

        <table class="meta-table">
            <tr>
                <td class="meta-label">TANGGAL BAST</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $formatDate($lhpp->tanggal_bast) }}</td>
            </tr>
            <tr>
                <td class="meta-label">NOMOR ORDER</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $lhpp->nomor_order }}</td>
            </tr>
            <tr>
                <td class="meta-label">DESKRIPSI PEKERJAAN</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $lhpp->deskripsi_pekerjaan }}</td>
            </tr>
            <tr>
                <td class="meta-label">TIPE PEKERJAAN</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ \App\Models\LhppBast::tipePekerjaanLabel($lhpp->tipe_pekerjaan) }}</td>
            </tr>
            <tr>
                <td class="meta-label">UNIT KERJA PEMINTA (USER)</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $lhpp->seksi ?: $lhpp->unit_kerja }}</td>
            </tr>
            <tr>
                <td class="meta-label">PURCHASING ORDER (P.O)</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $currentPurchaseOrderNumber }}</td>
            </tr>
            <tr>
                <td class="meta-label">TANGGAL DIMULAINYA PEKERJAAN</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $formatDate($lhpp->tanggal_mulai_pekerjaan) }}</td>
            </tr>
            <tr>
                <td class="meta-label">TANGGAL SELESAINYA PEKERJAAN</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $formatDate($lhpp->tanggal_selesai_pekerjaan) }}</td>
            </tr>
        </table>

        <div class="spacer"></div>

        <table class="value-box">
            <tr>
                <th>NILAI HPP (Rupiah)</th>
            </tr>
            <tr>
                <td>Rp {{ $formatMoney($lhpp->nilai_hpp) }}</td>
            </tr>
        </table>

        <div class="spacer"></div>

        <table class="detail-table">
            <colgroup>
                <col style="width: 4%;">
                <col style="width: 52%;">
                <col style="width: 17%;">
                <col style="width: 14%;">
                <col style="width: 13%;">
            </colgroup>
            <thead>
                <tr>
                    <th class="detail-no">NO.</th>
                    <th class="detail-left-title detail-name">A. AKTUAL PEMAKAIAN MATERIAL</th>
                    <th class="detail-volume">TOTAL DURASI/VOLUME/LUASAN PEKERJAAN<br><span class="detail-header-unit">(Jam/Kg/M2/CM3/Liter)</span></th>
                    <th class="detail-price">HARGA SATUAN<br><span class="detail-header-unit">( Rp )</span></th>
                    <th class="detail-total">JUMLAH<br><span class="detail-header-unit">( Rp )</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($materialItems as $index => $item)
                    <tr>
                        <td class="detail-no">{{ $index + 1 }}</td>
                        <td>{{ $item['name'] ?? '' }}</td>
                        <td class="row-text-right">{{ trim(($item['volume'] ?? '').' '.($item['unit'] ?? '')) }}</td>
                        <td class="row-text-right">{{ $formatItemMoney($item['unit_price'] ?? 0) }}</td>
                        <td class="row-text-right">{{ $formatMoney($item['amount'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="detail-no">1</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="row-text-right">0</td>
                    </tr>
                @endforelse
                <tr class="subtotal-row">
                    <td colspan="4">SUB TOTAL ( A )</td>
                    <td class="row-text-right">{{ $formatMoney($lhpp->subtotal_material) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="detail-table">
            <colgroup>
                <col style="width: 4%;">
                <col style="width: 52%;">
                <col style="width: 17%;">
                <col style="width: 14%;">
                <col style="width: 13%;">
            </colgroup>
            <thead>
                <tr>
                    <th class="detail-no">NO.</th>
                    <th class="detail-left-title detail-name">B. AKTUAL BIAYA JASA</th>
                    <th class="detail-volume">TOTAL DURASI/VOLUME/LUASAN PEKERJAAN<br><span class="detail-header-unit">(Jam/Kg/M2/CM3/Liter)</span></th>
                    <th class="detail-price">HARGA SATUAN<br><span class="detail-header-unit">( Rp )</span></th>
                    <th class="detail-total">JUMLAH<br><span class="detail-header-unit">( Rp )</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($serviceItems as $index => $item)
                    <tr>
                        <td class="detail-no">{{ $index + 1 }}</td>
                        <td>{{ $item['name'] ?? '' }}</td>
                        <td class="row-text-right">{{ trim(($item['volume'] ?? '').' '.($item['unit'] ?? '')) }}</td>
                        <td class="row-text-right">{{ $formatItemMoney($item['unit_price'] ?? 0) }}</td>
                        <td class="row-text-right">{{ $formatMoney($item['amount'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="detail-no">1</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="row-text-right">0</td>
                    </tr>
                @endforelse
                <tr class="subtotal-row">
                    <td colspan="4">SUB TOTAL ( B )</td>
                    <td class="row-text-right">{{ $formatMoney($lhpp->subtotal_jasa) }}</td>
                </tr>
                <tr class="summary-row">
                    <td colspan="4" class="summary-label">TOTAL AKTUAL BIAYA ( A + B )</td>
                    <td class="summary-value">{{ $formatMoney($lhpp->total_aktual_biaya) }}</td>
                </tr>
                <tr class="summary-row summary-row-gray">
                    <td colspan="4" class="summary-label">{{ $terminDisplayLabel }}</td>
                    <td class="summary-value">{{ $formatMoney($terminDisplayValue) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="qc-table">
            <tr>
                <th colspan="2">HASIL QUALITY CONTROL</th>
            </tr>
            <tr>
                <td>APPROVE</td>
                <td class="qc-mark">{!! $qualityControlStatus === 'approved' ? '&#10003;' : '' !!}</td>
            </tr>
            <tr>
                <td>REJECT</td>
                <td class="qc-mark">{!! $qualityControlStatus === 'rejected' ? '&#10003;' : '' !!}</td>
            </tr>
        </table>

        <div class="signature-wrapper clearfix">
            <div class="signature-main">
                <table class="signature-table">
                    <colgroup>
                        @if ($isOver250)
                            <col style="width: 26%;">
                            <col style="width: 24.6667%;">
                            <col style="width: 24.6667%;">
                            <col style="width: 24.6666%;">
                        @else
                            <col style="width: 33.3333%;">
                            <col style="width: 33.3333%;">
                            <col style="width: 33.3334%;">
                        @endif
                    </colgroup>
                    <tr>
                        <td colspan="{{ count($approvalRoles) }}" class="signed-header">Menyetujui,</td>
                    </tr>
                    <tr class="date-row">
                        @foreach ($approvalRoles as $role)
                            <td>Tanggal :</td>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($approvalRoles as $role)
                            <td class="signature-role">{{ $role }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($signatureNames as $signatureName)
                            <td class="signature-space">{!! $renderSignature($signatureName) !!}</td>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($signatureNames as $signatureName)
                            <td class="signature-name">{{ is_string($signatureName) ? $signatureName : '' }}</td>
                        @endforeach
                    </tr>
                </table>
            </div>

            <div class="signature-pkm">
                <table class="signature-pkm-table">
                    <tr class="date-row">
                        <td>Tanggal :</td>
                    </tr>
                    <tr>
                        <td class="signature-role">PT. PKM</td>
                    </tr>
                    <tr>
                        <td class="signature-space">{!! $renderSignature($lhpp->manager_pkm_signature) !!}</td>
                    </tr>
                    <tr>
                        <td class="signature-name">{{ is_string($lhpp->manager_pkm_signature) ? $lhpp->manager_pkm_signature : '' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    @if ($imageItems->isNotEmpty())
        <div class="page-break"></div>

        <div class="page">
            <div class="image-page-title">Gambar Pekerjaan</div>

            <table class="image-table">
                <tbody>
                    @foreach ($imageItems->chunk(2) as $chunk)
                        <tr>
                            @foreach ($chunk as $image)
                                <td>
                                    <div class="image-card-title">{{ $image['name'] }}</div>
                                    @if ($image['src'])
                                        <img src="{{ $image['src'] }}" alt="{{ $image['name'] }}" class="image-preview">
                                    @else
                                        <div class="image-empty">Gambar tidak ditemukan</div>
                                    @endif
                                </td>
                            @endforeach

                            @if ($chunk->count() === 1)
                                <td></td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</body>
</html>
