<x-layouts.admin title="Display Pekerjaan Bengkel">
    @php
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

        $picAvatarUrl = function (?string $path): ?string {
            if (! $path) {
                return null;
            }

            $cleanPath = ltrim((string) $path, '/');
            if (str_starts_with($cleanPath, 'storage/')) {
                $cleanPath = substr($cleanPath, strlen('storage/'));
            }

            return '/storage/'.$cleanPath;
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
    @endphp

    @if (session('status'))
        <div id="bengkel-task-status-alert" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    <div class="space-y-6">
        <section class="rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                        <i data-lucide="monitor" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h1 class="text-[1.45rem] font-bold leading-none tracking-tight text-slate-900">Display Pekerjaan Bengkel</h1>
                        <p class="mt-1.5 text-[12px] text-slate-500">Kelola daftar pekerjaan bengkel yang tampil di dashboard display dan pembagian regunya.</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('display.bengkel') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        <i data-lucide="monitor-play" class="h-4 w-4"></i>
                        Buka Display
                    </a>
                    <a href="{{ route('admin.bengkel-pics.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        <i data-lucide="users" class="h-4 w-4"></i>
                        Master PIC
                    </a>
                    <a href="{{ route('admin.bengkel-tasks.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Tambah Pekerjaan
                    </a>
                </div>
            </div>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.bengkel-tasks.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
                <div class="md:col-span-5">
                    <label class="mb-1.5 block text-[11px] font-semibold text-slate-700">Cari</label>
                    <div class="flex items-center gap-2">
                        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama pekerjaan / nomor order / unit / seksi" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                        <a href="{{ route('admin.bengkel-tasks.index') }}" class="text-xs font-medium text-slate-500 underline transition hover:text-slate-800">Reset</a>
                    </div>
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


        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-slate-700">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Nama Pekerjaan</th>
                            <th class="px-4 py-3 text-left font-semibold">Nomor Order</th>
                            <th class="px-4 py-3 text-left font-semibold">Regu</th>
                            <th class="px-4 py-3 text-left font-semibold">Penanggung Jawab</th>
                            <th class="px-4 py-3 text-left font-semibold">Target</th>
                            <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($tasks as $task)
                            @php
                                $badge = $reguBadge($task->catatan ?? null);
                                $profiles = is_array($task->person_in_charge_profiles) ? $task->person_in_charge_profiles : [];
                                $names = is_array($task->person_in_charge) ? $task->person_in_charge : [];
                            @endphp
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $task->job_name }}</div>
                                    <div class="mt-1 text-xs text-slate-600 leading-snug">
                                        <div class="truncate">{{ $task->unit_work ?: '-' }}</div>
                                        <div class="truncate text-[11px] text-slate-500">Seksi: {{ $task->seksi ?: '-' }}</div>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $task->notification_number ?: '-' }}</div>
                                    @if ($task->notification_number)
                                        <div class="text-[11px] text-slate-500">Notification</div>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge['class'] }}">
                                        {{ $badge['label'] }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    @if ($profiles !== [])
                                        <div class="flex flex-wrap items-center gap-2">
                                            @foreach ($profiles as $profile)
                                                @php
                                                    $name = is_array($profile) ? ($profile['name'] ?? '') : '';
                                                    $avatar = is_array($profile) ? $picAvatarUrl($profile['avatar_path'] ?? null) : null;
                                                @endphp
                                                <span class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-2 py-1 ring-1 ring-slate-200">
                                                    @if ($avatar)
                                                        <img src="{{ $avatar }}" alt="" class="h-6 w-6 rounded-full object-cover ring-1 ring-white">
                                                    @else
                                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">
                                                            {{ $picInitials($name) }}
                                                        </span>
                                                    @endif
                                                    <span class="text-xs font-medium text-slate-700">{{ $name !== '' ? $name : '-' }}</span>
                                                </span>
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
                                </td>

                                <td class="px-4 py-3">
                                    {{ optional($task->usage_plan_date)->format('d-m-Y') ?: '-' }}
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.bengkel-tasks.edit', $task) }}" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                            Edit
                                        </a>
                                        <form action="{{ route('admin.bengkel-tasks.destroy', $task) }}" method="POST" class="inline-block delete-bengkel-task-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada pekerjaan bengkel.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tasks->hasPages())
                <div class="border-t border-slate-200 px-4 py-4">
                    {{ $tasks->links() }}
                </div>
            @endif
        </section>
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
        });
    </script>
</x-layouts.admin>
