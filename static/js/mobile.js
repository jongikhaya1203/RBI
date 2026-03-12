/**
 * Mobile JavaScript - RBI Engineering Suite
 * Touch handlers, pull-to-refresh, infinite scroll, bottom sheets,
 * responsive tables, FAB, pinch-to-zoom, orientation handling
 */

'use strict';

const RBI_MOBILE = (() => {
    const isMobile = () => window.innerWidth < 768;
    const isTouch = () => 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX = 0;
    let touchEndY = 0;

    // ── Swipe Gesture: Open/Close Sidebar ──────────────────────
    function initSwipeGestures() {
        if (!isMobile()) return;

        const threshold = 60;
        const restraint = 100;

        document.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
        }, { passive: true });

        document.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            touchEndY = e.changedTouches[0].screenY;

            const deltaX = touchEndX - touchStartX;
            const deltaY = Math.abs(touchEndY - touchStartY);

            // Only trigger if horizontal swipe is dominant
            if (deltaY > restraint) return;

            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');

            if (deltaX > threshold && touchStartX < 40) {
                // Swipe right from left edge: open sidebar
                openSidebar(sidebar, backdrop);
            } else if (deltaX < -threshold && sidebar?.classList.contains('show')) {
                // Swipe left: close sidebar
                closeSidebar(sidebar, backdrop);
            }
        }, { passive: true });

        // Backdrop click to close
        const backdrop = document.getElementById('sidebarBackdrop');
        if (backdrop) {
            backdrop.addEventListener('click', () => {
                const sidebar = document.getElementById('sidebar');
                closeSidebar(sidebar, backdrop);
            });
        }
    }

    function openSidebar(sidebar, backdrop) {
        if (!sidebar) return;
        sidebar.classList.add('show');
        if (backdrop) backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';

        // Haptic feedback
        triggerHaptic('light');
    }

    function closeSidebar(sidebar, backdrop) {
        if (!sidebar) return;
        sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    // ── Pull-to-Refresh ────────────────────────────────────────
    function initPullToRefresh() {
        if (!isMobile()) return;

        const mainContent = document.querySelector('.main-content');
        if (!mainContent) return;

        let pulling = false;
        let pullDistance = 0;
        const pullThreshold = 80;

        // Create indicator
        const indicator = document.createElement('div');
        indicator.className = 'ptr-indicator';
        indicator.innerHTML = '<i class="fas fa-arrow-down"></i>';
        mainContent.insertBefore(indicator, mainContent.firstChild);
        mainContent.style.position = 'relative';

        mainContent.addEventListener('touchstart', (e) => {
            if (mainContent.scrollTop === 0) {
                touchStartY = e.touches[0].clientY;
                pulling = true;
            }
        }, { passive: true });

        mainContent.addEventListener('touchmove', (e) => {
            if (!pulling) return;

            pullDistance = e.touches[0].clientY - touchStartY;

            if (pullDistance > 0 && pullDistance < 150) {
                indicator.style.top = `${Math.min(pullDistance - 50, 20)}px`;
                indicator.classList.add('active');

                const icon = indicator.querySelector('i');
                if (pullDistance > pullThreshold) {
                    icon.className = 'fas fa-arrow-up';
                    icon.style.color = '#3b82f6';
                } else {
                    icon.className = 'fas fa-arrow-down';
                    icon.style.color = '#94a3b8';
                }
            }
        }, { passive: true });

        mainContent.addEventListener('touchend', () => {
            if (!pulling) return;

            if (pullDistance > pullThreshold) {
                // Trigger refresh
                indicator.classList.add('refreshing');
                indicator.querySelector('i').className = 'fas fa-sync';
                triggerHaptic('medium');

                // Reload or refresh data
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                indicator.classList.remove('active');
                indicator.style.top = '-50px';
            }

            pulling = false;
            pullDistance = 0;
        }, { passive: true });
    }

    // ── Infinite Scroll ────────────────────────────────────────
    function initInfiniteScroll(options = {}) {
        if (!isMobile()) return;

        const {
            container = '.main-content',
            loadMore = null,
            threshold = 200
        } = options;

        const containerEl = document.querySelector(container);
        if (!containerEl || !loadMore) return;

        let loading = false;
        let page = 1;
        let hasMore = true;

        containerEl.addEventListener('scroll', () => {
            if (loading || !hasMore) return;

            const scrollBottom = containerEl.scrollHeight - containerEl.scrollTop - containerEl.clientHeight;
            if (scrollBottom < threshold) {
                loading = true;
                page++;

                // Show loading spinner
                const spinner = document.createElement('div');
                spinner.className = 'text-center py-3 infinite-scroll-spinner';
                spinner.innerHTML = '<i class="fas fa-spinner fa-spin text-muted"></i>';
                containerEl.appendChild(spinner);

                loadMore(page)
                    .then((moreAvailable) => {
                        hasMore = moreAvailable !== false;
                        loading = false;
                        spinner.remove();
                    })
                    .catch(() => {
                        loading = false;
                        spinner.remove();
                    });
            }
        }, { passive: true });
    }

    // ── Bottom Sheet Component ─────────────────────────────────
    function createBottomSheet(options = {}) {
        const {
            title = '',
            content = '',
            onClose = null
        } = options;

        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'bottom-sheet-overlay';

        // Create sheet
        const sheet = document.createElement('div');
        sheet.className = 'bottom-sheet';
        sheet.innerHTML = `
            <div class="bottom-sheet-handle"></div>
            <div class="bottom-sheet-header">
                <h3>${title}</h3>
                <button class="btn-close" aria-label="Close"></button>
            </div>
            <div class="bottom-sheet-body">${content}</div>
        `;

        document.body.appendChild(overlay);
        document.body.appendChild(sheet);

        // Open with animation
        requestAnimationFrame(() => {
            overlay.classList.add('show');
            sheet.classList.add('show');
        });

        // Close handlers
        const close = () => {
            sheet.classList.remove('show');
            overlay.classList.remove('show');
            setTimeout(() => {
                sheet.remove();
                overlay.remove();
            }, 300);
            if (onClose) onClose();
        };

        overlay.addEventListener('click', close);
        sheet.querySelector('.btn-close').addEventListener('click', close);

        // Swipe down to close
        let startY = 0;
        const handle = sheet.querySelector('.bottom-sheet-handle');

        handle.addEventListener('touchstart', (e) => {
            startY = e.touches[0].clientY;
        }, { passive: true });

        handle.addEventListener('touchmove', (e) => {
            const deltaY = e.touches[0].clientY - startY;
            if (deltaY > 0) {
                sheet.style.transform = `translateY(${deltaY}px)`;
            }
        }, { passive: true });

        handle.addEventListener('touchend', (e) => {
            const deltaY = e.changedTouches[0].clientY - startY;
            if (deltaY > 100) {
                close();
            } else {
                sheet.style.transform = '';
            }
        }, { passive: true });

        return { close, sheet, overlay };
    }

    // ── Haptic Feedback ────────────────────────────────────────
    function triggerHaptic(style = 'light') {
        if (!navigator.vibrate) return;

        switch (style) {
            case 'light':
                navigator.vibrate(10);
                break;
            case 'medium':
                navigator.vibrate(20);
                break;
            case 'heavy':
                navigator.vibrate(40);
                break;
            case 'success':
                navigator.vibrate([10, 50, 10]);
                break;
            case 'error':
                navigator.vibrate([30, 50, 30, 50, 30]);
                break;
        }
    }

    // ── Mobile Chart Config ────────────────────────────────────
    function getMobileChartConfig(baseConfig = {}) {
        if (!isMobile()) return baseConfig;

        const mobileDefaults = {
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 8,
                            font: { size: 10 }
                        }
                    },
                    tooltip: {
                        bodyFont: { size: 11 },
                        titleFont: { size: 12 }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: { size: 9 },
                            maxRotation: 45,
                            maxTicksLimit: 6
                        }
                    },
                    y: {
                        ticks: {
                            font: { size: 9 },
                            maxTicksLimit: 5
                        }
                    }
                }
            }
        };

        return deepMerge(mobileDefaults, baseConfig);
    }

    // ── Responsive Table Toggle ────────────────────────────────
    function initTableToggle() {
        if (!isMobile()) return;

        document.querySelectorAll('[data-table-toggle]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const tableId = btn.dataset.tableToggle;
                const table = document.getElementById(tableId);
                if (!table) return;

                table.classList.toggle('table-card-view');
                btn.classList.toggle('active');

                const icon = btn.querySelector('i');
                if (table.classList.contains('table-card-view')) {
                    icon.className = 'fas fa-table';
                    btn.title = 'Table view';
                } else {
                    icon.className = 'fas fa-grip';
                    btn.title = 'Card view';
                }

                triggerHaptic('light');
            });
        });
    }

    // ── FAB Speed Dial ─────────────────────────────────────────
    function initFAB() {
        const fab = document.querySelector('.fab');
        const menu = document.querySelector('.fab-menu');
        if (!fab || !menu) return;

        fab.addEventListener('click', () => {
            fab.classList.toggle('open');
            menu.classList.toggle('show');
            triggerHaptic('light');
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.fab-container')) {
                fab.classList.remove('open');
                menu.classList.remove('show');
            }
        });

        // Close on menu item click
        menu.querySelectorAll('button').forEach((btn) => {
            btn.addEventListener('click', () => {
                fab.classList.remove('open');
                menu.classList.remove('show');
                triggerHaptic('medium');
            });
        });
    }

    // ── Pinch-to-Zoom for Risk Matrix ──────────────────────────
    function initPinchZoom() {
        const containers = document.querySelectorAll('.risk-matrix-container');

        containers.forEach((container) => {
            let scale = 1;
            let lastScale = 1;
            let initialDistance = 0;

            container.addEventListener('touchstart', (e) => {
                if (e.touches.length === 2) {
                    initialDistance = getDistance(e.touches[0], e.touches[1]);
                }
            }, { passive: true });

            container.addEventListener('touchmove', (e) => {
                if (e.touches.length === 2) {
                    const currentDistance = getDistance(e.touches[0], e.touches[1]);
                    scale = lastScale * (currentDistance / initialDistance);
                    scale = Math.max(0.5, Math.min(scale, 3));

                    const content = container.querySelector('table') || container.firstElementChild;
                    if (content) {
                        content.style.transform = `scale(${scale})`;
                        content.style.transformOrigin = 'top left';
                    }
                }
            }, { passive: true });

            container.addEventListener('touchend', () => {
                lastScale = scale;
            }, { passive: true });
        });
    }

    function getDistance(touch1, touch2) {
        return Math.hypot(
            touch2.clientX - touch1.clientX,
            touch2.clientY - touch1.clientY
        );
    }

    // ── Long-Press Context Menu ────────────────────────────────
    function initLongPress() {
        if (!isTouch()) return;

        document.querySelectorAll('[data-long-press]').forEach((el) => {
            let timer = null;

            el.addEventListener('touchstart', (e) => {
                timer = setTimeout(() => {
                    triggerHaptic('medium');

                    const menuItems = JSON.parse(el.dataset.longPress || '[]');
                    if (menuItems.length === 0) return;

                    const menuHTML = menuItems.map((item) =>
                        `<a href="${item.url || '#'}" class="list-group-item list-group-item-action py-3">
                            <i class="fas ${item.icon || 'fa-circle'} me-3 text-muted"></i>${item.label}
                        </a>`
                    ).join('');

                    createBottomSheet({
                        title: el.dataset.longPressTitle || 'Actions',
                        content: `<div class="list-group list-group-flush">${menuHTML}</div>`
                    });
                }, 600);
            }, { passive: true });

            el.addEventListener('touchend', () => clearTimeout(timer), { passive: true });
            el.addEventListener('touchmove', () => clearTimeout(timer), { passive: true });
        });
    }

    // ── Orientation Change Handler ─────────────────────────────
    function initOrientationHandler() {
        let resizeTimer;

        function handleResize() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                // Resize all Chart.js instances
                if (typeof Chart !== 'undefined') {
                    Chart.helpers.each(Chart.instances, (chart) => {
                        chart.resize();
                    });
                }

                // Re-evaluate mobile state
                const sidebar = document.getElementById('sidebar');
                const backdrop = document.getElementById('sidebarBackdrop');
                if (!isMobile()) {
                    closeSidebar(sidebar, backdrop);
                }
            }, 250);
        }

        window.addEventListener('resize', handleResize, { passive: true });
        window.addEventListener('orientationchange', () => {
            setTimeout(handleResize, 100);
        });
    }

    // ── Virtual Keyboard Detection ─────────────────────────────
    function initKeyboardDetection() {
        if (!isMobile()) return;

        const viewportHeight = window.innerHeight;

        // Use visualViewport API if available
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', () => {
                const isKeyboardOpen = window.visualViewport.height < viewportHeight * 0.75;
                document.body.classList.toggle('keyboard-open', isKeyboardOpen);
            });
        } else {
            // Fallback: detect focus on input elements
            document.addEventListener('focusin', (e) => {
                const tag = e.target.tagName;
                if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
                    document.body.classList.add('keyboard-open');
                }
            });

            document.addEventListener('focusout', () => {
                setTimeout(() => {
                    document.body.classList.remove('keyboard-open');
                }, 100);
            });
        }
    }

    // ── Mobile Sidebar Toggle Fix ──────────────────────────────
    function initMobileSidebarToggle() {
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (!toggle || !sidebar) return;

        toggle.addEventListener('click', (e) => {
            if (isMobile()) {
                e.stopPropagation();
                if (sidebar.classList.contains('show')) {
                    closeSidebar(sidebar, backdrop);
                } else {
                    openSidebar(sidebar, backdrop);
                }
            }
        });
    }

    // ── Deep Merge Utility ─────────────────────────────────────
    function deepMerge(target, source) {
        const output = { ...target };
        for (const key of Object.keys(source)) {
            if (source[key] instanceof Object && key in target && target[key] instanceof Object) {
                output[key] = deepMerge(target[key], source[key]);
            } else {
                output[key] = source[key];
            }
        }
        return output;
    }

    // ── Initialize ─────────────────────────────────────────────
    function init() {
        initSwipeGestures();
        initPullToRefresh();
        initTableToggle();
        initFAB();
        initPinchZoom();
        initLongPress();
        initOrientationHandler();
        initKeyboardDetection();
        initMobileSidebarToggle();

        console.log('[Mobile] Initialized, isMobile:', isMobile(), 'isTouch:', isTouch());
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ── Public API ─────────────────────────────────────────────
    return {
        isMobile,
        isTouch,
        createBottomSheet,
        triggerHaptic,
        getMobileChartConfig,
        initInfiniteScroll,
        initPinchZoom,
        openSidebar: (s, b) => openSidebar(
            s || document.getElementById('sidebar'),
            b || document.getElementById('sidebarBackdrop')
        ),
        closeSidebar: (s, b) => closeSidebar(
            s || document.getElementById('sidebar'),
            b || document.getElementById('sidebarBackdrop')
        )
    };
})();
