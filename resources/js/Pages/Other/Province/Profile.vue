<template>
    <AuthenticatedLayout>
        <div class="d-flex flex-column gap-3">

            <!-- Breadcrumb -->
            <div class="d-flex align-center gap-2">
                <v-btn icon size="x-small" variant="text" color="medium-emphasis" @click="router.visit('/province-directories')">
                    <v-icon size="18">mdi-arrow-left</v-icon>
                </v-btn>
                <span class="text-caption text-medium-emphasis">Province Directories</span>
                <v-icon size="12" color="medium-emphasis">mdi-chevron-right</v-icon>
                <span class="text-caption font-weight-medium">{{ profile.name }}</span>
            </div>

            <!-- Hero Card -->
            <v-card elevation="0" border rounded="lg" class="overflow-hidden">
                <div class="hero-banner px-5 py-4">
                    <div class="d-flex align-center gap-3">
                        <v-avatar color="white" size="44" rounded="lg" style="opacity:0.9;">
                            <v-icon color="primary" size="22">mdi-map-marker-outline</v-icon>
                        </v-avatar>
                        <div>
                            <div class="text-h6 font-weight-bold text-white">{{ profile.name }}</div>
                            <v-chip size="x-small" color="white" variant="tonal" class="mt-1 font-weight-medium text-white">
                                {{ profile.category_label ?? profile.category }}
                            </v-chip>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap">
                    <div class="stat-panel flex-grow-1 pa-4 d-flex align-center gap-4">
                        <div class="stat-item">
                            <div class="stat-label">Total Employees</div>
                            <div class="stat-value">{{ employees.length }}</div>
                        </div>
                        <v-divider vertical class="mx-2" style="height:36px;" />
                        <div class="stat-item">
                            <div class="stat-label">Director Status</div>
                            <v-chip size="x-small" variant="tonal" :color="profile.provincial_director ? 'success' : 'warning'" class="mt-1 font-weight-medium">
                                {{ profile.provincial_director ? 'Assigned' : 'Vacant' }}
                            </v-chip>
                        </div>
                        <v-divider vertical class="mx-2" style="height:36px;" />
                        <div class="stat-item">
                            <div class="stat-label">Classification</div>
                            <v-chip size="x-small" variant="tonal" :color="categoryColor(profile.category)" class="mt-1 font-weight-medium text-capitalize">
                                {{ profile.category }}
                            </v-chip>
                        </div>
                    </div>

                    <v-divider vertical />

                    <div
                        class="director-panel pa-4 d-flex align-center gap-3"
                        style="min-width:300px;"
                        :class="profile.provincial_director ? 'director-clickable' : ''"
                        @click="profile.provincial_director && router.visit(`/profile/${profile.provincial_director.id}`)"
                    >
                        <template v-if="profile.provincial_director">
                            <v-avatar :color="nameColor(directorName)" size="44" rounded="lg">
                                <span class="text-white font-weight-bold" style="font-size:0.85rem;">{{ initials(directorName) }}</span>
                            </v-avatar>
                            <div style="min-width:0; flex:1;">
                                <div class="d-flex align-center gap-2">
                                    <div class="text-body-2 font-weight-bold text-truncate">{{ directorName }}</div>
                                    <v-icon size="13" color="primary" class="flex-shrink-0">mdi-open-in-new</v-icon>
                                </div>
                                <div class="text-caption text-medium-emphasis">{{ profile.provincial_director.dost_employee_id }}</div>
                                <div class="d-flex align-center gap-1 mt-1">
                                    <v-icon size="12" color="medium-emphasis">mdi-email-outline</v-icon>
                                    <span class="text-caption text-medium-emphasis text-truncate">{{ profile.provincial_director.email }}</span>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <v-icon size="28" color="warning">mdi-account-alert-outline</v-icon>
                            <div>
                                <div class="text-body-2 font-weight-medium text-warning">No Director Assigned</div>
                                <div class="text-caption text-medium-emphasis">Position currently vacant</div>
                            </div>
                        </template>
                    </div>
                </div>
            </v-card>

            <!-- KPI Section -->
            <v-card elevation="0" border rounded="lg">
                <!-- KPI Header -->
                <div class="d-flex align-center justify-space-between gap-3 px-4 pt-3 pb-3 flex-wrap">
                    <div class="d-flex align-center gap-2 flex-wrap">
                        <div class="text-subtitle-2 font-weight-bold">Key Performance Indicators</div>
                        <v-chip size="x-small" variant="tonal" color="indigo">{{ kpis.length }} Outcomes</v-chip>
                        <v-tooltip location="bottom" max-width="320">
                            <template #activator="{ props: tip }">
                                <v-chip v-bind="tip" size="x-small" variant="tonal" color="blue-grey" prepend-icon="mdi-scale-balance" class="cursor-pointer">
                                    RA 11914
                                </v-chip>
                            </template>
                            <div class="pa-1">
                                <div class="font-weight-bold mb-1">Republic Act 11914 (PSTO Act)</div>
                                <div class="text-caption mb-2 opacity-80">Each KPI outcome below is grounded in the functional mandates of Section 6 of the Act, which defines the duties of the Provincial Science and Technology Office.</div>
                                <div v-for="(sections, id) in RA11914" :key="id" class="text-caption d-flex gap-2 mb-1">
                                    <span class="font-weight-medium text-indigo-lighten-2">{{ sections.map(s => s.ref).join(', ') }}</span>
                                    <span class="opacity-70">{{ sections[0].label }}</span>
                                </div>
                            </div>
                        </v-tooltip>
                    </div>

                    <div class="d-flex align-center gap-2">
                        <!-- Year filter -->
                        <template v-if="availableYears.length > 0">
                            <v-btn-toggle v-model="selectedYear" mandatory density="compact" variant="outlined" divided>
                                <v-btn
                                    v-for="yr in availableYears"
                                    :key="yr"
                                    :value="yr"
                                    size="small"
                                    class="px-3"
                                >{{ yr }}</v-btn>
                            </v-btn-toggle>
                        </template>
                        <template v-else>
                            <v-chip size="x-small" variant="tonal" color="grey">No data entered yet</v-chip>
                        </template>

                        <v-divider vertical style="height:20px;" class="mx-1" />
                        <v-btn size="x-small" variant="text" color="primary" @click="openPanels = kpis.map(k => k.id)">Expand All</v-btn>
                        <v-btn size="x-small" variant="text" color="medium-emphasis" @click="openPanels = []">Collapse</v-btn>
                    </div>
                </div>
                <v-divider />

                <!-- Empty state -->
                <div v-if="!kpis.length" class="text-center py-10">
                    <v-icon size="36" color="grey-lighten-2" class="mb-2">mdi-chart-bar</v-icon>
                    <div class="text-body-2 text-medium-emphasis">No KPI data available. Run the seeder to populate.</div>
                </div>

                <!-- Accordion panels -->
                <v-expansion-panels v-else v-model="openPanels" multiple variant="accordion" flat>
                    <v-expansion-panel
                        v-for="(kpi, i) in kpis"
                        :key="kpi.id"
                        :value="kpi.id"
                        :rounded="false"
                        :class="{ 'border-bottom': i < kpis.length - 1 }"
                    >
                        <v-expansion-panel-title class="py-3 px-4">
                            <div class="d-flex align-center gap-3">
                                <v-avatar :color="kpiColor(i)" size="30" rounded="md">
                                    <v-icon size="15" color="white">{{ kpiIcon(i) }}</v-icon>
                                </v-avatar>
                                <div>
                                    <div>
                                        <span class="text-caption text-medium-emphasis me-1">Outcome {{ kpi.id }}</span>
                                        <span class="text-body-2 font-weight-medium">{{ kpi.outcome_title }}</span>
                                    </div>
                                    <div v-if="legalBasis(kpi.id).length" class="d-flex align-center gap-1 flex-wrap mt-1">
                                        <v-chip
                                            v-for="sec in legalBasis(kpi.id)"
                                            :key="sec.ref"
                                            size="x-small"
                                            variant="tonal"
                                            color="indigo"
                                            class="font-weight-medium"
                                        >RA 11914 {{ sec.ref }}</v-chip>
                                    </div>
                                </div>
                            </div>
                            <template #actions>
                                <v-chip size="x-small" variant="tonal" color="grey" class="me-2">
                                    {{ kpi.subrows.length }} indicators
                                </v-chip>
                            </template>
                        </v-expansion-panel-title>

                        <v-expansion-panel-text class="pa-0">
                            <v-table density="compact" class="kpi-table">
                                <thead>
                                    <tr>
                                        <th class="kpi-th" style="width:36px;">#</th>
                                        <th class="kpi-th">Indicator</th>
                                        <th class="kpi-th text-center" style="width:90px;">Target</th>
                                        <th class="kpi-th text-center" style="width:110px;">Accomplished</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, j) in kpi.subrows" :key="row.id" class="kpi-row">
                                        <td class="text-caption text-medium-emphasis text-center">{{ j + 1 }}</td>
                                        <td class="text-body-2 py-2" style="line-height:1.45;">{{ row.description }}</td>
                                        <td class="text-center">
                                            <span v-if="scoreFor(row, 'target') != null" class="text-body-2 font-weight-medium" style="white-space:pre-line;">
                                                {{ scoreFor(row, 'target') }}
                                            </span>
                                            <span v-else class="text-caption text-disabled">—</span>
                                        </td>
                                        <td class="text-center">
                                            <template v-if="scoreFor(row, 'accomplished') != null">
                                                <v-chip
                                                    size="x-small"
                                                    variant="tonal"
                                                    :color="accomplishedColor(row)"
                                                    class="font-weight-medium"
                                                    style="height:auto; white-space:pre-line;"
                                                >{{ scoreFor(row, 'accomplished') }}</v-chip>
                                            </template>
                                            <span v-else class="text-caption text-disabled">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </v-table>
                        </v-expansion-panel-text>
                    </v-expansion-panel>
                </v-expansion-panels>
            </v-card>

            <!-- Employees -->
            <v-card elevation="0" border rounded="lg">
                <div class="d-flex align-center justify-space-between gap-3 px-4 pt-3 pb-3 flex-wrap">
                    <div class="d-flex align-center gap-2">
                        <div class="text-subtitle-2 font-weight-bold">Employees</div>
                        <v-chip size="x-small" variant="tonal" color="primary">{{ employees.length }}</v-chip>
                    </div>
                    <v-text-field
                        v-model="search"
                        placeholder="Search name, ID or position..."
                        variant="solo-filled"
                        density="compact"
                        hide-details
                        clearable
                        prepend-inner-icon="mdi-magnify"
                        style="min-width:220px; max-width:280px;"
                    />
                </div>
                <v-divider />

                <v-data-table
                    :headers="headers"
                    :items="filtered"
                    density="compact"
                    hover
                    hide-default-footer
                    :items-per-page="-1"
                    @click:row="(_, { item }) => router.visit(`/profile/${item.profile_id}`)"
                    class="employee-table"
                >
                    <template #item.name="{ item }">
                        <div class="d-flex align-center gap-3 py-2">
                            <v-avatar :color="nameColor(item.name)" size="32" rounded="md">
                                <span class="text-white font-weight-bold" style="font-size:0.65rem;">{{ initials(item.name) }}</span>
                            </v-avatar>
                            <div>
                                <div class="text-body-2 font-weight-medium">{{ item.name }}</div>
                                <div class="text-caption text-medium-emphasis">{{ item.id }}</div>
                            </div>
                            <v-icon size="13" color="primary" class="ms-1 opacity-0 row-arrow">mdi-open-in-new</v-icon>
                        </div>
                    </template>
                    <template #item.position="{ item }">
                        <span class="text-body-2 text-medium-emphasis">{{ item.position }}</span>
                    </template>
                    <template #item.status="{ item }">
                        <v-chip v-if="item.status" size="x-small" variant="tonal" :color="item.status === 'permanent' ? 'teal' : 'orange'" class="text-capitalize font-weight-medium">
                            {{ item.status }}
                        </v-chip>
                        <span v-else class="text-caption text-medium-emphasis">—</span>
                    </template>
                    <template #item.actions>
                        <v-icon size="14" color="medium-emphasis">mdi-chevron-right</v-icon>
                    </template>
                    <template #no-data>
                        <div class="text-center py-10">
                            <v-icon size="32" color="grey-lighten-2" class="mb-2">mdi-account-search-outline</v-icon>
                            <div class="text-body-2 text-medium-emphasis">No employees found</div>
                        </div>
                    </template>
                </v-data-table>
            </v-card>

        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    province_profile: { type: Object },
    kpis:             { type: Array,  default: () => [] },
    available_years:  { type: Array,  default: () => [] },
});

