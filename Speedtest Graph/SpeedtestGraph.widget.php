<?php

// /usr/local/www/widgets/widgets/SpeedtestGraph.widget.php

require_once("guiconfig.inc");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pconfig = array();
    $pconfig['speedtestgraphrefreshinterval'] = !empty($config['widgets']['speedtestgraphrefreshinterval']) ? $config['widgets']['speedtestgraphrefreshinterval'] : '0';
    $pconfig['speedtestgraphaspectratio'] = !empty($config['widgets']['speedtestgraphaspectratio']) ? $config['widgets']['speedtestgraphaspectratio'] : '50%';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pconfig = $_POST;
    if (!empty($pconfig['speedtestgraphrefreshinterval'])) {
        $config['widgets']['speedtestgraphrefreshinterval'] = $pconfig['speedtestgraphrefreshinterval'];
    } else {
        unset($config['widgets']['speedtestgraphrefreshinterval']);
    }
    if (!empty($pconfig['speedtestgraphaspectratio'])) {
        $config['widgets']['speedtestgraphaspectratio'] = $pconfig['speedtestgraphaspectratio'];
    } else {
        unset($config['widgets']['speedtestgraphaspectratio']);
    }
    write_config("Saved SpeedtestGraph settings via Dashboard");
    header(url_safe('Location: /index.php'));
    exit;
}

?>

<div id="speedtestgraph-settings" class="widgetconfigdiv" style="display:none;">
    <form action="/widgets/widgets/SpeedtestGraph.widget.php" method="post" name="iformd">
        <table class="table table-condensed">
            <tr>
                <td>
                    <label for="SpeedtestGraphRefreshInterval">Refresh Interval:</label>
                    <select id="SpeedtestGraphRefreshInterval" name="speedtestgraphrefreshinterval" class="selectpicker_widget">
                        <option value="0" <?= $pconfig['speedtestgraphrefreshinterval'] == '0' ? 'selected="selected"' : '' ?>>Never</option>
                        <option value="900000" <?= $pconfig['speedtestgraphrefreshinterval'] == '900000' ? 'selected="selected"' : '' ?>>Every 15 minutes</option>
                        <option value="1800000" <?= $pconfig['speedtestgraphrefreshinterval'] == '1800000' ? 'selected="selected"' : '' ?>>Every 30 minutes</option>
                        <option value="3600000" <?= $pconfig['speedtestgraphrefreshinterval'] == '3600000' ? 'selected="selected"' : '' ?>>Every 1 hour</option>
                        <option value="21600000" <?= $pconfig['speedtestgraphrefreshinterval'] == '21600000' ? 'selected="selected"' : '' ?>>Every 6 hours</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="SpeedtestGraphAspectRatio">Aspect RatioGraph Size:</label>
                    <select id="SpeedtestGraphAspectRatio" name="speedtestgraphaspectratio" class="selectpicker_widget">
                        <option value="0"     <?= $pconfig['speedtestgraphaspectratio'] == '0'     ? 'selected="selected"' : '' ?>>Default</option>
                        <option value="200px" <?= $pconfig['speedtestgraphaspectratio'] == '200px' ? 'selected="selected"' : '' ?>>Small</option>
                        <option value="300px" <?= $pconfig['speedtestgraphaspectratio'] == '300px' ? 'selected="selected"' : '' ?>>Medium</option>
                        <option value="400px" <?= $pconfig['speedtestgraphaspectratio'] == '400px' ? 'selected="selected"' : '' ?>>Large</option>
                        <option value="500px" <?= $pconfig['speedtestgraphaspectratio'] == '500px' ? 'selected="selected"' : '' ?>>Very Large</option>
                        <option value="600px" <?= $pconfig['speedtestgraphaspectratio'] == '600px' ? 'selected="selected"' : '' ?>>Extra Large</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <button id="submitd" name="submitd" type="submit" class="btn btn-primary" value="yes"><?= gettext('Save') ?></button>
                </td>
            </tr>
        </table>
    </form>
</div>

<?php
if ($pconfig['speedtestgraphaspectratio'] != '200px' && $pconfig['speedtestgraphaspectratio'] != '300px' && $pconfig['speedtestgraphaspectratio'] != '400px' && $pconfig['speedtestgraphaspectratio'] != '500px' && $pconfig['speedtestgraphaspectratio'] != '600px') {
    ?>
    <div id="speedtest-chart-widget1" style="width: 100%; padding: 20px;">
        <canvas id="speedTestChart" style="width: 100%"></canvas>
    </div>
    <?php
} else {
    ?>
    <div id="speedtest-chart-widget3" style="position: relative; width: 100%; height: <?= $pconfig['speedtestgraphaspectratio'] ?>;">
        <canvas id="speedTestChart" style="width: 100%; height: 100%;"></canvas>
    </div>

    <?php
}
?>

<!-- <script src="/js/chart.min.js"></script>
<script src="/js/chartjs-adapter-date-fns.bundle.js"></script> -->

