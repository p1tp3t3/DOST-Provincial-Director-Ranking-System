<template>
    <Head title="Correction Requests" />
    <div class="d-flex flex-column gap-4">

        <!-- Header -->
        <v-card border elevation="0" rounded="lg">
            <div class="d-flex align-center justify-space-between flex-wrap gap-3 px-5 py-4">
                <div>
                    <div class="text-subtitle-1 font-weight-bold">Correction Requests</div>
                    <div class="text-body-2 text-medium-emphasis">
                        <template v-if="isCentral">
                            Requests forwarded by regional directors. Approve to reopen the period until a date you choose, or decline with a reason.
                        </template>
                        <template v-else>
                            Requests from provinces in {{ region ?? 'your region' }} to change a locked period. Forward the ones the central office should decide on, or return them for more detail.
                        </template>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <v-chip v-for="c in summary" :key="c.label" :color="c.color" variant="tonal" size="small" class="font-weight-medium">
                        {{ c.count }} {{ c.label }}
                    </v-chip>
                </div>
            </div>
            <v-divider />
            <v-tabs v-model="tab" density="compact" color="indigo" class="px-2">
                <v-tab value="action">
                    Needs your action
                    <v-chip v-if="needsAction.length" size="x-small" color="error" variant="flat" class="ml-2">{{ needsAction.length }}</v-chip>
                </v-tab>
                <v-tab value="all">All requests ({{ requests.length }})</v-tab>
            </v-tabs>
        </v-card>

        <!-- List -->
        <div v-if="shown.length" class="d-flex flex-column gap-3">
            <CorrectionRequestCard v-for="r in shown" :key="r.id" :request="r" show-province>
                <template v-if="canAct(r) || isCentral" #actions>
                    <v-btn v-if="isCentral" variant="text" size="small" prepend-icon="mdi-table-edit" @click="router.visit(`/kpi-data/${r.province_url_id}/${r.year}`)">
                        View figures
                    </v-btn>
                    <template v-if="canAct(r)">
                        <template v-if="isCentral">
                            <v-btn variant="outlined" color="error" size="small" prepend-icon="mdi-close" @click="openDialog('decline', r)">Decline</v-btn>
                            <v-btn variant="flat" color="success" size="small" prepend-icon="mdi-check" @click="openDialog('approve', r)">Approve</v-btn>
                        </template>
                        <template v-else>
                            <v-btn variant="outlined" color="orange-darken-3" size="small" prepend-icon="mdi-undo-variant" @click="openDialog('return', r)">Return to province</v-btn>
                            <v-btn variant="flat" color="indigo" size="small" prepend-icon="mdi-share" @click="openDialog('forward', r)">Forward to central office</v-btn>
                        </template>
                    </template>
                </template>
            </CorrectionRequestCard>
        </div>
        <v-card v-else border elevation="0" rounded="lg" class="text-center py-12">
            <v-icon size="40" color="grey-lighten-1">mdi-inbox-outline</v-icon>
            <div class="text-body-1 font-weight-medium mt-2">{{ tab === 'action' ? 'Nothing waiting for you' : 'No correction requests yet' }}</div>
            <div class="text-body-2 text-medium-emphasis">
                {{ tab === 'action' ? 'New requests will show up here and in your notifications.' : 'Provinces request corrections from their KPI Entry page once a period is locked.' }}
            </div>
        </v-card>
    </div>

    <!-- Forward / Return / Approve / Decline -->
    <v-dialog v-model="dialog.open" max-width="520" persistent>
        <v-card v-if="dialog.request" rounded="lg">
            <v-card-title class="d-flex align-center gap-2 pt-4 px-5">
                <v-icon :color="ACTIONS[dialog.action].color">{{ ACTIONS[dialog.action].icon }}</v-icon>
                {{ ACTIONS[dialog.action].title }}
            </v-card-title>
            <v-card-text class="px-5">
                <div class="text-body-2 mb-4">
                    <strong>{{ dialog.request.province }}</strong> · {{ dialog.request.period }}
                    <div class="text-medium-emphasis mt-1">{{ ACTIONS[dialog.action].help }}</div>
                </div>

                <v-text-field
                    v-if="dialog.action === 'approve'"
                    v-model="dialog.openUntil"
                    type="date"
                    label="Open for changes until"
                    :min="today"
                    variant="outlined"
                    density="comfortable"
                    :error-messages="dialog.errors.open_until"
                    hint="The province can change this period through the end of this day."
                    persistent-hint
                    class="mb-4"
                />

                <v-textarea
                    v-model="dialog.note"
                    :label="ACTIONS[dialog.action].noteLabel"
                    variant="outlined"
                    rows="3"
                    auto-grow
                    counter="2000"
                    :error-messages="dialog.errors.note || dialog.errors.status"
                />
            </v-card-text>
            <v-card-actions class="px-5 pb-4">
                <v-spacer />
                <v-btn variant="text" :disabled="dialog.sending" @click="dialog.open = false">Cancel</v-btn>
                <v-btn variant="flat" :color="ACTIONS[dialog.action].color" :loading="dialog.sending"
                       :disabled="!dialogValid" @click="submit">
                    {{ ACTIONS[dialog.action].confirm }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.show" color="success" timeout="3500" location="bottom right">{{ toast.text }}</v-snackbar>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import CorrectionRequestCard from '@/Components/Kpi/CorrectionRequestCard.vue';

const props = defineProps({
    requests: { type: Array,  default: () => [] },
    mode:     { type: String, default: 'regional' },   // 'regional' | 'central'
    region:   { type: String, default: null },
});

const isCentral = computed(() => props.mode === 'central');

// The step this user takes: forward/return at the region, approve/decline centrally.
const canAct = (r) => r.status === (isCentral.value ? 'pending_central' : 'pending_regional');

const needsAction = computed(() => props.requests.filter(canAct));
const tab   = ref(needsAction.value.length ? 'action' : 'all');
const shown = computed(() => (tab.value === 'action' ? needsAction.value : props.requests));

const summary = computed(() => {
    const count = (fn) => props.requests.filter(fn).length;
    return [
        { label: 'at regional director', color: 'amber-darken-3', count: count(r => r.status === 'pending_regional') },
        { label: 'at central office',    color: 'blue-darken-1',  count: count(r => r.status === 'pending_central') },
        { label: 'returned',             color: 'orange-darken-3', count: count(r => r.status === 'returned') },
        { label: 'open now',             color: 'success',        count: count(r => r.status === 'approved' && r.is_open) },
    ].filter(c => c.count);
});

// ── Dialog ────────────────────────────────────────────────────────────────
const ACTIONS = {
    forward: { title: 'Forward to the central office', icon: 'mdi-share', color: 'indigo', confirm: 'Forward',
               noteLabel: 'Note to the central office (optional)', noteRequired: false,
               help: 'The super admin will decide whether to reopen this period.' },
    return:  { title: 'Return to the province', icon: 'mdi-undo-variant', color: 'orange-darken-3', confirm: 'Return',
               noteLabel: 'What should the province add or fix?', noteRequired: true,
               help: 'The province can update its reason and send the request back to you.' },
    approve: { title: 'Approve the correction', icon: 'mdi-check-decagram', color: 'success', confirm: 'Approve',
               noteLabel: 'Note to the province (optional)', noteRequired: false,
               help: 'Only this period reopens, only for this province, until the date you choose.' },
    decline: { title: 'Decline the correction', icon: 'mdi-close-circle', color: 'error', confirm: 'Decline',
               noteLabel: 'Reason for declining', noteRequired: true,
               help: 'The period stays locked. The province and the regional director will see your reason.' },
};

const toDateInput = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const today = toDateInput(new Date());
const inAWeek = () => { const d = new Date(); d.setDate(d.getDate() + 7); return toDateInput(d); };

const dialog = reactive({ open: false, action: 'forward', request: null, note: '', openUntil: '', errors: {}, sending: false });

const openDialog = (action, request) => {
    Object.assign(dialog, { open: true, action, request, note: '', openUntil: inAWeek(), errors: {}, sending: false });
};

const dialogValid = computed(() => {
    const a = ACTIONS[dialog.action];
    if (a.noteRequired && dialog.note.trim().length < 5) return false;
    if (dialog.action === 'approve' && (!dialog.openUntil || dialog.openUntil < today)) return false;
    return true;
});

const toast = reactive({ show: false, text: '' });

const submit = () => {
    const r = dialog.request;
    const payload = { note: dialog.note.trim() || null };
    if (dialog.action === 'approve') payload.open_until = dialog.openUntil;

    const done = {
        forward: `Forwarded ${r.province}'s request for ${r.period} to the central office.`,
        return:  `Returned ${r.province}'s request for ${r.period}.`,
        approve: `Approved: ${r.province} ${r.period} is open until ${new Date(`${dialog.openUntil}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}.`,
        decline: `Declined ${r.province}'s request for ${r.period}.`,
    }[dialog.action];

    router.post(`/kpi-corrections/${r.id}/${dialog.action}`, payload, {
        preserveScroll: true,
        onStart:   () => { dialog.sending = true; },
        onFinish:  () => { dialog.sending = false; },
        onError:   (errors) => { dialog.errors = errors; },
        onSuccess: () => {
            dialog.open = false;
            Object.assign(toast, { show: true, text: done });
        },
    });
};
</script>
