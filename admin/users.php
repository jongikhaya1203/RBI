<?php
/**
 * User Management - RBI Engineering Suite
 */
$pageTitle = 'User Management';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Administration', 'url' => '#'],
    ['label' => 'Users', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-people me-2"></i>User Management</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus me-1"></i>Add User
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="usersTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <tr><td><div class="d-flex align-items-center gap-2"><div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:.75rem">JS</div> John Smith</div></td><td>john.smith@company.com</td><td><span class="badge bg-danger">Admin</span></td><td>Engineering</td><td><span class="badge bg-success">Active</span></td><td>Mar 11, 2026 09:30</td><td><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                    <tr><td><div class="d-flex align-items-center gap-2"><div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:.75rem">SJ</div> Sarah Jones</div></td><td>sarah.jones@company.com</td><td><span class="badge bg-primary">Engineer</span></td><td>Integrity</td><td><span class="badge bg-success">Active</span></td><td>Mar 11, 2026 08:45</td><td><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                    <tr><td><div class="d-flex align-items-center gap-2"><div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:.75rem">MC</div> Mike Chen</div></td><td>mike.chen@company.com</td><td><span class="badge bg-info">Inspector</span></td><td>Inspection</td><td><span class="badge bg-success">Active</span></td><td>Mar 10, 2026 16:20</td><td><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                    <tr><td><div class="d-flex align-items-center gap-2"><div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:.75rem">RW</div> Robert Williams</div></td><td>r.williams@company.com</td><td><span class="badge bg-warning text-dark">Manager</span></td><td>Operations</td><td><span class="badge bg-success">Active</span></td><td>Mar 09, 2026 14:10</td><td><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                    <tr><td><div class="d-flex align-items-center gap-2"><div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:.75rem">LP</div> Lisa Park</div></td><td>l.park@company.com</td><td><span class="badge bg-secondary">Viewer</span></td><td>Management</td><td><span class="badge bg-warning text-dark">Inactive</span></td><td>Feb 20, 2026</td><td><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add New User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Role</label><select name="role" class="form-select"><option value="viewer">Viewer</option><option value="inspector">Inspector</option><option value="engineer">Engineer</option><option value="manager">Manager</option><option value="admin">Admin</option></select></div>
                        <div class="col-md-6"><label class="form-label">Department</label><input type="text" name="department" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="8"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Create User</button></div>
            </form>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>$(document).ready(function(){$("#usersTable").DataTable({pageLength:25,order:[[0,"asc"]]});});</script>';
require_once INCLUDES_PATH . '/footer.php';
