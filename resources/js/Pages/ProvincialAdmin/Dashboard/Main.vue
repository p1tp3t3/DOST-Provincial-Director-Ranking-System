<template>
    <Head title="Dashboard" />
    <div class="d-flex flex-column gap-3">

        <!-- The quarter due next: deadline, completeness, and every quarter's state -->
        <v-card border elevation="0" rounded="lg" class="overflow-hidden">
            <div class="pa-5 d-flex align-center gap-6 flex-wrap">
                <div class="flex-grow-1" style="min-width:240px;">
                    <div class="eyebrow">{{ province.name }} · {{ reporting.year }} reporting</div>
                    <div class="hero-title">Q{{ reporting.focus_quarter }} {{ reporting.year }}</div>
                    <div class="hero-sub" :class="dueClass">
                        <v-icon size="16" class="mr-1">{{ dueIcon }}</v-icon>{{ dueText }}
                    </div>
                </div>

                <div class="progress-block">
                    <div class="d-flex justify-space-between text-caption mb-1">
                        <span>Q{{ reporting.focus_quarter }} figures entered</span>
                        <strong>{{ focus.entered }} / {{ reporting.expected.accomplished }}</strong>
                    </div>
                    <v-progress-linear :model-value="pct(focus.entered, reporting.expected.accomplished)" color="indigo" height="8" rounded bg-color="grey-lighten-3" />
                    <div class="d-flex justify-space-between text-caption mt-3 mb-1">
                        <span>{{ reporting.year }} targets set</span>
                        <strong>{{ reporting.targets_set }} / {{ reporting.expected.targets }}</strong>
                    </div>
                    <v-progress-linear :model-value="pct(reporting.targets_set, reporting.expected.targets)" color="teal" height="8" rounded bg-color="grey-lighten-3" />
                </div>

                <v-btn color="indigo" size="large" prepend-icon="mdi-pencil" @click="router.visit(`/provincial-kpi/${reporting.year}`)">
                    Open KPI Entry
                </v-btn>
            </div>
            <v-divider />
            <div class="quarter-strip">
                <div v-for="q in reporting.quarters" :key="q.quarter" class="quarter-card" :class="{ 'quarter-card--focus': q.quarter === reporting.focus_quarter }">
                    <div class="d-flex align-center justify-space-between">
                        <span class="quarter-name">Q{{ q.quarter }}</span>
                        <v-chip size="x-small" :color="q.open ? 'success' : 'blue-grey'" variant="tonal">
                            <v-icon start size="12">{{ q.open ? 'mdi-lock-open-variant-outline' : 'mdi-lock-outline' }}</v-icon>{{ q.open ? 'Open' : 'Locked' }}
                        </v-chip>
                    </div>
                    <div class="text-caption text-medium-emphasis mt-1">{{ q.entered }} / {{ reporting.expected.accomplished }} entered</div>
                    <v-progress-linear :model-value="pct(q.entered, reporting.expected.accomplished)" color="indigo" height="4" rounded bg-color="grey-lighten-3" class="mt-1" />
                    <div class="text-caption mt-2 d-flex align-center gap-1" :class="q.approved_by ? 'text-success' : 'text-medium-emphasis'">
                        <v-icon size="14">{{ q.approved_by ? 'mdi-check-decagram' : (q.has_data ? 'mdi-clock-outline' : 'mdi-minus-circle-outline') }}</v-icon>
                        {{ q.approved_by ? 'Approved' : (q.has_data ? 'Awaiting approval' : 'No figures yet') }}
                    </div>
                </div>
            </div>
        </v-card>

        <v-row dense>
            <!-- Needs attention -->
            <v-col cols="12" md="7">
                <v-card border elevation="0" rounded="lg" class="h-100">
                    <div class="card-head">
                        <v-icon size="16" color="indigo">mdi-alert-circle-outline</v-icon>
                        <span>Needs attention</span>
                        <v-chip v-if="attention.length" size="x-small" color="indigo" variant="tonal">{{ attention.length }}</v-chip>
                    </div>
                    <v-divider />
                    <div v-if="attention.length" class="py-1">
                        <Link v-for="(item, i) in attention" :key="i" :href="item.url" class="attention-row">
                            <v-icon size="18" :color="LEVELS[item.level].color">{{ LEVELS[item.level].icon }}</v-icon>
                            <span class="flex-grow-1">{{ item.text }}</span>
                            <v-icon size="16" color="medium-emphasis">mdi-chevron-right</v-icon>
                        </Link>
                    </div>
                    <div v-else class="empty-state">
                        <v-icon size="28" color="success">mdi-check-circle-outline</v-icon>
                        <div>All caught up for {{ reporting.year }}.</div>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" md="5" class="d-flex flex-column gap-3">
                <!-- Standing, compact -->
                <v-card border elevation="0" rounded="lg">
                    <div class="card-head">
                        <v-icon size="16" color="indigo">mdi-podium</v-icon>
                        <span>Official standing</span>
                        <v-chip v-if="standing?.revised_at" size="x-small" color="deep-orange" variant="tonal" prepend-icon="mdi-history" class="ml-auto"
                                :title="'Figures were corrected after these rankings were released'">
                            Revised {{ new Date(standing.revised_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) }}
                        </v-chip>
                        <span v-if="standing" class="text-caption text-medium-emphasis" :class="{ 'ml-auto': !standing.revised_at }">{{ standing.year }}</span>
                    </div>
                    <v-divider />
                    <div v-if="standing?.status === 'ranked'" class="pa-4 d-flex align-center gap-4">
                        <div>
                            <div class="standing-rank">#{{ standing.rank }} <span>of {{ standing.tier_size }}</span></div>
                            <div class="text-caption text-medium-emphasis text-capitalize">{{ standing.tier }} tier</div>
                        </div>
                        <div class="ml-auto text-right">
                            <div class="standing-score">{{ fmt(standing.total_pct) }}%</div>
                            <v-chip v-if="standing.bucket" size="x-small" :color="BUCKETS[standing.bucket]" variant="tonal" class="font-weight-medium">{{ standing.bucket }} group</v-chip>
                        </div>
                    </div>
                    <div v-else class="pa-4 text-body-2 text-medium-emphasis">
                        {{ standing ? `Not ranked for ${standing.year}: ${standing.status === 'no_director' ? 'no provincial director assigned' : 'no figures submitted'}.` : 'No rankings released yet.' }}
                    </div>
                    <div v-if="release && !release.released" class="px-4 pb-3">
                        <ReleaseNotice :status="release" compact />
                    </div>
                </v-card>

                <!-- Staff -->
                <v-card border elevation="0" rounded="lg">
                    <div class="card-head">
                        <v-icon size="16" color="indigo">mdi-account-group-outline</v-icon>
                        <span>Accounts</span>
                        <Link href="/users" class="head-link ml-auto">Manage users →</Link>
                    </div>
                    <v-divider />
                    <div class="stat-grid">
                        <div><div class="stat-num">{{ staff.staff }}</div><div class="stat-label">Staff</div></div>
                        <div><div class="stat-num">{{ staff.accounts }}</div><div class="stat-label">All accounts</div></div>
                        <div><div class="stat-num" :class="{ 'text-error': staff.deactivated }">{{ staff.deactivated }}</div><div class="stat-label">Deactivated</div></div>
                    </div>
                    <div class="px-4 pb-3 text-caption text-medium-emphasis">
                        Provincial director: <strong :class="staff.director ? 'text-high-emphasis' : 'text-error'">{{ staff.director ?? 'Vacant' }}</strong>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <v-row dense>
            <!-- Latest entries -->
            <v-col cols="12" md="7">
                <v-card border elevation="0" rounded="lg" class="h-100">
                    <div class="card-head">
                        <v-icon size="16" color="indigo">mdi-history</v-icon>
                        <span>Latest entries</span>
                    </div>
                    <v-divider />
                    <div v-if="recent_entries.length">
                        <div v-for="(e, i) in recent_entries" :key="i" class="entry-row">
                            <div class="flex-grow-1" style="min-width:0;">
                                <div class="text-body-2 text-truncate">{{ e.kpi }}</div>
                                <div class="text-caption text-medium-emphasis">Q{{ e.quarter }} {{ e.year }} · {{ e.by }} · {{ ago(e.at) }}</div>
                            </div>
                            <div class="entry-value">{{ e.accomplished === null ? 'cleared' : fmt(e.accomplished) }}</div>
                        </div>
                    </div>
                    <div v-else class="empty-state">
                        <v-icon size="28" color="blue-grey">mdi-pencil-outline</v-icon>
                        <div>No quarterly figures entered yet.</div>
                    </div>
                </v-card>
            </v-col>

            <!-- Evidence records -->
            <v-col cols="12" md="5">
                <v-card border elevation="0" rounded="lg" class="h-100">
                    <div class="card-head">
                        <v-icon size="16" color="teal">mdi-shield-check-outline</v-icon>
                        <span>{{ records.year }} evidence records</span>
                    </div>
                    <v-divider />
                    <div class="record-row">
                        <span class="flex-grow-1">Linkages (signed MOA / MOU)</span>
                        <strong class="mr-3">{{ records.linkages }}</strong>
                        <Link v-if="records.linkages_url" :href="records.linkages_url" class="head-link">Manage →</Link>
                    </div>
                    <div class="record-row">
                        <span class="flex-grow-1">Facebook posts</span>
                        <strong class="mr-3">{{ records.facebook_posts }}</strong>
                        <Link v-if="records.posts_url" :href="records.posts_url" class="head-link">Manage →</Link>
                    </div>
                    <div v-if="!records.linkages_url" class="px-4 pb-3 text-caption text-medium-emphasis">Records can be managed once a provincial director is assigned.</div>
                </v-card>
            </v-col>
        </v-row>

    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useKpiCatalogSync } from '@/Composables/useKpiCatalogSync';
