<div class="space-y-6">
    <section class="rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
        <div class="flex items-center gap-4">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                <i data-lucide="users" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-[1.45rem] font-bold leading-none tracking-tight text-slate-900">{{ $title }}</h1>
                <p class="mt-1.5 text-[12px] text-slate-500">{{ $description }}</p>
            </div>
        </div>
    </section>

    <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="space-y-4">
                <div>
                    <label for="name" class="mb-1.5 block text-[11px] font-semibold text-slate-700">Nama PIC</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $bengkel_pic->name ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none" required>
                </div>

                <div>
                    <label for="avatar" class="mb-1.5 block text-[11px] font-semibold text-slate-700">Foto PIC</label>
                    <input id="avatar" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                    <div class="mt-1 text-[11px] text-slate-500">Maks. 2 MB • Format: JPG, JPEG, PNG, WEBP</div>
                </div>
            </div>

            <div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <div class="mb-2 text-[11px] font-semibold text-slate-700">Preview Foto</div>
                    @if (!empty($bengkel_pic?->avatar_url))
                        <img src="{{ $bengkel_pic->avatar_url }}" alt="{{ $bengkel_pic->name }}" class="h-44 w-44 rounded-2xl object-cover ring-1 ring-slate-200">
                    @else
                        <div class="inline-flex h-44 w-44 items-center justify-center rounded-2xl bg-white text-slate-400 ring-1 ring-slate-200">
                            <i data-lucide="user" class="h-10 w-10"></i>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('admin.bengkel-pics.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Kembali
            </a>

            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                <i data-lucide="save" class="h-4 w-4"></i>
                {{ $submitLabel }}
            </button>
        </div>
    </section>
</div>

@if ($errors->any())
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            window.Swal?.fire({
                icon: 'error',
                title: 'Gagal',
                text: @json($errors->first()),
                confirmButtonText: 'OK',
            });
        });
    </script>
@endif
