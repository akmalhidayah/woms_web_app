<script>
    document.addEventListener('DOMContentLoaded', () => {
        const oldCreateJenisKontrak = @json((string) old('jenis_kontrak', ''));
        const oldCreateYears = @json(old('target_years', []));
        const oldCreateValues = @json(old('target_values', []));
        const oldEditMethod = @json((string) old('_method', ''));
        const oldEditId = @json((string) old('_edit_id', ''));
        const oldEditYears = @json(old('target_years', []));
        const oldEditValues = @json(old('target_values', []));
        const currentYear = new Date().getFullYear();
        const success = document.getElementById('outline-agreement-success');

        const formatCurrency = (value) => `Rp ${new Intl.NumberFormat('id-ID').format(Number(value || 0))}`;

        if (success?.dataset.message && window.Swal) {
            window.Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: success.dataset.message,
                timer: 1700,
                showConfirmButton: false,
            });
        }

        document.querySelectorAll('[data-delete-oa-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const oaNumber = form.dataset.oaNumber || 'OA ini';
                let confirmed = false;

                if (window.Swal) {
                    const result = await window.Swal.fire({
                        icon: 'warning',
                        title: 'Hapus OA?',
                        text: `${oaNumber} akan dihapus beserta histori dan target biayanya. Snapshot di dokumen yang sudah dibuat tetap tersimpan.`,
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#e11d48',
                    });

                    confirmed = result.isConfirmed;
                } else {
                    confirmed = window.confirm(`Hapus ${oaNumber}? Histori dan target biaya OA ini ikut terhapus.`);
                }

                if (confirmed) {
                    form.submit();
                }
            });
        });

        document.querySelectorAll('[data-toggle-oa-status-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const oaNumber = form.dataset.oaNumber || 'OA ini';
                const isActivate = form.dataset.nextAction === 'activate';
                const title = isActivate ? 'Aktifkan OA?' : 'Nonaktifkan OA?';
                const text = isActivate
                    ? `${oaNumber} akan tersedia kembali untuk proses yang memakai OA aktif.`
                    : `${oaNumber} tidak akan dihitung sebagai OA aktif sampai diaktifkan kembali.`;
                let confirmed = false;

                if (window.Swal) {
                    const result = await window.Swal.fire({
                        icon: isActivate ? 'question' : 'warning',
                        title,
                        text,
                        showCancelButton: true,
                        confirmButtonText: isActivate ? 'Ya, aktifkan' : 'Ya, nonaktifkan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: isActivate ? '#2563eb' : '#d97706',
                    });

                    confirmed = result.isConfirmed;
                } else {
                    confirmed = window.confirm(`${title} ${oaNumber}`);
                }

                if (confirmed) {
                    form.submit();
                }
            });
        });

        const makeYearOptions = (selectedYear = '') => {
            let output = '<option value="">Pilih Tahun</option>';
            for (let i = 0; i < 4; i += 1) {
                const year = currentYear + i;
                output += `<option value="${year}" ${String(selectedYear) === String(year) ? 'selected' : ''}>${year}</option>`;
            }
            return output;
        };

        const createTargetRow = ({ year = '', value = '', mode = 'create' } = {}) => {
            const row = document.createElement('div');
            row.className = 'grid gap-3 rounded-2xl border border-emerald-200 bg-white p-4 md:grid-cols-[180px_1fr_auto] md:items-end';
            row.innerHTML = `
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Tahun</label>
                    <select name="target_years[]" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                        ${makeYearOptions(year)}
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Nilai Target</label>
                    <input type="number" step="0.01" min="0" name="target_values[]" value="${value ?? ''}" placeholder="0" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                </div>
                <button type="button" class="inline-flex items-center justify-center rounded-xl bg-rose-50 px-3 py-3 text-sm font-semibold text-rose-600 transition hover:bg-rose-100">
                    Hapus
                </button>
            `;

            row.querySelector('button').addEventListener('click', () => row.remove());
            row.dataset.mode = mode;
            return row;
        };

        const parseSectionOptions = (unitElement) => {
            const selectedOption = unitElement?.selectedOptions?.[0];
            const raw = selectedOption?.dataset?.sections;

            if (!raw) return [];

            try {
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed.filter(Boolean) : [];
            } catch (error) {
                console.error('Gagal parse seksi unit kerja', error);
                return [];
            }
        };

        const syncJenisKontrakOptions = (unitElement, jenisElement, selectedValue = '') => {
            if (!unitElement || !jenisElement) return;

            const sections = parseSectionOptions(unitElement);
            const normalizedSelected = selectedValue || '';

            jenisElement.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = sections.length > 0 ? 'Pilih seksi unit kerja' : 'Unit kerja ini belum punya seksi';
            placeholder.selected = normalizedSelected === '';
            jenisElement.appendChild(placeholder);

            sections.forEach((name) => {
                const option = document.createElement('option');
                option.value = name;
                option.textContent = name;
                if (normalizedSelected === name) option.selected = true;
                jenisElement.appendChild(option);
            });

            if (normalizedSelected && !sections.includes(normalizedSelected)) {
                const legacyOption = document.createElement('option');
                legacyOption.value = normalizedSelected;
                legacyOption.textContent = normalizedSelected;
                legacyOption.selected = true;
                jenisElement.appendChild(legacyOption);
            }
        };

        const createUnitWorkId = document.getElementById('createUnitWorkId');
        const createJenisKontrak = document.getElementById('jenisKontrak');
        createUnitWorkId?.addEventListener('change', () => syncJenisKontrakOptions(createUnitWorkId, createJenisKontrak));
        syncJenisKontrakOptions(createUnitWorkId, createJenisKontrak, oldCreateJenisKontrak);

        const createTargetsContainer = document.getElementById('targetsContainer');
        document.getElementById('addTargetRow')?.addEventListener('click', () => {
            createTargetsContainer?.appendChild(createTargetRow({ mode: 'create' }));
        });

        if (createTargetsContainer) {
            if (oldCreateYears.length || oldCreateValues.length) {
                const maxRows = Math.max(oldCreateYears.length, oldCreateValues.length);
                for (let i = 0; i < maxRows; i += 1) {
                    createTargetsContainer.appendChild(createTargetRow({
                        year: oldCreateYears[i] ?? '',
                        value: oldCreateValues[i] ?? '',
                        mode: 'create',
                    }));
                }
            } else {
                createTargetsContainer.appendChild(createTargetRow({ mode: 'create' }));
            }
        }

        const editModal = document.getElementById('oaEditModal');
        const editForm = document.getElementById('editOutlineAgreementForm');
        const editAgreementId = document.getElementById('editAgreementId');
        const editUnitWorkId = document.getElementById('editUnitWorkId');
        const editNomorOa = document.getElementById('editNomorOa');
        const editJenisKontrak = document.getElementById('editJenisKontrak');
        const editNamaKontrak = document.getElementById('editNamaKontrak');
        const editCurrentTotalNilai = document.getElementById('editCurrentTotalNilai');
        const editCurrentPeriodEnd = document.getElementById('editCurrentPeriodEnd');
        const editCurrentPeriodStartHidden = document.getElementById('editCurrentPeriodStartHidden');
        const editInitialValueHidden = document.getElementById('editInitialValueHidden');
        const editInitialValuePreview = document.getElementById('editInitialValuePreview');
        const editPeriodStartPreview = document.getElementById('editPeriodStartPreview');
        const editTargetsContainer = document.getElementById('editTargetsContainer');
        const editKeteranganPerubahan = document.getElementById('editKeteranganPerubahan');
        const closeEditModalButton = document.getElementById('closeEditModal');
        const cancelEditModalButton = document.getElementById('cancelEditModal');

        const openEditModal = (payload) => {
            if (!editModal || !editForm) return;

            editAgreementId.value = payload.id ?? '';
            editForm.action = `{{ url('admin/outline-agreements') }}/${payload.id}`;
            editUnitWorkId.value = payload.unitWorkId ?? '';
            editNomorOa.value = payload.nomor ?? '';
            syncJenisKontrakOptions(editUnitWorkId, editJenisKontrak, payload.jenis ?? '');
            editNamaKontrak.value = payload.nama ?? '';
            editCurrentTotalNilai.value = payload.total ?? '';
            editCurrentPeriodEnd.value = payload.periodEnd ?? '';
            editCurrentPeriodEnd.min = payload.periodStart ?? '';
            if (editCurrentPeriodStartHidden) editCurrentPeriodStartHidden.value = payload.periodStart ?? '';
            if (editInitialValueHidden) editInitialValueHidden.value = payload.initialValue ?? '';
            editInitialValuePreview.textContent = formatCurrency(payload.initialValue ?? 0);
            editPeriodStartPreview.textContent = payload.periodStartLabel ?? (payload.periodStart || '-');
            editKeteranganPerubahan.value = payload.note ?? '';

            if (editTargetsContainer) {
                editTargetsContainer.innerHTML = '';
                const targets = Array.isArray(payload.targets) ? payload.targets : [];
                if (targets.length) {
                    targets.forEach((target) => {
                        editTargetsContainer.appendChild(createTargetRow({
                            year: target.year ?? '',
                            value: target.value ?? '',
                            mode: 'edit',
                        }));
                    });
                } else {
                    editTargetsContainer.appendChild(createTargetRow({ mode: 'edit' }));
                }
            }

            editModal.classList.remove('hidden');
            editModal.classList.add('flex');
        };

        const closeEditModal = () => {
            if (!editModal) return;
            editModal.classList.add('hidden');
            editModal.classList.remove('flex');
        };

        editUnitWorkId?.addEventListener('change', () => syncJenisKontrakOptions(editUnitWorkId, editJenisKontrak));
        document.getElementById('editAddTargetRow')?.addEventListener('click', () => {
            editTargetsContainer?.appendChild(createTargetRow({ mode: 'edit' }));
        });

        document.querySelectorAll('[data-edit-trigger]').forEach((button) => {
            button.addEventListener('click', () => {
                openEditModal({
                    id: button.dataset.id,
                    unitWorkId: button.dataset.unitWorkId,
                    nomor: button.dataset.nomor,
                    jenis: button.dataset.jenis,
                    nama: button.dataset.nama,
                    total: button.dataset.total,
                    periodStart: button.dataset.periodStart,
                    periodEnd: button.dataset.periodEnd,
                    periodStartLabel: button.dataset.periodStart,
                    initialValue: button.dataset.initialValue,
                    targets: JSON.parse(button.dataset.targets || '[]'),
                });
            });
        });

        closeEditModalButton?.addEventListener('click', closeEditModal);
        cancelEditModalButton?.addEventListener('click', closeEditModal);
        editModal?.addEventListener('click', (event) => {
            if (event.target === editModal) closeEditModal();
        });

        if (oldEditMethod === 'PUT') {
            openEditModal({
                id: oldEditId,
                unitWorkId: @json((string) old('unit_work_id', '')),
                nomor: @json((string) old('nomor_oa', '')),
                jenis: @json((string) old('jenis_kontrak', '')),
                nama: @json((string) old('nama_kontrak', '')),
                total: @json((string) old('current_total_nilai', '')),
                periodStart: @json((string) old('current_period_start', '')),
                periodEnd: @json((string) old('current_period_end', '')),
                periodStartLabel: @json((string) old('current_period_start', '')),
                initialValue: @json((string) old('initial_value_preview', '0')),
                note: @json((string) old('keterangan_perubahan', '')),
                targets: oldEditYears.map((year, index) => ({
                    year: year ?? '',
                    value: oldEditValues[index] ?? '',
                })),
            });
        }

        const monthlyModal = document.getElementById('oaMonthlyRealizationModal');
        const monthlyForm = document.getElementById('monthlyRealizationForm');
        const monthlyOaId = document.getElementById('monthlyRealizationOaId');
        const monthlyAgreementInfo = document.getElementById('monthlyRealizationAgreementInfo');
        const monthlyYear = document.getElementById('monthlyRealizationYear');
        const monthlyMonth = document.getElementById('monthlyRealizationMonth');
        const monthlyPrPo = document.getElementById('monthlyRealizationPrPo');
        const monthlyUrgent = document.getElementById('monthlyRealizationUrgent');
        const monthlyRows = document.getElementById('monthlyRealizationRows');
        const monthlyEmpty = document.getElementById('monthlyRealizationEmpty');
        const monthlyNames = [
            '', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
        ];
        const monthlyCsrfToken = @json(csrf_token());
        const oldMonthlyOaId = @json((string) old('_monthly_oa_id', ''));

        const formatMonthlyAmount = (value) => new Intl.NumberFormat('id-ID').format(Number(value || 0));

        const fillMonthlyForm = (realization = null) => {
            if (!monthlyYear || !monthlyMonth || !monthlyPrPo || !monthlyUrgent) return;

            if (realization) {
                monthlyYear.value = realization.year ?? '';
                monthlyMonth.value = realization.month ?? '';
                monthlyPrPo.value = formatMonthlyAmount(realization.pr_po_amount);
                monthlyUrgent.value = formatMonthlyAmount(realization.urgent_amount);
                monthlyYear.focus();
                return;
            }

            monthlyYear.value = '';
            monthlyMonth.value = '';
            monthlyPrPo.value = '0';
            monthlyUrgent.value = '0';
        };

        const bindMonthlyAmountFormatter = (input) => {
            input?.addEventListener('input', () => {
                const digits = input.value.replace(/\D/g, '');
                input.value = digits === '' ? '' : formatMonthlyAmount(digits);
            });
        };

        bindMonthlyAmountFormatter(monthlyPrPo);
        bindMonthlyAmountFormatter(monthlyUrgent);

        const renderMonthlyRows = (realizations) => {
            if (!monthlyRows || !monthlyEmpty) return;

            monthlyRows.innerHTML = '';
            monthlyEmpty.classList.toggle('hidden', realizations.length > 0);

            realizations.forEach((realization) => {
                const row = document.createElement('tr');
                row.className = 'text-slate-700';

                const periodCell = document.createElement('td');
                periodCell.className = 'whitespace-nowrap px-4 py-3 font-semibold';
                periodCell.textContent = `${monthlyNames[Number(realization.month)] || realization.month} ${realization.year}`;

                const prPoCell = document.createElement('td');
                prPoCell.className = 'whitespace-nowrap px-4 py-3 text-right';
                prPoCell.textContent = `Rp${formatMonthlyAmount(realization.pr_po_amount)}`;

                const urgentCell = document.createElement('td');
                urgentCell.className = 'whitespace-nowrap px-4 py-3 text-right';
                urgentCell.textContent = `Rp${formatMonthlyAmount(realization.urgent_amount)}`;

                const actionCell = document.createElement('td');
                actionCell.className = 'whitespace-nowrap px-4 py-3 text-right';

                const actionWrap = document.createElement('div');
                actionWrap.className = 'inline-flex items-center gap-2';

                const editButton = document.createElement('button');
                editButton.type = 'button';
                editButton.className = 'font-semibold text-sky-600 hover:text-sky-700';
                editButton.textContent = 'Edit';
                editButton.addEventListener('click', () => fillMonthlyForm(realization));

                const deleteForm = document.createElement('form');
                deleteForm.method = 'POST';
                deleteForm.action = realization.destroy_url;
                deleteForm.dataset.deleteMonthlyRealizationForm = '';
                deleteForm.innerHTML = `<input type="hidden" name="_token" value="${monthlyCsrfToken}"><input type="hidden" name="_method" value="DELETE">`;

                const deleteButton = document.createElement('button');
                deleteButton.type = 'submit';
                deleteButton.className = 'font-semibold text-rose-600 hover:text-rose-700';
                deleteButton.textContent = 'Hapus';
                deleteForm.appendChild(deleteButton);
                deleteForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    let confirmed = false;

                    if (window.Swal) {
                        const result = await window.Swal.fire({
                            icon: 'warning',
                            title: 'Hapus realisasi biaya?',
                            text: `Realisasi periode ${periodCell.textContent} akan dihapus.`,
                            showCancelButton: true,
                            confirmButtonText: 'Ya, hapus',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#e11d48',
                        });
                        confirmed = result.isConfirmed;
                    } else {
                        confirmed = window.confirm(`Hapus realisasi periode ${periodCell.textContent}?`);
                    }

                    if (confirmed) deleteForm.submit();
                });

                actionWrap.append(editButton, deleteForm);
                actionCell.appendChild(actionWrap);
                row.append(periodCell, prPoCell, urgentCell, actionCell);
                monthlyRows.appendChild(row);
            });
        };

        const openMonthlyModal = (button, preserveOldInput = false) => {
            if (!monthlyModal || !monthlyForm || !monthlyOaId || !monthlyAgreementInfo) return;

            const realizations = JSON.parse(button.dataset.realizations || '[]');
            monthlyForm.action = button.dataset.storeUrl || '';
            monthlyOaId.value = button.dataset.id || '';
            monthlyAgreementInfo.textContent = `${button.dataset.number || '-'} · ${button.dataset.name || '-'}`;
            renderMonthlyRows(realizations);

            if (!preserveOldInput) {
                fillMonthlyForm();
                const start = button.dataset.periodStart ? new Date(`${button.dataset.periodStart}T00:00:00`) : null;
                const end = button.dataset.periodEnd ? new Date(`${button.dataset.periodEnd}T00:00:00`) : null;
                const today = new Date();
                const initialDate = start && today < start ? start : (end && today > end ? end : today);
                monthlyYear.value = initialDate.getFullYear();
                monthlyMonth.value = initialDate.getMonth() + 1;
            }

            monthlyModal.classList.remove('hidden');
            monthlyModal.classList.add('flex');
        };

        const closeMonthlyModal = () => {
            if (!monthlyModal) return;
            monthlyModal.classList.add('hidden');
            monthlyModal.classList.remove('flex');
        };

        const monthlyTriggers = document.querySelectorAll('[data-monthly-realization-trigger]');
        monthlyTriggers.forEach((button) => {
            button.addEventListener('click', () => openMonthlyModal(button));
        });

        document.getElementById('closeMonthlyRealizationModal')?.addEventListener('click', closeMonthlyModal);
        monthlyModal?.addEventListener('click', (event) => {
            if (event.target === monthlyModal) closeMonthlyModal();
        });

        if (oldMonthlyOaId) {
            const trigger = Array.from(monthlyTriggers).find((button) => button.dataset.id === oldMonthlyOaId);
            if (trigger) openMonthlyModal(trigger, true);
        }
    });
</script>
