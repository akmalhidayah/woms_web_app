<x-layouts.admin title="Tambah User Aplikasi Inventory">
    <div class="admin-compact space-y-4">
        @include('admin.inventory.partials.header', ['icon' => 'user-plus', 'title' => 'Tambah User Aplikasi', 'description' => 'Buat akun baru untuk pengguna aplikasi Flutter Inventory.'])

        @if ($errors->any() || $configurationError)
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                @if ($configurationError)<p>{{ $configurationError }}</p>@endif
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.inventory.users.store') }}" x-data="{ submitting: false }" x-on:submit="submitting = true" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            <div class="grid gap-5 lg:grid-cols-3">
                <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
                    <div class="sm:col-span-2"><label class="mb-1 block text-sm font-semibold">Nama Lengkap</label><input name="name" value="{{ old('name') }}" required maxlength="255" class="w-full rounded-lg border-slate-300">@error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="mb-1 block text-sm font-semibold">Email</label><input type="email" name="email" value="{{ old('email') }}" required maxlength="255" class="w-full rounded-lg border-slate-300">@error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="mb-1 block text-sm font-semibold">Nomor Pegawai</label><input name="employee_number" value="{{ old('employee_number') }}" maxlength="255" class="w-full rounded-lg border-slate-300"></div>
                    <div><label class="mb-1 block text-sm font-semibold">Nomor Telepon</label><input name="phone" value="{{ old('phone') }}" maxlength="30" placeholder="+62..." class="w-full rounded-lg border-slate-300"></div>
                    <div><label class="mb-1 block text-sm font-semibold">Jabatan</label><input name="position" value="{{ old('position') }}" maxlength="255" class="w-full rounded-lg border-slate-300"></div>
                    <div><label class="mb-1 block text-sm font-semibold">Departemen</label><input name="department" value="{{ old('department') }}" required maxlength="255" class="w-full rounded-lg border-slate-300">@error('department')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="mb-1 block text-sm font-semibold">Status Akun</label><label class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1') class="rounded border-slate-300 text-blue-600"><span>Aktif</span></label></div>
                </div>
                <aside class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <div class="flex items-center gap-2"><i data-lucide="key-round" class="h-5 w-5 text-blue-600"></i><h2 class="font-bold">Password Awal</h2></div>
                    <p class="mt-3 text-sm text-slate-600">Password awal akun diatur otomatis oleh sistem.</p>
                    @if ($defaultPassword)
                        <p class="mt-3 break-all rounded-lg bg-white px-3 py-2 font-mono font-bold text-blue-700 ring-1 ring-blue-100">{{ $defaultPassword }}</p>
                    @endif
                    <p class="mt-3 text-sm font-semibold text-amber-700">User wajib mengganti password pada login pertama.</p>
                </aside>
            </div>
            <div class="mt-5 flex justify-end gap-2 border-t border-slate-200 pt-4">
                <a href="{{ route('admin.inventory.users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Batal</a>
                <button type="submit" :disabled="submitting || {{ $configurationError ? 'true' : 'false' }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"><span x-text="submitting ? 'Menyimpan...' : 'Simpan User'"></span></button>
            </div>
        </form>
    </div>
</x-layouts.admin>
