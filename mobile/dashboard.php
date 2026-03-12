<?php
/**
 * Mobile-Optimized Dashboard - RBI Engineering Suite
 * Simplified layout for field use with swipeable KPI cards,
 * quick actions, alerts feed, and bottom navigation
 */
require_once dirname(__DIR__) . '/config/app.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

$currentUser = $auth->getCurrentUser();
$pageTitle = 'Dashboard';

// Fetch dashboard data
try {
    $db = Database::getInstance()->getConnection();

    // KPI data
    $totalAssets = $db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
    $highRiskCount = $db->query("SELECT COUNT(*) FROM risk_assessments WHERE risk_level IN ('Very High', 'High')")->fetchColumn();
    $overdueInspections = $db->query("SELECT COUNT(*) FROM inspection_tasks WHERE status = 'Overdue' OR (due_date < CURDATE() AND status != 'Completed')")->fetchColumn();
    $completedThisMonth = $db->query("SELECT COUNT(*) FROM inspection_tasks WHERE status = 'Completed' AND MONTH(completed_date) = MONTH(CURDATE()) AND YEAR(completed_date) = YEAR(CURDATE())")->fetchColumn();

    // Recent alerts
    $alerts = $db->query("
        SELECT ra.*, a.tag_number, a.description
        FROM risk_assessments ra
        JOIN assets a ON ra.asset_id = a.id
        WHERE ra.risk_level IN ('Very High', 'High')
        ORDER BY ra.assessment_date DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Upcoming tasks
    $tasks = $db->query("
        SELECT it.*, a.tag_number
        FROM inspection_tasks it
        JOIN assets a ON it.asset_id = a.id
        WHERE it.status != 'Completed'
        ORDER BY it.due_date ASC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $totalAssets = $highRiskCount = $overdueInspections = $completedThisMonth = 0;
    $alerts = $tasks = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/rbi/manifest.json">
    <link rel="apple-touch-icon" href="/rbi/static/icons/apple-touch-icon.png">
    <title>Dashboard | RBI Engineering Suite</title>

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
            padding-top: env(safe-area-inset-top, 0px);
        }

        .mobile-header {
            background: linear-gradient(135deg, #1a237e, #3b82f6);
            color: white;
            padding: 20px 16px 60px;
            position: relative;
        }

        .mobile-header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .mobile-header p {
            font-size: 0.8rem;
            opacity: 0.8;
            margin: 0;
        }

        .mobile-header .header-actions {
            position: absolute;
            top: 20px;
            right: 16px;
            display: flex;
            gap: 8px;
        }

        .mobile-header .header-actions button {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: rgba(255,255,255,0.15);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mobile-body {
            padding: 0 16px 100px;
            margin-top: -44px;
            position: relative;
        }

        /* KPI Carousel */
        .kpi-carousel {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            padding: 4px;
            margin: 0 -16px;
            padding-left: 16px;
            padding-right: 16px;
        }

        .kpi-carousel::-webkit-scrollbar { display: none; }

        .kpi-card {
            flex: 0 0 calc(50% - 6px);
            scroll-snap-align: start;
            background: white;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .kpi-card .kpi-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            margin-bottom: 12px;
        }

        .kpi-card .kpi-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1;
        }

        .kpi-card .kpi-label {
            font-size: 0.7rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 20px;
        }

        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: white;
            border: none;
            border-radius: 16px;
            padding: 16px 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            text-decoration: none;
            color: #1e293b;
            transition: transform 0.15s;
            -webkit-tap-highlight-color: transparent;
        }

        .quick-action-btn:active { transform: scale(0.95); }

        .quick-action-btn i {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: white;
        }

        .quick-action-btn span {
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Section headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 24px 0 12px;
        }

        .section-header h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .section-header a {
            font-size: 0.8rem;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        /* Alert card */
        .alert-card {
            background: white;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            text-decoration: none;
            color: inherit;
        }

        .alert-card .alert-indicator {
            width: 4px;
            height: 36px;
            border-radius: 2px;
            flex-shrink: 0;
        }

        .alert-card .alert-info {
            flex: 1;
            min-width: 0;
        }

        .alert-card .alert-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .alert-card .alert-desc {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        /* Task item */
        .task-item {
            background: white;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .task-item .task-date {
            text-align: center;
            flex-shrink: 0;
            min-width: 44px;
        }

        .task-item .task-date .day {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1;
        }

        .task-item .task-date .month {
            font-size: 0.65rem;
            color: #94a3b8;
            text-transform: uppercase;
        }

        .task-item .task-info {
            flex: 1;
            min-width: 0;
        }

        .task-item .task-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e293b;
        }

        .task-item .task-meta {
            font-size: 0.75rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>

<!-- Mobile Header -->
<div class="mobile-header" id="mobileHeader">
    <div class="header-actions">
        <button onclick="window.location.href='/rbi/dashboard.php'" title="Desktop View">
            <i class="fas fa-desktop"></i>
        </button>
        <button onclick="window.location.reload()" title="Refresh">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
    <h1>Good <?= date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening') ?>, <?= htmlspecialchars($currentUser['first_name'] ?? 'User') ?></h1>
    <p><?= date('l, F j, Y') ?></p>
</div>

<!-- Mobile Body -->
<div class="mobile-body">
    <!-- KPI Cards Carousel -->
    <div class="kpi-carousel">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#eff6ff;color:#3b82f6;">
                <i class="fas fa-industry"></i>
            </div>
            <div class="kpi-value"><?= number_format($totalAssets) ?></div>
            <div class="kpi-label">Total Assets</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fef2f2;color:#ef4444;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="kpi-value"><?= number_format($highRiskCount) ?></div>
            <div class="kpi-label">High Risk</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fff7ed;color:#f97316;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="kpi-value"><?= number_format($overdueInspections) ?></div>
            <div class="kpi-label">Overdue</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#f0fdf4;color:#22c55e;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="kpi-value"><?= number_format($completedThisMonth) ?></div>
            <div class="kpi-label">Completed</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="/rbi/mobile/inspection-form.php" class="quick-action-btn">
            <i style="background:#3b82f6;"><span class="fas fa-clipboard-check"></span></i>
            <span>Inspection</span>
        </a>
        <a href="/rbi/mobile/readings.php" class="quick-action-btn">
            <i style="background:#22c55e;"><span class="fas fa-ruler"></span></i>
            <span>Readings</span>
        </a>
        <a href="/rbi/risk/matrix.php" class="quick-action-btn">
            <i style="background:#f59e0b;"><span class="fas fa-table-cells"></span></i>
            <span>Risk Matrix</span>
        </a>
    </div>

    <!-- Recent Alerts -->
    <div class="section-header">
        <h2><i class="fas fa-bell me-2 text-danger"></i>Risk Alerts</h2>
        <a href="/rbi/risk/assessments.php">View All</a>
    </div>

    <?php if (empty($alerts)): ?>
        <div class="alert-card">
            <div class="alert-info text-center py-2">
                <span class="text-muted"><i class="fas fa-check-circle text-success me-2"></i>No high-risk alerts</span>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($alerts as $alert): ?>
            <a href="/rbi/assets/view.php?id=<?= $alert['asset_id'] ?>" class="alert-card">
                <div class="alert-indicator" style="background: <?= $alert['risk_level'] === 'Very High' ? '#721c24' : '#dc3545' ?>;"></div>
                <div class="alert-info">
                    <div class="alert-title"><?= htmlspecialchars($alert['tag_number']) ?></div>
                    <div class="alert-desc"><?= htmlspecialchars($alert['risk_level']) ?> Risk - <?= htmlspecialchars(substr($alert['description'] ?? '', 0, 50)) ?></div>
                </div>
                <i class="fas fa-chevron-right text-muted" style="font-size:0.75rem;"></i>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Upcoming Tasks -->
    <div class="section-header">
        <h2><i class="fas fa-calendar me-2 text-primary"></i>Upcoming Tasks</h2>
        <a href="/rbi/inspections/tasks.php">View All</a>
    </div>

    <?php if (empty($tasks)): ?>
        <div class="task-item">
            <div class="task-info text-center py-2">
                <span class="text-muted">No upcoming tasks</span>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($tasks as $task): ?>
            <?php
                $dueDate = !empty($task['due_date']) ? new DateTime($task['due_date']) : null;
                $isOverdue = $dueDate && $dueDate < new DateTime();
            ?>
            <div class="task-item">
                <div class="task-date">
                    <?php if ($dueDate): ?>
                        <div class="day" style="<?= $isOverdue ? 'color:#ef4444;' : '' ?>"><?= $dueDate->format('d') ?></div>
                        <div class="month"><?= $dueDate->format('M') ?></div>
                    <?php else: ?>
                        <div class="day">--</div>
                        <div class="month">TBD</div>
                    <?php endif; ?>
                </div>
                <div class="task-info">
                    <div class="task-title"><?= htmlspecialchars($task['tag_number'] ?? 'Asset') ?></div>
                    <div class="task-meta">
                        <?= htmlspecialchars($task['task_type'] ?? 'Inspection') ?>
                        <?php if ($isOverdue): ?>
                            <span class="badge bg-danger ms-1" style="font-size:0.6rem;">Overdue</span>
                        <?php endif; ?>
                    </div>
                </div>
                <i class="fas fa-chevron-right text-muted" style="font-size:0.75rem;"></i>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Bottom Navigation -->
<nav class="bottom-nav" style="display:flex;">
    <a href="/rbi/mobile/dashboard.php" class="bottom-nav-item active">
        <i class="fas fa-gauge-high"></i>
        <span>Dashboard</span>
    </a>
    <a href="/rbi/assets/index.php" class="bottom-nav-item">
        <i class="fas fa-industry"></i>
        <span>Assets</span>
    </a>
    <a href="/rbi/risk/matrix.php" class="bottom-nav-item">
        <i class="fas fa-table-cells"></i>
        <span>Risk</span>
    </a>
    <a href="/rbi/inspections/schedule.php" class="bottom-nav-item">
        <i class="fas fa-clipboard-check"></i>
        <span>Inspect</span>
        <?php if ($overdueInspections > 0): ?>
            <span class="bottom-nav-badge"><?= $overdueInspections > 9 ? '9+' : $overdueInspections ?></span>
        <?php endif; ?>
    </a>
    <a href="#" class="bottom-nav-item" id="moreMenuBtn">
        <i class="fas fa-ellipsis-h"></i>
        <span>More</span>
    </a>
</nav>

<!-- Offline Bar -->
<div class="offline-bar" id="offlineBar">
    <i class="fas fa-wifi-slash"></i>
    <span>You're offline</span>
    <span class="sync-count"></span>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/rbi/static/js/app.js"></script>
<script src="/rbi/static/js/mobile.js"></script>
<script src="/rbi/static/js/pwa.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pull-to-refresh via custom header
    // More menu bottom sheet
    document.getElementById('moreMenuBtn')?.addEventListener('click', function(e) {
        e.preventDefault();
        RBI_MOBILE.createBottomSheet({
            title: 'More',
            content: `
                <div class="list-group list-group-flush">
                    <a href="/rbi/analytics/remaining-life.php" class="list-group-item list-group-item-action py-3">
                        <i class="fas fa-hourglass-half me-3 text-muted"></i>Remaining Life Analysis
                    </a>
                    <a href="/rbi/analytics/corrosion-rates.php" class="list-group-item list-group-item-action py-3">
                        <i class="fas fa-chart-line me-3 text-muted"></i>Corrosion Rates
                    </a>
                    <a href="/rbi/reports/index.php" class="list-group-item list-group-item-action py-3">
                        <i class="fas fa-file-pdf me-3 text-muted"></i>Reports
                    </a>
                    <a href="/rbi/damage-mechanisms/index.php" class="list-group-item list-group-item-action py-3">
                        <i class="fas fa-shield-virus me-3 text-muted"></i>Damage Mechanisms
                    </a>
                    <a href="/rbi/admin/settings.php" class="list-group-item list-group-item-action py-3">
                        <i class="fas fa-cog me-3 text-muted"></i>Settings
                    </a>
                    <a href="/rbi/dashboard.php" class="list-group-item list-group-item-action py-3">
                        <i class="fas fa-desktop me-3 text-muted"></i>Desktop View
                    </a>
                    <a href="/rbi/logout.php" class="list-group-item list-group-item-action py-3 text-danger">
                        <i class="fas fa-sign-out-alt me-3"></i>Logout
                    </a>
                </div>
            `
        });
    });

    // Auto-refresh KPIs
    RBI_APP.startDashboardRefresh(function(data) {
        // Update KPI values if data is returned
        if (data && data.stats) {
            const values = document.querySelectorAll('.kpi-value');
            if (data.stats.total_assets !== undefined && values[0]) values[0].textContent = data.stats.total_assets;
            if (data.stats.high_risk !== undefined && values[1]) values[1].textContent = data.stats.high_risk;
            if (data.stats.overdue !== undefined && values[2]) values[2].textContent = data.stats.overdue;
            if (data.stats.completed !== undefined && values[3]) values[3].textContent = data.stats.completed;
        }
    });
});
</script>

</body>
</html>
