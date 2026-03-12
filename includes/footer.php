<?php
/**
 * Footer - RBI Engineering Suite
 */
?>
</div><!-- /.main-content -->

<!-- Search Modal -->
<div class="modal fade" id="searchModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body p-0">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-0 shadow-none" id="globalSearch"
                           placeholder="Search assets, assessments, reports..." autofocus>
                    <span class="input-group-text bg-white border-0">
                        <kbd class="bg-light text-muted small px-2 rounded">ESC</kbd>
                    </span>
                </div>
                <div id="searchResults" class="border-top" style="max-height:400px;overflow-y:auto;display:none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Footer Bar -->
<footer class="text-center py-3" style="margin-left:var(--sidebar-width);color:#94a3b8;font-size:0.75rem;transition:margin-left 0.3s ease;">
    <span>&copy; <?= date('Y') ?> <?= APP_NAME ?></span>
    <span class="mx-2">|</span>
    <span>Version <?= APP_VERSION ?></span>
    <span class="mx-2">|</span>
    <span>API 580/581 Methodology</span>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js (for analytics pages) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // ── Sidebar Toggle ──────────────────────────────────────────
    const toggle = document.getElementById('sidebarToggle');
    if (toggle) {
        toggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
        });
        // Restore state
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }
    }

    // ── Mobile sidebar ──────────────────────────────────────────
    if (window.innerWidth <= 992) {
        const sidebar = document.getElementById('sidebar');
        if (toggle && sidebar) {
            toggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
            document.querySelector('.main-content').addEventListener('click', function() {
                sidebar.classList.remove('show');
            });
        }
    }

    // ── Search Modal Keyboard Shortcut ──────────────────────────
    document.addEventListener('keydown', function(e) {
        // Ctrl+K or Cmd+K opens search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            var modal = new bootstrap.Modal(document.getElementById('searchModal'));
            modal.show();
        }
    });

    // ── Tooltips ────────────────────────────────────────────────
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(el) {
        return new bootstrap.Tooltip(el);
    });

    // ── Auto-dismiss alerts ─────────────────────────────────────
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // ── Confirm delete actions ──────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<!-- Offline Indicator Bar -->
<div class="offline-bar" id="offlineBar">
    <i class="fas fa-wifi-slash"></i>
    <span>You're offline - some features may be limited</span>
    <span class="sync-count"></span>
</div>

<!-- Online Restored Bar -->
<div class="online-bar" id="onlineBar">
    <i class="fas fa-wifi"></i>
    <span>Connection restored</span>
</div>

<!-- Update Available Toast -->
<div class="update-toast" id="updateToast">
    <div class="update-toast-icon">
        <i class="fas fa-arrow-up"></i>
    </div>
    <div class="update-toast-text">
        <strong>Update Available</strong>
        A new version is ready.
    </div>
    <button class="btn-update" onclick="RBI_PWA.applyUpdate()">Update Now</button>
</div>

<!-- Install App Banner -->
<div class="install-banner" id="installBanner">
    <div class="install-banner-content">
        <div class="install-banner-icon">
            <i class="fas fa-shield-halved"></i>
        </div>
        <div class="install-banner-text">
            <h4>Install RBI Suite</h4>
            <p>Add to your home screen for offline access and faster loading.</p>
        </div>
        <div class="install-banner-actions">
            <button class="btn-install" onclick="RBI_PWA.installApp()">Install</button>
            <button class="btn-dismiss" onclick="RBI_PWA.dismissInstall()">Later</button>
        </div>
    </div>
</div>

