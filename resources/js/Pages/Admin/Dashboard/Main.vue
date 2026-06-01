<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <div class="d-flex flex-column gap-3">

            <!-- Stat Cards -->
            <v-row dense>
                <v-col cols="12" sm="3">
                    <QuantityCard title="Total Provinces"      :quantity="total_provinces"  :icon="RiBuildingLine"   color="indigo" />
                </v-col>
                <v-col cols="12" sm="3">
                    <QuantityCard title="Provincial Directors" :quantity="total_directors"  :icon="RiUserStarLine"   color="teal"   />
                </v-col>
                <v-col cols="12" sm="3">
                    <QuantityCard title="Total Employees"      :quantity="total_employees"  :icon="RiGroupLine"      color="indigo" />
                </v-col>
                <v-col cols="12" sm="3">
                    <QuantityCard title="Sub-Administrators"   :quantity="total_sub_admins" :icon="RiShieldUserLine" color="indigo" />
                </v-col>
            </v-row>

            <!-- Year Selector + Legend -->
            <div class="d-flex align-center flex-wrap gap-3">
                <div class="d-flex align-center gap-2">
                    <span class="text-caption font-weight-medium text-medium-emphasis">KPI Year</span>
                    <v-btn-toggle v-model="selectedYear" mandatory density="compact" variant="outlined" divided>
                        <v-btn v-for="y in available_years" :key="y" :value="y" size="small" class="px-3 text-caption">{{ y }}</v-btn>
                    </v-btn-toggle>
                </div>
                <div class="d-flex align-center gap-2 flex-wrap">
                    <span class="legend-dot" style="background:#15803d;"></span><span class="text-caption text-medium-emphasis">Top Performing (≥100%)</span>
                    <span class="legend-dot" style="background:#ca8a04;"></span><span class="text-caption text-medium-emphasis">Average Performers (70–99%)</span>
                    <span class="legend-dot" style="background:#b91c1c;"></span><span class="text-caption text-medium-emphasis">Low Performers (&lt;70%)</span>
                </div>
                <v-chip size="x-small" color="primary" variant="tonal" class="font-weight-medium ml-auto">
                    {{ filteredScores.length }} provinces
                    <template v-if="selectedCategory !== 'all'"> · {{ categoryLabel }}</template>
                </v-chip>
            </div>

            <!-- Leaderboard Table -->
            <v-row dense>
                <v-col cols="12">
                    <v-card border elevation="0" rounded="lg">

                        <!-- Header -->
                        <div class="chart-header px-4 pt-3 pb-0 d-flex align-center justify-space-between flex-wrap gap-2">
                            <div>
                                <div class="d-flex align-center gap-2">
                                    <v-icon size="15" color="indigo">mdi-trophy-outline</v-icon>
                                    <span class="text-body-2 font-weight-bold">Province Performance Race</span>
                                    <v-tooltip location="bottom" max-width="340">
                                        <template #activator="{ props: tip }">
                                            <v-chip v-bind="tip" size="x-small" variant="tonal" color="blue-grey" prepend-icon="mdi-scale-balance" class="cursor-pointer">
                                                RA 11914
                                            </v-chip>
                                        </template>
                                        <div class="pa-1">
                                            <div class="font-weight-bold mb-1">Republic Act 11914 (PSTO Act)</div>
                                            <div class="text-caption mb-2 opacity-80">
                                                Rankings are based on KPI accomplishment rates derived from the 7 functional mandates of Section 6 of RA 11914. Province classification (Micro/Small/Medium/Large) follows Section 7 of the same Act.
                                            </div>
                                            <div class="text-caption opacity-70">Score = average of (accomplished ÷ target × 100%) across all tracked indicators, capped at 200% per indicator to reward over-achievement.</div>
                                        </div>
                                    </v-tooltip>
                                </div>
                                <div class="text-caption text-medium-emphasis mb-2">
                                    Ranked by classification · {{ kpiFilterLabel }} · {{ selectedYear }}
                                </div>
                            </div>
                            <v-text-field
                                v-model="raceSearch"
                                placeholder="Search province or director…"
                                variant="solo-filled"
                                density="compact"
                                hide-details
                                clearable
                                prepend-inner-icon="mdi-magnify"
                                style="max-width:240px;"
                                class="mb-1"
                            />
                        </div>

                        <!-- Category Tabs -->
                        <v-tabs
                            v-model="selectedCategory"
                            density="compact"
                            color="indigo"
                            class="px-2"
                            @update:modelValue="raceSearch = ''"
                        >
                            <v-tab value="all" class="text-caption">
                                All
                                <v-chip size="x-small" variant="tonal" class="ml-1">{{ categoryCounts.all }}</v-chip>
                            </v-tab>
                            <v-tab v-for="cat in categories" :key="cat.value" :value="cat.value" class="text-caption">
                                {{ cat.label }}
                                <v-chip
                                    size="x-small"
                                    :color="cat.color"
                                    variant="tonal"
                                    class="ml-1"
                                >{{ categoryCounts[cat.value] ?? 0 }}</v-chip>
                            </v-tab>
                        </v-tabs>

                        <v-divider />

                        <!-- KPI Filter -->
                        <div class="d-flex align-center gap-2 px-4 py-2 flex-wrap kpi-filter-bar">
                            <span class="text-caption font-weight-medium text-medium-emphasis" style="white-space:nowrap;">View by KPI:</span>
                            <v-chip-group v-model="selectedKpi" mandatory selected-class="kpi-chip-active" @update:modelValue="raceSearch = ''">
                                <v-chip value="overall" size="small" variant="tonal" color="indigo" class="font-weight-medium">
                                    Overall
                                </v-chip>
                                <v-tooltip
                                    v-for="kpi in kpi_outcomes"
                                    :key="kpi.id"
                                    :text="kpi.title"
                                    location="bottom"
                                    max-width="260"
                                >
                                    <template #activator="{ props: tip }">
                                        <v-chip
                                            v-bind="tip"
                                            :value="kpi.id"
                                            size="small"
                                            variant="tonal"
                                            color="blue-grey"
                                            class="font-weight-medium"
                                        >
                                            KPI {{ kpi.id }}
                                        </v-chip>
                                    </template>
                                </v-tooltip>
                            </v-chip-group>
                        </div>

                        <v-divider />

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
                                <span class="text-body-2 font-weight-medium">{{ item.province }}</span>
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
                                <div class="d-flex align-center gap-2 py-1" style="min-width:220px;">
                                    <v-tooltip :text="`${item.score}% accomplishment rate`" location="top">
                                        <template #activator="{ props: tip }">
                                            <div v-bind="tip" class="score-track" style="cursor:default;">
                                                <div
                                                    class="score-fill"
                                                    :style="{
                                                        width: `${Math.min(item.score, 200) / 200 * 100}%`,
                                                        background: tierColor(item.score),
                                                    }"
                                                />
                                            </div>
                                        </template>
                                    </v-tooltip>
                                    <span class="text-caption font-weight-bold" :style="{ color: tierColor(item.score) }">
                                        {{ tierLabel(item.score) }}
                                    </span>
                                </div>
                            </template>

                            <template #no-data>
                                <div class="text-center py-8 text-medium-emphasis text-body-2">
                                    No provinces found
                                </div>
                            </template>
                        </v-data-table>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Top 10 + Failing side by side -->
            <v-row dense>

                <v-col cols="12" md="6">
                    <v-card border elevation="0" rounded="lg" class="overflow-hidden">
                        <div class="chart-header px-4 pt-3 pb-2">
                            <div class="d-flex align-center gap-2">
                                <v-icon size="15" color="success">mdi-star-circle-outline</v-icon>
                                <span class="text-body-2 font-weight-bold">Top 10 Provinces & Directors</span>
                            </div>
                            <div class="text-caption text-medium-emphasis">
                                Best performing · {{ categoryLabel }} · {{ selectedYear }}
                            </div>
                        </div>
                        <VueApexCharts
                            type="bar"
                            height="360"
                            :options="top10Options"
                            :series="top10Series"
                            :key="`top10-${selectedYear}-${selectedCategory}`"
                        />
                    </v-card>
                </v-col>

                <v-col cols="12" md="6">
                    <v-card border elevation="0" rounded="lg" class="overflow-hidden">
                        <div class="chart-header px-4 pt-3 pb-2">
                            <div class="d-flex align-center gap-2">
                                <v-icon size="15" color="error">mdi-alert-circle-outline</v-icon>
                                <span class="text-body-2 font-weight-bold">Low Performers</span>
                            </div>
                            <div class="text-caption text-medium-emphasis">
                                Below 70% · {{ categoryLabel }} · {{ selectedYear }}
                            </div>
                        </div>
                        <VueApexCharts
                            v-if="failingData.names.length"
                            type="bar"
                            height="360"
                            :options="failingOptions"
                            :series="failingSeries"
                            :key="`fail-${selectedYear}-${selectedCategory}`"
                        />
                        <div v-else class="d-flex flex-column align-center justify-center gap-2" style="height:360px;">
                            <v-icon size="48" color="success">mdi-check-decagram-outline</v-icon>
                            <div class="text-body-2 font-weight-medium">No Low Performers!</div>
                            <div class="text-caption text-medium-emphasis">All provinces are Average Performers or Top Performing</div>
                        </div>
                    </v-card>
                </v-col>

            </v-row>

        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import QuantityCard        from '@/Components/Cards/QuantityCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    RiGroupLine,
    RiUserStarLine,
    RiShieldUserLine,
    RiBuildingLine,
} from '@remixicon/vue';

