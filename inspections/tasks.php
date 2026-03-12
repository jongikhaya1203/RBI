<?php
/**
 * Inspection Tasks - RBI Engineering Suite
 */
$pageTitle = 'Inspection Tasks';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Inspection Tasks', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-list-task me-2"></i>Inspection Tasks</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaskModal">
        <i class="bi bi-plus-lg me-1"></i>Create Task
    </button>
</div>

<!-- Status Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card text-center py-3"><div class="text-warning fs-3 fw-bold">12</div><div class="small text-muted">Pending</div></div></div>
    <div class="col-md-3"><div class="card text-center py-3"><div class="text-info fs-3 fw-bold">5</div><div class="small text-muted">In Progress</div></div></div>
    <div class="col-md-3"><div class="card text-center py-3"><div class="text-success fs-3 fw-bold">48</div><div class="small text-muted">Completed</div></div></div>
    <div class="col-md-3"><div class="card text-center py-3"><div class="text-danger fs-3 fw-bold">3</div><div class="small text-muted">Overdue</div></div></div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Status</label>
                <select class="form-select form-select-sm" id="filter-status">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Assigned To</label>
                <select class="form-select form-select-sm" id="filter-assignee">
                    <option value="">All Inspectors</option>
                    <option>John Smith</option>
                    <option>Sarah Jones</option>
                    <option>Mike Chen</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Due Date From</label>
                <input type="date" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Due Date To</label>
                <input type="date" class="form-control form-control-sm">
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tasksTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr><th>Task</th><th>Asset</th><th>Strategy</th><th>Assigned To</th><th>Due Date</th><th>Priority</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <tr><td>UT Grid Survey</td><td>V-101 Separator</td><td>ut_grid</td><td>John Smith</td><td>Mar 18, 2026</td><td><span class="badge bg-danger">High</span></td><td><span class="badge bg-warning text-dark">Pending</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button> <button class="btn btn-sm btn-outline-success" title="Record Findings"><i class="bi bi-check-lg"></i></button></td></tr>
                    <tr><td>PAUT Weld Inspection</td><td>E-205 Exchanger</td><td>paut</td><td>Sarah Jones</td><td>Mar 22, 2026</td><td><span class="badge bg-warning text-dark">Medium</span></td><td><span class="badge bg-info">In Progress</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button> <button class="btn btn-sm btn-outline-success"><i class="bi bi-check-lg"></i></button></td></tr>
                    <tr><td>Visual + UT Spot</td><td>P-302A Pump</td><td>ut_spot</td><td>Mike Chen</td><td>Mar 28, 2026</td><td><span class="badge bg-info">Normal</span></td><td><span class="badge bg-warning text-dark">Pending</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button> <button class="btn btn-sm btn-outline-success"><i class="bi bi-check-lg"></i></button></td></tr>
                    <tr><td>MFL Floor Scan</td><td>T-401 Tank</td><td>mfl</td><td>John Smith</td><td>Apr 02, 2026</td><td><span class="badge bg-danger">High</span></td><td><span class="badge bg-warning text-dark">Pending</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button> <button class="btn btn-sm btn-outline-success"><i class="bi bi-check-lg"></i></button></td></tr>
                    <tr><td>External CUI Check</td><td>V-101 Separator</td><td>visual</td><td>Mike Chen</td><td>Feb 28, 2026</td><td><span class="badge bg-info">Normal</span></td><td><span class="badge bg-danger">Overdue</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button> <button class="btn btn-sm btn-outline-success"><i class="bi bi-check-lg"></i></button></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Create Inspection Task</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Asset</label><select name="asset_id" class="form-select" required><option value="">Select...</option><option>V-101 Separator</option><option>E-205 Exchanger</option><option>T-401 Tank</option></select></div>
                        <div class="col-md-6"><label class="form-label">Inspection Plan</label><select name="plan_id" class="form-select"><option value="">Select plan...</option><option>RBI Plan - V-101</option></select></div>
                        <div class="col-md-4"><label class="form-label">Strategy</label><select name="strategy" class="form-select"><option>UT Grid</option><option>UT Spot</option><option>PAUT</option><option>Visual</option><option>MFL</option></select></div>
                        <div class="col-md-4"><label class="form-label">Due Date</label><input type="date" name="due_date" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Assigned To</label><select name="assigned_to" class="form-select"><option>John Smith</option><option>Sarah Jones</option><option>Mike Chen</option></select></div>
                        <div class="col-md-4"><label class="form-label">Priority</label><select name="priority" class="form-select"><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
                        <div class="col-md-4"><label class="form-label">Estimated Hours</label><input type="number" name="estimated_hours" class="form-control" value="8"></div>
                        <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Create Task</button></div>
            </form>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>$(document).ready(function(){$("#tasksTable").DataTable({pageLength:25,order:[[4,"asc"]]});});</script>';
require_once INCLUDES_PATH . '/footer.php';