import ReleaseNotice from '@/Components/Dashboard/ReleaseNotice.vue';

const props = defineProps({
    province:       { type: Object, required: true },
    // { year, focus_quarter, focus_deadline, days_left, expected: { accomplished, targets },
    //   targets_set, targets_open, quarters: [{ quarter, entered, has_data, deadline, open, approved_by, approved_at }] }
    reporting:      { type: Object, required: true },
    attention:      { type: Array,  default: () => [] },   // [{ level, text, url }]
    records:        { type: Object, required: true },
    recent_entries: { type: Array,  default: () => [] },
    staff:          { type: Object, required: true },
    standing:       { type: Object, default: null },
    // The current year's ranking release: { year, released, submitted, total, deadline }
    release:        { type: Object, default: null },
});

// A catalog change can move the standing and the expected counts.
useKpiCatalogSync(['reporting', 'attention', 'standing']);

const LEVELS = {
    error:   { color: 'error',   icon: 'mdi-alert-octagon-outline' },
    warning: { color: 'warning', icon: 'mdi-alert-outline' },
    info:    { color: 'indigo',  icon: 'mdi-information-outline' },
};
const BUCKETS = { Top: '#ca8a04', Average: '#64748b', Low: '#9a3412' };

const focus = computed(() => props.reporting.quarters.find(q => q.quarter === props.reporting.focus_quarter));

