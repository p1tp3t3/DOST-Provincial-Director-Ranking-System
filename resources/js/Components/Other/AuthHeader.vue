<template>
  <v-toolbar color="header" elevation="2">

    <!-- Agency name with the OneDOST4U tagline under it - the system's
         always-visible signature (the logo sits just left, in the sidebar). -->
    <div class="dost-brand ml-4">
      <div class="brand-title">Department of Science and Technology</div>
      <div class="brand-tagline">
        <span class="tagline-mark">One<span class="tagline-dost">DOST</span>4U</span>
        <span class="tagline-sep">:</span>
        <span class="tagline-sub">Solutions and Opportunities for All</span>
      </div>
    </div>

    <v-spacer></v-spacer>

    <!-- Full screen: the whole app, not just one card (Esc / F11 also exit) -->
    <v-btn
      v-if="canFullscreen"
      icon
      class="mr-1"
      :aria-label="isFullscreen ? 'Exit full screen' : 'Full screen'"
      :aria-pressed="isFullscreen"
      @click="toggleFullscreen"
    >
      <v-icon>{{ isFullscreen ? 'mdi-fullscreen-exit' : 'mdi-fullscreen' }}</v-icon>
      <v-tooltip activator="parent" location="bottom">{{ isFullscreen ? 'Exit full screen' : 'Full screen' }}</v-tooltip>
    </v-btn>

    <!-- Light / dark: one click flips it. The icon shows the mode it switches to. -->
    <v-btn
      icon
      class="mr-1"
      :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
      @click="toggleTheme"
    >
      <v-icon>{{ isDark ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
      <v-tooltip activator="parent" location="bottom">{{ isDark ? 'Light mode' : 'Dark mode' }}</v-tooltip>
    </v-btn>

    <NotificationPanel />

    <!-- Who's signed in: photo, name, and position · role (text hidden on narrow screens) -->
    <v-menu min-width="260px" rounded location="bottom end" offset="6">
      <template #activator="{ props }">
        <v-btn
          v-bind="props"
          variant="text"
          height="50"
          class="profile-chip text-none mr-2"
          :aria-label="`Account menu: ${fullName}`"
        >
          <v-avatar color="secondary" size="38" class="profile-avatar">
            <v-img :src="avatarSrc" alt="" cover></v-img>
          </v-avatar>
          <span class="profile-text d-none d-md-flex" :title="`${fullName}\n${headline}`">
            <span class="profile-name">{{ fullName }}</span>
            <span v-if="headline" class="profile-sub">{{ headline }}</span>
          </span>
          <v-icon size="small" class="profile-chevron">mdi-chevron-down</v-icon>
        </v-btn>
      </template>

      <v-list>
        <v-list-item :title="fullName" :subtitle="roleLabel" lines="two" @click="goToProfile">
            <template #prepend>
                <v-avatar color="secondary" size="small">
                    <v-img :src="avatarSrc" alt="" cover></v-img>
                </v-avatar>
            </template>
        </v-list-item>
        <template v-if="lockedRoles.length > 1">
            <v-divider class="my-2"></v-divider>
            <v-list-subheader>Switch Role</v-list-subheader>
            <v-list-item
                v-for="r in lockedRoles"
                :key="r.id"
                :title="ROLE_LABELS[r.name] ?? r.name"
                :active="r.name === authUser?.role"
                @click="switchRole(r)"
            >
                <template #prepend>
                    <v-icon size="small" :icon="r.name === authUser?.role ? 'mdi-check-circle' : 'mdi-circle-outline'" />
                </template>
            </v-list-item>
        </template>
        <v-divider class="my-2"></v-divider>
        <v-list-item prepend-icon="mdi-cog" title="Settings" value="settings" @click="router.visit('/settings')"></v-list-item>
        <v-divider class="my-2"></v-divider>
        <v-list-item prepend-icon="mdi-logout" base-color="error" title="Logout" value="logout" @click="router.post('/logout')"></v-list-item>
      </v-list>
    </v-menu>
  </v-toolbar>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import NotificationPanel from '@/Components/Other/NotificationPanel.vue';
import { useColorMode } from '@/Composables/useColorMode';

const emit = defineEmits(['toggle-drawer']);

// Full screen via the browser's Fullscreen API on the whole page. The button is
// hidden where the API isn't available (e.g. iPhone Safari). State follows the
// browser's fullscreenchange event, so leaving with Esc updates the icon too, and
// a map's own full-screen view (nested inside this one) keeps it on.
const fsElement = () => document.fullscreenElement ?? document.webkitFullscreenElement ?? null;
const canFullscreen = typeof document !== 'undefined'
    && !!(document.fullscreenEnabled ?? document.webkitFullscreenEnabled);
const isFullscreen = ref(false);
const syncFullscreen = () => { isFullscreen.value = !!fsElement(); };
const toggleFullscreen = async () => {
    const root = document.documentElement;
    try {
        if (fsElement()) await (document.exitFullscreen ?? document.webkitExitFullscreen).call(document);
        else await (root.requestFullscreen ?? root.webkitRequestFullscreen).call(root);
    } catch { /* blocked by the browser: nothing to undo */ }
    syncFullscreen();
};
onMounted(() => {
    syncFullscreen();
    document.addEventListener('fullscreenchange', syncFullscreen);
    document.addEventListener('webkitfullscreenchange', syncFullscreen);
});
onUnmounted(() => {
    document.removeEventListener('fullscreenchange', syncFullscreen);
    document.removeEventListener('webkitfullscreenchange', syncFullscreen);
});

// Flips whatever is showing now (an older "match my device" choice included)
// and saves the result as this browser's choice.
const { isDark, setPreference } = useColorMode();
const toggleTheme = () => setPreference(isDark.value ? 'light' : 'dark');

const page = usePage();
const authUser = computed(() => page.props.auth?.user ?? null);

const ROLE_LABELS = {
    super_admin:          'System Administrator',
    sub_admin:            'Sub Administrator',
    regional_director:    'Regional Director',
    provincial_admin:     'Provincial Administrator',
    provincial_director:  'Provincial Director',
    employee:             'Employee',
};
// "First Last" from the profile; the employee no. when no name is set yet.
const fullName = computed(() => {
    const p = authUser.value?.profile;
    return [p?.first_name, p?.last_name].filter(Boolean).join(' ').trim()
        || authUser.value?.dost_employee_id || 'User';
});
// Second line in the top bar: "Position · Role". Shown once when the position
// just repeats the role (e.g. a System Administrator's default position).
const headline = computed(() => {
    const position = authUser.value?.profile?.employee_profile?.position?.trim() ?? '';
    const role     = ROLE_LABELS[authUser.value?.role] ?? '';
    if (!position) return role;
    if (!role || position.toLowerCase() === role.toLowerCase()) return position;
    return `${position} · ${role}`;
});

const roleLabel = computed(() => {
    const base = ROLE_LABELS[authUser.value?.role] ?? '';
    const prov = authUser.value?.province?.name;
    const reg = authUser.value?.region?.name;
    if (!base) return '';
    if (authUser.value?.role === 'regional_director' && reg) return `${base} of ${reg}`;
    if (authUser.value?.role === 'provincial_director' && prov) return `${base} of ${prov}`;
    if (prov && ['provincial_admin', 'employee'].includes(authUser.value?.role)) return `${base} (${prov})`;
    return base;
});

const defAvatar = '/profile-picture?filename=profile-pic-2026-06-01-202414.png';
const avatarSrc = computed(() => {
    const file = authUser.value?.profile?.profile_picture;
    return file ? `/profile-picture?filename=${file}` : defAvatar;
});

const goToProfile = () => {
    if (authUser.value?.id) router.visit(`/profile/${authUser.value.id}`);
};

const lockedRoles = computed(() => (authUser.value?.roles ?? []).filter(r => r.pivot?.locked ?? r.locked));

const switchRole = (role) => {
    if (role.name === authUser.value?.role) return;
    router.patch('/switch-role', { role: role.name });
};

const toggleDrawer = () => {
  emit('toggle-drawer');
};
</script>

<style scoped>
.dost-brand {
    min-width: 0;
    color: #ffffff;
    line-height: 1.25;
}
.brand-title,
.brand-tagline {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.brand-title {
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: 0.03em;
    text-transform: uppercase;
}
.brand-tagline { font-size: 0.8rem; margin-top: 2px; }
.tagline-mark  { font-weight: 800; letter-spacing: 0.01em; }
.tagline-dost  { color: #7dd3fc; }
.tagline-sep   { margin: 0 5px; opacity: 0.7; font-weight: 500; }
.tagline-sub   { font-weight: 500; opacity: 0.85; }

/* Signed-in user: a soft panel on the header holding photo, name and
   position · role (the text collapses to just the photo on narrow screens). */
.profile-chip {
    background: rgba(255, 255, 255, 0.10);
    border-radius: 12px;
    padding: 0 8px 0 6px !important;
    letter-spacing: normal;
}
.profile-chip:hover { background: rgba(255, 255, 255, 0.16); }
.profile-avatar { box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.55); }
.profile-text {
    flex-direction: column;
    align-items: flex-start;
    margin-left: 10px;
    max-width: 260px;
    min-width: 0;
    text-align: left;
    line-height: 1.25;
}
.profile-name,
.profile-sub {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.profile-name { font-size: 0.9rem; font-weight: 600; color: #fff; }
.profile-sub  { font-size: 0.75rem; font-weight: 400; color: rgba(255, 255, 255, 0.78); }
.profile-chevron { margin-left: 6px; opacity: 0.8; }

/* On narrow screens keep just the OneDOST4U mark so the name never crowds
   the buttons on the right. */
@media (max-width: 599px) {
    .brand-title, .tagline-sep, .tagline-sub { display: none; }
}
</style>
