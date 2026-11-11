import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import AuthenticatedLayout from './Layouts/AuthenticatedLayout.vue';
import { installColorMode, setShellActive } from './Composables/useColorMode';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

// Light is Vuetify's default look; dark uses deep slate surfaces. `header` is
// the top bar, kept a deep blue in dark mode instead of the brighter dark
// `primary`. The mode itself is chosen in the header (useColorMode).
const vuetify = createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: { header: '#1867C0' },
      },
      dark: {
        dark: true,
        colors: {
          background: '#0b1120',
          surface:    '#111827',
          'surface-bright': '#1b2638',
          // Tooltips, snackbars and the tinted table headers: slate, not Vuetify's flat grey.
          'surface-variant':    '#334155',
          'on-surface-variant': '#e2e8f0',
          header:     '#14305a',
        },
      },
    },
  },
})
installColorMode(vuetify.theme)

// Pages that own their full layout (no sidebar/auth shell needed) — and that
// always show in light mode.
const PUBLIC_PAGES = ['Auth/', 'Landing/', 'Other/Maintenance/'];


const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    // Browser tab reads "DOST - PRISM | Dashboard". Every page passes a short,
    // ideally one-word name via <Head title="…">; a page that passes none
    // falls back to the bare app name rather than showing a stray separator.
    title: (title) => title ? `DOST - PRISM | ${title}` : 'DOST - PRISM',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const page  = pages[`./Pages/${name}.vue`];

        const needsLayout = !PUBLIC_PAGES.some(p => name.startsWith(p));

        if (needsLayout) {
            page.default.layout = page.default.layout ?? AuthenticatedLayout;
        }

        return page;
    },
    setup({ el, App, props, plugin }) {
        // Apply the saved mode before the first render, so a dark-mode user
        // doesn't see the app flash light while it loads.
        setShellActive(!PUBLIC_PAGES.some(p => props.initialPage.component.startsWith(p)));

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(vuetify)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
