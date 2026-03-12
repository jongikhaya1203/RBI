<?php
/**
 * Asset Detail View - RBI Engineering Suite
 */
$pageTitle = 'Asset Details';
require_once __DIR__ . '/../config/app.php';
requireAuth();

$assetId = (int)($_GET['id'] ?? 0);
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Asset Registry', 'url' => BASE_URL . '/assets/index.php'],
    ['label' => 'Asset Details', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>V-101 - Inlet Separator</h5>
        <small class="text-muted">Pressure Vessel | Unit 100 - Crude Distillation</small>
    </div>
    <div>
        <span class="badge bg-danger fs-6 me-2">High Risk</span>
        <span class="badge bg-success fs-6">In Service</span>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#overview"><i class="bi bi-info-circle me-1"></i>Overview</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#risk-history"><i class="bi bi-graph-up me-1"></i>Risk History</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#damage-mechs"><i class="bi bi-bug me-1"></i>Damage Mechanisms</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#inspection-history"><i class="bi bi-clipboard-check me-1"></i>Inspection History</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#corrosion-data"><i class="bi bi-graph-down me-1"></i>Corrosion Data</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#documents"><i class="bi bi-file-earmark me-1"></i>Documents</a></li>
</ul>

<div class="tab-content">
    <!-- Overview Tab -->
    <div class="tab-pane fade show active" id="overview">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">Design Data</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" style="width:40%">Asset Tag</td><td class="fw-semibold">V-101</td></tr>
                            <tr><td class="text-muted">Design Code</td><td>ASME VIII Div 1</td></tr>
                            <tr><td class="text-muted">Material</td><td>SA-516 Gr 70</td></tr>
                            <tr><td class="text-muted">Design Pressure</td><td>1.50 MPa</td></tr>
                            <tr><td class="text-muted">Design Temperature</td><td>150 &deg;C</td></tr>
                            <tr><td class="text-muted">Nominal Thickness</td><td>12.70 mm</td></tr>
                            <tr><td class="text-muted">Corrosion Allowance</td><td>3.18 mm</td></tr>
                            <tr><td class="text-muted">Min Required Thickness</td><td>4.20 mm</td></tr>
                            <tr><td class="text-muted">Joint Efficiency</td><td>1.0</td></tr>
                            <tr><td class="text-muted">PWHT</td><td>Yes</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">Operational Data</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" style="width:40%">Operating Pressure</td><td>0.85 MPa</td></tr>
                            <tr><td class="text-muted">Operating Temperature</td><td>120 &deg;C</td></tr>
                            <tr><td class="text-muted">Fluid Service</td><td>Crude Oil (Wet)</td></tr>
                            <tr><td class="text-muted">Fluid Phase</td><td>Two-phase</td></tr>
                            <tr><td class="text-muted">H2S Content</td><td>0.35%</td></tr>
                            <tr><td class="text-muted">CO2 Content</td><td>1.2%</td></tr>
                            <tr><td class="text-muted">Chloride Content</td><td>45 ppm</td></tr>
                            <tr><td class="text-muted">Flow Velocity</td><td>2.5 m/s</td></tr>
                            <tr><td class="text-muted">Install Date</td><td>Mar 15, 2005</td></tr>
                            <tr><td class="text-muted">Age</td><td>21.0 years</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">Current Condition</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted">Measured Thickness</td><td class="fw-semibold">9.82 mm</td></tr>
                            <tr><td class="text-muted">Corrosion Rate</td><td>0.18 mm/yr</td></tr>
                            <tr><td class="text-muted">Remaining Life</td><td class="fw-semibold text-warning">31.2 years</td></tr>
                            <tr><td class="text-muted">Condition Grade</td><td><span class="badge bg-warning text-dark">C</span></td></tr>
                            <tr><td class="text-muted">Last Inspection</td><td>Jan 15, 2026</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">Risk Summary</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted">POF Category</td><td>4 - Likely</td></tr>
                            <tr><td class="text-muted">COF Category</td><td>D - Medium-High</td></tr>
                            <tr><td class="text-muted">Risk Level</td><td><span class="badge bg-danger">High</span></td></tr>
                            <tr><td class="text-muted">Damage Factor</td><td>45.2</td></tr>
                            <tr><td class="text-muted">Last Assessment</td><td>Jan 20, 2026</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">Inspection Plan</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted">Plan Type</td><td>Risk-Based</td></tr>
                            <tr><td class="text-muted">Interval</td><td>12 months</td></tr>
                            <tr><td class="text-muted">Next Due</td><td class="fw-semibold">Jan 15, 2027</td></tr>
                            <tr><td class="text-muted">Strategy</td><td>UT Grid + MT Welds</td></tr>
                            <tr><td class="text-muted">Priority</td><td><span class="badge bg-danger">High</span></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk History Tab -->
    <div class="tab-pane fade" id="risk-history">
        <div class="card">
            <div class="card-body">
                <canvas id="riskHistoryChart" style="height:300px"></canvas>
                <hr>
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>POF</th><th>COF</th><th>Risk Level</th><th>Risk Value</th><th>Assessed By</th></tr></thead>
                    <tbody>
                        <tr><td>Jan 20, 2026</td><td>4 - Likely</td><td>D - Med-High</td><td><span class="badge bg-danger">High</span></td><td>0.0845</td><td>J. Smith</td></tr>
                        <tr><td>Jul 12, 2025</td><td>3 - Possible</td><td>D - Med-High</td><td><span class="badge bg-danger">High</span></td><td>0.0623</td><td>J. Smith</td></tr>
                        <tr><td>Jan 05, 2025</td><td>3 - Possible</td><td>C - Medium</td><td><span class="badge bg-warning text-dark">Med-High</span></td><td>0.0412</td><td>S. Jones</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Damage Mechanisms Tab -->
    <div class="tab-pane fade" id="damage-mechs">
        <div class="card">
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Code</th><th>Mechanism</th><th>Category</th><th>Susceptibility</th><th>Rate (mm/yr)</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr><td>THIN-01</td><td>General Corrosion</td><td>Thinning</td><td><span class="badge bg-warning text-dark">Medium</span></td><td>0.15</td><td><span class="badge bg-success">Active</span></td></tr>
                        <tr><td>THIN-07</td><td>Sulfidic Corrosion</td><td>Thinning</td><td><span class="badge bg-danger">High</span></td><td>0.22</td><td><span class="badge bg-success">Active</span></td></tr>
                        <tr><td>SCC-09</td><td>Wet H2S Cracking</td><td>Cracking</td><td><span class="badge bg-warning text-dark">Medium</span></td><td>N/A</td><td><span class="badge bg-success">Active</span></td></tr>
                        <tr><td>EXT-01</td><td>CUI</td><td>External</td><td><span class="badge bg-danger">High</span></td><td>0.25</td><td><span class="badge bg-success">Active</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Inspection History Tab -->
    <div class="tab-pane fade" id="inspection-history">
        <div class="card">
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Method</th><th>Thickness (mm)</th><th>Condition</th><th>Inspector</th><th>Findings</th></tr></thead>
                    <tbody>
                        <tr><td>Jan 15, 2026</td><td>UT Grid</td><td>9.82</td><td><span class="badge bg-warning text-dark">C</span></td><td>J. Smith</td><td>Localized thinning at 6 o'clock</td></tr>
                        <tr><td>Jan 10, 2025</td><td>UT Spot</td><td>10.01</td><td><span class="badge bg-info">B</span></td><td>S. Jones</td><td>General wall loss within expected range</td></tr>
                        <tr><td>Jan 08, 2024</td><td>UT Grid + MT</td><td>10.22</td><td><span class="badge bg-info">B</span></td><td>J. Smith</td><td>No indications found at welds</td></tr>
                        <tr><td>Jul 15, 2023</td><td>Visual</td><td>--</td><td><span class="badge bg-info">B</span></td><td>M. Chen</td><td>External coating in good condition</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Corrosion Data Tab -->
    <div class="tab-pane fade" id="corrosion-data">
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">Thickness Trend</div>
                    <div class="card-body"><canvas id="thicknessTrendChart" style="height:300px"></canvas></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Corrosion Rates</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr><td class="text-muted">Short-Term Rate</td><td class="fw-semibold">0.19 mm/yr</td></tr>
                            <tr><td class="text-muted">Long-Term Rate</td><td class="fw-semibold">0.15 mm/yr</td></tr>
                            <tr><td class="text-muted">Weighted Rate</td><td class="fw-semibold">0.18 mm/yr</td></tr>
                            <tr><td class="text-muted">Trend</td><td><span class="badge bg-warning text-dark">Increasing</span></td></tr>
                            <tr><td class="text-muted">Data Points</td><td>4</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents Tab -->
    <div class="tab-pane fade" id="documents">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <span></span>
                    <button class="btn btn-sm btn-primary"><i class="bi bi-upload me-1"></i>Upload Document</button>
                </div>
                <table class="table table-sm">
                    <thead><tr><th>Document</th><th>Type</th><th>Uploaded</th><th>Size</th><th>Actions</th></tr></thead>
                    <tbody>
                        <tr><td><i class="bi bi-file-pdf text-danger me-1"></i>V-101_Design_Drawing.pdf</td><td>Design Drawing</td><td>Mar 2005</td><td>2.4 MB</td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button></td></tr>
                        <tr><td><i class="bi bi-file-pdf text-danger me-1"></i>V-101_Inspection_Report_2026.pdf</td><td>Inspection Report</td><td>Jan 2026</td><td>1.8 MB</td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button></td></tr>
                        <tr><td><i class="bi bi-file-earmark-excel text-success me-1"></i>V-101_Thickness_Data.xlsx</td><td>Thickness Data</td><td>Jan 2026</td><td>245 KB</td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
// Thickness Trend Chart
if(document.getElementById("thicknessTrendChart")) {
    new Chart(document.getElementById("thicknessTrendChart"), {
        type: "line",
        data: {
            labels: ["2020","2021","2022","2023","2024","2025","2026"],
            datasets: [
                { label: "Measured Thickness", data: [11.5,11.2,10.8,10.5,10.22,10.01,9.82], borderColor: "#3498db", backgroundColor: "rgba(52,152,219,.1)", fill: true, tension: 0.3 },
                { label: "Min Required", data: [4.2,4.2,4.2,4.2,4.2,4.2,4.2], borderColor: "#dc3545", borderDash: [5,5], pointRadius: 0, fill: false }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { title: { display: true, text: "Thickness (mm)" } } } }
    });
}
// Risk History Chart
if(document.getElementById("riskHistoryChart")) {
    new Chart(document.getElementById("riskHistoryChart"), {
        type: "line",
        data: {
            labels: ["Jan 2025","Jul 2025","Jan 2026"],
            datasets: [{ label: "Risk Value", data: [0.0412, 0.0623, 0.0845], borderColor: "#dc3545", backgroundColor: "rgba(220,53,69,.1)", fill: true, tension: 0.3 }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}
</script>';
require_once INCLUDES_PATH . '/footer.php';
