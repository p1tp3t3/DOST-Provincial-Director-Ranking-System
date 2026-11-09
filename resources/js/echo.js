import { configureEcho, echo } from '@laravel/echo-vue';
import { router } from '@inertiajs/vue3';


configureEcho({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    // Production: Reverb sits behind the /ws reverse-proxy path (VITE_REVERB_PATH="/ws").
    // wsPath: import.meta.env.VITE_REVERB_PATH ?? '',
});


// helper-functions.js's receiveBroadcast() calls window.Echo directly.
window.Echo = echo();

// Manual connectivity check — trigger from tinker with
// broadcast(new App\Events\TestPing('hi')) and watch these logs.
const pusher = window.Echo.connector.pusher;
console.log('[Echo test] initial connection state:', pusher.connection.state);
pusher.connection.bind('state_change', (states) => {
    console.log('[Echo test] connection state changed:', states.previous, '->', states.current);
});
pusher.connection.bind('error', (err) => {
    console.log('[Echo test] connection error:', err);
});

// kpi-catalog is a private channel, so only subscribe when the page loaded
// with a signed-in user — on the login and landing pages the auth request
// would just come back 403.
const initialPage = JSON.parse(document.getElementById('app')?.dataset.page ?? '{}');

if (initialPage.props?.auth?.user) {
    window.Echo.private('kpi-catalog')
        .subscribed(() => console.log('[Echo test] kpi-catalog authenticated'))
        .error((err) => console.log('[Echo test] kpi-catalog auth failed:', err))
        // Wildcard: logs every event that actually arrives on this channel,
        // whatever its real name is — useful if the expected event name never
        // matches (e.g. after a broadcastAs() change that hasn't deployed yet).
        .listenToAll((eventName, data) => {
            console.log('[Echo test] channel event received:', eventName, data);
        })
        .listen('TestPing', (e) => {
            console.log('[Echo test] TestPing received:', e);
        });
}

// ── Maintenance mode, live ─────────────────────────────────────────────────
// When the super admin toggles maintenance mode (MaintenanceModeChanged on the
// public "system" channel), open pages react without a manual refresh:
//   on  → everyone except super admins reloads; CheckMaintenanceMode then sends
//         them to the notice with the page they were on, encrypted, as ?return=.
//   off → anyone on the notice goes back to that page (the server decrypts it
//         into the page's return_to prop). Users signed out by maintenance pass
//         through the login first and land back there after it.
// Public channel: signed-out visitors on the landing/login pages are affected too.
const MAINTENANCE_NOTICE = '/maintenance-notice';
let currentPage = initialPage;
router.on('navigate', (event) => { currentPage = event.detail.page; });

window.Echo.channel('system').listen('.maintenance.changed', ({ enabled }) => {
    const path     = window.location.pathname;
    const onNotice = path === MAINTENANCE_NOTICE;

    if (enabled) {
        const isSuperAdmin = currentPage?.props?.auth?.user?.role === 'super_admin';
        // The break-glass console stays usable during maintenance.
        if (onNotice || isSuperAdmin || path.startsWith('/admin/login')) return;
        window.location.reload();
    } else if (onNotice) {
        window.location.assign(currentPage?.props?.return_to || '/login');
    }
});
