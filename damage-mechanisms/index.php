<?php
/**
 * Damage Mechanism Library - RBI Engineering Suite
 */
$pageTitle = 'Damage Mechanism Library';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Damage Mechanisms', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';

$categories = [
    'thinning'      => ['label' => 'Thinning / Corrosion', 'icon' => 'bi-droplet-half', 'color' => 'primary'],
    'cracking'      => ['label' => 'Cracking / SCC',       'icon' => 'bi-lightning',     'color' => 'danger'],
    'high_temp'     => ['label' => 'High Temperature',      'icon' => 'bi-thermometer-high', 'color' => 'warning'],
    'external'      => ['label' => 'External',              'icon' => 'bi-cloud-rain',   'color' => 'info'],
    'mechanical'    => ['label' => 'Mechanical / Fatigue',  'icon' => 'bi-gear',          'color' => 'secondary'],
    'metallurgical' => ['label' => 'Metallurgical',         'icon' => 'bi-gem',           'color' => 'dark'],
];

$mechanisms = [
    ['code' => 'THIN-01', 'name' => 'General Corrosion',          'category' => 'thinning',  'desc' => 'Uniform metal loss over a broad area caused by electrochemical reactions.', 'materials' => 'Carbon Steel, Low Alloy', 'conditions' => 'Aqueous environments, acids'],
    ['code' => 'THIN-04', 'name' => 'Erosion / Erosion-Corrosion','category' => 'thinning',  'desc' => 'Metal loss from fluid impingement or combined erosion and corrosion.', 'materials' => 'All metals', 'conditions' => 'High velocity, >3 m/s, particulates'],
    ['code' => 'THIN-05', 'name' => 'MIC',                        'category' => 'thinning',  'desc' => 'Corrosion promoted by microbial activity in water systems.', 'materials' => 'Carbon Steel, SS', 'conditions' => 'Stagnant water, 10-80C'],
    ['code' => 'THIN-07', 'name' => 'Sulfidic Corrosion',         'category' => 'thinning',  'desc' => 'Corrosion by H2S and sulfur compounds at elevated temperature.', 'materials' => 'Carbon Steel', 'conditions' => 'H2S present, >230C'],
    ['code' => 'THIN-09', 'name' => 'CO2 Corrosion',              'category' => 'thinning',  'desc' => 'Sweet corrosion in the presence of CO2 and water.', 'materials' => 'Carbon Steel', 'conditions' => 'CO2 + water, gas systems'],
    ['code' => 'SCC-01',  'name' => 'Chloride SCC',               'category' => 'cracking',  'desc' => 'Cracking of austenitic stainless steels in chloride environments above 60C.', 'materials' => 'Austenitic SS (304, 316)', 'conditions' => 'Cl- > 10ppm, T > 60C'],
    ['code' => 'SCC-02',  'name' => 'Sulfide Stress Cracking',    'category' => 'cracking',  'desc' => 'Cracking in sour (H2S) environments at low temperatures.', 'materials' => 'Carbon Steel, High Strength', 'conditions' => 'Wet H2S, ambient T'],
    ['code' => 'SCC-09',  'name' => 'Wet H2S Cracking',           'category' => 'cracking',  'desc' => 'Blistering and cracking from hydrogen charging in wet H2S service.', 'materials' => 'Carbon Steel', 'conditions' => 'Wet H2S, pH < 7'],
    ['code' => 'HT-01',   'name' => 'HTHA',                       'category' => 'high_temp', 'desc' => 'Internal decarburization and fissuring by hydrogen at high temperature and pressure.', 'materials' => 'Carbon Steel, Low Alloy', 'conditions' => 'H2, >204C, high pressure'],
    ['code' => 'HT-02',   'name' => 'Creep',                      'category' => 'high_temp', 'desc' => 'Time-dependent deformation at elevated temperature under stress.', 'materials' => 'Carbon Steel, Low Alloy', 'conditions' => 'T > 400C, continuous stress'],
    ['code' => 'EXT-01',  'name' => 'CUI',                        'category' => 'external',  'desc' => 'External corrosion beneath insulation or fireproofing.', 'materials' => 'Carbon Steel', 'conditions' => '-12C to 175C, insulated'],
    ['code' => 'EXT-02',  'name' => 'Atmospheric Corrosion',      'category' => 'external',  'desc' => 'External corrosion in ambient atmospheric conditions.', 'materials' => 'Carbon Steel', 'conditions' => 'All external surfaces'],
    ['code' => 'FAT-01',  'name' => 'Mechanical Fatigue',         'category' => 'mechanical','desc' => 'Cracking from cyclic mechanical loading.', 'materials' => 'All metals', 'conditions' => 'Cyclic stress, vibration'],
    ['code' => 'BF-01',   'name' => 'Brittle Fracture',           'category' => 'metallurgical','desc' => 'Sudden fracture below the ductile-to-brittle transition temperature.', 'materials' => 'Carbon Steel', 'conditions' => 'Low temperature, high stress'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Damage Mechanism Library (API 571)</h5>
</div>

<!-- Category Filter -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <button class="btn btn-sm btn-primary active" onclick="filterDM('all', this)">All</button>
    <?php foreach ($categories as $key => $cat): ?>
    <button class="btn btn-sm btn-outline-<?= $cat['color'] ?>" onclick="filterDM('<?= $key ?>', this)">
        <i class="bi <?= $cat['icon'] ?> me-1"></i><?= $cat['label'] ?>
    </button>
    <?php endforeach; ?>
</div>

<!-- Mechanism Cards -->
<div class="row g-3" id="dm-grid">
    <?php foreach ($mechanisms as $m):
        $cat = $categories[$m['category']];
    ?>
    <div class="col-lg-4 col-md-6 dm-card" data-category="<?= $m['category'] ?>">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3 mb-2">
                    <div class="kpi-icon bg-<?= $cat['color'] ?> bg-opacity-10 text-<?= $cat['color'] ?>" style="min-width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center">
                        <i class="bi <?= $cat['icon'] ?>"></i>
                    </div>
                    <div>
                        <span class="badge bg-<?= $cat['color'] ?> bg-opacity-10 text-<?= $cat['color'] ?> mb-1"><?= e($m['code']) ?></span>
                        <h6 class="mb-0"><?= e($m['name']) ?></h6>
                    </div>
                </div>
                <p class="small text-muted mb-2"><?= e($m['desc']) ?></p>
                <div class="small">
                    <div class="mb-1"><strong>Materials:</strong> <?= e($m['materials']) ?></div>
                    <div><strong>Conditions:</strong> <?= e($m['conditions']) ?></div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top">
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#dmDetailModal">
                    <i class="bi bi-info-circle me-1"></i>Details
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="dmDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Damage Mechanism Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Detailed information about the selected damage mechanism including screening questions, susceptibility criteria, inspection recommendations, and relevant API 571/581 references.</p>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
function filterDM(cat, btn) {
    document.querySelectorAll(".dm-card").forEach(c => {
        c.style.display = (cat === "all" || c.dataset.category === cat) ? "" : "none";
    });
    document.querySelectorAll("[onclick^=filterDM]").forEach(b => {
        b.classList.remove("active","btn-primary","btn-danger","btn-warning","btn-info","btn-secondary","btn-dark");
        if(!b.classList.contains("btn-outline-primary")) {
            b.className = b.className.replace(/btn-outline-\w+/,"");
        }
    });
    btn.classList.add("active");
}
</script>';
require_once INCLUDES_PATH . '/footer.php';
