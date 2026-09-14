<template>
    <Head title="KPI Catalog" />
    <div class="d-flex flex-column gap-3">

        <!-- Header -->
        <v-card border elevation="0" rounded="lg">
            <div class="d-flex align-center justify-space-between gap-3 px-4 py-3 flex-wrap">
                <div>
                    <div class="text-h6 font-weight-bold">KPI Catalog</div>
                    <div class="text-caption text-medium-emphasis">
                        The shared matrix used to score every province. Managed here as one thing —
                        setting a specific province's target/accomplished still happens in the
                        <a href="/kpi-data" class="text-indigo" @click.prevent="router.visit('/kpi-data')">KPI Data Editor</a>.
                    </div>
                </div>
                <div class="d-flex align-center gap-2">
                    <v-btn
                        size="small"
                        variant="outlined"
                        color="indigo"
                        prepend-icon="mdi-file-upload-outline"
                        @click="openCsvDialog"
                    >Bulk Add via CSV</v-btn>
                </div>
            </div>
            <v-divider />
            <div class="px-4 py-2 d-flex align-center gap-3 flex-wrap" style="background:#f8fafc;">
                <span class="text-caption text-medium-emphasis">
                    Editing the shared catalog. Weight/category changes affect every province's live ranking immediately.
                </span>
                <v-spacer />
                <v-chip v-if="justSaved" size="small" color="success" variant="tonal" prepend-icon="mdi-check-circle">
                    Saved
                </v-chip>
                <v-btn
                    color="indigo"
                    size="small"
                    :disabled="!isDirty"
                    prepend-icon="mdi-content-save-check-outline"
                    @click="openPreview"
                >Update</v-btn>
            </div>
        </v-card>

        <!-- Category sections -->
        <v-card
            v-for="cat in form"
            :key="cat.id"
            border elevation="0" rounded="lg"
        >
            <div class="d-flex align-center gap-2 px-4 py-3" :style="{ background: catBg(cat.code) }">
                <v-avatar :color="catColor(cat.code)" size="30" rounded="md">
                    <v-icon size="16" color="white">{{ catIcon(cat.code) }}</v-icon>
                </v-avatar>
                <div class="flex-grow-1">
                    <div class="text-subtitle-2 font-weight-bold">{{ cat.name }}</div>
                    <div class="text-caption text-medium-emphasis">
                        Weight: <strong>{{ (cat.weight * 100).toFixed(0) }}%</strong>
                        · {{ cat.kpis.filter(k => Number(k.weightPercent) > 0).length }} scored KPIs
                    </div>
                </div>
                <v-btn
                    size="small"
                    variant="tonal"
                    color="indigo"
                    prepend-icon="mdi-plus"
                    @click="openAddDialog(cat.id)"
                >Add KPI</v-btn>
            </div>
            <v-divider />

            <v-table density="compact" class="kpi-catalog-table">
                <thead>
                    <tr>
                        <th class="kpi-th" style="width:28px;">#</th>
                        <th class="kpi-th">KPI</th>
                        <th class="kpi-th" style="width:150px;">Category</th>
                        <th class="kpi-th" style="width:110px; text-align:center;">Weight %</th>
                        <th class="kpi-th" style="width:40px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(kpi, idx) in cat.kpis" :key="kpi.id" class="kpi-row" :class="{ 'kpi-row--input-only': Number(kpi.weightPercent) === 0 }">
                        <td class="text-caption text-medium-emphasis text-center">{{ idx + 1 }}</td>
                        <td class="py-2">
                            <v-text-field
                                v-model="kpi.name"
                                variant="solo-filled"
                                density="compact"
                                hide-details
                                class="kpi-input kpi-input--name"
                                :class="{ 'kpi-input--dirty': isKpiChanged(kpi) }"
                            />
                            <div class="d-flex align-center gap-2 flex-wrap mt-1">
                                <v-chip v-if="kpi.inverse_scoring" size="x-small" variant="tonal" color="orange" class="font-weight-medium">Inverse</v-chip>
                                <v-tooltip text="Weight is 0, so this KPI doesn't count toward the ranking yet. Give it a weight to include it." location="top">
                                    <template #activator="{ props: tip }">
                                        <v-chip v-if="Number(kpi.weightPercent) === 0" v-bind="tip" size="x-small" variant="tonal" color="blue-grey" class="font-weight-medium">Input only</v-chip>
                                    </template>
                                </v-tooltip>
                                <v-chip v-if="kpi.derivation_type === 'delinquent_ratio'" size="x-small" variant="tonal" color="indigo" class="font-weight-medium">Auto-derived</v-chip>
                            </div>
                        </td>
                        <td>
                            <v-select
                                v-model="kpi.category_id"
                                :items="categoryOptions"
                                item-title="name"
                                item-value="id"
                                variant="solo-filled"
                                density="compact"
                                hide-details
                                class="kpi-input"
                                :class="{ 'kpi-input--dirty': isKpiChanged(kpi) }"
                            />
                        </td>
                        <td class="text-center">
                            <v-text-field
                                v-model="kpi.weightPercent"
                                type="number"
                                min="0" max="100" step="0.1"
                                variant="solo-filled"
                                density="compact"
                                hide-details
                                class="kpi-input"
                                :class="{ 'kpi-input--dirty': isKpiChanged(kpi) }"
                            />
                        </td>
                        <td class="text-center">
                            <v-tooltip text="Remove (soft delete)" location="top">
                                <template #activator="{ props: tip }">
                                    <v-btn v-bind="tip" icon size="x-small" variant="text" color="error" @click="confirmDelete(kpi)">
                                        <v-icon size="16">mdi-trash-can-outline</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                        </td>
                    </tr>
                    <tr v-if="!cat.kpis.length">
                        <td colspan="5" class="text-center text-caption text-medium-emphasis py-4">No KPIs in this category yet.</td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>

        <!-- Sticky save bar at bottom -->
        <div class="save-bar">
            <span class="text-caption text-medium-emphasis">
                <template v-if="isDirty">{{ changedKpis.length }} unsaved change{{ changedKpis.length === 1 ? '' : 's' }}</template>
                <template v-else>No pending changes</template>
            </span>
            <v-spacer />
            <v-btn
                variant="text"
                size="small"
                :disabled="!isDirty"
                @click="discard"
            >Discard</v-btn>
            <v-btn
                color="indigo"
                size="small"
                :disabled="!isDirty"
                prepend-icon="mdi-content-save-check-outline"
                @click="openPreview"
            >Update</v-btn>
        </div>

        <!-- Preview Changes Dialog -->
        <v-dialog v-model="previewOpen" fullscreen persistent :scrim="false" transition="dialog-bottom-transition">
            <v-card rounded="0" class="d-flex flex-column" style="height:100vh;">
                <v-toolbar color="white" class="border-b flex-grow-0">
                    <v-avatar color="indigo-lighten-5" size="40" rounded="lg" class="ml-4">
                        <v-icon color="indigo" size="20">mdi-content-save-check-outline</v-icon>
                    </v-avatar>
                    <div class="ml-3">
                        <div class="text-subtitle-1 font-weight-bold">Review Changes</div>
                        <div class="text-caption text-medium-emphasis">
                            {{ changedKpis.length }} KPI{{ changedKpis.length === 1 ? '' : 's' }} will be updated across every province's matrix
                        </div>
                    </div>
                    <v-spacer />
                    <v-btn icon variant="text" @click="previewOpen = false" class="mr-2">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-toolbar>

                <div class="flex-grow-1 overflow-y-auto pa-6" style="background:#f8fafc;">
                    <div class="preview-content">
                        <v-alert type="warning" variant="tonal" density="compact" icon="mdi-alert-outline" class="mb-4">
                            <div class="text-caption">
                                Weight changes affect the live ranking for <strong>every</strong> province immediately upon confirming.
                            </div>
                        </v-alert>
                        <div class="preview-grid">
                            <v-card
                                v-for="c in changedKpis" :key="c.id"
                                variant="flat" rounded="lg" class="preview-card"
                        >
                            <div class="d-flex align-start justify-space-between gap-3 pa-4 pb-3">
                                <div style="min-width:0;">
                                    <div class="text-subtitle-2 font-weight-bold">{{ c.newName }}</div>
                                    <div v-if="c.nameChanged" class="text-caption mt-1">
                                        Renamed from <span class="diff-old">{{ c.oldName }}</span>
                                    </div>
                                </div>
                                <v-chip size="x-small" color="indigo" variant="tonal" class="flex-shrink-0">
                                    {{ c.changeCount }} change{{ c.changeCount === 1 ? '' : 's' }}
                                </v-chip>
                            </div>
                            <v-divider />
                            <div class="pa-4 d-flex flex-column gap-3">
                                <div v-if="c.categoryChanged" class="d-flex align-center gap-3">
                                    <v-avatar size="28" rounded="md" color="grey-lighten-4">
                                        <v-icon size="15" color="medium-emphasis">mdi-shape-outline</v-icon>
                                    </v-avatar>
                                    <span class="text-caption text-medium-emphasis" style="width:70px;">Category</span>
                                    <v-chip size="small" color="error" variant="tonal" class="diff-chip-old">{{ c.oldCategoryName }}</v-chip>
                                    <v-icon size="16" color="medium-emphasis">mdi-arrow-right</v-icon>
                                    <v-chip size="small" color="success" variant="flat">{{ c.newCategoryName }}</v-chip>
                                </div>
                                <div v-if="c.weightChanged" class="d-flex align-center gap-3">
                                    <v-avatar size="28" rounded="md" color="grey-lighten-4">
                                        <v-icon size="15" color="medium-emphasis">mdi-percent-outline</v-icon>
                                    </v-avatar>
                                    <span class="text-caption text-medium-emphasis" style="width:70px;">Weight</span>
                                    <v-chip size="small" color="error" variant="tonal" class="diff-chip-old">{{ c.oldWeight }}%</v-chip>
                                    <v-icon size="16" color="medium-emphasis">mdi-arrow-right</v-icon>
                                    <v-chip size="small" color="success" variant="flat">{{ c.newWeight }}%</v-chip>
                                </div>
                            </div>
                            </v-card>
                        </div>
                    </div>
                </div>

                <v-divider />
                <v-card-actions class="px-6 py-3 gap-2 justify-end flex-grow-0">
                    <v-btn variant="text" @click="previewOpen = false">Keep Editing</v-btn>
                    <v-btn
                        variant="flat"
                        color="indigo"
                        prepend-icon="mdi-check"
                        :loading="updating"
                        @click="submitUpdate"
                    >Confirm & Update</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Add KPI Dialog -->
        <v-dialog v-model="addDialogOpen" max-width="440" persistent>
            <v-card rounded="lg">
                <v-card-item class="pt-5 pb-2 px-5">
                    <div class="d-flex align-center gap-3">
                        <v-avatar color="indigo-lighten-5" size="40" rounded="lg">
                            <v-icon color="indigo" size="20">mdi-plus-circle-outline</v-icon>
                        </v-avatar>
                        <div>
                            <div class="text-subtitle-2 font-weight-bold">Add KPI</div>
                            <div class="text-caption text-medium-emphasis">Applies to every province's matrix</div>
                        </div>
                    </div>
                </v-card-item>
                <v-card-text class="px-5 pb-3 d-flex flex-column gap-3">
                    <v-text-field
                        v-model="addForm.name"
                        label="KPI name"
                        variant="outlined"
                        density="comfortable"
                        :error-messages="addForm.errors.name"
                        autofocus
                    />
                    <v-select
                        v-model="addForm.category_id"
                        :items="categoryOptions"
                        item-title="name"
                        item-value="id"
                        label="Category"
                        variant="outlined"
                        density="comfortable"
                        :error-messages="addForm.errors.category_id"
                    />
                    <v-text-field
                        v-model="addForm.weight"
                        label="Weight %"
                        type="number"
                        min="0" max="100" step="0.1"
                        variant="outlined"
                        density="comfortable"
                        :error-messages="addForm.errors.weight"
                    />
                </v-card-text>
                <v-divider />
                <v-card-actions class="px-5 py-3 gap-2 justify-end">
                    <v-btn variant="text" size="small" @click="addDialogOpen = false">Cancel</v-btn>
                    <v-btn
                        variant="flat"
                        color="indigo"
                        size="small"
                        :loading="addForm.processing"
                        :disabled="!addForm.name.trim() || !addForm.category_id || addForm.weight === ''"
                        @click="submitAdd"
                    >Add KPI</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete KPI Confirm Dialog -->
        <v-dialog v-model="deleteDialogOpen" max-width="420" persistent>
            <v-card rounded="lg">
                <v-card-item class="pt-5 pb-2 px-5">
                    <div class="d-flex align-center gap-3">
                        <v-avatar color="error-lighten-5" size="40" rounded="lg">
                            <v-icon color="error" size="20">mdi-trash-can-outline</v-icon>
                        </v-avatar>
                        <div>
                            <div class="text-subtitle-2 font-weight-bold">Remove KPI</div>
                            <div class="text-caption text-medium-emphasis">Soft delete — historical data is kept</div>
                        </div>
                    </div>
                </v-card-item>
                <v-card-text class="px-5 pb-3">
                    <p class="text-body-2">
                        Remove <strong>{{ deleteTarget?.name }}</strong> from the KPI matrix for every province?
                        Past target/accomplished records stay in the database and remain visible in historical
                        reports — this only stops it from being scored or edited going forward.
                    </p>
                </v-card-text>
                <v-divider />
                <v-card-actions class="px-5 py-3 gap-2 justify-end">
                    <v-btn variant="text" size="small" @click="deleteDialogOpen = false">Cancel</v-btn>
                    <v-btn variant="flat" color="error" size="small" :loading="deleting" @click="doDelete">Remove</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Bulk Add via CSV Dialog -->
        <v-dialog v-model="csvDialogOpen" max-width="700" persistent scrollable>
            <v-card rounded="lg">
                <v-card-item class="pt-5 pb-2 px-5">
                    <div class="d-flex align-center gap-3">
                        <v-avatar color="indigo-lighten-5" size="40" rounded="lg">
                            <v-icon color="indigo" size="20">mdi-file-upload-outline</v-icon>
                        </v-avatar>
                        <div>
                            <div class="text-subtitle-2 font-weight-bold">Bulk Add KPIs via CSV</div>
                            <div class="text-caption text-medium-emphasis">
                                {{ csvStep === 'upload' ? "Adds to every province's matrix" : `Review ${csvResults.length} row(s) before committing` }}
                            </div>
                        </div>
                    </div>
                </v-card-item>
                <v-divider />

                <!-- Step: upload -->
                <v-card-text v-if="csvStep === 'upload'" class="px-5 py-4 d-flex flex-column gap-4">
                    <v-alert type="info" variant="tonal" density="compact" icon="mdi-information-outline">
                        <div class="text-caption">
                            CSV columns: <strong>name, category, weight</strong>. Category must be
                            <em>Core</em>, <em>Strategic</em>, or <em>Support</em>. Weight is a percent (e.g. 5 for 5%).
                        </div>
                    </v-alert>
                    <div class="d-flex align-center justify-space-between">
                        <div class="d-flex align-center gap-2">
                            <v-icon size="14" color="indigo">mdi-table-eye</v-icon>
                            <span class="text-caption font-weight-bold text-medium-emphasis text-uppercase" style="letter-spacing:.06em;">Sample CSV Format</span>
                        </div>
                        <v-btn size="x-small" variant="tonal" color="indigo" prepend-icon="mdi-download-outline" @click="downloadCsvTemplate">
                            Download Template
                        </v-btn>
                    </div>
                    <div class="csv-sample-wrap rounded-lg overflow-x-auto">
                        <table class="csv-sample">
                            <thead>
                                <tr><th>name</th><th>category</th><th>weight</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Trainings Conducted</td><td>Strategic</td><td>3</td></tr>
                                <tr><td>Linkages Established</td><td>Core</td><td>5</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <v-file-input
                        v-model="csvFile"
                        label="CSV file"
                        variant="outlined"
                        density="comfortable"
                        accept=".csv,text/csv"
                        prepend-icon=""
                        prepend-inner-icon="mdi-file-delimited-outline"
                        :error-messages="csvUploadError"
                        hide-details="auto"
                    />
                </v-card-text>

                <!-- Step: review -->
                <v-card-text v-else class="px-5 py-4">
                    <div class="d-flex align-center gap-2 mb-3 flex-wrap">
                        <v-chip size="small" color="success" variant="tonal">{{ validCsvCount }} valid</v-chip>
                        <v-chip v-if="invalidCsvCount" size="small" color="error" variant="tonal">{{ invalidCsvCount }} need attention</v-chip>
                        <v-spacer />
                        <span class="text-caption text-medium-emphasis">Only checked rows are imported</span>
                    </div>
                    <v-table density="compact">
                        <thead>
                            <tr>
                                <th style="width:32px;"></th>
                                <th>Name</th>
                                <th style="width:150px;">Category</th>
                                <th style="width:110px;">Weight %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in csvResults" :key="row.row_index" :class="{ 'csv-row--invalid': row.status === 'invalid' }">
                                <td>
                                    <v-checkbox v-model="row.include" density="compact" hide-details :disabled="row.status === 'invalid'" />
                                </td>
                                <td>
                                    <div class="text-body-2">{{ row.data.name || '—' }}</div>
                                    <div v-if="row.errors.name" class="text-caption text-error">{{ row.errors.name }}</div>
                                </td>
                                <td>
                                    <v-select
                                        v-model="row.data.category_id"
                                        :items="csvCategoryOptions"
                                        item-title="name"
                                        item-value="id"
                                        density="compact"
                                        variant="solo-filled"
                                        hide-details
                                        @update:model-value="revalidateCsvRow(row)"
                                    />
                                    <div v-if="row.errors.category" class="text-caption text-error">{{ row.errors.category }}</div>
                                </td>
                                <td>
                                    <v-text-field
                                        v-model.number="row.data.weight"
                                        type="number" min="0" max="100" step="0.1"
                                        density="compact" variant="solo-filled" hide-details
                                        @update:model-value="revalidateCsvRow(row)"
                                    />
                                    <div v-if="row.errors.weight" class="text-caption text-error">{{ row.errors.weight }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card-text>

                <v-divider />
                <v-card-actions class="px-5 py-3 gap-2 justify-end">
                    <v-btn variant="text" size="small" @click="closeCsvDialog">Cancel</v-btn>
                    <v-btn
                        v-if="csvStep === 'upload'"
                        variant="flat" color="indigo" size="small"
                        :loading="csvVerifying"
                        :disabled="!csvFile"
                        @click="submitCsvVerify"
                    >Verify</v-btn>
                    <v-btn
                        v-else
                        variant="flat" color="indigo" size="small"
                        :loading="csvCommitting"
                        :disabled="!validCsvCount"
                        prepend-icon="mdi-check"
                        @click="submitCsvCommit"
                    >Import {{ validCsvCount }} KPI(s)</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </div>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { useEcho } from '@laravel/echo-vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    kpi_categories:    { type: Array, default: () => [] },
    category_options:  { type: Array, default: () => [] },
});

