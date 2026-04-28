<x-layouts.admin title="User Panel">
    <div
        x-data="userPanelPage({
            create: @js($initialCreateModal),
            edit: @js($initialEditModal),
            routes: {
                store: @js(route('admin.user-panel.store')),
            }
        })"
        class="space-y-6"
    >
        @if (session('success'))
            <div id="user-panel-success" data-message="{{ session('success') }}" class="hidden"></div>
        @endif

        @if (session('error'))
            <div id="user-panel-error" data-message="{{ session('error') }}" class="hidden"></div>
        @endif

        <section
            class="rounded-[1.5rem] border border-blue-100 px-6 py-5 shadow-sm"
            style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);"
        >
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                        <i data-lucide="users" class="h-6 w-6"></i>
                    </span>
                    <div>
                        <h1 class="text-[2rem] font-bold leading-none tracking-tight text-slate-900">User Panel</h1>
                        <p class="mt-2 text-sm text-slate-500">Kelola akun pembuat order, approval, vendor, dan admin dari satu halaman.</p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="openCreate()"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                >
                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                    Tambah User
                </button>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($roleLabels as $roleKey => $label)
                @php
                    $count = $summaryCounts[$roleKey] ?? 0;
                @endphp
                <div class="rounded-[1.35rem] border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $label }}</div>
                    <div class="mt-3 text-[2rem] font-bold leading-none text-slate-900">{{ $count }}</div>
                    <div class="mt-2 text-xs text-slate-500">Total user dengan tipe {{ strtolower($label) }}.</div>
                </div>
            @endforeach
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="inline-flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-1">
                        @foreach ($roleLabels as $roleKey => $label)
                            <a
                                href="{{ route('admin.user-panel.index', ['role' => $roleKey]) }}"
                                class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ $role === $roleKey ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white' }}"
                            >
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>

                    <form method="GET" action="{{ route('admin.user-panel.index') }}" class="flex flex-wrap items-center gap-2">
                        <input type="hidden" name="role" value="{{ $role }}">
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input
                                id="searchUsersLive"
                                type="text"
                                name="search"
                                value="{{ $search }}"
                                placeholder="Cari nama, email, nomor HP..."
                                class="w-72 rounded-xl border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none"
                            >
                        </div>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                            <i data-lucide="search" class="h-4 w-4"></i>
                            Cari
                        </button>
                        <a href="{{ route('admin.user-panel.index', ['role' => $role]) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                            Reset
                        </a>
                    </form>
                </div>

                <div class="overflow-hidden rounded-[1.25rem] border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-100 text-left text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Nama</th>
                                    <th class="px-4 py-3">Inisial</th>
                                    <th class="px-4 py-3">Nomor HP</th>
                                    <th class="px-4 py-3">Email</th>
                                    <th class="px-4 py-3">User Type</th>
                                    <th class="px-4 py-3">Admin Role</th>
                                    <th class="px-4 py-3">Dibuat</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody" class="divide-y divide-slate-200 bg-white text-slate-700">
                                @forelse ($users as $user)
                                    @php
                                        $editPayload = [
                                            'action' => route('admin.user-panel.update', $user),
                                            'id' => $user->id,
                                            'name' => $user->name,
                                            'email' => $user->email,
                                            'nomor_hp' => $user->nomor_hp,
                                            'inisial' => $user->inisial,
                                            'role' => $user->role,
                                            'admin_role' => $user->resolvedAdminRole() ?? \App\Models\User::ADMIN_ROLE_ADMIN,
                                        ];
                                        $canManage = auth()->user()->isSuperAdmin() || $user->role !== \App\Models\User::ROLE_ADMIN;
                                    @endphp
                                    <tr class="align-top hover:bg-slate-50/80">
                                        <td class="px-4 py-3.5">
                                            <div class="font-semibold text-slate-900">{{ $user->name }}</div>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $user->initials() }}</span>
                                        </td>
                                        <td class="px-4 py-3.5">{{ $user->nomor_hp ?: '-' }}</td>
                                        <td class="px-4 py-3.5">{{ $user->email }}</td>
                                        <td class="px-4 py-3.5">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                                {{ $user->role === \App\Models\User::ROLE_ADMIN ? 'bg-blue-100 text-blue-700' : '' }}
                                                {{ $user->role === \App\Models\User::ROLE_APPROVER ? 'bg-emerald-100 text-emerald-700' : '' }}
                                                {{ $user->role === \App\Models\User::ROLE_PKM ? 'bg-amber-100 text-amber-700' : '' }}
                                                {{ $user->role === \App\Models\User::ROLE_USER ? 'bg-slate-100 text-slate-700' : '' }}
                                            ">
                                                {{ $roleLabels[$user->role] ?? strtoupper($user->role) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            @if ($user->role === \App\Models\User::ROLE_ADMIN)
                                                <span class="inline-flex rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700">
                                                    {{ $adminRoleOptions[$user->resolvedAdminRole()] ?? 'Admin' }}
                                                </span>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-sm text-slate-500">{{ $user->created_at?->format('d M Y') ?? '-' }}</td>
                                        <td class="px-4 py-3.5">
                                            <div class="flex items-center justify-center gap-2">
                                                <button
                                                    type="button"
                                                    data-user="{{ rawurlencode(base64_encode(json_encode($editPayload))) }}"
                                                    @click="openEdit($el.dataset.user)"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition hover:bg-blue-100 {{ $canManage ? '' : 'opacity-50 cursor-not-allowed' }}"
                                                    title="Edit User"
                                                    {{ $canManage ? '' : 'disabled' }}
                                                >
                                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                                </button>

                                                <form method="POST" action="{{ route('admin.user-panel.destroy', $user) }}" class="delete-user-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        data-name="{{ $user->name }}"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-600 transition hover:bg-rose-100 {{ auth()->id() === $user->id || ! $canManage ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                        title="Hapus User"
                                                        {{ auth()->id() === $user->id || ! $canManage ? 'disabled' : '' }}
                                                    >
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-12 text-center">
                                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                                <i data-lucide="users" class="h-6 w-6"></i>
                                            </div>
                                            <div class="mt-4 text-lg font-semibold text-slate-900">Belum ada user</div>
                                            <div class="mt-1 text-sm text-slate-500">Gunakan tombol `Tambah User` untuk membuat akun pertama di kategori ini.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    {{ $users->links() }}
                </div>
            </div>
        </section>

        <div x-show="showCreateModal" x-transition.opacity x-cloak class="fixed inset-0 z-40 bg-slate-950/55" @click="closeCreate()"></div>
        <div x-show="showCreateModal" x-transition.opacity x-cloak class="fixed inset-0 z-50 overflow-y-auto p-4">
            <div class="flex min-h-full items-start justify-center py-10">
                <div class="w-full overflow-hidden rounded-[1.5rem] bg-white shadow-2xl" style="max-width: 780px;" @click.stop>
                    <form method="POST" :action="createAction" class="flex max-h-[88vh] flex-col">
                        @csrf

                        <div class="overflow-y-auto px-6 pb-6 pt-6">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-5">
                                <div>
                                    <h2 class="text-[2rem] font-bold leading-tight text-slate-900">Tambah Pengguna</h2>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-500">Buat akun baru berdasarkan struktur `users` yang aktif saat ini.</p>
                                </div>
                                <button type="button" @click="closeCreate()" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                                    <i data-lucide="x" class="h-5 w-5"></i>
                                </button>
                            </div>

                            @if ($errors->any() && session('user_panel_modal') === 'create')
                                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                    <div class="font-semibold">Data belum bisa disimpan.</div>
                                    <ul class="mt-2 list-disc space-y-1 pl-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="mt-5 grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Nama</label>
                                    <input type="text" name="name" x-model="createForm.name" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                                    <input type="email" name="email" x-model="createForm.email" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Nomor HP</label>
                                    <input type="text" name="nomor_hp" x-model="createForm.nomor_hp" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Inisial</label>
                                    <input type="text" name="inisial" x-model="createForm.inisial" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm uppercase text-slate-700 focus:border-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">User Type</label>
                                    <select name="role" x-model="createForm.role" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                        @foreach ($roleLabels as $roleKey => $label)
                                            <option value="{{ $roleKey }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div x-show="createForm.role === 'admin'" x-cloak>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Admin Role</label>
                                    <select name="admin_role" x-model="createForm.admin_role" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                        @foreach ($adminRoleOptions as $adminRoleKey => $adminRoleLabel)
                                            <option value="{{ $adminRoleKey }}">{{ $adminRoleLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                                    <input type="password" name="password" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Konfirmasi Password</label>
                                    <input type="password" name="password_confirmation" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                            <button type="button" @click="closeCreate()" class="rounded-xl bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">Batal</button>
                            <button type="submit" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="showEditModal" x-transition.opacity x-cloak class="fixed inset-0 z-40 bg-slate-950/55" @click="closeEdit()"></div>
        <div x-show="showEditModal" x-transition.opacity x-cloak class="fixed inset-0 z-50 overflow-y-auto p-4">
            <div class="flex min-h-full items-start justify-center py-10">
                <div class="w-full overflow-hidden rounded-[1.5rem] bg-white shadow-2xl" style="max-width: 780px;" @click.stop>
                    <form method="POST" :action="editAction" class="flex max-h-[88vh] flex-col">
                        @csrf
                        @method('PUT')

                        <div class="overflow-y-auto px-6 pb-6 pt-6">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-5">
                                <div>
                                    <h2 class="text-[2rem] font-bold leading-tight text-slate-900">Edit Pengguna</h2>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-500">Ubah data akun tanpa mengikuti field lama yang sudah tidak ada di tabel `users`.</p>
                                </div>
                                <button type="button" @click="closeEdit()" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                                    <i data-lucide="x" class="h-5 w-5"></i>
                                </button>
                            </div>

                            @if ($errors->any() && session('user_panel_modal') === 'edit')
                                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                    <div class="font-semibold">Data belum bisa disimpan.</div>
                                    <ul class="mt-2 list-disc space-y-1 pl-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="mt-5 grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Nama</label>
                                    <input type="text" name="name" x-model="editForm.name" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                                    <input type="email" name="email" x-model="editForm.email" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Nomor HP</label>
                                    <input type="text" name="nomor_hp" x-model="editForm.nomor_hp" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Inisial</label>
                                    <input type="text" name="inisial" x-model="editForm.inisial" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm uppercase text-slate-700 focus:border-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">User Type</label>
                                    <select name="role" x-model="editForm.role" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                        @foreach ($roleLabels as $roleKey => $label)
                                            <option value="{{ $roleKey }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div x-show="editForm.role === 'admin'" x-cloak>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Admin Role</label>
                                    <select name="admin_role" x-model="editForm.admin_role" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                        @foreach ($adminRoleOptions as $adminRoleKey => $adminRoleLabel)
                                            <option value="{{ $adminRoleKey }}">{{ $adminRoleLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                            <button type="button" @click="closeEdit()" class="rounded-xl bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">Batal</button>
                            <button type="submit" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function userPanelPage(config) {
            return {
                showCreateModal: config.create.open ?? false,
                showEditModal: config.edit.open ?? false,
                createAction: config.routes.store,
                editAction: config.edit.action || '',
                createForm: {
                    name: config.create.form?.name || '',
                    email: config.create.form?.email || '',
                    nomor_hp: config.create.form?.nomor_hp || '',
                    inisial: config.create.form?.inisial || '',
                    role: config.create.form?.role || 'user',
                    admin_role: config.create.form?.admin_role || 'admin',
                },
                editForm: {
                    name: config.edit.form?.name || '',
                    email: config.edit.form?.email || '',
                    nomor_hp: config.edit.form?.nomor_hp || '',
                    inisial: config.edit.form?.inisial || '',
                    role: config.edit.form?.role || 'user',
                    admin_role: config.edit.form?.admin_role || 'admin',
                },
                openCreate() {
                    this.showCreateModal = true;
                    this.createForm = { name: '', email: '', nomor_hp: '', inisial: '', role: 'user', admin_role: 'admin' };
                },
                closeCreate() {
                    this.showCreateModal = false;
                },
                openEdit(encodedPayload) {
                    const payload = JSON.parse(atob(decodeURIComponent(encodedPayload)));
                    this.editAction = payload.action;
                    this.editForm = {
                        name: payload.name || '',
                        email: payload.email || '',
                        nomor_hp: payload.nomor_hp || '',
                        inisial: payload.inisial || '',
                        role: payload.role || 'user',
                        admin_role: payload.admin_role || 'admin',
                    };
                    this.showEditModal = true;
                },
                closeEdit() {
                    this.showEditModal = false;
                },
            };
        }

        document.addEventListener('DOMContentLoaded', () => {
            const success = document.getElementById('user-panel-success');
            const error = document.getElementById('user-panel-error');

            if (success?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: success.dataset.message,
                    timer: 1700,
                    showConfirmButton: false,
                });
            }

            if (error?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: error.dataset.message,
                });
            }

            document.querySelectorAll('.delete-user-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const button = form.querySelector('button[type="submit"]');
                    if (button?.disabled) return;

                    const name = button?.dataset.name || 'pengguna ini';
                    const confirmed = window.Swal
                        ? (await window.Swal.fire({
                            icon: 'warning',
                            title: 'Hapus pengguna?',
                            text: `Yakin ingin menghapus ${name}?`,
                            showCancelButton: true,
                            confirmButtonText: 'Ya, hapus',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#dc2626',
                        })).isConfirmed
                        : confirm(`Yakin ingin menghapus ${name}?`);

                    if (confirmed) {
                        form.submit();
                    }
                });
            });

            const liveSearch = document.getElementById('searchUsersLive');
            const tbody = document.getElementById('userTableBody');
            if (liveSearch && tbody) {
                liveSearch.addEventListener('keyup', function () {
                    const query = (this.value || '').toLowerCase();
                    tbody.querySelectorAll('tr').forEach((row) => {
                        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
                    });
                });
            }
        });
    </script>
</x-layouts.admin>
