<template>
    <Head title="KPI Edit Requests" />
    <div class="d-flex flex-column gap-3">

        <v-card border elevation="0" rounded="lg">
            <div class="pa-4 d-flex align-center gap-3">
                <v-avatar color="teal" size="44" rounded="lg">
                    <v-icon color="white" size="22">mdi-lock-open-variant-outline</v-icon>
                </v-avatar>
                <div>
                    <div class="text-h6 font-weight-bold">KPI Edit Requests</div>
                    <div class="text-caption text-medium-emphasis">
                        Provincial admins request extra edit access here once they've used their free KPI edit for a year.
                    </div>
                </div>
                <v-spacer />
                <v-chip v-if="pendingCount" color="warning" variant="tonal" size="small">
                    {{ pendingCount }} pending
                </v-chip>
            </div>
        </v-card>

        <v-card border elevation="0" rounded="lg">
            <v-table density="compact">
                <thead>
                    <tr>
                        <th class="req-th">Province</th>
                        <th class="req-th">Director</th>
                        <th class="req-th">Requested By</th>
                        <th class="req-th">Year</th>
                        <th class="req-th">Status</th>
                        <th class="req-th" style="width:180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!requests.length">
                        <td colspan="6" class="text-center text-caption text-medium-emphasis py-8">
                            No edit access requests yet.
                        </td>
                    </tr>
                    <tr v-for="r in requests" :key="r.id">
                        <td class="text-body-2">{{ r.province }}</td>
                        <td class="text-body-2">{{ r.director_name }}</td>
                        <td class="text-body-2">{{ r.requested_by }}</td>
                        <td class="text-body-2">{{ r.year }}</td>
                        <td>
                            <v-chip size="x-small" variant="tonal" :color="statusColor(r.status)">
                                {{ statusLabel(r) }}
                            </v-chip>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <v-btn size="x-small" variant="tonal" @click="openView(r)">View</v-btn>
                                <template v-if="r.status === 'pending'">
                                    <v-btn size="x-small" color="success" variant="tonal" :loading="busyId === r.id" @click="approve(r)">
                                        Approve
                                    </v-btn>
                                    <v-btn size="x-small" color="error" variant="tonal" :loading="busyId === r.id" @click="openReject(r)">
                                        Reject
                                    </v-btn>
                                </template>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>

        <v-dialog v-model="rejectDialogOpen" max-width="420">
            <v-card rounded="lg">
                <v-card-title class="text-subtitle-1 font-weight-bold">Reject Request</v-card-title>
                <v-card-text>
                    <v-textarea
                        v-model="rejectNote"
                        label="Reason (optional)"
                        variant="outlined"
                        density="compact"
                        rows="3"
                        hide-details
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="rejectDialogOpen = false">Cancel</v-btn>
                    <v-btn color="error" :loading="busyId === rejectTarget?.id" @click="confirmReject">Reject</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- View details dialog -->
        <v-dialog v-model="viewDialogOpen" max-width="480">
            <v-card v-if="viewTarget" rounded="lg">
                <v-card-title class="text-subtitle-1 font-weight-bold">Request Details</v-card-title>
                <v-card-text>
                    <div class="view-grid">
                        <div class="view-label">Province</div>
                        <div class="text-body-2">{{ viewTarget.province }}</div>

                        <div class="view-label">Director</div>
                        <div class="text-body-2">{{ viewTarget.director_name }}</div>

                        <div class="view-label">Requested By</div>
                        <div class="text-body-2">{{ viewTarget.requested_by }}</div>

                        <div class="view-label">Year</div>
                        <div class="text-body-2">{{ viewTarget.year }}</div>

                        <div class="view-label">Requested On</div>
                        <div class="text-body-2">{{ formatDate(viewTarget.created_at) }}</div>

                        <div class="view-label">Status</div>
                        <div>
                            <v-chip size="x-small" variant="tonal" :color="statusColor(viewTarget.status)">
                                {{ statusLabel(viewTarget) }}
                            </v-chip>
                        </div>

                        <template v-if="viewTarget.status !== 'pending'">
                            <div class="view-label">Reviewed On</div>
                            <div class="text-body-2">{{ formatDate(viewTarget.reviewed_at) }}</div>
                        </template>
                    </div>

                    <v-divider class="my-3" />

                    <div class="view-label mb-1">Reason</div>
                    <p class="text-body-2" style="white-space:pre-wrap;">{{ viewTarget.reason || '—' }}</p>

                    <template v-if="viewTarget.status === 'rejected'">
                        <div class="view-label mb-1 mt-2">Response Note</div>
                        <p class="text-body-2" style="white-space:pre-wrap;">{{ viewTarget.response_note || '—' }}</p>
                    </template>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="viewDialogOpen = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    requests: { type: Array, default: () => [] },
});

const busyId = ref(null);

const pendingCount = computed(() => props.requests.filter(r => r.status === 'pending').length);

const statusColor = (status) => ({ pending: 'warning', approved: 'success', rejected: 'error' }[status] ?? 'grey');
const statusLabel = (r) => {
    if (r.status === 'approved') return r.used ? 'Approved · used' : 'Approved · unused';
    return r.status.charAt(0).toUpperCase() + r.status.slice(1);
};
const formatDate = (v) => v ? new Date(v).toLocaleString() : '—';

const viewDialogOpen = ref(false);
const viewTarget = ref(null);

const openView = (r) => {
    viewTarget.value = r;
    viewDialogOpen.value = true;
};

const approve = (r) => {
    busyId.value = r.id;
    router.patch(`/regional-kpi-edit-requests/${r.id}/approve`, {}, {
        preserveScroll: true,
        onFinish: () => { busyId.value = null; },
    });
};

const rejectDialogOpen = ref(false);
const rejectTarget = ref(null);
const rejectNote = ref('');

const openReject = (r) => {
    rejectTarget.value = r;
    rejectNote.value = '';
    rejectDialogOpen.value = true;
};

const confirmReject = () => {
    if (!rejectTarget.value) return;
    busyId.value = rejectTarget.value.id;
    router.patch(`/regional-kpi-edit-requests/${rejectTarget.value.id}/reject`, { response_note: rejectNote.value }, {
        preserveScroll: true,
        onSuccess: () => { rejectDialogOpen.value = false; },
        onFinish: () => { busyId.value = null; },
    });
};
</script>

<style scoped>
.req-th {
    font-size: 0.65rem !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(var(--v-theme-on-surface), 0.5) !important;
    background: rgba(var(--v-theme-surface-variant), 0.4) !important;
    padding: 8px 12px !important;
    white-space: nowrap;
}
.view-grid {
    display: grid;
    grid-template-columns: 120px 1fr;
    row-gap: 8px;
    column-gap: 12px;
    align-items: center;
}
.view-label {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(var(--v-theme-on-surface), 0.5);
}
</style>
