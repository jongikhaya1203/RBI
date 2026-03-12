<?php
/**
 * Mobile Thickness Reading Entry - RBI Engineering Suite
 * Quick entry form optimized for field use with grid-based CML entry,
 * previous readings comparison, auto corrosion rate, offline queue
 */
require_once dirname(__DIR__) . '/config/app.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

$currentUser = $auth->getCurrentUser();
$pageTitle = 'Thickness Readings';

// Fetch assets for dropdown
try {
    $db = Database::getInstance()->getConnection();
    $assetsStmt = $db->query("SELECT id, tag_number, description FROM assets ORDER BY tag_number");
    $assetsList = $assetsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $assetsList = [];
}

$selectedAssetId = $_GET['asset_id'] ?? null;
$previousReadings = [];

if ($selectedAssetId) {
    try {
        $stmt = $db->prepare("
            SELECT * FROM thickness_readings
            WHERE asset_id = ?
            ORDER BY reading_date DESC
            LIMIT 20
        ");
        $stmt->execute([$selectedAssetId]);
        $previousReadings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $previousReadings = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/rbi/manifest.json">
    <title>Thickness Readings | RBI Suite</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/rbi/static/css/mobile.css" rel="stylesheet">
    <link href="/rbi/static/css/pwa.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f1f5f9;
            margin: 0;
        }

        .reading-header {
            background: white;
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .reading-header .d-flex {
            align-items: center;
        }

        .reading-header h1 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            color: #1e293b;
        }

        .reading-header .back-btn {
            background: none;
            border: none;
            color: #3b82f6;
            font-size: 1.1rem;
            padding: 8px;
            margin: -8px;
            margin-right: 8px;
        }

        .reading-body {
            padding: 16px 16px 120px;
        }

        /* CML Grid */
        .cml-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 8px;
            margin-top: 12px;
        }

        .cml-cell {
            position: relative;
        }

        .cml-cell label {
            display: block;
            font-size: 0.65rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 2px;
            text-align: center;
        }

        .cml-cell input {
            width: 100%;
            height: 48px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            text-align: center;
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            background: white;
            outline: none;
            transition: border-color 0.15s;
            -webkit-appearance: none;
            padding: 0;
        }

        .cml-cell input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        .cml-cell input.decreased {
            border-color: #f59e0b;
            background: #fffbeb;
        }

        .cml-cell input.critical {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .cml-cell .prev-value {
            font-size: 0.6rem;
            color: #94a3b8;
            text-align: center;
            margin-top: 2px;
        }

        .cml-cell .prev-value.loss {
            color: #f59e0b;
        }

        /* Quick Info Cards */
        .info-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .info-card .label {
            font-size: 0.65rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-card .value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
        }

        .info-card .unit {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 400;
        }

        /* Reading History */
        .reading-history {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .reading-history-header {
            padding: 12px 16px;
            font-weight: 700;
            font-size: 0.85rem;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .reading-history-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.85rem;
        }

        .reading-history-item:last-child { border-bottom: none; }

        .reading-history-item .date { color: #64748b; }
        .reading-history-item .value { font-weight: 600; color: #1e293b; }
        .reading-history-item .rate { font-size: 0.75rem; }

        /* Sync Queue */
        .sync-queue {
            background: #eff6ff;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sync-queue i {
            color: #3b82f6;
        }

        .sync-queue .info {
            flex: 1;
            font-size: 0.8rem;
            color: #475569;
        }

        .sync-queue .count {
            font-weight: 700;
            color: #3b82f6;
        }

        /* Bottom Actions */
        .bottom-actions {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 12px 16px;
            padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px));
            display: flex;
            gap: 12px;
            z-index: 100;
        }

        .bottom-actions .btn {
            flex: 1;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        /* Add CML row */
        .add-cml-btn {
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            width: 100%;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            background: transparent;
            font-size: 0.8rem;
            cursor: pointer;
        }

        .section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1e293b;
            margin: 20px 0 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="reading-header">
    <div class="d-flex">
        <button class="back-btn" onclick="window.location.href='/rbi/mobile/dashboard.php'">
            <i class="fas fa-arrow-left"></i>
        </button>
        <h1>Thickness Readings</h1>
        <div class="ms-auto">
            <div class="sync-indicator">
                <i class="fas fa-cloud"></i>
                <span>Ready</span>
            </div>
        </div>
    </div>
</div>

<!-- Body -->
<div class="reading-body">
    <form id="readingForm" data-autosave="readings">

        <!-- Asset Selection -->
        <div class="field-group" style="margin-bottom:16px;">
            <label style="font-size:0.8rem;font-weight:600;color:#475569;margin-bottom:6px;display:block;">
                Select Asset
            </label>
            <select class="form-select" name="asset_id" id="assetSelect"
                    style="font-size:16px;padding:12px 14px;border-radius:12px;" required
                    onchange="loadAssetData(this.value)">
                <option value="">Choose asset...</option>
                <?php foreach ($assetsList as $asset): ?>
                    <option value="<?= $asset['id'] ?>" <?= $selectedAssetId == $asset['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($asset['tag_number']) ?> - <?= htmlspecialchars(substr($asset['description'], 0, 30)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Quick Info -->
        <div class="info-cards" id="infoCards">
            <div class="info-card">
                <div class="label">Nominal Thickness</div>
                <div class="value" id="nominalThk">-- <span class="unit">mm</span></div>
            </div>
            <div class="info-card">
                <div class="label">Min. Allowable</div>
                <div class="value" id="minThk">-- <span class="unit">mm</span></div>
            </div>
            <div class="info-card">
                <div class="label">Last Reading</div>
                <div class="value" id="lastReading">-- <span class="unit">mm</span></div>
            </div>
            <div class="info-card">
                <div class="label">Corrosion Rate</div>
                <div class="value" id="corrRate">-- <span class="unit">mm/yr</span></div>
            </div>
        </div>

        <!-- Reading Date -->
        <div class="field-group" style="margin-bottom:16px;">
            <label style="font-size:0.8rem;font-weight:600;color:#475569;margin-bottom:6px;display:block;">
                Reading Date
            </label>
            <input type="date" class="form-control" name="reading_date"
                   value="<?= date('Y-m-d') ?>"
                   style="font-size:16px;padding:12px 14px;border-radius:12px;" required>
        </div>

        <!-- CML Grid Entry -->
        <div class="section-title">
            <i class="fas fa-th text-primary"></i>
            CML Readings
        </div>

        <div class="mb-2" style="font-size:0.75rem;color:#94a3b8;">
            Enter thickness values (mm). Previous readings shown below each cell.
        </div>

        <div class="cml-grid" id="cmlGrid">
            <!-- Default CML points -->
            <div class="cml-cell">
                <label>CML-1</label>
                <input type="number" name="cml_1" inputmode="decimal" step="0.01"
                       placeholder="--" data-cml="1" onfocus="this.select()">
                <div class="prev-value" data-prev="1">--</div>
            </div>
            <div class="cml-cell">
                <label>CML-2</label>
                <input type="number" name="cml_2" inputmode="decimal" step="0.01"
                       placeholder="--" data-cml="2" onfocus="this.select()">
                <div class="prev-value" data-prev="2">--</div>
            </div>
            <div class="cml-cell">
                <label>CML-3</label>
                <input type="number" name="cml_3" inputmode="decimal" step="0.01"
                       placeholder="--" data-cml="3" onfocus="this.select()">
                <div class="prev-value" data-prev="3">--</div>
            </div>
            <div class="cml-cell">
                <label>CML-4</label>
                <input type="number" name="cml_4" inputmode="decimal" step="0.01"
                       placeholder="--" data-cml="4" onfocus="this.select()">
                <div class="prev-value" data-prev="4">--</div>
            </div>
            <div class="cml-cell">
                <label>CML-5</label>
                <input type="number" name="cml_5" inputmode="decimal" step="0.01"
                       placeholder="--" data-cml="5" onfocus="this.select()">
                <div class="prev-value" data-prev="5">--</div>
            </div>
            <div class="cml-cell">
                <label>CML-6</label>
                <input type="number" name="cml_6" inputmode="decimal" step="0.01"
                       placeholder="--" data-cml="6" onfocus="this.select()">
                <div class="prev-value" data-prev="6">--</div>
            </div>
            <div class="cml-cell">
                <label>CML-7</label>
                <input type="number" name="cml_7" inputmode="decimal" step="0.01"
                       placeholder="--" data-cml="7" onfocus="this.select()">
                <div class="prev-value" data-prev="7">--</div>
            </div>
            <div class="cml-cell">
                <label>CML-8</label>
                <input type="number" name="cml_8" inputmode="decimal" step="0.01"
                       placeholder="--" data-cml="8" onfocus="this.select()">
                <div class="prev-value" data-prev="8">--</div>
            </div>
        </div>

        <button type="button" class="add-cml-btn mt-2" id="addCmlBtn" onclick="addCMLPoint()">
            <i class="fas fa-plus me-1"></i>Add CML Point
        </button>

        <!-- Calculated Results -->
        <div class="section-title mt-4">
            <i class="fas fa-calculator text-success"></i>
            Calculated Results
        </div>

        <div class="info-cards" id="calcResults">
            <div class="info-card">
                <div class="label">Min. Reading</div>
                <div class="value" id="calcMin">-- <span class="unit">mm</span></div>
            </div>
            <div class="info-card">
                <div class="label">Avg. Reading</div>
                <div class="value" id="calcAvg">-- <span class="unit">mm</span></div>
            </div>
            <div class="info-card">
                <div class="label">Est. Corr. Rate</div>
                <div class="value" id="calcCorrRate">-- <span class="unit">mm/yr</span></div>
            </div>
            <div class="info-card">
                <div class="label">Est. Remaining Life</div>
                <div class="value" id="calcLife">-- <span class="unit">yrs</span></div>
            </div>
        </div>

        <!-- Notes -->
        <div class="field-group" style="margin-top:16px;">
            <label style="font-size:0.8rem;font-weight:600;color:#475569;margin-bottom:6px;display:block;">
                Notes
            </label>
            <textarea class="form-control" name="notes" rows="2" placeholder="Any observations..."
                      style="font-size:16px;padding:12px 14px;border-radius:12px;"></textarea>
        </div>

        <!-- Offline Queue -->
        <div class="sync-queue" id="syncQueue" style="display:none;">
            <i class="fas fa-clock"></i>
            <div class="info">
                <span class="count" id="queueCount">0</span> readings pending sync
            </div>
            <button type="button" class="btn btn-sm btn-primary" onclick="forceSyncReadings()">
                Sync Now
            </button>
        </div>

    </form>

    <!-- Previous Readings History -->
    <?php if (!empty($previousReadings)): ?>
        <div class="section-title mt-4">
            <i class="fas fa-history text-info"></i>
            Reading History
        </div>

        <div class="reading-history">
            <div class="reading-history-header">
                <span>Date</span>
                <span>Min. / Avg.</span>
            </div>
            <?php foreach (array_slice($previousReadings, 0, 8) as $reading): ?>
                <div class="reading-history-item">
                    <span class="date"><?= date('M j, Y', strtotime($reading['reading_date'] ?? '')) ?></span>
                    <span>
                        <span class="value"><?= number_format($reading['min_reading'] ?? 0, 2) ?></span>
                        <span class="text-muted">/</span>
                        <span class="value"><?= number_format($reading['avg_reading'] ?? 0, 2) ?></span>
                        <span class="unit text-muted">mm</span>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Bottom Actions -->
<div class="bottom-actions">
    <button type="button" class="btn btn-outline-secondary" onclick="clearForm()"
            style="background:#f1f5f9;border:none;color:#475569;">
        Clear
    </button>
    <button type="button" class="btn btn-primary" id="saveBtn" onclick="saveReadings()"
            style="background:#3b82f6;border:none;">
        <i class="fas fa-save me-1"></i>Save Readings
    </button>
</div>

<!-- Offline Bar -->
<div class="offline-bar" id="offlineBar">
    <i class="fas fa-wifi-slash"></i>
    <span>Offline mode</span>
    <span class="sync-count"></span>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/rbi/static/js/app.js"></script>
<script src="/rbi/static/js/mobile.js"></script>
<script src="/rbi/static/js/pwa.js"></script>

<script>
'use strict';

let cmlCount = 8;
let previousData = {};

document.addEventListener('DOMContentLoaded', function() {
    initCMLInputHandlers();
    checkSyncQueue();
});

// ── Load Asset Data ────────────────────────────────────────
async function loadAssetData(assetId) {
    if (!assetId) return;

    try {
        const response = await RBI_APP.request(`/rbi/api/assets.php?id=${assetId}&include=readings`);
        if (response.ok) {
            const data = await response.json();

            // Update info cards
            if (data.asset) {
                document.getElementById('nominalThk').innerHTML =
                    `${parseFloat(data.asset.nominal_thickness || 0).toFixed(2)} <span class="unit">mm</span>`;
                document.getElementById('minThk').innerHTML =
                    `${parseFloat(data.asset.min_thickness || 0).toFixed(2)} <span class="unit">mm</span>`;
            }

            if (data.lastReading) {
                document.getElementById('lastReading').innerHTML =
                    `${parseFloat(data.lastReading.min_reading || 0).toFixed(2)} <span class="unit">mm</span>`;
            }

            if (data.corrosionRate !== undefined) {
                document.getElementById('corrRate').innerHTML =
                    `${parseFloat(data.corrosionRate || 0).toFixed(3)} <span class="unit">mm/yr</span>`;
            }

            // Update previous readings in CML cells
            if (data.previousCML) {
                previousData = data.previousCML;
                Object.entries(data.previousCML).forEach(([key, val]) => {
                    const prevEl = document.querySelector(`[data-prev="${key}"]`);
                    if (prevEl) {
                        prevEl.textContent = `prev: ${parseFloat(val).toFixed(2)}`;
                    }
                });
            }
        }
    } catch (err) {
        console.warn('Could not load asset data:', err);
    }
}

// ── CML Input Handlers ────────────────────────────────────
function initCMLInputHandlers() {
    document.querySelectorAll('.cml-cell input').forEach(input => {
        input.addEventListener('input', onCMLInput);
        input.addEventListener('keydown', onCMLKeydown);
    });
}

function onCMLInput(e) {
    const input = e.target;
    const cmlNum = input.dataset.cml;
    const value = parseFloat(input.value);

    // Compare with previous
    if (!isNaN(value) && previousData[cmlNum]) {
        const prev = parseFloat(previousData[cmlNum]);
        if (value < prev * 0.9) {
            input.classList.add('critical');
            input.classList.remove('decreased');
        } else if (value < prev) {
            input.classList.add('decreased');
            input.classList.remove('critical');
        } else {
            input.classList.remove('decreased', 'critical');
        }

        // Show loss
        const prevEl = document.querySelector(`[data-prev="${cmlNum}"]`);
        if (prevEl) {
            const loss = prev - value;
            if (loss > 0) {
                prevEl.textContent = `${prev.toFixed(2)} (-${loss.toFixed(2)})`;
                prevEl.classList.add('loss');
            } else {
                prevEl.textContent = `prev: ${prev.toFixed(2)}`;
                prevEl.classList.remove('loss');
            }
        }
    }

    updateCalculations();
    RBI_MOBILE.triggerHaptic('light');
}

function onCMLKeydown(e) {
    // Tab / Enter moves to next CML
    if (e.key === 'Tab' || e.key === 'Enter') {
        e.preventDefault();
        const inputs = Array.from(document.querySelectorAll('.cml-cell input'));
        const idx = inputs.indexOf(e.target);
        if (idx < inputs.length - 1) {
            inputs[idx + 1].focus();
        }
    }
}

// ── Add CML Point ──────────────────────────────────────────
function addCMLPoint() {
    cmlCount++;
    const grid = document.getElementById('cmlGrid');

    const cell = document.createElement('div');
    cell.className = 'cml-cell';
    cell.innerHTML = `
        <label>CML-${cmlCount}</label>
        <input type="number" name="cml_${cmlCount}" inputmode="decimal" step="0.01"
               placeholder="--" data-cml="${cmlCount}" onfocus="this.select()">
        <div class="prev-value" data-prev="${cmlCount}">--</div>
    `;

    grid.appendChild(cell);

    const input = cell.querySelector('input');
    input.addEventListener('input', onCMLInput);
    input.addEventListener('keydown', onCMLKeydown);
    input.focus();

    RBI_MOBILE.triggerHaptic('light');
}

// ── Update Calculations ────────────────────────────────────
function updateCalculations() {
    const inputs = document.querySelectorAll('.cml-cell input');
    const values = [];

    inputs.forEach(input => {
        const val = parseFloat(input.value);
        if (!isNaN(val) && val > 0) values.push(val);
    });

    if (values.length === 0) return;

    const min = Math.min(...values);
    const avg = values.reduce((a, b) => a + b, 0) / values.length;

    document.getElementById('calcMin').innerHTML = `${min.toFixed(2)} <span class="unit">mm</span>`;
    document.getElementById('calcAvg').innerHTML = `${avg.toFixed(2)} <span class="unit">mm</span>`;

    // Estimate corrosion rate (if we have previous data)
    const lastReadingEl = document.getElementById('lastReading');
    const lastVal = parseFloat(lastReadingEl?.textContent);

    if (!isNaN(lastVal) && lastVal > 0) {
        // Assume 1 year interval for estimate
        const rate = Math.max(0, lastVal - min);
        document.getElementById('calcCorrRate').innerHTML =
            `${rate.toFixed(3)} <span class="unit">mm/yr</span>`;

        // Remaining life
        const minAllowable = parseFloat(document.getElementById('minThk')?.textContent) || 0;
        if (rate > 0 && min > minAllowable) {
            const life = (min - minAllowable) / rate;
            document.getElementById('calcLife').innerHTML =
                `${life.toFixed(1)} <span class="unit">yrs</span>`;
        }
    }
}

// ── Save Readings ──────────────────────────────────────────
async function saveReadings() {
    const form = document.getElementById('readingForm');
    const formData = new FormData(form);
    const data = {};
    formData.forEach((val, key) => { data[key] = val; });

    if (!data.asset_id) {
        RBI_APP.showToast('Please select an asset', 'warning');
        RBI_MOBILE.triggerHaptic('error');
        return;
    }

    // Collect CML values
    const readings = {};
    document.querySelectorAll('.cml-cell input').forEach(input => {
        if (input.value) {
            readings[input.name] = parseFloat(input.value);
        }
    });

    if (Object.keys(readings).length === 0) {
        RBI_APP.showToast('Please enter at least one reading', 'warning');
        RBI_MOBILE.triggerHaptic('error');
        return;
    }

    data.readings = readings;

    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

    try {
        if (navigator.onLine) {
            const response = await RBI_APP.request('/rbi/api/readings.php', {
                method: 'POST',
                body: data
            });

            if (response.ok || response.offline) {
                RBI_MOBILE.triggerHaptic('success');
                RBI_APP.showToast('Readings saved successfully!', 'success');
                clearForm();
            } else {
                throw new Error('Save failed');
            }
        } else {
            await RBI_PWA.registerSync('sync-readings', {
                url: '/rbi/api/readings.php',
                data: data
            });

            RBI_MOBILE.triggerHaptic('success');
            RBI_APP.showToast('Saved offline - will sync when connected', 'info');
            clearForm();
            checkSyncQueue();
        }
    } catch (err) {
        RBI_APP.showToast('Save failed. Please try again.', 'error');
        RBI_MOBILE.triggerHaptic('error');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save Readings';
    }
}

// ── Clear Form ─────────────────────────────────────────────
function clearForm() {
    document.querySelectorAll('.cml-cell input').forEach(input => {
        input.value = '';
        input.classList.remove('decreased', 'critical');
    });

    document.getElementById('calcMin').innerHTML = '-- <span class="unit">mm</span>';
    document.getElementById('calcAvg').innerHTML = '-- <span class="unit">mm</span>';
    document.getElementById('calcCorrRate').innerHTML = '-- <span class="unit">mm/yr</span>';
    document.getElementById('calcLife').innerHTML = '-- <span class="unit">yrs</span>';

    document.querySelector('textarea[name="notes"]').value = '';
}

// ── Sync Queue ─────────────────────────────────────────────
async function checkSyncQueue() {
    if (typeof RBI_PWA === 'undefined') return;

    const count = await RBI_PWA.getPendingSyncCount();
    const queueEl = document.getElementById('syncQueue');
    const countEl = document.getElementById('queueCount');

    if (count > 0) {
        queueEl.style.display = 'flex';
        countEl.textContent = count;
    } else {
        queueEl.style.display = 'none';
    }
}

function forceSyncReadings() {
    if (navigator.onLine && navigator.serviceWorker.controller) {
        navigator.serviceWorker.ready.then(reg => {
            reg.sync.register('sync-readings');
        });
        RBI_APP.showToast('Sync initiated...', 'info');
    } else {
        RBI_APP.showToast('No connection available', 'warning');
    }
}
</script>

</body>
</html>
