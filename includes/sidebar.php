<?php
/**
 * Sidebar Navigation - RBI Engineering Suite
 * Dark-themed left sidebar with collapsible sub-menus
 */
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
$currentModule = $currentModule ?? '';

/**
 * Helper: return 'active' class if page matches
 */
function navActive(string $page, string $current): string {
    return ($page === $current) ? 'active' : '';
}
function navOpen(string $module, string $current): string {
    return ($module === $current) ? 'show' : '';
}
function navExpanded(string $module, string $current): string {
    return ($module === $current) ? '' : 'collapsed';
}
?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <span>
            <strong>RBI</strong> Engineering
            <small>Suite v<?= APP_VERSION ?></small>
        </span>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">

        <!-- Dashboard -->
        <a href="<?= BASE_URL ?>/index.php" class="nav-link <?= navActive('index', $currentPage) ?>">
            <span class="nav-icon"><i class="fas fa-th-large"></i></span>
            <span class="nav-text">Dashboard</span>
        </a>

        <!-- Asset Management -->
        <div class="sidebar-section-title">ASSET MANAGEMENT</div>

        <a class="nav-link nav-toggle <?= navExpanded('assets', $currentModule) ?>"
           data-bs-toggle="collapse" href="#navAssets" role="button">
            <span class="nav-icon"><i class="fas fa-industry"></i></span>
            <span class="nav-text">Asset Management</span>
            <span class="nav-arrow"><i class="fas fa-chevron-right"></i></span>
        </a>
        <div class="collapse <?= navOpen('assets', $currentModule) ?>" id="navAssets">
            <a href="<?= BASE_URL ?>/assets/hierarchy.php" class="nav-link sub-link <?= navActive('hierarchy', $currentPage) ?>">
                <span class="nav-text">Asset Hierarchy</span>
            </a>
            <a href="<?= BASE_URL ?>/assets/registry.php" class="nav-link sub-link <?= navActive('registry', $currentPage) ?>">
                <span class="nav-text">Asset Registry</span>
            </a>
            <a href="<?= BASE_URL ?>/assets/circuits.php" class="nav-link sub-link <?= navActive('circuits', $currentPage) ?>">
                <span class="nav-text">Corrosion Circuits</span>
            </a>
        </div>

        <!-- Risk Assessment -->
        <div class="sidebar-section-title">RISK ANALYSIS</div>

        <a class="nav-link nav-toggle <?= navExpanded('risk', $currentModule) ?>"
           data-bs-toggle="collapse" href="#navRisk" role="button">
            <span class="nav-icon"><i class="fas fa-exclamation-triangle"></i></span>
            <span class="nav-text">Risk Assessment</span>
            <span class="nav-arrow"><i class="fas fa-chevron-right"></i></span>
        </a>
        <div class="collapse <?= navOpen('risk', $currentModule) ?>" id="navRisk">
            <a href="<?= BASE_URL ?>/risk/assessments.php" class="nav-link sub-link <?= navActive('assessments', $currentPage) ?>">
                <span class="nav-text">Assessments</span>
            </a>
            <a href="<?= BASE_URL ?>/risk/matrix.php" class="nav-link sub-link <?= navActive('matrix', $currentPage) ?>">
                <span class="nav-text">Risk Matrix</span>
            </a>
            <a href="<?= BASE_URL ?>/risk/rankings.php" class="nav-link sub-link <?= navActive('rankings', $currentPage) ?>">
                <span class="nav-text">Risk Rankings</span>
            </a>
        </div>

        <!-- Damage Mechanisms -->
        <a href="<?= BASE_URL ?>/damage-mechanisms.php" class="nav-link <?= navActive('damage-mechanisms', $currentPage) ?>">
            <span class="nav-icon"><i class="fas fa-bolt"></i></span>
            <span class="nav-text">Damage Mechanisms</span>
        </a>

        <!-- Inspection Planning -->
        <div class="sidebar-section-title">INSPECTION</div>

        <a class="nav-link nav-toggle <?= navExpanded('inspection', $currentModule) ?>"
           data-bs-toggle="collapse" href="#navInspection" role="button">
            <span class="nav-icon"><i class="fas fa-clipboard-check"></i></span>
            <span class="nav-text">Inspection Planning</span>
            <span class="nav-arrow"><i class="fas fa-chevron-right"></i></span>
        </a>
        <div class="collapse <?= navOpen('inspection', $currentModule) ?>" id="navInspection">
            <a href="<?= BASE_URL ?>/inspection/plans.php" class="nav-link sub-link <?= navActive('plans', $currentPage) ?>">
                <span class="nav-text">Inspection Plans</span>
            </a>
            <a href="<?= BASE_URL ?>/inspection/schedule.php" class="nav-link sub-link <?= navActive('schedule', $currentPage) ?>">
                <span class="nav-text">Schedule</span>
            </a>
            <a href="<?= BASE_URL ?>/inspection/tasks.php" class="nav-link sub-link <?= navActive('tasks', $currentPage) ?>">
                <span class="nav-text">Tasks</span>
            </a>
        </div>

        <!-- Analytics -->
        <div class="sidebar-section-title">ANALYTICS</div>

        <a class="nav-link nav-toggle <?= navExpanded('analytics', $currentModule) ?>"
           data-bs-toggle="collapse" href="#navAnalytics" role="button">
            <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
            <span class="nav-text">Analytics</span>
            <span class="nav-arrow"><i class="fas fa-chevron-right"></i></span>
        </a>
        <div class="collapse <?= navOpen('analytics', $currentModule) ?>" id="navAnalytics">
            <a href="<?= BASE_URL ?>/analytics/remaining-life.php" class="nav-link sub-link <?= navActive('remaining-life', $currentPage) ?>">
                <span class="nav-text">Remaining Life</span>
            </a>
            <a href="<?= BASE_URL ?>/analytics/corrosion-rates.php" class="nav-link sub-link <?= navActive('corrosion-rates', $currentPage) ?>">
                <span class="nav-text">Corrosion Rates</span>
            </a>
            <a href="<?= BASE_URL ?>/analytics/sensitivity.php" class="nav-link sub-link <?= navActive('sensitivity', $currentPage) ?>">
                <span class="nav-text">Sensitivity Analysis</span>
            </a>
            <a href="<?= BASE_URL ?>/analytics/financial.php" class="nav-link sub-link <?= navActive('financial', $currentPage) ?>">
                <span class="nav-text">Financial Risk</span>
            </a>
            <a href="<?= BASE_URL ?>/analytics/predictive.php" class="nav-link sub-link <?= navActive('predictive', $currentPage) ?>">
                <span class="nav-text"><i class="fas fa-brain me-1" style="font-size:0.7rem;"></i>Predictive Analytics</span>
            </a>
            <a href="<?= BASE_URL ?>/analytics/auto-risk.php" class="nav-link sub-link <?= navActive('auto-risk', $currentPage) ?>">
                <span class="nav-text"><i class="fas fa-shield-alt me-1" style="font-size:0.7rem;"></i>Auto Risk Scoring</span>
            </a>
            <a href="<?= BASE_URL ?>/analytics/clustering.php" class="nav-link sub-link <?= navActive('clustering', $currentPage) ?>">
                <span class="nav-text"><i class="fas fa-project-diagram me-1" style="font-size:0.7rem;"></i>Asset Clustering</span>
            </a>
        </div>

        <!-- Integrations -->
        <a class="nav-link nav-toggle <?= navExpanded('integrations', $currentModule) ?>"
           data-bs-toggle="collapse" href="#navIntegrations" role="button">
            <span class="nav-icon"><i class="fas fa-plug"></i></span>
            <span class="nav-text">Integrations</span>
            <span class="nav-arrow"><i class="fas fa-chevron-right"></i></span>
        </a>
        <div class="collapse <?= navOpen('integrations', $currentModule) ?>" id="navIntegrations">
            <a href="<?= BASE_URL ?>/integrations/manager.php" class="nav-link sub-link <?= navActive('manager', $currentPage) ?>">
                <span class="nav-text">Integration Hub</span>
            </a>
            <a href="<?= BASE_URL ?>/integrations/sap.php" class="nav-link sub-link <?= navActive('sap', $currentPage) ?>">
                <span class="nav-text">SAP PM</span>
            </a>
            <a href="<?= BASE_URL ?>/integrations/maximo.php" class="nav-link sub-link <?= navActive('maximo', $currentPage) ?>">
                <span class="nav-text">IBM Maximo</span>
            </a>
            <a href="<?= BASE_URL ?>/integrations/osisoft-pi.php" class="nav-link sub-link <?= navActive('osisoft-pi', $currentPage) ?>">
                <span class="nav-text">OSIsoft PI</span>
            </a>
        </div>

        <!-- Reports -->
        <a href="<?= BASE_URL ?>/reports.php" class="nav-link <?= navActive('reports', $currentPage) ?>">
            <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
            <span class="nav-text">Reports</span>
        </a>

        <!-- Admin -->
        <div class="sidebar-section-title">ADMINISTRATION</div>

        <!-- Control Panel -->
        <a class="nav-link nav-toggle <?= navExpanded('admin', $currentModule) ?>"
           data-bs-toggle="collapse" href="#navControlPanel" role="button">
            <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
            <span class="nav-text">Control Panel</span>
            <span class="nav-arrow"><i class="fas fa-chevron-right"></i></span>
        </a>
        <div class="collapse <?= navOpen('admin', $currentModule) ?>" id="navControlPanel">
            <a href="<?= BASE_URL ?>/admin/control-panel.php" class="nav-link sub-link <?= navActive('control-panel', $currentPage) ?>">
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/training/" class="nav-link sub-link <?= navActive('index', $currentPage) && str_contains($_SERVER['REQUEST_URI'] ?? '', '/training') ? 'active' : '' ?>">
                <span class="nav-text">Training Center</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/active-directory.php" class="nav-link sub-link <?= navActive('active-directory', $currentPage) ?>">
                <span class="nav-text">Active Directory</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/marketing/" class="nav-link sub-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/marketing') ? 'active' : '' ?>">
                <span class="nav-text">Marketing</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/data-management.php" class="nav-link sub-link <?= navActive('data-management', $currentPage) ?>">
                <span class="nav-text">Data Management</span>
            </a>
        </div>

        <!-- Admin -->
        <a class="nav-link nav-toggle <?= navExpanded('admin', $currentModule) ?>"
           data-bs-toggle="collapse" href="#navAdmin" role="button">
            <span class="nav-icon"><i class="fas fa-cogs"></i></span>
            <span class="nav-text">Admin</span>
            <span class="nav-arrow"><i class="fas fa-chevron-right"></i></span>
        </a>
        <div class="collapse <?= navOpen('admin', $currentModule) ?>" id="navAdmin">
            <a href="<?= BASE_URL ?>/admin/users.php" class="nav-link sub-link <?= navActive('users', $currentPage) ?>">
                <span class="nav-text">Users</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/roles.php" class="nav-link sub-link <?= navActive('roles', $currentPage) ?>">
                <span class="nav-text">Roles & Permissions</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/settings.php" class="nav-link sub-link <?= navActive('settings', $currentPage) ?>">
                <span class="nav-text">Settings</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/audit-log.php" class="nav-link sub-link <?= navActive('audit-log', $currentPage) ?>">
                <span class="nav-text">Audit Log</span>
            </a>
        </div>

    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2">
            <span class="status-dot bg-success"></span>
            <span class="nav-text small">System Online</span>
        </div>
    </div>
