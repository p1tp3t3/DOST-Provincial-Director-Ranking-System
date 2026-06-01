<template>
    <AuthenticatedLayout>
        <div class="py-5">

            <!-- Back Button -->
            <div class="mb-4">
                <v-btn
                    variant="text"
                    color="primary"
                    prepend-icon="mdi-arrow-left"
                    size="small"
                    @click="() => history.back()"
                >
                    Back
                </v-btn>
            </div>

            <!-- Profile Hero Card -->
            <v-card class="mb-5 rounded-xl overflow-hidden" elevation="2">
                <div class="hero-section d-flex align-center gap-5 px-6 py-5">
                    <v-avatar
                        size="80"
                        :color="avatarColor"
                        class="flex-shrink-0 elevation-3"
                        style="border: 3px solid rgba(255,255,255,0.5);"
                    >
                        <span class="text-h5 font-weight-bold text-white">{{ initials }}</span>
                    </v-avatar>
                    <div>
                        <div class="text-h6 font-weight-bold text-white">{{ profile.name || '—' }}</div>
                        <div class="text-body-2 mt-1" style="color: rgba(255,255,255,0.75);">{{ roleLabel }}</div>
                    </div>
                </div>
            </v-card>

            <!-- Info Grid -->
            <v-row class="mb-2">

                <!-- Personal Info -->
                <v-col cols="12" md="6">
                    <v-card class="rounded-xl elevation-2 h-100">
                        <v-card-title class="section-header pa-4 pb-3 d-flex align-center gap-2">
                            <v-icon color="primary" size="20">mdi-account-details</v-icon>
                            <span class="text-subtitle-2 font-weight-bold">Personal Information</span>
                        </v-card-title>
                        <v-divider></v-divider>
                        <v-list density="comfortable" class="px-1 py-1">

                            <v-list-item v-if="profile.id_number" rounded="lg" class="info-row">
                                <template #prepend>
                                    <v-icon color="primary" size="18" class="me-1">mdi-badge-account-outline</v-icon>
                                </template>
                                <div class="info-label">Employee ID</div>
                                <div class="info-value">{{ profile.id_number }}</div>
                            </v-list-item>

                            <v-list-item rounded="lg" class="info-row">
                                <template #prepend>
                                    <v-icon color="primary" size="18" class="me-1">mdi-shield-account-outline</v-icon>
                                </template>
                                <div class="info-label">Role</div>
                                <div class="info-value">{{ roleLabel }}</div>
                            </v-list-item>

                            <v-list-item v-if="profile.province" rounded="lg" class="info-row">
                                <template #prepend>
                                    <v-icon color="primary" size="18" class="me-1">mdi-map-marker-outline</v-icon>
                                </template>
                                <div class="info-label">Province</div>
                                <div class="info-value">{{ profile.province }}</div>
                            </v-list-item>

                            <v-list-item v-if="profile.position" rounded="lg" class="info-row">
                                <template #prepend>
                                    <v-icon color="primary" size="18" class="me-1">mdi-briefcase-outline</v-icon>
                                </template>
                                <div class="info-label">Position</div>
                                <div class="info-value">{{ profile.position }}</div>
                            </v-list-item>

                            <v-list-item v-if="profile.status" rounded="lg" class="info-row">
                                <template #prepend>
                                    <v-icon color="primary" size="18" class="me-1">mdi-card-account-details-outline</v-icon>
                                </template>
                                <div class="info-label">Employment Status</div>
                                <div class="info-value">
                                    <v-chip
                                        size="x-small"
                                        :color="profile.status === 'permanent' ? 'success' : 'warning'"
                                        variant="tonal"
                                        class="font-weight-medium"
                                    >
                                        {{ profile.status === 'cos' ? 'Contract of Service (COS)' : 'Permanent' }}
                                    </v-chip>
                                </div>
                            </v-list-item>

                            <v-list-item v-if="profile.work_specification" rounded="lg" class="info-row">
                                <template #prepend>
                                    <v-icon color="primary" size="18" class="me-1">mdi-cog-outline</v-icon>
                                </template>
                                <div class="info-label">Work Specification</div>
                                <div class="info-value">{{ profile.work_specification }}</div>
                            </v-list-item>

                            <v-list-item rounded="lg" class="info-row">
                                <template #prepend>
                                    <v-icon color="primary" size="18" class="me-1">mdi-calendar-clock-outline</v-icon>
                                </template>
                                <div class="info-label">Length of Service</div>
                                <div class="info-value">{{ formatService(profile.length_of_service) || '—' }}</div>
                            </v-list-item>

                        </v-list>
                    </v-card>
                </v-col>

                <!-- Education Attainment -->
                <v-col cols="12" md="6">
                    <v-card class="rounded-xl elevation-2 h-100">
                        <v-card-title class="section-header pa-4 pb-3 d-flex align-center gap-2">
                            <v-icon color="primary" size="20">mdi-school-outline</v-icon>
                            <span class="text-subtitle-2 font-weight-bold">Education Attainment</span>
                        </v-card-title>
                        <v-divider></v-divider>

                        <v-card-text v-if="profile.education_attaiment?.length" class="pa-4">
                            <div class="d-flex flex-column gap-2">
                                <div
                                    v-for="(edu, i) in profile.education_attaiment"
                                    :key="i"
                                    class="edu-item d-flex align-start gap-3 pa-3 rounded-lg"
                                >
                                    <v-icon color="primary" size="16" class="mt-1 flex-shrink-0">
                                        {{ i === 0 ? 'mdi-school' : i === 1 ? 'mdi-certificate' : 'mdi-star-circle-outline' }}
                                    </v-icon>
                                    <div>
                                        <div class="text-caption text-medium-emphasis mb-0-5">
                                            {{ ['Bachelor', 'Master', 'Doctorate'][i] ?? 'Other' }}
                                        </div>
                                        <div class="text-body-2 font-weight-medium">{{ edu }}</div>
                                    </div>
                                </div>
                            </div>
                        </v-card-text>

                        <v-card-text v-else class="text-center py-10">
                            <v-icon size="40" color="grey-lighten-1" class="mb-2">mdi-school-outline</v-icon>
                            <div class="text-body-2 text-medium-emphasis">No education records available.</div>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const roleLabels = {
    super_admin:         'Super Admin',
    sub_admin:           'Sub Admin',
    provincial_admin:    'Provincial Admin',
    provincial_director: 'Provincial Director',
    employee:            'Employee',
};


