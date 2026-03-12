<?php
/**
 * SAP PM Integration Management Page
 * Configure, sync, and monitor SAP Plant Maintenance integration
 */
$pageTitle = 'SAP PM Integration';
$pageSection = 'Integrations';
$currentModule = 'integrations';
require_once __DIR__ . '/../config/app.php';
requireAuth();
require_once INCLUDES_PATH . '/header.php';

$db = new Database();

// Get SAP integration config if exists
$sapConfig = $db->query(
    "SELECT * FROM integration_configs WHERE vendor LIKE '%SAP%' OR vendor LIKE '%sap%' LIMIT 1"
)->fetch();

$syncHistory = [];
$fieldMappings = [];
if ($sapConfig) {
    $syncHistory = $db->query(
        "SELECT * FROM integration_sync_log WHERE integration_id = ? ORDER BY started_at DESC LIMIT 20",
        [$sapConfig['id']]
    )->fetchAll();

    $fieldMappings = $db->query(
        "SELECT * FROM integration_field_mappings WHERE integration_id = ? ORDER BY entity_type, external_field",
        [$sapConfig['id']]
    )->fetchAll();
}
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-plug me-2"></i>SAP Plant Maintenance Integration</h1>
        <p class="text-muted mb-0">Synchronize equipment, maintenance orders, and measurements with SAP PM</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/integrations/manager.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left me-1"></i>Integration Hub
        </a>
    </div>
</div>

