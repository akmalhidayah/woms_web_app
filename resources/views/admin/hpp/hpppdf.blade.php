<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HPP - {{ $hpp->nomor_order }}</title>
    <style>
        @page {
            margin: 5mm;
            size: A4 landscape;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            padding: 2px;
        }

        table {
            width: 98%;
            border-collapse: collapse;
        }

        td, th {
            padding: 3px;
            vertical-align: top;
        }

        .no-border td, .no-border th {
            border: none !important;
            padding: 5px;
        }

        .table-hpp {
            font-size: 8px;
        }

        .table-hpp th, .table-hpp td {
            padding: 2px;
            font-size: 8px;
        }

        .table-hpp th {
            font-weight: bold;
            background-color: #B0C4DE;
        }

        .table-hpp tr {
            page-break-inside: avoid;
        }

        .sig-box {
            position: relative;
            height: 46px;
            overflow: visible;
            padding-bottom: 0;
        }

        .sig-box > img {
            position: absolute;
            left: 50%;
            bottom: -20px;
            transform: translateX(-50%);
            height: 240px;
            max-width: 100%;
            width: auto;
            object-fit: contain;
            display: block;
            z-index: 2;
            filter:
                brightness(0)
                contrast(860%)
                drop-shadow(.8px .8px .8px rgba(0, 0, 0, .5));
        }

        .sig-fallback {
            font-size: 18px;
            font-weight: 700;
            position: absolute;
            left: 50%;
            bottom: 4px;
            transform: translateX(-50%);
            z-index: 2;
        }

        .sig-date {
            font-size: 9px;
            text-align: right;
            color: #333;
            margin: 0 4px 2px 0;
            line-height: 1;
            z-index: 3;
        }

        .sig-inline {
            height: 20px;
            width: auto;
            object-fit: contain;
            vertical-align: middle;
            margin-right: 2px;
            filter:
                brightness(0)
                contrast(650%)
                drop-shadow(.6px .6px .8px rgba(0, 0, 0, .5));
        }

        .sig-initial {
            font-size: 9px;
            margin-right: 4px;
            vertical-align: middle;
        }
    </style>
