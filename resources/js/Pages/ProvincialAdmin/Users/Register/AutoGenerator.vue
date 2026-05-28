<template>
    <AuthenticatedLayout>
        <div class="grid gap-4 w-full">

            <!-- Header -->
            <div>
                <div class="text-h6 font-weight-bold">Auto Account Generator</div>
                <div class="text-caption text-medium-emphasis">Generate employee accounts in bulk</div>
            </div>

            <v-row>
                <!-- Left: Steps -->
                <v-col cols="12" md="8">
                    <div class="d-flex flex-column gap-4">

                        <!-- Step 1: Guidelines -->
                        <v-card class="elevation-1 border-0 rounded-md">
                            <div class="pa-5 pb-3 d-flex align-center gap-3">
                                <v-avatar
                                    :color="guidelinesRead ? 'success-lighten-5' : 'indigo-lighten-5'"
                                    rounded="lg"
                                    size="36"
                                >
                                    <v-icon :color="guidelinesRead ? 'success' : 'indigo'" size="18">
                                        {{ guidelinesRead ? 'mdi-check-circle' : 'mdi-file-document-outline' }}
                                    </v-icon>
                                </v-avatar>
                                <div>
                                    <div class="text-subtitle-2 font-weight-bold">Step 1 — Read the Guidelines</div>
                                    <div class="text-caption text-medium-emphasis">Review all requirements before generating accounts</div>
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
                                                        <span class="text-caption font-weight-bold" style="color:#5c6bc0; font-size:0.6rem;">{{ i + 1 }}</span>
                                                    </v-avatar>
                                                    <div class="text-body-2">{{ item }}</div>
                                                </div>
                                            </div>
                                        </v-expansion-panel-text>
                                    </v-expansion-panel>
                                </v-expansion-panels>

                                <v-checkbox
                                    v-model="guidelinesRead"
                                    color="success"
                                    hide-details
                                    class="mt-3"
                                >
                                    <template #label>
                                        <span class="text-body-2">I have read and understood all the guidelines</span>
                                    </template>
                                </v-checkbox>
                            </div>
                        </v-card>

                        <!-- Step 3: CSV Upload -->
                        <v-card class="elevation-1 border-0 rounded-md" :class="{ 'step-disabled': !guidelinesRead }">
                            <div class="pa-5 pb-3 d-flex align-center justify-space-between">
                                <div class="d-flex align-center gap-3">
                                    <v-avatar
                                        :color="guidelinesRead ? 'indigo-lighten-5' : 'grey-lighten-3'"
                                        rounded="lg"
                                        size="36"
                                    >
                                        <v-icon :color="guidelinesRead ? 'indigo' : 'grey'" size="18">mdi-file-delimited-outline</v-icon>
                                    </v-avatar>
                                    <div>
                                        <div class="text-subtitle-2 font-weight-bold">Step 2 — Employee CSV File</div>
                                        <div class="text-caption text-medium-emphasis">Upload the employee list using the required template</div>
                                    </div>
                                </div>
                                <v-btn
                                    size="x-small"
                                    variant="tonal"
                                    color="indigo"
                                    prepend-icon="mdi-download-outline"
                                    :disabled="!guidelinesRead"
                                >Download Template</v-btn>
                            </div>
                            <v-divider></v-divider>
                            <div class="pa-5">
                                <div
                                    class="csv-dropzone rounded-lg pa-8 text-center"
                                    :class="{
                                        'csv-dropzone--active': isDragging,
                                        'csv-dropzone--filled': form.csv_file,
                                        'csv-dropzone--disabled': !guidelinesRead,
                                    }"
                                    @click="guidelinesRead && triggerFileInput()"
                                    @dragover.prevent="guidelinesRead && (isDragging = true)"
                                    @dragleave.prevent="isDragging = false"
                                    @drop.prevent="guidelinesRead && onFileDrop($event)"
                                >
                                    <input ref="fileInput" type="file" accept=".csv" class="d-none" @change="onFileChange" />

                                    <template v-if="!form.csv_file">
                                        <v-icon size="40" :color="guidelinesRead ? 'indigo-lighten-3' : 'grey-lighten-2'" class="mb-3">
                                            mdi-file-upload-outline
                                        </v-icon>
                                        <div class="text-body-2 font-weight-medium" :class="guidelinesRead ? '' : 'text-medium-emphasis'">
                                            Click to upload or drag & drop
                                        </div>
                                        <div class="text-caption text-medium-emphasis mt-1">CSV files only · Max 5MB</div>
                                    </template>

                                    <template v-else>
                                        <div class="d-flex align-center justify-center gap-3">
                                            <v-avatar color="success-lighten-5" rounded="lg" size="48">
                                                <v-icon color="success" size="24">mdi-file-delimited-outline</v-icon>
                                            </v-avatar>
                                            <div class="text-left">
                                                <div class="text-body-2 font-weight-bold">{{ form.csv_file.name }}</div>
                                                <div class="text-caption text-medium-emphasis">{{ formatFileSize(form.csv_file.size) }}</div>
                                            </div>
                                            <v-btn icon size="x-small" variant="text" color="error" @click.stop="clearFile">
                                                <v-icon size="16">mdi-close</v-icon>
                                            </v-btn>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </v-card>

                        <!-- Actions -->
                        <div class="d-flex justify-end gap-2">
                            <v-btn variant="text" color="medium-emphasis" size="small" @click="resetForm">Clear</v-btn>
                            <v-btn
                                color="primary"
                                variant="tonal"
                                prepend-icon="mdi-account-multiple-plus-outline"
                                :disabled="!canSubmit"
                                @click="generateAccounts"
                            >Generate Accounts</v-btn>
                        </div>

                    </div>
                </v-col>

                <!-- Right: Summary Panel -->
                <v-col cols="12" md="4">
                    <div class="d-flex flex-column gap-4">

                        <!-- What will be generated -->
                        <v-card class="elevation-1 border-0 rounded-md">
                            <div class="px-5 pt-4 pb-3">
                                <div class="text-subtitle-2 font-weight-bold">What Will Be Generated</div>
                                <div class="text-caption text-medium-emphasis">Summary of accounts to be created</div>
                            </div>
                            <v-divider></v-divider>
                            <div class="pa-4 d-flex flex-column gap-3">
                                <!-- Employee accounts -->
                                <div class="summary-item rounded-lg pa-3" :class="form.csv_file ? 'summary-item--filled' : ''">
                                    <div class="d-flex align-center gap-3">
                                        <v-avatar
                                            :color="form.csv_file ? 'indigo-lighten-5' : 'grey-lighten-3'"
                                            rounded="lg"
                                            size="36"
                                        >
                                            <v-icon :color="form.csv_file ? 'indigo' : 'grey-lighten-1'" size="18">
                                                mdi-account-group-outline
                                            </v-icon>
                                        </v-avatar>
                                        <div class="flex-grow-1">
                                            <div class="text-body-2 font-weight-medium">Employee Accounts</div>
                                            <div v-if="form.csv_file" class="text-caption text-indigo">
                                                {{ form.csv_file.name }}
                                            </div>
                                            <div v-else class="text-caption text-medium-emphasis">No CSV uploaded yet</div>
                                        </div>
                                        <v-icon v-if="form.csv_file" size="16" color="success">mdi-check-circle</v-icon>
                                    </div>
                                </div>

                                <!-- Checklist -->
                                <v-divider></v-divider>
                                <div class="d-flex flex-column gap-1">
                                    <div v-for="check in checklist" :key="check.label" class="d-flex align-center gap-2">
                                        <v-icon size="14" :color="check.done ? 'success' : 'grey-lighten-2'">
                                            {{ check.done ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                                        </v-icon>
                                        <span class="text-caption" :class="check.done ? 'text-success' : 'text-medium-emphasis'">
                                            {{ check.label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </v-card>

                        <!-- Credentials note -->
                        <v-card class="elevation-1 border-0 rounded-md">
                            <div class="pa-4">
                                <div class="d-flex align-start gap-3">
                                    <v-icon size="18" color="indigo" class="mt-px">mdi-shield-key-outline</v-icon>
                                    <div>
                                        <div class="text-body-2 font-weight-medium mb-1">Default Credentials</div>
                                        <div class="text-caption text-medium-emphasis">
                                            Generated accounts will use the employee's DOST ID as the username and a system-generated password sent to their registered email.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </v-card>

                    </div>
                </v-col>
            </v-row>

        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    requests: { type: Array, default: () => [] },
});

const guidelines = [
    'Ensure all employee data in the CSV file follows the required template format. Download the template before filling in data.',
    'The CSV must include the following columns: Last Name, First Name, Middle Name, DOST Employee ID, Email, Position, and Date of Birth.',
    'Each row represents one employee. Do not include the header row more than once and avoid blank rows.',
    'Generated accounts will use the DOST Employee ID as the username. A temporary password will be sent to each registered email.',
    'Duplicate DOST Employee IDs or emails in the CSV will cause the generation to fail. Ensure all entries are unique.',
    'Once accounts are generated, they cannot be bulk-deleted. Review all data carefully before clicking Generate Accounts.',
];

const guidelinesPanel = ref('open');
const guidelinesRead  = ref(false);
const fileInput       = ref(null);
const isDragging      = ref(false);

const defaultForm = () => ({
    csv_file:             null,
});

const form = ref(defaultForm());

const canSubmit = computed(() =>
    guidelinesRead.value &&
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
    guidelinesRead.value = false;
    if (fileInput.value) fileInput.value.value = '';
};

const generateAccounts = () => {
    const data = new FormData();
    Object.entries(form.value).forEach(([k, v]) => { if (v !== null) data.append(k, v); });
    router.post('/provincial-admin/auto-generator/generate', data, { onSuccess: resetForm });
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
};
</script>

<style scoped>
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
.csv-dropzone--disabled {
    cursor: not-allowed;
    opacity: 0.5;
}
.step-disabled {
    opacity: 0.6;
    pointer-events: none;
}
.summary-item {
    background: rgba(0, 0, 0, 0.03);
    transition: background 0.2s;
}
.summary-item--filled {
    background: rgba(var(--v-theme-indigo), 0.05);
}
</style>
