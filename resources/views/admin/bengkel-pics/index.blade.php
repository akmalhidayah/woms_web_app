<x-layouts.admin title="Master PIC Bengkel">
    @if (session('status'))
        <div id="bengkel-pic-status-alert" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    <div class="space-y-6">
        <section class="rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                        <i data-lucide="users" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h1 class="text-[1.45rem] font-bold leading-none tracking-tight text-slate-900">Master PIC Bengkel</h1>
                        <p class="mt-1.5 text-[12px] text-slate-500">Kelola daftar PIC yang akan dipakai di display pekerjaan bengkel.</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.bengkel-tasks.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        <i data-lucide="monitor" class="h-4 w-4"></i>
                        Daftar Pekerjaan
                    </a>
                    <a href="{{ route('admin.bengkel-pics.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Tambah PIC
                    </a>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-slate-700">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">PIC</th>
                            <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($pics as $pic)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($pic->avatar_url)
                                            <img src="{{ $pic->avatar_url }}" alt="{{ $pic->name }}" class="h-10 w-10 rounded-full border border-slate-200 object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                            <div style="display:none" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-slate-500">
                                                <i data-lucide="user" class="h-5 w-5"></i>
                                            </div>
                                        @else
                                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-slate-500">
                                                <i data-lucide="user" class="h-5 w-5"></i>
                                            </div>
                                        @endif
                                        <div class="font-semibold text-slate-900">{{ $pic->name }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.bengkel-pics.edit', $pic) }}" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                            Edit
                                        </a>
                                        <form action="{{ route('admin.bengkel-pics.destroy', $pic) }}" method="POST" class="inline-block delete-bengkel-pic-form">
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
                                <td colspan="2" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada PIC bengkel.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pics->hasPages())
                <div class="border-t border-slate-200 px-4 py-4">
                    {{ $pics->links() }}
                </div>
            @endif
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const statusAlert = document.getElementById('bengkel-pic-status-alert');

            if (statusAlert?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: statusAlert.dataset.message,
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            document.querySelectorAll('.delete-bengkel-pic-form').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();

                    window.Swal?.fire({
                        icon: 'warning',
                        title: 'Hapus PIC?',
                        text: 'PIC yang dihapus tidak akan bisa dipilih lagi di pekerjaan bengkel.',
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
