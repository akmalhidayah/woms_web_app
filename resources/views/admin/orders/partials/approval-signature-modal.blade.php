<div id="approvalSignatureInfoModal" class="fixed inset-0 z-[80] hidden items-center justify-center p-4">
    <button type="button" data-close-approval-signature class="absolute inset-0 bg-slate-950/60" aria-label="Tutup informasi approval"></button>

    <div class="relative z-10 w-full max-w-lg rounded-3xl bg-white p-5 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-500">Approval Dokumen</div>
                <h2 id="approvalSignatureTitle" class="mt-1 text-lg font-bold text-slate-900">Informasi Tanda Tangan</h2>
                <p id="approvalSignatureSummary" class="mt-1 text-xs font-medium text-slate-500">-</p>
            </div>
            <button type="button" data-close-approval-signature class="text-2xl leading-none text-slate-400 transition hover:text-slate-700">&times;</button>
        </div>

        <div id="approvalSignatureChecklist" class="mt-5 space-y-2"></div>

        <div id="approvalSignatureActive" class="mt-4 hidden rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3">
            <div class="text-[9px] font-semibold uppercase tracking-[0.14em] text-blue-500">TTD Aktif</div>
            <div id="approvalSignatureActiveRole" class="mt-1 text-sm font-bold text-slate-800">-</div>
            <div id="approvalSignatureActiveSigner" class="mt-0.5 text-xs text-slate-600">-</div>
            <div id="approvalSignatureExpiry" class="mt-1 text-[10px] font-medium text-slate-500">-</div>
        </div>

        <div class="mt-5 flex flex-wrap justify-end gap-2">
            <button id="approvalSignatureCopy" type="button" class="hidden items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                <i data-lucide="copy" class="h-3.5 w-3.5"></i>
                <span data-copy-label>Salin Link</span>
            </button>
            <a id="approvalSignatureOpen" href="#" target="_blank" rel="noopener noreferrer" class="hidden items-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                Buka TTD
            </a>
            <a id="approvalSignatureWhatsapp" href="#" target="_blank" rel="noopener noreferrer" class="hidden items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                <i data-lucide="message-circle" class="h-3.5 w-3.5"></i>
                WhatsApp
            </a>
            <span id="approvalSignatureNoWhatsapp" class="hidden items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-400" title="Nomor WhatsApp approver belum tersedia di user panel">
                <i data-lucide="message-circle-off" class="h-3.5 w-3.5"></i>
                No WA
            </span>
            <form id="approvalSignatureResendForm" method="POST" action="#" class="hidden">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 transition hover:bg-sky-100">
                    <i data-lucide="send" class="h-3.5 w-3.5"></i>
                    Resend Email
                </button>
            </form>
            <form id="approvalSignatureRegenerateForm" method="POST" action="#" class="hidden">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">
                    <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                    Buat Token Baru
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('approvalSignatureInfoModal');
        const title = document.getElementById('approvalSignatureTitle');
        const summary = document.getElementById('approvalSignatureSummary');
        const checklist = document.getElementById('approvalSignatureChecklist');
        const activePanel = document.getElementById('approvalSignatureActive');
        const activeRole = document.getElementById('approvalSignatureActiveRole');
        const activeSigner = document.getElementById('approvalSignatureActiveSigner');
        const expiry = document.getElementById('approvalSignatureExpiry');
        const copyButton = document.getElementById('approvalSignatureCopy');
        const openLink = document.getElementById('approvalSignatureOpen');
        const whatsappLink = document.getElementById('approvalSignatureWhatsapp');
        const noWhatsappLabel = document.getElementById('approvalSignatureNoWhatsapp');
        const resendForm = document.getElementById('approvalSignatureResendForm');
        const regenerateForm = document.getElementById('approvalSignatureRegenerateForm');
        let approvalUrl = '';

        if (!modal) {
            return;
        }

        const escapeHtml = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const statusClasses = (status) => ({
            signed: 'border-emerald-200 bg-emerald-50 text-emerald-700',
            pending: 'border-blue-200 bg-blue-50 text-blue-700',
            locked: 'border-slate-200 bg-slate-50 text-slate-600',
            missing: 'border-amber-200 bg-amber-50 text-amber-700',
        }[status] || 'border-slate-200 bg-slate-50 text-slate-600');

        const setAction = (element, url, displayClass) => {
            const available = Boolean(url);
            element.classList.toggle('hidden', !available);
            element.classList.toggle(displayClass, available);
            if (available) {
                element.setAttribute(element.tagName === 'A' ? 'href' : 'action', url);
            }
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        document.querySelectorAll('.approval-signature-info-trigger').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                let items = [];
                try {
                    items = JSON.parse(trigger.dataset.checklist || '[]');
                } catch (error) {
                    items = [];
                }

                title.textContent = trigger.dataset.title || 'Informasi Tanda Tangan';
                summary.textContent = trigger.dataset.summary || '-';
                checklist.innerHTML = items.map((item) => `
                    <div class="flex items-start justify-between gap-3 rounded-2xl border px-3 py-2.5 ${statusClasses(item.status)}">
                        <div class="min-w-0">
                            <div class="text-xs font-bold">${escapeHtml(item.role || '-')}</div>
                            <div class="mt-0.5 truncate text-[11px] font-medium">${escapeHtml(item.name || '-')}</div>
                            ${item.signed_at ? `<div class="mt-0.5 text-[9px] opacity-75">${escapeHtml(item.signed_at)}</div>` : ''}
                        </div>
                        <span class="shrink-0 rounded-full bg-white/80 px-2 py-1 text-[9px] font-bold">${escapeHtml(item.status_label || '-')}</span>
                    </div>
                `).join('');

                const hasActiveSigner = Boolean(trigger.dataset.activeRole);
                activePanel.classList.toggle('hidden', !hasActiveSigner);
                activeRole.textContent = trigger.dataset.activeRole || '-';
                activeSigner.textContent = trigger.dataset.activeSigner || '-';
                expiry.textContent = trigger.dataset.expiry || '-';

                approvalUrl = trigger.dataset.approvalUrl || '';
                const whatsappUrl = trigger.dataset.whatsappUrl || '';
                setAction(copyButton, approvalUrl, 'inline-flex');
                setAction(openLink, approvalUrl, 'inline-flex');
                setAction(whatsappLink, whatsappUrl, 'inline-flex');
                noWhatsappLabel.classList.toggle('hidden', !approvalUrl || Boolean(whatsappUrl));
                noWhatsappLabel.classList.toggle('inline-flex', Boolean(approvalUrl) && !whatsappUrl);
                setAction(resendForm, trigger.dataset.resendUrl || '', 'block');
                setAction(regenerateForm, trigger.dataset.regenerateUrl || '', 'block');

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                window.lucide?.createIcons();
            });
        });

        copyButton.addEventListener('click', async () => {
            if (!approvalUrl) {
                return;
            }

            await navigator.clipboard.writeText(approvalUrl);
            const label = copyButton.querySelector('[data-copy-label]');
            const originalText = label.textContent;
            label.textContent = 'Tersalin';
            window.setTimeout(() => {
                label.textContent = originalText;
            }, 1500);
        });

        modal.querySelectorAll('[data-close-approval-signature]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    });
</script>
