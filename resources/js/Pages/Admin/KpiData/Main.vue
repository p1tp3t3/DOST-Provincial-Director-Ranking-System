<template>
    <Head title="KPI Data" />
    <div class="d-flex flex-column gap-3">

    <!-- Reporting deadlines: provinces enter each quarter until its deadline;
         after it the quarter is locked (the Q4 deadline also locks targets). -->
    <v-card border elevation="0" rounded="lg">
        <div class="d-flex align-center gap-2 px-4 pt-3 pb-2 flex-wrap">
            <v-icon size="18" color="indigo">mdi-calendar-clock</v-icon>
            <span class="text-body-1 font-weight-bold">Reporting Deadlines</span>
            <span class="text-caption text-medium-emphasis">Last day provinces can enter each quarter. After it, changes need an approved correction request; the Q4 deadline also locks the year's targets.</span>
        </div>
        <v-divider />
        <div class="d-flex align-end gap-3 px-4 py-3 flex-wrap">
            <v-select v-model="deadlineYear" :items="deadline_years" label="Year" density="compact" variant="outlined" hide-details style="max-width:110px;" />
            <v-text-field
                v-for="q in [1, 2, 3, 4]" :key="q"
                v-model="deadlineForm[q]"
                :label="`Q${q} deadline`"
                type="date" density="compact" variant="outlined" hide-details clearable
                style="max-width:180px;"
            />
            <v-btn color="indigo" size="small" prepend-icon="mdi-content-save" :loading="savingDeadlines" :disabled="!deadlinesChanged" @click="saveDeadlines">Save deadlines</v-btn>
        </div>
    </v-card>

    <v-card border elevation="0" rounded="lg">

        <!-- Header -->
        <div class="d-flex align-center justify-space-between gap-3 px-4 pt-3 pb-2 flex-wrap">
            <div class="d-flex align-center gap-2">
                <v-icon size="18" color="indigo">mdi-table-edit</v-icon>
                <span class="text-body-1 font-weight-bold">KPI Data Editor</span>
                <v-chip size="x-small" color="indigo" variant="tonal">Super Admin</v-chip>
                <v-tooltip location="bottom" max-width="320">
                    <template #activator="{ props: tip }">
                        <v-chip v-bind="tip" size="x-small" variant="tonal" color="blue-grey" prepend-icon="mdi-information-outline" class="cursor-pointer">
                            How it works
                        </v-chip>
                    </template>
                    <div class="pa-1">
                        <div class="font-weight-bold mb-1">PRISM Matrix Data Entry</div>
                        <div class="text-caption opacity-80">
                            Pick a province below to edit its target & accomplished values for each KPI by year. Changes here update the weighted ranking on the Dashboard and Map immediately. Province Directories stay read-only.
                        </div>
                    </div>
                </v-tooltip>
            </div>
            <v-text-field
                v-model="search"
                placeholder="Search province or director…"
                variant="solo-filled"
                density="compact"
                hide-details
                clearable
                prepend-inner-icon="mdi-magnify"
                style="max-width:300px;"
            />
        </div>

        <v-divider />

        <!-- Tier filter -->
        <div class="d-flex align-center gap-2 px-4 py-2 flex-wrap">
            <span class="filter-label">Tier</span>
            <div class="segmented">
                <button class="segmented-btn" :class="{ active: selectedTier === 'all' }" @click="selectedTier = 'all'">
                    All <span class="seg-count seg-count--all">{{ tierCounts.all }}</span>
                </button>
                <button v-for="t in tiers" :key="t.value"
                        class="segmented-btn"
                        :class="['category-btn-' + t.value, { active: selectedTier === t.value }]"
                        @click="selectedTier = t.value">
                    {{ t.label }}
                    <span class="seg-count" :class="'seg-count--' + t.value">{{ tierCounts[t.value] ?? 0 }}</span>
                </button>
            </div>
        </div>

        <v-divider />

        <!-- Province table -->
        <v-data-table
            :headers="headers"
            :items="filtered"
            :search="search"
            density="compact"
            hover
            hide-default-footer
            :items-per-page="-1"
            class="editor-table"
        >
            <template #item.name="{ item }">
                <div class="d-flex align-center gap-2">
                    <v-avatar :color="tierColor(item.category)" size="28" rounded="md">
                        <span class="text-white font-weight-bold" style="font-size:0.7rem;">{{ item.name?.charAt(0) ?? '?' }}</span>
                    </v-avatar>
                    <div>
                        <div class="text-body-2 font-weight-medium">{{ item.name }}</div>
                        <div class="text-caption text-medium-emphasis">{{ item.director || 'No director assigned' }}</div>
                    </div>
                </div>
            </template>

            <template #item.category="{ item }">
                <v-chip :color="tierColor(item.category)" size="x-small" variant="tonal" class="font-weight-medium text-uppercase">
                    {{ item.category }}
                </v-chip>
            </template>

            <template #item.years_count="{ item }">
                <v-chip size="x-small" variant="tonal" :color="item.years_count > 0 ? 'success' : 'grey'">
                    {{ item.years_count }} year{{ item.years_count !== 1 ? 's' : '' }}
                </v-chip>
            </template>

            <template #item.years_list="{ item }">
                <div v-if="item.years.length" class="d-flex gap-1 flex-wrap">
                    <v-chip v-for="y in item.years" :key="y" size="x-small" variant="tonal" color="indigo" class="font-weight-medium">
                        {{ y }}
                    </v-chip>
                </div>
                <span v-else class="text-caption text-disabled">No data</span>
            </template>

            <template #item.actions="{ item }">
                <!-- Figures belong to the province, so they can be opened even
                     while the director post is vacant. -->
                <v-btn
                    size="x-small"
                    variant="tonal"
                    color="indigo"
                    prepend-icon="mdi-pencil"
                    @click="editProvince(item)"
                >Open</v-btn>
            </template>

            <template #no-data>
                <div class="text-center py-8 text-medium-emphasis text-body-2">No provinces match the filter.</div>
            </template>
        </v-data-table>

    </v-card>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    provinces:       { type: Array,  default: () => [] },
    available_years: { type: Array,  default: () => [] },
    deadline_years:  { type: Array,  default: () => [] },
    // { <year>: { <quarter>: 'YYYY-MM-DD' } }
    deadlines:       { type: Object, default: () => ({}) },
});

