<template>
    <Head title="Dashboard" />
    <div class="d-flex flex-column gap-3">

        <!-- Welcome Banner -->
        <v-card border elevation="0" rounded="lg" class="overflow-hidden">
            <div class="employee-banner px-5 py-4 d-flex align-center justify-space-between flex-wrap gap-3">
                <div class="d-flex align-center gap-3">
                    <v-avatar color="indigo-lighten-4" size="48" rounded="lg">
                        <v-icon color="indigo" size="26">mdi-account-circle-outline</v-icon>
                    </v-avatar>
                    <div>
                        <div class="text-subtitle-1 font-weight-bold">
                            Welcome, {{ authUser?.profile?.first_name ?? authUser?.username ?? 'Employee' }}
                        </div>
                        <div class="text-caption text-medium-emphasis">
                            {{ [props.my_province, props.my_region].filter(Boolean).join(' · ') || 'DOST Employee' }}
                        </div>
                    </div>
                </div>
                <div class="d-flex align-center gap-2 flex-wrap">
                    <v-chip
                        v-if="myEntry"
                        size="small"
                        variant="tonal"
                        :color="bucketChipColor(myEntry.bucket)"
                        prepend-icon="mdi-map-marker-outline"
                    >
                        {{ myEntry.province }} - Rank #{{ myGlobalRank }}
                    </v-chip>
                    <v-chip size="small" variant="tonal" color="indigo" prepend-icon="mdi-calendar">
                        {{ selectedYear }}
                    </v-chip>
                </div>
            </div>
        </v-card>

        <!-- Province Highlight Cards -->
        <v-row v-if="myEntry" dense>
            <v-col cols="12" sm="3">
                <v-card border elevation="0" rounded="lg" class="pa-4">
                    <div class="text-caption text-medium-emphasis font-weight-bold text-uppercase mb-1">Province Rank</div>
                    <div class="text-h4 font-weight-black" :style="{ color: bucketHex(myEntry.bucket) }">
                        #{{ myGlobalRank }}
                    </div>
                    <div class="text-caption text-medium-emphasis mt-1">out of {{ totalRanked }} provinces</div>
                </v-card>
            </v-col>
            <v-col cols="12" sm="3">
                <v-card border elevation="0" rounded="lg" class="pa-4">
                    <div class="text-caption text-medium-emphasis font-weight-bold text-uppercase mb-1">KPI Score</div>
                    <div class="text-h4 font-weight-black" :style="{ color: bucketHex(myEntry.bucket) }">
                        {{ myEntry.total_pct.toFixed(1) }}%
                    </div>
                    <div class="text-caption text-medium-emphasis mt-1">Weighted overall score</div>
                </v-card>
            </v-col>
            <v-col cols="12" sm="3">
                <v-card border elevation="0" rounded="lg" class="pa-4">
                    <div class="text-caption text-medium-emphasis font-weight-bold text-uppercase mb-1">Performer Level</div>
                    <div class="text-h6 font-weight-black mt-2" :style="{ color: bucketHex(myEntry.bucket) }">
                        {{ myEntry.bucket ?? '—' }}
                    </div>
                    <div class="text-caption text-medium-emphasis mt-1">{{ myEntry.province }}</div>
                </v-card>
            </v-col>
            <v-col cols="12" sm="3">
                <v-card border elevation="0" rounded="lg" class="pa-4">
                    <div class="text-caption text-medium-emphasis font-weight-bold text-uppercase mb-1">Province Tier</div>
                    <div class="mt-2">
                        <v-chip :color="tierColor(myEntry.category)" variant="tonal" class="text-uppercase font-weight-bold">
                            {{ myEntry.category }}
                        </v-chip>
                    </div>
                    <div class="text-caption text-medium-emphasis mt-2">
                        Rank #{{ myEntry.rank }} in tier
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- Province Performance Leaderboard -->
        <v-card border elevation="0" rounded="lg">

            <!-- Header -->
            <div class="px-4 pt-3 pb-0">
                <div class="d-flex align-center justify-space-between flex-wrap gap-3 mb-2">
                    <div class="d-flex align-center gap-2 flex-wrap">
                        <v-icon size="15" color="indigo">mdi-trophy-outline</v-icon>
                        <span class="text-body-2 font-weight-bold">Province Performance Leaderboard</span>
                        <v-chip v-if="props.my_region" size="x-small" variant="tonal" color="blue-grey" prepend-icon="mdi-map-marker-radius-outline">
                            {{ props.my_region }}
                        </v-chip>
                        <v-chip v-if="myEntry" size="x-small" variant="tonal" color="indigo" prepend-icon="mdi-map-marker">
                            Your Province: #{{ myGlobalRank }}
                        </v-chip>
                    </div>
                    <div class="d-flex align-center gap-2 flex-wrap">
                        <v-select
                            v-model="selectedTier"
                            :items="tierSelectItems"
                            item-title="label"
                            item-value="value"
                            label="Tier"
                            density="compact"
                            variant="outlined"
                            hide-details
                            style="width:170px;"
                        />
                        <v-select
                            v-model="selectedYear"
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

            <!-- Legend + Search -->
            <div class="d-flex align-center gap-3 px-4 py-2 flex-wrap">
                <div class="d-flex align-center gap-3">
                    <div class="d-flex align-center gap-1">
                        <span class="legend-dot" style="background:#15803d;"></span>
                        <span class="text-caption text-medium-emphasis">Top</span>
                    </div>
                    <div class="d-flex align-center gap-1">
                        <span class="legend-dot" style="background:#ca8a04;"></span>
                        <span class="text-caption text-medium-emphasis">Average</span>
                    </div>
                    <div class="d-flex align-center gap-1">
                        <span class="legend-dot" style="background:#b91c1c;"></span>
                        <span class="text-caption text-medium-emphasis">Low</span>
                    </div>
                    <div class="d-flex align-center gap-1">
                        <span class="legend-dot" style="background:#94a3b8;"></span>
                        <span class="text-caption text-medium-emphasis">Pending</span>
                    </div>
                </div>
                <div class="ml-auto">
                    <v-text-field
                        v-model="searchTerm"
                        placeholder="Search province or director…"
                        variant="solo-filled"
                        density="compact"
                        hide-details
                        clearable
                        prepend-inner-icon="mdi-magnify"
                        style="max-width:260px;"
                    />
                </div>
            </div>

            <v-divider />

            <!-- Leaderboard Table -->
            <v-data-table
                :headers="tableHeaders"
                :items="rankedScores"
                :search="searchTerm"
                density="compact"
                fixed-header
                height="460"
                hide-default-footer
                :items-per-page="-1"
                :row-props="rowProps"
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
                        <v-chip
                            v-if="props.my_province && item.province === props.my_province"
                            size="x-small"
                            color="indigo"
                            variant="tonal"
                        >You</v-chip>
                    </div>
                    <div v-if="item.region" class="text-caption text-disabled" style="line-height:1.2;">{{ item.region }}</div>
                </template>

                <template #item.director="{ item }">
                    <span class="text-body-2 text-medium-emphasis">{{ item.director || 'Vacant' }}</span>
                </template>

                <template #item.bucket="{ item }">
                    <v-chip v-if="item.status === 'no_director'" color="blue-grey" size="x-small" variant="tonal">No director</v-chip>
                    <v-chip v-else-if="item.status === 'no_data'"    color="blue-grey" size="x-small" variant="tonal">No data</v-chip>
                    <v-chip v-else-if="item.bucket" :color="bucketHex(item.bucket)" size="x-small" variant="tonal" class="font-weight-medium">
                        {{ item.bucket }}
                    </v-chip>
                    <span v-else class="text-caption text-disabled">—</span>
                </template>

                <template #item.category="{ item }">
                    <v-chip :color="tierColor(item.category)" size="x-small" variant="tonal" class="font-weight-medium text-uppercase">
                        {{ item.category }}
                    </v-chip>
                </template>

                <template #item.core="{ item }">
                    <span v-if="item.status === 'ranked'" class="text-caption">
                        {{ item.subtotals_pct?.CORE?.toFixed(1) ?? '—' }}%
                    </span>
                    <span v-else class="text-caption text-disabled">—</span>
                </template>

                <template #item.functional="{ item }">
                    <span v-if="item.status === 'ranked'" class="text-caption">
                        {{ item.subtotals_pct?.FUNCTIONAL?.toFixed(1) ?? '—' }}%
                    </span>
                    <span v-else class="text-caption text-disabled">—</span>
                </template>

                <template #item.support="{ item }">
                    <span v-if="item.status === 'ranked'" class="text-caption">
                        {{ item.subtotals_pct?.SUPPORT?.toFixed(1) ?? '—' }}%
                    </span>
                    <span v-else class="text-caption text-disabled">—</span>
                </template>

                <template #item.total_pct="{ item }">
                    <div v-if="item.status === 'ranked'" class="d-flex align-center gap-2 py-1" style="min-width:180px;">
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
                    <div class="text-center py-8 text-medium-emphasis text-body-2">No provinces found</div>
                </template>
            </v-data-table>

        </v-card>

        <!-- Top Performers Chart + Distribution Donut -->
        <v-row dense>
            <v-col cols="12" md="8">
                <v-card border elevation="0" rounded="lg" class="overflow-hidden">
                    <div class="chart-header px-4 pt-3 pb-2">
                        <div class="d-flex align-center gap-2">
                            <v-icon size="15" color="success">mdi-star-circle-outline</v-icon>
                            <span class="text-body-2 font-weight-bold">Top Provinces</span>
                        </div>
                        <div class="text-caption text-medium-emphasis">
                            {{ props.my_region ?? 'Region' }} · {{ selectedYear }}
                        </div>
                    </div>
                    <VueApexCharts
                        v-if="top10Data.names.length"
                        type="bar"
                        height="340"
                        :options="top10Options"
                        :series="top10Series"
                        :key="`top10-${selectedYear}-${selectedTier}`"
                    />
                    <div v-else class="d-flex align-center justify-center" style="height:340px;">
                        <div class="text-center text-medium-emphasis">
                            <v-icon size="40" color="blue-grey" class="mb-2">mdi-chart-bar</v-icon>
                            <div class="text-body-2">No ranking data for {{ selectedYear }}</div>
                        </div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card border elevation="0" rounded="lg" class="overflow-hidden">
                    <div class="chart-header px-4 pt-3 pb-2">
                        <div class="d-flex align-center gap-2">
                            <v-icon size="15" color="primary">mdi-chart-donut</v-icon>
                            <span class="text-body-2 font-weight-bold">Performance Distribution</span>
                        </div>
                        <div class="text-caption text-medium-emphasis">
                            {{ props.my_region ?? 'Region' }} · {{ selectedYear }}
                        </div>
                    </div>
                    <VueApexCharts
                        v-if="pieSeries.some(v => v > 0)"
                        type="donut"
                        height="340"
                        :options="pieOptions"
                        :series="pieSeries"
                        :key="`pie-${selectedYear}-${selectedTier}`"
                    />
                    <div v-else class="d-flex align-center justify-center" style="height:340px;">
                        <div class="text-center text-medium-emphasis">
                            <v-icon size="40" color="blue-grey" class="mb-2">mdi-chart-donut</v-icon>
                            <div class="text-body-2">No data yet</div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    my_province:      { type: String, default: null },
    my_region:        { type: String, default: null },
    rankings_by_year: { type: Object, default: () => ({}) },
    kpi_categories:   { type: Array,  default: () => [] },
    available_years:  { type: Array,  default: () => [] },
});

