<?php
/**
 * Audit Log - RBI Engineering Suite
 */
$pageTitle = 'Audit Log';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Audit Log', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Audit Trail</h5>
    <button class="btn btn-outline-primary btn-sm"><i class="bi bi-download me-1"></i>Export Log</button>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">User</label>
                <select class="form-select form-select-sm">
                    <option value="">All Users</option>
                    <option>John Smith</option>
                    <option>Sarah Jones</option>
                    <option>Mike Chen</option>
                    <option>Robert Williams</option>
                    <option>System</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Action Type</label>
                <select class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <option>login</option>
                    <option>logout</option>
                    <option>create</option>
                    <option>update</option>
                    <option>delete</option>
                    <option>assessment</option>
                    <option>export</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Module</label>
                <select class="form-select form-select-sm">
                    <option value="">All Modules</option>
                    <option>auth</option>
                    <option>assets</option>
                    <option>risk</option>
                    <option>inspections</option>
                    <option>reports</option>
                    <option>admin</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input type="date" class="form-control form-control-sm" value="2026-03-01">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input type="date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i></button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="auditTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr><th>Timestamp</th><th>User</th><th>Action</th><th>Module</th><th>Entity</th><th>Details</th><th>IP Address</th></tr>
                </thead>
                <tbody>
                    <tr><td>Mar 11, 2026 10:30:15</td><td>John Smith</td><td><span class="badge bg-primary">assessment</span></td><td>risk</td><td>V-101</td><td>Risk assessment completed - Level: High</td><td>192.168.1.45</td></tr>
                    <tr><td>Mar 11, 2026 09:30:02</td><td>John Smith</td><td><span class="badge bg-success">login</span></td><td>auth</td><td>--</td><td>Successful login</td><td>192.168.1.45</td></tr>
                    <tr><td>Mar 11, 2026 09:15:33</td><td>System</td><td><span class="badge bg-info">sync</span></td><td>integrations</td><td>CMMS</td><td>CMMS sync completed - 1,247 records</td><td>127.0.0.1</td></tr>
                    <tr><td>Mar 10, 2026 16:45:11</td><td>Sarah Jones</td><td><span class="badge bg-warning text-dark">update</span></td><td>assets</td><td>E-205</td><td>Updated operational data - temperature changed</td><td>192.168.1.52</td></tr>
                    <tr><td>Mar 10, 2026 16:20:05</td><td>Mike Chen</td><td><span class="badge bg-secondary">logout</span></td><td>auth</td><td>--</td><td>User logged out</td><td>192.168.1.60</td></tr>
                    <tr><td>Mar 10, 2026 15:30:22</td><td>Mike Chen</td><td><span class="badge bg-success">create</span></td><td>inspections</td><td>Task #127</td><td>Created inspection task for P-302A</td><td>192.168.1.60</td></tr>
                    <tr><td>Mar 10, 2026 14:10:18</td><td>Robert Williams</td><td><span class="badge bg-info">export</span></td><td>reports</td><td>Risk Register</td><td>Generated Risk Register Q1 2026 (PDF)</td><td>192.168.1.48</td></tr>
                    <tr><td>Mar 10, 2026 11:25:44</td><td>Sarah Jones</td><td><span class="badge bg-danger">delete</span></td><td>assets</td><td>V-999</td><td>Decommissioned asset V-999 Test Vessel</td><td>192.168.1.52</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>$(document).ready(function(){$("#auditTable").DataTable({pageLength:25,order:[[0,"desc"]]});});</script>';
require_once INCLUDES_PATH . '/footer.php';
