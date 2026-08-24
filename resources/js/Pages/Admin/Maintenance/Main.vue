<template>
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

                        <!-- System Export -->
                        <v-card class="elevation-1 border-0 rounded-md" style="border-left: 3px solid rgb(var(--v-theme-primary)) !important;">
                            <div class="pa-5 pb-3 d-flex align-center gap-3">
                                <v-avatar color="primary-lighten-5" rounded="lg" size="38">
                                    <v-icon color="primary" size="20">mdi-archive-arrow-down-outline</v-icon>
                                </v-avatar>
                                <div class="flex-1-1">
                                    <div class="text-subtitle-2 font-weight-bold">Full System Export</div>
                                    <div class="text-caption text-medium-emphasis">Export the entire project - source code, database, and configuration - as a ZIP archive</div>
                                </div>
                            </div>
                            <v-divider></v-divider>
                            <div class="pa-5 d-flex flex-column gap-4">

                                <!-- What's included -->
                                <div>
                                    <div class="section-label mb-2">What's included</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <v-chip size="x-small" color="primary" variant="tonal" prepend-icon="mdi-code-braces">Source Code</v-chip>
                                        <v-chip size="x-small" color="primary" variant="tonal" prepend-icon="mdi-database-outline">Database Dump</v-chip>
                                        <v-chip size="x-small" color="primary" variant="tonal" prepend-icon="mdi-folder-outline">Uploaded Files</v-chip>
                                        <v-chip size="x-small" color="primary" variant="tonal" prepend-icon="mdi-cog-outline">Config (.env)</v-chip>
                                    </div>
                                </div>

                                <v-alert type="info" variant="tonal" density="compact" icon="mdi-information-outline">
                                    <span class="text-caption">Excludes <strong>vendor/</strong>, <strong>node_modules/</strong>, and <strong>.git/</strong>. This may take a minute depending on project size.</span>
                                </v-alert>

                                <!-- Export history -->
                                <div v-if="systemBackups.length" class="d-flex flex-column gap-2">
                                    <div class="section-label mb-1">Recent Exports</div>
                                    <div
                                        v-for="b in systemBackups"
                                        :key="b.filename"
                                        class="d-flex align-center justify-space-between pa-3 rounded-lg bg-grey-lighten-5"
                                    >
                                        <div class="d-flex align-center gap-3">
                                            <v-icon size="18" color="primary">mdi-archive-outline</v-icon>
                                            <div>
                                                <div class="text-body-2 font-weight-medium">{{ b.filename }}</div>
                                                <div class="text-caption text-medium-emphasis">{{ b.size }} · {{ b.created_at }}</div>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <v-tooltip text="Download" location="top">
                                                <template #activator="{ props: tip }">
                                                    <v-btn v-bind="tip" icon size="x-small" variant="text" color="primary" :href="`/maintenance/system-backup/${b.filename}`">
                                                        <v-icon size="16">mdi-download-outline</v-icon>
                                                    </v-btn>
                                                </template>
                                            </v-tooltip>
                                            <v-tooltip text="Delete" location="top">
                                                <template #activator="{ props: tip }">
                                                    <v-btn v-bind="tip" icon size="x-small" variant="text" color="error" @click="confirmDeleteSystemBackup(b)">
                                                        <v-icon size="16">mdi-trash-can-outline</v-icon>
                                                    </v-btn>
                                                </template>
                                            </v-tooltip>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-caption text-medium-emphasis">No system exports yet.</div>

                                <div class="d-flex justify-end">
                                    <v-btn
                                        color="primary"
                                        variant="flat"
                                        size="small"
                                        prepend-icon="mdi-archive-arrow-down-outline"
                                        :loading="exportingSystem"
                                        @click="createSystemBackup"
                                    >Export Full System</v-btn>
                                </div>
                            </div>
                        </v-card>

                        <!-- Database Backup -->
                        <v-card class="elevation-1 border-0 rounded-md">
                            <div class="pa-5 pb-3 d-flex align-center gap-3">
                                <v-avatar color="primary-lighten-5" rounded="lg" size="38">
                                    <v-icon color="primary" size="20">mdi-database-export-outline</v-icon>
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
                                                <v-icon size="18" color="primary">mdi-database-outline</v-icon>
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
                                            color="primary"
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

                        <!-- Storage Backup -->
                        <v-card class="elevation-1 border-0 rounded-md">
                            <div class="pa-5 pb-3 d-flex align-center gap-3">
                                <v-avatar color="primary-lighten-5" rounded="lg" size="38">
                                    <v-icon color="primary" size="20">mdi-folder-zip-outline</v-icon>
                                </v-avatar>
                                <div>
                                    <div class="text-subtitle-2 font-weight-bold">Storage Backup</div>
                                    <div class="text-caption text-medium-emphasis">Create and download a ZIP archive of all uploaded files</div>
                                </div>
                            </div>
                            <v-divider></v-divider>
                            <div class="pa-5">
                                <div class="d-flex flex-column gap-3">
                                    <div v-if="storageBackups.length" class="d-flex flex-column gap-2">
                                        <div class="section-label mb-1">Recent Storage Backups</div>
                                        <div
                                            v-for="b in storageBackups"
                                            :key="b.filename"
                                            class="d-flex align-center justify-space-between pa-3 rounded-lg bg-grey-lighten-5"
                                        >
                                            <div class="d-flex align-center gap-3">
                                                <v-icon size="18" color="primary">mdi-folder-zip-outline</v-icon>
                                                <div>
                                                    <div class="text-body-2 font-weight-medium">{{ b.filename }}</div>
                                                    <div class="text-caption text-medium-emphasis">{{ b.size }} · {{ b.created_at }}</div>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-1">
                                                <v-tooltip text="Download" location="top">
                                                    <template #activator="{ props: tip }">
                                                        <v-btn v-bind="tip" icon size="x-small" variant="text" color="primary" :href="`/maintenance/storage-backup/${b.filename}`">
                                                            <v-icon size="16">mdi-download-outline</v-icon>
                                                        </v-btn>
                                                    </template>
                                                </v-tooltip>
                                                <v-tooltip text="Delete" location="top">
                                                    <template #activator="{ props: tip }">
                                                        <v-btn v-bind="tip" icon size="x-small" variant="text" color="error" @click="confirmDeleteStorageBackup(b)">
                                                            <v-icon size="16">mdi-trash-can-outline</v-icon>
                                                        </v-btn>
                                                    </template>
                                                </v-tooltip>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-caption text-medium-emphasis">No storage backups found.</div>
                                    <div class="d-flex justify-end">
                                        <v-btn
                                            color="primary"
                                            variant="tonal"
                                            size="small"
                                            prepend-icon="mdi-folder-zip-outline"
                                            :loading="backingUpStorage"
                                            @click="createStorageBackup"
                                        >Backup Storage Now</v-btn>
                                    </div>
                                </div>
                            </div>
                        </v-card>

                        <!-- Clear Cache -->
                        <v-card class="elevation-1 border-0 rounded-md">
                            <div class="pa-5 pb-3 d-flex align-center gap-3">
                                <v-avatar color="primary-lighten-5" rounded="lg" size="38">
                                    <v-icon color="primary" size="20">mdi-cached</v-icon>
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

                        <!-- Maintenance Mode Toggle -->
                        <v-card
                            class="elevation-1 border-0 rounded-md overflow-hidden"
                            :style="maintenanceMode ? 'border-left: 3px solid rgb(var(--v-theme-error)) !important;' : 'border-left: 3px solid rgb(var(--v-theme-success)) !important;'"
                        >
                            <div class="pa-4 d-flex align-center gap-3">
                                <v-avatar :color="maintenanceMode ? 'error-lighten-5' : 'success-lighten-5'" rounded="lg" size="38">
                                    <v-icon :color="maintenanceMode ? 'error' : 'success'" size="20">
                                        {{ maintenanceMode ? 'mdi-wrench-clock' : 'mdi-check-circle-outline' }}
                                    </v-icon>
                                </v-avatar>
                                <div class="flex-1-1">
                                    <div class="text-subtitle-2 font-weight-bold">Maintenance Mode</div>
                                    <div class="text-caption text-medium-emphasis">
                                        {{ maintenanceMode ? 'System is in maintenance. Only super admins can log in.' : 'System is live. All users can access normally.' }}
                                    </div>
                                </div>
                                <v-switch
                                    :model-value="maintenanceMode"
                                    :color="maintenanceMode ? 'error' : 'success'"
                                    :loading="togglingMode"
                                    hide-details
                                    density="compact"
                                    @update:model-value="toggleMaintenanceMode"
                                />
                            </div>
                            <template v-if="maintenanceMode">
                                <v-divider></v-divider>
                                <div class="px-4 py-3">
                                    <v-alert type="warning" variant="tonal" density="compact" icon="mdi-account-lock-outline" class="text-caption">
                                        Regular users are redirected to the maintenance notice.
                                        Super admins can still get in using the break-glass URL below.
                                    </v-alert>
                                </div>
                            </template>
                        </v-card>

                        <!-- Break-glass Login Password -->
                        <v-card class="elevation-1 border-0 rounded-md">
                            <div class="pa-4 d-flex align-center gap-3">
                                <v-avatar color="primary-lighten-5" rounded="lg" size="38">
                                    <v-icon color="primary" size="20">mdi-shield-key-outline</v-icon>
                                </v-avatar>
                                <div class="flex-1-1">
                                    <div class="text-subtitle-2 font-weight-bold">Break-glass Login Password</div>
                                    <div class="text-caption text-medium-emphasis">
                                        Lets a super admin log in during maintenance via a secret URL
                                    </div>
                                </div>
                                <v-chip
                                    size="x-small"
                                    :color="breakglassSet ? 'success' : 'grey'"
                                    variant="tonal"
                                >{{ breakglassSet ? 'Configured' : 'Not set' }}</v-chip>
                            </div>
                            <v-divider></v-divider>
                            <div class="pa-4 d-flex flex-column gap-3">
                                <v-text-field
                                    v-model="breakglassForm.password"
                                    label="New password"
                                    :type="showBreakglassPw ? 'text' : 'password'"
                                    :append-inner-icon="showBreakglassPw ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                    variant="outlined"
                                    density="compact"
                                    hide-details="auto"
                                    :error-messages="breakglassForm.errors.password"
                                    @click:append-inner="showBreakglassPw = !showBreakglassPw"
                                />
                                <v-text-field
                                    v-model="breakglassForm.password_confirmation"
                                    label="Confirm password"
                                    :type="showBreakglassPw ? 'text' : 'password'"
                                    variant="outlined"
                                    density="compact"
                                    hide-details="auto"
                                />
                                <div class="d-flex gap-2">
                                    <v-btn
                                        variant="tonal"
                                        color="primary"
                                        size="small"
                                        prepend-icon="mdi-dice-multiple-outline"
                                        @click="generateBreakglassPassword"
                                    >Generate</v-btn>
                                    <v-spacer />
                                    <v-btn
                                        variant="flat"
                                        color="primary"
                                        size="small"
                                        prepend-icon="mdi-content-save-outline"
                                        :loading="savingBreakglass"
                                        :disabled="!breakglassForm.password || !breakglassForm.password_confirmation"
                                        @click="saveBreakglassPassword"
                                    >Save</v-btn>
                                </div>

                                <v-alert
                                    v-if="breakglassRevealUrl"
                                    type="success"
                                    variant="tonal"
                                    density="compact"
                                    icon="mdi-key-variant"
                                    closable
                                    @click:close="breakglassRevealUrl = null"
                                >
                                    <div class="text-caption font-weight-bold mb-1">Saved — copy this now</div>
                                    <div class="text-caption mb-2">
                                        This URL won't be shown again; the password is stored as a one-way hash.
                                    </div>
                                    <div class="d-flex align-center gap-2">
                                        <code class="breakglass-url">{{ breakglassRevealUrl }}</code>
                                        <v-btn icon size="x-small" variant="text" @click="copyBreakglassUrl">
                                            <v-icon size="16">mdi-content-copy</v-icon>
                                        </v-btn>
                                    </div>
                                </v-alert>
                            </div>
                        </v-card>

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

            <!-- Delete System Export Confirm Dialog -->
            <v-dialog v-model="deleteSystemBackupDialog" max-width="400" persistent>
                <v-card rounded="lg">
                    <v-card-item class="pt-5 pb-2 px-5">
                        <div class="d-flex align-center gap-3">
                            <v-avatar color="error-lighten-5" size="40" rounded="lg">
                                <v-icon color="error" size="20">mdi-trash-can-outline</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-subtitle-2 font-weight-bold">Delete System Export</div>
                                <div class="text-caption text-medium-emphasis">This cannot be undone</div>
                            </div>
                        </div>
                    </v-card-item>
                    <v-card-text class="px-5 pb-3">
                        <p class="text-body-2">
                            Are you sure you want to delete <strong>{{ targetSystemBackup?.filename }}</strong>?
                        </p>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="px-5 py-3 gap-2 justify-end">
                        <v-btn variant="text" size="small" @click="deleteSystemBackupDialog = false">Cancel</v-btn>
                        <v-btn variant="flat" color="error" size="small" @click="deleteSystemBackup">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Delete Storage Backup Confirm Dialog -->
            <v-dialog v-model="deleteStorageBackupDialog" max-width="400" persistent>
                <v-card rounded="lg">
                    <v-card-item class="pt-5 pb-2 px-5">
                        <div class="d-flex align-center gap-3">
                            <v-avatar color="error-lighten-5" size="40" rounded="lg">
                                <v-icon color="error" size="20">mdi-trash-can-outline</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-subtitle-2 font-weight-bold">Delete Storage Backup</div>
                                <div class="text-caption text-medium-emphasis">This cannot be undone</div>
                            </div>
                        </div>
                    </v-card-item>
                    <v-card-text class="px-5 pb-3">
                        <p class="text-body-2">
                            Are you sure you want to delete <strong>{{ targetStorageBackup?.filename }}</strong>?
                        </p>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="px-5 py-3 gap-2 justify-end">
                        <v-btn variant="text" size="small" @click="deleteStorageBackupDialog = false">Cancel</v-btn>
                        <v-btn variant="flat" color="error" size="small" @click="deleteStorageBackup">Delete</v-btn>
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
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    backups:                  { type: Array,   default: () => [] },
    storage_backups:          { type: Array,   default: () => [] },
    system_backups:           { type: Array,   default: () => [] },
    system_info:               { type: Array,   default: () => [] },
    storage_info:              { type: Array,   default: () => [] },
    maintenance_mode:          { type: Boolean, default: false },
    breakglass_password_set:   { type: Boolean, default: false },
});

