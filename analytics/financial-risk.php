<?php
/**
 * Financial Risk Modeling - RBI Engineering Suite
 */
$pageTitle = 'Financial Risk';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Financial Risk', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-currency-dollar me-2"></i>Financial Risk Modeling</h5>
</div>

<!-- Summary KPIs -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card kpi-card"><div class="card-body text-center"><div class="text-muted small">Total Annual Risk Exposure</div><div class="fs-4 fw-bold text-danger">$2.4M</div></div></div></div>
    <div class="col-md-3"><div class="card kpi-card"><div class="card-body text-center"><div class="text-muted small">5-Year Cumulative Risk</div><div class="fs-4 fw-bold text-warning">$14.8M</div></div></div></div>
    <div class="col-md-3"><div class="card kpi-card"><div class="card-body text-center"><div class="text-muted small">Annual Inspection Budget</div><div class="fs-4 fw-bold text-primary">$350K</div></div></div></div>
    <div class="col-md-3"><div class="card kpi-card"><div class="card-body text-center"><div class="text-muted small">Avg Inspection ROI</div><div class="fs-4 fw-bold text-success">285%</div></div></div></div>
</div>

<div class="row g-4">
    <!-- Cost of Failure vs Inspection -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Cost of Failure vs Cost of Inspection</div>
            <div class="card-body">
                <canvas id="costComparisonChart" style="height:300px"></canvas>
            </div>
        </div>
    </div>
    <!-- Risk-Spend Optimization -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Risk-Spend Optimization</div>
            <div class="card-body">
                <canvas id="riskSpendChart" style="height:300px"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- NPV Analysis -->
<div class="card mt-4 mb-4">
    <div class="card-header">Net Present Value - Inspection Investment Analysis</div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3"><label class="form-label small">Discount Rate (%)</label><input type="number" class="form-control form-control-sm" value="8" id="npv-rate"></div>
            <div class="col-md-3"><label class="form-label small">Analysis Period (years)</label><input type="number" class="form-control form-control-sm" value="10" id="npv-years"></div>
            <div class="col-md-3"><label class="form-label small">Annual Inspection Cost ($)</label><input type="number" class="form-control form-control-sm" value="50000" id="npv-cost"></div>
            <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary btn-sm w-100" onclick="calcNPV()">Calculate NPV</button></div>
        </div>
        <canvas id="npvChart" style="height:250px"></canvas>
    </div>
</div>

<!-- Asset-Level Financial Risk Table -->
<div class="card">
    <div class="card-header">Asset-Level Financial Risk Ranking</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="financialTable">
                <thead>
                    <tr><th>Asset</th><th>Risk Level</th><th>Annual Expected Loss</th><th>5-Year Cumulative</th><th>Inspection Cost</th><th>Risk Reduction</th><th>ROI</th></tr>
                </thead>
                <tbody>
                    <tr><td>T-401 Storage Tank</td><td><span class="badge bg-danger">Very High</span></td><td class="fw-bold">$892,000</td><td>$5,450,000</td><td>$45,000</td><td>$267,600</td><td class="text-success fw-bold">495%</td></tr>
                    <tr><td>V-101 Separator</td><td><span class="badge bg-danger">High</span></td><td>$425,000</td><td>$2,596,000</td><td>$35,000</td><td>$127,500</td><td class="text-success fw-bold">264%</td></tr>
                    <tr><td>C-102 Column</td><td><span class="badge bg-danger">High</span></td><td>$380,000</td><td>$2,320,000</td><td>$55,000</td><td>$114,000</td><td class="text-success fw-bold">107%</td></tr>
                    <tr><td>E-205 Exchanger</td><td><span class="badge bg-warning text-dark">Med-High</span></td><td>$156,000</td><td>$952,000</td><td>$30,000</td><td>$46,800</td><td class="text-success fw-bold">56%</td></tr>
                    <tr><td>P-302A Pump</td><td><span class="badge bg-info">Medium</span></td><td>$49,000</td><td>$299,000</td><td>$15,000</td><td>$14,700</td><td class="text-warning fw-bold">-2%</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
// Cost Comparison Chart
new Chart(document.getElementById("costComparisonChart"), {
    type: "bar",
    data: {
        labels: ["T-401","V-101","C-102","E-205","P-302A"],
        datasets: [
            { label: "Cost of Failure ($K)", data: [892, 425, 380, 156, 49], backgroundColor: "rgba(220,53,69,.7)", borderRadius: 4 },
            { label: "Cost of Inspection ($K)", data: [45, 35, 55, 30, 15], backgroundColor: "rgba(52,152,219,.7)", borderRadius: 4 }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom" } }, scales: { y: { beginAtZero: true, title: { display: true, text: "Cost ($K)" } } } }
});

// Risk-Spend Optimization Chart
new Chart(document.getElementById("riskSpendChart"), {
    type: "scatter",
    data: {
        datasets: [{
            label: "Assets",
            data: [
                {x: 45, y: 892, r: 15}, {x: 35, y: 425, r: 12}, {x: 55, y: 380, r: 11},
                {x: 30, y: 156, r: 8}, {x: 15, y: 49, r: 5}
            ],
            backgroundColor: ["#dc3545","#dc3545","#dc3545","#fd7e14","#ffc107"],
            pointRadius: [15,12,11,8,5]
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        scales: {
            x: { title: { display: true, text: "Inspection Cost ($K)" }, beginAtZero: true },
            y: { title: { display: true, text: "Annual Expected Loss ($K)" }, beginAtZero: true }
        },
        plugins: { legend: { display: false } }
    }
});

// NPV Chart
let npvChart;
function calcNPV() {
    let rate = parseFloat(document.getElementById("npv-rate").value) / 100;
    let years = parseInt(document.getElementById("npv-years").value);
    let cost = parseFloat(document.getElementById("npv-cost").value);
    let riskReduction = cost * 3;

    let labels = [], withInsp = [], withoutInsp = [];
    let cumWith = 0, cumWithout = 0;
    for(let y = 1; y <= years; y++) {
        labels.push("Year " + y);
        let discount = Math.pow(1 + rate, y);
        cumWithout += (riskReduction + cost * 5) / discount;
        cumWith += cost / discount;
        withoutInsp.push(Math.round(cumWithout / 1000));
        withInsp.push(Math.round(cumWith / 1000));
    }

    if(npvChart) npvChart.destroy();
    npvChart = new Chart(document.getElementById("npvChart"), {
        type: "line",
        data: {
            labels: labels,
            datasets: [
                { label: "Without Inspection (Risk Cost)", data: withoutInsp, borderColor: "#dc3545", backgroundColor: "rgba(220,53,69,.1)", fill: true, tension: 0.3 },
                { label: "With Inspection (Inspection Cost)", data: withInsp, borderColor: "#28a745", backgroundColor: "rgba(40,167,69,.1)", fill: true, tension: 0.3 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { title: { display: true, text: "Cumulative NPV ($K)" } } }, plugins: { legend: { position: "bottom" } } }
    });
}
calcNPV();
</script>';
require_once INCLUDES_PATH . '/footer.php';
