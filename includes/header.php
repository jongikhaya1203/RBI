<?php
/**
 * Header - RBI Engineering Suite
 * Responsive layout with top navbar and dark sidebar
 */
if (!defined('APP_NAME')) {
    require_once dirname(__DIR__) . '/config/app.php';
}

$auth = new Auth();
$currentUser = $auth->getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Risk-Based Inspection Engineering Platform">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/rbi/manifest.json">
    <link rel="apple-touch-icon" href="/rbi/static/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/rbi/static/icons/favicon.png">
    <title><?= sanitize($pageTitle ?? 'Dashboard') ?> | <?= APP_NAME ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Mobile & PWA Styles -->
    <link href="/rbi/static/css/mobile.css" rel="stylesheet">
    <link href="/rbi/static/css/pwa.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed: 60px;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: #1a237e;
            --sidebar-text: #94a3b8;
            --sidebar-text-active: #ffffff;
            --topbar-height: 56px;
            --brand-primary: #1a237e;
            --brand-accent: #3f51b5;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f1f5f9;
            margin: 0;
            overflow-x: hidden;
        }

        /* ── Top Navbar ────────────────────────────────────────── */
        .top-navbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 1030;
            transition: left 0.3s ease;
        }

        .top-navbar .nav-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .top-navbar .breadcrumb {
            margin: 0;
            font-size: 0.85rem;
        }

        .top-navbar .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .top-navbar .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            position: relative;
        }
        .top-navbar .btn-icon:hover { background: #f1f5f9; color: #334155; }

        .notification-badge {
            position: absolute;
            top: 4px; right: 4px;
            width: 8px; height: 8px;
            background: #ef4444;
            border-radius: 50%;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 4px 12px;
            border-radius: 8px;
        }
        .user-menu:hover { background: #f1f5f9; }
        .user-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--brand-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .user-info { line-height: 1.2; }
        .user-info .name { font-weight: 600; font-size: 0.85rem; color: #1e293b; }
        .user-info .role { font-size: 0.7rem; color: #94a3b8; text-transform: capitalize; }

        /* ── Sidebar toggle ───────────────────────────────────── */
        #sidebarToggle {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #64748b;
            cursor: pointer;
            padding: 4px;
        }
        #sidebarToggle:hover { color: #1e293b; }

        /* ── Content Area ─────────────────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 24px;
            min-height: calc(100vh - var(--topbar-height));
            transition: margin-left 0.3s ease;
        }

        /* ── Collapsed state ──────────────────────────────────── */
        body.sidebar-collapsed .sidebar { width: var(--sidebar-collapsed); }
        body.sidebar-collapsed .sidebar .sidebar-brand span,
        body.sidebar-collapsed .sidebar .nav-text,
        body.sidebar-collapsed .sidebar .nav-arrow,
        body.sidebar-collapsed .sidebar .sidebar-section-title { display: none; }
        body.sidebar-collapsed .sidebar .nav-link { justify-content: center; padding: 10px; }
        body.sidebar-collapsed .sidebar .nav-icon { margin-right: 0; }
        body.sidebar-collapsed .top-navbar { left: var(--sidebar-collapsed); }
        body.sidebar-collapsed .main-content { margin-left: var(--sidebar-collapsed); }

        /* ── Cards & Utilities ────────────────────────────────── */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            padding: 16px 20px;
        }

        .stat-card {
            border-radius: 12px;
            padding: 20px;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .stat-card .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .stat-card .stat-value { font-size: 1.8rem; font-weight: 700; color: #1e293b; }
        .stat-card .stat-label { font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Risk level badges */
        .badge-risk-vh { background: #721c24; }
        .badge-risk-h  { background: #dc3545; }
        .badge-risk-mh { background: #fd7e14; }
        .badge-risk-m  { background: #ffc107; color: #333; }
        .badge-risk-l  { background: #28a745; }

        .bg-danger-dark { background-color: #721c24 !important; }
        .bg-warning-dark { background-color: #fd7e14 !important; }

        /* ── Responsive ───────────────────────────────────────── */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .top-navbar { left: 0; }
            .main-content { margin-left: 0; }
        }

        /* ── Tables ───────────────────────────────────────────── */
        .table th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 600;
            border-bottom-width: 1px;
        }
        .table td { vertical-align: middle; font-size: 0.875rem; }

        /* ── Page header ──────────────────────────────────────── */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .page-header h1 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; }
    </style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<!-- Sidebar Backdrop (Mobile) -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Top Navbar -->
<nav class="top-navbar">
    <div class="nav-left">
        <button id="sidebarToggle" title="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
                <?php if (!empty($pageSection)): ?>
                    <li class="breadcrumb-item"><?= sanitize($pageSection) ?></li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?= sanitize($pageTitle ?? 'Dashboard') ?></li>
            </ol>
        </nav>
    </div>
    <div class="nav-right">
        <!-- Search -->
        <button class="btn-icon" title="Global Search" data-bs-toggle="modal" data-bs-target="#searchModal">
            <i class="fas fa-search"></i>
        </button>

        <!-- Notifications -->
        <div class="dropdown">
            <button class="btn-icon" data-bs-toggle="dropdown" title="Notifications">
                <i class="fas fa-bell"></i>
                <span class="notification-badge"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end" style="width:320px;">
                <h6 class="dropdown-header">Notifications</h6>
                <a class="dropdown-item small py-2" href="#">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>3 overdue inspections
                </a>
                <a class="dropdown-item small py-2" href="#">
                    <i class="fas fa-chart-line text-danger me-2"></i>2 assets approaching minimum thickness
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-center small" href="<?= BASE_URL ?>/notifications.php">View All</a>
            </div>
        </div>

        <!-- User Menu -->
        <div class="dropdown">
            <div class="user-menu" data-bs-toggle="dropdown">
                <div class="user-avatar">
                    <?= $currentUser ? strtoupper(substr($currentUser['first_name'] ?? 'U', 0, 1) . substr($currentUser['last_name'] ?? '', 0, 1)) : 'U' ?>
                </div>
                <div class="user-info d-none d-md-block">
                    <div class="name"><?= sanitize($_SESSION['user_name'] ?? 'User') ?></div>
                    <div class="role"><?= sanitize($_SESSION['auth_user_role'] ?? 'Inspector') ?></div>
                </div>
                <i class="fas fa-chevron-down ms-1" style="font-size:0.65rem;color:#94a3b8;"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="main-content">
    <?= renderFlashMessages() ?>
