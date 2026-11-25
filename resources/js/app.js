import Chart from 'chart.js/auto';

const dashboardCharts = window.takeDashboardCharts;

if (dashboardCharts) {
    const topAssetsCanvas = document.querySelector('[data-chart="top-assets"]');

    if (topAssetsCanvas) {
        new Chart(topAssetsCanvas, {
            type: 'bar',
            data: {
                labels: dashboardCharts.topAssets.labels,
                datasets: [{
                    data: dashboardCharts.topAssets.values,
                    backgroundColor: '#75835a',
                    borderRadius: 0,
                    maxBarThickness: 30,
                }],
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: '#eeeae1' },
                    },
                    y: {
                        grid: { display: false },
                    },
                },
            },
        });
    }

    const statusesCanvas = document.querySelector('[data-chart="borrowing-statuses"]');

    if (statusesCanvas) {
        new Chart(statusesCanvas, {
            type: 'doughnut',
            data: {
                labels: dashboardCharts.statuses.labels,
                datasets: [{
                    data: dashboardCharts.statuses.values,
                    backgroundColor: ['#e7b64c', '#70a8c4', '#7583c2', '#75a68c', '#d98278'],
                    borderColor: '#fff',
                    borderWidth: 3,
                }],
            },
            options: {
                maintainAspectRatio: false,
                cutout: '66%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 18,
                        },
                    },
                },
            },
        });
    }
}
