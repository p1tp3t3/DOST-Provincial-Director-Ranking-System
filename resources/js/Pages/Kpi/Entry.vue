<template>
    <Head :title="can_edit ? 'KPI Entry' : 'KPI Review'" />
    <div class="d-flex flex-column gap-3">

        <!-- Breadcrumb -->
        <div class="d-flex align-center gap-2">
            <template v-if="is_central">
                <v-btn icon size="x-small" variant="text" color="medium-emphasis" @click="leave('/kpi-data')">
                    <v-icon size="18">mdi-arrow-left</v-icon>
                </v-btn>
                <span class="text-caption text-medium-emphasis cursor-pointer" @click="leave('/kpi-data')">KPI Data Editor</span>
                <v-icon size="12" color="medium-emphasis">mdi-chevron-right</v-icon>
                <span class="text-caption font-weight-medium">{{ province.name }}</span>
            </template>
            <span v-else class="text-subtitle-1 font-weight-bold">{{ can_edit ? 'KPI Entry' : 'KPI Review' }}</span>
        </div>

        <!-- Header: province, director, year -->
        <v-card border elevation="0" rounded="lg" class="overflow-hidden">
            <div class="d-flex flex-wrap">
                <div class="pa-4 d-flex align-center gap-3 flex-grow-1">
                    <v-avatar :color="tierColor(province.category)" size="44" rounded="lg">
                        <v-icon color="white" size="22">mdi-map-marker-outline</v-icon>
                    </v-avatar>
                    <div>
                        <div class="text-h6 font-weight-bold">{{ province.name }}</div>
                        <div class="d-flex align-center gap-2 mt-1">
                            <v-chip :color="tierColor(province.category)" size="x-small" variant="tonal" class="font-weight-medium text-uppercase">{{ province.category }}</v-chip>
                            <span class="text-caption text-medium-emphasis">·</span>
                            <span class="text-caption text-medium-emphasis">{{ director?.name ?? 'No provincial director' }}</span>
                        </div>
                    </div>
                </div>
                <v-divider vertical />
                <div class="pa-4">
                    <div class="filter-label mb-1">Reporting Year</div>
                    <div class="segmented">
                        <button v-for="yr in years" :key="yr" class="segmented-btn" :class="{ active: year === yr }" @click="changeYear(yr)">{{ yr }}</button>
                    </div>
                </div>
            </div>
            <v-divider />

            <!-- Quarter status: deadline / lock, and the director's approval -->
            <div class="period-strip">
                <div v-for="p in periods" :key="p.quarter" class="period-card">
                    <div class="d-flex align-center justify-space-between">
                        <span class="period-name">Q{{ p.quarter }}</span>
                        <v-chip size="x-small" :color="periodState(p).color" variant="tonal" class="font-weight-medium">
                            <v-icon start size="12">{{ periodState(p).icon }}</v-icon>{{ periodState(p).label }}
                        </v-chip>
                    </div>
                    <div class="text-caption text-medium-emphasis mt-1">{{ periodState(p).detail }}</div>
                    <div class="mt-2">
                        <div v-if="p.approval" class="text-caption text-success d-flex align-center gap-1">
                            <v-icon size="14">mdi-check-decagram</v-icon>
                            Approved by {{ p.approval.by }} · {{ formatDate(p.approval.at) }}
                        </div>
                        <div v-else class="text-caption text-medium-emphasis d-flex align-center gap-1">
                            <v-icon size="14">mdi-clock-outline</v-icon> Awaiting director approval
                        </div>
                        <v-btn
                            v-if="can_approve && !p.approval"
                            size="x-small" color="success" variant="flat" class="mt-2"
                            prepend-icon="mdi-check"
                            :loading="approving === p.quarter"
                            :disabled="!quarterHasData(p.quarter)"
                            @click="approve(p.quarter)"
                        >Approve Q{{ p.quarter }}</v-btn>
                    </div>
                </div>
                <div class="period-card period-card--targets">
                    <div class="d-flex align-center justify-space-between">
                        <span class="period-name">Targets</span>
                        <v-chip size="x-small" :color="targets_open ? 'success' : 'blue-grey'" variant="tonal" class="font-weight-medium">
                            <v-icon start size="12">{{ targets_open ? 'mdi-lock-open-variant-outline' : 'mdi-lock-outline' }}</v-icon>{{ targets_open ? 'Open' : 'Locked' }}
                        </v-chip>
                    </div>
                    <div class="text-caption text-medium-emphasis mt-1">Annual targets close with the Q4 deadline.</div>
                </div>
            </div>
        </v-card>

        <!-- One table per category -->
        <v-card v-for="cat in kpi_categories" :key="cat.id" border elevation="0" rounded="lg">
            <div class="d-flex align-center gap-2 px-4 py-3" :style="{ background: catVisual(cat.code).bg }">
                <v-avatar :color="catVisual(cat.code).color" size="30" rounded="md">
                    <v-icon size="16" color="white">{{ catVisual(cat.code).icon }}</v-icon>
                </v-avatar>
                <div>
                    <div class="text-subtitle-2 font-weight-bold">{{ cat.name }}</div>
                    <div class="text-caption text-medium-emphasis">
                        Weight: <strong>{{ (cat.weight * 100).toFixed(0) }}%</strong> · {{ cat.kpis.filter(k => k.is_scored).length }} scored KPIs
                    </div>
                </div>
            </div>
            <v-divider />

            <div class="table-scroll">
                <v-table density="compact" class="kpi-table">
                    <thead>
                        <tr>
                            <th class="kpi-th" style="width:28px;">#</th>
                            <th class="kpi-th">KPI</th>
                            <th class="kpi-th text-center" style="width:64px;">Weight</th>
                            <th class="kpi-th text-center" style="width:120px;">Target</th>
                            <th v-for="q in QUARTERS" :key="q" class="kpi-th text-center" style="width:104px;">
                                Q{{ q }} <v-icon v-if="!periodFor(q)?.open" size="11">mdi-lock-outline</v-icon>
                            </th>
                            <th class="kpi-th text-center" style="width:120px;">Annual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(kpi, idx) in cat.kpis" :key="kpi.id" class="kpi-row" :class="`kpi-row--${kpi.entry_type}`">
                            <td class="text-caption text-medium-emphasis text-center">{{ idx + 1 }}</td>
                            <td class="text-body-2 py-2" style="line-height:1.4;">
                                <div class="d-flex align-center gap-2 flex-wrap">
                                    <span>{{ kpi.name }}</span>
                                    <v-chip v-if="kpi.inverse_scoring" size="x-small" variant="tonal" color="orange">Inverse</v-chip>
                                    <v-chip v-if="!kpi.is_scored" size="x-small" variant="tonal" color="blue-grey">Input only</v-chip>
                                </div>
                                <div v-if="kpi.entry_type === 'derived'" class="text-caption text-medium-emphasis mt-1">
                                    Calculated from the ongoing and delinquent SETUP counts.
                                </div>
                                <div v-else-if="kpi.entry_type === 'record'" class="text-caption text-medium-emphasis mt-1">
                                    Counted from {{ kpi.module?.label ?? 'its records' }} by date.
                                    <a v-if="kpi.module && can_edit" href="#" class="module-link" @click.prevent="leave(kpi.module.url)">Manage {{ kpi.module.label.toLowerCase() }} →</a>
                                </div>
                                <div v-else-if="kpi.annual_only && !hasQuarterInput(kpi)" class="text-caption text-medium-emphasis mt-1">
                                    Reported as one annual figure. Entering quarters replaces it.
                                </div>
                            </td>
                            <td class="text-caption text-medium-emphasis text-center">
                                {{ kpi.is_scored ? (kpi.weight * 100).toFixed(1) + '%' : '-' }}
                            </td>

                            <!-- Target -->
                            <td>
                                <span v-if="kpi.entry_type === 'derived'" class="cell-na">n/a</span>
                                <input
                                    v-else
                                    v-model="values[kpi.id].target"
                                    type="number" min="0" step="any"
                                    class="num-input"
                                    :class="{ 'num-input--changed': isChanged(kpi.id, 'target') }"
                                    :disabled="!targetsEditable"
                                    :title="kpi.target_raw && String(kpi.target_raw) !== String(kpi.target) ? `Entered as: ${kpi.target_raw}` : ''"
                                />
                            </td>

                            <!-- Quarters -->
                            <td v-for="q in QUARTERS" :key="q">
                                <span v-if="kpi.entry_type === 'derived'" class="cell-na">n/a</span>
                                <span v-else-if="kpi.entry_type === 'record'" class="cell-count">{{ formatNumber(kpi.quarters[q]) }}</span>
                                <input
                                    v-else
                                    v-model="values[kpi.id][q]"
                                    type="number" min="0" step="any"
                                    class="num-input"
                                    :class="{ 'num-input--changed': isChanged(kpi.id, q) }"
                                    :disabled="!isQuarterEditable(q)"
                                />
                            </td>

                            <!-- Annual (rolled up) -->
                            <td class="text-center">
                                <span v-if="kpi.entry_type === 'derived'" class="cell-na">auto</span>
                                <template v-else>
                                    <div class="annual-value">{{ formatNumber(annualFor(kpi)) }}</div>
                                    <div class="annual-rule">{{ annualCaption(kpi) }}</div>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </v-table>
            </div>
        </v-card>

        <!-- Sticky save bar -->
        <div v-if="can_edit" class="save-bar">
            <span class="text-caption text-medium-emphasis">
                <template v-if="changedCount">{{ changedCount }} unsaved {{ changedCount === 1 ? 'change' : 'changes' }}</template>
                <template v-else>No pending changes</template>
                · Locked quarters can only be changed through a correction request.
            </span>
            <v-spacer />
            <v-btn variant="text" size="small" :disabled="!changedCount || saving" @click="reset">Discard</v-btn>
            <v-btn color="indigo" size="small" :loading="saving" :disabled="!changedCount" prepend-icon="mdi-content-save" @click="save">Save Changes</v-btn>
        </div>

    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { categoryVisual } from '@/Data/kpiCategories';
