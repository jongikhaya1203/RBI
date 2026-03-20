<?php
/**
 * Damage Mechanisms Library - RBI Engineering Suite
 */
$pageTitle = 'Damage Mechanisms';
$currentModule = 'damage_mechanisms';
require_once __DIR__ . '/config/app.php';
requireAuth();

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Damage Mechanisms', 'url' => '#']
];

$db = new Database();
// Fetch all damage mechanisms
$mechanisms = $db->query("
    SELECT id, dm_code, dm_name, category, api_571_reference, default_susceptibility, is_active 
    FROM damage_mechanisms 
    ORDER BY category ASC, sort_order ASC
")->fetchAll();

require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-bug me-2"></i>Damage Mechanisms Library</h5>
    <button class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Add Mechanism
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="dmTable" class="table table-hover table-striped align-middle" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>API 571 Ref</th>
                        <th>Default Susceptibility</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mechanisms as $dm): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($dm['dm_code']) ?></td>
                            <td><?= htmlspecialchars($dm['dm_name']) ?></td>
                            <td><?= ucwords(str_replace('_', ' ', $dm['category'])) ?></td>
                            <td><?= htmlspecialchars($dm['api_571_reference'] ?? '--') ?></td>
                            <td>
                                <?php
                                $suscColors = ['high' => 'danger', 'medium' => 'warning text-dark', 'low' => 'info', 'none' => 'success'];
                                $sc = $suscColors[$dm['default_susceptibility']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $sc ?>"><?= ucfirst($dm['default_susceptibility']) ?></span>
                            </td>
                            <td>
                                <?php if ($dm['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View Details"><i class="bi bi-eye"></i></button>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Edit"><i class="bi bi-pencil"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof $ !== "undefined" && $.fn.DataTable) {
        $("#dmTable").DataTable({
            pageLength: 25,
            order: [[2, "asc"], [0, "asc"]]
        });
    }
});
</script>';
require_once INCLUDES_PATH . '/footer.php';