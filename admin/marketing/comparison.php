<?php
/**
 * Software Comparison - RBI Engineering Suite
 * Detailed feature-by-feature comparison against competitors
 */
$pageTitle = 'Software Comparison';
$pageSection = 'Marketing';
$currentModule = 'admin';

require_once dirname(dirname(__DIR__)) . '/config/app.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    flash('Please log in to continue.', 'warning');
    redirect(BASE_URL . '/login.php');
}

// Competitor data
$competitors = [
    'rbi' => ['name' => 'RBI Engineering Suite', 'short' => 'RBI Suite', 'color' => '#1a237e'],
    'dnv' => ['name' => 'DNV Synergi RBI', 'short' => 'DNV Synergi', 'color' => '#0d6efd'],
    'hex' => ['name' => 'Hexagon APM (GE Meridium)', 'short' => 'Hexagon APM', 'color' => '#6610f2'],
    'cen' => ['name' => 'Cenosco IMS PEI', 'short' => 'Cenosco', 'color' => '#fd7e14'],
    'eqe' => ['name' => 'Equity Eng. PlantManager', 'short' => 'PlantManager', 'color' => '#198754'],
    'twi' => ['name' => 'TWI RiskWISE', 'short' => 'RiskWISE', 'color' => '#dc3545'],
    'pcms' => ['name' => 'PCMS RBI (Hexagon)', 'short' => 'PCMS', 'color' => '#6f42c1'],
];

