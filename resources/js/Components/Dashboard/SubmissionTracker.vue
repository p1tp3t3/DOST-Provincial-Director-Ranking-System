<script setup>
import { computed } from 'vue';

// Per-province data-entry progress for one year: which directors have
// submitted, how many entry KPIs have an accomplishment and how many scored
// ones have a target. Rows are the dashboard's filtered score rows; each
// carries `submission: { accomplished, targets }` from the controller.
const props = defineProps({
    rows:     { type: Array,  default: () => [] },
    expected: { type: Object, default: () => ({ accomplished: 0, targets: 0 }) },
    // Show how many quarters the director has approved — a province has
    // submitted its year once all four are approved (see RankingRelease).
    showApprovals: { type: Boolean, default: false },
});

const STATES = {
    not_started: { label: 'Not started', color: 'error',     order: 0 },
    in_progress: { label: 'In progress', color: 'warning',   order: 1 },
    complete:    { label: 'Complete',    color: 'success',   order: 2 },
    no_director: { label: 'No director', color: 'blue-grey', order: 3 },
};

// Furthest behind first, so the provinces to follow up with are on top.
const items = computed(() => {
    const exp = props.expected;
    return props.rows.map(r => {
        const s   = r.submission ?? { accomplished: 0, targets: 0 };
        const key = !r.director_id ? 'no_director'
                  : s.accomplished === 0 ? 'not_started'
                  : s.accomplished >= exp.accomplished ? 'complete'
                  : 'in_progress';
        return {
            ...r,
            submission:          s,
            state:               STATES[key],
            accomplishedPct:     exp.accomplished ? (s.accomplished / exp.accomplished) * 100 : 0,
            targetsPct:          exp.targets ? (s.targets / exp.targets) * 100 : 0,
            missingAccomplished: Math.max(exp.accomplished - s.accomplished, 0),
            missingTargets:      Math.max(exp.targets - s.targets, 0),
        };
    }).sort((a, b) =>
        a.state.order - b.state.order
        || a.accomplishedPct - b.accomplishedPct
        || a.province.localeCompare(b.province));
});

const summary = computed(() =>
    Object.entries(STATES)
        .map(([key, s]) => ({ key, ...s, count: items.value.filter(r => r.state === s).length }))
        .filter(s => s.count > 0)
);
</script>

<template>
    <div class="px-4 py-2 d-flex align-center gap-2 flex-wrap">
        <v-chip v-for="s in summary" :key="s.key" size="small" :color="s.color" variant="tonal" class="font-weight-medium">
            {{ s.label }}: {{ s.count }}
        </v-chip>
        <span class="text-caption text-medium-emphasis ml-auto">
            {{ expected.accomplished }} KPIs need an accomplishment; {{ expected.targets }} of them are scored and need a target.
        </span>
    </div>
    <div class="submission-scroll">
        <v-table density="compact" fixed-header class="submission-table">
            <thead>
                <tr>
                    <th>Province</th>
                    <th style="width:140px;">Status</th>
                    <th style="width:240px;">Accomplishments</th>
                    <th style="width:240px;">Targets</th>
                    <th v-if="showApprovals" style="width:150px;">Director approval</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in items" :key="row.province_id">
                    <td>
                        <a :href="`/province-directories/${row.province_url_id}`" class="sub-link text-body-2 font-weight-medium">{{ row.province }}</a>
                        <div class="text-caption" style="line-height:1.2;">
                            <a v-if="row.director_id" :href="`/profile/${row.director_id}`" class="sub-link text-medium-emphasis">{{ row.director }}</a>
                            <span v-else class="text-disabled font-italic">Vacant</span>
                        </div>
                    </td>
                    <td>
                        <v-chip size="x-small" :color="row.state.color" variant="tonal" class="font-weight-medium">{{ row.state.label }}</v-chip>
                    </td>
                    <td>
                        <template v-if="row.director_id">
                            <div class="d-flex justify-space-between text-caption">
                                <span>{{ row.submission.accomplished }} / {{ expected.accomplished }}</span>
                                <span v-if="row.missingAccomplished" class="text-medium-emphasis">{{ row.missingAccomplished }} missing</span>
                            </div>
                            <v-progress-linear :model-value="row.accomplishedPct" :color="row.state.color" height="5" rounded bg-color="grey-lighten-3" />
                        </template>
                        <span v-else class="text-caption text-disabled">—</span>
                    </td>
                    <td>
                        <template v-if="row.director_id">
                            <div class="d-flex justify-space-between text-caption">
                                <span>{{ row.submission.targets }} / {{ expected.targets }}</span>
                                <span v-if="row.missingTargets" class="text-medium-emphasis">{{ row.missingTargets }} missing</span>
                            </div>
                            <v-progress-linear :model-value="row.targetsPct" color="indigo" height="5" rounded bg-color="grey-lighten-3" />
                        </template>
                        <span v-else class="text-caption text-disabled">—</span>
                    </td>
                    <td v-if="showApprovals">
                        <v-chip v-if="row.approved_quarters >= 4" size="x-small" color="success" variant="tonal" class="font-weight-medium">
                            <v-icon start size="12">mdi-check-decagram</v-icon>Submitted
                        </v-chip>
                        <span v-else class="text-caption text-medium-emphasis">{{ row.approved_quarters ?? 0 }} of 4 quarters</span>
                    </td>
                </tr>
                <tr v-if="!items.length">
                    <td :colspan="showApprovals ? 5 : 4" class="text-center py-8 text-medium-emphasis text-body-2">No provinces match this filter.</td>
                </tr>
            </tbody>
        </v-table>
    </div>
    <div class="px-4 py-2 text-caption text-medium-emphasis">
        A scored KPI with no target is measured against the median target its peers set, so missing targets don't zero out a province's score.
    </div>
</template>

<style scoped>
.submission-scroll { max-height: 560px; overflow-y: auto; }
.submission-table :deep(thead th) { font-size: 13px !important; font-weight: 700 !important; white-space: nowrap; }
.submission-table :deep(tbody td) { font-size: 14px !important; padding-top: 6px !important; padding-bottom: 6px !important; }
.sub-link { text-decoration: none !important; color: inherit; transition: color 0.15s; }
.sub-link:hover { color: var(--tx-4f46e5) !important; text-decoration: underline !important; }
</style>