const page     = usePage();
const authUser = computed(() => page.props.auth?.user ?? null);

const selectedYear = ref(props.available_years[0] ?? new Date().getFullYear());
const selectedTier = ref('all');
const searchTerm   = ref('');

const tiers = [
    { value: 'micro',  label: 'Micro'  },
    { value: 'small',  label: 'Small'  },
    { value: 'medium', label: 'Medium' },
    { value: 'large',  label: 'Large'  },
];

// ── Data ─────────────────────────────────────────────────────────────────────

// All rows for the selected year, flattened from all tiers
const allRows = computed(() => {
    const yearData = props.rankings_by_year[selectedYear.value] ?? {};
    return Object.values(yearData).flat();
});

// Ranked rows only (ranked + sorted by total_pct) with cross-tier rank assigned
const allRankedGlobal = computed(() =>
    allRows.value
        .filter(r => r.status === 'ranked')
        .slice()
        .sort((a, b) => b.total_pct - a.total_pct)
        .map((r, i) => ({ ...r, global_rank: i + 1 }))
);

const totalRanked = computed(() => allRankedGlobal.value.length);

// Rows shown in the table: tier-filtered ranked rows + unranked appended at bottom
const rankedScores = computed(() => {
    let rows = allRows.value;
    if (selectedTier.value !== 'all') rows = rows.filter(r => r.category === selectedTier.value);

    const ranked   = rows.filter(r => r.status === 'ranked')
        .slice().sort((a, b) => b.total_pct - a.total_pct)
        .map((r, i) => ({ ...r, rank: i + 1 }));
    const unranked = rows.filter(r => r.status !== 'ranked')
        .slice().sort((a, b) => a.province.localeCompare(b.province))
        .map(r => ({ ...r, rank: null }));
    return [...ranked, ...unranked];
});

