@php
    $activeRequesterCells = array_values($specialRequesterCells ?? []);
    $requesterColumnCount = max(count($activeRequesterCells), 1);
@endphp
<table class="approval-table">
    <tr>
        <th class="approval-group-title" colspan="{{ $requesterColumnCount }}">FUNGSI PEMINTA</th>
    </tr>

    @if ($activeRequesterCells !== [])
        <tr>
            @foreach ($activeRequesterCells as $cell)
                <td class="{{ $approvalRoleClass($cell['title']) }}">{{ $cell['title'] }}</td>
            @endforeach
        </tr>
        <tr>
            @foreach ($activeRequesterCells as $cell)
                <td class="approval-signature">
                    <div class="sig-box">
                        <div class="sig-date">{{ $cell['date'] }}</div>
                        @if ($cell['signature'])
                            <img src="{{ $cell['signature'] }}" alt="{{ $cell['title'] }}">
                        @elseif ($cell['show_placeholder'] ?? true)
                            <strong class="sig-fallback"><span class="placeholder-line"></span></strong>
                        @endif
                    </div>
                </td>
            @endforeach
        </tr>
        <tr>
            @foreach ($activeRequesterCells as $cell)
                <td class="approval-name">{{ $cell['name'] }}</td>
            @endforeach
        </tr>
    @else
        <tr>
            <td class="approval-role">Manager Peminta (Paraf)</td>
        </tr>
        <tr>
            <td class="approval-signature">
                <div class="sig-box">
                    <div class="sig-date">{{ $requesterManagerInitial['date'] }}</div>
                    @if ($requesterManagerInitial['signature'])
                        <img src="{{ $requesterManagerInitial['signature'] }}" alt="{{ $requesterManagerInitial['label'] }}">
                    @else
                        <strong class="sig-fallback"><span class="placeholder-line"></span></strong>
                    @endif
                </div>
            </td>
        </tr>
    @endif

    @if ($activeRequesterCells !== [])
        <tr>
            <td class="approval-inline-cell" colspan="{{ $requesterColumnCount }}">
                @if ($requesterManagerInitial['signature'])
                    <span class="sig-initial sig-inline-value">{{ $requesterManagerInitial['value'] }} /</span>
                    <img src="{{ $requesterManagerInitial['signature'] }}" alt="{{ $requesterManagerInitial['label'] }}" class="sig-inline">
                    <span class="sig-inline-date">{{ $requesterManagerInitial['date'] }}</span>
                @else
                    <span class="sig-initial">{{ $requesterManagerInitial['label'] }}:</span>
                    <span class="sig-initial">{{ $requesterManagerInitial['value'] }}</span>
                @endif
            </td>
        </tr>
    @endif
</table>
