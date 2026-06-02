<template>
    <Head title="Dashboard" />
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

            <!-- Leaderboard Card -->
            <v-row dense>
                <v-col cols="12">
                    <v-card border elevation="0" rounded="lg">

                        <!-- Unified Header -->
                        <div class="px-4 pt-3 pb-0">

                            <!-- Row 1: Title + Year + View controls -->
                            <div class="d-flex align-center justify-space-between flex-wrap gap-3 mb-2">
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
                                            <div class="text-caption opacity-70">Choose an evaluation method: <strong>Strict</strong> (met ÷ active), <strong>Operational</strong> (active ÷ 54), <strong>Absolute</strong> (met ÷ 54), or <strong>Excellence</strong> (exceeded ÷ active — bonus credit for over-delivering).</div>
                                        </div>
                                    </v-tooltip>
                                    <v-chip size="x-small" color="primary" variant="tonal" class="font-weight-medium">
                                        {{ filteredScores.length }}
                                        <template v-if="selectedCategory !== 'all'"> · {{ categoryLabel }}</template>
                                    </v-chip>
                                </div>
                                <div class="d-flex align-center gap-3 flex-wrap">
                                    <div class="d-flex align-center gap-2">
                                        <span class="text-caption font-weight-medium text-medium-emphasis">Method</span>
                                        <v-tooltip :text="evaluationMeta[selectedEvaluation].hint" location="bottom" max-width="280">
                                            <template #activator="{ props: tip }">
                                                <v-btn-toggle v-bind="tip" v-model="selectedEvaluation" mandatory density="compact" variant="outlined" divided>
                                                    <v-btn value="strict"      size="small" class="px-2 text-caption">Strict</v-btn>
                                                    <v-btn value="operational" size="small" class="px-2 text-caption">Operational</v-btn>
                                                    <v-btn value="absolute"    size="small" class="px-2 text-caption">Absolute</v-btn>
                                                    <v-btn value="excellence"  size="small" class="px-2 text-caption">Excellence</v-btn>
                                                </v-btn-toggle>
                                            </template>
                                        </v-tooltip>
                                    </div>
                                    <v-divider vertical style="height:24px;" />
                                    <div class="d-flex align-center gap-2">
                                        <span class="text-caption font-weight-medium text-medium-emphasis">Year</span>
                                        <v-btn-toggle v-model="selectedYear" mandatory density="compact" variant="outlined" divided>
                                            <v-btn v-for="y in available_years" :key="y" :value="y" size="small" class="px-3 text-caption">{{ y }}</v-btn>
                                        </v-btn-toggle>
                                    </div>
                                    <v-divider vertical style="height:24px;" />
                                    <div class="d-flex align-center gap-2">
                                        <v-btn-toggle v-model="viewMode" mandatory density="compact" variant="outlined" divided>
                                            <v-btn value="table" size="small" title="Table view">
                                                <v-icon size="15">mdi-table-large</v-icon>
                                            </v-btn>
                                            <v-btn value="podium" size="small" title="Podium view">
                                                <v-icon size="15">mdi-podium-gold</v-icon>
                                            </v-btn>
                                        </v-btn-toggle>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <v-divider />

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

                        <!-- KPI Filter + Legend -->
                        <div class="d-flex align-center gap-2 px-4 py-1 flex-wrap">
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
                            <div class="ml-auto d-flex align-center gap-3">
                                <div class="d-flex align-center gap-1">
                                    <span class="legend-dot" style="background:#15803d;"></span>
                                    <span class="text-caption text-medium-emphasis">Top</span>
                                </div>
                                <div class="d-flex align-center gap-1">
                                    <span class="legend-dot" style="background:#ca8a04;"></span>
                                    <span class="text-caption text-medium-emphasis">Avg</span>
                                </div>
                                <div class="d-flex align-center gap-1">
                                    <span class="legend-dot" style="background:#b91c1c;"></span>
                                    <span class="text-caption text-medium-emphasis">Low</span>
                                </div>
                            </div>
                        </div>

                        <v-divider />

                        <!-- Search row (table view only) -->
                        <div v-if="viewMode === 'table'" class="px-4 py-2 d-flex justify-end">
                            <v-text-field
                                v-model="raceSearch"
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
                                    <div class="score-track" style="cursor:default;">
                                        <div
                                            class="score-fill"
                                            :style="{
                                                width: `${Math.min(item.score, 100)}%`,
                                                background: tierColor(item.score),
                                            }"
                                        />
                                    </div>
                                    <div class="d-flex flex-column" style="min-width:0;">
                                        <span class="text-caption font-weight-bold" :style="{ color: tierColor(item.score) }">
                                            {{ item.score }}%
                                        </span>
                                        <span class="text-medium-emphasis" style="font-size:10px; line-height:1.3; white-space:nowrap;">
                                            {{ item.met }} met · {{ item.exceeded }} exceeded · {{ item.active }}/{{ item.total }} active
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <template #no-data>
                                <div class="text-center py-8 text-medium-emphasis text-body-2">
                                    No provinces found
                                </div>
                            </template>
                        </v-data-table>

                        <!-- Podium View -->
                        <div v-else class="px-5 pt-3 pb-5">

                            <!-- Leader banner -->
                            <div v-if="top3[0]" class="podium-banner mb-4">
                                <v-icon size="16" color="amber-darken-2">mdi-trophy</v-icon>
                                <span class="text-body-2 ml-2">
                                    <strong>{{ top3[0].province }}</strong> leads {{ selectedYear }} with
                                    <span class="font-weight-bold" :style="{ color: tierColor(top3[0].score) }">{{ top3[0].score }}%</span>
                                    &nbsp;·&nbsp; Dir. {{ top3[0].director }}
                                </span>
                            </div>

                            <div class="d-flex gap-5">

                                <!-- Podium stage -->
                                <div class="podium-stage">
                                    <div class="podium-items">

                                        <!-- 2nd place -->
                                        <div v-if="top3[1]" class="podium-item">
                                            <div class="podium-info">
                                                <div class="podium-province">{{ top3[1].province }}</div>
                                                <div class="podium-director">{{ top3[1].director }}</div>
                                                <div class="podium-score" :style="{ color: tierColor(top3[1].score) }">{{ top3[1].score }}%</div>
                                                <div class="podium-raw">{{ top3[1].met }} met · {{ top3[1].exceeded }} exceeded · {{ top3[1].active }}/{{ top3[1].total }}</div>
                                            </div>
                                            <div class="podium-block podium-silver">
                                                <v-icon color="white" size="22">mdi-medal</v-icon>
                                                <span class="podium-rank-num">2nd</span>
                                            </div>
                                        </div>

                                        <!-- 1st place -->
                                        <div v-if="top3[0]" class="podium-item">
                                            <div class="podium-info">
                                                <v-icon color="amber-darken-1" size="26" class="mb-1">mdi-trophy</v-icon>
                                                <div class="podium-province">{{ top3[0].province }}</div>
                                                <div class="podium-director">{{ top3[0].director }}</div>
                                                <div class="podium-score" :style="{ color: tierColor(top3[0].score) }">{{ top3[0].score }}%</div>
                                                <div class="podium-raw">{{ top3[0].met }} met · {{ top3[0].exceeded }} exceeded · {{ top3[0].active }}/{{ top3[0].total }}</div>
                                            </div>
                                            <div class="podium-block podium-gold">
                                                <span class="podium-rank-num">1st</span>
                                            </div>
                                        </div>

                                        <!-- 3rd place -->
                                        <div v-if="top3[2]" class="podium-item">
                                            <div class="podium-info">
                                                <div class="podium-province">{{ top3[2].province }}</div>
                                                <div class="podium-director">{{ top3[2].director }}</div>
                                                <div class="podium-score" :style="{ color: tierColor(top3[2].score) }">{{ top3[2].score }}%</div>
                                                <div class="podium-raw">{{ top3[2].met }} met · {{ top3[2].exceeded }} exceeded · {{ top3[2].active }}/{{ top3[2].total }}</div>
                                            </div>
                                            <div class="podium-block podium-bronze">
                                                <v-icon color="white" size="22">mdi-medal-outline</v-icon>
                                                <span class="podium-rank-num">3rd</span>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <v-divider vertical class="mx-1" />

                                <!-- Ranks 4+ -->
                                <div class="flex-1 overflow-y-auto" style="max-height:460px;">
                                    <div
                                        v-for="item in restList"
                                        :key="item.province"
                                        class="podium-rest-row"
                                    >
                                        <span class="podium-rest-rank">#{{ item.rank }}</span>
                                        <div class="flex-1" style="min-width:0;">
                                            <div class="text-body-2 font-weight-medium text-truncate">{{ item.province }}</div>
                                            <div class="text-caption text-medium-emphasis text-truncate">{{ item.director }}</div>
                                        </div>
                                        <v-chip :color="categoryColor(item.category)" size="x-small" variant="tonal" class="font-weight-medium text-capitalize flex-shrink-0">
                                            {{ item.category }}
                                        </v-chip>
                                        <div class="podium-rest-score">
                                            <div class="score-track">
                                                <div class="score-fill" :style="{ width: `${Math.min(item.score, 100)}%`, background: tierColor(item.score) }" />
                                            </div>
                                            <span class="text-caption font-weight-bold" :style="{ color: tierColor(item.score) }">{{ item.score }}%</span>
                                            <span class="text-medium-emphasis" style="font-size:10px; line-height:1;">{{ item.met }} met · {{ item.exceeded }} exceeded</span>
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
                            <div class="text-caption text-medium-emphasis">All provinces are Average Performers or Top Performers</div>
                        </div>
                    </v-card>
                </v-col>

            </v-row>

        </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import QuantityCard        from '@/Components/Cards/QuantityCard.vue';
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

