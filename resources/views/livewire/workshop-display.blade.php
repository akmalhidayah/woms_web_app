<div class="h-screen w-screen overflow-hidden">
    @if ($activeSection === \App\Livewire\WorkshopDisplay::SECTION_WORKSHOP)
        <livewire:dashboard-pekerjaan
            mode="display"
            :orchestrated="true"
            :key="'workshop-display-'.$cycle"
        />
    @else
        <livewire:daily-report-display :key="'daily-report-display-'.$cycle" />
    @endif
</div>
