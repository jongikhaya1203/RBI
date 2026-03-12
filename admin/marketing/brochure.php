<?php
/**
 * Product Brochure Generator - RBI Engineering Suite
 * Generates a professional, print-ready brochure styled for PDF export
 */
require_once dirname(dirname(__DIR__)) . '/config/app.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    flash('Please log in to continue.', 'warning');
    redirect(BASE_URL . '/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RBI Engineering Suite - Product Brochure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; margin: 0; background: #f1f5f9; color: #1e293b; }

        .brochure-page {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 0;
            overflow: hidden;
            position: relative;
        }

        /* Cover Page */
        .cover-page {
            background: linear-gradient(135deg, #0f172a 0%, #1a237e 40%, #1565c0 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 80px 60px;
            min-height: 297mm;
        }
        .cover-page .logo-icon {
            width: 100px; height: 100px;
            background: rgba(255,255,255,0.15);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin-bottom: 40px;
        }
        .cover-page h1 { font-size: 3rem; font-weight: 800; margin-bottom: 16px; letter-spacing: -1px; }
        .cover-page .tagline { font-size: 1.3rem; opacity: 0.8; max-width: 500px; line-height: 1.6; margin-bottom: 40px; }
        .cover-page .edition { font-size: 0.9rem; opacity: 0.5; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px; margin-top: auto; }

        /* Content Pages */
        .page-content { padding: 50px 60px; min-height: 297mm; }
        .page-header-bar {
            background: #1a237e;
            color: #fff;
            padding: 16px 60px;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .section-title { font-size: 1.6rem; font-weight: 800; color: #1a237e; margin-bottom: 8px; }
        .section-subtitle { font-size: 0.95rem; color: #64748b; margin-bottom: 24px; }

        .feature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .feature-box {
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .feature-box h5 { font-size: 1rem; font-weight: 700; margin-bottom: 6px; }
        .feature-box p { font-size: 0.82rem; color: #64748b; margin-bottom: 0; line-height: 1.5; }
        .feature-box .f-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            font-size: 1rem;
        }

        .module-item {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .module-item:last-child { border-bottom: none; }
        .module-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.1rem;
        }
        .module-item h6 { font-weight: 700; margin-bottom: 4px; }
        .module-item p { font-size: 0.82rem; color: #64748b; margin-bottom: 0; }

        .spec-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .spec-table th { background: #f8fafc; padding: 10px 16px; text-align: left; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; }
        .spec-table td { padding: 10px 16px; border-bottom: 1px solid #f1f5f9; }
        .spec-table tr:last-child td { border-bottom: none; }

        .cta-box {
            background: linear-gradient(135deg, #1a237e, #3f51b5);
            color: #fff;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
        }

        .success-story {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 16px;
            border-left: 4px solid #3f51b5;
        }
        .success-story .quote { font-style: italic; color: #475569; margin-bottom: 8px; font-size: 0.9rem; }
        .success-story .author { font-weight: 700; font-size: 0.85rem; }
        .success-story .title { font-size: 0.78rem; color: #94a3b8; }

        .comp-row { display: flex; border-bottom: 1px solid #f1f5f9; }
        .comp-row:last-child { border-bottom: none; }
        .comp-cell { flex: 1; padding: 8px 12px; font-size: 0.82rem; text-align: center; }
        .comp-cell:first-child { text-align: left; flex: 1.5; font-weight: 600; }
        .comp-header { background: #f8fafc; font-weight: 700; color: #475569; }

        .page-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px 60px;
            font-size: 0.7rem;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #f1f5f9;
        }

        /* Print styles */
        @media print {
            body { background: #fff; }
            .brochure-page { box-shadow: none; margin: 0; width: 100%; }
            .no-print { display: none !important; }
            .brochure-page { page-break-after: always; }
            .brochure-page:last-child { page-break-after: auto; }
        }

        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 8px;
        }
    </style>
</head>
<body>

<!-- Print Controls -->
<div class="print-controls no-print">
    <button onclick="window.print()" class="btn btn-primary btn-lg shadow">
        <i class="fas fa-print me-2"></i>Save as PDF
    </button>
    <a href="<?= BASE_URL ?>/admin/marketing/" class="btn btn-secondary btn-lg shadow">
        <i class="fas fa-arrow-left me-2"></i>Back
    </a>
</div>

<!-- PAGE 1: Cover -->
<div class="brochure-page">
    <div class="cover-page">
        <div class="logo-icon"><i class="fas fa-shield-alt"></i></div>
        <h1>RBI Engineering Suite</h1>
        <p class="tagline">The most comprehensive Risk-Based Inspection platform for the process industries</p>
        <div style="display:flex;gap:24px;margin-bottom:60px;">
            <div style="text-align:center;">
                <div style="font-size:2rem;font-weight:800;">API</div>
                <div style="font-size:0.75rem;opacity:0.7;">580/581 Compliant</div>
            </div>
            <div style="width:1px;background:rgba(255,255,255,0.2);"></div>
            <div style="text-align:center;">
                <div style="font-size:2rem;font-weight:800;">ML</div>
                <div style="font-size:0.75rem;opacity:0.7;">Powered Analytics</div>
            </div>
            <div style="width:1px;background:rgba(255,255,255,0.2);"></div>
            <div style="text-align:center;">
                <div style="font-size:2rem;font-weight:800;">IoT</div>
                <div style="font-size:0.75rem;opacity:0.7;">Sensor Ready</div>
            </div>
        </div>
        <div class="edition">
            Product Brochure | <?= date('Y') ?> Edition<br>
            www.rbi-engineering.com
        </div>
    </div>
</div>

<!-- PAGE 2: Overview -->
<div class="brochure-page">
    <div class="page-header-bar">Product Overview</div>
    <div class="page-content">
        <h2 class="section-title">Transform Your Inspection Program</h2>
        <p class="section-subtitle">From reactive to predictive integrity management</p>

        <p style="font-size:0.95rem;line-height:1.8;color:#475569;margin-bottom:24px;">
            RBI Engineering Suite is an enterprise-grade platform that enables organizations to implement comprehensive Risk-Based Inspection programs in accordance with API 580/581 standards. Our software combines rigorous risk assessment methodology with cutting-edge technology including machine learning, IoT sensor integration, and cloud-native architecture to deliver unmatched inspection optimization.
        </p>

        <div style="background:#f0f9ff;border-radius:12px;padding:24px;margin-bottom:24px;">
            <h5 style="color:#1a237e;font-weight:700;margin-bottom:16px;">By The Numbers</h5>
            <div style="display:flex;text-align:center;">
                <div style="flex:1;border-right:1px solid #e2e8f0;">
                    <div style="font-size:2rem;font-weight:800;color:#1a237e;">35%</div>
                    <div style="font-size:0.78rem;color:#64748b;">Average Inspection Cost Reduction</div>
                </div>
                <div style="flex:1;border-right:1px solid #e2e8f0;">
                    <div style="font-size:2rem;font-weight:800;color:#1a237e;">50%</div>
                    <div style="font-size:0.78rem;color:#64748b;">Fewer Unplanned Failures</div>
                </div>
                <div style="flex:1;border-right:1px solid #e2e8f0;">
                    <div style="font-size:2rem;font-weight:800;color:#1a237e;">200+</div>
                    <div style="font-size:0.78rem;color:#64748b;">Facilities Worldwide</div>
                </div>
                <div style="flex:1;">
                    <div style="font-size:2rem;font-weight:800;color:#1a237e;">94%</div>
                    <div style="font-size:0.78rem;color:#64748b;">Customer Satisfaction</div>
                </div>
            </div>
        </div>

        <h5 style="color:#1a237e;font-weight:700;margin-bottom:16px;">Who Is It For?</h5>
        <div class="feature-grid">
            <div class="feature-box">
                <h5><i class="fas fa-oil-can text-primary me-2"></i>Refineries</h5>
                <p>Comprehensive RBI for CDU, FCC, hydrotreaters, reformers, and all refinery equipment.</p>
            </div>
            <div class="feature-box">
                <h5><i class="fas fa-flask text-success me-2"></i>Chemical Plants</h5>
                <p>Manage corrosive chemical environments with specialized damage mechanism screening.</p>
            </div>
            <div class="feature-box">
                <h5><i class="fas fa-fire text-danger me-2"></i>Petrochemicals</h5>
                <p>Ethylene, propylene, and polymer plants with high-temperature service monitoring.</p>
            </div>
            <div class="feature-box">
                <h5><i class="fas fa-water text-info me-2"></i>Upstream O&G</h5>
                <p>Offshore platforms, FPSOs, and onshore processing facilities.</p>
            </div>
        </div>
    </div>
    <div class="page-footer">
        <span>RBI Engineering Suite | Product Brochure</span>
        <span>Page 2</span>
    </div>
</div>

<!-- PAGE 3-4: Key Features -->
<div class="brochure-page">
    <div class="page-header-bar">Key Features</div>
    <div class="page-content">
        <h2 class="section-title">Comprehensive Feature Set</h2>
        <p class="section-subtitle">Everything you need for world-class integrity management</p>

        <div class="feature-grid" style="margin-bottom:24px;">
            <div class="feature-box">
                <div class="f-icon" style="background:#3b82f620;color:#3b82f6;"><i class="fas fa-file-contract"></i></div>
                <h5>API 580/581 Risk Engine</h5>
                <p>Full quantitative RBI methodology with all damage factors, consequence models, and inspection effectiveness tables per API 581 3rd edition.</p>
            </div>
            <div class="feature-box">
                <div class="f-icon" style="background:#8b5cf620;color:#8b5cf6;"><i class="fas fa-brain"></i></div>
                <h5>Predictive Analytics</h5>
                <p>Machine learning models for failure prediction, corrosion rate forecasting, and anomaly detection. Auto-clustering of similar equipment.</p>
            </div>
            <div class="feature-box">
                <div class="f-icon" style="background:#06b6d420;color:#06b6d4;"><i class="fas fa-wifi"></i></div>
                <h5>IoT Integration</h5>
                <p>Real-time data from UT sensors, corrosion probes, environmental monitors. Automated alerts when thresholds are exceeded.</p>
            </div>
            <div class="feature-box">
                <div class="f-icon" style="background:#22c55e20;color:#22c55e;"><i class="fas fa-mobile-alt"></i></div>
                <h5>Mobile Field App</h5>
                <p>Progressive Web App for field inspectors with offline capability, photo documentation, and real-time data synchronization.</p>
            </div>
            <div class="feature-box">
                <div class="f-icon" style="background:#f59e0b20;color:#f59e0b;"><i class="fas fa-plug"></i></div>
                <h5>Enterprise Integrations</h5>
                <p>Native connectors for SAP PM, IBM Maximo, OSIsoft PI. Open REST API for custom integrations with any enterprise system.</p>
            </div>
            <div class="feature-box">
                <div class="f-icon" style="background:#ef444420;color:#ef4444;"><i class="fas fa-chart-line"></i></div>
                <h5>Advanced Analytics</h5>
                <p>Remaining life calculations, corrosion rate trending, sensitivity analysis, Monte Carlo simulation, and financial risk modeling.</p>
            </div>
        </div>

        <div style="background:linear-gradient(135deg,#1a237e,#3f51b5);color:#fff;border-radius:12px;padding:24px;display:flex;align-items:center;gap:24px;">
            <i class="fas fa-shield-alt fa-3x" style="opacity:0.5;"></i>
            <div>
                <h5 style="font-weight:700;margin-bottom:4px;">Enterprise Security</h5>
                <p style="margin:0;font-size:0.85rem;opacity:0.8;">Role-based access control, full audit trail, LDAP/Active Directory integration, SSO support, data encryption at rest and in transit.</p>
            </div>
        </div>
    </div>
    <div class="page-footer">
        <span>RBI Engineering Suite | Product Brochure</span>
        <span>Page 3</span>
    </div>
</div>

<!-- PAGE 5: Modules -->
<div class="brochure-page">
    <div class="page-header-bar">Platform Modules</div>
    <div class="page-content">
        <h2 class="section-title">Modular Architecture</h2>
        <p class="section-subtitle">Select the modules that fit your needs</p>

        <?php
        $modules = [
            ['icon' => 'fa-industry', 'color' => '#3b82f6', 'name' => 'Asset Management', 'desc' => 'Hierarchical asset registry with equipment data management, corrosion circuits, and condition monitoring locations.'],
            ['icon' => 'fa-exclamation-triangle', 'color' => '#ef4444', 'name' => 'Risk Assessment', 'desc' => 'Qualitative, semi-quantitative, and full API 581 quantitative risk assessment with automated damage factor calculation.'],
            ['icon' => 'fa-bolt', 'color' => '#f59e0b', 'name' => 'Damage Mechanisms', 'desc' => 'API 571 damage mechanism library with susceptibility screening, corrosion rate estimation, and CML management.'],
            ['icon' => 'fa-clipboard-check', 'color' => '#22c55e', 'name' => 'Inspection Planning', 'desc' => 'Risk-based inspection plans with NDE method selection, interval optimization, scheduling, and mobile field execution.'],
            ['icon' => 'fa-chart-line', 'color' => '#8b5cf6', 'name' => 'Analytics & Reporting', 'desc' => 'Remaining life, corrosion trending, financial risk, Monte Carlo simulation, and configurable report generation.'],
            ['icon' => 'fa-brain', 'color' => '#ec4899', 'name' => 'Predictive Intelligence', 'desc' => 'ML-powered failure prediction, anomaly detection, auto risk scoring, and asset clustering for fleet analysis.'],
            ['icon' => 'fa-plug', 'color' => '#06b6d4', 'name' => 'Integration Hub', 'desc' => 'Pre-built connectors for SAP PM, IBM Maximo, OSIsoft PI, SCADA systems, and IoT sensor networks.'],
            ['icon' => 'fa-graduation-cap', 'color' => '#84cc16', 'name' => 'Training Center', 'desc' => 'Built-in RBI training with courses, quizzes, certifications, and progress tracking for team competency management.'],
        ];
        foreach ($modules as $m):
        ?>
        <div class="module-item">
            <div class="module-icon" style="background:<?= $m['color'] ?>15;color:<?= $m['color'] ?>;">
                <i class="fas <?= $m['icon'] ?>"></i>
            </div>
            <div>
                <h6><?= $m['name'] ?></h6>
                <p><?= $m['desc'] ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="page-footer">
        <span>RBI Engineering Suite | Product Brochure</span>
        <span>Page 4</span>
    </div>
</div>

<!-- PAGE 6: Screenshots -->
<div class="brochure-page">
    <div class="page-header-bar">Product Screenshots</div>
    <div class="page-content">
        <h2 class="section-title">Intuitive Interface</h2>
        <p class="section-subtitle">Enterprise-grade UI designed for efficiency</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <?php
            $screenshots = [
                ['name' => 'Dashboard & Risk Overview', 'color' => '#1a237e'],
                ['name' => 'Risk Matrix Visualization', 'color' => '#c62828'],
                ['name' => 'Asset Hierarchy Browser', 'color' => '#1565c0'],
                ['name' => 'Inspection Calendar', 'color' => '#2e7d32'],
                ['name' => 'Corrosion Rate Analytics', 'color' => '#6a1b9a'],
                ['name' => 'Mobile Field App', 'color' => '#e65100'],
            ];
            foreach ($screenshots as $s):
            ?>
            <div style="background:<?= $s['color'] ?>;border-radius:10px;height:140px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.6);font-weight:600;font-size:0.85rem;">
                <div style="text-align:center;">
                    <i class="fas fa-desktop fa-lg mb-1 d-block"></i>
                    <?= $s['name'] ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="page-footer">
        <span>RBI Engineering Suite | Product Brochure</span>
        <span>Page 5</span>
    </div>
</div>

<!-- PAGE 7: Technical Specifications -->
<div class="brochure-page">
    <div class="page-header-bar">Technical Specifications</div>
    <div class="page-content">
        <h2 class="section-title">Technical Details</h2>
        <p class="section-subtitle">Built on modern, enterprise-grade technology</p>

        <table class="spec-table" style="margin-bottom:24px;">
            <tr><th colspan="2">Platform</th></tr>
            <tr><td>Architecture</td><td>Microservices, Cloud-native</td></tr>
            <tr><td>Deployment</td><td>Cloud (AWS, Azure, GCP), On-premise, Hybrid</td></tr>
            <tr><td>Web Interface</td><td>Responsive HTML5, Bootstrap 5, Progressive Web App</td></tr>
            <tr><td>Mobile</td><td>PWA with offline sync (iOS, Android, Windows)</td></tr>
            <tr><td>Database</td><td>MySQL 8.0+, PostgreSQL 14+, SQL Server 2019+</td></tr>
            <tr><td>API</td><td>RESTful JSON API with OAuth 2.0 authentication</td></tr>
        </table>

        <table class="spec-table" style="margin-bottom:24px;">
            <tr><th colspan="2">Security & Compliance</th></tr>
            <tr><td>Authentication</td><td>LDAP/AD, SAML 2.0, OAuth 2.0, MFA</td></tr>
            <tr><td>Authorization</td><td>Role-based access control (RBAC)</td></tr>
            <tr><td>Encryption</td><td>AES-256 at rest, TLS 1.3 in transit</td></tr>
            <tr><td>Audit</td><td>Complete audit trail with user attribution</td></tr>
            <tr><td>Compliance</td><td>SOC 2 Type II, ISO 27001</td></tr>
        </table>

        <table class="spec-table" style="margin-bottom:24px;">
            <tr><th colspan="2">Integration Capabilities</th></tr>
            <tr><td>EAM/CMMS</td><td>SAP PM, IBM Maximo, Infor EAM</td></tr>
            <tr><td>Historian</td><td>OSIsoft PI, Honeywell PHD, Yokogawa Exaquantum</td></tr>
            <tr><td>IoT/SCADA</td><td>MQTT, OPC-UA, Modbus TCP</td></tr>
            <tr><td>File Formats</td><td>Import/Export CSV, Excel, PDF, JSON</td></tr>
            <tr><td>Custom</td><td>REST API, Webhooks, SDK</td></tr>
        </table>

        <table class="spec-table">
            <tr><th colspan="2">Performance</th></tr>
            <tr><td>Availability</td><td>99.9% uptime SLA (cloud deployment)</td></tr>
            <tr><td>Scalability</td><td>100,000+ assets per instance</td></tr>
            <tr><td>Concurrent Users</td><td>500+ simultaneous users</td></tr>
            <tr><td>Response Time</td><td>&lt; 200ms average page load</td></tr>
        </table>
    </div>
    <div class="page-footer">
        <span>RBI Engineering Suite | Product Brochure</span>
        <span>Page 6</span>
    </div>
</div>

<!-- PAGE 8: Competitive Comparison -->
<div class="brochure-page">
    <div class="page-header-bar">Competitive Advantage</div>
    <div class="page-content">
        <h2 class="section-title">How We Compare</h2>
        <p class="section-subtitle">Feature comparison with leading alternatives</p>

        <div style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:24px;">
            <div class="comp-row comp-header">
                <div class="comp-cell">Feature</div>
                <div class="comp-cell" style="color:#1a237e;">RBI Suite</div>
                <div class="comp-cell">DNV Synergi</div>
                <div class="comp-cell">Hexagon APM</div>
                <div class="comp-cell">Cenosco</div>
            </div>
            <?php
            $compItems = [
                ['API 581 Quantitative', '&#10004;', '&#10004;', '&#10004;', '~'],
                ['Semi-Quantitative', '&#10004;', '&#10004;', '&#10004;', '&#10004;'],
                ['ML Predictive Analytics', '&#10004;', '&#10006;', '~', '&#10006;'],
                ['IoT Sensor Integration', '&#10004;', '&#10006;', '~', '&#10006;'],
                ['Mobile PWA/Offline', '&#10004;', '&#10006;', '~', '&#10006;'],
                ['SAP/Maximo Integration', '&#10004;', '~', '&#10004;', '~'],
                ['Cloud Deployment', '&#10004;', '~', '&#10004;', '&#10006;'],
                ['Built-in Training', '&#10004;', '&#10006;', '&#10006;', '&#10006;'],
                ['REST API', '&#10004;', '~', '&#10004;', '~'],
                ['Digital Twin Support', '&#10004;', '&#10006;', '~', '&#10006;'],
            ];
            foreach ($compItems as $item):
            ?>
            <div class="comp-row">
                <div class="comp-cell"><?= $item[0] ?></div>
                <div class="comp-cell" style="color:#22c55e;font-weight:700;"><?= $item[1] ?></div>
                <div class="comp-cell"><?= $item[2] === '&#10004;' ? '<span style="color:#22c55e;">'.$item[2].'</span>' : ($item[2] === '&#10006;' ? '<span style="color:#ef4444;">'.$item[2].'</span>' : '<span style="color:#f59e0b;">'.$item[2].'</span>') ?></div>
                <div class="comp-cell"><?= $item[3] === '&#10004;' ? '<span style="color:#22c55e;">'.$item[3].'</span>' : ($item[3] === '&#10006;' ? '<span style="color:#ef4444;">'.$item[3].'</span>' : '<span style="color:#f59e0b;">'.$item[3].'</span>') ?></div>
                <div class="comp-cell"><?= $item[4] === '&#10004;' ? '<span style="color:#22c55e;">'.$item[4].'</span>' : ($item[4] === '&#10006;' ? '<span style="color:#ef4444;">'.$item[4].'</span>' : '<span style="color:#f59e0b;">'.$item[4].'</span>') ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <p style="font-size:0.78rem;color:#94a3b8;"><i class="fas fa-info-circle me-1"></i>&#10004; = Fully supported &nbsp; ~ = Partial/Limited &nbsp; &#10006; = Not available. Based on publicly available information as of <?= date('Y') ?>.</p>
    </div>
    <div class="page-footer">
        <span>RBI Engineering Suite | Product Brochure</span>
        <span>Page 7</span>
    </div>
</div>

<!-- PAGE 9: Customer Success -->
<div class="brochure-page">
    <div class="page-header-bar">Customer Success</div>
    <div class="page-content">
        <h2 class="section-title">Customer Success Stories</h2>
        <p class="section-subtitle">Real results from real customers</p>

        <div class="success-story">
            <p class="quote">"After implementing RBI Engineering Suite across our 3 refineries, we achieved a 42% reduction in inspection costs and extended our turnaround interval from 4 to 6 years for low-risk equipment. The predictive analytics module identified a critical vessel issue 6 months before our scheduled inspection, potentially preventing a major safety incident."</p>
            <div class="author">John Richardson, VP of Operations</div>
            <div class="title">Gulf Coast Refining - 3 Refineries, 12,000+ assets</div>
            <div style="display:flex;gap:24px;margin-top:12px;">
                <div><span style="font-weight:800;color:#1a237e;font-size:1.2rem;">42%</span><br><small style="color:#64748b;">Cost Reduction</small></div>
                <div><span style="font-weight:800;color:#1a237e;font-size:1.2rem;">6yr</span><br><small style="color:#64748b;">Extended TA Interval</small></div>
                <div><span style="font-weight:800;color:#1a237e;font-size:1.2rem;">1</span><br><small style="color:#64748b;">Major Incident Prevented</small></div>
            </div>
        </div>

        <div class="success-story">
            <p class="quote">"The mobile app transformed our field inspection workflow. We eliminated paper forms completely, reduced data entry time by 75%, and improved data quality. Our inspectors can now access equipment history, enter readings, and take photos all from their tablets."</p>
            <div class="author">Dr. Lisa Chang, Chief Integrity Engineer</div>
            <div class="title">Pacific Petrochemicals - 2 Chemical Plants, 8,000+ assets</div>
            <div style="display:flex;gap:24px;margin-top:12px;">
                <div><span style="font-weight:800;color:#1a237e;font-size:1.2rem;">75%</span><br><small style="color:#64748b;">Less Data Entry</small></div>
                <div><span style="font-weight:800;color:#1a237e;font-size:1.2rem;">100%</span><br><small style="color:#64748b;">Paperless</small></div>
                <div><span style="font-weight:800;color:#1a237e;font-size:1.2rem;">95%</span><br><small style="color:#64748b;">Error Reduction</small></div>
            </div>
        </div>

        <div class="success-story">
            <p class="quote">"The integration with our SAP PM system was seamless. Work orders generated by RBI flow directly into SAP for scheduling and resource allocation. We now have a single source of truth for equipment integrity across our enterprise."</p>
            <div class="author">Mark Stevens, Refinery Manager</div>
            <div class="title">Midwest Processing Corp - 1 Refinery, 5,000+ assets</div>
            <div style="display:flex;gap:24px;margin-top:12px;">
                <div><span style="font-weight:800;color:#1a237e;font-size:1.2rem;">8mo</span><br><small style="color:#64748b;">ROI Payback</small></div>
                <div><span style="font-weight:800;color:#1a237e;font-size:1.2rem;">100%</span><br><small style="color:#64748b;">SAP Integration</small></div>
                <div><span style="font-weight:800;color:#1a237e;font-size:1.2rem;">38%</span><br><small style="color:#64748b;">Cost Savings</small></div>
            </div>
        </div>
    </div>
    <div class="page-footer">
        <span>RBI Engineering Suite | Product Brochure</span>
        <span>Page 8</span>
    </div>
</div>

<!-- PAGE 10: Pricing & Contact -->
<div class="brochure-page">
    <div class="page-header-bar">Pricing & Contact</div>
    <div class="page-content">
        <h2 class="section-title">Flexible Pricing Options</h2>
        <p class="section-subtitle">Plans designed for organizations of all sizes</p>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:32px;">
            <div style="border:1px solid #e2e8f0;border-radius:12px;padding:24px;text-align:center;">
                <h5 style="color:#64748b;font-weight:700;">Starter</h5>
                <div style="font-size:2rem;font-weight:800;color:#1a237e;">$2,500</div>
                <div style="color:#94a3b8;font-size:0.85rem;margin-bottom:16px;">/month</div>
                <div style="font-size:0.82rem;text-align:left;">
                    <div style="padding:6px 0;border-bottom:1px solid #f1f5f9;">Up to 500 assets</div>
                    <div style="padding:6px 0;border-bottom:1px solid #f1f5f9;">5 user licenses</div>
                    <div style="padding:6px 0;border-bottom:1px solid #f1f5f9;">Core RBI engine</div>
                    <div style="padding:6px 0;">Email support</div>
                </div>
            </div>
            <div style="border:2px solid #3f51b5;border-radius:12px;padding:24px;text-align:center;position:relative;">
                <div style="position:absolute;top:-10px;left:50%;transform:translateX(-50%);background:#3f51b5;color:#fff;padding:2px 16px;border-radius:10px;font-size:0.7rem;font-weight:600;">POPULAR</div>
                <h5 style="color:#3f51b5;font-weight:700;">Professional</h5>
                <div style="font-size:2rem;font-weight:800;color:#1a237e;">$7,500</div>
                <div style="color:#94a3b8;font-size:0.85rem;margin-bottom:16px;">/month</div>
                <div style="font-size:0.82rem;text-align:left;">
                    <div style="padding:6px 0;border-bottom:1px solid #f1f5f9;">Up to 2,000 assets</div>
                    <div style="padding:6px 0;border-bottom:1px solid #f1f5f9;">20 user licenses</div>
                    <div style="padding:6px 0;border-bottom:1px solid #f1f5f9;">ML & integrations</div>
                    <div style="padding:6px 0;">Priority support</div>
                </div>
            </div>
            <div style="border:1px solid #e2e8f0;border-radius:12px;padding:24px;text-align:center;">
                <h5 style="color:#64748b;font-weight:700;">Enterprise</h5>
                <div style="font-size:2rem;font-weight:800;color:#1a237e;">Custom</div>
                <div style="color:#94a3b8;font-size:0.85rem;margin-bottom:16px;">pricing</div>
                <div style="font-size:0.82rem;text-align:left;">
                    <div style="padding:6px 0;border-bottom:1px solid #f1f5f9;">Unlimited assets</div>
                    <div style="padding:6px 0;border-bottom:1px solid #f1f5f9;">Unlimited users</div>
                    <div style="padding:6px 0;border-bottom:1px solid #f1f5f9;">Full platform + IoT</div>
                    <div style="padding:6px 0;">24/7 dedicated support</div>
                </div>
            </div>
        </div>

        <div class="cta-box" style="margin-bottom:32px;">
            <h3 style="font-weight:800;margin-bottom:8px;">Ready to Transform Your Inspection Program?</h3>
            <p style="opacity:0.8;margin-bottom:0;">Contact us for a personalized demonstration and ROI analysis.</p>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
            <div>
                <h5 style="font-weight:700;color:#1a237e;margin-bottom:12px;"><i class="fas fa-envelope me-2"></i>Contact Us</h5>
                <div style="font-size:0.9rem;line-height:2;">
                    <strong>Sales:</strong> sales@rbi-engineering.com<br>
                    <strong>Support:</strong> support@rbi-engineering.com<br>
                    <strong>Phone:</strong> +1 (800) 555-RBI1<br>
                    <strong>Web:</strong> www.rbi-engineering.com
                </div>
            </div>
            <div>
                <h5 style="font-weight:700;color:#1a237e;margin-bottom:12px;"><i class="fas fa-map-marker-alt me-2"></i>Headquarters</h5>
                <div style="font-size:0.9rem;line-height:2;">
                    RBI Engineering Inc.<br>
                    1234 Industrial Parkway, Suite 500<br>
                    Houston, TX 77001, USA<br>
                    <br>
                    <strong>Regional Offices:</strong> London, Singapore, Abu Dhabi
                </div>
            </div>
        </div>
    </div>
    <div class="page-footer">
        <span>RBI Engineering Suite | Product Brochure | &copy; <?= date('Y') ?> RBI Engineering Inc.</span>
        <span>Page 9</span>
    </div>
</div>

</body>
</html>
