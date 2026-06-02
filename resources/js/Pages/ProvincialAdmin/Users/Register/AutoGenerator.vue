<template>
    <AuthenticatedLayout>
        <div class="grid gap-4 w-full">

            <!-- Header -->
            <div class="d-flex align-center justify-space-between flex-wrap gap-2">
                <div>
                    <div class="text-h6 font-weight-bold">Auto Account Generator</div>
                    <div class="text-caption text-medium-emphasis">Upload CSV → verify → review → commit</div>
                </div>
                <v-btn
                    v-if="step !== 'upload'"
                    variant="text"
                    size="small"
                    color="medium-emphasis"
                    prepend-icon="mdi-arrow-left"
                    @click="resetAll"
                >Start Over</v-btn>
            </div>

            <!-- ── STEP: UPLOAD ────────────────────────────────── -->
            <template v-if="step === 'upload'">
                <v-row>
                    <v-col cols="12" md="8">
                        <div class="d-flex flex-column gap-4">

                            <!-- Guidelines -->
                            <v-card class="elevation-1 border-0 rounded-md">
                                <div class="pa-5 pb-3 d-flex align-center gap-3">
                                    <v-avatar :color="guidelinesRead ? 'success-lighten-5' : 'indigo-lighten-5'" rounded="lg" size="36">
                                        <v-icon :color="guidelinesRead ? 'success' : 'indigo'" size="18">
                                            {{ guidelinesRead ? 'mdi-check-circle' : 'mdi-file-document-outline' }}
                                        </v-icon>
                                    </v-avatar>
                                    <div>
                                        <div class="text-subtitle-2 font-weight-bold">Step 1 — Read the Guidelines</div>
                                        <div class="text-caption text-medium-emphasis">Review all requirements before proceeding</div>
                                    </div>
                                </div>
                                <v-divider></v-divider>
                                <div class="pa-5">
                                    <v-expansion-panels v-model="guidelinesPanel" variant="accordion">
                                        <v-expansion-panel value="open" rounded="lg" elevation="0" class="border">
                                            <v-expansion-panel-title class="text-body-2 font-weight-medium">
                                                <v-icon size="16" color="indigo" class="mr-2">mdi-information-outline</v-icon>
                                                Submission Guidelines
                                            </v-expansion-panel-title>
                                            <v-expansion-panel-text>
                                                <div class="d-flex flex-column gap-3 py-1">
                                                    <div v-for="(item, i) in guidelines" :key="i" class="d-flex align-start gap-3">
                                                        <v-avatar color="indigo-lighten-5" size="24" rounded="sm" class="flex-shrink-0 mt-px">
                                                            <span style="color:#5c6bc0; font-size:0.6rem; font-weight:700;">{{ i + 1 }}</span>
                                                        </v-avatar>
                                                        <div class="text-body-2">{{ item }}</div>
                                                    </div>
                                                </div>
                                            </v-expansion-panel-text>
                                        </v-expansion-panel>
                                    </v-expansion-panels>
                                    <v-checkbox v-model="guidelinesRead" color="success" hide-details class="mt-3">
                                        <template #label>
                                            <span class="text-body-2">I have read and understood all the guidelines</span>
                                        </template>
                                    </v-checkbox>
                                </div>
                            </v-card>

                            <!-- CSV Upload -->
                            <v-card class="elevation-1 border-0 rounded-md" :class="{ 'step-disabled': !guidelinesRead }">
                                <div class="pa-5 pb-3 d-flex align-center justify-space-between">
                                    <div class="d-flex align-center gap-3">
                                        <v-avatar :color="guidelinesRead ? 'indigo-lighten-5' : 'grey-lighten-3'" rounded="lg" size="36">
                                            <v-icon :color="guidelinesRead ? 'indigo' : 'grey'" size="18">mdi-file-delimited-outline</v-icon>
                                        </v-avatar>
                                        <div>
                                            <div class="text-subtitle-2 font-weight-bold">Step 2 — Upload CSV</div>
                                            <div class="text-caption text-medium-emphasis">Upload your employee list for verification</div>
                                        </div>
                                    </div>
                                    <v-btn size="x-small" variant="tonal" color="indigo" prepend-icon="mdi-download-outline" :disabled="!guidelinesRead" @click="downloadTemplate">
                                        Download Template
                                    </v-btn>
                                </div>
                                <v-divider></v-divider>
                                <div class="pa-5">
                                    <div
                                        class="csv-dropzone rounded-lg pa-8 text-center"
                                        :class="{
                                            'csv-dropzone--active': isDragging,
                                            'csv-dropzone--filled': csvFile,
                                            'csv-dropzone--disabled': !guidelinesRead,
                                        }"
                                        @click="guidelinesRead && triggerFileInput()"
                                        @dragover.prevent="guidelinesRead && (isDragging = true)"
                                        @dragleave.prevent="isDragging = false"
                                        @drop.prevent="guidelinesRead && onFileDrop($event)"
                                    >
                                        <input ref="fileInput" type="file" accept=".csv" class="d-none" @change="onFileChange" />
                                        <template v-if="!csvFile">
                                            <v-icon size="40" :color="guidelinesRead ? 'indigo-lighten-3' : 'grey-lighten-2'" class="mb-3">mdi-file-upload-outline</v-icon>
                                            <div class="text-body-2 font-weight-medium" :class="guidelinesRead ? '' : 'text-medium-emphasis'">Click to upload or drag & drop</div>
                                            <div class="text-caption text-medium-emphasis mt-1">CSV files only · Max 5MB</div>
                                        </template>
                                        <template v-else>
                                            <div class="d-flex align-center justify-center gap-3">
                                                <v-avatar color="success-lighten-5" rounded="lg" size="48">
                                                    <v-icon color="success" size="24">mdi-file-delimited-outline</v-icon>
                                                </v-avatar>
                                                <div class="text-left">
                                                    <div class="text-body-2 font-weight-bold">{{ csvFile.name }}</div>
                                                    <div class="text-caption text-medium-emphasis">{{ formatFileSize(csvFile.size) }}</div>
                                                </div>
                                                <v-btn icon size="x-small" variant="text" color="error" @click.stop="clearFile">
                                                    <v-icon size="16">mdi-close</v-icon>
                                                </v-btn>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </v-card>

                            <!-- Submit -->
                            <div class="d-flex justify-end gap-2">
                                <v-btn variant="text" color="medium-emphasis" size="small" @click="resetAll">Clear</v-btn>
                                <v-btn
                                    color="primary"
                                    variant="tonal"
                                    prepend-icon="mdi-magnify"
                                    :disabled="!canVerify || verifying"
                                    :loading="verifying"
                                    @click="submitVerify"
                                >Verify CSV</v-btn>
                            </div>
                        </div>
                    </v-col>

                    <!-- Right sidebar -->
                    <v-col cols="12" md="4">
                        <v-card class="elevation-1 border-0 rounded-md">
                            <div class="px-5 pt-4 pb-3">
                                <div class="text-subtitle-2 font-weight-bold">How It Works</div>
                            </div>
                            <v-divider></v-divider>
                            <div class="pa-4 d-flex flex-column gap-3">
                                <div v-for="(step, i) in howItWorks" :key="i" class="d-flex align-start gap-3">
                                    <v-avatar color="indigo-lighten-5" size="28" rounded="lg" class="flex-shrink-0 mt-px">
                                        <span style="font-size:0.65rem; font-weight:700; color:#5c6bc0;">{{ i + 1 }}</span>
                                    </v-avatar>
                                    <div>
                                        <div class="text-body-2 font-weight-medium">{{ step.title }}</div>
                                        <div class="text-caption text-medium-emphasis">{{ step.desc }}</div>
                                    </div>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </template>

            <!-- ── STEP: VERIFYING ─────────────────────────────── -->
            <template v-if="step === 'verifying'">
                <v-card class="elevation-1 border-0 rounded-md pa-8 text-center">
                    <v-icon size="48" color="indigo" class="mb-4">mdi-magnify</v-icon>
                    <div class="text-subtitle-1 font-weight-bold mb-2">Verifying CSV Data...</div>
                    <div class="text-body-2 text-medium-emphasis mb-6">
                        Checking for duplicates, invalid formats, and missing fields.
                    </div>
                    <v-progress-linear indeterminate color="indigo" rounded height="6" style="max-width:320px; margin:0 auto;" />
                    <div class="text-caption text-medium-emphasis mt-4">{{ verifyTotal }} rows queued for verification</div>
                </v-card>
            </template>

            <!-- ── STEP: REVIEW ────────────────────────────────── -->
            <template v-if="step === 'review'">

                <!-- Summary bar -->
                <v-row dense>
                    <v-col cols="6" sm="3">
                        <v-card class="elevation-1 border-0 rounded-md pa-4">
                            <div class="text-h6 font-weight-bold">{{ reviewRows.length }}</div>
                            <div class="text-caption text-medium-emphasis">Total Rows</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <v-card class="elevation-1 border-0 rounded-md pa-4">
                            <div class="text-h6 font-weight-bold text-success">{{ validCount }}</div>
                            <div class="text-caption text-medium-emphasis">Valid</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <v-card class="elevation-1 border-0 rounded-md pa-4">
                            <div class="text-h6 font-weight-bold text-error">{{ invalidCount }}</div>
                            <div class="text-caption text-medium-emphasis">Invalid</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <v-card class="elevation-1 border-0 rounded-md pa-4">
                            <div class="text-h6 font-weight-bold text-primary">{{ includedCount }}</div>
                            <div class="text-caption text-medium-emphasis">Selected to Commit</div>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Review table -->
                <v-card class="elevation-1 border-0 rounded-md">
                    <div class="px-5 pt-4 pb-3 d-flex align-center justify-space-between flex-wrap gap-2">
                        <div>
                            <div class="text-subtitle-2 font-weight-bold">Review & Edit Results</div>
                            <div class="text-caption text-medium-emphasis">Invalid rows are sorted to the top — fix errors, toggle rows, then commit</div>
                        </div>
                        <div class="d-flex gap-2">
                            <v-btn size="x-small" variant="tonal" color="success" prepend-icon="mdi-check-all" @click="includeAll">Include All Valid</v-btn>
                            <v-btn size="x-small" variant="tonal" color="indigo"  prepend-icon="mdi-plus"      @click="addRow">Add Row</v-btn>
                        </div>
                    </div>
                    <div class="px-5 pb-3">
                        <v-text-field
                            v-model="reviewSearch"
                            placeholder="Search by name, email, or DOST ID..."
                            variant="solo-filled"
                            density="compact"
                            hide-details
                            clearable
                            prepend-inner-icon="mdi-magnify"
                        />
                    </div>
                    <v-divider></v-divider>

                    <div class="pa-4">
                        <div class="text-caption text-medium-emphasis mb-3 px-1" v-if="reviewSearch">
                            Showing {{ sortedFiltered.length }} of {{ reviewRows.length }} rows
                        </div>

                        <v-row dense>
                            <v-col
                                v-for="row in sortedFiltered"
                                :key="row._key"
                                cols="12"
                                md="6"
                                xl="4"
                            >
                                <v-card
                                    class="review-card h-100"
                                    :class="{
                                        'review-card--valid':   row.include && !hasErrors(row),
                                        'review-card--invalid': hasErrors(row),
                                        'review-card--skipped': !row.include && !hasErrors(row),
                                    }"
                                    variant="outlined"
                                    rounded="lg"
                                >
                                    <!-- Card header -->
                                    <div class="d-flex align-center justify-space-between px-4 pt-3 pb-2">
                                        <div class="d-flex align-center gap-2">
                                            <v-switch
                                                :model-value="row.include"
                                                color="success"
                                                hide-details
                                                density="compact"
                                                style="margin:0;"
                                                @update:model-value="toggleInclude(row)"
                                            />
                                            <v-chip
                                                size="x-small"
                                                :color="row.include && !hasErrors(row) ? 'success' : !row.include ? 'grey' : 'error'"
                                                variant="tonal"
                                            >
                                                <v-icon start size="10">
                                                    {{ row.include && !hasErrors(row) ? 'mdi-check-circle-outline' : !row.include ? 'mdi-minus-circle-outline' : 'mdi-alert-circle-outline' }}
                                                </v-icon>
                                                {{ row.include && !hasErrors(row) ? 'Valid' : !row.include ? 'Skipped' : 'Invalid' }}
                                            </v-chip>
                                        </div>
                                        <v-btn icon size="x-small" variant="text" color="error" @click="removeRow(row)">
                                            <v-icon size="15">mdi-trash-can-outline</v-icon>
                                        </v-btn>
                                    </div>

                                    <v-divider></v-divider>

                                    <!-- Fields grid -->
                                    <div class="pa-3">
                                        <v-row dense>
                                            <!-- Row 1: Prefix · First · Middle · Last · Suffix -->
                                            <v-col cols="2">
                                                <v-text-field
                                                    v-model="row.data.prefix"
                                                    label="Prefix"
                                                    variant="outlined"
                                                    density="compact"
                                                    hide-details
                                                    placeholder="Mr."
                                                />
                                            </v-col>
                                            <v-col cols="4">
                                                <v-text-field
                                                    v-model="row.data.first_name"
                                                    label="First Name"
                                                    variant="outlined"
                                                    density="compact"
                                                    hide-details="auto"
                                                    :error="!!row.errors?.first_name"
                                                    :error-messages="row.errors?.first_name"
                                                    @input="clearError(row, 'first_name')"
                                                />
                                            </v-col>
                                            <v-col cols="4">
                                                <v-text-field
                                                    v-model="row.data.middle_name"
                                                    label="Middle Name"
                                                    variant="outlined"
                                                    density="compact"
                                                    hide-details
                                                />
                                            </v-col>
                                            <v-col cols="2">
                                                <v-text-field
                                                    v-model="row.data.suffix"
                                                    label="Suffix"
                                                    variant="outlined"
                                                    density="compact"
                                                    hide-details
                                                    placeholder="Jr."
                                                />
                                            </v-col>
                                            <!-- Row 2: Last Name -->
                                            <v-col cols="12">
                                                <v-text-field
                                                    v-model="row.data.last_name"
                                                    label="Last Name"
                                                    variant="outlined"
                                                    density="compact"
                                                    hide-details="auto"
                                                    :error="!!row.errors?.last_name"
                                                    :error-messages="row.errors?.last_name"
                                                    @input="clearError(row, 'last_name')"
                                                />
                                            </v-col>
                                            <!-- Row 3: Email -->
                                            <v-col cols="12">
                                                <v-text-field
                                                    v-model="row.data.email"
                                                    label="Email"
                                                    variant="outlined"
                                                    density="compact"
                                                    hide-details="auto"
                                                    :error="!!row.errors?.email"
                                                    :error-messages="row.errors?.email"
                                                    @input="clearError(row, 'email')"
                                                />
                                            </v-col>
                                            <!-- Row 4: DOST ID -->
                                            <v-col cols="12">
                                                <v-text-field
                                                    v-model="row.data.dost_id"
                                                    label="DOST Employee ID"
                                                    variant="outlined"
                                                    density="compact"
                                                    hide-details="auto"
                                                    :error="!!row.errors?.dost_id"
                                                    :error-messages="row.errors?.dost_id"
                                                    @input="clearError(row, 'dost_id')"
                                                />
                                            </v-col>
                                            <!-- Row 5: Position · Yrs -->
                                            <v-col cols="8">
                                                <v-text-field
                                                    v-model="row.data.position"
                                                    label="Position"
                                                    variant="outlined"
                                                    density="compact"
                                                    hide-details
                                                />
                                            </v-col>
                                            <v-col cols="4">
                                                <v-text-field
                                                    v-model="row.data.length_of_service"
                                                    label="Yrs of Service"
                                                    variant="outlined"
                                                    density="compact"
                                                    hide-details
                                                />
                                            </v-col>
                                        </v-row>
                                    </div>
                                </v-card>
                            </v-col>
                        </v-row>

                        <div v-if="sortedFiltered.length === 0" class="text-center py-10">
                            <v-icon size="36" color="grey-lighten-2" class="mb-2">mdi-magnify</v-icon>
                            <div class="text-body-2 text-medium-emphasis">No rows match your search</div>
                        </div>
                    </div>

                    <!-- Commit bar -->
                    <v-divider></v-divider>
                    <div class="pa-4 d-flex align-center justify-space-between flex-wrap gap-2">
                        <div class="text-caption text-medium-emphasis">
                            <strong>{{ includedCount }}</strong> row(s) selected — {{ invalidCount }} invalid row(s) excluded automatically
                        </div>
                        <div class="d-flex gap-2">
                            <v-btn variant="text" size="small" color="medium-emphasis" @click="resetAll">Cancel</v-btn>
                            <v-btn
                                color="primary"
                                variant="tonal"
                                size="small"
                                prepend-icon="mdi-database-import-outline"
                                :disabled="includedCount === 0 || committing"
                                :loading="committing"
                                @click="commitRows"
                            >Commit {{ includedCount }} Account(s)</v-btn>
                        </div>
                    </div>
                </v-card>
            </template>

            <!-- ── STEP: COMMITTING ───────────────────────────── -->
            <template v-if="step === 'committing'">
                <v-card class="elevation-1 border-0 rounded-md pa-8">
                    <div class="d-flex align-center gap-4 mb-4">
                        <v-avatar :color="batch?.finished ? 'success-lighten-5' : 'indigo-lighten-5'" rounded="lg" size="48">
                            <v-icon :color="batch?.finished ? 'success' : 'indigo'" size="24">
                                {{ batch?.finished ? 'mdi-check-circle' : 'mdi-cog-sync-outline' }}
                            </v-icon>
                        </v-avatar>
                        <div>
                            <div class="text-subtitle-1 font-weight-bold">
                                {{ batch?.finished ? 'Accounts Created Successfully' : 'Creating Accounts...' }}
                            </div>
                            <div class="text-body-2 text-medium-emphasis">
                                {{ batch?.processed ?? 0 }} / {{ batch?.total ?? 0 }} accounts processed
                                <span v-if="batch?.failed > 0" class="text-error ml-1">· {{ batch.failed }} failed</span>
                            </div>
                        </div>
                        <v-spacer></v-spacer>
                        <span class="text-h5 font-weight-black" :class="batch?.finished ? 'text-success' : 'text-primary'">
                            {{ batch?.progress ?? 0 }}%
                        </span>
                    </div>
                    <v-progress-linear
                        :model-value="batch?.progress ?? 0"
                        :color="(batch?.failed > 0) ? 'warning' : batch?.finished ? 'success' : 'primary'"
                        height="10"
                        rounded
                        bg-color="grey-lighten-3"
                        :indeterminate="!batch?.finished && batch?.progress === 0"
                    />
                    <div v-if="batch?.finished" class="mt-4 d-flex align-center gap-2">
                        <v-icon size="14" color="success">mdi-check-circle-outline</v-icon>
                        <span class="text-caption text-medium-emphasis">Finished at {{ batch.finished_at }}</span>
                        <v-spacer></v-spacer>
                        <v-btn size="small" variant="tonal" color="primary" @click="resetAll">Generate More</v-btn>
                    </div>
                </v-card>
            </template>

        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({ requests: { type: Array, default: () => [] } });

