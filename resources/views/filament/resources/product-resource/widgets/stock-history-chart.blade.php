<x-filament::widget>
    <div>
        <h3 class="text-lg font-bold mb-2">Évolution du stock</h3>
        <canvas id="stock-history-chart"></canvas>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('stock-history-chart').getContext('2d');
            const data = @json($data);
            new window.Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.date),
                    datasets: [{
                        label: 'Stock',
                        data: data.map(d => d.stock),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        fill: true,
                        tension: 0.2,
                    }],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Stock'
                            },
                            beginAtZero: true
                        },
                    },
                },
            });
        });
    </script>
</x-filament::widget>
