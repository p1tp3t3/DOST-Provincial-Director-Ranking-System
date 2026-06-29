<template>
    <!-- Toolbar -->
    <div class="d-flex align-center justify-space-between mb-4 flex-wrap gap-3">
        <div class="d-flex align-center gap-3 flex-wrap">
            <v-text-field
                v-model="search"
                placeholder="Search by name, email, or username..."
                variant="solo-filled"
                density="compact"
                hide-details
                clearable
                prepend-inner-icon="mdi-magnify"
                style="min-width: 260px; max-width: 320px;"
            />
            <v-select
                v-model="selectedRole"
                :items="roleOptions"
                item-title="label"
                item-value="value"
                placeholder="All Roles"
                variant="solo-filled"
                density="compact"
                hide-details
                clearable
                style="min-width: 180px; max-width: 210px;"
            />
        </div>
        <span class="text-caption text-medium-emphasis">
            {{ list.meta.total }} admins
        </span>
    </div>

    <!-- Table -->
    <v-card class="elevation-1 border-0 rounded-md">
        <v-table density="comfortable" hover>
            <thead>
                <tr>
                    <th class="text-caption text-medium-emphasis" width="50">#</th>
                    <th class="text-caption text-medium-emphasis">Name</th>
                    <th class="text-caption text-medium-emphasis">Username</th>
                    <th class="text-caption text-medium-emphasis">Email</th>
                    <th class="text-caption text-medium-emphasis">Province / Region</th>
                    <th class="text-caption text-medium-emphasis text-center" width="140">Role</th>
                    <th class="text-caption text-medium-emphasis text-center" width="100">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(admin, i) in filtered" :key="admin.id">
                    <td class="text-body-2 text-medium-emphasis">{{ i + 1 }}</td>
                    <td>
                        <div class="d-flex align-center gap-3 py-2">
                            <v-avatar :color="roleColor(admin.role) + '-lighten-5'" size="34" rounded="lg">
                                <v-icon :color="roleColor(admin.role)" size="16">{{ roleIcon(admin.role) }}</v-icon>
                            </v-avatar>
                            <span class="text-body-2 font-weight-medium">{{ admin.name }}</span>
                        </div>
                    </td>
                    <td class="text-body-2 text-medium-emphasis">{{ admin.username ?? '-' }}</td>
                    <td class="text-body-2">{{ admin.email }}</td>
                    <td class="text-body-2 text-medium-emphasis">{{ admin.province ?? admin.region ?? '-' }}</td>
                    <td class="text-center">
                        <v-chip
                            size="small"
                            variant="tonal"
                            :color="roleColor(admin.role)"
                        >{{ roleLabel(admin.role) }}</v-chip>
                    </td>
                    <td class="text-center">
                        <div class="d-flex align-center justify-center gap-1">
                            <v-tooltip text="View Profile" location="top">
                                <template #activator="{ props: tip }">
                                    <v-btn
                                        v-bind="tip"
                                        icon size="x-small" variant="text" color="primary"
                                        @click="router.visit(`/profile/${admin.id}`)"
                                    >
                                        <v-icon size="16">mdi-eye-outline</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                            <v-tooltip text="Delete" location="top">
                                <template #activator="{ props: tip }">
                                    <v-btn
                                        v-bind="tip"
                                        icon size="x-small" variant="text" color="error"
                                        @click="confirmDelete(admin)"
                                    >
                                        <v-icon size="16">mdi-trash-can-outline</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                        </div>
                    </td>
                </tr>

                <tr v-if="filtered.length === 0">
                    <td colspan="7">
                        <div class="text-center py-12">
                            <v-icon size="40" color="grey-lighten-2" class="mb-3">mdi-account-search-outline</v-icon>
                            <div class="text-body-2 text-medium-emphasis">No admins found</div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </v-table>
    </v-card>

    <!-- Delete Dialog -->
    <v-dialog v-model="deleteDialog" max-width="420" persistent>
        <v-card rounded="lg">
            <v-card-item class="pt-5 pb-2 px-5">
                <div class="d-flex align-center gap-3">
                    <v-avatar color="error-lighten-5" size="40" rounded="lg">
                        <v-icon color="error" size="20">mdi-trash-can-outline</v-icon>
                    </v-avatar>
                    <div>
                        <div class="text-subtitle-2 font-weight-bold">Delete Admin</div>
                        <div class="text-caption text-medium-emphasis">This action cannot be undone</div>
                    </div>
                </div>
            </v-card-item>
            <v-card-text class="px-5 pb-3">
                <p class="text-body-2">
                    Are you sure you want to delete
                    <strong>{{ targetAdmin?.name }}</strong>?
                    Their account and all associated data will be permanently removed.
                </p>
            </v-card-text>
            <v-divider></v-divider>
            <v-card-actions class="px-5 py-3 gap-2 justify-end">
                <v-btn variant="text" size="small" @click="deleteDialog = false">Cancel</v-btn>
                <v-btn variant="flat" color="error" size="small" :loading="deleting" @click="deleteAdmin">
                    Delete
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Pagination -->
    <div v-if="list.meta.last_page > 1" class="d-flex justify-end mt-6">
        <v-pagination
            v-model="currentPage"
            :length="list.meta.last_page"
            :total-visible="5"
            @update:model-value="changePage"
            color="primary"
            rounded="circle"
            variant="text"
            density="comfortable"
        />
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    list:          { type: Object, required: true },
    initialSearch: { type: String, default: '' },
    initialRole:   { type: String, default: '' },
});

