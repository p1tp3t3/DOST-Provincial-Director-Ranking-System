<template>
    <Head title="Dashboard" />
    <div class="d-flex flex-column gap-3">

        <!-- Official standing -->
        <v-card border elevation="0" rounded="lg" class="overflow-hidden">
            <div v-if="standing?.status === 'ranked'" class="standing-hero">
                <div class="hero-block">
                    <div class="eyebrow">{{ province.name }} · {{ standing.year }} official standing</div>
                    <div class="hero-rank">#{{ standing.rank }} <span>of {{ standing.tier_size }}</span></div>
                    <div class="d-flex align-center gap-2 mt-1">
                        <span class="text-body-2 text-medium-emphasis text-capitalize">{{ standing.tier }} tier</span>
                        <v-chip v-if="standing.bucket" size="small" :color="BUCKETS[standing.bucket]" variant="flat" class="text-white font-weight-medium">{{ standing.bucket }} group</v-chip>
                        <v-chip v-if="standing.revised_at" size="small" color="deep-orange" variant="tonal" prepend-icon="mdi-history"
                                :title="'Figures were corrected after these rankings were released'">Revised {{ formatShort(standing.revised_at) }}</v-chip>
                    </div>
                </div>

                <div class="hero-block">
                    <div class="eyebrow">Score</div>
                    <div class="hero-score">{{ fmt(standing.total_pct) }}%</div>
                    <div v-if="standing.change" class="text-body-2 mt-1" :class="standing.change.points >= 0 ? 'text-success' : 'text-error'">
                        {{ standing.change.points >= 0 ? '▲' : '▼' }} {{ fmt(Math.abs(standing.change.points)) }} pts
                        <span class="text-medium-emphasis">· {{ placesText(standing.change.places) }} vs {{ standing.previous_year }}</span>
                    </div>
                    <div v-else class="text-body-2 text-medium-emphasis mt-1">{{ standing.adjective_label }}</div>
                </div>

                <div class="hero-block hero-block--gaps">
                    <div class="eyebrow">To move up</div>
                    <div v-if="standing.gap_to_next_rank" class="gap-row">
                        <span>Reach #{{ standing.gap_to_next_rank.rank }}</span>
                        <strong>{{ gapText(standing.gap_to_next_rank.points) }}</strong>
                    </div>
                    <div v-else class="gap-row"><span>You're #1 in your tier</span><v-icon color="amber-darken-2" size="18">mdi-trophy</v-icon></div>
                    <div v-if="standing.gap_to_next_group" class="gap-row">
                        <span>Reach the {{ standing.gap_to_next_group.group }} group</span>
                        <strong>{{ gapText(standing.gap_to_next_group.points) }}</strong>
                    </div>
                    <div v-else-if="standing.bucket === 'Top'" class="gap-row"><span>In the Top group</span><v-icon color="success" size="18">mdi-check-circle</v-icon></div>
                </div>
            </div>
            <div v-else class="pa-6 d-flex align-center gap-4">
                <v-icon size="36" color="blue-grey">mdi-podium</v-icon>
                <div>
                    <div class="text-subtitle-1 font-weight-bold">{{ province.name }} isn't ranked{{ standing ? ` for ${standing.year}` : ' yet' }}</div>
                    <div class="text-body-2 text-medium-emphasis">
                        {{ standing?.status === 'no_director' ? 'The province has no provincial director assigned.' : 'Rankings appear once KPI figures are submitted.' }}
                    </div>
                </div>
            </div>
        </v-card>
        <ReleaseNotice v-if="release && !release.released" :status="release" compact class="px-1" />

        <v-row dense>
            <!-- Waiting for approval -->
            <v-col cols="12" md="5">
                <v-card border elevation="0" rounded="lg" class="h-100">
                    <div class="card-head">
                        <v-icon size="16" color="indigo">mdi-check-decagram-outline</v-icon>
                        <span>Waiting for your approval</span>
                        <v-chip v-if="awaiting_approval.quarters.length" size="x-small" color="warning" variant="tonal">{{ awaiting_approval.quarters.length }}</v-chip>
                    </div>
                    <v-divider />
                    <div v-if="awaiting_approval.quarters.length">
                        <div v-for="q in awaiting_approval.quarters" :key="q.quarter" class="approval-row">
                            <div class="flex-grow-1">
                                <div class="text-body-2 font-weight-medium">Q{{ q.quarter }} {{ awaiting_approval.year }}</div>
                                <div class="text-caption text-medium-emphasis">{{ q.entered }} of {{ awaiting_approval.expected }} KPIs entered</div>
                            </div>
                            <v-btn size="small" color="indigo" variant="tonal" @click="router.visit(`/provincial-kpi/${awaiting_approval.year}`)">Review</v-btn>
                        </div>
                    </div>
                    <div v-else class="empty-state">
                        <v-icon size="28" color="success">mdi-check-circle-outline</v-icon>
                        <div>Nothing waiting for your approval.</div>
                    </div>
                </v-card>
            </v-col>

            <!-- Trend -->
            <v-col cols="12" md="7">
                <v-card border elevation="0" rounded="lg" class="h-100">
                    <div class="card-head">
                        <v-icon size="16" color="indigo">mdi-chart-line</v-icon>
                        <span>Score by year</span>
                    </div>
                    <v-divider />
                    <div v-if="trend.length > 1" class="px-2 pt-2">
                        <VueApexCharts type="line" height="200" :options="trendOptions" :series="trendSeries" />
                    </div>
                    <div v-else class="empty-state">
                        <v-icon size="28" color="blue-grey">mdi-chart-line</v-icon>
                        <div>{{ trend.length ? `Only ${trend[0].year} is ranked so far.` : 'No ranked years yet.' }}</div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <v-row v-if="standing?.status === 'ranked'" dense>
            <!-- Where the points are lost -->
            <v-col cols="12" md="7">
                <v-card border elevation="0" rounded="lg" class="h-100">
                    <div class="card-head">
                        <v-icon size="16" color="indigo">mdi-bullseye-arrow</v-icon>
                        <span>Where to improve</span>
                        <span class="text-caption text-medium-emphasis ml-auto">points not earned, {{ standing.year }}</span>
                    </div>
                    <v-divider />
                    <div v-for="(k, i) in improvement" :key="k.kpi_id" class="improve-row">
                        <span class="improve-num">{{ i + 1 }}</span>
                        <div class="flex-grow-1" style="min-width:0;">
                            <div class="text-body-2">{{ k.name }}</div>
                            <div class="d-flex align-center gap-2 mt-1 flex-wrap">
                                <v-chip size="x-small" variant="tonal" color="indigo">{{ categoryName(k.category) }}</v-chip>
                                <v-chip size="x-small" variant="tonal" :color="missingTarget(k) ? 'orange-darken-2' : (RATINGS[k.rating] ?? 'blue-grey')">{{ ratingLabel(k) }}</v-chip>
                                <span class="text-caption text-medium-emphasis">{{ figures(k) }}</span>
                            </div>
                            <v-progress-linear :model-value="pct(k.earned, k.possible)" color="indigo" height="5" rounded bg-color="red-lighten-4" class="mt-2" />
                        </div>
                        <div class="improve-missed">
                            <div>−{{ fmt(k.missed) }}</div>
                            <div class="text-caption text-medium-emphasis">of {{ fmt(k.possible) }} pts</div>
                        </div>
                    </div>
                    <div v-if="!improvement.length" class="empty-state">
                        <v-icon size="28" color="success">mdi-star-circle-outline</v-icon>
                        <div>Every scored KPI earned full points.</div>
                    </div>
                </v-card>
            </v-col>

            <!-- Beside the tier -->
            <v-col cols="12" md="5">
                <v-card border elevation="0" rounded="lg" class="h-100">
                    <div class="card-head">
                        <v-icon size="16" color="indigo">mdi-compare-horizontal</v-icon>
                        <span>You vs your tier</span>
                        <span class="text-caption text-medium-emphasis ml-auto text-capitalize">{{ versus_tier.size }} {{ versus_tier.tier }} provinces</span>
                    </div>
                    <v-divider />
                    <div v-for="row in tierRows" :key="row.code" class="tier-row">
                        <div class="d-flex justify-space-between text-body-2 mb-1">
                            <span :class="{ 'font-weight-bold': row.code === 'TOTAL' }">{{ row.label }}</span>
                            <strong :class="row.mine >= row.tier_average ? 'text-success' : 'text-error'">{{ fmt(row.mine) }}%</strong>
                        </div>
                        <div class="tier-track">
                            <div class="tier-fill" :style="{ width: `${pct(row.mine, row.scale)}%` }" />
                            <div class="tier-mark tier-mark--avg"  :style="{ left: `${pct(row.tier_average, row.scale)}%` }" :title="`Tier average ${fmt(row.tier_average)}%`" />
                            <div class="tier-mark tier-mark--best" :style="{ left: `${pct(row.tier_best, row.scale)}%` }" :title="`Tier best ${fmt(row.tier_best)}%`" />
                        </div>
                        <div class="text-caption text-medium-emphasis mt-1">tier average {{ fmt(row.tier_average) }}% · best {{ fmt(row.tier_best) }}%</div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import { useKpiCatalogSync } from '@/Composables/useKpiCatalogSync';