const props = defineProps({
    total_users:         { type: Number, default: 0 },
    total_sub_admins:    { type: Number, default: 0 },
    total_directors:     { type: Number, default: 0 },
    total_employees:     { type: Number, default: 0 },
    total_provinces:     { type: Number, default: 0 },
    kpi_scores_by_year:  { type: Object, default: () => ({}) },
    kpi_outcomes:        { type: Array,  default: () => [] },
    available_years:     { type: Array,  default: () => [] },
});

const selectedYear     = ref(props.available_years[0] ?? 2025);
const selectedCategory = ref('all');
const selectedKpi      = ref('overall');
const raceSearch       = ref('');

const categories = [
    { value: 'micro',  label: 'Micro',  color: 'blue-grey'   },
    { value: 'small',  label: 'Small',  color: 'teal'        },
    { value: 'medium', label: 'Medium', color: 'indigo'      },
    { value: 'large',  label: 'Large',  color: 'deep-purple' },
    { value: 'cstc',   label: 'CSTC',   color: 'pink'        },
];

const categoryLabel = computed(() =>
    selectedCategory.value === 'all'
        ? 'All Categories'
        : (categories.find(c => c.value === selectedCategory.value)?.label ?? '')
);

const currentScores = computed(() => {
    const yearData = props.kpi_scores_by_year[selectedYear.value];
    if (!yearData) return [];
    if (selectedKpi.value === 'overall') return yearData.overall ?? [];
    return yearData.kpi?.[selectedKpi.value] ?? [];
});