const profile        = props.province_profile?.data[0] ?? {};
const kpis           = props.kpis ?? [];
const availableYears = props.available_years ?? [];

const search      = ref('');
const openPanels  = ref([]);
const selectedYear = ref(availableYears[0] ?? null);

// Look up target/accomplished for a subrow in the selected year
const scoreFor = (row, field) => {
    if (!selectedYear.value) return null;
    return row.scores?.[String(selectedYear.value)]?.[field] ?? null;
};

const accomplishedColor = (row) => {
    const a = parseFloat(scoreFor(row, 'accomplished'));
    const t = parseFloat(scoreFor(row, 'target'));
    if (isNaN(a) || isNaN(t)) return 'primary';
    return a >= t ? 'success' : 'warning';
};

const kpiIcons  = ['mdi-lightbulb-outline','mdi-rocket-launch-outline','mdi-flask-outline','mdi-trending-up','mdi-shield-outline','mdi-cog-outline','mdi-dots-horizontal-circle-outline'];
const kpiColors = ['indigo','teal','deep-purple','blue','green','orange','blue-grey'];
const kpiIcon   = (i) => kpiIcons[i % kpiIcons.length];
const kpiColor  = (i) => kpiColors[i % kpiColors.length];

// RA 11914 (PSTO Act) Section 6 — functional mandate per KPI outcome
const RA11914 = {
    1: [{ ref: '§6(a)', label: 'Stimulate and accelerate innovation' }],
    2: [{ ref: '§6(c)', label: 'Promote technology adoption and diffusion' }],
    3: [{ ref: '§6(h)', label: 'Foster a culture of science and technology' }],
    4: [{ ref: '§6(c)', label: 'Improve productivity and efficiency of MSMEs' }],
    5: [{ ref: '§6(b)', label: 'STI interventions for DRRM and climate change' }],
    6: [{ ref: '§6(k)', label: 'Perform STI governance functions' }],
    7: [
        { ref: '§6(e)', label: 'Implement SETUP' },
        { ref: '§6(f)', label: 'Implement CEST' },
        { ref: '§6(g)', label: 'Manage GIA programs and linkages' },
    ],
};
const legalBasis = (kpiId) => RA11914[kpiId] ?? [];

