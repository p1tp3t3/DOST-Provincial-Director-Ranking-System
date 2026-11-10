<script setup>
import { computed } from 'vue';

// One KPI correction request as every screen shows it: the period, where it is
// in the flow, the province's reason, and each step so far with its note.
// Shape: KpiCorrectionService::present(). Buttons go in the `actions` slot.
const props = defineProps({
    request:      { type: Object,  required: true },
    showProvince: { type: Boolean, default: false },
});

const STATUS = {
    pending_regional: { color: 'amber-darken-3',  icon: 'mdi-account-clock-outline' },
    pending_central:  { color: 'blue-darken-1',   icon: 'mdi-bank-outline' },
    returned:         { color: 'orange-darken-3', icon: 'mdi-undo-variant' },
    approved:         { color: 'success',         icon: 'mdi-lock-open-variant-outline' },
    declined:         { color: 'error',           icon: 'mdi-close-circle-outline' },
};
const status = computed(() => {
    if (props.request.status === 'approved' && !props.request.is_open) return { color: 'blue-grey', icon: 'mdi-lock-outline' };
    return STATUS[props.request.status] ?? { color: 'grey', icon: 'mdi-help-circle-outline' };
});

// Plain dates ("2026-10-21") are read as that local day, not UTC midnight.
const formatDate = (iso) => (iso
    ? new Date(iso.length === 10 ? `${iso}T00:00:00` : iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
    : '');
const formatWhen = (iso) => (iso ? new Date(iso).toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '');

// A reviewer's or the province's own words.
const quote = (s) => (s ? `“${s}”` : null);

// One recorded step (KpiCorrectionRequest::addStep) as a timeline line.
const fromHistory = (h, r) => ({
    requested:   { icon: 'mdi-send-outline', color: 'indigo', title: `Requested by ${h.by}`,
                   // The first reason, when the province has since rewritten it.
                   note: h.note && h.note !== r.reason ? `First reason: ${quote(h.note)}` : null },
    returned:    { icon: 'mdi-undo-variant', color: 'orange-darken-3', title: `Returned to the province by ${h.by}`, note: quote(h.note) },
    resubmitted: { icon: 'mdi-send-outline', color: 'indigo', title: `Resubmitted by ${h.by} with an updated reason` },
    forwarded:   { icon: 'mdi-share-outline', color: 'blue-darken-1', title: `Forwarded to the central office by ${h.by}`, note: quote(h.note) },
    approved:    { icon: 'mdi-check-decagram', color: 'success', title: `Approved by ${h.by} · open until ${formatDate(h.open_until)}`, note: quote(h.note) },
    declined:    { icon: 'mdi-close-circle', color: 'error', title: `Declined by ${h.by}`, note: quote(h.note) },
}[h.step] ?? { icon: 'mdi-circle-small', color: 'grey', title: h.step });

// What has happened so far, oldest first, then what it's waiting on.
const steps = computed(() => {
    const r   = props.request;
    const out = r.history?.length
        ? r.history.map(h => ({ ...fromHistory(h, r), at: h.at }))
        : legacySteps(r);

    if (r.applied_at) out.push({ icon: 'mdi-content-save-check-outline', color: 'teal', title: 'Changes saved', at: r.applied_at });
    if (r.revised_at) out.push({ icon: 'mdi-history', color: 'deep-orange', title: 'Released rankings revised', at: r.revised_at });
    if (r.status === 'approved' && !r.is_open && !r.applied_at) {
        out.push({ icon: 'mdi-lock-outline', color: 'blue-grey', title: 'Window closed with no changes saved' });
    }

    const waiting = {
        pending_regional: 'Waiting for the regional director',
        pending_central:  'Waiting for the central office',
        returned:         'Waiting for the province to update and resubmit',
    }[r.status];
    if (waiting) out.push({ icon: 'mdi-clock-outline', color: 'grey', title: waiting, pending: true });

    return out;
});

// Requests recorded before the step history existed: rebuilt from the
// latest regional and central review.
function legacySteps(r) {
    const out = [{ icon: 'mdi-send-outline', color: 'indigo', title: `Requested by ${r.requested_by ?? 'the province'}`, at: r.requested_at }];
    if (r.regional) {
        // A regional review on a request back at the region was a return the
        // province has since answered.
        const returned = r.status === 'returned' || r.status === 'pending_regional';
        out.push(returned
            ? { icon: 'mdi-undo-variant', color: 'orange-darken-3', title: `Returned by ${r.regional.by}`, at: r.regional.at, note: quote(r.regional.note) }
            : { icon: 'mdi-share-outline', color: 'blue-darken-1', title: `Forwarded to the central office by ${r.regional.by}`, at: r.regional.at, note: quote(r.regional.note) });
        if (r.status === 'pending_regional') {
            out.push({ icon: 'mdi-send-outline', color: 'indigo', title: 'Resubmitted with an updated reason' });
        }
    }
    if (r.central) {
        out.push(r.status === 'approved'
            ? { icon: 'mdi-check-decagram', color: 'success', title: `Approved by ${r.central.by} · open until ${formatDate(r.open_until)}`, at: r.central.at, note: quote(r.central.note) }
            : { icon: 'mdi-close-circle', color: 'error', title: `Declined by ${r.central.by}`, at: r.central.at, note: quote(r.central.note) });
    }
    return out;
}
</script>

<template>
    <div class="cr" :class="[`cr--${request.status}`, { 'cr--closed': request.status === 'approved' && !request.is_open }]">
        <div class="cr-head">
            <div class="cr-title">
                <template v-if="showProvince">
                    <span class="cr-province">{{ request.province }}</span>
                    <span v-if="request.region" class="cr-region">{{ request.region }}</span>
                </template>
                <v-chip size="small" variant="tonal" color="indigo" class="font-weight-bold">
                    <v-icon start size="14">mdi-calendar-range</v-icon>{{ request.period }}
                </v-chip>
            </div>
            <v-chip size="small" :color="status.color" variant="flat" class="font-weight-medium">
                <v-icon start size="14">{{ status.icon }}</v-icon>{{ request.status_label }}
            </v-chip>
        </div>

        <div class="cr-reason">
            <div class="cr-label">Reason</div>
            <div class="cr-reason-text">{{ request.reason }}</div>
        </div>

        <ol class="cr-steps">
            <li v-for="(s, i) in steps" :key="i" class="cr-step" :class="{ 'cr-step--pending': s.pending }">
                <v-icon size="16" :color="s.color" class="cr-step-icon">{{ s.icon }}</v-icon>
                <div class="cr-step-body">
                    <div class="cr-step-title">
                        {{ s.title }}
                        <span v-if="s.at" class="cr-step-at">· {{ formatWhen(s.at) }}</span>
                    </div>
                    <div v-if="s.note" class="cr-step-note">{{ s.note }}</div>
                </div>
            </li>
        </ol>

        <div v-if="$slots.actions" class="cr-actions">
            <slot name="actions" />
        </div>
    </div>
</template>

<style scoped>
.cr { border: 1px solid var(--bd-e2e8f0); border-left: 4px solid #94a3b8; border-radius: 10px; background: var(--bg-fff); padding: 14px 16px; }
.cr--pending_regional { border-left-color: #d97706; }
.cr--pending_central  { border-left-color: #2563eb; }
.cr--returned         { border-left-color: #ea580c; }
.cr--approved         { border-left-color: #16a34a; }
.cr--declined         { border-left-color: #dc2626; }
.cr--approved.cr--closed { border-left-color: #94a3b8; }

.cr-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.cr-title { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.cr-province { font-size: 15px; font-weight: 700; color: var(--tx-0f172a); }
.cr-region { font-size: 12px; color: var(--tx-64748b); }

.cr-reason { margin-top: 10px; padding: 10px 12px; background: var(--bg-f8fafc); border-radius: 8px; }
.cr-label { font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--tx-64748b); margin-bottom: 2px; }
.cr-reason-text { font-size: 14px; color: var(--tx-1e293b); white-space: pre-wrap; line-height: 1.5; }

.cr-steps { list-style: none; margin: 12px 0 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
.cr-step { display: flex; gap: 8px; align-items: flex-start; }
.cr-step-icon { margin-top: 1px; flex-shrink: 0; }
.cr-step-title { font-size: 13px; color: var(--tx-1e293b); font-weight: 500; }
.cr-step-at { color: var(--tx-64748b); font-weight: 400; }
.cr-step-note { font-size: 13px; color: var(--tx-475569); font-style: italic; margin-top: 2px; white-space: pre-wrap; }
.cr-step--pending .cr-step-title { color: var(--tx-64748b); font-style: italic; font-weight: 400; }

.cr-actions { display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--bd-f1f5f9); }
</style>
