async function loadVoteChart() {

    try {

        const response = await fetch(
            '../vote/result_data.php'
        );

        const data = await response.json();

        const labels = data.map(
            item => item.name
        );

        const totals = data.map(
            item => item.total
        );

        const canvas = document.getElementById(
            'voteChart'
        );

        if (!canvas) return;

        if (window.voteChartInstance) {

            window.voteChartInstance.destroy();
        }

        window.voteChartInstance = new Chart(
            canvas,
            {

                type: 'bar',

                data: {

                    labels: labels,

                    datasets: [{

                        label: 'Jumlah Suara',

                        data: totals,

                        borderWidth: 1

                    }]
                },

                options: {

                    responsive: true,

                    scales: {

                        y: {

                            beginAtZero: true,

                            ticks: {

                                precision: 0
                            }
                        }
                    }
                }
            }
        );

    } catch(error) {

        console.error(
            "Chart Error:",
            error
        );
    }
}

document.addEventListener(
    'DOMContentLoaded',
    () => {

        loadVoteChart();

        setInterval(
            loadVoteChart,
            5000
        );
    }
);