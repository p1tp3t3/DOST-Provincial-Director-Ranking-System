<template>
    <v-card class="elevation-1 border-0 rounded-md">
        <div class="d-flex align-center justify-space-between px-5 pt-4 pb-3">
            <div>
                <div class="text-subtitle-2 font-weight-bold">KPI Outcomes Breakdown</div>
                <div class="text-caption text-medium-emphasis">Performance indicators for the current period</div>
            </div>
            <div class="d-flex align-center gap-2">
                <v-chip size="small" variant="tonal" color="primary">{{ outcomes.length }} Outcomes</v-chip>
                <v-btn size="small" variant="flat" color="primary" prepend-icon="mdi-pencil-outline">
                    Manage KPI
                </v-btn>
            </div>
        </div>
        <v-divider></v-divider>
        <v-table density="comfortable" hover>
            <thead>
                <tr>
                    <th class="text-caption text-medium-emphasis" width="50">#</th>
                    <th class="text-caption text-medium-emphasis">Outcome</th>
                    <th class="text-caption text-medium-emphasis text-center" width="110">Target</th>
                    <th class="text-caption text-medium-emphasis text-center" width="110">Actual</th>
                    <th class="text-caption text-medium-emphasis text-center" width="100">Status</th>
                    <th class="text-caption text-medium-emphasis text-center" width="80">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(outcome, i) in outcomes" :key="i">
                    <td class="text-body-2 text-medium-emphasis">{{ i + 1 }}</td>
                    <td class="text-body-2">{{ outcome.title }}</td>
                    <td class="text-body-2 text-center">{{ outcome.target }}</td>
                    <td class="text-body-2 text-center font-weight-medium">{{ outcome.actual }}</td>
                    <td class="text-center">
                        <v-chip size="x-small" variant="tonal" :color="outcome.met ? 'success' : 'error'">
                            {{ outcome.met ? 'Met' : 'Not Met' }}
                        </v-chip>
                    </td>
                    <td class="text-center">
                        <v-tooltip text="Edit Outcome" location="top">
                            <template #activator="{ props: tip }">
                                <v-btn v-bind="tip" icon size="x-small" variant="text" color="primary"
                                    @click="$emit('edit', outcome)">
                                    <v-icon size="15">mdi-pencil-outline</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>
                    </td>
                </tr>

                <tr v-if="outcomes.length === 0">
                    <td colspan="6">
                        <div class="text-center py-12">
                            <v-icon size="40" color="grey-lighten-2" class="mb-3">mdi-clipboard-text-off-outline</v-icon>
                            <div class="text-body-2 text-medium-emphasis">No KPI outcomes recorded</div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </v-table>
    </v-card>
</template>

<script setup>
defineProps({
    outcomes: { type: Array, default: () => [] },
});

defineEmits(['edit']);
</script>
