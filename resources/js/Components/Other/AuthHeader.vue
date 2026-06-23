<template>
  <v-toolbar color="primary" elevation="2">

    <!-- DOST tagline - always-visible system signature.
         Kept readable but not loud: bold brand mark + lighter descriptor. -->
    <div class="dost-tagline d-flex align-center ml-4">
      <span class="tagline-mark">OneDOST4U</span>
      <span class="tagline-sep">-</span>
      <span class="tagline-sub">Solutions and Opportunities for All</span>
    </div>

    <v-spacer></v-spacer>

    <NotificationPanel />

    <v-menu min-width="240px" rounded>
      <template #activator="{ props }">
        <v-btn v-bind="props" variant="text" class="text-none px-2 h-auto py-1">
            <div class="d-flex align-center">
                <v-avatar color="secondary" size="small">
                    <v-img :src="avatarSrc" alt="User"></v-img>
                </v-avatar>
                <v-icon size="small" class="ml-1 hidden-sm-and-down">mdi-chevron-down</v-icon>
            </div>
        </v-btn>
      </template>

      <v-list>
        <v-list-item :title="authUser?.username || 'User'" :subtitle="roleLabel" @click="goToProfile">
            <template #prepend>
                <v-avatar color="secondary" size="small">
                    <v-img :src="avatarSrc" alt="User"></v-img>
                </v-avatar>
            </template>
        </v-list-item>
        <v-divider class="my-2"></v-divider>
        <v-list-item prepend-icon="mdi-cog" title="Settings" value="settings" @click="router.visit('/settings')"></v-list-item>
        <v-divider class="my-2"></v-divider>
        <v-list-item prepend-icon="mdi-logout" base-color="error" title="Logout" value="logout" @click="router.post('/logout')"></v-list-item>
      </v-list>
    </v-menu>
  </v-toolbar>
</template>

<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import NotificationPanel from '@/Components/Other/NotificationPanel.vue';

const emit = defineEmits(['toggle-drawer']);

const page = usePage();
const authUser = computed(() => page.props.auth?.user ?? null);

const ROLE_LABELS = {
    super_admin:          'System Administrator',
    sub_admin:            'Sub Administrator',
    regional_admin:       'Regional Administrator',
    provincial_admin:     'Provincial Administrator',
    provincial_director:  'Provincial Director',
    employee:             'Employee',
};
const roleLabel = computed(() => {
    const base = ROLE_LABELS[authUser.value?.role] ?? '';
    const prov = authUser.value?.province?.name;
    const reg = authUser.value?.region?.name;
    if (!base) return '';
    if (authUser.value?.role === 'regional_admin' && reg) return `${base} of ${reg}`;
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

const toggleDrawer = () => {
  emit('toggle-drawer');
};
</script>

<style scoped>
.dost-tagline {
    color: #ffffff;
    font-size: 0.86rem;
    letter-spacing: 0.01em;
    line-height: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tagline-mark { font-weight: 800; letter-spacing: 0.02em; }
.tagline-sep  { margin: 0 6px; opacity: 0.55; font-weight: 400; }
.tagline-sub  { font-weight: 500; opacity: 0.85; }

/* On narrow screens drop the descriptor and keep just the brand mark
   so it never crowds the avatar/notification buttons. */
@media (max-width: 599px) {
    .tagline-sep, .tagline-sub { display: none; }
}
</style>