// ── State machine ──────────────────────────────────────────────
const step = ref('upload'); // upload | verifying | review | committing

// ── Guidelines ─────────────────────────────────────────────────
const guidelinesPanel = ref('open');
const guidelinesRead  = ref(false);

const guidelines = [
    'Ensure all employee data follows the required CSV template format. Download the template before filling in data.',
    'Required columns: first_name, middle_name, last_name, email, dost_id_number.',
    'Each row represents one employee. Avoid blank rows and do not repeat the header.',
    'Duplicate emails or DOST IDs — within the CSV or already in the system — will be flagged as invalid.',
    'After verification you can review, edit, or remove rows before committing to the database.',
    'Only rows marked as "included" will be committed. Invalid rows are excluded by default.',
];

const howItWorks = [
    { title: 'Upload CSV',    desc: 'Select your employee CSV file.' },
    { title: 'Verify',        desc: 'The system validates each row in the background.' },
    { title: 'Review & Edit', desc: 'Fix errors, remove rows, or add new ones.' },
    { title: 'Commit',        desc: 'Save the selected rows as new employee accounts.' },
];

// ── Upload ─────────────────────────────────────────────────────
const fileInput  = ref(null);
const isDragging = ref(false);
const csvFile    = ref(null);
const verifying  = ref(false);
const verifyTotal = ref(0);

