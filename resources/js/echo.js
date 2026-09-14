import { configureEcho, echo } from '@laravel/echo-vue';

const key = import.meta.env.VITE_REVERB_APP_KEY;

// Without a key Pusher throws during construction, which happens at import
// time and takes the whole app down with a blank page. Broadcasting is
// optional locally (BROADCAST_CONNECTION=log), so degrade to a no-op instead.
if (!key) {
    console.warn('[Echo] VITE_REVERB_APP_KEY is not set - realtime updates are disabled.');

    const noop = () => stub;
    const stub = {
        listen: noop, stopListening: noop, subscribed: noop, error: noop,
        whisper: noop, listenForWhisper: noop, notification: noop,
    };

    window.Echo = {
        private: noop, channel: noop, join: noop, encryptedPrivate: noop,
        leave: () => {}, leaveChannel: () => {}, disconnect: () => {},
    };
} else {
    configureEcho({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        wsPath: import.meta.env.VITE_REVERB_PATH ?? '',
    });

    // helper-functions.js's receiveBroadcast() calls window.Echo directly.
    window.Echo = echo();
}
