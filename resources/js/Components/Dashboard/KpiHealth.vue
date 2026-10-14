<script setup>
import { ref, computed } from 'vue';
import { useKpiCategories, formatWeight } from '@/Data/kpiCategories';

// How each scored KPI went across the selected provinces: one row per KPI with
// a bar of how many provinces landed in each rating, weakest KPIs first. Click
// a KPI to see which provinces scored what. Rows are the dashboard's filtered
// score rows (each carries kpi_scores); categories is the live KPI catalog.
const props = defineProps({
    rows:       { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

// Best to worst. "No data" is a KPI the province left blank.
const RATINGS = [
    { label: 'Outstanding',       color: '#166534', group: 'good' },
    { label: 'Very Satisfactory', color: '#22c55e', group: 'good' },
    { label: 'Satisfactory',      color: '#86efac', group: 'good' },
    { label: 'Average',           color: '#fcd34d', group: 'weak' },
    { label: 'Unsatisfactory',    color: '#fb923c', group: 'weak' },
    { label: 'Poor',              color: '#dc2626', group: 'weak' },
    { label: 'No data',           color: '#cbd5e1', group: 'blank' },
];
const RATING = Object.fromEntries(RATINGS.map((r, i) => [r.label, { ...r, order: i }]));

const sortBy   = ref('weakest');   // 'weakest' | 'matrix'
const onlyWeak = ref(false);
const open     = ref(new Set());
const toggle   = (id) => { const s = new Set(open.value); s.has(id) ? s.delete(id) : s.add(id); open.value = s; };

const kpiCategories = useKpiCategories(() => props.categories);

// Only provinces with figures are rated; the rest are listed, not counted.
const rated    = computed(() => props.rows.filter(r => r.status === 'ranked'));
const notRated = computed(() => props.rows.filter(r => r.status !== 'ranked').map(r => r.province));

const kpis = computed(() => {
    const n = rated.value.length;
    return kpiCategories.value.flatMap(cat => {
        const source = props.categories.find(c => c.code === cat.code);
        return (source?.kpis ?? []).filter(k => k.is_scored).map(kpi => {
            const results = rated.value.map(p => {
                const s = (p.kpi_scores ?? []).find(x => x.kpi_id === kpi.id);
                return { province: p.province, label: s?.adjective_label ?? 'No data', pct: s?.actual_pct ?? null };
            });
            const counts = Object.fromEntries(RATINGS.map(r => [r.label, 0]));
            for (const r of results) counts[RATING[r.label] ? r.label : 'No data']++;
            const good  = RATINGS.filter(r => r.group === 'good').reduce((a, r) => a + counts[r.label], 0);
            const weak  = RATINGS.filter(r => r.group === 'weak').reduce((a, r) => a + counts[r.label], 0);
            const blank = counts['No data'];
            return {
                ...kpi, category: cat,
                counts, good, weak, blank,
                share: n ? (weak + blank) / n : 0,               // share needing attention
                attention: n > 0 && (weak + blank) * 2 >= n,
                // Weakest rating first, then lowest %; blanks listed on their own.
                results: results.filter(r => r.label !== 'No data')
                    .sort((a, b) => RATING[b.label].order - RATING[a.label].order || (a.pct ?? 0) - (b.pct ?? 0)),
                blanks: results.filter(r => r.label === 'No data').map(r => r.province),
            };
        });
    });
});

const visible = computed(() => {
    let list = onlyWeak.value ? kpis.value.filter(k => k.attention) : kpis.value;
    if (sortBy.value === 'weakest') {
        list = [...list].sort((a, b) => b.share - a.share || b.weight - a.weight);
    }
    return list;
});

// Matrix order keeps the category headings; weakest-first is one ranked list.
const groups = computed(() => {
    if (sortBy.value === 'weakest') return [{ key: 'all', title: null, kpis: visible.value }];
    return kpiCategories.value
        .map(cat => ({ key: cat.code, title: `${cat.name} · ${cat.weightLabel}`, kpis: visible.value.filter(k => k.category.code === cat.code) }))
        .filter(g => g.kpis.length);
});

const attentionCount = computed(() => kpis.value.filter(k => k.attention).length);

const fmtPct = (v) => (v === null || v === undefined ? '—' : `${Math.round(v).toLocaleString()}%`);
const summary = (k) => [
    k.good  && `${k.good} good`,
    k.weak  && `${k.weak} weak`,
    k.blank && `${k.blank} blank`,
].filter(Boolean).join(' · ');
</script>

<template>
    <!-- Controls -->
    <div class="px-4 py-3 d-flex align-center gap-3 flex-wrap">
        <v-btn-toggle v-model="sortBy" mandatory density="compact" variant="outlined" divided color="indigo">
            <v-btn value="weakest" size="small" prepend-icon="mdi-sort-ascending">Weakest first</v-btn>
            <v-btn value="matrix" size="small" prepend-icon="mdi-format-list-group">By category</v-btn>
        </v-btn-toggle>
        <v-checkbox v-model="onlyWeak" density="compact" hide-details color="indigo" class="flex-grow-0">
            <template #label><span class="text-body-2">Only KPIs needing attention ({{ attentionCount }})</span></template>
        </v-checkbox>
        <div class="legend ml-auto">
            <span v-for="r in RATINGS" :key="r.label" class="legend-item"><span class="legend-swatch" :style="{ background: r.color }"></span>{{ r.label }}</span>
        </div>
    </div>
    <div class="px-4 pb-2 text-caption text-medium-emphasis">
        Across {{ rated.length }} {{ rated.length === 1 ? 'province' : 'provinces' }} with figures.
        <template v-if="notRated.length">
            Not counted ({{ notRated.length }}, no figures or no director): {{ notRated.join(', ') }}.
        </template>
        A KPI needs attention when half or more of the provinces are rated Average or below on it, or left it blank.
    </div>
    <v-divider />

    <div v-if="rated.length">
        <template v-for="g in groups" :key="g.key">
            <div v-if="g.title" class="group-title">{{ g.title }}</div>
            <div v-for="k in g.kpis" :key="k.id" class="kpi" :class="{ 'kpi--open': open.has(k.id) }">
                <button type="button" class="kpi-head" @click="toggle(k.id)">
                    <v-icon size="18" class="chev">{{ open.has(k.id) ? 'mdi-chevron-down' : 'mdi-chevron-right' }}</v-icon>
                    <div class="kpi-name">
                        <span>{{ k.name }}</span>
                        <span class="kpi-meta">
                            <template v-if="sortBy === 'weakest'">{{ k.category.code }} · </template>{{ formatWeight(k.weight) }}
                        </span>
                    </div>
                    <div class="dist" :title="RATINGS.filter(r => k.counts[r.label]).map(r => `${r.label}: ${k.counts[r.label]}`).join('\n')">
                        <span v-for="r in RATINGS" v-show="k.counts[r.label]" :key="r.label" class="dist-seg"
                              :style="{ flexGrow: k.counts[r.label], background: r.color }" />
                    </div>
                    <div class="kpi-summary">
                        <span>{{ summary(k) }}</span>
                        <v-icon v-if="k.attention" size="16" color="warning" class="ml-1">mdi-alert</v-icon>
                    </div>
                </button>

                <!-- Provinces on this KPI: weakest first, blanks on their own line -->
                <div v-if="open.has(k.id)" class="kpi-detail">
                    <div class="province-grid">
                        <div v-for="r in k.results" :key="r.province" class="province-chip" :style="{ borderLeftColor: RATING[r.label].color }">
                            <span class="province-name">{{ r.province }}</span>
                            <span class="province-pct">{{ fmtPct(r.pct) }}</span>
                            <span class="province-label">{{ r.label }}</span>
                        </div>
                    </div>
                    <div v-if="k.blanks.length" class="text-caption text-medium-emphasis mt-2">
                        <strong>Left blank ({{ k.blanks.length }}):</strong> {{ k.blanks.join(', ') }}
                    </div>
                </div>
            </div>
        </template>
        <div v-if="!visible.length" class="text-center py-8 text-medium-emphasis text-body-2">No KPI needs attention for this selection.</div>
    </div>
    <div v-else class="text-center py-8 text-medium-emphasis text-body-2">No provinces with figures for this selection.</div>
</template>

<style scoped>
.legend { display: flex; flex-wrap: wrap; gap: 4px 10px; }
.legend-item { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: #475569; white-space: nowrap; }
.legend-swatch { width: 12px; height: 12px; border-radius: 3px; }

.group-title { padding: 10px 16px 6px; font-size: 13px; font-weight: 700; color: #0f172a; background: #f8fafc; border-bottom: 1px solid #f1f5f9; }

.kpi { border-bottom: 1px solid #f1f5f9; }
.kpi--open { background: #fafbff; }
.kpi-head { all: unset; box-sizing: border-box; width: 100%; display: grid; grid-template-columns: 20px minmax(220px, 2fr) minmax(160px, 3fr) 190px; align-items: center; gap: 12px; padding: 10px 16px; cursor: pointer; }
.kpi-head:hover { background: #f8fafc; }
.kpi-head:focus-visible { outline: 2px solid #6366f1; outline-offset: -2px; }
.chev { color: #94a3b8; }
.kpi-name { display: flex; flex-direction: column; font-size: 14px; color: #0f172a; line-height: 1.3; min-width: 0; }
.kpi-meta { font-size: 11px; color: #94a3b8; margin-top: 2px; }
.dist { display: flex; height: 14px; border-radius: 7px; overflow: hidden; background: #f1f5f9; gap: 1px; }
.dist-seg { flex-basis: 0; min-width: 3px; }
.kpi-summary { display: flex; align-items: center; justify-content: flex-end; font-size: 12px; color: #475569; white-space: nowrap; }

.kpi-detail { padding: 4px 16px 14px 48px; }
.province-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 6px; }
.province-chip { display: grid; grid-template-columns: 1fr auto; grid-template-rows: auto auto; column-gap: 8px; padding: 6px 10px; background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid; border-radius: 6px; }
.province-name { font-size: 13px; font-weight: 600; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.province-pct { font-size: 13px; font-weight: 700; color: #0f172a; text-align: right; }
.province-label { grid-column: 1 / -1; font-size: 11px; color: #64748b; }

@media (max-width: 900px) {
    .kpi-head { grid-template-columns: 20px 1fr; }
    .dist, .kpi-summary { grid-column: 2; }
    .kpi-summary { justify-content: flex-start; }
}
</style>
