<?php
/**
 * Marketing Materials Hub - RBI Engineering Suite
 * Product marketing page with features, testimonials, ROI calculator, pricing
 */
$pageTitle = 'Marketing Materials';
$pageSection = 'Administration';
$currentModule = 'admin';

require_once dirname(dirname(__DIR__)) . '/config/app.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    flash('Please log in to continue.', 'warning');
    redirect(BASE_URL . '/login.php');
}

include INCLUDES_PATH . '/header.php';
?>

<style>
.mkt-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1a237e 50%, #1565c0 100%);
    border-radius: 16px;
    padding: 60px 40px;
    color: #fff;
    text-align: center;
    margin-bottom: 40px;
    position: relative;
    overflow: hidden;
}
.mkt-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 500px;
    height: 500px;
    background: rgba(255,255,255,0.03);
    border-radius: 50%;
}
.mkt-hero h1 { font-size: 2.5rem; font-weight: 800; margin-bottom: 12px; }
.mkt-hero .tagline { font-size: 1.2rem; opacity: 0.85; max-width: 600px; margin: 0 auto 24px; }

.feature-card {
    background: #fff;
    border-radius: 12px;
    padding: 32px 24px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: all 0.3s;
    height: 100%;
}
.feature-card:hover { transform: translateY(-6px); box-shadow: 0 12px 32px rgba(0,0,0,0.1); }
.feature-icon {
    width: 64px; height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 16px;
}

