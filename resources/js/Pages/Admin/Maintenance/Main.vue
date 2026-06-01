<template>
    <AuthenticatedLayout>
        <div class="grid gap-4 w-full">

            <!-- Header -->
            <div>
                <div class="text-h6 font-weight-bold">Maintenance</div>
                <div class="text-caption text-medium-emphasis">System tools, backups, and configuration</div>
            </div>

            <v-row>
                <!-- Left Column -->
                <v-col cols="12" md="8">
                    <div class="d-flex flex-column gap-4">

                        <!-- Database Backup -->
                        <v-card class="elevation-1 border-0 rounded-md">
                            <div class="pa-5 pb-3 d-flex align-center gap-3">
                                <v-avatar color="indigo-lighten-5" rounded="lg" size="38">
                                    <v-icon color="indigo" size="20">mdi-database-export-outline</v-icon>
                                </v-avatar>
                                <div>
                                    <div class="text-subtitle-2 font-weight-bold">Database Backup</div>
                                    <div class="text-caption text-medium-emphasis">Create and download a full backup of the system database</div>
                                </div>
                            </div>
                            <v-divider></v-divider>
                            <div class="pa-5">
                                <div class="d-flex flex-column gap-3">
                                    <!-- Backup history -->
                                    <div v-if="backups.length" class="d-flex flex-column gap-2">
                                        <div class="section-label mb-1">Recent Backups</div>
                                        <div
                                            v-for="b in backups"
                                            :key="b.id"
                                            class="d-flex align-center justify-space-between pa-3 rounded-lg bg-grey-lighten-5"
                                        >
                                            <div class="d-flex align-center gap-3">
                                                <v-icon size="18" color="indigo">mdi-database-outline</v-icon>
                                                <div>
                                                    <div class="text-body-2 font-weight-medium">{{ b.filename }}</div>
                                                    <div class="text-caption text-medium-emphasis">{{ b.size }} · {{ b.created_at }}</div>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-1">
                                                <v-tooltip text="Download" location="top">
                                                    <template #activator="{ props: tip }">
                                                        <v-btn v-bind="tip" icon size="x-small" variant="text" color="primary" :href="`/maintenance/backup/${b.filename}`">
                                                            <v-icon size="16">mdi-download-outline</v-icon>
                                                        </v-btn>
                                                    </template>
                                                </v-tooltip>
                                                <v-tooltip text="Delete" location="top">
                                                    <template #activator="{ props: tip }">
                                                        <v-btn v-bind="tip" icon size="x-small" variant="text" color="error" @click="confirmDeleteBackup(b)">
                                                            <v-icon size="16">mdi-trash-can-outline</v-icon>
                                                        </v-btn>
                                                    </template>
                                                </v-tooltip>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-caption text-medium-emphasis">No backups found.</div>
                                    <div class="d-flex justify-end">
                                        <v-btn
                                            color="indigo"
                                            variant="tonal"
                                            size="small"
                                            prepend-icon="mdi-database-export-outline"
                                            :loading="backingUp"
                                            @click="createBackup"
                                        >Create Backup Now</v-btn>
                                    </div>
                                </div>
                            </div>
                        </v-card>

                        <!-- Clear Cache -->
                        <v-card class="elevation-1 border-0 rounded-md">
                            <div class="pa-5 pb-3 d-flex align-center gap-3">
                                <v-avatar color="orange-lighten-5" rounded="lg" size="38">
                                    <v-icon color="orange" size="20">mdi-cached</v-icon>
                                </v-avatar>
                                <div>
                                    <div class="text-subtitle-2 font-weight-bold">Clear Cache</div>
                                    <div class="text-caption text-medium-emphasis">Flush application, config, and route caches</div>
                                </div>
                            </div>
                            <v-divider></v-divider>
                            <div class="pa-5 d-flex flex-column gap-3">
                                <div
                                    v-for="cache in cacheItems"
                                    :key="cache.key"
                                    class="d-flex align-center justify-space-between pa-3 rounded-lg bg-grey-lighten-5"
                                >
                                    <div class="d-flex align-center gap-3">
                                        <v-icon size="16" :color="cache.color">{{ cache.icon }}</v-icon>
                                        <div>
                                            <div class="text-body-2 font-weight-medium">{{ cache.label }}</div>
                                            <div class="text-caption text-medium-emphasis">{{ cache.description }}</div>
                                        </div>
                                    </div>
                                    <v-btn
                                        size="x-small"
                                        variant="tonal"
                                        :color="cache.color"
                                        :loading="clearing === cache.key"
                                        @click="clearCache(cache.key)"
                                    >Clear</v-btn>
                                </div>
                            </div>
                        </v-card>

                        <!-- Seeder / Data Reset -->
                        <v-card class="elevation-1 border-0 rounded-md">
                            <div class="pa-5 pb-3 d-flex align-center gap-3">
                                <v-avatar color="red-lighten-5" rounded="lg" size="38">
                                    <v-icon color="red" size="20">mdi-database-refresh-outline</v-icon>
                                </v-avatar>
                                <div>
                                    <div class="text-subtitle-2 font-weight-bold">Data Reset</div>
                                    <div class="text-caption text-medium-emphasis">Re-seed the database with default data. This will erase all current records.</div>
                                </div>
                            </div>
                            <v-divider></v-divider>
                            <div class="pa-5">
                                <v-alert type="warning" variant="tonal" density="compact" class="mb-4" icon="mdi-alert-outline">
                                    <span class="text-caption">This action is <strong>irreversible</strong>. All user data, KPI records, and logs will be permanently deleted and replaced with seed data.</span>
                                </v-alert>
                                <div class="d-flex justify-end">
                                    <v-btn
                                        color="error"
                                        variant="tonal"
                                        size="small"
                                        prepend-icon="mdi-database-refresh-outline"
                                        @click="resetDialog = true"
                                    >Reset &amp; Re-seed</v-btn>
                                </div>
                            </div>
                        </v-card>

                    </div>
                </v-col>

                <!-- Right Column -->
                <v-col cols="12" md="4">
                    <div class="d-flex flex-column gap-4">

                        <!-- System Info -->
                        <v-card class="elevation-1 border-0 rounded-md">
                            <div class="px-5 pt-4 pb-3">
                                <div class="text-subtitle-2 font-weight-bold">System Info</div>
                            </div>
                            <v-divider></v-divider>
                            <div class="pa-4 d-flex flex-column gap-3">
                                <div v-for="info in systemInfo" :key="info.label" class="d-flex align-center justify-space-between">
                                    <span class="text-caption text-medium-emphasis">{{ info.label }}</span>
                                    <span class="text-caption font-weight-medium">{{ info.value }}</span>
                                </div>
                            </div>
                        </v-card>

                        <!-- Storage Usage -->
                        <v-card class="elevation-1 border-0 rounded-md">
                            <div class="px-5 pt-4 pb-3">
                                <div class="text-subtitle-2 font-weight-bold">Storage Usage</div>
                            </div>
                            <v-divider></v-divider>
                            <div class="pa-4 d-flex flex-column gap-3">
                                <div v-for="s in storageItems" :key="s.label">
                                    <div class="d-flex align-center justify-space-between mb-1">
                                        <span class="text-caption text-medium-emphasis">{{ s.label }}</span>
                                        <span class="text-caption font-weight-medium">{{ s.used }} / {{ s.total }}</span>
                                    </div>
                                    <v-progress-linear
                                        :model-value="s.percent"
                                        :color="s.percent > 80 ? 'error' : s.percent > 60 ? 'warning' : 'primary'"
                                        rounded
                                        height="6"
                                        bg-color="grey-lighten-3"
                                    />
                                </div>
                            </div>
                        </v-card>

                        <!-- Quick Actions -->
                        <v-card class="elevation-1 border-0 rounded-md">
                            <div class="px-5 pt-4 pb-3">
                                <div class="text-subtitle-2 font-weight-bold">Quick Actions</div>
                            </div>
                            <v-divider></v-divider>
                            <div class="pa-3 d-flex flex-column gap-1">
                                <v-btn
                                    v-for="action in quickActions"
                                    :key="action.label"
                                    variant="text"
                                    :color="action.color ?? 'default'"
                                    class="justify-start"
                                    size="small"
                                    :prepend-icon="action.icon"
                                    @click="action.handler"
                                >{{ action.label }}</v-btn>
                            </div>
                        </v-card>

                    </div>
                </v-col>
            </v-row>

            <!-- Delete Backup Confirm Dialog -->
            <v-dialog v-model="deleteBackupDialog" max-width="400" persistent>
                <v-card rounded="lg">
                    <v-card-item class="pt-5 pb-2 px-5">
                        <div class="d-flex align-center gap-3">
                            <v-avatar color="error-lighten-5" size="40" rounded="lg">
                                <v-icon color="error" size="20">mdi-trash-can-outline</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-subtitle-2 font-weight-bold">Delete Backup</div>
                                <div class="text-caption text-medium-emphasis">This cannot be undone</div>
                            </div>
                        </div>
                    </v-card-item>
                    <v-card-text class="px-5 pb-3">
                        <p class="text-body-2">
                            Are you sure you want to delete <strong>{{ targetBackup?.filename }}</strong>?
                        </p>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="px-5 py-3 gap-2 justify-end">
                        <v-btn variant="text" size="small" @click="deleteBackupDialog = false">Cancel</v-btn>
                        <v-btn variant="flat" color="error" size="small" @click="deleteBackup">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Reset Confirm Dialog -->
            <v-dialog v-model="resetDialog" max-width="440" persistent>
                <v-card rounded="lg">
                    <v-card-item class="pt-5 pb-2 px-5">
                        <div class="d-flex align-center gap-3">
                            <v-avatar color="error-lighten-5" size="40" rounded="lg">
                                <v-icon color="error" size="20">mdi-database-refresh-outline</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-subtitle-2 font-weight-bold">Reset & Re-seed Database</div>
                                <div class="text-caption text-medium-emphasis">All data will be permanently erased</div>
                            </div>
                        </div>
                    </v-card-item>
                    <v-card-text class="px-5 pb-3">
                        <p class="text-body-2 mb-3">
                            This will drop all existing records and re-populate the database with default seed data. Type <strong>RESET</strong> below to confirm.
                        </p>
                        <v-text-field
                            v-model="resetConfirmText"
                            placeholder="Type RESET to confirm"
                            variant="outlined"
                            density="compact"
                            hide-details
                        />
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="px-5 py-3 gap-2 justify-end">
                        <v-btn variant="text" size="small" @click="resetDialog = false; resetConfirmText = ''">Cancel</v-btn>
                        <v-btn
                            variant="flat"
                            color="error"
                            size="small"
                            :disabled="resetConfirmText !== 'RESET'"
                            :loading="resetting"
                            @click="resetDatabase"
                        >Confirm Reset</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    backups:      { type: Array, default: () => [] },
    system_info:  { type: Array, default: () => [] },
    storage_info: { type: Array, default: () => [] },
});

