<template>
    <v-card
        class="elevation-1 border-0 rounded-md h-100"
        :class="{ 'director-clickable': director.user_id }"
        @click="director.user_id && router.visit(`/profile/${director.user_id}`)"
    >
        <v-card-item class="pa-5">
            <div class="d-flex align-center justify-space-between mb-4">
                <span class="text-caption text-medium-emphasis font-weight-medium" style="letter-spacing:0.05em; text-transform:uppercase; font-size:0.6rem;">
                    Provincial Director
                </span>
                <span v-if="director.user_id" class="view-profile-hint text-caption text-indigo">
                    View Profile <v-icon size="12">mdi-arrow-right</v-icon>
                </span>
            </div>
            <div class="d-flex align-center gap-4">
                <v-avatar size="56" color="indigo-lighten-4">
                    <v-img v-if="avatarSrc" :src="avatarSrc" cover></v-img>
                    <v-icon v-else size="28" color="indigo">mdi-account-tie</v-icon>
                </v-avatar>
                <div>
                    <div class="text-subtitle-2 font-weight-bold">{{ director.name }}</div>
                    <div class="text-caption text-medium-emphasis mb-2">{{ director.id }}</div>
                    <v-chip size="x-small" color="indigo" variant="tonal">Provincial Director</v-chip>
                </div>
            </div>
            <v-divider class="my-4"></v-divider>
            <div class="d-flex flex-column gap-2">
                <div class="d-flex align-center gap-2">
                    <v-icon size="14" color="medium-emphasis">mdi-map-marker-outline</v-icon>
                    <span class="text-caption text-medium-emphasis">{{ director.province }}</span>
                </div>
                <div v-if="hasService" class="d-flex align-center gap-2">
                    <v-icon size="14" color="medium-emphasis">mdi-calendar-check-outline</v-icon>
                    <span class="text-caption text-medium-emphasis">{{ director.length_of_service }} yrs of service</span>
                </div>
                <div class="d-flex align-center justify-space-between mt-2">
                    <span class="text-caption text-medium-emphasis">Overall KPI Score</span>
                    <span
                        class="text-body-2 font-weight-bold"
                        :class="director.kpi_score >= 85 ? 'text-success' : director.kpi_score >= 70 ? 'text-warning' : 'text-error'"
                    >{{ displayScore }}</span>
                </div>
                <v-progress-linear
                    :model-value="director.kpi_score ?? 0"
                    :color="director.kpi_score >= 85 ? 'success' : director.kpi_score >= 70 ? 'warning' : 'error'"
                    height="6"
                    rounded
                    bg-color="grey-lighten-3"
                ></v-progress-linear>
            </div>
        </v-card-item>
    </v-card>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    director: { type: Object, required: true },
});

const avatarSrc = computed(() => {
    const pic = props.director?.profile_picture;
    return pic ? `/profile-picture?filename=${encodeURIComponent(pic)}` : null;
});

const displayScore = computed(() => {
    const s = props.director?.kpi_score;
    if (s == null || s === 0) return '—';
    return Number(s).toFixed(1);
});

const hasService = computed(() => {
    const v = props.director?.length_of_service;
    return v != null && v !== '—' && v !== '';
});
</script>

<style scoped>
.director-clickable {
    cursor: pointer;
    transition: box-shadow 0.18s ease, border-color 0.18s ease;
}
.director-clickable:hover {
    box-shadow: 0 4px 16px rgba(79, 70, 229, 0.14) !important;
    border-color: rgba(79, 70, 229, 0.3) !important;
}
.view-profile-hint {
    opacity: 0;
    transition: opacity 0.18s ease;
    font-weight: 600;
}
.director-clickable:hover .view-profile-hint {
    opacity: 1;
}
</style>