// ── Maintenance Mode ───────────────────────────────────────────
const maintenanceMode  = ref(props.maintenance_mode);
const togglingMode     = ref(false);

const toggleMaintenanceMode = () => {
    togglingMode.value = true;
    router.post('/maintenance/toggle-mode', {}, {
        onSuccess: () => { maintenanceMode.value = !maintenanceMode.value; },
        onFinish:  () => { togglingMode.value = false; },
    });
};

// ── Break-glass Login Password ────────────────────────────────
const breakglassSet       = ref(props.breakglass_password_set);
const showBreakglassPw    = ref(false);
const savingBreakglass    = ref(false);
const breakglassRevealUrl = ref(null);

const breakglassForm = useForm({
    password: '',
    password_confirmation: '',
});

const generateBreakglassPassword = () => {
    const bytes = crypto.getRandomValues(new Uint8Array(24));
    const password = Array.from(bytes, (b) => b.toString(36).padStart(2, '0')).join('').slice(0, 32);
    breakglassForm.password = password;
    breakglassForm.password_confirmation = password;
    showBreakglassPw.value = true;
};

const saveBreakglassPassword = () => {
    savingBreakglass.value = true;
    const plaintext = breakglassForm.password;

    breakglassForm.post('/maintenance/breakglass-password', {
        preserveScroll: true,
        onSuccess: () => {
            breakglassSet.value = true;
            breakglassRevealUrl.value = `${window.location.origin}/admin/login/${encodeURIComponent(plaintext)}`;
            breakglassForm.reset();
            showBreakglassPw.value = false;
        },
        onFinish: () => { savingBreakglass.value = false; },
    });
};

