<template>
        <div class="grid gap-4 w-full">

            <!-- Header -->
            <div class="d-flex align-center justify-space-between flex-wrap gap-2">
                <div>
                    <div class="text-h6 font-weight-bold">System Report</div>
                    <div class="text-caption text-medium-emphasis">User accounts and activity log summary</div>
                </div>
                <v-btn
                    color="primary"
                    variant="tonal"
                    size="small"
                    prepend-icon="mdi-file-chart-outline"
                    :loading="exporting"
                    @click="exportPDF"
                >Export PDF</v-btn>
            </div>

            <!-- Date filter -->
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
                    <v-chip size="small" variant="tonal" color="indigo" prepend-icon="mdi-information-outline">
                        Showing system-wide data
                    </v-chip>
                </div>
            </v-card>

            <!-- User Summary -->
            <div>
                <div class="text-subtitle-2 font-weight-bold mb-3">User Accounts</div>
                <v-row dense>
                    <v-col v-for="stat in userStats" :key="stat.label" cols="6" sm="4" md="2">
                        <v-card class="elevation-1 border-0 rounded-md pa-4">
                            <v-avatar :color="stat.color + '-lighten-5'" rounded="lg" size="38" class="mb-3">
                                <v-icon :color="stat.color" size="18">{{ stat.icon }}</v-icon>
                            </v-avatar>
                            <div class="text-h6 font-weight-bold">{{ stat.value }}</div>
                            <div class="text-caption text-medium-emphasis">{{ stat.label }}</div>
                        </v-card>
                    </v-col>
                </v-row>
            </div>

            <!-- Users by Role breakdown + Recent registrations -->
            <v-row>
                <v-col cols="12" md="5">
                    <v-card class="elevation-1 border-0 rounded-md h-100">
                        <div class="px-5 pt-4 pb-3">
                            <div class="text-subtitle-2 font-weight-bold">Users by Role</div>
                        </div>
                        <v-divider></v-divider>
                        <div class="pa-4 d-flex flex-column gap-3">
                            <div v-for="role in roleBreakdown" :key="role.label">
                                <div class="d-flex align-center justify-space-between mb-1">
                                    <div class="d-flex align-center gap-2">
                                        <v-avatar :color="role.color + '-lighten-5'" size="24" rounded="sm">
                                            <v-icon :color="role.color" size="13">{{ role.icon }}</v-icon>
                                        </v-avatar>
                                        <span class="text-body-2">{{ role.label }}</span>
                                    </div>
                                    <span class="text-body-2 font-weight-bold">{{ role.count }}</span>
                                </div>
                                <v-progress-linear
                                    :model-value="totalUsers ? (role.count / totalUsers * 100) : 0"
                                    :color="role.color"
                                    height="5"
                                    rounded
                                    bg-color="grey-lighten-3"
                                />
                            </div>
                        </div>
                    </v-card>
                </v-col>

                <v-col cols="12" md="7">
                    <v-card class="elevation-1 border-0 rounded-md h-100">
                        <div class="px-5 pt-4 pb-3">
                            <div class="text-subtitle-2 font-weight-bold">Recently Registered Users</div>
                        </div>
                        <v-divider></v-divider>
                        <v-table density="comfortable">
                            <thead>
                                <tr>
                                    <th class="text-caption text-medium-emphasis">Name</th>
                                    <th class="text-caption text-medium-emphasis">Role</th>
                                    <th class="text-caption text-medium-emphasis">Province</th>
                                    <th class="text-caption text-medium-emphasis">Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="u in recentUsers" :key="u.id">
                                    <td>
                                        <div class="d-flex align-center gap-2 py-1">
                                            <v-avatar :color="roleColor(u.role) + '-lighten-5'" size="28" rounded="sm">
                                                <v-icon :color="roleColor(u.role)" size="14">mdi-account-outline</v-icon>
                                            </v-avatar>
                                            <div>
                                                <div class="text-body-2 font-weight-medium">{{ u.name }}</div>
                                                <div class="text-caption text-medium-emphasis">{{ u.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <v-chip size="x-small" variant="tonal" :color="roleColor(u.role)">
                                            {{ roleLabel(u.role) }}
                                        </v-chip>
                                    </td>
                                    <td class="text-caption text-medium-emphasis">{{ u.province ?? '—' }}</td>
                                    <td class="text-caption text-medium-emphasis">{{ u.registered }}</td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Activity Log Summary -->
            <div>
                <div class="text-subtitle-2 font-weight-bold mb-3">Activity Log Summary</div>
                <v-row dense>
                    <v-col v-for="log in logStats" :key="log.label" cols="6" sm="4" md="3">
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

            <!-- Recent Activity Logs -->
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
                    </tbody>
                </v-table>
            </v-card>

        </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    user_stats:    { type: Object, default: null },
    recent_users:  { type: Array,  default: null },
    recent_logs:   { type: Array,  default: null },
    log_stats:     { type: Object, default: null },
});

