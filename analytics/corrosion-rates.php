<?php
/**
 * Corrosion Rate Tracking - RBI Engineering Suite
 */
$pageTitle = 'Corrosion Rate Tracking';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Corrosion Rates', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-graph-down me-2"></i>Corrosion Rate Tracking</h5>
    <div>
        <select class="form-select form-select-sm d-inline-block" style="width:auto">
            <option>All Circuits</option>
            <option>CC-101</option>
            <option>CC-201</option>
            <option>CC-301</option>
            <option>CC-401</option>
        </select>
    </div>
</div>

<!-- Alert Cards -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="alert alert-danger d-flex align-items-center mb-0">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong>Accelerating Corrosion Detected:</strong>
                T-401 Crude Storage Tank - Short-term rate (0.42 mm/yr) significantly exceeds long-term rate (0.25 mm/yr).
                <a href="<?= BASE_URL ?>/assets/view.php?id=3" class="alert-link">View Asset</a>
            </div>
        </div>
    </div>
</div>

<!-- Rate Table -->
<div class="card mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table id="corrosionTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr><th>Asset</th><th>Type</th><th>Circuit</th><th>Short-Term Rate<br>(mm/yr)</th><th>Long-Term Rate<br>(mm/yr)</th><th>Weighted Rate<br>(mm/yr)</th><th>Trend</th><th>Data Points</th><th>Last Reading</th></tr>
                </thead>
                <tbody>
                    <tr><td>V-101 Separator</td><td>Vessel</td><td>CC-101</td><td>0.19</td><td>0.15</td><td>0.18</td><td><span class="badge bg-warning text-dark"><i class="bi bi-arrow-up"></i> Increasing</span></td><td>4</td><td>Jan 15, 2026</td></tr>
                    <tr><td>E-205 Exchanger</td><td>Heat Exchanger</td><td>CC-201</td><td>0.11</td><td>0.12</td><td>0.11</td><td><span class="badge bg-success"><i class="bi bi-arrow-right"></i> Stable</span></td><td>6</td><td>Dec 03, 2025</td></tr>
                    <tr class="table-danger"><td>T-401 Tank</td><td>Storage Tank</td><td>CC-401</td><td class="fw-bold text-danger">0.42</td><td>0.25</td><td>0.37</td><td><span class="badge bg-danger"><i class="bi bi-arrow-up"></i> Increasing</span></td><td>5</td><td>Nov 20, 2025</td></tr>
                    <tr><td>P-302A Pump</td><td>Pump</td><td>CC-301</td><td>0.06</td><td>0.08</td><td>0.07</td><td><span class="badge bg-info"><i class="bi bi-arrow-down"></i> Decreasing</span></td><td>3</td><td>Feb 10, 2026</td></tr>
                    <tr><td>C-102 Column</td><td>Column</td><td>CC-102</td><td>0.14</td><td>0.13</td><td>0.14</td><td><span class="badge bg-success"><i class="bi bi-arrow-right"></i> Stable</span></td><td>8</td><td>Oct 05, 2025</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Trend Chart -->
<div class="card">
    <div class="card-header"><i class="bi bi-graph-down me-2"></i>Corrosion Rate Trends by Asset</div>
    <div class="card-body">
        <canvas id="corrosionTrendChart" style="height:350px"></canvas>
    </div>
</div>

<?php
$extraJs = '<script>
$(document).ready(function(){
    $("#corrosionTable").DataTable({ pageLength: 25, order: [[3, "desc"]] });
});
new Chart(document.getElementById("corrosionTrendChart"), {
    type: "line",
    data: {
        labels: ["2021","2022","2023","2024","2025","2026"],
        datasets: [
            { label: "V-101", data: [0.12,0.13,0.14,0.15,0.17,0.19], borderColor: "#3498db", tension: 0.3 },
            { label: "E-205", data: [0.13,0.12,0.12,0.11,0.12,0.11], borderColor: "#2ecc71", tension: 0.3 },
            { label: "T-401", data: [0.18,0.20,0.22,0.25,0.32,0.42], borderColor: "#e74c3c", tension: 0.3 },
            { label: "P-302A", data: [0.10,0.09,0.08,0.08,0.07,0.06], borderColor: "#9b59b6", tension: 0.3 },
            { label: "C-102", data: [0.14,0.13,0.13,0.14,0.13,0.14], borderColor: "#f39c12", tension: 0.3 },
            { label: "Alert Threshold", data: [0.30,0.30,0.30,0.30,0.30,0.30], borderColor: "#dc3545", borderDash: [5,5], pointRadius: 0 }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, title: { display: true, text: "Corrosion Rate (mm/yr)" } } },
        plugins: { legend: { position: "bottom" } }
    }
});
</script>';
require_once INCLUDES_PATH . '/footer.php';
