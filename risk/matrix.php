<?php
/**
 * Interactive Risk Matrix - RBI Engineering Suite
 */
$pageTitle = 'Risk Matrix';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Risk Matrix', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';

$riskColors = [
    'L'  => '#28a745', 'M'  => '#ffc107', 'MH' => '#fd7e14',
    'H'  => '#dc3545', 'VH' => '#721c24',
];
$matrix = [
    5 => ['A' => 'MH', 'B' => 'H',  'C' => 'H',  'D' => 'VH', 'E' => 'VH'],
    4 => ['A' => 'M',  'B' => 'MH', 'C' => 'H',  'D' => 'H',  'E' => 'VH'],
    3 => ['A' => 'M',  'B' => 'M',  'C' => 'MH', 'D' => 'H',  'E' => 'H'],
    2 => ['A' => 'L',  'B' => 'M',  'C' => 'M',  'D' => 'MH', 'E' => 'H'],
    1 => ['A' => 'L',  'B' => 'L',  'C' => 'M',  'D' => 'M',  'E' => 'MH'],
];
$pofLabels = [5 => 'Very Likely', 4 => 'Likely', 3 => 'Possible', 2 => 'Unlikely', 1 => 'Improbable'];
$cofLabels = ['A' => 'Low', 'B' => 'Medium-Low', 'C' => 'Medium', 'D' => 'Medium-High', 'E' => 'Very High'];
$riskLabels = ['L' => 'Low', 'M' => 'Medium', 'MH' => 'Medium-High', 'H' => 'High', 'VH' => 'Very High'];

// Sample counts per cell
$counts = [
    '5A' => 0, '5B' => 1, '5C' => 2, '5D' => 1, '5E' => 1,
    '4A' => 3, '4B' => 4, '4C' => 5, '4D' => 3, '4E' => 2,
    '3A' => 8, '3B' => 12, '3C' => 15, '3D' => 6, '3E' => 3,
    '2A' => 18, '2B' => 14, '2C' => 10, '2D' => 5, '2E' => 2,
    '1A' => 22, '1B' => 10, '1C' => 5, '1D' => 3, '1E' => 1,
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-grid-3x3 me-2"></i>5x5 Risk Matrix</h5>
    <div>
        <select class="form-select form-select-sm d-inline-block" style="width:auto">
            <option>All Facilities</option>
            <option>Refinery Alpha</option>
        </select>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex">
                    <!-- Y-axis label -->
                    <div class="d-flex align-items-center me-2" style="writing-mode:vertical-rl;transform:rotate(180deg)">
                        <span class="fw-bold text-muted small">Probability of Failure (POF)</span>
                    </div>
                    <div class="flex-grow-1">
                        <table class="table table-bordered text-center mb-2" style="table-layout:fixed">
                            <thead>
                                <tr>
                                    <th style="width:80px" class="bg-light"></th>
                                    <?php foreach ($cofLabels as $key => $label): ?>
                                    <th class="bg-light"><small class="fw-bold"><?= $key ?></small><br><small class="text-muted"><?= $label ?></small></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($pof = 5; $pof >= 1; $pof--): ?>
                                <tr>
                                    <td class="bg-light fw-bold align-middle"><small class="fw-bold"><?= $pof ?></small><br><small class="text-muted"><?= $pofLabels[$pof] ?></small></td>
                                    <?php foreach (array_keys($cofLabels) as $cof):
                                        $level = $matrix[$pof][$cof];
                                        $color = $riskColors[$level];
                                        $count = $counts[$pof . $cof] ?? 0;
                                        $textColor = in_array($level, ['H','VH']) ? '#fff' : '#333';
                                    ?>
                                    <td style="background:<?= $color ?>; color:<?= $textColor ?>; cursor:pointer; height:70px; vertical-align:middle"
                                        class="risk-cell" data-pof="<?= $pof ?>" data-cof="<?= $cof ?>"
                                        onclick="showCellAssets(<?= $pof ?>, '<?= $cof ?>')">
                                        <div class="fs-4 fw-bold"><?= $count ?></div>
                                        <small><?= $riskLabels[$level] ?></small>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                        <div class="text-center">
                            <span class="fw-bold text-muted small">Consequence of Failure (COF)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Legend & Details Panel -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Legend</div>
            <div class="card-body">
                <?php foreach ($riskLabels as $key => $label): ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width:24px;height:24px;border-radius:4px;background:<?= $riskColors[$key] ?>"></div>
                    <span class="small"><?= $label ?> (<?= $key ?>)</span>
                </div>
                <?php endforeach; ?>
                <hr>
                <div class="small text-muted">
                    <p class="mb-1"><strong>Total Assets:</strong> 156</p>
                    <p class="mb-1"><strong>Assessed:</strong> 134 (85.9%)</p>
                    <p class="mb-0">Click any cell to view assets in that risk category.</p>
                </div>
            </div>
        </div>

        <div class="card" id="cell-detail-card" style="display:none">
            <div class="card-header" id="cell-detail-title">Assets in Category</div>
            <div class="card-body p-0" id="cell-detail-list"></div>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
function showCellAssets(pof, cof) {
    document.querySelectorAll(".risk-cell").forEach(c => c.style.outline = "none");
    event.currentTarget.style.outline = "3px solid #333";
    let card = document.getElementById("cell-detail-card");
    card.style.display = "block";
    document.getElementById("cell-detail-title").textContent = "POF " + pof + " / COF " + cof;
    let items = [
        {name:"V-101 Separator",type:"Vessel"},
        {name:"E-205 Exchanger",type:"Heat Exchanger"},
        {name:"P-302A Pump",type:"Pump"}
    ];
    let html = "<div class=\"list-group list-group-flush\">";
    items.forEach(a => {
        html += `<a href="' . BASE_URL . '/assets/view.php?id=1" class="list-group-item list-group-item-action py-2 px-3">
            <div class="d-flex justify-content-between"><span class="fw-semibold small">${a.name}</span><span class="text-muted small">${a.type}</span></div>
        </a>`;
    });
    html += "</div>";
    document.getElementById("cell-detail-list").innerHTML = html;
}
</script>';
require_once INCLUDES_PATH . '/footer.php';
