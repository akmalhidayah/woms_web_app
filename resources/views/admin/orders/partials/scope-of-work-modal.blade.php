@php
    $scopeItems = old('scope_pekerjaan')
        ? collect(old('scope_pekerjaan'))->map(function ($scopePekerjaan, $index) {
            return [
                'scope_pekerjaan' => $scopePekerjaan,
                'qty' => old('qty.'.$index, ''),
                'satuan' => old('satuan.'.$index, ''),
                'keterangan' => old('keterangan.'.$index, ''),
            ];
        })->values()->all()
        : ($scopeOfWork?->scope_items ?? [['scope_pekerjaan' => '', 'qty' => '', 'satuan' => '', 'keterangan' => '']]);
@endphp

<div
    x-show="showSowModal"
    x-transition.opacity
    x-cloak
    class="fixed inset-0 z-40 bg-slate-950/55"
    @click="closeSowModal()"
></div>

<div
    x-show="showSowModal"
    x-transition.scale.origin.center
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <div class="w-full max-w-5xl rounded-3xl bg-white shadow-2xl" @click.stop>
        <form
            method="POST"
            action="{{ $scopeOfWork ? route('admin.orders.scope-of-work.update', [$order, $scopeOfWork]) : route('admin.orders.scope-of-work.store', $order) }}"
            class="max-h-[90vh] overflow-y-auto p-6"
        >
            @csrf
            @if ($scopeOfWork)
                @method('PUT')
            @endif

            <input type="hidden" name="tanda_tangan" x-model="signatureData">

            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">{{ $scopeOfWork ? 'Edit Scope of Work' : 'Buat Scope of Work' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $order->nomor_order }} • {{ $order->nama_pekerjaan }}</p>
                </div>

                <button type="button" @click="closeSowModal()" class="text-2xl text-slate-500 transition hover:text-slate-700">&times;</button>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nomor Order</label>
                    <input type="text" value="{{ $order->nomor_order }}" readonly class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nama Pekerjaan</label>
                    <input type="text" value="{{ $order->nama_pekerjaan }}" readonly class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nama Penginput</label>
                    <input
                        type="text"
                        name="nama_penginput"
                        value="{{ old('nama_penginput', $scopeOfWork?->nama_penginput ?? auth()->user()?->name ?? '') }}"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        required
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Unit Kerja</label>
                    <input type="text" value="{{ $order->unit_kerja }}" readonly class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Dokumen</label>
                    <input
                        type="date"
                        name="tanggal_dokumen"
                        value="{{ old('tanggal_dokumen', optional($scopeOfWork?->tanggal_dokumen)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        required
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal Pemakaian</label>
                    <input
                        type="date"
                        name="tanggal_pemakaian"
                        value="{{ old('tanggal_pemakaian', optional($scopeOfWork?->tanggal_pemakaian)->format('Y-m-d') ?? optional($order->target_selesai)->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                </div>
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-slate-900">Scope of Work</h3>
                    <button
                        type="button"
                        @click="scopeRows.push({ scope_pekerjaan: '', qty: '', satuan: '', keterangan: '' })"
                        class="rounded-xl bg-blue-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-600"
                    >
                        Tambah Baris
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <template x-for="(row, index) in scopeRows" :key="index">
                        <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[2fr_0.8fr_0.8fr_1.5fr_auto]">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Scope Pekerjaan</label>
                                <input x-model="row.scope_pekerjaan" :name="`scope_pekerjaan[${index}]`" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" required>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Qty</label>
                                <input x-model="row.qty" :name="`qty[${index}]`" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" required>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Satuan</label>
                                <input x-model="row.satuan" :name="`satuan[${index}]`" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" required>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Keterangan</label>
                                <input x-model="row.keterangan" :name="`keterangan[${index}]`" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                            </div>

                            <div class="flex items-end">
                                <button
                                    type="button"
                                    @click="if (scopeRows.length > 1) scopeRows.splice(index, 1)"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-rose-100 text-rose-700 transition hover:bg-rose-200"
                                >
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="mt-6">
                <label class="mb-2 block text-sm font-medium text-slate-700">Catatan</label>
                <textarea name="catatan" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('catatan', $scopeOfWork?->catatan ?? '') }}</textarea>
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Tanda Tangan Pembuat</h3>
                        <p class="mt-1 text-sm text-slate-500">Tanda tangan langsung di area ini sebelum menyimpan scope of work.</p>
                    </div>

                    <button
                        type="button"
                        @click="clearSignature()"
                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Bersihkan
                    </button>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-300 bg-white p-3">
                    <canvas
                        x-ref="signatureCanvas"
                        data-existing-signature="{{ old('tanda_tangan', $scopeOfWork?->tanda_tangan ?? '') }}"
                        class="h-52 w-full touch-none rounded-xl border border-dashed border-slate-300 bg-slate-50"
                    ></canvas>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="closeSowModal()" class="rounded-xl bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">
                    Batal
                </button>
                <button type="submit" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                    {{ $scopeOfWork ? 'Update Scope of Work' : 'Simpan Scope of Work' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function initScopeOfWorkModal(config) {
        return {
            showSowModal: config.showOnLoad ?? false,
            scopeRows: config.scopeRows ?? [{ scope_pekerjaan: '', qty: '', satuan: '', keterangan: '' }],
            signatureData: config.signatureData ?? '',
            signaturePad: null,
            canvas: null,
            ctx: null,
            isDrawing: false,

            openSowModal() {
                this.showSowModal = true;
                this.$nextTick(() => this.setupSignatureCanvas());
            },

            closeSowModal() {
                this.showSowModal = false;
            },

            setupSignatureCanvas() {
                if (!this.$refs.signatureCanvas) return;

                this.canvas = this.$refs.signatureCanvas;
                this.ctx = this.canvas.getContext('2d');

                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const rect = this.canvas.getBoundingClientRect();
                this.canvas.width = rect.width * ratio;
                this.canvas.height = rect.height * ratio;
                this.ctx.scale(ratio, ratio);
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = 'round';
                this.ctx.strokeStyle = '#0f172a';

                this.bindCanvasEvents();
                this.renderExistingSignature();
            },

            bindCanvasEvents() {
                if (!this.canvas || this.canvas.dataset.bound === '1') return;

                const point = (event) => {
                    const rect = this.canvas.getBoundingClientRect();
                    const clientX = event.touches ? event.touches[0].clientX : event.clientX;
                    const clientY = event.touches ? event.touches[0].clientY : event.clientY;

                    return {
                        x: clientX - rect.left,
                        y: clientY - rect.top,
                    };
                };

                const start = (event) => {
                    event.preventDefault();
                    const current = point(event);
                    this.isDrawing = true;
                    this.ctx.beginPath();
                    this.ctx.moveTo(current.x, current.y);
                };

                const move = (event) => {
                    if (!this.isDrawing) return;
                    event.preventDefault();
                    const current = point(event);
                    this.ctx.lineTo(current.x, current.y);
                    this.ctx.stroke();
                    this.signatureData = this.canvas.toDataURL('image/png');
                };

                const stop = () => {
                    if (!this.isDrawing) return;
                    this.isDrawing = false;
                    this.signatureData = this.canvas.toDataURL('image/png');
                };

                this.canvas.addEventListener('mousedown', start);
                this.canvas.addEventListener('mousemove', move);
                window.addEventListener('mouseup', stop);
                this.canvas.addEventListener('touchstart', start, { passive: false });
                this.canvas.addEventListener('touchmove', move, { passive: false });
                window.addEventListener('touchend', stop, { passive: false });
                this.canvas.dataset.bound = '1';
            },

            renderExistingSignature() {
                const existing = this.$refs.signatureCanvas.dataset.existingSignature || this.signatureData;
                if (!existing) return;

                const image = new Image();
                image.onload = () => {
                    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                    this.ctx.drawImage(image, 0, 0, this.canvas.getBoundingClientRect().width, this.canvas.getBoundingClientRect().height);
                    this.signatureData = existing;
                };
                image.src = existing;
            },

            clearSignature() {
                if (!this.ctx || !this.canvas) return;
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                this.signatureData = '';
            },

            init() {
                if (this.showSowModal) {
                    this.$nextTick(() => this.setupSignatureCanvas());
                }
            },
        };
    }
</script>
