/**
 * Main Application JavaScript - RBI Engineering Suite
 * AJAX wrapper, toast notifications, global search, keyboard shortcuts,
 * auto-save, session timeout, real-time refresh, chart config, utilities
 */

'use strict';

const RBI_APP = (() => {
    // ── Configuration ──────────────────────────────────────────
    const CONFIG = {
        baseUrl: '/rbi',
        refreshInterval: 30000,       // 30 seconds
        sessionWarning: 5 * 60 * 1000, // 5 minutes before expiry
        sessionTimeout: 30 * 60 * 1000, // 30 minutes (default)
        autoSaveInterval: 10000,       // 10 seconds
        toastDuration: 5000,
        maxToasts: 5
    };

    let refreshTimer = null;
    let sessionTimer = null;
    let autoSaveTimers = new Map();

    // ── AJAX Request Wrapper ───────────────────────────────────
    async function request(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };

        // Merge options
        const config = { ...defaults, ...options };
        config.headers = { ...defaults.headers, ...options.headers };

        // Add CSRF token for non-GET requests
        if (config.method !== 'GET') {
            const csrfToken = getCSRFToken();
            if (csrfToken) {
                config.headers['X-CSRF-Token'] = csrfToken;
            }

            // Auto-set content type for JSON
            if (config.body && typeof config.body === 'object' && !(config.body instanceof FormData)) {
                config.headers['Content-Type'] = 'application/json';
                config.body = JSON.stringify(config.body);
            }
        }

        // Handle relative URLs
        if (url.startsWith('/')) {
            url = url;
        } else if (!url.startsWith('http')) {
            url = `${CONFIG.baseUrl}/${url}`;
        }

        try {
            const response = await fetch(url, config);

            // Handle session expiry
            if (response.status === 401) {
                showToast('Session expired. Please log in again.', 'warning');
                setTimeout(() => {
                    window.location.href = `${CONFIG.baseUrl}/index.php`;
                }, 2000);
                throw new Error('Unauthorized');
            }

            // Handle server errors
            if (response.status >= 500) {
                showToast('Server error. Please try again.', 'error');
                throw new Error(`Server error: ${response.status}`);
            }

            return response;
        } catch (err) {
            if (err.name === 'TypeError' && !navigator.onLine) {
                // Offline - try to queue for background sync
                if (config.method !== 'GET' && typeof RBI_PWA !== 'undefined') {
                    const synced = await RBI_PWA.registerSync('sync-form-data', {
                        url,
                        method: config.method,
                        headers: config.headers,
                        data: config.body ? JSON.parse(config.body) : null
                    });

                    if (synced) {
                        showToast('You\'re offline. Data will sync when connection restores.', 'info');
                        return { ok: true, offline: true };
                    }
                }
                showToast('No internet connection', 'error');
            }
            throw err;
        }
    }

    function getCSRFToken() {
        // Try meta tag first
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.content;

        // Try form input
        const input = document.querySelector('input[name="csrf_token"]');
        if (input) return input.value;

        return null;
    }

    // ── Toast Notification System ──────────────────────────────
    function createToastContainer() {
        let container = document.getElementById('toastContainer');
        if (container) return container;

        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = `
            position: fixed; top: 16px; right: 16px; z-index: 1090;
            display: flex; flex-direction: column; gap: 8px;
            max-width: 380px; width: calc(100% - 32px);
            pointer-events: none;
        `;
        document.body.appendChild(container);
        return container;
    }

    function showToast(message, type = 'info', duration = CONFIG.toastDuration) {
        const container = createToastContainer();

        // Limit active toasts
        const toasts = container.querySelectorAll('.toast-item');
        if (toasts.length >= CONFIG.maxToasts) {
            toasts[0].remove();
        }

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const colors = {
            success: '#22c55e',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };

        const toast = document.createElement('div');
        toast.className = 'toast-item';
        toast.style.cssText = `
            display: flex; align-items: center; gap: 12px;
            background: #1e293b; color: #e2e8f0;
            padding: 14px 18px; border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            font-size: 0.875rem; pointer-events: auto;
            transform: translateX(110%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
            border-left: 4px solid ${colors[type] || colors.info};
            cursor: pointer;
        `;

        toast.innerHTML = `
            <i class="fas ${icons[type] || icons.info}" style="color:${colors[type] || colors.info};font-size:1.1rem;flex-shrink:0;"></i>
            <span style="flex:1;line-height:1.4;">${message}</span>
            <button style="background:none;border:none;color:#64748b;cursor:pointer;padding:0;font-size:1rem;flex-shrink:0;">&times;</button>
        `;

        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.style.transform = 'translateX(0)';
        });

        // Click to dismiss
        toast.addEventListener('click', () => dismissToast(toast));

        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => dismissToast(toast), duration);
        }

        return toast;
    }

    function dismissToast(toast) {
        toast.style.transform = 'translateX(110%)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }

    // ── Global Search (Ctrl+K) ─────────────────────────────────
    function initGlobalSearch() {
        const searchInput = document.getElementById('globalSearch');
        const resultsContainer = document.getElementById('searchResults');
        if (!searchInput || !resultsContainer) return;

        let searchTimeout = null;

        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();

            clearTimeout(searchTimeout);

            if (query.length < 2) {
                resultsContainer.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await request(`/rbi/api/search.php?q=${encodeURIComponent(query)}`);
                    if (response.ok) {
                        const data = await response.json();
                        renderSearchResults(data, resultsContainer);
                    }
                } catch {
                    resultsContainer.innerHTML = `
                        <div class="p-3 text-muted text-center small">
                            <i class="fas fa-search me-1"></i>Search unavailable
                        </div>
                    `;
                    resultsContainer.style.display = 'block';
                }
            }, 300);
        });
    }

    function renderSearchResults(data, container) {
        if (!data.results || data.results.length === 0) {
            container.innerHTML = `
                <div class="p-4 text-muted text-center">
                    <i class="fas fa-search mb-2 d-block" style="font-size:1.5rem;"></i>
                    No results found
                </div>
            `;
        } else {
            container.innerHTML = data.results.map((item) => `
                <a href="${item.url}" class="d-flex align-items-center gap-3 p-3 text-decoration-none border-bottom"
                   style="color:inherit;">
                    <div style="width:36px;height:36px;border-radius:8px;background:${item.color || '#f1f5f9'};
                         display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas ${item.icon || 'fa-file'}" style="font-size:0.85rem;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;font-size:0.875rem;">${item.title}</div>
                        <div style="font-size:0.75rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            ${item.subtitle || ''}
                        </div>
                    </div>
                    <span class="badge bg-light text-muted" style="font-size:0.65rem;">${item.type || ''}</span>
                </a>
            `).join('');
        }
        container.style.display = 'block';
    }

    // ── Keyboard Shortcuts ─────────────────────────────────────
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Don't trigger in input fields
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;

            // Ctrl/Cmd + K: Search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const modal = document.getElementById('searchModal');
                if (modal) {
                    const bsModal = bootstrap.Modal.getOrCreateInstance(modal);
                    bsModal.show();
                }
                return;
            }

            // G then D: Go to Dashboard
            // G then A: Go to Assets
            // G then R: Go to Risk Matrix
            // G then I: Go to Inspections
            if (e.key === '?') {
                showKeyboardShortcuts();
            }
        });
    }

    function showKeyboardShortcuts() {
        if (typeof RBI_MOBILE !== 'undefined' && RBI_MOBILE.isMobile()) {
            RBI_MOBILE.createBottomSheet({
                title: 'Keyboard Shortcuts',
                content: getShortcutsHTML()
            });
        } else {
            showToast('Press ? to see shortcuts', 'info');
        }
    }

    function getShortcutsHTML() {
        return `
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex justify-content-between">
                    <span>Global Search</span><kbd>Ctrl + K</kbd>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span>Show Shortcuts</span><kbd>?</kbd>
                </div>
            </div>
        `;
    }

    // ── Auto-Save Form Drafts ──────────────────────────────────
    function initAutoSave() {
        document.querySelectorAll('form[data-autosave]').forEach((form) => {
            const key = `rbi-draft-${form.dataset.autosave || form.id || 'form'}`;

            // Restore draft
            const saved = localStorage.getItem(key);
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    const age = Date.now() - (data._timestamp || 0);

                    // Only restore if less than 24 hours old
                    if (age < 24 * 60 * 60 * 1000) {
                        Object.entries(data).forEach(([name, value]) => {
                            if (name.startsWith('_')) return;
                            const input = form.querySelector(`[name="${name}"]`);
                            if (input && !input.value) {
                                input.value = value;
                            }
                        });

                        showToast('Draft restored from previous session', 'info', 3000);
                    } else {
                        localStorage.removeItem(key);
                    }
                } catch {
                    localStorage.removeItem(key);
                }
            }

            // Save on change
            const saveHandler = () => {
                const formData = new FormData(form);
                const data = { _timestamp: Date.now() };
                formData.forEach((value, name) => {
                    if (name !== 'csrf_token' && name !== 'password') {
                        data[name] = value;
                    }
                });
                localStorage.setItem(key, JSON.stringify(data));
            };

            form.addEventListener('input', debounce(saveHandler, CONFIG.autoSaveInterval));

            // Clear draft on successful submit
            form.addEventListener('submit', () => {
                localStorage.removeItem(key);
            });
        });
    }

    // ── Session Timeout Warning ────────────────────────────────
    function initSessionTimeout() {
        resetSessionTimer();

        // Reset timer on user activity
        ['click', 'keypress', 'scroll', 'touchstart'].forEach((event) => {
            document.addEventListener(event, () => resetSessionTimer(), { passive: true });
        });
    }

    function resetSessionTimer() {
        clearTimeout(sessionTimer);

        sessionTimer = setTimeout(() => {
            showSessionWarning();
        }, CONFIG.sessionTimeout - CONFIG.sessionWarning);
    }

    function showSessionWarning() {
        const toast = showToast(
            'Your session will expire in 5 minutes. Click here to extend.',
            'warning',
            0 // Don't auto-dismiss
        );

        if (toast) {
            toast.addEventListener('click', async () => {
                try {
                    await request(`${CONFIG.baseUrl}/api/heartbeat.php`);
                    resetSessionTimer();
                    showToast('Session extended', 'success');
                } catch {
                    showToast('Could not extend session', 'error');
                }
            });
        }
    }

    // ── Real-Time Dashboard Refresh ────────────────────────────
    function startDashboardRefresh(callback) {
        stopDashboardRefresh();

        refreshTimer = setInterval(async () => {
            if (!navigator.onLine) return;

            try {
                const response = await request(`${CONFIG.baseUrl}/api/dashboard.php`);
                if (response.ok) {
                    const data = await response.json();
                    if (callback) callback(data);
                }
            } catch {
                // Silently fail for polling
            }
        }, CONFIG.refreshInterval);
    }

    function stopDashboardRefresh() {
        if (refreshTimer) {
            clearInterval(refreshTimer);
            refreshTimer = null;
        }
    }

    function refreshCurrentView() {
        // Trigger a custom event that page scripts can listen to
        document.dispatchEvent(new CustomEvent('rbi:refresh'));
    }

    // ── Chart Theme Configuration ──────────────────────────────
    const CHART_COLORS = {
        primary: '#3b82f6',
        success: '#22c55e',
        warning: '#f59e0b',
        danger: '#ef4444',
        info: '#06b6d4',
        purple: '#8b5cf6',
        pink: '#ec4899',
        indigo: '#6366f1',
        teal: '#14b8a6',
        orange: '#f97316',

        // Risk levels
        riskVeryHigh: '#721c24',
        riskHigh: '#dc3545',
        riskMediumHigh: '#fd7e14',
        riskMedium: '#ffc107',
        riskLow: '#28a745',

        // Grays
        gray100: '#f1f5f9',
        gray200: '#e2e8f0',
        gray300: '#cbd5e1',
        gray400: '#94a3b8',
        gray500: '#64748b',
        gray600: '#475569',
        gray700: '#334155',
        gray800: '#1e293b',
        gray900: '#0f172a'
    };

    function getChartDefaults() {
        return {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    labels: {
                        font: { family: "'Inter', sans-serif", size: 12 },
                        padding: 12,
                        usePointStyle: true,
                        pointStyleWidth: 10
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { family: "'Inter', sans-serif", size: 13, weight: '600' },
                    bodyFont: { family: "'Inter', sans-serif", size: 12 },
                    padding: 12,
                    cornerRadius: 8,
                    boxPadding: 4
                }
            },
            scales: {
                x: {
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { family: "'Inter', sans-serif", size: 11 } }
                },
                y: {
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { family: "'Inter', sans-serif", size: 11 } }
                }
            }
        };
    }

    // ── DataTable Default Configuration ────────────────────────
    function getDataTableDefaults() {
        return {
            responsive: true,
            pageLength: 25,
            language: {
                search: '',
                searchPlaceholder: 'Search...',
                lengthMenu: 'Show _MENU_',
                info: 'Showing _START_ to _END_ of _TOTAL_',
                emptyTable: 'No data available',
                loadingRecords: 'Loading...',
                processing: '<i class="fas fa-spinner fa-spin me-2"></i>Processing...'
            },
            dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            drawCallback: function() {
                // Re-init tooltips after table redraw
                const tooltips = this.api().table().container()
                    .querySelectorAll('[data-bs-toggle="tooltip"]');
                tooltips.forEach((el) => new bootstrap.Tooltip(el));
            }
        };
    }

    // ── Form Validation Helpers ────────────────────────────────
    function validateForm(form) {
        let isValid = true;

        // Clear previous errors
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach((el) => el.remove());

        // Validate required fields
        form.querySelectorAll('[required]').forEach((input) => {
            if (!input.value.trim()) {
                markInvalid(input, 'This field is required');
                isValid = false;
            }
        });

        // Validate email fields
        form.querySelectorAll('input[type="email"]').forEach((input) => {
            if (input.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
                markInvalid(input, 'Please enter a valid email address');
                isValid = false;
            }
        });

        // Validate number ranges
        form.querySelectorAll('input[type="number"]').forEach((input) => {
            const val = parseFloat(input.value);
            const min = parseFloat(input.min);
            const max = parseFloat(input.max);

            if (input.value && isNaN(val)) {
                markInvalid(input, 'Please enter a valid number');
                isValid = false;
            } else if (!isNaN(min) && val < min) {
                markInvalid(input, `Minimum value is ${min}`);
                isValid = false;
            } else if (!isNaN(max) && val > max) {
                markInvalid(input, `Maximum value is ${max}`);
                isValid = false;
            }
        });

        return isValid;
    }

    function markInvalid(input, message) {
        input.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        input.parentNode.appendChild(feedback);
    }

    // ── Date/Time Formatting ───────────────────────────────────
    function formatDate(dateStr, format = 'short') {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;

        const options = {
            short: { year: 'numeric', month: 'short', day: 'numeric' },
            long: { year: 'numeric', month: 'long', day: 'numeric' },
            full: { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' },
            iso: null,
            relative: null
        };

        if (format === 'iso') {
            return date.toISOString().split('T')[0];
        }

        if (format === 'relative') {
            return getRelativeTime(date);
        }

        return date.toLocaleDateString('en-US', options[format] || options.short);
    }

    function getRelativeTime(date) {
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffMins < 1) return 'just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        if (diffDays < 30) return `${Math.floor(diffDays / 7)}w ago`;
        return formatDate(date, 'short');
    }

    // ── Number Formatting ──────────────────────────────────────
    function formatNumber(value, options = {}) {
        if (value === null || value === undefined) return '-';

        const {
            decimals = 2,
            unit = '',
            prefix = '',
            compact = false
        } = options;

        const num = parseFloat(value);
        if (isNaN(num)) return value;

        if (compact) {
            if (Math.abs(num) >= 1e6) return `${prefix}${(num / 1e6).toFixed(1)}M ${unit}`.trim();
            if (Math.abs(num) >= 1e3) return `${prefix}${(num / 1e3).toFixed(1)}K ${unit}`.trim();
        }

        return `${prefix}${num.toFixed(decimals)} ${unit}`.trim();
    }

    function formatCurrency(value, currency = 'USD') {
        if (value === null || value === undefined) return '-';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    }

    function formatThickness(value, unit = 'mm') {
        if (value === null || value === undefined) return '-';
        const num = parseFloat(value);
        if (isNaN(num)) return value;
        return `${num.toFixed(2)} ${unit}`;
    }

    function formatCorrosionRate(value, unit = 'mm/yr') {
        if (value === null || value === undefined) return '-';
        const num = parseFloat(value);
        if (isNaN(num)) return value;
        return `${num.toFixed(3)} ${unit}`;
    }

    // ── Utility Functions ──────────────────────────────────────
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    function throttle(func, limit) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => { inThrottle = false; }, limit);
            }
        };
    }

    // ── Initialize ─────────────────────────────────────────────
    function init() {
        initGlobalSearch();
        initKeyboardShortcuts();
        initAutoSave();
        initSessionTimeout();

        // Apply Chart.js defaults globally
        if (typeof Chart !== 'undefined') {
            const defaults = getChartDefaults();
            Object.assign(Chart.defaults, {
                font: { family: "'Inter', sans-serif" },
                color: '#64748b'
            });
        }

        console.log('[App] RBI Engineering Suite initialized');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ── Public API ─────────────────────────────────────────────
    return {
        request,
        showToast,
        validateForm,
        formatDate,
        formatNumber,
        formatCurrency,
        formatThickness,
        formatCorrosionRate,
        startDashboardRefresh,
        stopDashboardRefresh,
        refreshCurrentView,
        getChartDefaults,
        getDataTableDefaults,
        CHART_COLORS,
        debounce,
        throttle,
        CONFIG
    };
})();