const canVerify = computed(() => guidelinesRead.value && csvFile.value);

const triggerFileInput = () => fileInput.value?.click();
const onFileChange     = (e)  => { const f = e.target.files[0]; if (f) csvFile.value = f; };
const onFileDrop       = (e)  => { isDragging.value = false; const f = e.dataTransfer.files[0]; if (f?.name.endsWith('.csv')) csvFile.value = f; };
const clearFile        = ()   => { csvFile.value = null; if (fileInput.value) fileInput.value.value = ''; };

const xsrfToken = () => {
    const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
};

const submitVerify = async () => {
    verifying.value = true;
    const data = new FormData();
    data.append('csv_file', csvFile.value);

    try {
        const res = await fetch('/provincial-admin/csv/verify', {
            method: 'POST',
            headers: { 'X-XSRF-TOKEN': xsrfToken() },
            body: data,
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            alert(err.message ?? 'Verification failed. Please try again.');
            verifying.value = false;
            return;
        }
        const json = await res.json();
        // Results returned immediately — no polling needed
        loadReview(json.results);
    } catch {
        alert('Network error. Please try again.');
        verifying.value = false;
    }
};

// ── Verify polling ─────────────────────────────────────────────
let pollTimer = null;

const pollVerify = (key) => {
    clearInterval(pollTimer);
    pollTimer = setInterval(async () => {
        try {
            const res  = await fetch(`/provincial-admin/csv/verify/${key}`);
            const data = await res.json();
            if (data.status === 'done') {
                clearInterval(pollTimer);
                loadReview(data.results);
            }
        } catch {
            clearInterval(pollTimer);
            alert('Error checking verification status.');
            step.value = 'upload';
        }
    }, 1500);
};

// ── Review ─────────────────────────────────────────────────────
const reviewRows = ref([]);
let rowKey = 0;

const LS_KEY     = 'csv_verify_results';
const LS_VERSION = 'v3'; // bumped: added prefix and suffix fields

const normalizeRow = (r) => ({
    _key:    rowKey++,
    include: r.include ?? true,
    status:  r.status  ?? 'valid',
    errors:  { ...(r.errors ?? {}) },
    data: {
        prefix:            r.data?.prefix            ?? '',
        first_name:        r.data?.first_name        ?? '',
        middle_name:       r.data?.middle_name       ?? '',
        last_name:         r.data?.last_name         ?? '',
        suffix:            r.data?.suffix            ?? '',
        email:             r.data?.email             ?? '',
        dost_id:           r.data?.dost_id           ?? '',
        position:          r.data?.position          ?? '',
        length_of_service: r.data?.length_of_service ?? '',
    },
});

const loadReview = (results) => {
    const rows = results.map(normalizeRow);
    reviewRows.value = rows;
    localStorage.setItem(LS_KEY,               JSON.stringify(rows));
    localStorage.setItem(LS_KEY + '_version',  LS_VERSION);
    step.value = 'review';
    verifying.value = false;
};

// Restore from localStorage only if schema version matches
const storedVersion = localStorage.getItem(LS_KEY + '_version');
const stored        = localStorage.getItem(LS_KEY);

if (stored && storedVersion === LS_VERSION) {
    try {
        reviewRows.value = JSON.parse(stored).map(normalizeRow);
        step.value = 'review';
    } catch {
        localStorage.removeItem(LS_KEY);
        localStorage.removeItem(LS_KEY + '_version');
    }
} else {
    // Stale schema — discard and force a fresh verification
    localStorage.removeItem(LS_KEY);
    localStorage.removeItem(LS_KEY + '_version');
}

const reviewSearch  = ref('');
const sortVersion   = ref(0); // incremented on any include toggle to force re-sort

const toggleInclude = (row) => {
    row.include = !row.include;
    sortVersion.value++;
};

const validCount    = computed(() => reviewRows.value.filter(r => !hasErrors(r)).length);
const invalidCount  = computed(() => reviewRows.value.filter(r => hasErrors(r)).length);
const includedCount = computed(() => reviewRows.value.filter(r => r.include && !hasErrors(r)).length);

const hasErrors  = (row) => Object.keys(row.errors ?? {}).length > 0;
const clearError = (row, field) => { if (row.errors) delete row.errors[field]; };

// Invalid rows first, then new rows, then valid — each group filtered by search
const sortedFiltered = computed(() => {
    sortVersion.value; // reactive dependency — re-runs when any toggle fires
    const q = reviewSearch.value.toLowerCase().trim();
    const match = (row) => {
        if (!q) return true;
        const d = row.data;
        return [d.first_name, d.middle_name, d.last_name, d.email, d.dost_id]
            .some(v => (v ?? '').toLowerCase().includes(q));
    };
    return [...reviewRows.value]
        .filter(match)
        .sort((a, b) => {
            const rank = (r) => {
                if (hasErrors(r))          return 0; // invalid → top
                if (!r.include)            return 1; // toggled off → second
                if (r.status === 'new')    return 2; // new blank rows → third
                return 3;                            // valid & included → bottom
            };
            return rank(a) - rank(b);
        });
});

const includeAll = () => {
    reviewRows.value.forEach(r => { if (!hasErrors(r)) r.include = true; });
    sortVersion.value++;
};

const addRow = () => {
    // New rows go to the top — prepend instead of push
    reviewRows.value.unshift({
        _key:    rowKey++,
        include: true,
        status:  'new',
        errors:  {},
        data:    { prefix: '', first_name: '', middle_name: '', last_name: '', suffix: '', email: '', dost_id: '', position: '', length_of_service: '' },
    });
};

const removeRow = (row) => {
    const idx = reviewRows.value.findIndex(r => r._key === row._key);
    if (idx !== -1) reviewRows.value.splice(idx, 1);
};

// ── Commit ─────────────────────────────────────────────────────
const committing = ref(false);
const batch      = ref(null);
let   batchPoll  = null;

const commitRows = async () => {
    const rows = reviewRows.value
        .filter(r => r.include && !hasErrors(r))
        .map(r => r.data);

    if (!rows.length) return;

    committing.value = true;
    step.value = 'committing';

    try {
        const res = await fetch('/provincial-admin/csv/commit', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
            body:    JSON.stringify({ rows }),
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            alert(err.message ?? 'Commit failed. Please try again.');
            step.value = 'review';
            committing.value = false;
            return;
        }
        const json = await res.json();
        batch.value = { id: json.batch_id, total: json.total_jobs, processed: 0, failed: 0, progress: 0, finished: false, finished_at: null };
        pollBatch(json.batch_id);
    } catch {
        alert('Network error during commit.');
        step.value = 'review';
        committing.value = false;
    }
};

const pollBatch = (batchId) => {
    clearInterval(batchPoll);
    batchPoll = setInterval(async () => {
        try {
            const res  = await fetch(`/auto-generator/batch/${batchId}`);
            const data = await res.json();
            batch.value = {
                id:          data.id,
                total:       data.total_jobs,
                processed:   data.processed_jobs,
                failed:      data.failed_jobs,
                progress:    data.progress,
                finished:    data.finished,
                finished_at: data.finished_at,
            };
            if (data.finished) { clearInterval(batchPoll); committing.value = false; localStorage.removeItem(LS_KEY); localStorage.removeItem(LS_KEY + '_version'); }
        } catch { clearInterval(batchPoll); }
    }, 1500);
};

// ── Reset ──────────────────────────────────────────────────────
const resetAll = () => {
    clearInterval(pollTimer);
    clearInterval(batchPoll);
    localStorage.removeItem(LS_KEY);
    localStorage.removeItem(LS_KEY + '_version');
    step.value           = 'upload';
    csvFile.value        = null;
    guidelinesRead.value = false;
    verifying.value      = false;
    committing.value     = false;
    reviewRows.value     = [];
    batch.value          = null;
    if (fileInput.value) fileInput.value.value = '';
};

const downloadTemplate = () => {
    const headers = 'prefix,dost_id_number,first_name,middle_name,last_name,suffix,email,position,length_of_service';
    const sample  = 'Mr.,DOST-YYYY-EMP-001,Juan,dela,Cruz,,juan.delacruz@dost.gov.ph,Science Research Analyst,3';
    const csv     = `${headers}\n${sample}\n`;

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'employee-accounts-template.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};

const formatFileSize = (bytes) => {
    if (bytes < 1024)    return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
};
</script>

<style scoped>
.csv-dropzone {
    border: 2px dashed rgba(0,0,0,0.12);
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
.csv-dropzone--disabled {
    cursor: not-allowed;
    opacity: 0.5;
}
.step-disabled {
    opacity: 0.6;
    pointer-events: none;
}

/* Review cards */
.review-card {
    transition: border-color 0.15s, background 0.15s;
}
.review-card--valid {
    border-color: rgba(var(--v-theme-success), 0.35) !important;
    background: rgba(var(--v-theme-success), 0.03);
}
.review-card--invalid {
    border-color: rgba(var(--v-theme-error), 0.35) !important;
    background: rgba(var(--v-theme-error), 0.03);
}
.review-card--skipped {
    border-color: rgba(0,0,0,0.08) !important;
    background: #f9fafb;
    opacity: 0.6;
}
</style>
