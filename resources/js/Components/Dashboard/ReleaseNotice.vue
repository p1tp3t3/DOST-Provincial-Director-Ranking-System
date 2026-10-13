<script setup>
import { computed } from 'vue';

// Why a year's rankings aren't shown yet, and what releases them: every
// province submitting (its director approving all four quarters), or the
// year's Q4 deadline passing. See RankingRelease on the server.
const props = defineProps({
    // { year, released, reason, submitted, total, deadline }
    status:  { type: Object,  required: true },
    compact: { type: Boolean, default: false },
});

const formatDate = (d) => new Date(`${d}T00:00:00`).toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' });

const releaseRule = computed(() => {
    const s = props.status;
    return s.deadline
        ? `They're released once every province has submitted, or automatically after the Q4 deadline on ${formatDate(s.deadline)}.`
        : `They're released once every province has submitted, or automatically after the Q4 deadline — which hasn't been set yet, so otherwise when ${s.year} ends.`;
});
const progress = computed(() => (props.status.total ? (props.status.submitted / props.status.total) * 100 : 0));
</script>

<template>
    <div v-if="compact" class="text-caption text-medium-emphasis d-flex align-center gap-1">
        <v-icon size="14">mdi-lock-clock</v-icon>
        {{ status.year }} rankings: {{ status.submitted }} of {{ status.total }} provinces submitted. {{ releaseRule }}
    </div>
    <v-card v-else border elevation="0" rounded="lg">
        <div class="pa-6 d-flex gap-4 align-start flex-wrap">
            <v-avatar color="indigo-lighten-5" size="48" rounded="lg">
                <v-icon color="indigo" size="26">mdi-lock-clock</v-icon>
            </v-avatar>
            <div class="flex-grow-1" style="min-width:260px;">
                <div class="text-subtitle-1 font-weight-bold">{{ status.year }} rankings aren't released yet</div>
                <div class="text-body-2 text-medium-emphasis mt-1">
                    A province has submitted when its director has approved all four quarters. {{ releaseRule }}
                </div>
                <div class="d-flex justify-space-between text-caption mt-4 mb-1">
                    <span>Provinces submitted</span>
                    <strong>{{ status.submitted }} / {{ status.total }}</strong>
                </div>
                <v-progress-linear :model-value="progress" color="indigo" height="8" rounded bg-color="grey-lighten-3" />
                <div class="text-caption text-medium-emphasis mt-3">
                    The Submissions tab shows where each province is.
                </div>
            </div>
        </div>
    </v-card>
</template>