const headers = [
    { title: 'Employee', key: 'name',     sortable: true  },
    { title: 'Position', key: 'position', sortable: true  },
    { title: 'Status',   key: 'status',   sortable: true  },
    { title: '',         key: 'actions',  sortable: false, align: 'end', width: '36px' },
];

const directorName = computed(() => {
    const p = profile.provincial_director?.profile;
    if (!p) return '—';
    const middle = p.middle_name ? `${p.middle_name} ` : '';
    return `${p.first_name ?? ''} ${middle}${p.last_name ?? ''}`.trim();
});

const employees = computed(() =>
    (profile.employees ?? []).map(u => ({
        profile_id: u.id,
        id:         u.dost_employee_id ?? `#${u.id}`,
        name:       buildName(u.profile),
        position:   u.profile?.employee_profile?.position ?? u.profile?.position ?? '—',
        status:     u.profile?.employee_profile?.status ?? null,
    }))
);

const filtered = computed(() => {
    const q = search.value?.toLowerCase().trim();
    if (!q) return employees.value;
    return employees.value.filter(e =>
        e.name?.toLowerCase().includes(q) ||
        e.id?.toLowerCase().includes(q)   ||
        e.position?.toLowerCase().includes(q)
    );
});

const buildName = (p) => {
    if (!p) return '—';
    const middle = p.middle_name ? `${p.middle_name} ` : '';
    return `${p.first_name ?? ''} ${middle}${p.last_name ?? ''}`.trim();
};