// ── Backups ────────────────────────────────────────────────────
const backups = computed(() => props.backups);

const backingUp         = ref(false);
const deleteBackupDialog = ref(false);
const targetBackup      = ref(null);

const createBackup = () => {
    backingUp.value = true;
    router.post('/maintenance/backup', {}, {
        onFinish: () => { backingUp.value = false; },
    });
};

const confirmDeleteBackup = (b) => {
    targetBackup.value = b;
    deleteBackupDialog.value = true;
};

const deleteBackup = () => {
    router.delete(`/maintenance/backup/${targetBackup.value.filename}`, {
        onSuccess: () => {
            deleteBackupDialog.value = false;
            targetBackup.value = null;
        },
    });
};

// ── Cache ──────────────────────────────────────────────────────
const clearing = ref(null);

const cacheItems = [
    { key: 'app',    label: 'Application Cache', description: 'General app-level cache',        icon: 'mdi-application-outline',  color: 'orange'  },
    { key: 'config', label: 'Config Cache',       description: 'Cached configuration values',   icon: 'mdi-cog-outline',           color: 'blue'    },
    { key: 'route',  label: 'Route Cache',        description: 'Cached route definitions',      icon: 'mdi-routes',                color: 'teal'    },
    { key: 'view',   label: 'View Cache',         description: 'Compiled Blade/template files', icon: 'mdi-eye-outline',           color: 'purple'  },
];