// Comparison data: category => features => competitor support
// Values: 'yes', 'no', 'partial'
$comparison = [
    'Core RBI Engine' => [
        'API 580/581 compliance' =>       ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'partial','eqe'=>'yes','twi'=>'yes','pcms'=>'yes'],
        'Quantitative risk assessment' => ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'partial','eqe'=>'yes','twi'=>'yes','pcms'=>'yes'],
        'Semi-quantitative assessment' => ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'yes','eqe'=>'yes','twi'=>'yes','pcms'=>'yes'],
        'Qualitative screening' =>        ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'yes','eqe'=>'yes','twi'=>'yes','pcms'=>'yes'],
        'Custom risk matrices' =>         ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'yes','eqe'=>'partial','twi'=>'partial','pcms'=>'yes'],
        'Multi-unit assessment' =>        ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'partial','eqe'=>'yes','twi'=>'no','pcms'=>'partial'],
    ],
    'Damage Mechanisms' => [
        'API 571 damage mechanism library' => ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'partial','eqe'=>'yes','twi'=>'yes','pcms'=>'yes'],
        'Custom mechanism definition' =>      ['rbi'=>'yes','dnv'=>'partial','hex'=>'yes','cen'=>'yes','eqe'=>'partial','twi'=>'no','pcms'=>'partial'],
        'Susceptibility screening' =>         ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'partial','eqe'=>'yes','twi'=>'yes','pcms'=>'yes'],
        'Damage rate calculation' =>          ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'partial','eqe'=>'yes','twi'=>'partial','pcms'=>'yes'],
        'CML management' =>                  ['rbi'=>'yes','dnv'=>'partial','hex'=>'yes','cen'=>'yes','eqe'=>'partial','twi'=>'no','pcms'=>'yes'],
    ],
    'Inspection Planning' => [
        'Risk-based interval calculation' =>  ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'yes','eqe'=>'yes','twi'=>'yes','pcms'=>'yes'],
        'NDE method selection' =>             ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'partial','eqe'=>'partial','twi'=>'partial','pcms'=>'yes'],
        'Task management' =>                  ['rbi'=>'yes','dnv'=>'partial','hex'=>'yes','cen'=>'yes','eqe'=>'no','twi'=>'no','pcms'=>'partial'],
        'Calendar scheduling' =>              ['rbi'=>'yes','dnv'=>'partial','hex'=>'yes','cen'=>'yes','eqe'=>'no','twi'=>'no','pcms'=>'partial'],
        'Mobile field forms' =>               ['rbi'=>'yes','dnv'=>'no','hex'=>'partial','cen'=>'partial','eqe'=>'no','twi'=>'no','pcms'=>'no'],
        'Barcode/QR scanning' =>              ['rbi'=>'yes','dnv'=>'no','hex'=>'partial','cen'=>'no','eqe'=>'no','twi'=>'no','pcms'=>'no'],
    ],
    'Analytics' => [
        'Remaining life calculation' =>       ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'yes','eqe'=>'yes','twi'=>'yes','pcms'=>'yes'],
        'Corrosion rate trending' =>          ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'yes','eqe'=>'yes','twi'=>'partial','pcms'=>'yes'],
        'ML predictive analytics' =>          ['rbi'=>'yes','dnv'=>'no','hex'=>'partial','cen'=>'no','eqe'=>'no','twi'=>'no','pcms'=>'no'],
        'Monte Carlo simulation' =>           ['rbi'=>'yes','dnv'=>'no','hex'=>'partial','cen'=>'no','eqe'=>'yes','twi'=>'no','pcms'=>'no'],
        'Financial risk modeling' =>          ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'partial','eqe'=>'yes','twi'=>'partial','pcms'=>'partial'],
        'Fleet analysis' =>                   ['rbi'=>'yes','dnv'=>'partial','hex'=>'yes','cen'=>'no','eqe'=>'partial','twi'=>'no','pcms'=>'partial'],
    ],
    'Integration' => [
        'SAP PM integration' =>               ['rbi'=>'yes','dnv'=>'partial','hex'=>'yes','cen'=>'partial','eqe'=>'no','twi'=>'no','pcms'=>'partial'],
        'IBM Maximo integration' =>           ['rbi'=>'yes','dnv'=>'partial','hex'=>'yes','cen'=>'partial','eqe'=>'no','twi'=>'no','pcms'=>'partial'],
        'OSIsoft PI integration' =>           ['rbi'=>'yes','dnv'=>'no','hex'=>'yes','cen'=>'no','eqe'=>'no','twi'=>'no','pcms'=>'no'],
        'SCADA connectivity' =>               ['rbi'=>'yes','dnv'=>'no','hex'=>'partial','cen'=>'no','eqe'=>'no','twi'=>'no','pcms'=>'no'],
        'IoT sensor support' =>               ['rbi'=>'yes','dnv'=>'no','hex'=>'partial','cen'=>'no','eqe'=>'no','twi'=>'no','pcms'=>'no'],
        'Digital twin support' =>             ['rbi'=>'yes','dnv'=>'no','hex'=>'partial','cen'=>'no','eqe'=>'no','twi'=>'no','pcms'=>'no'],
        'REST API' =>                         ['rbi'=>'yes','dnv'=>'partial','hex'=>'yes','cen'=>'partial','eqe'=>'partial','twi'=>'no','pcms'=>'partial'],
    ],
    'Platform' => [
        'Cloud deployment' =>                 ['rbi'=>'yes','dnv'=>'partial','hex'=>'yes','cen'=>'no','eqe'=>'partial','twi'=>'no','pcms'=>'partial'],
        'On-premise deployment' =>            ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'yes','eqe'=>'yes','twi'=>'yes','pcms'=>'yes'],
        'Mobile app (PWA)' =>                 ['rbi'=>'yes','dnv'=>'no','hex'=>'partial','cen'=>'no','eqe'=>'no','twi'=>'no','pcms'=>'no'],
        'Offline capability' =>               ['rbi'=>'yes','dnv'=>'no','hex'=>'partial','cen'=>'no','eqe'=>'no','twi'=>'no','pcms'=>'no'],
        'Multi-language' =>                   ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'partial','eqe'=>'no','twi'=>'partial','pcms'=>'partial'],
        'Role-based access' =>                ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'yes','eqe'=>'partial','twi'=>'partial','pcms'=>'yes'],
        'Audit trail' =>                      ['rbi'=>'yes','dnv'=>'yes','hex'=>'yes','cen'=>'yes','eqe'=>'partial','twi'=>'partial','pcms'=>'yes'],
    ],
    'Pricing' => [
        'Entry-level (annual est.)' =>        ['rbi'=>'$30K','dnv'=>'$50K+','hex'=>'$75K+','cen'=>'$40K+','eqe'=>'$35K+','twi'=>'$25K+','pcms'=>'$45K+'],
        'Licensing model' =>                  ['rbi'=>'Per asset','dnv'=>'Per user','hex'=>'Per user','cen'=>'Per user','eqe'=>'Per asset','twi'=>'Per site','pcms'=>'Per user'],
    ],
];

// Scores for radar chart (out of 10)
$scores = [
    'categories' => ['Core RBI', 'Damage Mech.', 'Inspection', 'Analytics', 'Integration', 'Platform', 'Value'],
    'data' => [
        'rbi'  => [9.5, 9.0, 9.5, 9.5, 9.5, 9.5, 9.0],
        'dnv'  => [9.0, 8.5, 7.0, 7.0, 4.0, 6.5, 6.0],
        'hex'  => [9.0, 8.5, 8.0, 8.0, 8.5, 7.5, 5.5],
        'cen'  => [7.0, 6.5, 7.0, 5.0, 3.5, 4.0, 7.0],
        'eqe'  => [8.5, 7.5, 5.0, 7.5, 2.5, 5.0, 7.5],
        'twi'  => [8.0, 6.5, 4.0, 5.0, 1.5, 4.0, 7.5],
        'pcms' => [8.5, 8.0, 6.5, 6.5, 4.5, 6.0, 6.5],
    ],
];

