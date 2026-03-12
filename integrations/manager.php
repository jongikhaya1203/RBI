<?php
/**
 * Integration Manager Dashboard
 * Central hub for all external system integrations
 */
$pageTitle = 'Integration Manager';
$pageSection = 'Integrations';
$currentModule = 'integrations';
require_once __DIR__ . '/../config/app.php';
requireAuth();
require_once INCLUDES_PATH . '/header.php';

$manager = new IntegrationManager();
$health = $manager->getIntegrationHealth();
$conflicts = $manager->getConflicts();
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-plug me-2"></i>Integration Manager</h1>
        <p class="text-muted mb-0">Monitor and manage all external system integrations</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" id="btnTestAll">
            <i class="fas fa-plug me-1"></i>Test All
        </button>
        <button class="btn btn-primary" id="btnSyncAll">
            <i class="fas fa-sync me-1"></i>Sync All
        </button>
    </div>
</div>

<!-- Health Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="stat-label">Active Integrations</div>
                    <div class="stat-value"><?= $health['summary']['total_active'] ?></div>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-plug"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="stat-label">Records Synced (24h)</div>
                    <div class="stat-value"><?= number_format($health['summary']['total_records_24h']) ?></div>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-database"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="stat-label">Errors (24h)</div>
                    <div class="stat-value <?= $health['summary']['total_errors_24h'] > 0 ? 'text-danger' : '' ?>"><?= $health['summary']['total_errors_24h'] ?></div>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="stat-label">Pending Conflicts</div>
                    <div class="stat-value <?= $health['summary']['pending_conflicts'] > 0 ? 'text-warning' : '' ?>"><?= $health['summary']['pending_conflicts'] ?></div>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-code-branch"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Integration Cards -->
