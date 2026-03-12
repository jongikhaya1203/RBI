<?php
/**
 * IBM Maximo Integration Management Page
 * Configure, sync, and monitor Maximo Asset Management integration
 */
$pageTitle = 'IBM Maximo Integration';
$pageSection = 'Integrations';
$currentModule = 'integrations';
require_once __DIR__ . '/../config/app.php';
requireAuth();
require_once INCLUDES_PATH . '/header.php';

$db = new Database();

$maximoConfig = $db->query(
    "SELECT * FROM integration_configs WHERE vendor LIKE '%Maximo%' OR vendor LIKE '%maximo%' OR vendor LIKE '%IBM%' LIMIT 1"
)->fetch();

$syncHistory = [];
if ($maximoConfig) {
    $syncHistory = $db->query(
        "SELECT * FROM integration_sync_log WHERE integration_id = ? ORDER BY started_at DESC LIMIT 20",
        [$maximoConfig['id']]
    )->fetchAll();
}
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-plug me-2"></i>IBM Maximo Integration</h1>
        <p class="text-muted mb-0">Synchronize assets, work orders, and meter readings with Maximo</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/integrations/manager.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Integration Hub
        </a>
    </div>
</div>

<!-- Status Banner -->
<div class="alert <?= $maximoConfig && $maximoConfig['is_active'] ? 'alert-success' : 'alert-warning' ?> d-flex align-items-center mb-4">
    <i class="fas <?= $maximoConfig && $maximoConfig['is_active'] ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2 fs-5"></i>
    <div>
        <?php if ($maximoConfig && $maximoConfig['is_active']): ?>
            <strong>Connected</strong> &mdash; Last sync: <?= $maximoConfig['last_sync_at'] ? date('M j, Y g:i A', strtotime($maximoConfig['last_sync_at'])) : 'Never' ?>
        <?php else: ?>
            <strong>Not Configured</strong> &mdash; Set up your Maximo connection below.
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Connection Configuration -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-cog me-2"></i>Connection Configuration
            </div>
            <div class="card-body">
                <form id="maximoConfigForm">
                    <input type="hidden" name="integration_id" value="<?= $maximoConfig['id'] ?? '' ?>">

                    <div class="mb-3">
                        <label class="form-label">Maximo Base URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" name="base_url"
                               placeholder="https://maximo.company.com"
                               value="<?= e($maximoConfig['api_base_url'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Authentication Type</label>
                        <select class="form-select" name="auth_type" id="maxAuthType">
                            <option value="apikey" <?= ($maximoConfig['auth_type'] ?? '') === 'api_key' ? 'selected' : '' ?>>API Key (MAXAUTH)</option>
                            <option value="basic" <?= ($maximoConfig['auth_type'] ?? '') === 'basic' ? 'selected' : '' ?>>Basic / LDAP</option>
                            <option value="oauth2" <?= ($maximoConfig['auth_type'] ?? '') === 'oauth2' ? 'selected' : '' ?>>OAuth 2.0</option>
                        </select>
                    </div>

                    <div id="maxApiKeyFields">
                        <div class="mb-3">
                            <label class="form-label">API Key</label>
                            <input type="text" class="form-control" name="api_key" placeholder="Enter Maximo API key">
                        </div>
                    </div>

                    <div id="maxBasicFields">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" placeholder="maxadmin">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password">
                            </div>
                        </div>
                    </div>

                    <div id="maxOAuth2Fields" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label">Token URL</label>
                            <input type="url" class="form-control" name="oauth_token_url">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Client ID</label>
                                <input type="text" class="form-control" name="oauth_client_id">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Client Secret</label>
                                <input type="password" class="form-control" name="oauth_client_secret">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Site ID</label>
                            <input type="text" class="form-control" name="site_id" placeholder="e.g., BEDFORD">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Org ID</label>
                            <input type="text" class="form-control" name="org_id" placeholder="e.g., EAGLENA">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sync Direction</label>
                        <select class="form-select" name="sync_direction">
                            <option value="inbound">Inbound (Maximo &rarr; RBI)</option>
                            <option value="outbound">Outbound (RBI &rarr; Maximo)</option>
                            <option value="bidirectional" selected>Bidirectional</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="verify_ssl" id="maxVerifySsl" checked>
                            <label class="form-check-label" for="maxVerifySsl">Verify SSL Certificate</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save
                        </button>
                        <button type="button" class="btn btn-outline-success" id="btnTestMaximo">
                            <i class="fas fa-plug me-1"></i>Test Connection
                        </button>
                    </div>
                </form>
                <div id="maxTestResult" class="mt-3" style="display:none;"></div>
            </div>
        </div>
    </div>

    <!-- Asset Sync Panel -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-sync-alt me-2"></i>Asset Synchronization
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Asset Type Filter</label>
                        <select class="form-select" id="maxAssetTypeFilter">
                            <option value="">All Types</option>
                            <option value="VESSEL">Pressure Vessels</option>
                            <option value="PIPE">Piping</option>
                            <option value="EXCHANGER">Heat Exchangers</option>
                            <option value="TANK">Storage Tanks</option>
                            <option value="PUMP">Pumps</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status Filter</label>
                        <select class="form-select" id="maxStatusFilter">
                            <option value="">All Statuses</option>
                            <option value="OPERATING" selected>Operating</option>
                            <option value="NOT READY">Not Ready</option>
                            <option value="DECOMMISSIONED">Decommissioned</option>
                        </select>
                    </div>
                </div>

                <button class="btn btn-success w-100 mb-3" id="btnSyncMaximoAssets" <?= !$maximoConfig ? 'disabled' : '' ?>>
                    <i class="fas fa-play me-1"></i>Sync Assets
                </button>

                <div id="maxSyncProgress" style="display:none;">
                    <div class="progress mb-2" style="height: 24px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" id="maxSyncBar" style="width: 0%;">0%</div>
                    </div>
                    <small class="text-muted" id="maxSyncStatus">Initializing...</small>
                </div>

                <div id="maxSyncResults" style="display:none;">
                    <hr>
                    <div class="row g-2 text-center">
                        <div class="col-3"><div class="border rounded p-2"><div class="fs-5 fw-bold text-primary" id="maxResProc">0</div><small class="text-muted">Processed</small></div></div>
                        <div class="col-3"><div class="border rounded p-2"><div class="fs-5 fw-bold text-success" id="maxResNew">0</div><small class="text-muted">Created</small></div></div>
                        <div class="col-3"><div class="border rounded p-2"><div class="fs-5 fw-bold text-info" id="maxResUpd">0</div><small class="text-muted">Updated</small></div></div>
                        <div class="col-3"><div class="border rounded p-2"><div class="fs-5 fw-bold text-danger" id="maxResFail">0</div><small class="text-muted">Failed</small></div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Work Order Sync -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-wrench me-2"></i>Work Order Sync
            </div>
            <div class="card-body">
                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-outline-primary flex-fill" id="btnSyncWOIn" <?= !$maximoConfig ? 'disabled' : '' ?>>
                        <i class="fas fa-arrow-down me-1"></i>Import WOs from Maximo
                    </button>
                    <button class="btn btn-outline-success flex-fill" id="btnSyncWOOut" <?= !$maximoConfig ? 'disabled' : '' ?>>
                        <i class="fas fa-arrow-up me-1"></i>Push Plans to Maximo
                    </button>
                </div>
                <div id="woSyncResult" style="display:none;"></div>
            </div>
        </div>

        <!-- Meter Reading Sync -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-tachometer-alt me-2"></i>Meter Readings
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2">Sync thickness measurements and condition readings.</p>
                <button class="btn btn-outline-info w-100" id="btnSyncMeters" <?= !$maximoConfig ? 'disabled' : '' ?>>
                    <i class="fas fa-sync me-1"></i>Sync Meter Readings
                </button>
            </div>
        </div>
    </div>

    <!-- Field Mapping -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-exchange-alt me-2"></i>Field Mapping</span>
                <button class="btn btn-sm btn-primary" id="btnSaveMaxMapping"><i class="fas fa-save me-1"></i>Save</button>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>Maximo Field</th>
                                <th>RBI Field</th>
                                <th>Direction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><code>assetnum</code></td><td><code>asset_tag</code></td><td><span class="badge bg-info">both</span></td></tr>
                            <tr><td><code>description</code></td><td><code>equipment_name</code></td><td><span class="badge bg-success">inbound</span></td></tr>
                            <tr><td><code>status</code></td><td><code>status</code></td><td><span class="badge bg-info">both</span></td></tr>
                            <tr><td><code>location</code></td><td><code>location</code></td><td><span class="badge bg-success">inbound</span></td></tr>
                            <tr><td><code>assettype</code></td><td><code>asset_type</code></td><td><span class="badge bg-success">inbound</span></td></tr>
                            <tr><td><code>installdate</code></td><td><code>installation_date</code></td><td><span class="badge bg-success">inbound</span></td></tr>
                            <tr><td><code>manufacturer</code></td><td><code>manufacturer</code></td><td><span class="badge bg-success">inbound</span></td></tr>
                            <tr><td><code>serialnum</code></td><td><code>serial_number</code></td><td><span class="badge bg-success">inbound</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Sync History & Errors -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-history me-2"></i>Sync History</div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($syncHistory)): ?>
                    <p class="text-center text-muted py-4">No sync history yet</p>
                <?php else: ?>
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Date</th><th>Records</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($syncHistory as $log): ?>
                                <tr>
                                    <td class="small"><?= date('M j, g:i A', strtotime($log['started_at'])) ?></td>
                                    <td><?= number_format($log['records_processed']) ?></td>
                                    <td>
                                        <?php $sc = ['completed'=>'success','failed'=>'danger','partial'=>'warning','running'=>'info']; ?>
                                        <span class="badge bg-<?= $sc[$log['status']] ?? 'secondary' ?>"><?= e($log['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Failure Analysis Import -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="fas fa-bug me-2"></i>Failure Analysis Data Import</div>
            <div class="card-body">
                <p class="text-muted">Import failure class/problem/cause/remedy data from Maximo for damage mechanism screening and failure probability analysis.</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Asset</label>
                        <input type="text" class="form-control" id="failureAssetNum" placeholder="Enter Maximo asset number">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="failureDateFrom">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" id="failureDateTo">
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-outline-primary w-100" id="btnImportFailures" <?= !$maximoConfig ? 'disabled' : '' ?>>
                            <i class="fas fa-download me-1"></i>Import Failure Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const authType = document.getElementById('maxAuthType');
    if (authType) {
        authType.addEventListener('change', function() {
            document.getElementById('maxApiKeyFields').style.display = this.value === 'apikey' ? '' : 'none';
            document.getElementById('maxBasicFields').style.display = ['basic','apikey'].includes(this.value) ? '' : 'none';
            document.getElementById('maxOAuth2Fields').style.display = this.value === 'oauth2' ? '' : 'none';
        });
        authType.dispatchEvent(new Event('change'));
    }

    // Save config
    document.getElementById('maximoConfigForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        data.action = 'save_config';
        data.vendor = 'IBM Maximo';
        data.system_type = 'cmms';
        data.host = data.base_url;

        fetch('<?= BASE_URL ?>/api/integrations.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(result => {
            const msg = result.success ? 'Configuration saved' : (result.error || 'Failed');
            showToast(msg, result.success ? 'success' : 'danger');
        })
        .catch(err => showToast('Error: ' + err.message, 'danger'));
    });

    // Test connection
    document.getElementById('btnTestMaximo').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Testing...';

        const data = Object.fromEntries(new FormData(document.getElementById('maximoConfigForm')));
        data.action = 'test_connection';

        fetch('<?= BASE_URL ?>/api/integrations.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(result => {
            const div = document.getElementById('maxTestResult');
            div.style.display = '';
            const ok = result.success && result.data?.status === 'connected';
            div.innerHTML = `<div class="alert alert-${ok?'success':'danger'} mb-0">
                <i class="fas fa-${ok?'check':'times'}-circle me-1"></i>
                ${ok ? 'Connected! Latency: '+result.data.latency_ms+'ms' : (result.data?.message || result.error || 'Failed')}
            </div>`;
        })
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-plug me-1"></i>Test Connection'; });
    });

    // Sync assets
    document.getElementById('btnSyncMaximoAssets').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        document.getElementById('maxSyncProgress').style.display = '';
        document.getElementById('maxSyncResults').style.display = 'none';

        const bar = document.getElementById('maxSyncBar');
        let p = 0;
        const iv = setInterval(() => { if (p < 90) { p += Math.random()*15; bar.style.width=Math.min(p,90)+'%'; bar.textContent=Math.round(Math.min(p,90))+'%'; }}, 500);

        fetch('<?= BASE_URL ?>/api/integrations.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action:'sync', integration_id:'<?= $maximoConfig['id'] ?? '' ?>', sync_type:'full'})
        })
        .then(r => r.json())
        .then(result => {
            clearInterval(iv);
            bar.style.width = '100%'; bar.textContent = '100%';
            bar.classList.remove('progress-bar-animated');
            if (result.success) {
                const d = result.data;
                document.getElementById('maxResProc').textContent = d.records_processed||0;
                document.getElementById('maxResNew').textContent = d.records_created||0;
                document.getElementById('maxResUpd').textContent = d.records_updated||0;
                document.getElementById('maxResFail').textContent = d.records_failed||0;
                document.getElementById('maxSyncResults').style.display = '';
            }
        })
        .finally(() => btn.disabled = false);
    });

    function showToast(msg, type) {
        const t = document.createElement('div');
        t.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        t.style.cssText = 'top:70px;right:20px;z-index:9999;min-width:300px;';
        t.innerHTML = `${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 5000);
    }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