// ── Reporting deadlines ───────────────────────────────────────────────────
const deadlineYear    = ref(new Date().getFullYear());
const deadlineForm    = ref({});
const savingDeadlines = ref(false);

const savedDeadlines = (year) => Object.fromEntries([1, 2, 3, 4].map(q => [q, props.deadlines?.[year]?.[q] ?? null]));
watch([deadlineYear, () => props.deadlines], () => { deadlineForm.value = savedDeadlines(deadlineYear.value); }, { immediate: true });

const deadlinesChanged = computed(() =>
    [1, 2, 3, 4].some(q => (deadlineForm.value[q] || null) !== savedDeadlines(deadlineYear.value)[q])
);

const saveDeadlines = () => {
    const deadlines = Object.fromEntries([1, 2, 3, 4].map(q => [q, deadlineForm.value[q] || null]));
    router.put(`/kpi-deadlines/${deadlineYear.value}`, { deadlines }, {
        preserveScroll: true,
        onStart:  () => { savingDeadlines.value = true; },
        onFinish: () => { savingDeadlines.value = false; },
    });
};

const search       = ref('');
const selectedTier = ref('all');

const tiers = [
    { value: 'micro',  label: 'Micro'  },
    { value: 'small',  label: 'Small'  },
    { value: 'medium', label: 'Medium' },
    { value: 'large',  label: 'Large'  },
];

const tierCounts = computed(() => {
    const counts = { all: props.provinces.length };
    for (const p of props.provinces) counts[p.category] = (counts[p.category] ?? 0) + 1;
    return counts;
});

const filtered = computed(() =>
    selectedTier.value === 'all'
        ? props.provinces
        : props.provinces.filter(p => p.category === selectedTier.value)
);

const headers = [
    { title: 'Province',      key: 'name',         sortable: true  },
    { title: 'Tier',          key: 'category',     width: '90px',  align: 'center', sortable: true  },
    { title: 'Years w/ data', key: 'years_count',  width: '110px', align: 'center', sortable: true  },
    { title: 'Reporting Years', key: 'years_list', sortable: false },
    { title: '',              key: 'actions',      width: '90px',  align: 'end',    sortable: false },
];

const tierColor = (cat) => ({ micro: 'blue-grey', small: 'teal', medium: 'indigo', large: 'deep-purple' }[cat] ?? 'grey');

// Open the most recent year the director has data for; if none, fall back to
// the system's latest available year so the editor isn't blank.
const editProvince = (item) => {
    const yr = item.years[0] ?? props.available_years[0] ?? '';
    router.visit(`/kpi-data/${item.id}${yr ? '/' + yr : ''}`);
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
.segmented-btn:hover:not(.active) { color: #0f172a; background: rgba(255, 255, 255, 0.6); }
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

.category-btn-micro.active  { color: #455a64; background: #eceff1; box-shadow: 0 1px 2px rgba(69,90,100,0.10); }
.category-btn-small.active  { color: #00695c; background: #e0f2f1; box-shadow: 0 1px 2px rgba(0,105,92,0.10); }
.category-btn-medium.active { color: #283593; background: #e8eaf6; box-shadow: 0 1px 2px rgba(40,53,147,0.10); }
.category-btn-large.active  { color: #4527a0; background: #ede7f6; box-shadow: 0 1px 2px rgba(69,39,160,0.10); }

.editor-table :deep(thead th) { font-size: 11px !important; font-weight: 600 !important; }
.editor-table :deep(tbody tr) { cursor: default; }
</style>
