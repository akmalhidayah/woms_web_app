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

    <div class="space-y-4 sm:space-y-6">
        <section class="rounded-[1.25rem] border border-blue-100 px-4 py-4 shadow-sm sm:rounded-[1.35rem] sm:px-5" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3 sm:items-center sm:gap-4">
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200 sm:h-12 sm:w-12">
                        <i data-lucide="monitor" class="h-5 w-5"></i>
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-xl font-bold leading-tight tracking-tight text-slate-900 sm:text-[1.45rem]">Display Pekerjaan Bengkel</h1>
                        <p class="mt-1.5 max-w-2xl text-[12px] leading-5 text-slate-500">Kelola daftar pekerjaan bengkel yang tampil di dashboard display dan pembagian regunya.</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center">
                    <a href="{{ route('display.bengkel') }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 sm:px-4 sm:text-sm">
                        <i data-lucide="monitor-play" class="h-4 w-4"></i>
                        Buka Display
                    </a>
                    <a href="{{ route('admin.bengkel-pics.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 sm:px-4 sm:text-sm">
                        <i data-lucide="users" class="h-4 w-4"></i>
                        Master PIC
                    </a>
                    <a href="{{ route('admin.bengkel-tasks.create') }}" class="col-span-2 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 sm:col-span-1">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Tambah Pekerjaan
                    </a>
                </div>
            </div>
        </section>

        <section class="rounded-[1.25rem] border border-slate-200 bg-white p-4 shadow-sm sm:rounded-[1.5rem] sm:p-5">
            <div class="mb-4 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-start">
                <div>
                    <h2 class="text-[1.05rem] font-bold text-slate-900 sm:text-[1.1rem]">Pengaturan Running Text</h2>
                    <p class="mt-1 text-[12px] leading-5 text-slate-500">Ubah isi running text dan kecepatan geraknya untuk layar display bengkel.</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-3 py-2 text-[11px] leading-5 text-slate-500">
                    Semakin kecil nilai detik, running text akan bergerak lebih cepat.
                </div>
            </div>

            <form method="POST" action="{{ route('admin.bengkel-tasks.display-settings.update', $indexQuery) }}" class="grid gap-4 lg:grid-cols-[1.2fr_0.45fr_auto] lg:items-end">
                @csrf
                @method('PATCH')

                <div>
                    <label for="ticker_text" class="mb-1.5 block text-[11px] font-semibold text-slate-700">Isi Running Text</label>
                    <textarea id="ticker_text" name="ticker_text" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none" placeholder="Contoh: Bengkel siap support pekerjaan prioritas minggu ini.">{{ old('ticker_text', $displaySetting->ticker_text ?? '') }}</textarea>
                    <div class="mt-1 text-[11px] leading-5 text-slate-500">Kosongkan jika ingin kembali memakai teks default otomatis.</div>
                    @error('ticker_text')
                        <div class="mt-1 text-[11px] font-medium text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="ticker_speed_seconds" class="mb-1.5 block text-[11px] font-semibold text-slate-700">Kecepatan</label>
                    <div class="relative">
                        <input id="ticker_speed_seconds" type="number" name="ticker_speed_seconds" min="5" max="60" value="{{ old('ticker_speed_seconds', $displaySetting->ticker_speed_seconds ?? 18) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 pr-14 text-sm text-slate-700 focus:border-blue-500 focus:outline-none" required>
                        <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-xs font-semibold text-slate-400">detik</span>
                    </div>
                    @error('ticker_speed_seconds')
                        <div class="mt-1 text-[11px] font-medium text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 lg:w-auto">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Simpan Running Text
                </button>
            </form>
        </section>

        <section class="rounded-[1.25rem] border border-slate-200 bg-white p-4 shadow-sm sm:rounded-[1.5rem]">
            <form method="GET" action="{{ route('admin.bengkel-tasks.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
                <div class="md:col-span-5">
                    <div class="mb-1.5 flex items-center justify-between">
                        <label class="block text-[11px] font-semibold text-slate-700">Cari</label>
                        <a href="{{ route('admin.bengkel-tasks.index') }}" class="text-xs font-medium text-slate-500 underline transition hover:text-slate-800">Reset</a>
                    </div>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama pekerjaan / nomor order / unit / seksi" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                </div>

                <div class="md:col-span-3">
                    <label class="mb-1.5 block text-[11px] font-semibold text-slate-700">Filter Regu</label>
                    <select name="regu" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option value="" @selected($regu === '')>Semua Regu</option>
                        <option value="fabrikasi" @selected($regu === 'fabrikasi')>Regu Fabrikasi</option>
                        <option value="refurbish" @selected($regu === 'refurbish')>Regu Bengkel (Refurbish)</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-[11px] font-semibold text-slate-700">Per Halaman</label>
                    <select name="per_page" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                        @foreach ([10, 25, 50] as $option)
                            <option value="{{ $option }}" @selected((int) $perPage === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                        <i data-lucide="filter" class="h-4 w-4"></i>
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

            document.querySelectorAll('.delete-bengkel-task-form').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();

                    window.Swal?.fire({
                        icon: 'warning',
                        title: 'Hapus pekerjaan?',
                        text: 'Data pekerjaan bengkel yang dihapus tidak bisa dikembalikan.',
                        showCancelButton: true,
                        confirmButtonText: 'Hapus',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#dc2626',
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
            const bulkDeleteForm = document.getElementById('bulk-delete-bengkel-tasks-form');
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

            bulkDeleteForm?.addEventListener('submit', (event) => {
                const checkedCount = visibleCheckboxes().filter((checkbox) => checkbox.checked).length;

                if (checkedCount === 0) {
                    event.preventDefault();
                    window.Swal?.fire({
                        icon: 'info',
                        title: 'Belum ada data dipilih',
                        text: 'Centang pekerjaan yang ingin dihapus terlebih dahulu.',
                        confirmButtonText: 'OK',
                    });
                    return;
                }

                event.preventDefault();

                window.Swal?.fire({
                    icon: 'warning',
                    title: 'Hapus pekerjaan terpilih?',
                    text: `${checkedCount} pekerjaan bengkel akan dihapus dari display.`,
                    showCancelButton: true,
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#dc2626',
                }).then((result) => {
                    if (result.isConfirmed) {
                        bulkDeleteForm.submit();
                    }
                });
            });
        });
    </script>
</x-layouts.admin>
