<template>
        <div class="grid gap-4 w-full">

            <!-- ── SUPER ADMIN VIEW ───────────────────────────────── -->
            <template v-if="role === 'super_admin'">

                <div class="d-flex align-center justify-space-between">
                    <div>
                        <div class="text-h6 font-weight-bold">Account Generation Requests</div>
                        <div class="text-caption text-medium-emphasis">Review and process provincial admin requests</div>
                    </div>
                </div>

                <!-- Stats -->
                <v-row dense>
                    <v-col cols="12" sm="4" v-for="stat in stats" :key="stat.label">
                        <v-card class="elevation-1 border-0 rounded-md pa-4">
                            <div class="d-flex align-center gap-3">
                                <v-avatar :color="stat.color + '-lighten-5'" rounded="lg" size="44">
                                    <v-icon :color="stat.color" size="20">{{ stat.icon }}</v-icon>
                                </v-avatar>
                                <div>
                                    <div class="text-h6 font-weight-bold">{{ stat.count }}</div>
                                    <div class="text-caption text-medium-emphasis">{{ stat.label }}</div>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Requests List -->
                <v-card class="elevation-1 border-0 rounded-md">
                    <div class="d-flex align-center justify-space-between px-4 pt-3 pb-0 flex-wrap gap-2">
                        <v-tabs v-model="activeTab" density="compact" color="primary">
                            <v-tab value="all">All</v-tab>
                            <v-tab value="pending">
                                Pending
                                <v-chip v-if="pendingCount > 0" size="x-small" color="warning" variant="tonal" class="ml-1">{{ pendingCount }}</v-chip>
                            </v-tab>
                            <v-tab value="approved">Approved</v-tab>
                            <v-tab value="rejected">Rejected</v-tab>
                        </v-tabs>
                        <v-text-field
                            v-model="search"
                            placeholder="Search province or admin..."
                            variant="solo-filled"
                            density="compact"
                            hide-details
                            clearable
                            prepend-inner-icon="mdi-magnify"
                            style="max-width:260px"
                        />
                    </div>
                    <v-divider class="mt-3"></v-divider>

                    <div class="pa-4">
                        <div v-if="filteredRequests.length === 0" class="text-center py-12">
                            <v-icon size="40" color="grey-lighten-2" class="mb-3">mdi-inbox-outline</v-icon>
                            <div class="text-body-2 text-medium-emphasis">No requests found</div>
                        </div>

                        <div v-else class="d-flex flex-column gap-3">
                            <v-card
                                v-for="req in filteredRequests"
                                :key="req.id"
                                variant="outlined"
                                class="rounded-lg request-card"
                            >
                                <div class="pa-4">
                                    <!-- Header row -->
                                    <div class="d-flex align-start justify-space-between gap-2 flex-wrap">
                                        <div class="d-flex align-center gap-3">
                                            <v-avatar color="indigo-lighten-5" rounded="lg" size="44">
                                                <v-icon color="indigo" size="20">mdi-map-marker-outline</v-icon>
                                            </v-avatar>
                                            <div>
                                                <div class="text-subtitle-2 font-weight-bold">{{ req.province_name }}</div>
                                                <div class="text-caption text-medium-emphasis">
                                                    Submitted by {{ req.submitted_by }} · {{ req.submitted_at }}
                                                </div>
                                            </div>
                                        </div>
                                        <v-chip :color="statusColor(req.status)" size="small" variant="tonal" class="text-capitalize">
                                            <v-icon start size="12">{{ statusIcon(req.status) }}</v-icon>
                                            {{ req.status }}
                                        </v-chip>
                                    </div>

                                    <v-divider class="my-3"></v-divider>

                                    <!-- Details row -->
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="section-label mb-1">Provincial Director</div>
                                            <div class="d-flex align-center gap-2">
                                                <v-icon size="13" color="medium-emphasis">mdi-account-tie-outline</v-icon>
                                                <span class="text-body-2">{{ req.director.name }}</span>
                                            </div>
                                            <div class="d-flex align-center gap-2 mt-1">
                                                <v-icon size="13" color="medium-emphasis">mdi-email-outline</v-icon>
                                                <span class="text-caption text-medium-emphasis">{{ req.director.email }}</span>
                                            </div>
                                            <div class="d-flex align-center gap-2 mt-1">
                                                <v-icon size="13" color="medium-emphasis">mdi-badge-account-outline</v-icon>
                                                <span class="text-caption text-medium-emphasis">{{ req.director.dost_id }}</span>
                                            </div>
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="section-label mb-1">Employee CSV</div>
                                            <div class="d-flex align-center gap-2">
                                                <v-icon size="13" color="success">mdi-file-delimited-outline</v-icon>
                                                <span class="text-body-2">{{ req.csv_filename }}</span>
                                            </div>
                                            <div class="text-caption text-medium-emphasis mt-1">
                                                {{ req.employee_count }} employees · {{ req.file_size }}
                                            </div>
                                        </v-col>
                                    </v-row>

                                    <!-- Actions or reason -->
                                    <div v-if="req.status === 'pending'" class="d-flex gap-2 mt-3 justify-end">
                                        <v-btn
                                            size="small"
                                            variant="tonal"
                                            color="error"
                                            prepend-icon="mdi-close-circle-outline"
                                            @click="openAction(req, 'reject')"
                                        >Reject</v-btn>
                                        <v-btn
                                            size="small"
                                            color="success"
                                            variant="tonal"
                                            prepend-icon="mdi-check-circle-outline"
                                            @click="openAction(req, 'approve')"
                                        >Approve</v-btn>
                                    </div>

                                    <div v-else-if="req.reason" class="mt-3 pa-3 rounded-lg bg-grey-lighten-4">
                                        <span class="text-caption text-medium-emphasis">
                                            <v-icon size="12" class="mr-1">mdi-comment-text-outline</v-icon>
                                            {{ req.status === 'rejected' ? 'Rejection reason' : 'Note' }}: {{ req.reason }}
                                        </span>
                                    </div>
                                </div>
                            </v-card>
                        </div>
                    </div>
                </v-card>

                <!-- Approve / Reject Dialog -->
                <v-dialog v-model="actionDialog" max-width="440" persistent>
                    <v-card rounded="lg">
                        <v-card-title class="pa-5 pb-3">
                            <div class="d-flex align-center justify-space-between">
                                <div class="d-flex align-center gap-2">
                                    <v-icon :color="actionType === 'approve' ? 'success' : 'error'" size="20">
                                        {{ actionType === 'approve' ? 'mdi-check-circle-outline' : 'mdi-close-circle-outline' }}
                                    </v-icon>
                                    <span class="text-subtitle-1 font-weight-bold">
                                        {{ actionType === 'approve' ? 'Approve Request' : 'Reject Request' }}
                                    </span>
                                </div>
                                <v-btn icon size="x-small" variant="text" @click="actionDialog = false">
                                    <v-icon size="16">mdi-close</v-icon>
                                </v-btn>
                            </div>
                            <div class="text-caption text-medium-emphasis mt-1">{{ selectedRequest?.province_name }}</div>
                        </v-card-title>
                        <v-divider></v-divider>
                        <v-card-text class="pa-5">
                            <p class="text-body-2 mb-4" v-if="actionType === 'approve'">
                                Approving will generate accounts for
                                <strong>{{ selectedRequest?.employee_count }} employees</strong> and
                                <strong>1 provincial director</strong> under
                                <strong>{{ selectedRequest?.province_name }}</strong>.
                            </p>
                            <p class="text-body-2 mb-4" v-else>
                                The provincial admin will be notified of this rejection.
                            </p>
                            <v-textarea
                                v-model="actionReason"
                                :label="actionType === 'approve' ? 'Note (optional)' : 'Reason for rejection'"
                                variant="outlined"
                                density="compact"
                                rows="3"
                                hide-details="auto"
                                auto-grow
                            ></v-textarea>
                        </v-card-text>
                        <v-divider></v-divider>
                        <v-card-actions class="pa-4 gap-2 justify-end">
                            <v-btn variant="text" color="medium-emphasis" size="small" @click="actionDialog = false">Cancel</v-btn>
                            <v-btn
                                :color="actionType === 'approve' ? 'success' : 'error'"
                                variant="tonal"
                                size="small"
                                @click="submitAction"
                            >
                                {{ actionType === 'approve' ? 'Approve' : 'Reject' }}
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>

            </template>

            <!-- ── PROVINCIAL ADMIN VIEW ──────────────────────────── -->
            <template v-else-if="role === 'provincial_admin'">

                <div class="d-flex align-center justify-space-between flex-wrap gap-2">
                    <div>
                        <div class="text-h6 font-weight-bold">Request Account Generation</div>
                        <div class="text-caption text-medium-emphasis">Submit employee and director account requests to the super admin</div>
                    </div>
                    <v-chip
                        v-if="hasPendingRequest"
                        size="small"
                        color="warning"
                        variant="tonal"
                        prepend-icon="mdi-clock-outline"
                    >Request Pending</v-chip>
                </div>

                <!-- Form -->
                <v-card class="elevation-1 border-0 rounded-md">
                    <!-- Director Details -->
                    <v-card-item class="pa-5">
                        <div class="section-label mb-4">Provincial Director Details</div>
                        <v-row dense>
                            <v-col cols="12" sm="4">
                                <v-text-field
                                    v-model="form.director_first_name"
                                    label="First Name"
                                    variant="outlined"
                                    density="compact"
                                    hide-details="auto"
                                />
                            </v-col>
                            <v-col cols="12" sm="4">
                                <v-text-field
                                    v-model="form.director_middle_name"
                                    label="Middle Name"
                                    variant="outlined"
                                    density="compact"
                                    hide-details="auto"
                                />
                            </v-col>
                            <v-col cols="12" sm="4">
                                <v-text-field
                                    v-model="form.director_last_name"
                                    label="Last Name"
                                    variant="outlined"
                                    density="compact"
                                    hide-details="auto"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.director_email"
                                    label="Email Address"
                                    type="email"
                                    variant="outlined"
                                    density="compact"
                                    hide-details="auto"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.director_dost_id"
                                    label="DOST Employee ID"
                                    variant="outlined"
                                    density="compact"
                                    hide-details="auto"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="form.director_position"
                                    label="Position / Designation"
                                    variant="outlined"
                                    density="compact"
                                    hide-details="auto"
                                />
                            </v-col>
                        </v-row>
                    </v-card-item>

                    <v-divider></v-divider>

                    <!-- CSV Upload -->
                    <v-card-item class="pa-5">
                        <div class="d-flex align-center justify-space-between mb-4">
                            <div class="section-label">Employee CSV File</div>
                            <v-btn size="x-small" variant="text" color="primary" prepend-icon="mdi-download-outline">
                                Download Template
                            </v-btn>
                        </div>

                        <div
                            class="csv-dropzone rounded-lg pa-6 text-center"
                            :class="{ 'csv-dropzone--active': isDragging, 'csv-dropzone--filled': form.csv_file }"
                            @click="triggerFileInput"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="onFileDrop"
                        >
                            <input ref="fileInput" type="file" accept=".csv" class="d-none" @change="onFileChange" />

                            <template v-if="!form.csv_file">
                                <v-icon size="36" color="grey-lighten-1" class="mb-2">mdi-file-upload-outline</v-icon>
                                <div class="text-body-2 font-weight-medium">Click to upload or drag & drop</div>
                                <div class="text-caption text-medium-emphasis mt-1">CSV files only · Max 5MB</div>
                            </template>

                            <template v-else>
                                <div class="d-flex align-center justify-center gap-3">
                                    <v-avatar color="success-lighten-5" rounded="lg" size="44">
                                        <v-icon color="success" size="22">mdi-file-delimited-outline</v-icon>
                                    </v-avatar>
                                    <div class="text-left">
                                        <div class="text-body-2 font-weight-medium">{{ form.csv_file.name }}</div>
                                        <div class="text-caption text-medium-emphasis">{{ formatFileSize(form.csv_file.size) }}</div>
                                    </div>
                                    <v-btn icon size="x-small" variant="text" color="error" class="ml-2" @click.stop="clearFile">
                                        <v-icon size="16">mdi-close</v-icon>
                                    </v-btn>
                                </div>
                            </template>
                        </div>
                    </v-card-item>

                    <v-divider></v-divider>

                    <v-card-actions class="pa-4 justify-end gap-2">
                        <v-btn variant="text" color="medium-emphasis" size="small" @click="resetForm">Clear</v-btn>
                        <v-btn
                            color="primary"
                            size="small"
                            variant="tonal"
                            prepend-icon="mdi-send-outline"
                            :disabled="!canSubmit"
                            @click="submitRequest"
                        >Submit Request</v-btn>
                    </v-card-actions>
                </v-card>

                <!-- My Requests History -->
                <v-card class="elevation-1 border-0 rounded-md">
                    <div class="d-flex align-center justify-space-between px-5 pt-4 pb-3">
                        <div>
                            <div class="text-subtitle-2 font-weight-bold">My Requests</div>
                            <div class="text-caption text-medium-emphasis">History of your submitted requests</div>
                        </div>
                    </div>
                    <v-divider></v-divider>
                    <div class="pa-4">
                        <div v-if="myRequests.length === 0" class="text-center py-10">
                            <v-icon size="32" color="grey-lighten-2" class="mb-2">mdi-inbox-outline</v-icon>
                            <div class="text-caption text-medium-emphasis">No requests submitted yet</div>
                        </div>
                        <div v-else class="d-flex flex-column gap-2">
                            <div
                                v-for="req in myRequests"
                                :key="req.id"
                                class="d-flex align-center justify-space-between pa-3 rounded-lg bg-grey-lighten-5"
                            >
                                <div class="d-flex align-center gap-3">
                                    <v-avatar color="grey-lighten-3" rounded="lg" size="36">
                                        <v-icon size="16" color="medium-emphasis">mdi-file-delimited-outline</v-icon>
                                    </v-avatar>
                                    <div>
                                        <div class="text-body-2 font-weight-medium">{{ req.csv_filename }}</div>
                                        <div class="text-caption text-medium-emphasis">
                                            {{ req.employee_count }} employees · {{ req.submitted_at }}
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-center gap-2">
                                    <v-chip :color="statusColor(req.status)" size="x-small" variant="tonal" class="text-capitalize">
                                        {{ req.status }}
                                    </v-chip>
                                    <v-tooltip v-if="req.reason" :text="req.reason" location="top">
                                        <template #activator="{ props: p }">
                                            <v-icon v-bind="p" size="16" color="medium-emphasis">mdi-information-outline</v-icon>
                                        </template>
                                    </v-tooltip>
                                </div>
                            </div>
                        </div>
                    </div>
                </v-card>

            </template>

        </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const role = computed(() => page.props.auth?.user?.role);

