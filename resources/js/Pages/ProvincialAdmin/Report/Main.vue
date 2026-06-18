<template>
    <div class="grid gap-4 w-full">

        <!-- Header -->
        <div class="d-flex align-center justify-space-between flex-wrap gap-2">
            <div>
                <div class="text-h6 font-weight-bold">
                    {{ province?.name ?? 'Province' }} — Report
                </div>
                <div class="text-caption text-medium-emphasis">
                    Employee summary, director KPI, and activity overview
                </div>
            </div>
            <div class="d-flex align-center gap-2">
                <v-alert
                    v-if="exportError"
                    type="error"
                    variant="tonal"
                    density="compact"
                    closable
                    class="text-caption py-1"
                    @click:close="exportError = ''"
                >{{ exportError }}</v-alert>
                <v-btn
                    color="primary"
                    variant="tonal"
                    size="small"
                    prepend-icon="mdi-file-chart-outline"
                    :loading="exporting"
                    @click="exportPDF"
                >Export PDF</v-btn>
            </div>
        </div>

        <!-- Province info banner -->
        <v-card v-if="province?.name" class="elevation-1 border-0 rounded-md pa-4">
            <div class="d-flex align-center gap-4 flex-wrap">
                <v-avatar color="teal-lighten-5" rounded="lg" size="44">
                    <v-icon color="teal" size="24">mdi-map-marker-outline</v-icon>
                </v-avatar>
                <div>
                    <div class="text-subtitle-1 font-weight-bold">{{ province.name }}</div>
                    <div class="d-flex gap-2 mt-1 flex-wrap">
                        <v-chip v-if="province.category"          size="x-small" variant="tonal" color="teal">{{ province.category }}</v-chip>
                        <v-chip v-if="province.director"          size="x-small" variant="tonal" color="blue" prepend-icon="mdi-account-tie-outline">{{ province.director }}</v-chip>
                        <v-chip v-if="province.num_municipalities" size="x-small" variant="tonal" color="grey" prepend-icon="mdi-city-variant-outline">{{ province.num_municipalities }} municipalities</v-chip>
                        <v-chip v-if="province.num_cities"        size="x-small" variant="tonal" color="grey" prepend-icon="mdi-office-building-outline">{{ province.num_cities }} cities</v-chip>
                        <v-chip v-if="province.num_plantilla"     size="x-small" variant="tonal" color="purple" prepend-icon="mdi-account-group-outline">{{ province.num_plantilla }} plantilla</v-chip>
                    </div>
                </div>
            </div>
        </v-card>

        <!-- Summary stat cards -->
        <v-row dense>
            <v-col v-for="card in summaryCards" :key="card.label" cols="6" sm="4" md="2">
                <v-card class="elevation-1 border-0 rounded-md pa-4">
                    <v-avatar :color="card.color + '-lighten-5'" rounded="lg" size="36" class="mb-3">
                        <v-icon :color="card.color" size="17">{{ card.icon }}</v-icon>
                    </v-avatar>
                    <div class="text-h6 font-weight-bold">{{ card.value }}</div>
                    <div class="text-caption text-medium-emphasis">{{ card.label }}</div>
                </v-card>
            </v-col>
        </v-row>

        <!-- Employee list + Director KPI -->
        <v-row>

            <!-- Employee list -->
            <v-col cols="12" md="8">
                <v-card class="elevation-1 border-0 rounded-md h-100">
                    <div class="px-5 pt-4 pb-3 d-flex align-center justify-space-between flex-wrap gap-2">
                        <div class="text-subtitle-2 font-weight-bold">Employees</div>
                        <v-text-field
                            v-model="search"
                            placeholder="Search name, ID, or position…"
                            variant="outlined"
                            density="compact"
                            prepend-inner-icon="mdi-magnify"
                            clearable
                            hide-details
                            style="max-width:220px;"
                        />
                    </div>
                    <v-divider></v-divider>
                    <v-table density="comfortable" hover>
                        <thead>
                            <tr>
                                <th class="text-caption text-medium-emphasis">Name</th>
                                <th class="text-caption text-medium-emphasis">DOST ID</th>
                                <th class="text-caption text-medium-emphasis">Position</th>
                                <th class="text-caption text-medium-emphasis">Status</th>
                                <th class="text-caption text-medium-emphasis text-center">Yrs of Service</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="e in filteredEmployees" :key="e.id">
                                <td>
                                    <div class="d-flex align-center gap-2 py-1">
                                        <v-avatar color="success-lighten-5" size="28" rounded="sm">
                                            <v-icon color="success" size="14">mdi-account-outline</v-icon>
                                        </v-avatar>
                                        <span class="text-body-2 font-weight-medium">{{ e.name || '—' }}</span>
                                    </div>
                                </td>
                                <td class="text-caption text-medium-emphasis">{{ e.dost_id }}</td>
                                <td class="text-body-2">{{ e.position }}</td>
                                <td>
                                    <v-chip size="x-small" variant="tonal" :color="statusColor(e.status)">
                                        {{ statusLabel(e.status) }}
                                    </v-chip>
                                </td>
                                <td class="text-center text-body-2">{{ e.length_of_service }}</td>
                            </tr>
                            <tr v-if="!filteredEmployees.length">
                                <td colspan="5" class="text-center text-caption text-medium-emphasis py-6">No employees found.</td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card>
            </v-col>

            <!-- Director KPI -->
            <v-col cols="12" md="4">
                <v-card class="elevation-1 border-0 rounded-md h-100">
                    <div class="px-5 pt-4 pb-3">
                        <div class="text-subtitle-2 font-weight-bold">Director KPI</div>
                        <div class="text-caption text-medium-emphasis">{{ province?.director }}</div>
                    </div>
                    <v-divider></v-divider>

                    <div v-if="directorKpi?.total_items > 0" class="pa-5 d-flex flex-column align-center gap-4">

                        <!-- Rate gauge -->
                        <div class="text-center">
                            <div class="text-h3 font-weight-bold" :style="{ color: rateColor(directorKpi.rate) }">
                                {{ directorKpi.rate }}%
                            </div>
                            <div class="text-caption text-medium-emphasis mt-1">Accomplishment Rate</div>
                        </div>

                        <v-progress-linear
                            :model-value="directorKpi.rate"
                            :color="rateColor(directorKpi.rate)"
                            height="10"
                            rounded
                            bg-color="grey-lighten-3"
                            class="w-100"
                        />

                        <v-row dense class="w-100 text-center">
                            <v-col cols="4">
                                <div class="text-h6 font-weight-bold text-primary">{{ directorKpi.total_items }}</div>
                                <div class="text-caption text-medium-emphasis">KPI Items</div>
                            </v-col>
                            <v-col cols="4">
                                <div class="text-h6 font-weight-bold text-success">{{ directorKpi.accomplished }}</div>
                                <div class="text-caption text-medium-emphasis">Accomplished</div>
                            </v-col>
                            <v-col cols="4">
                                <div class="text-h6 font-weight-bold">{{ directorKpi.target }}</div>
                                <div class="text-caption text-medium-emphasis">Target</div>
                            </v-col>
                        </v-row>

                        <v-chip
                            size="small"
                            :color="rateColor(directorKpi.rate)"
                            variant="tonal"
                            :prepend-icon="directorKpi.rate >= 90 ? 'mdi-check-circle-outline' : directorKpi.rate >= 75 ? 'mdi-alert-circle-outline' : 'mdi-close-circle-outline'"
                        >
                            {{ directorKpi.rate >= 90 ? 'On Track' : directorKpi.rate >= 75 ? 'Needs Attention' : 'Below Target' }}
                        </v-chip>

                    </div>
                    <div v-else class="pa-6 text-center text-caption text-medium-emphasis">
                        No director KPI data available for this province.
                    </div>
                </v-card>
            </v-col>

        </v-row>

        <!-- Staff breakdown -->
        <div>
            <div class="text-subtitle-2 font-weight-bold mb-3">Staff Breakdown</div>
            <v-row dense>
                <v-col v-for="item in staffBreakdown" :key="item.label" cols="6" sm="4" md="3">
                    <v-card class="elevation-1 border-0 rounded-md pa-4">
                        <div class="d-flex align-center gap-3">
                            <v-avatar :color="item.color + '-lighten-5'" rounded="lg" size="36">
                                <v-icon :color="item.color" size="17">{{ item.icon }}</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-h6 font-weight-bold">{{ item.value }}</div>
                                <div class="text-caption text-medium-emphasis">{{ item.label }}</div>
                            </div>
                        </div>
                        <v-progress-linear
                            v-if="us.total > 0"
                            :model-value="Math.round(item.value / us.total * 100)"
                            :color="item.color"
                            height="4"
                            rounded
                            bg-color="grey-lighten-3"
                            class="mt-3"
                        />
                    </v-card>
                </v-col>
            </v-row>
        </div>

        <!-- Activity Log Summary -->
        <div>
            <div class="text-subtitle-2 font-weight-bold mb-3">Activity Log Summary</div>
            <v-row dense>
                <v-col v-for="stat in logStatCards" :key="stat.label" cols="6" sm="4" md="3">
                    <v-card class="elevation-1 border-0 rounded-md pa-4">
                        <div class="d-flex align-center gap-3">
                            <v-avatar :color="stat.color + '-lighten-5'" rounded="lg" size="36">
                                <v-icon :color="stat.color" size="17">{{ stat.icon }}</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-h6 font-weight-bold">{{ stat.count }}</div>
                                <div class="text-caption text-medium-emphasis">{{ stat.label }}</div>
                            </div>
                        </div>
                    </v-card>
                </v-col>
            </v-row>
        </div>

        <!-- Recent Activity -->
        <v-card class="elevation-1 border-0 rounded-md">
            <div class="px-5 pt-4 pb-3 d-flex align-center justify-space-between">
                <div class="text-subtitle-2 font-weight-bold">Recent Activity</div>
                <v-chip size="small" variant="tonal" color="primary">{{ recentLogs.length }} entries</v-chip>
            </div>
            <v-divider></v-divider>
            <v-table density="comfortable" hover>
                <thead>
                    <tr>
                        <th class="text-caption text-medium-emphasis">User</th>
                        <th class="text-caption text-medium-emphasis">Role</th>
                        <th class="text-caption text-medium-emphasis">Action</th>
                        <th class="text-caption text-medium-emphasis">Description</th>
                        <th class="text-caption text-medium-emphasis" width="160">Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in recentLogs" :key="log.id">
                        <td>
                            <div class="text-body-2 font-weight-medium">{{ log.name || '—' }}</div>
                            <div class="text-caption text-medium-emphasis">{{ log.dost_id }}</div>
                        </td>
                        <td>
                            <v-chip size="x-small" variant="tonal" :color="roleColor(log.role)">{{ roleLabel(log.role) }}</v-chip>
                        </td>
                        <td>
                            <v-chip size="x-small" variant="tonal" :color="typeColor(log.type)">
                                <v-icon start size="10">{{ typeIcon(log.type) }}</v-icon>
                                {{ log.type }}
                            </v-chip>
                        </td>
                        <td class="text-body-2 text-medium-emphasis" style="max-width:260px;">
                            <span class="text-truncate d-block">{{ log.description }}</span>
                        </td>
                        <td class="text-caption text-medium-emphasis">{{ log.created_at }}</td>
                    </tr>
                    <tr v-if="!recentLogs.length">
                        <td colspan="5" class="text-center text-caption text-medium-emphasis py-6">No recent activity.</td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>

    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    province:      { type: Object, default: null },
    user_stats:    { type: Object, default: null },
    employee_list: { type: Array,  default: null },
    director_kpi:  { type: Object, default: null },
    log_stats:     { type: Object, default: null },
    recent_logs:   { type: Array,  default: null },
});

