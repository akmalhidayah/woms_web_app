@php
    $rows = $tab === 'history' ? $history : $waiting;
    $isHistory = $tab === 'history';
@endphp

<x-layouts.admin title="Serah Terima Bengkel">
    <div class="space-y-4">
        <section class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-5">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-900 text-white">
                    <i data-lucide="handshake" class="h-5 w-5"></i>
                </span>
                <div>
                    <h1 class="text-lg font-bold text-slate-900">Serah Terima</h1>
                    <p class="mt-0.5 text-xs text-slate-500">Monitoring proses penyerahan hasil pekerjaan bengkel.</p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap gap-2 border-b border-slate-200 px-4 pt-4">
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'waiting']) }}" class="rounded-lg px-3 py-2 text-xs font-semibold {{ ! $isHistory ? 'bg-blue-700 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                    Menunggu Serah Terima <span class="ml-1 rounded-full bg-white/20 px-1.5 py-0.5">{{ $waitingCount }}</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'history']) }}" class="rounded-lg px-3 py-2 text-xs font-semibold {{ $isHistory ? 'bg-blue-700 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                    Riwayat <span class="ml-1 rounded-full bg-white/20 px-1.5 py-0.5">{{ $historyCount }}</span>
                </a>
            </div>

            <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-200 p-4 sm:grid-cols-12 sm:items-end">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="sm:col-span-8">
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-500">Pencarian</label>
                    <input name="search" value="{{ $search }}" placeholder="Cari nomor order / pekerjaan / unit..." class="h-10 w-full rounded-lg border border-slate-300 px-3 text-xs">
                </div>
                <div class="sm:col-span-3">
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-500">Jalur</label>
                    <select name="path" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs">
                        <option value="">Semua Jalur</option>
                        <option value="Critical" @selected($path === 'Critical')>Critical</option>
                        <option value="Non-Critical" @selected($path === 'Non-Critical')>Non-Critical</option>
                    </select>
                </div>
                <button class="h-10 rounded-lg bg-blue-700 px-4 text-xs font-semibold text-white">Terapkan</button>
            </form>

            @if (session('status'))
                <div class="mx-4 mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mx-4 mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700">{{ $errors->first() }}</div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-[1000px] w-full text-left text-xs">
                    <thead class="bg-slate-100 text-[10px] uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3">Detail Pekerjaan</th>
                            <th class="px-4 py-3">Jalur</th>
                            <th class="px-4 py-3">Progress Approval</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($rows as $row)
                            @php
                                $order = $row;
                                $handover = $order->workshopHandover;
                                $isLegacyHistory = $isHistory && $handover === null && $order->orderWorkshop?->legacyCompleted();
                                $number = $isHistory && $handover ? $handover->order_no_snapshot : $order->nomor_order;
                                $jobName = $isHistory && $handover ? $handover->job_name_snapshot : $order->nama_pekerjaan;
                                $unit = $isHistory && $handover ? $handover->unit_snapshot : $order->unit_kerja;
                                $section = $isHistory && $handover ? $handover->section_snapshot : $order->seksi;
                                $picUser = trim((string) $order->orderWorkshop?->pic_user);
                                $photoPath = $handover?->photo_paths[0] ?? null;
                                $progressText = $isLegacyHistory ? 'Legacy' : ($handover?->progress() ?? '0/2');
                                $progressRole = match (true) {
                                    $isLegacyHistory => 'Selesai (Data Legacy)',
                                    $handover?->isCompleted() => 'Approval selesai',
                                    $handover !== null => $handover->recipient_position_snapshot ?: 'PIC User',
                                    default => 'Bukti Serah Terima',
                                };
                                $progressActivity = match (true) {
                                    $isLegacyHistory => 'Data legacy',
                                    $handover?->isCompleted() => 'Serah terima selesai',
                                    $handover !== null => 'Menunggu '.($handover->recipient_position_snapshot ?: 'PIC User'),
                                    default => 'Menunggu proses serah terima',
                                };
                                $progressUpdatedAt = $handover?->user_signed_at ?: $handover?->updated_at;
                                $handoverChecklist = $handover ? [
                                    [
                                        'step' => 1,
                                        'role' => 'Admin Workshop',
                                        'name' => $handover->admin_name_snapshot ?: '-',
                                        'status' => 'signed',
                                        'status_label' => 'Sudah TTD',
                                        'signed_at' => $handover->admin_signed_at?->format('d/m/Y H:i'),
                                        'is_active' => false,
                                    ],
                                    [
                                        'step' => 2,
                                        'role' => $handover->recipient_position_snapshot ?: 'PIC User',
                                        'name' => $handover->recipient_name_snapshot ?: '-',
                                        'status' => $handover->user_signed_at ? 'signed' : 'pending',
                                        'status_label' => $handover->user_signed_at ? 'Sudah TTD' : ($handover->tokenExpired() ? 'Token kedaluwarsa' : 'Menunggu TTD'),
                                        'signed_at' => $handover->user_signed_at?->format('d/m/Y H:i'),
                                        'is_active' => ! $handover->user_signed_at,
                                    ],
                                ] : [];
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    <p class="font-bold text-slate-900">{{ $number }}</p>
                                    <p class="text-[10px] text-blue-600">Notif: {{ $order->notifikasi ?: '-' }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <p class="font-semibold text-slate-900">{{ $jobName }}</p>
                                    <p class="text-[10px] text-slate-500">Unit: {{ $unit ?: '-' }}</p>
                                    <p class="text-[10px] text-blue-600">Seksi: {{ $section ?: '-' }}</p>
                                    @if ($order?->workPackages?->isNotEmpty())
                                        <div class="mt-2 rounded-lg border border-blue-100 bg-blue-50/60 px-2 py-1.5 text-[10px] text-slate-700">
                                            <span class="font-semibold text-blue-700">{{ $order->workPackages->count() }} paket pekerjaan</span>
                                            <span class="ml-1">{{ $order->workPackages->where('status', \App\Models\WorkshopWorkPackage::STATUS_COMPLETED)->count() }}/{{ $order->workPackages->count() }} selesai</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-700">{{ $handover?->path ?? $queue->path($order) }}</span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="min-w-[220px] rounded-xl border border-blue-100 bg-blue-50 px-2 py-1.5">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex min-w-0 items-center gap-1.5">
                                                <span class="inline-flex shrink-0 rounded-full bg-white px-1.5 py-0.5 text-[8px] font-bold text-blue-700 ring-1 ring-blue-100">{{ $progressText }}</span>
                                                <span class="truncate text-[9px] font-semibold text-slate-800" title="{{ $progressRole }}">{{ $progressRole }}</span>
                                            </div>
                                            @if ($handover)
                                                <button type="button" class="approval-signature-info-trigger inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-blue-200 bg-white text-blue-700 transition hover:bg-blue-100" title="Informasi approval" aria-label="Informasi approval Serah Terima {{ $number }}" data-title="Approval Serah Terima - {{ $number }}" data-summary="{{ $progressText }} TTD" data-checklist='@json($handoverChecklist)' data-approval-url="{{ $handover->approvalUrl() }}" data-whatsapp-url="" data-resend-url="{{ $handover->approvalUrl() ? route('admin.workshop-handover.resend', $handover) : '' }}" data-regenerate-url="{{ $handover->tokenExpired() && ! $handover->isCompleted() ? route('admin.workshop-handover.regenerate', $handover) : '' }}" data-expiry="{{ $handover->token_expires_at?->format('d/m/Y H:i') }}">
                                                    <i data-lucide="info" class="h-3.5 w-3.5"></i>
                                                </button>
                                            @endif
                                        </div>
                                        <div class="mt-1 flex items-center gap-1 text-[8px] text-slate-400">
                                            <i data-lucide="clock-3" class="h-2.5 w-2.5"></i>
                                            <span class="truncate">{{ $progressActivity }}{{ $progressUpdatedAt ? ' · '.$progressUpdatedAt->locale('id')->diffForHumans() : '' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center align-top">
                                    @if ($isLegacyHistory)
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-slate-100 text-slate-400" title="Tanpa dokumen" aria-label="Tanpa dokumen"><i data-lucide="file-x-2" class="h-3.5 w-3.5"></i></span>
                                    @elseif ($isHistory)
                                        <div class="flex flex-wrap justify-center gap-1.5">
                                            @if ($photoPath)
                                                <a href="{{ route('admin.workshop-handover.photo', [$handover, 0]) }}" target="_blank" rel="noopener" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-100" title="Lihat bukti serah terima" aria-label="Lihat bukti serah terima"><i data-lucide="image" class="h-3.5 w-3.5"></i></a>
                                            @else
                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-slate-100 text-slate-400" title="Bukti tidak tersedia" aria-label="Bukti tidak tersedia"><i data-lucide="image-off" class="h-3.5 w-3.5"></i></span>
                                            @endif
                                            <a href="{{ route('admin.workshop-handover.pdf', $handover) }}" target="_blank" rel="noopener" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100" title="Preview PDF Serah Terima" aria-label="Preview PDF Serah Terima"><i data-lucide="file-text" class="h-3.5 w-3.5"></i></a>
                                        </div>
                                    @elseif ($handover)
                                        <div class="flex flex-wrap justify-center gap-1.5">
                                            @if ($photoPath)
                                                <a href="{{ route('admin.workshop-handover.photo', [$handover, 0]) }}" target="_blank" rel="noopener" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-100" title="Lihat bukti serah terima" aria-label="Lihat bukti serah terima"><i data-lucide="image" class="h-3.5 w-3.5"></i></a>
                                            @endif
                                            <a href="{{ route('admin.workshop-handover.pdf', $handover) }}" target="_blank" rel="noopener" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100" title="Preview PDF Serah Terima" aria-label="Preview PDF Serah Terima"><i data-lucide="file-text" class="h-3.5 w-3.5"></i></a>
                                        </div>
                                    @else
                                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-700 text-white transition hover:bg-blue-800" title="Proses Serah Terima" aria-label="Proses Serah Terima" data-open-handover data-action="{{ route('admin.workshop-handover.process', $order) }}" data-order="{{ $order->nomor_order }}" data-job="{{ $order->nama_pekerjaan }}" data-unit="{{ $order->unit_kerja ?: '-' }}" data-section="{{ $order->seksi ?: '-' }}" data-path="{{ $queue->path($order) }}" data-pic-user="{{ $picUser }}">
                                            <i data-lucide="handshake" class="h-3.5 w-3.5"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ $isHistory ? 'Belum ada riwayat Serah Terima.' : 'Belum ada pekerjaan yang siap diserahterimakan.' }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3 text-[10px] text-slate-500">Menampilkan {{ $rows->count() }} {{ $isHistory ? 'riwayat' : 'antrean' }}.</div>
            @if ($rows->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
            @endif
        </section>
    </div>

    <div id="workshop-handover-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="workshop-handover-modal-title">
        <div class="max-h-[95vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 id="workshop-handover-modal-title" class="text-base font-bold text-slate-900">Proses Serah Terima</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Upload bukti pekerjaan dan tanda tangan Admin Workshop.</p>
                </div>
                <button type="button" data-close-handover class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Tutup modal">&times;</button>
            </div>

            <form id="workshop-handover-form" method="POST" enctype="multipart/form-data" class="space-y-4 p-5">
                @csrf
                <div id="workshop-handover-errors" class="hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700"></div>
                <div class="grid gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs sm:grid-cols-2">
                    <div><span class="text-slate-500">Nomor Order</span><p data-handover-value="order" class="font-semibold text-slate-900">-</p></div>
                    <div><span class="text-slate-500">Nama Pekerjaan</span><p data-handover-value="job" class="font-semibold text-slate-900">-</p></div>
                    <div><span class="text-slate-500">Unit</span><p data-handover-value="unit" class="font-semibold text-slate-900">-</p></div>
                    <div><span class="text-slate-500">Seksi</span><p data-handover-value="section" class="font-semibold text-slate-900">-</p></div>
                    <div><span class="text-slate-500">Jalur</span><p data-handover-value="path" class="font-semibold text-slate-900">-</p></div>
                    <div><span class="text-slate-500">Tanggal Penyerahan</span><p class="font-semibold text-slate-900">{{ now()->format('d-m-Y H:i') }}</p></div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="workshop-handover-pic-name" class="mb-1.5 block text-xs font-semibold text-slate-700">Nama PIC User <span class="text-rose-600">*</span></label>
                        <input id="workshop-handover-pic-name" type="text" name="pic_user_name" required maxlength="255" autocomplete="name" placeholder="Masukkan nama PIC User" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-xs text-slate-700 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label for="workshop-handover-pic-email" class="mb-1.5 block text-xs font-semibold text-slate-700">Email PIC User <span class="text-rose-600">*</span></label>
                        <input id="workshop-handover-pic-email" type="email" name="pic_user_email" required maxlength="255" autocomplete="email" placeholder="Masukkan email PIC User" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-xs text-slate-700 focus:border-blue-500 focus:outline-none">
                    </div>
                    <p class="text-[11px] text-slate-500 sm:col-span-2">Jika email belum terdaftar, akun Approval dibuat otomatis dan informasi login dikirim melalui email.</p>
                </div>

                <div>
                    <label for="workshop-handover-photos" class="mb-1.5 block text-xs font-semibold text-slate-700">Foto Bukti <span class="text-rose-600">*</span></label>
                    <input id="workshop-handover-photos" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700">
                    <p class="mt-1 text-[11px] text-slate-500">Wajib 1–3 foto, format JPG, JPEG, PNG, atau WebP, maksimal 5MB per foto.</p>
                    <div id="workshop-handover-photo-preview" class="mt-3 hidden grid gap-2 sm:grid-cols-3"></div>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-700">Tanda Tangan Admin Workshop <span class="text-rose-600">*</span></label>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <canvas id="workshop-handover-signature" class="h-36 w-full touch-none rounded-lg bg-white ring-1 ring-slate-200"></canvas>
                        <input type="hidden" name="admin_signature_data" id="workshop-handover-signature-data">
                        <div class="mt-2 flex items-center justify-between gap-2">
                            <span class="text-[11px] text-slate-500">Gunakan mouse atau layar sentuh.</span>
                            <button type="button" id="workshop-handover-signature-clear" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Hapus/Ulangi</button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                    <button type="button" data-close-handover class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                    <button type="submit" id="workshop-handover-submit" class="rounded-lg bg-blue-700 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-800">Kirim ke Email PIC User</button>
                </div>
            </form>
        </div>
    </div>

    @include('admin.orders.partials.approval-signature-modal', ['approvalReassignmentUsers' => collect()])

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('workshop-handover-modal');
        const form = document.getElementById('workshop-handover-form');
        const canvas = document.getElementById('workshop-handover-signature');
        const signatureData = document.getElementById('workshop-handover-signature-data');
        const clearSignature = document.getElementById('workshop-handover-signature-clear');
        const photoInput = document.getElementById('workshop-handover-photos');
        const picUserName = document.getElementById('workshop-handover-pic-name');
        const picUserEmail = document.getElementById('workshop-handover-pic-email');
        const photoPreview = document.getElementById('workshop-handover-photo-preview');
        const errors = document.getElementById('workshop-handover-errors');
        const submitButton = document.getElementById('workshop-handover-submit');
        if (! modal || ! form || ! canvas || ! signatureData) return;

        const context = canvas.getContext('2d');
        let drawing = false;
        let hasStroke = false;

        const resizeCanvas = () => {
            const ratio = window.devicePixelRatio || 1;
            const rect = canvas.getBoundingClientRect();
            canvas.width = Math.max(1, rect.width * ratio);
            canvas.height = Math.max(1, rect.height * ratio);
            context.setTransform(ratio, 0, 0, ratio, 0, 0);
            context.lineWidth = 2;
            context.lineCap = 'round';
            context.strokeStyle = '#0f172a';
        };

        const clearCanvas = () => {
            const rect = canvas.getBoundingClientRect();
            context.clearRect(0, 0, rect.width, rect.height);
            signatureData.value = '';
            hasStroke = false;
        };

        const point = (event) => {
            const rect = canvas.getBoundingClientRect();
            return { x: event.clientX - rect.left, y: event.clientY - rect.top };
        };

        canvas.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            drawing = true;
            canvas.setPointerCapture?.(event.pointerId);
            const current = point(event);
            context.beginPath();
            context.moveTo(current.x, current.y);
        });
        canvas.addEventListener('pointermove', (event) => {
            if (! drawing) return;
            event.preventDefault();
            const current = point(event);
            context.lineTo(current.x, current.y);
            context.stroke();
            hasStroke = true;
        });
        const stopDrawing = () => { drawing = false; };
        canvas.addEventListener('pointerup', stopDrawing);
        canvas.addEventListener('pointercancel', stopDrawing);
        clearSignature.addEventListener('click', clearCanvas);

        const showError = (message) => {
            errors.textContent = message;
            errors.classList.remove('hidden');
        };
        const hideError = () => {
            errors.textContent = '';
            errors.classList.add('hidden');
        };

        const resetPhotos = () => {
            photoInput.value = '';
            photoPreview.innerHTML = '';
            photoPreview.classList.add('hidden');
        };

        photoInput.addEventListener('change', () => {
            hideError();
            const files = Array.from(photoInput.files || []);
            const allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (files.length < 1 || files.length > 3) {
                resetPhotos();
                showError('Foto bukti wajib diisi minimal 1 dan maksimal 3 foto.');
                return;
            }
            if (files.some((file) => ! allowed.includes(file.type))) {
                resetPhotos();
                showError('Format foto harus JPG, JPEG, PNG, atau WebP.');
                return;
            }
            photoPreview.classList.remove('hidden');
            files.forEach((file) => {
                const card = document.createElement('div');
                card.className = 'overflow-hidden rounded-lg border border-slate-200 bg-white';
                const image = document.createElement('img');
                image.src = URL.createObjectURL(file);
                image.alt = '';
                image.className = 'h-20 w-full object-cover';
                const name = document.createElement('div');
                name.className = 'truncate px-2 py-1.5 text-[10px] font-medium text-slate-600';
                name.textContent = file.name;
                card.append(image, name);
                photoPreview.appendChild(card);
            });
        });

        document.querySelectorAll('[data-open-handover]').forEach((button) => {
            button.addEventListener('click', () => {
                hideError();
                form.action = button.dataset.action;
                ['order', 'job', 'unit', 'section', 'path'].forEach((key) => {
                    modal.querySelector(`[data-handover-value="${key}"]`).textContent = button.dataset[key] || '-';
                });
                picUserName.value = button.dataset.picUser || '';
                picUserEmail.value = '';
                resetPhotos();
                clearCanvas();
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
                window.requestAnimationFrame(resizeCanvas);
            });
        });

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        };
        modal.querySelectorAll('[data-close-handover]').forEach((button) => button.addEventListener('click', closeModal));
        modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });

        form.addEventListener('submit', (event) => {
            hideError();
            if (! photoInput.files?.length || photoInput.files.length > 3) {
                event.preventDefault();
                showError('Foto bukti wajib diisi minimal 1 dan maksimal 3 foto.');
                return;
            }
            if (! hasStroke) {
                event.preventDefault();
                showError('Tanda tangan Admin Workshop wajib diisi.');
                return;
            }
            signatureData.value = canvas.toDataURL('image/png');
            submitButton.disabled = true;
            submitButton.classList.add('cursor-not-allowed', 'opacity-60');
            submitButton.textContent = 'Mengirim...';
        });
    });
</script>

</x-layouts.admin>