const testRequests = [
    { id: 1,  province_name: 'Davao del Sur',         submitted_by: 'Admin Reyes',    submitted_at: 'May 1, 2026',  status: 'pending',  director: { name: 'Maria Santos',        email: 'maria.santos@dost.gov.ph',        dost_id: 'DOST-2024-001' }, csv_filename: 'employees_davao_del_sur.csv',         employee_count: 45, file_size: '2.3 KB', reason: null },
    { id: 2,  province_name: 'Bukidnon',               submitted_by: 'Admin Cruz',     submitted_at: 'May 3, 2026',  status: 'approved', director: { name: 'Jose Dela Cruz',       email: 'jose.delacruz@dost.gov.ph',       dost_id: 'DOST-2024-002' }, csv_filename: 'employees_bukidnon.csv',               employee_count: 62, file_size: '3.1 KB', reason: 'All documents verified.' },
    { id: 3,  province_name: 'Misamis Oriental',       submitted_by: 'Admin Gomez',    submitted_at: 'May 5, 2026',  status: 'rejected', director: { name: 'Ana Villanueva',       email: 'ana.villanueva@dost.gov.ph',      dost_id: 'DOST-2024-003' }, csv_filename: 'employees_misamis_oriental.csv',       employee_count: 38, file_size: '1.9 KB', reason: 'Incomplete director information. Please resubmit.' },
    { id: 4,  province_name: 'Misamis Occidental',     submitted_by: 'Admin Lim',      submitted_at: 'May 6, 2026',  status: 'pending',  director: { name: 'Roberto Mendoza',      email: 'roberto.mendoza@dost.gov.ph',     dost_id: 'DOST-2024-004' }, csv_filename: 'employees_misamis_occidental.csv',     employee_count: 29, file_size: '1.5 KB', reason: null },
    { id: 5,  province_name: 'Lanao del Norte',        submitted_by: 'Admin Tan',      submitted_at: 'May 7, 2026',  status: 'pending',  director: { name: 'Carla Bautista',       email: 'carla.bautista@dost.gov.ph',      dost_id: 'DOST-2024-005' }, csv_filename: 'employees_lanao_del_norte.csv',        employee_count: 54, file_size: '2.7 KB', reason: null },
    { id: 6,  province_name: 'Zamboanga del Norte',    submitted_by: 'Admin Flores',   submitted_at: 'May 8, 2026',  status: 'approved', director: { name: 'Eduardo Garcia',       email: 'eduardo.garcia@dost.gov.ph',      dost_id: 'DOST-2024-006' }, csv_filename: 'employees_zamboanga_norte.csv',        employee_count: 41, file_size: '2.1 KB', reason: null },
    { id: 7,  province_name: 'Zamboanga del Sur',      submitted_by: 'Admin Rivera',   submitted_at: 'May 9, 2026',  status: 'rejected', director: { name: 'Luz Fernandez',        email: 'luz.fernandez@dost.gov.ph',       dost_id: 'DOST-2024-007' }, csv_filename: 'employees_zamboanga_sur.csv',          employee_count: 33, file_size: '1.7 KB', reason: 'CSV format does not match the required template.' },
    { id: 8,  province_name: 'Surigao del Norte',      submitted_by: 'Admin Torres',   submitted_at: 'May 10, 2026', status: 'pending',  director: { name: 'Ramon Aquino',         email: 'ramon.aquino@dost.gov.ph',        dost_id: 'DOST-2024-008' }, csv_filename: 'employees_surigao_norte.csv',          employee_count: 27, file_size: '1.4 KB', reason: null },
    { id: 9,  province_name: 'Surigao del Sur',        submitted_by: 'Admin Ramos',    submitted_at: 'May 11, 2026', status: 'approved', director: { name: 'Gloria Pascual',       email: 'gloria.pascual@dost.gov.ph',      dost_id: 'DOST-2024-009' }, csv_filename: 'employees_surigao_sur.csv',            employee_count: 48, file_size: '2.4 KB', reason: null },
    { id: 10, province_name: 'Agusan del Norte',       submitted_by: 'Admin Morales',  submitted_at: 'May 12, 2026', status: 'pending',  director: { name: 'Dennis Castillo',      email: 'dennis.castillo@dost.gov.ph',     dost_id: 'DOST-2024-010' }, csv_filename: 'employees_agusan_norte.csv',           employee_count: 36, file_size: '1.8 KB', reason: null },
    { id: 11, province_name: 'Agusan del Sur',         submitted_by: 'Admin Navarro',  submitted_at: 'May 13, 2026', status: 'rejected', director: { name: 'Rowena Diaz',          email: 'rowena.diaz@dost.gov.ph',         dost_id: 'DOST-2024-011' }, csv_filename: 'employees_agusan_sur.csv',             employee_count: 22, file_size: '1.1 KB', reason: 'DOST Employee ID format is invalid.' },
    { id: 12, province_name: 'Sarangani',              submitted_by: 'Admin Reyes',    submitted_at: 'May 14, 2026', status: 'approved', director: { name: 'Antonio Peralta',      email: 'antonio.peralta@dost.gov.ph',     dost_id: 'DOST-2024-012' }, csv_filename: 'employees_sarangani.csv',              employee_count: 19, file_size: '0.9 KB', reason: null },
    { id: 13, province_name: 'South Cotabato',         submitted_by: 'Admin Ocampo',   submitted_at: 'May 15, 2026', status: 'pending',  director: { name: 'Teresita Romualdo',    email: 'teresita.romualdo@dost.gov.ph',   dost_id: 'DOST-2024-013' }, csv_filename: 'employees_south_cotabato.csv',         employee_count: 57, file_size: '2.8 KB', reason: null },
    { id: 14, province_name: 'Sultan Kudarat',         submitted_by: 'Admin Santiago', submitted_at: 'May 16, 2026', status: 'approved', director: { name: 'Ernesto Villanueva',   email: 'ernesto.villanueva@dost.gov.ph',  dost_id: 'DOST-2024-014' }, csv_filename: 'employees_sultan_kudarat.csv',         employee_count: 43, file_size: '2.2 KB', reason: 'Batch 1 of 2.' },
    { id: 15, province_name: 'North Cotabato',         submitted_by: 'Admin Gutierrez',submitted_at: 'May 17, 2026', status: 'pending',  director: { name: 'Maricel Soriano',      email: 'maricel.soriano@dost.gov.ph',     dost_id: 'DOST-2024-015' }, csv_filename: 'employees_north_cotabato.csv',         employee_count: 66, file_size: '3.3 KB', reason: null },
    { id: 16, province_name: 'Davao del Norte',        submitted_by: 'Admin Dela Rosa',submitted_at: 'May 18, 2026', status: 'rejected', director: { name: 'Florencia Yap',        email: 'florencia.yap@dost.gov.ph',       dost_id: 'DOST-2024-016' }, csv_filename: 'employees_davao_norte.csv',            employee_count: 51, file_size: '2.6 KB', reason: 'CSV contains duplicate employee IDs.' },
    { id: 17, province_name: 'Davao Oriental',         submitted_by: 'Admin Reyes',    submitted_at: 'May 19, 2026', status: 'pending',  director: { name: 'Benjamin Ong',         email: 'benjamin.ong@dost.gov.ph',        dost_id: 'DOST-2024-017' }, csv_filename: 'employees_davao_oriental.csv',         employee_count: 30, file_size: '1.5 KB', reason: null },
    { id: 18, province_name: 'Davao Occidental',       submitted_by: 'Admin Padilla',  submitted_at: 'May 20, 2026', status: 'approved', director: { name: 'Cynthia Marquez',      email: 'cynthia.marquez@dost.gov.ph',     dost_id: 'DOST-2024-018' }, csv_filename: 'employees_davao_occidental.csv',       employee_count: 24, file_size: '1.2 KB', reason: null },
    { id: 19, province_name: 'Compostela Valley',      submitted_by: 'Admin Villanueva',submitted_at:'May 21, 2026', status: 'pending',  director: { name: 'Ferdinand Salazar',    email: 'ferdinand.salazar@dost.gov.ph',   dost_id: 'DOST-2024-019' }, csv_filename: 'employees_compostela_valley.csv',      employee_count: 39, file_size: '2.0 KB', reason: null },
    { id: 20, province_name: 'Cotabato City',          submitted_by: 'Admin Lorenzo',  submitted_at: 'May 22, 2026', status: 'approved', director: { name: 'Josephine Bonifacio',  email: 'josephine.bonifacio@dost.gov.ph', dost_id: 'DOST-2024-020' }, csv_filename: 'employees_cotabato_city.csv',          employee_count: 72, file_size: '3.6 KB', reason: null },
];

