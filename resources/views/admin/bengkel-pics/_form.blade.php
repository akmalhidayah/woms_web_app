@php
    $pageQuery = request()->only('page');
    $avatarPositionX = (int) old('avatar_position_x', $bengkel_pic->avatar_position_x ?? 50);
    $avatarPositionY = (int) old('avatar_position_y', $bengkel_pic->avatar_position_y ?? 50);
    $existingAvatarUrl = $bengkel_pic->avatar_url ?? null;
    $existingAvatarName = $bengkel_pic->name ?? 'PIC Bengkel';
    $previewObjectPosition = max(0, min(100, $avatarPositionX)).'% '.max(0, min(100, $avatarPositionY)).'%';
@endphp

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

                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <div class="text-[11px] font-semibold text-slate-700">Posisi Foto</div>
                            <p class="mt-1 text-[11px] text-slate-500">Geser foto langsung di preview atau atur slider agar wajah pas di tengah.</p>
                        </div>
                        <button type="button" id="avatar-position-reset" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50">
                            <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                            Reset
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-[11px] font-semibold text-slate-700">
                                <label for="avatar_position_x">Geser Horizontal</label>
                                <span id="avatar-position-x-value">{{ $avatarPositionX }}%</span>
                            </div>
                            <input id="avatar_position_x" type="range" name="avatar_position_x" min="0" max="100" value="{{ $avatarPositionX }}" class="h-2 w-full cursor-pointer appearance-none rounded-full bg-slate-200 accent-blue-600">
                        </div>

                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-[11px] font-semibold text-slate-700">
                                <label for="avatar_position_y">Geser Vertikal</label>
                                <span id="avatar-position-y-value">{{ $avatarPositionY }}%</span>
                            </div>
                            <input id="avatar_position_y" type="range" name="avatar_position_y" min="0" max="100" value="{{ $avatarPositionY }}" class="h-2 w-full cursor-pointer appearance-none rounded-full bg-slate-200 accent-blue-600">
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <div class="mb-2 text-[11px] font-semibold text-slate-700">Preview Foto</div>

                    <div id="avatar-preview-frame" class="relative inline-flex h-52 w-52 cursor-grab select-none items-center justify-center overflow-hidden rounded-[1.35rem] bg-white ring-1 ring-slate-200 active:cursor-grabbing">
                        <img
                            id="avatar-preview-image"
                            src="{{ $existingAvatarUrl ?: '' }}"
                            alt="{{ $existingAvatarName }}"
                            class="{{ $existingAvatarUrl ? '' : 'hidden ' }}absolute inset-0 h-full w-full rounded-[1.35rem] object-cover"
                            style="object-position: {{ $previewObjectPosition }};"
                            draggable="false"
                        >

                        <div id="avatar-preview-placeholder" class="{{ $existingAvatarUrl ? 'hidden ' : '' }}inline-flex h-full w-full items-center justify-center rounded-[1.35rem] bg-white text-slate-400">
                            <i data-lucide="user" class="h-10 w-10"></i>
                        </div>

                        <div class="pointer-events-none absolute inset-x-6 top-1/2 h-px -translate-y-1/2 border-t border-dashed border-white/85"></div>
                        <div class="pointer-events-none absolute inset-y-6 left-1/2 w-px -translate-x-1/2 border-l border-dashed border-white/85"></div>
                    </div>

                    <p class="mt-3 text-[11px] leading-5 text-slate-500">
                        Foto di display akan dipotong otomatis menjadi lingkaran atau kotak. Gunakan area preview ini untuk menentukan fokus wajah.
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('admin.bengkel-pics.index', $pageQuery) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
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

<script>
    window.addEventListener('DOMContentLoaded', () => {
        const fileInput = document.getElementById('avatar');
        const previewFrame = document.getElementById('avatar-preview-frame');
        const previewImage = document.getElementById('avatar-preview-image');
        const previewPlaceholder = document.getElementById('avatar-preview-placeholder');
        const positionXInput = document.getElementById('avatar_position_x');
        const positionYInput = document.getElementById('avatar_position_y');
        const positionXValue = document.getElementById('avatar-position-x-value');
        const positionYValue = document.getElementById('avatar-position-y-value');
        const resetButton = document.getElementById('avatar-position-reset');

        if (!previewFrame || !previewImage || !previewPlaceholder || !positionXInput || !positionYInput || !positionXValue || !positionYValue) {
            return;
        }

        let objectUrl = null;
        let isDragging = false;

        const syncLabels = () => {
            positionXValue.textContent = `${positionXInput.value}%`;
            positionYValue.textContent = `${positionYInput.value}%`;
        };

        const applyObjectPosition = () => {
            previewImage.style.objectPosition = `${positionXInput.value}% ${positionYInput.value}%`;
            syncLabels();
        };

        const showImage = (src) => {
            if (!src) {
                previewImage.classList.add('hidden');
                previewPlaceholder.classList.remove('hidden');
                previewImage.removeAttribute('src');
                return;
            }

            previewImage.src = src;
            previewImage.classList.remove('hidden');
            previewPlaceholder.classList.add('hidden');
            applyObjectPosition();
        };

        const updatePositionFromPointer = (event) => {
            const rect = previewFrame.getBoundingClientRect();

            if (!rect.width || !rect.height) {
                return;
            }

            const nextX = Math.max(0, Math.min(100, Math.round(((event.clientX - rect.left) / rect.width) * 100)));
            const nextY = Math.max(0, Math.min(100, Math.round(((event.clientY - rect.top) / rect.height) * 100)));

            positionXInput.value = String(nextX);
            positionYInput.value = String(nextY);
            applyObjectPosition();
        };

        positionXInput.addEventListener('input', applyObjectPosition);
        positionYInput.addEventListener('input', applyObjectPosition);

        fileInput?.addEventListener('change', (event) => {
            const file = event.target.files?.[0];

            if (!file) {
                showImage(previewImage.getAttribute('src'));
                return;
            }

            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }

            objectUrl = URL.createObjectURL(file);
            showImage(objectUrl);
        });

        previewFrame.addEventListener('pointerdown', (event) => {
            if (previewImage.classList.contains('hidden')) {
                return;
            }

            isDragging = true;
            previewFrame.setPointerCapture(event.pointerId);
            updatePositionFromPointer(event);
        });

        previewFrame.addEventListener('pointermove', (event) => {
            if (!isDragging) {
                return;
            }

            updatePositionFromPointer(event);
        });

        const stopDragging = (event) => {
            if (!isDragging) {
                return;
            }

            isDragging = false;

            if (event?.pointerId !== undefined && previewFrame.hasPointerCapture(event.pointerId)) {
                previewFrame.releasePointerCapture(event.pointerId);
            }
        };

        previewFrame.addEventListener('pointerup', stopDragging);
        previewFrame.addEventListener('pointercancel', stopDragging);
        previewFrame.addEventListener('pointerleave', (event) => {
            if (isDragging && (event.buttons ?? 0) === 0) {
                stopDragging(event);
            }
        });

        resetButton?.addEventListener('click', () => {
            positionXInput.value = '50';
            positionYInput.value = '50';
            applyObjectPosition();
        });

        syncLabels();
        applyObjectPosition();
        showImage(previewImage.getAttribute('src'));
    });
</script>