<!-- Status Banner -->
<div class="alert <?= $sapConfig && $sapConfig['is_active'] ? 'alert-success' : 'alert-warning' ?> d-flex align-items-center mb-4" role="alert">
    <i class="fas <?= $sapConfig && $sapConfig['is_active'] ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2 fs-5"></i>
    <div>
        <?php if ($sapConfig && $sapConfig['is_active']): ?>
            <strong>Connected</strong> &mdash; Last sync: <?= $sapConfig['last_sync_at'] ? date('M j, Y g:i A', strtotime($sapConfig['last_sync_at'])) : 'Never' ?>
            <span class="badge bg-<?= $sapConfig['last_sync_status'] === 'success' ? 'success' : ($sapConfig['last_sync_status'] === 'failed' ? 'danger' : 'warning') ?> ms-2">
                <?= ucfirst($sapConfig['last_sync_status'] ?? 'never') ?>
            </span>
        <?php else: ?>
            <strong>Not Configured</strong> &mdash; Set up your SAP connection below to begin synchronization.
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Connection Configuration -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-cog me-2"></i>Connection Configuration</span>
                <span class="badge bg-secondary" id="connStatus"><?= $sapConfig ? 'Configured' : 'Not Set' ?></span>
            </div>
            <div class="card-body">
                <form id="sapConfigForm">
                    <input type="hidden" name="integration_id" value="<?= $sapConfig['id'] ?? '' ?>">

                    <div class="mb-3">
                        <label class="form-label">SAP Host URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" name="host" placeholder="https://sap-server.company.com"
                               value="<?= e($sapConfig['api_base_url'] ?? '') ?>" required>
                        <div class="form-text">SAP Gateway or S/4HANA OData endpoint base URL</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Client <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="client" placeholder="100"
                                   value="<?= e($sapConfig['filter_config'] ? (json_decode($sapConfig['filter_config'], true)['client'] ?? '100') : '100') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">System ID</label>
                            <input type="text" class="form-control" name="system_id" placeholder="PRD"
                                   value="<?= e($sapConfig['filter_config'] ? (json_decode($sapConfig['filter_config'], true)['system_id'] ?? '') : '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Language</label>
                            <select class="form-select" name="language">
                                <option value="EN" selected>English</option>
                                <option value="DE">German</option>
                                <option value="FR">French</option>
                                <option value="ES">Spanish</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Authentication Type</label>
                        <select class="form-select" name="auth_type" id="sapAuthType">
                            <option value="basic" <?= ($sapConfig['auth_type'] ?? '') === 'basic' ? 'selected' : '' ?>>Basic Authentication</option>
                            <option value="oauth2" <?= ($sapConfig['auth_type'] ?? '') === 'oauth2' ? 'selected' : '' ?>>OAuth 2.0 (S/4HANA Cloud)</option>
                        </select>
                    </div>

                    <!-- Basic Auth Fields -->
                    <div id="basicAuthFields">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" placeholder="SAP_RBI_USER">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" placeholder="Enter password">
                            </div>
                        </div>
                    </div>

                    <!-- OAuth2 Fields -->
                    <div id="oauth2Fields" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label">Token URL</label>
                            <input type="url" class="form-control" name="oauth_token_url" placeholder="https://auth.sap.com/oauth/token">
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

                    <div class="mb-3">
                        <label class="form-label">Sync Direction</label>
                        <select class="form-select" name="sync_direction">
                            <option value="inbound">Inbound Only (SAP &rarr; RBI)</option>
                            <option value="outbound">Outbound Only (RBI &rarr; SAP)</option>
                            <option value="bidirectional" selected>Bidirectional</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="verify_ssl" id="verifySsl" checked>
                            <label class="form-check-label" for="verifySsl">Verify SSL Certificate</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Configuration
                        </button>
                        <button type="button" class="btn btn-outline-success" id="btnTestConnection">
                            <i class="fas fa-plug me-1"></i>Test Connection
                        </button>
                    </div>
                </form>

                <!-- Test Results -->
                <div id="testResult" class="mt-3" style="display:none;"></div>
            </div>
        </div>
    </div>

    <!-- Equipment Sync Panel -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-sync-alt me-2"></i>Equipment Synchronization</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Sync Scope</label>
                    <select class="form-select" id="syncScope">
                        <option value="full">Full Sync (All Equipment)</option>
                        <option value="incremental">Incremental (Since Last Sync)</option>
                        <option value="selective">Selective (By Plant/FuncLoc)</option>
                    </select>
                </div>

                <div id="selectiveFilters" style="display:none;">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Plant Code</label>
                            <input type="text" class="form-control" id="filterPlant" placeholder="e.g., 1000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Functional Location</label>
                            <input type="text" class="form-control" id="filterFuncLoc" placeholder="e.g., FN-1000-A">
                        </div>
                    </div>
                </div>

                <button class="btn btn-success w-100 mb-3" id="btnStartSync" <?= !$sapConfig ? 'disabled' : '' ?>>
                    <i class="fas fa-play me-1"></i>Start Equipment Sync
                </button>

                <!-- Sync Progress -->
                <div id="syncProgress" style="display:none;">
                    <div class="progress mb-2" style="height: 24px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" id="syncProgressBar"
                             role="progressbar" style="width: 0%;">0%</div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span id="syncStatus">Initializing...</span>
                        <span id="syncCount">0 records</span>
                    </div>
                </div>

                <!-- Sync Results Summary -->
                <div id="syncResults" style="display:none;">
                    <hr>
                    <h6>Sync Results</h6>
                    <div class="row g-2 text-center">
                        <div class="col-3">
                            <div class="border rounded p-2">
                                <div class="fs-5 fw-bold text-primary" id="resProcessed">0</div>
                                <small class="text-muted">Processed</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="border rounded p-2">
                                <div class="fs-5 fw-bold text-success" id="resCreated">0</div>
                                <small class="text-muted">Created</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="border rounded p-2">
                                <div class="fs-5 fw-bold text-info" id="resUpdated">0</div>
                                <small class="text-muted">Updated</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="border rounded p-2">
                                <div class="fs-5 fw-bold text-danger" id="resFailed">0</div>
                                <small class="text-muted">Failed</small>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Work Order Creation -->
                <h6><i class="fas fa-wrench me-1"></i>Create SAP Maintenance Order</h6>
                <form id="createOrderForm">
                    <div class="mb-2">
                        <select class="form-select form-select-sm" name="inspection_plan" id="inspectionPlanSelect">
                            <option value="">Select Inspection Plan...</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <input type="text" class="form-control form-control-sm" name="order_type" placeholder="Order Type (PM01)" value="PM01">
                        </div>
                        <div class="col-md-6">
                            <select class="form-select form-select-sm" name="priority">
                                <option value="1">1 - Very High</option>
                                <option value="2" selected>2 - High</option>
                                <option value="3">3 - Medium</option>
                                <option value="4">4 - Low</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary" <?= !$sapConfig ? 'disabled' : '' ?>>
                        <i class="fas fa-paper-plane me-1"></i>Create SAP Order
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Field Mapping Editor -->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-exchange-alt me-2"></i>Field Mapping Configuration</span>
                <div>
                    <button class="btn btn-sm btn-outline-secondary me-1" id="btnExportMapping">
                        <i class="fas fa-download me-1"></i>Export CSV
                    </button>
                    <button class="btn btn-sm btn-outline-secondary me-1" id="btnImportMapping">
                        <i class="fas fa-upload me-1"></i>Import CSV
                    </button>
                    <button class="btn btn-sm btn-primary" id="btnAddMapping">
                        <i class="fas fa-plus me-1"></i>Add Mapping
                    </button>
                </div>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="mappingTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#mapEquipment">Equipment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#mapWorkOrder">Work Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#mapNotification">Notifications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#mapMeasurement">Measurements</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="mapEquipment">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="mappingTable">
                                <thead>
                                    <tr>
                                        <th>SAP Field</th>
                                        <th>RBI Field</th>
                                        <th>Transform</th>
                                        <th>Default</th>
                                        <th>Key</th>
                                        <th>Required</th>
                                        <th>Direction</th>
                                        <th width="80">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $equipMappings = array_filter($fieldMappings, fn($m) => $m['entity_type'] === 'equipment');
                                    if (empty($equipMappings)):
                                        // Show default mappings
                                        $defaults = [
                                            ['Equipment', 'asset_tag', '', '', 1, 1, 'both'],
                                            ['EquipmentName', 'equipment_name', '', '', 0, 1, 'inbound'],
                                            ['FunctionalLocation', 'sap_floc', '', '', 0, 0, 'inbound'],
                                            ['EquipmentCategory', 'asset_type', 'mapAssetType', '', 0, 0, 'inbound'],
                                            ['ConstructionYear', 'construction_year', '', '', 0, 0, 'inbound'],
                                            ['ManufacturerPartNbr', 'manufacturer', '', '', 0, 0, 'inbound'],
                                            ['ManufacturerSerialNumber', 'serial_number', '', '', 0, 0, 'inbound'],
                                        ];
                                        foreach ($defaults as $d): ?>
                                            <tr>
                                                <td><code><?= $d[0] ?></code></td>
                                                <td><code><?= $d[1] ?></code></td>
                                                <td><?= $d[2] ?: '<span class="text-muted">-</span>' ?></td>
                                                <td><?= $d[3] ?: '<span class="text-muted">-</span>' ?></td>
                                                <td><?= $d[4] ? '<i class="fas fa-key text-warning"></i>' : '' ?></td>
                                                <td><?= $d[5] ? '<i class="fas fa-check text-success"></i>' : '' ?></td>
                                                <td><span class="badge bg-info"><?= $d[6] ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach;
                                    else:
                                        foreach ($equipMappings as $m): ?>
                                            <tr>
                                                <td><code><?= e($m['external_field']) ?></code></td>
                                                <td><code><?= e($m['internal_field']) ?></code></td>
                                                <td><?= e($m['transform_function']) ?: '<span class="text-muted">-</span>' ?></td>
                                                <td><?= e($m['default_value']) ?: '<span class="text-muted">-</span>' ?></td>
                                                <td><?= $m['is_key'] ? '<i class="fas fa-key text-warning"></i>' : '' ?></td>
                                                <td><?= $m['is_required'] ? '<i class="fas fa-check text-success"></i>' : '' ?></td>
                                                <td><span class="badge bg-info"><?= e($m['direction']) ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach;
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="mapWorkOrder">
                        <p class="text-muted">Work order field mappings. Configure how SAP PM orders map to RBI inspection plans.</p>
                    </div>
                    <div class="tab-pane fade" id="mapNotification">
                        <p class="text-muted">Notification field mappings. Configure how SAP notifications map to RBI findings.</p>
                    </div>
                    <div class="tab-pane fade" id="mapMeasurement">
                        <p class="text-muted">Measurement field mappings. Configure how SAP measurement documents map to thickness readings.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sync History & Error Log -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history me-2"></i>Sync History
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover" id="syncHistoryTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Direction</th>
                                <th>Processed</th>
                                <th>Created</th>
                                <th>Updated</th>
                                <th>Failed</th>
                                <th>Status</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($syncHistory as $log): ?>
                                <tr>
                                    <td><?= date('M j, g:i A', strtotime($log['started_at'])) ?></td>
                                    <td><span class="badge bg-secondary"><?= e($log['sync_type']) ?></span></td>
                                    <td><?= e($log['direction']) ?></td>
                                    <td><?= number_format($log['records_processed']) ?></td>
                                    <td class="text-success"><?= number_format($log['records_created']) ?></td>
                                    <td class="text-info"><?= number_format($log['records_updated']) ?></td>
                                    <td class="text-danger"><?= number_format($log['records_failed']) ?></td>
                                    <td>
                                        <?php
                                        $statusColors = ['completed' => 'success', 'failed' => 'danger', 'partial' => 'warning', 'running' => 'info'];
                                        ?>
                                        <span class="badge bg-<?= $statusColors[$log['status']] ?? 'secondary' ?>"><?= e($log['status']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($log['completed_at'] && $log['started_at']): ?>
                                            <?= round((strtotime($log['completed_at']) - strtotime($log['started_at'])), 0) ?>s
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($syncHistory)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-4">No sync history yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Log -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-exclamation-triangle me-2"></i>Recent Errors
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php
                $errors = [];
                foreach ($syncHistory as $log) {
                    if (!empty($log['error_log'])) {
                        $errs = json_decode($log['error_log'], true);
                        if (is_array($errs)) {
                            foreach ($errs as $err) {
                                $errors[] = ['time' => $log['started_at'], 'message' => $err];
                            }
                        }
                    }
                }
                if (empty($errors)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fs-3 d-block mb-2 text-success"></i>
                        No errors recorded
                    </div>
                <?php else:
                    foreach (array_slice($errors, 0, 20) as $err): ?>
                        <div class="border-start border-danger border-3 ps-3 mb-2 small">
                            <div class="text-muted"><?= date('M j, g:i A', strtotime($err['time'])) ?></div>
                            <div><?= e($err['message']) ?></div>
                        </div>
                    <?php endforeach;
                endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auth type toggle
    const authType = document.getElementById('sapAuthType');
    if (authType) {
        authType.addEventListener('change', function() {
            document.getElementById('basicAuthFields').style.display = this.value === 'basic' ? '' : 'none';
            document.getElementById('oauth2Fields').style.display = this.value === 'oauth2' ? '' : 'none';
        });
    }

    // Sync scope toggle
    const syncScope = document.getElementById('syncScope');
    if (syncScope) {
        syncScope.addEventListener('change', function() {
            document.getElementById('selectiveFilters').style.display = this.value === 'selective' ? '' : 'none';
        });
    }

    // Save configuration
    document.getElementById('sapConfigForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        data.action = 'save_config';
        data.vendor = 'SAP PM';
        data.system_type = 'cmms';

        fetch('<?= BASE_URL ?>/api/integrations.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                showToast('Configuration saved successfully', 'success');
                document.getElementById('connStatus').textContent = 'Configured';
                document.getElementById('connStatus').className = 'badge bg-success';
            } else {
                showToast(result.error || 'Failed to save', 'danger');
            }
        })
        .catch(err => showToast('Network error: ' + err.message, 'danger'));
    });

    // Test connection
    document.getElementById('btnTestConnection').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Testing...';

        const formData = new FormData(document.getElementById('sapConfigForm'));
        const data = Object.fromEntries(formData);
        data.action = 'test_connection';

        fetch('<?= BASE_URL ?>/api/integrations.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(result => {
            const div = document.getElementById('testResult');
            div.style.display = '';
            if (result.success && result.data.status === 'connected') {
                div.innerHTML = `<div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle me-1"></i>
                    <strong>Connected!</strong> Latency: ${result.data.latency_ms}ms
                </div>`;
            } else {
                div.innerHTML = `<div class="alert alert-danger mb-0">
                    <i class="fas fa-times-circle me-1"></i>
                    <strong>Failed:</strong> ${result.data?.message || result.error || 'Connection failed'}
                </div>`;
            }
        })
        .catch(err => {
            document.getElementById('testResult').style.display = '';
            document.getElementById('testResult').innerHTML = `<div class="alert alert-danger mb-0">Network error: ${err.message}</div>`;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plug me-1"></i>Test Connection';
        });
    });

    // Start sync
    document.getElementById('btnStartSync').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        document.getElementById('syncProgress').style.display = '';
        document.getElementById('syncResults').style.display = 'none';

        const progressBar = document.getElementById('syncProgressBar');
        const syncStatus = document.getElementById('syncStatus');
        const syncCount = document.getElementById('syncCount');

        // Simulate progress
        let progress = 0;
        const progressInterval = setInterval(() => {
            if (progress < 90) {
                progress += Math.random() * 15;
                progressBar.style.width = Math.min(progress, 90) + '%';
                progressBar.textContent = Math.round(Math.min(progress, 90)) + '%';
            }
        }, 500);

        syncStatus.textContent = 'Synchronizing with SAP...';

        const data = {
            action: 'sync',
            integration_id: '<?= $sapConfig['id'] ?? '' ?>',
            sync_type: document.getElementById('syncScope').value
        };

        fetch('<?= BASE_URL ?>/api/integrations.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(result => {
            clearInterval(progressInterval);
            progressBar.style.width = '100%';
            progressBar.textContent = '100%';
            progressBar.classList.remove('progress-bar-animated');

            if (result.success) {
                syncStatus.textContent = 'Sync completed!';
                const d = result.data;
                document.getElementById('resProcessed').textContent = d.records_processed || 0;
                document.getElementById('resCreated').textContent = d.records_created || 0;
                document.getElementById('resUpdated').textContent = d.records_updated || 0;
                document.getElementById('resFailed').textContent = d.records_failed || 0;
                syncCount.textContent = (d.records_processed || 0) + ' records';
                document.getElementById('syncResults').style.display = '';
            } else {
                syncStatus.textContent = 'Sync failed: ' + (result.error || 'Unknown error');
                progressBar.classList.add('bg-danger');
            }
        })
        .catch(err => {
            clearInterval(progressInterval);
            syncStatus.textContent = 'Error: ' + err.message;
            progressBar.classList.add('bg-danger');
        })
        .finally(() => {
            btn.disabled = false;
        });
    });

    // Load inspection plans for WO creation
    fetch('<?= BASE_URL ?>/api/assets.php?format=plans')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                const select = document.getElementById('inspectionPlanSelect');
                data.data.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.plan_name || 'Plan #' + p.id;
                    select.appendChild(opt);
                });
            }
        })
        .catch(() => {});

    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top:70px;right:20px;z-index:9999;min-width:300px;';
        toast.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
