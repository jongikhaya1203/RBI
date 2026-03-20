<?php
/**
 * Asset Registry - RBI Engineering Suite
 */
$pageTitle = 'Asset Registry';
require_once __DIR__ . '/../config/app.php';
requireAuth();

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Assets', 'url' => '#'],
    ['label' => 'Registry', 'url' => BASE_URL . '/assets/registry.php']
];

$db = new Database();
// Fetch all assets along with their hierarchical location
$assets = $db->query("
    SELECT ar.id, ar.asset_tag, ar.asset_name, ar.asset_type, ar.status, ar.criticality, ar.rbi_status,
           eh.name as unit_name
    FROM asset_registry ar
    LEFT JOIN equipment_hierarchy eh ON ar.hierarchy_id = eh.id
    ORDER BY ar.asset_tag ASC
")->fetchAll();

require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Asset Registry</h5>
    <button class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Asset</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="assetRegistryTable" class="table table-hover table-striped align-middle" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>Tag</th>
                        <th>Name</th>
                        <th>Unit / Location</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Criticality</th>
                        <th>RBI Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $asset): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($asset['asset_tag']) ?></td>
                            <td><?= htmlspecialchars($asset['asset_name']) ?></td>
                            <td><?= htmlspecialchars($asset['unit_name'] ?? 'Unassigned') ?></td>
                            <td><?= ucwords(str_replace('_', ' ', $asset['asset_type'])) ?></td>
                            <td>
                                <?php
                                $statusColors = ['in_service' => 'success', 'out_of_service' => 'danger', 'mothballed' => 'warning', 'retired' => 'secondary', 'pending_install' => 'info'];
                                $sc = $statusColors[$asset['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $sc ?>"><?= ucwords(str_replace('_', ' ', $asset['status'])) ?></span>
                            </td>
                            <td>
                                <?php
                                $critColors = ['critical' => 'danger', 'high' => 'warning text-dark', 'medium' => 'info', 'low' => 'success'];
                                $cc = $critColors[$asset['criticality']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $cc ?>"><?= ucfirst($asset['criticality']) ?></span>
                            </td>
                            <td>
                                <?php
                                $rbiColors = ['assessed' => 'success', 'in_progress' => 'info', 'not_assessed' => 'secondary', 'overdue' => 'danger'];
                                $rc = $rbiColors[$asset['rbi_status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $rc ?>"><?= ucwords(str_replace('_', ' ', $asset['rbi_status'])) ?></span>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/assets/view.php?id=<?= $asset['id'] ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View Details"><i class="bi bi-eye"></i></a>
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
$extraJs = '<script>document.addEventListener("DOMContentLoaded", function() { if (typeof $ !== "undefined" && $.fn.DataTable) { $("#assetRegistryTable").DataTable(RBI_APP.getDataTableDefaults()); } });</script>';
require_once INCLUDES_PATH . '/footer.php';