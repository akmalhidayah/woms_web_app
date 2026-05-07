<table class="header">
    <tr>
        <td style="width: 18%; text-align: center;">
            @if (is_file($sigLogo))
                <img src="{{ $sigLogo }}" class="logo" alt="SIG">
            @endif
        </td>
        <td class="title">QUALITY CONTROL FABRICATION RECORD</td>
        <td style="width: 28%;">
            <div><strong>Report No.</strong>: {{ $report->report_no ?: '-' }}</div>
            <div style="margin-top: 8px;"><strong>Tanggal</strong>: {{ optional($report->report_date)->format('d-m-Y') ?: '-' }}</div>
        </td>
    </tr>
</table>

<table class="meta" style="margin-top: 8px;">
    <tr>
        <td style="width: 16%;"><strong>Unit</strong></td>
        <td>{{ $order->seksi ?: '-' }}</td>
        <td style="width: 16%;"><strong>No.Order</strong></td>
        <td>{{ $order->nomor_order ?: '-' }}</td>
    </tr>
    <tr>
        <td><strong>Pekerjaan</strong></td>
        <td colspan="3">{{ $order->nama_pekerjaan ?: '-' }}</td>
    </tr>
</table>
