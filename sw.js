/**
 * Service Worker - RBI Engineering Suite
 * Handles caching, offline support, background sync, and push notifications
 */

const CACHE_VERSION = 'rbi-v1.0.0';
const STATIC_CACHE = `static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `dynamic-${CACHE_VERSION}`;
const API_CACHE = `api-${CACHE_VERSION}`;

// Critical assets to precache on install
const PRECACHE_ASSETS = [
  '/rbi/offline.php',
  '/rbi/static/css/mobile.css',
  '/rbi/static/css/pwa.css',
  '/rbi/static/js/app.js',
  '/rbi/static/js/mobile.js',
  '/rbi/static/js/pwa.js',
  '/rbi/static/icons/icon-192x192.png',
  '/rbi/static/icons/icon-512x512.png',
  '/rbi/manifest.json',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
  'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js'
];

// Routes that should use network-first strategy
const NETWORK_FIRST_PATTERNS = [
  /\/api\//,
  /\/admin\//,
  /\.php\?/,
  /logout\.php/,
  /login\.php/
];

// Routes that should never be cached
const NO_CACHE_PATTERNS = [
  /\/logout\.php/,
  /\/api\/.*\?/,
  /csrf/i
];

// ── Install Event ──────────────────────────────────────────
self.addEventListener('install', (event) => {
  console.log('[SW] Installing service worker:', CACHE_VERSION);

  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => {
        console.log('[SW] Precaching critical assets');
        return cache.addAll(PRECACHE_ASSETS).catch((err) => {
          console.warn('[SW] Some precache assets failed:', err);
          // Add assets individually so one failure doesn't block all
          return Promise.allSettled(
            PRECACHE_ASSETS.map((url) => cache.add(url).catch(() => console.warn('[SW] Failed to cache:', url)))
          );
        });
      })
      .then(() => self.skipWaiting())
  );
});

// ── Activate Event ─────────────────────────────────────────
self.addEventListener('activate', (event) => {
  console.log('[SW] Activating service worker:', CACHE_VERSION);

  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((name) => {
              return name !== STATIC_CACHE &&
                     name !== DYNAMIC_CACHE &&
                     name !== API_CACHE;
            })
            .map((name) => {
              console.log('[SW] Deleting old cache:', name);
              return caches.delete(name);
            })
        );
      })
      .then(() => self.clients.claim())
  );
});

// ── Fetch Event ────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip chrome-extension and other non-http requests
  if (!url.protocol.startsWith('http')) {
    return;
  }

  // Skip no-cache patterns
  if (NO_CACHE_PATTERNS.some((pattern) => pattern.test(url.pathname))) {
    return;
  }

  // Handle navigation requests
  if (request.mode === 'navigate') {
    event.respondWith(handleNavigationRequest(request));
    return;
  }

  // API calls: network-first
  if (NETWORK_FIRST_PATTERNS.some((pattern) => pattern.test(url.pathname))) {
    event.respondWith(networkFirst(request, API_CACHE));
    return;
  }

  // Static assets: cache-first
  if (isStaticAsset(url)) {
    event.respondWith(cacheFirst(request, STATIC_CACHE));
    return;
  }

  // Everything else: network-first with dynamic cache
  event.respondWith(networkFirst(request, DYNAMIC_CACHE));
});

// ── Cache Strategies ───────────────────────────────────────
async function cacheFirst(request, cacheName) {
  try {
    const cached = await caches.match(request);
    if (cached) {
      return cached;
    }

    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch (err) {
    const cached = await caches.match(request);
    if (cached) return cached;
    throw err;
  }
}

async function networkFirst(request, cacheName) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch (err) {
    const cached = await caches.match(request);
    if (cached) {
      return cached;
    }
    throw err;
  }
}

async function handleNavigationRequest(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, response.clone());
    }
    return response;
  } catch (err) {
    // Try cache
    const cached = await caches.match(request);
    if (cached) {
      return cached;
    }

    // Fall back to offline page
    const offlinePage = await caches.match('/rbi/offline.php');
    if (offlinePage) {
      return offlinePage;
    }

    return new Response(getOfflineHTML(), {
      headers: { 'Content-Type': 'text/html' }
    });
  }
}

// ── Helper Functions ───────────────────────────────────────
function isStaticAsset(url) {
  const staticExtensions = [
    '.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg',
    '.ico', '.woff', '.woff2', '.ttf', '.eot', '.webp'
  ];
  return staticExtensions.some((ext) => url.pathname.endsWith(ext)) ||
         url.hostname !== self.location.hostname;
}

function getOfflineHTML() {
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Offline | RBI Engineering Suite</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
           background: #0f172a; color: #e2e8f0; display: flex; align-items: center;
           justify-content: center; min-height: 100vh; padding: 20px; }
    .container { text-align: center; max-width: 400px; }
    .icon { font-size: 64px; margin-bottom: 24px; }
    h1 { font-size: 1.5rem; margin-bottom: 12px; }
    p { color: #94a3b8; margin-bottom: 24px; }
    button { background: #3b82f6; color: white; border: none; padding: 12px 32px;
             border-radius: 8px; font-size: 1rem; cursor: pointer; }
    button:hover { background: #2563eb; }
  </style>
</head>
<body>
  <div class="container">
    <div class="icon">&#x1F4E1;</div>
    <h1>You're Offline</h1>
    <p>Please check your internet connection and try again.</p>
    <button onclick="window.location.reload()">Retry Connection</button>
  </div>
</body>
</html>`;
}

