// Browser push notifications (KPI reporting reminders).
//
// Registers public/sw.js, subscribes this browser with the server's VAPID
// public key (shared as the `vapidPublicKey` Inertia prop) and saves the
// subscription via /push-subscriptions, so the reminder jobs can reach it.
// Browsers only allow push on HTTPS (or localhost), and most only show the
// permission prompt after a click, so subscribing is started by a button.

import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const supported = typeof window !== 'undefined'
    && 'serviceWorker' in navigator
    && 'PushManager' in window
    && 'Notification' in window
    && window.isSecureContext;

// Shared across components so every bell shows the same state.
const permission = ref(supported ? Notification.permission : 'unsupported');
const subscribed = ref(false);
const busy       = ref(false);
const error      = ref('');
const checked    = ref(false); // refresh() has finished looking at this browser

const urlBase64ToUint8Array = (base64) => {
    const padding = '='.repeat((4 - (base64.length % 4)) % 4);
    const raw = atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
};

const registration = () => navigator.serviceWorker.register('/sw.js');

const save = (subscription) => {
    const json = subscription.toJSON();
    return axios.post('/push-subscriptions', {
        endpoint: json.endpoint,
        keys: json.keys,
        contentEncoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0],
    });
};

export function usePushNotifications() {
    const page   = usePage();
    const vapidKey = computed(() => page.props.vapidPublicKey);
    const available = computed(() => supported && !!vapidKey.value);

    // On load: if this browser already allowed notifications, make sure the
    // server has its subscription (e.g. after logging in as someone else), and
    // subscribe silently when permission was granted but nothing is subscribed.
    const refresh = async () => {
        if (!available.value || checked.value) return;
        try {
            const reg = await registration();
            const sub = await reg.pushManager.getSubscription();
            subscribed.value = !!sub;
            if (Notification.permission !== 'granted') return;
            if (sub) await save(sub);
            else await enable();
        } catch {
            // Leave the toggle usable; enable() reports real errors.
        } finally {
            checked.value = true;
        }
    };

    async function enable() {
        if (!available.value) return;
        busy.value  = true;
        error.value = '';
        try {
            permission.value = await Notification.requestPermission();
            if (permission.value !== 'granted') return;

            const reg = await registration();
            await navigator.serviceWorker.ready;
            const sub = (await reg.pushManager.getSubscription())
                ?? await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidKey.value),
                });
            await save(sub);
            subscribed.value = true;
        } catch (e) {
            error.value = 'Could not turn on browser notifications.';
            console.error(e);
        } finally {
            busy.value = false;
        }
    }

    const disable = async () => {
        busy.value  = true;
        error.value = '';
        try {
            const reg = await navigator.serviceWorker.getRegistration('/sw.js');
            const sub = await reg?.pushManager.getSubscription();
            if (sub) {
                await axios.delete('/push-subscriptions', { data: { endpoint: sub.endpoint } });
                await sub.unsubscribe();
            }
            subscribed.value = false;
        } catch (e) {
            error.value = 'Could not turn off browser notifications.';
            console.error(e);
        } finally {
            busy.value = false;
        }
    };

    return { supported, available, permission, subscribed, busy, error, checked, refresh, enable, disable };
}
