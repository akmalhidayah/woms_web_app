<article class="grid min-w-0 grid-cols-[2rem_minmax(0,1fr)] gap-3 rounded-xl border border-slate-200 p-4 lg:grid-cols-[2.5rem_minmax(0,1fr)_12rem_minmax(0,1.2fr)] lg:gap-4 lg:rounded-none lg:border-x-0 lg:border-t-0" :class="{ 'bg-amber-50': answers['{{ $item['id'] }}'].rating === 'B', 'bg-red-50': answers['{{ $item['id'] }}'].rating === 'C' }">
    <span class="text-sm font-bold text-slate-500">{{ $itemNumber }}</span>
    <div class="min-w-0">
        <h4 class="break-words text-sm font-bold leading-5">{{ $item['label'] }}</h4>
        <p x-show="hasFinding('{{ $item['id'] }}')" class="mt-2 text-xs font-semibold text-red-800" x-cloak>Keterangan temuan wajib sebelum tanda tangan.</p>
    </div>
    <input type="hidden" name="answers[{{ $item['id'] }}][rating]" :value="answers['{{ $item['id'] }}'].rating">
    <input type="hidden" name="answers[{{ $item['id'] }}][remark]" :value="answers['{{ $item['id'] }}'].remark">
    <fieldset class="col-span-2 min-w-0 lg:col-span-1" @disabled($readOnly)>
        <legend class="sr-only">Kondisi item {{ $itemNumber }}: {{ $item['label'] }}</legend>
        <div class="grid grid-cols-3 gap-2">
            @foreach ($ratings as $value => $rating)
                <label class="cursor-pointer">
                    <input type="radio" name="choice-{{ $item['id'] }}" value="{{ $value }}" x-model="answers['{{ $item['id'] }}'].rating" class="peer sr-only">
                    <span class="flex min-h-14 flex-col items-center justify-center rounded-xl border border-slate-300 bg-white px-1 py-2 text-center text-xs font-bold text-slate-600 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-red-700 {{ $rating['classes'] }}">
                        <span class="text-base" x-text="answers['{{ $item['id'] }}'].rating === '{{ $value }}' ? '✓ {{ $value }}' : '{{ $value }}'">{{ $value }}</span>
                        <span class="mt-0.5 text-[10px]">{{ $rating['label'] }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </fieldset>
    <div class="col-span-2 min-w-0 space-y-2 lg:col-span-1">
        <label for="remark-{{ $item['id'] }}" class="block text-xs font-semibold text-slate-600">Keterangan <span x-text="hasFinding('{{ $item['id'] }}') ? '(wajib sebelum TTD)' : '(opsional)'"></span></label>
        <textarea id="remark-{{ $item['id'] }}" x-model="answers['{{ $item['id'] }}'].remark" rows="3" maxlength="2000" @readonly($readOnly) class="block w-full min-w-0 resize-y rounded-xl border border-slate-300 bg-white px-3 py-2 text-base leading-5 sm:text-sm" placeholder="Kondisi atau temuan pemeriksaan..."></textarea>
        @foreach ($attachmentsByItem[$item['id']] ?? [] as $file)
            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-2">
                <a href="{{ route('inspector.inspections.attachments.show', [$inspection, $file]) }}" target="_blank" rel="noopener" class="shrink-0"><img src="{{ route('inspector.inspections.attachments.show', [$inspection, $file]) }}" alt="Foto item {{ $itemNumber }}" loading="lazy" class="h-16 w-20 rounded object-cover"></a>
                @unless ($readOnly)
                    <label class="text-xs text-red-800"><input type="checkbox" name="delete_attachments[]" value="{{ $file->id }}" x-model="deleteAttachments"> Hapus saat simpan</label>
                @else
                    <span class="text-xs text-slate-500">Foto tersimpan</span>
                @endunless
            </div>
        @endforeach
        @unless ($readOnly)
            <div x-show="hasFinding('{{ $item['id'] }}') || filesSelected['{{ $item['id'] }}']" class="space-y-2" x-cloak>
                <label for="photos-{{ $item['id'] }}" class="block text-xs text-slate-600">Foto opsional · maksimal 3/item, 2 MB/foto (JPG, PNG, WebP)</label>
                <input id="photos-{{ $item['id'] }}" data-upload="{{ $item['id'] }}" name="photos[{{ $item['id'] }}][]" type="file" accept="image/jpeg,image/png,image/webp" multiple @change="selectPhotos('{{ $item['id'] }}', $event)" class="block w-full min-w-0 rounded-lg border border-slate-300 bg-white p-2 text-xs">
                <button type="button" x-show="filesSelected['{{ $item['id'] }}']" @click="clearPhotos('{{ $item['id'] }}')" class="text-xs font-semibold text-red-800 underline">Batalkan foto baru</button>
            </div>
        @endunless
    </div>
</article>
