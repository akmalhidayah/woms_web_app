<div id="userTimelineInfoModal" class="fixed inset-0 z-[90] hidden items-start justify-center overflow-y-auto p-3 sm:items-center sm:p-5">
    <button type="button" data-close-user-timeline-info class="absolute inset-0 bg-slate-950/55" aria-label="Tutup detail timeline"></button>

    <div class="relative z-10 my-3 flex max-h-[calc(100dvh-1.5rem)] w-full max-w-3xl flex-col overflow-hidden rounded-[22px] bg-white shadow-2xl sm:my-0 sm:max-h-[calc(100dvh-2.5rem)]">
        <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 bg-white px-4 py-3 sm:px-5">
            <div class="flex min-w-0 items-start gap-3">
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#7f1017] text-white shadow-sm">
                    <i data-lucide="list-checks" class="h-4 w-4"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-[10px] font-black uppercase tracking-[0.2em] text-[#7f1017]">Informasi Timeline</div>
                    <h2 id="userTimelineInfoTitle" class="mt-0.5 truncate text-lg font-black text-slate-900 sm:text-xl">Detail</h2>
                </div>
            </div>
            <button type="button" data-close-user-timeline-info class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800" aria-label="Tutup">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div id="userTimelineInfoRows" class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5"></div>
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

        const hasMeaningfulNote = (value) => {
            const text = String(value ?? '').trim();
            const normalized = text.toLowerCase();

            return text !== ''
                && text !== '-'
                && normalized !== 'belum ada catatan.';
        };

        const noteForStatus = (statusLabel, noteRows) => {
            const normalized = String(statusLabel || '').toLowerCase();
            const wanted = normalized.includes('konfirmasi')
                ? 'catatan konfirmasi'
                : normalized.includes('material')
                    ? 'catatan material'
                    : normalized.includes('progress')
                        ? 'catatan progress'
                        : '';

            if (!wanted) {
                return null;
            }

            return noteRows.find((row) => String(row.label || '').toLowerCase() === wanted) || null;
        };

        const openModal = (payload) => {
            const rows = Array.isArray(payload.rows) ? payload.rows : [];
            const noteRows = rows.filter((row) => String(row.label || '').toLowerCase().includes('catatan'));
            const statusRows = rows.filter((row) => ! String(row.label || '').toLowerCase().includes('catatan'));

            title.textContent = payload.title || 'Detail';
            rowsContainer.innerHTML = rows.length > 0
                ? `
                    <div class="grid gap-2.5 md:grid-cols-2">
                        ${statusRows.map((row) => {
                            const icon = rowIcon(row.label);
                            const value = displayValue(row.value);
                            const tone = rowTone(value);
                            const note = noteForStatus(row.label, noteRows);
                            const noteValue = note ? displayValue(note.value, '') : '';

                            return `
                                <div class="rounded-2xl border border-stone-200 bg-white p-3 shadow-sm">
                                    <div class="flex items-start gap-2.5">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border ${tone}">
                                            <i data-lucide="${icon}" class="h-4 w-4"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">${escapeHtml(row.label || '-')}</div>
                                            <div class="mt-0.5 text-sm font-black leading-5 text-slate-900">${escapeHtml(value)}</div>
                                        </div>
                                    </div>
                                    ${hasMeaningfulNote(noteValue) ? `
                                        <div class="mt-2.5 rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm font-semibold leading-5 text-slate-600">
                                            ${escapeHtml(noteValue)}
                                        </div>
                                    ` : ''}
                                </div>
                            `;
                        }).join('')}
                    </div>
                `
                : '<div class="rounded-2xl border border-dashed border-stone-200 bg-stone-50 px-4 py-8 text-center text-sm font-semibold text-slate-500">Belum ada informasi.</div>';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            rowsContainer.scrollTop = 0;
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