// ── Background Sync ────────────────────────────────────────
self.addEventListener('sync', (event) => {
  console.log('[SW] Background sync event:', event.tag);

  if (event.tag === 'sync-form-data') {
    event.waitUntil(syncFormData());
  }

  if (event.tag === 'sync-inspection-data') {
    event.waitUntil(syncInspectionData());
  }

  if (event.tag === 'sync-readings') {
    event.waitUntil(syncReadings());
  }
});

async function syncFormData() {
  try {
    const db = await openDB();
    const tx = db.transaction('pending-forms', 'readonly');
    const store = tx.objectStore('pending-forms');
    const entries = await getAllFromStore(store);

    for (const entry of entries) {
      try {
        const response = await fetch(entry.url, {
          method: entry.method || 'POST',
          headers: entry.headers || { 'Content-Type': 'application/json' },
          body: JSON.stringify(entry.data)
        });

        if (response.ok) {
          const deleteTx = db.transaction('pending-forms', 'readwrite');
          deleteTx.objectStore('pending-forms').delete(entry.id);

          // Notify clients
          const clients = await self.clients.matchAll();
          clients.forEach((client) => {
            client.postMessage({
              type: 'SYNC_COMPLETE',
              id: entry.id,
              success: true
            });
          });
        }
      } catch (err) {
        console.warn('[SW] Sync failed for entry:', entry.id, err);
      }
    }
  } catch (err) {
    console.error('[SW] syncFormData error:', err);
  }
}

async function syncInspectionData() {
  return syncFromStore('pending-inspections');
}

async function syncReadings() {
  return syncFromStore('pending-readings');
}

async function syncFromStore(storeName) {
  try {
    const db = await openDB();
    const tx = db.transaction(storeName, 'readonly');
    const store = tx.objectStore(storeName);
    const entries = await getAllFromStore(store);

    for (const entry of entries) {
      try {
        const response = await fetch(entry.url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(entry.data)
        });

        if (response.ok) {
          const deleteTx = db.transaction(storeName, 'readwrite');
          deleteTx.objectStore(storeName).delete(entry.id);

          const clients = await self.clients.matchAll();
          clients.forEach((client) => {
            client.postMessage({
              type: 'SYNC_COMPLETE',
              store: storeName,
              id: entry.id,
              success: true
            });
          });
        }
      } catch (err) {
        console.warn(`[SW] Sync failed for ${storeName}:`, entry.id);
      }
    }
  } catch (err) {
    console.error(`[SW] sync ${storeName} error:`, err);
  }
}

