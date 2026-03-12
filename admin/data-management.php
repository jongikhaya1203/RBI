<?php
/**
 * Data Management - RBI Engineering Suite
 * Comprehensive data management: sample data, DB status, import/export, maintenance, backups
 */
$pageTitle = 'Data Management';
$pageSection = 'Administration';
$currentModule = 'admin';
require_once __DIR__ . '/../config/app.php';
requireAuth();
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-database me-2"></i>Data Management</h1>
    <div>
        <span class="badge bg-info fs-6"><i class="fas fa-server me-1"></i><?= DB_NAME ?></span>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs mb-4" id="dataMgmtTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" id="sample-tab" data-bs-toggle="tab" data-bs-target="#sampleData" type="button" role="tab">
            <i class="fas fa-flask me-1"></i>Sample Data
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="status-tab" data-bs-toggle="tab" data-bs-target="#dbStatus" type="button" role="tab">
            <i class="fas fa-heartbeat me-1"></i>Database Status
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="importexport-tab" data-bs-toggle="tab" data-bs-target="#importExport" type="button" role="tab">
            <i class="fas fa-exchange-alt me-1"></i>Import / Export
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">
            <i class="fas fa-wrench me-1"></i>Maintenance
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backupRestore" type="button" role="tab">
            <i class="fas fa-hdd me-1"></i>Backup & Restore
        </button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="dataMgmtContent">

    <!-- ================================================================== -->
    <!-- TAB 1: SAMPLE DATA MANAGEMENT -->
    <!-- ================================================================== -->
    <div class="tab-pane fade show active" id="sampleData" role="tabpanel">
        <div class="row g-4">

            <!-- Load Sample Data Card -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-upload text-success me-2"></i>Load Sample Data
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Load a comprehensive set of realistic petroleum refinery sample data for demonstration and testing purposes.</p>
                        <div class="small mb-3">
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>Demo Users</span><span class="fw-semibold">4 users</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>Equipment Hierarchy</span><span class="fw-semibold">36 items</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>Assets (vessels, exchangers, tanks)</span><span class="fw-semibold">20 assets</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>Corrosion Circuits</span><span class="fw-semibold">8 circuits</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>Damage Mechanism Assignments</span><span class="fw-semibold">30 assignments</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>Risk Assessments</span><span class="fw-semibold">15 assessments</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>Inspection Plans & Tasks</span><span class="fw-semibold">10 plans / 20 tasks</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>Inspection Findings</span><span class="fw-semibold">15 findings</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>Corrosion Rate Tracking</span><span class="fw-semibold">20 readings</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>Remaining Life Estimates</span><span class="fw-semibold">15 records</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>Risk Scores (ML-enhanced)</span><span class="fw-semibold">20 scores</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span>Risk Alerts</span><span class="fw-semibold">10 alerts</span>
                            </div>
                        </div>
                        <div id="loadProgress" class="d-none mb-3">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block">Loading sample data...</small>
                        </div>
                        <div id="loadResult" class="d-none mb-3"></div>
                        <button class="btn btn-success w-100 btn-lg" id="btnLoadSampleData">
                            <i class="fas fa-play-circle me-2"></i>Load Sample Data
                        </button>
                    </div>
                </div>
            </div>

            <!-- Clear Sample Data Card -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-eraser text-danger me-2"></i>Clear Sample Data
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This will permanently delete all data from transactional tables. Library data (roles, permissions, damage mechanisms, risk matrix, inspection strategies) and the admin user will be preserved.
                        </div>
                        <p class="text-muted small mb-3">Tables that will be cleared: users (except admin), equipment hierarchy, assets, design/operational data, corrosion circuits, damage mechanism assignments, risk assessments, inspection plans/tasks/findings, corrosion rate tracking, remaining life estimates, risk scores, risk alerts, ML models/predictions, and all audit logs.</p>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmClear">
                            <label class="form-check-label text-danger fw-semibold" for="confirmClear">
                                I understand this will delete all sample/transactional data
                            </label>
                        </div>
                        <div id="clearProgress" class="d-none mb-3">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 100%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block">Clearing data...</small>
                        </div>
                        <div id="clearResult" class="d-none mb-3"></div>
                        <button class="btn btn-danger w-100" id="btnClearData" disabled>
                            <i class="fas fa-trash-alt me-2"></i>Clear All Sample Data
                        </button>
                    </div>
                </div>
            </div>

            <!-- Factory Reset Card -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-redo text-warning me-2"></i>Reset to Factory
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger mb-3">
                            <i class="fas fa-radiation me-2"></i>
                            <strong>Danger Zone:</strong> This will DROP and RECREATE all database tables from the original schema files. ALL data will be permanently lost, including the admin user, all configurations, and library data.
                        </div>
                        <p class="text-muted small mb-3">This operation re-executes <code>schema.sql</code>, <code>ml_tables.sql</code>, and <code>integration_tables.sql</code> to rebuild the database from scratch.</p>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="confirmReset1">
                            <label class="form-check-label text-danger fw-semibold" for="confirmReset1">
                                I understand ALL data will be permanently destroyed
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Type <strong>RESET</strong> to confirm:</label>
                            <input type="text" class="form-control form-control-sm" id="resetConfirmText" placeholder="Type RESET here" autocomplete="off">
                        </div>
                        <div id="resetProgress" class="d-none mb-3">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width: 100%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block">Executing factory reset...</small>
                        </div>
                        <div id="resetResult" class="d-none mb-3"></div>
                        <button class="btn btn-warning w-100" id="btnFactoryReset" disabled>
                            <i class="fas fa-redo me-2"></i>Factory Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================== -->
    <!-- TAB 2: DATABASE STATUS -->
    <!-- ================================================================== -->
    <div class="tab-pane fade" id="dbStatus" role="tabpanel">
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Total Tables</div>
                            <div class="stat-value" id="statTableCount">--</div>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-table"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Total Rows</div>
                            <div class="stat-value" id="statTotalRows">--</div>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-database"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Database Size</div>
                            <div class="stat-value" id="statDbSize">--</div>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="fas fa-hdd"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">MySQL Version</div>
                            <div class="stat-value" id="statMysqlVer" style="font-size: 1.2rem;">--</div>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-code-branch"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connection Info -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-plug me-2"></i>Connection Information</span>
                <button class="btn btn-sm btn-outline-primary" id="btnRefreshStatus">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
            </div>
            <div class="card-body">
                <div class="row" id="connectionInfo">
                    <div class="col-md-3"><strong>Host:</strong> <span id="connHost">--</span></div>
                    <div class="col-md-3"><strong>Database:</strong> <span id="connDb">--</span></div>
                    <div class="col-md-3"><strong>User:</strong> <span id="connUser">--</span></div>
                    <div class="col-md-3"><strong>Charset:</strong> <span id="connCharset">--</span></div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3"><strong>Last Backup:</strong> <span id="connBackup" class="text-muted">--</span></div>
                </div>
            </div>
        </div>

        <!-- Table Statistics -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-bar me-2"></i>Table Statistics
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0" id="tableStatsTable">
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th class="text-end">Rows</th>
                                <th class="text-end">Data Size</th>
                                <th class="text-end">Index Size</th>
                                <th class="text-end">Auto Increment</th>
                                <th>Engine</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody id="tableStatsBody">
                            <tr><td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin me-2"></i>Loading table statistics...
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================== -->
    <!-- TAB 3: IMPORT / EXPORT -->
    <!-- ================================================================== -->
    <div class="tab-pane fade" id="importExport" role="tabpanel">
        <div class="row g-4">

            <!-- Export Section -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-file-export text-primary me-2"></i>Export Data
                    </div>
                    <div class="card-body">
                        <!-- Export All SQL -->
                        <h6 class="fw-semibold mb-2">SQL Dump (Full Database)</h6>
                        <p class="text-muted small">Export the entire database as a SQL dump file including structure and data.</p>
                        <button class="btn btn-primary mb-4" id="btnExportAllSql">
                            <i class="fas fa-download me-1"></i>Export Full SQL Dump
                        </button>

                        <!-- Export Specific Tables -->
                        <h6 class="fw-semibold mb-2">Export Specific Tables</h6>
                        <div id="exportTableCheckboxes" class="mb-2" style="max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px;">
                            <div class="text-muted small"><i class="fas fa-spinner fa-spin me-1"></i>Loading tables...</div>
                        </div>
                        <div class="d-flex gap-2 mb-4">
                            <button class="btn btn-sm btn-outline-secondary" id="btnSelectAllTables">Select All</button>
                            <button class="btn btn-sm btn-outline-secondary" id="btnDeselectAllTables">Deselect All</button>
                            <button class="btn btn-sm btn-primary" id="btnExportSelectedSql">
                                <i class="fas fa-download me-1"></i>Export Selected
                            </button>
                        </div>

                        <!-- Export CSV -->
                        <h6 class="fw-semibold mb-2">Export as CSV</h6>
                        <div class="input-group">
                            <select class="form-select" id="csvExportTable">
                                <option value="">Select a table...</option>
                            </select>
                            <button class="btn btn-outline-primary" id="btnExportCsv">
                                <i class="fas fa-file-csv me-1"></i>Export CSV
                            </button>
                        </div>
                    </div>
                </div>
                <div id="exportResult" class="d-none"></div>
            </div>

            <!-- Import Section -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-file-import text-success me-2"></i>Import Data
                    </div>
                    <div class="card-body">
                        <!-- Import SQL -->
                        <h6 class="fw-semibold mb-2">Import SQL File</h6>
                        <p class="text-muted small">Upload a .sql file to execute against the database. Use caution with DROP/ALTER statements.</p>
                        <form id="importSqlForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input type="file" class="form-control" id="sqlFileInput" name="sql_file" accept=".sql">
                            </div>
                            <button type="submit" class="btn btn-success mb-4" id="btnImportSql">
                                <i class="fas fa-upload me-1"></i>Import SQL
                            </button>
                        </form>

                        <!-- Import CSV -->
                        <h6 class="fw-semibold mb-2">Import CSV to Table</h6>
                        <p class="text-muted small">Upload a CSV file with headers matching the target table columns.</p>
                        <form id="importCsvForm" enctype="multipart/form-data">
                            <div class="mb-2">
                                <select class="form-select" id="csvImportTable" name="table_name">
                                    <option value="">Select target table...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <input type="file" class="form-control" id="csvFileInput" name="csv_file" accept=".csv">
                            </div>
                            <button type="submit" class="btn btn-success mb-4" id="btnImportCsv">
                                <i class="fas fa-upload me-1"></i>Import CSV
                            </button>
                        </form>

                        <!-- Drag and Drop Zone -->
                        <h6 class="fw-semibold mb-2">Drag & Drop</h6>
                        <div id="dropZone" class="border-2 border-dashed rounded-3 p-4 text-center" style="border: 2px dashed #cbd5e1; background: #f8fafc; cursor: pointer; transition: all 0.2s;">
                            <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2 d-block"></i>
                            <p class="text-muted mb-0">Drag and drop .sql or .csv files here</p>
                            <small class="text-muted">or click to browse</small>
                            <input type="file" id="dropZoneInput" class="d-none" accept=".sql,.csv" multiple>
                        </div>
                    </div>
                </div>
                <div id="importResult" class="d-none"></div>
            </div>
        </div>
    </div>

    <!-- ================================================================== -->
    <!-- TAB 4: MAINTENANCE -->
    <!-- ================================================================== -->
    <div class="tab-pane fade" id="maintenance" role="tabpanel">
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-tachometer-alt fa-2x text-primary"></i>
                            </div>
                        </div>
                        <h6 class="fw-semibold">Optimize Tables</h6>
                        <p class="text-muted small">Reclaim unused space and defragment data files for all tables.</p>
                        <button class="btn btn-primary" id="btnOptimize">
                            <i class="fas fa-tachometer-alt me-1"></i>Optimize
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="rounded-circle bg-info bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-stethoscope fa-2x text-info"></i>
                            </div>
                        </div>
                        <h6 class="fw-semibold">Check Tables</h6>
                        <p class="text-muted small">Verify table integrity and check for corruption or errors.</p>
                        <button class="btn btn-info text-white" id="btnCheckTables">
                            <i class="fas fa-stethoscope me-1"></i>Check
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-tools fa-2x text-warning"></i>
                            </div>
                        </div>
                        <h6 class="fw-semibold">Repair Tables</h6>
                        <p class="text-muted small">Attempt to repair corrupted tables. Note: InnoDB tables use a different repair mechanism.</p>
                        <button class="btn btn-warning" id="btnRepairTables">
                            <i class="fas fa-tools me-1"></i>Repair
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-broom fa-2x text-danger"></i>
                            </div>
                        </div>
                        <h6 class="fw-semibold">Flush Caches</h6>
                        <p class="text-muted small">Clear MySQL table cache and query cache to free memory.</p>
                        <button class="btn btn-danger" id="btnFlushCaches">
                            <i class="fas fa-broom me-1"></i>Flush
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Results -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-terminal me-2"></i>Maintenance Results
            </div>
            <div class="card-body">
                <div id="maintenanceResults" class="font-monospace small" style="max-height: 400px; overflow-y: auto; background: #1e293b; color: #e2e8f0; padding: 16px; border-radius: 8px;">
                    <span class="text-muted">Run a maintenance operation above to see results here...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================== -->
    <!-- TAB 5: BACKUP & RESTORE -->
    <!-- ================================================================== -->
    <div class="tab-pane fade" id="backupRestore" role="tabpanel">
        <div class="row g-4">

            <!-- Create Backup -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-plus-circle text-success me-2"></i>Create Backup
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Generate a complete SQL dump of the database and save it to the server's <code>backups/</code> directory.</p>
                        <div id="backupProgress" class="d-none mb-3">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block">Creating backup...</small>
                        </div>
                        <div id="backupResult" class="d-none mb-3"></div>
                        <button class="btn btn-success btn-lg" id="btnCreateBackup">
                            <i class="fas fa-save me-2"></i>Create Backup Now
                        </button>
                    </div>
                </div>

                <!-- Restore from Backup -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-undo text-warning me-2"></i>Restore from Backup
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Upload a previously exported SQL backup file to restore the database.</p>
                        <div class="alert alert-warning small">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Restoring from backup will overwrite existing data. Create a backup first.
                        </div>
                        <form id="restoreForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input type="file" class="form-control" id="restoreFileInput" name="sql_file" accept=".sql">
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-undo me-1"></i>Restore from File
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Backup List -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-list me-2"></i>Previous Backups</span>
                        <button class="btn btn-sm btn-outline-primary" id="btnRefreshBackups">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div id="backupList" class="list-group list-group-flush">
                            <div class="list-group-item text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin me-2"></i>Loading backups...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Auto-backup Settings -->
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-clock me-2"></i>Auto-Backup Schedule
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="autoBackupEnabled">
                            <label class="form-check-label" for="autoBackupEnabled">Enable automatic backups</label>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">Frequency</label>
                                <select class="form-select form-select-sm" id="autoBackupFreq">
                                    <option value="daily">Daily</option>
                                    <option value="weekly" selected>Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Retention (days)</label>
                                <input type="number" class="form-control form-control-sm" id="autoBackupRetention" value="30" min="1" max="365">
                            </div>
                        </div>
                        <button class="btn btn-sm btn-primary mt-3" id="btnSaveAutoBackup">
                            <i class="fas fa-save me-1"></i>Save Settings
                        </button>
                        <p class="text-muted small mt-2 mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Auto-backup requires a server-side cron job. Configure your scheduler to call <code>api/data-management.php?action=export_sql</code> at the desired frequency.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /tab-content -->