// ── Test data ─────────────────────────────────────────────────
const testProvince = {
    id: 1, name: 'Davao del Sur', category: '1st class',
    director: 'Maria Santos', num_municipalities: 10, num_cities: 2, num_plantilla: 45,
};

const testUserStats = {
    total: 26, employees: 18, admins: 3, sub_admins: 2, directors: 1,
    permanent: 10, cos: 5, jo: 3,
};

const testEmployees = [
    { id:1, name:'Juan Dela Cruz',  dost_id:'DOST-2026-EMP-001', position:'Science Research Analyst II',    status:'permanent', length_of_service:'5' },
    { id:2, name:'Maria Santos',    dost_id:'DOST-2026-EMP-002', position:'Administrative Officer III',     status:'cos',       length_of_service:'3' },
    { id:3, name:'Jose Villanueva', dost_id:'DOST-2026-EMP-003', position:'Project Development Officer II', status:'permanent', length_of_service:'7' },
    { id:4, name:'Ana Bautista',    dost_id:'DOST-2026-EMP-004', position:'Engineer II',                    status:'jo',        length_of_service:'1' },
    { id:5, name:'Roberto Mendoza', dost_id:'DOST-2026-EMP-005', position:'Accountant III',                 status:'permanent', length_of_service:'9' },
    { id:6, name:'Carla Garcia',    dost_id:'DOST-2026-EMP-006', position:'IT Officer I',                   status:'cos',       length_of_service:'2' },
    { id:7, name:'Eduardo Reyes',   dost_id:'DOST-2026-EMP-007', position:'Science Research Specialist II', status:'permanent', length_of_service:'6' },
    { id:8, name:'Luz Fernandez',   dost_id:'DOST-2026-EMP-008', position:'Administrative Aide VI',         status:'jo',        length_of_service:'1' },
];

