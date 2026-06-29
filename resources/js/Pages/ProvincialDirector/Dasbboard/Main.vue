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
                        <div class="text-body-2">No director profile found</div>
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
                    <v-col cols="12" sm="6" class="mt-2">
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
                    <v-col cols="12" sm="6" class="mt-2">
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

        <!-- Row 2: KPI Category Breakdown -->
        <v-card v-if="province_ranking?.status === 'ranked'" border elevation="0" rounded="lg">
            <div class="px-4 pt-3 pb-2 d-flex align-center justify-space-between">
                <div class="d-flex align-center gap-2">
                    <v-icon size="15" color="indigo">mdi-chart-bar</v-icon>
                    <span class="text-body-2 font-weight-bold">KPI Category Breakdown</span>
                    <v-chip v-if="latest_year" size="x-small" color="primary" variant="tonal">{{ latest_year }}</v-chip>
                </div>
                <div class="text-caption text-medium-emphasis">
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
        <v-alert v-else type="info" variant="tonal" density="compact" class="text-body-2">
            No KPI ranking data found for {{ latest_year ?? 'this year' }}. Rankings appear once KPI data is submitted.
        </v-alert>

        <!-- Row 3: Employees -->
        <v-card class="elevation-1 border-0 rounded-md">
            <div class="d-flex align-center justify-space-between px-5 pt-4 pb-3">
                <div>
                    <div class="text-subtitle-2 font-weight-bold">Employees</div>
                    <div class="text-caption text-medium-emphasis">Staff under your provincial office</div>
                </div>
                <v-chip size="small" variant="tonal" color="primary">{{ total_employees }} Employees</v-chip>
            </div>
            <v-divider />
            <div class="pa-4">
                <NewEmployeeList :employees="employees" />
            </div>
        </v-card>

        <!-- Row 4: Regional Leaderboard -->
        <v-card border elevation="0" rounded="lg">

            <div class="px-4 pt-3 pb-0">
                <div class="d-flex align-center justify-space-between flex-wrap gap-3 mb-2">
                    <div class="d-flex align-center gap-2 flex-wrap">
                        <v-icon size="15" color="indigo">mdi-trophy-outline</v-icon>
                        <span class="text-body-2 font-weight-bold">Province Performance Leaderboard</span>
                        <v-chip v-if="my_region" size="x-small" variant="tonal" color="blue-grey" prepend-icon="mdi-map-marker-radius-outline">
                            {{ my_region }}
                        </v-chip>
                        <v-chip v-if="myProvinceEntry" size="x-small" variant="tonal" color="indigo" prepend-icon="mdi-map-marker">
                            {{ myProvinceEntry.province }} · #{{ myProvinceEntry.rank }}
                        </v-chip>
                    </div>
                    <div class="d-flex align-center gap-2 flex-wrap">
                        <v-select
                            v-model="lbTier"
                            :items="lbTierItems"
                            item-title="label"
                            item-value="value"
                            label="Tier"
                            density="compact"
                            variant="outlined"
                            hide-details
                            style="width:170px;"
                        />
                        <v-select
                            v-model="lbYear"
                            :items="available_years"
                            label="Year"
                            density="compact"
                            variant="outlined"
                            hide-details
                            style="width:100px;"
                        />
                    </div>
                </div>
            </div>

            <v-divider />

            <v-data-table
                :headers="lbHeaders"
                :items="lbRows"
                density="compact"
                fixed-header
                height="380"
                hide-default-footer
                :items-per-page="-1"
                :row-props="lbRowProps"
                class="leaderboard-table"
            >
                <template #item.rank="{ item }">
                    <span v-if="item.status !== 'ranked'" class="text-caption text-disabled">—</span>
                    <span v-else-if="item.rank === 1" class="rank-medal rank-gold">1st</span>
                    <span v-else-if="item.rank === 2" class="rank-medal rank-silver">2nd</span>
                    <span v-else-if="item.rank === 3" class="rank-medal rank-bronze">3rd</span>
                    <span v-else class="text-caption text-medium-emphasis">#{{ item.rank }}</span>
                </template>

                <template #item.province="{ item }">
                    <div class="d-flex align-center gap-2">
                        <span class="text-body-2 font-weight-medium">{{ item.province }}</span>
                        <v-chip v-if="myProvinceName && item.province === myProvinceName" size="x-small" color="indigo" variant="tonal">Yours</v-chip>
                    </div>
                </template>

                <template #item.director="{ item }">
                    <span class="text-body-2 text-medium-emphasis">{{ item.director || 'Vacant' }}</span>
                </template>

                <template #item.category="{ item }">
                    <v-chip :color="tierColor(item.category)" size="x-small" variant="tonal" class="text-uppercase font-weight-medium">
                        {{ item.category }}
                    </v-chip>
                </template>

                <template #item.bucket="{ item }">
                    <v-chip v-if="item.status === 'no_director'" color="blue-grey" size="x-small" variant="tonal">No director</v-chip>
                    <v-chip v-else-if="item.status === 'no_data'"    color="blue-grey" size="x-small" variant="tonal">No data</v-chip>
                    <v-chip v-else-if="item.bucket" :color="bucketChipColor(item.bucket)" size="x-small" variant="tonal" class="font-weight-medium">
                        {{ item.bucket }}
                    </v-chip>
                    <span v-else class="text-caption text-disabled">—</span>
                </template>

                <template #item.total_pct="{ item }">
                    <div v-if="item.status === 'ranked'" class="d-flex align-center gap-2 py-1" style="min-width:160px;">
                        <div class="score-track">
                            <div class="score-fill" :style="{ width: `${Math.min(item.total_pct, 100)}%`, background: bucketHex(item.bucket) }" />
                        </div>
                        <span class="text-caption font-weight-bold" :style="{ color: bucketHex(item.bucket) }">
                            {{ item.total_pct.toFixed(2) }}%
                        </span>
                    </div>
                    <span v-else class="text-caption text-disabled">—</span>
                </template>

                <template #no-data>
                    <div class="text-center py-6 text-medium-emphasis text-body-2">No data available for {{ lbYear }}</div>
                </template>
            </v-data-table>

        </v-card>

    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import DirectorInfoCard from '@/Components/Cards/DirectorInfoCard.vue';