const copyBreakglassUrl = () => {
    if (breakglassRevealUrl.value) navigator.clipboard.writeText(breakglassRevealUrl.value);
};

// ── DB Backups ─────────────────────────────────────────────────
const backups = computed(() => props.backups);

const backingUp          = ref(false);
const deleteBackupDialog = ref(false);
const targetBackup       = ref(null);

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

// ── System Export ──────────────────────────────────────────────
const systemBackups             = computed(() => props.system_backups);

const exportingSystem           = ref(false);
const deleteSystemBackupDialog  = ref(false);
const targetSystemBackup        = ref(null);

const createSystemBackup = () => {
    exportingSystem.value = true;
    router.post('/maintenance/system-backup', {}, {
        onFinish: () => { exportingSystem.value = false; },
    });
};

const confirmDeleteSystemBackup = (b) => {
    targetSystemBackup.value = b;
    deleteSystemBackupDialog.value = true;
};

const deleteSystemBackup = () => {
    router.delete(`/maintenance/system-backup/${targetSystemBackup.value.filename}`, {
        onSuccess: () => {
            deleteSystemBackupDialog.value = false;
            targetSystemBackup.value = null;
        },
    });
};

// ── Storage Backups ────────────────────────────────────────────
const storageBackups              = computed(() => props.storage_backups);

