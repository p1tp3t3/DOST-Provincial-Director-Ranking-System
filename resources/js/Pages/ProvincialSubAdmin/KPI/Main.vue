<template>
    <Head title="KPI Data Editor" />
    <div class="d-flex flex-column gap-3">

        <!-- No director state -->
        <v-card v-if="no_director" border elevation="0" rounded="lg">
            <div class="text-center py-12">
                <v-icon size="48" color="grey-lighten-2" class="mb-3">mdi-account-off-outline</v-icon>
                <div class="text-body-1 font-weight-medium mb-1">No Provincial Director Assigned</div>
                <div class="text-caption text-medium-emphasis">
                    Your province does not have a director yet. Contact the system administrator.
                </div>
            </div>
        </v-card>

        <template v-else>
            <!-- Header card -->
            <v-card border elevation="0" rounded="lg" class="overflow-hidden">
                <div class="d-flex flex-wrap">
                    <div class="pa-4 d-flex align-center gap-3 flex-grow-1">
                        <v-avatar color="teal" size="44" rounded="lg">
                            <v-icon color="white" size="22">mdi-table-edit</v-icon>
                        </v-avatar>
                        <div>
                            <div class="text-h6 font-weight-bold">KPI Data Editor</div>
                            <div class="d-flex align-center gap-2 mt-1">
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
                        Editing <strong>{{ selectedYear }}</strong>. Empty fields are saved as "no data".
                    </span>
                    <v-spacer />
                    <v-chip v-if="form.recentlySuccessful" size="small" color="success" variant="tonal" prepend-icon="mdi-check-circle">
                        Saved
                    </v-chip>
                    <v-btn
                        color="teal"
                        size="small"
                        :loading="form.processing"
                        :disabled="!isDirty || edit_access.locked"
                        prepend-icon="mdi-content-save"
                        @click="openConfirmDialog"
                    >Save Changes</v-btn>
                </div>
            </v-card>

            <!-- Edit-access lock banner -->
            <v-alert
                v-if="edit_access.locked"
                type="warning" variant="tonal" density="compact" rounded="lg"
            >
                <div class="d-flex align-center flex-wrap gap-2">
                    <span class="text-body-2">
                        You've used this year's free edit for {{ selectedYear }}.
                        <template v-if="edit_access.pending_request">
                            Request sent — waiting on your regional admin.
                        </template>
                        <template v-else-if="edit_access.last_request?.status === 'rejected'">
                            Last request was rejected{{ edit_access.last_request.response_note ? `: ${edit_access.last_request.response_note}` : '.' }}
                        </template>
                    </span>
                    <v-btn
                        v-if="!edit_access.pending_request"
                        size="x-small" variant="outlined" color="warning"
                        @click="openRequestDialog"
                    >Request Access</v-btn>
                </div>
            </v-alert>

            <!-- Category sections -->
            <v-card
                v-for="cat in categories"
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
                                    Computed from <em>ongoing</em> and <em>delinquent</em> SETUP counts below.
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
                                    :disabled="kpi.derivation_type === 'delinquent_ratio' || edit_access.locked"
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
                                    :disabled="kpi.derivation_type === 'delinquent_ratio' || edit_access.locked"
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

            <!-- Sticky save bar -->
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
                    color="teal"
                    size="small"
                    :loading="form.processing"
                    :disabled="!isDirty || edit_access.locked"
                    prepend-icon="mdi-content-save"
                    @click="openConfirmDialog"
                >Save Changes</v-btn>
            </div>
        </template>

        <!-- Confirm changes dialog -->
        <v-dialog v-model="confirmDialogOpen" max-width="560">
            <v-card rounded="lg">
                <v-card-title class="text-subtitle-1 font-weight-bold">Confirm KPI Changes</v-card-title>
                <v-card-text>
                    <p class="text-body-2 text-medium-emphasis mb-3">
                        Please review the values below before saving — this may use your free edit for {{ selectedYear }} if it hasn't been used yet.
                    </p>
                    <div class="confirm-diff">
                        <div v-for="group in pendingChanges" :key="group.code" class="confirm-diff-group">
                            <div class="confirm-diff-group-title" :style="{ background: catBg(group.code) }">
                                <v-icon size="14" :color="catColor(group.code)">{{ catIcon(group.code) }}</v-icon>
                                <span>{{ group.name }}</span>
                            </div>
                            <div v-for="(c, i) in group.changes" :key="i" class="confirm-diff-row">
                                <div class="confirm-diff-kpi">{{ c.kpi }}</div>
                                <div class="confirm-diff-fields">
                                    <div v-for="(f, fi) in c.fields" :key="fi" class="confirm-diff-field">
                                        <v-chip size="x-small" variant="tonal" class="confirm-diff-chip" :color="f.field === 'Target' ? 'indigo' : 'teal'">{{ f.field }}</v-chip>
                                        <span class="text-caption text-medium-emphasis">{{ f.old }}</span>
                                        <v-icon size="14">mdi-arrow-right</v-icon>
                                        <span class="text-caption font-weight-bold">{{ f.new }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="!pendingChanges.length" class="text-caption text-medium-emphasis">
                            No changed values detected.
                        </div>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="confirmDialogOpen = false">Cancel</v-btn>
                    <v-btn color="teal" @click="confirmAndSave">Yes, Save Changes</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Request access dialog -->
        <v-dialog v-model="requestDialogOpen" max-width="440">
            <v-card rounded="lg">
                <v-card-title class="text-subtitle-1 font-weight-bold">Request edit access</v-card-title>
                <v-card-text>
                    <p class="text-body-2 text-medium-emphasis mb-3">
                        This sends a request to your regional admin to allow one more edit for <strong>{{ selectedYear }}</strong>.
                    </p>
                    <v-textarea
                        v-model="requestForm.reason"
                        label="Reason (optional)"
                        variant="outlined"
                        density="compact"
                        rows="3"
                        hide-details
                    />
                    <div v-if="requestForm.errors.edit_access" class="text-caption text-error mt-2">
                        {{ requestForm.errors.edit_access }}
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="requestDialogOpen = false">Cancel</v-btn>
                    <v-btn color="teal" :loading="requestForm.processing" @click="submitRequest">Send Request</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    director:          { type: Object, default: () => ({}) },
    year:              { type: Number, default: null },
    available_years:   { type: Array,  default: () => [] },
    director_years:    { type: Array,  default: () => [] },
    kpi_categories:    { type: Array,  default: () => [] },
    modular_kpi_codes: { type: Object, default: () => ({}) },
    no_director:       { type: Boolean, default: false },
    edit_access:       {
        type: Object,
        default: () => ({ locked: false, pending_request: null, last_request: null }),
    },
});