.testimonial-card {
    background: #fff;
    border-radius: 12px;
    padding: 32px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    position: relative;
    height: 100%;
}
.testimonial-card::before {
    content: '\201C';
    font-size: 4rem;
    color: #e2e8f0;
    position: absolute;
    top: 10px;
    left: 20px;
    font-family: Georgia, serif;
    line-height: 1;
}
.testimonial-text { font-style: italic; color: #475569; margin-bottom: 16px; padding-top: 24px; }
.testimonial-author { font-weight: 700; color: #1e293b; }
.testimonial-role { font-size: 0.82rem; color: #94a3b8; }

.pricing-card {
    background: #fff;
    border-radius: 16px;
    padding: 40px 32px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: all 0.3s;
    height: 100%;
    border: 2px solid transparent;
}
.pricing-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
.pricing-card.featured { border-color: #3f51b5; position: relative; }
.pricing-card.featured::before {
    content: 'Most Popular';
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: #3f51b5;
    color: #fff;
    padding: 4px 20px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}
.pricing-card .price { font-size: 2.5rem; font-weight: 800; color: #1a237e; }
.pricing-card .price small { font-size: 1rem; color: #94a3b8; font-weight: 400; }
.pricing-feature { padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.875rem; }
.pricing-feature:last-child { border-bottom: none; }

.roi-calculator {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-radius: 16px;
    padding: 40px;
}

.screenshot-placeholder {
    border-radius: 12px;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s;
    cursor: pointer;
}
.screenshot-placeholder:hover { opacity: 0.85; transform: scale(1.02); }

.partner-logo {
    width: 120px;
    height: 60px;
    background: #f8fafc;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-weight: 600;
    font-size: 0.8rem;
    border: 1px solid #e2e8f0;
}

.section-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
.section-subtitle { font-size: 1rem; color: #64748b; margin-bottom: 32px; }
</style>

<!-- Hero Section -->
<div class="mkt-hero">
    <div class="position-relative">
        <span class="badge bg-white text-primary mb-3 px-3 py-2">Enterprise Software</span>
        <h1><i class="fas fa-shield-alt me-2"></i>RBI Engineering Suite</h1>
        <p class="tagline">The most comprehensive Risk-Based Inspection platform for the process industries. API 580/581 compliant, ML-powered, cloud-native.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="<?= BASE_URL ?>/admin/marketing/brochure.php" class="btn btn-light btn-lg">
                <i class="fas fa-file-pdf me-1"></i>Download Brochure
            </a>
            <button class="btn btn-outline-light btn-lg" data-bs-toggle="modal" data-bs-target="#demoModal">
                <i class="fas fa-play-circle me-1"></i>Request Demo
            </button>
        </div>
    </div>
</div>

<!-- Feature Highlights -->
<div class="text-center mb-3">
    <h2 class="section-title">Why Choose RBI Engineering Suite?</h2>
    <p class="section-subtitle">Enterprise-grade features designed for refinery and chemical plant integrity management</p>
</div>

<div class="row g-4 mb-5">
    <?php
    $features = [
        ['icon' => 'fa-file-contract', 'color' => '#3b82f6', 'title' => 'API 580/581 Compliant Risk Engine', 'desc' => 'Full quantitative and semi-quantitative risk assessment methodology with all damage factors per API 581 3rd edition.'],
        ['icon' => 'fa-brain', 'color' => '#8b5cf6', 'title' => 'ML-Powered Predictive Analytics', 'desc' => 'Machine learning models predict failure probability, optimize inspection intervals, and identify at-risk equipment.'],
        ['icon' => 'fa-wifi', 'color' => '#06b6d4', 'title' => 'Real-time IoT Integration', 'desc' => 'Connect UT sensors, corrosion probes, and environmental monitors for continuous, real-time integrity data.'],
        ['icon' => 'fa-mobile-alt', 'color' => '#22c55e', 'title' => 'Mobile-First Field Inspection', 'desc' => 'Progressive Web App with offline capability for field inspectors. Photo documentation and instant data sync.'],
        ['icon' => 'fa-plug', 'color' => '#f59e0b', 'title' => 'Enterprise Integration', 'desc' => 'Native integration with SAP PM, IBM Maximo, OSIsoft PI, and any system via REST API.'],
        ['icon' => 'fa-cloud', 'color' => '#ef4444', 'title' => 'Cloud-Native Architecture', 'desc' => 'Deploy on-premise or in the cloud. Scalable microservices architecture with 99.9% uptime SLA.'],
    ];
    foreach ($features as $f):
    ?>
    <div class="col-lg-4 col-md-6">
        <div class="feature-card">
            <div class="feature-icon" style="background:<?= $f['color'] ?>15;color:<?= $f['color'] ?>;">
                <i class="fas <?= $f['icon'] ?>"></i>
            </div>
            <h5 class="fw-bold mb-2"><?= e($f['title']) ?></h5>
            <p class="text-muted small mb-0"><?= e($f['desc']) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Product Screenshots -->
<div class="text-center mb-3">
    <h2 class="section-title">Product Screenshots</h2>
    <p class="section-subtitle">See the RBI Engineering Suite in action</p>
</div>

<div class="row g-4 mb-5">
    <?php
    $screens = [
        ['name' => 'Risk Matrix Dashboard', 'color' => '#1a237e'],
        ['name' => 'Asset Hierarchy View', 'color' => '#1565c0'],
        ['name' => 'Inspection Planning Calendar', 'color' => '#0d47a1'],
        ['name' => 'Analytics & Reporting', 'color' => '#283593'],
        ['name' => 'Mobile Field App', 'color' => '#1b5e20'],
        ['name' => 'IoT Sensor Dashboard', 'color' => '#4a148c'],
    ];
    foreach ($screens as $s):
    ?>
    <div class="col-lg-4 col-md-6">
        <div class="screenshot-placeholder" style="background:<?= $s['color'] ?>;color:rgba(255,255,255,0.7);">
            <div>
                <i class="fas fa-desktop fa-2x mb-2 d-block"></i>
                <?= e($s['name']) ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Testimonials -->
<div class="text-center mb-3">
    <h2 class="section-title">What Our Customers Say</h2>
    <p class="section-subtitle">Trusted by leading refineries and chemical plants worldwide</p>
</div>

<div class="row g-4 mb-5">
    <?php
    $testimonials = [
        ['text' => 'Implementing RBI Engineering Suite reduced our inspection costs by 38% while simultaneously improving our safety record. The predictive analytics module identified three critical equipment items that our traditional program had under-inspected.', 'author' => 'John Richardson', 'role' => 'VP of Operations, Gulf Coast Refining', 'avatar' => 'JR'],
        ['text' => 'The integration with our existing SAP PM system was seamless. Our inspectors love the mobile app - it has eliminated paper forms and reduced data entry errors by 95%. The ROI was realized within 8 months.', 'author' => 'Dr. Lisa Chang', 'role' => 'Chief Integrity Engineer, Pacific Petrochemicals', 'avatar' => 'LC'],
        ['text' => 'After evaluating five RBI software packages, we chose RBI Engineering Suite for its comprehensive API 581 implementation and superior analytics capabilities. It has become the backbone of our mechanical integrity program.', 'author' => 'Mark Stevens', 'role' => 'Refinery Manager, Midwest Processing Corp', 'avatar' => 'MS'],
        ['text' => 'The training module built into the platform allowed us to onboard our entire inspection team in two weeks. The certification tracking ensures all our inspectors maintain their qualifications. Outstanding product.', 'author' => 'Sarah O\'Connor', 'role' => 'Chief Inspector, Atlantic Energy Partners', 'avatar' => 'SO'],
    ];
    foreach ($testimonials as $t):
    ?>
    <div class="col-lg-6 col-md-6">
        <div class="testimonial-card">
            <p class="testimonial-text">"<?= e($t['text']) ?>"</p>
            <div class="d-flex align-items-center gap-3">
                <div style="width:48px;height:48px;border-radius:50%;background:#1a237e;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;"><?= $t['avatar'] ?></div>
                <div>
                    <div class="testimonial-author"><?= e($t['author']) ?></div>
                    <div class="testimonial-role"><?= e($t['role']) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ROI Calculator -->
<div class="text-center mb-3">
    <h2 class="section-title">ROI Calculator</h2>
    <p class="section-subtitle">Estimate your potential savings with RBI Engineering Suite</p>
</div>

<div class="roi-calculator mb-5">
    <div class="row g-4">
        <div class="col-lg-5">
            <h5 class="fw-bold mb-4"><i class="fas fa-calculator me-2 text-primary"></i>Enter Your Parameters</h5>
            <div class="mb-3">
                <label class="form-label fw-semibold">Number of Inspectable Assets</label>
                <input type="number" class="form-control form-control-lg" id="roiAssets" value="500" oninput="calculateROI()">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Current Annual Inspection Cost ($)</label>
                <input type="number" class="form-control form-control-lg" id="roiCost" value="750000" oninput="calculateROI()">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Annual Unplanned Failures</label>
                <input type="number" class="form-control form-control-lg" id="roiFailures" value="3" oninput="calculateROI()">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Average Cost per Failure ($)</label>
                <input type="number" class="form-control form-control-lg" id="roiFailCost" value="250000" oninput="calculateROI()">
            </div>
        </div>
        <div class="col-lg-7">
            <h5 class="fw-bold mb-4"><i class="fas fa-chart-bar me-2 text-success"></i>Projected Results with RBI</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card p-3 text-center">
                        <div class="small text-muted text-uppercase fw-semibold">Inspection Cost Savings</div>
                        <div class="fs-3 fw-bold text-success" id="roiInspSavings">$262,500</div>
                        <small class="text-muted">35% average reduction</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3 text-center">
                        <div class="small text-muted text-uppercase fw-semibold">Failure Prevention Savings</div>
                        <div class="fs-3 fw-bold text-success" id="roiFailSavings">$375,000</div>
                        <small class="text-muted">50% fewer unplanned failures</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3 text-center">
                        <div class="small text-muted text-uppercase fw-semibold">Total Annual Savings</div>
                        <div class="fs-3 fw-bold text-primary" id="roiTotalSavings">$637,500</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3 text-center">
                        <div class="small text-muted text-uppercase fw-semibold">ROI (First Year)</div>
                        <div class="fs-3 fw-bold text-warning" id="roiPercent">710%</div>
                        <small class="text-muted">Payback in &lt; 3 months</small>
                    </div>
                </div>
            </div>
            <div class="card mt-3 p-3">
                <canvas id="roiChart" height="150"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Pricing -->
<div class="text-center mb-3">
    <h2 class="section-title">Flexible Pricing</h2>
    <p class="section-subtitle">Choose the plan that fits your organization</p>
</div>

<div class="row g-4 mb-5">
    <div class="col-lg-4">
        <div class="pricing-card">
            <h5 class="fw-bold text-muted">Starter</h5>
            <div class="price mb-1">$2,500<small>/month</small></div>
            <p class="text-muted small mb-4">Up to 500 assets</p>
            <div class="text-start">
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>API 580/581 Risk Engine</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>Asset Management</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>Inspection Planning</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>Standard Analytics</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>5 User Licenses</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>Email Support</div>
                <div class="pricing-feature text-muted"><i class="fas fa-times text-muted me-2"></i>Predictive ML</div>
                <div class="pricing-feature text-muted"><i class="fas fa-times text-muted me-2"></i>IoT Integration</div>
            </div>
            <button class="btn btn-outline-primary w-100 mt-4">Get Started</button>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="pricing-card featured">
            <h5 class="fw-bold text-primary">Professional</h5>
            <div class="price mb-1">$7,500<small>/month</small></div>
            <p class="text-muted small mb-4">Up to 2,000 assets</p>
            <div class="text-start">
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>Everything in Starter</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>Advanced Analytics</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>Predictive ML Engine</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>SAP/Maximo Integration</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>Mobile PWA</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>20 User Licenses</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>Priority Support</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>Training Module</div>
            </div>
            <button class="btn btn-primary w-100 mt-4">Get Started</button>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="pricing-card">
            <h5 class="fw-bold text-muted">Enterprise</h5>
            <div class="price mb-1">Custom</div>
            <p class="text-muted small mb-4">Unlimited assets</p>
            <div class="text-start">
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>Everything in Professional</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>Unlimited Users</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>IoT Sensor Integration</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>Digital Twin Support</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>Custom Integrations</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>On-Premise Deployment</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>24/7 Dedicated Support</div>
                <div class="pricing-feature"><i class="fas fa-check text-success me-2"></i>SLA Guarantee</div>
            </div>
            <button class="btn btn-outline-primary w-100 mt-4" data-bs-toggle="modal" data-bs-target="#demoModal">Contact Sales</button>
        </div>
    </div>
</div>

<!-- Partners -->
<div class="text-center mb-3">
    <h2 class="section-title">Technology Partners</h2>
    <p class="section-subtitle">Integrated with the systems you already use</p>
</div>

<div class="d-flex flex-wrap justify-content-center gap-4 mb-5">
    <?php foreach (['SAP', 'IBM Maximo', 'OSIsoft PI', 'AWS', 'Azure', 'Hexagon', 'Bentley', 'AVEVA'] as $partner): ?>
    <div class="partner-logo"><?= e($partner) ?></div>
    <?php endforeach; ?>
</div>

<!-- Comparison Link -->
<div class="card mb-5">
    <div class="card-body text-center py-5">
        <i class="fas fa-balance-scale fa-3x text-primary mb-3" style="opacity:0.3;"></i>
        <h4 class="fw-bold">See How We Compare</h4>
        <p class="text-muted mb-3">Detailed feature-by-feature comparison against DNV Synergi, Hexagon APM, Cenosco, and more.</p>
        <a href="<?= BASE_URL ?>/admin/marketing/comparison.php" class="btn btn-primary btn-lg">
            <i class="fas fa-balance-scale me-1"></i>View Comparison
        </a>
    </div>
</div>

<!-- Contact -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-envelope me-2"></i>Contact Information</div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="fw-semibold">Sales Inquiries</div>
                    <div class="text-muted">sales@rbi-engineering.com</div>
                    <div class="text-muted">+1 (800) 555-RBI1</div>
                </div>
                <div class="mb-3">
                    <div class="fw-semibold">Technical Support</div>
                    <div class="text-muted">support@rbi-engineering.com</div>
                    <div class="text-muted">+1 (800) 555-RBI2</div>
                </div>
                <div>
                    <div class="fw-semibold">Headquarters</div>
                    <div class="text-muted">1234 Industrial Parkway, Suite 500<br>Houston, TX 77001, USA</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-download me-2"></i>Resources</div>
            <div class="card-body">
                <a href="<?= BASE_URL ?>/admin/marketing/brochure.php" class="d-flex align-items-center gap-3 p-3 rounded-3 mb-2" style="background:#f8fafc;text-decoration:none;color:inherit;">
                    <i class="fas fa-file-pdf fa-2x text-danger"></i>
                    <div>
                        <div class="fw-semibold">Product Brochure</div>
                        <small class="text-muted">Download printable PDF brochure</small>
                    </div>
                </a>
                <a href="<?= BASE_URL ?>/admin/marketing/comparison.php" class="d-flex align-items-center gap-3 p-3 rounded-3 mb-2" style="background:#f8fafc;text-decoration:none;color:inherit;">
                    <i class="fas fa-table fa-2x text-primary"></i>
                    <div>
                        <div class="fw-semibold">Competitive Comparison</div>
                        <small class="text-muted">Feature comparison matrix</small>
                    </div>
                </a>
                <a href="<?= BASE_URL ?>/admin/training/" class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:#f8fafc;text-decoration:none;color:inherit;">
                    <i class="fas fa-graduation-cap fa-2x text-success"></i>
                    <div>
                        <div class="fw-semibold">Training Center</div>
                        <small class="text-muted">RBI courses and certifications</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Demo Request Modal -->
<div class="modal fade" id="demoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-play-circle me-2"></i>Request a Demo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6"><label class="form-label">First Name</label><input type="text" class="form-control" placeholder="John"></div>
                    <div class="col-6"><label class="form-label">Last Name</label><input type="text" class="form-control" placeholder="Smith"></div>
                    <div class="col-12"><label class="form-label">Work Email</label><input type="email" class="form-control" placeholder="john@company.com"></div>
                    <div class="col-12"><label class="form-label">Company</label><input type="text" class="form-control" placeholder="Acme Refining"></div>
                    <div class="col-6"><label class="form-label">Job Title</label><input type="text" class="form-control" placeholder="Integrity Engineer"></div>
                    <div class="col-6"><label class="form-label">Phone</label><input type="tel" class="form-control" placeholder="+1 555-1234"></div>
                    <div class="col-12">
                        <label class="form-label">Number of Inspectable Assets</label>
                        <select class="form-select">
                            <option>Less than 500</option>
                            <option>500 - 2,000</option>
                            <option>2,000 - 5,000</option>
                            <option>5,000 - 10,000</option>
                            <option>More than 10,000</option>
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label">Message (optional)</label><textarea class="form-control" rows="3" placeholder="Tell us about your needs..."></textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100" onclick="alert('Demo request submitted! Our team will contact you within 24 hours.'); bootstrap.Modal.getInstance(document.getElementById('demoModal')).hide();">
                    <i class="fas fa-paper-plane me-1"></i>Submit Request
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    calculateROI();
    initROIChart();
});

let roiChart = null;

function calculateROI() {
    const assets = parseInt(document.getElementById('roiAssets').value) || 0;
    const cost = parseInt(document.getElementById('roiCost').value) || 0;
    const failures = parseInt(document.getElementById('roiFailures').value) || 0;
    const failCost = parseInt(document.getElementById('roiFailCost').value) || 0;

    const inspSavings = Math.round(cost * 0.35);
    const failSavings = Math.round(failures * failCost * 0.50);
    const totalSavings = inspSavings + failSavings;

    // Estimate license cost based on assets
    let licenseCost = assets <= 500 ? 30000 : (assets <= 2000 ? 90000 : 150000);
    const roi = licenseCost > 0 ? Math.round(((totalSavings - licenseCost) / licenseCost) * 100) : 0;

    document.getElementById('roiInspSavings').textContent = '$' + inspSavings.toLocaleString();
    document.getElementById('roiFailSavings').textContent = '$' + failSavings.toLocaleString();
    document.getElementById('roiTotalSavings').textContent = '$' + totalSavings.toLocaleString();
    document.getElementById('roiPercent').textContent = roi + '%';

    // Update chart
    if (roiChart) {
        roiChart.data.datasets[0].data = [cost, cost - inspSavings];
        roiChart.data.datasets[1].data = [failures * failCost, (failures * failCost) - failSavings];
        roiChart.update();
    }
}

function initROIChart() {
    const ctx = document.getElementById('roiChart');
    if (!ctx) return;

    const cost = parseInt(document.getElementById('roiCost').value) || 0;
    const failures = parseInt(document.getElementById('roiFailures').value) || 0;
    const failCost = parseInt(document.getElementById('roiFailCost').value) || 0;

    roiChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Current', 'With RBI'],
            datasets: [
                {
                    label: 'Inspection Costs',
                    data: [cost, Math.round(cost * 0.65)],
                    backgroundColor: ['#3b82f6', '#93c5fd']
                },
                {
                    label: 'Failure Costs',
                    data: [failures * failCost, Math.round(failures * failCost * 0.5)],
                    backgroundColor: ['#ef4444', '#fca5a5']
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                x: { stacked: true },
                y: { stacked: true, ticks: { callback: v => '$' + (v/1000).toFixed(0) + 'K' } }
            }
        }
    });
}
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