const backingUpStorage            = ref(false);
const deleteStorageBackupDialog   = ref(false);
const targetStorageBackup         = ref(null);

const createStorageBackup = () => {
    backingUpStorage.value = true;
    router.post('/maintenance/storage-backup', {}, {
        onFinish: () => { backingUpStorage.value = false; },
    });
};

const confirmDeleteStorageBackup = (b) => {
    targetStorageBackup.value = b;
    deleteStorageBackupDialog.value = true;
};

const deleteStorageBackup = () => {
    router.delete(`/maintenance/storage-backup/${targetStorageBackup.value.filename}`, {
        onSuccess: () => {
            deleteStorageBackupDialog.value = false;
            targetStorageBackup.value = null;
        },
    });
};

// ── Cache ──────────────────────────────────────────────────────
const clearing = ref(null);

const cacheItems = [
    { key: 'app',    label: 'Application Cache', description: 'General app-level cache',        icon: 'mdi-application-outline',  color: 'primary' },
    { key: 'config', label: 'Config Cache',       description: 'Cached configuration values',   icon: 'mdi-cog-outline',           color: 'primary' },
    { key: 'route',  label: 'Route Cache',        description: 'Cached route definitions',      icon: 'mdi-routes',                color: 'primary' },
    { key: 'view',   label: 'View Cache',         description: 'Compiled Blade/template files', icon: 'mdi-eye-outline',           color: 'primary' },
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
    { label: 'Clear All Caches',       icon: 'mdi-cached',                 color: 'primary', handler: () => clearCache('all') },
    { label: 'Optimize Application',   icon: 'mdi-lightning-bolt-outline', color: 'primary', handler: () => router.post('/maintenance/optimize') },
];
</script>

<style scoped>
.breakglass-url {
    flex: 1;
    font-size: 0.72rem;
    word-break: break-all;
    background: rgba(0, 0, 0, 0.05);
    padding: 4px 8px;
    border-radius: 4px;
}

.section-label {
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 0.6rem;
    font-weight: 600;
    color: rgba(0, 0, 0, 0.45);
}
</style>
