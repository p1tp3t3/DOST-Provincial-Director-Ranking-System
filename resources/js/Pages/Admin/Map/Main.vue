<template>
    <Head title="Performance Map" />
        <v-card border elevation="0" rounded="lg" class="overflow-hidden">

            <!-- Header -->
            <div class="px-5 pt-3 pb-0 d-flex align-center justify-space-between flex-wrap gap-3">
                <div class="d-flex align-center gap-2">
                    <v-icon size="15" color="indigo">mdi-map-outline</v-icon>
                    <span class="text-body-2 font-weight-bold">Performance Map</span>
                    <span class="text-caption text-medium-emphasis">· Click any area for details</span>
                </div>
                <div class="d-flex align-center gap-3">
                    <div class="d-flex align-center gap-2">
                        <span class="text-caption text-medium-emphasis">Method</span>
                        <v-btn-toggle v-model="selectedEvaluation" mandatory density="compact" variant="outlined" divided>
                            <v-btn value="strict"      size="small" class="px-2 text-caption">Strict</v-btn>
                            <v-btn value="operational" size="small" class="px-2 text-caption">Operational</v-btn>
                            <v-btn value="absolute"    size="small" class="px-2 text-caption">Absolute</v-btn>
                            <v-btn value="excellence"  size="small" class="px-2 text-caption">Excellence</v-btn>
                        </v-btn-toggle>
                    </div>
                    <v-divider vertical style="height:20px;" />
                    <div class="d-flex align-center gap-2">
                        <span class="text-caption text-medium-emphasis">Year</span>
                        <v-btn-toggle v-model="selectedYear" mandatory density="compact" variant="outlined" divided>
                            <v-btn v-for="y in available_years" :key="y" :value="y" size="small" class="px-3 text-caption">{{ y }}</v-btn>
                        </v-btn-toggle>
                    </div>
                </div>
            </div>

            <v-divider class="mt-3" />

            <!-- Category tabs -->
            <v-tabs
                v-model="selectedCategory"
                density="compact"
                color="indigo"
                class="px-2"
                @update:modelValue="selectedCategory = $event"
            >
                <v-tab value="all" class="text-caption">
                    All
                    <v-chip size="x-small" variant="tonal" class="ml-1">{{ categoryCounts.all }}</v-chip>
                </v-tab>
                <v-tab v-for="cat in categories" :key="cat.value" :value="cat.value" class="text-caption">
                    {{ cat.label }}
                    <v-chip size="x-small" :color="cat.color" variant="tonal" class="ml-1">
                        {{ categoryCounts[cat.value] ?? 0 }}
                    </v-chip>
                </v-tab>
            </v-tabs>

            <!-- KPI filter + legend -->
            <div class="d-flex align-center gap-2 px-4 py-1 flex-wrap">
                <span class="text-caption text-medium-emphasis" style="white-space:nowrap;">KPI:</span>
                <v-chip-group v-model="selectedKpi" mandatory selected-class="kpi-chip-active">
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
                <div class="d-flex align-center gap-3 ml-auto flex-wrap">
                    <div class="d-flex align-center gap-1">
                        <span class="legend-dot" style="background:#15803d;"></span>
                        <span class="text-caption text-medium-emphasis">Top ({{ tierCounts.top }})</span>
                    </div>
                    <div class="d-flex align-center gap-1">
                        <span class="legend-dot" style="background:#ca8a04;"></span>
                        <span class="text-caption text-medium-emphasis">Avg ({{ tierCounts.avg }})</span>
                    </div>
                    <div class="d-flex align-center gap-1">
                        <span class="legend-dot" style="background:#b91c1c;"></span>
                        <span class="text-caption text-medium-emphasis">Low ({{ tierCounts.low }})</span>
                    </div>
                    <div class="d-flex align-center gap-1">
                        <span class="legend-dot" style="background:#94a3b8;"></span>
                        <span class="text-caption text-medium-emphasis">No data</span>
                    </div>
                </div>
            </div>

            <v-divider />

            <!-- Map -->
            <PhilippinesMap
                key="ph-map"
                :scores="rankedScores"
                :year-data="kpi_scores_by_year[selectedYear]"
                :kpi-outcomes="kpi_outcomes"
                :selected-year="selectedYear"
                :selected-category="selectedCategory"
                height="calc(100vh - 260px)"
            />

        </v-card>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import PhilippinesMap      from '@/Components/Map/PhilippinesMap.vue';

const props = defineProps({
    kpi_scores_by_year: { type: Object, default: () => ({}) },
    kpi_outcomes:       { type: Array,  default: () => [] },
    available_years:    { type: Array,  default: () => [] },
});

const selectedYear       = ref(props.available_years[0] ?? 2025);
const selectedCategory   = ref('all');
const selectedKpi        = ref('overall');
const selectedEvaluation = ref('operational');

const SCORE_FIELD = {
    strict:      'strict_score',
    operational: 'operational_score',
    absolute:    'absolute_score',
    excellence:  'excellence_score',
};

const categories = [
    { value: 'micro',  label: 'Micro',  color: 'blue-grey'   },
    { value: 'small',  label: 'Small',  color: 'teal'        },
    { value: 'medium', label: 'Medium', color: 'indigo'      },
    { value: 'large',  label: 'Large',  color: 'deep-purple' },
    { value: 'cstc',   label: 'CSTC',   color: 'pink'        },
];

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

const filteredScores = computed(() =>
    selectedCategory.value === 'all'
        ? currentScores.value
        : currentScores.value.filter(s => s.category === selectedCategory.value)
);

const rankedScores = computed(() =>
    filteredScores.value.map((s, i) => ({ ...s, rank: i + 1 }))
);

const categoryCounts = computed(() => {
    const counts = { all: currentScores.value.length };
    for (const s of currentScores.value) {
        if (s.category) counts[s.category] = (counts[s.category] ?? 0) + 1;
    }
    return counts;
});

const tierCounts = computed(() => ({
    top: filteredScores.value.filter(s => s.score >= 70).length,
    avg: filteredScores.value.filter(s => s.score >= 40 && s.score < 70).length,
    low: filteredScores.value.filter(s => s.score < 40).length,
}));
</script>

<style scoped>
:deep(.kpi-chip-active) { font-weight: 700 !important; opacity: 1 !important; }

.legend-dot {
    display: inline-block;
    width: 9px; height: 9px;
    border-radius: 50%; flex-shrink: 0;
}
</style>
