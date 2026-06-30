<div id="userApprovalFlowModal" class="fixed inset-0 z-[90] hidden items-center justify-center p-4">
    <button type="button" data-close-user-approval-flow class="absolute inset-0 bg-slate-950/55" aria-label="Tutup status approval"></button>

    <div class="relative z-10 flex max-h-[86vh] w-full max-w-xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div class="min-w-0">
                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-600">Status Alur</div>
                <h2 id="userApprovalFlowTitle" class="mt-1 truncate text-xl font-black text-slate-900">Approval Dokumen</h2>
                <p id="userApprovalFlowSummary" class="mt-1 text-sm leading-5 text-slate-500">-</p>
            </div>
            <button type="button" data-close-user-approval-flow class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800" aria-label="Tutup">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3">
            <span id="userApprovalFlowCount" class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700 ring-1 ring-blue-100">0/0 TTD</span>
            <span id="userApprovalFlowPercent" class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">0%</span>
            <span id="userApprovalFlowState" class="ml-auto inline-flex rounded-full px-3 py-1 text-xs font-black ring-1">-</span>
        </div>

        <div id="userApprovalFlowItems" class="space-y-2 overflow-y-auto px-5 py-4"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('userApprovalFlowModal');
        const title = document.getElementById('userApprovalFlowTitle');
        const summary = document.getElementById('userApprovalFlowSummary');
        const count = document.getElementById('userApprovalFlowCount');
        const percent = document.getElementById('userApprovalFlowPercent');
        const state = document.getElementById('userApprovalFlowState');
        const itemsContainer = document.getElementById('userApprovalFlowItems');

        if (! modal || ! itemsContainer) {
            return;
        }

        const escapeHtml = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const statusClasses = (status) => ({
            signed: 'border-emerald-200 bg-emerald-50 text-emerald-800',
            pending: 'border-blue-200 bg-blue-50 text-blue-800',
            locked: 'border-slate-200 bg-slate-50 text-slate-600',
            missing: 'border-rose-200 bg-rose-50 text-rose-800',
            skipped: 'border-amber-200 bg-amber-50 text-amber-800',
        }[status] || 'border-slate-200 bg-slate-50 text-slate-600');

        const stateClasses = (value) => ({
            completed: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            pending: 'bg-blue-50 text-blue-700 ring-blue-100',
            expired: 'bg-amber-50 text-amber-700 ring-amber-100',
            missing: 'bg-rose-50 text-rose-700 ring-rose-100',
            in_review: 'bg-slate-100 text-slate-700 ring-slate-200',
            none: 'bg-slate-100 text-slate-600 ring-slate-200',
        }[value] || 'bg-slate-100 text-slate-600 ring-slate-200');

        const copyText = async (text) => {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return;
            }

            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            textarea.remove();
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        const openModal = (payload) => {
            const items = Array.isArray(payload.items) ? payload.items : [];

            title.textContent = payload.title || 'Approval Dokumen';
            summary.textContent = payload.document_number
                ? `${payload.document_number} - ${payload.summary || '-'}`
                : (payload.summary || '-');
            count.textContent = `${payload.completed_steps || 0}/${payload.total_steps || 0} TTD`;
            percent.textContent = `${payload.progress_percent || 0}%`;
            state.textContent = payload.label || '-';
            state.className = `ml-auto inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 ${stateClasses(payload.state)}`;

            itemsContainer.innerHTML = items.length > 0
                ? items.map((item) => {
                    const isActive = Boolean(item.is_active);
                    const hasLink = isActive && item.link;
                    const meta = [
                        item.expires_at ? `Berlaku sampai ${escapeHtml(item.expires_at)}` : '',
                        item.signed_at ? `TTD ${escapeHtml(item.signed_at)}` : '',
                        item.delegated_from_name ? `Dialihkan dari ${escapeHtml(item.delegated_from_name)}` : '',
                    ].filter(Boolean).join(' &bull; ');

                    return `
                        <div class="rounded-2xl border px-3.5 py-3 ${statusClasses(item.status)}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-black">${escapeHtml(item.role_label || '-')}</div>
                                    <div class="mt-1 truncate text-xs font-semibold opacity-80">${escapeHtml(item.signer_name || '-')}</div>
                                    ${meta ? `<div class="mt-1 text-[11px] font-medium opacity-70">${meta}</div>` : ''}
                                    ${item.delegation_reason ? `<div class="mt-1 text-[11px] font-medium opacity-70">Alasan: ${escapeHtml(item.delegation_reason)}</div>` : ''}
                                </div>
                                <span class="shrink-0 rounded-full bg-white/80 px-2.5 py-1 text-[10px] font-black">${escapeHtml(item.status_label || '-')}</span>
                            </div>
                            ${hasLink ? `
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <button type="button" class="user-approval-copy-link inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-white px-3 py-2 text-[11px] font-black text-blue-700 transition hover:bg-blue-50" data-link="${escapeHtml(item.link)}">
                                        <i data-lucide="copy" class="h-3.5 w-3.5"></i>
                                        <span data-copy-label>Salin Link</span>
                                    </button>
                                    ${item.whatsapp_url ? `
                                        <a href="${escapeHtml(item.whatsapp_url)}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-white px-3 py-2 text-[11px] font-black text-emerald-700 transition hover:bg-emerald-50">
                                            <i data-lucide="message-circle" class="h-3.5 w-3.5"></i>
                                            WhatsApp
                                        </a>
                                    ` : `
                                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] font-black text-slate-400">
                                            <i data-lucide="message-circle-off" class="h-3.5 w-3.5"></i>
                                            No WA
                                        </span>
                                    `}
                                </div>
                            ` : ''}
                        </div>
                    `;
                }).join('')
                : '<div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm font-semibold text-slate-500">Belum ada alur approval.</div>';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            window.lucide?.createIcons();
        };

        document.querySelectorAll('.approval-flow-trigger').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                try {
                    openModal(JSON.parse(trigger.dataset.payload || '{}'));
                } catch (error) {
                    openModal({});
                }
            });
        });

        itemsContainer.addEventListener('click', async (event) => {
            const copyButton = event.target.closest('.user-approval-copy-link');

            if (! copyButton) {
                return;
            }

            const link = copyButton.dataset.link || '';
            const label = copyButton.querySelector('[data-copy-label]');

            if (! link || ! label) {
                return;
            }

            await copyText(link);
            const original = label.textContent;
            label.textContent = 'Tersalin';
            window.setTimeout(() => {
                label.textContent = original;
            }, 1400);
        });

        modal.querySelectorAll('[data-close-user-approval-flow]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && ! modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    });
</script>
