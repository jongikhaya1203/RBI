<?php
/**
 * OSIsoft PI (AVEVA PI) Integration Page
 * Configure PI Web API connection, tag mappings, real-time data, and excursion monitoring
 */
$pageTitle = 'OSIsoft PI Integration';
$pageSection = 'Integrations';
$currentModule = 'integrations';
require_once __DIR__ . '/../config/app.php';
requireAuth();
require_once INCLUDES_PATH . '/header.php';

$db = new Database();

$piConfig = $db->query(
    "SELECT * FROM integration_configs WHERE vendor LIKE '%PI%' OR vendor LIKE '%OSIsoft%' OR vendor LIKE '%AVEVA%' LIMIT 1"
)->fetch();

// Get tag mappings
$tagMappings = $db->query(
    "SELECT ptm.*, ar.asset_tag, ar.equipment_name
     FROM pi_tag_mappings ptm
     JOIN asset_registry ar ON ptm.asset_id = ar.id
     WHERE ptm.is_active = 1
     ORDER BY ar.asset_tag, ptm.parameter_type"
)->fetchAll();

// Get recent excursions
$excursions = $db->query(
    "SELECT oe.*, ar.asset_tag, ar.equipment_name
     FROM operating_excursions oe
     JOIN asset_registry ar ON oe.asset_id = ar.id
     ORDER BY oe.start_time DESC LIMIT 30"
)->fetchAll();

// Get assets for mapping dropdown
$assets = $db->query(
    "SELECT id, asset_tag, equipment_name FROM asset_registry WHERE status = 'in_service' ORDER BY asset_tag"
)->fetchAll();
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-chart-area me-2"></i>OSIsoft PI Integration</h1>
        <p class="text-muted mb-0">Process data historian -- real-time monitoring and excursion detection</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/integrations/manager.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Integration Hub
        </a>
    </div>
</div>

