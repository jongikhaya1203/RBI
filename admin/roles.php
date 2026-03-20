<?php
/**
 * Role Management - RBI Engineering Suite
 */
$pageTitle = 'Role Management';
$currentModule = 'admin';
require_once __DIR__ . '/../config/app.php';
requireAuth();

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Administration', 'url' => '#'],
    ['label' => 'Roles & Permissions', 'url' => '#']
];

$db = new Database();
// Fetch roles along with a count of currently assigned users
$roles = $db->query("
    SELECT r.*, COUNT(u.id) as user_count 
    FROM roles r 
    LEFT JOIN users u ON r.id = u.role_id 
    GROUP BY r.id 
    ORDER BY r.is_system_role DESC, r.role_name ASC
")->fetchAll();

require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Roles & Permissions</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRoleModal">
        <i class="bi bi-plus-circle me-1"></i>Create Role
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="rolesTable" class="table table-hover align-middle" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>Role Name</th>
                        <th>Role Key</th>
                        <th>Description</th>
                        <th class="text-center">Active Users</th>
                        <th>Type</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($role['role_name']) ?></td>
                            <td><code><?= htmlspecialchars($role['role_key']) ?></code></td>
                            <td><?= htmlspecialchars($role['description']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-pill"><?= $role['user_count'] ?></span>
                            </td>
                            <td>
                                <?php if ($role['is_system_role']): ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">System</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">Custom</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Manage Permissions"><i class="bi bi-key"></i></button>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Edit Role"><i class="bi bi-pencil"></i></button>
                                <?php if (!$role['is_system_role']): ?>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete Role"><i class="bi bi-trash"></i></button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-danger disabled" data-bs-toggle="tooltip" title="System roles cannot be deleted"><i class="bi bi-trash"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-shield-plus me-2"></i>Create New Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addRoleForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="role_name" placeholder="e.g. Regional Manager" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role Key <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="role_key" placeholder="e.g. regional_manager" required>
                        <div class="form-text">Unique identifier, lowercase with underscores.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Briefly describe the responsibilities of this role..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Role</button>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof $ !== "undefined" && $.fn.DataTable) {
        $("#rolesTable").DataTable({
            pageLength: 25,
            order: [[4, "desc"], [0, "asc"]],
            language: { search: "Search roles:" },
            columnDefs: [{ orderable: false, targets: 5 }]
        });
    }
});
</script>';
require_once INCLUDES_PATH . '/footer.php';