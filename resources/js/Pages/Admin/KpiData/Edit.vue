<template>
    <Head :title="`Edit KPI Data - ${province.name}`" />
    <div class="d-flex flex-column gap-3">

        <!-- Breadcrumb -->
        <div class="d-flex align-center gap-2">
            <v-btn icon size="x-small" variant="text" color="medium-emphasis" @click="router.visit('/kpi-data')">
                <v-icon size="18">mdi-arrow-left</v-icon>
            </v-btn>
            <span class="text-caption text-medium-emphasis cursor-pointer" @click="router.visit('/kpi-data')">KPI Data Editor</span>
            <v-icon size="12" color="medium-emphasis">mdi-chevron-right</v-icon>
            <span class="text-caption font-weight-medium">{{ province.name }}</span>
            <v-spacer />
            <v-btn
                size="small"
                variant="text"
                color="indigo"
                prepend-icon="mdi-archive-outline"
                @click="router.visit('/kpi-catalog')"
            >Manage KPI Catalog</v-btn>
        </div>

        <!-- Header card: province + director identity + year picker -->
        <v-card border elevation="0" rounded="lg" class="overflow-hidden">
            <div class="d-flex flex-wrap">
                <div class="pa-4 d-flex align-center gap-3 flex-grow-1">
                    <v-avatar :color="tierColor(province.category)" size="44" rounded="lg">
                        <v-icon color="white" size="22">mdi-map-marker-outline</v-icon>
                    </v-avatar>
                    <div>
                        <div class="text-h6 font-weight-bold">{{ province.name }}</div>
                        <div class="d-flex align-center gap-2 mt-1">
                            <v-chip :color="tierColor(province.category)" size="x-small" variant="tonal" class="font-weight-medium text-uppercase">
                                {{ province.category }}
                            </v-chip>
                            <span class="text-caption text-medium-emphasis">·</span>
                            <span class="text-caption text-medium-emphasis">{{ director.name }}</span>
                        </div>
                    </div>
                </div>

                <v-divider vertical />

                <div class="pa-4 d-flex align-center gap-3">
                    <div>
                        <div class="filter-label mb-1">Reporting Year</div>
                        <div class="d-flex align-center gap-2">
                            <div class="segmented">
                                <button
                                    v-for="yr in allYears" :key="yr"
                                    class="segmented-btn"
                                    :class="{ active: selectedYear === yr }"
                                    @click="changeYear(yr)"
                                >{{ yr }}</button>
                            </div>
                            <v-menu>
                                <template #activator="{ props: tip }">
                                    <v-btn v-bind="tip" size="small" variant="outlined" prepend-icon="mdi-plus" class="ms-1">
                                        Add year
                                    </v-btn>
                                </template>
                                <v-list density="compact">
                                    <v-list-item
                                        v-for="yr in addableYears" :key="yr"
                                        @click="changeYear(yr)"
                                    >
                                        <v-list-item-title>{{ yr }}</v-list-item-title>
                                    </v-list-item>
                                </v-list>
                            </v-menu>
                        </div>
                    </div>
                </div>
            </div>
            <v-divider />
            <div class="px-4 py-2 d-flex align-center gap-3 flex-wrap" style="background:#f8fafc;">
                <span class="text-caption text-medium-emphasis">
                    Editing <strong>{{ selectedYear }}</strong>. Empty fields are saved as "no data" (count as 0 in scoring).
                </span>
                <v-spacer />
                <v-chip v-if="form.recentlySuccessful" size="small" color="success" variant="tonal" prepend-icon="mdi-check-circle">
                    Saved
                </v-chip>
                <v-btn
                    color="indigo"
                    size="small"
                    :loading="form.processing"
                    :disabled="!isDirty"
                    prepend-icon="mdi-content-save"
                    @click="save"
                >Save Changes</v-btn>
            </div>
        </v-card>

        <!-- Category sections -->
        <v-card
            v-for="cat in form.categories"
            :key="cat.id"
            border elevation="0" rounded="lg"
        >
            <div class="d-flex align-center gap-2 px-4 py-3" :style="{ background: catBg(cat.code) }">
                <v-avatar :color="catColor(cat.code)" size="30" rounded="md">
                    <v-icon size="16" color="white">{{ catIcon(cat.code) }}</v-icon>
                </v-avatar>
                <div>
                    <div class="text-subtitle-2 font-weight-bold">{{ cat.name }}</div>
                    <div class="text-caption text-medium-emphasis">
                        Weight: <strong>{{ (cat.weight * 100).toFixed(0) }}%</strong>
                        · {{ cat.kpis.filter(k => k.is_scored).length }} scored KPIs
                    </div>
                </div>
            </div>
            <v-divider />

            <v-table density="compact" class="kpi-edit-table">
                <thead>
                    <tr>
                        <th class="kpi-th" style="width:28px;">#</th>
                        <th class="kpi-th">KPI</th>
                        <th class="kpi-th" style="width:70px; text-align:center;">Weight</th>
                        <th class="kpi-th" style="width:160px; text-align:center;">Target</th>
                        <th class="kpi-th" style="width:160px; text-align:center;">Accomplished</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(kpi, idx) in cat.kpis" :key="kpi.id" class="kpi-row" :class="{ 'kpi-row--input-only': !kpi.is_scored, 'kpi-row--modular': isModular(kpi.code) }">
                        <td class="text-caption text-medium-emphasis text-center">{{ idx + 1 }}</td>
                        <td class="text-body-2 py-2" style="line-height:1.45;">
                            <div class="d-flex align-center gap-2 flex-wrap">
                                <span>{{ kpi.name }}</span>
                                <v-chip v-if="kpi.inverse_scoring" size="x-small" variant="tonal" color="orange" class="font-weight-medium">Inverse</v-chip>
                                <v-chip v-if="!kpi.is_scored" size="x-small" variant="tonal" color="blue-grey" class="font-weight-medium">Input only</v-chip>
                                <v-chip v-if="kpi.derivation_type === 'delinquent_ratio'" size="x-small" variant="tonal" color="indigo" class="font-weight-medium">Auto-derived</v-chip>
                                <v-chip v-if="isModular(kpi.code)" size="x-small" variant="tonal" color="teal" class="font-weight-medium">
                                    <v-icon size="12" start>mdi-shield-check-outline</v-icon>
                                    Record-based
                                </v-chip>
                            </div>
                            <div v-if="kpi.derivation_type === 'delinquent_ratio'" class="text-caption text-medium-emphasis mt-1">
                                Computed from <em>ongoing</em> and <em>delinquent</em> SETUP counts below. Leave Target / Accomplished blank.
                            </div>
                            <div v-if="isModular(kpi.code)" class="text-caption text-medium-emphasis mt-1">
                                Accomplishment is the live record count from the linked module - cannot be typed.
                            </div>
                        </td>
                        <td class="text-caption text-medium-emphasis text-center">
                            <span v-if="kpi.is_scored">{{ (kpi.weight * 100).toFixed(1) }}%</span>
                            <span v-else>-</span>
                        </td>
                        <td>
                            <v-text-field
                                v-model="kpi.target"
                                :placeholder="kpi.derivation_type ? 'n/a' : ''"
                                :disabled="kpi.derivation_type === 'delinquent_ratio'"
                                variant="solo-filled"
                                density="compact"
                                hide-details
                                class="kpi-input"
                            />
                        </td>
                        <td>
                            <button
                                v-if="isModular(kpi.code)"
                                type="button"
                                class="modular-count"
                                @click="openModule(kpi.code)"
                                :title="`Open ${kpi.name} records`"
                            >
                                <v-icon size="14" class="modular-count__icon">mdi-counter</v-icon>
                                <span class="modular-count__value">{{ kpi.accomplished || 0 }}</span>
                                <v-icon size="14" class="modular-count__chevron">mdi-arrow-top-right</v-icon>
                            </button>
                            <v-text-field
                                v-else
                                v-model="kpi.accomplished"
                                :placeholder="kpi.derivation_type ? 'n/a' : ''"
                                :disabled="kpi.derivation_type === 'delinquent_ratio'"
                                variant="solo-filled"
                                density="compact"
                                hide-details
                                class="kpi-input"
                            />
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>

        <!-- Sticky save bar at bottom -->
        <div class="save-bar">
            <span class="text-caption text-medium-emphasis">
                <template v-if="isDirty">Unsaved changes</template>
                <template v-else>No pending changes</template>
            </span>
            <v-spacer />
            <v-btn
                variant="text"
                size="small"
                :disabled="!isDirty || form.processing"
                @click="reset"
            >Discard</v-btn>
            <v-btn
                color="indigo"
                size="small"
                :loading="form.processing"
                :disabled="!isDirty"
                prepend-icon="mdi-content-save"
                @click="save"
            >Save Changes</v-btn>
        </div>

    </div>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { useEcho } from '@laravel/echo-vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    province:          { type: Object, required: true },
    director:          { type: Object, required: true },
    year:              { type: Number, required: true },
    available_years:   { type: Array,  default: () => [] },  // years across the whole system
    director_years:    { type: Array,  default: () => [] },  // years this director already has data for
    kpi_categories:    { type: Array,  default: () => [] },
    modular_kpi_codes: { type: Object, default: () => ({}) },  // { kpi_code: '/module/path' }
});

