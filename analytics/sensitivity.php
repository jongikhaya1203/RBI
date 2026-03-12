<?php
/**
 * Sensitivity Analysis - RBI Engineering Suite
 */
$pageTitle = 'Sensitivity Analysis';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Sensitivity Analysis', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-sliders me-2"></i>Sensitivity Analysis</h5>
</div>

<div class="row g-4">
    <!-- Input Controls -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Parameters</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Asset</label>
                    <select class="form-select" id="sa-asset" onchange="runSensitivity()">
                        <option value="1">V-101 - Inlet Separator</option>
                        <option value="2">E-205 - Feed/Effluent Exchanger</option>
                        <option value="3">T-401 - Crude Storage Tank</option>
                    </select>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between">
                        Corrosion Rate (mm/yr)
                        <span class="fw-bold" id="sa-rate-val">0.18</span>
                    </label>
                    <input type="range" class="form-range" id="sa-rate" min="0.01" max="1.0" step="0.01" value="0.18" oninput="updateSlider(this,'sa-rate-val');runSensitivity()">
                </div>
                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between">
                        Current Thickness (mm)
                        <span class="fw-bold" id="sa-thick-val">9.82</span>
                    </label>
                    <input type="range" class="form-range" id="sa-thick" min="3" max="25" step="0.1" value="9.82" oninput="updateSlider(this,'sa-thick-val');runSensitivity()">
                </div>
                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between">
                        Min Required Thickness (mm)
                        <span class="fw-bold" id="sa-min-val">4.20</span>
                    </label>
                    <input type="range" class="form-range" id="sa-min" min="1" max="10" step="0.1" value="4.20" oninput="updateSlider(this,'sa-min-val');runSensitivity()">
                </div>
                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between">
                        Management Factor (FMS)
                        <span class="fw-bold" id="sa-fms-val">1.0</span>
                    </label>
                    <input type="range" class="form-range" id="sa-fms" min="0.1" max="10" step="0.1" value="1.0" oninput="updateSlider(this,'sa-fms-val');runSensitivity()">
                </div>
                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between">
                        Damage Factor
                        <span class="fw-bold" id="sa-df-val">10</span>
                    </label>
                    <input type="range" class="form-range" id="sa-df" min="1" max="500" step="1" value="10" oninput="updateSlider(this,'sa-df-val');runSensitivity()">
                </div>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div class="col-lg-8">
        <!-- Current Results -->
        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="card text-center py-3"><div class="text-muted small">Remaining Life</div><div class="fs-3 fw-bold text-primary" id="sa-result-rl">31.2 yr</div></div></div>
            <div class="col-md-4"><div class="card text-center py-3"><div class="text-muted small">Risk Level</div><div class="fs-3 fw-bold" id="sa-result-risk">Medium</div></div></div>
            <div class="col-md-4"><div class="card text-center py-3"><div class="text-muted small">Retirement Date</div><div class="fs-3 fw-bold" id="sa-result-date">2057</div></div></div>
        </div>

        <!-- Tornado Chart -->
        <div class="card mb-4">
            <div class="card-header">Tornado Chart - Impact on Remaining Life</div>
            <div class="card-body">
                <canvas id="tornadoChart" style="height:250px"></canvas>
            </div>
        </div>

        <!-- Sensitivity Line Chart -->
        <div class="card">
            <div class="card-header">Remaining Life vs Corrosion Rate</div>
            <div class="card-body">
                <canvas id="sensitivityChart" style="height:250px"></canvas>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
function updateSlider(el, labelId) {
    document.getElementById(labelId).textContent = parseFloat(el.value).toFixed(el.step.includes(".") ? el.step.split(".")[1].length : 0);
}

let tornadoChart, sensitivityChart;

function runSensitivity() {
    let rate = parseFloat(document.getElementById("sa-rate").value);
    let thick = parseFloat(document.getElementById("sa-thick").value);
    let minThk = parseFloat(document.getElementById("sa-min").value);
    let fms = parseFloat(document.getElementById("sa-fms").value);
    let df = parseFloat(document.getElementById("sa-df").value);

    let rl = rate > 0 ? (thick - minThk) / rate : 999;
    let retYear = new Date().getFullYear() + Math.round(rl);

    document.getElementById("sa-result-rl").textContent = rl.toFixed(1) + " yr";
    document.getElementById("sa-result-date").textContent = retYear;

    let riskScore = df * fms;
    let riskLabel = riskScore < 5 ? "Low" : riskScore < 20 ? "Medium" : riskScore < 100 ? "Medium-High" : riskScore < 500 ? "High" : "Very High";
    let riskColor = riskScore < 5 ? "#28a745" : riskScore < 20 ? "#ffc107" : riskScore < 100 ? "#fd7e14" : "#dc3545";
    document.getElementById("sa-result-risk").textContent = riskLabel;
    document.getElementById("sa-result-risk").style.color = riskColor;

    // Tornado chart: impact of +/-50% change in each parameter
    let baseRL = rl;
    let impacts = [
        { label: "Corrosion Rate", low: (thick-minThk)/(rate*1.5), high: (thick-minThk)/(rate*0.5) },
        { label: "Current Thickness", low: (thick*0.9-minThk)/rate, high: (thick*1.1-minThk)/rate },
        { label: "Min Required Thk", low: (thick-minThk*1.2)/rate, high: (thick-minThk*0.8)/rate },
        { label: "Management Factor", low: baseRL, high: baseRL },
        { label: "Damage Factor", low: baseRL, high: baseRL }
    ];
    let labels = impacts.map(i => i.label);
    let lowDeltas = impacts.map(i => i.low - baseRL);
    let highDeltas = impacts.map(i => i.high - baseRL);

    if(tornadoChart) tornadoChart.destroy();
    tornadoChart = new Chart(document.getElementById("tornadoChart"), {
        type: "bar",
        data: {
            labels: labels,
            datasets: [
                { label: "-50%", data: lowDeltas, backgroundColor: "rgba(220,53,69,.6)", borderRadius: 4 },
                { label: "+50%", data: highDeltas, backgroundColor: "rgba(40,167,69,.6)", borderRadius: 4 }
            ]
        },
        options: { indexAxis: "y", responsive: true, maintainAspectRatio: false, scales: { x: { title: { display: true, text: "Change in Remaining Life (years)" } } }, plugins: { legend: { position: "bottom" } } }
    });

    // Sensitivity line chart
    let ratePoints = [], rlPoints = [];
    for(let r = 0.05; r <= 0.8; r += 0.02) {
        ratePoints.push(r.toFixed(2));
        rlPoints.push(Math.max((thick - minThk) / r, 0));
    }
    if(sensitivityChart) sensitivityChart.destroy();
    sensitivityChart = new Chart(document.getElementById("sensitivityChart"), {
        type: "line",
        data: {
            labels: ratePoints,
            datasets: [{
                label: "Remaining Life (years)", data: rlPoints,
                borderColor: "#3498db", backgroundColor: "rgba(52,152,219,.1)", fill: true, tension: 0.3, pointRadius: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { x: { title: { display: true, text: "Corrosion Rate (mm/yr)" } }, y: { title: { display: true, text: "Remaining Life (years)" }, beginAtZero: true } } }
    });
}
runSensitivity();
</script>';
require_once INCLUDES_PATH . '/footer.php';
