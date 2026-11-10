{{-- Label merah kecil di bawah Nomor Order / Notifikasi bila sudah dipakai. Hanya petunjuk; validasi unique di backend tetap berlaku. --}}
@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const checkUrl = @js(route('admin.orders.check-unique'));
            const fields = [
                ['createNomorOrder', 'nomor_order'],
                ['createNotifikasi', 'notifikasi'],
                ['editNomorOrder', 'nomor_order'],
                ['editNotifikasi', 'notifikasi'],
            ];

            fields.forEach(([inputId, field]) => {
                const input = document.getElementById(inputId);

                if (!input) {
                    return;
                }

                const label = document.createElement('p');
                label.id = `${inputId}UniqueHint`;
                label.className = 'mt-1 hidden text-xs font-medium text-rose-600';
                label.setAttribute('aria-live', 'polite');
                input.insertAdjacentElement('afterend', label);

                let timer = null;
                let sequence = 0;
                let controller = null;

                const clear = () => {
                    window.clearTimeout(timer);
                    controller?.abort();
                    sequence++;
                    label.textContent = '';
                    label.classList.add('hidden');
                    input.removeAttribute('aria-describedby');
                };

                const check = async () => {
                    const value = input.value.trim();

                    if (value === '') {
                        clear();
                        return;
                    }

                    const current = ++sequence;
                    controller?.abort();
                    controller = new AbortController();

                    const params = new URLSearchParams({ field, value });
                    const ignore = inputId.startsWith('edit')
                        ? (document.getElementById('editOriginalOrder')?.value || '').trim()
                        : '';

                    if (ignore !== '') {
                        params.set('ignore', ignore);
                    }

                    try {
                        const response = await fetch(`${checkUrl}?${params.toString()}`, {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                            cache: 'no-store',
                            signal: controller.signal,
                        });

                        if (!response.ok || current !== sequence || input.value.trim() !== value) {
                            return;
                        }

                        const payload = await response.json();

                        if (payload.exists && payload.message) {
                            label.textContent = payload.message;
                            label.classList.remove('hidden');
                            input.setAttribute('aria-describedby', label.id);
                        } else {
                            clear();
                        }
                    } catch (error) {
                        // Pengecekan hanya petunjuk; kegagalan jaringan tidak menghalangi pengisian form.
                    }
                };

                input.addEventListener('input', () => {
                    window.clearTimeout(timer);
                    label.classList.add('hidden');
                    timer = window.setTimeout(check, 450);
                });
                input.addEventListener('blur', () => {
                    if (input.value.trim() !== '') {
                        window.clearTimeout(timer);
                        check();
                    }
                });

                // Bersihkan label lama setiap modal dibuka/ditutup (nilai input diisi ulang lewat script).
                const modal = input.closest('.fixed');

                if (modal) {
                    new MutationObserver(clear).observe(modal, { attributes: true, attributeFilter: ['class'] });
                }
            });
        });
    </script>
@endonce
