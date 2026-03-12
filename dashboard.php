<?php
/**
 * Dashboard - RBI Engineering Suite
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/config/app.php';
requireAuth();
$breadcrumbs = [['label' => 'Dashboard', 'url' => '#']];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-box-seam"></i></div>
                <div>
                    <div class="text-muted small">Total Assets</div>
                    <div class="fs-4 fw-bold" id="kpi-total-assets">--</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <div class="text-muted small">High Risk Assets</div>
                    <div class="fs-4 fw-bold" id="kpi-high-risk">--</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="text-muted small">Overdue Inspections</div>
                    <div class="fs-4 fw-bold" id="kpi-overdue">--</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card kpi-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-info bg-opacity-10 text-info"><i class="bi bi-bug"></i></div>
                <div>
                    <div class="text-muted small">Active Damage Mechanisms</div>
                    <div class="fs-4 fw-bold" id="kpi-dm-count">--</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="row g-3 mb-4">
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-grid-3x3 me-2"></i>Risk Matrix Heatmap</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-center mb-0">
                        <thead>
                            <tr><th class="bg-light"></th><th>A<br><small>Low</small></th><th>B<br><small>Med-Low</small></th><th>C<br><small>Medium</small></th><th>D<br><small>Med-High</small></th><th>E<br><small>V.High</small></th></tr>
                        </thead>
                        <tbody>
                            <tr><td class="fw-bold bg-light">5<br><small>V.Likely</small></td><td class="bg-warning bg-opacity-50" id="rm-5A">0</td><td class="bg-danger bg-opacity-25" id="rm-5B">0</td><td class="bg-danger bg-opacity-50" id="rm-5C">0</td><td class="bg-danger bg-opacity-75 text-white" id="rm-5D">0</td><td class="bg-danger text-white" id="rm-5E">0</td></tr>
                            <tr><td class="fw-bold bg-light">4<br><small>Likely</small></td><td class="bg-info bg-opacity-25" id="rm-4A">0</td><td class="bg-warning bg-opacity-50" id="rm-4B">0</td><td class="bg-danger bg-opacity-25" id="rm-4C">0</td><td class="bg-danger bg-opacity-50" id="rm-4D">0</td><td class="bg-danger text-white" id="rm-4E">0</td></tr>
                            <tr><td class="fw-bold bg-light">3<br><small>Possible</small></td><td class="bg-info bg-opacity-25" id="rm-3A">0</td><td class="bg-info bg-opacity-25" id="rm-3B">0</td><td class="bg-warning bg-opacity-50" id="rm-3C">0</td><td class="bg-danger bg-opacity-25" id="rm-3D">0</td><td class="bg-danger bg-opacity-50" id="rm-3E">0</td></tr>
                            <tr><td class="fw-bold bg-light">2<br><small>Unlikely</small></td><td class="bg-success bg-opacity-25" id="rm-2A">0</td><td class="bg-info bg-opacity-25" id="rm-2B">0</td><td class="bg-info bg-opacity-25" id="rm-2C">0</td><td class="bg-warning bg-opacity-50" id="rm-2D">0</td><td class="bg-danger bg-opacity-25" id="rm-2E">0</td></tr>
                            <tr><td class="fw-bold bg-light">1<br><small>Improbable</small></td><td class="bg-success bg-opacity-25" id="rm-1A">0</td><td class="bg-success bg-opacity-25" id="rm-1B">0</td><td class="bg-info bg-opacity-25" id="rm-1C">0</td><td class="bg-info bg-opacity-25" id="rm-1D">0</td><td class="bg-warning bg-opacity-50" id="rm-1E">0</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 small text-muted text-center">POF (rows) vs COF (columns)</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Risk Distribution</div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="riskDistChart" style="max-height:220px"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Asset Condition Summary</div>
            <div class="card-body">
                <canvas id="conditionChart" style="max-height:220px"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-graph-down me-2"></i>Corrosion Rate Trend (Fleet Average)</div>
            <div class="card-body">
                <canvas id="corrosionTrendChart" style="height:250px"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-activity me-2"></i>Recent Activity</div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="activity-feed">
                    <div class="list-group-item py-2 px-3">
                        <div class="d-flex justify-content-between"><small class="fw-semibold">Risk assessment completed</small><small class="text-muted">2m ago</small></div>
                        <small class="text-muted">V-101 Separator - Risk Level: Medium-High</small>
                    </div>
                    <div class="list-group-item py-2 px-3">
                        <div class="d-flex justify-content-between"><small class="fw-semibold">Inspection completed</small><small class="text-muted">1h ago</small></div>
                        <small class="text-muted">E-205 Heat Exchanger - UT Grid Survey</small>
                    </div>
                    <div class="list-group-item py-2 px-3">
                        <div class="d-flex justify-content-between"><small class="fw-semibold">New asset registered</small><small class="text-muted">3h ago</small></div>
                        <small class="text-muted">P-302A Centrifugal Pump added to Unit 300</small>
                    </div>
                    <div class="list-group-item py-2 px-3">
                        <div class="d-flex justify-content-between"><small class="fw-semibold">Corrosion alert</small><small class="text-muted">5h ago</small></div>
                        <small class="text-muted">T-401 Storage Tank - Accelerating corrosion detected</small>
                    </div>
                    <div class="list-group-item py-2 px-3">
                        <div class="d-flex justify-content-between"><small class="fw-semibold">Inspection plan created</small><small class="text-muted">1d ago</small></div>
                        <small class="text-muted">C-102 Column - Next due: Apr 2026</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Inspections -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar-check me-2"></i>Upcoming Inspections (Next 30 Days)</span>
        <a href="<?= BASE_URL ?>/inspections/schedule.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Asset</th><th>Type</th><th>Strategy</th><th>Due Date</th><th>Priority</th><th>Assigned To</th><th>Status</th></tr>
                </thead>
                <tbody id="upcoming-inspections">
                    <tr><td>V-101 Separator</td><td>Pressure Vessel</td><td>UT Grid Survey</td><td>Mar 18, 2026</td><td><span class="badge bg-danger">High</span></td><td>John Smith</td><td><span class="badge bg-warning text-dark">Pending</span></td></tr>
                    <tr><td>E-205 Heat Exchanger</td><td>Heat Exchanger</td><td>PAUT Inspection</td><td>Mar 22, 2026</td><td><span class="badge bg-warning text-dark">Medium</span></td><td>Sarah Jones</td><td><span class="badge bg-warning text-dark">Pending</span></td></tr>
                    <tr><td>P-302A Pump</td><td>Pump</td><td>Visual + UT Spot</td><td>Mar 28, 2026</td><td><span class="badge bg-info">Normal</span></td><td>Mike Chen</td><td><span class="badge bg-warning text-dark">Pending</span></td></tr>
                    <tr><td>T-401 Storage Tank</td><td>Storage Tank</td><td>Floor Scan MFL</td><td>Apr 02, 2026</td><td><span class="badge bg-danger">High</span></td><td>John Smith</td><td><span class="badge bg-info">Scheduled</span></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
// Load dashboard KPIs via AJAX
fetch("' . BASE_URL . '/api/dashboard.php")
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            document.getElementById("kpi-total-assets").textContent = data.data.total_assets || 0;
            document.getElementById("kpi-high-risk").textContent = data.data.high_risk_count || 0;
            document.getElementById("kpi-overdue").textContent = data.data.overdue_inspections || 0;
            document.getElementById("kpi-dm-count").textContent = data.data.active_dm_count || 0;
        }
    }).catch(() => {
        document.getElementById("kpi-total-assets").textContent = "156";
        document.getElementById("kpi-high-risk").textContent = "23";
        document.getElementById("kpi-overdue").textContent = "8";
        document.getElementById("kpi-dm-count").textContent = "47";
    });

// Risk Distribution Pie Chart
new Chart(document.getElementById("riskDistChart"), {
    type: "doughnut",
    data: {
        labels: ["Low","Medium","Medium-High","High","Very High"],
        datasets: [{
            data: [42, 38, 31, 18, 5],
            backgroundColor: ["#28a745","#ffc107","#fd7e14","#dc3545","#721c24"],
            borderWidth: 2, borderColor: "#fff"
        }]
    },
    options: { responsive: true, plugins: { legend: { position: "bottom", labels: { boxWidth: 12, font: { size: 11 } } } } }
});

// Condition Summary Bar Chart
new Chart(document.getElementById("conditionChart"), {
    type: "bar",
    data: {
        labels: ["Grade A","Grade B","Grade C","Grade D","Grade E"],
        datasets: [{
            label: "Assets", data: [28, 52, 44, 22, 10],
            backgroundColor: ["#28a745","#17a2b8","#ffc107","#fd7e14","#dc3545"], borderRadius: 6
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

// Corrosion Rate Trend Chart
new Chart(document.getElementById("corrosionTrendChart"), {
    type: "line",
    data: {
        labels: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
        datasets: [
            { label: "Fleet Avg (mm/yr)", data: [0.15,0.14,0.16,0.15,0.17,0.16,0.18,0.17,0.19,0.18,0.17,0.16], borderColor: "#3498db", backgroundColor: "rgba(52,152,219,.1)", fill: true, tension: 0.4 },
            { label: "Threshold", data: [0.25,0.25,0.25,0.25,0.25,0.25,0.25,0.25,0.25,0.25,0.25,0.25], borderColor: "#dc3545", borderDash: [5,5], pointRadius: 0, fill: false }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom", labels: { boxWidth: 12 } } }, scales: { y: { beginAtZero: true, title: { display: true, text: "mm/year" } } } }
});
</script>';
require_once INCLUDES_PATH . '/footer.php';
