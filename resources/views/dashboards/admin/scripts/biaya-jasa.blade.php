    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const globalFilterForm = document.getElementById('dashboardGlobalFilter');
            const globalAgreementSelect = document.getElementById('dashboardOutlineAgreement');
            const globalYearSelect = document.getElementById('dashboardYear');
            const startMonthSelect = document.getElementById('monthlyStartMonth');
            const endMonthSelect = document.getElementById('monthlyEndMonth');
            const applyFiltersButton = document.getElementById('applyMonthlyRealizationFilter');
            const chartTotal = document.getElementById('monthlyRealizationTotal');
            const chartEmptyState = document.getElementById('monthlyRealizationEmptyState');
            const chartContainer = document.getElementById('monthlyRealizationChartContainer');
            const chartCanvas = document.getElementById('monthlyRealizationChart');
            const nonMaintenanceOutstandingCanvas = document.getElementById('nonMaintenanceOutstandingChart');
            const capexOutstandingCanvas = document.getElementById('capexOutstandingChart');
            const topTenGeneralCostChartContainer = document.getElementById('topTenGeneralCostChartContainer');
            const topTenGeneralCostCanvas = document.getElementById('topTenGeneralCostChart');
            const topTenGeneralCostEmptyState = document.getElementById('topTenGeneralCostEmptyState');
            const topTenMaintenanceCostChartContainer = document.getElementById('topTenMaintenanceCostChartContainer');
            const topTenMaintenanceCostCanvas = document.getElementById('topTenMaintenanceCostChart');
            const topTenMaintenanceCostEmptyState = document.getElementById('topTenMaintenanceCostEmptyState');
            const overhaulPrognosisCanvas = document.getElementById('overhaulPrognosisChart');
            const initialChartData = @json($realizationChartData ?? []);
            const initialTopTenCostSections = @json($topTenCostSections ?? []);
            const initialTopTenMaintenanceCostSections = @json($topTenMaintenanceCostSections ?? []);
            const initialOverhaulPrognosis = @json($overhaulPrognosis ?? []);
            const chartEndpoint = @json(url('/admin/realisasi-biaya'));
            const selectedAgreementId = @json($selectedOutlineAgreementId ?? null);
            const selectedDashboardYear = @json($selectedDashboardYear);
            const chartColors = {
                general: '#2563eb',
                maintenance: '#10b981',
                non_maintenance: '#7c3aed',
                capex: '#0891b2',
            };
            const hasRealizationChart = [
                startMonthSelect,
                endMonthSelect,
                applyFiltersButton,
                chartTotal,
                chartEmptyState,
                chartContainer,
                chartCanvas,
            ].every(Boolean);
            const monthNames = {
                1: 'Jan', 2: 'Feb', 3: 'Mar', 4: 'Apr', 5: 'Mei', 6: 'Jun',
                7: 'Jul', 8: 'Agu', 9: 'Sep', 10: 'Okt', 11: 'Nov', 12: 'Des',
            };

            function loadMonths() {
                const months = [
                    { number: 1, name: 'Januari' }, { number: 2, name: 'Februari' }, { number: 3, name: 'Maret' },
                    { number: 4, name: 'April' }, { number: 5, name: 'Mei' }, { number: 6, name: 'Juni' },
                    { number: 7, name: 'Juli' }, { number: 8, name: 'Agustus' }, { number: 9, name: 'September' },
                    { number: 10, name: 'Oktober' }, { number: 11, name: 'November' }, { number: 12, name: 'Desember' }
                ];

                [startMonthSelect, endMonthSelect].forEach(select => {
                    select.innerHTML = '';
                    months.forEach(month => {
                        select.innerHTML += `<option value="${month.number}">${month.name}</option>`;
                    });
                });
            }

            function loadSavedFilters() {
                const firstRow = initialChartData[0] || {};
                const lastRow = initialChartData[initialChartData.length - 1] || firstRow;
                const savedStartMonth = Number(localStorage.getItem('monthlyRealizationStartMonth'));
                const savedEndMonth = Number(localStorage.getItem('monthlyRealizationEndMonth'));
                const hasValidSavedFilter = savedStartMonth >= 1
                    && savedStartMonth <= 12
                    && savedEndMonth >= 1
                    && savedEndMonth <= 12
                    && savedStartMonth <= savedEndMonth;
                const startMonth = hasValidSavedFilter ? savedStartMonth : Number(firstRow.month || 1);
                const endMonth = hasValidSavedFilter ? savedEndMonth : Number(lastRow.month || 12);

                if (startMonth) startMonthSelect.value = startMonth;
                if (endMonth) endMonthSelect.value = endMonth;

                if (hasValidSavedFilter) {
                    fetchRealizationData(startMonth, endMonth);
                } else {
                    renderChart(initialChartData);
                }
            }

            function fetchRealizationData(startMonth = null, endMonth = null) {
                const queryParams = new URLSearchParams({
                    oa_id: selectedAgreementId,
                    ...(selectedDashboardYear && { year: selectedDashboardYear }),
                    ...(startMonth && { startMonth }),
                    ...(endMonth && { endMonth })
                }).toString();

                fetch(`${chartEndpoint}?${queryParams}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!Array.isArray(data)) {
                            throw new Error('Format data tidak valid.');
                        }

                        renderChart(data);
                    })
                    .catch(error => {
                        console.error('Error saat memproses data:', error);
                        alert('Terjadi kesalahan saat mengambil data.');
                    });
            }

            function renderChart(rows) {
                const labels = rows.map(item => item.label || `${monthNames[item.month] || item.month} ${item.year}`);
                const datasetDefinitions = [
                    { key: 'general', label: 'General', color: chartColors.general },
                    { key: 'maintenance', label: 'Pemeliharaan', color: chartColors.maintenance },
                    { key: 'non_maintenance', label: 'Non Pemeliharaan', color: chartColors.non_maintenance },
                    { key: 'capex', label: 'CAPEX', color: chartColors.capex },
                ];
                const total = rows.reduce((sum, item) => sum + Number(item.general || 0), 0);
                const hasData = rows.some(item => datasetDefinitions.some(dataset => Number(item[dataset.key] || 0) > 0));

                chartTotal.textContent = formatRupiah(total);
                chartEmptyState.classList.toggle('hidden', hasData);
                chartEmptyState.classList.toggle('flex', !hasData);
                chartContainer.classList.toggle('hidden', !hasData);

                if (window.realisasiBiayaChart) window.realisasiBiayaChart.destroy();

                if (hasData) {
                    window.realisasiBiayaChart = new Chart(chartCanvas, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: datasetDefinitions.map(dataset => ({
                                key: dataset.key,
                                label: dataset.label,
                                data: rows.map(item => Number(item[dataset.key] || 0)),
                                borderColor: dataset.color,
                                backgroundColor: dataset.color,
                                borderWidth: dataset.key === 'general' ? 3 : 2,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                tension: 0.3,
                                fill: false,
                            })),
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: {
                                        display: selectedDashboardYear !== null,
                                    },
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: value => compactRupiah(value),
                                    },
                                },
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    labels: {
                                        boxWidth: 10,
                                        boxHeight: 10,
                                        font: { size: 9 },
                                    },
                                },
                                tooltip: {
                                    callbacks: {
                                        label: context => `${context.dataset.label}: ${formatRupiah(context.raw)}`,
                                    },
                                },
                            },
                        },
                    });
                }
            }

            function renderTopTenCostCharts(generalRows, maintenanceRows) {
                renderTopTenCostChart({
                    rows: generalRows,
                    canvas: topTenGeneralCostCanvas,
                    container: topTenGeneralCostChartContainer,
                    emptyState: topTenGeneralCostEmptyState,
                    instanceKey: 'topTenGeneralCostChartInstance',
                    pluginId: 'topTenGeneralCostSectionLabels',
                    datasetLabel: 'General',
                    color: '#2563eb',
                });
                renderTopTenCostChart({
                    rows: maintenanceRows,
                    canvas: topTenMaintenanceCostCanvas,
                    container: topTenMaintenanceCostChartContainer,
                    emptyState: topTenMaintenanceCostEmptyState,
                    instanceKey: 'topTenMaintenanceCostChartInstance',
                    pluginId: 'topTenMaintenanceCostSectionLabels',
                    datasetLabel: 'Pemeliharaan',
                    color: '#10b981',
                });
            }

            function renderOutstandingStageChart(canvas, instanceKey) {
                if (!canvas) return;

                let stages = {};
                try {
                    stages = JSON.parse(canvas.dataset.stages || '{}');
                } catch (error) {
                    console.error('Gagal membaca data outstanding kategori.', error);
                }

                const labels = ['HPP', 'Purchase Order', 'LPJ Process'];
                const amounts = [
                    Number(stages.hpp || 0),
                    Number(stages.purchase_order || 0),
                    Number(stages.lpj_process || 0),
                ];
                const color = canvas.dataset.color || '#2563eb';

                if (window[instanceKey]) {
                    window[instanceKey].destroy();
                    window[instanceKey] = null;
                }

                const valueLabels = {
                    id: `${instanceKey}ValueLabels`,
                    afterDatasetsDraw(chart) {
                        const { ctx, chartArea } = chart;
                        const metadata = chart.getDatasetMeta(0);

                        ctx.save();
                        ctx.fillStyle = '#334155';
                        ctx.font = '600 9px sans-serif';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';

                        metadata.data.forEach((bar, index) => {
                            const y = Math.max(chartArea.top + 10, bar.y - 5);
                            ctx.fillText(compactRupiah(amounts[index]), bar.x, y);
                        });

                        ctx.restore();
                    },
                };

                window[instanceKey] = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            data: amounts,
                            backgroundColor: color,
                            borderRadius: 7,
                            maxBarThickness: 52,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { padding: { top: 20 } },
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: {
                                    color: '#475569',
                                    font: { size: 8, weight: '600' },
                                    maxRotation: 0,
                                    autoSkip: false,
                                },
                            },
                            y: {
                                beginAtZero: true,
                                border: { display: false },
                                grid: { color: 'rgba(148, 163, 184, 0.16)' },
                                ticks: {
                                    maxTicksLimit: 5,
                                    callback: value => compactRupiah(value),
                                },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: context => formatRupiah(context.raw),
                                },
                            },
                        },
                    },
                    plugins: [valueLabels],
                });
            }

            function truncateCanvasText(ctx, text, maxWidth) {
                if (maxWidth <= 0) return '';
                if (ctx.measureText(text).width <= maxWidth) return text;

                const ellipsis = '…';
                let truncated = text;
                while (truncated.length > 0 && ctx.measureText(`${truncated}${ellipsis}`).width > maxWidth) {
                    truncated = truncated.slice(0, -1);
                }

                return truncated.length > 0 ? `${truncated}${ellipsis}` : '';
            }

            function renderTopTenCostChart({
                rows,
                canvas,
                container,
                emptyState,
                instanceKey,
                pluginId,
                datasetLabel,
                color,
            }) {
                if (!canvas || !container || !emptyState) return;

                const safeRows = Array.isArray(rows) ? rows.slice(0, 10) : [];
                const sections = safeRows.map(item => String(item.section || ''));
                const amounts = safeRows.map(item => Number(item.amount || 0));
                const hasData = safeRows.length > 0;

                container.classList.toggle('hidden', !hasData);
                emptyState.classList.toggle('hidden', hasData);
                emptyState.classList.toggle('flex', !hasData);

                if (window[instanceKey]) {
                    window[instanceKey].destroy();
                    window[instanceKey] = null;
                }

                if (!hasData) {
                    return;
                }

                const sectionLabelsPlugin = {
                    id: pluginId,
                    afterDatasetsDraw(chart) {
                        const { ctx, chartArea } = chart;
                        const metadata = chart.getDatasetMeta(0);

                        ctx.save();
                        ctx.font = '600 11px sans-serif';
                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'middle';

                        metadata.data.forEach((bar, index) => {
                            const label = `${sections[index]} · ${formatRupiah(amounts[index])}`;
                            const insideX = chartArea.left + 9;
                            const insideWidth = bar.x - insideX - 8;
                            const fullLabelWidth = ctx.measureText(label).width;

                            if (insideWidth >= fullLabelWidth) {
                                ctx.fillStyle = '#ffffff';
                                ctx.fillText(label, insideX, bar.y);
                                return;
                            }

                            const outsideX = Math.max(chartArea.left + 7, bar.x + 8);
                            const outsideWidth = chartArea.right - outsideX - 6;
                            const outsideLabel = truncateCanvasText(ctx, label, outsideWidth);

                            if (outsideLabel !== '') {
                                ctx.fillStyle = '#334155';
                                ctx.fillText(outsideLabel, outsideX, bar.y);
                            }
                        });

                        ctx.restore();
                    },
                };

                container.style.height = `${Math.max(320, Math.min(620, (safeRows.length * 54) + 52))}px`;
                window[instanceKey] = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: sections,
                        datasets: [{
                            label: datasetLabel,
                            data: amounts,
                            backgroundColor: color,
                            borderRadius: 6,
                            maxBarThickness: 34,
                            categoryPercentage: 0.76,
                            barPercentage: 0.86,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                beginAtZero: true,
                                grace: '15%',
                                grid: { display: false },
                                border: { display: false },
                                ticks: {
                                    maxTicksLimit: 5,
                                    callback: value => compactRupiah(value),
                                },
                            },
                            y: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: {
                                    display: false,
                                },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: items => sections[items[0]?.dataIndex] || '',
                                    label: context => `${datasetLabel}: ${formatRupiah(context.raw)}`,
                                },
                            },
                        },
                    },
                    plugins: [sectionLabelsPlugin],
                });
            }

            function renderOverhaulPrognosisChart(rows) {
                const safeRows = Array.isArray(rows) ? rows : [];
                const labels = safeRows.map(item => item.label);
                const amounts = safeRows.map(item => Number(item.amount || 0));

                if (window.overhaulPrognosisChartInstance) {
                    window.overhaulPrognosisChartInstance.destroy();
                    window.overhaulPrognosisChartInstance = null;
                }

                const overhaulValueLabels = {
                    id: 'overhaulValueLabels',
                    afterDatasetsDraw(chart) {
                        const { ctx, chartArea } = chart;
                        const metadata = chart.getDatasetMeta(0);

                        ctx.save();
                        ctx.fillStyle = '#334155';
                        ctx.font = '600 10px sans-serif';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';

                        metadata.data.forEach((bar, index) => {
                            const y = Math.max(chartArea.top + 12, bar.y - 6);
                            ctx.fillText(formatRupiah(amounts[index]), bar.x, y);
                        });

                        ctx.restore();
                    },
                };

                window.overhaulPrognosisChartInstance = new Chart(overhaulPrognosisCanvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Prognosa Biaya',
                            data: amounts,
                            backgroundColor: ['#f59e0b', '#d97706', '#b45309'],
                            borderRadius: 8,
                            maxBarThickness: 56,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: {
                                    color: '#475569',
                                    font: { size: 10 },
                                },
                            },
                            y: {
                                beginAtZero: true,
                                display: false,
                                grid: { display: false },
                                border: { display: false },
                                grace: '15%',
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: context => context.label + ': ' + formatRupiah(context.raw),
                                },
                            },
                        },
                    },
                    plugins: [overhaulValueLabels],
                });
            }

            function formatRupiah(value) {
                return `Rp ${Number(value || 0).toLocaleString('id-ID')}`;
            }

            function compactRupiah(value) {
                const number = Number(value || 0);
                if (number >= 1000000000) return `Rp ${(number / 1000000000).toLocaleString('id-ID')} M`;
                if (number >= 1000000) return `Rp ${(number / 1000000).toLocaleString('id-ID')} jt`;
                if (number >= 1000) return `Rp ${(number / 1000).toLocaleString('id-ID')} rb`;
                return `Rp ${number.toLocaleString('id-ID')}`;
            }

            if (hasRealizationChart) {
                applyFiltersButton.addEventListener('click', function () {
                    const startMonth = startMonthSelect.value;
                    const endMonth = endMonthSelect.value;

                    if (startMonth && endMonth && parseInt(startMonth) > parseInt(endMonth)) {
                        alert('Bulan mulai tidak boleh lebih besar dari bulan akhir!');
                        return;
                    }

                    localStorage.setItem('monthlyRealizationStartMonth', startMonth);
                    localStorage.setItem('monthlyRealizationEndMonth', endMonth);

                    fetchRealizationData(startMonth, endMonth);
                });

                renderTopTenCostCharts(initialTopTenCostSections, initialTopTenMaintenanceCostSections);
                renderOverhaulPrognosisChart(initialOverhaulPrognosis);
                loadMonths();
                loadSavedFilters();
            } else {
                renderTopTenCostCharts(initialTopTenCostSections, initialTopTenMaintenanceCostSections);
                renderOverhaulPrognosisChart(initialOverhaulPrognosis);
            }

            renderOutstandingStageChart(
                nonMaintenanceOutstandingCanvas,
                'nonMaintenanceOutstandingChartInstance',
            );
            renderOutstandingStageChart(
                capexOutstandingCanvas,
                'capexOutstandingChartInstance',
            );

            const dashboardChartResizeState = window.__womsDashboardChartResizeState || {
                timeoutId: null,
                listenersRegistered: false,
            };
            window.__womsDashboardChartResizeState = dashboardChartResizeState;

            const scheduleDashboardChartResize = () => {
                if (dashboardChartResizeState.timeoutId !== null) {
                    window.clearTimeout(dashboardChartResizeState.timeoutId);
                }

                dashboardChartResizeState.timeoutId = window.setTimeout(() => {
                    dashboardChartResizeState.timeoutId = null;

                    [
                        window.realisasiBiayaChart,
                        window.nonMaintenanceOutstandingChartInstance,
                        window.capexOutstandingChartInstance,
                        window.topTenGeneralCostChartInstance,
                        window.topTenMaintenanceCostChartInstance,
                        window.overhaulPrognosisChartInstance,
                    ].forEach(chart => {
                        if (chart && typeof chart.resize === 'function') {
                            chart.resize();
                        }
                    });
                }, 330);
            };

            if (!dashboardChartResizeState.listenersRegistered) {
                window.addEventListener('resize', scheduleDashboardChartResize, { passive: true });
                window.addEventListener('orientationchange', scheduleDashboardChartResize, { passive: true });
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        scheduleDashboardChartResize();
                    }
                });
                dashboardChartResizeState.listenersRegistered = true;
            }

            scheduleDashboardChartResize();

            [globalAgreementSelect, globalYearSelect].forEach(select => {
                select?.addEventListener('change', () => globalFilterForm?.submit());
            });
        });
    </script>