<div class="row g-4 mb-4">
    <?php
    $integrationPages = [
        'SAP'     => ['icon' => 'fa-industry',    'color' => 'primary',  'url' => BASE_URL . '/integrations/sap.php'],
        'Maximo'  => ['icon' => 'fa-cogs',        'color' => 'info',     'url' => BASE_URL . '/integrations/maximo.php'],
        'OSIsoft' => ['icon' => 'fa-chart-area',   'color' => 'success',  'url' => BASE_URL . '/integrations/osisoft-pi.php'],
        'AVEVA'   => ['icon' => 'fa-chart-area',   'color' => 'success',  'url' => BASE_URL . '/integrations/osisoft-pi.php'],
        'PI'      => ['icon' => 'fa-chart-area',   'color' => 'success',  'url' => BASE_URL . '/integrations/osisoft-pi.php'],
    ];

    foreach ($health['integrations'] as $int):
        $healthColors = ['healthy'=>'success', 'warning'=>'warning', 'critical'=>'danger', 'inactive'=>'secondary', 'unknown'=>'secondary'];
        $healthIcons  = ['healthy'=>'fa-check-circle', 'warning'=>'fa-exclamation-triangle', 'critical'=>'fa-times-circle', 'inactive'=>'fa-pause-circle', 'unknown'=>'fa-question-circle'];

        $matchedPage = null;
        foreach ($integrationPages as $key => $page) {
            if (stripos($int['vendor'] ?? '', $key) !== false || stripos($int['name'] ?? '', $key) !== false) {
                $matchedPage = $page;
                break;
            }
        }
    ?>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas <?= $matchedPage['icon'] ?? 'fa-plug' ?> me-2 text-<?= $matchedPage['color'] ?? 'secondary' ?>"></i>
                        <?= e($int['name']) ?>
                    </span>
                    <span class="badge bg-<?= $healthColors[$int['health']] ?? 'secondary' ?>">
                        <i class="fas <?= $healthIcons[$int['health']] ?? 'fa-question' ?> me-1"></i>
                        <?= ucfirst($int['health']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-3">
                        <tr><td class="text-muted" style="width:40%">Vendor</td><td><?= e($int['vendor'] ?? 'N/A') ?></td></tr>
                        <tr><td class="text-muted">Type</td><td><?= e($int['type'] ?? 'N/A') ?></td></tr>
                        <tr><td class="text-muted">Auth</td><td><?= e($int['auth_type'] ?? 'N/A') ?></td></tr>
                        <tr><td class="text-muted">Sync Freq</td><td><?= ($int['sync_frequency'] ?? 60) ?> min</td></tr>
                        <tr>
                            <td class="text-muted">Last Sync</td>
                            <td>
                                <?php if ($int['last_sync_at']): ?>
                                    <?= date('M j, g:i A', strtotime($int['last_sync_at'])) ?>
                                    <br>
                                    <span class="badge bg-<?= $int['last_sync_status'] === 'success' ? 'success' : ($int['last_sync_status'] === 'failed' ? 'danger' : 'warning') ?> small">
                                        <?= ucfirst($int['last_sync_status'] ?? 'never') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($int['errors_24h'] > 0): ?>
                            <tr><td class="text-muted">Errors (24h)</td><td class="text-danger fw-bold"><?= $int['errors_24h'] ?></td></tr>
                        <?php endif; ?>
                        <?php if ($int['pending_conflicts'] > 0): ?>
                            <tr><td class="text-muted">Conflicts</td><td class="text-warning fw-bold"><?= $int['pending_conflicts'] ?></td></tr>
                        <?php endif; ?>
                    </table>

                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary btn-sync-one" data-id="<?= $int['id'] ?>">
                            <i class="fas fa-sync me-1"></i>Sync
                        </button>
                        <button class="btn btn-sm btn-outline-success btn-test-one" data-id="<?= $int['id'] ?>">
                            <i class="fas fa-plug me-1"></i>Test
                        </button>
                        <?php if ($matchedPage): ?>
                            <a href="<?= $matchedPage['url'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-cog me-1"></i>Configure
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($health['integrations'])): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-plug fs-1 text-muted mb-3 d-block"></i>
                    <h5>No Integrations Configured</h5>
                    <p class="text-muted">Set up connections to SAP PM, IBM Maximo, or OSIsoft PI to begin synchronizing data.</p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="<?= BASE_URL ?>/integrations/sap.php" class="btn btn-primary"><i class="fas fa-industry me-1"></i>SAP PM</a>
                        <a href="<?= BASE_URL ?>/integrations/maximo.php" class="btn btn-info"><i class="fas fa-cogs me-1"></i>IBM Maximo</a>
                        <a href="<?= BASE_URL ?>/integrations/osisoft-pi.php" class="btn btn-success"><i class="fas fa-chart-area me-1"></i>OSIsoft PI</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="row g-4">
    <!-- Data Flow Visualization -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="fas fa-project-diagram me-2"></i>Data Flow</div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-around flex-wrap gap-3 py-3">
                    <!-- External Systems -->
                    <div class="text-center">
                        <div class="border rounded p-3 mb-2" style="min-width:120px;">
                            <i class="fas fa-industry fs-3 text-primary d-block mb-1"></i>
                            <strong class="small">SAP PM</strong>
                            <br><small class="text-muted">Equipment, Orders</small>
                        </div>
                        <div class="border rounded p-3 mb-2">
                            <i class="fas fa-cogs fs-3 text-info d-block mb-1"></i>
                            <strong class="small">Maximo</strong>
                            <br><small class="text-muted">Assets, WOs</small>
                        </div>
                        <div class="border rounded p-3">
                            <i class="fas fa-chart-area fs-3 text-success d-block mb-1"></i>
                            <strong class="small">PI Historian</strong>
                            <br><small class="text-muted">Process Data</small>
                        </div>
                    </div>

                    <!-- Arrows -->
                    <div class="text-center">
                        <div class="mb-3"><i class="fas fa-arrows-alt-h fs-3 text-primary"></i><br><small>Bidirectional</small></div>
                        <div class="mb-3"><i class="fas fa-arrows-alt-h fs-3 text-info"></i><br><small>Bidirectional</small></div>
                        <div><i class="fas fa-arrow-right fs-3 text-success"></i><br><small>Inbound</small></div>
                    </div>

                    <!-- RBI Suite -->
                    <div class="text-center">
                        <div class="border border-primary border-2 rounded p-4" style="min-width:160px;">
                            <i class="fas fa-shield-alt fs-2 text-primary d-block mb-2"></i>
                            <strong>RBI Engineering Suite</strong>
                            <hr class="my-2">
                            <small class="text-muted d-block">Asset Registry</small>
                            <small class="text-muted d-block">Risk Assessments</small>
                            <small class="text-muted d-block">Inspection Plans</small>
                            <small class="text-muted d-block">Corrosion Data</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conflict Resolution Queue -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-code-branch me-2"></i>Conflict Queue</span>
                <span class="badge bg-warning"><?= count($conflicts) ?></span>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($conflicts)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fs-3 d-block mb-2 text-success"></i>
                        No pending conflicts
                    </div>
                <?php else:
                    foreach ($conflicts as $conflict): ?>
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <strong class="small"><?= e($conflict['entity_type']) ?></strong>
                                <small class="text-muted"><?= date('M j', strtotime($conflict['created_at'])) ?></small>
                            </div>
                            <div class="small mb-1">
                                <code><?= e($conflict['field_name']) ?></code>
                            </div>
                            <div class="row g-1 mb-2 small">
                                <div class="col-6">
                                    <span class="text-muted">Internal:</span> <?= e(substr($conflict['internal_value'] ?? '-', 0, 30)) ?>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted">External:</span> <?= e(substr($conflict['external_value'] ?? '-', 0, 30)) ?>
                                </div>
                            </div>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary btn-resolve" data-id="<?= $conflict['id'] ?>" data-resolution="internal_wins">Keep Ours</button>
                                <button class="btn btn-sm btn-outline-success btn-resolve" data-id="<?= $conflict['id'] ?>" data-resolution="external_wins">Accept Theirs</button>
                            </div>
                        </div>
                    <?php endforeach;
                endif; ?>
            </div>
        </div>
    </div>

    <!-- Sync Schedule -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-clock me-2"></i>Sync Schedule</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Integration</th>
                            <th>Frequency</th>
                            <th>Next Run</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($health['integrations'] as $int): ?>
                            <tr>
                                <td><?= e($int['name']) ?></td>
                                <td>
                                    <select class="form-select form-select-sm sched-freq" data-id="<?= $int['id'] ?>" style="width:auto;">
                                        <option value="5" <?= ($int['sync_frequency']??60)==5?'selected':'' ?>>5 min</option>
                                        <option value="15" <?= ($int['sync_frequency']??60)==15?'selected':'' ?>>15 min</option>
                                        <option value="60" <?= ($int['sync_frequency']??60)==60?'selected':'' ?>>1 hour</option>
                                        <option value="360" <?= ($int['sync_frequency']??60)==360?'selected':'' ?>>6 hours</option>
                                        <option value="1440" <?= ($int['sync_frequency']??60)==1440?'selected':'' ?>>Daily</option>
                                        <option value="10080" <?= ($int['sync_frequency']??60)==10080?'selected':'' ?>>Weekly</option>
                                    </select>
                                </td>
                                <td class="small">
                                    <?php
                                    if ($int['last_sync_at']) {
                                        $next = strtotime($int['last_sync_at']) + (($int['sync_frequency'] ?? 60) * 60);
                                        echo date('M j, g:i A', $next);
                                    } else {
                                        echo '<span class="text-muted">Not scheduled</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary btn-save-sched" data-id="<?= $int['id'] ?>">
                                        <i class="fas fa-save"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- System Settings -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-sliders-h me-2"></i>Integration Settings</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Default Conflict Resolution</label>
                    <select class="form-select">
                        <option value="manual">Manual Review</option>
                        <option value="external_wins">External System Wins</option>
                        <option value="internal_wins">RBI Data Wins</option>
                        <option value="newest_wins">Newest Timestamp Wins</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Data Cache TTL (minutes)</label>
                    <input type="number" class="form-control" value="30" min="5" max="1440">
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="autoRetry" checked>
                        <label class="form-check-label" for="autoRetry">Auto-retry failed syncs</label>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notifyErrors" checked>
                        <label class="form-check-label" for="notifyErrors">Email notification on sync failures</label>
                    </div>
                </div>
                <button class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Settings</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sync individual integration
    document.querySelectorAll('.btn-sync-one').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('<?= BASE_URL ?>/api/integrations.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'sync', integration_id: id, sync_type: 'manual'})
            }).then(r => r.json()).then(result => {
                showToast(result.success ? 'Sync completed' : (result.error||'Sync failed'), result.success ? 'success' : 'danger');
                if (result.success) setTimeout(() => location.reload(), 1500);
            }).finally(() => { this.disabled = false; this.innerHTML = '<i class="fas fa-sync me-1"></i>Sync'; });
        });
    });

    // Test individual
    document.querySelectorAll('.btn-test-one').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('<?= BASE_URL ?>/api/integrations.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'test_connection', integration_id: id})
            }).then(r => r.json()).then(result => {
                const ok = result.success && result.data?.status === 'connected';
                showToast(ok ? 'Connection OK ('+result.data.latency_ms+'ms)' : (result.data?.message||'Failed'), ok ? 'success' : 'danger');
            }).finally(() => { this.disabled = false; this.innerHTML = '<i class="fas fa-plug me-1"></i>Test'; });
        });
    });

    // Sync all
    document.getElementById('btnSyncAll').addEventListener('click', function() {
        if (!confirm('Run sync for all active integrations?')) return;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Syncing...';

        fetch('<?= BASE_URL ?>/api/integrations.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'sync_all'})
        }).then(r => r.json()).then(result => {
            showToast(result.success ? 'All syncs completed' : 'Some syncs failed', result.success ? 'success' : 'warning');
            setTimeout(() => location.reload(), 2000);
        }).finally(() => { this.disabled = false; this.innerHTML = '<i class="fas fa-sync me-1"></i>Sync All'; });
    });

    // Resolve conflicts
    document.querySelectorAll('.btn-resolve').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const resolution = this.dataset.resolution;

            fetch('<?= BASE_URL ?>/api/integrations.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'resolve_conflict', conflict_id: id, resolution: resolution})
            }).then(r => r.json()).then(result => {
                if (result.success) {
                    this.closest('.border').remove();
                    showToast('Conflict resolved', 'success');
                }
            });
        });
    });

    // Save schedule
    document.querySelectorAll('.btn-save-sched').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const freq = document.querySelector(`.sched-freq[data-id="${id}"]`).value;

            fetch('<?= BASE_URL ?>/api/integrations.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'schedule_sync', integration_id: id, schedule: freq})
            }).then(r => r.json()).then(result => {
                showToast(result.success ? 'Schedule saved' : 'Failed', result.success ? 'success' : 'danger');
            });
        });
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
