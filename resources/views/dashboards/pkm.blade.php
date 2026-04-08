<x-layouts.pkm :title="$pageTitle . ' - PKM'">
    @php
        $isDashboardPage = request()->routeIs('pkm.dashboard');
        $isJobWaitingPage = request()->routeIs('pkm.jobwaiting');
        $isLhppIndexPage = request()->routeIs('pkm.lhpp.index');
        $isLhppCreatePage = request()->routeIs('pkm.lhpp.create');
        $isLhppTerminTwoCreatePage = request()->routeIs('pkm.lhpp.termin2.create');
        $isLhppEditPage = request()->routeIs('pkm.lhpp.edit');
    @endphp

    @if (session('status'))
        <div id="pkm-jobwaiting-status-alert" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    @if ($isDashboardPage)
        @include('pkm.dashboard')
    @elseif ($isJobWaitingPage)
        @include('pkm.jobwaiting')
    @elseif ($isLhppIndexPage)
        @include('pkm.lhpp.index')
    @elseif ($isLhppCreatePage || $isLhppTerminTwoCreatePage || $isLhppEditPage)
        @include('pkm.lhpp.create')
    @else
        @include('pkm.placeholder')
    @endif
</x-layouts.pkm>