const categoryOptions = computed(() => props.category_options);
const categoryNameById = computed(() => Object.fromEntries(categoryOptions.value.map(c => [c.id, c.name])));

const catColor = (code) => ({ CORE: 'indigo', STRATEGIC: 'teal', SUPPORT: 'deep-purple' }[code] ?? 'blue-grey');
const catBg    = (code) => ({ CORE: '#eef2ff', STRATEGIC: '#e0f2f1', SUPPORT: '#ede7f6' }[code] ?? '#f1f5f9');
const catIcon  = (code) => ({ CORE: 'mdi-rocket-launch-outline', STRATEGIC: 'mdi-shield-outline', SUPPORT: 'mdi-cog-outline' }[code] ?? 'mdi-chart-bar');

// Weight is stored as a 0-1 decimal server-side, edited here as a 0-100 percent.
const toPercent = (decimal) => Math.round(Number(decimal) * 10000) / 100;

// Clone into a local editable form so edits accumulate before an explicit
// Update — each kpi also carries a `weightPercent` field for the input,
// derived once here and converted back to a decimal only on submit.
const buildFormCategories = () => JSON.parse(JSON.stringify(props.kpi_categories)).map(cat => ({
    ...cat,
    kpis: cat.kpis.map(k => ({ ...k, weightPercent: toPercent(k.weight) })),
}));

