<template>
        <div class="grid gap-4 w-full">

            <!-- Header -->
            <div class="d-flex align-center justify-space-between flex-wrap gap-2">
                <div>
                    <div class="text-h6 font-weight-bold">Provincial Report</div>
                    <div class="text-caption text-medium-emphasis">Province overview, director rankings, and activity summary</div>
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

            <!-- Filters -->
            <v-card class="elevation-1 border-0 rounded-md pa-4">
                <div class="d-flex align-center gap-3 flex-wrap">
                    <v-text-field
                        v-model="dateFrom"
                        label="From"
                        type="date"
                        variant="outlined"
                        density="compact"
                        hide-details
                        style="max-width:170px;"
                    />
                    <v-text-field
                        v-model="dateTo"
                        label="To"
                        type="date"
                        variant="outlined"
                        density="compact"
                        hide-details
                        style="max-width:170px;"
                    />
                    <v-text-field
                        v-model="search"
                        label="Search province"
                        variant="outlined"
                        density="compact"
                        prepend-inner-icon="mdi-magnify"
                        clearable
                        hide-details
                        style="max-width:220px;"
                    />
                    <v-chip size="small" variant="tonal" color="teal" prepend-icon="mdi-information-outline">
                        {{ filteredProvinces.length }} provinces shown
                    </v-chip>
                </div>
            </v-card>

            <!-- Summary cards -->
            <v-row dense>
                <v-col v-for="card in summaryCards" :key="card.label" cols="6" sm="4" md="2">
                    <v-card class="elevation-1 border-0 rounded-md pa-4">
                        <v-avatar :color="card.color + '-lighten-5'" rounded="lg" size="38" class="mb-3">
                            <v-icon :color="card.color" size="18">{{ card.icon }}</v-icon>
                        </v-avatar>
                        <div class="text-h6 font-weight-bold">{{ card.value }}</div>
                        <div class="text-caption text-medium-emphasis">{{ card.label }}</div>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Province table + Director rankings -->
            <v-row>

                <!-- Province table -->
                <v-col cols="12" md="7">
                    <v-card class="elevation-1 border-0 rounded-md h-100">
                        <div class="px-5 pt-4 pb-3 d-flex align-center justify-space-between">
                            <div class="text-subtitle-2 font-weight-bold">Province Overview</div>
                            <v-chip size="x-small" variant="tonal" color="teal">{{ filteredProvinces.length }} total</v-chip>
                        </div>
                        <v-divider></v-divider>
                        <v-table density="comfortable" hover>
                            <thead>
                                <tr>
                                    <th class="text-caption text-medium-emphasis">Province</th>
                                    <th class="text-caption text-medium-emphasis">Director</th>
                                    <th class="text-caption text-medium-emphasis text-center">Employees</th>
                                    <th class="text-caption text-medium-emphasis text-center">Admins</th>
                                    <th class="text-caption text-medium-emphasis text-center">Total Users</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in filteredProvinces" :key="p.id">
                                    <td>
                                        <div class="d-flex align-center gap-2 py-1">
                                            <v-avatar color="teal-lighten-5" size="28" rounded="sm">
                                                <v-icon color="teal" size="14">mdi-map-marker-outline</v-icon>
                                            </v-avatar>
                                            <div>
                                                <div class="text-body-2 font-weight-medium">{{ p.name }}</div>
                                                <v-chip v-if="p.category" size="x-small" variant="tonal" color="grey" class="mt-1">{{ p.category }}</v-chip>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-body-2">{{ p.director }}</td>
                                    <td class="text-center">
                                        <v-chip size="x-small" variant="tonal" color="success">{{ p.employees }}</v-chip>
                                    </td>
                                    <td class="text-center">
                                        <v-chip size="x-small" variant="tonal" color="blue">{{ p.admins }}</v-chip>
                                    </td>
                                    <td class="text-center text-body-2 font-weight-medium">{{ p.total_users }}</td>
                                </tr>
                                <tr v-if="!filteredProvinces.length">
                                    <td colspan="5" class="text-center text-caption text-medium-emphasis py-6">No provinces match your search.</td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-card>
                </v-col>

                <!-- Director KPI Rankings -->
                <v-col cols="12" md="5">
                    <v-card class="elevation-1 border-0 rounded-md h-100">
                        <div class="px-5 pt-4 pb-3 d-flex align-center justify-space-between">
                            <div class="text-subtitle-2 font-weight-bold">Director KPI Rankings</div>
                            <v-chip size="x-small" variant="tonal" color="blue">By accomplishment rate</v-chip>
                        </div>
                        <v-divider></v-divider>
                        <div class="pa-4 d-flex flex-column gap-3">
                            <div v-for="(d, i) in directors" :key="d.id">
                                <div class="d-flex align-center gap-2 mb-1">
                                    <!-- Rank badge -->
                                    <v-avatar
                                        :color="i === 0 ? 'amber' : i === 1 ? 'grey-lighten-1' : i === 2 ? 'brown-lighten-2' : 'blue-lighten-5'"
                                        size="22"
                                        rounded="sm"
                                    >
                                        <span class="text-caption font-weight-bold" style="font-size:10px;">{{ i + 1 }}</span>
                                    </v-avatar>
                                    <div class="flex-1-1">
                                        <div class="d-flex align-center justify-space-between">
                                            <div>
                                                <div class="text-body-2 font-weight-medium">{{ d.name || '-' }}</div>
                                                <div class="text-caption text-medium-emphasis">{{ d.province }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-body-2 font-weight-bold" :style="{ color: rateColor(d.rate) }">
                                                    {{ d.rate }}%
                                                </div>
                                                <div class="text-caption text-medium-emphasis">{{ d.accomplished }}/{{ d.target }}</div>
                                            </div>
                                        </div>
                                        <v-progress-linear
                                            :model-value="d.rate"
                                            :color="rateColor(d.rate)"
                                            height="5"
                                            rounded
                                            bg-color="grey-lighten-3"
                                            class="mt-1"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div v-if="!directors.length" class="text-caption text-medium-emphasis text-center py-4">
                                No director KPI data available.
                            </div>
                        </div>
                    </v-card>
                </v-col>

            </v-row>

            <!-- Activity Log Summary -->
            <div>
                <div class="text-subtitle-2 font-weight-bold mb-3">Activity Log Summary</div>
                <v-row dense>
                    <v-col v-for="log in logStatCards" :key="log.label" cols="6" sm="4" md="3">
                        <v-card class="elevation-1 border-0 rounded-md pa-4">
                            <div class="d-flex align-center gap-3">
                                <v-avatar :color="log.color + '-lighten-5'" rounded="lg" size="38">
                                    <v-icon :color="log.color" size="18">{{ log.icon }}</v-icon>
                                </v-avatar>
                                <div>
                                    <div class="text-h6 font-weight-bold">{{ log.count }}</div>
                                    <div class="text-caption text-medium-emphasis">{{ log.label }}</div>
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
                                <div class="text-body-2 font-weight-medium">{{ log.name }}</div>
                                <div class="text-caption text-medium-emphasis">{{ log.employee_id }}</div>
                            </td>
                            <td>
                                <v-chip size="x-small" variant="tonal" :color="roleColor(log.role)">
                                    {{ roleLabel(log.role) }}
                                </v-chip>
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
    province_stats:    { type: Array,  default: null },
    user_stats:        { type: Object, default: null },
    director_rankings: { type: Array,  default: null },
    log_stats:         { type: Object, default: null },
    recent_logs:       { type: Array,  default: null },
});