// Employee's own province entry (from global cross-tier ranking)
const myEntry = computed(() =>
    props.my_province
        ? allRankedGlobal.value.find(r => r.province === props.my_province) ?? null
        : null
);

const myGlobalRank = computed(() => myEntry.value?.global_rank ?? null);

// Tier counts for the filter dropdown labels
const tierCounts = computed(() => {
    const all = allRows.value;
    const counts = { all: all.length };
    for (const r of all) counts[r.category] = (counts[r.category] ?? 0) + 1;
    return counts;
});

const tierSelectItems = computed(() => [
    { value: 'all', label: `All Tiers (${tierCounts.value.all ?? 0})` },
    ...tiers.map(t => ({ value: t.value, label: `${t.label} (${tierCounts.value[t.value] ?? 0})` })),
]);

// ── Helpers ───────────────────────────────────────────────────────────────────

const bucketHex      = (b) => ({ Top: '#15803d', Average: '#ca8a04', Low: '#b91c1c' }[b] ?? '#94a3b8');
const bucketChipColor = (b) => ({ Top: 'success', Average: 'warning', Low: 'error' }[b] ?? 'grey');
const tierColor      = (c) => ({ micro: 'blue-grey', small: 'teal', medium: 'indigo', large: 'deep-purple' }[c] ?? 'grey');