const kpiFilterLabel = computed(() => {
    if (selectedKpi.value === 'overall') return 'Overall KPI Score';
    const found = props.kpi_outcomes.find(k => k.id === selectedKpi.value);
    return found ? `KPI ${found.id}: ${found.title}` : `KPI ${selectedKpi.value}`;
});

const categoryCounts = computed(() => {
    const counts = { all: currentScores.value.length };
    for (const s of currentScores.value) {
        if (s.category) counts[s.category] = (counts[s.category] ?? 0) + 1;
    }
    return counts;
});

// Scores filtered by selected category, preserving score-desc sort from PHP
const filteredScores = computed(() =>
    selectedCategory.value === 'all'
        ? currentScores.value
        : currentScores.value.filter(s => s.category === selectedCategory.value)
);

// Rank is 1-based within the filtered category
const rankedScores = computed(() =>
    filteredScores.value.map((s, i) => ({ ...s, rank: i + 1 }))
);

const raceHeaders = [
    { title: 'Rank',      key: 'rank',     width: '64px',  sortable: false },
    { title: 'Province',  key: 'province', sortable: true  },
    { title: 'Director',  key: 'director', sortable: false },
    { title: 'Category',  key: 'category', width: '100px', sortable: true  },
    { title: 'Tracked',   key: 'count',    width: '80px',  align: 'center', sortable: true },
    { title: 'KPI Score', key: 'score',    width: '260px', sortable: true  },
];