// ── Test data ─────────────────────────────────────────────────
const testUserStats = {
    total: 312, super_admin: 1, sub_admin: 3, provincial_admin: 81,
    provincial_sub_admin: 81, provincial_director: 81, employee: 65,
};

const testRecentUsers = [
    { id: 1,  name: 'Eduardo Santos',   email: 'eduardo.santos@dost.gov.ph',   role: 'provincial_admin',     province: 'Davao del Sur',     registered: 'May 28, 2026' },
    { id: 2,  name: 'Maria Villanueva', email: 'maria.villanueva@dost.gov.ph', role: 'provincial_admin',     province: 'Bukidnon',           registered: 'May 27, 2026' },
    { id: 3,  name: 'Jose Dela Cruz',   email: 'jose.delacruz@dost.gov.ph',    role: 'provincial_director',  province: 'Misamis Oriental',   registered: 'May 26, 2026' },
    { id: 4,  name: 'Ana Bautista',     email: 'ana.bautista@dost.gov.ph',     role: 'provincial_admin',     province: 'Surigao del Norte',  registered: 'May 25, 2026' },
    { id: 5,  name: 'Ramon Cruz',       email: 'ramon.cruz@dost.gov.ph',       role: 'sub_admin',            province: null,                 registered: 'May 24, 2026' },
    { id: 6,  name: 'Rosa Lim',         email: 'rosa.lim@dost.gov.ph',         role: 'employee',             province: 'Davao del Sur',      registered: 'May 23, 2026' },
];

const testLogStats = { login: 148, create: 34, update: 67, delete: 12, generate: 22, export: 18, view: 203 };

const testRecentLogs = [
    { id: 1, name: 'Vince Muloc',      employee_id: 'SA-001',      role: 'super_admin',      type: 'login',    description: 'Super admin logged into the system.',                               created_at: 'May 28, 2026 8:01 AM' },
    { id: 2, name: 'Eduardo Santos',   employee_id: 'PA-2024-003', role: 'provincial_admin', type: 'generate', description: 'Generated employee accounts from CSV (32 accounts created).',        created_at: 'May 28, 2026 8:45 AM' },
    { id: 3, name: 'Vince Muloc',      employee_id: 'SA-001',      role: 'super_admin',      type: 'create',   description: 'Created new user account for Maria Santos (provincial_director).',   created_at: 'May 28, 2026 8:32 AM' },
    { id: 4, name: 'Ramon Cruz',       employee_id: 'SA-2024-001', role: 'sub_admin',        type: 'export',   description: 'Exported activity logs report (PDF) for May 2026.',                  created_at: 'May 28, 2026 10:05 AM'},
    { id: 5, name: 'Maria Villanueva', employee_id: 'PA-2024-004', role: 'provincial_admin', type: 'update',   description: 'Updated provincial director KPI record.',                            created_at: 'May 28, 2026 10:20 AM'},
    { id: 6, name: 'Vince Muloc',      employee_id: 'SA-001',      role: 'super_admin',      type: 'delete',   description: 'Deleted inactive user account (ID: usr-0045).',                      created_at: 'May 28, 2026 9:35 AM' },
    { id: 7, name: 'Liza Reyes',       employee_id: 'SA-2024-002', role: 'sub_admin',        type: 'create',   description: 'Registered new provincial admin account for Surigao del Norte.',     created_at: 'May 28, 2026 11:05 AM'},
    { id: 8, name: 'Jose Dela Cruz',   employee_id: 'PA-2024-005', role: 'provincial_admin', type: 'login',    description: 'Provincial admin logged into the system.',                           created_at: 'May 28, 2026 10:35 AM'},
];

