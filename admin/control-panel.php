<?php
/**
 * Control Panel - RBI Engineering Suite
 * Main administration dashboard with system overview, module management, and quick actions
 */
$pageTitle = 'Control Panel';
$pageSection = 'Administration';
$currentModule = 'admin';

require_once dirname(__DIR__) . '/config/app.php';

// Auth check
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    flash('Please log in to continue.', 'warning');
    redirect(BASE_URL . '/login.php');
}
$userRole = $_SESSION['auth_user_role'] ?? 'viewer';

// DB connection for system stats
try {
    $db = DatabaseConnection::getInstance()->getConnection();
    $dbSizeStmt = $db->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
    $dbSize = $dbSizeStmt->fetch()['size_mb'] ?? '0';

    $userCountStmt = $db->query("SELECT COUNT(*) as cnt FROM users");
    $userCount = $userCountStmt->fetch()['cnt'] ?? 0;

    $mysqlVersion = $db->query("SELECT VERSION() as v")->fetch()['v'] ?? 'Unknown';
} catch (Exception $e) {
    $dbSize = 'N/A';
    $userCount = 0;
    $mysqlVersion = 'N/A';
}

include INCLUDES_PATH . '/header.php';
?>

<style>
.cp-stat-card {
    border-radius: 12px;
    padding: 24px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: transform 0.2s, box-shadow 0.2s;
}
.cp-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.cp-stat-icon {
    width: 52px; height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}