const palette    = ['#5C6BC0','#42A5F5','#26A69A','#66BB6A','#FFA726','#EC407A','#AB47BC','#78909C'];
const nameColor  = (name = '') => palette[[...name].reduce((a, c) => a + c.charCodeAt(0), 0) % palette.length];
const initials   = (name = '') => name.split(' ').filter(Boolean).slice(0, 2).map(n => n[0]?.toUpperCase() ?? '').join('');
const categoryColor = (cat) => ({ micro: 'blue-grey', small: 'teal', medium: 'indigo', large: 'deep-purple', cstc: 'pink' }[cat] ?? 'grey');
</script>

<style scoped>
.hero-banner {
    background: linear-gradient(120deg, rgb(var(--v-theme-primary)) 0%, rgba(var(--v-theme-primary), 0.75) 100%);
}
.stat-panel { background: transparent; }
.stat-item  { display: flex; flex-direction: column; }
.stat-label {
    font-size: 0.65rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.05em; color: rgba(var(--v-theme-on-surface), 0.5); margin-bottom: 2px;
}
.stat-value { font-size: 1.25rem; font-weight: 700; line-height: 1; color: rgb(var(--v-theme-on-surface)); }
.director-panel { background: rgba(var(--v-theme-surface-variant), 0.3); }
.director-clickable { cursor: pointer; transition: background-color 0.15s; }
.director-clickable:hover { background: rgba(var(--v-theme-primary), 0.06); }

.border-bottom { border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)) !important; }

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
    padding: 5px 12px !important;
    border-bottom: 1px solid rgba(var(--v-border-color), 0.4) !important;
    vertical-align: middle;
}
.kpi-row:last-child td { border-bottom: none !important; }

.employee-table :deep(tr) { cursor: pointer; }
.employee-table :deep(tr:hover .row-arrow) { opacity: 1 !important; }
</style>
