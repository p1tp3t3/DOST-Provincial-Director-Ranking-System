import { configureEcho, echo } from '@laravel/echo-vue';


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