const props = defineProps({
    requests: { type: Object },
});

// ── SUPER ADMIN ────────────────────────────────────────────────
const activeTab = ref('all');
const search = ref('');
const actionDialog = ref(false);
const actionType = ref('approve');
const actionReason = ref('');
const selectedRequest = ref(null);

const pendingCount = computed(() => testRequests.filter(r => r.status === 'pending').length);

const stats = computed(() => [
    { label: 'Pending',  count: pendingCount.value, color: 'warning', icon: 'mdi-clock-outline' },
    { label: 'Approved', count: testRequests.filter(r => r.status === 'approved').length, color: 'success', icon: 'mdi-check-circle-outline' },
    { label: 'Rejected', count: testRequests.filter(r => r.status === 'rejected').length, color: 'error',   icon: 'mdi-close-circle-outline' },
]);

const filteredRequests = computed(() => {
    let data = testRequests;
    if (activeTab.value !== 'all') data = data.filter(r => r.status === activeTab.value);
    const q = search.value.toLowerCase().trim();
    if (q) data = data.filter(r =>
        r.province_name?.toLowerCase().includes(q) ||
        r.submitted_by?.toLowerCase().includes(q)
    );
    return data;
});

const openAction = (req, type) => {
    selectedRequest.value = req;
    actionType.value = type;
    actionReason.value = '';
    actionDialog.value = true;
};