</head>
@php
    use Illuminate\Support\Facades\Storage;

    $order = $hpp->order;
    $outlineAgreement = $hpp->outlineAgreement;

    $safeSignaturePath = function (?string $relativePath): ?string {
        if (! $relativePath) {
            return null;
        }

        try {
            if (! Storage::disk('signatures')->exists($relativePath)) {
                return null;
            }

            return Storage::disk('signatures')->path($relativePath);
        } catch (\Throwable $e) {
            return null;
        }
    };

    $formatDate = function ($value): string {
        if (empty($value)) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $formatMoney = function ($value): string {
        $amount = (float) ($value ?? 0);

        return $amount > 0 ? number_format($amount, 0, ',', '.') : '';
    };

    $formatQty = function ($value): string {
        if ($value === null || $value === '') {
            return '';
        }

        $formatted = number_format((float) $value, 3, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',');
    };

    $initials = function (?string $value): string {
        $parts = preg_split('/\s+/', trim((string) $value)) ?: [];
        $letters = collect($parts)
            ->filter()
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->take(3)
            ->implode('');

        return $letters !== '' ? $letters : 'N/A';
    };

    $resolvePublicImage = function (array $paths): ?string {
        foreach ($paths as $path) {
            $absolutePath = public_path($path);

            if (is_file($absolutePath)) {
                return $absolutePath;
            }
        }

        return null;
    };

    $nomorOrder = $hpp->nomor_order ?: '-';
    $deskripsi = $order?->deskripsi ?: ($hpp->nama_pekerjaan ?: '-');
    $costCentre = $hpp->cost_centre ?: '-';
    $rencanaPemakaian = $order?->target_selesai ? $formatDate($order->target_selesai) : '-';
    $unitKerjaPeminta = $order?->seksi ?: ($hpp->unit_kerja ?: '-');
    $unitKerjaPengendali = $outlineAgreement?->jenis_kontrak ?: ($hpp->unit_kerja_pengendali ?: '-');
    $unitPemintaLabel = $hpp->unit_kerja ?: '-';
    $unitPengendaliLabel = $hpp->unit_kerja_pengendali ?: '-';
    $periodeOA = $hpp->periode_outline_agreement ?: '-';
    $requestingNotes = $order?->catatan ? [$order->catatan] : [];
    $controllingNotes = [];
    $creatorName = $hpp->creator?->name ?: 'N/A';
    $requestingInitials = $initials($hpp->creator?->name);
    $controllingInitials = 'N/A';

    $SIG_DIR = $safeSignaturePath(data_get($hpp, 'director_signature'));
    $SIG_GM = $safeSignaturePath(data_get($hpp, 'general_manager_signature'));
    $SIG_SM = $safeSignaturePath(data_get($hpp, 'senior_manager_signature'));
    $SIG_MG = $safeSignaturePath(data_get($hpp, 'manager_signature'));
    $SIG_REQ_GM = $safeSignaturePath(data_get($hpp, 'general_manager_signature_requesting_unit'));
    $SIG_REQ_SM = $safeSignaturePath(data_get($hpp, 'senior_manager_signature_requesting_unit'));
    $SIG_REQ_MG = $safeSignaturePath(data_get($hpp, 'manager_signature_requesting_unit'));

    $DT_DIR = $formatDate(data_get($hpp, 'director_signed_at'));
    $DT_GM = $formatDate(data_get($hpp, 'general_manager_signed_at'));
    $DT_SM = $formatDate(data_get($hpp, 'senior_manager_signed_at'));
    $DT_REQ_GM = $formatDate(data_get($hpp, 'general_manager_requesting_signed_at'));
    $DT_REQ_SM = $formatDate(data_get($hpp, 'senior_manager_requesting_signed_at'));
    $logoSigPath = $resolvePublicImage([
        'assets/branding/logos/logo-sig.png',
        'assets/branding/logos/logo-sig.jpg',
    ]);
    $logoStPath = $resolvePublicImage([
        'assets/branding/logos/logo-st.png',
        'assets/branding/logos/logo-st.jpg',
    ]);

    $groupsByJenis = [];
    $itemGroups = is_array($hpp->item_groups) ? $hpp->item_groups : [];

    foreach ($itemGroups as $group) {
        $label = trim((string) ($group['jenis_item'] ?? ''));
        $groupKey = $label !== '' ? $label : 'Lainnya';

        foreach (($group['items'] ?? []) as $item) {
            $groupsByJenis[$groupKey][] = [
                'nama' => $item['nama_item'] ?? '',
                'jumlah' => $item['jumlah_item'] ?? '',
                'qty' => $item['qty'] ?? null,
                'satuan' => $item['satuan'] ?? '',
                'harga_satuan' => $item['harga_satuan'] ?? null,
                'harga_total' => $item['harga_total'] ?? null,
                'keterangan' => $item['keterangan'] ?? '',
            ];
        }
    }

    $indexToLetters = function (int $index): string {
        $letters = '';
        $number = $index + 1;

        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $letters = chr(65 + $remainder).$letters;
            $number = intdiv($number - 1, 26);
        }

        return $letters;
    };

    $totalRows = 0;
    foreach ($groupsByJenis as $rows) {
        $totalRows += 1;
        $totalRows += count($rows);
    }
    if ($totalRows === 0) {
        $totalRows = 1;
    }
