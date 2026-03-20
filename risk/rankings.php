<?php
/**
 * Risk Rankings - RBI Engineering Suite
 */
$pageTitle = 'Risk Rankings';
$currentModule = 'risk';
require_once __DIR__ . '/../config/app.php';
requireAuth();

$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Risk Assessment', 'url' => '#'],
    ['label' => 'Rankings', 'url' => '#']
];

$db = new Database();
// Fetch latest risk scores joined with asset info
$rankings = $db->query("
    SELECT ar.id as asset_id, ar.asset_tag, ar.asset_name, ar.asset_type, ar.criticality,
           rs.overall_risk, rs.pof_score, rs.cof_score, rs.risk_category, rs.scored_at
    FROM asset_registry ar
    LEFT JOIN risk_scores rs ON ar.id = rs.asset_id
    WHERE rs.id = (
        SELECT MAX(id) FROM risk_scores WHERE asset_id = ar.id
    )
    ORDER BY rs.overall_risk DESC
")->fetchAll();

require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-sort-numeric-down me-2"></i>Risk Rankings</h5>
    <button class="btn btn-outline-primary btn-sm"><i class="bi bi-download me-1"></i>Export CSV</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="rankingsTable" class="table table-hover align-middle" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>Rank</th>
                        <th>Asset Tag</th>
                        <th>Asset Name</th>
                        <th>Type</th>
                        <th class="text-end">PoF Score</th>
                        <th class="text-end">CoF Score</th>
                        <th class="text-end">Overall Risk</th>
                        <th>Risk Category</th>
                        <th>Last Assessed</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($rankings as $row): 
                    ?>
                        <tr>
                            <td class="text-muted fw-bold">#<?= $rank++ ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($row['asset_tag']) ?></td>
                            <td><?= htmlspecialchars($row['asset_name']) ?></td>
                            <td><?= ucwords(str_replace('_', ' ', $row['asset_type'])) ?></td>
                            <td class="text-end"><?= number_format($row['pof_score'], 2) ?></td>
                            <td class="text-end"><?= number_format($row['cof_score'], 2) ?></td>
                            <td class="text-end fw-bold"><?= number_format($row['overall_risk'], 2) ?></td>
                            <td>
                                <?php
                                $catColors = ['very_high' => 'danger', 'high' => 'warning text-dark', 'medium' => 'info', 'low' => 'success', 'very_low' => 'secondary'];
                                $rc = $catColors[$row['risk_category']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $rc ?>"><?= ucwords(str_replace('_', ' ', $row['risk_category'] ?? 'Unassessed')) ?></span>
                            </td>
                            <td><?= date('M d, Y', strtotime($row['scored_at'])) ?></td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/assets/view.php?id=<?= $row['asset_id'] ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View Asset"><i class="bi bi-eye"></i></a>
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
        $("#rankingsTable").DataTable({
            pageLength: 25,
            order: [[6, "desc"]], // Sort by overall risk by default
            language: { search: "Search rankings:" }
        });
    }
});
</script>';
require_once INCLUDES_PATH . '/footer.php';