</aside>

<style>
/* ── Sidebar Styles ──────────────────────────────────────────── */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: var(--sidebar-bg);
    z-index: 1040;
    display: flex;
    flex-direction: column;
    transition: width 0.3s ease;
    overflow: hidden;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    min-height: var(--topbar-height);
}
.sidebar-brand .brand-icon {
    width: 32px; height: 32px;
    background: var(--brand-accent);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.95rem;
    flex-shrink: 0;
}
.sidebar-brand span {
    color: #fff;
    font-size: 0.9rem;
    line-height: 1.2;
    white-space: nowrap;
}
.sidebar-brand span strong { font-weight: 700; }
.sidebar-brand span small {
    display: block;
    font-size: 0.65rem;
    color: var(--sidebar-text);
}

.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 8px 0;
    scrollbar-width: thin;
    scrollbar-color: #334155 transparent;
}
.sidebar-nav::-webkit-scrollbar { width: 4px; }
.sidebar-nav::-webkit-scrollbar-thumb { background: #334155; border-radius: 2px; }

.sidebar-section-title {
    font-size: 0.65rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 16px 20px 6px;
    white-space: nowrap;
}

.sidebar .nav-link {
    display: flex;
    align-items: center;
    padding: 9px 20px;
    color: var(--sidebar-text);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 400;
    border-radius: 0;
    transition: all 0.15s ease;
    white-space: nowrap;
    position: relative;
}
.sidebar .nav-link:hover {
    background: var(--sidebar-hover);
    color: #e2e8f0;
}
.sidebar .nav-link.active {
    background: var(--sidebar-active);
    color: var(--sidebar-text-active);
    font-weight: 500;
}
.sidebar .nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--brand-accent);
    border-radius: 0 3px 3px 0;
}

.sidebar .nav-icon {
    width: 20px;
    text-align: center;
    margin-right: 12px;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.sidebar .nav-text { flex: 1; }

.sidebar .nav-arrow {
    font-size: 0.6rem;
    transition: transform 0.2s ease;
    opacity: 0.5;
}
.sidebar .nav-toggle:not(.collapsed) .nav-arrow {
    transform: rotate(90deg);
}

.sidebar .sub-link {
    padding-left: 52px;
    font-size: 0.82rem;
}
.sidebar .sub-link::before {
    content: '';
    display: inline-block;
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #475569;
    margin-right: 10px;
    flex-shrink: 0;
}
.sidebar .sub-link.active::before {
    background: var(--brand-accent);
}

.sidebar-footer {
    padding: 12px 20px;
    border-top: 1px solid rgba(255,255,255,0.06);
    color: var(--sidebar-text);
    font-size: 0.75rem;
}
.status-dot {
    display: inline-block;
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
</style>