import { useKpiCatalogSync } from '@/Composables/useKpiCatalogSync';

const props = defineProps({
    province:       { type: Object,  required: true },
    director:       { type: Object,  default: null },
    year:           { type: Number,  required: true },
    years:          { type: Array,   default: () => [] },
    base_url:       { type: String,  required: true },
    kpi_categories: { type: Array,   default: () => [] },
    // [{ quarter, deadline, open, reopened_until, approval: { by, at, note } | null }]
    periods:        { type: Array,   default: () => [] },
    targets_open:   { type: Boolean, default: true },
    can_edit:       { type: Boolean, default: false },
    can_approve:    { type: Boolean, default: false },
    is_central:     { type: Boolean, default: false },
});

const QUARTERS = [1, 2, 3, 4];
const allKpis = computed(() => props.kpi_categories.flatMap(c => c.kpis));

// ── Editable state ────────────────────────────────────────────────────────
// kpi_id → { target, 1, 2, 3, 4 } as input strings, so an emptied box is ''.
const toInput = (v) => (v === null || v === undefined ? '' : String(v));
const snapshot = () => Object.fromEntries(allKpis.value.map(k => [k.id, {
    target: toInput(k.target),
    ...Object.fromEntries(QUARTERS.map(q => [q, toInput(k.quarters?.[q])])),
}]));

