@php
    $viewerTitle = $title ?? 'Preview Dokumen';
    $viewerUrl = $url ?? null;
@endphp

<div
    id="approvalPdfPreview"
    class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50"
    data-initial-title="{{ $viewerTitle }}"
    data-initial-url="{{ $viewerUrl }}"
>
    <div
        id="approvalPdfToolbar"
        class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-700 sm:px-4"
    >
        <span id="activePreviewTitle" class="min-w-0 flex-1 truncate text-sm font-black text-slate-900">
            {{ $viewerTitle }}
        </span>
        <button
            type="button"
            id="approvalPdfPrevPage"
            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 transition hover:border-red-200 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
        >
            <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
            Prev
        </button>
        <span id="approvalPdfPageIndicator" class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-slate-600">
            Halaman 0 / 0
        </span>
        <button
            type="button"
            id="approvalPdfNextPage"
            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 transition hover:border-red-200 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
        >
            Next
            <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
        </button>
        <button
            type="button"
            id="approvalPdfZoomOut"
            class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 transition hover:border-red-200 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
        >
            Zoom -
        </button>
        <span id="approvalPdfZoomLabel" class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-slate-600">100%</span>
        <button
            type="button"
            id="approvalPdfZoomIn"
            class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 transition hover:border-red-200 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
        >
            Zoom +
        </button>
        <button
            type="button"
            id="approvalPdfFitWidth"
            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 transition hover:border-red-200 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
        >
            <i data-lucide="maximize" class="h-3.5 w-3.5"></i>
            Fit Width
        </button>
        <a
            id="activePreviewOpen"
            href="{{ $viewerUrl ?: '#' }}"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-red-200 hover:bg-white hover:text-red-800"
        >
            <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
            Buka Dokumen
        </a>
        <a
            id="activePreviewDownload"
            href="{{ $viewerUrl ?: '#' }}"
            download
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-red-200 hover:bg-white hover:text-red-800"
        >
            <i data-lucide="download" class="h-3.5 w-3.5"></i>
            Download
        </a>
    </div>

    <div class="relative min-h-[32rem] overflow-hidden bg-slate-100 sm:min-h-[42rem] xl:min-h-[54rem]">
        <div
            id="approvalPdfEmptyState"
            class="hidden h-[32rem] items-center justify-center px-6 text-center sm:h-[42rem] xl:h-[54rem]"
        >
            <div>
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-400">
                    <i data-lucide="files" class="h-5 w-5"></i>
                </div>
                <div class="mt-4 text-base font-semibold text-slate-700">Dokumen belum tersedia</div>
                <p class="mt-2 text-sm leading-6 text-slate-500">Pilih dokumen lain yang sudah tersedia untuk dipreview.</p>
            </div>
        </div>

        <div
            id="approvalPdfLoadingState"
            class="hidden h-[32rem] items-center justify-center px-6 text-center text-sm font-semibold text-slate-600 sm:h-[42rem] xl:h-[54rem]"
        >
            Memuat dokumen...
        </div>

        <div
            id="approvalPdfErrorState"
            class="hidden border-b border-slate-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800"
        >
            Preview PDF tidak dapat dimuat. Silakan gunakan tombol Buka Dokumen.
        </div>

        <div id="approvalPdfCanvasWrapper" class="hidden h-[32rem] max-w-full overflow-auto p-3 sm:h-[42rem] xl:h-[54rem] xl:p-5">
            <canvas id="approvalPdfCanvas" class="mx-auto block max-w-full rounded-sm bg-white"></canvas>
        </div>

        <iframe
            id="activePreviewFrame"
            src="{{ $viewerUrl ?: 'about:blank' }}"
            class="hidden h-[32rem] w-full bg-white sm:h-[42rem] xl:h-[54rem]"
            title="{{ $viewerTitle }}"
        ></iframe>
    </div>
</div>

