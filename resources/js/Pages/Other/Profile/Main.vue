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
                    @click="() => router.visit('/dashboard')"
                >
                    Back
                </v-btn>
            </div>

            <!-- Profile Hero Card -->
            <v-card class="mb-6 rounded-xl elevation-2 overflow-hidden">
                <v-sheet color="primary" height="5" width="100%"></v-sheet>
                <v-card-item class="pa-6">
                    <v-row align="center" no-gutters class="gap-5">
                        <v-col cols="12" sm="auto" class="d-flex justify-center justify-sm-start mb-4 mb-sm-0">
                            <v-avatar size="120" class="border-xl border-primary elevation-4">
                                <v-img :src="defPic" :alt="profile.name" cover></v-img>
                            </v-avatar>
                        </v-col>
                        <v-col cols="12" sm class="ps-0 ps-sm-6 text-sm-start">
                            <div class="text-[1.8em] font-weight-bold mb-2">{{ profile.name }}</div>
                            <div class="d-flex flex-wrap gap-2 justify-sm-start mb-3">
                                <v-chip
                                    size="small"
                                    color="primary"
                                    variant="tonal"
                                    prepend-icon="mdi-badge-account"
                                    class="font-weight-bold"
                                >
                                    {{ profile.id_number }}
                                </v-chip>
                                <v-chip
                                    v-if="profile.position"
                                    size="small"
                                    color="secondary"
                                    variant="tonal"
                                    prepend-icon="mdi-briefcase"
                                >
                                    {{ profile.position }}
                                </v-chip>
                            </div>
                            <div class="text-body-2 text-medium-emphasis d-flex align-center justify-sm-start gap-1">
                                <v-icon size="16">mdi-clock-outline</v-icon>
                                {{ profile.length_of_service }} Years of Service
                            </div>
                        </v-col>
                    </v-row>
                </v-card-item>
            </v-card>

            <!-- Info Grid -->
            <v-row class="mb-2">

                <!-- Personal Info -->
                <v-col cols="12" md="6">
                    <v-card class="rounded-xl elevation-2 h-100">
                        <v-card-title class="text-subtitle-1 font-weight-bold pa-4 pb-2 d-flex align-center gap-2">
                            <v-icon color="primary" size="20">mdi-account-details</v-icon>
                            Personal Information
                        </v-card-title>
                        <v-divider></v-divider>
                        <v-list density="comfortable" class="px-2">
                            <v-list-item rounded="lg">
                                <template #prepend>
                                    <v-icon color="primary" size="18" class="me-2">mdi-badge-account-outline</v-icon>
                                </template>
                                <v-list-item-title class="text-caption text-medium-emphasis mb-1">Employee ID</v-list-item-title>
                                <v-list-item-subtitle class="text-body-2 font-weight-medium text-high-emphasis">
                                    {{ profile.id_number }}
                                </v-list-item-subtitle>
                            </v-list-item>

                            <v-list-item v-if="profile.position" rounded="lg">
                                <template #prepend>
                                    <v-icon color="primary" size="18" class="me-2">mdi-briefcase-outline</v-icon>
                                </template>
                                <v-list-item-title class="text-caption text-medium-emphasis mb-1">Position</v-list-item-title>
                                <v-list-item-subtitle class="text-body-2 font-weight-medium text-high-emphasis">
                                    {{ profile.position }}
                                </v-list-item-subtitle>
                            </v-list-item>

                            <v-list-item v-if="profile.work_specification" rounded="lg">
                                <template #prepend>
                                    <v-icon color="primary" size="18" class="me-2">mdi-cog-outline</v-icon>
                                </template>
                                <v-list-item-title class="text-caption text-medium-emphasis mb-1">Work Specification</v-list-item-title>
                                <v-list-item-subtitle class="text-body-2 font-weight-medium text-high-emphasis">
                                    {{ profile.work_specification }}
                                </v-list-item-subtitle>
                            </v-list-item>

                            <v-list-item rounded="lg">
                                <template #prepend>
                                    <v-icon color="primary" size="18" class="me-2">mdi-calendar-clock-outline</v-icon>
                                </template>
                                <v-list-item-title class="text-caption text-medium-emphasis mb-1">Length of Service</v-list-item-title>
                                <v-list-item-subtitle class="text-body-2 font-weight-medium text-high-emphasis">
                                    {{ profile.length_of_service }} Years
                                </v-list-item-subtitle>
                            </v-list-item>
                        </v-list>
                    </v-card>
                </v-col>

                <!-- Education Attainment -->
                <v-col cols="12" md="6">
                    <v-card class="rounded-xl elevation-2 h-100">
                        <v-card-title class="text-subtitle-1 font-weight-bold pa-4 pb-2 d-flex align-center gap-2">
                            <v-icon color="primary" size="20">mdi-school-outline</v-icon>
                            Education Attainment
                        </v-card-title>
                        <v-divider></v-divider>
                        <v-card-text v-if="profile.education_attaiment?.length">
                            <div class="d-flex flex-wrap gap-2 pt-1">
                                <v-chip
                                    v-for="(edu, i) in profile.education_attaiment"
                                    :key="i"
                                    color="primary"
                                    variant="outlined"
                                    size="small"
                                    prepend-icon="mdi-book-education-outline"
                                >
                                    {{ edu }}
                                </v-chip>
                            </div>
                        </v-card-text>
                        <v-card-text v-else class="text-center py-8">
                            <v-icon size="40" color="grey-lighten-1" class="mb-2">mdi-school-outline</v-icon>
                            <div class="text-body-2 text-medium-emphasis">No education records available.</div>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <!-- KPI Section -->
            <v-card class="rounded-xl elevation-2 mb-4">
                <v-card-title class="text-subtitle-1 font-weight-bold pa-4 pb-2 d-flex align-center gap-2">
                    <v-icon color="primary" size="20">mdi-chart-bar</v-icon>
                    KPI Overview
                </v-card-title>
                <v-divider></v-divider>

                <template v-if="profile.kpi?.length">
                    <template v-for="(kpi, i) in profile.kpi" :key="i">
                        <!-- Outcome group header -->
                        <div class="kpi-group-header px-5 py-2 d-flex align-center gap-2">
                            <span class="text-caption font-weight-bold">
                                {{ i + 1 }}. {{ kpi.outcome_title }}
                            </span>
                        </div>

                        <!-- Indicator rows -->
                        <v-list density="compact" class="py-0">
                            <v-list-item
                                v-for="(row, j) in kpi.sub_rows"
                                :key="j"
                                class="px-5"
                                min-height="40"
                            >
                                <v-list-item-title class="text-caption text-medium-emphasis text-wrap">
                                    {{ row.indicator }}
                                </v-list-item-title>
                                <template #append>
                                    <div class="d-flex align-center gap-2 ms-4">
                                        <span
                                            class="text-caption font-weight-medium"
                                            :class="row.actual >= row.target ? 'text-success' : 'text-warning'"
                                        >
                                            {{ row.actual }}
                                        </span>
                                        <span class="text-caption text-disabled">/ {{ row.target }}</span>
                                        <v-icon
                                            size="14"
                                            :color="row.actual >= row.target ? 'success' : 'warning'"
                                        >
                                            {{ row.actual >= row.target ? 'mdi-check-circle-outline' : 'mdi-alert-circle-outline' }}
                                        </v-icon>
                                    </div>
                                </template>
                            </v-list-item>
                        </v-list>

                        <v-divider v-if="i < profile.kpi.length - 1"></v-divider>
                    </template>
                </template>

                <v-card-text v-else class="text-center py-10">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-chart-bar</v-icon>
                    <div class="text-body-2 text-medium-emphasis">No KPI data available for this director.</div>
                </v-card-text>
            </v-card>

            <!-- Projects Section -->
            <v-card class="rounded-xl elevation-2">
                <v-card-title class="text-subtitle-1 font-weight-bold pa-4 pb-2 d-flex align-center gap-2">
                    <v-icon color="primary" size="20">mdi-folder-multiple-outline</v-icon>
                    Projects
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text v-if="profile.projects?.length" class="pa-4">
                    <v-row>
                        <v-col
                            v-for="(project, i) in profile.projects"
                            :key="i"
                            cols="12"
                            md="6"
                        >
                            <v-card variant="elevated" rounded="lg" class="h-100">
                                <v-sheet :color="project.status_color" height="3" width="100%" rounded="t-lg"></v-sheet>
                                <v-card-item class="pt-3 pb-1">
                                    <v-card-title class="text-body-2 font-weight-bold text-wrap">
                                        {{ project.title }}
                                    </v-card-title>
                                    <template #append>
                                        <v-chip
                                            size="x-small"
                                            :color="project.status_color"
                                            variant="tonal"
                                            class="font-weight-bold"
                                        >
                                            {{ project.status }}
                                        </v-chip>
                                    </template>
                                </v-card-item>
                                <v-card-text class="pt-1">
                                    <div class="text-caption text-medium-emphasis mb-2">{{ project.description }}</div>
                                    <div class="d-flex flex-wrap gap-3 mt-2">
                                        <div class="d-flex align-center gap-1 text-caption text-medium-emphasis">
                                            <v-icon size="14">mdi-calendar-range</v-icon>
                                            {{ project.duration }}
                                        </div>
                                        <div class="d-flex align-center gap-1 text-caption text-medium-emphasis">
                                            <v-icon size="14">mdi-currency-php</v-icon>
                                            Budget: {{ project.budget }}
                                        </div>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-text v-else class="text-center py-10">
                    <v-icon size="48" color="grey-lighten-1" class="mb-3">mdi-folder-open-outline</v-icon>
                    <div class="text-body-2 text-medium-emphasis">No projects assigned yet.</div>
                </v-card-text>
            </v-card>

        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const defPic = 'https://scontent.fcgy1-3.fna.fbcdn.net/v/t39.30808-1/569409499_2926389544213362_5572906559510250325_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=107&ccb=1-7&_nc_sid=1d2534&_nc_eui2=AeHxMS2Jaxqdlz7XjktrvQNCkuMFs6-OJrWS4wWzr44mtaR_gFGX3XynJKcVctLnDQznMva1uf7y4DJ9zvqkENur&_nc_ohc=t1KgQyv8YI4Q7kNvwFZDK9s&_nc_oc=AdovjHXEhGImiLI-b4UzqvAlKfytDYJV4eb0rG9Z9EgUyAyg_EF3UGX2mFLadgn20tFa5hK9DE54diCLrTUm3qlo&_nc_zt=24&_nc_ht=scontent.fcgy1-3.fna&_nc_gid=AYiNPwYYm5mm809vxgPhoA&_nc_ss=782a8&oh=00_Af5BeBLxqbptd619Z7yoL_PoCiaDpG1OQR9LWJj9XiJMMw&oe=6A13B822';

