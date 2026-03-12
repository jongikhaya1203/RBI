<?php
/**
 * Risk Calculator - RBI Engineering Suite
 */
$pageTitle = 'Risk Calculator';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Risk Assessment', 'url' => BASE_URL . '/risk/assessments.php'],
    ['label' => 'Risk Calculator', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-calculator me-2"></i>Risk Calculation (API 580/581)</h5>
</div>

<form id="riskCalcForm">
    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">

    <div class="row g-4">
        <!-- Asset Selection -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">Select Asset</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Asset</label>
                            <select name="asset_id" class="form-select" id="asset-select" required>
                                <option value="">Select an asset...</option>
                                <option value="1">V-101 - Inlet Separator</option>
                                <option value="2">E-205 - Feed/Effluent Exchanger</option>
                                <option value="3">T-401 - Crude Storage Tank</option>
                                <option value="4">P-302A - HDS Feed Pump</option>
                                <option value="5">C-102 - Atmospheric Column</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Assessment Date</label>
                            <input type="date" name="assessment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Projection (years)</label>
                            <input type="number" name="years_from_now" class="form-control" value="0" min="0" max="50" step="0.5">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- POF Parameters -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-primary bg-opacity-10">
                    <i class="bi bi-lightning me-2"></i>Probability of Failure (POF)
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            Generic Failure Frequency (gff)
                            <span class="text-muted small" id="gff-val">3.06e-5</span>
                        </label>
                        <input type="range" class="form-range" name="gff" min="-6" max="-2" step="0.1" value="-4.51"
                               oninput="document.getElementById('gff-val').textContent=Math.pow(10,this.value).toExponential(2)">
                        <small class="text-muted">Per API 581 Table 4.1 - based on equipment type</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            Equipment Modification Factor
                            <span class="text-muted small" id="emf-val">1.0</span>
                        </label>
                        <input type="range" class="form-range" name="equipment_mod_factor" min="0.1" max="5" step="0.1" value="1.0"
                               oninput="document.getElementById('emf-val').textContent=parseFloat(this.value).toFixed(1)">
                        <small class="text-muted">Adjusts for equipment-specific modifications</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            Management Systems Factor (FMS)
                            <span class="text-muted small" id="fms-val">1.0</span>
                        </label>
                        <input type="range" class="form-range" name="management_factor" min="0.1" max="10" step="0.1" value="1.0"
                               oninput="document.getElementById('fms-val').textContent=parseFloat(this.value).toFixed(1)">
                        <small class="text-muted">0.1 (excellent) to 10.0 (poor management)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            Damage Mechanism Factor (Df)
                            <span class="text-muted small" id="df-val">10.0</span>
                        </label>
                        <input type="range" class="form-range" name="damage_factor" min="1" max="1000" step="1" value="10"
                               oninput="document.getElementById('df-val').textContent=parseFloat(this.value).toFixed(0)">
                        <small class="text-muted">Aggregate damage factor from thinning, cracking, etc.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- COF Parameters -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-danger bg-opacity-10">
                    <i class="bi bi-exclamation-diamond me-2"></i>Consequence of Failure (COF)
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            Flammable Consequence Area (m&sup2;)
                            <span class="text-muted small" id="cof-flam-val">5000</span>
                        </label>
                        <input type="range" class="form-range" name="cof_flammable" min="0" max="100000" step="100" value="5000"
                               oninput="document.getElementById('cof-flam-val').textContent=parseInt(this.value).toLocaleString()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            Toxic Consequence Area (m&sup2;)
                            <span class="text-muted small" id="cof-toxic-val">2000</span>
                        </label>
                        <input type="range" class="form-range" name="cof_toxic" min="0" max="100000" step="100" value="2000"
                               oninput="document.getElementById('cof-toxic-val').textContent=parseInt(this.value).toLocaleString()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            Environmental Cost ($)
                            <span class="text-muted small" id="cof-env-val">50,000</span>
                        </label>
                        <input type="range" class="form-range" name="cof_environmental" min="0" max="5000000" step="10000" value="50000"
                               oninput="document.getElementById('cof-env-val').textContent=parseInt(this.value).toLocaleString()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            Financial Consequence ($)
                            <span class="text-muted small" id="cof-fin-val">500,000</span>
                        </label>
                        <input type="range" class="form-range" name="cof_financial" min="0" max="50000000" step="50000" value="500000"
                               oninput="document.getElementById('cof-fin-val').textContent=parseInt(this.value).toLocaleString()">
                    </div>
                </div>
            </div>
        </div>

        <!-- Calculate Button -->
        <div class="col-12 text-center">
            <button type="button" class="btn btn-primary btn-lg px-5" onclick="calculateRisk()">
                <i class="bi bi-calculator me-2"></i>Calculate Risk
            </button>
        </div>

        <!-- Results Panel -->
        <div class="col-12" id="results-panel" style="display:none">
            <div class="card border-top border-4 border-primary">
                <div class="card-header bg-primary bg-opacity-10 fw-bold fs-5">
                    <i class="bi bi-graph-up me-2"></i>Risk Calculation Results
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-md-3">
                            <div class="p-3 rounded bg-light">
                                <div class="text-muted small">POF Value</div>
                                <div class="fs-4 fw-bold" id="result-pof">--</div>
                                <div class="small" id="result-pof-cat">--</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded bg-light">
                                <div class="text-muted small">COF Value</div>
                                <div class="fs-4 fw-bold" id="result-cof">--</div>
                                <div class="small" id="result-cof-cat">--</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded bg-light">
                                <div class="text-muted small">Risk Value</div>
                                <div class="fs-4 fw-bold" id="result-risk-val">--</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded" id="result-risk-box" style="background:#ffc107">
                                <div class="text-muted small">Risk Level</div>
                                <div class="fs-3 fw-bold" id="result-risk-level">--</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <button class="btn btn-outline-primary me-2"><i class="bi bi-save me-1"></i>Save Assessment</button>
                        <button class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print Report</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
$extraJs = '<script>
function calculateRisk() {
    let form = document.getElementById("riskCalcForm");
    let gff = Math.pow(10, parseFloat(form.gff.value));
    let emf = parseFloat(form.equipment_mod_factor.value);
    let fms = parseFloat(form.management_factor.value);
    let df  = parseFloat(form.damage_factor.value);
    let cofFlam = parseFloat(form.cof_flammable.value);
    let cofToxic = parseFloat(form.cof_toxic.value);
    let cofEnv = parseFloat(form.cof_environmental.value);
    let cofFin = parseFloat(form.cof_financial.value);

    let pof = gff * emf * fms * df;
    let cof = Math.max(cofFlam, cofToxic, cofEnv, cofFin);
    let riskVal = pof * cof;

    // Categorize POF
    let pofCat = pof < 1e-5 ? 1 : pof < 1e-4 ? 2 : pof < 1e-3 ? 3 : pof < 1e-2 ? 4 : 5;
    let pofLabels = {1:"Improbable",2:"Unlikely",3:"Possible",4:"Likely",5:"Very Likely"};

    // Categorize COF
    let cofCat = cof < 10000 ? "A" : cof < 50000 ? "B" : cof < 200000 ? "C" : cof < 1000000 ? "D" : "E";
    let cofLabels = {A:"Low",B:"Medium-Low",C:"Medium",D:"Medium-High",E:"Very High"};

    // Risk matrix
    let matrix = {
        5:{A:"MH",B:"H",C:"H",D:"VH",E:"VH"},
        4:{A:"M",B:"MH",C:"H",D:"H",E:"VH"},
        3:{A:"M",B:"M",C:"MH",D:"H",E:"H"},
        2:{A:"L",B:"M",C:"M",D:"MH",E:"H"},
        1:{A:"L",B:"L",C:"M",D:"M",E:"MH"}
    };
    let riskLevel = matrix[pofCat][cofCat];
    let riskColors = {L:"#28a745",M:"#ffc107",MH:"#fd7e14",H:"#dc3545",VH:"#721c24"};
    let riskNames = {L:"Low",M:"Medium",MH:"Medium-High",H:"High",VH:"Very High"};

    document.getElementById("result-pof").textContent = pof.toExponential(3);
    document.getElementById("result-pof-cat").textContent = pofCat + " - " + pofLabels[pofCat];
    document.getElementById("result-cof").textContent = cof.toLocaleString();
    document.getElementById("result-cof-cat").textContent = cofCat + " - " + cofLabels[cofCat];
    document.getElementById("result-risk-val").textContent = riskVal.toFixed(4);
    document.getElementById("result-risk-level").textContent = riskNames[riskLevel];
    let box = document.getElementById("result-risk-box");
    box.style.background = riskColors[riskLevel];
    box.style.color = ["H","VH"].includes(riskLevel) ? "#fff" : "#333";
    document.getElementById("results-panel").style.display = "block";
    document.getElementById("results-panel").scrollIntoView({behavior:"smooth"});
}
</script>';
require_once INCLUDES_PATH . '/footer.php';