<!-- JavaScript for Data Management -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const API = '<?= BASE_URL ?>/api/data-management.php';

    // ════════════════════════════════════════════════════════════════════════
    // HELPER FUNCTIONS
    // ════════════════════════════════════════════════════════════════════════

    function apiCall(action, method, body, progressEl) {
        if (progressEl) {
            progressEl.classList.remove('d-none');
        }

        const opts = { method: method || 'POST' };
        if (body instanceof FormData) {
            opts.body = body;
        } else if (body) {
            opts.headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
            opts.body = new URLSearchParams(body).toString();
        }

        const url = method === 'GET' ? API + '?action=' + action : API + '?action=' + action;
        return fetch(url, opts)
            .then(r => r.json())
            .finally(() => {
                if (progressEl) progressEl.classList.add('d-none');
            });
    }

    function showResult(el, data, type) {
        el.classList.remove('d-none');
        const alertClass = data.success ? 'alert-success' : 'alert-danger';
        let html = '<div class="alert ' + alertClass + ' alert-dismissible fade show small">';
        html += '<button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>';
        html += '<strong>' + (data.success ? 'Success' : 'Error') + ':</strong> ' + (data.message || data.error || 'Unknown');

        if (data.summary) {
            html += '<hr class="my-2"><div class="row">';
            for (const [key, val] of Object.entries(data.summary)) {
                html += '<div class="col-6"><small>' + key.replace(/_/g, ' ') + ': <strong>' + val + '</strong></small></div>';
            }
            html += '</div>';
        }

        if (data.cleared) {
            html += '<hr class="my-2">';
            let total = 0;
            for (const [table, count] of Object.entries(data.cleared)) {
                if (typeof count === 'number' && count > 0) {
                    html += '<small class="d-block">' + table + ': ' + count + ' rows deleted</small>';
                    total += count;
                }
            }
            html += '<small class="fw-bold mt-1 d-block">Total: ' + total + ' rows cleared</small>';
        }

        html += '</div>';
        el.innerHTML = html;
    }

    function formatNumber(n) {
        return new Intl.NumberFormat().format(n);
    }

    // ════════════════════════════════════════════════════════════════════════
    // TAB 1: SAMPLE DATA
    // ════════════════════════════════════════════════════════════════════════

    // Load Sample Data
    document.getElementById('btnLoadSampleData').addEventListener('click', function() {
        this.disabled = true;
        apiCall('load_sample_data', 'POST', {}, document.getElementById('loadProgress'))
            .then(data => {
                showResult(document.getElementById('loadResult'), data);
                this.disabled = false;
            })
            .catch(err => {
                showResult(document.getElementById('loadResult'), { success: false, error: err.message });
                this.disabled = false;
            });
    });

    // Clear Data - checkbox control
    document.getElementById('confirmClear').addEventListener('change', function() {
        document.getElementById('btnClearData').disabled = !this.checked;
    });

    document.getElementById('btnClearData').addEventListener('click', function() {
        if (!document.getElementById('confirmClear').checked) return;
        this.disabled = true;
        apiCall('clear_sample_data', 'POST', {}, document.getElementById('clearProgress'))
            .then(data => {
                showResult(document.getElementById('clearResult'), data);
                document.getElementById('confirmClear').checked = false;
                this.disabled = true;
            })
            .catch(err => {
                showResult(document.getElementById('clearResult'), { success: false, error: err.message });
                this.disabled = false;
            });
    });

    // Factory Reset - double confirmation
    function checkResetReady() {
        const cb = document.getElementById('confirmReset1').checked;
        const text = document.getElementById('resetConfirmText').value;
        document.getElementById('btnFactoryReset').disabled = !(cb && text === 'RESET');
    }
    document.getElementById('confirmReset1').addEventListener('change', checkResetReady);
    document.getElementById('resetConfirmText').addEventListener('input', checkResetReady);

    document.getElementById('btnFactoryReset').addEventListener('click', function() {
        if (!confirm('FINAL WARNING: This will completely destroy all data and recreate tables. Continue?')) return;
        this.disabled = true;
        apiCall('factory_reset', 'POST', { confirm: 'RESET' }, document.getElementById('resetProgress'))
            .then(data => {
                showResult(document.getElementById('resetResult'), data);
                document.getElementById('confirmReset1').checked = false;
                document.getElementById('resetConfirmText').value = '';
                this.disabled = true;
            })
            .catch(err => {
                showResult(document.getElementById('resetResult'), { success: false, error: err.message });
                this.disabled = false;
            });
    });

    // ════════════════════════════════════════════════════════════════════════
    // TAB 2: DATABASE STATUS
    // ════════════════════════════════════════════════════════════════════════

    function loadDbStatus() {
        fetch(API + '?action=db_status')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;

                document.getElementById('statTableCount').textContent = data.table_count;
                document.getElementById('statTotalRows').textContent = formatNumber(data.total_rows);
                document.getElementById('statDbSize').textContent = data.total_size;
                document.getElementById('statMysqlVer').textContent = data.mysql_version;
                document.getElementById('connHost').textContent = data.connection.host + ':' + data.connection.port;
                document.getElementById('connDb').textContent = data.connection.database;
                document.getElementById('connUser').textContent = data.connection.user;
                document.getElementById('connCharset').textContent = data.connection.charset;
                document.getElementById('connBackup').textContent = data.last_backup || 'Never';

                let html = '';
                data.tables.forEach(t => {
                    html += '<tr>';
                    html += '<td><code class="text-primary">' + t.TABLE_NAME + '</code></td>';
                    html += '<td class="text-end">' + formatNumber(t.TABLE_ROWS || 0) + '</td>';
                    html += '<td class="text-end">' + t.DATA_LENGTH_FORMATTED + '</td>';
                    html += '<td class="text-end">' + t.INDEX_LENGTH_FORMATTED + '</td>';
                    html += '<td class="text-end">' + (t.AUTO_INCREMENT || '-') + '</td>';
                    html += '<td><span class="badge bg-secondary">' + (t.ENGINE || '-') + '</span></td>';
                    html += '<td class="text-muted small">' + (t.UPDATE_TIME || '-') + '</td>';
                    html += '</tr>';
                });
                document.getElementById('tableStatsBody').innerHTML = html;
            });
    }

    document.getElementById('btnRefreshStatus').addEventListener('click', loadDbStatus);
    // Load when tab is shown
    document.getElementById('status-tab').addEventListener('shown.bs.tab', loadDbStatus);

    // ════════════════════════════════════════════════════════════════════════
    // TAB 3: IMPORT / EXPORT
    // ════════════════════════════════════════════════════════════════════════

    function loadTableList() {
        fetch(API + '?action=table_list')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;

                // Checkboxes for export
                let cbHtml = '<div class="row g-1">';
                data.tables.forEach(t => {
                    cbHtml += '<div class="col-6"><div class="form-check"><input class="form-check-input export-table-cb" type="checkbox" value="' + t + '" id="exp_' + t + '"><label class="form-check-label small" for="exp_' + t + '">' + t + '</label></div></div>';
                });
                cbHtml += '</div>';
                document.getElementById('exportTableCheckboxes').innerHTML = cbHtml;

                // Dropdowns for CSV export/import
                let optHtml = '<option value="">Select a table...</option>';
                data.tables.forEach(t => {
                    optHtml += '<option value="' + t + '">' + t + '</option>';
                });
                document.getElementById('csvExportTable').innerHTML = optHtml;
                document.getElementById('csvImportTable').innerHTML = optHtml;
            });
    }

    document.getElementById('importexport-tab').addEventListener('shown.bs.tab', loadTableList);

    document.getElementById('btnSelectAllTables').addEventListener('click', function() {
        document.querySelectorAll('.export-table-cb').forEach(cb => cb.checked = true);
    });
    document.getElementById('btnDeselectAllTables').addEventListener('click', function() {
        document.querySelectorAll('.export-table-cb').forEach(cb => cb.checked = false);
    });

    // Export full SQL
    document.getElementById('btnExportAllSql').addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Exporting...';
        fetch(API + '?action=export_sql', { method: 'POST' })
            .then(r => r.json())
            .then(data => {
                const resultEl = document.getElementById('exportResult');
                resultEl.classList.remove('d-none');
                if (data.success) {
                    resultEl.innerHTML = '<div class="alert alert-success alert-dismissible fade show small"><button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>Backup created: <strong>' + data.filename + '</strong> (' + data.size + ') <a href="' + data.path + '" class="ms-2 btn btn-sm btn-outline-success" download><i class="fas fa-download me-1"></i>Download</a></div>';
                } else {
                    resultEl.innerHTML = '<div class="alert alert-danger small">' + data.error + '</div>';
                }
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-download me-1"></i>Export Full SQL Dump';
            });
    });

    // Export selected tables SQL
    document.getElementById('btnExportSelectedSql').addEventListener('click', function() {
        const selected = [];
        document.querySelectorAll('.export-table-cb:checked').forEach(cb => selected.push(cb.value));
        if (selected.length === 0) {
            alert('Please select at least one table.');
            return;
        }
        this.disabled = true;
        fetch(API + '?action=export_sql&tables=' + selected.join(','), { method: 'POST' })
            .then(r => r.json())
            .then(data => {
                const resultEl = document.getElementById('exportResult');
                resultEl.classList.remove('d-none');
                if (data.success) {
                    resultEl.innerHTML = '<div class="alert alert-success alert-dismissible fade show small"><button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>Exported ' + selected.length + ' tables: <strong>' + data.filename + '</strong> (' + data.size + ') <a href="' + data.path + '" class="ms-2 btn btn-sm btn-outline-success" download><i class="fas fa-download me-1"></i>Download</a></div>';
                }
                this.disabled = false;
            });
    });

    // Export CSV
    document.getElementById('btnExportCsv').addEventListener('click', function() {
        const table = document.getElementById('csvExportTable').value;
        if (!table) { alert('Please select a table.'); return; }
        window.location.href = API + '?action=export_csv&table=' + encodeURIComponent(table);
    });

    // Import SQL
    document.getElementById('importSqlForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const file = document.getElementById('sqlFileInput').files[0];
        if (!file) { alert('Please select a SQL file.'); return; }

        const formData = new FormData();
        formData.append('sql_file', file);

        document.getElementById('btnImportSql').disabled = true;
        document.getElementById('btnImportSql').innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Importing...';

        fetch(API + '?action=import_sql', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                showResult(document.getElementById('importResult'), data);
                document.getElementById('btnImportSql').disabled = false;
                document.getElementById('btnImportSql').innerHTML = '<i class="fas fa-upload me-1"></i>Import SQL';
            });
    });

    // Import CSV
    document.getElementById('importCsvForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const table = document.getElementById('csvImportTable').value;
        const file = document.getElementById('csvFileInput').files[0];
        if (!table || !file) { alert('Please select a table and CSV file.'); return; }

        const formData = new FormData();
        formData.append('table_name', table);
        formData.append('csv_file', file);

        document.getElementById('btnImportCsv').disabled = true;
        fetch(API + '?action=import_csv', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                showResult(document.getElementById('importResult'), data);
                document.getElementById('btnImportCsv').disabled = false;
            });
    });

    // Drag and Drop
    const dropZone = document.getElementById('dropZone');
    const dropInput = document.getElementById('dropZoneInput');

    dropZone.addEventListener('click', () => dropInput.click());

    ['dragenter', 'dragover'].forEach(evt => {
        dropZone.addEventListener(evt, function(e) {
            e.preventDefault();
            this.style.borderColor = '#3b82f6';
            this.style.background = '#eff6ff';
        });
    });

    ['dragleave', 'drop'].forEach(evt => {
        dropZone.addEventListener(evt, function(e) {
            e.preventDefault();
            this.style.borderColor = '#cbd5e1';
            this.style.background = '#f8fafc';
        });
    });

    dropZone.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        handleDroppedFiles(files);
    });

    dropInput.addEventListener('change', function() {
        handleDroppedFiles(this.files);
    });

    function handleDroppedFiles(files) {
        Array.from(files).forEach(file => {
            if (file.name.endsWith('.sql')) {
                const formData = new FormData();
                formData.append('sql_file', file);
                fetch(API + '?action=import_sql', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => showResult(document.getElementById('importResult'), data));
            } else if (file.name.endsWith('.csv')) {
                alert('For CSV imports, please use the "Import CSV to Table" form above so you can select the target table.');
            } else {
                alert('Unsupported file type: ' + file.name + '. Only .sql and .csv files are accepted.');
            }
        });
    }

    // ════════════════════════════════════════════════════════════════════════
    // TAB 4: MAINTENANCE
    // ════════════════════════════════════════════════════════════════════════

    function runMaintenance(action, btnId) {
        const btn = document.getElementById(btnId);
        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Running...';

        const method = (action === 'check_tables') ? 'GET' : 'POST';
        const url = (method === 'GET') ? API + '?action=' + action : API + '?action=' + action;
        const opts = { method: method };

        fetch(url, opts)
            .then(r => r.json())
            .then(data => {
                const resultsEl = document.getElementById('maintenanceResults');
                let output = '<span class="text-success"># ' + action.replace(/_/g, ' ').toUpperCase() + ' - ' + new Date().toLocaleString() + '</span>\n';
                output += '<span class="text-info">' + (data.message || '') + '</span>\n\n';

                if (data.results) {
                    output += '<table style="width:100%;border-collapse:collapse;">';
                    output += '<tr style="border-bottom:1px solid #334155;"><th style="text-align:left;padding:4px 12px 4px 0;color:#94a3b8;">Table</th><th style="text-align:left;padding:4px 0;color:#94a3b8;">Result</th></tr>';
                    for (const [table, result] of Object.entries(data.results)) {
                        const color = (result === 'OK' || result === 'Table is already up to date' || result === 'status' || result === 'done') ? '#22c55e' : '#f59e0b';
                        output += '<tr style="border-bottom:1px solid #1e293b;"><td style="padding:3px 12px 3px 0;">' + table + '</td><td style="color:' + color + ';padding:3px 0;">' + result + '</td></tr>';
                    }
                    output += '</table>';
                }

                resultsEl.innerHTML = output;
                btn.disabled = false;
                btn.innerHTML = origHtml;
            })
            .catch(err => {
                document.getElementById('maintenanceResults').innerHTML = '<span class="text-danger">Error: ' + err.message + '</span>';
                btn.disabled = false;
                btn.innerHTML = origHtml;
            });
    }

    document.getElementById('btnOptimize').addEventListener('click', () => runMaintenance('optimize_tables', 'btnOptimize'));
    document.getElementById('btnCheckTables').addEventListener('click', () => runMaintenance('check_tables', 'btnCheckTables'));
    document.getElementById('btnRepairTables').addEventListener('click', () => runMaintenance('repair_tables', 'btnRepairTables'));
    document.getElementById('btnFlushCaches').addEventListener('click', () => runMaintenance('flush_caches', 'btnFlushCaches'));

    // ════════════════════════════════════════════════════════════════════════
    // TAB 5: BACKUP & RESTORE
    // ════════════════════════════════════════════════════════════════════════

    function loadBackupList() {
        fetch(API + '?action=list_backups')
            .then(r => r.json())
            .then(data => {
                const listEl = document.getElementById('backupList');
                if (!data.success || !data.backups.length) {
                    listEl.innerHTML = '<div class="list-group-item text-center text-muted py-4"><i class="fas fa-archive me-2"></i>No backups found. Create your first backup above.</div>';
                    return;
                }

                let html = '';
                data.backups.forEach(b => {
                    html += '<div class="list-group-item d-flex justify-content-between align-items-center">';
                    html += '<div>';
                    html += '<div class="fw-semibold small"><i class="fas fa-file-code text-primary me-2"></i>' + b.filename + '</div>';
                    html += '<small class="text-muted">' + b.created + ' &middot; ' + b.size + '</small>';
                    html += '</div>';
                    html += '<a href="' + b.path + '" class="btn btn-sm btn-outline-primary" download title="Download"><i class="fas fa-download"></i></a>';
                    html += '</div>';
                });
                listEl.innerHTML = html;
            });
    }

    document.getElementById('btnRefreshBackups').addEventListener('click', loadBackupList);
    document.getElementById('backup-tab').addEventListener('shown.bs.tab', loadBackupList);

    // Create Backup
    document.getElementById('btnCreateBackup').addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Backup...';
        apiCall('export_sql', 'POST', {}, document.getElementById('backupProgress'))
            .then(data => {
                const resultEl = document.getElementById('backupResult');
                resultEl.classList.remove('d-none');
                if (data.success) {
                    resultEl.innerHTML = '<div class="alert alert-success alert-dismissible fade show small"><button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>Backup created: <strong>' + data.filename + '</strong> (' + data.size + ') <a href="' + data.path + '" class="btn btn-sm btn-outline-success ms-2" download><i class="fas fa-download me-1"></i>Download</a></div>';
                    loadBackupList();
                } else {
                    resultEl.innerHTML = '<div class="alert alert-danger small">' + data.error + '</div>';
                }
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-save me-2"></i>Create Backup Now';
            });
    });

    // Restore from backup file
    document.getElementById('restoreForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const file = document.getElementById('restoreFileInput').files[0];
        if (!file) { alert('Please select a SQL backup file.'); return; }
        if (!confirm('Are you sure you want to restore from this backup? This will overwrite existing data.')) return;

        const formData = new FormData();
        formData.append('sql_file', file);

        fetch(API + '?action=import_sql', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                alert(data.success ? 'Database restored successfully!' : 'Restore failed: ' + data.error);
            });
    });

    // Auto-backup settings (stored in localStorage since we don't have a settings table for this)
    document.getElementById('btnSaveAutoBackup').addEventListener('click', function() {
        const settings = {
            enabled: document.getElementById('autoBackupEnabled').checked,
            frequency: document.getElementById('autoBackupFreq').value,
            retention: document.getElementById('autoBackupRetention').value
        };
        localStorage.setItem('rbi_auto_backup_settings', JSON.stringify(settings));
        alert('Auto-backup settings saved. Remember to configure the server-side cron job.');
    });

    // Load saved auto-backup settings
    const savedSettings = JSON.parse(localStorage.getItem('rbi_auto_backup_settings') || '{}');
    if (savedSettings.enabled !== undefined) {
        document.getElementById('autoBackupEnabled').checked = savedSettings.enabled;
        document.getElementById('autoBackupFreq').value = savedSettings.frequency || 'weekly';
        document.getElementById('autoBackupRetention').value = savedSettings.retention || 30;
    }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
