<x-layouts.admin title="Access Control">
    @if (session('status'))
        <div id="flash-access-control" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    @php
        $menuKeysByRole = collect($menuKeysByRole ?? [])->map(
            fn (array $menuKeys) => collect($menuKeys)
        );
        $configurableAdminRoles = $configurableAdminRoles ?? [];
        $groupLabels = [
            'dashboard' => 'Dashboard',
            'main' => 'Pekerjaan Jasa',
            'workshop' => 'Pekerjaan Bengkel',
            'support' => 'Menu Pendukung',
            'other' => 'Lainnya',
        ];
        $menusByGroup = collect($menuOptions ?? [])->groupBy(fn (array $menu) => $menu['group'] ?? 'other');
        $groupedMenus = collect(['dashboard', 'main', 'workshop', 'support', 'other'])
            ->mapWithKeys(fn (string $group): array => [$group => $menusByGroup->get($group, collect())])
            ->filter(fn ($menus): bool => $menus->isNotEmpty());
    @endphp

    <div class="other-menu-compact space-y-4">
        <section>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">Role & Permission</h1>
            <p class="mt-1 text-sm text-slate-500">Permission diatur terpisah untuk setiap subrole Admin. Super Admin selalu memiliki akses penuh.</p>
        </section>

        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-xl border border-rose-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-4">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-xl bg-rose-600 text-white">
                        <i data-lucide="shield-check" class="h-6 w-6"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Super Admin</h2>
                        <p class="text-sm leading-5 text-slate-500">Akses penuh ke seluruh menu dan pengaturan permission.</p>
                    </div>
                </div>
            </article>

            @foreach ($configurableAdminRoles as $adminRole => $adminRoleLabel)
                @php($isEmployeeRole = $adminRole === \App\Models\User::ADMIN_ROLE_KARYAWAN)
                <article class="rounded-xl border {{ $isEmployeeRole ? 'border-amber-200' : 'border-blue-200' }} bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-xl {{ $isEmployeeRole ? 'bg-amber-500' : 'bg-blue-600' }} text-white">
                            <i data-lucide="{{ $isEmployeeRole ? 'users' : 'user-cog' }}" class="h-6 w-6"></i>
                        </span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">{{ $adminRoleLabel }}</h2>
                            <p class="text-sm leading-5 text-slate-500">Akses sesuai menu yang diaktifkan pada kolom {{ $adminRoleLabel }}.</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        <form method="POST" action="{{ route('admin.access-control.update') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            @csrf
            @method('PUT')
            <input type="hidden" name="permission_matrix_version" value="2">

            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Menu Access Matrix</h2>
                    <p class="text-sm text-slate-500">Aktifkan menu yang boleh tampil dan dibuka oleh akun Admin maupun Karyawan.</p>
                </div>
                <button type="submit" class="inline-flex w-fit items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Simpan Permission
                </button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th class="w-48 px-4 py-3 text-left">Group</th>
                            <th class="px-4 py-3 text-left">Menu</th>
                            <th class="w-36 px-4 py-3 text-center">Super Admin</th>
                            @foreach ($configurableAdminRoles as $adminRoleLabel)
                                <th class="w-36 px-4 py-3 text-center">{{ $adminRoleLabel }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($groupedMenus as $group => $menus)
                            @foreach ($menus as $index => $menu)
                                @php
                                    $isDashboard = (bool) ($menu['always_visible'] ?? false);
                                    $isSuperOnly = (bool) ($menu['super_admin_only'] ?? false);
                                    $isConfigurable = (bool) ($menu['admin_configurable'] ?? false);
                                @endphp

                                <tr>
                                    @if ($index === 0)
                                        <td rowspan="{{ $menus->count() }}" class="border-r border-slate-200 px-4 py-3 align-middle font-bold text-slate-900">
                                            {{ $groupLabels[$group] ?? str($group)->headline() }}
                                        </td>
                                    @endif

                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900">{{ $menu['label'] }}</div>
                                        @if ($isDashboard)
                                            <div class="mt-0.5 text-xs text-slate-500">Selalu aktif untuk semua admin.</div>
                                        @elseif ($isSuperOnly)
                                            <div class="mt-0.5 text-xs text-slate-500">Khusus Super Admin.</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                            <i data-lucide="check" class="h-4 w-4"></i>
                                        </span>
                                    </td>
                                    @foreach ($configurableAdminRoles as $adminRole => $adminRoleLabel)
                                        @php
                                            $roleMenuKeys = $menuKeysByRole->get($adminRole, collect());
                                            $isChecked = $isDashboard || ($isConfigurable && $roleMenuKeys->contains($menu['key']));
                                        @endphp
                                        <td class="px-4 py-3 text-center">
                                            @if ($isDashboard)
                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                                    <i data-lucide="check" class="h-4 w-4"></i>
                                                </span>
                                            @elseif ($isSuperOnly || ! $isConfigurable)
                                                <span class="inline-flex h-8 w-14 items-center rounded-full bg-slate-200 px-1">
                                                    <span class="h-6 w-6 rounded-full bg-white shadow-sm"></span>
                                                </span>
                                            @else
                                                <label class="relative inline-flex cursor-pointer items-center" title="Akses {{ $adminRoleLabel }}: {{ $menu['label'] }}">
                                                    <input
                                                        type="checkbox"
                                                        name="menu_keys[{{ $adminRole }}][]"
                                                        value="{{ $menu['key'] }}"
                                                        @checked($isChecked)
                                                        class="peer sr-only"
                                                    >
                                                    <span class="h-8 w-14 rounded-full bg-slate-200 transition peer-checked:bg-emerald-600"></span>
                                                    <span class="absolute left-1 h-6 w-6 rounded-full bg-white shadow-sm transition peer-checked:translate-x-6"></span>
                                                </label>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>
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
