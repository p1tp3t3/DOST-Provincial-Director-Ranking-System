<template>
    <Head title="Dashboard" />
    <div class="d-flex flex-column gap-3">

        <!-- Stat Cards -->
        <v-row dense>
            <v-col cols="12" sm="3">
                <QuantityCard title="Total Provinces"            :quantity="total_provinces"            :icon="RiBuildingLine"      color="indigo" />
            </v-col>
            <v-col cols="12" sm="3">
                <QuantityCard title="Provincial Directors"       :quantity="total_directors"            :icon="RiUserStarLine"      color="indigo" />
            </v-col>
            <v-col cols="12" sm="3">
                <QuantityCard title="Total Employees"            :quantity="total_employees"            :icon="RiGroupLine"         color="indigo" />
            </v-col>
            <v-col cols="12" sm="3">
                <QuantityCard title="Active Reporting Provinces" :quantity="active_reporting_provinces" :icon="RiCheckboxCircleLine" color="indigo" />
            </v-col>
        </v-row>

        <!-- Leaderboard Card -->
        <v-row dense>
            <v-col cols="12">
                <v-card border elevation="0" rounded="lg">

                    <div class="px-4 pt-3 pb-0">
                        <div class="d-flex align-center justify-space-between flex-wrap gap-3 mb-2">
                            <div class="d-flex align-center gap-2">
                                <v-icon size="15" color="indigo">mdi-trophy-outline</v-icon>
                                <span class="text-body-2 font-weight-bold">PSTD Ranking Matrix</span>
                                <v-tooltip location="bottom" max-width="360">
                                    <template #activator="{ props: tip }">
                                        <v-chip v-bind="tip" size="x-small" variant="tonal" color="blue-grey" prepend-icon="mdi-information-outline" class="cursor-pointer">
                                            Scoring
                                        </v-chip>
                                    </template>
                                    <div class="pa-1">
                                        <div class="font-weight-bold mb-1">Weighted PSTD Matrix Score</div>
                                        <div class="text-caption mb-2 opacity-80">
                                            For each of the 37 KPIs we compute accomplishment % vs target, map it to an adjective score (Outstanding 1.0 / VS 0.8 / Sat 0.6 / Avg 0.4 / Unsat 0.2 / Poor 0.0), then multiply by the KPI's weight. CORE = 60%, FUNCTIONAL = 30%, SUPPORT = 10%.
                                        </div>
                                        <div class="text-caption opacity-70">
                                            Provinces are ranked within their CSTC tier. Top 20% by rank = Top Performers, next 60% = Average, bottom 20% = Under.
                                        </div>
                                    </div>
                                </v-tooltip>
                                <v-chip size="x-small" color="primary" variant="tonal" class="font-weight-medium">
                                    {{ rankedScores.length }}
                                    <template v-if="selectedTier !== 'all'"> · {{ tierLabel }}</template>
                                </v-chip>
                            </div>
                            <div class="d-flex align-center gap-3 flex-wrap">
                                <div class="d-flex align-center gap-1">
                                    <span class="legend-dot" style="background:#15803d;"></span>
                                    <span class="text-caption text-medium-emphasis">Top ({{ bucketCounts.Top }})</span>
                                </div>
                                <div class="d-flex align-center gap-1">
                                    <span class="legend-dot" style="background:#ca8a04;"></span>
                                    <span class="text-caption text-medium-emphasis">Average ({{ bucketCounts.Average }})</span>
                                </div>
                                <div class="d-flex align-center gap-1">
                                    <span class="legend-dot" style="background:#b91c1c;"></span>
                                    <span class="text-caption text-medium-emphasis">Under ({{ bucketCounts.Under }})</span>
                                </div>
                                <div v-if="unrankedProvinces.length" class="d-flex align-center gap-1">
                                    <span class="legend-dot" style="background:#94a3b8;"></span>
                                    <span class="text-caption text-medium-emphasis">Pending ({{ unrankedProvinces.length }})</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <v-divider />

                    <!-- Tier segmented + Table/Podium view toggle -->
                    <div class="d-flex align-center gap-3 px-4 py-2 flex-wrap">
                        <div class="d-flex align-center gap-2">
                            <span class="filter-label">Tier</span>
                            <div class="segmented category-segmented">
                                <button class="segmented-btn" :class="{ active: selectedTier === 'all' }" @click="selectedTier = 'all'; searchTerm = ''">
                                    All <span class="seg-count seg-count--all">{{ tierCounts.all }}</span>
                                </button>
                                <button
                                    v-for="t in tiers"
                                    :key="t.value"
                                    class="segmented-btn"
                                    :class="['category-btn-' + t.value, { active: selectedTier === t.value }]"
                                    @click="selectedTier = t.value; searchTerm = ''"
                                >
                                    {{ t.label }}
                                    <span class="seg-count" :class="'seg-count--' + t.value">{{ tierCounts[t.value] ?? 0 }}</span>
                                </button>
                            </div>
                        </div>
                        <v-btn-toggle v-model="viewMode" mandatory density="compact" variant="outlined" divided class="ml-auto">
                            <v-btn value="table"  size="small" title="Table view"><v-icon size="15">mdi-table-large</v-icon></v-btn>
                            <v-btn value="podium" size="small" title="Podium view"><v-icon size="15">mdi-podium-gold</v-icon></v-btn>
                        </v-btn-toggle>
                    </div>

                    <!-- Island + Region filter row -->
                    <div class="d-flex align-center gap-3 px-4 py-2 flex-wrap" style="border-top:1px solid rgba(0,0,0,0.06);">
                        <div class="d-flex align-center gap-2">
                            <span class="filter-label">Island</span>
                            <div class="segmented">
                                <button class="segmented-btn" :class="{ active: selectedIsland === 'all' }" @click="setIsland('all')">All</button>
                                <button class="segmented-btn" :class="{ active: selectedIsland === 'luzon' }" @click="setIsland('luzon')">Luzon</button>
                                <button class="segmented-btn" :class="{ active: selectedIsland === 'visayas' }" @click="setIsland('visayas')">Visayas</button>
                                <button class="segmented-btn" :class="{ active: selectedIsland === 'mindanao' }" @click="setIsland('mindanao')">Mindanao</button>
                            </div>
                        </div>
                        <div class="d-flex align-center gap-2">
                            <span class="filter-label">Region</span>
                            <v-select
                                v-model="selectedRegion"
                                :items="availableRegionOptions"
                                item-title="title"
                                item-value="value"
                                density="compact"
                                variant="solo-filled"
                                hide-details
                                style="max-width:175px; min-width:150px;"
                            />
                        </div>
                    </div>

                    <!-- Category segmented (Overall / CORE / FUNCTIONAL / SUPPORT) + Year segmented -->
                    <div class="d-flex align-center gap-3 px-4 py-2 flex-wrap" style="border-top:1px solid rgba(0,0,0,0.06);">
                        <div class="d-flex align-center gap-2">
                            <span class="filter-label">Category</span>
                            <div class="segmented outcome-segmented">
                                <button
                                    class="segmented-btn"
                                    :class="{ active: selectedCategory === 'overall' }"
                                    @click="selectedCategory = 'overall'"
                                >Overall</button>
                                <button
                                    v-for="c in categoryOptions" :key="c.value"
                                    class="segmented-btn"
                                    :class="{ active: selectedCategory === c.value }"
                                    @click="selectedCategory = c.value"
                                >{{ c.label }} <span class="cat-weight">{{ c.weight }}</span></button>
                            </div>
                        </div>
                        <span v-if="selectedCategory !== 'overall'" class="text-caption text-medium-emphasis">
                            Ranking by <strong>{{ selectedCategory }}</strong> contribution
                            <template v-if="selectedTier !== 'all'"> · re-bucketed within {{ tierLabel }}</template>
                        </span>
                        <div class="ml-auto d-flex align-center gap-2">
                            <span class="filter-label">Year</span>
                            <div class="segmented">
                                <button
                                    v-for="y in available_years" :key="y"
                                    class="segmented-btn"
                                    :class="{ active: selectedYear === y }"
                                    @click="selectedYear = y"
                                >{{ y }}</button>
                            </div>
                        </div>
                    </div>

                    <v-divider />

                    <!-- Search row (table view only) -->
                    <div v-if="viewMode === 'table'" class="px-4 py-2 d-flex justify-end">
                        <v-text-field
                            v-model="searchTerm"
                            placeholder="Search province or director…"
                            variant="solo-filled"
                            density="compact"
                            hide-details
                            clearable
                            prepend-inner-icon="mdi-magnify"
                            style="max-width:280px;"
                        />
                    </div>

                    <!-- Table View -->
                    <v-data-table
                        v-if="viewMode === 'table'"
                        :headers="tableHeaders"
                        :items="rankedScores"
                        :search="searchTerm"
                        density="compact"
                        fixed-header
                        height="460"
                        hide-default-footer
                        :items-per-page="-1"
                        class="leaderboard-table"
                    >
                        <template #item.rank="{ item }">
                            <span v-if="!isRanked(item)" class="text-caption text-disabled">—</span>
                            <span v-else-if="item.rank === 1" class="rank-medal rank-gold">1st</span>
                            <span v-else-if="item.rank === 2" class="rank-medal rank-silver">2nd</span>
                            <span v-else-if="item.rank === 3" class="rank-medal rank-bronze">3rd</span>
                            <span v-else class="text-caption text-medium-emphasis">#{{ item.rank }}</span>
                        </template>

                        <template #item.bucket="{ item }">
                            <v-chip v-if="item.status === 'no_director'" color="blue-grey" size="x-small" variant="tonal" class="font-weight-medium">
                                No director
                            </v-chip>
                            <v-chip v-else-if="item.status === 'no_data'" color="blue-grey" size="x-small" variant="tonal" class="font-weight-medium">
                                No data
                            </v-chip>
                            <v-chip v-else-if="item.bucket" :color="bucketColor(item.bucket)" size="x-small" variant="tonal" class="font-weight-medium">
                                {{ bucketDisplay(item.bucket) }}
                            </v-chip>
                            <span v-else class="text-caption text-disabled">—</span>
                        </template>

                        <template #item.province="{ item }">
                            <a :href="`/province-directories/${item.province_url_id}`"
                               class="text-body-2 font-weight-medium province-link"
                               :class="{ 'text-disabled': !isRanked(item) }">
                                {{ item.province }}
                            </a>
                            <div v-if="item.region" class="text-caption text-disabled" style="line-height:1.2;margin-top:1px;">{{ item.region }}</div>
                        </template>

                        <template #item.director="{ item }">
                            <a v-if="item.director && item.director_id"
                               :href="`/profile/${item.director_id}`"
                               class="text-body-2 director-link"
                               :class="!isRanked(item) ? 'text-disabled font-italic' : 'text-medium-emphasis'">
                                {{ item.director }}
                            </a>
                            <span v-else class="text-body-2" :class="!isRanked(item) ? 'text-disabled font-italic' : 'text-medium-emphasis'">
                                {{ item.director || 'Vacant' }}
                            </span>
                        </template>

                        <template #item.category="{ item }">
                            <v-chip :color="tierColor(item.category)" size="x-small" variant="tonal" class="font-weight-medium text-uppercase">
                                {{ item.category }}
                            </v-chip>
                        </template>

                        <template #item.core="{ item }">
                            <span v-if="isRanked(item)" class="text-caption cat-cell" :class="{ 'cat-cell--active': selectedCategory === 'CORE' }">
                                {{ item.subtotals_pct.CORE.toFixed(1) }}%
                            </span>
                            <span v-else class="text-caption text-disabled">—</span>
                        </template>
                        <template #item.functional="{ item }">
                            <span v-if="isRanked(item)" class="text-caption cat-cell" :class="{ 'cat-cell--active': selectedCategory === 'FUNCTIONAL' }">
                                {{ item.subtotals_pct.FUNCTIONAL.toFixed(1) }}%
                            </span>
                            <span v-else class="text-caption text-disabled">—</span>
                        </template>
                        <template #item.support="{ item }">
                            <span v-if="isRanked(item)" class="text-caption cat-cell" :class="{ 'cat-cell--active': selectedCategory === 'SUPPORT' }">
                                {{ item.subtotals_pct.SUPPORT.toFixed(1) }}%
                            </span>
                            <span v-else class="text-caption text-disabled">—</span>
                        </template>

                        <template #item.total_pct="{ item }">
                            <span v-if="isRanked(item)" class="score-pct cat-cell" :class="{ 'cat-cell--active': selectedCategory === 'overall' }" :style="{ color: bucketColor(item.bucket) }">
                                {{ item.total_pct.toFixed(2) }}%
                            </span>
                            <span v-else class="text-caption text-disabled">—</span>
                        </template>

                        <template #no-data>
                            <div class="text-center py-8 text-medium-emphasis text-body-2">No provinces found</div>
                        </template>
                    </v-data-table>

                    <!-- Podium View -->
                    <div v-else class="px-5 pt-3 pb-5">
                        <div v-if="top3[0]" class="podium-banner mb-4">
                            <v-icon size="16" color="amber-darken-2">mdi-trophy</v-icon>
                            <span class="text-body-2 ml-2">
                                <a :href="`/province-directories/${top3[0].province_url_id}`" class="province-link font-weight-bold">{{ top3[0].province }}</a> leads {{ selectedYear }}<template v-if="selectedTier !== 'all'"> in {{ tierLabel }}</template><template v-if="selectedCategory !== 'overall'"> on {{ selectedCategory }}</template> with
                                <span class="font-weight-bold" :style="{ color: bucketColor(top3[0].bucket) }">{{ getScore(top3[0]).toFixed(2) }}%</span>
                            </span>
                        </div>

                        <div class="d-flex gap-5">
                            <div ref="podiumStageRef" class="podium-stage">
                                <div class="podium-items">
                                    <div v-if="top3[0]" class="podium-item">
                                        <div class="podium-info">
                                            <v-icon color="amber-darken-1" size="26" class="mb-1">mdi-trophy</v-icon>
                                            <div class="podium-province"><a :href="`/province-directories/${top3[0].province_url_id}`" class="province-link">{{ top3[0].province }}</a></div>
                                            <div v-if="top3[0].region" class="podium-region">{{ top3[0].region }}</div>
                                            <div class="podium-score" :style="{ color: bucketColor(top3[0].bucket) }">{{ top3[0].total_pct.toFixed(2) }}%</div>
                                        </div>
                                        <div class="podium-block podium-gold">
                                            <v-icon color="white" size="26">mdi-crown</v-icon>
                                            <span class="podium-rank-num">1st</span>
                                        </div>
                                    </div>
                                    <div v-if="top3[1]" class="podium-item">
                                        <div class="podium-info">
                                            <div class="podium-province"><a :href="`/province-directories/${top3[1].province_url_id}`" class="province-link">{{ top3[1].province }}</a></div>
                                            <div v-if="top3[1].region" class="podium-region">{{ top3[1].region }}</div>
                                            <div class="podium-score" :style="{ color: bucketColor(top3[1].bucket) }">{{ top3[1].total_pct.toFixed(2) }}%</div>
                                        </div>
                                        <div class="podium-block podium-silver">
                                            <v-icon color="white" size="22">mdi-medal</v-icon>
                                            <span class="podium-rank-num">2nd</span>
                                        </div>
                                    </div>
                                    <div v-if="top3[2]" class="podium-item">
                                        <div class="podium-info">
                                            <div class="podium-province"><a :href="`/province-directories/${top3[2].province_url_id}`" class="province-link">{{ top3[2].province }}</a></div>
                                            <div v-if="top3[2].region" class="podium-region">{{ top3[2].region }}</div>
                                            <div class="podium-score" :style="{ color: bucketColor(top3[2].bucket) }">{{ top3[2].total_pct.toFixed(2) }}%</div>
                                        </div>
                                        <div class="podium-block podium-bronze">
                                            <v-icon color="white" size="22">mdi-medal-outline</v-icon>
                                            <span class="podium-rank-num">3rd</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Top 3 category-subtotal breakdown -->
                                <div v-if="top3.length" class="podium-breakdown">
                                    <div v-for="(p, idx) in top3" :key="p.province" class="breakdown-col">
                                        <div v-for="cat in ['CORE','FUNCTIONAL','SUPPORT']" :key="cat" class="breakdown-row">
                                            <span class="breakdown-label">{{ cat }}</span>
                                            <span class="breakdown-score">{{ p.subtotals_pct[cat].toFixed(1) }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <v-divider vertical class="mx-1" />

                            <div class="flex-1 podium-rest-scroll" :style="podiumStageHeight ? { maxHeight: podiumStageHeight + 'px' } : null">
                                <div v-for="item in restList" :key="item.province" class="podium-rest-row">
                                    <span class="podium-rest-rank">#{{ item.rank }}</span>
                                    <div class="flex-1" style="min-width:0;">
                                        <div class="text-body-2 font-weight-medium text-truncate">
                                            <a :href="`/province-directories/${item.province_url_id}`" class="province-link">{{ item.province }}</a>
                                        </div>
                                        <div v-if="item.region" class="text-caption text-disabled text-truncate" style="line-height:1.2;">{{ item.region }}</div>
                                    </div>
                                    <v-chip v-if="item.bucket && selectedTier !== 'all'" :color="bucketColor(item.bucket)" size="x-small" variant="tonal" class="font-weight-medium flex-shrink-0">
                                        {{ bucketDisplay(item.bucket) }}
                                    </v-chip>
                                    <v-chip :color="tierColor(item.category)" size="x-small" variant="tonal" class="font-weight-medium text-uppercase flex-shrink-0">
                                        {{ item.category }}
                                    </v-chip>
                                    <div class="score-cell podium-rest-cell">
                                        <div class="score-track">
                                            <div class="score-fill" :style="{ width: `${Math.min(item.total_pct, 100)}%`, background: bucketColor(item.bucket) }" />
                                        </div>
                                        <div class="score-text">
                                            <span class="score-pct" :style="{ color: bucketColor(item.bucket) }">{{ item.total_pct.toFixed(2) }}%</span>
                                            <span class="score-counts">CORE {{ item.subtotals_pct.CORE.toFixed(1) }} · FUNC {{ item.subtotals_pct.FUNCTIONAL.toFixed(1) }} · SUPP {{ item.subtotals_pct.SUPPORT.toFixed(1) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="!restList.length" class="text-center py-8 text-caption text-medium-emphasis">
                                    Only {{ top3.length }} province(s) in this filter
                                </div>
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- Top 10 + Under Performers side by side -->
        <v-row dense>
            <v-col cols="12" md="6">
                <v-card border elevation="0" rounded="lg" class="overflow-hidden">
                    <div class="chart-header px-4 pt-3 pb-2">
                        <div class="d-flex align-center gap-2">
                            <v-icon size="15" color="success">mdi-star-circle-outline</v-icon>
                            <span class="text-body-2 font-weight-bold">Top 10 Provinces & Directors</span>
                        </div>
                        <div class="text-caption text-medium-emphasis">
                            Highest weighted score · {{ tierLabel }} · {{ selectedYear }}
                        </div>
                    </div>
                    <VueApexCharts
                        type="bar"
                        height="360"
                        :options="top10Options"
                        :series="top10Series"
                        :key="`top10-${selectedYear}-${selectedTier}`"
                    />
                </v-card>
            </v-col>
            <v-col cols="12" md="6">
                <v-card border elevation="0" rounded="lg" class="overflow-hidden">
                    <div class="chart-header px-4 pt-3 pb-2">
                        <div class="d-flex align-center gap-2">
                            <v-icon size="15" color="error">mdi-alert-circle-outline</v-icon>
                            <span class="text-body-2 font-weight-bold">Under Performers</span>
                        </div>
                        <div class="text-caption text-medium-emphasis">
                            Bottom 20% of each tier · {{ tierLabel }} · {{ selectedYear }}
                        </div>
                    </div>
                    <VueApexCharts
                        v-if="underData.names.length"
                        type="bar"
                        height="360"
                        :options="underOptions"
                        :series="underSeries"
                        :key="`under-${selectedYear}-${selectedTier}`"
                    />
                    <div v-else class="d-flex flex-column align-center justify-center gap-2" style="height:360px;">
                        <v-icon size="48" color="success">mdi-check-decagram-outline</v-icon>
                        <div class="text-body-2 font-weight-medium">No Under Performers</div>
                        <div class="text-caption text-medium-emphasis">Tier is too small to bucket, or no rankings yet</div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import QuantityCard from '@/Components/Cards/QuantityCard.vue';
import { RiGroupLine, RiUserStarLine, RiCheckboxCircleLine, RiBuildingLine } from '@remixicon/vue';

const props = defineProps({
    total_users:                { type: Number, default: 0 },
    active_reporting_provinces: { type: Number, default: 0 },
    total_directors:            { type: Number, default: 0 },
    total_employees:            { type: Number, default: 0 },
    total_provinces:            { type: Number, default: 0 },
    rankings_by_year:           { type: Object, default: () => ({}) },
    kpi_categories:             { type: Array,  default: () => [] },
    available_years:            { type: Array,  default: () => [] },
});

const selectedYear     = ref(props.available_years[0] ?? new Date().getFullYear());
const selectedTier     = ref('all');
const selectedCategory = ref('overall'); // 'overall' | 'CORE' | 'FUNCTIONAL' | 'SUPPORT'
const selectedIsland   = ref('all');
const selectedRegion   = ref('');
const searchTerm       = ref('');
const viewMode         = ref('table');

const ISLAND_REGIONS = {
    luzon:    ['NCR', 'CAR', 'Region I', 'Region II', 'Region III', 'Region IV-A', 'Region IV-B', 'Region V'],
    visayas:  ['Region VI', 'Region VII', 'Region VIII'],
    mindanao: ['Region IX', 'Region X', 'Region XI', 'Region XII', 'Region XIII', 'BARMM'],
};

const setIsland = (island) => {
    selectedIsland.value = island;
    selectedRegion.value = '';
};

const availableRegionOptions = computed(() => {
    const pool = selectedIsland.value === 'all'
        ? [...ISLAND_REGIONS.luzon, ...ISLAND_REGIONS.visayas, ...ISLAND_REGIONS.mindanao]
        : (ISLAND_REGIONS[selectedIsland.value] ?? []);
    return [{ title: 'All Regions', value: '' }, ...pool.map(r => ({ title: r, value: r }))];
});

const tiers = [
    { value: 'micro',  label: 'Micro'  },
    { value: 'small',  label: 'Small'  },
    { value: 'medium', label: 'Medium' },
    { value: 'large',  label: 'Large'  },
    { value: 'cstc',   label: 'CSTC'   },
];

// Category options derived from the kpi_categories prop so the weight labels
// stay in sync with whatever the matrix says.
const categoryOptions = computed(() =>
    props.kpi_categories.map(c => ({
        value:  c.code,
        label:  c.code,
        weight: `${Math.round(c.weight * 100)}%`,
    }))
);

const tierLabel = computed(() =>
    selectedTier.value === 'all'
        ? 'All Tiers'
        : (tiers.find(t => t.value === selectedTier.value)?.label ?? '')
);

// Rank-percentile bucketing matches RankingService::rankAndBucket on the backend.
// Mirrored client-side so the Category filter can re-bucket within the active
// tier when the user picks CORE / FUNCTIONAL / SUPPORT instead of Overall.
// If config/ranking.php values change, update these to match.
const BUCKET_TOP_PCT   = 0.20;
const BUCKET_UNDER_PCT = 0.20;
const MIN_GROUP_FOR_BUCKETS = 5;

const getScore = (row) =>
    selectedCategory.value === 'overall'
        ? row.total_pct
        : (row.subtotals_pct?.[selectedCategory.value] ?? 0);

// Buckets only the ranked rows (status === 'ranked'); unranked rows keep their
// null rank/bucket and get appended at the end alphabetically.
const isRanked = (r) => (r.status ?? 'ranked') === 'ranked';

const assignBuckets = (sortedRows) => {
    const ranked   = sortedRows.filter(isRanked);
    const unranked = sortedRows.filter(r => !isRanked(r))
        .slice().sort((a, b) => a.province.localeCompare(b.province))
        .map(r => ({ ...r, rank: null, bucket: null }));

    const n = ranked.length;
    if (n < MIN_GROUP_FOR_BUCKETS) {
        return [...ranked.map((r, i) => ({ ...r, rank: i + 1, bucket: null })), ...unranked];
    }
    const topN = Math.max(1, Math.round(n * BUCKET_TOP_PCT));
    let   undN = Math.max(1, Math.round(n * BUCKET_UNDER_PCT));
    if (topN + undN >= n) undN = Math.max(1, n - topN - 1);
    const avgEnd = n - undN;
    const bucketed = ranked.map((r, i) => ({
        ...r,
        rank:   i + 1,
        bucket: i < topN ? 'Top' : i < avgEnd ? 'Average' : 'Under',
    }));
    return [...bucketed, ...unranked];
};

// Pull this year's per-tier rankings from the controller payload, flatten into
// one array with rank already assigned per tier. When "All" is selected we
// resort globally by total_pct so the table is comparable across tiers — but
// the per-tier rank field is still useful so we surface it as `tier_rank`.
const allTierRows = computed(() => {
    const yearData = props.rankings_by_year[selectedYear.value] ?? {};
    const out = [];
    for (const [tier, rows] of Object.entries(yearData)) {
        for (const r of rows) {
            out.push({ ...r, tier_rank: r.rank });
        }
    }
    return out;
});

const baseRows = computed(() => {
    let rows = allTierRows.value;
    if (selectedIsland.value !== 'all') {
        const regions = ISLAND_REGIONS[selectedIsland.value];
        rows = rows.filter(r => regions.includes(r.region));
    }
    if (selectedRegion.value) {
        rows = rows.filter(r => r.region === selectedRegion.value);
    }
    return rows;
});

const rankedScores = computed(() => {
    const isOverall = selectedCategory.value === 'overall';

    if (selectedTier.value === 'all') {
        // Global view: ranked rows sorted by active score, unranked appended at the
        // bottom alphabetically. Performer column is hidden in All Tiers mode.
        const all      = baseRows.value;
        const ranked   = all.filter(isRanked)
            .slice().sort((a, b) => getScore(b) - getScore(a))
            .map((r, i) => ({ ...r, rank: i + 1 }));
        const unranked = all.filter(r => !isRanked(r))
            .slice().sort((a, b) => a.province.localeCompare(b.province))
            .map(r => ({ ...r, rank: null, bucket: null }));
        return [...ranked, ...unranked];
    }

    const tierRows = baseRows.value.filter(r => r.category === selectedTier.value);

    // Overall view: backend already excluded unranked from bucketing. Trust its
    // ordering — unranked rows are appended at the end with null rank/bucket.
    if (isOverall) return tierRows;

    // Category view: re-sort + re-bucket only the ranked rows within the tier.
    const sorted = tierRows.slice().sort((a, b) => {
        // Unranked sinks to the bottom regardless of category sort
        if (!isRanked(a) && isRanked(b)) return 1;
        if (isRanked(a) && !isRanked(b)) return -1;
        return getScore(b) - getScore(a);
    });
    return assignBuckets(sorted);
});

const tierCounts = computed(() => {
    const counts = { all: baseRows.value.length };
    for (const r of baseRows.value) counts[r.category] = (counts[r.category] ?? 0) + 1;
    return counts;
});

const bucketCounts = computed(() => {
    const counts = { Top: 0, Average: 0, Under: 0 };
    for (const r of rankedScores.value) {
        if (r.bucket && counts[r.bucket] !== undefined) counts[r.bucket]++;
    }
    return counts;
});

// Podium + rest list operate on RANKED rows only so unranked never appears on
// the leader podium. Unranked provinces still show in the table at the bottom.
const rankedOnly       = computed(() => rankedScores.value.filter(isRanked));
const unrankedProvinces = computed(() => rankedScores.value.filter(r => !isRanked(r)));
const top3     = computed(() => rankedOnly.value.slice(0, 3));
const restList = computed(() => rankedOnly.value.slice(3));

// Performer (Top / Average / Under) is bucketed per-tier so it's only meaningful
// when a single tier is selected. In the "All Tiers" view we hide the column
// entirely — mixing buckets across tiers would put a SMALL "Top" below a
// LARGE "Average" on the absolute % axis, which reads as inconsistent.
const tableHeaders = computed(() => {
    const cols = [
        { title: 'Rank',     key: 'rank',     width: '70px',  sortable: false },
        { title: 'Performer', key: 'bucket',  width: '110px', sortable: true  },
        { title: 'Province', key: 'province', sortable: true  },
        { title: 'Director', key: 'director', sortable: false },
        { title: 'Tier',     key: 'category', width: '90px',  align: 'center', sortable: true  },
        { title: 'CORE 60%', key: 'core',     width: '90px',  align: 'center', sortable: false },
        { title: 'FUNC 30%', key: 'functional', width: '90px', align: 'center', sortable: false },
        { title: 'SUPP 10%', key: 'support',  width: '90px',  align: 'center', sortable: false },
        { title: 'Total',    key: 'total_pct', width: '110px', align: 'center', sortable: true  },
    ];
    return selectedTier.value === 'all' ? cols.filter(c => c.key !== 'bucket') : cols;
});

const bucketColor = (b) => ({ Top: '#15803d', Average: '#ca8a04', Under: '#b91c1c' }[b] ?? '#64748b');
const bucketDisplay = (b) => ({ Top: 'Top', Average: 'Average', Under: 'Under' }[b] ?? '—');
const tierColor = (cat) => ({ micro: 'blue-grey', small: 'teal', medium: 'indigo', large: 'deep-purple', cstc: 'pink' }[cat] ?? 'grey');

// Top 10 by the active score (total or category subtotal). Excludes unranked.
const top10Data = computed(() => {
    const slice = rankedOnly.value.slice(0, 10);
    return {
        names:     slice.map(s => s.province),
        scores:    slice.map(s => getScore(s)),
        directors: slice.map(s => s.director || '—'),
        buckets:   slice.map(s => s.bucket),
    };
});

// Under Performers = anyone with bucket=Under in the current filter (sorted desc)
const underData = computed(() => {
    const slice = rankedOnly.value
        .filter(s => s.bucket === 'Under')
        .slice()
        .sort((a, b) => getScore(b) - getScore(a));
    return {
        names:     slice.map(s => s.province),
        scores:    slice.map(s => getScore(s)),
        directors: slice.map(s => s.director || '—'),
        buckets:   slice.map(s => s.bucket),
    };
});

const tooltipHtml = (data, i) => {
    const score = (data.scores[i] ?? 0).toFixed(2);
    const dir   = data.directors[i] ?? '—';
    const name  = data.names[i]     ?? '';
    const bk    = data.buckets[i]   ?? '—';
    const color = bucketColor(bk);
    const label = selectedCategory.value === 'overall' ? 'weighted score' : `${selectedCategory.value} contribution`;
    return `<div style="padding:8px 12px;font-size:12px;font-family:inherit;min-width:200px;">
                <div style="font-weight:700;margin-bottom:4px;">${name}</div>
                <div style="color:#64748b;margin-bottom:2px;">Director: ${dir}</div>
                <div style="color:#64748b;margin-bottom:6px;">Performer: ${bucketDisplay(bk)}</div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:${color};flex-shrink:0;"></span>
                    <strong style="color:${color};">${score}% ${label}</strong>
                </div>
            </div>`;
};

const makeHorizOptions = (data) => {
    const max = Math.max(...data.scores, 0);
    const computedMax = Math.max(10, Math.ceil((max * 1.15) / 5) * 5);
    return {
        chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit',
                 animations: { enabled: true, speed: 700, animateGradually: { enabled: true, delay: 80 } } },
        plotOptions: { bar: { horizontal: true, barHeight: '68%', borderRadius: 3, distributed: true } },
        colors: data.buckets.map(bucketColor),
        legend: { show: false },
        grid: { borderColor: '#f1f5f9',
                xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } },
                padding: { left: 0, right: 12, top: -8, bottom: 0 } },
        dataLabels: { enabled: true, formatter: v => `${v.toFixed(1)}%`,
                      style: { fontSize: '10px', fontFamily: 'inherit', fontWeight: '600', colors: ['#fff'] } },
        xaxis: { categories: data.names, min: 0, max: computedMax,
                 labels: { formatter: v => `${v}%`, style: { fontSize: '10px', fontFamily: 'inherit', colors: '#94a3b8' } },
                 axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { fontSize: '10.5px', fontFamily: 'inherit', colors: '#475569' }, maxWidth: 115 } },
        tooltip: { theme: 'light', custom: ({ dataPointIndex }) => tooltipHtml(data, dataPointIndex) },
    };
};