const form = ref(buildFormCategories());
const initialSnapshot = ref(JSON.stringify(form.value));

const isDirty = computed(() => JSON.stringify(form.value) !== initialSnapshot.value);

const allKpisFlat = computed(() => form.value.flatMap(cat => cat.kpis.map(k => ({ ...k, categoryCode: cat.code }))));
const originalById = computed(() => {
    const map = new Map();
    for (const cat of JSON.parse(initialSnapshot.value)) {
        for (const k of cat.kpis) map.set(k.id, k);
    }
    return map;
});

const isKpiChanged = (kpi) => {
    const orig = originalById.value.get(kpi.id);
    if (!orig) return false;
    return orig.category_id !== kpi.category_id
        || Number(orig.weightPercent) !== Number(kpi.weightPercent)
        || orig.name.trim() !== kpi.name.trim();
};

const changedKpis = computed(() => allKpisFlat.value
    .filter(isKpiChanged)
    .map(kpi => {
        const orig = originalById.value.get(kpi.id);
        const nameChanged = orig.name.trim() !== kpi.name.trim();
        const categoryChanged = orig.category_id !== kpi.category_id;
        const weightChanged = Number(orig.weightPercent) !== Number(kpi.weightPercent);
        return {
            id: kpi.id,
            nameChanged,
            oldName: orig.name,
            newName: kpi.name,
            categoryChanged,
            oldCategoryName: categoryNameById.value[orig.category_id],
            newCategoryName: categoryNameById.value[kpi.category_id],
            weightChanged,
            oldWeight: orig.weightPercent,
            newWeight: kpi.weightPercent,
            changeCount: [nameChanged, categoryChanged, weightChanged].filter(Boolean).length,
        };
    }));