const initial = ref(snapshot());
const values  = ref(snapshot());

// Fresh props after a save or a year switch become the new baseline.
watch(() => props.kpi_categories, () => {
    initial.value = snapshot();
    values.value  = snapshot();
});

const same = (a, b) => (a === '' || a === null ? '' : Number(a)) === (b === '' || b === null ? '' : Number(b));
const isChanged = (kpiId, field) => !same(values.value[kpiId]?.[field], initial.value[kpiId]?.[field]);
const changedCount = computed(() =>
    allKpis.value.reduce((n, k) => n + ['target', ...QUARTERS].filter(f => isChanged(k.id, f)).length, 0)
);

const periodFor = (q) => props.periods.find(p => p.quarter === q);
const isQuarterEditable = (q) => props.can_edit && !!periodFor(q)?.open;
const targetsEditable = computed(() => props.can_edit && props.targets_open);

// ── Annual roll-up preview (mirrors KpiEntryService::combine) ─────────────
const RULE_LABELS = { sum: 'sum of quarters', latest: 'latest quarter', average: 'average of quarters' };

const hasQuarterInput = (kpi) => QUARTERS.some(q => values.value[kpi.id]?.[q] !== '');

const combine = (nums, rule) => {
    const present = nums.filter(v => v !== null);
    if (!present.length) return null;
    if (rule === 'latest')  return present[present.length - 1];
    if (rule === 'average') return present.reduce((a, b) => a + b, 0) / present.length;
    return present.reduce((a, b) => a + b, 0);
};

