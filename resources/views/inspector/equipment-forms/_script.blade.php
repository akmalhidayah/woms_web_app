<script>
window.equipmentInspectionPage = function (config) {
    return {
        answers: config.answers, inspectionDate: config.date,
        savedAnswers: config.savedAnswers, savedDate: config.savedDate, today: config.today,
        persisted: config.persisted, readOnly: config.readOnly, conflict: config.conflict,
        deleteAttachments: [], filesSelected: {}, saving: false, signing: false,
        signOpen: false, signatureData: '', hasInk: false, pointerId: null,
        get dirty() {
            return JSON.stringify(this.answers) !== JSON.stringify(this.savedAnswers)
                || this.inspectionDate !== this.savedDate || this.deleteAttachments.length > 0
                || Object.values(this.filesSelected).some(count => count > 0);
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
            return this.persisted && !this.readOnly && !this.conflict && !this.dirty && this.complete
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
        reset() {
            if (!window.confirm('Kembalikan isian ke kondisi terakhir yang tersimpan? Foto tersimpan tidak dihapus.')) return;
            this.answers = JSON.parse(JSON.stringify(this.savedAnswers));
            this.inspectionDate = this.savedDate;
            Object.keys(this.filesSelected).forEach(id => this.clearPhotos(id));
            this.deleteAttachments = [];
            this.signatureData = '';
            this.signOpen = false;
        },
        submitDraft(event) {
            if (this.saving || this.readOnly || this.conflict) { event.preventDefault(); return; }
            if (this.deleteAttachments.length && !window.confirm('Hapus foto yang dipilih saat menyimpan draft ini?')) {
                event.preventDefault(); return;
            }
            this.saving = true;
        },
        openSignature() {
            if (!this.canSign) return;
            this.signOpen = true;
            this.$nextTick(() => this.clearSignature());
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
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
            this.hasInk = false; this.signatureData = ''; this.pointerId = null;
        },
        previewSignature() {
            if (this.hasInk) this.signatureData = this.$refs.signatureCanvas.toDataURL('image/png');
        },
        submitSignature(event) {
            if (!this.canSign || !this.signatureData) { event.preventDefault(); return; }
            this.signing = true;
        },
    };
};
</script>