const submitAction = () => {
    router.post(`/auto-generator/${selectedRequest.value.id}/${actionType.value}`, {
        reason: actionReason.value,
    }, { onSuccess: () => { actionDialog.value = false; } });
};

// ── PROVINCIAL ADMIN ───────────────────────────────────────────
const fileInput = ref(null);
const isDragging = ref(false);

const defaultForm = () => ({
    director_first_name:  '',
    director_middle_name: '',
    director_last_name:   '',
    director_email:       '',
    director_dost_id:     '',
    director_position:    '',
    csv_file:             null,
});

const form = ref(defaultForm());

const myRequests = computed(() => props.requests);
const hasPendingRequest = computed(() => myRequests.value.some(r => r.status === 'pending'));

const canSubmit = computed(() =>
    form.value.director_first_name &&
    form.value.director_last_name &&
    form.value.director_email &&
    form.value.csv_file
);

const triggerFileInput = () => fileInput.value?.click();

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) form.value.csv_file = file;
};

const onFileDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer.files[0];
    if (file?.name.endsWith('.csv')) form.value.csv_file = file;
};

const clearFile = () => {
    form.value.csv_file = null;
    if (fileInput.value) fileInput.value.value = '';
};

const resetForm = () => {
    form.value = defaultForm();
    if (fileInput.value) fileInput.value.value = '';
};

