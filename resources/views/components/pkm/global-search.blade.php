@php
    $searchUrl = route('pkm.global-search.index');
@endphp

<div
    class="ml-auto flex shrink-0 justify-end lg:ml-0 lg:min-w-0 lg:flex-1 lg:justify-center lg:px-3"
    x-data="{
        searchUrl: @js($searchUrl),
        searchQuery: '',
        results: [],
        searchOpen: false,
        mobileSearchOpen: false,
        searchLoading: false,
        searchPerformed: false,
        searchError: '',
        selectedIndex: -1,
        searchTimer: null,
        requestSequence: 0,
        requestController: null,
        scheduleSearch() {
            window.clearTimeout(this.searchTimer);
            const query = this.searchQuery.trim();
            this.searchError = '';
            this.searchPerformed = false;

            if (query.length < 2) {
                this.requestController?.abort();
                this.results = [];
                this.selectedIndex = -1;
                this.searchLoading = false;
                this.searchOpen = false;
                return;
            }

            this.results = [];
            this.selectedIndex = -1;
            this.searchOpen = true;
            this.searchLoading = true;
            this.searchTimer = window.setTimeout(() => this.runSearch(query), 400);
        },
        async runSearch(query) {
            const sequence = ++this.requestSequence;
            this.requestController?.abort();
            this.requestController = new AbortController();

            try {
                const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(query)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                    signal: this.requestController.signal,
                });

                if (sequence !== this.requestSequence || query !== this.searchQuery.trim()) return;

                if (response.status === 401 || response.redirected) throw new Error('Sesi berakhir, silakan login kembali.');
                if (response.status === 403) throw new Error('Anda tidak memiliki akses pencarian pekerjaan.');
                if (response.status === 422) throw new Error('Masukkan minimal 2 dan maksimal 100 karakter.');
                if (response.status === 429) throw new Error('Terlalu banyak pencarian, coba beberapa saat lagi.');
                if (!response.ok) throw new Error('Pencarian belum dapat dimuat.');

                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) throw new Error('Pencarian belum dapat dimuat.');

                const payload = await response.json();
                this.results = Array.isArray(payload.results) ? payload.results : [];
                this.selectedIndex = this.results.length > 0 ? 0 : -1;
                this.searchPerformed = true;
                this.$nextTick(() => window.lucide?.createIcons());
            } catch (error) {
                if (error?.name === 'AbortError' || sequence !== this.requestSequence) return;
                this.results = [];
                this.selectedIndex = -1;
                this.searchPerformed = true;
                const publicMessages = [
                    'Sesi berakhir, silakan login kembali.',
                    'Anda tidak memiliki akses pencarian pekerjaan.',
                    'Masukkan minimal 2 dan maksimal 100 karakter.',
                    'Terlalu banyak pencarian, coba beberapa saat lagi.',
                    'Pencarian belum dapat dimuat.',
                ];
                this.searchError = publicMessages.includes(error?.message) ? error.message : 'Pencarian belum dapat dimuat.';
            } finally {
                if (sequence === this.requestSequence) this.searchLoading = false;
            }
        },
        moveSelection(direction) {
            if (this.results.length === 0) return;
            this.searchOpen = true;
            this.selectedIndex = (this.selectedIndex + direction + this.results.length) % this.results.length;
        },
        openSelected() {
            if (this.searchLoading) return;
            const result = this.results[this.selectedIndex >= 0 ? this.selectedIndex : 0];
            if (result) this.openUrl(result.url);
        },
        openUrl(url) {
            if (url) window.location.assign(url);
        },
        closeSearchResults() {
            this.searchOpen = false;
            this.selectedIndex = -1;
        },
        openMobileSearch() {
            this.mobileSearchOpen = true;
            this.searchOpen = this.searchQuery.trim().length >= 2;
            this.$nextTick(() => this.$refs.mobileGlobalSearch?.focus());
        },
        closeMobileSearch() {
            this.mobileSearchOpen = false;
            this.closeSearchResults();
        },
        activeDescendant(prefix) {
            return this.selectedIndex >= 0 ? `${prefix}-${this.selectedIndex}` : null;
        },
        toneClasses(tone) {
            return {
                emerald: 'bg-emerald-100 text-emerald-700', rose: 'bg-rose-100 text-rose-700',
                violet: 'bg-violet-100 text-violet-700', amber: 'bg-amber-100 text-amber-700',
                blue: 'bg-blue-100 text-blue-700',
            }[tone] || 'bg-slate-100 text-slate-600';
        },
    }"
    @keydown.escape.window="closeSearchResults()"
