<table class="top">
    <tr>
        <td style="width: 18%; text-align: center;">
            @if (is_file($sigLogo))
                <img src="{{ $sigLogo }}" class="logo" alt="SIG">
            @endif
        </td>
        <td class="company">
            PT. SEMEN TONASA<br>
            MACHINE WORKSHOP
        </td>
        <td style="width: 18%; text-align: center;">
            @if (is_file($stLogo))
                <img src="{{ $stLogo }}" class="logo" alt="Semen Tonasa">
            @endif
        </td>
    </tr>
</table>

<div class="title">
    LEMBAR KERJA REFURBISH
    <span class="title-report">Report No: {{ $report->report_no ?: '-' }}</span>
</div>

<table>
    <tr>
        <td style="width: 15%;"><strong>Tanggal diterima</strong></td>
        <td>{{ $payload['received_date'] ?? '-' }}</td>
        <td style="width: 13%;"><strong>Tanggal selesai</strong></td>
        <td>{{ $payload['finished_date'] ?? '-' }}</td>
        <td style="width: 12%;"><strong>Working days</strong></td>
        <td>{{ $payload['working_days'] ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>No Notifikasi</strong></td>
        <td>{{ $payload['notification_number'] ?? ($order->notifikasi ?: $order->nomor_order) }}</td>
        <td><strong>Unit kerja</strong></td>
        <td>{{ $payload['unit_work'] ?? ($order->seksi ?: '-') }}</td>
        <td><strong>No section</strong></td>
        <td>{{ filled($payload['section_number'] ?? null) ? $payload['section_number'] : '-' }}</td>
    </tr>
    <tr>
        <td><strong>Jenis peralatan</strong></td>
        <td colspan="3">{{ $payload['equipment_type'] ?? $order->nama_pekerjaan }}</td>
        <td><strong>Plant</strong></td>
        <td>{{ $payload['plant'] ?? '-' }}</td>
    </tr>
</table>
