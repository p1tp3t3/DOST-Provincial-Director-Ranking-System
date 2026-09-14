import { configureEcho, echo } from '@laravel/echo-vue';


configureEcho({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    wsPath: import.meta.env.VITE_REVERB_PATH ?? '',
});


// helper-functions.js's receiveBroadcast() calls window.Echo directly.
window.Echo = echo();

// Manual connectivity check — trigger from tinker with
// broadcast(new App\Events\TestPing('hi')) and watch this log.
window.Echo.private('kpi-catalog')
    .subscribed(() => console.log('[Echo test] kpi-catalog authenticated'))
    .error((err) => console.log('[Echo test] kpi-catalog auth failed:', err))
    .listen('.test.ping', (e) => {
        console.log('[Echo test] test.ping received:', e);
    });
