<?php
/**
 * Remaining Life Calculator - RBI Engineering Suite
 */
$pageTitle = 'Remaining Life Calculator';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Analytics', 'url' => '#'],
    ['label' => 'Remaining Life', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-hourglass-split me-2"></i>Remaining Life Calculator</h5>
</div>

<div class="row g-4">
    <!-- Input Panel -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Input Parameters</div>
            <div class="card-body">
                <form id="rlForm">
                    <div class="mb-3">
                        <label class="form-label">Asset (optional)</label>
                        <select name="asset_id" class="form-select" id="rl-asset">
                            <option value="">Manual input</option>
                            <option value="1">V-101 - Inlet Separator</option>
                            <option value="2">E-205 - Feed/Effluent Exchanger</option>
                            <option value="3">T-401 - Crude Storage Tank</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nominal Thickness (mm)</label>
                        <input type="number" step="0.01" class="form-control" id="rl-nominal" value="12.70" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Measured Thickness (mm)</label>
                        <input type="number" step="0.01" class="form-control" id="rl-measured" value="9.82" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Corrosion Rate (mm/yr)</label>
                        <input type="number" step="0.001" class="form-control" id="rl-rate" value="0.18" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Minimum Required Thickness (mm)</label>
                        <input type="number" step="0.01" class="form-control" id="rl-min" value="4.20" required>
                    </div>
                    <button type="button" class="btn btn-primary w-100" onclick="calculateRL()">
                        <i class="bi bi-calculator me-1"></i>Calculate Remaining Life
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Results Panel -->
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header">Results</div>
            <div class="card-body">
                <div class="row g-3 text-center mb-3">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Remaining Life</div>
                            <div class="fs-2 fw-bold text-primary" id="rl-result-life">31.2</div>
                            <div class="small">years</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Retirement Date</div>
                            <div class="fs-4 fw-bold" id="rl-result-date">2057</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Remaining CA</div>
                            <div class="fs-4 fw-bold" id="rl-result-ca">5.62</div>
                            <div class="small">mm</div>
                        </div>
                    </div>
                </div>
                <!-- Progress Bar -->
                <div class="mb-2">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Life Utilization</span>
                        <span id="rl-util-pct">40%</span>
                    </div>
                    <div class="progress" style="height:20px">
                        <div class="progress-bar bg-success" id="rl-util-bar" style="width:40%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Thickness Projection Over Time</div>
            <div class="card-body">
                <canvas id="rlProjectionChart" style="height:300px"></canvas>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
let rlChart;
function calculateRL() {
    let nominal = parseFloat(document.getElementById("rl-nominal").value);
    let measured = parseFloat(document.getElementById("rl-measured").value);
    let rate = parseFloat(document.getElementById("rl-rate").value);
    let minThk = parseFloat(document.getElementById("rl-min").value);

    let remainingCA = measured - minThk;
    let remainingLife = rate > 0 ? remainingCA / rate : 999;
    let retYear = new Date().getFullYear() + Math.round(remainingLife);
    let ageEstimate = rate > 0 ? (nominal - measured) / rate : 0;
    let totalLife = ageEstimate + remainingLife;
    let utilization = totalLife > 0 ? (ageEstimate / totalLife) * 100 : 0;

    document.getElementById("rl-result-life").textContent = remainingLife.toFixed(1);
    document.getElementById("rl-result-date").textContent = retYear;
    document.getElementById("rl-result-ca").textContent = remainingCA.toFixed(2);
    document.getElementById("rl-util-pct").textContent = utilization.toFixed(0) + "%";
    document.getElementById("rl-util-bar").style.width = utilization + "%";
    document.getElementById("rl-util-bar").className = "progress-bar " +
        (utilization > 80 ? "bg-danger" : utilization > 60 ? "bg-warning" : "bg-success");

    // Generate projection data
    let labels = [], thicknesses = [], minLine = [];
    let currentYear = new Date().getFullYear();
    for (let y = 0; y <= Math.min(Math.ceil(remainingLife * 1.3), 60); y++) {
        labels.push(currentYear + y);
        thicknesses.push(Math.max(measured - (rate * y), 0));
        minLine.push(minThk);
    }

    if(rlChart) rlChart.destroy();
    rlChart = new Chart(document.getElementById("rlProjectionChart"), {
        type: "line",
        data: {
            labels: labels,
            datasets: [
                { label: "Projected Thickness", data: thicknesses, borderColor: "#3498db", backgroundColor: "rgba(52,152,219,.1)", fill: true, tension: 0.1 },
                { label: "Min Required", data: minLine, borderColor: "#dc3545", borderDash: [5,5], pointRadius: 0, fill: false }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { title: { display: true, text: "Thickness (mm)" }, beginAtZero: true } },
            plugins: { annotation: {} }
        }
    });
}
calculateRL();
</script>';
require_once INCLUDES_PATH . '/footer.php';