@once
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.getElementById('approvalPdfPreview');

            if (! root) {
                return;
            }

            const pdfjsLib = window.pdfjsLib;
            const toolbar = document.getElementById('approvalPdfToolbar');
            const title = document.getElementById('activePreviewTitle');
            const openLink = document.getElementById('activePreviewOpen');
            const downloadLink = document.getElementById('activePreviewDownload');
            const prevButton = document.getElementById('approvalPdfPrevPage');
            const nextButton = document.getElementById('approvalPdfNextPage');
            const zoomOutButton = document.getElementById('approvalPdfZoomOut');
            const zoomInButton = document.getElementById('approvalPdfZoomIn');
            const fitWidthButton = document.getElementById('approvalPdfFitWidth');
            const pageIndicator = document.getElementById('approvalPdfPageIndicator');
            const zoomLabel = document.getElementById('approvalPdfZoomLabel');
            const emptyState = document.getElementById('approvalPdfEmptyState');
            const loadingState = document.getElementById('approvalPdfLoadingState');
            const errorState = document.getElementById('approvalPdfErrorState');
            const canvasWrapper = document.getElementById('approvalPdfCanvasWrapper');
            const canvas = document.getElementById('approvalPdfCanvas');
            const fallbackFrame = document.getElementById('activePreviewFrame');

            if (! canvas || ! fallbackFrame) {
                return;
            }

            if (pdfjsLib) {
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            }

            const canvasContext = canvas.getContext('2d');
            const controlButtons = [prevButton, nextButton, zoomOutButton, zoomInButton, fitWidthButton];
            let activeDocumentUrl = '';
            let activeRenderKey = 0;
            let loadingTask = null;
            let renderTask = null;
            let pdfDocument = null;
            let currentPage = 1;
            let currentScale = 1;

            const showElement = (element, displayClass = 'block') => {
                if (! element) {
                    return;
                }

                element.classList.remove('hidden', 'flex', 'block');
                element.classList.add(displayClass);
            };

            const hideElement = (element) => {
                if (! element) {
                    return;
                }

                element.classList.add('hidden');
                element.classList.remove('flex', 'block');
            };

            const setControlsDisabled = (disabled) => {
                controlButtons.forEach((button) => {
                    if (button) {
                        button.disabled = disabled;
                    }
                });
            };

            const updateControls = () => {
                const totalPages = pdfDocument ? pdfDocument.numPages : 0;

                if (pageIndicator) {
                    pageIndicator.textContent = `Halaman ${totalPages ? currentPage : 0} / ${totalPages}`;
                }

                if (zoomLabel) {
                    zoomLabel.textContent = `${Math.round(currentScale * 100)}%`;
                }

                if (prevButton) {
                    prevButton.disabled = ! pdfDocument || currentPage <= 1;
                }

                if (nextButton) {
                    nextButton.disabled = ! pdfDocument || currentPage >= totalPages;
                }

                [zoomOutButton, zoomInButton, fitWidthButton].forEach((button) => {
                    if (button) {
                        button.disabled = ! pdfDocument;
                    }
                });
            };

            const resetCanvas = () => {
                if (! canvasContext) {
                    return;
                }

                canvasContext.clearRect(0, 0, canvas.width || 1, canvas.height || 1);
                canvas.width = 0;
                canvas.height = 0;
            };

            const cancelActiveRender = () => {
                activeRenderKey += 1;

                if (renderTask) {
                    renderTask.cancel();
                    renderTask = null;
                }

                if (loadingTask) {
                    loadingTask.destroy();
                    loadingTask = null;
                }

                pdfDocument = null;
            };

            const showEmptyState = () => {
                showElement(toolbar, 'flex');
                hideElement(loadingState);
                hideElement(errorState);
                hideElement(canvasWrapper);
                hideElement(fallbackFrame);
                showElement(emptyState, 'flex');
                resetCanvas();
                setControlsDisabled(true);
                updateControls();
            };

            const showLoadingState = () => {
                showElement(toolbar, 'flex');
                hideElement(emptyState);
                hideElement(errorState);
                hideElement(canvasWrapper);
                hideElement(fallbackFrame);
                showElement(loadingState, 'flex');
                setControlsDisabled(true);
            };

            const showCanvasState = () => {
                showElement(toolbar, 'flex');
                hideElement(emptyState);
                hideElement(loadingState);
                hideElement(errorState);
                hideElement(fallbackFrame);
                showElement(canvasWrapper, 'block');
            };

            const showFallbackFrame = (documentUrl) => {
                showElement(toolbar, 'flex');
                hideElement(emptyState);
                hideElement(loadingState);
                showElement(errorState, 'block');
                hideElement(canvasWrapper);
                fallbackFrame.src = documentUrl || 'about:blank';
                showElement(fallbackFrame, 'block');
                resetCanvas();
                setControlsDisabled(true);
                updateControls();
            };

            const renderPage = async (renderKey = activeRenderKey) => {
                if (! pdfDocument || renderKey !== activeRenderKey) {
                    return;
                }

                if (renderTask) {
                    renderTask.cancel();
                    renderTask = null;
                }

                const page = await pdfDocument.getPage(currentPage);

                if (renderKey !== activeRenderKey) {
                    return;
                }

                const viewport = page.getViewport({ scale: currentScale });
                const pixelRatio = window.devicePixelRatio || 1;
                canvas.width = Math.floor(viewport.width * pixelRatio);
                canvas.height = Math.floor(viewport.height * pixelRatio);
                canvas.style.width = `${Math.floor(viewport.width)}px`;
                canvas.style.height = `${Math.floor(viewport.height)}px`;

                showCanvasState();
                updateControls();

                renderTask = page.render({
                    canvasContext,
                    viewport,
                    transform: pixelRatio !== 1 ? [pixelRatio, 0, 0, pixelRatio, 0, 0] : null,
                });

                try {
                    await renderTask.promise;
                } catch (error) {
                    if (error?.name !== 'RenderingCancelledException' && renderKey === activeRenderKey) {
                        showFallbackFrame(activeDocumentUrl);
                    }
                } finally {
                    if (renderKey === activeRenderKey) {
                        renderTask = null;
                    }
                }
            };

            const fitToWidth = async () => {
                if (! pdfDocument || ! canvasWrapper) {
                    return;
                }

                const page = await pdfDocument.getPage(currentPage);
                const baseViewport = page.getViewport({ scale: 1 });
                const availableWidth = Math.max(canvasWrapper.clientWidth - 32, 240);
                currentScale = Math.min(Math.max(availableWidth / baseViewport.width, 0.5), 2.5);
                await renderPage();
            };

            const loadPdf = async (documentTitle, documentUrl) => {
                cancelActiveRender();
                activeDocumentUrl = documentUrl || '';
                currentPage = 1;
                currentScale = 1;

                if (title) {
                    title.textContent = documentTitle || 'Preview Dokumen';
                }

                [openLink, downloadLink].forEach((link) => {
                    if (! link) {
                        return;
                    }

                    link.href = activeDocumentUrl || '#';
                });

                if (! activeDocumentUrl) {
                    fallbackFrame.src = 'about:blank';
                    showEmptyState();
                    return;
                }

                if (! pdfjsLib) {
                    showFallbackFrame(activeDocumentUrl);
                    return;
                }

                const renderKey = activeRenderKey;
                showLoadingState();
                fallbackFrame.src = 'about:blank';

                try {
                    loadingTask = pdfjsLib.getDocument({
                        url: activeDocumentUrl,
                        withCredentials: true,
                    });
                    pdfDocument = await loadingTask.promise;

                    if (renderKey !== activeRenderKey) {
                        return;
                    }

                    await fitToWidth();
                } catch (error) {
                    if (renderKey === activeRenderKey) {
                        showFallbackFrame(activeDocumentUrl);
                    }
                } finally {
                    if (renderKey === activeRenderKey) {
                        loadingTask = null;
                    }
                }
            };

            prevButton?.addEventListener('click', async () => {
                if (! pdfDocument || currentPage <= 1) {
                    return;
                }

                currentPage -= 1;
                await renderPage();
            });

            nextButton?.addEventListener('click', async () => {
                if (! pdfDocument || currentPage >= pdfDocument.numPages) {
                    return;
                }

                currentPage += 1;
                await renderPage();
            });

            zoomOutButton?.addEventListener('click', async () => {
                if (! pdfDocument) {
                    return;
                }

                currentScale = Math.max(currentScale - 0.15, 0.5);
                await renderPage();
            });

            zoomInButton?.addEventListener('click', async () => {
                if (! pdfDocument) {
                    return;
                }

                currentScale = Math.min(currentScale + 0.15, 3);
                await renderPage();
            });

            fitWidthButton?.addEventListener('click', fitToWidth);

            window.addEventListener('resize', () => {
                if (! pdfDocument) {
                    return;
                }

                window.clearTimeout(window.approvalPdfPreviewResizeTimer);
                window.approvalPdfPreviewResizeTimer = window.setTimeout(fitToWidth, 200);
            });

            window.approvalPdfPreview = {
                load: loadPdf,
            };

            loadPdf(root.dataset.initialTitle || 'Preview Dokumen', root.dataset.initialUrl || '');
        });
    </script>
@endonce
