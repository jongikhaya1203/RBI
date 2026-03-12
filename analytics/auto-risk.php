<?php
/**
 * Automated Risk Scoring Dashboard - RBI Engineering Suite
 * Fleet risk overview, individual scoring, what-if analysis, Monte Carlo simulation.
 */
$pageTitle = 'Automated Risk Scoring';
$pageSection = 'Analytics';
$currentModule = 'analytics';
require_once __DIR__ . '/../config/app.php';
requireAuth();

$db = new Database();
$assets = $db->query(
    "SELECT id, asset_tag, asset_name, asset_type, criticality FROM asset_registry WHERE status = 'in_service' ORDER BY asset_tag"
)->fetchAll();

require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-shield-alt me-2"></i>Automated Risk Scoring</h1>
    <div class="d-flex gap-2">
        <button class="btn btn-danger btn-sm" id="btnGenerateAlerts">
            <i class="fas fa-bell me-1"></i>Generate Alerts
        </button>
        <button class="btn btn-primary btn-sm" id="btnBatchScore">
            <i class="fas fa-calculator me-1"></i>Score All Assets
        </button>
    </div>
</div>

<!-- Batch Progress Bar (hidden by default) -->
<div class="card mb-4 d-none" id="batchProgressCard">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
            <span class="fw-semibold">Scoring all assets...</span>
            <span id="batchProgressText">0%</span>
        </div>
        <div class="progress" style="height:8px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" id="batchProgressBar" style="width:0%"></div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabFleet">Fleet Overview</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabIndividual">Individual Asset</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabAlerts">Risk Alerts</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabWhatIf">What-If Analysis</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabMonteCarlo">Monte Carlo</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabOptimize">Inspection Optimization</a></li>
</ul>