const selectedYear       = ref(props.available_years[0] ?? 2025);
const selectedCategory   = ref('all');
const selectedKpi        = ref('overall');
const raceSearch         = ref('');
const viewMode           = ref('table');
const selectedEvaluation = ref('operational'); // 'strict' | 'operational' | 'absolute' | 'excellence'

// Each ranking record from the controller carries all four scores; the active
// one is selected here so the rest of the template only sees `item.score`.
const SCORE_FIELD = {
    strict:      'strict_score',
    operational: 'operational_score',
    absolute:    'absolute_score',
    excellence:  'excellence_score',
};
const evaluationMeta = {
    strict:      { label: 'Strict',      desc: 'Met ÷ Active Targets',  hint: 'Skill rate: of the targets you set, how many you hit.' },
    operational: { label: 'Operational', desc: '(Met + Monitored) ÷ 54', hint: 'Board coverage: how many of the 54 indicators you engaged with.' },
    absolute:    { label: 'Absolute',    desc: 'Met ÷ 54',               hint: 'Hardest line: blanks and misses both count as zero.' },
    excellence:  { label: 'Excellence',  desc: 'Exceeded ÷ Active Targets', hint: 'Bonus credit: of the targets you set, how many you actually beat.' },
};

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

// Project the active evaluation score onto each record as `score`, then re-sort.
// The controller pre-sorts by operational so other order is recomputed here.
const currentScores = computed(() => {
    const yearData = props.kpi_scores_by_year[selectedYear.value];
    if (!yearData) return [];
    const raw = selectedKpi.value === 'overall'
        ? (yearData.overall ?? [])
        : (yearData.kpi?.[selectedKpi.value] ?? []);
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

const top3     = computed(() => rankedScores.value.slice(0, 3));
const restList = computed(() => rankedScores.value.slice(3));

const raceHeaders = computed(() => [
    { title: 'Rank',     key: 'rank',     width: '64px',  sortable: false },
    { title: 'Province', key: 'province', sortable: true  },
    { title: 'Director', key: 'director', sortable: false },
    { title: 'Category', key: 'category', width: '100px', sortable: true  },
    { title: 'Active',   key: 'active',   width: '70px',  align: 'center', sortable: true },
    { title: 'Met',      key: 'met',      width: '60px',  align: 'center', sortable: true },
    { title: 'Exceeded', key: 'exceeded', width: '80px',  align: 'center', sortable: true },
    { title: `${evaluationMeta[selectedEvaluation.value].label} Score`,
        key: 'score', width: '240px', sortable: true },
]);

// Scores are 0-100 binary-counting percentages. Tiers calibrated so a
// reasonable Operational/Absolute score lands in the middle band.
const tierColor = score =>
    score >= 70 ? '#15803d' :
    score >= 40 ? '#ca8a04' : '#b91c1c';

const tierLabel = score =>
    score >= 70 ? 'Top Performers'    :
    score >= 40 ? 'Average Performers' : 'Low Performers';

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
        counts:    slice.map(s => s.active ?? 0),
    };
});