const us = computed(() => props.user_stats ?? testUserStats);
const recentUsers = computed(() => props.recent_users ?? testRecentUsers);
const recentLogs  = computed(() => props.recent_logs  ?? testRecentLogs);
const ls = computed(() => props.log_stats ?? testLogStats);

const totalUsers = computed(() => us.value.total ?? 0);

const userStats = computed(() => [
    { label: 'Total Users',          value: us.value.total,                color: 'indigo',  icon: 'mdi-account-group-outline'     },
    { label: 'Super Admins',         value: us.value.super_admin,          color: 'indigo',  icon: 'mdi-shield-crown-outline'      },
    { label: 'Sub Admins',           value: us.value.sub_admin,            color: 'purple',  icon: 'mdi-shield-account-outline'    },
    { label: 'Provincial Admins',    value: us.value.provincial_admin,     color: 'teal',    icon: 'mdi-account-cog-outline'       },
    { label: 'Directors',            value: us.value.provincial_director,  color: 'blue',    icon: 'mdi-account-tie-outline'       },
    { label: 'Employees',            value: us.value.employee,             color: 'success', icon: 'mdi-account-outline'           },
]);

const roleBreakdown = computed(() => [
    { label: 'Super Admin',          count: us.value.super_admin,          color: 'indigo',  icon: 'mdi-shield-crown-outline'   },
    { label: 'Sub Admin',            count: us.value.sub_admin,            color: 'purple',  icon: 'mdi-shield-account-outline' },
    { label: 'Provincial Admin',     count: us.value.provincial_admin,     color: 'teal',    icon: 'mdi-account-cog-outline'    },
    { label: 'Provincial Sub Admin', count: us.value.provincial_sub_admin, color: 'cyan',    icon: 'mdi-account-settings-outline'},
    { label: 'Provincial Director',  count: us.value.provincial_director,  color: 'blue',    icon: 'mdi-account-tie-outline'    },
    { label: 'Employee',             count: us.value.employee,             color: 'success', icon: 'mdi-account-outline'        },
]);

const logStats = computed(() => [
    { label: 'Logins',    count: ls.value.login,    color: 'success', icon: 'mdi-login'                       },
    { label: 'Creates',   count: ls.value.create,   color: 'indigo',  icon: 'mdi-plus-circle-outline'         },
    { label: 'Updates',   count: ls.value.update,   color: 'blue',    icon: 'mdi-pencil-outline'              },
    { label: 'Deletes',   count: ls.value.delete,   color: 'error',   icon: 'mdi-trash-can-outline'           },
    { label: 'Generates', count: ls.value.generate, color: 'teal',    icon: 'mdi-account-multiple-plus-outline'},
    { label: 'Exports',   count: ls.value.export,   color: 'purple',  icon: 'mdi-export'                      },
    { label: 'Views',     count: ls.value.view,     color: 'orange',  icon: 'mdi-eye-outline'                 },
    { label: 'Total Logs',count: Object.values(ls.value).reduce((a,b)=>a+b,0), color: 'grey', icon: 'mdi-text-box-outline' },
]);

// ── Filters ───────────────────────────────────────────────────
const dateFrom  = ref('');
const dateTo    = ref('');
const exporting = ref(false);

const xsrfToken = () => {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
};

const exportPDF = () => {
    exporting.value = true;
    const params = new URLSearchParams();
    if (dateFrom.value) params.set('date_from', dateFrom.value);
    if (dateTo.value)   params.set('date_to',   dateTo.value);

    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = `/super-admin-report/export?${params.toString()}`;
    document.body.appendChild(iframe);
    setTimeout(() => {
        exporting.value = false;
        document.body.removeChild(iframe);
    }, 3500);
};

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
