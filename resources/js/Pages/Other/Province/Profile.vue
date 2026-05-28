<template>
    <AuthenticatedLayout>
        <div class="grid gap-4 w-full">

            <!-- Back + Breadcrumb -->
            <div class="d-flex align-center gap-2">
                <v-btn icon size="x-small" variant="text" color="medium-emphasis" @click="router.visit('/province-directories')">
                    <v-icon size="18">mdi-arrow-left</v-icon>
                </v-btn>
                <span class="text-caption text-medium-emphasis">Province Directories</span>
                <v-icon size="12" color="medium-emphasis">mdi-chevron-right</v-icon>
                <span class="text-caption font-weight-medium">{{ profile.name }}</span>
            </div>

            <!-- Province Info + Director -->
            <v-row>
                <!-- Province Info -->
                <v-col cols="12" md="4">
                    <v-card class="elevation-1 border-0 rounded-md h-100">
                        <v-card-item class="pa-5">
                            <div class="text-caption text-medium-emphasis font-weight-medium mb-4" style="letter-spacing:0.05em; text-transform:uppercase; font-size:0.6rem;">
                                Province Information
                            </div>
                            <div class="d-flex align-center gap-3 mb-4">
                                <v-avatar color="indigo-lighten-5" size="52" rounded="lg">
                                    <v-icon color="indigo" size="26">mdi-map-marker-outline</v-icon>
                                </v-avatar>
                                <div>
                                    <div class="text-subtitle-1 font-weight-bold">{{ profile.name }}</div>
                                    <v-chip size="x-small" variant="tonal" color="indigo" class="text-capitalize mt-1">
                                        {{ profile.category }}
                                    </v-chip>
                                </div>
                            </div>
                            <v-divider class="mb-4"></v-divider>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex align-center justify-space-between">
                                    <span class="text-caption text-medium-emphasis">Total Employees</span>
                                    <span class="text-body-2 font-weight-medium">{{ employees.length }}</span>
                                </div>
                                <div class="d-flex align-center justify-space-between">
                                    <span class="text-caption text-medium-emphasis">Director Status</span>
                                    <v-chip
                                        size="x-small"
                                        variant="tonal"
                                        :color="profile.provincial_director ? 'success' : 'warning'"
                                    >
                                        {{ profile.provincial_director ? 'Assigned' : 'Vacant' }}
                                    </v-chip>
                                </div>
                            </div>
                        </v-card-item>
                    </v-card>
                </v-col>

                <!-- Provincial Director -->
                <v-col cols="12" md="8">
                    <v-card class="elevation-1 border-0 rounded-md h-100">
                        <v-card-item class="pa-5">
                            <div class="text-caption text-medium-emphasis font-weight-medium mb-4" style="letter-spacing:0.05em; text-transform:uppercase; font-size:0.6rem;">
                                Provincial Director
                            </div>

                            <template v-if="profile.provincial_director">
                                <div class="d-flex align-center gap-4 mb-4">
                                    <v-avatar size="64">
                                        <v-img :src="defPic" cover></v-img>
                                    </v-avatar>
                                    <div>
                                        <div class="text-subtitle-2 font-weight-bold">{{ directorName }}</div>
                                        <div class="text-caption text-medium-emphasis">{{ profile.provincial_director.dost_employee_id }}</div>
                                        <v-chip size="x-small" color="indigo" variant="tonal" class="mt-1">Provincial Director</v-chip>
                                    </div>
                                </div>
                                <v-divider class="mb-4"></v-divider>
                                <v-row dense>
                                    <v-col cols="12" sm="6">
                                        <div class="info-row">
                                            <v-icon size="14" color="medium-emphasis">mdi-email-outline</v-icon>
                                            <span class="text-caption text-medium-emphasis">{{ profile.provincial_director.email }}</span>
                                        </div>
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <div class="info-row">
                                            <v-icon size="14" color="medium-emphasis">mdi-account-outline</v-icon>
                                            <span class="text-caption text-medium-emphasis">{{ profile.provincial_director.username }}</span>
                                        </div>
                                    </v-col>
                                    <v-col v-if="profile.provincial_director.profile?.position" cols="12" sm="6">
                                        <div class="info-row">
                                            <v-icon size="14" color="medium-emphasis">mdi-briefcase-outline</v-icon>
                                            <span class="text-caption text-medium-emphasis">{{ profile.provincial_director.profile.position }}</span>
                                        </div>
                                    </v-col>
                                </v-row>
                            </template>

                            <template v-else>
                                <div class="d-flex align-center gap-3 py-6">
                                    <v-icon size="36" color="warning-lighten-2">mdi-account-alert-outline</v-icon>
                                    <div>
                                        <div class="text-body-2 font-weight-medium text-warning">No Director Assigned</div>
                                        <div class="text-caption text-medium-emphasis">This province currently has no provincial director.</div>
                                    </div>
                                </div>
                            </template>
                        </v-card-item>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Employees -->
            <v-card class="elevation-1 border-0 rounded-md">
                <div class="d-flex align-center justify-space-between px-5 pt-4 pb-3">
                    <div>
                        <div class="text-subtitle-2 font-weight-bold">Employees</div>
                        <div class="text-caption text-medium-emphasis">All employees under {{ profile.name }}</div>
                    </div>
                    <v-chip size="small" variant="tonal" color="primary">{{ employees.length }} Employees</v-chip>
                </div>
                <v-divider></v-divider>
                <div class="pa-4">
                    <NewEmployeeList :employees="employees" />
                </div>
            </v-card>

        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NewEmployeeList from '@/Components/Lists/NewEmployeeList.vue';