const avatarPalette = ['primary', 'indigo', 'deep-purple', 'teal', 'blue-darken-2', 'cyan-darken-2', 'green-darken-2'];

const props = defineProps({
    user_profile: { type: Object, default: null },
});

const profile   = computed(() => props.user_profile ?? {});
const roleLabel = computed(() => roleLabels[profile.value.role] ?? profile.value.role ?? '—');

const initials = computed(() => {
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

    // bare integer: "3" → "3 years"
    if (/^\d+$/.test(s)) return `${s} years`;

    // bare decimal: "2.6" → "2 years 7 months"
    if (/^\d+\.\d+$/.test(s)) {
        const total   = parseFloat(s);
        const years   = Math.floor(total);
        const months  = Math.round((total - years) * 12);
        if (years === 0)  return `${months} months`;
        if (months === 0) return `${years} years`;
        return `${years} years ${months} months`;
    }

    return s;
};
</script>

<style scoped>
.hero-section {
    background: linear-gradient(120deg, rgb(var(--v-theme-primary)) 0%, rgba(var(--v-theme-primary), 0.65) 100%);
}
.section-header {
    background-color: rgba(var(--v-theme-surface-variant), 0.4);
}
.info-row {
    min-height: 52px;
}
.info-label {
    font-size: 0.72rem;
    color: rgba(var(--v-theme-on-surface), 0.55);
    font-weight: 500;
    line-height: 1.2;
    margin-bottom: 2px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.info-value {
    font-size: 0.875rem;
    font-weight: 500;
    color: rgba(var(--v-theme-on-surface), 0.87);
    line-height: 1.4;
}
.edu-item {
    background-color: rgba(var(--v-theme-primary), 0.04);
    border: 1px solid rgba(var(--v-theme-primary), 0.1);
    transition: background-color 0.15s;
}
.edu-item:hover {
    background-color: rgba(var(--v-theme-primary), 0.08);
}
</style>