const submitRequest = () => {
    const data = new FormData();
    Object.entries(form.value).forEach(([k, v]) => { if (v !== null) data.append(k, v); });
    router.post('/auto-generator/submit', data, { onSuccess: resetForm });
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
};

const statusColor = (s) => ({ pending: 'warning', approved: 'success', rejected: 'error' }[s] ?? 'grey');
const statusIcon  = (s) => ({ pending: 'mdi-clock-outline', approved: 'mdi-check-circle-outline', rejected: 'mdi-close-circle-outline' }[s] ?? 'mdi-circle-outline');
</script>

<style scoped>
.section-label {
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 0.6rem;
    font-weight: 600;
    color: rgba(0,0,0,0.45);
}
.csv-dropzone {
    border: 2px dashed rgba(0, 0, 0, 0.12);
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
}
.csv-dropzone:hover,
.csv-dropzone--active {
    border-color: rgba(var(--v-theme-primary), 0.6);
    background: rgba(var(--v-theme-primary), 0.04);
}
.csv-dropzone--filled {
    border-color: rgba(var(--v-theme-success), 0.4);
    background: rgba(var(--v-theme-success), 0.04);
}
.request-card {
    transition: box-shadow 0.15s;
}
.request-card:hover {
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08) !important;
}
</style>