const isModular = (code) => Object.prototype.hasOwnProperty.call(props.modular_kpi_codes, code);

const openModule = (code) => {
    const base = props.modular_kpi_codes[code];
    if (!base) return;
    if (isDirty.value && !confirm('You have unsaved changes. Discard and open the module?')) return;
    router.visit(`${base}/${props.director.id}/${selectedYear.value}`);
};

const selectedYear = ref(props.year);

// Live editable state — bound directly by v-model. initialSnapshot is the fixed
// point dirtiness is compared against; it's resynced whenever the server sends
// back fresh kpi_categories (e.g. right after a successful save).
const categories = ref(JSON.parse(JSON.stringify(props.kpi_categories)));
let initialSnapshot = JSON.stringify(props.kpi_categories);
const isDirty = computed(() => JSON.stringify(categories.value) !== initialSnapshot);

const allYears = computed(() => {
    const set = new Set(props.director_years);
    if (selectedYear.value) set.add(selectedYear.value);
    return [...set].sort((a, b) => b - a);
});

const addableYears = computed(() => {
    const existing = new Set(props.director_years);
    const end = new Date().getFullYear();
    const out = [];
    for (let y = end; y >= 2022; y--) {
        if (!existing.has(y) && y !== selectedYear.value) out.push(y);
    }
    return out;
});

const catColor = (code) => ({ CORE: 'indigo', STRATEGIC: 'teal', SUPPORT: 'deep-purple' }[code] ?? 'blue-grey');
const catBg    = (code) => ({ CORE: '#eef2ff', STRATEGIC: '#e0f2f1', SUPPORT: '#ede7f6' }[code] ?? '#f1f5f9');
const catIcon  = (code) => ({ CORE: 'mdi-rocket-launch-outline', STRATEGIC: 'mdi-shield-outline', SUPPORT: 'mdi-cog-outline' }[code] ?? 'mdi-chart-bar');

const form = useForm({});

