@php
    $existingFiles = collect($files ?? []);
@endphp

<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
    <label class="block text-[12px] font-semibold text-slate-700">{{ $label }}</label>
    <input type="file" name="{{ $name }}[]" multiple accept="image/jpeg,image/png,image/webp" data-qc-photo-input data-preview-target="preview-{{ $name }}" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700">
    <div class="mt-1 text-[11px] text-slate-500">Format jpg, jpeg, png, webp. Maksimal 5MB per foto.</div>
    @error($name.'.*')
        <div class="mt-1 text-[11px] font-medium text-rose-600">{{ $message }}</div>
    @enderror

    <div id="preview-{{ $name }}" class="mt-3 hidden grid gap-3 sm:grid-cols-2 lg:grid-cols-3"></div>

    @if ($existingFiles->isNotEmpty())
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($existingFiles as $file)
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <img src="{{ route('admin.quality-control.files.preview', [$report, $file]) }}" alt="" class="h-32 w-full object-cover">
                    <div class="flex items-center justify-between gap-2 px-3 py-2">
                        <div class="min-w-0 truncate text-[11px] font-medium text-slate-600">{{ $file->original_name ?: basename($file->file_path) }}</div>
                        <button type="submit" form="delete-qc-file-{{ $file->id }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-rose-600 transition hover:bg-rose-100" title="Hapus foto">
                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