const defPic = 'https://scontent.fcgy1-3.fna.fbcdn.net/v/t39.30808-1/569409499_2926389544213362_5572906559510250325_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=107&ccb=1-7&_nc_sid=1d2534&_nc_eui2=AeHxMS2Jaxqdlz7XjktrvQNCkuMFs6-OJrWS4wWzr44mtaR_gFGX3XynJKcVctLnDQznMva1uf7y4DJ9zvqkENur&_nc_ohc=t1KgQyv8YI4Q7kNvwFZDK9s&_nc_oc=AdovjHXEhGImiLI-b4UzqvAlKfytDYJV4eb0rG9Z9EgUyAyg_EF3UGX2mFLadgn20tFa5hK9DE54diCLrTUm3qlo&_nc_zt=24&_nc_ht=scontent.fcgy1-3.fna&_nc_gid=AYiNPwYYm5mm809vxgPhoA&_nc_ss=782a8&oh=00_Af5BeBLxqbptd619Z7yoL_PoCiaDpG1OQR9LWJj9XiJMMw&oe=6A13B822';

const props = defineProps({
    province_profile: { type: Object },
});

const profile = props.province_profile?.data[0] ?? {};

const directorName = computed(() => {
    const p = profile.provincial_director?.profile;
    if (!p) return '—';
    const middle = p.middle_name ? `${p.middle_name} ` : '';
    return `${p.first_name ?? ''} ${middle}${p.last_name ?? ''}`.trim();
});

// Map raw employee users to the shape NewEmployeeList expects
const employees = computed(() => {
    const raw = profile.employees ?? [];
    return raw.map(u => ({
        id:       u.dost_employee_id ?? u.id,
        name:     buildName(u.profile),
        position: u.profile?.employee_profile?.position ?? u.profile?.position ?? '—',
    }));
});

const buildName = (p) => {
    if (!p) return '—';
    const middle = p.middle_name ? `${p.middle_name} ` : '';
    return `${p.first_name ?? ''} ${middle}${p.last_name ?? ''}`.trim();
};
</script>

<style scoped>
.info-row {
    display: flex;
    align-items: center;
    gap: 6px;
}
</style>
