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
                        <div class="text-subtitle-1 font-weight-bold">Welcome, {{ authUser?.profile?.first_name ?? authUser?.username ?? 'Employee' }}</div>
                        <div class="text-caption text-medium-emphasis">
                            {{ authUser?.province?.name ? `${authUser.province.name} · ` : '' }}DOST Employee
                        </div>
                    </div>
                </div>
                <div class="d-flex align-center gap-2 flex-wrap">
                    <v-chip
                        v-if="myRankEntry"
                        size="small"
                        variant="tonal"
                        :color="tierChipColor(myRankEntry.score)"
                        prepend-icon="mdi-map-marker-outline"
                    >
                        {{ myRankEntry.province }} — Rank #{{ myRank }}
                    </v-chip>
                    <v-chip size="small" variant="tonal" color="indigo" prepend-icon="mdi-calendar">
                        {{ selectedYear }}
                    </v-chip>
                </div>
            </div>
        </v-card>

        <!-- Province Highlight Cards (only shown when user has a province) -->
        <v-row v-if="myRankEntry" dense>
            <v-col cols="12" sm="3">
                <v-card border elevation="0" rounded="lg" class="pa-4">
                    <div class="text-caption text-medium-emphasis font-weight-bold text-uppercase mb-1">Province Rank</div>
                    <div class="text-h4 font-weight-black" :style="{ color: tierColor(myRankEntry.score) }">#{{ myRank }}</div>
                    <div class="text-caption text-medium-emphasis mt-1">out of {{ rankedScores.length }} provinces</div>
                </v-card>
            </v-col>
            <v-col cols="12" sm="3">
                <v-card border elevation="0" rounded="lg" class="pa-4">
                    <div class="text-caption text-medium-emphasis font-weight-bold text-uppercase mb-1">KPI Score</div>
                    <div class="text-h4 font-weight-black" :style="{ color: tierColor(myRankEntry.score) }">{{ myRankEntry.score }}%</div>
                    <div class="text-caption text-medium-emphasis mt-1">{{ evaluationMeta[selectedEvaluation].label }} method</div>
                </v-card>
            </v-col>
            <v-col cols="12" sm="3">
                <v-card border elevation="0" rounded="lg" class="pa-4">
                    <div class="text-caption text-medium-emphasis font-weight-bold text-uppercase mb-1">Indicators Met</div>
                    <div class="text-h4 font-weight-black text-success">{{ myRankEntry.met }}</div>
                    <div class="text-caption text-medium-emphasis mt-1">{{ myRankEntry.exceeded }} exceeded · {{ myRankEntry.active }} active</div>
                </v-card>
            </v-col>
            <v-col cols="12" sm="3">
                <v-card border elevation="0" rounded="lg" class="pa-4">
                    <div class="text-caption text-medium-emphasis font-weight-bold text-uppercase mb-1">Performance Tier</div>
                    <div class="text-h6 font-weight-black mt-2" :style="{ color: tierColor(myRankEntry.score) }">
                        {{ tierLabel(myRankEntry.score) }}
                    </div>
                    <div class="text-caption text-medium-emphasis mt-1">{{ myRankEntry.province }}</div>
                </v-card>
            </v-col>
        </v-row>

        <!-- Province Performance Leaderboard -->
        <v-row dense>
            <v-col cols="12">
                <v-card border elevation="0" rounded="lg">

                    <!-- Header -->
                    <div class="px-4 pt-3 pb-0">
                        <div class="d-flex align-center justify-space-between flex-wrap gap-3 mb-2">
                            <div class="d-flex align-center gap-2">
                                <v-icon size="15" color="indigo">mdi-trophy-outline</v-icon>
                                <span class="text-body-2 font-weight-bold">Province Performance Leaderboard</span>
                                <v-chip v-if="myRankEntry" size="x-small" variant="tonal" color="indigo" prepend-icon="mdi-map-marker">
                                    Your Province: #{{ myRank }}
                                </v-chip>
                            </div>
                            <div class="d-flex align-center gap-3 flex-wrap">
                                <div class="d-flex align-center gap-2">
                                    <span class="text-caption font-weight-medium text-medium-emphasis">Method</span>
                                    <v-btn-toggle v-model="selectedEvaluation" mandatory density="compact" variant="outlined" divided>
                                        <v-btn value="strict"      size="small" class="px-2 text-caption">Strict</v-btn>
                                        <v-btn value="operational" size="small" class="px-2 text-caption">Operational</v-btn>
                                        <v-btn value="absolute"    size="small" class="px-2 text-caption">Absolute</v-btn>
                                        <v-btn value="excellence"  size="small" class="px-2 text-caption">Excellence</v-btn>
                                    </v-btn-toggle>
                                </div>
                                <v-divider vertical style="height:24px;" />
                                <div class="d-flex align-center gap-2">
                                    <span class="text-caption font-weight-medium text-medium-emphasis">Year</span>
                                    <v-btn-toggle v-model="selectedYear" mandatory density="compact" variant="outlined" divided>
                                        <v-btn v-for="y in available_years" :key="y" :value="y" size="small" class="px-3 text-caption">{{ y }}</v-btn>
                                    </v-btn-toggle>
                                </div>
                            </div>
                        </div>
                    </div>

                    <v-divider />

                    <!-- Category Tabs -->
                    <v-tabs v-model="selectedCategory" density="compact" color="indigo" class="px-2" @update:modelValue="raceSearch = ''">
                        <v-tab value="all" class="text-caption">
                            All
                            <v-chip size="x-small" variant="tonal" class="ml-1">{{ categoryCounts.all }}</v-chip>
                        </v-tab>
                        <v-tab v-for="cat in categories" :key="cat.value" :value="cat.value" class="text-caption">
                            {{ cat.label }}
                            <v-chip size="x-small" :color="cat.color" variant="tonal" class="ml-1">{{ categoryCounts[cat.value] ?? 0 }}</v-chip>
                        </v-tab>
                    </v-tabs>

                    <!-- Legend + Search -->
                    <div class="d-flex align-center gap-3 px-4 py-2 flex-wrap">
                        <div class="d-flex align-center gap-3">
                            <div class="d-flex align-center gap-1">
                                <span class="legend-dot" style="background:#15803d;"></span>
                                <span class="text-caption text-medium-emphasis">Top (≥70%)</span>
                            </div>
                            <div class="d-flex align-center gap-1">
                                <span class="legend-dot" style="background:#ca8a04;"></span>
                                <span class="text-caption text-medium-emphasis">Avg (40–69%)</span>
                            </div>
                            <div class="d-flex align-center gap-1">
                                <span class="legend-dot" style="background:#b91c1c;"></span>
                                <span class="text-caption text-medium-emphasis">Low (&lt;40%)</span>
                            </div>
                        </div>
                        <div class="ml-auto">
                            <v-text-field
                                v-model="raceSearch"
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
                        :headers="raceHeaders"
                        :items="rankedScores"
                        :search="raceSearch"
                        density="compact"
                        fixed-header
                        height="460"
                        hide-default-footer
                        :items-per-page="-1"
                        class="leaderboard-table"
                    >
                        <template #item.rank="{ item }">
                            <span v-if="item.rank === 1" class="rank-medal rank-gold">1st</span>
                            <span v-else-if="item.rank === 2" class="rank-medal rank-silver">2nd</span>
                            <span v-else-if="item.rank === 3" class="rank-medal rank-bronze">3rd</span>
                            <span v-else class="text-caption text-medium-emphasis">#{{ item.rank }}</span>
                        </template>

                        <template #item.province="{ item }">
                            <div class="d-flex align-center gap-2">
                                <span class="text-body-2 font-weight-medium">{{ item.province }}</span>
                                <v-chip
                                    v-if="my_province && item.province === my_province"
                                    size="x-small"
                                    color="indigo"
                                    variant="tonal"
                                >You</v-chip>
                            </div>
                        </template>

                        <template #item.director="{ item }">
                            <span class="text-body-2 text-medium-emphasis">{{ item.director }}</span>
                        </template>

                        <template #item.category="{ item }">
                            <v-chip :color="categoryColor(item.category)" size="x-small" variant="tonal" class="font-weight-medium text-capitalize">
                                {{ item.category }}
                            </v-chip>
                        </template>

                        <template #item.score="{ item }">
                            <div class="d-flex align-center gap-2 py-1" style="min-width:200px;">
                                <div class="score-track">
                                    <div
                                        class="score-fill"
                                        :style="{ width: `${Math.min(item.score, 100)}%`, background: tierColor(item.score) }"
                                    />
                                </div>
                                <div class="d-flex flex-column" style="min-width:0;">
                                    <span class="text-caption font-weight-bold" :style="{ color: tierColor(item.score) }">
                                        {{ item.score }}%
                                    </span>
                                    <span class="text-medium-emphasis" style="font-size:10px; white-space:nowrap;">
                                        {{ item.met }} met · {{ item.exceeded }} exceeded · {{ item.active }}/{{ item.total }} active
                                    </span>
                                </div>
                            </div>
                        </template>

                        <template #no-data>
                            <div class="text-center py-8 text-medium-emphasis text-body-2">No provinces found</div>
                        </template>
                    </v-data-table>

                </v-card>
            </v-col>
        </v-row>

        <!-- Top 10 Bar Chart -->
        <v-row dense>
            <v-col cols="12" md="8">
                <v-card border elevation="0" rounded="lg" class="overflow-hidden">
                    <div class="chart-header px-4 pt-3 pb-2">
                        <div class="d-flex align-center gap-2">
                            <v-icon size="15" color="success">mdi-star-circle-outline</v-icon>
                            <span class="text-body-2 font-weight-bold">Top 10 Provinces</span>
                        </div>
                        <div class="text-caption text-medium-emphasis">Best performing · {{ selectedYear }}</div>
                    </div>
                    <VueApexCharts
                        type="bar"
                        height="340"
                        :options="top10Options"
                        :series="top10Series"
                        :key="`top10-${selectedYear}-${selectedCategory}-${selectedEvaluation}`"
                    />
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card border elevation="0" rounded="lg" class="overflow-hidden">
                    <div class="chart-header px-4 pt-3 pb-2">
                        <div class="d-flex align-center gap-2">
                            <v-icon size="15" color="primary">mdi-chart-donut</v-icon>
                            <span class="text-body-2 font-weight-bold">Performance Distribution</span>
                        </div>
                        <div class="text-caption text-medium-emphasis">All provinces · {{ selectedYear }}</div>
                    </div>
                    <VueApexCharts
                        type="donut"
                        height="340"
                        :options="pieOptions"
                        :series="pieSeries"
                        :key="`pie-${selectedYear}-${selectedCategory}-${selectedEvaluation}`"
                    />
                </v-card>
            </v-col>
        </v-row>

    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import QuantityCard from '@/Components/Cards/QuantityCard.vue';

