<?php
/**
 * Corrosion Circuits List - RBI Engineering Suite
 */
$pageTitle = 'Corrosion Circuits';
require_once __DIR__ . '/../config/app.php';
requireAuth();

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Assets', 'url' => BASE_URL . '/assets/index.php'],
    ['label' => 'Circuits', 'url' => '#']
];

$db = new Database();
// Fetch all circuits along with their hierarchy unit and a count of assigned assets
$circuits = $db->query("
    SELECT cc.id, cc.circuit_code, cc.circuit_name, cc.process_fluid, cc.material_spec, cc.status,
           eh.name AS unit_name,
           (SELECT COUNT(*) FROM corrosion_circuit_assets cca WHERE cca.circuit_id = cc.id) AS asset_count
    FROM corrosion_circuits cc
    LEFT JOIN equipment_hierarchy eh ON cc.hierarchy_id = eh.id
    ORDER BY cc.circuit_code ASC
")->fetchAll();

require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-bezier2 me-2"></i>Corrosion Circuits</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCircuitModal">
        <i class="bi bi-plus-circle me-1"></i>Add Circuit
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="circuitsTable" class="table table-hover table-striped align-middle" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>Circuit Code</th>
                        <th>Name</th>
                        <th>Location / Unit</th>
                        <th>Process Fluid</th>
                        <th>Material Spec</th>
                        <th class="text-center">Assets</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($circuits as $circuit): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($circuit['circuit_code']) ?></td>
                            <td><?= htmlspecialchars($circuit['circuit_name']) ?></td>
                            <td><?= htmlspecialchars($circuit['unit_name'] ?? 'Unassigned') ?></td>
                            <td><?= htmlspecialchars($circuit['process_fluid'] ?? '--') ?></td>
                            <td><?= htmlspecialchars($circuit['material_spec'] ?? '--') ?></td>
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-pill"><?= (int)$circuit['asset_count'] ?></span>
                            </td>
                            <td>
                                <?php
                                $statusColors = ['active' => 'success', 'inactive' => 'secondary', 'merged' => 'warning'];
                                $sc = $statusColors[$circuit['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $sc ?>"><?= ucwords(str_replace('_', ' ', $circuit['status'])) ?></span>
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

<!-- Add Circuit Modal (Placeholder) -->
<div class="modal fade" id="addCircuitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-node-plus me-2"></i>Create New Circuit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-tools text-muted fs-1 mb-3 d-block"></i>
                <p class="text-muted mb-0">Circuit creation form placeholder.<br>Form logic would go here to insert into `corrosion_circuits`.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof $ !== "undefined" && $.fn.DataTable) {
        $("#circuitsTable").DataTable({
            pageLength: 25,
            order: [[0, "asc"]]
        });
    }
});
</script>';
require_once INCLUDES_PATH . '/footer.php';