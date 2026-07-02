<div id="userTimelineInfoModal" class="fixed inset-0 z-[90] hidden items-center justify-center p-4">
    <button type="button" data-close-user-timeline-info class="absolute inset-0 bg-slate-950/55" aria-label="Tutup detail timeline"></button>

    <div class="relative z-10 w-full max-w-4xl overflow-hidden rounded-[26px] bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 bg-white px-5 py-4 sm:px-6">
            <div class="flex min-w-0 items-start gap-3">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#7f1017] text-white shadow-sm">
                    <i data-lucide="list-checks" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-[10px] font-black uppercase tracking-[0.2em] text-[#7f1017]">Informasi Timeline</div>
                    <h2 id="userTimelineInfoTitle" class="mt-1 truncate text-xl font-black text-slate-900 sm:text-2xl">Detail</h2>
                </div>
            </div>
            <button type="button" data-close-user-timeline-info class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800" aria-label="Tutup">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div id="userTimelineInfoRows" class="max-h-[calc(100vh-10rem)] overflow-y-auto px-5 py-5 sm:px-6"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('userTimelineInfoModal');
        const title = document.getElementById('userTimelineInfoTitle');
        const rowsContainer = document.getElementById('userTimelineInfoRows');

        if (! modal || ! rowsContainer) {
            return;
        }

        const escapeHtml = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        const rowIcon = (label) => {
            const normalized = String(label || '').toLowerCase();

            if (normalized.includes('konfirmasi')) return 'badge-check';
            if (normalized.includes('budget') || normalized.includes('transfer')) return 'wallet-cards';
            if (normalized.includes('material')) return 'package-check';
            if (normalized.includes('progress')) return 'activity';
            if (normalized.includes('regu')) return 'users';
            if (normalized.includes('catatan')) return 'message-square-text';

            return 'info';
        };

        const rowTone = (value) => {
            const normalized = String(value || '').toLowerCase();

            if (normalized.includes('not ready') || normalized.includes('waiting') || normalized.includes('pending') || normalized.includes('menunggu')) {
                return 'border-amber-200 bg-amber-50 text-amber-700';
            }

            if (normalized === '-' || normalized.includes('belum') || normalized.includes('tidak berlaku')) {
                return 'border-stone-200 bg-stone-50 text-slate-500';
            }

            if (normalized.includes('complete') || normalized.includes('selesai') || normalized.includes('ready')) {
                return 'border-emerald-200 bg-emerald-50 text-emerald-700';
            }

            return 'border-slate-200 bg-white text-slate-700';
        };

        const displayValue = (value, fallback = '-') => {
            const text = String(value ?? '').trim();

            return text === '' ? fallback : text;
        };

        const openModal = (payload) => {
            const rows = Array.isArray(payload.rows) ? payload.rows : [];
            const noteRows = rows.filter((row) => String(row.label || '').toLowerCase().includes('catatan'));
            const statusRows = rows.filter((row) => ! String(row.label || '').toLowerCase().includes('catatan'));
            const summary = displayValue(payload.summary || '', '');
            const headline = displayValue(payload.headline || '', payload.title || 'Detail');
            const badge = displayValue(payload.badge || '', '');

            title.textContent = payload.title || 'Detail';
            rowsContainer.innerHTML = rows.length > 0
                ? `
                    <div class="rounded-[22px] border border-red-100 bg-red-50/60 px-4 py-4 sm:px-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-[#7f1017]">Ringkasan</div>
                                <div class="mt-1 text-xl font-black leading-tight text-slate-900">${escapeHtml(headline)}</div>
                                ${summary ? `<div class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-slate-600">${escapeHtml(summary)}</div>` : ''}
                            </div>
                            ${badge ? `<span class="inline-flex w-fit shrink-0 rounded-full border border-red-200 bg-white px-3 py-1 text-xs font-black text-[#7f1017]">${escapeHtml(badge)}</span>` : ''}
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        ${statusRows.map((row) => {
                            const icon = rowIcon(row.label);
                            const value = displayValue(row.value);
                            const tone = rowTone(value);

                            return `
                                <div class="rounded-[18px] border border-stone-200 bg-white p-4 shadow-sm">
                                    <div class="flex items-start gap-3">
                                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border ${tone}">
                                            <i data-lucide="${icon}" class="h-4 w-4"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">${escapeHtml(row.label || '-')}</div>
                                            <div class="mt-1 text-sm font-black leading-6 text-slate-900">${escapeHtml(value)}</div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>

                    ${noteRows.length > 0 ? `
                        <div class="mt-4 rounded-[22px] border border-stone-200 bg-stone-50/80 p-4">
                            <div class="mb-3 flex items-center gap-2">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-white text-[#7f1017] ring-1 ring-stone-200">
                                    <i data-lucide="notebook-text" class="h-4 w-4"></i>
                                </span>
                                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Catatan</div>
                            </div>
                            <div class="grid gap-2 md:grid-cols-3">
                                ${noteRows.map((row) => {
                                    const value = displayValue(row.value, 'Belum ada catatan.');

                                    return `
                                        <div class="rounded-2xl border border-stone-200 bg-white px-4 py-3">
                                            <div class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">${escapeHtml(row.label || '-')}</div>
                                            <div class="mt-1 text-sm font-semibold leading-6 text-slate-700">${escapeHtml(value === '-' ? 'Belum ada catatan.' : value)}</div>
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    ` : ''}
                `
                : '<div class="rounded-2xl border border-dashed border-stone-200 bg-stone-50 px-4 py-8 text-center text-sm font-semibold text-slate-500">Belum ada informasi.</div>';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            window.lucide?.createIcons();
        };

        document.querySelectorAll('.timeline-info-trigger').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                try {
                    openModal(JSON.parse(trigger.dataset.payload || '{}'));
                } catch (error) {
                    openModal({});
                }
            });
        });

        modal.querySelectorAll('[data-close-user-timeline-info]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && ! modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    });
</script>