// Competitor summaries
$summaries = [
    'dnv' => [
        'strengths' => ['Strong API 581 implementation', 'Well-known brand in maritime & oil/gas', 'Extensive global support network'],
        'weaknesses' => ['Limited mobile/cloud capabilities', 'No ML/predictive analytics', 'Complex licensing model'],
    ],
    'hex' => [
        'strengths' => ['Comprehensive APM platform', 'Strong SAP/Maximo integration', 'Large install base'],
        'weaknesses' => ['Very high cost', 'Complex implementation', 'Steep learning curve'],
    ],
    'cen' => [
        'strengths' => ['Good inspection management', 'User-friendly interface', 'Reasonable pricing'],
        'weaknesses' => ['Limited quantitative RBI', 'No predictive analytics', 'No cloud deployment'],
    ],
    'eqe' => [
        'strengths' => ['Strong API 581 methodology', 'Good corrosion engineering tools', 'Competitive pricing'],
        'weaknesses' => ['Limited inspection planning', 'No mobile app', 'Minimal integrations'],
    ],
    'twi' => [
        'strengths' => ['TWI research backing', 'Good for fitness-for-service', 'Affordable entry point'],
        'weaknesses' => ['Limited features overall', 'No integrations', 'Desktop-only'],
    ],
    'pcms' => [
        'strengths' => ['Good RBI methodology', 'Established product', 'Now part of Hexagon'],
        'weaknesses' => ['Aging interface', 'No mobile support', 'Limited analytics'],
    ],
];

include INCLUDES_PATH . '/header.php';
?>