const top10Options = computed(() => makeHorizOptions(top10Data.value));
const top10Series  = computed(() => [{ name: 'Weighted Score', data: top10Data.value.scores }]);
const underOptions = computed(() => makeHorizOptions(underData.value));
const underSeries  = computed(() => [{ name: 'Weighted Score', data: underData.value.scores }]);

// Match the right-side rest list's max height to the podium stage so the two
// columns line up flush at the bottom regardless of how many top-3 details or
// breakdown rows render. Without this the rest list either leaves a gap below
// the last row or overflows past the breakdown when many provinces are listed.
const podiumStageRef    = ref(null);
const podiumStageHeight = ref(0);
let podiumResizeObserver = null;

const updatePodiumHeight = () => {
    if (podiumStageRef.value) podiumStageHeight.value = podiumStageRef.value.offsetHeight;
};

onMounted(() => {
    nextTick(updatePodiumHeight);
    if (typeof ResizeObserver !== 'undefined') {
        podiumResizeObserver = new ResizeObserver(updatePodiumHeight);
        if (podiumStageRef.value) podiumResizeObserver.observe(podiumStageRef.value);
    }
});

onUnmounted(() => {
    if (podiumResizeObserver) podiumResizeObserver.disconnect();
});

// Re-measure when the podium re-renders (view toggle, filter change) since the
// ResizeObserver is rebound to a new DOM node when v-if flips podium ↔ table.
watch([viewMode, selectedYear, selectedTier, selectedCategory, selectedIsland, selectedRegion], async () => {
    await nextTick();
    updatePodiumHeight();
    if (podiumResizeObserver && podiumStageRef.value) {
        podiumResizeObserver.disconnect();
        podiumResizeObserver.observe(podiumStageRef.value);
    }
});
</script>