const previewOpen = ref(false);
const updating    = ref(false);
const justSaved   = ref(false);

const openPreview = () => { previewOpen.value = true; };

const discard = () => {
    form.value = JSON.parse(initialSnapshot.value);
};

const submitUpdate = () => {
    updating.value = true;
    const changes = changedKpis.value.map(c => {
        const kpi = allKpisFlat.value.find(k => k.id === c.id);
        return {
            id: c.id,
            name: kpi.name.trim(),
            category_id: kpi.category_id,
            weight: Number(kpi.weightPercent),
        };
    });

    router.put('/kpi-catalog/kpis', { changes }, {
        preserveScroll: true,
        onSuccess: () => {
            previewOpen.value = false;
            resyncForm(); // accept the just-submitted values as the new baseline
            justSaved.value = true;
            setTimeout(() => { justSaved.value = false; }, 2500);
        },
        onFinish: () => { updating.value = false; },
    });
};

// ── Add KPI ────────────────────────────────────────────────────────────────
const addDialogOpen = ref(false);

const addForm = useForm({
    category_id: null,
    name:        '',
    weight:      '',
});

const openAddDialog = (categoryId) => {
    addForm.reset();
    addForm.category_id = categoryId;
    addDialogOpen.value = true;
};

const submitAdd = () => {
    addForm.post('/kpi-catalog/kpis', {
        preserveScroll: true,
        onSuccess: () => { addDialogOpen.value = false; },
    });
};

