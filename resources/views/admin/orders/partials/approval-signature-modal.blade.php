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
    </div>
</div>

<div id="approvalReassignmentModal" class="fixed inset-0 z-[90] hidden items-center justify-center p-4">
    <button type="button" data-close-approval-reassignment class="absolute inset-0 bg-slate-950/60" aria-label="Tutup alih approver"></button>

    <div class="relative z-10 w-full max-w-md rounded-3xl bg-white p-5 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-orange-500">Alihkan Approver</div>
                <h2 id="approvalReassignmentTitle" class="mt-1 text-lg font-bold text-slate-900">-</h2>
                <p id="approvalReassignmentCurrent" class="mt-1 text-xs font-medium text-slate-500">-</p>
            </div>
            <button type="button" data-close-approval-reassignment class="text-2xl leading-none text-slate-400 transition hover:text-slate-700">&times;</button>
        </div>

        <form id="approvalReassignmentForm" method="POST" action="#" class="mt-5 space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label for="approvalReassignmentSigner" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">Approver PLT</label>
                <select id="approvalReassignmentSigner" name="signer_user_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                    <option value="">Pilih user</option>
                </select>
            </div>

            <div>
                <label for="approvalReassignmentReason" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">Alasan</label>
                <textarea id="approvalReassignmentReason" name="delegation_reason" required rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none" placeholder="Contoh: pejabat definitif cuti/dinas, approval dialihkan ke PLT."></textarea>
            </div>

            <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
                <input type="checkbox" name="send_email" value="1" checked class="rounded border-slate-300 text-blue-600">
                Kirim email approval setelah dialihkan
            </label>

            <div class="flex justify-end gap-2">
                <button type="button" data-close-approval-reassignment class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-xl bg-orange-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-orange-700">Simpan Alih Approver</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('approvalSignatureInfoModal');
        const reassignmentModal = document.getElementById('approvalReassignmentModal');
        const reassignmentForm = document.getElementById('approvalReassignmentForm');
        const reassignmentTitle = document.getElementById('approvalReassignmentTitle');
        const reassignmentCurrent = document.getElementById('approvalReassignmentCurrent');
        const reassignmentSigner = document.getElementById('approvalReassignmentSigner');
        const reassignmentReason = document.getElementById('approvalReassignmentReason');
        const title = document.getElementById('approvalSignatureTitle');
        const summary = document.getElementById('approvalSignatureSummary');
        const checklist = document.getElementById('approvalSignatureChecklist');
        const reassignmentUsers = @json(($approvalReassignmentUsers ?? collect())->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'nomor_hp' => $user->nomor_hp,
        ])->values());

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

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };
        const closeReassignmentModal = () => {
            reassignmentModal?.classList.add('hidden');
            reassignmentModal?.classList.remove('flex');
        };
        const openReassignmentModal = (item) => {
            if (!reassignmentModal || !reassignmentForm || !reassignmentSigner) {
                return;
            }

            reassignmentForm.action = item.reassign_url || '#';
            reassignmentTitle.textContent = `PLT ${item.original_role || item.role || '-'}`;
            reassignmentCurrent.textContent = `Saat ini: ${item.name || '-'}${item.delegated_from_name ? ` (dialihkan dari ${item.delegated_from_name})` : ''}`;
            reassignmentReason.value = '';
            reassignmentSigner.innerHTML = '<option value="">Pilih user</option>' + reassignmentUsers.map((user) => `
                <option value="${escapeHtml(user.id)}" ${String(user.id) === String(item.signer_user_id || '') ? 'disabled' : ''}>
                    ${escapeHtml(user.name || '-')} - ${escapeHtml(user.email || '-')} (${escapeHtml(user.role || '-')})
                </option>
            `).join('');
            reassignmentModal.classList.remove('hidden');
            reassignmentModal.classList.add('flex');
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
                const approvalUrl = trigger.dataset.approvalUrl || '';
                const whatsappUrl = trigger.dataset.whatsappUrl || '';
                const resendUrl = trigger.dataset.resendUrl || '';
                const regenerateUrl = trigger.dataset.regenerateUrl || '';
                const expiry = trigger.dataset.expiry || '';

                checklist.innerHTML = items.map((item) => {
                    const isActive = Boolean(item.is_active);
                    const canReassign = Boolean(item.can_reassign && item.reassign_url);
                    const actionButtons = isActive
                        ? `
                            ${expiry ? `<div class="mt-1 text-[9px] font-medium opacity-75">${escapeHtml(expiry)}</div>` : ''}
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                ${approvalUrl ? `
                                    <button type="button" class="approval-modal-copy-link inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-blue-700 transition hover:bg-blue-100" data-link="${escapeHtml(approvalUrl)}">
                                        <i data-lucide="copy" class="h-3 w-3"></i>
                                        <span data-copy-label>Salin Link</span>
                                    </button>
                                    ${whatsappUrl ? `
                                        <a href="${escapeHtml(whatsappUrl)}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                            <i data-lucide="message-circle" class="h-3 w-3"></i>
                                            WhatsApp
                                        </a>
                                    ` : `
                                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-slate-400" title="Nomor WhatsApp approver belum tersedia di user panel">
                                            <i data-lucide="message-circle-off" class="h-3 w-3"></i>
                                            No WA
                                        </span>
                                    `}
                                ` : ''}
                                ${resendUrl ? `
                                    <form method="POST" action="${escapeHtml(resendUrl)}" class="inline-block">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-sky-700 transition hover:bg-sky-100">
                                            <i data-lucide="send" class="h-3 w-3"></i>
                                            Resend
                                        </button>
                                    </form>
                                ` : ''}
                                ${regenerateUrl ? `
                                    <form method="POST" action="${escapeHtml(regenerateUrl)}" class="inline-block">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-amber-700 transition hover:bg-amber-100">
                                            <i data-lucide="refresh-cw" class="h-3 w-3"></i>
                                            Buat Token Baru
                                        </button>
                                    </form>
                                ` : ''}
                            </div>
                        `
                        : '';
                    const reassignButton = canReassign
                        ? `
                            <button type="button" class="approval-modal-reassign inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-orange-700 transition hover:bg-orange-100" data-item='${escapeHtml(JSON.stringify(item))}'>
                                <i data-lucide="user-cog" class="h-3 w-3"></i>
                                Alihkan
                            </button>
                        `
                        : '';

                    return `
                        <div class="rounded-xl border px-3 py-2.5 ${statusClasses(item.status)}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold">${escapeHtml(item.role || '-')}</div>
                                    <div class="mt-0.5 truncate text-[11px] font-medium">${escapeHtml(item.name || '-')}</div>
                                    ${item.delegated_from_name ? `<div class="mt-0.5 text-[9px] opacity-75">Dialihkan dari ${escapeHtml(item.delegated_from_name)}</div>` : ''}
                                    ${item.delegation_reason ? `<div class="mt-0.5 text-[9px] opacity-75">Alasan: ${escapeHtml(item.delegation_reason)}</div>` : ''}
                                    ${item.signed_at ? `<div class="mt-0.5 text-[9px] opacity-75">${escapeHtml(item.signed_at)}</div>` : ''}
                                </div>
                                <span class="shrink-0 rounded-full bg-white/80 px-2 py-1 text-[9px] font-bold">${escapeHtml(item.status_label || '-')}</span>
                            </div>
                            ${actionButtons}
                            ${reassignButton ? `<div class="mt-2 flex flex-wrap items-center gap-1.5">${reassignButton}</div>` : ''}
                        </div>
                    `;
                }).join('');

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                window.lucide?.createIcons();
            });
        });

        checklist.addEventListener('click', async (event) => {
            const copyButton = event.target.closest('.approval-modal-copy-link');
            const reassignButton = event.target.closest('.approval-modal-reassign');
            if (reassignButton) {
                try {
                    openReassignmentModal(JSON.parse(reassignButton.dataset.item || '{}'));
                } catch (error) {
                    openReassignmentModal({});
                }
                return;
            }

            if (!copyButton) {
                return;
            }

            const approvalUrl = copyButton.dataset.link || '';
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
        reassignmentModal?.querySelectorAll('[data-close-approval-reassignment]').forEach((button) => {
            button.addEventListener('click', closeReassignmentModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    });
</script>
