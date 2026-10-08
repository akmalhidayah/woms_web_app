<script>
    document.addEventListener('DOMContentLoaded', function () {
        const summary = @json($workshopDashboard['summary']);
        const reguSummary = @json($workshopDashboard['regu']);
        const trend = @json($workshopDashboard['trend']);
        const monthlyWorkValues = @json($workshopDashboard['monthly_work_values']);
        const completionCanvas = document.getElementById('workshopCompletionChart');
        const reguCanvases = document.querySelectorAll('[data-workshop-regu-chart]');
        const trendCanvas = document.getElementById('workshopCompletionTrendChart');
        const workValueCanvas = document.getElementById('workshopWorkValueChart');

        const destroyChart = (instanceKey) => {
            if (window[instanceKey]) {
                window[instanceKey].destroy();
                window[instanceKey] = null;
            }
        };

        const formatRupiah = (value) => `Rp ${Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 })}`;
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
                ctx.fillText('PENYELESAIAN', center.x, center.y - 10);
                ctx.fillStyle = '#0f172a';
                ctx.font = '800 22px sans-serif';
                ctx.fillText(`${Number(summary.completion_percentage || 0).toLocaleString('id-ID')}%`, center.x, center.y + 12);
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
            const chartTextColor = '#475569';
            const values = [
                Number(regu.in_progress || 0),
                Number(regu.completed || 0),
                Number(regu.total || 0),
            ];
            const valueLabels = {
                id: `workshopReguValueLabels${index}`,
                afterDatasetsDraw(chart) {
                    const { ctx, chartArea } = chart;
                    const bars = chart.getDatasetMeta(0)?.data || [];

                    ctx.save();
                    ctx.fillStyle = chartTextColor;
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
                    labels: [['ORDER', 'PROSES'], ['ORDER', 'SELESAI'], ['TOTAL', 'ORDER']],
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
                                color: chartTextColor,
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
            const trendColors = ['#2563eb', '#f59e0b', '#4f46e5'];
            const reguTrendDatasets = reguSummary.map((regu, index) => ({
                label: regu.name,
                data: trend.map(item => Number(item.regu?.[regu.name]?.completion_percentage || 0)),
                borderColor: trendColors[index] || trendColors[0],
                backgroundColor: trendColors[index] || trendColors[0],
                pointBackgroundColor: trendColors[index] || trendColors[0],
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                borderWidth: 2,
                tension: 0.3,
                fill: false,
            }));
            window.workshopCompletionTrendChartInstance = new Chart(trendCanvas, {
                type: 'line',
                data: {
                    labels: trend.map(item => item.label),
                    datasets: [
                        ...reguTrendDatasets,
                        {
                            label: `Target ${Number(summary.completion_target || 0)}%`,
                            data: trend.map(() => Number(summary.completion_target || 0)),
                            borderColor: '#ef4444',
                            borderDash: [7, 5],
                            borderWidth: 2,
                            pointRadius: 0,
                            tension: 0,
                            workshopTarget: true,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'nearest', intersect: false },
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
                            grid: { display: false },
                            ticks: { callback: value => `${value}%` },
                        },
                    },
                    plugins: {
                        legend: {
                            labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, font: { size: 10 } },
                        },
                        tooltip: {
                            callbacks: {
                                title: items => trend[items[0]?.dataIndex]?.period_label || '',
                                label: context => {
                                    const point = trend[context.dataIndex] || {};

                                    if (context.dataset.workshopTarget) {
                                        return `Target: ${Number(point.target || summary.completion_target || 0).toLocaleString('id-ID')}%`;
                                    }

                                    const metric = point.regu?.[context.dataset.label] || {};

                                    return [
                                        context.dataset.label,
                                        `Total Order : ${Number(metric.total || 0).toLocaleString('id-ID')}`,
                                        `Selesai : ${Number(metric.completed || 0).toLocaleString('id-ID')}`,
                                        `Belum Selesai : ${Number(metric.incomplete || 0).toLocaleString('id-ID')}`,
                                        `Penyelesaian : ${Number(metric.completion_percentage || 0).toLocaleString('id-ID')}%`,
                                    ];
                                },
                            },
                        },
                    },
                },
            });
        }

        if (workValueCanvas) {
            destroyChart('workshopWorkValueChartInstance');
            const valueColors = ['#2563eb', '#f59e0b', '#4f46e5'];
            window.workshopWorkValueChartInstance = new Chart(workValueCanvas, {
                type: 'line',
                data: {
                    labels: monthlyWorkValues.map(item => item.label),
                    datasets: reguSummary.map((regu, index) => ({
                        label: regu.name,
                        data: monthlyWorkValues.map(item => Number(item.regu?.[regu.name] || 0)),
                        borderColor: valueColors[index] || valueColors[0],
                        backgroundColor: valueColors[index] || valueColors[0],
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        borderWidth: 2,
                        tension: 0.3,
                        fill: false,
                    })),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'nearest', intersect: false },
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
                        legend: {
                            position: 'top',
                            labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, font: { size: 10 } },
                        },
                        tooltip: {
                            callbacks: {
                                title: items => monthlyWorkValues[items[0]?.dataIndex]?.period_label || '',
                                label: context => [
                                    context.dataset.label,
                                    `Nilai Pekerjaan: ${formatRupiah(context.raw)}`,
                                ],
                            },
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
                    window.workshopWorkValueChartInstance,
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
