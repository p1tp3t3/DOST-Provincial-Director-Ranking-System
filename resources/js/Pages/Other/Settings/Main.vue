<template>
    <Head title="Settings" />
    <div class="w-full">

        <!-- Header -->
        <div class="mb-4">
            <div class="text-h6 font-weight-bold">Settings</div>
            <div class="text-caption text-medium-emphasis">Manage your account and preferences</div>
        </div>

        <!-- Success / Error banners -->
        <v-alert v-if="flashSuccess" type="success" variant="tonal" density="compact" closable class="mb-4"
            @click:close="flashSuccess = ''">{{ flashSuccess }}</v-alert>
        <v-alert v-if="flashError" type="error" variant="tonal" density="compact" closable class="mb-4"
            @click:close="flashError = ''">{{ flashError }}</v-alert>

        <v-row>

            <!-- ── Left nav ───────────────────────────── -->
            <v-col cols="12" md="3">
                <v-card border elevation="0" rounded="lg" class="overflow-hidden">
                    <v-list density="compact" nav class="pa-2">
                        <v-list-item
                            v-for="section in visibleSections"
                            :key="section.key"
                            :value="section.key"
                            :active="active === section.key"
                            active-color="primary"
                            rounded="lg"
                            @click="active = section.key"
                        >
                            <template #prepend>
                                <v-icon :icon="section.icon" size="18" class="me-1" />
                            </template>
                            <v-list-item-title class="text-body-2">{{ section.label }}</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-card>
            </v-col>

            <!-- ── Content ────────────────────────────── -->
            <v-col cols="12" md="9">

                <!-- ── Account Overview ───── -->
                <div v-if="active === 'account'">
                    <v-card border elevation="0" rounded="lg">
                        <div class="px-5 pt-4 pb-3 d-flex align-center gap-2">
                            <v-avatar color="indigo-lighten-5" rounded="lg" size="32">
                                <v-icon color="indigo" size="16">mdi-account-outline</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-subtitle-2 font-weight-bold">Account Overview</div>
                                <div class="text-caption text-medium-emphasis">Your account details</div>
                            </div>
                        </div>
                        <v-divider />
                        <div class="pa-5">
                            <div class="info-grid mb-4">
                                <div v-for="row in accountRows" :key="row.label" class="info-row">
                                    <div class="info-label">{{ row.label }}</div>
                                    <div class="info-value">
                                        <v-chip v-if="row.chip" size="x-small" :color="row.chipColor" variant="tonal" class="font-weight-medium text-capitalize">
                                            {{ row.value }}
                                        </v-chip>
                                        <span v-else>{{ row.value || '—' }}</span>
                                    </div>
                                </div>
                            </div>
                            <v-divider class="mb-4" />
                            <div class="d-flex gap-2 flex-wrap">
                                <v-btn
                                    size="small"
                                    variant="tonal"
                                    color="primary"
                                    prepend-icon="mdi-pencil-outline"
                                    :href="route('profile.edit')"
                                >Edit Profile</v-btn>
                            </div>
                        </div>
                    </v-card>
                </div>

                <!-- ── Security ───────────── -->
                <div v-else-if="active === 'security'">
                    <v-card border elevation="0" rounded="lg">
                        <div class="px-5 pt-4 pb-3 d-flex align-center gap-2">
                            <v-avatar color="orange-lighten-5" rounded="lg" size="32">
                                <v-icon color="orange" size="16">mdi-lock-outline</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-subtitle-2 font-weight-bold">Change Password</div>
                                <div class="text-caption text-medium-emphasis">Keep your account secure with a strong password</div>
                            </div>
                        </div>
                        <v-divider />
                        <v-form ref="pwFormRef" class="pa-5" @submit.prevent="changePassword">
                            <div class="d-flex flex-column gap-4" style="max-width:480px;">
                                <v-text-field
                                    v-model="pwForm.current_password"
                                    label="Current Password"
                                    :type="showCurrent ? 'text' : 'password'"
                                    variant="outlined"
                                    density="compact"
                                    hide-details="auto"
                                    :rules="[r.required]"
                                    :error-messages="pwErrors.current_password"
                                    :append-inner-icon="showCurrent ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                    @click:append-inner="showCurrent = !showCurrent"
                                />
                                <v-text-field
                                    v-model="pwForm.password"
                                    label="New Password"
                                    :type="showNew ? 'text' : 'password'"
                                    variant="outlined"
                                    density="compact"
                                    hide-details="auto"
                                    :rules="[r.required, r.min8]"
                                    :append-inner-icon="showNew ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                    @click:append-inner="showNew = !showNew"
                                />
                                <v-text-field
                                    v-model="pwForm.password_confirmation"
                                    label="Confirm New Password"
                                    :type="showConfirm ? 'text' : 'password'"
                                    variant="outlined"
                                    density="compact"
                                    hide-details="auto"
                                    :rules="[r.required, r.match(pwForm.password)]"
                                    :append-inner-icon="showConfirm ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                    @click:append-inner="showConfirm = !showConfirm"
                                />

                                <!-- Strength hints -->
                                <div class="d-flex flex-column gap-1">
                                    <div v-for="hint in pwHints" :key="hint.label" class="d-flex align-center gap-2">
                                        <v-icon :color="hint.ok ? 'success' : 'grey-lighten-1'" size="13">
                                            {{ hint.ok ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                        </v-icon>
                                        <span class="text-caption" :class="hint.ok ? 'text-success' : 'text-medium-emphasis'">{{ hint.label }}</span>
                                    </div>
                                </div>

                                <div class="d-flex justify-end gap-2">
                                    <v-btn variant="text" color="medium-emphasis" size="small" @click="resetPwForm">Cancel</v-btn>
                                    <v-btn
                                        type="submit"
                                        color="primary"
                                        variant="tonal"
                                        size="small"
                                        prepend-icon="mdi-lock-reset"
                                        :loading="savingPw"
                                        :disabled="!canSavePw"
                                    >Update Password</v-btn>
                                </div>
                            </div>
                        </v-form>
                    </v-card>
                </div>

                <!-- ── Notification Preferences ── -->
                <div v-else-if="active === 'notifications'">
                    <v-card border elevation="0" rounded="lg">
                        <div class="px-5 pt-4 pb-3 d-flex align-center gap-2">
                            <v-avatar color="teal-lighten-5" rounded="lg" size="32">
                                <v-icon color="teal" size="16">mdi-bell-outline</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-subtitle-2 font-weight-bold">Notification Preferences</div>
                                <div class="text-caption text-medium-emphasis">Control what notifications you receive</div>
                            </div>
                        </div>
                        <v-divider />
                        <div class="pa-5 d-flex flex-column gap-1">
                            <div v-for="notif in notifSettings" :key="notif.key" class="d-flex align-center justify-space-between py-3 notif-row">
                                <div>
                                    <div class="text-body-2 font-weight-medium">{{ notif.label }}</div>
                                    <div class="text-caption text-medium-emphasis">{{ notif.desc }}</div>
                                </div>
                                <v-switch
                                    v-model="notif.enabled"
                                    color="primary"
                                    density="compact"
                                    hide-details
                                    inset
                                />
                            </div>
                        </div>
                    </v-card>
                </div>

                <!-- ── Admin — System Info ─── -->
                <div v-else-if="active === 'system'">
                    <v-card border elevation="0" rounded="lg">
                        <div class="px-5 pt-4 pb-3 d-flex align-center gap-2">
                            <v-avatar color="indigo-lighten-5" rounded="lg" size="32">
                                <v-icon color="indigo" size="16">mdi-information-outline</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-subtitle-2 font-weight-bold">System Information</div>
                                <div class="text-caption text-medium-emphasis">Current system and application details</div>
                            </div>
                        </div>
                        <v-divider />
                        <div class="pa-5">
                            <div class="info-grid">
                                <div v-for="row in systemRows" :key="row.label" class="info-row">
                                    <div class="info-label">{{ row.label }}</div>
                                    <div class="info-value">{{ row.value }}</div>
                                </div>
                            </div>
                            <v-divider class="my-4" />
                            <v-btn size="small" variant="tonal" color="indigo" prepend-icon="mdi-wrench-outline" href="/maintenance" v-if="isSuperAdmin">
                                Open Maintenance Panel
                            </v-btn>
                        </div>
                    </v-card>
                </div>

            </v-col>
        </v-row>

    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    account: { type: Object, default: () => ({}) },
});

