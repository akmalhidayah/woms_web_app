<x-layouts.admin title="Display Pekerjaan Bengkel">
    @php
        $indexQuery = request()->only(['q', 'regu', 'per_page', 'page']);
        $picInitials = function (?string $name): string {
            $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
            $parts = array_values(array_filter($parts));
            $parts = array_slice($parts, 0, 2);
            $initials = '';

            foreach ($parts as $part) {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }

            return $initials !== '' ? $initials : '?';
        };

        $reguBadge = function (?string $catatan): array {
            $value = trim((string) ($catatan ?? ''));

            if ($value === '' || $value === 'Regu Fabrikasi') {
                return ['label' => 'Regu Fabrikasi', 'class' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'];
            }

            if ($value === 'Regu Bengkel (Refurbish)') {
                return ['label' => 'Regu Bengkel (Refurbish)', 'class' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'];
            }

            return ['label' => $value, 'class' => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200'];
        };

        $avatarObjectPosition = function ($profile): string {
            $x = max(0, min(100, (int) (is_array($profile) ? ($profile['avatar_position_x'] ?? 50) : 50)));
            $y = max(0, min(100, (int) (is_array($profile) ? ($profile['avatar_position_y'] ?? 50) : 50)));

            return "{$x}% {$y}%";
        };
    @endphp

    @if (session('status'))
        <div id="bengkel-task-status-alert" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    <div x-data="{ runningTextModal: {{ ($errors->has('ticker_text') || $errors->has('ticker_speed_seconds')) ? 'true' : 'false' }} }" class="space-y-3 sm:space-y-4">
        <section class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3 sm:items-center">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-900 text-white shadow-sm">
                        <i data-lucide="monitor" class="h-4 w-4"></i>
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-[1.05rem] font-bold leading-tight text-slate-900 sm:text-[1.18rem]">Display Pekerjaan Bengkel</h1>
                        <p class="mt-1 max-w-2xl text-[11px] leading-4 text-slate-500">Kelola pekerjaan yang tampil di layar display bengkel dan pembagian regunya.</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center">
                    <a href="{{ route('display.bengkel') }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50">
                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                        Buka Display
                    </a>
                    <button type="button" @click="runningTextModal = true" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50">
                        <i data-lucide="settings-2" class="h-3.5 w-3.5"></i>
                        Running Text
                    </button>
                    <a href="{{ route('admin.bengkel-pics.index') }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50">
                        <i data-lucide="user-round-cog" class="h-3.5 w-3.5"></i>
                        Master PIC
                    </a>
                    <a href="{{ route('admin.bengkel-tasks.create') }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-900 px-3 py-2 text-[11px] font-semibold text-white transition hover:bg-blue-800">
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                        Tambah Pekerjaan
                    </a>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
            <form method="GET" action="{{ route('admin.bengkel-tasks.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
                <div class="md:col-span-5">
                    <label class="mb-1.5 block text-[11px] font-semibold leading-none text-slate-700">Cari</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Nama pekerjaan, nomor order, unit, atau seksi" class="h-9 w-full rounded-lg border border-slate-300 px-3 text-xs text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none">
                </div>

                <div class="md:col-span-3">
                    <label class="mb-1.5 block text-[11px] font-semibold leading-none text-slate-700">Filter Regu</label>
                    <select name="regu" class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option value="" @selected($regu === '')>Semua Regu</option>
                        <option value="fabrikasi" @selected($regu === 'fabrikasi')>Regu Fabrikasi</option>
                        <option value="refurbish" @selected($regu === 'refurbish')>Regu Bengkel (Refurbish)</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-[11px] font-semibold leading-none text-slate-700">Per Halaman</label>
                    <select name="per_page" class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs text-slate-700 focus:border-blue-500 focus:outline-none">
                        @foreach ([10, 25, 50] as $option)
                            <option value="{{ $option }}" @selected((int) $perPage === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2 md:col-span-2">
                    <a href="{{ route('admin.bengkel-tasks.index') }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                        Reset
                    </a>
                    <button type="submit" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-blue-900 px-3 text-xs font-semibold text-white transition hover:bg-slate-800">
                        <i data-lucide="filter" class="h-3.5 w-3.5"></i>
                        Terapkan
                    </button>
                </div>
            </form>
        </section>

        @include('admin.bengkel-tasks.partials.tasks-table', [
            'tasks' => $tasks,
            'indexQuery' => $indexQuery,
            'picInitials' => $picInitials,
            'reguBadge' => $reguBadge,
            'avatarObjectPosition' => $avatarObjectPosition,
        ])

        <div x-cloak x-show="runningTextModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6" @keydown.escape.window="runningTextModal = false">
            <div x-show="runningTextModal" x-transition @click.outside="runningTextModal = false" class="w-full max-w-xl overflow-hidden rounded-xl bg-white shadow-xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Pengaturan Running Text</h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Ubah teks dan kecepatan gerak untuk layar display bengkel.</p>
                    </div>
                    <button type="button" @click="runningTextModal = false" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.bengkel-tasks.display-settings.update', $indexQuery) }}" class="space-y-4 px-5 py-4">
                    @csrf
                    @method('PATCH')

                    <div class="rounded-lg bg-slate-50 px-3 py-2 text-[11px] leading-5 text-slate-500">
                        Semakin kecil nilai detik, running text akan bergerak lebih cepat.
                    </div>

                    <div>
                        <label for="ticker_text" class="mb-1.5 block text-[11px] font-semibold text-slate-700">Isi Running Text</label>
                        <textarea id="ticker_text" name="ticker_text" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none" placeholder="Contoh: Bengkel siap support pekerjaan prioritas minggu ini.">{{ old('ticker_text', $displaySetting->ticker_text ?? '') }}</textarea>
                        <div class="mt-1 text-[11px] leading-5 text-slate-500">Kosongkan jika ingin kembali memakai teks default otomatis.</div>
                        @error('ticker_text')
                            <div class="mt-1 text-[11px] font-medium text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="ticker_speed_seconds" class="mb-1.5 block text-[11px] font-semibold text-slate-700">Kecepatan</label>
                        <div class="relative max-w-[180px]">
                            <input id="ticker_speed_seconds" type="number" name="ticker_speed_seconds" min="5" max="60" value="{{ old('ticker_speed_seconds', $displaySetting->ticker_speed_seconds ?? 18) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 pr-14 text-sm text-slate-700 focus:border-blue-500 focus:outline-none" required>
                            <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-xs font-semibold text-slate-400">detik</span>
                        </div>
                        @error('ticker_speed_seconds')
                            <div class="mt-1 text-[11px] font-medium text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
                        <button type="button" @click="runningTextModal = false" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800">
                            <i data-lucide="save" class="h-3.5 w-3.5"></i>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const statusAlert = document.getElementById('bengkel-task-status-alert');

            if (statusAlert?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: statusAlert.dataset.message,
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            document.querySelectorAll('.archive-bengkel-task-form').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();

                    window.Swal?.fire({
                        icon: 'question',
                        title: 'Arsipkan pekerjaan?',
                        text: 'Data akan dipindahkan ke Order Pekerjaan Bengkel dan tidak tampil lagi di display.',
                        showCancelButton: true,
                        confirmButtonText: 'Arsipkan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#1d4ed8',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            document.querySelectorAll('.complete-bengkel-task-form').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();

                    window.Swal?.fire({
                        icon: 'question',
                        title: 'Tandai selesai?',
                        text: 'Card pekerjaan ini akan tampil hijau di display.',
                        showCancelButton: true,
                        confirmButtonText: 'Selesai',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#059669',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            const selectAll = document.getElementById('select-all-bengkel-tasks');
            const allCheckboxes = () => Array.from(document.querySelectorAll('.bengkel-task-checkbox'));
            const visibleCheckboxes = () => allCheckboxes().filter((checkbox) => checkbox.offsetParent !== null);
            const bulkArchiveForm = document.getElementById('bulk-archive-bengkel-tasks-form');
            const syncSelectAllState = () => {
                if (! selectAll) {
                    return;
                }

                const checkboxes = visibleCheckboxes();
                selectAll.checked = checkboxes.length > 0 && checkboxes.every((item) => item.checked);
                selectAll.indeterminate = checkboxes.some((item) => item.checked) && ! selectAll.checked;
            };

            selectAll?.addEventListener('change', () => {
                visibleCheckboxes().forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
            });

            allCheckboxes().forEach((checkbox) => {
                checkbox.addEventListener('change', syncSelectAllState);
            });

            bulkArchiveForm?.addEventListener('submit', (event) => {
                const checkedCount = visibleCheckboxes().filter((checkbox) => checkbox.checked).length;

                if (checkedCount === 0) {
                    event.preventDefault();
                    window.Swal?.fire({
                        icon: 'info',
                        title: 'Belum ada data dipilih',
                        text: 'Centang pekerjaan yang ingin diarsipkan terlebih dahulu.',
                        confirmButtonText: 'OK',
                    });
                    return;
                }

                event.preventDefault();

                window.Swal?.fire({
                    icon: 'question',
                    title: 'Arsipkan pekerjaan terpilih?',
                    text: `${checkedCount} pekerjaan bengkel akan dipindahkan ke Order Pekerjaan Bengkel.`,
                    showCancelButton: true,
                    confirmButtonText: 'Arsipkan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#1d4ed8',
                }).then((result) => {
                    if (result.isConfirmed) {
                        bulkArchiveForm.submit();
                    }
                });
            });
        });
    </script>
</x-layouts.admin>