// ── Test data ─────────────────────────────────────────────────
const testProvinces = [
    { id:1,  name:'Davao del Sur',      category:'1st class', director:'Maria Santos',      employees:18, admins:2, total_users:22 },
    { id:2,  name:'Bukidnon',           category:'1st class', director:'Jose Dela Cruz',    employees:14, admins:1, total_users:17 },
    { id:3,  name:'Misamis Oriental',   category:'2nd class', director:'Ana Villanueva',    employees:11, admins:1, total_users:14 },
    { id:4,  name:'Surigao del Norte',  category:'2nd class', director:'-',                 employees:9,  admins:1, total_users:11 },
    { id:5,  name:'Agusan del Norte',   category:'3rd class', director:'Ramon Bautista',    employees:7,  admins:1, total_users:10 },
    { id:6,  name:'Lanao del Norte',    category:'3rd class', director:'Gloria Reyes',      employees:6,  admins:1, total_users:9  },
    { id:7,  name:'South Cotabato',     category:'1st class', director:'Carlos Mendoza',    employees:16, admins:2, total_users:20 },
    { id:8,  name:'Sarangani',          category:'4th class', director:'Rosa Lim',          employees:5,  admins:1, total_users:7  },
];

const testUserStats = {
    total: 156, provinces: 8, provincial_admin: 10, provincial_director: 7,
    provincial_sub_admin: 5, employee: 86,
};

