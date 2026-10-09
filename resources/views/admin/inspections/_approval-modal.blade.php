<div x-show="approvalModalOpen" x-cloak @keydown.escape.window="approvalModalOpen = false" class="fixed inset-0 z-[120] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="inspection-flow-title">
    <button type="button" @click="approvalModalOpen = false" class="absolute inset-0 bg-slate-900/45" aria-label="Tutup status alur"></button>
    <div class="relative flex min-h-full items-start justify-center px-4 pb-6 pt-28 sm:pb-8 sm:pt-32">
        <div class="my-2 w-full max-w-md overflow-hidden rounded-[1.2rem] border border-slate-200 bg-white shadow-2xl">
            <header class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">Status Alur</p>
                    <h2 id="inspection-flow-title" class="mt-1.5 text-[1.2rem] font-bold leading-none tracking-tight text-slate-900" x-text="approvalProgress?.number ?? '-'">-</h2>
                    <p class="mt-2 text-[11px] text-slate-500">Progress tanda tangan Inspeksi Peralatan yang sedang berjalan.</p>
                </div>
                <button type="button" @click="approvalModalOpen = false" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup detail approval Inspeksi Peralatan">
                    <i data-lucide="x" class="h-3.5 w-3.5" aria-hidden="true"></i>
                </button>
            </header>

            <div class="max-h-[58vh] space-y-3 overflow-y-auto px-4 py-3.5">
                <p x-show="approvalLoading" role="status" class="text-[11px] text-slate-500">Memuat progres approval…</p>
                <p x-show="approvalError" x-text="approvalError" role="alert" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700"></p>

                <template x-if="approvalProgress">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold text-blue-700 ring-1 ring-blue-100" x-text="approvalProgress.signed_count + '/' + approvalProgress.total + ' TTD'"></span>
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600" x-text="approvalProgress.percent + '%'">0%</span>
                        </div>

                        <p x-show="approvalProgress.error" x-text="approvalProgress.error" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700"></p>

                        <template x-for="step in approvalProgress.steps" :key="step.role">
                            <article class="rounded-xl border px-3 py-2.5" :class="step.state === 'signed' ? 'border-emerald-200 bg-emerald-50' : (step.state === 'pending' ? 'border-blue-200 bg-blue-50' : (step.state === 'returned' || step.state === 'revision' ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50'))">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="truncate text-[13px] font-medium text-slate-800" x-text="step.label"></h3>
                                        <p class="mt-1 truncate text-[11px] text-slate-500" x-text="step.name"></p>
                                    </div>
                                    <span class="inline-flex shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                        :class="step.state === 'signed' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : (step.state === 'pending' ? 'border-blue-200 bg-blue-50 text-blue-700' : (step.state === 'returned' || step.state === 'revision' ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-slate-200 bg-slate-100 text-slate-500'))"
                                        x-text="step.state === 'signed' ? 'OK' : step.state_label"></span>
                                </div>
                                <p x-show="step.signed_at" class="mt-2 text-[10px] text-slate-500" x-text="step.signed_at"></p>
                                <p x-show="step.note" class="mt-2 whitespace-pre-wrap text-[11px] text-slate-600" x-text="step.note"></p>
                                <p x-show="step.email_status" class="mt-2 text-[10px] text-slate-500" x-text="'Email: ' + step.email_status"></p>
                                <template x-if="step.resend_url">
                                    <form method="POST" :action="step.resend_url" class="mt-2" onsubmit="return confirm('Kirim ulang email kepada Manager Workshop?')">
                                        @csrf
                                        <button class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-sky-700 transition hover:bg-sky-100">
                                            <i data-lucide="send" class="h-3 w-3" aria-hidden="true"></i>Resend
                                        </button>
                                    </form>
                                </template>
                            </article>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
