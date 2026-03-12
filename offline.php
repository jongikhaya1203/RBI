<?php
/**
 * Offline Fallback Page - RBI Engineering Suite
 * Displayed when user is offline and requested page is not cached
 */
$pageTitle = 'Offline';
$isOfflinePage = true;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#3b82f6">
    <title>Offline | RBI Engineering Suite</title>

    <!-- Bootstrap 5 CSS (may load from cache) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }

        .offline-container {
            max-width: 480px;
            width: 100%;
            text-align: center;
        }

        .offline-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 32px;
            animation: pulse 2s ease-in-out infinite;
        }

        .offline-icon i {
            font-size: 48px;
            color: #3b82f6;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        .offline-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: #f1f5f9;
        }

        .offline-message {
            color: #94a3b8;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .btn-retry {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-retry:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .btn-retry:active {
            transform: translateY(0);
        }

        .btn-retry.loading .fa-rotate-right { display: none; }
        .btn-retry.loading .fa-spinner { display: inline-block; }
        .btn-retry .fa-spinner { display: none; }

        .cached-pages {
            margin-top: 48px;
            text-align: left;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .cached-pages h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .cached-page-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .cached-page-list li {
            margin-bottom: 4px;
        }

        .cached-page-list a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.15s;
        }

        .cached-page-list a:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #f1f5f9;
        }

        .cached-page-list a i {
            color: #3b82f6;
            width: 20px;
            text-align: center;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            margin-top: 32px;
            font-size: 0.85rem;
            color: #64748b;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ef4444;
        }

        .status-dot.online {
            background: #22c55e;
            animation: none;
        }

        .connection-info {
            margin-top: 16px;
            font-size: 0.8rem;
            color: #475569;
        }

        .brand-footer {
            margin-top: 48px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #475569;
            font-size: 0.8rem;
        }

        .brand-footer i { color: #3b82f6; }
    </style>
</head>
<body>

<div class="offline-container">
    <div class="offline-icon">
        <i class="fas fa-wifi-slash" id="offlineIconEl"></i>
    </div>

    <h1 class="offline-title">You're Currently Offline</h1>

    <p class="offline-message">
        It looks like you've lost your internet connection. Some features may be
        limited, but you can still access previously viewed pages from the cache.
    </p>

    <button class="btn-retry" id="retryBtn" onclick="retryConnection()">
        <i class="fas fa-rotate-right"></i>
        <i class="fas fa-spinner fa-spin"></i>
        Retry Connection
    </button>

    <div class="status-indicator">
        <span class="status-dot" id="statusDot"></span>
        <span id="statusText">No internet connection</span>
    </div>

    <div class="connection-info" id="connectionInfo"></div>

    <div class="cached-pages" id="cachedPages" style="display: none;">
        <h3><i class="fas fa-clock-rotate-left me-2"></i>Recently Viewed Pages</h3>
        <ul class="cached-page-list" id="cachedPageList"></ul>
    </div>

    <div class="brand-footer">
        <i class="fas fa-shield-halved"></i>
        <span>RBI Engineering Suite</span>
    </div>
</div>

<script>
// Map of URL patterns to page info
const PAGE_MAP = {
    'dashboard': { icon: 'fa-gauge-high', label: 'Dashboard' },
    'assets/index': { icon: 'fa-industry', label: 'Asset Register' },
    'assets/view': { icon: 'fa-eye', label: 'Asset Details' },
    'assets/hierarchy': { icon: 'fa-sitemap', label: 'Asset Hierarchy' },
    'risk/matrix': { icon: 'fa-table-cells', label: 'Risk Matrix' },
    'risk/assessments': { icon: 'fa-clipboard-check', label: 'Risk Assessments' },
    'inspections/schedule': { icon: 'fa-calendar', label: 'Inspection Schedule' },
    'inspections/plans': { icon: 'fa-file-lines', label: 'Inspection Plans' },
    'inspections/tasks': { icon: 'fa-list-check', label: 'Inspection Tasks' },
    'analytics/remaining-life': { icon: 'fa-hourglass-half', label: 'Remaining Life' },
    'analytics/corrosion-rates': { icon: 'fa-chart-line', label: 'Corrosion Rates' },
    'reports/index': { icon: 'fa-file-pdf', label: 'Reports' }
};

function getPageInfo(url) {
    for (const [pattern, info] of Object.entries(PAGE_MAP)) {
        if (url.includes(pattern)) {
            return info;
        }
    }
    // Extract filename for unknown pages
    const match = url.match(/\/([^/]+)\.php/);
    if (match) {
        return {
            icon: 'fa-file',
            label: match[1].charAt(0).toUpperCase() + match[1].slice(1).replace(/-/g, ' ')
        };
    }
    return null;
}

// Load cached pages list
async function loadCachedPages() {
    if (!('caches' in window)) return;

    try {
        const cacheNames = await caches.keys();
        const pages = new Set();

        for (const name of cacheNames) {
            const cache = await caches.open(name);
            const keys = await cache.keys();

            for (const request of keys) {
                const url = new URL(request.url);
                if (url.pathname.endsWith('.php') &&
                    url.pathname.includes('/rbi/') &&
                    !url.pathname.includes('offline.php') &&
                    !url.pathname.includes('logout.php') &&
                    !url.pathname.includes('/api/')) {
                    pages.add(url.pathname);
                }
            }
        }

        if (pages.size > 0) {
            const list = document.getElementById('cachedPageList');
            const container = document.getElementById('cachedPages');
            container.style.display = 'block';
            list.innerHTML = '';

            for (const pageUrl of Array.from(pages).slice(0, 8)) {
                const info = getPageInfo(pageUrl);
                if (!info) continue;

                const li = document.createElement('li');
                li.innerHTML = `<a href="${pageUrl}"><i class="fas ${info.icon}"></i>${info.label}</a>`;
                list.appendChild(li);
            }
        }
    } catch (err) {
        console.warn('Failed to load cached pages:', err);
    }
}

// Retry connection
function retryConnection() {
    const btn = document.getElementById('retryBtn');
    btn.classList.add('loading');
    btn.disabled = true;

    fetch('/rbi/dashboard.php', { method: 'HEAD', cache: 'no-store' })
        .then((response) => {
            if (response.ok) {
                window.location.href = '/rbi/dashboard.php';
            } else {
                throw new Error('Server error');
            }
        })
        .catch(() => {
            btn.classList.remove('loading');
            btn.disabled = false;
        });
}

// Monitor online status
function updateOnlineStatus() {
    const dot = document.getElementById('statusDot');
    const text = document.getElementById('statusText');
    const iconEl = document.getElementById('offlineIconEl');

    if (navigator.onLine) {
        dot.classList.add('online');
        text.textContent = 'Connection restored - redirecting...';
        iconEl.className = 'fas fa-wifi';
        setTimeout(() => {
            window.location.href = '/rbi/dashboard.php';
        }, 1000);
    } else {
        dot.classList.remove('online');
        text.textContent = 'No internet connection';
        iconEl.className = 'fas fa-wifi-slash';
    }
}

// Connection info
function showConnectionInfo() {
    const info = document.getElementById('connectionInfo');
    const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;

    if (conn) {
        const parts = [];
        if (conn.type && conn.type !== 'unknown') parts.push(`Type: ${conn.type}`);
        if (conn.effectiveType) parts.push(`Speed: ${conn.effectiveType.toUpperCase()}`);
        if (parts.length) info.textContent = parts.join(' | ');
    }
}

// Init
window.addEventListener('online', updateOnlineStatus);
window.addEventListener('offline', updateOnlineStatus);
updateOnlineStatus();
showConnectionInfo();
loadCachedPages();
</script>

</body>
</html>