import ReleaseNotice from '@/Components/Dashboard/ReleaseNotice.vue';

const props = defineProps({
    province:          { type: Object, required: true },
    categories:        { type: Array,  default: () => [] },   // [{ code, name, weight }]
    // { year, previous_year, status, tier, rank, tier_size, bucket, total_pct, adjective_label,
    //   change: { points, places } | null, gap_to_next_rank: { rank, points } | null,
    //   gap_to_next_group: { group, points } | null }
    standing:          { type: Object, default: null },
    improvement:       { type: Array,  default: () => [] },
    versus_tier:       { type: Object, default: null },
    trend:             { type: Array,  default: () => [] },   // [{ year, total_pct, rank, tier_size }]
    awaiting_approval: { type: Object, required: true },      // { year, expected, quarters: [{ quarter, entered }] }
    // The current year's ranking release: { year, released, submitted, total, deadline }
    release:           { type: Object, default: null },
});

// A catalog edit (weights, categories) changes the scores everywhere.
useKpiCatalogSync(['categories', 'standing', 'improvement', 'versus_tier', 'trend', 'release']);

const BUCKETS = { Top: '#ca8a04', Average: '#64748b', Low: '#9a3412' };
const RATINGS = {
    'Outstanding': 'green-darken-2', 'Very Satisfactory': 'green', 'Satisfactory': 'light-green-darken-2',
    'Average': 'amber-darken-2', 'Unsatisfactory': 'orange-darken-2', 'Poor': 'red', 'No data': 'blue-grey',
};

