<script>
    document.addEventListener('DOMContentLoaded', function () {
        const summary = @json($workshopDashboard['summary']);
        const reguSummary = @json($workshopDashboard['regu']);
        const trend = @json($workshopDashboard['trend']);
        const monthlyCosts = @json($workshopDashboard['monthly_costs']);
        const completionCanvas = document.getElementById('workshopCompletionChart');
        const reguCanvases = document.querySelectorAll('[data-workshop-regu-chart]');
        const trendCanvas = document.getElementById('workshopCompletionTrendChart');
        const monthlyCostCanvas = document.getElementById('workshopMonthlyCostChart');

        const destroyChart = (instanceKey) => {
            if (window[instanceKey]) {
                window[instanceKey].destroy();
                window[instanceKey] = null;
            }
        };

        const formatRupiah = (value) => `Rp ${Number(value || 0).toLocaleString('id-ID')}`;
        const compactRupiah = (value) => {
            const number = Number(value || 0);
            if (number >= 1000000000000) return `Rp ${(number / 1000000000000).toLocaleString('id-ID')} T`;
            if (number >= 1000000000) return `Rp ${(number / 1000000000).toLocaleString('id-ID')} M`;
            if (number >= 1000000) return `Rp ${(number / 1000000).toLocaleString('id-ID')} jt`;
            if (number >= 1000) return `Rp ${(number / 1000).toLocaleString('id-ID')} rb`;
            return formatRupiah(number);
        };
        const completionCenterText = {
            id: 'workshopCompletionCenterText',
            afterDraw(chart) {
                const center = chart.getDatasetMeta(0)?.data?.[0];

                if (!center) return;

                const { ctx } = chart;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = '#64748b';
                ctx.font = '700 9px sans-serif';
                ctx.fillText('TOTAL ORDER', center.x, center.y - 10);
                ctx.fillStyle = '#0f172a';
                ctx.font = '800 22px sans-serif';
                ctx.fillText(Number(summary.total || 0).toLocaleString('id-ID'), center.x, center.y + 12);
                ctx.restore();
            },
        };

        if (completionCanvas) {
            destroyChart('workshopCompletionChartInstance');
            window.workshopCompletionChartInstance = new Chart(completionCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Selesai', 'Belum Selesai'],
                    datasets: [{
                        data: [Number(summary.completed || 0), Number(summary.incomplete || 0)],
                        backgroundColor: ['#10b981', '#e2e8f0'],
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverOffset: 3,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                usePointStyle: true,
                                padding: 18,
                                font: { size: 10, weight: '600' },
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label: context => `${context.label}: ${Number(context.raw || 0).toLocaleString('id-ID')} order`,
                            },
                        },
                    },
                },
                plugins: [completionCenterText],
            });
        }

        if (Array.isArray(window.workshopReguChartInstances)) {
            window.workshopReguChartInstances.forEach(chart => chart?.destroy());
        }
        window.workshopReguChartInstances = Array.from(reguCanvases).map((canvas, index) => {
            const regu = reguSummary[index] || {};
            const values = [
                Number(regu.in_progress || 0),
                Number(regu.completed || 0),
                Number(regu.incomplete || 0),
            ];
            const valueLabels = {
                id: `workshopReguValueLabels${index}`,
                afterDatasetsDraw(chart) {
                    const { ctx, chartArea } = chart;
                    const bars = chart.getDatasetMeta(0)?.data || [];

                    ctx.save();
                    ctx.fillStyle = '#0f172a';
                    ctx.font = '700 10px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';
                    bars.forEach((bar, barIndex) => {
                        ctx.fillText(values[barIndex].toLocaleString('id-ID'), bar.x, Math.max(bar.y - 4, chartArea.top + 10));
                    });
                    ctx.restore();
                },
            };

            return new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: [['ORDER', 'PROSES'], ['ORDER', 'SELESAI'], ['ORDER BELUM', 'SELESAI']],
                    datasets: [{
                        data: values,
                        backgroundColor: ['#22c55e', '#facc15', '#2563eb'],
                        borderRadius: 3,
                        barPercentage: 0.72,
                        categoryPercentage: 0.8,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 12 } },
                    scales: {
                        x: {
                            border: { display: false },
                            grid: { display: false },
                            ticks: {
                                color: '#64748b',
                                font: { size: 7, weight: '700' },
                                maxRotation: 0,
                                minRotation: 0,
                            },
                        },
                        y: {
                            beginAtZero: true,
                            grace: '20%',
                            display: false,
                        },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: context => `${Number(context.raw || 0).toLocaleString('id-ID')} order`,
                            },
                        },
                    },
                },
                plugins: [valueLabels],
            });
        });

        if (trendCanvas) {
            destroyChart('workshopCompletionTrendChartInstance');
            window.workshopCompletionTrendChartInstance = new Chart(trendCanvas, {
                type: 'line',
                data: {
                    labels: trend.map(item => item.label),
                    datasets: [
                        {
                            label: 'Penyelesaian',
                            data: trend.map(item => Number(item.percentage || 0)),
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.12)',
                            pointBackgroundColor: '#2563eb',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: `Target ${Number(summary.completion_target || 0)}%`,
                            data: trend.map(() => Number(summary.completion_target || 0)),
                            borderColor: '#f59e0b',
                            borderDash: [7, 5],
                            borderWidth: 2,
                            pointRadius: 0,
                            tension: 0,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { color: '#64748b', font: { size: 10, weight: '600' } },
                        },
                        y: {
                            beginAtZero: true,
                            max: 100,
                            border: { display: false },
                            grid: { color: 'rgba(148, 163, 184, 0.18)' },
                            ticks: { callback: value => `${value}%` },
                        },
                    },
                    plugins: {
                        legend: {
                            labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, font: { size: 10 } },
                        },
                        tooltip: {
                            callbacks: {
                                label: context => context.datasetIndex === 0
                                    ? `Penyelesaian: ${Number(context.raw || 0).toLocaleString('id-ID')}%`
                                    : `Target: ${Number(context.raw || 0).toLocaleString('id-ID')}%`,
                            },
                        },
                    },
                },
            });
        }

        if (monthlyCostCanvas) {
            destroyChart('workshopMonthlyCostChartInstance');
            window.workshopMonthlyCostChartInstance = new Chart(monthlyCostCanvas, {
                type: 'bar',
                data: {
                    labels: monthlyCosts.map(item => item.label),
                    datasets: [{
                        label: 'Biaya Order Bengkel',
                        data: monthlyCosts.map(item => Number(item.amount || 0)),
                        backgroundColor: '#4f46e5',
                        borderRadius: 7,
                        maxBarThickness: 54,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { color: '#64748b', font: { size: 10, weight: '600' } },
                        },
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            grid: { color: 'rgba(148, 163, 184, 0.18)' },
                            ticks: { callback: value => compactRupiah(value) },
                        },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: { label: context => `Biaya Order Bengkel: ${formatRupiah(context.raw)}` },
                        },
                    },
                },
            });
        }

        const resizeState = window.__womsWorkshopDashboardChartResizeState || {
            timeoutId: null,
            listenersRegistered: false,
        };
        window.__womsWorkshopDashboardChartResizeState = resizeState;

        const scheduleResize = () => {
            if (resizeState.timeoutId !== null) {
                window.clearTimeout(resizeState.timeoutId);
            }

            resizeState.timeoutId = window.setTimeout(() => {
                resizeState.timeoutId = null;
                [
                    window.workshopCompletionChartInstance,
                    ...(window.workshopReguChartInstances || []),
                    window.workshopCompletionTrendChartInstance,
                    window.workshopMonthlyCostChartInstance,
                ].forEach(chart => {
                    if (chart && typeof chart.resize === 'function') {
                        chart.resize();
                    }
                });
            }, 330);
        };

        if (!resizeState.listenersRegistered) {
            window.addEventListener('resize', scheduleResize, { passive: true });
            window.addEventListener('orientationchange', scheduleResize, { passive: true });
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    scheduleResize();
                }
            });
            resizeState.listenersRegistered = true;
        }

        scheduleResize();
    });
</script>
