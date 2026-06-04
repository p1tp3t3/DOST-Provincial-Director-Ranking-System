<template>
    <div class="w-full">

        <!-- Back -->
        <div class="mb-3">
            <v-btn variant="text" color="medium-emphasis" prepend-icon="mdi-arrow-left" size="small" @click="goBack">
                Back
            </v-btn>
        </div>

        <!-- ── Hero Card ───────────────────────────────────────── -->
        <v-card class="mb-4 rounded-xl overflow-hidden" elevation="0" border>

            <!-- Avatar + identity row -->
            <div class="d-flex align-center justify-space-between px-6 py-5 flex-wrap gap-3">
                <div class="d-flex align-center gap-4">
                    <v-avatar
                        size="88"
                        :color="profile?.profile_picture ? undefined : avatarColor"
                        class="flex-shrink-0 elevation-2"
                    >
                        <v-img :src="`/profile-picture?filename=${profile?.profile_picture}`" alt="Admin"></v-img>
                    </v-avatar>
                    <div>
                        <div class="text-h6 font-weight-bold">{{ profile.name || '—' }}</div>
                        <div class="d-flex align-center gap-2 mt-1 flex-wrap">
                            <v-chip size="x-small" color="primary" variant="tonal" class="font-weight-medium">
                                {{ roleLabel }}
                            </v-chip>
                            <span v-if="profile.province" class="text-caption text-medium-emphasis d-flex align-center gap-1">
                                <v-icon size="12">mdi-map-marker-outline</v-icon>{{ profile.province }}
                            </span>
                        </div>
                    </div>
                </div>

                <v-btn
                    v-if="isOwnProfile"
                    color="primary"
                    variant="tonal"
                    size="small"
                    prepend-icon="mdi-pencil-outline"
                    :href="route('profile.edit')"
                >Edit Profile</v-btn>

            </div>
        </v-card>

        <!-- ── Quick Stat Tiles ────────────────────────────────── -->
        <v-row dense class="mb-4">
            <v-col v-for="tile in statTiles" :key="tile.label" cols="6" sm="3">
                <v-card border elevation="0" rounded="lg" class="pa-4">
                    <div class="d-flex align-center gap-3">
                        <v-avatar :color="tile.color + '-lighten-5'" rounded="lg" size="36">
                            <v-icon :color="tile.color" size="18">{{ tile.icon }}</v-icon>
                        </v-avatar>
                        <div class="min-w-0">
                            <div class="text-caption text-medium-emphasis" style="font-size:10px; text-transform:uppercase; letter-spacing:.06em;">{{ tile.label }}</div>
                            <div class="text-body-2 font-weight-bold text-truncate mt-px">{{ tile.value || '—' }}</div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ── Info Cards ──────────────────────────────────────── -->
        <v-row>

            <!-- Personal Information -->
            <v-col cols="12" :md="['employee', 'provincial_director'].includes(profile.role) ? 6 : 12">
                <v-card border elevation="0" rounded="xl" class="h-100">
                    <div class="px-5 pt-4 pb-3 d-flex align-center gap-2">
                        <v-avatar color="indigo-lighten-5" rounded="lg" size="32">
                            <v-icon color="indigo" size="16">mdi-account-details</v-icon>
                        </v-avatar>
                        <span class="text-subtitle-2 font-weight-bold">Personal Information</span>
                    </div>
                    <v-divider />
                    <div class="pa-4">
                        <div class="info-grid">
                            <div v-for="row in personalRows" :key="row.label" class="info-row">
                                <div class="info-label">{{ row.label }}</div>
                                <div class="info-value">
                                    <template v-if="row.chip">
                                        <v-chip size="x-small" :color="row.chipColor" variant="tonal" class="font-weight-medium">
                                            {{ row.value }}
                                        </v-chip>
                                    </template>
                                    <template v-else>{{ row.value || '—' }}</template>
                                </div>
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-col>

            <!-- Education Attainment (employees + directors only) -->
            <v-col v-if="['employee', 'provincial_director'].includes(profile.role)" cols="12" md="6">
                <v-card border elevation="0" rounded="xl" class="h-100">
                    <div class="px-5 pt-4 pb-3 d-flex align-center gap-2">
                        <v-avatar color="purple-lighten-5" rounded="lg" size="32">
                            <v-icon color="purple" size="16">mdi-school-outline</v-icon>
                        </v-avatar>
                        <span class="text-subtitle-2 font-weight-bold">Education Attainment</span>
                    </div>
                    <v-divider />

                    <div v-if="profile.education_attaiment?.length" class="pa-4 d-flex flex-column gap-3">
                        <div
                            v-for="(edu, i) in profile.education_attaiment"
                            :key="i"
                            class="edu-item d-flex align-start gap-3 pa-3 rounded-lg"
                        >
                            <v-avatar :color="eduColors[i] + '-lighten-5'" rounded="lg" size="36" class="flex-shrink-0">
                                <v-icon :color="eduColors[i]" size="18">{{ eduIcons[i] }}</v-icon>
                            </v-avatar>
                            <div>
                                <div class="edu-level">{{ eduLevels[i] ?? 'Other' }}</div>
                                <div class="text-body-2 font-weight-medium">{{ edu }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="d-flex flex-column align-center justify-center gap-2 pa-8">
                        <v-icon size="42" color="grey-lighten-2">mdi-school-outline</v-icon>
                        <div class="text-body-2 text-medium-emphasis">No education records available.</div>
                    </div>
                </v-card>
            </v-col>

        </v-row>

    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page         = usePage();
const goBack       = () => window.history.back();
const isOwnProfile = computed(() => page.props.auth?.user?.id === props.user_profile?.id);

const props = defineProps({
    user_profile: { type: Object, default: null },
});

const profile   = computed(() => props.user_profile ?? {});

const roleLabels = {
    super_admin:          'Super Admin',
    sub_admin:            'Sub Admin',
    provincial_admin:     'Provincial Admin',
    provincial_sub_admin: 'Provincial Sub Admin',
    provincial_director:  'Provincial Director',
    employee:             'Employee',
};
const roleLabel = computed(() => roleLabels[profile.value.role] ?? profile.value.role ?? '—');

const avatarPalette = ['primary', 'indigo', 'deep-purple', 'teal', 'blue-darken-2', 'cyan-darken-2'];
const initials      = computed(() => {
    const name = profile.value.name ?? '';
    return name.split(' ').filter(Boolean).slice(0, 2).map(n => n[0]?.toUpperCase() ?? '').join('') || '?';
});
const avatarColor = computed(() => {
    const name = profile.value.name ?? '';
    const idx  = [...name].reduce((a, c) => a + c.charCodeAt(0), 0) % avatarPalette.length;
    return avatarPalette[idx];
});

const formatService = (val) => {
    if (!val && val !== 0) return '';
    const s = String(val).trim();
    if (/^\d+$/.test(s))       return `${s} ${s === '1' ? 'year' : 'years'}`;
    if (/^\d+\.\d+$/.test(s)) {
        const total  = parseFloat(s);
        const years  = Math.floor(total);
        const months = Math.round((total - years) * 12);
        if (years === 0)  return `${months} months`;
        if (months === 0) return `${years} years`;
        return `${years} yrs ${months} mos`;
    }
    return s;
};

const statusLabel = (s) => ({ permanent: 'Permanent', cos: 'Contract of Service', jo: 'Job Order' }[s] ?? s);
const statusColor = (s) => ({ permanent: 'success', cos: 'warning', jo: 'orange' }[s] ?? 'grey');

// Quick stat tiles
const statTiles = computed(() => {
    const p = profile.value;
    const tiles = [
        { label: 'Employee ID',      value: p.id_number,                           icon: 'mdi-badge-account-outline',   color: 'indigo'  },
        { label: 'Length of Service',value: formatService(p.length_of_service),    icon: 'mdi-calendar-clock-outline',  color: 'teal'    },
    ];
    if (p.position)
        tiles.push({ label: 'Position', value: p.position, icon: 'mdi-briefcase-outline', color: 'blue' });
    if (p.status)
        tiles.push({ label: 'Status', value: statusLabel(p.status), icon: 'mdi-card-account-details-outline', color: statusColor(p.status) === 'success' ? 'green' : 'orange' });
    // Pad to 4 tiles
    while (tiles.length < 4)
        tiles.push({ label: 'Province', value: p.province, icon: 'mdi-map-outline', color: 'primary' });
    return tiles.slice(0, 4);
});

// Personal info rows
const personalRows = computed(() => {
    const p = profile.value;
    const rows = [
        { label: 'Employee ID', value: p.id_number },
        { label: 'Role',        value: roleLabel.value },
        { label: 'Province',    value: p.province },
        { label: 'Length of Service', value: formatService(p.length_of_service) },
    ];
    if (p.position)
        rows.push({ label: 'Position', value: p.position });
    if (p.status)
        rows.push({ label: 'Employment Status', value: statusLabel(p.status), chip: true, chipColor: statusColor(p.status) });
    if (p.work_specification)
        rows.push({ label: 'Work Specification', value: p.work_specification });
    return rows;
});

const eduLevels = ["Bachelor's Degree", "Master's Degree", 'Doctorate'];
const eduIcons  = ['mdi-school-outline', 'mdi-certificate-outline', 'mdi-star-circle-outline'];
const eduColors = ['indigo', 'purple', 'deep-purple'];
</script>

<style scoped>

.info-grid {
    display: grid;
    gap: 0;
}
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

.edu-item {
    background: rgba(var(--v-theme-surface-variant), 0.35);
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    transition: background 0.15s;
}
.edu-item:hover { background: rgba(var(--v-theme-surface-variant), 0.6); }
.edu-level {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: rgba(var(--v-theme-on-surface), 0.45);
    margin-bottom: 3px;
}
</style>