const isModular = (code) => Object.prototype.hasOwnProperty.call(props.modular_kpi_codes, code);

const openModule = (code) => {
    const base = props.modular_kpi_codes[code];
    if (!base) return;
    if (isDirty.value && !confirm('You have unsaved changes. Discard and open the module?')) return;
    router.visit(`${base}/${props.director.id}/${selectedYear.value}`);
};

const selectedYear = ref(props.year);

// Deep-clone categories into a form object so v-model works on each input
// without mutating the original prop. Inertia's useForm tracks dirtiness for us.
const form = useForm({
    categories: JSON.parse(JSON.stringify(props.kpi_categories)),
});

// Snapshot of the initial state so we can compute dirtiness against it
const initialSnapshot = JSON.stringify(props.kpi_categories);

const isDirty = computed(() => {
    return JSON.stringify(form.categories) !== initialSnapshot;
});

const allYears = computed(() => {
    const set = new Set(props.director_years);
    set.add(selectedYear.value);
    return [...set].sort((a, b) => b - a);
});

const addableYears = computed(() => {
    const directorSet = new Set(props.director_years);
    const startYear = 2022;
    const endYear   = new Date().getFullYear();
    const out = [];
    for (let y = endYear; y >= startYear; y--) {
        if (!directorSet.has(y) && y !== selectedYear.value) out.push(y);
    }
    return out;
});

