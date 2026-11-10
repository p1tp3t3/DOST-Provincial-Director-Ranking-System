import { computed, ref } from 'vue';

// Light / dark mode for the signed-in system. The choice — 'light', 'dark' or
// 'system' (follow the device setting) — is remembered per browser. Public
// pages (landing, login, maintenance notice) are designed for light and always
// stay light, so the choice only applies inside the app shell
// (AuthenticatedLayout calls setShellActive).
//
// Applying a mode switches Vuetify's theme (cards, tables, menus, inputs) and
// toggles the `theme-dark` class on <html>, which swaps the app's own colour
// variables (resources/css/app.css).

const STORAGE_KEY = 'prism-color-mode';
const MODES = ['light', 'dark', 'system'];

const media = typeof window !== 'undefined' && window.matchMedia
    ? window.matchMedia('(prefers-color-scheme: dark)')
    : null;

const readPreference = () => {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        return MODES.includes(saved) ? saved : 'light';
    } catch {
        return 'light';
    }
};

const preference = ref(readPreference());   // what the user picked
const mode       = ref('light');            // what is showing now: 'light' | 'dark'
let theme        = null;                    // Vuetify's theme instance
let shellActive  = false;

const resolve = () => (preference.value === 'system' ? (media?.matches ? 'dark' : 'light') : preference.value);

function apply() {
    mode.value = shellActive ? resolve() : 'light';
    theme?.change(mode.value);
    if (typeof document !== 'undefined') {
        document.documentElement.classList.toggle('theme-dark', mode.value === 'dark');
    }
}

// Once, at startup, with the app's Vuetify instance.
export function installColorMode(vuetifyTheme) {
    theme = vuetifyTheme;
    media?.addEventListener('change', () => { if (preference.value === 'system') apply(); });
    apply();
}

// The app shell turns the mode on while it's mounted; public pages turn it off.
export function setShellActive(active) {
    shellActive = active;
    apply();
}

export function useColorMode() {
    const setPreference = (value) => {
        if (!MODES.includes(value)) return;
        preference.value = value;
        try { localStorage.setItem(STORAGE_KEY, value); } catch { /* storage blocked */ }
        apply();
    };

    return {
        preference,
        mode,
        isDark: computed(() => mode.value === 'dark'),
        setPreference,
    };
}
