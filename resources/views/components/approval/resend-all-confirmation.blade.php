@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.js-resend-all-approval-form, .js-regenerate-expired-approval-form').forEach((form) => {
                const submitButton = form.querySelector('button[type="submit"]');
                const cooldownLabel = form.querySelector('[data-resend-cooldown-label]');
                const availableAt = Date.parse(form.dataset.resendAvailableAt || '');
                const regeneratesExpiredTokens = form.classList.contains('js-regenerate-expired-approval-form');

                if (submitButton && Number.isFinite(availableAt)) {
                    const syncCooldown = () => {
                        const remainingMilliseconds = availableAt - Date.now();

                        if (remainingMilliseconds <= 0) {
                            submitButton.disabled = false;
                            cooldownLabel?.setAttribute('hidden', 'hidden');
                            return false;
                        }

                        submitButton.disabled = true;

                        if (cooldownLabel) {
                            const totalMinutes = Math.ceil(remainingMilliseconds / 60000);
                            const hours = Math.floor(totalMinutes / 60);
                            const minutes = totalMinutes % 60;
                            cooldownLabel.textContent = `Bisa lagi dalam ${hours}j ${minutes}m`;
                            cooldownLabel.removeAttribute('hidden');
                        }

                        return true;
                    };

                    if (syncCooldown()) {
                        const cooldownInterval = window.setInterval(() => {
                            if (!syncCooldown()) {
                                window.clearInterval(cooldownInterval);
                            }
                        }, 30000);
                    }
                }

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    if (form.dataset.submitting === 'true') {
                        return;
                    }

                    const documentLabel = form.dataset.approvalDocument || 'dokumen';
                    const cooldownText = form.dataset.cooldownHours
                        ? ` Setelah berhasil dikirim, tombol PKM akan dikunci selama ${form.dataset.cooldownHours} jam.`
                        : '';
                    const title = regeneratesExpiredTokens
                        ? `Regenerate semua token kedaluwarsa ${documentLabel}?`
                        : `Resend semua email ${documentLabel}?`;
                    const confirmationText = regeneratesExpiredTokens
                        ? `Semua token approval ${documentLabel} yang sudah kedaluwarsa akan diganti dan email approval baru akan dikirim. Token yang masih valid tidak berubah.`
                        : `Email approval akan dikirim ulang kepada seluruh approver ${documentLabel} yang sedang aktif.${cooldownText}`;
                    const confirmationButtonText = regeneratesExpiredTokens ? 'Ya, regenerate' : 'Ya, kirim ulang';

                    if (!window.Swal) {
                        form.submit();
                        return;
                    }

                    const result = await window.Swal.fire({
                        icon: 'question',
                        title,
                        text: confirmationText,
                        showCancelButton: true,
                        confirmButtonText: confirmationButtonText,
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#2563eb',
                        cancelButtonColor: '#64748b',
                        reverseButtons: true,
                        focusCancel: true,
                    });

                    if (!result.isConfirmed) {
                        return;
                    }

                    form.dataset.submitting = 'true';

                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.classList.add('cursor-not-allowed', 'opacity-60');
                        submitButton.textContent = regeneratesExpiredTokens ? 'Memproses...' : 'Mengirim...';
                    }

                    form.submit();
                });
            });
        });
    </script>
@endonce