const page        = usePage();
const authUser    = computed(() => page.props.auth?.user);
const isSuperAdmin = computed(() => authUser.value?.role === 'super_admin');

const roleLabels = {
    super_admin:          'Super Admin',
    sub_admin:            'Sub Admin',
    provincial_admin:     'Provincial Admin',
    provincial_director:  'Provincial Director',
    employee:             'Employee',
};

const roleColor = (r) => ({
    super_admin: 'indigo', sub_admin: 'purple', provincial_admin: 'teal',
    provincial_director: 'blue', employee: 'success',
}[r] ?? 'grey');

// ── Active section ────────────────────────────────────────────
const active = ref('account');

const allSections = [
    { key: 'account',       label: 'Account',       icon: 'mdi-account-circle-outline',    roles: null },
    { key: 'security',      label: 'Security',       icon: 'mdi-lock-outline',              roles: null },
    { key: 'notifications', label: 'Notifications',  icon: 'mdi-bell-outline',              roles: null },
    { key: 'system',        label: 'System Info',    icon: 'mdi-information-outline',       roles: ['super_admin', 'sub_admin'] },
];

const visibleSections = computed(() =>
    allSections.filter(s => !s.roles || s.roles.includes(authUser.value?.role))
);

// ── Account overview rows ─────────────────────────────────────
const accountRows = computed(() => {
    const a = props.account;
    const rows = [
        { label: 'Full Name',    value: a.name                                 },
        { label: 'Username',     value: a.username                             },
        { label: 'Email',        value: a.email                                },
        { label: 'Employee ID',  value: a.employee_id                          },
        { label: 'Role',         value: roleLabels[a.role] ?? a.role, chip: true, chipColor: roleColor(a.role) },
    ];
    if (a.province) rows.push({ label: 'Province', value: a.province });
    return rows;
});