const annualFor = (kpi) => {
    if (kpi.entry_type === 'record') return kpi.annual;
    if (kpi.annual_only && !hasQuarterInput(kpi)) return kpi.annual;
    const nums = QUARTERS.map(q => (values.value[kpi.id][q] === '' ? null : Number(values.value[kpi.id][q])));
    return combine(nums, kpi.rollup_rule);
};
const annualCaption = (kpi) => (kpi.annual_only && !hasQuarterInput(kpi) ? 'annual figure' : RULE_LABELS[kpi.rollup_rule] ?? RULE_LABELS.sum);

const formatNumber = (v) => {
    if (v === null || v === undefined || v === '') return '—';
    const n = Number(v);
    return Number.isNaN(n) ? String(v) : n.toLocaleString(undefined, { maximumFractionDigits: 2 });
};

// ── Period display ───────────────────────────────────────────────────────
// Date-only values ("2026-03-31") are read as local dates, not UTC midnight.
const formatDate = (s) => new Date(s.length === 10 ? `${s}T00:00:00` : s.replace(' ', 'T'))
    .toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });

const periodState = (p) => {
    if (p.reopened_until) return { label: 'Reopened', color: 'warning', icon: 'mdi-lock-open-alert-outline', detail: `Correction approved · open until ${formatDate(p.reopened_until)}` };
    if (!p.open)          return { label: 'Locked',   color: 'blue-grey', icon: 'mdi-lock-outline', detail: `Deadline passed ${formatDate(p.deadline)}` };
    if (p.deadline)       return { label: 'Open',     color: 'success', icon: 'mdi-lock-open-variant-outline', detail: `Due ${formatDate(p.deadline)}` };
    return { label: 'Open', color: 'success', icon: 'mdi-lock-open-variant-outline', detail: 'No deadline set yet' };
};

const quarterHasData = (q) => allKpis.value.some(k => k.quarters?.[q] !== null && k.quarters?.[q] !== undefined);

// ── Actions ──────────────────────────────────────────────────────────────
const saving    = ref(false);
const approving = ref(null);

// Sends only what changed, and only for periods that are open.
const save = () => {
    const targets  = {};
    const quarters = {};
    for (const k of allKpis.value) {
        if (k.entry_type === 'derived') continue;
        // Record-backed KPIs take a target; their quarters come from the records.
        if (targetsEditable.value && isChanged(k.id, 'target')) targets[k.id] = values.value[k.id].target === '' ? null : values.value[k.id].target;
        if (k.entry_type !== 'entry') continue;
        for (const q of QUARTERS) {
            if (!isQuarterEditable(q) || !isChanged(k.id, q)) continue;
            (quarters[q] ??= {})[k.id] = values.value[k.id][q] === '' ? null : values.value[k.id][q];
        }
    }
    router.put(`${props.base_url}/${props.year}`, { targets, quarters }, {
        preserveScroll: true,
        onStart:  () => { saving.value = true; },
        onFinish: () => { saving.value = false; },
    });
};

