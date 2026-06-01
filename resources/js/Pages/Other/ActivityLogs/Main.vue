<template>
    <AuthenticatedLayout>
        <div class="grid gap-4 w-full">

            <!-- Header -->
            <div class="d-flex align-center justify-space-between flex-wrap gap-2">
                <div>
                    <div class="text-h6 font-weight-bold">Activity Logs</div>
                    <div class="text-caption text-medium-emphasis">Track all user actions across the system</div>
                </div>
                <v-btn
                    color="primary"
                    variant="tonal"
                    size="small"
                    prepend-icon="mdi-file-chart-outline"
                    @click="reportDialog = true"
                >Generate Report</v-btn>
            </div>

            <!-- List -->
            <ActivityLogList :list="listData" />

            <!-- Generate Report Dialog -->
            <v-dialog v-model="reportDialog" max-width="480" persistent>
                <v-card rounded="lg">
                    <v-card-title class="pa-5 pb-3">
                        <div class="d-flex align-center justify-space-between">
                            <div class="d-flex align-center gap-2">
                                <v-avatar color="primary-lighten-5" rounded="lg" size="36">
                                    <v-icon color="primary" size="18">mdi-file-chart-outline</v-icon>
                                </v-avatar>
                                <div>
                                    <div class="text-subtitle-1 font-weight-bold">Generate Logs Report</div>
                                    <div class="text-caption text-medium-emphasis">Export filtered activity logs</div>
                                </div>
                            </div>
                            <v-btn icon size="x-small" variant="text" @click="reportDialog = false">
                                <v-icon size="16">mdi-close</v-icon>
                            </v-btn>
                        </div>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text class="pa-5">
                        <div class="d-flex flex-column gap-4">

                            <!-- Date Range -->
                            <div>
                                <div class="section-label mb-2">Date Range</div>
                                <v-row dense>
                                    <v-col cols="6">
                                        <v-text-field
                                            v-model="report.date_from"
                                            label="From"
                                            type="date"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                        />
                                    </v-col>
                                    <v-col cols="6">
                                        <v-text-field
                                            v-model="report.date_to"
                                            label="To"
                                            type="date"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                        />
                                    </v-col>
                                </v-row>
                            </div>

                            <!-- Action Type -->
                            <div>
                                <div class="section-label mb-2">Action Type</div>
                                <v-select
                                    v-model="report.type"
                                    :items="[{ label: 'All Types', value: null }, ...typeOptions]"
                                    item-title="label"
                                    item-value="value"
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                />
                            </div>

                            <!-- Format -->
                            <div>
                                <div class="section-label mb-2">Export Format</div>
                                <div class="d-flex gap-3">
                                    <v-card
                                        v-for="fmt in formatOptions"
                                        :key="fmt.value"
                                        :variant="report.format === fmt.value ? 'tonal' : 'outlined'"
                                        :color="report.format === fmt.value ? 'primary' : undefined"
                                        class="format-card flex-grow-1 pa-3 text-center"
                                        rounded="lg"
                                        @click="report.format = fmt.value"
                                    >
                                        <v-icon size="22" :color="report.format === fmt.value ? 'primary' : 'medium-emphasis'" class="mb-1">
                                            {{ fmt.icon }}
                                        </v-icon>
                                        <div class="text-body-2 font-weight-medium">{{ fmt.label }}</div>
                                        <div class="text-caption text-medium-emphasis">{{ fmt.hint }}</div>
                                    </v-card>
                                </div>
                            </div>

                        </div>
                    </v-card-text>
                    <v-divider></v-divider>
                    <!-- Generating progress -->
                    <div v-if="generating" class="px-5 pb-4 pt-2">
                        <div class="d-flex align-center gap-3 mb-2">
                            <v-icon size="16" color="primary">mdi-file-chart-outline</v-icon>
                            <span class="text-body-2 font-weight-medium">Generating PDF report...</span>
                        </div>
                        <v-progress-linear indeterminate color="primary" rounded height="6" />
                        <div class="text-caption text-medium-emphasis mt-2">Your download will start automatically.</div>
                    </div>

                    <v-card-actions v-if="!generating" class="pa-4 gap-2 justify-end">
                        <v-btn variant="text" color="medium-emphasis" size="small" @click="reportDialog = false">Cancel</v-btn>
                        <v-btn
                            color="primary"
                            variant="tonal"
                            size="small"
                            prepend-icon="mdi-download-outline"
                            :disabled="!report.date_from || !report.date_to"
                            @click="generateReport"
                        >Export</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ActivityLogList from '@/Components/Lists/ActivityLogList.vue';

