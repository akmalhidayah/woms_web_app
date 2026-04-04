<x-layouts.admin title="Upload Informasi">
    @if (session('success'))
        <div id="information-upload-success" data-message="{{ session('success') }}" class="hidden"></div>
    @endif

    <div class="space-y-6">
        <section
            class="rounded-[1.5rem] border border-blue-100 px-6 py-5 shadow-sm"
            style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);"
        >
            <div class="flex items-center gap-4">
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                    <i data-lucide="upload" class="h-6 w-6"></i>
                </span>
                <div>
                    <h1 class="text-[2rem] font-bold leading-none tracking-tight text-slate-900">Upload Informasi</h1>
                    <p class="mt-2 text-sm text-slate-500">Kelola dokumen informasi berdasarkan kategori dan role pengguna.</p>
                </div>
            </div>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">Unggah Dokumen Informasi</h2>

            <form action="{{ route('admin.information-upload.store') }}" method="POST" enctype="multipart/form-data" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2" x-data="{ category: '{{ old('type', \App\Models\AdminInformationFile::TYPE_CARA_KERJA) }}' }">
                @csrf

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Kategori Dokumen</label>
                    <select name="type" x-model="category" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option value="{{ \App\Models\AdminInformationFile::TYPE_CARA_KERJA }}">Cara Kerja</option>
                        <option value="{{ \App\Models\AdminInformationFile::TYPE_FLOWCHART_APLIKASI }}">Flowchart Aplikasi</option>
                        <option value="{{ \App\Models\AdminInformationFile::TYPE_KONTRAK_PKM }}">Kontrak PKM</option>
                    </select>
                </div>

                <div x-show="category === '{{ \App\Models\AdminInformationFile::TYPE_CARA_KERJA }}'" x-cloak>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Role Pengguna</label>
                    <select name="role" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                        @foreach ($roleOptions as $roleKey => $label)
                            <option value="{{ $roleKey }}" @selected(old('role') === $roleKey)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">File Dokumen</label>
                    <input
                        type="file"
                        name="files[]"
                        multiple
                        required
                        class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700"
                    >
                    <p class="mt-2 text-xs text-slate-500">Maksimal 10 MB per file. Bisa unggah lebih dari satu file sekaligus.</p>
                </div>

                @if ($errors->any())
                    <div class="md:col-span-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <div class="font-semibold">Data belum bisa disimpan.</div>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                        <i data-lucide="upload" class="h-4 w-4"></i>
                        Upload Dokumen
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm space-y-10">
            <div>
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Cara Kerja Aplikasi</h3>

                @foreach ($roleOptions as $roleKey => $label)
                    <div class="mb-6">
                        <h4 class="mb-3 text-sm font-semibold text-slate-600">{{ $label }}</h4>

                        @forelse ($caraKerja[$roleKey] as $file)
                            <div class="mb-2 flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <span class="flex min-w-0 items-center gap-2 text-sm text-slate-700">
                                    <i data-lucide="file-text" class="h-4 w-4 shrink-0 text-blue-500"></i>
                                    <span class="truncate">{{ $file->original_name }}</span>
                                </span>

                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.information-upload.preview', $file) }}" target="_blank" class="text-blue-600 transition hover:text-blue-800">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </a>

                                    <form action="{{ route('admin.information-upload.destroy', $file) }}" method="POST" class="delete-information-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" data-name="{{ $file->original_name }}" class="text-rose-600 transition hover:text-rose-800">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Tidak ada dokumen.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>

            <div>
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Flowchart Aplikasi</h3>

                @forelse ($flowchartFiles as $file)
                    <div class="mb-2 flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <span class="min-w-0 truncate text-sm text-slate-700">{{ $file->original_name }}</span>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.information-upload.preview', $file) }}" target="_blank" class="text-blue-600 transition hover:text-blue-800">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </a>
                            <form action="{{ route('admin.information-upload.destroy', $file) }}" method="POST" class="delete-information-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" data-name="{{ $file->original_name }}" class="text-rose-600 transition hover:text-rose-800">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Tidak ada dokumen.</p>
                @endforelse
            </div>

            <div>
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Kontrak PKM</h3>

                @forelse ($kontrakFiles as $file)
                    <div class="mb-2 flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <span class="min-w-0 truncate text-sm text-slate-700">{{ $file->original_name }}</span>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.information-upload.preview', $file) }}" target="_blank" class="text-blue-600 transition hover:text-blue-800">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </a>
                            <form action="{{ route('admin.information-upload.destroy', $file) }}" method="POST" class="delete-information-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" data-name="{{ $file->original_name }}" class="text-rose-600 transition hover:text-rose-800">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Tidak ada dokumen.</p>
                @endforelse
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const success = document.getElementById('information-upload-success');

            if (success?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: success.dataset.message,
                    timer: 1700,
                    showConfirmButton: false,
                });
            }

            document.querySelectorAll('.delete-information-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const button = form.querySelector('button[type="submit"]');
                    const name = button?.dataset.name || 'dokumen ini';

                    const confirmed = window.Swal
                        ? (await window.Swal.fire({
                            icon: 'warning',
                            title: 'Hapus dokumen?',
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
        });
    </script>
</x-layouts.admin>
