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
    <tr>
        <td colspan="{{ max(1, $columnCount) }}" class="approval-inline">
            @forelse ($initials as $initial)
                <span class="sig-initial">{{ $initial['label'] }}:</span>
                @if($initial['signature'])
                    <img src="{{ $initial['signature'] }}" alt="{{ $initial['label'] }}" class="sig-inline">
                @else
                    <span class="sig-initial">{{ $initial['value'] }}</span>
                @endif
                @if (! $loop->last)
                    <span class="sig-initial" style="margin: 0 6px;">|</span>
                @endif
            @empty
                <span class="sig-initial">-</span>
            @endforelse
        </td>
    </tr>
</table>
