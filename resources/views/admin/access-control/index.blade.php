<x-layouts.admin title="Access Control">
    @if (session('status'))
        <div id="flash-access-control" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    <div class="space-y-6">
        <section
            class="rounded-[1.5rem] border border-blue-100 px-6 py-5 shadow-sm"
            style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);"
        >
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                        <i data-lucide="shield-check" class="h-6 w-6"></i>
                    </span>
                    <div>
                        <h1 class="text-[2rem] font-bold leading-none tracking-tight text-slate-900">Access Control</h1>
                        <p class="mt-2 text-sm text-slate-500">Atur subrole admin dan menu apa saja yang boleh diakses oleh setiap admin.</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 rounded-2xl border border-slate-200 bg-white/80 p-3 text-sm text-slate-600 shadow-sm">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Super Admin</div>
                        <div class="mt-1 text-xl font-bold text-slate-900">
                            {{ $adminUsers->where('admin_role', \App\Models\User::ADMIN_ROLE_SUPER_ADMIN)->count() }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Admin</div>
                        <div class="mt-1 text-xl font-bold text-slate-900">
                            {{ $adminUsers->where('admin_role', \App\Models\User::ADMIN_ROLE_ADMIN)->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <i data-lucide="info" class="h-4 w-4"></i>
                <span>`Dashboard` selalu bisa diakses admin. `Access Control` hanya bisa dibuka oleh `Super Admin`.</span>
            </div>

            <div class="mt-5 grid gap-4 xl:grid-cols-2">
                @foreach ($adminUsers as $admin)
                    @php
                        $selectedMenuKeys = $admin->adminMenuAccesses->pluck('menu_key')->all();
                        $resolvedRole = $admin->resolvedAdminRole();
                    @endphp

                    <form
                        method="POST"
                        action="{{ route('admin.access-control.update', $admin) }}"
                        x-data="{ adminRole: '{{ $resolvedRole }}' }"
                        class="rounded-[1.25rem] border border-slate-200 bg-slate-50/70 p-5 shadow-sm"
                    >
                        @csrf
                        @method('PUT')

                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="text-lg font-semibold text-slate-900">{{ $admin->name }}</div>
                                <div class="truncate text-sm text-slate-500">{{ $admin->email }}</div>
                            </div>

                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $resolvedRole === \App\Models\User::ADMIN_ROLE_SUPER_ADMIN ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $adminRoleOptions[$resolvedRole] ?? 'Admin' }}
                            </span>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">User Type</label>
                                <input
                                    type="text"
                                    value="Admin"
                                    disabled
                                    class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm text-slate-500"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Admin Role</label>
                                <select
                                    name="admin_role"
                                    x-model="adminRole"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none"
                                >
                                    @foreach ($adminRoleOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div
                            x-show="adminRole === '{{ \App\Models\User::ADMIN_ROLE_SUPER_ADMIN }}'"
                            x-cloak
                            class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                        >
                            Super Admin mendapat akses penuh ke semua menu admin dan dapat mengatur hak akses admin lain.
                        </div>

                        <div x-show="adminRole === '{{ \App\Models\User::ADMIN_ROLE_ADMIN }}'" x-cloak class="mt-4">
                            <div class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Hak Akses Menu</div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($menuOptions as $menu)
                                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 transition hover:border-blue-200 hover:bg-blue-50/40">
                                        <input
                                            type="checkbox"
                                            name="menu_keys[]"
                                            value="{{ $menu['key'] }}"
                                            @checked(in_array($menu['key'], $selectedMenuKeys, true))
                                            class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        >
                                        <span class="min-w-0">
                                            <span class="block text-sm font-semibold text-slate-800">{{ $menu['label'] }}</span>
                                            <span class="block text-xs text-slate-500">Izinkan admin membuka menu ini dari sidebar admin.</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-5 flex items-center justify-end gap-3">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                            >
                                <i data-lucide="save" class="h-4 w-4"></i>
                                Simpan Akses
                            </button>
                        </div>
                    </form>
                @endforeach
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const flash = document.getElementById('flash-access-control');

            if (flash?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: flash.dataset.message,
                    timer: 1700,
                    showConfirmButton: false,
                });
            }
        });
    </script>
</x-layouts.admin>