const testDirectorKpi = { director_id:1, target:120, accomplished:108, rate:90.0, total_items:15 };

const testLogStats = { login:44, logout:38, create:12, update:21, delete:3, generate:8, export:5, view:67 };

const testRecentLogs = [
    { id:1, name:'Maria Santos',    dost_id:'DOST-DIR-001',       role:'provincial_director', type:'login',    description:'Director logged into the system.',           created_at:'Jun 1, 2026 8:02 AM'  },
    { id:2, name:'Juan Dela Cruz',  dost_id:'DOST-2026-EMP-001',  role:'employee',            type:'view',     description:'Viewed personal KPI dashboard.',              created_at:'Jun 1, 2026 8:30 AM'  },
    { id:3, name:'Pedro Admin',     dost_id:'DOST-ADM-002',       role:'provincial_admin',    type:'create',   description:'Created new employee account.',               created_at:'Jun 1, 2026 9:10 AM'  },
    { id:4, name:'Maria Santos',    dost_id:'DOST-DIR-001',       role:'provincial_director', type:'update',   description:'Updated KPI accomplishment for Q2 2026.',     created_at:'Jun 1, 2026 9:45 AM'  },
    { id:5, name:'Ana Bautista',    dost_id:'DOST-2026-EMP-004',  role:'employee',            type:'view',     description:'Viewed employee list.',                       created_at:'Jun 1, 2026 10:05 AM' },
    { id:6, name:'Pedro Admin',     dost_id:'DOST-ADM-002',       role:'provincial_admin',    type:'generate', description:'Generated 8 employee accounts via CSV.',      created_at:'Jun 1, 2026 10:30 AM' },
    { id:7, name:'Maria Santos',    dost_id:'DOST-DIR-001',       role:'provincial_director', type:'logout',   description:'Director logged out.',                        created_at:'Jun 1, 2026 11:00 AM' },
    { id:8, name:'Carla Garcia',    dost_id:'DOST-2026-EMP-006',  role:'employee',            type:'login',    description:'Employee logged into the system.',            created_at:'Jun 1, 2026 11:20 AM' },
];