const fmt = (v) => (v === null || v === undefined ? '—' : Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
const pct = (n, d) => (d ? Math.max(0, Math.min(100, (n / d) * 100)) : 0);
const formatShort = (iso) => new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
const gapText = (points) => (points > 0 ? `+${fmt(points)} pts` : 'level — tie-break');
const placesText = (places) => (places > 0 ? `up ${places}` : places < 0 ? `down ${-places}` : 'same rank');
const categoryName = (code) => props.categories.find(c => c.code === code)?.name ?? code;

// A figure with no target scores nothing — say so, rather than "No data".
const missingTarget = (k) => k.rating === 'No data' && k.accomplished !== null && !Number(k.target);
const ratingLabel   = (k) => (missingTarget(k) ? 'No target' : k.rating);

// "Target 4 · got 0", or the achieved % for the calculated KPI.
const figures = (k) => {
    if (missingTarget(k)) return `No target set · got ${k.accomplished}`;
    if (k.target !== null || k.accomplished !== null) return `Target ${k.target ?? '—'} · got ${k.accomplished ?? '—'}`;
    return k.actual_pct === null ? 'No data entered' : `${fmt(k.actual_pct)}% achieved`;
};

// Each category is worth its weight in points, so that's the bar's scale.
const tierRows = computed(() => {
    if (!props.versus_tier) return [];
    return [
        { code: 'TOTAL', label: 'Overall', scale: 100, ...props.versus_tier.total },
        ...props.versus_tier.categories.map(c => {
            const cat = props.categories.find(x => x.code === c.code);
            return { ...c, label: `${cat?.name ?? c.code} (${Math.round((cat?.weight ?? 0) * 100)}%)`, scale: (cat?.weight ?? 1) * 100 };
        }),
    ];
});

const trendSeries = computed(() => [{ name: 'Score', data: props.trend.map(t => t.total_pct) }]);
const trendOptions = computed(() => ({
    chart: { toolbar: { show: false }, fontFamily: 'inherit', animations: { enabled: false } },
    stroke: { curve: 'straight', width: 3 },
    markers: { size: 5, strokeColors: '#ffffff', strokeWidth: 2, hover: { sizeOffset: 2 } },
    colors: ['#4f46e5'],
    // Each score sits on a small white tag with dark text above its point, so
    // it stays readable over the line and the gridlines.
    dataLabels: {
        enabled: true, formatter: (v) => `${v.toFixed(1)}%`, offsetY: -10,
        style: { fontSize: '12px', fontWeight: 600, colors: ['#ffffff'] },
        background: { enabled: true, foreColor: '#0f172a', borderColor: '#cbd5e1', borderWidth: 1, borderRadius: 4, padding: 4, opacity: 1, dropShadow: { enabled: false } },
    },
    xaxis: { categories: props.trend.map(t => t.year), labels: { style: { fontSize: '12px', colors: '#475569' } }, axisBorder: { color: '#cbd5e1' }, axisTicks: { show: false } },
    yaxis: { min: 0, max: 100, tickAmount: 4, labels: { formatter: (v) => `${v}%`, style: { colors: '#64748b' } } },
    grid: { borderColor: '#eef1f5', padding: { top: 8 } },
    tooltip: { y: { formatter: (v, { dataPointIndex: i }) => `${v.toFixed(2)}% · #${props.trend[i].rank} of ${props.trend[i].tier_size}` } },
}));
</script>

<style scoped>
.eyebrow { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
.standing-hero { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
.hero-block { padding: 20px 24px; border-right: 1px solid #f1f5f9; }
.hero-block:last-child { border-right: none; }
.hero-block--gaps { background: #f8fafc; }
.hero-rank { font-size: 44px; font-weight: 800; color: #0f172a; line-height: 1.05; margin-top: 4px; }
.hero-rank span { font-size: 18px; font-weight: 600; color: #64748b; }
.hero-score { font-size: 36px; font-weight: 800; color: #0f172a; line-height: 1.1; margin-top: 4px; }
.gap-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 8px 0; font-size: 14px; border-bottom: 1px solid #e2e8f0; }
.gap-row:last-child { border-bottom: none; }
.gap-row strong { color: #4f46e5; }

.card-head { display: flex; align-items: center; gap: 8px; padding: 12px 16px; font-size: 14px; font-weight: 700; color: #0f172a; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 28px 16px; color: #64748b; font-size: 14px; }

.approval-row { display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-bottom: 1px solid #f1f5f9; }
.approval-row:last-child { border-bottom: none; }

.improve-row { display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-bottom: 1px solid #f1f5f9; }
.improve-row:last-child { border-bottom: none; }
.improve-num { width: 22px; height: 22px; border-radius: 50%; background: #eef2ff; color: #4338ca; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.improve-missed { text-align: right; font-weight: 700; color: #b91c1c; font-size: 15px; white-space: nowrap; }

.tier-row { padding: 10px 16px; border-bottom: 1px solid #f1f5f9; }
.tier-row:last-child { border-bottom: none; }
.tier-track { position: relative; height: 8px; background: #f1f5f9; border-radius: 4px; }
.tier-fill { position: absolute; inset: 0 auto 0 0; background: #6366f1; border-radius: 4px; }
.tier-mark { position: absolute; top: -3px; width: 2px; height: 14px; border-radius: 1px; transform: translateX(-1px); }
.tier-mark--avg  { background: #0f172a; }
.tier-mark--best { background: #16a34a; }
</style>
