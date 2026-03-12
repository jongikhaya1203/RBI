<?php
/**
 * Corrosion Circuit Management - RBI Engineering Suite
 */
$pageTitle = 'Corrosion Circuits';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Corrosion Circuits', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-bezier2 me-2"></i>Corrosion Circuits</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCircuitModal">
        <i class="bi bi-plus-lg me-1"></i>Add Circuit
    </button>
</div>

<div class="row g-3">
    <!-- Circuit CC-101 -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">CC-101 - Crude Feed Circuit</span>
                <span class="badge bg-danger">High Risk</span>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-4"><small class="text-muted d-block">Assets</small><span class="fw-semibold">8</span></div>
                    <div class="col-4"><small class="text-muted d-block">Avg Corrosion Rate</small><span class="fw-semibold">0.18 mm/yr</span></div>
                    <div class="col-4"><small class="text-muted d-block">Min Remaining Life</small><span class="fw-semibold text-warning">12.5 yrs</span></div>
                </div>
                <h6 class="small text-muted mb-2">Assigned Assets:</h6>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 py-1 d-flex justify-content-between">
                        <span><i class="bi bi-box me-1 text-primary"></i>V-101 Inlet Separator</span>
                        <span class="badge bg-danger">High</span>
                    </div>
                    <div class="list-group-item px-0 py-1 d-flex justify-content-between">
                        <span><i class="bi bi-box me-1 text-primary"></i>C-102 Atmospheric Column</span>
                        <span class="badge bg-warning text-dark">Med-High</span>
                    </div>
                    <div class="list-group-item px-0 py-1 d-flex justify-content-between">
                        <span><i class="bi bi-box me-1 text-primary"></i>E-103 Preheater</span>
                        <span class="badge bg-info">Medium</span>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil me-1"></i>Edit</button>
                    <a href="<?= BASE_URL ?>/assets/view.php?circuit=101" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i>View Details</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Circuit CC-201 -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">CC-201 - Reformer Reactor Loop</span>
                <span class="badge bg-warning text-dark">Medium-High Risk</span>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-4"><small class="text-muted d-block">Assets</small><span class="fw-semibold">5</span></div>
                    <div class="col-4"><small class="text-muted d-block">Avg Corrosion Rate</small><span class="fw-semibold">0.12 mm/yr</span></div>
                    <div class="col-4"><small class="text-muted d-block">Min Remaining Life</small><span class="fw-semibold">18.3 yrs</span></div>
                </div>
                <h6 class="small text-muted mb-2">Assigned Assets:</h6>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 py-1 d-flex justify-content-between">
                        <span><i class="bi bi-box me-1 text-primary"></i>E-205 Feed/Effluent Exchanger</span>
                        <span class="badge bg-warning text-dark">Med-High</span>
                    </div>
                    <div class="list-group-item px-0 py-1 d-flex justify-content-between">
                        <span><i class="bi bi-box me-1 text-primary"></i>R-201 Reactor</span>
                        <span class="badge bg-danger">High</span>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil me-1"></i>Edit</button>
                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i>View Details</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Circuit CC-301 -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">CC-301 - HDS Reactor Loop</span>
                <span class="badge bg-info">Medium Risk</span>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-4"><small class="text-muted d-block">Assets</small><span class="fw-semibold">6</span></div>
                    <div class="col-4"><small class="text-muted d-block">Avg Corrosion Rate</small><span class="fw-semibold">0.08 mm/yr</span></div>
                    <div class="col-4"><small class="text-muted d-block">Min Remaining Life</small><span class="fw-semibold text-success">25.1 yrs</span></div>
                </div>
                <h6 class="small text-muted mb-2">Assigned Assets:</h6>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 py-1 d-flex justify-content-between">
                        <span><i class="bi bi-box me-1 text-primary"></i>P-302A HDS Feed Pump</span>
                        <span class="badge bg-info">Medium</span>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil me-1"></i>Edit</button>
                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i>View Details</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Circuit CC-401 -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">CC-401 - Tank Farm</span>
                <span class="badge bg-danger">Very High Risk</span>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-4"><small class="text-muted d-block">Assets</small><span class="fw-semibold">4</span></div>
                    <div class="col-4"><small class="text-muted d-block">Avg Corrosion Rate</small><span class="fw-semibold text-danger">0.32 mm/yr</span></div>
                    <div class="col-4"><small class="text-muted d-block">Min Remaining Life</small><span class="fw-semibold text-danger">5.2 yrs</span></div>
                </div>
                <h6 class="small text-muted mb-2">Assigned Assets:</h6>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 py-1 d-flex justify-content-between">
                        <span><i class="bi bi-box me-1 text-primary"></i>T-401 Crude Storage Tank</span>
                        <span class="badge bg-danger">V.High</span>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil me-1"></i>Edit</button>
                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i>View Details</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Circuit Modal -->
<div class="modal fade" id="addCircuitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Corrosion Circuit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Circuit ID</label>
                        <input type="text" name="circuit_id" class="form-control" placeholder="e.g. CC-501" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Circuit Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Amine Treatment Loop" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign Assets</label>
                        <select name="assets[]" class="form-select" multiple size="5">
                            <option value="1">V-101 Inlet Separator</option>
                            <option value="2">E-205 Feed/Effluent Exchanger</option>
                            <option value="3">T-401 Crude Storage Tank</option>
                            <option value="4">P-302A HDS Feed Pump</option>
                            <option value="5">C-102 Atmospheric Column</option>
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Circuit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