const props = defineProps({
    logs: { type: Object, default: null },
});

const testData = {
    data: [
        { id: 1,  name: 'Vince Muloc',      employee_id: 'SA-001',      role: 'super_admin',          type: 'login',    description: 'Super admin logged into the system.',                               created_at: '2026-05-28T08:01:00Z' },
        { id: 2,  name: 'Eduardo Santos',    employee_id: 'PA-2024-003', role: 'provincial_admin',     type: 'login',    description: 'Provincial admin logged into the system.',                          created_at: '2026-05-28T08:15:00Z' },
        { id: 3,  name: 'Vince Muloc',       employee_id: 'SA-001',      role: 'super_admin',          type: 'create',   description: 'Created new user account for Maria Santos (provincial_director).',   created_at: '2026-05-28T08:32:00Z' },
        { id: 4,  name: 'Eduardo Santos',    employee_id: 'PA-2024-003', role: 'provincial_admin',     type: 'generate', description: 'Generated employee accounts from CSV (32 accounts created).',        created_at: '2026-05-28T08:45:00Z' },
        { id: 5,  name: 'Vince Muloc',       employee_id: 'SA-001',      role: 'super_admin',          type: 'update',   description: 'Updated province category for Davao del Sur.',                      created_at: '2026-05-28T09:00:00Z' },
        { id: 6,  name: 'Maria Villanueva',  employee_id: 'PA-2024-004', role: 'provincial_admin',     type: 'login',    description: 'Provincial admin logged into the system.',                          created_at: '2026-05-28T09:10:00Z' },
        { id: 7,  name: 'Ramon Cruz',        employee_id: 'SA-2024-001', role: 'sub_admin',            type: 'view',     description: 'Viewed province directory for Bukidnon.',                           created_at: '2026-05-28T09:22:00Z' },
        { id: 8,  name: 'Vince Muloc',       employee_id: 'SA-001',      role: 'super_admin',          type: 'delete',   description: 'Deleted inactive user account (ID: usr-0045).',                     created_at: '2026-05-28T09:35:00Z' },
        { id: 9,  name: 'Maria Villanueva',  employee_id: 'PA-2024-004', role: 'provincial_admin',     type: 'generate', description: 'Generated employee accounts from CSV (18 accounts created).',        created_at: '2026-05-28T09:50:00Z' },
        { id: 10, name: 'Ramon Cruz',        employee_id: 'SA-2024-001', role: 'sub_admin',            type: 'export',   description: 'Exported activity logs report (PDF) for May 2026.',                created_at: '2026-05-28T10:05:00Z' },
        { id: 11, name: 'Eduardo Santos',    employee_id: 'PA-2024-003', role: 'provincial_admin',     type: 'update',   description: 'Updated provincial director KPI record.',                           created_at: '2026-05-28T10:20:00Z' },
        { id: 12, name: 'Jose Dela Cruz',    employee_id: 'PA-2024-005', role: 'provincial_admin',     type: 'login',    description: 'Provincial admin logged into the system.',                          created_at: '2026-05-28T10:35:00Z' },
        { id: 13, name: 'Vince Muloc',       employee_id: 'SA-001',      role: 'super_admin',          type: 'view',     description: 'Viewed ranking report for Region XI.',                              created_at: '2026-05-28T10:50:00Z' },
        { id: 14, name: 'Liza Reyes',        employee_id: 'SA-2024-002', role: 'sub_admin',            type: 'create',   description: 'Registered new provincial admin account for Surigao del Norte.',    created_at: '2026-05-28T11:05:00Z' },
        { id: 15, name: 'Jose Dela Cruz',    employee_id: 'PA-2024-005', role: 'provincial_admin',     type: 'generate', description: 'Generated employee accounts from CSV (24 accounts created).',        created_at: '2026-05-28T11:20:00Z' },
        { id: 16, name: 'Ramon Cruz',        employee_id: 'SA-2024-001', role: 'sub_admin',            type: 'update',   description: 'Updated user profile for Eduardo Santos.',                          created_at: '2026-05-28T11:35:00Z' },
        { id: 17, name: 'Vince Muloc',       employee_id: 'SA-001',      role: 'super_admin',          type: 'export',   description: 'Exported ranking report (CSV) for all provinces.',                  created_at: '2026-05-28T11:50:00Z' },
        { id: 18, name: 'Ana Bautista',      employee_id: 'PA-2024-006', role: 'provincial_admin',     type: 'login',    description: 'Provincial admin logged into the system.',                          created_at: '2026-05-28T13:00:00Z' },
        { id: 19, name: 'Liza Reyes',        employee_id: 'SA-2024-002', role: 'sub_admin',            type: 'delete',   description: 'Deleted duplicate employee account (ID: usr-0112).',                created_at: '2026-05-28T13:15:00Z' },
        { id: 20, name: 'Ana Bautista',      employee_id: 'PA-2024-006', role: 'provincial_admin',     type: 'generate', description: 'Generated employee accounts from CSV (15 accounts created).',        created_at: '2026-05-28T13:30:00Z' },
    ],
    meta: { current_page: 1, last_page: 1 },
};

