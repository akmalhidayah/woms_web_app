@php
    $groups = ($position ?? 'top') === 'top'
        ? [['label' => 'FUNGSI PEMINTA', 'cells' => [$gmRequesterCell, $smRequesterCell]]]
        : [['label' => 'FUNGSI PENGENDALI', 'cells' => $isOver ? [$directorCell, $gmControllerCell, $smControllerCell] : [$gmControllerCell, $smControllerCell]]];

    $initials = ($position ?? 'top') === 'top'
        ? [$requesterManagerInitial]
        : [$controllerManagerInitial];

    $columnCount = collect($groups)->sum(fn (array $group): int => count($group['cells']));
@endphp
<table class="approval-table">
    <tr>
        @foreach ($groups as $group)
            <th class="approval-group-title" colspan="{{ count($group['cells']) }}">{{ $group['label'] }}</th>
        @endforeach
    </tr>
    <tr>
        @foreach ($groups as $group)
            @foreach ($group['cells'] as $cell)
                <td class="approval-role">{{ $cell['title'] }}</td>
            @endforeach
        @endforeach
    </tr>
    <tr>
        @foreach ($groups as $group)
            @foreach ($group['cells'] as $cell)
                <td class="approval-signature">
                    <div class="sig-box">
                        <div class="sig-date">{{ $cell['date'] }}</div>
                        @if($cell['signature'])
                            <img src="{{ $cell['signature'] }}" alt="{{ $cell['title'] }}">
                        @else
                            <strong class="sig-fallback"><span class="placeholder-line"></span></strong>
                        @endif
                    </div>
                </td>
            @endforeach
        @endforeach
    </tr>
    <tr>
        @foreach ($groups as $group)
            @foreach ($group['cells'] as $cell)
                <td class="approval-name">{{ $cell['name'] }}</td>
            @endforeach
        @endforeach
    </tr>
    @if (($position ?? 'top') === 'top')
        <tr>
            <td class="approval-inline-cell"></td>
            <td class="approval-inline-cell">
                <span class="sig-initial">{{ $requesterManagerInitial['label'] }}:</span>
                @if($requesterManagerInitial['signature'])
                    <img src="{{ $requesterManagerInitial['signature'] }}" alt="{{ $requesterManagerInitial['label'] }}" class="sig-inline">
                @else
                    <span class="sig-initial">{{ $requesterManagerInitial['value'] }}</span>
                @endif
            </td>
        </tr>
    @else
        <tr>
            @if($isOver)
                <td class="approval-inline-cell"></td>
            @endif
            <td class="approval-inline-cell"></td>
            <td class="approval-inline-cell">
                <span class="sig-initial">{{ $controllerManagerInitial['label'] }}:</span>
                @if($controllerManagerInitial['signature'])
                    <img src="{{ $controllerManagerInitial['signature'] }}" alt="{{ $controllerManagerInitial['label'] }}" class="sig-inline">
                @else
                    <span class="sig-initial">{{ $controllerManagerInitial['value'] }}</span>
                @endif
            </td>
        </tr>
    @endif
</table>