<style>
.comp-table-container { overflow-x: auto; margin-bottom: 32px; }
.comp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82rem;
    min-width: 900px;
}
.comp-table th {
    background: #f8fafc;
    padding: 10px 12px;
    text-align: center;
    font-weight: 600;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 5;
}
.comp-table th:first-child { text-align: left; min-width: 200px; }
.comp-table th.highlight { background: #1a237e; color: #fff; }
.comp-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; text-align: center; }
.comp-table td:first-child { text-align: left; font-weight: 500; }
.comp-table td.highlight { background: #f0f4ff; }
.comp-table .cat-row td { background: #1e293b; color: #fff; font-weight: 700; font-size: 0.85rem; }
.comp-table .cat-row td:first-child { padding-left: 16px; }

.comp-yes { color: #22c55e; font-weight: 700; }
.comp-no { color: #ef4444; }
.comp-partial { color: #f59e0b; }

.competitor-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    padding: 24px;
    height: 100%;
}
.competitor-card h5 { font-weight: 700; margin-bottom: 16px; }
.str-weak-list { list-style: none; padding: 0; margin: 0; font-size: 0.82rem; }
.str-weak-list li { padding: 6px 0; border-bottom: 1px solid #f1f5f9; }
.str-weak-list li:last-child { border-bottom: none; }

@media print {
    .no-print { display: none !important; }
    .main-content { margin-left: 0 !important; }
    .sidebar, .top-navbar, footer { display: none !important; }
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fas fa-balance-scale me-2 text-primary"></i>Software Comparison</h1>
        <p class="text-muted mb-0 mt-1">Comprehensive feature comparison against leading RBI software solutions</p>
    </div>
    <div class="d-flex gap-2 no-print">
        <button onclick="window.print()" class="btn btn-outline-primary"><i class="fas fa-file-pdf me-1"></i>Download PDF</button>
        <a href="<?= BASE_URL ?>/admin/marketing/" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Marketing</a>
    </div>
</div>

<!-- Radar Chart -->
<div class="card mb-4">
    <div class="card-header"><i class="fas fa-chart-radar me-2"></i>Overall Capability Comparison</div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <canvas id="radarChart" height="350"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Comparison Table -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-table me-2"></i>Feature-by-Feature Comparison</span>
        <div class="d-flex gap-3 small no-print">
            <span><span class="comp-yes">&#10004;</span> = Supported</span>
            <span><span class="comp-partial">&#9679;</span> = Partial</span>
            <span><span class="comp-no">&#10006;</span> = Not Available</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="comp-table-container">
            <table class="comp-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <?php foreach ($competitors as $key => $comp): ?>
                        <th class="<?= $key === 'rbi' ? 'highlight' : '' ?>"><?= e($comp['short']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comparison as $category => $features): ?>
                    <tr class="cat-row">
                        <td colspan="<?= count($competitors) + 1 ?>"><i class="fas fa-folder-open me-2"></i><?= e($category) ?></td>
                    </tr>
                    <?php foreach ($features as $feature => $support): ?>
                    <tr>
                        <td><?= e($feature) ?></td>
                        <?php foreach ($competitors as $key => $comp):
                            $val = $support[$key] ?? 'no';
                            if ($val === 'yes') $display = '<span class="comp-yes">&#10004;</span>';
                            elseif ($val === 'partial') $display = '<span class="comp-partial">&#9679;</span>';
                            elseif ($val === 'no') $display = '<span class="comp-no">&#10006;</span>';
                            else $display = '<span class="small">' . e($val) . '</span>';
                        ?>
                        <td class="<?= $key === 'rbi' ? 'highlight' : '' ?>"><?= $display ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Competitor Summary Cards -->
<div class="card mb-4">
    <div class="card-header"><i class="fas fa-users me-2"></i>Competitor Analysis</div>
    <div class="card-body">
        <div class="row g-4">
            <?php foreach ($summaries as $key => $summary): ?>
            <div class="col-lg-4 col-md-6">
                <div class="competitor-card">
                    <h5>
                        <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:<?= $competitors[$key]['color'] ?>;margin-right:8px;"></span>
                        <?= e($competitors[$key]['name']) ?>
                    </h5>
                    <h6 class="text-success small fw-bold mb-2"><i class="fas fa-plus-circle me-1"></i>Strengths</h6>
                    <ul class="str-weak-list mb-3">
                        <?php foreach ($summary['strengths'] as $s): ?>
                        <li><i class="fas fa-check text-success me-2"></i><?= e($s) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <h6 class="text-danger small fw-bold mb-2"><i class="fas fa-minus-circle me-1"></i>Weaknesses</h6>
                    <ul class="str-weak-list">
                        <?php foreach ($summary['weaknesses'] as $w): ?>
                        <li><i class="fas fa-times text-danger me-2"></i><?= e($w) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Score Summary -->
<div class="card mb-4">
    <div class="card-header"><i class="fas fa-trophy me-2"></i>Overall Score Ranking</div>
    <div class="card-body">
        <?php
        $totalScores = [];
        foreach ($scores['data'] as $key => $vals) {
            $totalScores[$key] = round(array_sum($vals) / count($vals), 1);
        }
        arsort($totalScores);
        $rank = 0;
        foreach ($totalScores as $key => $score):
            $rank++;
            $barWidth = ($score / 10) * 100;
            $barColor = $key === 'rbi' ? '#1a237e' : ($competitors[$key]['color'] ?? '#94a3b8');
        ?>
        <div class="d-flex align-items-center gap-3 mb-3">
            <div style="width:30px;" class="fw-bold text-muted">#<?= $rank ?></div>
            <div style="width:180px;" class="fw-semibold small"><?= e($competitors[$key]['short']) ?></div>
            <div class="flex-grow-1">
                <div class="progress" style="height:24px;border-radius:12px;">
                    <div class="progress-bar" style="width:<?= $barWidth ?>%;background:<?= $barColor ?>;border-radius:12px;font-size:0.75rem;font-weight:600;" role="progressbar">
                        <?= $score ?>/10
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Disclaimer -->
<div class="alert alert-secondary small mb-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Disclaimer:</strong> This comparison is based on publicly available information and may not reflect the latest features of each product. Product names are trademarks of their respective owners. Pricing is estimated based on publicly available information and typical deployment sizes. Actual pricing may vary. Last updated: <?= date('F Y') ?>.
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('radarChart');
    if (!ctx) return;

    const categories = <?= json_encode($scores['categories']) ?>;
    const datasets = [];
    const colors = <?= json_encode(array_map(function($c) { return $c['color']; }, $competitors)) ?>;
    const names = <?= json_encode(array_map(function($c) { return $c['short']; }, $competitors)) ?>;
    const data = <?= json_encode($scores['data']) ?>;

    const keys = Object.keys(data);
    keys.forEach(function(key, i) {
        const isRBI = key === 'rbi';
        datasets.push({
            label: names[key],
            data: data[key],
            borderColor: colors[key],
            backgroundColor: isRBI ? colors[key] + '30' : 'transparent',
            borderWidth: isRBI ? 3 : 1.5,
            pointRadius: isRBI ? 4 : 2,
            pointBackgroundColor: colors[key],
        });
    });

    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: categories,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 16, font: { size: 11 } }
                }
            },
            scales: {
                r: {
                    min: 0,
                    max: 10,
                    ticks: { stepSize: 2, backdropColor: 'transparent', font: { size: 10 } },
                    pointLabels: { font: { size: 12, weight: '600' } },
                    grid: { color: '#e2e8f0' },
                    angleLines: { color: '#e2e8f0' }
                }
            }
        }
    });
});
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
