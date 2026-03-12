<?php
/**
 * Asset Clustering Dashboard - RBI Engineering Suite
 * K-means clustering visualization for fleet-level risk analysis.
 */
$pageTitle = 'Asset Clustering';
$pageSection = 'Analytics';
$currentModule = 'analytics';
require_once __DIR__ . '/../config/app.php';
requireAuth();

require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-project-diagram me-2"></i>Asset Clustering Analysis</h1>
    <div class="d-flex gap-2">
        <div class="input-group input-group-sm" style="width:180px;">
            <span class="input-group-text">Clusters (k)</span>
            <select id="clusterK" class="form-select form-select-sm">
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5" selected>5</option>
                <option value="6">6</option>
                <option value="8">8</option>
            </select>
        </div>
        <button class="btn btn-primary btn-sm" id="btnRunClustering">
            <i class="fas fa-play me-1"></i>Run Clustering
        </button>
    </div>
</div>

<!-- Cluster Summary Cards -->
<div class="row g-4 mb-4" id="clusterSummaryCards">
    <div class="col-12 text-center py-5 text-muted">
        <i class="fas fa-project-diagram fa-3x mb-3"></i>
        <h5>Click "Run Clustering" to group assets by risk profile</h5>
        <p>K-means clustering analyzes corrosion rate, age, criticality, damage mechanisms, and inspection compliance.</p>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4 d-none" id="chartsRow">
    <!-- Scatter Plot -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-chart-scatter me-2"></i>Cluster Visualization (2D Projection)</div>
            <div class="card-body">
                <canvas id="clusterScatterChart" height="400"></canvas>
            </div>
        </div>
    </div>
    <!-- Cluster Comparison Radar -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-chart-pie me-2"></i>Cluster Comparison</div>
            <div class="card-body">
                <canvas id="clusterRadarChart" height="400"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Asset Lists per Cluster -->
