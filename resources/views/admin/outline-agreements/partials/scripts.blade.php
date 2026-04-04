<script>
    document.addEventListener('DOMContentLoaded', () => {
        const contractOptions = @json($contractOptions);
        const oldCreateNamaKontrak = @json((string) old('nama_kontrak', ''));
        const oldCreateYears = @json(old('target_years', []));
        const oldCreateValues = @json(old('target_values', []));
        const oldEditMethod = @json((string) old('_method', ''));
        const oldEditId = @json((string) old('_edit_id', ''));
        const oldEditNamaKontrak = @json((string) old('nama_kontrak', ''));
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

        const syncContractNames = (jenisElement, namaElement, selectedName = '') => {
            if (!jenisElement || !namaElement) return;

            const optionGroup = contractOptions.find((item) => item.label === jenisElement.value);
            namaElement.innerHTML = '<option value="">Pilih Nama Kontrak</option>';

            if (!optionGroup) return;

            optionGroup.names.forEach((name) => {
                const option = document.createElement('option');
                option.value = name;
                option.textContent = name;
                if (selectedName && selectedName === name) option.selected = true;
                namaElement.appendChild(option);
            });
        };

        const createJenisKontrak = document.getElementById('jenisKontrak');
        const createNamaKontrak = document.getElementById('namaKontrak');
        createJenisKontrak?.addEventListener('change', () => syncContractNames(createJenisKontrak, createNamaKontrak));
        syncContractNames(createJenisKontrak, createNamaKontrak, oldCreateNamaKontrak);

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
            editJenisKontrak.value = payload.jenis ?? '';
            syncContractNames(editJenisKontrak, editNamaKontrak, payload.nama ?? '');
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

        editJenisKontrak?.addEventListener('change', () => syncContractNames(editJenisKontrak, editNamaKontrak));
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
                nama: oldEditNamaKontrak,
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
    });
</script>
