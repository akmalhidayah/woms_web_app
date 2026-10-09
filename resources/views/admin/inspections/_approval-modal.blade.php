<dialog x-ref="approvalDialog" aria-labelledby="inspection-flow-title" class="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl border border-slate-200 p-0 backdrop:bg-slate-900/50">
    <div class="flex max-h-[85vh] flex-col">
        <header class="flex items-start justify-between gap-3 border-b border-slate-200 p-5">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">Status Alur</p>
                <h2 id="inspection-flow-title" class="mt-1 break-words text-lg font-bold" x-text="approvalProgress?.number ?? 'Inspeksi Peralatan'"></h2>
                <p class="mt-1 text-xs text-slate-500" x-text="approvalProgress?.equipment ?? 'Progres tanda tangan inspeksi.'"></p>
            </div>
            <button type="button" @click="$refs.approvalDialog.close()" aria-label="Tutup status alur" class="rounded-lg px-2 py-1 text-slate-500">✕</button>
        </header>
        <div class="space-y-3 overflow-y-auto p-4">
            <p x-show="approvalLoading" role="status" class="text-sm">Memuat progres approval…</p>
            <p x-show="approvalError" x-text="approvalError" role="alert" class="text-sm text-red-700"></p>
            <template x-if="approvalProgress">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold">
                        <span class="rounded-full bg-blue-50 px-2.5 py-1.5 text-blue-700" x-text="approvalProgress.signed_count + '/' + approvalProgress.total + ' TTD'"></span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1.5 text-slate-600" x-text="approvalProgress.percent + '%'"></span>
                    </div>
                    <p class="text-xs text-slate-600" x-text="approvalProgress.status"></p>
                    <p x-show="approvalProgress.error" x-text="approvalProgress.error" class="rounded-lg border border-red-200 p-3 text-xs text-red-700"></p>
                    <template x-for="step in approvalProgress.steps" :key="step.role">
                        <article class="rounded-xl border p-3" :class="step.state === 'signed' ? 'border-emerald-200 bg-emerald-50' : (step.state === 'pending' ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-slate-50')">
                            <div class="flex items-start justify-between gap-2">
                                <div><h3 class="text-xs font-semibold" x-text="step.label"></h3><p class="mt-1 text-[11px] text-slate-500" x-text="step.name"></p></div>
                                <span class="shrink-0 rounded-full border bg-white px-2 py-1 text-[10px] font-semibold" x-text="step.state_label"></span>
                            </div>
                            <p x-show="step.signed_at" class="mt-2 text-[10px] text-slate-500" x-text="step.signed_at"></p>
                            <p x-show="step.note" class="mt-2 whitespace-pre-wrap text-xs" x-text="step.note"></p>
                            <p x-show="step.email_status" class="mt-2 text-[10px] text-slate-500" x-text="'Email: ' + step.email_status"></p>
                            <template x-if="step.resend_url">
                                <form method="POST" :action="step.resend_url" class="mt-3" onsubmit="return confirm('Kirim ulang email kepada Manager Workshop?')">
                                    @csrf
                                    <button class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-[10px] font-semibold text-blue-700">Kirim Ulang Email</button>
                                </form>
                            </template>
                        </article>
                    </template>
                </div>
            </template>
        </div>
        <footer class="flex justify-end gap-2 border-t border-slate-200 p-4">
            <template x-if="approvalProgress"><a :href="approvalProgress.detail_url" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold">Lihat Detail</a></template>
            <button type="button" @click="$refs.approvalDialog.close()" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white">Tutup</button>
        </footer>
    </div>
</dialog>