const reset = () => { values.value = JSON.parse(JSON.stringify(initial.value)); };

const approve = (q) => {
    if (!confirm(`Approve Q${q} ${props.year} figures for ${props.province.name}? Any later change to this quarter clears the approval.`)) return;
    router.post(`/provincial-kpi/${props.year}/quarters/${q}/approve`, {}, {
        preserveScroll: true,
        onStart:  () => { approving.value = q; },
        onFinish: () => { approving.value = null; },
    });
};

const leave = (url) => {
    if (changedCount.value && !confirm('You have unsaved changes. Leave this page and discard them?')) return;
    router.visit(url);
};
const changeYear = (yr) => { if (yr !== props.year) leave(`${props.base_url}/${yr}`); };

// ── Visuals ──────────────────────────────────────────────────────────────
const tierColor = (t) => ({ micro: 'blue-grey', small: 'teal', medium: 'indigo', large: 'deep-purple' }[t] ?? 'grey');
const catVisual = (code) => categoryVisual(code, Math.max(0, props.kpi_categories.findIndex(c => c.code === code)));

// A catalog edit (names, weights, roll-up rules) refreshes the matrix — but
// never over unsaved local entries.
useKpiCatalogSync(['kpi_categories'], { canReload: () => !changedCount.value });
</script>

<style scoped>
.filter-label { font-size: 11px; font-weight: 600; color: #64748b; letter-spacing: 0.02em; text-transform: uppercase; }
.segmented { display: inline-flex; gap: 2px; padding: 3px; background: #f1f5f9; border-radius: 9px; border: 1px solid #e2e8f0; flex-wrap: wrap; }
.segmented-btn { appearance: none; border: none; background: transparent; color: #64748b; font-size: 12px; font-weight: 500; padding: 5px 12px; border-radius: 6px; cursor: pointer; font-family: inherit; }
.segmented-btn:hover:not(.active) { color: #0f172a; background: rgba(255,255,255,0.6); }
.segmented-btn.active { background: #fff; color: #0f172a; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.06); }

.period-strip { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 10px; padding: 12px 16px; background: #f8fafc; }
.period-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; }
.period-card--targets { background: #fafaff; }
.period-name { font-weight: 700; font-size: 15px; color: #0f172a; }

.table-scroll { overflow-x: auto; }
.kpi-th { font-size: 0.65rem !important; font-weight: 700 !important; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(var(--v-theme-on-surface), 0.5) !important; background: rgba(var(--v-theme-surface-variant), 0.4) !important; padding: 8px 10px !important; white-space: nowrap; }
.kpi-row td { padding: 6px 8px !important; border-bottom: 1px solid rgba(var(--v-border-color), 0.4) !important; vertical-align: middle; }
.kpi-row--derived { background: #fafbfc; }
.kpi-row--record  { background: #f0fdfa; }

.num-input { width: 100%; min-width: 80px; height: 30px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; font-family: inherit; font-size: 13px; text-align: right; }
.num-input:focus { outline: 2px solid #6366f1; outline-offset: -1px; border-color: #6366f1; }
.num-input:disabled { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; cursor: not-allowed; }
.num-input--changed { border-color: #f59e0b; background: #fffbeb; }
.cell-na    { display: block; text-align: center; font-size: 12px; color: #94a3b8; }
.cell-count { display: block; text-align: right; padding-right: 8px; font-size: 13px; font-weight: 600; color: #0f766e; }
.annual-value { font-weight: 700; font-size: 14px; color: #0f172a; }
.annual-rule  { font-size: 11px; color: #94a3b8; line-height: 1.1; }
.module-link { color: #0d9488; font-weight: 600; text-decoration: none; margin-left: 4px; }
.module-link:hover { text-decoration: underline; }

.save-bar { position: sticky; bottom: 0; display: flex; align-items: center; gap: 12px; padding: 10px 16px; background: rgba(255,255,255,0.97); border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 -2px 12px rgba(0,0,0,0.04); z-index: 5; margin-top: 8px; }
</style>
