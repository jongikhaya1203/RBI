<?php
/**
 * Risk Assessments - RBI Engineering Suite
 */
$pageTitle = 'Risk Assessments';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Risk Assessments', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Risk Assessments</h5>
    <a href="<?= BASE_URL ?>/risk/calculate.php" class="btn btn-primary">
        <i class="bi bi-calculator me-1"></i>Run New Assessment
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Date Range</label>
                <input type="date" class="form-control form-control-sm" id="filter-from" value="2025-01-01">
            </div>
            <div class="col-md-3">
                <label class="form-label small">To</label>
                <input type="date" class="form-control form-control-sm" id="filter-to">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Risk Level</label>
                <select class="form-select form-select-sm" id="filter-risk">
                    <option value="">All Levels</option>
                    <option value="VH">Very High</option>
                    <option value="H">High</option>
                    <option value="MH">Medium-High</option>
                    <option value="M">Medium</option>
                    <option value="L">Low</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Asset Type</label>
                <select class="form-select form-select-sm" id="filter-type">
                    <option value="">All Types</option>
                    <option value="pressure_vessel">Pressure Vessel</option>
                    <option value="piping">Piping</option>
                    <option value="storage_tank">Storage Tank</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Apply</button>
            </div>
        </div>
    </div>
</div>

<!-- Assessments Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="assessmentsTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr><th>Date</th><th>Asset</th><th>Type</th><th>POF</th><th>COF</th><th>Risk Level</th><th>Risk Value</th><th>Assessed By</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <tr><td>Jan 20, 2026</td><td>V-101 Inlet Separator</td><td>Pressure Vessel</td><td><span class="badge bg-danger">4 - Likely</span></td><td><span class="badge bg-warning text-dark">D - Med-High</span></td><td><span class="badge bg-danger">High</span></td><td>0.0845</td><td>J. Smith</td><td><a href="<?= BASE_URL ?>/assets/view.php?id=1#risk-history" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td></tr>
                    <tr><td>Jan 18, 2026</td><td>T-401 Storage Tank</td><td>Storage Tank</td><td><span class="badge bg-danger">5 - V.Likely</span></td><td><span class="badge bg-danger">D - Med-High</span></td><td><span class="badge bg-danger">Very High</span></td><td>0.1523</td><td>S. Jones</td><td><a href="#" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td></tr>
                    <tr><td>Jan 15, 2026</td><td>E-205 Heat Exchanger</td><td>Heat Exchanger</td><td><span class="badge bg-warning text-dark">3 - Possible</span></td><td><span class="badge bg-warning text-dark">C - Medium</span></td><td><span class="badge bg-warning text-dark">Medium-High</span></td><td>0.0312</td><td>J. Smith</td><td><a href="#" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td></tr>
                    <tr><td>Jan 12, 2026</td><td>P-302A Pump</td><td>Pump</td><td><span class="badge bg-info">2 - Unlikely</span></td><td><span class="badge bg-info">B - Med-Low</span></td><td><span class="badge bg-info">Medium</span></td><td>0.0098</td><td>M. Chen</td><td><a href="#" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td></tr>
                    <tr><td>Jan 10, 2026</td><td>C-102 Column</td><td>Column</td><td><span class="badge bg-warning text-dark">3 - Possible</span></td><td><span class="badge bg-warning text-dark">D - Med-High</span></td><td><span class="badge bg-danger">High</span></td><td>0.0654</td><td>S. Jones</td><td><a href="#" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
$(document).ready(function() {
    $("#assessmentsTable").DataTable({ pageLength: 25, order: [[0, "desc"]] });
});
</script>';
require_once INCLUDES_PATH . '/footer.php';
