@php
    $isDraft = $inspection->isDraft();
    $inspectorSignature = $signatures->get('inspector');
    $managerSignature = $signatures->get('manager_workshop');
    $leaderSignature = $signatures->get('leader_gugus');
    $previousGroup = null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $inspection->form_name }}</title>
    <style>
        @page { margin: 22px 26px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #333; padding: 5px; vertical-align: top; word-wrap: break-word; }
        th { background: #ededed; font-weight: bold; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .center { text-align: center; }
        .masthead { page-break-inside: avoid; }
        .masthead td { border: 0; padding: 0 3px 5px; vertical-align: bottom; }
        .masthead .brand-logos { white-space: nowrap; vertical-align: middle; }
        .brand-logos img { vertical-align: middle; }
        .brand-sig { width: 62px; height: auto; }
        .brand-tonasa { width: 47px; height: auto; margin-left: 3px; }
        .title { font-size: 13px; font-weight: bold; }
        .task-record { font-size: 13px; font-weight: bold; font-style: italic; }
        .header-block { page-break-inside: avoid; }
        .small { font-size: 8px; }
        .meta { margin-bottom: 12px; page-break-inside: avoid; }
        .meta td { vertical-align: top; }
        .signature-space { height: 60px; text-align: center; }
        .signature-image { max-width: 115px; max-height: 48px; }
        .rating { text-align: center; vertical-align: middle; font-size: 15px; }
        .remark { white-space: pre-wrap; word-wrap: break-word; }
        .group td { background: #ededed; font-weight: bold; }
        .responsible { margin-top: 12px; page-break-inside: avoid; }
        .responsible td { vertical-align: middle; }
        .status { margin: 8px 0; font-size: 9px; }
        .watermark { position: fixed; top: 325px; left: 125px; font-size: 85px; color: #e6e6e6; transform: rotate(-35deg); z-index: -1000; }
        .photo-page { page-break-before: always; }
        .photo-box { margin-top: 12px; text-align: center; }
        .photo-box img { max-width: 490px; max-height: 310px; }
    </style>
</head>
<body>
    @if ($isDraft)<div class="watermark">DRAFT</div>@elseif ($inspection->status === \App\Models\EquipmentInspection::STATUS_REVISION)<div class="watermark">REVISI</div>@endif
    <div class="header-block">
    <table class="masthead">
        <colgroup><col style="width:27%"><col style="width:54%"><col style="width:19%"></colgroup>
        <tr>
            <td class="brand-logos">
                <img class="brand-sig" src="{{ public_path('assets/branding/logos/logo-sig.png') }}" alt="SIG">
                <img class="brand-tonasa" src="{{ public_path('assets/branding/logos/logo-st2.png') }}" alt="Semen Tonasa">
            </td>
            <td class="title center">{{ $inspection->form_name }}</td>
            <td class="task-record center">Task Record</td>
        </tr>
    </table>
    <table class="meta">
        <colgroup><col style="width:38%"><col style="width:24%"><col style="width:19%"><col style="width:19%"></colgroup>
        <tr>
            <td rowspan="2"><strong>Document No:</strong><br>{{ $inspection->document_no ?? 'Belum diterbitkan' }}<br><br><strong>Seksi:</strong> Bengkel Mesin<br><strong>Unit:</strong> Bengkel</td>
            <td rowspan="2" class="center"><strong>Process:</strong><br><br>Inspection &amp; List</td>
            <td class="center small"><strong>Approve<br>Manager Machine Workshop</strong></td>
            <td class="center small"><strong>Approve<br>Leader Gugus / Senior Manager TPM</strong></td>
        </tr>
        <tr>
            <td class="center"><div class="signature-space">@if (isset($signatureImages['manager_workshop']))<img class="signature-image" src="{{ $signatureImages['manager_workshop'] }}" alt="Tanda tangan Manager">@endif</div><span class="small">{{ $managerSignature?->signer_name }}<br>{{ $managerSignature?->signer_position }}<br>{{ $managerSignature?->signed_at->format('d/m/Y H:i') }}</span></td>
            <td class="center"><div class="signature-space">@if (isset($signatureImages['leader_gugus']))<img class="signature-image" src="{{ $signatureImages['leader_gugus'] }}" alt="Tanda tangan Leader Gugus">@endif</div><span class="small">{{ $leaderSignature?->signer_name }}<br>{{ $leaderSignature?->signer_position }}<br>{{ $leaderSignature?->signed_at->format('d/m/Y H:i') }}</span></td>
        </tr>
    </table>
    </div>
    <p class="status"><strong>{{ $inspection->status === \App\Models\EquipmentInspection::STATUS_APPROVED ? 'FINAL — Selesai' : ($isDraft ? 'DRAFT — Belum ditandatangani' : $inspection->statusLabel().' — Belum final') }}</strong> · Versi {{ $inspection->document_version }}@if ($isHistorical ?? false) · HISTORI — Tidak berlaku sebagai persetujuan versi aktif @endif</p>
    <table>
        <colgroup><col style="width:5%"><col style="width:33%"><col style="width:8%"><col style="width:8%"><col style="width:8%"><col style="width:38%"></colgroup>
        <thead>
            <tr><th rowspan="2">NO</th><th rowspan="2">ACTIVITY</th><th colspan="3">DESCRIPTION</th><th rowspan="2">REMARK</th></tr>
            <tr><th>A</th><th>B</th><th>C</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @php($answer = $row['answer'])
                @if ($answer->group_label && $previousGroup !== $answer->group_key)
                    <tr class="group"><td colspan="6">{{ $answer->group_label }}</td></tr>
                @endif
                @php($previousGroup = $answer->group_key)
                <tr>
                    <td class="center">{{ $row['continuation'] ? '' : $answer->position }}</td>
                    <td>{{ $answer->item_label }}{{ $row['continuation'] ? ' (lanjutan)' : '' }}</td>
                    @foreach (['A', 'B', 'C'] as $rating)<td class="rating">{{ ! $row['continuation'] && $answer->rating === $rating ? '✓' : '' }}</td>@endforeach
                    <td class="remark">{{ $row['remark'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="responsible">
        <colgroup><col style="width:24%"><col style="width:38%"><col style="width:38%"></colgroup>
        <tr><td><strong>Penanggung Jawab</strong></td><td>{{ $inspection->inspector_name }}</td><td><strong>Keterangan:</strong><br>A = Kondisi Normal</td></tr>
        <tr><td><strong>Paraf / Inspektor</strong></td><td class="center"><div class="signature-space">@if (isset($signatureImages['inspector']))<img class="signature-image" src="{{ $signatureImages['inspector'] }}" alt="Tanda tangan Inspektor">@endif</div>@if ($inspectorSignature)<span class="small">{{ $inspectorSignature->signer_position }}<br>TTD: {{ $inspectorSignature->signed_at->format('d/m/Y H:i') }}</span>@endif</td><td>B = Kondisi kurang Normal<br><br>C = Kondisi Rusak</td></tr>
        <tr><td><strong>Hari / Tanggal Pemeriksaan</strong></td><td>{{ $inspection->inspection_date->locale('id')->translatedFormat('l, d F Y') }}</td><td class="small">Hasil pemeriksaan mencatat kondisi peralatan. Approval tidak otomatis menyatakan alat layak digunakan.</td></tr>
    </table>

    @foreach ($photos as $photo)
        <div class="photo-page">
            <h2>Lampiran Foto Temuan</h2>
            <p><strong>{{ $inspection->form_name }}</strong><br>{{ $inspection->document_no ?? 'DRAFT — Belum diterbitkan' }}<br>Tanggal Pemeriksaan: {{ $inspection->inspection_date->format('d/m/Y') }}</p>
            <table>
                <tr><th style="width:22%">Item / Kelompok</th><td>{{ $photo['answer']->position }}{{ $photo['answer']->group_label ? ' / '.$photo['answer']->group_label : '' }}</td></tr>
                <tr><th>Aktivitas</th><td>{{ $photo['answer']->item_label }}</td></tr>
                <tr><th>Hasil</th><td>{{ $photo['answer']->rating ?: 'Belum diperiksa' }}</td></tr>
                @foreach ($photo['remarks'] as $chunk)
                    <tr><th>{{ $loop->first ? 'Remark' : 'Remark (lanjutan)' }}</th><td class="remark">{{ $chunk }}</td></tr>
                @endforeach
            </table>
            <div class="photo-box"><img src="{{ $photo['image'] }}" alt="Foto item {{ $photo['answer']->position }}"></div>
            <p class="small">Foto {{ $loop->iteration }} dari {{ count($photos) }}</p>
        </div>
    @endforeach
</body>
</html>