const props = defineProps({
    my_province:        { type: String,  default: null },
    kpi_scores_by_year: { type: Object,  default: () => ({}) },
    kpi_outcomes:       { type: Array,   default: () => [] },
    available_years:    { type: Array,   default: () => [] },
});

const page     = usePage();
const authUser = computed(() => page.props.auth?.user ?? null);

const selectedYear       = ref(props.available_years[0] ?? new Date().getFullYear());
const selectedCategory   = ref('all');
const selectedEvaluation = ref('operational');
const raceSearch         = ref('');

const SCORE_FIELD = {
    strict:      'strict_score',
    operational: 'operational_score',
    absolute:    'absolute_score',
    excellence:  'excellence_score',
};

const evaluationMeta = {
    strict:      { label: 'Strict'      },
    operational: { label: 'Operational' },
    absolute:    { label: 'Absolute'    },
    excellence:  { label: 'Excellence'  },
};

const categories = [
    { value: 'micro',  label: 'Micro',  color: 'blue-grey'   },
    { value: 'small',  label: 'Small',  color: 'teal'        },
    { value: 'medium', label: 'Medium', color: 'indigo'      },
    { value: 'large',  label: 'Large',  color: 'deep-purple' },
];

const currentScores = computed(() => {
    const yearData = props.kpi_scores_by_year[selectedYear.value];
    if (!yearData) return [];
    const raw   = yearData.overall ?? [];
    const field = SCORE_FIELD[selectedEvaluation.value];
    return raw
        .map(r => ({ ...r, score: r[field] ?? 0 }))
        .sort((a, b) => b.score - a.score);
});