<!-- Status -->
<div class="alert <?= $piConfig && $piConfig['is_active'] ? 'alert-success' : 'alert-warning' ?> d-flex align-items-center mb-4">
    <i class="fas <?= $piConfig && $piConfig['is_active'] ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2 fs-5"></i>
    <div>
        <?php if ($piConfig && $piConfig['is_active']): ?>
            <strong>Connected</strong> &mdash; <?= count($tagMappings) ?> tags mapped, <?= count($excursions) ?> recent excursions
        <?php else: ?>
            <strong>Not Configured</strong> &mdash; Configure your PI Web API connection below.
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Connection Config -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-cog me-2"></i>PI Web API Connection</div>
            <div class="card-body">
                <form id="piConfigForm">
                    <input type="hidden" name="integration_id" value="<?= $piConfig['id'] ?? '' ?>">
                    <div class="mb-3">
                        <label class="form-label">PI Web API URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" name="base_url"
                               placeholder="https://piwebapi.company.com"
                               value="<?= e($piConfig['api_base_url'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Authentication</label>
                        <select class="form-select" name="auth_type" id="piAuthType">
                            <option value="basic" <?= ($piConfig['auth_type'] ?? '') === 'basic' ? 'selected' : '' ?>>Basic Auth</option>
                            <option value="kerberos">Kerberos / Windows Auth</option>
                            <option value="bearer">Bearer Token</option>
                        </select>
                    </div>
                    <div id="piBasicFields">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <input type="text" class="form-control form-control-sm" name="username" placeholder="Username">
                            </div>
                            <div class="col-6">
                                <input type="password" class="form-control form-control-sm" name="password" placeholder="Password">
                            </div>
                        </div>
                    </div>
                    <div id="piBearerFields" style="display:none;">
                        <div class="mb-3">
                            <input type="text" class="form-control form-control-sm" name="bearer_token" placeholder="Bearer token">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="verify_ssl" id="piVerifySsl" checked>
                            <label class="form-check-label" for="piVerifySsl">Verify SSL</label>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Save</button>
                        <button type="button" class="btn btn-outline-success btn-sm" id="btnTestPI">
                            <i class="fas fa-plug me-1"></i>Test
                        </button>
                    </div>
                </form>
                <div id="piTestResult" class="mt-3" style="display:none;"></div>

                <!-- Server Info -->
                <div id="piServerInfo" class="mt-3" style="display:none;">
                    <hr>
                    <h6 class="small text-uppercase text-muted">Available Servers</h6>
                    <div id="piServerList"></div>
                </div>
            </div>
        </div>

        <!-- Auto-sync Config -->
        <div class="card mt-4">
            <div class="card-header"><i class="fas fa-clock me-2"></i>Auto-Sync</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Polling Interval</label>
                    <select class="form-select" id="piPollingInterval">
                        <option value="5">Every 5 minutes</option>
                        <option value="15">Every 15 minutes</option>
                        <option value="30">Every 30 minutes</option>
                        <option value="60" selected>Every hour</option>
                        <option value="360">Every 6 hours</option>
                        <option value="1440">Daily</option>
                    </select>
                </div>
                <button class="btn btn-sm btn-outline-primary w-100" id="btnSavePolling">
                    <i class="fas fa-save me-1"></i>Save Schedule
                </button>
            </div>
        </div>
    </div>

    <!-- Tag Browser & Mapping -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-tags me-2"></i>PI Tag Mapping</span>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTagModal">
                    <i class="fas fa-plus me-1"></i>Add Mapping
                </button>
            </div>
            <div class="card-body">
                <!-- Tag Search -->
                <div class="row g-2 mb-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="piTagSearch" placeholder="Search PI tags (e.g., PLANT1.UNIT2.*)">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-primary w-100" id="btnSearchTags" <?= !$piConfig ? 'disabled' : '' ?>>
                            <i class="fas fa-search me-1"></i>Browse Tags
                        </button>
                    </div>
                </div>

                <!-- Tag search results -->
                <div id="tagSearchResults" style="display:none; max-height:200px; overflow-y:auto;" class="border rounded p-2 mb-3">
                </div>

                <!-- Current mappings -->
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>Asset</th>
                                <th>PI Tag</th>
                                <th>Parameter</th>
                                <th>Unit</th>
                                <th>Thresholds</th>
                                <th>Last Value</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tagMappingBody">
                            <?php foreach ($tagMappings as $tm): ?>
                                <tr data-id="<?= $tm['id'] ?>">
                                    <td>
                                        <strong><?= e($tm['asset_tag']) ?></strong>
                                        <br><small class="text-muted"><?= e($tm['equipment_name']) ?></small>
                                    </td>
                                    <td><code class="small"><?= e($tm['pi_tag_name']) ?></code></td>
                                    <td><span class="badge bg-secondary"><?= e($tm['parameter_type']) ?></span></td>
                                    <td><?= e($tm['unit']) ?></td>
                                    <td class="small">
                                        <?php if ($tm['min_threshold'] !== null || $tm['max_threshold'] !== null): ?>
                                            <?= $tm['min_threshold'] !== null ? 'Lo: '.$tm['min_threshold'] : '' ?>
                                            <?= ($tm['min_threshold'] !== null && $tm['max_threshold'] !== null) ? ' / ' : '' ?>
                                            <?= $tm['max_threshold'] !== null ? 'Hi: '.$tm['max_threshold'] : '' ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tm['last_value'] !== null): ?>
                                            <strong><?= number_format($tm['last_value'], 2) ?></strong>
                                            <?php
                                            // Color based on threshold proximity
                                            $color = 'text-success';
                                            if ($tm['max_threshold'] && $tm['last_value'] > $tm['max_threshold']) $color = 'text-danger';
                                            elseif ($tm['max_threshold'] && $tm['last_value'] > $tm['max_threshold'] * 0.9) $color = 'text-warning';
                                            ?>
                                            <i class="fas fa-circle <?= $color ?>" style="font-size:0.5rem;"></i>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small"><?= $tm['last_updated'] ? date('M j, g:i A', strtotime($tm['last_updated'])) : '-' ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-info btn-view-history" data-webid="<?= e($tm['pi_web_id']) ?>" data-tag="<?= e($tm['pi_tag_name']) ?>" title="View history">
                                                <i class="fas fa-chart-line"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-delete-mapping" data-id="<?= $tm['id'] ?>" title="Remove">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($tagMappings)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No tag mappings configured. Click "Add Mapping" to get started.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Data Panel -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-broadcast-tower me-2"></i>Current Values</span>
                <button class="btn btn-sm btn-outline-primary" id="btnRefreshCurrent">
                    <i class="fas fa-sync me-1"></i>Refresh
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3" id="currentValuesGrid">
                    <?php foreach (array_slice($tagMappings, 0, 8) as $tm): ?>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted"><?= e($tm['asset_tag']) ?></small>
                                    <span class="badge bg-secondary"><?= e($tm['parameter_type']) ?></span>
                                </div>
                                <div class="fs-4 fw-bold">
                                    <?= $tm['last_value'] !== null ? number_format($tm['last_value'], 2) : 'N/A' ?>
                                    <small class="text-muted fs-6"><?= e($tm['unit']) ?></small>
                                </div>
                                <div class="mt-1">
                                    <canvas class="sparkline" data-tag="<?= e($tm['pi_tag_name']) ?>" height="30"></canvas>
                                </div>
                                <small class="text-muted"><?= e($tm['pi_tag_name']) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($tagMappings)): ?>
                        <div class="col-12 text-center text-muted py-4">Map PI tags to assets to see real-time values</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Historical Data Viewer -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-line me-2"></i>Historical Data</div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-5">
                        <select class="form-select form-select-sm" id="histTag">
                            <option value="">Select PI tag...</option>
                            <?php foreach ($tagMappings as $tm): ?>
                                <option value="<?= e($tm['pi_web_id']) ?>" data-tag="<?= e($tm['pi_tag_name']) ?>">
                                    <?= e($tm['asset_tag']) ?> - <?= e($tm['parameter_type']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <select class="form-select form-select-sm" id="histRange">
                            <option value="*-1d">Last 24 hours</option>
                            <option value="*-7d" selected>Last 7 days</option>
                            <option value="*-30d">Last 30 days</option>
                            <option value="*-90d">Last 90 days</option>
                            <option value="*-365d">Last year</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-primary w-100" id="btnLoadHistory">
                            <i class="fas fa-chart-line"></i>
                        </button>
                    </div>
                </div>
                <div style="height: 250px;">
                    <canvas id="histChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Excursion Monitor -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-exclamation-triangle me-2"></i>Operating Excursions</span>
                <button class="btn btn-sm btn-outline-warning" id="btnDetectExcursions" <?= !$piConfig ? 'disabled' : '' ?>>
                    <i class="fas fa-search me-1"></i>Detect Excursions
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>Asset</th>
                                <th>Type</th>
                                <th>Start</th>
                                <th>Duration</th>
                                <th>Peak</th>
                                <th>Threshold</th>
                                <th>Severity</th>
                                <th>Ack</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($excursions as $ex): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($ex['asset_tag']) ?></strong>
                                        <br><small class="text-muted"><?= e($ex['equipment_name']) ?></small>
                                    </td>
                                    <td><span class="badge bg-outline-secondary"><?= str_replace('_', ' ', e($ex['excursion_type'])) ?></span></td>
                                    <td class="small"><?= date('M j, g:i A', strtotime($ex['start_time'])) ?></td>
                                    <td><?= $ex['duration_minutes'] ? $ex['duration_minutes'].'m' : '-' ?></td>
                                    <td class="fw-bold"><?= number_format($ex['peak_value'], 2) ?></td>
                                    <td><?= number_format($ex['threshold_value'], 2) ?></td>
                                    <td>
                                        <?php
                                        $sevColors = ['minor'=>'info','moderate'=>'warning','severe'=>'danger','critical'=>'dark'];
                                        ?>
                                        <span class="badge bg-<?= $sevColors[$ex['severity']] ?? 'secondary' ?>"><?= e($ex['severity']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($ex['acknowledged']): ?>
                                            <i class="fas fa-check text-success"></i>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-success btn-ack-excursion" data-id="<?= $ex['id'] ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($excursions)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No excursions detected</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Corrosion Probe Data -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-vial me-2"></i>Corrosion Probes</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small">Probe Tag Prefix</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="probeTagPrefix" placeholder="PLANT1.CORR.*">
                        <button class="btn btn-outline-primary" id="btnLoadProbes" <?= !$piConfig ? 'disabled' : '' ?>>Load</button>
                    </div>
                </div>
                <div id="probeResults">
                    <p class="text-center text-muted small py-3">Enter a tag prefix and click Load to view corrosion probe data</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Tag Mapping Modal -->
<div class="modal fade" id="addTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add PI Tag Mapping</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTagForm">
                    <div class="mb-3">
                        <label class="form-label">Asset</label>
                        <select class="form-select" name="asset_id" required>
                            <option value="">Select asset...</option>
                            <?php foreach ($assets as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= e($a['asset_tag']) ?> - <?= e($a['equipment_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PI Tag Name</label>
                        <input type="text" class="form-control" name="pi_tag_name" placeholder="e.g., PLANT1.UNIT2.TI-101" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PI WebId</label>
                        <input type="text" class="form-control" name="pi_web_id" placeholder="Auto-detected or paste WebId">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Parameter Type</label>
                            <select class="form-select" name="parameter_type" required>
                                <option value="temperature">Temperature</option>
                                <option value="pressure">Pressure</option>
                                <option value="flow_rate">Flow Rate</option>
                                <option value="thickness">Thickness</option>
                                <option value="vibration">Vibration</option>
                                <option value="corrosion_rate">Corrosion Rate</option>
                                <option value="ph">pH</option>
                                <option value="conductivity">Conductivity</option>
                                <option value="h2s">H2S</option>
                                <option value="co2">CO2</option>
                                <option value="velocity">Velocity</option>
                                <option value="strain">Strain</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit</label>
                            <input type="text" class="form-control" name="unit" placeholder="e.g., degC, MPa, mm">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">Scale Factor</label>
                            <input type="number" class="form-control form-control-sm" name="scaling_factor" value="1.0" step="0.001">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Offset</label>
                            <input type="number" class="form-control form-control-sm" name="offset" value="0.0" step="0.001">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Min Threshold</label>
                            <input type="number" class="form-control form-control-sm" name="min_threshold" step="0.01">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Max Threshold</label>
                            <input type="number" class="form-control form-control-sm" name="max_threshold" step="0.01">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveTagMapping">
                    <i class="fas fa-save me-1"></i>Save Mapping
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auth type toggle
    document.getElementById('piAuthType').addEventListener('change', function() {
        document.getElementById('piBasicFields').style.display = ['basic','kerberos'].includes(this.value) ? '' : 'none';
        document.getElementById('piBearerFields').style.display = this.value === 'bearer' ? '' : 'none';
    });

    // Save config
    document.getElementById('piConfigForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        data.action = 'save_config';
        data.vendor = 'OSIsoft PI';
        data.system_type = 'historian';
        data.host = data.base_url;

        fetch('<?= BASE_URL ?>/api/integrations.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        }).then(r => r.json()).then(result => {
            showToast(result.success ? 'Configuration saved' : (result.error||'Failed'), result.success ? 'success' : 'danger');
        });
    });

    // Test connection
    document.getElementById('btnTestPI').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>...';

        const data = Object.fromEntries(new FormData(document.getElementById('piConfigForm')));
        data.action = 'test_connection';

        fetch('<?= BASE_URL ?>/api/integrations.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        }).then(r => r.json()).then(result => {
            const div = document.getElementById('piTestResult');
            div.style.display = '';
            const ok = result.success && result.data?.status === 'connected';
            div.innerHTML = `<div class="alert alert-${ok?'success':'danger'} mb-0 small">
                ${ok ? 'Connected! '+result.data.latency_ms+'ms' : (result.data?.message || result.error)}
            </div>`;

            if (ok && result.data?.details?.data_servers) {
                document.getElementById('piServerInfo').style.display = '';
                document.getElementById('piServerList').innerHTML = `<p class="small">${result.data.details.data_servers} data server(s) found</p>`;
            }
        }).finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-plug me-1"></i>Test'; });
    });

    // Save tag mapping
    document.getElementById('btnSaveTagMapping').addEventListener('click', function() {
        const data = Object.fromEntries(new FormData(document.getElementById('addTagForm')));
        data.action = 'save_pi_tag';

        fetch('<?= BASE_URL ?>/api/integrations.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        }).then(r => r.json()).then(result => {
            if (result.success) {
                showToast('Tag mapping saved', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.error || 'Failed', 'danger');
            }
        });
    });

    // Historical chart
    let histChart = null;
    document.getElementById('btnLoadHistory').addEventListener('click', function() {
        const webId = document.getElementById('histTag').value;
        const range = document.getElementById('histRange').value;
        const tagName = document.getElementById('histTag').selectedOptions[0]?.dataset.tag || '';

        if (!webId) { showToast('Select a PI tag first', 'warning'); return; }

        fetch('<?= BASE_URL ?>/api/integrations.php?action=pi_historical&web_id='+encodeURIComponent(webId)+'&start='+encodeURIComponent(range)+'&end=*')
        .then(r => r.json())
        .then(result => {
            if (result.success && result.data) {
                const labels = result.data.map(v => v.timestamp);
                const values = result.data.map(v => v.value);

                if (histChart) histChart.destroy();
                histChart = new Chart(document.getElementById('histChart'), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: tagName,
                            data: values,
                            borderColor: '#3f51b5',
                            backgroundColor: 'rgba(63,81,181,0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 1,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { display: true, ticks: { maxTicksLimit: 8, font: { size: 10 } } },
                            y: { display: true, ticks: { font: { size: 10 } } }
                        }
                    }
                });
            }
        });
    });

    // Refresh current values
    document.getElementById('btnRefreshCurrent').addEventListener('click', function() {
        fetch('<?= BASE_URL ?>/api/integrations.php?action=pi_current_values')
        .then(r => r.json())
        .then(result => {
            if (result.success) showToast('Values refreshed', 'success');
        });
    });

    function showToast(msg, type) {
        const t = document.createElement('div');
        t.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        t.style.cssText = 'top:70px;right:20px;z-index:9999;min-width:300px;';
        t.innerHTML = `${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 4000);
    }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
