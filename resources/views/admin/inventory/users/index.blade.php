<x-layouts.admin title="User Aplikasi Inventory">
    <div class="admin-compact space-y-4">
        @include('admin.inventory.partials.header', ['icon' => 'users', 'title' => 'User Aplikasi', 'description' => 'Kelola akun pengguna aplikasi Flutter Inventory.'])

        @if (session('success'))
            <div
                id="inventory-user-flash"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
                data-title="{{ session('success') }}"
                data-user="{{ session('inventory_user_name') }}"
                data-password="{{ session('temporary_password') }}"
                data-notice="{{ session('password_notice') }}"
            >
                <p class="font-semibold">{{ session('success') }}</p>
                @if (session('temporary_password'))
                    <p class="mt-1">Password sementara hanya ditampilkan pada notifikasi ini.</p>
                @endif
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <div class="flex justify-end">
            <a href="{{ route('admin.inventory.users.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">
                <i data-lucide="user-plus" class="h-4 w-4"></i> Tambah User
            </a>
        </div>

        <form method="GET" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_220px_auto]">
            <input name="search" value="{{ request('search') }}" placeholder="Cari nama, email, nomor pegawai, departemen, atau jabatan" class="min-w-0 rounded-lg border-slate-300">
            <select name="status" class="rounded-lg border-slate-300">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                <option value="must_change_password" @selected(request('status') === 'must_change_password')>Wajib ganti password</option>
            </select>
            <button class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white">Filter</button>
        </form>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-[1120px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                        <tr><th class="px-4 py-3">User</th><th class="px-4 py-3">Nomor Pegawai</th><th class="px-4 py-3">Jabatan</th><th class="px-4 py-3">Departemen</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Password</th><th class="px-4 py-3">Login Terakhir</th><th class="px-4 py-3">Dibuat</th><th class="px-4 py-3">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $inventoryUser)
                            <tr>
                                <td class="px-4 py-3"><div class="flex items-center gap-3"><span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">{{ str($inventoryUser->name)->substr(0, 2)->upper() }}</span><div><p class="font-semibold">{{ $inventoryUser->name }}</p><p class="text-xs text-slate-500">{{ $inventoryUser->email }}</p></div></div></td>
                                <td class="px-4 py-3">{{ $inventoryUser->employee_number ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $inventoryUser->position ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $inventoryUser->department ?? '-' }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $inventoryUser->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">{{ $inventoryUser->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $inventoryUser->must_change_password ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $inventoryUser->must_change_password ? 'Wajib Diganti' : 'Sudah Diganti' }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $inventoryUser->last_login_at?->format('d/m/Y H:i') ?? 'Belum pernah' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $inventoryUser->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('admin.inventory.users.status', $inventoryUser) }}" class="inventory-status-form" data-active="{{ $inventoryUser->is_active ? '1' : '0' }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $inventoryUser->is_active ? '0' : '1' }}"><button type="submit" class="rounded-lg border px-3 py-2 text-xs font-semibold {{ $inventoryUser->is_active ? 'border-rose-200 text-rose-700' : 'border-emerald-200 text-emerald-700' }}">{{ $inventoryUser->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                                        <form method="POST" action="{{ route('admin.inventory.users.reset-password', $inventoryUser) }}" class="inventory-reset-form">@csrf<button type="submit" class="rounded-lg border border-amber-200 px-3 py-2 text-xs font-semibold text-amber-700">Reset Password</button></form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-10 text-center text-slate-500">Belum ada user aplikasi yang sesuai filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if (method_exists($users, 'links'))<div class="border-t border-slate-200 px-4 py-3">{{ $users->links() }}</div>@endif
        </section>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const flash = document.getElementById('inventory-user-flash');
                if (flash?.dataset.password && window.Swal) {
                    window.Swal.fire({
                        icon: 'success',
                        title: flash.dataset.title,
                        text: `${flash.dataset.user} — Password sementara: ${flash.dataset.password}. ${flash.dataset.notice}`,
                        confirmButtonText: 'Saya sudah mencatat',
                    });
                }

                document.querySelectorAll('.inventory-status-form').forEach(form => {
                    form.addEventListener('submit', event => {
                        event.preventDefault();
                        const deactivating = form.dataset.active === '1';
                        const title = deactivating ? 'Nonaktifkan akun ini?' : 'Aktifkan kembali akun ini?';
                        const text = deactivating
                            ? 'User tidak dapat login dan seluruh sesi aktifnya akan dikeluarkan.'
                            : 'User harus login kembali menggunakan kredensialnya.';
                        if (!window.Swal) {
                            if (window.confirm(title)) form.submit();
                            return;
                        }
                        window.Swal.fire({ icon: 'warning', title, text, showCancelButton: true, confirmButtonText: 'Ya, lanjutkan', cancelButtonText: 'Batal' })
                            .then(result => { if (result.isConfirmed) form.submit(); });
                    });
                });

                document.querySelectorAll('.inventory-reset-form').forEach(form => {
                    form.addEventListener('submit', event => {
                        event.preventDefault();
                        const title = 'Reset password user?';
                        const text = 'Password akan kembali menjadi password awal dan seluruh sesi user akan dikeluarkan.';
                        if (!window.Swal) {
                            if (window.confirm(title)) form.submit();
                            return;
                        }
                        window.Swal.fire({ icon: 'warning', title, text, showCancelButton: true, confirmButtonText: 'Ya, reset', cancelButtonText: 'Batal' })
                            .then(result => { if (result.isConfirmed) form.submit(); });
                    });
                });
            });
        </script>
    @endpush
</x-layouts.admin>
