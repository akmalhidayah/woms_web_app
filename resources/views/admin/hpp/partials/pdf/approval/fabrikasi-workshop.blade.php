@php
    $groups = ($position ?? 'top') === 'top'
        ? [['label' => 'FUNGSI PEMINTA', 'cells' => [$managerRequesterCell]]]
        : [['label' => 'FUNGSI PENGENDALI', 'cells' => $isOver ? [$directorCell, $gmControllerCell, $smControllerCell] : [$gmControllerCell, $smControllerCell]]];

    $initials = [];
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
                <td class="{{ $approvalRoleClass($cell['title']) }}">{{ $cell['title'] }}</td>
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
</table>