const formatDate = (d) => new Date(`${d}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
const dueText = computed(() => {
    const r = props.reporting;
    if (!r.focus_deadline) return 'No deadline set yet';
    if (r.days_left < 0)   return `Closed ${formatDate(r.focus_deadline)}`;
    if (r.days_left === 0) return `Due today, ${formatDate(r.focus_deadline)}`;
    return `Due ${formatDate(r.focus_deadline)} · ${r.days_left} ${r.days_left === 1 ? 'day' : 'days'} left`;
});
const dueClass = computed(() => {
    const d = props.reporting.days_left;
    if (d === null) return 'text-medium-emphasis';
    return d <= 3 ? 'text-error' : d <= 14 ? 'text-warning' : 'text-success';
});
const dueIcon = computed(() => (props.reporting.focus_deadline ? 'mdi-calendar-clock' : 'mdi-calendar-blank-outline'));

const pct = (n, d) => (d ? Math.min(100, (n / d) * 100) : 0);
const fmt = (v) => (v === null || v === undefined ? '—' : Number(v).toLocaleString(undefined, { maximumFractionDigits: 2 }));

const ago = (at) => {
    if (!at) return '';
    const mins = Math.round((Date.now() - new Date(at.replace(' ', 'T'))) / 60000);
    if (mins < 1)  return 'just now';
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.round(mins / 60);
    if (hrs < 24)  return `${hrs}h ago`;
    return new Date(at.replace(' ', 'T')).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
};
</script>

<style scoped>
.eyebrow { font-size: 12px; font-weight: 600; color: var(--tx-64748b); text-transform: uppercase; letter-spacing: 0.04em; }
.hero-title { font-size: 30px; font-weight: 800; color: var(--tx-0f172a); line-height: 1.15; margin-top: 2px; }
.hero-sub { font-size: 14px; font-weight: 600; margin-top: 4px; display: flex; align-items: center; }
.progress-block { width: 300px; max-width: 100%; }

.quarter-strip { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; padding: 12px 16px; background: var(--bg-f8fafc); }
.quarter-card { background: var(--bg-fff); border: 1px solid var(--bd-e2e8f0); border-radius: 8px; padding: 10px 12px; }
.quarter-card--focus { border-color: #6366f1; box-shadow: 0 0 0 1px #6366f1; }
.quarter-name { font-weight: 700; font-size: 15px; color: var(--tx-0f172a); }

.card-head { display: flex; align-items: center; gap: 8px; padding: 12px 16px; font-size: 14px; font-weight: 700; color: var(--tx-0f172a); }
.head-link { font-size: 12px; font-weight: 600; color: var(--tx-4f46e5); text-decoration: none; }
.head-link:hover { text-decoration: underline; }

.attention-row { display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: inherit; text-decoration: none; font-size: 14px; border-bottom: 1px solid var(--bd-f1f5f9); }
.attention-row:last-child { border-bottom: none; }
.attention-row:hover { background: var(--bg-f8fafc); }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 28px 16px; color: var(--tx-64748b); font-size: 14px; }

.standing-rank { font-size: 28px; font-weight: 800; color: var(--tx-0f172a); line-height: 1; }
.standing-rank span { font-size: 15px; font-weight: 600; color: var(--tx-64748b); }
.standing-score { font-size: 22px; font-weight: 700; color: var(--tx-0f172a); margin-bottom: 4px; }

.stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); padding: 12px 16px; text-align: center; }
.stat-num { font-size: 22px; font-weight: 700; color: var(--tx-0f172a); }
.stat-label { font-size: 12px; color: var(--tx-64748b); }

.entry-row { display: flex; align-items: center; gap: 12px; padding: 9px 16px; border-bottom: 1px solid var(--bd-f1f5f9); }
.entry-row:last-child { border-bottom: none; }
.entry-value { font-weight: 700; font-size: 15px; color: var(--tx-0f172a); }
.record-row { display: flex; align-items: center; padding: 12px 16px; font-size: 14px; border-bottom: 1px solid var(--bd-f1f5f9); }
</style>
