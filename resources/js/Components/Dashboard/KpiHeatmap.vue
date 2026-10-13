<script setup>
import { computed } from 'vue';
import { useKpiCategories, formatWeight } from '@/Data/kpiCategories';

// Provinces × KPIs for one year: each cell is the province's accomplishment vs
// target, coloured by the rating it earned. Rows are the dashboard's filtered
// score rows (each carries `kpi_scores`); categories is the live KPI catalog.
const props = defineProps({
    rows:       { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

// Ordered best to worst; "No data" is a KPI the province left blank.
const RATINGS = {
    'Outstanding':       { bg: '#166534', fg: '#ffffff' },
    'Very Satisfactory': { bg: '#22c55e', fg: '#ffffff' },
    'Satisfactory':      { bg: '#bbf7d0', fg: '#14532d' },
    'Average':           { bg: '#fde68a', fg: '#78350f' },
    'Unsatisfactory':    { bg: '#fdba74', fg: '#7c2d12' },
    'Poor':              { bg: '#dc2626', fg: '#ffffff' },
    'No data':           { bg: '#f1f5f9', fg: '#94a3b8' },
};
const WEAK = new Set(['Average', 'Unsatisfactory', 'Poor', 'No data']);
const ratingStyle = (label) => RATINGS[label] ?? RATINGS['No data'];

const kpiCategories = useKpiCategories(() => props.categories);
const isRanked = (r) => r.status === 'ranked';

// province_id → kpi_id → { label, pct }
const cells = computed(() => {
    const out = {};
    for (const r of props.rows) {
        out[r.province_id] = Object.fromEntries(
            (r.kpi_scores ?? []).map(s => [s.kpi_id, { label: s.adjective_label, pct: s.actual_pct }])
        );
    }
    return out;
});
const cell = (province, kpi) =>
    cells.value[province.province_id]?.[kpi.id] ?? { label: 'No data', pct: null };

// Scored KPIs only, grouped by category, each with how many provinces need
// attention on it. Provinces with no director or no data at all are left out
// of that count — the Submissions tab already flags them.
const groups = computed(() => {
    const scoredCols = props.rows.filter(isRanked);
    return kpiCategories.value.map(cat => {
        const source = props.categories.find(c => c.code === cat.code);
        const kpis = (source?.kpis ?? []).filter(k => k.is_scored).map(k => ({
            ...k,
            weak: scoredCols.filter(p => WEAK.has(cell(p, k).label)).length,
        }));
        return { ...cat, kpis, rankedCount: scoredCols.length };
    }).filter(cat => cat.kpis.length);
});

const cellTitle = (p, kpi) => {
    const c = cell(p, kpi);
    return `${p.province} — ${kpi.name}\n${c.label}${c.pct != null ? ` (${c.pct}%)` : ''}`;
};
</script>

<template>
    <div class="px-4 py-2 d-flex align-center gap-3 flex-wrap">
        <span class="text-caption text-medium-emphasis">Each cell is the province's accomplishment vs target, coloured by its rating.</span>
        <div class="d-flex align-center gap-2 flex-wrap ml-auto">
            <span v-for="(style, label) in RATINGS" :key="label" class="legend-item">
                <span class="legend-swatch" :style="{ background: style.bg }"></span>{{ label }}
            </span>
        </div>
    </div>
    <div v-if="rows.length" class="heatmap-scroll">
        <table class="heatmap">
            <thead>
                <tr>
                    <th class="kpi-col">KPI</th>
                    <th v-for="p in rows" :key="p.province_id" class="prov-col">
                        <div class="prov-name">{{ p.province }}</div>
                        <div class="prov-tier">{{ p.category }}</div>
                    </th>
                    <th class="weak-col">Needs attention</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="cat in groups" :key="cat.code">
                    <tr class="cat-row">
                        <td class="kpi-col">{{ cat.name }} <span class="text-medium-emphasis">· {{ cat.weightLabel }}</span></td>
                        <td :colspan="rows.length + 1"></td>
                    </tr>
                    <tr v-for="kpi in cat.kpis" :key="kpi.id">
                        <td class="kpi-col">
                            {{ kpi.name }}
                            <span class="kpi-weight">{{ formatWeight(kpi.weight) }}</span>
                        </td>
                        <td
                            v-for="p in rows"
                            :key="p.province_id"
                            class="heat-cell"
                            :style="{ background: ratingStyle(cell(p, kpi).label).bg, color: ratingStyle(cell(p, kpi).label).fg }"
                            :title="cellTitle(p, kpi)"
                        >
                            {{ cell(p, kpi).pct != null ? `${Math.round(cell(p, kpi).pct)}%` : '—' }}
                        </td>
                        <td class="weak-col" :class="{ 'weak-col--hot': kpi.weak > 0 && kpi.weak * 2 >= cat.rankedCount }">
                            {{ kpi.weak }} / {{ cat.rankedCount }}
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <div v-else class="text-center py-8 text-medium-emphasis text-body-2">No provinces match this filter.</div>
    <div class="px-4 py-2 text-caption text-medium-emphasis">
        "Needs attention" counts provinces with data that are rated Average or below on that KPI, or left it blank.
    </div>
</template>

<style scoped>
.heatmap-scroll { overflow: auto; max-height: 640px; }
.heatmap { border-collapse: separate; border-spacing: 2px; width: 100%; font-size: 13px; }
.heatmap thead th { position: sticky; top: 0; z-index: 2; background: #fff; padding: 6px 4px; vertical-align: bottom; }
.heatmap .kpi-col { position: sticky; left: 0; z-index: 1; background: #fff; text-align: left; min-width: 280px; max-width: 360px; padding: 4px 8px; line-height: 1.3; }
.heatmap thead .kpi-col { z-index: 3; }
.heatmap .prov-col { min-width: 92px; text-align: center; }
.prov-name { font-weight: 700; font-size: 12.5px; line-height: 1.2; }
.prov-tier { font-size: 11px; color: #94a3b8; text-transform: uppercase; }
.kpi-weight { font-size: 11px; color: #94a3b8; margin-left: 4px; white-space: nowrap; }
.heat-cell { text-align: center; font-weight: 600; font-size: 12px; border-radius: 4px; padding: 5px 4px; cursor: default; }
.cat-row td { background: #f8fafc; font-weight: 700; font-size: 12.5px; padding: 6px 8px; }
.cat-row .kpi-col { background: #f8fafc; }
.weak-col { text-align: center; font-size: 12px; color: #64748b; min-width: 110px; white-space: nowrap; }
.weak-col--hot { color: #b91c1c; font-weight: 700; }
.legend-item { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: #475569; }
.legend-swatch { width: 12px; height: 12px; border-radius: 3px; border: 1px solid rgba(15,23,42,0.08); }
</style>