const tierColor = score =>
    score >= 100 ? '#15803d' :
    score >= 70  ? '#ca8a04' : '#b91c1c';

const tierLabel = score =>
    score >= 100 ? 'Top Performing'    :
    score >= 70  ? 'Average Performers' : 'Low Performers';

const categoryColor = cat => ({
    micro: 'blue-grey', small: 'teal', medium: 'indigo', large: 'deep-purple', cstc: 'pink'
}[cat] ?? 'grey');

// Top 10 from filtered category
const top10Data = computed(() => {
    const slice = filteredScores.value.slice(0, 10);
    return {
        names:     slice.map(s => s.province),
        scores:    slice.map(s => s.score),
        directors: slice.map(s => s.director),
        counts:    slice.map(s => s.count),
    };
});

// Failing from filtered category, sorted desc (least-failing at top)
const failingData = computed(() => {
    const slice = filteredScores.value
        .filter(s => s.score < 70)
        .slice()
        .sort((a, b) => b.score - a.score)
        .slice(0, 15);
    return {
        names:     slice.map(s => s.province),
        scores:    slice.map(s => s.score),
        directors: slice.map(s => s.director),
        counts:    slice.map(s => s.count),
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

const makeHorizOptions = (data, maxX = null, extraOpts = {}) => {
    const maxScore    = Math.max(...data.scores, 0);
    const computedMax = maxX ?? Math.ceil((maxScore * 1.12) / 10) * 10;
    return {
        chart: {
            type: 'bar',
            toolbar: { show: false },
            fontFamily: 'inherit',
            animations: { enabled: true, speed: 700, animateGradually: { enabled: true, delay: 80 } },
        },
        plotOptions: {
            bar: { horizontal: true, barHeight: '68%', borderRadius: 3, distributed: true },
        },
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
            max: computedMax,
            labels: {
                formatter: v => `${v}%`,
                style: { fontSize: '10px', fontFamily: 'inherit', colors: '#94a3b8' },
            },
            axisBorder: { show: false },
            axisTicks:  { show: false },
        },
        yaxis: {
            labels: {
                style: { fontSize: '10.5px', fontFamily: 'inherit', colors: '#475569' },
                maxWidth: 115,
            },
        },
        tooltip: {
            theme: 'light',
            custom: ({ dataPointIndex }) => tooltipHtml(data, dataPointIndex),
        },
        ...extraOpts,
    };
};

const top10Options = computed(() => makeHorizOptions(top10Data.value));
const top10Series  = computed(() => [{ name: 'KPI Score', data: top10Data.value.scores }]);

const failingOptions = computed(() => makeHorizOptions(failingData.value, 75, {
    annotations: {
        xaxis: [{
            x: 70,
            borderColor: '#f59e0b',
            strokeDashArray: 5,
            label: {
                text: '70% threshold',
                offsetY: 6,
                style: {
                    fontSize: '10px',
                    fontFamily: 'inherit',
                    background: '#fef3c7',
                    color: '#92400e',
                    padding: { top: 2, bottom: 2, left: 4, right: 4 },
                },
            },
        }],
    },
}));
const failingSeries = computed(() => [{ name: 'KPI Score', data: failingData.value.scores }]);
</script>

<style scoped>
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

.kpi-filter-bar { background: rgba(var(--v-theme-surface-variant), 0.3); }
:deep(.kpi-chip-active) { font-weight: 700 !important; opacity: 1 !important; }

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