const categoryCounts = computed(() => {
    const counts = { all: currentScores.value.length };
    for (const s of currentScores.value) {
        if (s.category) counts[s.category] = (counts[s.category] ?? 0) + 1;
    }
    return counts;
});

const filteredScores = computed(() =>
    selectedCategory.value === 'all'
        ? currentScores.value
        : currentScores.value.filter(s => s.category === selectedCategory.value)
);

const rankedScores = computed(() =>
    filteredScores.value.map((s, i) => ({ ...s, rank: i + 1 }))
);

const myRankEntry = computed(() =>
    props.my_province
        ? rankedScores.value.find(s => s.province === props.my_province) ?? null
        : null
);

const myRank = computed(() => myRankEntry.value?.rank ?? null);

const raceHeaders = computed(() => [
    { title: 'Rank',     key: 'rank',     width: '64px',  sortable: false },
    { title: 'Province', key: 'province', sortable: true  },
    { title: 'Director', key: 'director', sortable: false },
    { title: 'Category', key: 'category', width: '100px', sortable: true  },
    { title: 'Active',   key: 'active',   width: '70px',  align: 'center', sortable: true },
    { title: 'Met',      key: 'met',      width: '60px',  align: 'center', sortable: true },
    { title: 'Exceeded', key: 'exceeded', width: '80px',  align: 'center', sortable: true },
    { title: `${evaluationMeta[selectedEvaluation.value].label} Score`, key: 'score', width: '220px', sortable: true },
]);