<div class="tab-content">

    <!-- ═══════════ Fleet Overview Tab ═══════════ -->
    <div class="tab-pane fade show active" id="tabFleet">
        <div class="row g-4 mb-4">
            <!-- Stats Cards -->
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Total Scored</div>
                            <div class="stat-value" id="fleetTotalScored">--</div>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Mean Risk Score</div>
                            <div class="stat-value" id="fleetMeanRisk">--</div>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-chart-bar"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">High/Very High</div>
                            <div class="stat-value text-danger" id="fleetHighRisk">--</div>
                        </div>
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-exclamation-triangle"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Low/Very Low</div>
                            <div class="stat-value text-success" id="fleetLowRisk">--</div>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Risk Distribution Histogram -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Risk Distribution</div>
                    <div class="card-body">
                        <canvas id="fleetDistChart" height="280"></canvas>
                    </div>
                </div>
            </div>
            <!-- Risk by Asset Type -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><i class="fas fa-chart-pie me-2"></i>Risk by Asset Type</div>
                    <div class="card-body">
                        <canvas id="fleetTypeChart" height="280"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 10 Highest Risk -->
        <div class="card">
            <div class="card-header"><i class="fas fa-sort-amount-down me-2"></i>Top 10 Highest Risk Assets</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Asset Tag</th>
                                <th>Asset Name</th>
                                <th>POF Score</th>
                                <th>COF Score</th>
                                <th>Overall Risk</th>
                                <th>Risk Category</th>
                                <th>Health Index</th>
                            </tr>
                        </thead>
                        <tbody id="topRiskersTable">
                            <tr><td colspan="8" class="text-center text-muted py-4">Loading fleet data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ Individual Asset Tab ═══════════ -->
    <div class="tab-pane fade" id="tabIndividual">
        <div class="card mb-4">
            <div class="card-body py-3">
                <div class="row align-items-end g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Select Asset to Score</label>
                        <select id="indAssetSelector" class="form-select">
                            <option value="">-- Select an asset --</option>
                            <?php foreach ($assets as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= e($a['asset_tag']) ?> - <?= e($a['asset_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" id="btnScoreAsset" disabled>
                            <i class="fas fa-calculator me-1"></i>Score Asset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="indResultsContainer" class="d-none">
            <div class="row g-4 mb-4">
                <!-- Risk Score Card -->
                <div class="col-md-4">
                    <div class="card h-100 text-center">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <div class="text-muted small text-uppercase mb-2">Overall Risk Score</div>
                            <div class="display-3 fw-bold" id="indOverallRisk">--</div>
                            <span class="badge mt-2 fs-6" id="indRiskBadge">--</span>
                        </div>
                    </div>
                </div>
                <!-- POF / COF Breakdown -->
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-header"><i class="fas fa-chart-pie me-2"></i>POF / COF Breakdown</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <canvas id="pofRadarChart" height="200"></canvas>
                                    <div class="text-center mt-1 fw-semibold">POF Components</div>
                                </div>
                                <div class="col-6">
                                    <canvas id="cofRadarChart" height="200"></canvas>
                                    <div class="text-center mt-1 fw-semibold">COF Components</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Risk Trend -->
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-chart-line me-2"></i>Risk Trend Over Time</div>
                <div class="card-body">
                    <canvas id="riskTrendChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ Risk Alerts Tab ═══════════ -->
    <div class="tab-pane fade" id="tabAlerts">
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <select id="alertSeverityFilter" class="form-select form-select-sm">
                    <option value="">All Severities</option>
                    <option value="critical">Critical</option>
                    <option value="warning">Warning</option>
                    <option value="info">Info</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="alertTypeFilter" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="risk_increase">Risk Increase</option>
                    <option value="threshold_breach">Threshold Breach</option>
                    <option value="overdue_inspection">Overdue Inspection</option>
                    <option value="accelerating_degradation">Accelerating Degradation</option>
                    <option value="anomaly_detected">Anomaly Detected</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="alertAckFilter" class="form-select form-select-sm">
                    <option value="0">Unacknowledged</option>
                    <option value="1">Acknowledged</option>
                    <option value="">All</option>
                </select>
            </div>
            <div class="col-md-3 text-end">
                <button class="btn btn-outline-primary btn-sm" id="btnRefreshAlerts">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Severity</th>
                                <th>Type</th>
                                <th>Asset</th>
                                <th>Message</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="alertsTable">
                            <tr><td colspan="6" class="text-center text-muted py-4">Loading alerts...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ What-If Analysis Tab ═══════════ -->
    <div class="tab-pane fade" id="tabWhatIf">
        <div class="row g-4">
            <!-- Scenario Builder -->
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><i class="fas fa-sliders-h me-2"></i>Scenario Builder</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Asset</label>
                            <select id="wiAssetSelector" class="form-select">
                                <option value="">-- Select an asset --</option>
                                <?php foreach ($assets as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= e($a['asset_tag']) ?> - <?= e($a['asset_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <hr>
                        <h6>Scenario Parameters</h6>
                        <div class="mb-3">
                            <label class="form-label small">Inspection Interval (years)</label>
                            <input type="range" class="form-range" id="wiInspInterval" min="0.5" max="10" step="0.5" value="3">
                            <div class="d-flex justify-content-between small text-muted">
                                <span>0.5 yr</span><span id="wiInspIntervalVal">3.0 yr</span><span>10 yr</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Damage Mechanism Change</label>
                            <input type="range" class="form-range" id="wiDMChange" min="-3" max="3" step="1" value="0">
                            <div class="d-flex justify-content-between small text-muted">
                                <span>-3</span><span id="wiDMChangeVal">0 DMs</span><span>+3</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Temperature Change (%)</label>
                            <input type="range" class="form-range" id="wiTempChange" min="-50" max="50" step="5" value="0">
                            <div class="d-flex justify-content-between small text-muted">
                                <span>-50%</span><span id="wiTempChangeVal">0%</span><span>+50%</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Pressure Change (%)</label>
                            <input type="range" class="form-range" id="wiPressChange" min="-50" max="50" step="5" value="0">
                            <div class="d-flex justify-content-between small text-muted">
                                <span>-50%</span><span id="wiPressChangeVal">0%</span><span>+50%</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="wiCoating">
                                <label class="form-check-label small" for="wiCoating">Apply Coating/Lining</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="wiInhibitor">
                                <label class="form-check-label small" for="wiInhibitor">Apply Corrosion Inhibitor</label>
                            </div>
                        </div>
                        <button class="btn btn-primary w-100" id="btnRunWhatIf" disabled>
                            <i class="fas fa-play me-1"></i>Run Scenario Analysis
                        </button>
                    </div>
                </div>
            </div>
            <!-- Scenario Results -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Scenario Results</div>
                    <div class="card-body" id="wiResultsContainer">
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-sliders-h fa-3x mb-3"></i>
                            <p>Adjust parameters and click "Run Scenario Analysis" to see results.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ Monte Carlo Tab ═══════════ -->
    <div class="tab-pane fade" id="tabMonteCarlo">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><i class="fas fa-dice me-2"></i>Simulation Setup</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Asset</label>
                            <select id="mcAssetSelector" class="form-select">
                                <option value="">-- Select an asset --</option>
                                <?php foreach ($assets as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= e($a['asset_tag']) ?> - <?= e($a['asset_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Iterations</label>
                            <select id="mcIterations" class="form-select">
                                <option value="100">100 (Quick)</option>
                                <option value="500">500</option>
                                <option value="1000" selected>1,000 (Standard)</option>
                                <option value="5000">5,000 (High precision)</option>
                            </select>
                        </div>
                        <button class="btn btn-primary w-100" id="btnRunMC" disabled>
                            <i class="fas fa-play me-1"></i>Run Simulation
                        </button>
                    </div>
                </div>

                <!-- Percentile Table -->
                <div class="card mt-4 d-none" id="mcStatsCard">
                    <div class="card-header"><i class="fas fa-table me-2"></i>Percentile Table</div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Percentile</th><th>Risk Score</th></tr></thead>
                            <tbody id="mcPercentileTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><i class="fas fa-chart-area me-2"></i>Risk Distribution</div>
                    <div class="card-body" id="mcResultsContainer">
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-dice fa-3x mb-3"></i>
                            <p>Select an asset and run Monte Carlo simulation to visualize risk uncertainty.</p>
                        </div>
                    </div>
                </div>
                <div class="card mt-4 d-none" id="mcCategoryCard">
                    <div class="card-header"><i class="fas fa-chart-pie me-2"></i>Category Probabilities</div>
                    <div class="card-body">
                        <canvas id="mcCategoryChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ Inspection Optimization Tab ═══════════ -->
    <div class="tab-pane fade" id="tabOptimize">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-cogs me-2"></i>Risk-Based Inspection Optimization</span>
                <button class="btn btn-outline-success btn-sm" id="btnExportRisk">
                    <i class="fas fa-download me-1"></i>Export Risk Register
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Priority</th>
                                <th>Asset</th>
                                <th>Current Risk</th>
                                <th>Criticality</th>
                                <th>Recommended Interval</th>
                                <th>Current Next Planned</th>
                                <th>Last Inspection</th>
                                <th>Risk Reduction</th>
                                <th>Cost-Benefit</th>
                            </tr>
                        </thead>
                        <tbody id="optimizeTable">
                            <tr><td colspan="9" class="text-center text-muted py-4">Loading optimization data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const API_RISK = '<?= BASE_URL ?>/api/auto-risk.php';
    let charts = {};

    // ── Load initial data ───────────────────────────────────────
    loadFleetSummary();
    loadAlerts();
    loadOptimization();

    // ── Batch Score ──────────────────────────────────────────────
    document.getElementById('btnBatchScore').addEventListener('click', function() {
        if (!confirm('Score all in-service assets? This may take a moment.')) return;
        const card = document.getElementById('batchProgressCard');
        card.classList.remove('d-none');
        document.getElementById('batchProgressBar').style.width = '10%';
        document.getElementById('batchProgressText').textContent = 'Starting...';

        // Simulate progress while waiting
        let progress = 10;
        const interval = setInterval(() => {
            progress = Math.min(90, progress + Math.random() * 15);
            document.getElementById('batchProgressBar').style.width = progress + '%';
            document.getElementById('batchProgressText').textContent = Math.round(progress) + '%';
        }, 500);

        apiPost(API_RISK + '?action=batch_score', {}).then(data => {
            clearInterval(interval);
            document.getElementById('batchProgressBar').style.width = '100%';
            document.getElementById('batchProgressText').textContent = '100% - Complete!';
            const r = data.results || {};
            showToast('Batch scoring complete: ' + (r.scored || 0) + ' scored, ' + (r.failed || 0) + ' failed', 'success');
            setTimeout(() => card.classList.add('d-none'), 3000);
            loadFleetSummary();
        });
    });

    // ── Generate Alerts ──────────────────────────────────────────
    document.getElementById('btnGenerateAlerts').addEventListener('click', function() {
        apiPost(API_RISK + '?action=generate_alerts', {}).then(data => {
            showToast((data.alerts_generated || 0) + ' alerts generated', 'info');
            loadAlerts();
        });
    });

    // ── Fleet Summary ────────────────────────────────────────────
    function loadFleetSummary() {
        fetch(API_RISK + '?action=fleet_summary').then(r => r.json()).then(data => {
            if (!data.success) return;

            document.getElementById('fleetTotalScored').textContent = data.total_scored || 0;
            document.getElementById('fleetMeanRisk').textContent = data.statistics?.mean_risk || '--';
            document.getElementById('fleetHighRisk').textContent = ((data.distribution?.high || 0) + (data.distribution?.very_high || 0));
            document.getElementById('fleetLowRisk').textContent = ((data.distribution?.low || 0) + (data.distribution?.very_low || 0));

            renderFleetDistribution(data.distribution || {});
            renderFleetByType(data.by_type || {});
            renderTopRiskers(data.top_riskers || []);
        });
    }

    function renderFleetDistribution(dist) {
        if (charts.fleetDist) charts.fleetDist.destroy();
        charts.fleetDist = new Chart(document.getElementById('fleetDistChart'), {
            type: 'bar',
            data: {
                labels: ['Very Low', 'Low', 'Medium', 'High', 'Very High'],
                datasets: [{
                    label: 'Asset Count',
                    data: [dist.very_low||0, dist.low||0, dist.medium||0, dist.high||0, dist.very_high||0],
                    backgroundColor: ['#4caf50','#8bc34a','#ffc107','#ff5722','#b71c1c'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {y: {beginAtZero: true, ticks: {stepSize: 1}}},
                plugins: {legend: {display: false}}
            }
        });
    }

    function renderFleetByType(byType) {
        if (charts.fleetType) charts.fleetType.destroy();
        const labels = Object.keys(byType).map(t => t.replace(/_/g, ' '));
        const avgRisks = Object.values(byType).map(t => t.avg_risk);
        const counts = Object.values(byType).map(t => t.count);

        charts.fleetType = new Chart(document.getElementById('fleetTypeChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {label: 'Avg Risk', data: avgRisks, backgroundColor: 'rgba(63,81,181,0.7)', borderRadius: 4, yAxisID: 'y'},
                    {label: 'Count', data: counts, backgroundColor: 'rgba(255,152,0,0.5)', borderRadius: 4, yAxisID: 'y1'}
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: {position: 'left', title: {display: true, text: 'Avg Risk'}},
                    y1: {position: 'right', title: {display: true, text: 'Count'}, grid: {drawOnChartArea: false}}
                },
                plugins: {legend: {position: 'bottom'}}
            }
        });
    }

    function renderTopRiskers(riskers) {
        const tbody = document.getElementById('topRiskersTable');
        if (riskers.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No scored assets found. Click "Score All Assets" to begin.</td></tr>';
            return;
        }
        tbody.innerHTML = riskers.map((r, i) => {
            const catColors = {very_high:'danger',high:'warning',medium:'info',low:'success',very_low:'secondary'};
            const catColor = catColors[r.risk_category] || 'secondary';
            return `<tr>
                <td>${i+1}</td>
                <td><strong>${esc(r.asset_tag)}</strong></td>
                <td>${esc(r.asset_name)}</td>
                <td>${parseFloat(r.pof_score).toFixed(2)}</td>
                <td>${parseFloat(r.cof_score).toFixed(2)}</td>
                <td><strong>${parseFloat(r.overall_risk).toFixed(2)}</strong></td>
                <td><span class="badge bg-${catColor}">${(r.risk_category||'').replace(/_/g,' ')}</span></td>
                <td>${r.health_index ? parseFloat(r.health_index).toFixed(1) : 'N/A'}</td>
            </tr>`;
        }).join('');
    }

    // ── Individual Asset Scoring ─────────────────────────────────
    const indSelector = document.getElementById('indAssetSelector');
    indSelector.addEventListener('change', function() {
        document.getElementById('btnScoreAsset').disabled = !this.value;
    });

    document.getElementById('btnScoreAsset').addEventListener('click', function() {
        const assetId = parseInt(indSelector.value);
        if (!assetId) return;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Scoring...';

        apiPost(API_RISK + '?action=score', {asset_id: assetId}).then(data => {
            document.getElementById('btnScoreAsset').disabled = false;
            document.getElementById('btnScoreAsset').innerHTML = '<i class="fas fa-calculator me-1"></i>Score Asset';

            if (data.success) {
                document.getElementById('indResultsContainer').classList.remove('d-none');
                renderIndividualScore(data);
                loadRiskTrend(assetId);
                showToast('Asset scored: ' + data.risk_category.replace(/_/g,' '), 'success');
            } else {
                showToast('Scoring failed: ' + (data.error || ''), 'danger');
            }
        });
    });

    function renderIndividualScore(data) {
        const riskEl = document.getElementById('indOverallRisk');
        riskEl.textContent = parseFloat(data.overall_risk).toFixed(2);
        const catColors = {very_high:'danger',high:'warning',medium:'info',low:'success',very_low:'secondary'};
        const cc = catColors[data.risk_category] || 'secondary';
        riskEl.className = 'display-3 fw-bold text-' + cc;

        const badge = document.getElementById('indRiskBadge');
        badge.textContent = (data.risk_category||'').replace(/_/g,' ').toUpperCase();
        badge.className = 'badge mt-2 fs-6 bg-' + cc;

        // POF Radar
        if (charts.pofRadar) charts.pofRadar.destroy();
        const pComp = data.pof_details?.components || {};
        charts.pofRadar = new Chart(document.getElementById('pofRadarChart'), {
            type: 'radar',
            data: {
                labels: ['Thinning','Insp. Degrad.','Damage Mech.','Weibull','Age'],
                datasets: [{
                    data: [pComp.thinning?.score||0, pComp.inspection_degrad?.score||0, pComp.damage_mechanisms?.score||0, pComp.weibull?.score||0, pComp.age_equipment?.score||0],
                    borderColor: '#f44336', backgroundColor: 'rgba(244,67,54,0.15)', pointBackgroundColor: '#f44336'
                }]
            },
            options: {responsive:true, maintainAspectRatio:false, scales:{r:{min:0,max:5}}, plugins:{legend:{display:false}}}
        });

        // COF Radar
        if (charts.cofRadar) charts.cofRadar.destroy();
        const cComp = data.cof_details?.components || {};
        charts.cofRadar = new Chart(document.getElementById('cofRadarChart'), {
            type: 'radar',
            data: {
                labels: ['Fluid Hazard','Pressure/Temp','Inventory','Business Crit.','Env/Safety'],
                datasets: [{
                    data: [cComp.fluid_hazard?.score||0, cComp.pressure_temp?.score||0, cComp.inventory?.score||0, cComp.business_crit?.score||0, cComp.env_safety?.score||0],
                    borderColor: '#3f51b5', backgroundColor: 'rgba(63,81,181,0.15)', pointBackgroundColor: '#3f51b5'
                }]
            },
            options: {responsive:true, maintainAspectRatio:false, scales:{r:{min:0,max:5}}, plugins:{legend:{display:false}}}
        });
    }

    function loadRiskTrend(assetId) {
        fetch(API_RISK + '?action=risk_trend&asset_id=' + assetId + '&periods=20').then(r => r.json()).then(data => {
            if (!data.success) return;
            if (charts.riskTrend) charts.riskTrend.destroy();
            const scores = data.scores || [];
            charts.riskTrend = new Chart(document.getElementById('riskTrendChart'), {
                type: 'line',
                data: {
                    labels: scores.map(s => s.scored_at.split(' ')[0]),
                    datasets: [
                        {label: 'Overall Risk', data: scores.map(s => parseFloat(s.overall_risk)), borderColor: '#f44336', fill: false, tension: 0.3},
                        {label: 'POF', data: scores.map(s => parseFloat(s.pof_score)), borderColor: '#ff9800', borderDash: [5,3], fill: false, tension: 0.3},
                        {label: 'COF', data: scores.map(s => parseFloat(s.cof_score)), borderColor: '#3f51b5', borderDash: [5,3], fill: false, tension: 0.3}
                    ]
                },
                options: {responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}}}
            });
        });
    }

    // ── Alerts ───────────────────────────────────────────────────
    function loadAlerts() {
        const severity = document.getElementById('alertSeverityFilter').value;
        const type = document.getElementById('alertTypeFilter').value;
        const ack = document.getElementById('alertAckFilter').value;
        let url = API_RISK + '?action=alerts&limit=50';
        if (severity) url += '&severity=' + severity;
        if (type) url += '&type=' + type;
        if (ack !== '') url += '&acknowledged=' + ack;

        fetch(url).then(r => r.json()).then(data => {
            if (!data.success) return;
            const tbody = document.getElementById('alertsTable');
            const alerts = data.alerts || [];
            if (alerts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No alerts found</td></tr>';
                return;
            }
            tbody.innerHTML = alerts.map(a => {
                const sevColors = {critical:'danger',warning:'warning',info:'info'};
                const typeLabels = {risk_increase:'Risk Increase',threshold_breach:'Threshold Breach',overdue_inspection:'Overdue Inspection',accelerating_degradation:'Accel. Degradation',anomaly_detected:'Anomaly',model_drift:'Model Drift'};
                return `<tr>
                    <td><span class="badge bg-${sevColors[a.severity]||'secondary'}">${a.severity}</span></td>
                    <td><span class="badge bg-dark">${typeLabels[a.alert_type]||a.alert_type}</span></td>
                    <td><strong>${esc(a.asset_tag)}</strong></td>
                    <td class="small">${esc(a.message)}</td>
                    <td class="small">${a.created_at}</td>
                    <td>${a.acknowledged == 0 ? '<button class="btn btn-sm btn-outline-success btnAckAlert" data-id="'+a.id+'"><i class="fas fa-check"></i></button>' : '<span class="text-success"><i class="fas fa-check-circle"></i></span>'}</td>
                </tr>`;
            }).join('');

            // Bind acknowledge buttons
            document.querySelectorAll('.btnAckAlert').forEach(btn => {
                btn.addEventListener('click', function() {
                    apiPost(API_RISK + '?action=acknowledge', {alert_id: parseInt(this.dataset.id)}).then(() => loadAlerts());
                });
            });
        });
    }

    document.getElementById('btnRefreshAlerts').addEventListener('click', loadAlerts);
    ['alertSeverityFilter','alertTypeFilter','alertAckFilter'].forEach(id => {
        document.getElementById(id).addEventListener('change', loadAlerts);
    });

    // ── What-If Analysis ─────────────────────────────────────────
    const wiSelector = document.getElementById('wiAssetSelector');
    wiSelector.addEventListener('change', function() {
        document.getElementById('btnRunWhatIf').disabled = !this.value;
    });

    // Update slider labels
    ['wiInspInterval','wiDMChange','wiTempChange','wiPressChange'].forEach(id => {
        const el = document.getElementById(id);
        const label = document.getElementById(id + 'Val');
        el.addEventListener('input', function() {
            const suffixes = {wiInspInterval:' yr',wiDMChange:' DMs',wiTempChange:'%',wiPressChange:'%'};
            label.textContent = (parseFloat(this.value) > 0 && id !== 'wiInspInterval' ? '+' : '') + this.value + suffixes[id];
        });
    });

    document.getElementById('btnRunWhatIf').addEventListener('click', function() {
        const assetId = parseInt(wiSelector.value);
        if (!assetId) return;

        const scenarios = [{
            name: 'Custom Scenario',
            inspection_interval_years: parseFloat(document.getElementById('wiInspInterval').value),
            dm_change: parseInt(document.getElementById('wiDMChange').value),
            temp_change_pct: parseFloat(document.getElementById('wiTempChange').value),
            pressure_change_pct: parseFloat(document.getElementById('wiPressChange').value),
            apply_coating: document.getElementById('wiCoating').checked,
            apply_inhibitor: document.getElementById('wiInhibitor').checked
        }];

        apiPost(API_RISK + '?action=what_if', {asset_id: assetId, scenarios: scenarios}).then(data => {
            if (!data.success) {
                showToast('What-if analysis failed', 'danger');
                return;
            }
            renderWhatIfResults(data);
        });
    });

    function renderWhatIfResults(data) {
        const container = document.getElementById('wiResultsContainer');
        const baseline = data.baseline;
        const scenario = data.scenarios[0];

        const changeColor = scenario.risk_change_pct > 0 ? 'danger' : 'success';
        const changeIcon = scenario.risk_change_pct > 0 ? 'arrow-up' : 'arrow-down';

        container.innerHTML = `
            <div class="row g-3 mb-4">
                <div class="col-4 text-center">
                    <div class="border rounded-3 p-3">
                        <div class="text-muted small">Baseline Risk</div>
                        <div class="fs-3 fw-bold">${parseFloat(baseline.overall_risk).toFixed(2)}</div>
                        <span class="badge bg-secondary">${(baseline.risk_category||'').replace(/_/g,' ')}</span>
                    </div>
                </div>
                <div class="col-4 text-center d-flex align-items-center justify-content-center">
                    <div>
                        <i class="fas fa-${changeIcon} fa-2x text-${changeColor}"></i>
                        <div class="fw-bold text-${changeColor} mt-1">${scenario.risk_change_pct > 0 ? '+' : ''}${scenario.risk_change_pct}%</div>
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div class="border rounded-3 p-3">
                        <div class="text-muted small">Scenario Risk</div>
                        <div class="fs-3 fw-bold text-${changeColor}">${parseFloat(scenario.overall_risk).toFixed(2)}</div>
                        <span class="badge bg-${changeColor}">${(scenario.risk_category||'').replace(/_/g,' ')}</span>
                    </div>
                </div>
            </div>
            <canvas id="wiCompareChart" height="200"></canvas>
        `;

        new Chart(document.getElementById('wiCompareChart'), {
            type: 'bar',
            data: {
                labels: ['POF Score', 'COF Score', 'Overall Risk'],
                datasets: [
                    {label: 'Baseline', data: [baseline.pof_score, baseline.cof_score, baseline.overall_risk], backgroundColor: 'rgba(63,81,181,0.7)', borderRadius: 4},
                    {label: 'Scenario', data: [scenario.pof_score, scenario.cof_score, scenario.overall_risk], backgroundColor: scenario.risk_change_pct > 0 ? 'rgba(244,67,54,0.7)' : 'rgba(76,175,80,0.7)', borderRadius: 4}
                ]
            },
            options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {position: 'bottom'}}}
        });
    }

    // ── Monte Carlo ──────────────────────────────────────────────
    const mcSelector = document.getElementById('mcAssetSelector');
    mcSelector.addEventListener('change', function() {
        document.getElementById('btnRunMC').disabled = !this.value;
    });

    document.getElementById('btnRunMC').addEventListener('click', function() {
        const assetId = parseInt(mcSelector.value);
        if (!assetId) return;
        const iterations = parseInt(document.getElementById('mcIterations').value);

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Running...';

        apiPost(API_RISK + '?action=monte_carlo', {asset_id: assetId, iterations: iterations}).then(data => {
            document.getElementById('btnRunMC').disabled = false;
            document.getElementById('btnRunMC').innerHTML = '<i class="fas fa-play me-1"></i>Run Simulation';

            if (data.success) {
                renderMonteCarloResults(data);
                showToast('Monte Carlo simulation complete (' + iterations + ' iterations)', 'success');
            } else {
                showToast('Simulation failed: ' + (data.error || ''), 'danger');
            }
        });
    });

    function renderMonteCarloResults(data) {
        const container = document.getElementById('mcResultsContainer');
        const histogram = data.histogram || [];

        container.innerHTML = '<canvas id="mcHistogramChart" height="300"></canvas>';

        if (charts.mcHistogram) charts.mcHistogram.destroy();
        charts.mcHistogram = new Chart(document.getElementById('mcHistogramChart'), {
            type: 'bar',
            data: {
                labels: histogram.map(h => h.bin_start.toFixed(1)),
                datasets: [{
                    label: 'Frequency',
                    data: histogram.map(h => h.count),
                    backgroundColor: histogram.map(h => {
                        const mid = (h.bin_start + h.bin_end) / 2;
                        if (mid >= 20) return 'rgba(183,28,28,0.8)';
                        if (mid >= 15) return 'rgba(255,87,34,0.8)';
                        if (mid >= 8) return 'rgba(255,193,7,0.8)';
                        if (mid >= 4) return 'rgba(139,195,74,0.8)';
                        return 'rgba(76,175,80,0.8)';
                    }),
                    borderRadius: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: {title: {display: true, text: 'Risk Score'}},
                    y: {title: {display: true, text: 'Count'}, beginAtZero: true}
                },
                plugins: {
                    legend: {display: false},
                    title: {display: true, text: 'Risk Score Distribution (' + data.iterations + ' iterations)'}
                }
            }
        });

        // Percentile table
        const stats = data.statistics || {};
        document.getElementById('mcStatsCard').classList.remove('d-none');
        document.getElementById('mcPercentileTable').innerHTML = [
            ['P5', stats.p5], ['P10', stats.p10], ['P25', stats.p25],
            ['Median (P50)', stats.p50], ['P75', stats.p75], ['P90', stats.p90],
            ['P95', stats.p95], ['Mean', stats.mean], ['Std Dev', stats.std_dev]
        ].map(([label, val]) => `<tr><td>${label}</td><td class="fw-bold">${val?.toFixed(4) || '--'}</td></tr>`).join('');

        // Category probabilities
        const catProbs = data.category_probs || {};
        document.getElementById('mcCategoryCard').classList.remove('d-none');
        if (charts.mcCategory) charts.mcCategory.destroy();
        charts.mcCategory = new Chart(document.getElementById('mcCategoryChart'), {
            type: 'pie',
            data: {
                labels: ['Very Low','Low','Medium','High','Very High'],
                datasets: [{
                    data: [catProbs.very_low*100, catProbs.low*100, catProbs.medium*100, catProbs.high*100, catProbs.very_high*100],
                    backgroundColor: ['#4caf50','#8bc34a','#ffc107','#ff5722','#b71c1c']
                }]
            },
            options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {position: 'bottom'}}}
        });
    }

    // ── Optimization ─────────────────────────────────────────────
    function loadOptimization() {
        fetch(API_RISK + '?action=optimize').then(r => r.json()).then(data => {
            if (!data.success) return;
            const tbody = document.getElementById('optimizeTable');
            const recs = data.recommendations || [];
            if (recs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">No scored assets found. Score assets first.</td></tr>';
                return;
            }
            tbody.innerHTML = recs.map(r => {
                const prioColors = {urgent:'danger',high:'warning',medium:'info',routine:'secondary'};
                return `<tr>
                    <td><span class="badge bg-${prioColors[r.priority]||'secondary'}">${r.priority}</span></td>
                    <td><strong>${esc(r.asset_tag)}</strong><br><small class="text-muted">${esc(r.asset_name)}</small></td>
                    <td>${parseFloat(r.current_risk).toFixed(2)}</td>
                    <td>${r.criticality}</td>
                    <td class="fw-bold">${r.recommended_interval_years} yr</td>
                    <td>${r.current_next_planned || 'Not set'}</td>
                    <td>${r.last_inspection || 'Never'}</td>
                    <td>${parseFloat(r.risk_reduction_benefit).toFixed(2)}</td>
                    <td>${r.cost_benefit_ratio}</td>
                </tr>`;
            }).join('');
        });
    }

    // ── Export Risk Register ──────────────────────────────────────
    document.getElementById('btnExportRisk').addEventListener('click', function() {
        fetch(API_RISK + '?action=optimize').then(r => r.json()).then(data => {
            if (!data.success) return;
            const recs = data.recommendations || [];
            let csv = 'Priority,Asset Tag,Asset Name,Current Risk,Criticality,Recommended Interval (yr),Last Inspection,Risk Reduction,Cost-Benefit\n';
            recs.forEach(r => {
                csv += `${r.priority},"${r.asset_tag}","${r.asset_name}",${r.current_risk},${r.criticality},${r.recommended_interval_years},${r.last_inspection||''},${r.risk_reduction_benefit},${r.cost_benefit_ratio}\n`;
            });
            const blob = new Blob([csv], {type: 'text/csv'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = 'risk_register_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click(); URL.revokeObjectURL(url);
        });
    });

    // ── Helpers ──────────────────────────────────────────────────
    function apiPost(url, data) {
        return fetch(url, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)})
            .then(r => r.json()).catch(e => ({success:false, error:e.message}));
    }

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function showToast(message, type) {
        const container = document.querySelector('.main-content');
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
        alert.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        container.insertBefore(alert, container.firstChild);
        setTimeout(() => { if (alert.parentNode) alert.remove(); }, 5000);
    }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
