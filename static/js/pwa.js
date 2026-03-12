/**
 * PWA JavaScript - RBI Engineering Suite
 * Service worker registration, install prompt, online/offline status,
 * background sync, push notifications, cache management, app updates
 */

'use strict';

const RBI_PWA = (() => {
    let deferredPrompt = null;
    let swRegistration = null;
    let isOnline = navigator.onLine;
    let updateAvailable = false;

    // ── Service Worker Registration ────────────────────────────
    async function registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            console.warn('[PWA] Service workers not supported');
            return null;
        }

        try {
            const registration = await navigator.serviceWorker.register('/rbi/sw.js', {
                scope: '/rbi/'
            });

            swRegistration = registration;
            console.log('[PWA] Service worker registered:', registration.scope);

            // Check for updates
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                if (!newWorker) return;

                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        // New version available
                        updateAvailable = true;
                        showUpdateToast();
                    }
                });
            });

            // Listen for messages from service worker
            navigator.serviceWorker.addEventListener('message', handleSWMessage);

            // Check for existing waiting worker
            if (registration.waiting) {
                updateAvailable = true;
                showUpdateToast();
            }

            return registration;
        } catch (err) {
            console.error('[PWA] Service worker registration failed:', err);
            return null;
        }
    }

    // ── Handle SW Messages ─────────────────────────────────────
    function handleSWMessage(event) {
        const { data } = event;

        if (data.type === 'SYNC_COMPLETE') {
            showToast('Data synced successfully', 'success');
            updateSyncIndicator('synced');

            // Refresh current page data if applicable
            if (typeof RBI_APP !== 'undefined' && RBI_APP.refreshCurrentView) {
                RBI_APP.refreshCurrentView();
            }
        }
    }

    // ── Install Prompt Handling ────────────────────────────────
    function initInstallPrompt() {
        // Check if already installed
        if (window.matchMedia('(display-mode: standalone)').matches) {
            console.log('[PWA] App is running in standalone mode');
            return;
        }

        // Check if user dismissed the banner recently
        const dismissed = localStorage.getItem('rbi-install-dismissed');
        if (dismissed) {
            const dismissedTime = parseInt(dismissed, 10);
            const daysSince = (Date.now() - dismissedTime) / (1000 * 60 * 60 * 24);
            if (daysSince < 7) return; // Don't show for 7 days after dismiss
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;

            // Show install banner after a delay
            setTimeout(() => {
                showInstallBanner();
            }, 3000);
        });

        window.addEventListener('appinstalled', () => {
            console.log('[PWA] App installed successfully');
            deferredPrompt = null;
            hideInstallBanner();
            showToast('App installed successfully!', 'success');
        });
    }

    function showInstallBanner() {
        const banner = document.getElementById('installBanner');
        if (banner) {
            banner.classList.add('show');
        }
    }

    function hideInstallBanner() {
        const banner = document.getElementById('installBanner');
        if (banner) {
            banner.classList.remove('show');
        }
    }

    async function installApp() {
        if (!deferredPrompt) {
            console.warn('[PWA] No install prompt available');
            return false;
        }

        try {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;

            console.log('[PWA] Install prompt outcome:', outcome);
            deferredPrompt = null;
            hideInstallBanner();

            return outcome === 'accepted';
        } catch (err) {
            console.error('[PWA] Install error:', err);
            return false;
        }
    }

    function dismissInstall() {
        hideInstallBanner();
        localStorage.setItem('rbi-install-dismissed', Date.now().toString());
    }

    // ── Online/Offline Status ──────────────────────────────────
    function initOnlineStatus() {
        updateOnlineStatus();

        window.addEventListener('online', () => {
            isOnline = true;
            updateOnlineStatus();
            showOnlineBar();

            // Trigger sync
            if (swRegistration) {
                swRegistration.sync.register('sync-form-data').catch(() => {});
                swRegistration.sync.register('sync-inspection-data').catch(() => {});
                swRegistration.sync.register('sync-readings').catch(() => {});
            }
        });

        window.addEventListener('offline', () => {
            isOnline = false;
            updateOnlineStatus();
        });
    }

    function updateOnlineStatus() {
        const offlineBar = document.getElementById('offlineBar');
        const body = document.body;

        if (!isOnline) {
            body.classList.add('is-offline');
            if (offlineBar) offlineBar.classList.add('show');
        } else {
            body.classList.remove('is-offline');
            if (offlineBar) offlineBar.classList.remove('show');
        }
    }

    function showOnlineBar() {
        const bar = document.getElementById('onlineBar');
        if (bar) {
            bar.classList.add('show');
            setTimeout(() => {
                bar.classList.remove('show');
            }, 3000);
        }
    }

    // ── Background Sync Registration ───────────────────────────
    async function registerSync(tag, data) {
        if (!('serviceWorker' in navigator) || !('SyncManager' in window)) {
            // Fallback: try sending immediately
            console.warn('[PWA] Background sync not supported, sending immediately');
            return false;
        }

        try {
            // Store data in IndexedDB
            const db = await openDB();
            const storeName = getStoreForTag(tag);
            const tx = db.transaction(storeName, 'readwrite');
            const store = tx.objectStore(storeName);

            await new Promise((resolve, reject) => {
                const request = store.add({
                    ...data,
                    timestamp: Date.now()
                });
                request.onsuccess = resolve;
                request.onerror = reject;
            });

            // Register sync
            const reg = await navigator.serviceWorker.ready;
            await reg.sync.register(tag);

            updateSyncIndicator('syncing');
            console.log('[PWA] Background sync registered:', tag);
            return true;
        } catch (err) {
            console.error('[PWA] Failed to register sync:', err);
            return false;
        }
    }

    function getStoreForTag(tag) {
        const map = {
            'sync-form-data': 'pending-forms',
            'sync-inspection-data': 'pending-inspections',
            'sync-readings': 'pending-readings'
        };
        return map[tag] || 'pending-forms';
    }

    // ── Push Notifications ─────────────────────────────────────
    async function requestNotificationPermission() {
        if (!('Notification' in window)) {
            console.warn('[PWA] Notifications not supported');
            return 'denied';
        }

        if (Notification.permission === 'granted') {
            return 'granted';
        }

        if (Notification.permission === 'denied') {
            return 'denied';
        }

        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            await subscribeToPush();
        }
        return permission;
    }

    async function subscribeToPush() {
        if (!swRegistration) return null;

        try {
            const subscription = await swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(getVapidPublicKey())
            });

            // Send subscription to server
            await fetch('/rbi/api/push-subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(subscription)
            });

            console.log('[PWA] Push subscription successful');
            return subscription;
        } catch (err) {
            console.error('[PWA] Push subscription failed:', err);
            return null;
        }
    }

    function urlBase64ToUint8Array(base64String) {
        if (!base64String) return new Uint8Array();
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function getVapidPublicKey() {
        // This should be configured in your server settings
        return document.querySelector('meta[name="vapid-public-key"]')?.content || '';
    }

    // ── Cache Management ───────────────────────────────────────
    async function getCacheSize() {
        if (!('caches' in window)) return 0;

        const names = await caches.keys();
        let totalSize = 0;

        for (const name of names) {
            const cache = await caches.open(name);
            const keys = await cache.keys();
            totalSize += keys.length;
        }

        return totalSize;
    }

    async function clearAllCaches() {
        if (!('caches' in window)) return;

        const names = await caches.keys();
        await Promise.all(names.map((name) => caches.delete(name)));
        console.log('[PWA] All caches cleared');
    }

    async function clearCache(cacheName) {
        if (!('caches' in window)) return;
        await caches.delete(cacheName);
        console.log('[PWA] Cache cleared:', cacheName);
    }

    // ── App Update ─────────────────────────────────────────────
    function showUpdateToast() {
        const toast = document.getElementById('updateToast');
        if (toast) {
            toast.classList.add('show');
        }
    }

    function applyUpdate() {
        if (!swRegistration || !swRegistration.waiting) return;

        swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });

        // Reload after new SW takes over
        let refreshing = false;
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (refreshing) return;
            refreshing = true;
            window.location.reload();
        });
    }

    // ── Share API ──────────────────────────────────────────────
    async function shareContent(data) {
        if (!navigator.share) {
            // Fallback: copy to clipboard
            if (data.url) {
                await navigator.clipboard.writeText(data.url);
                showToast('Link copied to clipboard', 'info');
            }
            return false;
        }

        try {
            await navigator.share({
                title: data.title || 'RBI Engineering Suite',
                text: data.text || '',
                url: data.url || window.location.href
            });
            return true;
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('[PWA] Share failed:', err);
            }
            return false;
        }
    }

    // ── Periodic Sync ──────────────────────────────────────────
    async function registerPeriodicSync() {
        if (!('periodicSync' in (swRegistration || {}))) return;

        try {
            const status = await navigator.permissions.query({ name: 'periodic-background-sync' });
            if (status.state === 'granted') {
                await swRegistration.periodicSync.register('refresh-dashboard', {
                    minInterval: 60 * 60 * 1000 // 1 hour
                });
                console.log('[PWA] Periodic sync registered');
            }
        } catch (err) {
            console.warn('[PWA] Periodic sync not available:', err);
        }
    }

    // ── Sync Indicator ─────────────────────────────────────────
    function updateSyncIndicator(status) {
        const indicators = document.querySelectorAll('.sync-indicator');
        indicators.forEach((indicator) => {
            indicator.classList.remove('syncing', 'synced', 'error');
            if (status) indicator.classList.add(status);

            const icon = indicator.querySelector('i');
            const text = indicator.querySelector('span');

            switch (status) {
                case 'syncing':
                    if (icon) icon.className = 'fas fa-sync';
                    if (text) text.textContent = 'Syncing...';
                    break;
                case 'synced':
                    if (icon) icon.className = 'fas fa-check';
                    if (text) text.textContent = 'Synced';
                    break;
                case 'error':
                    if (icon) icon.className = 'fas fa-exclamation-triangle';
                    if (text) text.textContent = 'Sync failed';
                    break;
                default:
                    if (icon) icon.className = 'fas fa-cloud';
                    if (text) text.textContent = 'Ready';
            }
        });
    }

    // ── Toast Helper ───────────────────────────────────────────
    function showToast(message, type = 'info') {
        if (typeof RBI_APP !== 'undefined' && RBI_APP.showToast) {
            RBI_APP.showToast(message, type);
        } else {
            console.log(`[Toast ${type}]:`, message);
        }
    }

    // ── IndexedDB Helper ───────────────────────────────────────
    function openDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('rbi-offline', 1);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                ['pending-forms', 'pending-inspections', 'pending-readings'].forEach((name) => {
                    if (!db.objectStoreNames.contains(name)) {
                        db.createObjectStore(name, { keyPath: 'id', autoIncrement: true });
                    }
                });
            };

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // ── Pending Sync Count ─────────────────────────────────────
    async function getPendingSyncCount() {
        try {
            const db = await openDB();
            let total = 0;
            for (const store of ['pending-forms', 'pending-inspections', 'pending-readings']) {
                const tx = db.transaction(store, 'readonly');
                const count = await new Promise((resolve) => {
                    const req = tx.objectStore(store).count();
                    req.onsuccess = () => resolve(req.result);
                    req.onerror = () => resolve(0);
                });
                total += count;
            }
            return total;
        } catch {
            return 0;
        }
    }

    async function updatePendingCount() {
        const count = await getPendingSyncCount();
        const badge = document.querySelector('.offline-bar .sync-count');
        if (badge) {
            badge.textContent = count > 0 ? `${count} pending` : '';
            badge.style.display = count > 0 ? 'inline' : 'none';
        }
    }

    // ── Initialize ─────────────────────────────────────────────
    async function init() {
        await registerServiceWorker();
        initInstallPrompt();
        initOnlineStatus();
        registerPeriodicSync();
        updatePendingCount();

        // Periodic pending count update
        setInterval(updatePendingCount, 30000);

        console.log('[PWA] Initialized');
    }

    // Auto-init on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ── Public API ─────────────────────────────────────────────
    return {
        installApp,
        dismissInstall,
        applyUpdate,
        requestNotificationPermission,
        registerSync,
        shareContent,
        clearAllCaches,
        clearCache,
        getCacheSize,
        getPendingSyncCount,
        get isOnline() { return isOnline; },
        get updateAvailable() { return updateAvailable; },
        get registration() { return swRegistration; }
    };
})();
