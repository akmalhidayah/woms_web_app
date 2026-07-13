<x-layouts.pkm title="User Panel">
    <div
        x-data="pkmUserPanelPage({
            create: @js($initialCreateModal),
            edit: @js($initialEditModal),
            storeRoute: @js(route('pkm.user-panel.store')),
        })"
        class="space-y-4"
    >
        @if (session('success'))
            <div id="pkm-user-panel-success" data-message="{{ session('success') }}" class="hidden"></div>
        @endif
        @if (session('error'))
            <div id="pkm-user-panel-error" data-message="{{ session('error') }}" class="hidden"></div>
        @endif

        <section class="rounded-2xl border border-orange-100 bg-gradient-to-r from-orange-50 via-white to-amber-50 p-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#ca642f] shadow-sm ring-1 ring-orange-200">
                        <i data-lucide="users" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h1 class="text-lg font-black text-slate-900">User Panel</h1>
                        <p class="text-xs text-slate-500">Kelola akun PKM.</p>
                    </div>
                </div>
                <button type="button" @click="openCreate()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#ca642f] px-4 py-2.5 text-xs font-bold text-white transition hover:bg-[#b85b2b]">
                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                    Tambah User PKM
                </button>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-4">
                <div class="flex justify-end">
                    <form id="pkm-user-search-form" method="GET" action="{{ route('pkm.user-panel.index') }}" class="flex w-full gap-2 lg:max-w-md">
                        <div class="relative flex-1">
                            <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input id="pkmSearchUsers" type="search" name="search" value="{{ $search }}" placeholder="Nama, email, nomor HP, inisial..." class="w-full rounded-xl border border-slate-300 py-2.5 pl-9 pr-3 text-xs focus:border-orange-400 focus:outline-none">
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Inisial</th>
                            <th class="px-4 py-3">Nomor HP</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Dibuat</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($users as $listedUser)
                            @php
                                $editPayload = base64_encode(json_encode([
                                    'action' => route('pkm.user-panel.update', $listedUser),
                                    'name' => $listedUser->name,
                                    'email' => $listedUser->email,
                                    'nomor_hp' => $listedUser->nomor_hp,
                                    'inisial' => $listedUser->inisial,
                                ]));
                                $isCurrentUser = auth()->id() === $listedUser->id;
                            @endphp
                            <tr class="hover:bg-orange-50/40">
                                <td class="px-4 py-3 font-bold text-slate-900">{{ $listedUser->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $listedUser->inisial ?: '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $listedUser->nomor_hp ?: '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $listedUser->email }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $listedUser->created_at?->format('d M Y') ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="openEdit(@js($editPayload))" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-700 transition hover:bg-amber-100" title="Edit User">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        </button>
                                        <form method="POST" action="{{ route('pkm.user-panel.destroy', $listedUser) }}" class="pkm-delete-user-form">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="_return_search" value="{{ $search }}">
                                            <input type="hidden" name="_return_page" value="{{ $users->currentPage() }}">
                                            <button type="submit" data-name="{{ $listedUser->name }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-40" title="Hapus User" @disabled($isCurrentUser)>
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">Belum ada akun PKM.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">{{ $users->links() }}</div>
            @endif
        </section>

        <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-40 bg-slate-950/55" @click="closeCreate()"></div>
        <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto p-4">
            <div class="flex min-h-full items-center justify-center">
                <form method="POST" :action="storeRoute" class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl" @click.stop>
                    @csrf
                    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                        <h2 class="text-xl font-black text-slate-900">Tambah Pengguna</h2>
                        <button type="button" @click="closeCreate()" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
                    </div>
                    @if ($errors->any() && session('pkm_user_panel_modal') === 'create')
                        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700"><ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @include('pkm.user-panel.partials.fields', ['prefix' => 'create'])
                        <div class="sm:col-span-2 rounded-xl border border-orange-100 bg-orange-50 p-3 text-xs text-orange-800">
                            <div class="font-bold">Password awal akun baru</div>
                            <div class="mt-1">Setiap akun baru otomatis dibuat dengan password <span class="font-mono font-black">{{ $defaultPassword }}</span>.</div>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-2"><button type="button" @click="closeCreate()" class="rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700">Batal</button><button type="submit" class="rounded-xl bg-[#ca642f] px-4 py-2.5 text-xs font-bold text-white">Simpan</button></div>
                </form>
            </div>
        </div>

        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-40 bg-slate-950/55" @click="closeEdit()"></div>
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto p-4">
            <div class="flex min-h-full items-center justify-center">
                <form method="POST" :action="editAction" class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl" @click.stop>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_return_search" value="{{ $search }}"><input type="hidden" name="_return_page" value="{{ $users->currentPage() }}">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-4"><h2 class="text-xl font-black text-slate-900">Edit Pengguna</h2><button type="button" @click="closeEdit()" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button></div>
                    @if ($errors->any() && session('pkm_user_panel_modal') === 'edit')
                        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700"><ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">@include('pkm.user-panel.partials.fields', ['prefix' => 'edit'])</div>
                    <div class="mt-5 flex justify-end gap-2"><button type="button" @click="closeEdit()" class="rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700">Batal</button><button type="submit" class="rounded-xl bg-[#ca642f] px-4 py-2.5 text-xs font-bold text-white">Simpan Perubahan</button></div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function pkmUserPanelPage(config) {
            return {
                showCreateModal: Boolean(config.create?.open), showEditModal: Boolean(config.edit?.open), storeRoute: config.storeRoute, editAction: config.edit?.action || '',
                createForm: { name: config.create?.form?.name || '', email: config.create?.form?.email || '', nomor_hp: config.create?.form?.nomor_hp || '', inisial: config.create?.form?.inisial || '' },
                editForm: { name: config.edit?.form?.name || '', email: config.edit?.form?.email || '', nomor_hp: config.edit?.form?.nomor_hp || '', inisial: config.edit?.form?.inisial || '' },
                openCreate() { this.createForm = { name: '', email: '', nomor_hp: '', inisial: '' }; this.showCreateModal = true; },
                closeCreate() { this.showCreateModal = false; },
                openEdit(payload) { const data = JSON.parse(atob(payload)); this.editAction = data.action; this.editForm = { name: data.name || '', email: data.email || '', nomor_hp: data.nomor_hp || '', inisial: data.inisial || '' }; this.showEditModal = true; },
                closeEdit() { this.showEditModal = false; },
            };
        }

        document.addEventListener('DOMContentLoaded', () => {
            const success = document.getElementById('pkm-user-panel-success'); const error = document.getElementById('pkm-user-panel-error');
            if (success?.dataset.message && window.Swal) window.Swal.fire({ icon: 'success', title: 'Berhasil', text: success.dataset.message, timer: 1700, showConfirmButton: false });
            if (error?.dataset.message && window.Swal) window.Swal.fire({ icon: 'error', title: 'Gagal', text: error.dataset.message });
            document.querySelectorAll('.pkm-delete-user-form').forEach((form) => form.addEventListener('submit', async (event) => {
                event.preventDefault(); const button = form.querySelector('button[type="submit"]'); if (button?.disabled) return;
                const message = `Yakin ingin menghapus ${button?.dataset.name || 'pengguna ini'}?`;
                const confirmed = window.Swal ? (await window.Swal.fire({ icon: 'warning', title: 'Hapus pengguna?', text: message, showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', confirmButtonColor: '#dc2626' })).isConfirmed : window.confirm(message);
                if (confirmed) form.submit();
            }));
            const input = document.getElementById('pkmSearchUsers'); const form = document.getElementById('pkm-user-search-form'); let timer;
            input?.addEventListener('input', () => { window.clearTimeout(timer); timer = window.setTimeout(() => form?.submit(), 450); });
        });
    </script>
</x-layouts.pkm>
