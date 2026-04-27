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

        .case-banner {
            margin-bottom: 8px;
            border: 1px solid #000;
            background: #ff1c12;
            color: #000;
            font-size: 16px;
            text-align: center;
            padding: 8px 12px;
            text-transform: uppercase;
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

        .approval-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .approval-table td,
        .approval-table th {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        .approval-group-title {
            background: #e6f0db;
            font-weight: bold;
            text-transform: uppercase;
        }

        .approval-role {
            font-weight: bold;
            font-size: 9px;
            line-height: 1.1;
            min-height: 24px;
        }

        .approval-role-compact {
            font-size: 7.8px;
            line-height: 1.02;
        }

        .approval-signature {
            height: 54px;
            vertical-align: top;
            border-bottom: none !important;
        }

        .approval-name {
            height: 28px;
            vertical-align: bottom;
            font-size: 10px;
            font-weight: bold;
            border-top: none !important;
        }

        .approval-inline {
            font-size: 9px;
            text-align: right;
            white-space: nowrap;
        }

        .approval-inline-cell {
            font-size: 9px;
            text-align: center;
            white-space: nowrap;
            padding: 10px 4px 4px;
            position: relative;
        }

        .placeholder-line {
            display: inline-block;
            min-width: 88px;
            border-bottom: 1px dotted #000;
            height: 12px;
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
            padding: 10px 2px 0 2px;
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
            position: absolute;
            top: 0;
            right: 2px;
            font-size: 6.5px;
            color: #333;
            line-height: 1;
            text-align: right;
            white-space: nowrap;
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

        .sig-box > img {
            bottom: -6px;
            height: auto;
            max-height: 54px;
            max-width: 95%;
        }

        .sig-fallback {
            font-size: 14px;
            bottom: 2px;
        }

        .sig-date {
            top: 0;
            right: 2px;
            width: auto;
            font-size: 6px;
        }

        .sig-inline {
            height: 22px;
            margin-left: 4px;
            margin-right: 0;
            max-width: 54px;
        }

        .sig-initial {
            margin-right: 0;
        }

        .sig-inline-value {
            font-size: 8px;
            font-weight: bold;
            vertical-align: middle;
        }

        .sig-inline-date {
            position: absolute;
            top: 1px;
            right: 3px;
            float: none;
            font-size: 5px;
            color: #333;
            white-space: nowrap;
            line-height: 1;
            text-align: right;
        }

        .notes-cell {
            height: 120px;
            font-size: 9px;
        }

        .uraian-cell {
            padding: 3px 12px !important;
        }

        .uraian-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .uraian-table td {
            border: none !important;
            padding: 0 !important;
            vertical-align: top;
        }

        .uraian-bullet {
            width: 12px;
            padding-right: 6px !important;
            text-align: left;
        }

        .uraian-main {
            width: 66%;
            padding-right: 14px !important;
            text-align: left;
        }

        .uraian-detail {
            width: 32%;
            padding-left: 18px !important;
            white-space: normal;
            text-align: left;
            overflow-wrap: anywhere;
            word-break: break-word;
            font-size: 8px;
        }
    </style>
</head>
@php
    use App\Models\UnitWork;
    use App\Support\HppApprovalFlow;
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

    $buildApprovalCell = function (string $title, ?string $signaturePath = null, string $date = '-', string $name = 'N/A'): array {
        return [
            'title' => $title,
            'signature' => $signaturePath,
            'date' => $date,
            'name' => $name,
        ];
    };

    $nomorOrder = $hpp->nomor_order ?: '-';
    $deskripsi = $order?->nama_pekerjaan ?: ($hpp->nama_pekerjaan ?: ($order?->deskripsi ?: '-'));
    $costCentre = $hpp->cost_centre ?: '-';
    $rencanaPemakaian = $order?->target_selesai ? $formatDate($order->target_selesai) : '-';
    $unitKerjaPeminta = $order?->seksi ?: ($hpp->unit_kerja ?: '-');
    $unitKerjaPengendali = $outlineAgreement?->jenis_kontrak ?: ($hpp->unit_kerja_pengendali ?: '-');
    $unitPemintaLabel = $hpp->unit_kerja ?: '-';
    $unitPengendaliLabel = $hpp->unit_kerja_pengendali ?: '-';
    $requestingUnit = $hpp->unit_kerja
        ? UnitWork::query()
            ->with('department:id,name')
            ->where('name', $hpp->unit_kerja)
            ->first()
        : null;
    $requestingDepartmentLabel = $requestingUnit?->department?->name ?: $unitPemintaLabel;
    $controllingDepartmentLabel = $outlineAgreement?->unitWork?->department?->name ?: $unitPengendaliLabel;
    $periodeOA = $hpp->periode_outline_agreement ?: '-';
    $creatorName = $hpp->creator?->name ?: 'N/A';
    $requestingInitials = $initials($hpp->creator?->name);
    $controllingInitials = 'N/A';

    $logoSigPath = $resolvePublicImage([
        'assets/branding/logos/logo-sig.png',
        'assets/branding/logos/logo-sig.jpg',
    ]);
    $logoStPath = $resolvePublicImage([
        'assets/branding/logos/logo-st.png',
        'assets/branding/logos/logo-st.jpg',
    ]);

    $approvalCase = $hpp->approval_case
        ?: HppApprovalFlow::resolvePreviewCase($hpp->kategori_pekerjaan, $hpp->area_pekerjaan, $hpp->nilai_hpp_bucket)
        ?: '';

    $hppSignatures = ($hpp->relationLoaded('signatures') ? $hpp->signatures : $hpp->signatures()->get())
        ->keyBy('role_key');
    $isWorkshopCase = str_starts_with($approvalCase, 'FAB-WORKSHOP');
    $controllerManagerRole = $isWorkshopCase ? 'workshop_manager_pengendali' : 'manager_pengendali';
    $controllerSmRole = $isWorkshopCase ? 'workshop_sm_pengendali' : 'sm_pengendali';
    $controllerGmRole = $isWorkshopCase ? 'workshop_gm_pengendali' : 'gm_pengendali';
    $requesterManagerRole = $isWorkshopCase ? 'workshop_manager_pengendali' : 'manager_peminta';

    $signatureFor = fn (string $roleKey) => $hppSignatures->get($roleKey);
    $signatureImage = fn ($signature): ?string => $signature?->isSigned() ? $signature->signature_data : null;
    $signatureDate = function ($signature): string {
        if (! $signature?->signed_at) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($signature->signed_at)->format('d/m/Y H:i');
        } catch (\Throwable $e) {
            return (string) $signature->signed_at;
        }
    };
    $signatureName = fn ($signature): string => $signature?->signer_name_snapshot ?: 'N/A';
    $signatureTitle = fn ($signature, string $fallback): string => $signature?->signer_position_snapshot ?: $fallback;
    $approvalRoleClass = fn (?string $title): string => mb_strlen(trim((string) $title)) > 28
        ? 'approval-role approval-role-compact'
        : 'approval-role';
    $noteContextLabel = function ($signature): ?string {
        if (! $signature) {
            return null;
        }

        $roleKey = (string) $signature->role_key;

        if (str_contains($roleKey, 'gm')) {
            return $signature->signer_department_snapshot
                ?: $signature->signer_unit_snapshot
                ?: $signature->signer_section_snapshot;
        }

        if (str_contains($roleKey, 'sm') || str_contains($roleKey, 'planner')) {
            return $signature->signer_unit_snapshot
                ?: $signature->signer_department_snapshot
                ?: $signature->signer_section_snapshot;
        }

        return $signature->signer_section_snapshot
            ?: $signature->signer_unit_snapshot
            ?: $signature->signer_department_snapshot;
    };
    $requesterNoteRoles = [
        'manager_peminta',
        'sm_peminta',
        'gm_peminta',
        'workshop_manager_pengendali',
        'planner_control',
        'sm_counter_part',
        'manager_counter_part',
    ];
    $noteEntries = $hppSignatures
        ->filter(fn ($signature) => $signature?->isSigned() && filled(trim((string) $signature->approval_note)))
        ->map(fn ($signature): array => [
            'role_key' => $signature->role_key,
            'text' => trim((string) $signature->approval_note),
            'author' => $signature->signer_name_snapshot ?: 'N/A',
            'context' => $noteContextLabel($signature),
        ])
        ->values();
    $requestingNotes = $noteEntries
        ->filter(fn (array $note): bool => in_array($note['role_key'], $requesterNoteRoles, true))
        ->values()
        ->all();
    $controllingNotes = $noteEntries
        ->reject(fn (array $note): bool => in_array($note['role_key'], $requesterNoteRoles, true))
        ->values()
        ->all();

    $cellFromRole = function (string $roleKey, string $fallbackTitle) use (
        $buildApprovalCell,
        $signatureFor,
        $signatureImage,
        $signatureDate,
        $signatureName,
        $signatureTitle,
    ): array {
        $signature = $signatureFor($roleKey);

        return $buildApprovalCell(
            $signatureTitle($signature, $fallbackTitle),
            $signatureImage($signature),
            $signatureDate($signature),
            $signatureName($signature),
        );
    };

    $initialFromRole = function (string $roleKey, string $fallbackLabel, string $fallbackValue = 'N/A') use (
        $signatureFor,
        $signatureImage,
        $signatureDate,
        $initials,
    ): array {
        $signature = $signatureFor($roleKey);
        $name = $signature?->signer_name_snapshot;

        return [
            'label' => $signature?->role_label ?: $fallbackLabel,
            'signature' => $signatureImage($signature),
            'value' => $name ? $initials($name) : $fallbackValue,
            'date' => $signatureDate($signature),
            'signed' => (bool) $signature?->isSigned(),
        ];
    };

    /*
    $caseBanner = match ($approvalCase) {
        'KONS-DALAM-UNDER250' => 'FORM PEKERJAAN KONSTRUKSI < 250 JT USER T.2,3,4&5, PELABUHAN BIRINGKASSI & PACKING PLANT',
        'KONS-DALAM-OVER250' => 'FORM PEKERJAAN KONSTRUKSI > 250 JT USER T.2,3,4&5, PELABUHAN BIRINGKASSI & PACKING PLANT',
        'FAB-WORKSHOP-UNDER250' => 'FORM PEKERJAAN FABRIKASI & BUBUTAN < 250 JT USER UNIT WORKSHOP',
        'FAB-WORKSHOP-OVER250' => 'FORM PEKERJAAN FABRIKASI & BUBUTAN > 250 JT USER UNIT WORKSHOP',
        'FAB-DALAM-UNDER250' => 'FORM PEKERJAAN FABRIKASI (PLATE WORK & MACHINING) < 250 JT USER T.23,4,5, PELABUHAN BKS & PACKING PLANT',
        'FAB-DALAM-OVER250' => 'FORM PEKERJAAN FABRIKASI (PLATE WORK & MACHINING) > 250 JT USER T.23,4,5, PELABUHAN BKS & PACKING PLANT',
        'FAB-LUAR-UNDER250' => 'FORM PEKERJAAN FABRIKASI & BUBUTAN < 250 JT',
        'FAB-LUAR-OVER250' => 'FORM PEKERJAAN FABRIKASI & BUBUTAN > 250 JT',
        'KONS-LUAR-UNDER250' => 'FORM PEKERJAAN KONSTRUKSI BTG & CUS < 250 JT',
        'KONS-LUAR-OVER250' => 'FORM PEKERJAAN KONSTRUKSI BTG & CUS > 250 JT',
        default => 'FORM HARGA PERKIRAAN PERANCANG (HPP)',
    };
    */

    $requesterManagerInitial = $initialFromRole('manager_peminta', 'Manager Peminta', $requestingInitials);
    $controllerManagerInitial = $initialFromRole($controllerManagerRole, 'Manager Pengendali');
    $counterPartManagerInitial = $initialFromRole('manager_counter_part', 'Manager Counter Part');

    $plannerControlCell = $cellFromRole('planner_control', 'Planner Control');
    $counterPartCell = $cellFromRole('sm_counter_part', 'SM of Counter Part');
    $directorCell = $cellFromRole('dirops', 'Director of Operation');
    $gmControllerCell = $cellFromRole($controllerGmRole, 'GM of '.$controllingDepartmentLabel);
    $smControllerCell = $cellFromRole($controllerSmRole, 'SM of '.$unitPengendaliLabel);
    $gmRequesterCell = $cellFromRole('gm_peminta', 'GM of '.$requestingDepartmentLabel);
    $smRequesterCell = $cellFromRole('sm_peminta', 'SM of '.$unitPemintaLabel);
    $managerRequesterCell = $cellFromRole(
        $requesterManagerRole,
        $isWorkshopCase ? 'Mgr of '.$unitPengendaliLabel : 'Mgr of '.$unitPemintaLabel,
    );

    $approvalFamily = match (true) {
        str_starts_with($approvalCase, 'FAB-DALAM') => 'fabrikasi-dalam',
        str_starts_with($approvalCase, 'FAB-WORKSHOP') => 'fabrikasi-workshop',
        str_starts_with($approvalCase, 'FAB-LUAR') => 'fabrikasi-luar',
        str_starts_with($approvalCase, 'KONS-DALAM') => 'konstruksi-dalam',
        str_starts_with($approvalCase, 'KONS-LUAR') => 'konstruksi-luar',
        default => 'fabrikasi-dalam',
    };

    $approvalPartial = 'admin.hpp.partials.pdf.approval.'.$approvalFamily;
    $isOver = str_contains($approvalCase, 'OVER250');

    $groupsByJenis = [];
    $itemGroups = is_array($hpp->item_groups) ? $hpp->item_groups : [];

    foreach ($itemGroups as $group) {
        $label = trim((string) ($group['jenis_item'] ?? ''));
        $groupKey = $label !== '' ? $label : 'Lainnya';
        $groupsByJenis[$groupKey] ??= [];

        foreach (($group['items'] ?? []) as $item) {
            $kategoriLabel = trim((string) ($item['kategori_item'] ?? ''));
            $lastEntryIndex = array_key_last($groupsByJenis[$groupKey]);

            if (
                $lastEntryIndex === null
                || ($groupsByJenis[$groupKey][$lastEntryIndex]['label'] ?? '') !== $kategoriLabel
            ) {
                $groupsByJenis[$groupKey][] = [
                    'label' => $kategoriLabel,
                    'items' => [],
                ];
                $lastEntryIndex = array_key_last($groupsByJenis[$groupKey]);
            }

            $groupsByJenis[$groupKey][$lastEntryIndex]['items'][] = [
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
    foreach ($groupsByJenis as $kategoriGroups) {
        $totalRows += 1;

        foreach ($kategoriGroups as $kategoriGroup) {
            if (($kategoriGroup['label'] ?? '') !== '') {
                $totalRows += 1;
            }

            $totalRows += count($kategoriGroup['items'] ?? []);
        }
    }
    if ($totalRows === 0) {
        $totalRows = 1;
    }
@endphp
<body>
<div class="container">
    {{-- <div class="case-banner">{{ $caseBanner }}</div> --}}

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

            <td style="width: 40%; vertical-align: top; padding: 0; border-left: 1px solid black;">
                @include($approvalPartial, ['position' => 'top'])
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

                @foreach ($groupsByJenis as $label => $kategoriGroups)
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

                    @foreach ($kategoriGroups as $kategoriGroup)
                        @if(($kategoriGroup['label'] ?? '') !== '')
                            <tr>
                                <td style="border: 1px solid black; padding: 4px 18px; font-weight: bold;">
                                    {{ $kategoriGroup['label'] }}
                                </td>
                                <td style="border: 1px solid black;"></td>
                                <td style="border: 1px solid black;"></td>
                                <td style="border: 1px solid black;"></td>
                                <td style="border: 1px solid black;"></td>
                                <td style="border: 1px solid black;"></td>
                            </tr>
                        @endif

                        @foreach (($kategoriGroup['items'] ?? []) as $it)
                            <tr>
                                <td class="uraian-cell" style="border: 1px solid black;">
                                    <table class="uraian-table">
                                        <tr>
                                            <td class="uraian-bullet">-</td>
                                            <td class="uraian-main">{{ $it['nama'] }}</td>
                                            <td class="uraian-detail">
                                                @if($it['jumlah'])
                                                    {{ $it['jumlah'] }}
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
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
            <td class="notes-cell" style="width: 28%; border: 1px solid black; vertical-align: top; padding: 8px;">
                <strong>Catatan User Peminta:</strong><br>
                @if(!empty($requestingNotes))
                    @foreach($requestingNotes as $i => $note)
                        <div style="margin: 4px 0 8px; line-height: 1.35;">
                            <span>{{ $i + 1 }}. {{ $note['text'] ?? '-' }}</span>
                            <span style="font-size: 9px; color: #444;"> - {{ $note['author'] ?? 'N/A' }}{{ !empty($note['context']) ? ' ('.$note['context'].')' : '' }}</span>
                        </div>
                    @endforeach
                @else
                    <div style="color: #666; font-size: 10px;">-</div>
                @endif
            </td>

            <td class="notes-cell" style="width: 28%; border: 1px solid black; vertical-align: top; padding: 8px;">
                <strong>Catatan Pengendali:</strong><br>
                @if(!empty($controllingNotes))
                    @foreach($controllingNotes as $i => $note)
                        <div style="margin: 4px 0 8px; line-height: 1.35;">
                            <span>{{ $i + 1 }}. {{ $note['text'] ?? '-' }}</span>
                            <span style="font-size: 9px; color: #444;"> - {{ $note['author'] ?? 'N/A' }}{{ !empty($note['context']) ? ' ('.$note['context'].')' : '' }}</span>
                        </div>
                    @endforeach
                @else
                    <div style="color: #666; font-size: 10px;">-</div>
                @endif
            </td>

            <td style="width: 50%; border: 1px solid black; padding: 0;">
                @include($approvalPartial, ['position' => 'bottom'])
            </td>
        </tr>
    </table>
</div>
</body>
</html>
