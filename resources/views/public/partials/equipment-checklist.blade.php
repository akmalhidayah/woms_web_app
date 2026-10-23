<div class="checklist">
    @foreach ($inspection['items'] as $item)
        <div class="item">
            <div class="item-copy">
                @if (filled($item['group']))<small class="item-group">{{ $item['group'] }}</small>@endif
                <strong>{{ $item['label'] }}</strong>
                @if ($item['remark'])<p class="remark"><strong>Remarks:</strong> {{ $item['remark'] }}</p>@endif
            </div>
            <span class="rating {{ strtolower($item['rating']) }}" aria-label="{{ match ($item['rating']) { 'A' => 'Normal', 'B' => 'Kurang Normal', 'C' => 'Rusak' } }}">{{ $item['rating'] }}</span>
        </div>
    @endforeach
</div>