import QuantityCard from '@/Components/Cards/QuantityCard.vue';
import NewEmployeeList from '@/Components/Lists/NewEmployeeList.vue';
import {
    RiGroup2Line,
    RiMedalLine,
    RiListCheck3,
    RiUserStarFill,
} from '@remixicon/vue';

const props = defineProps({
    total_employees:  { type: Number, default: 0 },
    employees:        { type: Array,  default: () => [] },
    director:         { type: Object, default: null },
    province_ranking: { type: Object, default: null },
    latest_year:      { type: Number, default: null },
    // leaderboard
    my_region:        { type: String, default: null },
    rankings_by_year: { type: Object, default: () => ({}) },
    available_years:  { type: Array,  default: () => [] },
});

const kpiCategories = [
    { code: 'CORE',       label: 'Core',       weight: '60%' },
    { code: 'STRATEGIC',  label: 'Strategic',  weight: '30%' },
    { code: 'SUPPORT',    label: 'Support',    weight: '10%' },
];

const directorCard = computed(() => ({
    user_id:         props.director?.id ?? null,
    name:            props.director?.name ?? '—',
    id:              props.director?.dost_employee_id ?? '—',
    province:        props.province_ranking?.province ?? '—',
    profile_picture: props.director?.profile_picture ?? null,
    kpi_score:       props.province_ranking?.total_pct ?? 0,
}));

const bucketHex       = (b) => ({ Top: '#15803d', Average: '#ca8a04', Low: '#b91c1c' }[b] ?? '#64748b');
const bucketChipColor = (b) => ({ Top: 'success', Average: 'warning', Low: 'error' }[b] ?? 'grey');
const tierColor       = (c) => ({ micro: 'blue-grey', small: 'teal', medium: 'indigo', large: 'deep-purple' }[c] ?? 'grey');

// ── Leaderboard ───────────────────────────────────────────────────────────────

const lbYear = ref(props.available_years[0] ?? new Date().getFullYear());
const lbTier = ref('all');

const myProvinceName = computed(() => props.province_ranking?.province ?? null);

const lbAllRows = computed(() => {
    const yearData = props.rankings_by_year[lbYear.value] ?? {};
    return Object.values(yearData).flat();
});

const lbRows = computed(() => {
    let rows = lbAllRows.value;
    if (lbTier.value !== 'all') rows = rows.filter(r => r.category === lbTier.value);

    const ranked   = rows.filter(r => r.status === 'ranked')
        .slice().sort((a, b) => b.total_pct - a.total_pct)
        .map((r, i) => ({ ...r, rank: i + 1 }));
    const unranked = rows.filter(r => r.status !== 'ranked')
        .slice().sort((a, b) => a.province.localeCompare(b.province))
        .map(r => ({ ...r, rank: null }));
    return [...ranked, ...unranked];
});

const myProvinceEntry = computed(() =>
    myProvinceName.value
        ? lbRows.value.find(r => r.province === myProvinceName.value) ?? null
        : null
);

const lbTierCounts = computed(() => {
    const all = lbAllRows.value;
    const counts = { all: all.length };
    for (const r of all) counts[r.category] = (counts[r.category] ?? 0) + 1;
    return counts;
});

const lbTierItems = computed(() => [
    { value: 'all',    label: `All Tiers (${lbTierCounts.value.all ?? 0})` },
    { value: 'micro',  label: `Micro (${lbTierCounts.value.micro ?? 0})`   },
    { value: 'small',  label: `Small (${lbTierCounts.value.small ?? 0})`   },
    { value: 'medium', label: `Medium (${lbTierCounts.value.medium ?? 0})` },
    { value: 'large',  label: `Large (${lbTierCounts.value.large ?? 0})`   },
]);

const lbHeaders = [
    { title: 'Rank',      key: 'rank',      width: '64px',  sortable: false },
    { title: 'Performer', key: 'bucket',    width: '110px', sortable: false },
    { title: 'Province',  key: 'province',  sortable: true  },
    { title: 'Director',  key: 'director',  sortable: false },
    { title: 'Tier',      key: 'category',  width: '90px',  align: 'center', sortable: true },
    { title: 'Total',     key: 'total_pct', width: '200px', sortable: true  },
];

const lbRowProps = ({ item }) => ({
    class: myProvinceName.value && item.province === myProvinceName.value ? 'my-province-row' : '',
});
</script>

<style scoped>
.text-slate-800 { color: #1e293b !important; }

.leaderboard-table :deep(thead th) { font-size: 11px !important; font-weight: 700 !important; }
.leaderboard-table :deep(tr.my-province-row) td {
    background-color: rgba(99, 102, 241, 0.07) !important;
}

.rank-medal {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 20px;
}
.rank-gold   { background: #fef9c3; color: #854d0e; }
.rank-silver { background: #f1f5f9; color: #475569; }
.rank-bronze { background: #ffedd5; color: #9a3412; }

.score-track {
    width: 90px;
    flex-shrink: 0;
    height: 8px;
    background: #f1f5f9;
    border-radius: 4px;
    overflow: hidden;
}
.score-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.6s ease;
}
</style>