const tierColor = (cat) => ({ micro: 'blue-grey', small: 'teal', medium: 'indigo', large: 'deep-purple' }[cat] ?? 'grey');
const catColor  = (code) => ({ CORE: 'indigo', STRATEGIC: 'teal', SUPPORT: 'deep-purple' }[code] ?? 'blue-grey');
const catBg     = (code) => ({ CORE: '#eef2ff', STRATEGIC: '#e0f2f1', SUPPORT: '#ede7f6' }[code] ?? '#f1f5f9');
const catIcon   = (code) => ({ CORE: 'mdi-rocket-launch-outline', STRATEGIC: 'mdi-shield-outline', SUPPORT: 'mdi-cog-outline' }[code] ?? 'mdi-chart-bar');

const save = () => {
    const values = [];
    for (const cat of form.categories) {
        for (const kpi of cat.kpis) {
            values.push({
                kpi_id:       kpi.id,
                target:       kpi.target ?? '',
                accomplished: kpi.accomplished ?? '',
            });
        }
    }
    form
        .transform(() => ({ values }))
        .put(`/kpi-data/${props.director.id}/${selectedYear.value}`, {
            preserveScroll: true,
            preserveState:  true,
        });
};

const reset = () => {
    form.categories = JSON.parse(initialSnapshot);
};