const clearCache = (key) => {
    clearing.value = key;
    router.post(`/maintenance/cache/clear/${key}`, {}, {
        onFinish: () => { clearing.value = null; },
    });
};

// ── Reset ──────────────────────────────────────────────────────
const resetDialog      = ref(false);
const resetConfirmText = ref('');
const resetting        = ref(false);

const resetDatabase = () => {
    resetting.value = true;
    router.post('/maintenance/reset', {}, {
        onSuccess: () => {
            resetDialog.value = false;
            resetConfirmText.value = '';
            resetting.value = false;
        },
        onError: () => { resetting.value = false; },
    });
};

// ── Static info ────────────────────────────────────────────────
const systemInfo  = props.system_info;
const storageItems = props.storage_info;

const quickActions = [
    { label: 'Download Latest Backup', icon: 'mdi-download-outline',      color: 'primary', handler: () => {} },
    { label: 'View Error Logs',        icon: 'mdi-text-box-outline',       color: 'default', handler: () => router.visit('/activity-logs') },
    { label: 'Clear All Caches',       icon: 'mdi-cached',                 color: 'orange',  handler: () => clearCache('all') },
    { label: 'Optimize Application',   icon: 'mdi-lightning-bolt-outline', color: 'success', handler: () => router.post('/maintenance/optimize') },
];
</script>

<style scoped>
.section-label {
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 0.6rem;
    font-weight: 600;
    color: rgba(0, 0, 0, 0.45);
}
</style>
