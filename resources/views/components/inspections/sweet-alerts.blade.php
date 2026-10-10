@props([
    'success' => null,
    'error' => null,
    'warning' => null,
    'errorMessages' => [],
])

@php
    $messages = collect($errorMessages)->filter(fn ($message) => filled($message))->values()->all();
@endphp

<div
    data-inspection-alerts
    data-success-message="{{ $success }}"
    data-error-message="{{ $error }}"
    data-warning-message="{{ $warning }}"
    data-error-messages='@json($messages)'
    hidden
></div>

@once
    <script>
        (() => {
            const colors = { primary: '#7f1017', danger: '#dc2626', warning: '#d97706' };

            const fire = (options) => {
                if (!window.Swal) {
                    console.error('SweetAlert2 belum tersedia.', options?.text ?? options?.title ?? '');
                    return Promise.resolve({ isConfirmed: false });
                }

                return window.Swal.fire({
                    confirmButtonColor: colors.primary,
                    ...options,
                });
            };

            const confirm = async (options = {}) => {
                const result = await fire({
                    icon: options.icon ?? 'warning',
                    title: options.title ?? 'Lanjutkan tindakan?',
                    text: options.text ?? '',
                    showCancelButton: true,
                    confirmButtonText: options.confirmButtonText ?? 'Ya, lanjutkan',
                    cancelButtonText: options.cancelButtonText ?? 'Batal',
                    confirmButtonColor: options.confirmButtonColor ?? colors.warning,
                    reverseButtons: true,
                    focusCancel: true,
                });

                return result.isConfirmed === true;
            };

            window.inspectionSweetAlert = {
                fire,
                confirm,
                error(message, title = 'Gagal') {
                    return fire({ icon: 'error', title, text: message });
                },
                success(message, title = 'Berhasil') {
                    return fire({ icon: 'success', title, text: message, timer: 1900, showConfirmButton: false });
                },
                warning(message, title = 'Perhatian') {
                    return fire({ icon: 'warning', title, text: message, confirmButtonColor: colors.warning });
                },
            };

            document.addEventListener('submit', async (event) => {
                const form = event.target.closest('form[data-swal-confirm]');
                if (!form || form.dataset.swalConfirmed === 'true') {
                    return;
                }

                event.preventDefault();
                const submitter = event.submitter;
                const confirmed = await confirm({
                    icon: form.dataset.swalIcon,
                    title: form.dataset.swalTitle,
                    text: form.dataset.swalText,
                    confirmButtonText: form.dataset.swalConfirmText,
                    confirmButtonColor: form.dataset.swalConfirmColor,
                });

                if (!confirmed) {
                    return;
                }

                form.dataset.swalConfirmed = 'true';
                form.requestSubmit(submitter ?? undefined);
            });

            document.addEventListener('DOMContentLoaded', async () => {
                for (const element of document.querySelectorAll('[data-inspection-alerts]')) {
                    const success = element.dataset.successMessage;
                    const error = element.dataset.errorMessage;
                    const warning = element.dataset.warningMessage;
                    let errors = [];

                    try {
                        errors = JSON.parse(element.dataset.errorMessages || '[]');
                    } catch (_) {
                        errors = [];
                    }

                    if (errors.length > 0) {
                        const list = document.createElement('div');
                        list.className = 'space-y-2 text-left';
                        if (success) {
                            const saved = document.createElement('p');
                            saved.className = 'font-semibold text-emerald-700';
                            saved.textContent = success;
                            list.appendChild(saved);
                        }
                        errors.forEach((message) => {
                            const item = document.createElement('p');
                            item.textContent = `• ${message}`;
                            list.appendChild(item);
                        });
                        await fire({
                            icon: success ? 'warning' : 'error',
                            title: success ? 'Draft tersimpan, tanda tangan belum diproses' : 'Periksa kembali isian',
                            html: list,
                            confirmButtonColor: success ? colors.warning : colors.danger,
                        });
                    } else if (error) {
                        await window.inspectionSweetAlert.error(error);
                    } else if (success) {
                        await window.inspectionSweetAlert.success(success);
                    }

                    if (warning) {
                        await window.inspectionSweetAlert.warning(warning);
                    }
                }
            });
        })();
    </script>
@endonce