@endphp
<body>
<div class="container">
    <table class="no-border">
        <tr>
            <td style="width: 20%; text-align: left;">
                @if($logoSigPath)
                    <img src="{{ $logoSigPath }}" alt="Logo SIG" style="height: 70px;">
                @else
                    <span>Logo SIG</span>
                @endif
            </td>
            <td style="width: 60%; text-align: center;">
                <p style="font-size: 16px; font-weight: bold; line-height: 1;">HARGA PERKIRAAN PERANCANG (HPP)</p>
                <p style="font-size: 14px; font-weight: normal; line-height: 1;">KONTRAK JASA FABRIKASI KONSTRUKSI</p>
            </td>
            <td style="width: 20%; text-align: right;">
                @if($logoStPath)
                    <img src="{{ $logoStPath }}" alt="Logo Tonasa" style="height: 70px;">
                @else
                    <span>Logo Tonasa</span>
                @endif
            </td>
        </tr>
    </table>

    <table style="width: 100%; border: 1px solid black; border-collapse: collapse; font-size: 11px;">
        <tr>
            <td style="width: 60%; vertical-align: top; padding: 6px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 38%; font-weight: bold; padding: 2px 0;">ORDER NO</td>
                        <td style="width: 2%; text-align: center; padding: 2px 0;">:</td>
                        <td style="width: 60%; padding: 2px 0;">{{ $nomorOrder }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; padding: 2px 0;">DESKRIPSI</td>
                        <td style="text-align: center; padding: 2px 0;">:</td>
                        <td style="padding: 2px 0;">{{ $deskripsi }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; padding: 2px 0;">COST CENTRE</td>
                        <td style="text-align: center; padding: 2px 0;">:</td>
                        <td style="padding: 2px 0;">{{ $costCentre }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; padding: 2px 0;">RENCANA PEMAKAIAN</td>
                        <td style="text-align: center; padding: 2px 0;">:</td>
                        <td style="padding: 2px 0;">{{ $rencanaPemakaian }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; padding: 2px 0;">UNIT KERJA PEMINTA</td>
                        <td style="text-align: center; padding: 2px 0;">:</td>
                        <td style="padding: 2px 0;">{{ $unitKerjaPeminta }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; padding: 2px 0;">UNIT KERJA PENGENDALI</td>
                        <td style="text-align: center; padding: 2px 0;">:</td>
                        <td style="padding: 2px 0;">{{ $unitKerjaPengendali }}</td>
                    </tr>
                </table>
            </td>

            <td style="width: 18%; vertical-align: top; padding: 4px; border-left: 1px solid black;">
                <div style="border: 1px solid black; padding: 4px;">
                    <div style="text-align: center; font-weight: bold; border-bottom: 1px solid black; padding-bottom: 4px;">
                        FUNGSI PEMINTA
                    </div>

                    <table style="width: 100%; border-collapse: collapse; text-align: center;">
                        <tr>
                            <td style="width: 50%; border-right: 1px solid black; padding: 4px;">
                                <strong>GM Of</strong><br>
                                <span style="font-size: 10px;">{{ $unitPemintaLabel }}</span>
                            </td>
                            <td style="width: 50%; padding: 4px;">
                                <strong>SM Of</strong><br>
                                <span style="font-size: 10px;">{{ $unitPemintaLabel }}</span>
                            </td>
                        </tr>

                        <tr>
                            <td style="border-right: 1px solid black; padding: 4px; text-align: center; vertical-align: bottom;">
                                <div class="sig-box">
                                    <div class="sig-date">{{ $DT_REQ_GM }}</div>
                                    @if($SIG_REQ_GM)
                                        <img src="{{ $SIG_REQ_GM }}" alt="TTD GM Peminta">
                                    @else
                                        <strong class="sig-fallback">TTD</strong>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 4px; text-align: center; vertical-align: bottom;">
                                <div class="sig-box">
                                    <div class="sig-date">{{ $DT_REQ_SM }}</div>
                                    @if($SIG_REQ_SM)
                                        <img src="{{ $SIG_REQ_SM }}" alt="TTD SM Peminta">
                                    @else
                                        <strong class="sig-fallback">TTD</strong>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 4px; font-size: 10px;">
                                <strong>N/A</strong>
                            </td>
                            <td style="border-bottom: 1px solid black; padding: 4px; font-size: 10px;">
                                <strong>N/A</strong>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="3" style="border-top: 1px solid #000; border-bottom: 1px solid #000; text-align: right; padding: 2px 4px; vertical-align: middle;">
                                <strong class="sig-initial">{{ $requestingInitials }} /</strong>
                                @if($SIG_REQ_MG)
                                    <img src="{{ $SIG_REQ_MG }}" alt="Manager Signature" class="sig-inline">
                                @else
                                    <strong style="font-size: 9px; vertical-align: middle;">TTD</strong>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="overflow-x-auto">
        <table class="table-hpp" style="width: 100%; border-collapse: collapse; border: 1px solid black; font-size: 9px;">
            <thead style="background-color: #B0C4DE; color: #333;">
                <tr>
                    <th style="border: 1px solid black; padding: 5px; text-align: center; width: 14%;">OUTLINE AGREEMENT (OA)</th>
                    <th style="border: 1px solid black; padding: 5px; text-align: center;">URAIAN PEKERJAAN</th>
                    <th style="border: 1px solid black; padding: 5px; text-align: center; width: 6%;">QTY</th>
                    <th style="border: 1px solid black; padding: 5px; text-align: center; width: 10%;">SATUAN (EA/LOT/JAM/M2/KG)</th>
                    <th style="border: 1px solid black; padding: 5px; text-align: center; width: 12%;">HARGA SATUAN</th>
                    <th style="border: 1px solid black; padding: 5px; text-align: center; width: 12%;">JUMLAH</th>
                    <th style="border: 1px solid black; padding: 5px; text-align: center; width: 18%;">KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
            @if(empty($groupsByJenis))
                <tr>
                    <td style="border: 1px solid black; text-align: center;">{{ $hpp->outline_agreement ?? '' }}</td>
                    <td colspan="6" style="border: 1px solid black; text-align: center; padding: 6px;">Tidak ada data</td>
                </tr>
            @else
                @php $printedOA = false; $groupIndex = 0; @endphp

                @foreach ($groupsByJenis as $label => $items)
                    <tr>
                        @if (! $printedOA)
                            <td style="border: 1px solid black; text-align: center; vertical-align: top;" rowspan="{{ $totalRows }}">
                                {{ $hpp->outline_agreement ?? '' }}
                            </td>
                            @php $printedOA = true; @endphp
                        @endif

                        <td style="border: 1px solid black; padding: 4px 8px; font-weight: bold;">
                            {{ $indexToLetters($groupIndex) }}. {{ $label }}
                        </td>
                        <td style="border: 1px solid black;"></td>
                        <td style="border: 1px solid black;"></td>
                        <td style="border: 1px solid black;"></td>
                        <td style="border: 1px solid black;"></td>
                        <td style="border: 1px solid black;"></td>
                    </tr>

                    @foreach ($items as $it)
                        <tr>
                            <td style="border: 1px solid black; padding: 3px 12px;">
                                &nbsp;&nbsp;&nbsp;- {{ $it['nama'] }}@if($it['jumlah']) = {{ $it['jumlah'] }}@endif
                            </td>
                            <td style="border: 1px solid black; text-align: center;">
                                {{ $formatQty($it['qty']) }}
                            </td>
                            <td style="border: 1px solid black; text-align: center;">
                                {{ $it['satuan'] ?? '' }}
                            </td>
                            <td style="border: 1px solid black; text-align: right; padding-right: 6px;">
                                {{ $formatMoney($it['harga_satuan']) }}
                            </td>
                            <td style="border: 1px solid black; text-align: right; padding-right: 6px;">
                                {{ $formatMoney($it['harga_total']) }}
                            </td>
                            <td style="border: 1px solid black; padding: 4px;">
                                {{ $it['keterangan'] ?? '' }}
                            </td>
                        </tr>
                    @endforeach

                    @php $groupIndex++; @endphp
                @endforeach
            @endif

            <tr style="font-weight: bold; background-color: #DCDCDC;">
                <td colspan="5" style="border: 1px solid black; text-align: center;">TOTAL</td>
                <td style="border: 1px solid black; text-align: right; padding-right: 6px;">
                    {{ $formatMoney($hpp->total_keseluruhan) }}
                </td>
                <td style="border: 1px solid black;"></td>
            </tr>
            </tbody>
        </table>
    </div>

    <table style="width: 100%; border: 1px solid black; border-collapse: collapse;">
        <tr>
            <td style="width: 28%; border: 1px solid black; vertical-align: top; padding: 8px;">
                <strong>Catatan User Peminta:</strong><br>
                @if(!empty($requestingNotes))
                    @foreach($requestingNotes as $i => $note)
                        <div style="margin: 4px 0 8px;">
                            <div style="margin-bottom: 2px;">{{ $i + 1 }}. {{ $note ?: '-' }}</div>
                            <div style="font-size: 10px; color: #444;">- {{ $creatorName }}</div>
                        </div>
                    @endforeach
                @else
                    <div style="color: #666; font-size: 10px;">-</div>
                @endif
            </td>

            <td style="width: 28%; border: 1px solid black; vertical-align: top; padding: 8px;">
                <strong>Catatan Pengendali:</strong><br>
                @if(!empty($controllingNotes))
                    @foreach($controllingNotes as $i => $note)
                        <div style="margin: 4px 0 8px;">
                            <div style="margin-bottom: 2px;">{{ $i + 1 }}. {{ $note ?: '-' }}</div>
                            <div style="font-size: 10px; color: #444;">- {{ $unitPengendaliLabel }}</div>
                        </div>
                    @endforeach
                @else
                    <div style="color: #666; font-size: 10px;">-</div>
                @endif
            </td>

            <td style="width: 50%; border: 1px solid black;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid black;">
                    <tr>
                        <td style="width: 40%; border-right: 1px solid black; border-bottom: 1px solid black; font-weight: bold; font-style: italic; text-align: center; padding: 6px;">Menyetujui</td>
                        <td colspan="2" style="width: 60%; border-bottom: 1px solid black; font-weight: bold; font-style: italic; text-align: center; padding: 6px;">FUNGSI PENGENDALI</td>
                    </tr>
                    <tr>
                        <td style="width: 33%; border-right: 1px solid black; text-align: center; padding: 10px 6px;">
                            <strong>Director</strong> of Operation
                        </td>
                        <td style="width: 34%; border-right: 1px solid black; text-align: center; padding: 10px 6px;">
                            <strong>GM of</strong> {{ $unitPengendaliLabel }}
                        </td>
                        <td style="width: 33%; text-align: center; padding: 10px 6px;">
                            <strong>SM of</strong> {{ $unitPengendaliLabel }}
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 33%; border-right: 1px solid black; padding: 6px; font-size: 10px; text-align: right; vertical-align: top; color: #333;">
                            {{ $DT_DIR }}
                        </td>
                        <td style="width: 34%; border-right: 1px solid black; padding: 6px; font-size: 10px; text-align: right; vertical-align: top; color: #333;">
                            {{ $DT_GM }}
                        </td>
                        <td style="width: 33%; padding: 6px; font-size: 10px; text-align: right; vertical-align: top; color: #333;">
                            {{ $DT_SM }}
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 33%; border-right: 1px solid black; vertical-align: bottom; text-align: center; padding: 12px 6px;">
                            <div class="sig-box">
                                @if($SIG_DIR)
                                    <img src="{{ $SIG_DIR }}" alt="Director Signature">
                                @else
                                    <strong class="sig-fallback">TTD</strong>
                                @endif
                            </div>
                        </td>
                        <td style="width: 34%; border-right: 1px solid black; vertical-align: bottom; text-align: center; padding: 12px 6px;">
                            <div class="sig-box">
                                @if($SIG_GM)
                                    <img src="{{ $SIG_GM }}" alt="GM Signature">
                                @else
                                    <strong class="sig-fallback">TTD</strong>
                                @endif
                            </div>
                        </td>
                        <td style="width: 33%; vertical-align: bottom; text-align: center; padding: 12px 6px;">
                            <div class="sig-box">
                                @if($SIG_SM)
                                    <img src="{{ $SIG_SM }}" alt="SM Signature">
                                @else
                                    <strong class="sig-fallback">TTD</strong>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 33%; border-right: 1px solid black; border-bottom: 1px solid black; text-align: center; padding: 6px;">
                            <strong>N/A</strong>
                        </td>
                        <td style="width: 34%; border-right: 1px solid black; border-bottom: 1px solid black; text-align: center; padding: 6px;">
                            <strong>N/A</strong>
                        </td>
                        <td style="width: 33%; border-bottom: 1px solid black; text-align: center; padding: 6px;">
                            <strong>N/A</strong>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="border-top: 1px solid #000; border-bottom: 1px solid #000; text-align: right; padding: 2px 4px; vertical-align: middle;">
                            <div style="position: relative; display: inline-block; width: 100%;">
                                <strong class="sig-initial">{{ $controllingInitials }} /</strong>
                                @if($SIG_MG)
                                    <img src="{{ $SIG_MG }}" alt="Manager Signature" class="sig-inline">
                                @else
                                    <strong style="font-size: 9px; vertical-align: middle;">TTD</strong>
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