// ── Resolved data ─────────────────────────────────────────────
const province    = computed(() => props.province      ?? testProvince);
const us          = computed(() => props.user_stats    ?? testUserStats);
const employees   = computed(() => props.employee_list ?? testEmployees);
const directorKpi = computed(() => props.director_kpi  ?? testDirectorKpi);
const ls          = computed(() => props.log_stats     ?? testLogStats);
const recentLogs  = computed(() => props.recent_logs   ?? testRecentLogs);

// ── Search ────────────────────────────────────────────────────
const search = ref('');
const filteredEmployees = computed(() => {
    if (!search.value.trim()) return employees.value;
    const q = search.value.toLowerCase();
    return employees.value.filter(e =>
        (e.name     ?? '').toLowerCase().includes(q) ||
        (e.dost_id  ?? '').toLowerCase().includes(q) ||
        (e.position ?? '').toLowerCase().includes(q)
    );
});

// ── Summary cards ─────────────────────────────────────────────
const summaryCards = computed(() => [
    { label: 'Total Users',  value: us.value.total,     color: 'primary', icon: 'mdi-account-group-outline'   },
    { label: 'Employees',    value: us.value.employees,  color: 'success', icon: 'mdi-account-outline'         },
    { label: 'Directors',    value: us.value.directors,  color: 'blue',    icon: 'mdi-account-tie-outline'     },
    { label: 'Admins',       value: us.value.admins,     color: 'indigo',  icon: 'mdi-account-cog-outline'     },
    { label: 'KPI Rate',     value: (directorKpi.value?.rate ?? 0) + '%', color: directorKpi.value?.rate >= 90 ? 'success' : directorKpi.value?.rate >= 75 ? 'warning' : 'error', icon: 'mdi-chart-line' },
]);

