<div id="userTimelineInfoModal" class="fixed inset-0 z-[90] hidden items-center justify-center p-4">
    <button type="button" data-close-user-timeline-info class="absolute inset-0 bg-slate-950/55" aria-label="Tutup detail timeline"></button>

    <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div class="min-w-0">
                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-[#7f1017]">Informasi</div>
                <h2 id="userTimelineInfoTitle" class="mt-1 truncate text-xl font-black text-slate-900">Detail</h2>
            </div>
            <button type="button" data-close-user-timeline-info class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800" aria-label="Tutup">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div id="userTimelineInfoRows" class="grid gap-2 px-5 py-4"></div>
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

        const openModal = (payload) => {
            const rows = Array.isArray(payload.rows) ? payload.rows : [];

            title.textContent = payload.title || 'Detail';
            rowsContainer.innerHTML = rows.length > 0
                ? rows.map((row) => `
                    <div class="rounded-2xl border border-stone-200 bg-stone-50/80 px-4 py-3">
                        <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">${escapeHtml(row.label || '-')}</div>
                        <div class="mt-1 text-sm font-bold leading-6 text-slate-900">${escapeHtml(row.value || '-')}</div>
                    </div>
                `).join('')
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