// ── Table ─────────────────────────────────────────────────────────────────────

const tableHeaders = [
    { title: 'Rank',     key: 'rank',      width: '64px',  sortable: false },
    { title: 'Performer', key: 'bucket',   width: '110px', sortable: false },
    { title: 'Province', key: 'province',  sortable: true  },
    { title: 'Director', key: 'director',  sortable: false },
    { title: 'Tier',     key: 'category',  width: '90px',  align: 'center', sortable: true  },
    { title: 'CORE 60%', key: 'core',      width: '90px',  align: 'center', sortable: false },
    { title: 'FUNC 30%', key: 'functional',width: '90px',  align: 'center', sortable: false },
    { title: 'SUPP 10%', key: 'support',   width: '90px',  align: 'center', sortable: false },
    { title: 'Total',    key: 'total_pct', width: '200px', sortable: true  },
];

// Highlight the employee's own province row
const rowProps = ({ item }) => ({
    class: props.my_province && item.province === props.my_province ? 'my-province-row' : '',
});

// ── Charts ────────────────────────────────────────────────────────────────────

const top10Data = computed(() => {
    const ranked = rankedScores.value.filter(r => r.status === 'ranked').slice(0, 10);
    return {
        names:    ranked.map(r => r.province),
        scores:   ranked.map(r => r.total_pct),
        directors:ranked.map(r => r.director || '—'),
        buckets:  ranked.map(r => r.bucket),
    };
});