<div class="d-none" id="clusterTablesContainer">
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="clusterTabs" role="tablist"></ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content" id="clusterTabContent"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const API_ML = '<?= BASE_URL ?>/api/ml-predict.php';
    let charts = {};

    const clusterColors = [
        {bg: 'rgba(76,175,80,0.6)', border: '#4caf50'},
        {bg: 'rgba(33,150,243,0.6)', border: '#2196f3'},
        {bg: 'rgba(255,193,7,0.6)', border: '#ffc107'},
        {bg: 'rgba(255,87,34,0.6)', border: '#ff5722'},
        {bg: 'rgba(183,28,28,0.6)', border: '#b71c1c'},
        {bg: 'rgba(156,39,176,0.6)', border: '#9c27b0'},
        {bg: 'rgba(0,150,136,0.6)', border: '#009688'},
        {bg: 'rgba(121,85,72,0.6)', border: '#795548'},
    ];

    // ── Run Clustering ──────────────────────────────────────────
    document.getElementById('btnRunClustering').addEventListener('click', function() {
        const k = parseInt(document.getElementById('clusterK').value);
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Clustering...';

        fetch(API_ML + '?action=cluster', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({k: k})
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('btnRunClustering').disabled = false;
            document.getElementById('btnRunClustering').innerHTML = '<i class="fas fa-play me-1"></i>Run Clustering';

            if (data.success) {
                renderResults(data);
                showToast(data.total_assets + ' assets grouped into ' + k + ' clusters', 'success');
            } else {
                showToast('Clustering failed: ' + (data.error || 'Unknown error'), 'danger');
            }
        })
        .catch(err => {
            document.getElementById('btnRunClustering').disabled = false;
            document.getElementById('btnRunClustering').innerHTML = '<i class="fas fa-play me-1"></i>Run Clustering';
            showToast('Error: ' + err.message, 'danger');
        });
    });

    // ── Also try to load existing clusters ──────────────────────
    fetch(API_ML + '?action=get_clusters').then(r => r.json()).then(data => {
        if (data.success && data.clusters && data.clusters.length > 0) {
            renderExistingClusters(data.clusters);
        }
    });

    function renderExistingClusters(clusters) {
        // Transform to match the format from clusterAssets()
        const mockData = {
            success: true,
            k: clusters.length,
            total_assets: clusters.reduce((sum, c) => sum + c.assets.length, 0),
            feature_names: ['Corrosion Rate', 'Age', 'Criticality', 'DM Count', 'Overdue Inspections'],
            clusters: {}
        };

        clusters.forEach((c, idx) => {
            mockData.clusters[idx] = {
                name: c.cluster_name,
                count: c.assets.length,
                centroid: c.centroid,
                assets: c.assets.map(a => ({
                    asset_id: a.asset_id,
                    asset_tag: a.asset_tag,
                    asset_name: a.asset_name,
                    distance: parseFloat(a.distance_to_centroid),
                    features: []
                }))
            };
        });

        renderResults(mockData);
    }

    function renderResults(data) {
        const clusters = data.clusters || {};
        const k = Object.keys(clusters).length;
        const featureNames = data.feature_names || ['F1','F2','F3','F4','F5'];

        // Show containers
        document.getElementById('chartsRow').classList.remove('d-none');
        document.getElementById('clusterTablesContainer').classList.remove('d-none');

        // ── Summary Cards ───────────────────────────────────────
        const cardsContainer = document.getElementById('clusterSummaryCards');
        cardsContainer.innerHTML = '';

        Object.keys(clusters).forEach((cid, idx) => {
            const cluster = clusters[cid];
            const color = clusterColors[idx % clusterColors.length];
            const cardHtml = `
                <div class="col-md-${12 / Math.min(k, 6)}">
                    <div class="card h-100" style="border-left: 4px solid ${color.border};">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">${esc(cluster.name || 'Cluster ' + cid)}</h6>
                                <span class="badge" style="background:${color.border}">${cluster.count || cluster.assets?.length || 0}</span>
                            </div>
                            <div class="small text-muted">assets in this group</div>
                        </div>
                    </div>
                </div>
            `;
            cardsContainer.innerHTML += cardHtml;
        });

        // ── Scatter Plot ────────────────────────────────────────
        renderScatterChart(clusters, featureNames);

        // ── Radar Chart ─────────────────────────────────────────
        renderRadarChart(clusters, featureNames);

        // ── Asset Tables ────────────────────────────────────────
        renderClusterTables(clusters);
    }

    function renderScatterChart(clusters, featureNames) {
        if (charts.scatter) charts.scatter.destroy();

        const datasets = [];
        Object.keys(clusters).forEach((cid, idx) => {
            const cluster = clusters[cid];
            const color = clusterColors[idx % clusterColors.length];
            const assets = cluster.assets || [];

            // Use first two features for 2D projection, or distance/index
            const points = assets.map((a, i) => {
                if (a.features && a.features.length >= 2) {
                    return {x: a.features[0], y: a.features[1]};
                }
                // Fallback: use distance and index
                return {x: a.distance || Math.random(), y: i * 0.1 + Math.random() * 0.5};
            });

            datasets.push({
                label: cluster.name || 'Cluster ' + cid,
                data: points,
                backgroundColor: color.bg,
                borderColor: color.border,
                pointRadius: 6,
                pointHoverRadius: 9
            });

            // Add centroid marker
            if (cluster.centroid && cluster.centroid.length >= 2) {
                datasets.push({
                    label: 'Centroid ' + cid,
                    data: [{x: cluster.centroid[0], y: cluster.centroid[1]}],
                    backgroundColor: color.border,
                    borderColor: '#000',
                    borderWidth: 2,
                    pointRadius: 12,
                    pointStyle: 'star',
                    showLine: false
                });
            }
        });

        charts.scatter = new Chart(document.getElementById('clusterScatterChart'), {
            type: 'scatter',
            data: {datasets},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {title: {display: true, text: featureNames[0] || 'Feature 1'}},
                    y: {title: {display: true, text: featureNames[1] || 'Feature 2'}}
                },
                plugins: {
                    legend: {position: 'bottom', labels: {
                        filter: (item) => !item.text.startsWith('Centroid')
                    }},
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const ds = ctx.dataset;
                                if (ds.label.startsWith('Centroid')) return ds.label;
                                const cluster = ds.label;
                                return cluster + ': (' + ctx.parsed.x.toFixed(2) + ', ' + ctx.parsed.y.toFixed(2) + ')';
                            }
                        }
                    }
                }
            }
        });
    }

    function renderRadarChart(clusters, featureNames) {
        if (charts.radar) charts.radar.destroy();

        const datasets = [];
        Object.keys(clusters).forEach((cid, idx) => {
            const cluster = clusters[cid];
            const color = clusterColors[idx % clusterColors.length];
            const centroid = cluster.centroid || [];

            if (centroid.length > 0) {
                datasets.push({
                    label: cluster.name || 'Cluster ' + cid,
                    data: centroid.map(v => (v * 100).toFixed(1)),
                    borderColor: color.border,
                    backgroundColor: color.bg.replace('0.6', '0.15'),
                    pointBackgroundColor: color.border,
                    pointRadius: 4
                });
            }
        });

        charts.radar = new Chart(document.getElementById('clusterRadarChart'), {
            type: 'radar',
            data: {
                labels: featureNames,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {r: {min: 0, max: 100, ticks: {stepSize: 20}}},
                plugins: {legend: {position: 'bottom', labels: {padding: 10, boxWidth: 12}}}
            }
        });
    }

    function renderClusterTables(clusters) {
        const tabsContainer = document.getElementById('clusterTabs');
        const contentContainer = document.getElementById('clusterTabContent');
        tabsContainer.innerHTML = '';
        contentContainer.innerHTML = '';

        Object.keys(clusters).forEach((cid, idx) => {
            const cluster = clusters[cid];
            const color = clusterColors[idx % clusterColors.length];
            const assets = cluster.assets || [];
            const isActive = idx === 0 ? 'active' : '';
            const isShow = idx === 0 ? 'show active' : '';

            // Tab
            tabsContainer.innerHTML += `
                <li class="nav-item">
                    <a class="nav-link ${isActive}" data-bs-toggle="tab" href="#clusterPane${cid}">
                        <span class="badge me-1" style="background:${color.border}">${assets.length}</span>
                        ${esc(cluster.name || 'Cluster ' + cid)}
                    </a>
                </li>
            `;

            // Tab pane
            let tableRows = '';
            if (assets.length === 0) {
                tableRows = '<tr><td colspan="4" class="text-center text-muted py-3">No assets in this cluster</td></tr>';
            } else {
                tableRows = assets.map((a, i) => `
                    <tr>
                        <td>${i + 1}</td>
                        <td><strong>${esc(a.asset_tag)}</strong></td>
                        <td>${esc(a.asset_name)}</td>
                        <td>${a.distance !== undefined ? a.distance.toFixed(4) : '--'}</td>
                    </tr>
                `).join('');
            }

            contentContainer.innerHTML += `
                <div class="tab-pane fade ${isShow}" id="clusterPane${cid}">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr><th>#</th><th>Asset Tag</th><th>Asset Name</th><th>Distance to Centroid</th></tr>
                            </thead>
                            <tbody>${tableRows}</tbody>
                        </table>
                    </div>
                </div>
            `;
        });
    }

    // ── Helpers ──────────────────────────────────────────────────
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
