@if ($profiles !== [])
    <div class="flex flex-wrap items-center gap-2">
        @foreach ($profiles as $profile)
            @php
                $name = is_array($profile) ? ($profile['name'] ?? '') : '';
                $avatar = is_array($profile) ? ($profile['avatar_url'] ?? null) : null;
                $avatarPosition = $avatarObjectPosition($profile);
                $descriptions = collect(is_array($profile) ? ($profile['work_descriptions'] ?? []) : [])->filter()->values();
            @endphp
            <div class="rounded-xl bg-slate-50 px-2 py-1.5 ring-1 ring-slate-200">
                <div class="flex items-center gap-2">
                    @if ($avatar)
                        <img src="{{ $avatar }}" alt="" class="h-6 w-6 rounded-full object-cover ring-1 ring-white" style="object-position: {{ $avatarPosition }};" onerror="this.remove(); this.parentElement.querySelector('[data-pic-fallback]')?.classList.remove('hidden');">
                    @endif
                    <span data-pic-fallback class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700 {{ $avatar ? 'hidden' : '' }}">
                        {{ $picInitials($name) }}
                    </span>
                    <span class="text-xs font-semibold text-slate-700">{{ $name !== '' ? $name : '-' }}</span>
                </div>
                @if ($descriptions->isNotEmpty())
                    <ul class="mt-1 list-disc space-y-0.5 pl-8 text-[11px] leading-snug text-slate-500">
                        @foreach ($descriptions as $description)
                            <li>{{ $description }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
@elseif ($names !== [])
    <div class="flex flex-wrap gap-1">
        @foreach ($names as $name)
            <span class="rounded-full bg-blue-50 px-2 py-1 text-xs text-blue-700">{{ $name }}</span>
        @endforeach
    </div>
@else
    <span class="text-slate-400">-</span>
@endif
