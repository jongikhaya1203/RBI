<?php
/**
 * Predictive Analytics Dashboard - RBI Engineering Suite
 * ML-powered corrosion prediction, failure probability, health index, and anomaly detection.
 */
$pageTitle = 'Predictive Analytics';
$pageSection = 'Analytics';
$currentModule = 'analytics';
require_once __DIR__ . '/../config/app.php';
requireAuth();

$db = new Database();
$assets = $db->query(
    "SELECT id, asset_tag, asset_name, asset_type FROM asset_registry WHERE status = 'in_service' ORDER BY asset_tag"
)->fetchAll();

require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-brain me-2"></i>Predictive Analytics</h1>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" id="btnRetrainAll" title="Retrain all ML models">
            <i class="fas fa-sync-alt me-1"></i>Retrain All Models
        </button>
    </div>
</div>

<!-- Asset Selector -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row align-items-end g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Select Asset</label>
                <select id="assetSelector" class="form-select">
                    <option value="">-- Select an asset --</option>
                    <?php foreach ($assets as $a): ?>
                    <option value="<?= $a['id'] ?>" data-type="<?= e($a['asset_type']) ?>">
                        <?= e($a['asset_tag']) ?> - <?= e($a['asset_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Prediction Horizon</label>
                <select id="horizonSelect" class="form-select">
                    <option value="2">2 Years</option>
                    <option value="5" selected>5 Years</option>
                    <option value="10">10 Years</option>
                    <option value="20">20 Years</option>
                </select>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-primary me-2" id="btnTrain" disabled>
                    <i class="fas fa-graduation-cap me-1"></i>Train Model
                </button>
                <button class="btn btn-success me-2" id="btnPredict" disabled>
                    <i class="fas fa-chart-line me-1"></i>Predict
                </button>
                <button class="btn btn-warning" id="btnDetectAnomalies" disabled>
                    <i class="fas fa-exclamation-circle me-1"></i>Detect Anomalies
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="d-none">
    <div class="d-flex align-items-center justify-content-center p-4">
        <div class="spinner-border text-primary me-3" role="status"></div>
        <span class="text-muted" id="loadingMessage">Processing...</span>
    </div>
</div>

<!-- Results Container (hidden until asset selected) -->
<div id="resultsContainer" class="d-none">

    <!-- Row 1: Model Metrics + Health Index -->
    <div class="row g-4 mb-4">
        <!-- Model Accuracy Panel -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-bullseye me-2"></i>Model Accuracy Metrics</span>
                    <span class="badge bg-primary" id="modelTypeBadge">--</span>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-md-3">
                            <div class="border rounded-3 p-3">
                                <div class="text-muted small text-uppercase">R-Squared</div>
                                <div class="fs-3 fw-bold text-primary" id="metricR2">--</div>
                                <div class="text-muted small">Goodness of fit</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded-3 p-3">
                                <div class="text-muted small text-uppercase">RMSE</div>
                                <div class="fs-3 fw-bold text-info" id="metricRMSE">--</div>
                                <div class="text-muted small">mm</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded-3 p-3">
                                <div class="text-muted small text-uppercase">MAE</div>
                                <div class="fs-3 fw-bold text-success" id="metricMAE">--</div>
                                <div class="text-muted small">mm</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded-3 p-3">
                                <div class="text-muted small text-uppercase">Data Points</div>
                                <div class="fs-3 fw-bold text-secondary" id="metricPoints">--</div>
                                <div class="text-muted small">measurements</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3" id="modelInfoText"></div>
                </div>
            </div>
        </div>

        <!-- Health Index Gauge -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><i class="fas fa-heartbeat me-2"></i>Health Index</div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <canvas id="healthGaugeChart" width="220" height="160"></canvas>
                    <div class="fs-2 fw-bold mt-2" id="healthIndexValue">--</div>
                    <div class="text-muted" id="healthCategory">--</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Corrosion Prediction + Failure Probability -->
    <div class="row g-4 mb-4">
        <!-- Corrosion Prediction Chart -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-chart-area me-2"></i>Corrosion Rate Prediction</span>
                    <span class="badge bg-info" id="remainingLifeBadge">--</span>
                </div>
                <div class="card-body">
                    <canvas id="corrosionChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Failure Probability (Weibull) -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Failure Probability (Weibull)</div>
                <div class="card-body">
                    <canvas id="weibullChart" height="300"></canvas>
                    <div class="row g-2 mt-3 text-center" id="weibullStats">
                        <div class="col-4">
                            <div class="small text-muted">POF Now</div>
                            <div class="fw-bold" id="pofNow">--</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">POF 1yr</div>
                            <div class="fw-bold" id="pof1yr">--</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">MTTF</div>
                            <div class="fw-bold" id="mttfValue">--</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: Anomaly Detection Timeline -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-search me-2"></i>Anomaly Detection Timeline</span>
                    <span class="badge bg-danger" id="anomalyCountBadge">0 anomalies</span>
                </div>
                <div class="card-body">
                    <canvas id="anomalyChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 4: Trend Analysis + Health Components -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><i class="fas fa-wave-square me-2"></i>Trend Analysis</div>
                <div class="card-body">
                    <canvas id="trendChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><i class="fas fa-radar me-2"></i>Health Index Components</div>
                <div class="card-body">
                    <canvas id="healthRadarChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- No Asset Selected Placeholder -->
<div id="noAssetPlaceholder">
    <div class="text-center py-5">
        <i class="fas fa-brain fa-4x text-muted mb-3"></i>
        <h4 class="text-muted">Select an Asset to Begin</h4>
        <p class="text-muted">Choose an asset from the dropdown above to view predictive analytics,<br>
        train ML models, and detect anomalies in inspection data.</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const API_ML = '<?= BASE_URL ?>/api/ml-predict.php';
    let charts = {};
    let selectedAssetId = null;

    // ── Asset Selector ──────────────────────────────────────────
    document.getElementById('assetSelector').addEventListener('change', function() {
        selectedAssetId = this.value ? parseInt(this.value) : null;
        const btns = ['btnTrain','btnPredict','btnDetectAnomalies'];
        btns.forEach(b => document.getElementById(b).disabled = !selectedAssetId);

        if (selectedAssetId) {
            document.getElementById('noAssetPlaceholder').classList.add('d-none');
            document.getElementById('resultsContainer').classList.remove('d-none');
            loadAllData();
        } else {
            document.getElementById('noAssetPlaceholder').classList.remove('d-none');
            document.getElementById('resultsContainer').classList.add('d-none');
        }
    });

    // ── Button Handlers ─────────────────────────────────────────
    document.getElementById('btnTrain').addEventListener('click', function() {
        if (!selectedAssetId) return;
        showLoading('Training corrosion model...');
        apiPost(API_ML + '?action=train', {asset_id: selectedAssetId, model_type: 'corrosion'})
            .then(data => {
                hideLoading();
                if (data.success) {
                    showToast('Model trained successfully! R² = ' + (data.metrics?.r_squared || 'N/A'), 'success');
                    loadAllData();
                } else {
                    showToast('Training failed: ' + (data.error || 'Unknown error'), 'danger');
                }
            });
    });

    document.getElementById('btnPredict').addEventListener('click', function() {
        if (!selectedAssetId) return;
        const horizon = document.getElementById('horizonSelect').value;
        showLoading('Generating predictions...');
        apiPost(API_ML + '?action=predict', {
            asset_id: selectedAssetId,
            prediction_type: 'corrosion_rate',
            horizon: parseFloat(horizon)
        }).then(data => {
            hideLoading();
            if (data.success) {
                renderCorrosionChart(data);
                showToast('Prediction generated successfully', 'success');
            } else {
                showToast('Prediction failed: ' + (data.error || 'Unknown error'), 'warning');
            }
        });
    });

    document.getElementById('btnDetectAnomalies').addEventListener('click', function() {
        if (!selectedAssetId) return;
        showLoading('Running anomaly detection...');
        apiPost(API_ML + '?action=anomaly_detect', {asset_id: selectedAssetId})
            .then(data => {
                hideLoading();
                if (data.success) {
                    renderAnomalyChart(data);
                    showToast(data.anomaly_count + ' anomalies detected', data.anomaly_count > 0 ? 'warning' : 'success');
                } else {
                    showToast('Anomaly detection failed: ' + (data.error || 'Unknown error'), 'warning');
                }
            });
    });

    document.getElementById('btnRetrainAll').addEventListener('click', function() {
        if (!confirm('Retrain all ML models? This may take a few moments.')) return;
        showLoading('Retraining all models...');
        apiPost(API_ML + '?action=retrain_all', {})
            .then(data => {
                hideLoading();
                showToast('Retrained ' + (data.results?.trained || 0) + ' models, ' + (data.results?.failed || 0) + ' failed', 'info');
                if (selectedAssetId) loadAllData();
            });
    });

    // ── Load All Data ───────────────────────────────────────────
    function loadAllData() {
        const horizon = document.getElementById('horizonSelect').value;

        // Load corrosion prediction
        apiPost(API_ML + '?action=predict', {
            asset_id: selectedAssetId, prediction_type: 'corrosion_rate', horizon: parseFloat(horizon)
        }).then(data => { if (data.success) renderCorrosionChart(data); });

        // Load failure probability
        apiPost(API_ML + '?action=predict', {
            asset_id: selectedAssetId, prediction_type: 'failure_probability', horizon: parseFloat(horizon)
        }).then(data => { if (data.success) renderWeibullChart(data); });

        // Load health index
        apiPost(API_ML + '?action=health_index', {asset_id: selectedAssetId})
            .then(data => { if (data.success) renderHealthIndex(data); });

        // Load anomaly detection
        apiPost(API_ML + '?action=anomaly_detect', {asset_id: selectedAssetId})
            .then(data => { if (data.success) renderAnomalyChart(data); });

        // Load trend analysis
        apiPost(API_ML + '?action=trend', {asset_id: selectedAssetId, parameter: 'thickness'})
            .then(data => { if (data.success) renderTrendChart(data); });

        // Load model info
        fetch(API_ML + '?action=models&asset_id=' + selectedAssetId)
            .then(r => r.json())
            .then(data => { if (data.success) renderModelInfo(data.models); });
    }

    // ── Chart Renderers ─────────────────────────────────────────

    function renderCorrosionChart(data) {
        if (charts.corrosion) charts.corrosion.destroy();

        const historical = (data.historical || []).map(h => ({x: h.date, y: h.thickness}));
        const predicted = (data.predictions || []).map(p => ({x: p.date, y: p.predicted_thickness}));
        const upper = (data.predictions || []).map(p => ({x: p.date, y: p.confidence_upper}));
        const lower = (data.predictions || []).map(p => ({x: p.date, y: p.confidence_lower}));

        // Min thickness line
        const tMin = data.min_thickness;
        const allDates = [...historical.map(h => h.x), ...predicted.map(p => p.x)];

        const datasets = [
            {
                label: 'Historical Thickness',
                data: historical,
                borderColor: '#3f51b5',
                backgroundColor: '#3f51b5',
                pointRadius: 5,
                pointStyle: 'circle',
                showLine: true,
                borderWidth: 2,
                order: 1
            },
            {
                label: 'Predicted Thickness',
                data: predicted,
                borderColor: '#4caf50',
                borderDash: [5, 5],
                pointRadius: 2,
                showLine: true,
                borderWidth: 2,
                fill: false,
                order: 2
            },
            {
                label: '95% Confidence Upper',
                data: upper,
                borderColor: 'rgba(76,175,80,0.2)',
                backgroundColor: 'rgba(76,175,80,0.1)',
                pointRadius: 0,
                showLine: true,
                borderWidth: 1,
                fill: '+1',
                order: 3
            },
            {
                label: '95% Confidence Lower',
                data: lower,
                borderColor: 'rgba(76,175,80,0.2)',
                pointRadius: 0,
                showLine: true,
                borderWidth: 1,
                fill: false,
                order: 4
            }
        ];

        if (tMin) {
            datasets.push({
                label: 'Minimum Required (t_min)',
                data: allDates.map(d => ({x: d, y: tMin})),
                borderColor: '#f44336',
                borderDash: [10, 5],
                pointRadius: 0,
                showLine: true,
                borderWidth: 2,
                fill: false,
                order: 5
            });
        }

        charts.corrosion = new Chart(document.getElementById('corrosionChart'), {
            type: 'scatter',
            data: {datasets},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {type: 'category', title: {display: true, text: 'Date'}},
                    y: {title: {display: true, text: 'Thickness (mm)'}, beginAtZero: false}
                },
                plugins: {
                    legend: {position: 'bottom', labels: {usePointStyle: true, padding: 15}},
                    tooltip: {mode: 'nearest'}
                }
            }
        });

        // Update remaining life badge
        const rl = data.remaining_life;
        const rlBadge = document.getElementById('remainingLifeBadge');
        if (rl !== null && rl !== undefined) {
            rlBadge.textContent = 'Remaining Life: ' + rl + ' years';
            rlBadge.className = 'badge ' + (rl < 3 ? 'bg-danger' : rl < 7 ? 'bg-warning text-dark' : 'bg-success');
        } else {
            rlBadge.textContent = 'Remaining Life: N/A';
            rlBadge.className = 'badge bg-secondary';
        }

        // Update model metrics
        document.getElementById('metricR2').textContent = data.r_squared !== undefined ? data.r_squared.toFixed(4) : '--';
        document.getElementById('modelTypeBadge').textContent = (data.model_type || '--').replace('_', ' ');
    }

    function renderWeibullChart(data) {
        if (charts.weibull) charts.weibull.destroy();

        const curve = data.curve || [];
        const labels = curve.map(c => c.age_years + 'yr');
        const pofData = curve.map(c => (c.pof * 100));
        const reliabilityData = curve.map(c => (c.reliability * 100));

        charts.weibull = new Chart(document.getElementById('weibullChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Failure Probability (%)',
                        data: pofData,
                        borderColor: '#f44336',
                        backgroundColor: 'rgba(244,67,54,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 1
                    },
                    {
                        label: 'Reliability (%)',
                        data: reliabilityData,
                        borderColor: '#4caf50',
                        backgroundColor: 'rgba(76,175,80,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {min: 0, max: 100, title: {display: true, text: '%'}},
                    x: {title: {display: true, text: 'Asset Age'}}
                },
                plugins: {legend: {position: 'bottom', labels: {usePointStyle: true}}}
            }
        });

        // Update stats
        document.getElementById('pofNow').textContent = data.pof_current !== undefined ? (data.pof_current * 100).toFixed(2) + '%' : '--';
        document.getElementById('pof1yr').textContent = data.pof_1yr !== undefined ? (data.pof_1yr * 100).toFixed(2) + '%' : '--';
        document.getElementById('mttfValue').textContent = data.mttf !== undefined ? data.mttf + ' yr' : '--';
    }

    function renderHealthIndex(data) {
        if (charts.healthGauge) charts.healthGauge.destroy();
        if (charts.healthRadar) charts.healthRadar.destroy();

        const hi = data.health_index || 0;
        const category = data.category || 'unknown';

        document.getElementById('healthIndexValue').textContent = hi.toFixed(1);
        document.getElementById('healthCategory').textContent = category.charAt(0).toUpperCase() + category.slice(1);

        // Color based on category
        const colors = {excellent:'#4caf50', good:'#8bc34a', fair:'#ffc107', poor:'#ff9800', critical:'#f44336'};
        document.getElementById('healthIndexValue').style.color = colors[category] || '#666';

        // Gauge chart (doughnut)
        const gaugeColor = colors[category] || '#666';
        charts.healthGauge = new Chart(document.getElementById('healthGaugeChart'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [hi, 100 - hi],
                    backgroundColor: [gaugeColor, '#e9ecef'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                circumference: 180,
                rotation: -90,
                cutout: '75%',
                plugins: {legend: {display: false}, tooltip: {enabled: false}}
            }
        });

        // Radar chart for components
        const comp = data.components || {};
        const labels = ['Age', 'Corrosion', 'Inspection', 'Damage Mech.', 'Operating'];
        const values = [
            comp.age_factor?.value || 0,
            comp.corrosion_factor?.value || 0,
            comp.inspection_factor?.value || 0,
            comp.damage_mech_factor?.value || 0,
            comp.operating_severity?.value || 0
        ];

        charts.healthRadar = new Chart(document.getElementById('healthRadarChart'), {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Health Score',
                    data: values,
                    borderColor: '#3f51b5',
                    backgroundColor: 'rgba(63,81,181,0.15)',
                    pointBackgroundColor: '#3f51b5',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {r: {min: 0, max: 100, ticks: {stepSize: 20}}},
                plugins: {legend: {display: false}}
            }
        });
    }

    function renderAnomalyChart(data) {
        if (charts.anomaly) charts.anomaly.destroy();

        const readings = data.all_readings || [];
        const labels = readings.map(r => r.measurement_date);
        const values = readings.map(r => parseFloat(r.measured_thickness_mm));
        const bgColors = readings.map(r => r.is_anomaly ? 'rgba(244,67,54,0.8)' : 'rgba(63,81,181,0.6)');
        const borderColors = readings.map(r => r.is_anomaly ? '#f44336' : '#3f51b5');
        const pointRadii = readings.map(r => r.is_anomaly ? 8 : 4);

        charts.anomaly = new Chart(document.getElementById('anomalyChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Thickness Reading',
                    data: values,
                    borderColor: '#3f51b5',
                    backgroundColor: 'transparent',
                    pointBackgroundColor: bgColors,
                    pointBorderColor: borderColors,
                    pointRadius: pointRadii,
                    pointHoverRadius: 10,
                    tension: 0.2,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {title: {display: true, text: 'Thickness (mm)'}},
                    x: {title: {display: true, text: 'Date'}}
                },
                plugins: {
                    legend: {display: false},
                    tooltip: {
                        callbacks: {
                            afterLabel: function(ctx) {
                                const r = readings[ctx.dataIndex];
                                if (r && r.is_anomaly) {
                                    return 'ANOMALY: Z=' + r.z_score + ', Methods: ' + (r.anomaly_methods || []).join(', ');
                                }
                                return '';
                            }
                        }
                    }
                }
            }
        });

        document.getElementById('anomalyCountBadge').textContent = (data.anomaly_count || 0) + ' anomalies';
        document.getElementById('anomalyCountBadge').className = 'badge ' + (data.anomaly_count > 0 ? 'bg-danger' : 'bg-success');
    }

    function renderTrendChart(data) {
        if (charts.trend) charts.trend.destroy();

        const series = data.series || [];
        const forecast = data.forecast || [];
        const labels = [...series.map(s => s.date), ...forecast.map(f => f.date)];

        charts.trend = new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Actual',
                        data: [...series.map(s => s.actual), ...forecast.map(() => null)],
                        borderColor: '#3f51b5',
                        pointRadius: 3,
                        borderWidth: 2,
                        tension: 0.1
                    },
                    {
                        label: 'Trend (Moving Avg)',
                        data: [...series.map(s => s.trend), ...forecast.map(() => null)],
                        borderColor: '#ff9800',
                        borderDash: [5, 3],
                        pointRadius: 0,
                        borderWidth: 2,
                        tension: 0.3
                    },
                    {
                        label: 'Smoothed (EMA)',
                        data: [...series.map(s => s.smoothed), ...forecast.map(() => null)],
                        borderColor: '#4caf50',
                        pointRadius: 0,
                        borderWidth: 2,
                        tension: 0.3
                    },
                    {
                        label: 'Forecast',
                        data: [...series.map(() => null), ...forecast.map(f => f.value)],
                        borderColor: '#9c27b0',
                        borderDash: [8, 4],
                        pointRadius: 4,
                        borderWidth: 2,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {title: {display: true, text: 'Thickness (mm)'}},
                    x: {title: {display: true, text: 'Date'}}
                },
                plugins: {legend: {position: 'bottom', labels: {usePointStyle: true}}}
            }
        });
    }

    function renderModelInfo(models) {
        if (!models || models.length === 0) {
            document.getElementById('modelInfoText').innerHTML =
                '<div class="alert alert-info mb-0 small"><i class="fas fa-info-circle me-2"></i>No trained models found. Click "Train Model" to get started.</div>';
            return;
        }

        const active = models.find(m => m.status === 'active');
        if (active) {
            document.getElementById('metricR2').textContent = parseFloat(active.r_squared || 0).toFixed(4);
            document.getElementById('metricRMSE').textContent = parseFloat(active.rmse || 0).toFixed(4);
            document.getElementById('metricMAE').textContent = parseFloat(active.mae || 0).toFixed(4);
            document.getElementById('metricPoints').textContent = active.training_data_points || '--';
            document.getElementById('modelTypeBadge').textContent = (active.model_type || '').replace(/_/g, ' ');

            document.getElementById('modelInfoText').innerHTML =
                '<div class="small text-muted mt-2"><i class="fas fa-clock me-1"></i>Last trained: ' + (active.trained_at || 'N/A') + ' | Models available: ' + models.length + '</div>';
        }
    }

    // ── API Helpers ─────────────────────────────────────────────
    function apiPost(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        }).then(r => r.json()).catch(err => {
            console.error('API error:', err);
            hideLoading();
            return {success: false, error: err.message};
        });
    }

    function showLoading(msg) {
        document.getElementById('loadingOverlay').classList.remove('d-none');
        document.getElementById('loadingMessage').textContent = msg || 'Processing...';
    }

    function hideLoading() {
        document.getElementById('loadingOverlay').classList.add('d-none');
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