const testDirectorRankings = [
    { id:1, name:'Carlos Mendoza',  province:'South Cotabato',    target:120, accomplished:114, rate:95.0 },
    { id:2, name:'Maria Santos',    province:'Davao del Sur',     target:100, accomplished:92,  rate:92.0 },
    { id:3, name:'Jose Dela Cruz',  province:'Bukidnon',          target:110, accomplished:98,  rate:89.1 },
    { id:4, name:'Gloria Reyes',    province:'Lanao del Norte',   target:80,  accomplished:68,  rate:85.0 },
    { id:5, name:'Ramon Bautista',  province:'Agusan del Norte',  target:90,  accomplished:72,  rate:80.0 },
    { id:6, name:'Ana Villanueva',  province:'Misamis Oriental',  target:95,  accomplished:70,  rate:73.7 },
    { id:7, name:'Rosa Lim',        province:'Sarangani',         target:60,  accomplished:40,  rate:66.7 },
];

const testLogStats = { login:98, logout:86, create:22, update:44, delete:8, generate:14, export:11, view:137 };

const testRecentLogs = [
    { id:1, name:'Maria Santos',    employee_id:'DOST-DIR-001', role:'provincial_director', type:'login',    description:'Provincial director logged in.',                       created_at:'Jun 1, 2026 8:02 AM' },
    { id:2, name:'Eduardo Santos',  employee_id:'DOST-ADM-003', role:'provincial_admin',    type:'generate', description:'Generated 14 employee accounts from CSV.',              created_at:'Jun 1, 2026 8:45 AM' },
    { id:3, name:'Jose Dela Cruz',  employee_id:'DOST-DIR-002', role:'provincial_director', type:'update',   description:'Updated KPI accomplishment for Q2 2026.',               created_at:'Jun 1, 2026 9:10 AM' },
    { id:4, name:'Ramon Cruz',      employee_id:'DOST-SA-001',  role:'sub_admin',           type:'export',   description:'Exported provincial report PDF for June 2026.',         created_at:'Jun 1, 2026 10:05 AM'},
    { id:5, name:'Ana Villanueva',  employee_id:'DOST-DIR-003', role:'provincial_director', type:'view',     description:'Viewed employee list for Misamis Oriental.',            created_at:'Jun 1, 2026 10:30 AM'},
    { id:6, name:'Gloria Reyes',    employee_id:'DOST-DIR-004', role:'provincial_director', type:'update',   description:'Updated profile information.',                          created_at:'Jun 1, 2026 11:00 AM'},
    { id:7, name:'Ramon Bautista',  employee_id:'DOST-DIR-005', role:'provincial_director', type:'login',    description:'Provincial director logged in.',                        created_at:'Jun 1, 2026 11:15 AM'},
    { id:8, name:'Rosa Lim',        employee_id:'DOST-DIR-007', role:'provincial_director', type:'view',     description:'Viewed KPI dashboard.',                                 created_at:'Jun 1, 2026 11:45 AM'},
];

// ── Data resolved from props or test data ─────────────────────
const provinces  = computed(() => props.province_stats    ?? testProvinces);
const us         = computed(() => props.user_stats        ?? testUserStats);
const directors  = computed(() => props.director_rankings ?? testDirectorRankings);
const ls         = computed(() => props.log_stats         ?? testLogStats);
const recentLogs = computed(() => props.recent_logs       ?? testRecentLogs);

// ── Filters ───────────────────────────────────────────────────
const dateFrom = ref('');
const dateTo   = ref('');
const search   = ref('');

const filteredProvinces = computed(() => {
    if (!search.value.trim()) return provinces.value;
    const q = search.value.toLowerCase();
    return provinces.value.filter(p =>
        p.name.toLowerCase().includes(q) || p.director.toLowerCase().includes(q)
    );
});