const tooltipHtml = (data, i) => {
    const score = (data.scores[i] ?? 0).toFixed(2);
    const dir   = data.directors[i] ?? '—';
    const name  = data.names[i]     ?? '';
    const bk    = data.buckets[i]   ?? '';
    const color = bucketHex(bk);
    return `<div style="padding:8px 12px;font-size:12px;font-family:inherit;min-width:180px;">
                <div style="font-weight:700;margin-bottom:4px;">${name}</div>
                <div style="color:#64748b;margin-bottom:6px;">Director: ${dir}</div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:${color};flex-shrink:0;"></span>
                    <strong style="color:${color};">${score}% weighted score</strong>
                </div>
            </div>`;
};

const top10Options = computed(() => {
    const data  = top10Data.value;
    const maxX  = Math.max(10, Math.ceil((Math.max(...data.scores, 0) * 1.15) / 5) * 5);
    return {
        chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit',
                 animations: { enabled: true, speed: 700, animateGradually: { enabled: true, delay: 80 } } },
        plotOptions: { bar: { horizontal: true, barHeight: '68%', borderRadius: 3, distributed: true } },
        colors: data.buckets.map(bucketHex),
        legend: { show: false },
        grid: { borderColor: '#f1f5f9',
                xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } },
                padding: { left: 0, right: 12, top: -8, bottom: 0 } },
        dataLabels: { enabled: true, formatter: v => `${v.toFixed(1)}%`,
                      style: { fontSize: '10px', fontFamily: 'inherit', fontWeight: '600', colors: ['#fff'] } },
        xaxis: { categories: data.names, min: 0, max: maxX,
                 labels: { formatter: v => `${v}%`, style: { fontSize: '10px', fontFamily: 'inherit', colors: '#94a3b8' } },
                 axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { fontSize: '10.5px', fontFamily: 'inherit', colors: '#475569' }, maxWidth: 115 } },
        tooltip: { theme: 'light', custom: ({ dataPointIndex }) => tooltipHtml(top10Data.value, dataPointIndex) },
    };
});

const top10Series = computed(() => [{ name: 'Weighted Score', data: top10Data.value.scores }]);

// Donut: bucket distribution of ranked provinces
const pieSeries = computed(() => {
    const rows = rankedScores.value.filter(r => r.status === 'ranked');
    return [
        rows.filter(r => r.bucket === 'Top').length,
        rows.filter(r => r.bucket === 'Average').length,
        rows.filter(r => r.bucket === 'Low').length,
    ];
});

const pieOptions = {
    chart:   { type: 'donut', fontFamily: 'inherit', toolbar: { show: false } },
    labels:  ['Top Performer', 'Average Performer', 'Low Performer'],
    colors:  ['#15803d', '#ca8a04', '#b91c1c'],
    legend:  { position: 'bottom', fontSize: '11px', fontFamily: 'inherit' },
    dataLabels: { style: { fontSize: '11px', fontFamily: 'inherit', fontWeight: '600' } },
    plotOptions: { pie: { donut: { size: '65%', labels: { show: true,
        total: { show: true, label: 'Ranked', fontSize: '11px', fontWeight: '600', color: '#475569' } } } } },
    tooltip: { theme: 'light' },
};
</script>

<style scoped>
.employee-banner {
    background: linear-gradient(135deg, rgba(99,102,241,0.06) 0%, rgba(99,102,241,0.02) 100%);
    border-left: 4px solid #6366f1;
}

.chart-header {
    border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.legend-dot {
    display: inline-block;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    flex-shrink: 0;
}

.leaderboard-table :deep(tr) { cursor: default; }
.leaderboard-table :deep(thead th) { font-size: 11px !important; font-weight: 700 !important; }

/* Soft indigo highlight for the employee's own province */
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
