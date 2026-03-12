<?php
/**
 * System Settings - RBI Engineering Suite
 */
$pageTitle = 'System Settings';
require_once __DIR__ . '/../config/app.php';
requireAuth();
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Settings', 'url' => '#']
];
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><i class="bi bi-gear me-2"></i>System Settings</h5>
</div>

<div class="row g-4">
    <div class="col-lg-3">
        <div class="card">
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="settings-tabs">
                    <a href="#risk-matrix" class="list-group-item list-group-item-action active" data-bs-toggle="list"><i class="bi bi-grid-3x3 me-2"></i>Risk Matrix</a>
                    <a href="#inspection-defaults" class="list-group-item list-group-item-action" data-bs-toggle="list"><i class="bi bi-calendar me-2"></i>Inspection Defaults</a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="list"><i class="bi bi-bell me-2"></i>Notifications</a>
                    <a href="#api-keys" class="list-group-item list-group-item-action" data-bs-toggle="list"><i class="bi bi-key me-2"></i>API Keys</a>
                    <a href="#company" class="list-group-item list-group-item-action" data-bs-toggle="list"><i class="bi bi-building me-2"></i>Company Profile</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="tab-content">
            <!-- Risk Matrix Settings -->
            <div class="tab-pane fade show active" id="risk-matrix">
                <div class="card">
                    <div class="card-header">Risk Matrix Configuration</div>
                    <div class="card-body">
                        <form>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Matrix Size</label><select class="form-select"><option>5 x 5 (Standard)</option><option>4 x 4</option><option>6 x 6</option></select></div>
                                <div class="col-md-6"><label class="form-label">Standard</label><select class="form-select"><option>API 580/581</option><option>DNV-RP-G101</option><option>Custom</option></select></div>
                            </div>
                            <h6 class="mt-4 mb-3">POF Category Thresholds (failures/year)</h6>
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label small">Cat 1 (Improbable) max</label><input type="number" class="form-control form-control-sm" value="0.00001" step="0.00001"></div>
                                <div class="col-md-4"><label class="form-label small">Cat 2 (Unlikely) max</label><input type="number" class="form-control form-control-sm" value="0.0001" step="0.0001"></div>
                                <div class="col-md-4"><label class="form-label small">Cat 3 (Possible) max</label><input type="number" class="form-control form-control-sm" value="0.001" step="0.001"></div>
                                <div class="col-md-4"><label class="form-label small">Cat 4 (Likely) max</label><input type="number" class="form-control form-control-sm" value="0.01" step="0.01"></div>
                                <div class="col-md-4"><label class="form-label small">Cat 5 (Very Likely)</label><input type="text" class="form-control form-control-sm" value="> 0.01" disabled></div>
                            </div>
                            <h6 class="mt-4 mb-3">COF Category Thresholds</h6>
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label small">Cat A (Low) max</label><input type="number" class="form-control form-control-sm" value="10000"></div>
                                <div class="col-md-4"><label class="form-label small">Cat B (Med-Low) max</label><input type="number" class="form-control form-control-sm" value="50000"></div>
                                <div class="col-md-4"><label class="form-label small">Cat C (Medium) max</label><input type="number" class="form-control form-control-sm" value="200000"></div>
                                <div class="col-md-4"><label class="form-label small">Cat D (Med-High) max</label><input type="number" class="form-control form-control-sm" value="1000000"></div>
                            </div>
                            <button type="button" class="btn btn-primary mt-4"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Inspection Defaults -->
            <div class="tab-pane fade" id="inspection-defaults">
                <div class="card">
                    <div class="card-header">Default Inspection Intervals</div>
                    <div class="card-body">
                        <form>
                            <table class="table table-sm">
                                <thead><tr><th>Risk Level</th><th>Default Interval (months)</th><th>Max Interval (months)</th></tr></thead>
                                <tbody>
                                    <tr><td>Very High</td><td><input type="number" class="form-control form-control-sm" value="6" style="width:100px"></td><td><input type="number" class="form-control form-control-sm" value="12" style="width:100px"></td></tr>
                                    <tr><td>High</td><td><input type="number" class="form-control form-control-sm" value="12" style="width:100px"></td><td><input type="number" class="form-control form-control-sm" value="24" style="width:100px"></td></tr>
                                    <tr><td>Medium-High</td><td><input type="number" class="form-control form-control-sm" value="24" style="width:100px"></td><td><input type="number" class="form-control form-control-sm" value="48" style="width:100px"></td></tr>
                                    <tr><td>Medium</td><td><input type="number" class="form-control form-control-sm" value="48" style="width:100px"></td><td><input type="number" class="form-control form-control-sm" value="72" style="width:100px"></td></tr>
                                    <tr><td>Low</td><td><input type="number" class="form-control form-control-sm" value="72" style="width:100px"></td><td><input type="number" class="form-control form-control-sm" value="120" style="width:100px"></td></tr>
                                </tbody>
                            </table>
                            <div class="mt-3">
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" checked id="chk-rl-cap"><label class="form-check-label" for="chk-rl-cap">Cap interval at 50% of remaining life</label></div>
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" checked id="chk-reg-cap"><label class="form-check-label" for="chk-reg-cap">Apply regulatory cap (120 months per API 510/570)</label></div>
                            </div>
                            <button type="button" class="btn btn-primary mt-3"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="tab-pane fade" id="notifications">
                <div class="card">
                    <div class="card-header">Notification Settings</div>
                    <div class="card-body">
                        <form>
                            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" checked id="n1"><label class="form-check-label" for="n1">Email notifications for overdue inspections</label></div>
                            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" checked id="n2"><label class="form-check-label" for="n2">Alert on risk level changes</label></div>
                            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" checked id="n3"><label class="form-check-label" for="n3">Corrosion rate acceleration warnings</label></div>
                            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="n4"><label class="form-check-label" for="n4">Daily summary digest</label></div>
                            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" checked id="n5"><label class="form-check-label" for="n5">IoT sensor threshold alerts</label></div>
                            <div class="mb-3"><label class="form-label">Notification email recipients</label><input type="text" class="form-control" value="engineering@company.com, integrity@company.com"></div>
                            <button type="button" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- API Keys -->
            <div class="tab-pane fade" id="api-keys">
                <div class="card">
                    <div class="card-header">API Keys & Integration Settings</div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label">RBI Suite API Key</label>
                            <div class="input-group"><input type="password" class="form-control" value="rbi_api_key_xxxxxxxxxxxxxxxx" id="api-key-field"><button class="btn btn-outline-secondary" onclick="this.previousElementSibling.type=this.previousElementSibling.type==='password'?'text':'password'"><i class="bi bi-eye"></i></button></div>
                            <small class="text-muted">Use this key for external system integration</small>
                        </div>
                        <button type="button" class="btn btn-outline-warning"><i class="bi bi-arrow-repeat me-1"></i>Regenerate API Key</button>
                    </div>
                </div>
            </div>

            <!-- Company Profile -->
            <div class="tab-pane fade" id="company">
                <div class="card">
                    <div class="card-header">Company Profile</div>
                    <div class="card-body">
                        <form>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Company Name</label><input type="text" class="form-control" value="Acme Petrochemical Corp."></div>
                                <div class="col-md-6"><label class="form-label">Industry</label><select class="form-select"><option selected>Oil & Gas (Downstream)</option><option>Oil & Gas (Upstream)</option><option>Petrochemical</option><option>Power Generation</option><option>Mining</option></select></div>
                                <div class="col-md-8"><label class="form-label">Address</label><input type="text" class="form-control" value="123 Industrial Blvd, Houston, TX 77002"></div>
                                <div class="col-md-4"><label class="form-label">Timezone</label><select class="form-select"><option selected>UTC</option><option>US/Central</option><option>US/Eastern</option></select></div>
                                <div class="col-12"><label class="form-label">Logo</label><input type="file" class="form-control" accept="image/*"></div>
                            </div>
                            <button type="button" class="btn btn-primary mt-3"><i class="bi bi-check-lg me-1"></i>Save Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