const changeYear = (yr) => {
    if (yr === selectedYear.value) return;
    if (isDirty.value && !confirm('You have unsaved changes. Discard and switch year?')) return;
    router.visit(`/kpi-data/${props.province.id}/${yr}`);
};

// Keep selectedYear in sync if the prop changes after navigation
watch(() => props.year, (y) => { selectedYear.value = y; });

// Real-time: the KPI catalog (name/category/weight) is managed on a separate,
// general page (/kpi-catalog) — a change made there affects every province's
// live score, so refresh this screen's weights/categories to match.
const { leaveChannel } = useEcho('kpi-catalog', 'kpi.catalog.updated', () => {
    if (isDirty.value) return; // don't clobber unsaved local edits
    router.reload({ only: ['kpi_categories'], preserveScroll: true, preserveState: true });
});
onBeforeUnmount(() => leaveChannel());
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
.segmented-btn:hover:not(.active) { color: #0f172a; background: rgba(255, 255, 255, 0.6); }
.segmented-btn.active {
    background: #ffffff;
    color: #0f172a;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);
}

.kpi-th {
    font-size: 0.65rem !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(var(--v-theme-on-surface), 0.5) !important;
    background: rgba(var(--v-theme-surface-variant), 0.4) !important;
    padding: 8px 12px !important;
    white-space: nowrap;
}
.kpi-row td {
    padding: 6px 12px !important;
    border-bottom: 1px solid rgba(var(--v-border-color), 0.4) !important;
    vertical-align: middle;
}
.kpi-row--input-only {
    background: #fafbfc;
}
.kpi-row--modular {
    background: #f0fdfa;
}

.modular-count {
    appearance: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    width: 100%;
    min-height: 32px;
    padding: 4px 12px;
    background: #ffffff;
    border: 1px solid #14b8a6;
    border-radius: 8px;
    color: #0f766e;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.2;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, transform 0.1s ease, box-shadow 0.15s ease;
    text-align: left;
}
.modular-count:hover {
    background: #ccfbf1;
    border-color: #0d9488;
    color: #134e4a;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(13, 148, 136, 0.18), 0 1px 2px rgba(13, 148, 136, 0.1);
}
.modular-count:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(13, 148, 136, 0.18);
}
.modular-count:focus-visible {
    outline: 2px solid #0d9488;
    outline-offset: 2px;
}
.modular-count__icon {
    color: #0d9488;
    flex-shrink: 0;
}
.modular-count__value {
    flex-grow: 1;
    text-align: center;
    font-size: 14px;
    text-decoration: underline;
    text-decoration-color: rgba(13, 148, 136, 0.4);
    text-underline-offset: 3px;
}
.modular-count:hover .modular-count__value {
    text-decoration-color: #0d9488;
}
.modular-count__chevron {
    color: #0d9488;
    flex-shrink: 0;
    transition: transform 0.15s ease;
}
.modular-count:hover .modular-count__chevron {
    transform: translate(2px, -2px);
}
.kpi-input :deep(.v-field) {
    background: #fff;
    border-radius: 6px;
    font-size: 12px;
}
.kpi-input :deep(.v-field__input) {
    min-height: 28px;
    padding: 4px 10px;
}

.save-bar {
    position: sticky;
    bottom: 0;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    background: rgba(255,255,255,0.97);
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 -2px 12px rgba(0,0,0,0.04);
    backdrop-filter: blur(4px);
    z-index: 5;
    margin-top: 8px;
}
</style>
