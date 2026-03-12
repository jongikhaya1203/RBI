<?php
/**
 * Integration Hub - RBI Engineering Suite
 */
$pageTitle = 'Integrations';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Integrations', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-plug me-2"></i>Integration Hub</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIntegrationModal">
        <i class="bi bi-plus-lg me-1"></i>Add Integration
    </button>
</div>

<div class="row g-4">
    <!-- CMMS -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-tools me-2"></i>CMMS - SAP PM</span>
                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Connected</span>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-3">
                    <tr><td class="text-muted">System</td><td>SAP Plant Maintenance</td></tr>
                    <tr><td class="text-muted">API Endpoint</td><td class="text-truncate" style="max-width:200px">https://sap.company.com/api/pm</td></tr>
                    <tr><td class="text-muted">Auth Type</td><td>OAuth 2.0</td></tr>
                    <tr><td class="text-muted">Sync Interval</td><td>60 minutes</td></tr>
                    <tr><td class="text-muted">Last Sync</td><td>Mar 11, 2026 09:15 AM</td></tr>
                    <tr><td class="text-muted">Records Synced</td><td>1,247</td></tr>
                    <tr><td class="text-muted">Status</td><td><span class="text-success">Healthy</span></td></tr>
                </table>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>Sync Now</button>
                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-gear me-1"></i>Configure</button>
                    <button class="btn btn-sm btn-outline-info"><i class="bi bi-map me-1"></i>Field Mapping</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCADA -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-hdd-network me-2"></i>SCADA / DCS</span>
                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Connected</span>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-3">
                    <tr><td class="text-muted">System</td><td>Honeywell Experion DCS</td></tr>
                    <tr><td class="text-muted">API Endpoint</td><td class="text-truncate" style="max-width:200px">https://scada.plant.local/api/v2</td></tr>
                    <tr><td class="text-muted">Auth Type</td><td>API Key</td></tr>
                    <tr><td class="text-muted">Sync Interval</td><td>5 minutes</td></tr>
                    <tr><td class="text-muted">Last Sync</td><td>Mar 11, 2026 10:30 AM</td></tr>
                    <tr><td class="text-muted">Active Tags</td><td>342</td></tr>
                    <tr><td class="text-muted">Status</td><td><span class="text-success">Healthy</span></td></tr>
                </table>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>Sync Now</button>
                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-gear me-1"></i>Configure</button>
                </div>
            </div>
        </div>
    </div>

    <!-- IoT -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cpu me-2"></i>IoT Sensor Platform</span>
                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Degraded</span>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-3">
                    <tr><td class="text-muted">Platform</td><td>Permasense Corrosion Monitoring</td></tr>
                    <tr><td class="text-muted">Protocol</td><td>MQTT / REST API</td></tr>
                    <tr><td class="text-muted">Active Sensors</td><td>28 / 32 (4 offline)</td></tr>
                    <tr><td class="text-muted">Last Data</td><td>Mar 11, 2026 10:28 AM</td></tr>
                    <tr><td class="text-muted">Alerts Today</td><td><span class="text-danger">2 threshold alerts</span></td></tr>
                </table>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>Refresh</button>
                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-gear me-1"></i>Configure</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Digital Twin -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-boxes me-2"></i>Digital Twin</span>
                <span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i>Not Configured</span>
            </div>
            <div class="card-body">
                <div class="text-center text-muted py-4">
                    <i class="bi bi-boxes fs-1 d-block mb-2 opacity-25"></i>
                    <p>Digital twin integration has not been configured yet.</p>
                    <button class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Setup Integration</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sync History -->
<div class="card mt-4">
    <div class="card-header"><i class="bi bi-clock-history me-2"></i>Recent Sync History</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Timestamp</th><th>Integration</th><th>Status</th><th>Records</th><th>Duration</th><th>Errors</th></tr>
                </thead>
                <tbody>
                    <tr><td>Mar 11, 10:30</td><td>SCADA</td><td><span class="badge bg-success">Success</span></td><td>342 tags</td><td>1.2s</td><td>0</td></tr>
                    <tr><td>Mar 11, 09:15</td><td>CMMS</td><td><span class="badge bg-success">Success</span></td><td>1,247 records</td><td>4.8s</td><td>0</td></tr>
                    <tr><td>Mar 11, 09:00</td><td>IoT</td><td><span class="badge bg-warning text-dark">Partial</span></td><td>28 sensors</td><td>2.1s</td><td>4 offline</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Integration Modal -->
<div class="modal fade" id="addIntegrationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Integration</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Integration Type</label><select name="type" class="form-select"><option>CMMS</option><option>SCADA/DCS</option><option>IoT Platform</option><option>Digital Twin</option><option>ERP</option></select></div>
                        <div class="col-md-6"><label class="form-label">System Name</label><input type="text" name="system_name" class="form-control" placeholder="e.g. SAP PM"></div>
                        <div class="col-md-8"><label class="form-label">API URL</label><input type="url" name="api_url" class="form-control" placeholder="https://api.example.com/v1"></div>
                        <div class="col-md-4"><label class="form-label">Auth Type</label><select name="auth_type" class="form-select"><option>API Key</option><option>OAuth 2.0</option><option>Basic Auth</option></select></div>
                        <div class="col-md-8"><label class="form-label">API Key</label><input type="password" name="api_key" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Sync Interval (min)</label><input type="number" name="sync_interval" class="form-control" value="60"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Integration</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
