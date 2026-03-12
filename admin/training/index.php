<?php
/**
 * Training Module - RBI Engineering Suite
 * Training dashboard with course catalog, progress tracking, and certification
 */
$pageTitle = 'Training Center';
$pageSection = 'Administration';
$currentModule = 'admin';

require_once dirname(dirname(__DIR__)) . '/config/app.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    flash('Please log in to continue.', 'warning');
    redirect(BASE_URL . '/login.php');
}

// Course data
$courses = [
    1 => [
        'title' => 'Introduction to RBI',
        'description' => 'Learn the fundamentals of Risk-Based Inspection methodology, its history, benefits, and key terminology.',
        'category' => 'RBI Fundamentals',
        'difficulty' => 'Beginner',
        'duration' => '2 hours',
        'icon' => 'fa-book-open',
        'color' => 'primary',
        'lessons' => 5,
        'quizQuestions' => 10,
    ],
    2 => [
        'title' => 'API 580 - Risk-Based Inspection',
        'description' => 'Comprehensive coverage of API 580 standard including RBI program development, data collection, and risk management.',
        'category' => 'API 580/581',
        'difficulty' => 'Intermediate',
        'duration' => '4 hours',
        'icon' => 'fa-file-contract',
        'color' => 'warning',
        'lessons' => 8,
        'quizQuestions' => 15,
    ],
    3 => [
        'title' => 'API 581 - RBI Methodology',
        'description' => 'Advanced study of API 581 quantitative methodology covering damage factors, consequence analysis, and financial risk.',
        'category' => 'API 580/581',
        'difficulty' => 'Advanced',
        'duration' => '6 hours',
        'icon' => 'fa-calculator',
        'color' => 'danger',
        'lessons' => 8,
        'quizQuestions' => 20,
    ],
    4 => [
        'title' => 'Damage Mechanism Identification',
        'description' => 'Learn to identify and assess corrosion mechanisms, stress corrosion cracking, high-temperature damage, and more.',
        'category' => 'Damage Mechanisms',
        'difficulty' => 'Intermediate',
        'duration' => '3 hours',
        'icon' => 'fa-bolt',
        'color' => 'orange',
        'lessons' => 6,
        'quizQuestions' => 15,
    ],
    5 => [
        'title' => 'Inspection Techniques',
        'description' => 'Overview of NDE methods: UT, RT, MT, PT, VT, and advanced techniques including TOFD, Phased Array, and GWT.',
        'category' => 'Inspection Techniques',
        'difficulty' => 'Intermediate',
        'duration' => '3 hours',
        'icon' => 'fa-search',
        'color' => 'info',
        'lessons' => 6,
        'quizQuestions' => 15,
    ],
    6 => [
        'title' => 'Using RBI Engineering Suite',
        'description' => 'Hands-on guide to the RBI Engineering Suite: navigation, asset management, risk assessments, and reporting.',
        'category' => 'Software Usage',
        'difficulty' => 'Beginner',
        'duration' => '2 hours',
        'icon' => 'fa-laptop',
        'color' => 'success',
        'lessons' => 6,
        'quizQuestions' => 10,
    ],
    7 => [
        'title' => 'Corrosion Rate Analysis',
        'description' => 'Advanced techniques for corrosion rate analysis, remaining life calculations, and predictive analytics.',
        'category' => 'Damage Mechanisms',
        'difficulty' => 'Advanced',
        'duration' => '3 hours',
        'icon' => 'fa-chart-line',
        'color' => 'purple',
        'lessons' => 5,
        'quizQuestions' => 12,
    ],
    8 => [
        'title' => 'Safety & Compliance',
        'description' => 'OSHA PSM, EPA RMP, RAGAGEP standards, documentation requirements, and audit preparation.',
        'category' => 'Safety & Compliance',
        'difficulty' => 'Beginner',
        'duration' => '2 hours',
        'icon' => 'fa-hard-hat',
        'color' => 'secondary',
        'lessons' => 5,
        'quizQuestions' => 10,
    ],
];

$categories = ['All', 'RBI Fundamentals', 'API 580/581', 'Damage Mechanisms', 'Inspection Techniques', 'Software Usage', 'Safety & Compliance'];

include INCLUDES_PATH . '/header.php';
?>