<script>
    let SpeedtestGraphRefreshTimer;
    const SpeedtestGraphRefreshIntervalElement = document.getElementById('SpeedtestGraphRefreshInterval');
    const SpeedtestGraphAspectRatioElement = document.getElementById('SpeedtestGraphAspectRatio');
    const initialSpeedtestGraphRefreshInterval = parseInt(SpeedtestGraphRefreshIntervalElement.value, 10);
    const initialSpeedtestGraphAspectRatio = SpeedtestGraphAspectRatioElement.value;

    async function fetchSpeedTestData() {
        try {
            const response = await fetch('/api/speedtest/service/showlog');
            if (!response.ok) {
                throw new Error('Network response was not ok. Is Speedtest installed?');
            }
            const data = await response.json();
            console.log('Fetched Data:', data);
            return data;
        } catch (error) {
            console.error('Error fetching speed test data:', error);
            return [];
        }
    }

    function saveLegendState(chart) {
        const legendState = chart.legend.legendItems.map(item => item.hidden);
        console.log('Saving Legend State:', legendState);
        localStorage.setItem('speedtestGraphLegendState', JSON.stringify(legendState));
        console.log('Saved Legend State:', JSON.stringify(legendState));
    }

function applyLegendState(chart) {
    const legendState = JSON.parse(localStorage.getItem('speedtestGraphLegendState'));
    console.log('Applying Legend State:', legendState);
    if (legendState) {
        legendState.forEach((hidden, index) => {
            const meta = chart.getDatasetMeta(index);
            console.log('meta:', meta);
            // console.log('Index:', index);
            console.log('hidden:', hidden);
            meta.hidden = hidden;
        });
        chart.update();
    }
}

    function drawSpeedTestChart(data) {
        // Reverse the data arrays to have oldest items on the left
        data.reverse();

        const ctx = document.getElementById('speedTestChart').getContext('2d');
        const labels = data.map(entry => {
            let date = new Date(entry[0]).toLocaleString().slice(0, -3); // Remove the last 3 characters (seconds)
            let parts = date.split('/');
            parts[2] = parts[2].split(',')[1].trim(); // Remove the year part
            return parts.slice(0, 2).join('/') + ' - ' + parts[2]; // Join the parts back without the year
        });

        const downloadData = data.map(entry => parseFloat(entry[5]).toFixed(2));
        const uploadData = data.map(entry => parseFloat(entry[6]).toFixed(2));
        const latencyData = data.map(entry => parseFloat(entry[7]).toFixed(2));

        console.log('Labels:', labels);
        console.log('Download Data:', downloadData);
        console.log('Upload Data:', uploadData);
        console.log('Latency Data:', latencyData);

        const speedTestChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Download Speed (Mbps)',
                        data: downloadData,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        fill: true,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Upload Speed (Mbps)',
                        data: uploadData,
                        borderColor: 'rgba(153, 102, 255, 1)',
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        fill: true,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Latency (ms)',
                        data: latencyData,
                        borderColor: 'rgba(255, 159, 64, 1)',
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        fill: false,
                        yAxisID: 'y1',
                        borderDash: [3, 5], // Make the line dashed
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Timestamp'
                        },
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Speed (Mbps)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Latency (ms)'
                        },
                        grid: {
                            drawOnChartArea: false, // only want the grid lines for one axis to show up
                        },
                    },
                },
                plugins: {
                    legend: {
                        onClick: function(e, legendItem) {
                            const index = legendItem.datasetIndex;
                            const ci = this.chart;
                            const meta = ci.getDatasetMeta(index);
                            meta.hidden = meta.hidden === null ? !ci.data.datasets[index].hidden : null;
                            ci.update();
                            saveLegendState(ci);
                        }
                    }
                }
            }
        });

        applyLegendState(speedTestChart);
        // saveLegendState(speedTestChart);
    }

    function startSpeedtestGraphRefreshTimer(interval) {
        if (SpeedtestGraphRefreshTimer) {
            clearInterval(SpeedtestGraphRefreshTimer);
        }
        if (interval > 0) {
            SpeedtestGraphRefreshTimer = setInterval(() => {
                fetchSpeedTestData().then(data => drawSpeedTestChart(data));
            }, interval);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        fetchSpeedTestData().then(data => drawSpeedTestChart(data));

        SpeedtestGraphRefreshIntervalElement.addEventListener('change', function() {
            const interval = parseInt(this.value, 10);
            startSpeedtestGraphRefreshTimer(interval);
        });

        SpeedtestGraphAspectRatioElement.addEventListener('change', function() {
            fetchSpeedTestData().then(data => drawSpeedTestChart(data));
        });

        // Start the timer with the default selected value
        startSpeedtestGraphRefreshTimer(initialSpeedtestGraphRefreshInterval);
    });

    // Needed to display the widget settings menu
    document.getElementById("SpeedtestGraph-configure").addEventListener("click", function() {
        const configDiv = document.getElementById("speedtestgraph-settings");
        configDiv.style.display = configDiv.style.display === "none" ? "block" : "none";
    });
</script>

<!-- needed to display the widget settings menu -->
<script>
//<![CDATA[
  $("#SpeedtestGraph-configure").removeClass("disabled");
//]]>
</script>