const tierColor = score =>
    score >= 70 ? '#15803d' :
    score >= 40 ? '#ca8a04' : '#b91c1c';

const tierLabel = score =>
    score >= 70 ? 'Top Performer'    :
    score >= 40 ? 'Average Performer' : 'Low Performer';

const tierChipColor = score =>
    score >= 70 ? 'success' :
    score >= 40 ? 'warning' : 'error';

const categoryColor = cat => ({
    micro: 'blue-grey', small: 'teal', medium: 'indigo', large: 'deep-purple',
}[cat] ?? 'grey');

// Top 10 chart
const top10Data = computed(() => {
    const slice = filteredScores.value.slice(0, 10);
    return {
        names:     slice.map(s => s.province),
        scores:    slice.map(s => s.score),
        directors: slice.map(s => s.director),
        counts:    slice.map(s => s.active ?? 0),
    };
});

const tooltipHtml = (data, i) => {
    const score = data.scores[i]    ?? 0;
    const dir   = data.directors[i] ?? '—';
    const name  = data.names[i]     ?? '';
    const cnt   = data.counts[i]    ?? 0;
    const color = tierColor(score);
    return `<div style="padding:8px 12px;font-size:12px;font-family:inherit;min-width:180px;">
                <div style="font-weight:700;margin-bottom:4px;">${name}</div>
                <div style="color:#64748b;margin-bottom:2px;">Director: ${dir}</div>
                <div style="color:#64748b;margin-bottom:6px;">${cnt} indicators tracked</div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:${color};flex-shrink:0;"></span>
                    <strong style="color:${color};">${score}% accomplishment</strong>
                </div>
            </div>`;
};

const top10Options = computed(() => {
    const data     = top10Data.value;
    const maxScore = Math.max(...data.scores, 0);
    const maxX     = Math.ceil((maxScore * 1.12) / 10) * 10;
    return {
        chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit', animations: { enabled: true, speed: 700 } },
        plotOptions: { bar: { horizontal: true, barHeight: '68%', borderRadius: 3, distributed: true } },
        colors: data.scores.map(tierColor),
        legend: { show: false },
        grid: {
            borderColor: '#f1f5f9',
            xaxis: { lines: { show: true } },
            yaxis: { lines: { show: false } },
            padding: { left: 0, right: 12, top: -8, bottom: 0 },
        },
        dataLabels: {
            enabled: true,
            formatter: v => `${Math.round(v)}%`,
            style: { fontSize: '10px', fontFamily: 'inherit', fontWeight: '600', colors: ['#fff'] },
            dropShadow: { enabled: false },
        },
        xaxis: {
            categories: data.names,
            min: 0,
            max: maxX,
            labels: { formatter: v => `${v}%`, style: { fontSize: '10px', fontFamily: 'inherit', colors: '#94a3b8' } },
            axisBorder: { show: false },
            axisTicks:  { show: false },
        },
        yaxis: { labels: { style: { fontSize: '10.5px', fontFamily: 'inherit', colors: '#475569' }, maxWidth: 115 } },
        tooltip: { theme: 'light', custom: ({ dataPointIndex }) => tooltipHtml(data, dataPointIndex) },
    };
});

const top10Series = computed(() => [{ name: 'KPI Score', data: top10Data.value.scores }]);

// Donut chart — tier distribution
const pieSeries = computed(() => {
    const scores = filteredScores.value.map(s => s.score);
    return [
        scores.filter(s => s >= 70).length,
        scores.filter(s => s >= 40 && s < 70).length,
        scores.filter(s => s < 40).length,
    ];
});

const pieOptions = computed(() => ({
    chart: { type: 'donut', fontFamily: 'inherit', toolbar: { show: false } },
    labels: ['Top (≥70%)', 'Average (40–69%)', 'Low (<40%)'],
    colors: ['#15803d', '#ca8a04', '#b91c1c'],
    legend: { position: 'bottom', fontSize: '11px', fontFamily: 'inherit' },
    dataLabels: { style: { fontSize: '11px', fontFamily: 'inherit', fontWeight: '600' } },
    plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Provinces', fontSize: '11px', fontWeight: '600', color: '#475569' } } } } },
    tooltip: { theme: 'light' },
}));
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
.leaderboard-table :deep(thead th) { font-size: 11px !important; font-weight: 600 !important; }

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
    flex: 1;
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