const currentPage = computed({
    get: () => props.list.meta.current_page,
    set: (val) => val,
});

const changePage = (page) => {
    router.get(
        window.location.pathname,
        { page, search: search.value || undefined, role: selectedRole.value || undefined },
        { preserveScroll: true, preserveState: true }
    );
};

const search       = ref(props.initialSearch);
const selectedRole = ref(props.initialRole || null);
const deleteDialog = ref(false);
const deleting     = ref(false);
const targetAdmin  = ref(null);

const roleOptions = [
    { label: 'Super Admin',            value: 'super_admin'            },
    { label: 'Sub Admin',              value: 'sub_admin'              },
    { label: 'Regional Admin',         value: 'regional_admin'         },
    { label: 'Provincial Admin',       value: 'provincial_admin'       },
];

const filtered = computed(() => props.list.data);

let debounceTimer = null;
const sendSearch = () => {
    router.get(
        window.location.pathname,
        { search: search.value || undefined, role: selectedRole.value || undefined },
        { preserveScroll: true, preserveState: true, replace: true }
    );
};

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(sendSearch, 350);
});

watch(selectedRole, () => {
    clearTimeout(debounceTimer);
    sendSearch();
});

const roleLabel = (role) => {
    const map = {
        super_admin:          'Super Admin',
        sub_admin:            'Sub Admin',
        regional_admin:       'Regional Admin',
        provincial_admin:     'Provincial Admin',
    };
    return map[role] ?? role;
};

const roleColor = (role) => {
    const map = {
        super_admin:          'deep-purple',
        sub_admin:            'indigo',
        regional_admin:       'orange',
        provincial_admin:     'teal',
    };
    return map[role] ?? 'grey';
};

const roleIcon = (role) => {
    const map = {
        super_admin:          'mdi-shield-crown-outline',
        sub_admin:            'mdi-shield-account-outline',
        regional_admin:       'mdi-map-marker-radius-outline',
        provincial_admin:     'mdi-account-cog-outline',
    };
    return map[role] ?? 'mdi-account-outline';
};

const confirmDelete = (admin) => {
    targetAdmin.value = admin;
    deleteDialog.value = true;
};

const deleteAdmin = () => {
    if (!targetAdmin.value) return;
    deleting.value = true;
    router.delete(`/admins/${targetAdmin.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value     = false;
            deleteDialog.value = false;
            targetAdmin.value  = null;
        },
    });
};
</script>
