@php
    $materialPrefix = $prefix ?? 'create';
    $materialId = $materialPrefix.'PelayananMaterial';
    $materialChecked = old('form_context') === $materialPrefix && (bool) old('pelayanan_material');
@endphp

<div class="order-material-cell">
    <label class="order-material-toggle" for="{{ $materialId }}">
        <input type="hidden" name="pelayanan_material" value="0">
        <input
            id="{{ $materialId }}"
            name="pelayanan_material"
            type="checkbox"
            value="1"
            data-material-hint="{{ $materialId }}Hint"
            @checked($materialChecked)
        >
        <span>Pelayanan Material</span>
    </label>
    <p id="{{ $materialId }}Hint" class="order-material-hint" @if (! $materialChecked) hidden @endif>
        Tidak melalui Serah Terima.
    </p>
    @if (old('form_context') === $materialPrefix)
        @error('pelayanan_material')
            <p class="order-material-hint">{{ $message }}</p>
        @enderror
    @endif
</div>