const listData = computed(() => props.logs?.data?.length ? props.logs : testData);

// Report dialog
const reportDialog = ref(false);
const generating   = ref(false);

const defaultReport = () => ({
    date_from: '',
    date_to:   '',
    type:      null,
    format:    'pdf',
});
const report = ref(defaultReport());

const typeOptions = [
    { label: 'Login',    value: 'login'    },
    { label: 'Logout',   value: 'logout'   },
    { label: 'Create',   value: 'create'   },
    { label: 'Update',   value: 'update'   },
    { label: 'Delete',   value: 'delete'   },
    { label: 'Generate', value: 'generate' },
    { label: 'Export',   value: 'export'   },
    { label: 'View',     value: 'view'     },
];

const formatOptions = [
    { label: 'PDF',   value: 'pdf',  icon: 'mdi-file-pdf-box',          hint: 'Printable format' },
    { label: 'CSV',   value: 'csv',  icon: 'mdi-file-delimited-outline', hint: 'Spreadsheet'      },
    { label: 'Excel', value: 'xlsx', icon: 'mdi-file-excel-outline',     hint: 'Excel workbook'   },
];

const generateReport = () => {
    generating.value = true;

    const params = new URLSearchParams();
    params.set('date_from', report.value.date_from);
    params.set('date_to',   report.value.date_to);
    if (report.value.type) params.set('type', report.value.type);

    // Use a hidden iframe so the page doesn't navigate away
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = `/activity-logs/report?${params.toString()}`;
    document.body.appendChild(iframe);

    // Give the server time to generate and stream the file, then clean up
    setTimeout(() => {
        generating.value   = false;
        reportDialog.value = false;
        report.value       = defaultReport();
        document.body.removeChild(iframe);
    }, 3500);
};
</script>

<style scoped>
.section-label {
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 0.6rem;
    font-weight: 600;
    color: rgba(0, 0, 0, 0.45);
}
.format-card {
    cursor: pointer;
    transition: border-color 0.15s;
}
</style>
