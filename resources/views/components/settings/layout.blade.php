<div class="overflow-hidden rounded-[24px] border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
    <div class="space-y-5">
        <div class="rounded-[20px] border border-stone-200 bg-stone-50 p-3">
            <div class="mb-3 px-2 text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">Menu Settings</div>
            <flux:navlist>
                <flux:navlist.item href="{{ route('settings.profile') }}" wire:navigate>Profile</flux:navlist.item>
                <flux:navlist.item href="{{ route('settings.password') }}" wire:navigate>Password</flux:navlist.item>
                <flux:navlist.item href="{{ route('settings.appearance') }}" wire:navigate>Appearance</flux:navlist.item>
            </flux:navlist>
        </div>

        <div class="min-w-0">
            <flux:heading>{{ $heading ?? '' }}</flux:heading>
            <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

            <div class="mt-5 w-full max-w-2xl min-w-0">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