// ── Staff breakdown ───────────────────────────────────────────
const staffBreakdown = computed(() => [
    { label: 'Permanent', value: us.value.permanent, color: 'indigo',  icon: 'mdi-account-check-outline'   },
    { label: 'COS',       value: us.value.cos,       color: 'teal',    icon: 'mdi-account-clock-outline'   },
    { label: 'Job Order', value: us.value.jo,        color: 'orange',  icon: 'mdi-account-convert-outline' },
    { label: 'Total',     value: us.value.employees, color: 'primary', icon: 'mdi-account-multiple-outline'},
]);

// ── Log stat cards ─────────────────────────────────────────────
const logStatCards = computed(() => [
    { label: 'Logins',    count: ls.value.login,    color: 'success', icon: 'mdi-login'                         },
    { label: 'Creates',   count: ls.value.create,   color: 'indigo',  icon: 'mdi-plus-circle-outline'           },
    { label: 'Updates',   count: ls.value.update,   color: 'blue',    icon: 'mdi-pencil-outline'                },
    { label: 'Deletes',   count: ls.value.delete,   color: 'error',   icon: 'mdi-trash-can-outline'             },
    { label: 'Generates', count: ls.value.generate, color: 'teal',    icon: 'mdi-account-multiple-plus-outline' },
    { label: 'Exports',   count: ls.value.export,   color: 'purple',  icon: 'mdi-export'                        },
    { label: 'Views',     count: ls.value.view,     color: 'orange',  icon: 'mdi-eye-outline'                   },
    { label: 'Total',     count: Object.values(ls.value).reduce((a, b) => a + b, 0), color: 'grey', icon: 'mdi-text-box-outline' },
]);

// ── Export ────────────────────────────────────────────────────
const exporting   = ref(false);
const exportError = ref('');

const exportPDF = async () => {
    exporting.value   = true;
    exportError.value = '';
    try {
        const res = await fetch('/provincial-admin-report/export', { credentials: 'same-origin' });
        if (!res.ok) throw new Error(`Server error: ${res.status}`);
        const blob = await res.blob();
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `provincial-admin-report-${new Date().toISOString().slice(0, 10)}.pdf`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    } catch (e) {
        exportError.value = 'Export failed. Please try again.';
    } finally {
        exporting.value = false;
    }
};

// ── Helpers ───────────────────────────────────────────────────
const rateColor   = (r) => r >= 90 ? 'success' : r >= 75 ? 'warning' : 'error';

const statusColor = (s) => ({ permanent: 'indigo', cos: 'teal', jo: 'orange' }[s] ?? 'grey');
const statusLabel = (s) => ({ permanent: 'Permanent', cos: 'COS', jo: 'Job Order' }[s] ?? s);

const roleColor = (r) => ({
    super_admin: 'indigo', sub_admin: 'purple', provincial_admin: 'teal',
    provincial_director: 'blue', employee: 'success',
}[r] ?? 'grey');

const roleLabel = (r) => ({
    super_admin: 'Super Admin', sub_admin: 'Sub Admin', provincial_admin: 'Prov. Admin',
    provincial_director: 'Director', employee: 'Employee',
}[r] ?? r);

const typeColor = (t) => ({
    login:'success', logout:'grey', create:'indigo', update:'blue',
    delete:'error',  generate:'teal', export:'purple', view:'orange',
}[t] ?? 'grey');

const typeIcon = (t) => ({
    login:'mdi-login', logout:'mdi-logout', create:'mdi-plus-circle-outline',
    update:'mdi-pencil-outline', delete:'mdi-trash-can-outline',
    generate:'mdi-account-multiple-plus-outline', export:'mdi-export', view:'mdi-eye-outline',
}[t] ?? 'mdi-circle-outline');
</script>