<!-- Bottom Navigation (Mobile) -->
<nav class="bottom-nav" id="bottomNav">
    <?php
    $navItems = [
        ['url' => '/rbi/dashboard.php', 'icon' => 'fa-gauge-high', 'label' => 'Dashboard', 'page' => 'dashboard'],
        ['url' => '/rbi/assets/index.php', 'icon' => 'fa-industry', 'label' => 'Assets', 'page' => 'index'],
        ['url' => '/rbi/risk/matrix.php', 'icon' => 'fa-table-cells', 'label' => 'Risk', 'page' => 'matrix'],
        ['url' => '/rbi/inspections/schedule.php', 'icon' => 'fa-clipboard-check', 'label' => 'Inspect', 'page' => 'schedule'],
        ['url' => '#moreMenu', 'icon' => 'fa-ellipsis-h', 'label' => 'More', 'page' => 'more'],
    ];
    foreach ($navItems as $item):
        $isActive = ($currentPage ?? '') === $item['page'];
    ?>
    <a href="<?= $item['url'] ?>" class="bottom-nav-item <?= $isActive ? 'active' : '' ?>"
       <?= $item['page'] === 'more' ? 'id="bottomNavMore"' : '' ?>>
        <i class="fas <?= $item['icon'] ?>"></i>
        <span><?= $item['label'] ?></span>
    </a>
    <?php endforeach; ?>
</nav>

<!-- FAB - Floating Action Button (Mobile) -->
<div class="fab-container" id="fabContainer">
    <div class="fab-menu" id="fabMenu">
        <div class="fab-menu-item">
            <button onclick="window.location.href='/rbi/mobile/inspection-form.php'" title="New Inspection">
                <i class="fas fa-clipboard-check"></i>
            </button>
            <span>New Inspection</span>
        </div>
        <div class="fab-menu-item">
            <button onclick="window.location.href='/rbi/mobile/readings.php'" title="Add Readings">
                <i class="fas fa-ruler"></i>
            </button>
            <span>Add Readings</span>
        </div>
        <div class="fab-menu-item">
            <button onclick="window.location.href='/rbi/risk/calculate.php'" title="Risk Assessment">
                <i class="fas fa-calculator"></i>
            </button>
            <span>Risk Assessment</span>
        </div>
    </div>
    <button class="fab" id="fabBtn" aria-label="Quick actions">
        <i class="fas fa-plus"></i>
    </button>
</div>

<!-- App Scripts -->
<script src="/rbi/static/js/app.js"></script>
<script src="/rbi/static/js/mobile.js"></script>
<script src="/rbi/static/js/pwa.js"></script>

<script>
// Bottom nav "More" menu handler
document.getElementById('bottomNavMore')?.addEventListener('click', function(e) {
    e.preventDefault();
    if (typeof RBI_MOBILE !== 'undefined') {
        RBI_MOBILE.createBottomSheet({
            title: 'More',
            content: '<div class="list-group list-group-flush">' +
                '<a href="/rbi/analytics/remaining-life.php" class="list-group-item list-group-item-action py-3"><i class="fas fa-hourglass-half me-3 text-muted"></i>Remaining Life</a>' +
                '<a href="/rbi/analytics/corrosion-rates.php" class="list-group-item list-group-item-action py-3"><i class="fas fa-chart-line me-3 text-muted"></i>Corrosion Rates</a>' +
                '<a href="/rbi/analytics/financial-risk.php" class="list-group-item list-group-item-action py-3"><i class="fas fa-dollar-sign me-3 text-muted"></i>Financial Risk</a>' +
                '<a href="/rbi/reports/index.php" class="list-group-item list-group-item-action py-3"><i class="fas fa-file-pdf me-3 text-muted"></i>Reports</a>' +
                '<a href="/rbi/damage-mechanisms/index.php" class="list-group-item list-group-item-action py-3"><i class="fas fa-shield-virus me-3 text-muted"></i>Damage Mechanisms</a>' +
                '<a href="/rbi/integrations/index.php" class="list-group-item list-group-item-action py-3"><i class="fas fa-plug me-3 text-muted"></i>Integrations</a>' +
                '<a href="/rbi/admin/settings.php" class="list-group-item list-group-item-action py-3"><i class="fas fa-cog me-3 text-muted"></i>Settings</a>' +
                '<a href="/rbi/mobile/dashboard.php" class="list-group-item list-group-item-action py-3"><i class="fas fa-mobile-screen me-3 text-muted"></i>Mobile View</a>' +
                '</div>'
        });
    }
});
</script>

<?php if (!empty($pageScripts)): ?>
    <?php foreach ($pageScripts as $script): ?>
        <script src="<?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