const save = () => {
    const values = [];
    for (const cat of categories.value) {
        for (const kpi of cat.kpis) {
            values.push({
                kpi_id:       kpi.id,
                target:       kpi.target       ?? '',
                accomplished: kpi.accomplished ?? '',
            });
        }
    }
    form
        .transform(() => ({ values }))
        .put(`/provincial-kpi/${props.director.id}/${selectedYear.value}`, {
            preserveScroll: true,
            preserveState:  true,
        });
};

// Diff shown in the confirmation dialog so the provincial admin can double-check
// values before they're committed (and before the free/granted edit is spent).
// Grouped by category (Core / Functional / Support); within a category, one
// entry per KPI — a KPI with both Target and Accomplished changed lists both
// fields under the same row.
const pendingChanges = computed(() => {
    const groups = [];
    for (let ci = 0; ci < categories.value.length; ci++) {
        const cat     = categories.value[ci];
        const origCat = props.kpi_categories[ci];
        const changes = [];

        for (let ki = 0; ki < cat.kpis.length; ki++) {
            const kpi     = cat.kpis[ki];
            const origKpi = origCat?.kpis?.[ki];
            if (!origKpi) continue;

            const fields = [];

            const oldTarget = origKpi.target ?? '';
            const newTarget = kpi.target ?? '';
            if (oldTarget !== newTarget) {
                fields.push({ field: 'Target', old: oldTarget || '—', new: newTarget || '—' });
            }

            const oldAccomplished = origKpi.accomplished ?? '';
            const newAccomplished = kpi.accomplished ?? '';
            if (!isModular(kpi.code) && oldAccomplished !== newAccomplished) {
                fields.push({ field: 'Accomplished', old: oldAccomplished || '—', new: newAccomplished || '—' });
            }

            if (fields.length) {
                changes.push({ kpi: kpi.name, fields });
            }
        }

        if (changes.length) {
            groups.push({ code: cat.code, name: cat.name, changes });
        }
    }
    return groups;
});

const confirmDialogOpen = ref(false);
const openConfirmDialog = () => { confirmDialogOpen.value = true; };
const confirmAndSave = () => {
    confirmDialogOpen.value = false;
    save();
};

const reset = () => {
    categories.value = JSON.parse(initialSnapshot);
};

const changeYear = (yr) => {
    if (yr === selectedYear.value) return;
    if (isDirty.value && !confirm('You have unsaved changes. Discard and switch year?')) return;
    router.visit(`/provincial-kpi/${yr}`);
};

watch(() => props.year, (y) => { selectedYear.value = y; });
watch(() => props.kpi_categories, (v) => {
    categories.value = JSON.parse(JSON.stringify(v));
    initialSnapshot = JSON.stringify(v);
});

// ── Request access ──────────────────────────────────────────────────────────
const requestDialogOpen = ref(false);
const requestForm = useForm({ reason: '' });

const openRequestDialog = () => {
    requestForm.reason = '';
    requestDialogOpen.value = true;
};

const submitRequest = () => {
    requestForm.post(`/provincial-kpi/${props.director.id}/${selectedYear.value}/request-access`, {
        preserveScroll: true,
        onSuccess: () => { requestDialogOpen.value = false; },
    });
};
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
.segmented-btn:hover:not(.active) { color: #0f172a; background: rgba(255,255,255,0.6); }
.segmented-btn.active {
    background: #ffffff;
    color: #0f172a;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.04);
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
.kpi-row--input-only { background: #fafbfc; }
.kpi-row--modular { background: #f0fdfa; }

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
.modular-count__icon { color: #0d9488; flex-shrink: 0; }
.modular-count__value {
    flex-grow: 1;
    text-align: center;
    font-size: 14px;
    text-decoration: underline;
    text-decoration-color: rgba(13, 148, 136, 0.4);
    text-underline-offset: 3px;
}
.modular-count:hover .modular-count__value { text-decoration-color: #0d9488; }
.modular-count__chevron { color: #0d9488; flex-shrink: 0; transition: transform 0.15s ease; }
.modular-count:hover .modular-count__chevron { transform: translate(2px, -2px); }
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
.confirm-diff {
    max-height: 320px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.confirm-diff-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.confirm-diff-group-title {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    align-self: flex-start;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.confirm-diff-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fafbfc;
}
.confirm-diff-kpi {
    flex: 1 1 auto;
    min-width: 0;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.4;
}
.confirm-diff-fields {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
    flex-shrink: 0;
}
.confirm-diff-field {
    display: flex;
    align-items: center;
    gap: 8px;
}
.confirm-diff-chip {
    min-width: 92px;
    justify-content: center;
}
</style>
