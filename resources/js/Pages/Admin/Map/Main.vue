<template>
    <Head title="Performance Map" />
    <v-card border elevation="0" rounded="lg" class="overflow-hidden">

        <!-- Header: title + bucket legend -->
        <div class="px-5 pt-3 pb-0 d-flex align-center justify-space-between flex-wrap gap-3">
            <div class="d-flex align-center gap-2">
                <v-icon size="15" color="indigo">mdi-map-outline</v-icon>
                <span class="text-body-2 font-weight-bold">Performance Map</span>
                <span class="text-caption text-medium-emphasis">· Click any area for details</span>
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
                <div class="d-flex align-center gap-1">
                    <span class="legend-dot" style="background:#94a3b8;"></span>
                    <span class="text-caption text-medium-emphasis">No data</span>
                </div>
            </div>
        </div>

        <v-divider class="mt-3" />

        <!-- Tier segmented + Year segmented -->
        <div class="d-flex align-center gap-3 px-4 py-2 flex-wrap">
            <div class="d-flex align-center gap-2">
                <span class="filter-label">Tier</span>
                <div class="segmented category-segmented">
                    <button class="segmented-btn" :class="{ active: selectedTier === 'all' }" @click="selectedTier = 'all'">
                        All <span class="seg-count seg-count--all">{{ tierCounts.all }}</span>
                    </button>
                    <button
                        v-for="t in tiers"
                        :key="t.value"
                        class="segmented-btn"
                        :class="['category-btn-' + t.value, { active: selectedTier === t.value }]"
                        @click="selectedTier = t.value"
                    >
                        {{ t.label }}
                        <span class="seg-count" :class="'seg-count--' + t.value">{{ tierCounts[t.value] ?? 0 }}</span>
                    </button>
                </div>
            </div>
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

        <!-- Category segmented -->
        <div class="d-flex align-center gap-3 px-4 py-2 flex-wrap" style="border-top:1px solid rgba(0,0,0,0.06);">
            <span class="filter-label">Category</span>
            <div class="segmented">
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
            <v-spacer />
            <span v-if="selectedCategory !== 'overall'" class="text-caption text-medium-emphasis">
                Colored by <strong>{{ selectedCategory }}</strong> performance (re-bucketed within tier)
            </span>
        </div>

        <v-divider />

        <!-- Map -->
        <PhilippinesMap
            key="ph-map"
            :scores="filteredScores"
            :selected-year="selectedYear"
            :selected-tier="selectedTier"
            height="calc(100vh - 260px)"
        />

    </v-card>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import PhilippinesMap from '@/Components/Map/PhilippinesMap.vue';

const props = defineProps({
    rankings_by_year: { type: Object, default: () => ({}) },
    kpi_categories:   { type: Array,  default: () => [] },
    available_years:  { type: Array,  default: () => [] },
});

const selectedYear     = ref(props.available_years[0] ?? new Date().getFullYear());
const selectedTier     = ref('all');
const selectedCategory = ref('overall'); // 'overall' | 'CORE' | 'FUNCTIONAL' | 'SUPPORT'

const tiers = [
    { value: 'micro',  label: 'Micro'  },
    { value: 'small',  label: 'Small'  },
    { value: 'medium', label: 'Medium' },
    { value: 'large',  label: 'Large'  },
    { value: 'cstc',   label: 'CSTC'   },
];

const categoryOptions = computed(() =>
    props.kpi_categories.map(c => ({
        value:  c.code,
        label:  c.code,
        weight: `${Math.round(c.weight * 100)}%`,
    }))
);

// Rank-percentile bucketing mirrored from RankingService::rankAndBucket. Used
// to re-bucket within tier when the user picks a specific category. If the
// thresholds in config/ranking.php change, update these to match.
const BUCKET_TOP_PCT   = 0.20;
const BUCKET_UNDER_PCT = 0.20;
const MIN_GROUP_FOR_BUCKETS = 5;

const getScore = (row) =>
    selectedCategory.value === 'overall'
        ? row.total_pct
        : (row.subtotals_pct?.[selectedCategory.value] ?? 0);

const assignBuckets = (sortedRows) => {
    const n = sortedRows.length;
    if (n < MIN_GROUP_FOR_BUCKETS) return sortedRows.map(r => ({ ...r, bucket: null }));
    const topN = Math.max(1, Math.round(n * BUCKET_TOP_PCT));
    let   undN = Math.max(1, Math.round(n * BUCKET_UNDER_PCT));
    if (topN + undN >= n) undN = Math.max(1, n - topN - 1);
    const avgEnd = n - undN;
    return sortedRows.map((r, i) => ({
        ...r,
        bucket: i < topN ? 'Top' : i < avgEnd ? 'Average' : 'Under',
    }));
};

// Flatten + re-bucket. For Overall view we trust the backend's per-tier ranking
// and buckets. For a specific category we re-sort within each tier by that
// subtotal and re-bucket. `score` is always set to the active score so the
// PhilippinesMap component can stay generic.
const allTierRows = computed(() => {
    const yearData = props.rankings_by_year[selectedYear.value] ?? {};
    const out = [];
    for (const tierRows of Object.values(yearData)) {
        const ranked = selectedCategory.value === 'overall'
            ? tierRows
            : assignBuckets(tierRows.slice().sort((a, b) => getScore(b) - getScore(a)));

        for (const r of ranked) {
            out.push({
                ...r,
                tier_rank: r.rank,
                score:     getScore(r),
            });
        }
    }
    return out;
});

const filteredScores = computed(() =>
    selectedTier.value === 'all'
        ? allTierRows.value
        : allTierRows.value.filter(r => r.category === selectedTier.value)
);

const tierCounts = computed(() => {
    const counts = { all: allTierRows.value.length };
    for (const r of allTierRows.value) counts[r.category] = (counts[r.category] ?? 0) + 1;
    return counts;
});

const bucketCounts = computed(() => {
    const counts = { Top: 0, Average: 0, Under: 0 };
    for (const r of filteredScores.value) {
        if (r.bucket && counts[r.bucket] !== undefined) counts[r.bucket]++;
    }
    return counts;
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
.segmented-btn:hover:not(.active) {
    color: #0f172a;
    background: rgba(255, 255, 255, 0.6);
}
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

.legend-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
</style>