<style>
.training-hero {
    background: linear-gradient(135deg, #1a237e 0%, #3f51b5 50%, #1565c0 100%);
    border-radius: 16px;
    padding: 40px;
    color: #fff;
    margin-bottom: 32px;
}
.training-hero h1 { font-size: 2rem; font-weight: 700; margin-bottom: 8px; }
.training-hero p { opacity: 0.85; font-size: 1.05rem; }
.training-stat {
    text-align: center;
    padding: 12px;
}
.training-stat .value { font-size: 1.8rem; font-weight: 700; }
.training-stat .label { font-size: 0.75rem; opacity: 0.7; text-transform: uppercase; letter-spacing: 1px; }

.course-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: all 0.3s;
    overflow: hidden;
    height: 100%;
}
.course-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}
.course-card-header {
    padding: 24px 24px 16px;
}
.course-icon {
    width: 56px; height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin-bottom: 16px;
}
.course-card-body { padding: 0 24px 24px; }
.course-meta { display: flex; gap: 12px; margin-bottom: 12px; }
.course-meta span { font-size: 0.78rem; color: #64748b; }

.difficulty-badge {
    font-size: 0.7rem;
    padding: 3px 10px;
    border-radius: 20px;
    font-weight: 600;
}
.difficulty-beginner { background: #d1fae5; color: #065f46; }
.difficulty-intermediate { background: #fef3c7; color: #92400e; }
.difficulty-advanced { background: #fce7f3; color: #9d174d; }

.progress-thin {
    height: 6px;
    border-radius: 3px;
    background: #e2e8f0;
    margin: 12px 0;
}

.filter-btn {
    border: 1px solid #e2e8f0;
    background: #fff;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.82rem;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
}
.filter-btn:hover, .filter-btn.active {
    background: #1a237e;
    color: #fff;
    border-color: #1a237e;
}

.cert-card {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 2px solid #86efac;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
}
</style>

<!-- Training Hero -->
<div class="training-hero">
    <div class="row align-items-center">
        <div class="col-lg-7">
            <h1><i class="fas fa-graduation-cap me-2"></i>Training Center</h1>
            <p>Master Risk-Based Inspection methodology through comprehensive courses covering API 580/581, damage mechanisms, inspection techniques, and software training.</p>
            <a href="#courseGrid" class="btn btn-light btn-lg mt-2"><i class="fas fa-play me-2"></i>Browse Courses</a>
        </div>
        <div class="col-lg-5">
            <div class="row g-0 mt-3 mt-lg-0">
                <div class="col-3 training-stat">
                    <div class="value" id="totalCourses">8</div>
                    <div class="label">Courses</div>
                </div>
                <div class="col-3 training-stat">
                    <div class="value">50</div>
                    <div class="label">Lessons</div>
                </div>
                <div class="col-3 training-stat">
                    <div class="value" id="completedCourses">0</div>
                    <div class="label">Completed</div>
                </div>
                <div class="col-3 training-stat">
                    <div class="value" id="overallProgress">0%</div>
                    <div class="label">Progress</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Filters -->
<div class="d-flex flex-wrap gap-2 mb-4" id="categoryFilters">
    <?php foreach ($categories as $cat): ?>
    <button class="filter-btn <?= $cat === 'All' ? 'active' : '' ?>" onclick="filterCourses('<?= e($cat) ?>', this)">
        <?= e($cat) ?>
    </button>
    <?php endforeach; ?>
</div>

<!-- Course Grid -->
<div class="row g-4 mb-4" id="courseGrid">
    <?php foreach ($courses as $id => $course): ?>
    <div class="col-lg-4 col-md-6 course-item" data-category="<?= e($course['category']) ?>">
        <div class="card course-card">
            <div class="course-card-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="course-icon bg-<?= $course['color'] ?> bg-opacity-10 text-<?= $course['color'] ?>">
                        <i class="fas <?= $course['icon'] ?>"></i>
                    </div>
                    <span class="difficulty-badge difficulty-<?= strtolower($course['difficulty']) ?>">
                        <?= e($course['difficulty']) ?>
                    </span>
                </div>
                <h5 class="fw-bold mt-2 mb-1"><?= e($course['title']) ?></h5>
                <p class="text-muted small mb-0"><?= e($course['description']) ?></p>
            </div>
            <div class="course-card-body">
                <div class="course-meta">
                    <span><i class="fas fa-clock me-1"></i><?= e($course['duration']) ?></span>
                    <span><i class="fas fa-book me-1"></i><?= $course['lessons'] ?> Lessons</span>
                    <span><i class="fas fa-question-circle me-1"></i><?= $course['quizQuestions'] ?> Quiz Q's</span>
                </div>
                <div class="progress-thin">
                    <div class="progress-bar bg-<?= $course['color'] ?>" role="progressbar"
                         style="width: 0%; height: 100%; border-radius: 3px; transition: width 0.5s;"
                         id="progress-<?= $id ?>"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted" id="progressText-<?= $id ?>">0% Complete</small>
                    <a href="<?= BASE_URL ?>/admin/training/course.php?id=<?= $id ?>" class="btn btn-sm btn-<?= $course['color'] ?>" id="courseBtn-<?= $id ?>">
                        <i class="fas fa-play me-1"></i>Start
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Certification Section -->
<div class="card mb-4">
    <div class="card-header"><i class="fas fa-certificate me-2 text-warning"></i>Certifications</div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="cert-card">
                    <i class="fas fa-award fa-3x text-success mb-3"></i>
                    <h6 class="fw-bold">RBI Fundamentals</h6>
                    <p class="small text-muted mb-2">Complete Course 1 & 6</p>
                    <div id="cert-fundamentals-status">
                        <span class="badge bg-secondary">Not Earned</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="cert-card" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border-color:#93c5fd;">
                    <i class="fas fa-award fa-3x text-primary mb-3"></i>
                    <h6 class="fw-bold">API 580/581 Specialist</h6>
                    <p class="small text-muted mb-2">Complete Courses 2 & 3</p>
                    <div id="cert-api-status">
                        <span class="badge bg-secondary">Not Earned</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="cert-card" style="background:linear-gradient(135deg,#fefce8,#fef9c3);border-color:#fde047;">
                    <i class="fas fa-trophy fa-3x text-warning mb-3"></i>
                    <h6 class="fw-bold">RBI Master Certification</h6>
                    <p class="small text-muted mb-2">Complete all 8 courses</p>
                    <div id="cert-master-status">
                        <span class="badge bg-secondary">Not Earned</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    updateAllProgress();
});

function updateAllProgress() {
    let totalCompleted = 0;
    let totalLessons = 0;
    let completedCourses = 0;
    const courseCount = 8;

    const courseLessons = {1:5, 2:8, 3:8, 4:6, 5:6, 6:6, 7:5, 8:5};

    for (let id = 1; id <= courseCount; id++) {
        const total = courseLessons[id] + 1; // +1 for quiz
        let completed = 0;

        for (let l = 1; l <= courseLessons[id]; l++) {
            if (localStorage.getItem('rbi_course_' + id + '_lesson_' + l) === 'completed') {
                completed++;
            }
        }
        if (localStorage.getItem('rbi_course_' + id + '_quiz') === 'completed') {
            completed++;
        }

        totalCompleted += completed;
        totalLessons += total;

        const pct = Math.round((completed / total) * 100);
        const bar = document.getElementById('progress-' + id);
        const text = document.getElementById('progressText-' + id);
        const btn = document.getElementById('courseBtn-' + id);

        if (bar) bar.style.width = pct + '%';
        if (text) text.textContent = pct + '% Complete';

        if (btn) {
            if (pct === 100) {
                btn.innerHTML = '<i class="fas fa-check me-1"></i>Completed';
                completedCourses++;
            } else if (pct > 0) {
                btn.innerHTML = '<i class="fas fa-play me-1"></i>Continue';
            }
        }
    }

    const overallPct = totalLessons > 0 ? Math.round((totalCompleted / totalLessons) * 100) : 0;
    document.getElementById('completedCourses').textContent = completedCourses;
    document.getElementById('overallProgress').textContent = overallPct + '%';

    // Update certifications
    updateCertifications();
}

function updateCertifications() {
    const isComplete = (id) => localStorage.getItem('rbi_course_' + id + '_quiz') === 'completed';

    if (isComplete(1) && isComplete(6)) {
        document.getElementById('cert-fundamentals-status').innerHTML = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Earned</span><br><button class="btn btn-sm btn-outline-success mt-2" onclick="generateCertificate(\'RBI Fundamentals\')"><i class="fas fa-download me-1"></i>Download</button>';
    }
    if (isComplete(2) && isComplete(3)) {
        document.getElementById('cert-api-status').innerHTML = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Earned</span><br><button class="btn btn-sm btn-outline-primary mt-2" onclick="generateCertificate(\'API 580/581 Specialist\')"><i class="fas fa-download me-1"></i>Download</button>';
    }
    let allDone = true;
    for (let i = 1; i <= 8; i++) { if (!isComplete(i)) allDone = false; }
    if (allDone) {
        document.getElementById('cert-master-status').innerHTML = '<span class="badge bg-warning text-dark"><i class="fas fa-trophy me-1"></i>Earned</span><br><button class="btn btn-sm btn-outline-warning mt-2" onclick="generateCertificate(\'RBI Master Certification\')"><i class="fas fa-download me-1"></i>Download</button>';
    }
}

function generateCertificate(certName) {
    const userName = '<?= e($_SESSION['user_name'] ?? 'User') ?>';
    const dateStr = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const certWindow = window.open('', '_blank');
    certWindow.document.write(`<!DOCTYPE html>
<html><head><title>Certificate - ${certName}</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
    @media print { body { margin: 0; } .no-print { display: none; } }
    body { font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f1f5f9; margin: 0; }
    .certificate { width: 900px; background: #fff; border: 3px solid #1a237e; padding: 60px; text-align: center; position: relative; }
    .certificate::before { content: ''; position: absolute; top: 10px; left: 10px; right: 10px; bottom: 10px; border: 1px solid #c5cae9; }
    .cert-title { font-family: 'Playfair Display', serif; font-size: 2.5rem; color: #1a237e; margin-bottom: 8px; }
    .cert-subtitle { font-size: 1rem; color: #64748b; margin-bottom: 40px; letter-spacing: 3px; text-transform: uppercase; }
    .cert-name { font-family: 'Playfair Display', serif; font-size: 2rem; color: #1e293b; border-bottom: 2px solid #1a237e; display: inline-block; padding-bottom: 8px; margin: 20px 0; }
    .cert-course { font-size: 1.2rem; color: #3f51b5; margin: 16px 0; font-weight: 600; }
    .cert-date { color: #64748b; font-size: 0.9rem; margin-top: 30px; }
    .cert-seal { width: 80px; height: 80px; background: linear-gradient(135deg, #1a237e, #3f51b5); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 2rem; margin-top: 20px; }
    .cert-footer { display: flex; justify-content: space-around; margin-top: 40px; }
    .cert-sig { text-align: center; }
    .cert-sig-line { width: 200px; border-top: 1px solid #333; margin: 0 auto 8px; }
    .cert-sig-name { font-size: 0.85rem; color: #64748b; }
</style></head><body>
<div>
    <div class="certificate">
        <div style="font-size:3rem;color:#1a237e;margin-bottom:10px;">&#9733;</div>
        <div class="cert-title">Certificate of Completion</div>
        <div class="cert-subtitle">RBI Engineering Suite</div>
        <p style="color:#64748b;">This is to certify that</p>
        <div class="cert-name">${userName}</div>
        <p class="cert-course">has successfully completed<br>${certName}</p>
        <p style="color:#64748b;font-size:0.9rem;">demonstrating proficiency in Risk-Based Inspection methodology<br>in accordance with API 580/581 standards</p>
        <div class="cert-seal"><i class="fas fa-shield-alt" style="font-family:FontAwesome;">&#xf3ed;</i></div>
        <div class="cert-date">${dateStr}</div>
        <div class="cert-footer">
            <div class="cert-sig"><div class="cert-sig-line"></div><div class="cert-sig-name">Program Director</div></div>
            <div class="cert-sig"><div class="cert-sig-line"></div><div class="cert-sig-name">Chief Inspector</div></div>
        </div>
    </div>
    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" style="padding:12px 32px;background:#1a237e;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:1rem;">
            Print / Save as PDF
        </button>
    </div>
</div></body></html>`);
}

function filterCourses(category, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('.course-item').forEach(item => {
        if (category === 'All' || item.dataset.category === category) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