// Failing from filtered category, sorted desc (least-failing at top)
const failingData = computed(() => {
    const slice = filteredScores.value
        .filter(s => s.score < 40)
        .slice()
        .sort((a, b) => b.score - a.score)
        .slice(0, 15);
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

/* ── Podium view ─────────────────────────────────────── */
.podium-banner {
    display: flex;
    align-items: center;
    background: rgba(var(--v-theme-surface-variant), 0.4);
    border-left: 3px solid rgb(var(--v-theme-primary));
    border-radius: 0 6px 6px 0;
    padding: 8px 14px;
}

.podium-stage {
    width: 42%;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-self: flex-start;
}

.podium-items {
    display: flex;
    align-items: flex-end;
    gap: 8px;
}

.podium-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.podium-info {
    text-align: center;
    padding-bottom: 10px;
}

.podium-province {
    font-size: 14px;
    font-weight: 700;
    line-height: 1.3;
    max-width: 130px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.podium-director {
    font-size: 12px;
    color: #64748b;
    margin: 3px 0 6px;
    max-width: 130px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.podium-score {
    font-size: 24px;
    font-weight: 800;
    line-height: 1;
}

.podium-raw {
    font-size: 11px;
    color: #64748b;
    margin-top: 4px;
}

.podium-block {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border-radius: 8px 8px 0 0;
    color: white;
}

.podium-rank-num {
    font-size: 18px;
    font-weight: 800;
    color: white;
}

.podium-gold   { height: 210px; background: linear-gradient(160deg, #3730a3, #6366f1); }
.podium-silver { height: 160px; background: linear-gradient(160deg, #334155, #64748b); }
.podium-bronze { height: 120px; background: linear-gradient(160deg, #92400e, #d97706); }

.podium-rest-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 7px 0;
    border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.podium-rest-rank {
    width: 32px;
    text-align: right;
    flex-shrink: 0;
    font-size: 11px;
    color: #94a3b8;
    font-weight: 600;
}

.podium-rest-score {
    width: 130px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.podium-rest-score .score-track { flex: unset; }
</style>