// ── IndexedDB Helpers ──────────────────────────────────────
function openDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('rbi-offline', 1);

    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('pending-forms')) {
        db.createObjectStore('pending-forms', { keyPath: 'id', autoIncrement: true });
      }
      if (!db.objectStoreNames.contains('pending-inspections')) {
        db.createObjectStore('pending-inspections', { keyPath: 'id', autoIncrement: true });
      }
      if (!db.objectStoreNames.contains('pending-readings')) {
        db.createObjectStore('pending-readings', { keyPath: 'id', autoIncrement: true });
      }
    };

    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

function getAllFromStore(store) {
  return new Promise((resolve, reject) => {
    const request = store.getAll();
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

// ── Push Notifications ─────────────────────────────────────
self.addEventListener('push', (event) => {
  console.log('[SW] Push notification received');

  let data = {
    title: 'RBI Engineering Suite',
    body: 'You have a new notification',
    icon: '/rbi/static/icons/icon-192x192.png',
    badge: '/rbi/static/icons/icon-72x72.png',
    tag: 'rbi-notification',
    url: '/rbi/dashboard.php'
  };

  if (event.data) {
    try {
      const payload = event.data.json();
      data = { ...data, ...payload };
    } catch (e) {
      data.body = event.data.text();
    }
  }

  const options = {
    body: data.body,
    icon: data.icon || '/rbi/static/icons/icon-192x192.png',
    badge: data.badge || '/rbi/static/icons/icon-72x72.png',
    tag: data.tag || 'rbi-notification',
    vibrate: [200, 100, 200],
    data: {
      url: data.url || '/rbi/dashboard.php',
      dateOfArrival: Date.now()
    },
    actions: data.actions || [
      { action: 'view', title: 'View Details' },
      { action: 'dismiss', title: 'Dismiss' }
    ],
    requireInteraction: data.requireInteraction || false
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

self.addEventListener('notificationclick', (event) => {
  console.log('[SW] Notification clicked:', event.action);
  event.notification.close();

  if (event.action === 'dismiss') {
    return;
  }

  const url = event.notification.data?.url || '/rbi/dashboard.php';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        // Focus existing window if available
        for (const client of clientList) {
          if (client.url.includes('/rbi/') && 'focus' in client) {
            client.navigate(url);
            return client.focus();
          }
        }
        // Open new window
        if (self.clients.openWindow) {
          return self.clients.openWindow(url);
        }
      })
  );
});

self.addEventListener('notificationclose', (event) => {
  console.log('[SW] Notification dismissed');
});

// ── Message Handler ────────────────────────────────────────
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  if (event.data && event.data.type === 'CLEAR_CACHE') {
    event.waitUntil(
      caches.keys().then((names) => Promise.all(names.map((n) => caches.delete(n))))
    );
  }

  if (event.data && event.data.type === 'CACHE_URLS') {
    event.waitUntil(
      caches.open(DYNAMIC_CACHE)
        .then((cache) => cache.addAll(event.data.urls || []))
    );
  }
});

// ── Periodic Background Sync ───────────────────────────────
self.addEventListener('periodicsync', (event) => {
  if (event.tag === 'refresh-dashboard') {
    event.waitUntil(refreshDashboardData());
  }
});

async function refreshDashboardData() {
  try {
    const response = await fetch('/rbi/api/dashboard.php');
    if (response.ok) {
      const cache = await caches.open(API_CACHE);
      await cache.put('/rbi/api/dashboard.php', response);
    }
  } catch (err) {
    console.warn('[SW] Dashboard refresh failed:', err);
  }
}