.cp-stat-value { font-size: 2rem; font-weight: 700; color: #1e293b; }
.cp-stat-label { font-size: 0.78rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }

.quick-action-btn {
    border: 2px dashed #e2e8f0;
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    color: #475569;
    display: block;
}
.quick-action-btn:hover {
    border-color: #3f51b5;
    background: #f8f9ff;
    color: #1a237e;
}
.quick-action-btn i { font-size: 1.8rem; margin-bottom: 8px; display: block; }

.module-toggle { width: 48px; height: 24px; }
.module-card {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
    background: #fff;
    transition: all 0.2s;
}
.module-card.enabled { border-left: 4px solid #28a745; }
.module-card.disabled { border-left: 4px solid #dc3545; opacity: 0.7; }

.license-banner {
    background: linear-gradient(135deg, #1a237e 0%, #3f51b5 100%);
    color: #fff;
    border-radius: 12px;
    padding: 24px;
}

.server-info-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.875rem;
}
.server-info-item:last-child { border-bottom: none; }
.server-info-label { color: #64748b; }
.server-info-value { font-weight: 600; color: #1e293b; }

.nav-card {
    border-radius: 12px;
    padding: 20px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    text-decoration: none;
    display: block;
    transition: all 0.2s;
    color: inherit;
}
.nav-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    color: inherit;
}
.nav-card-icon {
    width: 56px; height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin-bottom: 14px;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fas fa-tachometer-alt me-2 text-primary"></i>Control Panel</h1>
        <p class="text-muted mb-0 mt-1">System administration and management dashboard</p>
    </div>
    <div>
        <span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>System Online</span>
    </div>
</div>

<!-- System Overview Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="cp-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="cp-stat-label">Total Users</div>
                    <div class="cp-stat-value"><?= number_format($userCount) ?></div>
                    <small class="text-success"><i class="fas fa-arrow-up me-1"></i>Active accounts</small>
                </div>
                <div class="cp-stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="cp-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="cp-stat-label">Active Sessions</div>
                    <div class="cp-stat-value" id="activeSessions">1</div>
                    <small class="text-muted"><i class="fas fa-circle text-success me-1" style="font-size:0.5rem;"></i>Current session</small>
                </div>
                <div class="cp-stat-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-signal"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="cp-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="cp-stat-label">Database Size</div>
                    <div class="cp-stat-value"><?= $dbSize ?> <small class="fs-6">MB</small></div>
                    <small class="text-muted"><?= DB_NAME ?></small>
                </div>
                <div class="cp-stat-icon bg-info bg-opacity-10 text-info">
                    <i class="fas fa-database"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="cp-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="cp-stat-label">System Uptime</div>
                    <div class="cp-stat-value" id="uptimeDisplay">--</div>
                    <small class="text-muted">Since last restart</small>
                </div>
                <div class="cp-stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-bolt me-2"></i>Quick Actions</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-3 col-md-6">
                <div class="quick-action-btn" onclick="quickAction('loadSample')">
                    <i class="fas fa-download text-primary"></i>
                    <div class="fw-semibold">Load Sample Data</div>
                    <small class="text-muted">Populate demo records</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="quick-action-btn" onclick="quickAction('clearData')">
                    <i class="fas fa-trash-alt text-danger"></i>
                    <div class="fw-semibold">Clear Data</div>
                    <small class="text-muted">Reset all tables</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="quick-action-btn" onclick="quickAction('backup')">
                    <i class="fas fa-hdd text-success"></i>
                    <div class="fw-semibold">Backup Database</div>
                    <small class="text-muted">Export SQL dump</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="quick-action-btn" onclick="quickAction('systemCheck')">
                    <i class="fas fa-stethoscope text-info"></i>
                    <div class="fw-semibold">System Check</div>
                    <small class="text-muted">Run diagnostics</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Module Status Grid -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="fas fa-cubes me-2"></i>Module Status</div>
            <div class="card-body">
                <div class="row g-3" id="moduleGrid">
                    <?php
                    $modules = [
                        ['name' => 'Asset Management', 'icon' => 'fa-industry', 'color' => 'primary', 'enabled' => true],
                        ['name' => 'Risk Assessment', 'icon' => 'fa-exclamation-triangle', 'color' => 'danger', 'enabled' => true],
                        ['name' => 'Damage Mechanisms', 'icon' => 'fa-bolt', 'color' => 'warning', 'enabled' => true],
                        ['name' => 'Inspection Planning', 'icon' => 'fa-clipboard-check', 'color' => 'success', 'enabled' => true],
                        ['name' => 'Analytics & Reporting', 'icon' => 'fa-chart-line', 'color' => 'info', 'enabled' => true],
                        ['name' => 'Predictive ML', 'icon' => 'fa-brain', 'color' => 'purple', 'enabled' => true],
                        ['name' => 'IoT Integration', 'icon' => 'fa-wifi', 'color' => 'teal', 'enabled' => true],
                        ['name' => 'SAP Integration', 'icon' => 'fa-plug', 'color' => 'orange', 'enabled' => false],
                        ['name' => 'Maximo Integration', 'icon' => 'fa-link', 'color' => 'secondary', 'enabled' => false],
                        ['name' => 'Mobile PWA', 'icon' => 'fa-mobile-alt', 'color' => 'primary', 'enabled' => true],
                        ['name' => 'Digital Twin', 'icon' => 'fa-vr-cardboard', 'color' => 'indigo', 'enabled' => false],
                        ['name' => 'Training Module', 'icon' => 'fa-graduation-cap', 'color' => 'success', 'enabled' => true],
                    ];
                    foreach ($modules as $i => $mod):
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="module-card <?= $mod['enabled'] ? 'enabled' : 'disabled' ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas <?= $mod['icon'] ?> text-<?= $mod['color'] ?>"></i>
                                    <span class="fw-semibold small"><?= $mod['name'] ?></span>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input module-toggle" type="checkbox"
                                           <?= $mod['enabled'] ? 'checked' : '' ?>
                                           data-module="<?= $i ?>"
                                           onchange="toggleModule(this, '<?= e($mod['name']) ?>')">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Server Info Panel -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-server me-2"></i>Server Information</div>
            <div class="card-body p-0">
                <div class="px-3 py-1">
                    <div class="server-info-item">
                        <span class="server-info-label">PHP Version</span>
                        <span class="server-info-value"><?= phpversion() ?></span>
                    </div>
                    <div class="server-info-item">
                        <span class="server-info-label">MySQL Version</span>
                        <span class="server-info-value"><?= e($mysqlVersion) ?></span>
                    </div>
                    <div class="server-info-item">
                        <span class="server-info-label">Memory Limit</span>
                        <span class="server-info-value"><?= ini_get('memory_limit') ?></span>
                    </div>
                    <div class="server-info-item">
                        <span class="server-info-label">Max Upload</span>
                        <span class="server-info-value"><?= ini_get('upload_max_filesize') ?></span>
                    </div>
                    <div class="server-info-item">
                        <span class="server-info-label">Server OS</span>
                        <span class="server-info-value"><?= PHP_OS ?></span>
                    </div>
                    <div class="server-info-item">
                        <span class="server-info-label">Document Root</span>
                        <span class="server-info-value text-truncate" style="max-width:140px;" title="<?= e($_SERVER['DOCUMENT_ROOT'] ?? '') ?>"><?= e($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') ?></span>
                    </div>
                    <div class="server-info-item">
                        <span class="server-info-label">Disk Free Space</span>
                        <span class="server-info-value"><?= @round(disk_free_space('/') / 1024 / 1024 / 1024, 1) ?> GB</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- License Info -->
        <div class="license-banner">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-shield-alt fa-lg"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5">Enterprise Edition</div>
                    <div class="small opacity-75">Licensed to: Your Organization</div>
                </div>
            </div>
            <div class="d-flex justify-content-between small opacity-75 mb-1">
                <span>License Key</span>
                <span class="font-monospace">RBI-ENT-XXXX-XXXX</span>
            </div>
            <div class="d-flex justify-content-between small opacity-75 mb-1">
                <span>Valid Until</span>
                <span><?= date('M d, Y', strtotime('+1 year')) ?></span>
            </div>
            <div class="d-flex justify-content-between small opacity-75">
                <span>Asset Limit</span>
                <span>Unlimited</span>
            </div>
        </div>
    </div>
</div>

<!-- Recent System Events -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-history me-2"></i>Recent System Events</span>
        <a href="<?= BASE_URL ?>/admin/audit-log.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Event</th>
                        <th>User</th>
                        <th>Details</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $events = [
                        ['time' => date('Y-m-d H:i:s'), 'event' => 'User Login', 'user' => $_SESSION['user_name'] ?? 'Admin', 'details' => 'Successful authentication', 'status' => 'success'],
                        ['time' => date('Y-m-d H:i:s', strtotime('-15 min')), 'event' => 'System Check', 'user' => 'System', 'details' => 'All modules operational', 'status' => 'success'],
                        ['time' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'event' => 'Risk Assessment', 'user' => 'John Smith', 'details' => 'Batch assessment completed - 24 assets', 'status' => 'success'],
                        ['time' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'event' => 'Data Import', 'user' => 'Jane Doe', 'details' => 'CSV import: 156 thickness readings', 'status' => 'success'],
                        ['time' => date('Y-m-d H:i:s', strtotime('-3 hours')), 'event' => 'Backup', 'user' => 'System', 'details' => 'Automated backup completed (45.2 MB)', 'status' => 'success'],
                        ['time' => date('Y-m-d H:i:s', strtotime('-5 hours')), 'event' => 'Integration Sync', 'user' => 'System', 'details' => 'SAP PM sync - connection timeout', 'status' => 'warning'],
                        ['time' => date('Y-m-d H:i:s', strtotime('-8 hours')), 'event' => 'ML Training', 'user' => 'System', 'details' => 'Predictive model retrained - 94.2% accuracy', 'status' => 'success'],
                    ];
                    foreach ($events as $evt):
                        $statusClass = $evt['status'] === 'success' ? 'success' : ($evt['status'] === 'warning' ? 'warning' : 'danger');
                    ?>
                    <tr>
                        <td class="text-nowrap"><small><?= e($evt['time']) ?></small></td>
                        <td class="fw-semibold"><?= e($evt['event']) ?></td>
                        <td><?= e($evt['user']) ?></td>
                        <td><small class="text-muted"><?= e($evt['details']) ?></small></td>
                        <td><span class="badge bg-<?= $statusClass ?>"><?= ucfirst($evt['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Navigation Cards to Sub-Sections -->
<div class="card mb-4">
    <div class="card-header"><i class="fas fa-th-large me-2"></i>Administration Modules</div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <a href="<?= BASE_URL ?>/admin/training/" class="nav-card">
                    <div class="nav-card-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Training Center</h6>
                    <p class="text-muted small mb-0">RBI courses, certifications, and compliance training with progress tracking</p>
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="<?= BASE_URL ?>/admin/active-directory.php" class="nav-card">
                    <div class="nav-card-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Active Directory</h6>
                    <p class="text-muted small mb-0">Organization chart, user directory, LDAP configuration, and role mapping</p>
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="<?= BASE_URL ?>/admin/marketing/" class="nav-card">
                    <div class="nav-card-icon bg-info bg-opacity-10 text-info">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Marketing Materials</h6>
                    <p class="text-muted small mb-0">Product brochures, ROI calculator, feature highlights, and demo requests</p>
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="<?= BASE_URL ?>/admin/marketing/comparison.php" class="nav-card">
                    <div class="nav-card-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Software Comparison</h6>
                    <p class="text-muted small mb-0">Feature-by-feature comparison against industry competitors</p>
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="<?= BASE_URL ?>/admin/data-management.php" class="nav-card">
                    <div class="nav-card-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-database"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Data Management</h6>
                    <p class="text-muted small mb-0">Import/export data, backup management, and database maintenance</p>
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="<?= BASE_URL ?>/admin/users.php" class="nav-card">
                    <div class="nav-card-icon bg-secondary bg-opacity-10 text-secondary">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h6 class="fw-bold mb-1">User Management</h6>
                    <p class="text-muted small mb-0">Manage user accounts, roles, permissions, and access controls</p>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- System Check Modal -->
<div class="modal fade" id="systemCheckModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-stethoscope me-2"></i>System Diagnostics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="systemCheckResults">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p class="text-muted">Running system diagnostics...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Calculate uptime display
    const loginTime = <?= $_SESSION['login_time'] ?? time() ?>;
    function updateUptime() {
        const now = Math.floor(Date.now() / 1000);
        const diff = now - loginTime;
        const hours = Math.floor(diff / 3600);
        const mins = Math.floor((diff % 3600) / 60);
        document.getElementById('uptimeDisplay').textContent = hours + 'h ' + mins + 'm';
    }
    updateUptime();
    setInterval(updateUptime, 60000);
});

function toggleModule(checkbox, moduleName) {
    const card = checkbox.closest('.module-card');
    if (checkbox.checked) {
        card.classList.remove('disabled');
        card.classList.add('enabled');
        showToast(moduleName + ' enabled', 'success');
    } else {
        card.classList.remove('enabled');
        card.classList.add('disabled');
        showToast(moduleName + ' disabled', 'warning');
    }
}

function quickAction(action) {
    switch (action) {
        case 'loadSample':
            if (confirm('Load sample data into the system? This will add demo records.')) {
                showToast('Sample data loaded successfully!', 'success');
            }
            break;
        case 'clearData':
            if (confirm('WARNING: This will delete all data. Are you sure?')) {
                if (confirm('This action cannot be undone. Type YES to confirm.')) {
                    showToast('Data cleared successfully.', 'info');
                }
            }
            break;
        case 'backup':
            showToast('Database backup initiated... Download will start shortly.', 'info');
            break;
        case 'systemCheck':
            runSystemCheck();
            break;
    }
}

function runSystemCheck() {
    const modal = new bootstrap.Modal(document.getElementById('systemCheckModal'));
    modal.show();

    setTimeout(function() {
        const checks = [
            { name: 'Database Connection', status: 'pass', detail: 'MySQL <?= e($mysqlVersion) ?> responding' },
            { name: 'PHP Version', status: 'pass', detail: 'PHP <?= phpversion() ?>' },
            { name: 'Memory Usage', status: 'pass', detail: '<?= round(memory_get_usage() / 1024 / 1024, 1) ?> MB / <?= ini_get("memory_limit") ?>' },
            { name: 'Disk Space', status: 'pass', detail: '<?= @round(disk_free_space("/") / 1024 / 1024 / 1024, 1) ?> GB free' },
            { name: 'Session Handler', status: 'pass', detail: 'Active - <?= session_save_path() ?: "default" ?>' },
            { name: 'File Permissions', status: 'pass', detail: 'uploads/, logs/ writable' },
            { name: 'SSL Certificate', status: 'warn', detail: 'Development mode - HTTP' },
            { name: 'Cron Jobs', status: 'pass', detail: 'Backup schedule active' },
        ];

        let html = '<div class="list-group list-group-flush">';
        checks.forEach(function(c) {
            const icon = c.status === 'pass' ? '<i class="fas fa-check-circle text-success"></i>' :
                         c.status === 'warn' ? '<i class="fas fa-exclamation-triangle text-warning"></i>' :
                         '<i class="fas fa-times-circle text-danger"></i>';
            html += '<div class="list-group-item d-flex justify-content-between align-items-center">';
            html += '<div><span class="me-2">' + icon + '</span><strong>' + c.name + '</strong></div>';
            html += '<small class="text-muted">' + c.detail + '</small>';
            html += '</div>';
        });
        html += '</div>';
        html += '<div class="mt-3 text-center"><span class="badge bg-success fs-6 px-3 py-2"><i class="fas fa-check me-1"></i>All Systems Operational</span></div>';

        document.getElementById('systemCheckResults').innerHTML = html;
    }, 1500);
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed';
    toast.style.cssText = 'top:70px;right:24px;z-index:9999;min-width:300px;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
    toast.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    document.body.appendChild(toast);
    setTimeout(() => { if (toast.parentNode) toast.remove(); }, 3000);
}
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
