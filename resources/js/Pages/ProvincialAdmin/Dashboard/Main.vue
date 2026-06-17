<template>
    <Head title="Dashboard" />
    <div class="d-flex flex-column gap-4 w-full">

        <!-- Row 1: Director Info + Stat Cards -->
        <v-row>
            <v-col cols="12" md="4">
                <DirectorInfoCard v-if="director" :director="directorCard" />
                <v-card v-else border elevation="0" rounded="lg" class="h-100 d-flex align-center justify-center" style="min-height:164px;">
                    <div class="text-center text-medium-emphasis pa-6">
                        <v-icon size="40" color="blue-grey" class="mb-2">mdi-account-off-outline</v-icon>
                        <div class="text-body-2">No director assigned to this province</div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" md="8">
                <v-row dense>
                    <v-col cols="12" sm="6">
                        <QuantityCard title="Total Employees" :quantity="total_employees" :icon="RiGroup2Line" color="indigo" />
                    </v-col>
                    <v-col cols="12" sm="6">
                        <v-card class="w-full border-0 elevation-1 rounded-md">
                            <div style="border-left:5px solid #ca8a04;">
                                <v-card-item class="py-4 px-4">
                                    <div class="d-flex align-center justify-space-between">
                                        <div>
                                            <div class="text-caption font-weight-bold text-uppercase mb-1 text-black">Province Rank</div>
                                            <div class="text-h4 font-weight-black text-slate-800">
                                                {{ province_ranking?.rank ? `#${province_ranking.rank}` : '—' }}
                                            </div>
                                            <div class="d-flex align-center gap-1 mt-1">
                                                <span v-if="latest_year" class="text-caption text-medium-emphasis">{{ latest_year }}</span>
                                                <v-chip v-if="province_ranking?.category" size="x-small" :color="tierColor(province_ranking.category)" variant="tonal" class="text-uppercase">
                                                    {{ province_ranking.category }}
                                                </v-chip>
                                            </div>
                                        </div>
                                        <v-avatar color="amber-lighten-5" size="40" rounded="lg">
                                            <component :is="RiMedalLine" class="text-amber-darken-1 w-5 h-5" />
                                        </v-avatar>
                                    </div>
                                </v-card-item>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" class="mt-2 mt-sm-2">
                        <v-card class="w-full border-0 elevation-1 rounded-md">
                            <div style="border-left:5px solid #3f51b5;">
                                <v-card-item class="py-4 px-4">
                                    <div class="d-flex align-center justify-space-between">
                                        <div>
                                            <div class="text-caption font-weight-bold text-uppercase mb-1 text-black">KPI Score</div>
                                            <div class="text-h4 font-weight-black" :style="{ color: province_ranking?.total_pct != null ? bucketHex(province_ranking.bucket) : '#1e293b' }">
                                                {{ province_ranking?.total_pct != null ? `${province_ranking.total_pct.toFixed(1)}%` : '—' }}
                                            </div>
                                        </div>
                                        <v-avatar color="indigo-lighten-5" size="40" rounded="lg">
                                            <component :is="RiListCheck3" class="text-indigo-darken-1 w-5 h-5" />
                                        </v-avatar>
                                    </div>
                                </v-card-item>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" class="mt-2 mt-sm-2">
                        <v-card class="w-full border-0 elevation-1 rounded-md">
                            <div :style="{ borderLeft: `5px solid ${bucketHex(province_ranking?.bucket)}` }">
                                <v-card-item class="py-4 px-4">
                                    <div class="d-flex align-center justify-space-between">
                                        <div>
                                            <div class="text-caption font-weight-bold text-uppercase mb-1 text-black">Performer Level</div>
                                            <v-chip v-if="province_ranking?.bucket" size="large" variant="tonal" :color="bucketHex(province_ranking.bucket)" class="font-weight-bold mt-1">
                                                {{ province_ranking.bucket }}
                                            </v-chip>
                                            <div v-else class="text-h4 font-weight-black text-slate-800">—</div>
                                        </div>
                                        <v-avatar color="indigo-lighten-5" size="40" rounded="lg">
                                            <component :is="RiUserStarFill" class="text-indigo-darken-1 w-5 h-5" />
                                        </v-avatar>
                                    </div>
                                </v-card-item>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </v-col>
        </v-row>

        <!-- Row 2: KPI Category Breakdown (only when ranked data is available) -->
        <v-card v-if="province_ranking && province_ranking.status === 'ranked'" border elevation="0" rounded="lg">
            <div class="px-4 pt-3 pb-2 d-flex align-center justify-space-between">
                <div class="d-flex align-center gap-2">
                    <v-icon size="15" color="indigo">mdi-chart-bar</v-icon>
                    <span class="text-body-2 font-weight-bold">KPI Category Breakdown</span>
                    <v-chip v-if="latest_year" size="x-small" color="primary" variant="tonal">{{ latest_year }}</v-chip>
                </div>
                <div class="d-flex align-center gap-2 text-caption text-medium-emphasis">
                    Rank #{{ province_ranking.rank }} · {{ province_ranking.total_pct.toFixed(2) }}% overall
                </div>
            </div>
            <v-divider />
            <div class="pa-4">
                <v-row>
                    <v-col v-for="cat in kpiCategories" :key="cat.code" cols="12" md="4">
                        <div class="d-flex align-center justify-space-between mb-1">
                            <span class="text-caption font-weight-medium">
                                {{ cat.label }}
                                <span class="text-disabled ml-1">{{ cat.weight }}</span>
                            </span>
                            <span class="text-caption font-weight-bold" :style="{ color: bucketHex(province_ranking.bucket) }">
                                {{ (province_ranking.subtotals_pct[cat.code] ?? 0).toFixed(1) }}%
                            </span>
                        </div>
                        <v-progress-linear
                            :model-value="province_ranking.subtotals_pct[cat.code] ?? 0"
                            color="indigo"
                            height="8"
                            rounded
                            bg-color="grey-lighten-3"
                        />
                    </v-col>
                </v-row>
            </div>
        </v-card>

        <!-- No ranking notice (when no ranking data exists) -->
        <v-alert v-else-if="!province_ranking || province_ranking.status !== 'ranked'" type="info" variant="tonal" density="compact" class="text-body-2">
            No KPI ranking data found for {{ latest_year ?? 'this year' }}. Rankings appear once a provincial director submits their KPI data.
        </v-alert>

        <!-- Row 3: Employees -->
        <v-card class="elevation-1 border-0 rounded-md">
            <div class="d-flex align-center justify-space-between px-5 pt-4 pb-3">
                <div>
                    <div class="text-subtitle-2 font-weight-bold">Employees</div>
                    <div class="text-caption text-medium-emphasis">Provincial employees under this office</div>
                </div>
                <v-chip size="small" variant="tonal" color="primary">{{ employees.length }} Employees</v-chip>
            </div>
            <v-divider />
            <div class="pa-4">
                <NewEmployeeList :employees="employees" />
            </div>
        </v-card>

        <!-- Row 4: Recent Activity -->
        <v-card border elevation="0" rounded="lg">
            <div class="px-4 pt-3 pb-2 d-flex align-center gap-2">
                <v-icon size="15" color="indigo">mdi-clock-outline</v-icon>
                <span class="text-body-2 font-weight-bold">Recent Activity</span>
            </div>
            <v-divider />
            <div v-if="recent_logs.length" class="pa-4 d-flex flex-column gap-3">
                <div v-for="log in recent_logs" :key="log.id" class="d-flex align-start gap-3">
                    <v-avatar size="32" :color="roleColor(log.role)" rounded="lg" class="flex-shrink-0 mt-1">
                        <span class="avatar-initials">{{ roleInitials(log.role) }}</span>
                    </v-avatar>
                    <div class="flex-1 min-w-0">
                        <div class="d-flex align-center gap-2 flex-wrap">
                            <span class="text-body-2 font-weight-medium">{{ log.name || 'System' }}</span>
                            <v-chip size="x-small" variant="tonal" :color="logTypeColor(log.type)">{{ log.type }}</v-chip>
                        </div>
                        <div class="text-caption text-medium-emphasis">{{ log.description }}</div>
                        <div class="text-caption text-disabled mt-1">{{ formatDate(log.created_at) }}</div>
                    </div>
                </div>
            </div>
            <div v-else class="pa-6 text-center text-medium-emphasis">
                <v-icon size="32" color="blue-grey" class="mb-2">mdi-clock-outline</v-icon>
                <div class="text-body-2">No recent activity logged</div>
            </div>
        </v-card>

    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import QuantityCard from '@/Components/Cards/QuantityCard.vue';
