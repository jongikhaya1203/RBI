<?php
/**
 * Asset Registry - RBI Engineering Suite
 */
$pageTitle = 'Asset Registry';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Asset Registry', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Asset Registry</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal">
        <i class="bi bi-plus-lg me-1"></i>Add Asset
    </button>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Asset Type</label>
                <select class="form-select form-select-sm" id="filter-type">
                    <option value="">All Types</option>
                    <option value="pressure_vessel">Pressure Vessel</option>
                    <option value="heat_exchanger">Heat Exchanger</option>
                    <option value="storage_tank">Storage Tank</option>
                    <option value="piping">Piping</option>
                    <option value="column">Column</option>
                    <option value="reactor">Reactor</option>
                    <option value="pump">Pump</option>
                    <option value="valve">Valve</option>
                    <option value="relief_device">Relief Device</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Location / Unit</label>
                <select class="form-select form-select-sm" id="filter-location">
                    <option value="">All Locations</option>
                    <option value="unit-100">Unit 100 - Crude</option>
                    <option value="unit-200">Unit 200 - Reformer</option>
                    <option value="unit-300">Unit 300 - HDS</option>
                    <option value="unit-400">Unit 400 - Storage</option>
                </select>
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
                <label class="form-label small">Status</label>
                <select class="form-select form-select-sm" id="filter-status">
                    <option value="">All Statuses</option>
                    <option value="in_service">In Service</option>
                    <option value="out_of_service">Out of Service</option>
                    <option value="retired">Retired</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary btn-sm w-100" onclick="resetFilters()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Asset Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="assetsTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Asset ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Circuit</th>
                        <th>Risk Level</th>
                        <th>Status</th>
                        <th>Last Inspection</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>V-101</td><td>Inlet Separator</td><td>Pressure Vessel</td><td>Unit 100</td><td>CC-101</td><td><span class="badge bg-danger">High</span></td><td><span class="badge bg-success">In Service</span></td><td>Jan 15, 2026</td><td><a href="<?= BASE_URL ?>/assets/view.php?id=1" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a> <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                    <tr><td>E-205</td><td>Feed/Effluent Exchanger</td><td>Heat Exchanger</td><td>Unit 200</td><td>CC-201</td><td><span class="badge bg-warning text-dark">Medium-High</span></td><td><span class="badge bg-success">In Service</span></td><td>Dec 03, 2025</td><td><a href="<?= BASE_URL ?>/assets/view.php?id=2" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a> <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                    <tr><td>T-401</td><td>Crude Storage Tank</td><td>Storage Tank</td><td>Unit 400</td><td>CC-401</td><td><span class="badge bg-danger">Very High</span></td><td><span class="badge bg-success">In Service</span></td><td>Nov 20, 2025</td><td><a href="<?= BASE_URL ?>/assets/view.php?id=3" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a> <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                    <tr><td>P-302A</td><td>HDS Feed Pump</td><td>Pump</td><td>Unit 300</td><td>CC-301</td><td><span class="badge bg-info">Medium</span></td><td><span class="badge bg-success">In Service</span></td><td>Feb 10, 2026</td><td><a href="<?= BASE_URL ?>/assets/view.php?id=4" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a> <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                    <tr><td>C-102</td><td>Atmospheric Column</td><td>Column</td><td>Unit 100</td><td>CC-102</td><td><span class="badge bg-warning text-dark">Medium-High</span></td><td><span class="badge bg-success">In Service</span></td><td>Oct 05, 2025</td><td><a href="<?= BASE_URL ?>/assets/view.php?id=5" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a> <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Asset Modal -->
<div class="modal fade" id="addAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/api/assets.php">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Asset Tag <span class="text-danger">*</span></label>
                            <input type="text" name="asset_tag" class="form-control" required placeholder="e.g. V-101">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Asset Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Inlet Separator">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Asset Type <span class="text-danger">*</span></label>
                            <select name="asset_type" class="form-select" required>
                                <option value="">Select type...</option>
                                <option value="pressure_vessel">Pressure Vessel</option>
                                <option value="heat_exchanger">Heat Exchanger</option>
                                <option value="storage_tank">Storage Tank</option>
                                <option value="piping">Piping</option>
                                <option value="column">Column</option>
                                <option value="reactor">Reactor</option>
                                <option value="pump">Pump</option>
                                <option value="valve">Valve</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Material</label>
                            <input type="text" name="material" class="form-control" placeholder="e.g. SA-516 Gr 70">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Design Pressure (MPa)</label>
                            <input type="number" step="0.01" name="design_pressure" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Design Temp (C)</label>
                            <input type="number" step="0.1" name="design_temperature" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nominal Thickness (mm)</label>
                            <input type="number" step="0.01" name="nominal_thickness" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Min Required Thickness (mm)</label>
                            <input type="number" step="0.01" name="minimum_thickness" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Install Date</label>
                            <input type="date" name="install_date" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Criticality</label>
                            <select name="criticality" class="form-select">
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
$(document).ready(function() {
    $("#assetsTable").DataTable({
        pageLength: 25,
        order: [[0, "asc"]],
        language: { search: "Search assets:" }
    });
});
function resetFilters() {
    document.querySelectorAll("select[id^=filter]").forEach(s => s.value = "");
}
</script>';
require_once INCLUDES_PATH . '/footer.php';