// ── System info rows ──────────────────────────────────────────
const systemRows = [
    { label: 'Application', value: 'PRISM — Provincial Director Ranking and Information System for Management' },
    { label: 'Framework',   value: 'Laravel 11 + Inertia.js + Vue 3' },
    { label: 'Environment', value: import.meta.env.MODE === 'production' ? 'Production' : 'Development' },
];

// ── Notification settings ─────────────────────────────────────
const notifSettings = ref([
    { key: 'login_alert',   label: 'Login Alerts',         desc: 'Notify when a new login is detected on your account', enabled: true  },
    { key: 'kpi_updates',   label: 'KPI Updates',          desc: 'Receive alerts when KPI data is updated',             enabled: true  },
    { key: 'system_notices',label: 'System Notices',       desc: 'Maintenance windows and important system messages',   enabled: true  },
    { key: 'report_ready',  label: 'Report Ready',         desc: 'Notify when a generated report is available',         enabled: false },
]);

// ── Flash handling ────────────────────────────────────────────
const flashSuccess = ref('');
const flashError   = ref('');

watch(() => page.props.flash, (flash) => {
    if (flash?.status === 'password-updated') {
        flashSuccess.value = 'Password updated successfully.';
        resetPwForm();
    }
}, { deep: true, immediate: true });

// ── Change Password form ──────────────────────────────────────
const pwFormRef = ref(null);
const savingPw  = ref(false);
const showCurrent = ref(false);
const showNew     = ref(false);
const showConfirm = ref(false);
const pwErrors    = ref({});

const defaultPw = () => ({ current_password: '', password: '', password_confirmation: '' });
const pwForm    = ref(defaultPw());

const resetPwForm = () => {
    pwForm.value = defaultPw();
    pwErrors.value = {};
    pwFormRef.value?.resetValidation();
    showCurrent.value = false;
    showNew.value     = false;
    showConfirm.value = false;
};

const pwHints = computed(() => [
    { label: 'At least 8 characters',  ok: pwForm.value.password.length >= 8 },
    { label: 'Contains a number',       ok: /\d/.test(pwForm.value.password) },
    { label: 'Contains a letter',       ok: /[a-zA-Z]/.test(pwForm.value.password) },
    { label: 'Passwords match',         ok: pwForm.value.password.length > 0 && pwForm.value.password === pwForm.value.password_confirmation },
]);

const canSavePw = computed(() => pwHints.value.every(h => h.ok) && !!pwForm.value.current_password);

const changePassword = async () => {
    const { valid } = await pwFormRef.value.validate();
    if (!valid) return;

    savingPw.value = true;
    pwErrors.value = {};

    router.put(route('password.update'), pwForm.value, {
        onSuccess: () => {
            flashSuccess.value = 'Password updated successfully.';
            resetPwForm();
        },
        onError: (errors) => {
            pwErrors.value = errors;
            if (errors.current_password) flashError.value = 'Current password is incorrect.';
        },
        onFinish: () => { savingPw.value = false; },
    });
};

// ── Validation rules ──────────────────────────────────────────
const r = {
    required: (v) => !!v || 'This field is required.',
    min8:     (v) => (v && v.length >= 8) || 'Minimum 8 characters.',
    match: (other) => (v) => v === other || 'Passwords do not match.',
};
</script>

<style scoped>
.info-grid { display: flex; flex-direction: column; gap: 0; }
.info-row {
    display: grid;
    grid-template-columns: 140px 1fr;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.info-row:last-child { border-bottom: none; }
.info-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: rgba(var(--v-theme-on-surface), 0.5);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.info-value {
    font-size: 0.875rem;
    font-weight: 500;
    color: rgba(var(--v-theme-on-surface), 0.87);
}

.notif-row {
    border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.notif-row:last-child { border-bottom: none; }
</style>
