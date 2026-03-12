<?php
/**
 * Inspection Plans - RBI Engineering Suite
 */
$pageTitle = 'Inspection Plans';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Inspection Plans', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-journal-check me-2"></i>Inspection Plans</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPlanModal">
        <i class="bi bi-plus-lg me-1"></i>Create Plan
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="plansTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr><th>Plan Name</th><th>Asset</th><th>Risk Level</th><th>Interval</th><th>Strategy</th><th>Next Due</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <tr><td>RBI Plan - V-101</td><td>V-101 Separator</td><td><span class="badge bg-danger">High</span></td><td>12 months</td><td>UT Grid + MT Welds</td><td>Jan 15, 2027</td><td><span class="badge bg-success">Active</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button> <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button></td></tr>
                    <tr><td>RBI Plan - E-205</td><td>E-205 Exchanger</td><td><span class="badge bg-warning text-dark">Med-High</span></td><td>24 months</td><td>PAUT + UT Spot</td><td>Dec 03, 2027</td><td><span class="badge bg-success">Active</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button> <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button></td></tr>
                    <tr><td>RBI Plan - T-401</td><td>T-401 Tank</td><td><span class="badge bg-danger">Very High</span></td><td>6 months</td><td>Floor Scan MFL + UT</td><td>May 20, 2026</td><td><span class="badge bg-success">Active</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button> <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button></td></tr>
                    <tr><td>RBI Plan - P-302A</td><td>P-302A Pump</td><td><span class="badge bg-info">Medium</span></td><td>48 months</td><td>Visual + Vibration</td><td>Feb 10, 2030</td><td><span class="badge bg-success">Active</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button> <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button></td></tr>
                    <tr><td>RBI Plan - C-102</td><td>C-102 Column</td><td><span class="badge bg-danger">High</span></td><td>12 months</td><td>Internal Visual + UT</td><td>Oct 05, 2026</td><td><span class="badge bg-success">Active</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button> <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Plan Modal -->
<div class="modal fade" id="createPlanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Inspection Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Asset</label>
                            <select name="asset_id" class="form-select" required>
                                <option value="">Select asset...</option>
                                <option value="1">V-101 - Inlet Separator</option>
                                <option value="2">E-205 - Feed/Effluent Exchanger</option>
                                <option value="3">T-401 - Crude Storage Tank</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Plan Name</label>
                            <input type="text" name="plan_name" class="form-control" placeholder="e.g. RBI Plan - V-101">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Interval (months)</label>
                            <input type="number" name="interval_months" class="form-control" value="12">
                            <small class="text-muted">Auto-calculated from risk level</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Strategy</label>
                            <select name="strategy" class="form-select">
                                <option>UT Grid Survey</option>
                                <option>UT Spot</option>
                                <option>PAUT</option>
                                <option>Visual + UT</option>
                                <option>MFL Floor Scan</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assigned Inspector</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">Select inspector...</option>
                                <option value="1">John Smith</option>
                                <option value="2">Sarah Jones</option>
                                <option value="3">Mike Chen</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Next Due Date</label>
                            <input type="date" name="next_due_date" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Scope / Notes</label>
                            <textarea name="scope" class="form-control" rows="3" placeholder="Describe the inspection scope, locations, and any special requirements..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>$(document).ready(function(){$("#plansTable").DataTable({pageLength:25,order:[[5,"asc"]]});});</script>';
require_once INCLUDES_PATH . '/footer.php';