import DirectorInfoCard from '@/Components/Cards/DirectorInfoCard.vue';
import NewEmployeeList from '@/Components/Lists/NewEmployeeList.vue';
import {
    RiGroup2Line,
    RiMedalLine,
    RiListCheck3,
    RiUserStarFill,
} from '@remixicon/vue';

const props = defineProps({
    total_employees:  { type: Number,      default: 0 },
    employees:        { type: Array,       default: () => [] },
    director:         { type: Object,      default: null },
    province_ranking: { type: Object,      default: null },
    latest_year:      { type: Number,      default: null },
    recent_logs:      { type: Array,       default: () => [] },
});

const kpiCategories = [
    { code: 'CORE',       label: 'Core',       weight: '60%' },
    { code: 'FUNCTIONAL', label: 'Functional', weight: '30%' },
    { code: 'SUPPORT',    label: 'Support',    weight: '10%' },
];

const directorCard = computed(() => ({
    user_id:           props.director?.id ?? null,
    name:              props.director?.name ?? '—',
    id:                props.director?.dost_employee_id ?? '—',
    province:          props.province_ranking?.province ?? '—',
    profile_picture:   props.director?.profile_picture ?? null,
    kpi_score:         props.province_ranking?.total_pct ?? 0,
}));

const bucketHex  = (b) => ({ Top: '#15803d', Average: '#ca8a04', Low: '#b91c1c' }[b] ?? '#64748b');
const tierColor  = (c) => ({ micro: 'blue-grey', small: 'teal', medium: 'indigo', large: 'deep-purple' }[c] ?? 'grey');

const roleColor   = (role) => ({ provincial_director: 'indigo', employee: 'teal', provincial_sub_admin: 'deep-purple', provincial_admin: 'blue' }[role] ?? 'grey');
const roleInitials = (role) => ({ provincial_director: 'PD', employee: 'EMP', provincial_sub_admin: 'PSA', provincial_admin: 'PA' }[role] ?? '?');

const logTypeColor = (type) => {
    const t = (type ?? '').toLowerCase();
    if (t.includes('create') || t.includes('register')) return 'success';
    if (t.includes('delete') || t.includes('remove'))   return 'error';
    if (t.includes('update') || t.includes('edit'))     return 'warning';
    if (t.includes('login')  || t.includes('logout'))   return 'blue';
    return 'grey';
};

const formatDate = (date) => {
    if (!date) return '';
    const d    = new Date(date);
    const diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 60)    return 'Just now';
    if (diff < 3600)  return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return d.toLocaleDateString();
};
</script>

<style scoped>
.text-slate-800   { color: #1e293b !important; }
.avatar-initials  { font-size: 0.62rem; font-weight: 700; color: white; }
</style>
