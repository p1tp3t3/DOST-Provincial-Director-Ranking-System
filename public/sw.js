// Service worker for browser push notifications (KPI reporting reminders).
// Registered by resources/js/Composables/usePushNotifications.js.

const LOGO = '/assets/logo.png';

self.addEventListener('push', (event) => {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch {
        payload = { title: 'PRISM', body: event.data.text() };
    }

    const { title = 'PRISM', ...options } = payload;
    // Always the PRISM logo, even if a push arrives without one.
    options.icon  ??= LOGO;
    options.badge ??= LOGO;
    event.waitUntil(self.registration.showNotification(title, options));
});

// Open the page the notification points to, reusing an open tab when there is one.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = new URL(event.notification.data?.url || '/dashboard', self.location.origin).href;

    event.waitUntil((async () => {
        const tabs = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const tab  = tabs.find((c) => c.url.startsWith(self.location.origin));
        if (tab) {
            await tab.focus();
            return tab.navigate(url);
        }
        return self.clients.openWindow(url);
    })());
});