// ── Summary cards ─────────────────────────────────────────────
const summaryCards = computed(() => [
    { label: 'Total Provinces',   value: us.value.provinces,           color: 'teal',    icon: 'mdi-map-outline'               },
    { label: 'Directors',         value: us.value.provincial_director, color: 'blue',    icon: 'mdi-account-tie-outline'       },
    { label: 'Provincial Admins', value: us.value.provincial_admin,    color: 'indigo',  icon: 'mdi-account-cog-outline'       },
    { label: 'Sub Admins',        value: us.value.provincial_sub_admin,color: 'purple',  icon: 'mdi-account-settings-outline'  },
    { label: 'Employees',         value: us.value.employee,            color: 'success', icon: 'mdi-account-outline'           },
    { label: 'Total Users',       value: us.value.total,              color: 'primary', icon: 'mdi-account-group-outline'     },
]);

// ── Log stat cards ─────────────────────────────────────────────
const logStatCards = computed(() => [
    { label: 'Logins',    count: ls.value.login,    color: 'success', icon: 'mdi-login'                        },
    { label: 'Creates',   count: ls.value.create,   color: 'indigo',  icon: 'mdi-plus-circle-outline'          },
    { label: 'Updates',   count: ls.value.update,   color: 'blue',    icon: 'mdi-pencil-outline'               },
    { label: 'Deletes',   count: ls.value.delete,   color: 'error',   icon: 'mdi-trash-can-outline'            },
    { label: 'Generates', count: ls.value.generate, color: 'teal',    icon: 'mdi-account-multiple-plus-outline'},
    { label: 'Exports',   count: ls.value.export,   color: 'purple',  icon: 'mdi-export'                       },
    { label: 'Views',     count: ls.value.view,     color: 'orange',  icon: 'mdi-eye-outline'                  },
    { label: 'Total',     count: Object.values(ls.value).reduce((a, b) => a + b, 0), color: 'grey', icon: 'mdi-text-box-outline' },
]);

// ── Export ────────────────────────────────────────────────────
const exporting = ref(false);
const exportError = ref('');

const exportPDF = async () => {
    exporting.value  = true;
    exportError.value = '';

    try {
        const params = new URLSearchParams();
        if (dateFrom.value) params.set('date_from', dateFrom.value);
        if (dateTo.value)   params.set('date_to',   dateTo.value);

        const res = await fetch(`/sub-admin-report/export?${params.toString()}`, {
            credentials: 'same-origin',
        });

        if (!res.ok) throw new Error(`Server error: ${res.status}`);

        const blob     = await res.blob();
        const url      = URL.createObjectURL(blob);
        const a        = document.createElement('a');
        a.href         = url;
        a.download     = `sub-admin-report-${new Date().toISOString().slice(0, 10)}.pdf`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    } catch (e) {
        exportError.value = 'Export failed. Please try again.';
        console.error(e);
    } finally {
        exporting.value = false;
    }
};

// ── Helpers ───────────────────────────────────────────────────
const rateColor = (rate) => rate >= 90 ? 'success' : rate >= 75 ? 'warning' : 'error';

const roleColor = (r) => ({
    super_admin: 'indigo', sub_admin: 'purple', provincial_admin: 'teal',
    provincial_sub_admin: 'cyan', provincial_director: 'blue', employee: 'success',
}[r] ?? 'grey');

const roleLabel = (r) => ({
    super_admin: 'Super Admin', sub_admin: 'Sub Admin',
    provincial_admin: 'Prov. Admin', provincial_sub_admin: 'Prov. Sub Admin',
    provincial_director: 'Director', employee: 'Employee',
}[r] ?? r);

const typeColor = (t) => ({
    login:'success', logout:'grey', create:'indigo', update:'blue',
    delete:'error', generate:'teal', export:'purple', view:'orange',
}[t] ?? 'grey');

const typeIcon = (t) => ({
    login:'mdi-login', logout:'mdi-logout', create:'mdi-plus-circle-outline',
    update:'mdi-pencil-outline', delete:'mdi-trash-can-outline',
    generate:'mdi-account-multiple-plus-outline', export:'mdi-export', view:'mdi-eye-outline',
}[t] ?? 'mdi-circle-outline');
</script>