// ── Delete (soft) KPI ──────────────────────────────────────────────────────
const deleteDialogOpen = ref(false);
const deleteTarget      = ref(null);
const deleting          = ref(false);

const confirmDelete = (kpi) => {
    deleteTarget.value = kpi;
    deleteDialogOpen.value = true;
};

const doDelete = () => {
    deleting.value = true;
    router.delete(`/kpi-catalog/kpis/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
            deleteTarget.value = null;
        },
    });
};

const resyncForm = () => {
    form.value = buildFormCategories();
    initialSnapshot.value = JSON.stringify(form.value);
};

// Keep the local editable form in sync whenever kpi_categories changes from
// an Add/Delete/CSV import — but never while there are unrelated unsaved
// weight/category edits pending, so those aren't silently discarded.
watch(() => props.kpi_categories, () => {
    if (!isDirty.value) resyncForm();
});

// ── Real-time: another admin's catalog change refreshes this screen ───────
const { leaveChannel } = useEcho('kpi-catalog', 'KpiCatalogUpdated', () => {
    if (isDirty.value) return; // don't clobber unsaved local edits
    router.reload({ only: ['kpi_categories', 'category_options'], preserveScroll: true, preserveState: true });
});
onBeforeUnmount(() => leaveChannel());

// ── Bulk Add via CSV (name, category, weight) ─────────────────────────────
const csvDialogOpen      = ref(false);
const csvStep            = ref('upload'); // 'upload' | 'review'
const csvFile            = ref(null);
const csvUploadError     = ref('');
const csvVerifying       = ref(false);
const csvCommitting      = ref(false);
const csvResults         = ref([]);
const csvCategoryOptions = ref([]);

const validCsvCount   = computed(() => csvResults.value.filter(r => r.include).length);
const invalidCsvCount = computed(() => csvResults.value.filter(r => r.status === 'invalid').length);

const xsrfToken = () => {
    const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
};

const openCsvDialog = () => {
    csvStep.value = 'upload';
    csvFile.value = null;
    csvUploadError.value = '';
    csvResults.value = [];
    csvDialogOpen.value = true;
};

const closeCsvDialog = () => {
    csvDialogOpen.value = false;
};

const downloadCsvTemplate = () => {
    const headers = 'name,category,weight';
    const rows = [
        'Trainings Conducted,Strategic,3',
        'Linkages Established,Core,5',
    ];
    const csv = `${headers}\n${rows.join('\n')}\n`;

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'kpi-catalog-template.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};

const submitCsvVerify = async () => {
    csvVerifying.value = true;
    csvUploadError.value = '';
    const data = new FormData();
    data.append('csv_file', csvFile.value);

    try {
        const res = await fetch('/kpi-catalog/kpis/verify-csv', {
            method: 'POST',
            headers: { 'X-XSRF-TOKEN': xsrfToken() },
            body: data,
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            csvUploadError.value = err.message ?? 'Verification failed. Please try again.';
            return;
        }
        const json = await res.json();
        csvCategoryOptions.value = json.category_options;
        csvResults.value = json.results;
        csvStep.value = 'review';
    } catch {
        csvUploadError.value = 'Network error. Please try again.';
    } finally {
        csvVerifying.value = false;
    }
};

// Re-check a row client-side after an inline edit, so fixing a typo can flip
// it back to includable without a full server round-trip.
const revalidateCsvRow = (row) => {
    const errors = {};
    if (!row.data.category_id) errors.category = 'Category is required.';
    const w = Number(row.data.weight);
    if (row.data.weight === '' || row.data.weight === null || Number.isNaN(w) || w < 0 || w > 100) {
        errors.weight = 'Weight must be a number between 0 and 100.';
    }
    if (!row.data.name) errors.name = 'Name is required.';

    row.errors = errors;
    row.status = Object.keys(errors).length ? 'invalid' : 'valid';
    row.include = row.status === 'valid';
};

const submitCsvCommit = async () => {
    csvCommitting.value = true;
    const rows = csvResults.value
        .filter(r => r.include)
        .map(r => ({
            name:        r.data.name,
            category_id: r.data.category_id,
            weight:      Number(r.data.weight),
        }));

    try {
        const res = await fetch('/kpi-catalog/kpis/commit-csv', {
            method: 'POST',
            headers: { 'X-XSRF-TOKEN': xsrfToken(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ rows }),
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            alert(err.message ?? 'Import failed. Please try again.');
            return;
        }
        csvDialogOpen.value = false;
        router.reload({ only: ['kpi_categories', 'category_options'], preserveScroll: true });
    } catch {
        alert('Network error. Please try again.');
    } finally {
        csvCommitting.value = false;
    }
};
</script>

<style scoped>
.kpi-input--dirty :deep(.v-field) {
    background: #fffbeb;
    outline: 1px solid #f59e0b;
}
.kpi-input--name {
    min-width: 220px;
}
.diff-old {
    color: #dc2626;
    text-decoration: line-through;
    text-decoration-color: rgba(220, 38, 38, 0.5);
}
.diff-new {
    color: #16a34a;
}
.diff-chip-old :deep(.v-chip__content) {
    text-decoration: line-through;
}
.preview-content {
    max-width: 1600px;
    margin: 0 auto;
}
.preview-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.preview-card {
    border: 1px solid #e2e8f0;
    border-left: 3px solid #6366f1;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
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

.csv-sample-wrap {
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 8px;
    background: #f8fafc;
}
.csv-sample {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    white-space: nowrap;
}
.csv-sample thead tr { background: #eef0f8; }
.csv-sample th {
    padding: 6px 10px;
    text-align: left;
    font-weight: 700;
    color: #3730a3;
    border-bottom: 1px solid #dde1f0;
}
.csv-sample td {
    padding: 5px 10px;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}
.csv-row--invalid { background: #fef2f2; }

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
.kpi-input :deep(.v-field) {
    background: #fff;
    border-radius: 6px;
    font-size: 12px;
}
.kpi-input :deep(.v-field__input) {
    min-height: 28px;
    padding: 4px 10px;
}
</style>
