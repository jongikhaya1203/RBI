<?php
/**
 * Mobile Inspection Data Entry Form - RBI Engineering Suite
 * Camera, GPS, voice notes, barcode scanner, offline-capable,
 * step-by-step wizard, drawing tool, digital signature
 */
require_once dirname(__DIR__) . '/config/app.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

$currentUser = $auth->getCurrentUser();
$pageTitle = 'New Inspection';

// Fetch assets for dropdown
try {
    $db = Database::getInstance()->getConnection();
    $assetsStmt = $db->query("SELECT id, tag_number, description, asset_type FROM assets ORDER BY tag_number");
    $assetsList = $assetsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $assetsList = [];
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
    <title>New Inspection | RBI Suite</title>

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

        .wizard-header {
            background: white;
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .wizard-header .back-btn {
            background: none;
            border: none;
            font-size: 1.1rem;
            color: #3b82f6;
            padding: 8px;
            margin: -8px;
        }

        .wizard-header h1 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            color: #1e293b;
        }

        .wizard-progress {
            display: flex;
            gap: 4px;
            margin-top: 12px;
        }

        .wizard-progress .step {
            flex: 1;
            height: 4px;
            border-radius: 2px;
            background: #e2e8f0;
            transition: background 0.3s;
        }

        .wizard-progress .step.active { background: #3b82f6; }
        .wizard-progress .step.done { background: #22c55e; }

        .wizard-step-label {
            display: flex;
            justify-content: space-between;
            margin-top: 6px;
            font-size: 0.65rem;
            color: #94a3b8;
        }

        .wizard-body {
            padding: 20px 16px 120px;
        }

        .wizard-section {
            display: none;
        }

        .wizard-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .wizard-section h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
        }

        .wizard-section p.section-desc {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 20px;
        }

        /* Form Fields */
        .field-group {
            margin-bottom: 20px;
        }

        .field-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
            display: block;
        }

        .field-group .form-control,
        .field-group .form-select {
            font-size: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            transition: border-color 0.15s;
        }

        .field-group .form-control:focus,
        .field-group .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        /* Photo Capture */
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .photo-item {
            aspect-ratio: 1;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-item .remove-photo {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(0,0,0,0.6);
            color: white;
            border: none;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo-add {
            aspect-ratio: 1;
            border-radius: 12px;
            border: 2px dashed #cbd5e1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            cursor: pointer;
            transition: border-color 0.15s;
            background: white;
        }

        .photo-add:active { border-color: #3b82f6; }
        .photo-add i { font-size: 1.3rem; margin-bottom: 4px; }
        .photo-add span { font-size: 0.7rem; }

        /* Voice Recording */
        .voice-recorder {
            background: white;
            border-radius: 12px;
            padding: 16px;
            border: 1.5px solid #e2e8f0;
            text-align: center;
        }

        .voice-recorder .rec-btn {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: none;
            background: #ef4444;
            color: white;
            font-size: 1.5rem;
            margin: 12px 0;
            transition: transform 0.15s;
        }

        .voice-recorder .rec-btn:active { transform: scale(0.9); }
        .voice-recorder .rec-btn.recording { animation: pulse-rec 1s ease-in-out infinite; }

        @keyframes pulse-rec {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
            50% { box-shadow: 0 0 0 12px rgba(239,68,68,0); }
        }

        .voice-recorder .rec-time {
            font-size: 1.2rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            color: #1e293b;
        }

        /* Drawing Canvas */
        .drawing-container {
            position: relative;
            background: white;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            overflow: hidden;
        }

        .drawing-container canvas {
            width: 100%;
            touch-action: none;
        }

        .drawing-toolbar {
            display: flex;
            gap: 8px;
            padding: 8px 12px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .drawing-toolbar button {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            background: white;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .drawing-toolbar button.active {
            background: #3b82f6;
            color: white;
        }

        /* Signature Pad */
        .signature-pad {
            background: white;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            position: relative;
        }

        .signature-pad canvas {
            width: 100%;
            height: 150px;
            touch-action: none;
        }

        .signature-pad .sig-label {
            position: absolute;
            bottom: 40px;
            left: 20px;
            right: 20px;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            font-size: 0.7rem;
            color: #94a3b8;
            pointer-events: none;
        }

        .signature-pad .sig-clear {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #f1f5f9;
            border: none;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 0.75rem;
            color: #64748b;
        }

        /* Wizard Footer */
        .wizard-footer {
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

        .wizard-footer .btn {
            flex: 1;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .btn-primary-custom {
            background: #3b82f6;
            color: white;
            border: none;
        }

        .btn-secondary-custom {
            background: #f1f5f9;
            color: #475569;
            border: none;
        }

        /* GPS indicator */
        .gps-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            padding: 8px 14px;
            border-radius: 20px;
            background: #f1f5f9;
            color: #64748b;
        }

        .gps-status.acquired {
            background: #f0fdf4;
            color: #22c55e;
        }

        .gps-status .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        /* Measurement input */
        .measurement-input {
            display: flex;
            gap: 8px;
        }

        .measurement-input .form-control {
            flex: 1;
        }

        .measurement-input .unit-select {
            width: 80px;
            flex-shrink: 0;
        }

        .sync-indicator {
            margin-top: 8px;
        }
    </style>
</head>
<body>

<!-- Wizard Header -->
<div class="wizard-header">
    <div class="d-flex align-items-center justify-content-between">
        <button class="back-btn" onclick="wizardBack()">
            <i class="fas fa-arrow-left"></i>
        </button>
        <h1>New Inspection</h1>
        <div class="sync-indicator">
            <i class="fas fa-cloud"></i>
            <span>Ready</span>
        </div>
    </div>
    <div class="wizard-progress">
        <div class="step active" data-step="1"></div>
        <div class="step" data-step="2"></div>
        <div class="step" data-step="3"></div>
        <div class="step" data-step="4"></div>
        <div class="step" data-step="5"></div>
    </div>
    <div class="wizard-step-label">
        <span>Asset</span>
        <span>Details</span>
        <span>Photos</span>
        <span>Findings</span>
        <span>Sign</span>
    </div>
</div>

<!-- Wizard Body -->
<div class="wizard-body">
    <form id="inspectionForm" data-autosave="inspection" method="POST" action="/rbi/api/inspections.php">

        <!-- Step 1: Asset Identification -->
        <div class="wizard-section active" data-step="1">
            <h2>Identify Asset</h2>
            <p class="section-desc">Select or scan the asset to inspect.</p>

            <div class="field-group">
                <label>Asset Tag Number</label>
                <select class="form-select" name="asset_id" id="assetSelect" required>
                    <option value="">Select an asset...</option>
                    <?php foreach ($assetsList as $asset): ?>
                        <option value="<?= $asset['id'] ?>" data-type="<?= htmlspecialchars($asset['asset_type']) ?>">
                            <?= htmlspecialchars($asset['tag_number']) ?> - <?= htmlspecialchars(substr($asset['description'], 0, 40)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="text-center my-3">
                <span class="text-muted" style="font-size:0.8rem;">or</span>
            </div>

            <!-- Barcode/QR Scanner -->
            <div class="field-group">
                <button type="button" class="btn btn-outline-primary w-100 py-3" id="scanBtn" style="border-radius:12px;">
                    <i class="fas fa-qrcode me-2"></i>Scan Barcode / QR Code
                </button>
                <div id="scannerContainer" style="display:none; margin-top:12px;">
                    <video id="scannerVideo" style="width:100%;border-radius:12px;background:#000;" autoplay playsinline></video>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="stopScanner()">Stop Scanner</button>
                </div>
            </div>

            <!-- GPS Location -->
            <div class="field-group">
                <label>GPS Location</label>
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-outline-secondary" id="gpsBtn" style="border-radius:12px;">
                        <i class="fas fa-location-crosshairs me-1"></i>Capture GPS
                    </button>
                    <span class="gps-status" id="gpsStatus">
                        <span class="dot"></span>
                        <span>Not captured</span>
                    </span>
                </div>
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="gps_accuracy" id="gpsAccuracy">
            </div>

            <div class="field-group">
                <label>Inspection Type</label>
                <select class="form-select" name="inspection_type" required>
                    <option value="">Select type...</option>
                    <option value="visual">Visual Inspection</option>
                    <option value="ut">Ultrasonic Thickness</option>
                    <option value="mt">Magnetic Particle</option>
                    <option value="pt">Liquid Penetrant</option>
                    <option value="rt">Radiographic</option>
                    <option value="api_510">API 510</option>
                    <option value="api_570">API 570</option>
                    <option value="api_653">API 653</option>
                    <option value="external">External Inspection</option>
                    <option value="internal">Internal Inspection</option>
                </select>
            </div>
        </div>

        <!-- Step 2: Inspection Details -->
        <div class="wizard-section" data-step="2">
            <h2>Inspection Details</h2>
            <p class="section-desc">Record the inspection parameters.</p>

            <div class="field-group">
                <label>Inspection Date</label>
                <input type="date" class="form-control" name="inspection_date"
                       value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="field-group">
                <label>Inspector</label>
                <input type="text" class="form-control" name="inspector"
                       value="<?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>"
                       readonly>
            </div>

            <div class="field-group">
                <label>Equipment Condition</label>
                <select class="form-select" name="condition">
                    <option value="">Select condition...</option>
                    <option value="good">Good - No significant damage</option>
                    <option value="fair">Fair - Minor damage/corrosion</option>
                    <option value="poor">Poor - Significant damage</option>
                    <option value="critical">Critical - Immediate attention</option>
                </select>
            </div>

            <div class="field-group">
                <label>Measured Thickness</label>
                <div class="measurement-input">
                    <input type="number" class="form-control" name="measured_thickness"
                           step="0.01" min="0" placeholder="0.00" inputmode="decimal">
                    <select class="form-select unit-select" name="thickness_unit">
                        <option value="mm">mm</option>
                        <option value="in">inch</option>
                    </select>
                </div>
            </div>

            <div class="field-group">
                <label>Operating Temperature</label>
                <div class="measurement-input">
                    <input type="number" class="form-control" name="operating_temp"
                           step="1" placeholder="0" inputmode="numeric">
                    <select class="form-select unit-select" name="temp_unit">
                        <option value="C">&deg;C</option>
                        <option value="F">&deg;F</option>
                    </select>
                </div>
            </div>

            <div class="field-group">
                <label>Operating Pressure</label>
                <div class="measurement-input">
                    <input type="number" class="form-control" name="operating_pressure"
                           step="0.1" placeholder="0.0" inputmode="decimal">
                    <select class="form-select unit-select" name="pressure_unit">
                        <option value="psi">psi</option>
                        <option value="bar">bar</option>
                        <option value="kPa">kPa</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Step 3: Photos & Media -->
        <div class="wizard-section" data-step="3">
            <h2>Photos & Evidence</h2>
            <p class="section-desc">Capture photos and voice notes.</p>

            <div class="field-group">
                <label>Inspection Photos</label>
                <div class="photo-grid" id="photoGrid">
                    <label class="photo-add" for="photoInput">
                        <i class="fas fa-camera"></i>
                        <span>Take Photo</span>
                    </label>
                </div>
                <input type="file" id="photoInput" accept="image/*" capture="environment"
                       multiple style="display:none;" name="photos[]">
            </div>

            <div class="field-group">
                <label>Voice Notes</label>
                <div class="voice-recorder" id="voiceRecorder">
                    <div class="rec-time" id="recTime">00:00</div>
                    <button type="button" class="rec-btn" id="recBtn">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <div style="font-size:0.8rem;color:#94a3b8;">Tap to start recording</div>
                    <div id="voiceNotesList" class="mt-3"></div>
                </div>
            </div>

            <div class="field-group">
                <label>Defect Location Markup</label>
                <div class="drawing-container">
                    <canvas id="drawingCanvas" width="350" height="300"></canvas>
                    <div class="drawing-toolbar">
                        <button type="button" class="active" data-tool="pen"><i class="fas fa-pen"></i></button>
                        <button type="button" data-tool="circle"><i class="fas fa-circle"></i></button>
                        <button type="button" data-tool="arrow"><i class="fas fa-arrow-right"></i></button>
                        <button type="button" data-tool="text"><i class="fas fa-font"></i></button>
                        <button type="button" data-tool="eraser"><i class="fas fa-eraser"></i></button>
                        <button type="button" data-tool="clear" style="margin-left:auto;color:#ef4444;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <input type="hidden" name="markup_data" id="markupData">
            </div>
        </div>

        <!-- Step 4: Findings -->
        <div class="wizard-section" data-step="4">
            <h2>Findings & Recommendations</h2>
            <p class="section-desc">Document your inspection findings.</p>

            <div class="field-group">
                <label>Damage Mechanisms Observed</label>
                <div class="d-flex flex-wrap gap-2">
                    <?php
                    $mechanisms = ['General Corrosion', 'Pitting', 'Erosion', 'CUI', 'Cracking',
                                   'Fatigue', 'Creep', 'Embrittlement', 'Fouling', 'None Observed'];
                    foreach ($mechanisms as $mech):
                    ?>
                        <label class="btn btn-outline-secondary" style="border-radius:20px;font-size:0.8rem;">
                            <input type="checkbox" name="damage_mechanisms[]"
                                   value="<?= htmlspecialchars($mech) ?>" class="d-none">
                            <?= $mech ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="field-group">
                <label>Findings Description</label>
                <textarea class="form-control" name="findings" rows="4"
                          placeholder="Describe what was found during inspection..."></textarea>
            </div>

            <div class="field-group">
                <label>Recommendations</label>
                <textarea class="form-control" name="recommendations" rows="3"
                          placeholder="Recommended actions or follow-up..."></textarea>
            </div>

            <div class="field-group">
                <label>Priority</label>
                <select class="form-select" name="priority">
                    <option value="low">Low - Schedule for next turnaround</option>
                    <option value="medium">Medium - Plan within 6 months</option>
                    <option value="high">High - Act within 30 days</option>
                    <option value="critical">Critical - Immediate action required</option>
                </select>
            </div>

            <div class="field-group">
                <label>Next Inspection Due</label>
                <input type="date" class="form-control" name="next_inspection_date">
            </div>
        </div>

        <!-- Step 5: Review & Sign -->
        <div class="wizard-section" data-step="5">
            <h2>Review & Submit</h2>
            <p class="section-desc">Review your inspection data and sign off.</p>

            <div id="reviewSummary" class="mb-4">
                <!-- Populated by JS -->
            </div>

            <div class="field-group">
                <label>Additional Comments</label>
                <textarea class="form-control" name="comments" rows="2" placeholder="Any final comments..."></textarea>
            </div>

            <div class="field-group">
                <label>Digital Signature</label>
                <div class="signature-pad" id="signaturePad">
                    <canvas id="sigCanvas" width="350" height="150"></canvas>
                    <div class="sig-label">Sign above</div>
                    <button type="button" class="sig-clear" onclick="clearSignature()">Clear</button>
                </div>
                <input type="hidden" name="signature_data" id="signatureData">
            </div>
        </div>

    </form>
</div>

<!-- Wizard Footer -->
<div class="wizard-footer">
    <button type="button" class="btn btn-secondary-custom" id="prevBtn" style="display:none;" onclick="wizardPrev()">
        Previous
    </button>
    <button type="button" class="btn btn-primary-custom" id="nextBtn" onclick="wizardNext()">
        Next <i class="fas fa-arrow-right ms-1"></i>
    </button>
    <button type="button" class="btn btn-primary-custom" id="submitBtn" style="display:none;" onclick="submitInspection()">
        <i class="fas fa-check me-1"></i>Submit Inspection
    </button>
</div>

<!-- Offline Bar -->
<div class="offline-bar" id="offlineBar">
    <i class="fas fa-wifi-slash"></i>
    <span>Offline - data will sync when connected</span>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/rbi/static/js/app.js"></script>
<script src="/rbi/static/js/mobile.js"></script>
<script src="/rbi/static/js/pwa.js"></script>

<script>
'use strict';

let currentStep = 1;
const totalSteps = 5;
let mediaRecorder = null;
let audioChunks = [];
let recordingTimer = null;
let recordingSeconds = 0;
let voiceNotes = [];
let drawingCtx = null;
let drawing = false;
let sigCtx = null;
let sigDrawing = false;

document.addEventListener('DOMContentLoaded', function() {
    initDrawingCanvas();
    initSignaturePad();
    initPhotoCapture();
    initCheckboxButtons();
    initGPS();
    initScanner();
});

// ── Wizard Navigation ──────────────────────────────────────
function wizardNext() {
    if (currentStep >= totalSteps) return;
    if (!validateStep(currentStep)) return;

    currentStep++;
    updateWizard();
    RBI_MOBILE.triggerHaptic('light');
}

function wizardPrev() {
    if (currentStep <= 1) return;
    currentStep--;
    updateWizard();
    RBI_MOBILE.triggerHaptic('light');
}

function wizardBack() {
    if (currentStep > 1) {
        wizardPrev();
    } else {
        if (confirm('Discard this inspection?')) {
            window.location.href = '/rbi/mobile/dashboard.php';
        }
    }
}

function updateWizard() {
    // Update sections
    document.querySelectorAll('.wizard-section').forEach(s => s.classList.remove('active'));
    document.querySelector(`.wizard-section[data-step="${currentStep}"]`).classList.add('active');

    // Update progress
    document.querySelectorAll('.wizard-progress .step').forEach(s => {
        const step = parseInt(s.dataset.step);
        s.classList.toggle('active', step === currentStep);
        s.classList.toggle('done', step < currentStep);
    });

    // Update buttons
    document.getElementById('prevBtn').style.display = currentStep > 1 ? '' : 'none';
    document.getElementById('nextBtn').style.display = currentStep < totalSteps ? '' : 'none';
    document.getElementById('submitBtn').style.display = currentStep === totalSteps ? '' : 'none';

    // Populate review on last step
    if (currentStep === totalSteps) {
        populateReview();
    }

    window.scrollTo(0, 0);
}

function validateStep(step) {
    const section = document.querySelector(`.wizard-section[data-step="${step}"]`);
    const required = section.querySelectorAll('[required]');
    let valid = true;

    required.forEach(input => {
        if (!input.value) {
            input.classList.add('is-invalid');
            valid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    if (!valid) {
        RBI_MOBILE.triggerHaptic('error');
        RBI_APP.showToast('Please fill in all required fields', 'warning');
    }

    return valid;
}

// ── GPS Capture ────────────────────────────────────────────
function initGPS() {
    document.getElementById('gpsBtn')?.addEventListener('click', captureGPS);
}

function captureGPS() {
    if (!navigator.geolocation) {
        RBI_APP.showToast('GPS not available on this device', 'error');
        return;
    }

    const status = document.getElementById('gpsStatus');
    status.innerHTML = '<span class="dot"></span><span>Acquiring...</span>';

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            document.getElementById('latitude').value = pos.coords.latitude.toFixed(6);
            document.getElementById('longitude').value = pos.coords.longitude.toFixed(6);
            document.getElementById('gpsAccuracy').value = pos.coords.accuracy.toFixed(1);

            status.classList.add('acquired');
            status.innerHTML = `<span class="dot"></span><span>${pos.coords.latitude.toFixed(4)}, ${pos.coords.longitude.toFixed(4)}</span>`;

            RBI_MOBILE.triggerHaptic('success');
        },
        (err) => {
            status.innerHTML = '<span class="dot" style="background:#ef4444;"></span><span>Failed</span>';
            RBI_APP.showToast('Could not get GPS location: ' + err.message, 'error');
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

// ── Barcode Scanner ────────────────────────────────────────
function initScanner() {
    document.getElementById('scanBtn')?.addEventListener('click', startScanner);
}

async function startScanner() {
    const container = document.getElementById('scannerContainer');
    const video = document.getElementById('scannerVideo');

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' }
        });
        video.srcObject = stream;
        container.style.display = 'block';

        // If BarcodeDetector API is available
        if ('BarcodeDetector' in window) {
            const detector = new BarcodeDetector();
            const scanInterval = setInterval(async () => {
                try {
                    const barcodes = await detector.detect(video);
                    if (barcodes.length > 0) {
                        clearInterval(scanInterval);
                        const code = barcodes[0].rawValue;
                        handleScannedCode(code);
                        stopScanner();
                    }
                } catch (e) { /* scanning */ }
            }, 500);
        } else {
            RBI_APP.showToast('Barcode scanning requires manual entry on this device', 'info');
        }
    } catch (err) {
        RBI_APP.showToast('Camera access denied', 'error');
    }
}

function stopScanner() {
    const video = document.getElementById('scannerVideo');
    if (video.srcObject) {
        video.srcObject.getTracks().forEach(t => t.stop());
        video.srcObject = null;
    }
    document.getElementById('scannerContainer').style.display = 'none';
}

function handleScannedCode(code) {
    const select = document.getElementById('assetSelect');
    for (const opt of select.options) {
        if (opt.textContent.includes(code)) {
            select.value = opt.value;
            RBI_MOBILE.triggerHaptic('success');
            RBI_APP.showToast(`Asset found: ${code}`, 'success');
            return;
        }
    }
    RBI_APP.showToast(`Asset not found: ${code}`, 'warning');
}

// ── Photo Capture ──────────────────────────────────────────
function initPhotoCapture() {
    document.getElementById('photoInput')?.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        const grid = document.getElementById('photoGrid');

        files.forEach(file => {
            const reader = new FileReader();
            reader.onload = function(ev) {
                const item = document.createElement('div');
                item.className = 'photo-item';
                item.innerHTML = `
                    <img src="${ev.target.result}" alt="Inspection photo">
                    <button type="button" class="remove-photo" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                grid.insertBefore(item, grid.lastElementChild);
            };
            reader.readAsDataURL(file);
        });

        RBI_MOBILE.triggerHaptic('light');
    });
}

// ── Voice Recording ────────────────────────────────────────
document.getElementById('recBtn')?.addEventListener('click', toggleRecording);

function toggleRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        stopRecording();
    } else {
        startRecording();
    }
}

async function startRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];
        recordingSeconds = 0;

        mediaRecorder.ondataavailable = (e) => audioChunks.push(e.data);

        mediaRecorder.onstop = () => {
            const blob = new Blob(audioChunks, { type: 'audio/webm' });
            const url = URL.createObjectURL(blob);
            voiceNotes.push({ blob, url, duration: recordingSeconds });
            renderVoiceNotes();
            stream.getTracks().forEach(t => t.stop());
        };

        mediaRecorder.start();

        document.getElementById('recBtn').classList.add('recording');
        document.getElementById('recBtn').innerHTML = '<i class="fas fa-stop"></i>';

        recordingTimer = setInterval(() => {
            recordingSeconds++;
            const mins = Math.floor(recordingSeconds / 60).toString().padStart(2, '0');
            const secs = (recordingSeconds % 60).toString().padStart(2, '0');
            document.getElementById('recTime').textContent = `${mins}:${secs}`;
        }, 1000);

        RBI_MOBILE.triggerHaptic('medium');
    } catch (err) {
        RBI_APP.showToast('Microphone access denied', 'error');
    }
}

function stopRecording() {
    if (mediaRecorder) {
        mediaRecorder.stop();
        clearInterval(recordingTimer);
        document.getElementById('recBtn').classList.remove('recording');
        document.getElementById('recBtn').innerHTML = '<i class="fas fa-microphone"></i>';
        document.getElementById('recTime').textContent = '00:00';
        RBI_MOBILE.triggerHaptic('success');
    }
}

function renderVoiceNotes() {
    const list = document.getElementById('voiceNotesList');
    list.innerHTML = voiceNotes.map((note, i) => `
        <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light rounded">
            <button type="button" class="btn btn-sm btn-primary rounded-circle" onclick="new Audio('${note.url}').play()">
                <i class="fas fa-play" style="font-size:0.7rem;"></i>
            </button>
            <span class="flex-grow-1" style="font-size:0.8rem;">Note ${i+1} (${note.duration}s)</span>
            <button type="button" class="btn btn-sm text-danger" onclick="voiceNotes.splice(${i},1);renderVoiceNotes();">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `).join('');
}

// ── Drawing Canvas ─────────────────────────────────────────
function initDrawingCanvas() {
    const canvas = document.getElementById('drawingCanvas');
    if (!canvas) return;

    drawingCtx = canvas.getContext('2d');
    drawingCtx.strokeStyle = '#ef4444';
    drawingCtx.lineWidth = 3;
    drawingCtx.lineCap = 'round';

    canvas.addEventListener('touchstart', (e) => {
        e.preventDefault();
        drawing = true;
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        drawingCtx.beginPath();
        drawingCtx.moveTo(
            (e.touches[0].clientX - rect.left) * scaleX,
            (e.touches[0].clientY - rect.top) * scaleY
        );
    });

    canvas.addEventListener('touchmove', (e) => {
        if (!drawing) return;
        e.preventDefault();
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        drawingCtx.lineTo(
            (e.touches[0].clientX - rect.left) * scaleX,
            (e.touches[0].clientY - rect.top) * scaleY
        );
        drawingCtx.stroke();
    });

    canvas.addEventListener('touchend', () => {
        drawing = false;
        document.getElementById('markupData').value = canvas.toDataURL();
    });

    // Toolbar buttons
    document.querySelectorAll('.drawing-toolbar button').forEach(btn => {
        btn.addEventListener('click', () => {
            const tool = btn.dataset.tool;
            if (tool === 'clear') {
                drawingCtx.clearRect(0, 0, canvas.width, canvas.height);
                document.getElementById('markupData').value = '';
                return;
            }
            if (tool === 'eraser') {
                drawingCtx.strokeStyle = '#ffffff';
                drawingCtx.lineWidth = 20;
            } else {
                drawingCtx.strokeStyle = '#ef4444';
                drawingCtx.lineWidth = 3;
            }
            document.querySelectorAll('.drawing-toolbar button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });
}

// ── Signature Pad ──────────────────────────────────────────
function initSignaturePad() {
    const canvas = document.getElementById('sigCanvas');
    if (!canvas) return;

    sigCtx = canvas.getContext('2d');
    sigCtx.strokeStyle = '#1e293b';
    sigCtx.lineWidth = 2;
    sigCtx.lineCap = 'round';

    canvas.addEventListener('touchstart', (e) => {
        e.preventDefault();
        sigDrawing = true;
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        sigCtx.beginPath();
        sigCtx.moveTo(
            (e.touches[0].clientX - rect.left) * scaleX,
            (e.touches[0].clientY - rect.top) * scaleY
        );
    });

    canvas.addEventListener('touchmove', (e) => {
        if (!sigDrawing) return;
        e.preventDefault();
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        sigCtx.lineTo(
            (e.touches[0].clientX - rect.left) * scaleX,
            (e.touches[0].clientY - rect.top) * scaleY
        );
        sigCtx.stroke();
    });

    canvas.addEventListener('touchend', () => {
        sigDrawing = false;
        document.getElementById('signatureData').value = canvas.toDataURL();
    });
}

function clearSignature() {
    const canvas = document.getElementById('sigCanvas');
    sigCtx.clearRect(0, 0, canvas.width, canvas.height);
    document.getElementById('signatureData').value = '';
}

// ── Checkbox Toggle Buttons ────────────────────────────────
function initCheckboxButtons() {
    document.querySelectorAll('.btn.btn-outline-secondary input[type="checkbox"]').forEach(cb => {
        const label = cb.closest('label');
        cb.addEventListener('change', () => {
            label.classList.toggle('btn-primary', cb.checked);
            label.classList.toggle('btn-outline-secondary', !cb.checked);
            label.style.color = cb.checked ? 'white' : '';
        });
    });
}

// ── Review Summary ─────────────────────────────────────────
function populateReview() {
    const form = document.getElementById('inspectionForm');
    const data = new FormData(form);
    const assetSelect = document.getElementById('assetSelect');
    const assetText = assetSelect.options[assetSelect.selectedIndex]?.text || 'Not selected';

    document.getElementById('reviewSummary').innerHTML = `
        <div class="card" style="border-radius:12px;">
            <div class="card-body" style="font-size:0.85rem;">
                <div class="mb-2"><strong>Asset:</strong> ${assetText}</div>
                <div class="mb-2"><strong>Type:</strong> ${data.get('inspection_type') || '-'}</div>
                <div class="mb-2"><strong>Date:</strong> ${data.get('inspection_date') || '-'}</div>
                <div class="mb-2"><strong>Condition:</strong> ${data.get('condition') || '-'}</div>
                <div class="mb-2"><strong>Thickness:</strong> ${data.get('measured_thickness') || '-'} ${data.get('thickness_unit') || 'mm'}</div>
                <div class="mb-2"><strong>Photos:</strong> ${document.querySelectorAll('.photo-item').length} captured</div>
                <div class="mb-2"><strong>Voice Notes:</strong> ${voiceNotes.length} recorded</div>
                <div class="mb-2"><strong>Priority:</strong> ${data.get('priority') || '-'}</div>
            </div>
        </div>
    `;
}

// ── Submit Inspection ──────────────────────────────────────
async function submitInspection() {
    if (!document.getElementById('signatureData').value) {
        RBI_APP.showToast('Please provide your signature', 'warning');
        RBI_MOBILE.triggerHaptic('error');
        return;
    }

    const form = document.getElementById('inspectionForm');
    const formData = new FormData(form);
    const data = {};
    formData.forEach((val, key) => { data[key] = val; });

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Submitting...';

    try {
        if (navigator.onLine) {
            const response = await RBI_APP.request('/rbi/api/inspections.php', {
                method: 'POST',
                body: data
            });

            if (response.ok || response.offline) {
                RBI_MOBILE.triggerHaptic('success');
                RBI_APP.showToast('Inspection submitted successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '/rbi/mobile/dashboard.php';
                }, 1500);
            } else {
                throw new Error('Submit failed');
            }
        } else {
            // Queue for offline sync
            await RBI_PWA.registerSync('sync-inspection-data', {
                url: '/rbi/api/inspections.php',
                data: data
            });

            RBI_MOBILE.triggerHaptic('success');
            RBI_APP.showToast('Saved offline - will sync when connected', 'info');
            setTimeout(() => {
                window.location.href = '/rbi/mobile/dashboard.php';
            }, 1500);
        }
    } catch (err) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check me-1"></i>Submit Inspection';
        RBI_APP.showToast('Submission failed. Please try again.', 'error');
        RBI_MOBILE.triggerHaptic('error');
    }
}
</script>

</body>
</html>