<style scoped>
.filter-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}
.segmented {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    padding: 3px;
    background: #f1f5f9;
    border-radius: 9px;
    border: 1px solid #e2e8f0;
}
.segmented-btn {
    appearance: none;
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 12px;
    font-weight: 500;
    padding: 5px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    font-family: inherit;
    line-height: 1.3;
    white-space: nowrap;
}
.segmented-btn:hover:not(.active) { color: #0f172a; background: rgba(255, 255, 255, 0.6); }
.segmented-btn.active {
    background: #ffffff;
    color: #0f172a;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);
}

.seg-count {
    display: inline-block;
    margin-left: 5px;
    padding: 1px 7px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 700;
    line-height: 1.5;
    background: #e2e8f0;
    color: #475569;
}
.seg-count--all    { background: #e0e7ff; color: #4338ca; }
.seg-count--micro  { background: #cfd8dc; color: #455a64; }
.seg-count--small  { background: #b2dfdb; color: #00695c; }
.seg-count--medium { background: #c5cae9; color: #283593; }
.seg-count--large  { background: #d1c4e9; color: #4527a0; }
.seg-count--cstc   { background: #f8bbd0; color: #ad1457; }

.category-btn-micro.active  { color: #455a64; background: #eceff1; box-shadow: 0 1px 2px rgba(69,90,100,0.10); }
.category-btn-small.active  { color: #00695c; background: #e0f2f1; box-shadow: 0 1px 2px rgba(0,105,92,0.10); }
.category-btn-medium.active { color: #283593; background: #e8eaf6; box-shadow: 0 1px 2px rgba(40,53,147,0.10); }
.category-btn-large.active  { color: #4527a0; background: #ede7f6; box-shadow: 0 1px 2px rgba(69,39,160,0.10); }
.category-btn-cstc.active   { color: #ad1457; background: #fce4ec; box-shadow: 0 1px 2px rgba(173,20,87,0.10); }

.chart-header { border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); }
.legend-dot   { display: inline-block; width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }

.leaderboard-table :deep(tr) { cursor: default; }

.province-link, .director-link {
    text-decoration: none !important;
    color: inherit;
    transition: color 0.15s;
}
.province-link:hover { color: #4f46e5 !important; text-decoration: underline !important; }
.director-link:hover { color: #4f46e5 !important; text-decoration: underline !important; }
.leaderboard-table :deep(thead th) { font-size: 11px !important; font-weight: 600 !important; }
.leaderboard-table :deep(table) { width: 100% !important; }

.rank-medal { display: inline-block; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 20px; }
.rank-gold   { background: #fef9c3; color: #854d0e; }
.rank-silver { background: #f1f5f9; color: #475569; }
.rank-bronze { background: #ffedd5; color: #9a3412; }

.score-track { flex: 1; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
.score-fill  { height: 100%; border-radius: 4px; transition: width 0.6s ease; }

.podium-banner {
    display: flex; align-items: center;
    background: linear-gradient(90deg, #fef9c3 0%, #fef3c7 60%, #fff 100%);
    border-left: 3px solid #ca8a04;
    border-radius: 0 6px 6px 0;
    padding: 8px 14px;
    box-shadow: 0 1px 2px rgba(202, 138, 4, 0.08);
}
.podium-stage   { width: 42%; flex-shrink: 0; display: flex; flex-direction: column; align-self: flex-start; }
.podium-items   { display: flex; align-items: flex-end; gap: 8px; }
.podium-item    { flex: 1; display: flex; flex-direction: column; align-items: center; }
.podium-info    { text-align: center; padding-bottom: 10px; }
.podium-province { font-size: 17px; font-weight: 700; line-height: 1.3; max-width: 170px; word-wrap: break-word; }
.podium-region   { font-size: 15px; color: #94a3b8; font-weight: 500; margin-top: 2px; line-height: 1.2; }
.podium-score   { font-size: 24px; font-weight: 800; line-height: 1; }
.podium-block   { width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; border-radius: 8px 8px 0 0; color: white; }
.podium-rank-num { font-size: 18px; font-weight: 800; color: white; }
.podium-gold    { height: 210px; background: linear-gradient(160deg, #ca8a04, #fde047); }
.podium-silver  { height: 160px; background: linear-gradient(160deg, #64748b, #cbd5e1); }
.podium-bronze  { height: 120px; background: linear-gradient(160deg, #78350f, #c2410c); }

.podium-rest-row { display: flex; align-items: center; gap: 12px; padding: 7px 0; border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); }
.podium-rest-rank { width: 32px; text-align: right; flex-shrink: 0; font-size: 11px; color: #94a3b8; font-weight: 600; }
.podium-rest-scroll { overflow-y: auto; }

.podium-breakdown { display: flex; gap: 20px; margin-top: 18px; padding-top: 16px; border-top: 1px solid rgba(0,0,0,0.06); }
.breakdown-col    { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 5px; }
.breakdown-row    { display: flex; align-items: center; gap: 8px; cursor: default; }
.breakdown-label  { font-size: 10px; font-weight: 600; color: #64748b; width: 110px; flex-shrink: 0; white-space: nowrap; }
.breakdown-score  { font-size: 11px; font-weight: 700; width: 60px; text-align: right; flex-shrink: 0; margin-left: auto; }

.score-text { display: flex; flex-direction: column; line-height: 1.2; }
.score-pct  { font-size: 16px; font-weight: 700; white-space: nowrap; }
.score-counts { font-size: 11px; color: rgba(0, 0, 0, 0.55); white-space: nowrap; }

/* Highlights the column matching the active category filter so the user can
   instantly see which value drives the current sort + bucket. */
.cat-cell {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    transition: background 0.15s ease;
}
.cat-cell--active {
    background: rgba(99, 102, 241, 0.10);
    font-weight: 700;
}

/* Small weight chip embedded in the Category segmented buttons */
.cat-weight {
    display: inline-block;
    margin-left: 5px;
    padding: 1px 6px;
    border-radius: 7px;
    font-size: 9.5px;
    font-weight: 700;
    background: #e0e7ff;
    color: #4338ca;
    line-height: 1.5;
}

.score-cell { display: flex; align-items: center; gap: 8px; width: 290px; flex-shrink: 0; }
.score-cell .score-track { flex: unset; width: 100px; flex-shrink: 0; }
.score-cell .score-text  { width: 175px; flex-shrink: 0; }
.score-cell .score-pct   { font-size: 12px; }
.score-cell .score-counts { font-size: 10px; overflow: hidden; text-overflow: ellipsis; }
.podium-rest-cell { width: 290px; }
</style>
