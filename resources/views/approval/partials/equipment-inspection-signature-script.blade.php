{{-- Same pointer/upload/recent-signature interaction as the existing QC approval page. --}}
<script>
        (() => {
            const canvas = document.getElementById('signatureCanvas');
            const form = document.getElementById('signatureForm');
            const signatureFile = document.getElementById('signatureFile');
            const clearButton = document.getElementById('clearSignature');
            const useRecentButton = document.getElementById('useRecentSignature');
            const signatureVisuals = window.createSignaturePadVisuals?.();
            signatureVisuals?.idle();

            if (!canvas || !form || !signatureFile) {
                return;
            }

            const ctx = canvas.getContext('2d');
            let drawing = false;
            let touched = false;
            let preparedSubmit = false;
            let lastPoint = null;
            let strokePointCount = 0;
            let strokeDistance = 0;
            let activePointerId = null;
            let resizeTimer = null;
            let resizePending = false;

            const minimumStrokePoints = 8;
            const minimumStrokeDistance = 40;

            const resizeCanvas = () => {
                const rect = canvas.getBoundingClientRect();
                // Keep the QC interaction while respecting private inspection PNG dimensions.
                const ratio = Math.min(window.devicePixelRatio || 1, 2000 / Math.max(1, rect.width), 1000 / Math.max(1, rect.height));
                const targetWidth = Math.max(1, Math.floor(rect.width * ratio));
                const targetHeight = Math.max(1, Math.floor(rect.height * ratio));

                if (canvas.width === targetWidth && canvas.height === targetHeight) {
                    return;
                }

                const snapshot = touched ? document.createElement('canvas') : null;

                if (snapshot) {
                    snapshot.width = canvas.width;
                    snapshot.height = canvas.height;
                    snapshot.getContext('2d')?.drawImage(canvas, 0, 0);
                }

                canvas.width = targetWidth;
                canvas.height = targetHeight;
                ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                ctx.lineWidth = 2.4;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.strokeStyle = '#0f172a';

                if (snapshot) {
                    ctx.drawImage(
                        snapshot,
                        0,
                        0,
                        snapshot.width,
                        snapshot.height,
                        0,
                        0,
                        rect.width,
                        rect.height,
                    );
                }
            };

            const scheduleCanvasResize = () => {
                resizePending = true;
                window.clearTimeout(resizeTimer);
                resizeTimer = window.setTimeout(() => {
                    if (drawing) {
                        return;
                    }

                    resizePending = false;
                    resizeCanvas();
                }, 200);
            };

            const applyCanvasStyle = () => {
                ctx.lineWidth = 2.4;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.strokeStyle = '#0f172a';
            };

            const clearCanvas = () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                applyCanvasStyle();
            };

            const drawImageToCanvas = (image) => {
                const rect = canvas.getBoundingClientRect();
                const maxWidth = rect.width * 0.8;
                const maxHeight = rect.height * 0.75;
                const scale = Math.min(maxWidth / image.naturalWidth, maxHeight / image.naturalHeight);
                const width = image.naturalWidth * scale;
                const height = image.naturalHeight * scale;
                const x = (rect.width - width) / 2;
                const y = (rect.height - height) / 2;

                clearCanvas();
                ctx.drawImage(image, x, y, width, height);
            };

            const loadRecentSignature = () => {
                const src = useRecentButton?.dataset.signatureSrc;

                if (!src) {
                    return;
                }

                const image = new Image();
                image.onload = () => {
                    drawImageToCanvas(image);
                    touched = true;
                    lastPoint = null;
                    strokePointCount = minimumStrokePoints;
                    strokeDistance = minimumStrokeDistance;
                    signatureFile.value = '';
                    signatureVisuals?.completed();
                };
                image.onerror = () => {
                    alert('TTD terakhir tidak dapat dimuat. Silakan tanda tangan ulang.');
                };
                image.src = src;
            };

            const point = (event) => {
                const rect = canvas.getBoundingClientRect();

                return {
                    x: event.clientX - rect.left,
                    y: event.clientY - rect.top,
                };
            };

            const start = (event) => {
                if (activePointerId !== null || (event.pointerType === 'mouse' && event.button !== 0)) {
                    return;
                }

                event.preventDefault();
                drawing = true;
                activePointerId = event.pointerId;
                canvas.setPointerCapture?.(event.pointerId);
                signatureVisuals?.active();
                const p = point(event);
                lastPoint = p;
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
            };

            const move = (event) => {
                if (!drawing || event.pointerId !== activePointerId) {
                    return;
                }

                event.preventDefault();
                const coalescedEvents = event.getCoalescedEvents?.();
                const pointerEvents = coalescedEvents?.length ? coalescedEvents : [event];

                pointerEvents.forEach((pointerEvent) => {
                    const p = point(pointerEvent);

                    if (lastPoint) {
                        strokeDistance += Math.hypot(p.x - lastPoint.x, p.y - lastPoint.y);
                    }

                    strokePointCount++;
                    lastPoint = p;
                    ctx.lineTo(p.x, p.y);
                    ctx.stroke();
                    touched = true;
                });
            };

            const stop = (event) => {
                if (!drawing || event.pointerId !== activePointerId) {
                    return;
                }

                drawing = false;
                activePointerId = null;
                lastPoint = null;

                if (touched) {
                    signatureVisuals?.completed();
                }

                if (resizePending) {
                    scheduleCanvasResize();
                }
            };

            canvas.addEventListener('pointerdown', start);
            canvas.addEventListener('pointermove', move);
            canvas.addEventListener('pointerup', stop);
            canvas.addEventListener('pointercancel', stop);
            canvas.addEventListener('lostpointercapture', stop);
            window.addEventListener('resize', scheduleCanvasResize);
            window.addEventListener('orientationchange', scheduleCanvasResize);

            clearButton?.addEventListener('click', () => {
                clearCanvas();
                touched = false;
                lastPoint = null;
                strokePointCount = 0;
                strokeDistance = 0;
                signatureFile.value = '';
                signatureVisuals?.idle();
            });

            useRecentButton?.addEventListener('click', loadRecentSignature);

            const hasEnoughSignatureStroke = () => {
                return touched
                    && strokePointCount >= minimumStrokePoints
                    && strokeDistance >= minimumStrokeDistance;
            };

            form.addEventListener('submit', async (event) => {
                if (preparedSubmit) {
                    return;
                }

                if (!hasEnoughSignatureStroke()) {
                    event.preventDefault();
                    const message = touched
                        ? 'Tanda tangan terlalu sedikit. Silakan tanda tangani dengan coretan yang jelas.'
                        : 'Silakan tanda tangan terlebih dahulu.';
                    signatureVisuals?.error(message);
                    alert(message);
                    return;
                }

                event.preventDefault();
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));

                if (!blob) {
                    alert('Tanda tangan belum terbaca. Silakan tanda tangani ulang.');
                    return;
                }

                const transfer = new DataTransfer();
                transfer.items.add(new File([blob], 'signature.png', { type: 'image/png' }));
                signatureFile.files = transfer.files;
                preparedSubmit = true;
                form.submit();
            });

            resizeCanvas();
        })();
    </script>
