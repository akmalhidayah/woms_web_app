@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-2 text-center">
    <h1 class="text-2xl font-semibold text-slate-900">{{ $title }}</h1>
    <p class="text-center text-sm leading-6 text-slate-500">{{ $description }}</p>
</div>