>
    {{-- Desktop --}}
    <div class="relative hidden w-full max-w-xl lg:block" @click.outside="closeSearchResults()">
        <label for="pkm-global-search" class="sr-only">Cari pekerjaan</label>
        <div class="relative">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-orange-100"></i>
            <input
                id="pkm-global-search"
                type="search"
                x-model="searchQuery"
                @input="scheduleSearch()"
                @focus="if (searchQuery.trim().length >= 2) searchOpen = true"
                @keydown.arrow-down.prevent="moveSelection(1)"
                @keydown.arrow-up.prevent="moveSelection(-1)"
                @keydown.enter.prevent="openSelected()"
                placeholder="Cari order, notifikasi SAP, atau pekerjaan…"
                autocomplete="off"
                role="combobox"
                aria-autocomplete="list"
                aria-controls="pkm-global-search-results"
                :aria-expanded="searchOpen"
                :aria-activedescendant="activeDescendant('pkm-global-search-results')"
                class="h-9 w-full rounded-xl border border-white/25 bg-[#b85a24]/40 pl-9 pr-9 text-xs font-semibold text-white outline-none transition placeholder:text-orange-100/80 focus:border-white/60 focus:bg-white focus:text-slate-900 focus:placeholder:text-slate-400 focus:ring-2 focus:ring-white/20"
            >
            <button
                x-show="searchQuery"
                x-cloak
                type="button"
                @click="searchQuery = ''; scheduleSearch()"
                class="absolute right-2 top-1/2 inline-flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-orange-100 hover:bg-white/10 focus:text-slate-500"
                aria-label="Hapus pencarian"
            >
                <i data-lucide="x" class="h-3.5 w-3.5"></i>
            </button>
        </div>

        <div
            x-show="searchOpen"
            x-transition.origin.top
            x-cloak
            class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >
            @include('components.pkm.global-search-results', ['listId' => 'pkm-global-search-results'])
        </div>
    </div>

    {{-- Mobile --}}
    <button
        type="button"
        @click="openMobileSearch()"
        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-white transition hover:bg-white/10 lg:hidden"
        aria-label="Cari pekerjaan"
    >
        <i data-lucide="search" class="h-5 w-5"></i>
    </button>

    <div x-show="mobileSearchOpen" x-cloak class="fixed inset-0 z-[70] lg:hidden" role="dialog" aria-modal="true" aria-label="Pencarian pekerjaan">
        <div class="absolute inset-0 bg-slate-950/55" @click="closeMobileSearch()"></div>
        <section class="relative mx-3 mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center gap-2 border-b border-slate-200 p-3">
                <div class="relative min-w-0 flex-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input
                        x-ref="mobileGlobalSearch"
                        type="search"
                        x-model="searchQuery"
                        @input="scheduleSearch()"
                        @keydown.arrow-down.prevent="moveSelection(1)"
                        @keydown.arrow-up.prevent="moveSelection(-1)"
                        @keydown.enter.prevent="openSelected()"
                        placeholder="Order, notifikasi SAP, atau pekerjaan…"
                        autocomplete="off"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-controls="pkm-mobile-global-search-results"
                        :aria-expanded="searchOpen"
                        :aria-activedescendant="activeDescendant('pkm-mobile-global-search-results')"
                        class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-9 pr-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-500 focus:bg-white focus:ring-2 focus:ring-orange-100"
                    >
                </div>
                <button type="button" @click="closeMobileSearch()" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 text-slate-600" aria-label="Tutup pencarian">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            @include('components.pkm.global-search-results', ['listId' => 'pkm-mobile-global-search-results'])

            <div x-show="!searchLoading && !searchPerformed && searchQuery.trim().length < 2" class="px-4 py-7 text-center text-xs text-slate-500">
                Masukkan minimal 2 karakter untuk mencari pekerjaan.
            </div>
        </section>
    </div>
</div>
