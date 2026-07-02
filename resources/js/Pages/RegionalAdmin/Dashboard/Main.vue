<template>
    <Head title="Dashboard" />
    <div class="d-flex flex-column gap-3">

        <!-- Sticky filter bar -->
        <div class="dashboard-sticky-shell">
        <div class="dashboard-sticky-filters">
            <div class="d-flex align-center gap-2 px-3 py-2 flex-wrap">
                <v-select
                    v-model="selectedTier"
                    :items="tierSelectItems"
                    item-title="label"
                    item-value="value"
                    label="Tier"
                    density="compact"
                    variant="outlined"
                    hide-details
                    class="filter-select"
                    @update:model-value="searchTerm = ''"
                />
                <v-divider vertical class="filter-divider" />
                <v-select
                    v-model="selectedCategory"
                    :items="categorySelectItems"
                    item-title="label"
                    item-value="value"
                    label="Category"
                    density="compact"
                    variant="outlined"
                    hide-details
                    class="filter-select"
                />
                <v-select
                    v-model="selectedYear"
                    :items="available_years"
                    label="Year"
                    density="compact"
                    variant="outlined"
                    hide-details
                    class="filter-select filter-select--narrow"
                    @update:model-value="searchTerm = ''"
                />

                <v-btn-toggle v-model="viewMode" mandatory density="compact" variant="outlined" divided class="ml-auto">
                    <v-btn value="table"  size="small" title="Table view"><v-icon size="15">mdi-table-large</v-icon></v-btn>
                    <v-btn value="podium" size="small" title="Podium view"><v-icon size="15">mdi-podium-gold</v-icon></v-btn>
                    <v-btn value="chart"  size="small" title="Bar chart"><v-icon size="15">mdi-chart-bar</v-icon></v-btn>
                </v-btn-toggle>
            </div>
        </div>
        </div>

        <!-- Stat cards -->
        <v-row dense>
            <v-col cols="12" sm="3">
                <QuantityCard title="Total Provinces"            :quantity="total_provinces"            :icon="RiBuildingLine"       color="indigo" />
            </v-col>
            <v-col cols="12" sm="3">
                <QuantityCard title="Provincial Directors"       :quantity="total_directors"            :icon="RiUserStarLine"       color="indigo" />
            </v-col>
            <v-col cols="12" sm="3">
                <QuantityCard title="Total Employees"            :quantity="total_employees"            :icon="RiGroupLine"          color="indigo" />
            </v-col>
            <v-col cols="12" sm="3">
                <QuantityCard title="Active Reporting Provinces" :quantity="active_reporting_provinces" :icon="RiCheckboxCircleLine" color="indigo" />
            </v-col>
        </v-row>

        <!-- Region leader banner -->
        <v-card v-if="topRow" border elevation="0" rounded="lg" class="region-banner">
            <v-card-item class="py-3 px-5">
                <div class="d-flex align-center gap-4 flex-wrap">
                    <v-avatar color="indigo-lighten-5" size="44" rounded="lg">
                        <v-icon :style="{ color: medalColor(1) }" size="24">mdi-trophy</v-icon>
                    </v-avatar>
                    <div class="flex-1">
                        <div class="text-caption text-medium-emphasis font-weight-bold text-uppercase">
                            {{ region_name }} · Top Performing Province
                        </div>
                        <div class="d-flex align-center gap-2 flex-wrap mt-1">
                            <span class="text-h5 font-weight-black">{{ topRow.province }}</span>
                            <v-chip size="x-small" :color="bandColor(topRow.adjective_label)" variant="flat" class="text-white font-weight-bold">
                                {{ topRow.adjective_label }}
                            </v-chip>
                            <span class="text-body-2 text-medium-emphasis">{{ getScore(topRow).toFixed(2) }}%</span>
                            <span v-if="selectedTier !== 'all'" class="text-caption text-medium-emphasis">· {{ tierLabel }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-caption text-medium-emphasis">Director</div>
                        <div class="text-body-1 font-weight-bold">{{ topRow.director || 'Vacant' }}</div>
                    </div>
                </div>
            </v-card-item>
        </v-card>

        <!-- Ranking card -->
        <v-card border elevation="0" rounded="lg">
            <div class="px-4 pt-3 pb-2 d-flex align-center justify-space-between gap-2 flex-wrap">
                <div class="d-flex align-center gap-2">
                    <v-icon size="15" color="indigo">mdi-podium</v-icon>
                    <span class="text-body-2 font-weight-bold">Provincial Director Ranking</span>
                    <v-chip size="x-small" color="primary" variant="tonal">{{ region_name }}</v-chip>
                    <v-chip size="x-small" color="blue-grey" variant="tonal">{{ rankedProvinces.length }} province(s)</v-chip>
                    <v-chip v-if="selectedTier !== 'all'" size="x-small" color="indigo" variant="tonal">{{ tierLabel }}</v-chip>
                    <v-chip v-if="selectedCategory !== 'overall'" size="x-small" color="teal" variant="tonal">{{ selectedCategory }}</v-chip>
                    <v-chip size="x-small" color="blue-grey" variant="tonal">{{ selectedYear }}</v-chip>
                </div>
                <v-text-field
                    v-if="viewMode !== 'chart'"
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
            <v-divider />

            <!-- Table view -->
            <v-data-table
                v-if="viewMode === 'table'"
                :headers="[
                    { title: 'Rank',         key: 'rank',       width: '70px',  sortable: true  },
                    { title: 'Province',     key: 'province',                   sortable: true  },
                    { title: 'Director',     key: 'director',                   sortable: false },
                    { title: 'Tier',         key: 'category',   width: '90px',  align: 'center', sortable: true  },
                    { title: 'CORE 60%',     key: 'core',       width: '90px',  align: 'center', sortable: false },
                    { title: 'FUNC 30%',     key: 'functional', width: '90px',  align: 'center', sortable: false },
                    { title: 'SUPP 10%',     key: 'support',    width: '90px',  align: 'center', sortable: false },
                    { title: 'Score',        key: 'total_pct',  width: '120px', align: 'center', sortable: true  },
                    { title: 'Performance',  key: 'label',      width: '150px', sortable: false },
                ]"
                :items="rankedProvinces"
                :search="searchTerm"
                :row-props="tableRowProps"
                density="compact"
                fixed-header
                height="540"
                hide-default-footer
                :items-per-page="-1"
                class="leaderboard-table"
            >
                <template #item.rank="{ item }">
                    <span v-if="item.rank == null" class="text-caption text-disabled">—</span>
                    <div v-else class="d-flex align-center gap-1">
                        <v-icon v-if="item.rank <= 3" size="15" :style="{ color: medalColor(item.rank) }">mdi-medal</v-icon>
                        <span v-if="item.rank === 1" class="rank-medal rank-gold">1st</span>
                        <span v-else-if="item.rank === 2" class="rank-medal rank-silver">2nd</span>
                        <span v-else-if="item.rank === 3" class="rank-medal rank-bronze">3rd</span>
                        <span v-else class="text-caption text-medium-emphasis">#{{ item.rank }}</span>
                    </div>
                </template>
                <template #item.province="{ item }">
                    <a :href="`/province-directories/${item.province_url_id}`" class="province-link font-weight-medium text-body-2">{{ item.province }}</a>
                </template>
                <template #item.director="{ item }">
                    <a v-if="item.director && item.director_id" :href="`/profile/${item.director_id}`" class="director-link text-body-2 text-medium-emphasis">{{ item.director }}</a>
                    <span v-else class="text-body-2 text-disabled font-italic">Vacant</span>
                </template>
                <template #item.category="{ item }">
                    <v-chip :color="tierColor(item.category)" size="x-small" variant="tonal" class="font-weight-medium text-uppercase">{{ item.category }}</v-chip>
                </template>
                <template #item.core="{ item }">
                    <span class="text-caption cat-cell" :class="{ 'cat-cell--active': selectedCategory === 'CORE' }">{{ (item.subtotals_pct?.CORE ?? 0).toFixed(1) }}%</span>
                </template>
                <template #item.functional="{ item }">
                    <span class="text-caption cat-cell" :class="{ 'cat-cell--active': selectedCategory === 'FUNCTIONAL' }">{{ (item.subtotals_pct?.FUNCTIONAL ?? 0).toFixed(1) }}%</span>
                </template>
                <template #item.support="{ item }">
                    <span class="text-caption cat-cell" :class="{ 'cat-cell--active': selectedCategory === 'SUPPORT' }">{{ (item.subtotals_pct?.SUPPORT ?? 0).toFixed(1) }}%</span>
                </template>
                <template #item.total_pct="{ item }">
                    <div v-if="item.rank != null">
                        <span class="font-weight-bold" :style="{ color: bandColor(item.adjective_label) }">{{ getScore(item).toFixed(2) }}%</span>
                        <v-progress-linear :model-value="getScore(item)" :color="bandColor(item.adjective_label)" height="3" rounded bg-color="grey-lighten-3" class="mt-1" />
                    </div>
                    <span v-else class="text-caption text-disabled">—</span>
                </template>
                <template #item.label="{ item }">
                    <v-chip v-if="item.status === 'no_director'" size="x-small" color="blue-grey" variant="tonal">No director</v-chip>
                    <v-chip v-else-if="item.status === 'no_data'" size="x-small" color="blue-grey" variant="tonal">No data</v-chip>
                    <v-chip v-else size="x-small" :color="bandColor(item.adjective_label)" variant="tonal" class="font-weight-bold">{{ item.adjective_label }}</v-chip>
                </template>
                <template #no-data>
                    <div class="text-center py-8 text-medium-emphasis text-body-2">No ranked data found for the selected filters.</div>
                </template>
            </v-data-table>

            <!-- Podium view -->
            <div v-else-if="viewMode === 'podium'" class="px-5 pt-3 pb-5">
                <div v-if="top3[0]" class="podium-banner mb-4">
                    <v-icon size="16" color="amber-darken-2">mdi-trophy</v-icon>
                    <span class="text-body-2 ml-2">
                        <strong>{{ top3[0].province }}</strong> leads {{ region_name }} in {{ selectedYear }}
                        <template v-if="selectedTier !== 'all'"> in {{ tierLabel }}</template>
                        scoring <span class="font-weight-bold" :style="{ color: bandColor(top3[0].adjective_label) }">{{ getScore(top3[0]).toFixed(2) }}%</span>
                    </span>
                </div>
                <div class="d-flex gap-5">
                    <div class="podium-stage">
                        <div class="podium-items">
                            <div v-if="top3[0]" class="podium-item" :class="rowHighlightClass(top3[0].province_id)" :data-pid="top3[0].province_id">
                                <div class="podium-info">
                                    <v-icon color="amber-darken-1" size="26" class="mb-1">mdi-trophy</v-icon>
                                    <div class="podium-region-name"><a :href="`/province-directories/${top3[0].province_url_id}`" class="province-link">{{ top3[0].province }}</a></div>
                                    <div class="podium-director">{{ top3[0].director || 'Vacant' }}</div>
                                    <div class="podium-score" :style="{ color: bandColor(top3[0].adjective_label) }">{{ getScore(top3[0]).toFixed(2) }}%</div>
                                </div>
                                <div class="podium-block podium-gold"><v-icon color="white" size="26">mdi-crown</v-icon><span class="podium-rank-num">1st</span></div>
                            </div>
                            <div v-if="top3[1]" class="podium-item" :class="rowHighlightClass(top3[1].province_id)" :data-pid="top3[1].province_id">
                                <div class="podium-info">
                                    <div class="podium-region-name"><a :href="`/province-directories/${top3[1].province_url_id}`" class="province-link">{{ top3[1].province }}</a></div>
                                    <div class="podium-director">{{ top3[1].director || 'Vacant' }}</div>
                                    <div class="podium-score" :style="{ color: bandColor(top3[1].adjective_label) }">{{ getScore(top3[1]).toFixed(2) }}%</div>
                                </div>
                                <div class="podium-block podium-silver"><v-icon color="white" size="22">mdi-medal</v-icon><span class="podium-rank-num">2nd</span></div>
                            </div>
                            <div v-if="top3[2]" class="podium-item" :class="rowHighlightClass(top3[2].province_id)" :data-pid="top3[2].province_id">
                                <div class="podium-info">
                                    <div class="podium-region-name"><a :href="`/province-directories/${top3[2].province_url_id}`" class="province-link">{{ top3[2].province }}</a></div>
                                    <div class="podium-director">{{ top3[2].director || 'Vacant' }}</div>
                                    <div class="podium-score" :style="{ color: bandColor(top3[2].adjective_label) }">{{ getScore(top3[2]).toFixed(2) }}%</div>
                                </div>
                                <div class="podium-block podium-bronze"><v-icon color="white" size="22">mdi-medal-outline</v-icon><span class="podium-rank-num">3rd</span></div>
                            </div>
                        </div>
                        <div v-if="top3.length" class="podium-breakdown">
                            <div v-for="p in top3" :key="p.province_id" class="breakdown-col">
                                <div v-for="cat in ['CORE','FUNCTIONAL','SUPPORT']" :key="cat" class="breakdown-row">
                                    <span class="breakdown-label">{{ cat }}</span>
                                    <span class="breakdown-score">{{ (p.subtotals_pct?.[cat] ?? 0).toFixed(1) }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <v-divider vertical class="mx-1" />
                    <div class="flex-1 podium-rest-scroll">
                        <div v-for="item in restList" :key="item.province_id" class="podium-rest-row" :class="rowHighlightClass(item.province_id)" :data-pid="item.province_id">
                            <span class="podium-rest-rank">#{{ item.rank }}</span>
                            <div class="flex-1" style="min-width:0;">
                                <div class="text-body-2 font-weight-medium text-truncate">
                                    <a :href="`/province-directories/${item.province_url_id}`" class="province-link">{{ item.province }}</a>
                                </div>
                                <div class="text-caption text-disabled text-truncate" style="line-height:1.2;">{{ item.director || 'Vacant' }}</div>
                            </div>
                            <v-chip :color="tierColor(item.category)" size="x-small" variant="tonal" class="font-weight-medium text-uppercase flex-shrink-0">{{ item.category }}</v-chip>
                            <v-chip :color="bandColor(item.adjective_label)" size="x-small" variant="tonal" class="font-weight-bold flex-shrink-0">{{ item.adjective_label }}</v-chip>
                            <span class="podium-rest-score flex-shrink-0" :style="{ color: bandColor(item.adjective_label) }">{{ getScore(item).toFixed(2) }}%</span>
                        </div>
                        <div v-if="!restList.length" class="text-center py-6 text-caption text-medium-emphasis">Only {{ top3.length }} province(s) with data</div>
                    </div>
                </div>
            </div>

            <!-- Bar chart view -->
            <div v-else class="px-4 pt-3 pb-5">
                <div class="text-caption text-medium-emphasis mb-3">
                    Score comparison — provinces in {{ region_name }} · {{ selectedYear }}
                    <template v-if="selectedTier !== 'all'"> · {{ tierLabel }}</template>
                    <template v-if="selectedCategory !== 'overall'"> · {{ selectedCategory }}</template>
                </div>
                <VueApexCharts
                    v-if="rankedOnly.length"
                    type="bar"
                    :height="chartHeight"
                    :options="chartOptions"
                    :series="chartSeries"
                    :key="`ra-chart-${selectedYear}-${selectedTier}-${selectedCategory}`"
                />
                <div v-else class="text-center py-8 text-medium-emphasis text-body-2">No ranked data found for the selected filters.</div>
            </div>
        </v-card>

        <v-alert v-if="!rankedOnly.length" type="info" variant="tonal" density="compact" class="text-body-2">
            No provinces in {{ region_name }} have submitted KPI data for {{ selectedYear }}
            <template v-if="selectedTier !== 'all'"> in the {{ tierLabel }} tier</template>.
            Rankings will appear once data is available.
        </v-alert>

    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import QuantityCard from '@/Components/Cards/QuantityCard.vue';
import { RiGroupLine, RiUserStarLine, RiCheckboxCircleLine, RiBuildingLine } from '@remixicon/vue';

const props = defineProps({
    region_name:                 { type: String, default: '' },
    region_id:                   { type: Number, default: null },
    regions:                     { type: Array,  default: () => [] },
    total_provinces:             { type: Number, default: 0 },
    total_users:                 { type: Number, default: 0 },
    total_directors:             { type: Number, default: 0 },
    total_employees:             { type: Number, default: 0 },
    active_reporting_provinces:  { type: Number, default: 0 },
    rankings_by_year:            { type: Object, default: () => ({}) },
    kpi_categories:              { type: Array,  default: () => [] },
    available_years:             { type: Array,  default: () => [] },
});

// ── Filters ───────────────────────────────────────────────────────────────
const selectedYear     = ref(props.available_years[0] ?? new Date().getFullYear());
const selectedTier     = ref('micro');
const selectedCategory = ref('overall');
const viewMode         = ref('table');
const searchTerm       = ref('');

const tiers = [
    { value: 'micro',  label: 'Micro'  },
    { value: 'small',  label: 'Small'  },
    { value: 'medium', label: 'Medium' },
    { value: 'large',  label: 'Large'  },
];

const tierLabel = computed(() =>
    selectedTier.value === 'all'
        ? 'All Tiers'
        : (tiers.find(t => t.value === selectedTier.value)?.label ?? '')
);

const tierSelectItems = computed(() =>
    tiers.map(t => ({ value: t.value, label: t.label }))
);

const categorySelectItems = computed(() => [
    { value: 'overall', label: 'Overall' },
    ...props.kpi_categories.map(c => ({
        value: c.code,
        label: `${c.code} (${Math.round(c.weight * 100)}%)`,
    })),
]);

// ── Score helpers ─────────────────────────────────────────────────────────
const getScore = (row) =>
    selectedCategory.value === 'overall'
        ? (row.total_pct ?? 0)
        : (row.subtotals_pct?.[selectedCategory.value] ?? 0);

const isRanked = (r) => (r.status ?? 'ranked') === 'ranked';

// ── Province ranking within this region ────────────────────────────────────
// rankings_by_year is already restricted server-side to provinces in this
// region, so every row here belongs to the admin's own region.
const allRows = computed(() => {
    const yearData = props.rankings_by_year[selectedYear.value] ?? {};
    return Object.values(yearData).flat();
});

const rankedProvinces = computed(() => {
    let rows = allRows.value;
    if (selectedTier.value !== 'all') rows = rows.filter(r => r.category === selectedTier.value);

    const ranked = rows.filter(isRanked)
        .slice()
        .sort((a, b) => getScore(b) - getScore(a))
        .map((r, i) => ({ ...r, rank: i + 1 }));
    const unranked = rows.filter(r => !isRanked(r))
        .slice()
        .sort((a, b) => a.province.localeCompare(b.province))
        .map(r => ({ ...r, rank: null }));
    return [...ranked, ...unranked];
});

const rankedOnly = computed(() => rankedProvinces.value.filter(isRanked));
const top3       = computed(() => rankedOnly.value.slice(0, 3));
const restList   = computed(() => rankedOnly.value.slice(3));
const topRow     = computed(() => rankedOnly.value[0] ?? null);

// ── Search ────────────────────────────────────────────────────────────────
const searchTarget = computed(() => {
    const q = searchTerm.value?.trim().toLowerCase();
    if (!q) return null;
    const rows = rankedProvinces.value;
    return (
        rows.find(r => r.province?.toLowerCase().startsWith(q))?.province_id ??
        rows.find(r => r.province?.toLowerCase().includes(q))?.province_id ??
        rows.find(r => (r.director || '').toLowerCase().includes(q))?.province_id ??
        null
    );
});

const rowHighlightClass = (provinceId) => provinceId === searchTarget.value ? 'search-highlight' : '';

const tableRowProps = ({ item }) => ({
    class: item.province_id === searchTarget.value ? 'search-highlight' : '',
});

// ── Colour helpers ─────────────────────────────────────────────────────────
const tierColor  = (cat) => ({ micro: 'blue-grey', small: 'teal', medium: 'indigo', large: 'deep-purple' }[cat] ?? 'grey');
const medalColor = (rank) => rank === 1 ? '#f59e0b' : rank === 2 ? '#94a3b8' : rank === 3 ? '#b45309' : '#cbd5e1';
const bandColor  = (label) => ({
    'Outstanding':       '#16a34a',
    'Very Satisfactory': '#2563eb',
    'Satisfactory':      '#7c3aed',
    'Unsatisfactory':    '#dc2626',
    'Poor':              '#6b7280',
}[label] ?? '#6b7280');

// ── Bar chart ─────────────────────────────────────────────────────────────
const chartData = computed(() => {
    const rows = rankedOnly.value;
    return {
        names:     rows.map(r => r.province),
        scores:    rows.map(r => getScore(r)),
        directors: rows.map(r => r.director || '—'),
        labels:    rows.map(r => r.adjective_label),
    };
});

const chartOptions = computed(() => {
    const data = chartData.value;
    const max  = Math.max(...data.scores, 0);
    const min  = data.scores.length ? Math.min(...data.scores) : 0;
    const computedMax = Math.max(10, Math.ceil((max * 1.15) / 5) * 5);
    // Start the axis just below the lowest bar (floored to a 5% step) so the
    // spread between provinces is visible instead of squashed against 0%.
    const computedMin = Math.max(0, Math.floor((min * 0.95) / 5) * 5);
    return {
        chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit', animations: { enabled: true, speed: 700 } },
        plotOptions: { bar: { horizontal: true, barHeight: '68%', borderRadius: 3, distributed: true, dataLabels: { position: 'center' } } },
        colors: data.labels.map(bandColor),
        legend: { show: false },
        grid:   { borderColor: '#f1f5f9', xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } }, padding: { left: 0, right: 12, top: -8, bottom: 0 } },
        dataLabels: { enabled: true, formatter: v => `${v.toFixed(1)}%`, style: { fontSize: '10px', fontFamily: 'inherit', fontWeight: '600', colors: ['#fff'] } },
        xaxis: { categories: data.names, min: computedMin, max: computedMax, labels: { formatter: v => `${v}%`, style: { fontSize: '10px', fontFamily: 'inherit', colors: '#94a3b8' } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { fontSize: '10.5px', fontFamily: 'inherit', colors: '#475569' }, maxWidth: 130 } },
        tooltip: {
            theme: 'light',
            custom: ({ dataPointIndex: i }) => {
                const score = (data.scores[i] ?? 0).toFixed(2);
                const label = selectedCategory.value === 'overall' ? 'weighted score' : selectedCategory.value;
                const color = bandColor(data.labels[i]);
                return `<div style="padding:8px 12px;font-size:12px;font-family:inherit;min-width:200px;">
                    <div style="font-weight:700;margin-bottom:4px;">${data.names[i]}</div>
                    <div style="color:#64748b;margin-bottom:6px;">Director: ${data.directors[i]}</div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="width:10px;height:10px;border-radius:50%;background:${color};flex-shrink:0;"></span>
                        <strong style="color:${color};">${score}% ${label}</strong>
                    </div>
                </div>`;
            },
        },
    };
});

