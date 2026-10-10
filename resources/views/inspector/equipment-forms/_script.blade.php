<script>
window.equipmentInspectionPage = function (config) {
    return {
        answers: config.answers, inspectionDate: config.date,
        savedAnswers: config.savedAnswers, savedDate: config.savedDate, today: config.today,
        persisted: config.persisted, readOnly: config.readOnly, conflict: config.conflict,
        recentSignatureDataUrl: config.recentSignatureDataUrl,
        deleteAttachments: [], filesSelected: {}, deletionConfirmed: false, saving: false, signing: false,
        signatureModalOpen: false, signatureData: '', signatureConfirmed: false, hasInk: false, pointerId: null,
        get dirty() {
            return JSON.stringify(this.answers) !== JSON.stringify(this.savedAnswers)
                || this.inspectionDate !== this.savedDate || this.deleteAttachments.length > 0
                || Object.values(this.filesSelected).some(count => count > 0) || this.hasInk;
        },
        get summary() {
            const values = Object.values(this.answers).map(answer => answer.rating);
            return { total: values.length, filled: values.filter(Boolean).length,
                A: values.filter(value => value === 'A').length, B: values.filter(value => value === 'B').length,
                C: values.filter(value => value === 'C').length, empty: values.filter(value => !value).length };
        },
        get complete() {
            return Object.values(this.answers).every(answer => ['A', 'B', 'C'].includes(answer.rating)
                && (answer.rating === 'A' || answer.remark.trim().length > 0));
        },
        get canSign() {
            return !this.readOnly && !this.conflict && this.complete
                && this.inspectionDate && this.inspectionDate <= this.today && !this.saving && !this.signing;
        },
        get formattedDate() {
            if (!this.inspectionDate) return 'Belum dipilih';
            const date = new Date(this.inspectionDate + 'T00:00:00');
            return Number.isNaN(date.getTime()) ? 'Tanggal tidak valid' : date.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        },
        hasFinding(id) { return ['B', 'C'].includes(this.answers[id].rating); },
        selectPhotos(id, event) { this.filesSelected[id] = event.target.files.length; },
        clearPhotos(id) {
            const input = this.$root.querySelector('[data-upload="' + id + '"]');
            if (input) input.value = '';
            this.filesSelected[id] = 0;
        },
        async submitInspection(event) {
            if (this.saving || this.readOnly || this.conflict) { event.preventDefault(); return; }
            if (this.deleteAttachments.length && !this.deletionConfirmed) {
                event.preventDefault();
                const submitter = event.submitter;
                const confirmed = await window.inspectionSweetAlert.confirm({
                    title: 'Hapus foto yang dipilih?',
                    text: 'Foto akan dihapus saat laporan disimpan. Foto historis pada versi yang sudah ditandatangani tetap dipertahankan.',
                    confirmButtonText: 'Ya, hapus dan simpan',
                    confirmButtonColor: '#dc2626',
                });
                if (!confirmed) return;
                this.deletionConfirmed = true;
                event.target.requestSubmit(submitter ?? undefined);
                return;
            }
            if (event.submitter?.dataset.action === 'sign') {
                if (!this.canSign || !this.signatureData || !this.signatureConfirmed) { event.preventDefault(); return; }
                this.signing = true;
                return;
            }
            this.saving = true;
        },
        openSignature() {
            if (!this.canSign) return;
            this.signatureModalOpen = true;
            this.$nextTick(() => {
                if (!this.hasInk) this.paintSignatureBackground();
            });
        },
        useRecentSignature() {
            if (!this.canSign || !this.recentSignatureDataUrl || this.pointerId !== null) return;
            const image = new Image();
            image.onload = () => {
                if (!this.canSign || !this.signatureModalOpen || this.pointerId !== null) return;
                const canvas = this.$refs.signatureCanvas;
                if (!canvas) return;
                const scale = Math.min(canvas.width * 0.8 / image.naturalWidth, canvas.height * 0.75 / image.naturalHeight);
                const width = image.naturalWidth * scale;
                const height = image.naturalHeight * scale;
                this.paintSignatureBackground();
                canvas.getContext('2d').drawImage(image, (canvas.width - width) / 2, (canvas.height - height) / 2, width, height);
                this.hasInk = true;
                this.signatureData = '';
                this.signatureConfirmed = false;
            };
            image.onerror = () => window.inspectionSweetAlert.error('TTD terakhir tidak dapat dimuat. Silakan tanda tangan ulang.');
            image.src = this.recentSignatureDataUrl;
        },
        paintSignatureBackground() {
            const canvas = this.$refs.signatureCanvas;
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        },
        point(event) {
            const canvas = this.$refs.signatureCanvas;
            const rect = canvas.getBoundingClientRect();
            return { x: (event.clientX - rect.left) * canvas.width / rect.width,
                y: (event.clientY - rect.top) * canvas.height / rect.height };
        },
        startStroke(event) {
            if (!event.isPrimary || this.pointerId !== null || !this.canSign || (event.pointerType === 'mouse' && event.button !== 0)) return;
            this.pointerId = event.pointerId;
            this.signatureData = '';
            this.signatureConfirmed = false;
            const canvas = this.$refs.signatureCanvas;
            canvas.setPointerCapture(event.pointerId);
            const ctx = canvas.getContext('2d');
            const point = this.point(event);
            ctx.beginPath(); ctx.moveTo(point.x, point.y);
        },
        drawStroke(event) {
            if (this.pointerId !== event.pointerId) return;
            const ctx = this.$refs.signatureCanvas.getContext('2d');
            const point = this.point(event);
            ctx.lineWidth = 6; ctx.lineCap = ctx.lineJoin = 'round'; ctx.strokeStyle = '#111827';
            ctx.lineTo(point.x, point.y); ctx.stroke(); this.hasInk = true;
        },
        endStroke(event) {
            if (this.pointerId !== event.pointerId) return;
            const canvas = this.$refs.signatureCanvas;
            if (canvas.hasPointerCapture(event.pointerId)) canvas.releasePointerCapture(event.pointerId);
            this.pointerId = null;
        },
        clearSignature() {
            const canvas = this.$refs.signatureCanvas;
            if (!canvas) return;
            this.paintSignatureBackground();
            this.hasInk = false; this.signatureData = ''; this.signatureConfirmed = false; this.pointerId = null;
        },
        acceptSignature() {
            if (!this.hasInk) return;
            this.signatureData = this.$refs.signatureCanvas.toDataURL('image/png');
            this.signatureModalOpen = false;
        },
    };
};
</script>