const testData = {
    id_number: 'DOST-XI-2019-0042',
    name: 'Maria Cristina B. Dela Cruz',
    length_of_service: 14,
    position: 'Provincial Director',
    work_specification: 'Science & Technology Program Management',
    education_attaiment: [
        'Bachelor of Science in Physics',
        'Master of Science in Science Education',
        'Doctor of Philosophy in Science Policy',
    ],
    kpi: [
        {
            outcome_title: 'Technology Transfer and Commercialization',
            sub_rows: [
                { indicator: 'Technologies transferred', target: 5, actual: 6 },
                { indicator: 'Revenue from tech licensing (₱)', target: 500000, actual: 620000 },
            ]
        },
        {
            outcome_title: 'Research and Development Output',
            sub_rows: [
                { indicator: 'Completed R&D projects', target: 8, actual: 7 },
                { indicator: 'Published research papers', target: 4, actual: 5 },
            ]
        },
        {
            outcome_title: 'Human Resource Development',
            sub_rows: [
                { indicator: 'Scholars supported', target: 20, actual: 23 },
                { indicator: 'Training programs conducted', target: 12, actual: 12 },
            ]
        },
        {
            outcome_title: 'S&T Infrastructure Development',
            sub_rows: [
                { indicator: 'Equipment procured and deployed', target: 10, actual: 9 },
                { indicator: 'Facilities upgraded', target: 3, actual: 3 },
            ]
        },
        {
            outcome_title: 'Community S&T Promotion',
            sub_rows: [
                { indicator: 'Barangays reached by S&T programs', target: 30, actual: 34 },
                { indicator: 'Science fairs organized', target: 2, actual: 2 },
            ]
        },
        {
            outcome_title: 'Stakeholder Engagement',
            sub_rows: [
                { indicator: 'MOAs / partnerships signed', target: 6, actual: 8 },
                { indicator: 'Industry collaborations established', target: 4, actual: 3 },
            ]
        },
    ],
    projects: [
        {
            title: 'Smart Farming Technology Adoption Program',
            status: 'Ongoing',
            status_color: 'success',
            duration: 'Jan 2023 – Dec 2025',
            budget: '₱ 4,500,000',
            description: 'Deployment of IoT-based crop monitoring systems to smallholder farmers across Davao del Norte.',
        },
        {
            title: 'Rural Internet Connectivity for S&T Access',
            status: 'Completed',
            status_color: 'primary',
            duration: 'Mar 2022 – Feb 2024',
            budget: '₱ 2,100,000',
            description: 'Established high-speed internet access points in 12 geographically isolated and disadvantaged areas.',
        },
        {
            title: 'Indigenous Knowledge Documentation Initiative',
            status: 'Ongoing',
            status_color: 'success',
            duration: 'Jul 2024 – Jun 2026',
            budget: '₱ 1,800,000',
            description: 'Systematic documentation and digitization of indigenous science practices and traditional ecological knowledge.',
        },
        {
            title: 'Provincial Science and Technology Fair',
            status: 'Completed',
            status_color: 'primary',
            duration: 'Nov 2023 – Nov 2023',
            budget: '₱ 350,000',
            description: 'Annual showcase of student and community-led innovations, with over 200 project entries.',
        },
    ]
};

const props = defineProps({
    user_profile: {
        type: Object,
        default: null
    }
});

const profile = computed(() => testData);
</script>

<style scoped>
.kpi-group-header {
    background-color: rgba(0, 0, 0, 0.03);
    border-left: 3px solid rgb(var(--v-theme-primary));
}
</style>