const chartSeries = computed(() => [{ name: 'Score', data: chartData.value.scores }]);
const chartHeight = computed(() => Math.max(320, rankedOnly.value.length * 28 + 40));
</script>

<style scoped>
.province-link, .director-link { text-decoration: none !important; color: inherit; transition: color 0.15s; }
.province-link:hover { color: #4f46e5 !important; text-decoration: underline !important; }
.director-link:hover { color: #4f46e5 !important; text-decoration: underline !important; }

.leaderboard-table :deep(tr) { cursor: default; }
.leaderboard-table :deep(thead th) { font-size: 13.5px !important; font-weight: 700 !important; }
.leaderboard-table :deep(tbody td) { font-size: 14px !important; }
.leaderboard-table :deep(table)    { width: 100% !important; }

.rank-medal  { display: inline-block; font-size: 13px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
.rank-gold   { background: #fef9c3; color: #854d0e; }
.rank-silver { background: #f1f5f9; color: #475569; }
.rank-bronze { background: #ffedd5; color: #9a3412; }

.cat-cell { display: inline-block; padding: 2px 8px; border-radius: 4px; transition: background 0.15s; }
.cat-cell--active { background: rgba(99, 102, 241, 0.10); font-weight: 700; }

.region-banner { border-left: 4px solid #6366f1 !important; }

.podium-banner { display: flex; align-items: center; background: linear-gradient(90deg, #fef9c3 0%, #fef3c7 60%, #fff 100%); border-left: 3px solid #ca8a04; border-radius: 0 6px 6px 0; padding: 8px 14px; box-shadow: 0 1px 2px rgba(202,138,4,0.08); }
.podium-stage        { width: 44%; flex-shrink: 0; display: flex; flex-direction: column; align-self: flex-start; }
.podium-items        { display: flex; align-items: flex-end; gap: 8px; }
.podium-item         { flex: 1; display: flex; flex-direction: column; align-items: center; }
.podium-info         { text-align: center; padding-bottom: 10px; }
.podium-region-name  { font-size: 17px; font-weight: 700; line-height: 1.3; max-width: 160px; word-wrap: break-word; }
.podium-director     { font-size: 12px; color: #94a3b8; margin-top: 1px; line-height: 1.3; max-width: 160px; word-wrap: break-word; }
.podium-score        { font-size: 26px; font-weight: 800; line-height: 1; margin-top: 4px; }
.podium-block        { width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; border-radius: 8px 8px 0 0; color: white; }
.podium-rank-num     { font-size: 22px; font-weight: 800; color: white; }
.podium-gold         { height: 210px; background: linear-gradient(160deg, #ca8a04, #fde047); }
.podium-silver       { height: 160px; background: linear-gradient(160deg, #64748b, #cbd5e1); }
.podium-bronze       { height: 120px; background: linear-gradient(160deg, #78350f, #c2410c); }
.podium-rest-scroll  { overflow-y: auto; max-height: 460px; }
.podium-rest-row     { display: flex; align-items: center; gap: 10px; padding: 7px 0; border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); }
.podium-rest-rank    { width: 36px; text-align: right; flex-shrink: 0; font-size: 14px; color: #64748b; font-weight: 700; }
.podium-rest-score   { font-size: 14px; font-weight: 700; min-width: 60px; text-align: right; }
.podium-breakdown    { display: flex; gap: 20px; margin-top: 18px; padding-top: 16px; border-top: 1px solid rgba(0,0,0,0.06); }
.breakdown-col       { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 5px; }
.breakdown-row       { display: flex; align-items: center; gap: 8px; }
.breakdown-label     { font-size: 13px; font-weight: 600; color: #475569; width: 120px; flex-shrink: 0; }
.breakdown-score     { font-size: 14px; font-weight: 700; width: 54px; text-align: right; flex-shrink: 0; margin-left: auto; }

.dashboard-sticky-shell { position: sticky; top: 64px; z-index: 4; padding-top: 12px; margin-top: -20px; background: #ffffff; }
.dashboard-sticky-filters { background: #ffffff; border: 1px solid rgba(15,23,42,0.12); border-top: 2px solid #4f46e5; border-radius: 8px; box-shadow: 0 8px 20px -6px rgba(15,23,42,0.18), 0 2px 4px rgba(15,23,42,0.06); }

.filter-select               { width: 160px; flex-shrink: 0; }
.filter-select--narrow       { width: 110px; }
.filter-select :deep(.v-field__input) { font-size: 14px; }
.filter-select :deep(.v-field__label) { font-size: 13px; }
.filter-divider { height: 32px; align-self: center; margin: 0 4px; opacity: 0.6; }

.text-caption { font-size: 13px !important; line-height: 1.45 !important; }
.text-body-2  { font-size: 15px !important; line-height: 1.45 !important; }
:deep(.v-chip--size-x-small) { font-size: 12.5px !important; }
:deep(.v-chip--size-small)   { font-size: 13.5px !important; }
:deep(.v-field__input) { font-size: 15px; }

@keyframes search-pulse {
    0%, 100% { background-color: rgba(245,158,11,0.45); }
    50%       { background-color: rgba(245,158,11,0.15); }
}
.search-highlight { background-color: rgba(99,102,241,0.16) !important; animation: search-pulse 0.55s ease-in-out 3; border-radius: 6px; }
.leaderboard-table :deep(tr.search-highlight) td { background-color: rgba(99,102,241,0.14) !important; }
.leaderboard-table :deep(tr.search-highlight) { animation: search-pulse 0.55s ease-in-out 3; }
</style>
