<template>
    <!-- Toolbar -->
    <div class="d-flex align-center justify-space-between mb-4 flex-wrap gap-3">
        <div class="d-flex align-center gap-3 flex-wrap">
            <v-text-field
                v-model="search"
                placeholder="Search by name, email, or ID..."
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
                style="min-width: 160px; max-width: 180px;"
            />
        </div>
        <span class="text-caption text-medium-emphasis">
            {{ list.meta.total }} users
        </span>
    </div>

    <!-- Table -->
    <v-card class="elevation-1 border-0 rounded-md">
        <v-table density="comfortable" hover>
            <thead>
                <tr>
                    <th class="text-caption text-medium-emphasis" width="50">#</th>
                    <th class="text-caption text-medium-emphasis">Name</th>
                    <th class="text-caption text-medium-emphasis">Employee ID</th>
                    <th class="text-caption text-medium-emphasis">Email</th>
                    <th class="text-caption text-medium-emphasis text-center" width="120">Role</th>
                    <th class="text-caption text-medium-emphasis text-center" width="110">Status</th>
                    <th class="text-caption text-medium-emphasis text-center" width="100">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(user, i) in filtered" :key="user.id">
                    <td class="text-body-2 text-medium-emphasis">{{ i + 1 }}</td>
                    <td>
                        <div class="d-flex align-center gap-3 py-2">
                            <v-avatar size="34">
                                <v-img :src="`/profile-picture?filename=${user.profile_picture}`" cover></v-img>
                            </v-avatar>
                            <span class="text-body-2 font-weight-medium">{{ user.name }}</span>
                        </div>
                    </td>
                    <td class="text-body-2 text-medium-emphasis">{{ user.employee_id ?? '—' }}</td>
                    <td class="text-body-2">{{ user.email }}</td>
                    <td class="text-center">
                        <v-chip
                            size="small"
                            variant="tonal"
                            :color="roleColor(user.role)"
                            class="text-capitalize"
                        >
                            {{ user.role }}
                        </v-chip>
                    </td>
                    <!-- Activation switch -->
                    <td class="text-center">
                        <v-tooltip :text="getActivate(user) ? 'Active — click to deactivate' : 'Inactive — click to activate'" location="top">
                            <template #activator="{ props: tip }">
                                <v-switch
                                    v-bind="tip"
                                    :model-value="getActivate(user)"
                                    :disabled="isOwnAccount(user.id) || toggling === user.id"
                                    :loading="toggling === user.id"
                                    color="success"
                                    density="compact"
                                    hide-details
                                    class="d-inline-flex justify-center"
                                    style="width:44px;"
                                    @update:model-value="toggleActivation(user)"
                                />
                            </template>
                        </v-tooltip>
                    </td>

                    <td class="text-center">
                        <div class="d-flex align-center justify-center gap-1">
                            <v-tooltip text="View Profile" location="top">
                                <template #activator="{ props: tip }">
                                    <v-btn
                                        v-bind="tip"
                                        icon
                                        size="x-small"
                                        variant="text"
                                        color="primary"
                                        @click="router.visit(`/profile/${user.id}`)"
                                    >
                                        <v-icon size="16">mdi-eye-outline</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                            <v-tooltip v-if="isSuperAdmin" text="Edit User" location="top">
                                <template #activator="{ props: tip }">
                                    <v-btn
                                        v-bind="tip"
                                        icon
                                        size="x-small"
                                        variant="text"
                                        color="warning"
                                        @click="openEdit(user)"
                                    >
                                        <v-icon size="16">mdi-pencil-outline</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                            <v-tooltip text="Delete User" location="top">
                                <template #activator="{ props: tip }">
                                    <v-btn
                                        v-bind="tip"
                                        icon
                                        size="x-small"
                                        variant="text"
                                        color="error"
                                        @click="confirmDelete(user)"
                                    >
                                        <v-icon size="16">mdi-trash-can-outline</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                        </div>
                    </td>
                </tr>

                <!-- Empty state -->
                <tr v-if="filtered.length === 0">
                    <td colspan="7">
                        <div class="text-center py-12">
                            <v-icon size="40" color="grey-lighten-2" class="mb-3">mdi-account-search-outline</v-icon>
                            <div class="text-body-2 text-medium-emphasis">
                                No users found matching "<strong>{{ search }}</strong>"
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </v-table>
    </v-card>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="420" persistent>
        <v-card rounded="lg">
            <v-card-item class="pt-5 pb-2 px-5">
                <div class="d-flex align-center gap-3">
                    <v-avatar color="error-lighten-5" size="40" rounded="lg">
                        <v-icon color="error" size="20">mdi-trash-can-outline</v-icon>
                    </v-avatar>
                    <div>
                        <div class="text-subtitle-2 font-weight-bold">Delete User</div>
                        <div class="text-caption text-medium-emphasis">This action cannot be undone</div>
                    </div>
                </div>
            </v-card-item>
            <v-card-text class="px-5 pb-3">
                <p class="text-body-2">
                    Are you sure you want to delete
                    <strong>{{ targetUser?.name }}</strong>?
                    Their account and all associated data will be permanently removed.
                </p>
            </v-card-text>
            <v-divider></v-divider>
            <v-card-actions class="px-5 py-3 gap-2 justify-end">
                <v-btn variant="text" size="small" @click="deleteDialog = false">Cancel</v-btn>
                <v-btn variant="flat" color="error" size="small" :loading="deleting" @click="deleteUser">
                    Delete
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <!-- Edit User Dialog -->
    <v-dialog v-model="editDialog" max-width="600" persistent scrollable>
        <v-card rounded="lg">
            <v-card-item class="pt-5 pb-2 px-5">
                <div class="d-flex align-center gap-3">
                    <v-avatar color="warning-lighten-5" size="40" rounded="lg">
                        <v-icon color="warning" size="20">mdi-pencil-outline</v-icon>
                    </v-avatar>
                    <div>
                        <div class="text-subtitle-2 font-weight-bold">Edit User</div>
                        <div class="text-caption text-medium-emphasis">Update account and profile information</div>
                    </div>
                </div>
            </v-card-item>

            <v-card-text class="px-5 pb-3">
                <v-form ref="editFormRef" @submit.prevent="saveEdit">
                    <div class="text-caption text-medium-emphasis text-uppercase font-weight-medium mb-2 mt-1">Account</div>
                    <v-row dense>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="editForm.username"
                                label="Username"
                                variant="outlined"
                                density="compact"
                                :error-messages="editErrors.username"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="editForm.email"
                                label="Email"
                                type="email"
                                variant="outlined"
                                density="compact"
                                :error-messages="editErrors.email"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="editForm.dost_employee_id"
                                label="DOST Employee ID"
                                variant="outlined"
                                density="compact"
                                :error-messages="editErrors.dost_employee_id"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-select
                                v-model="editForm.role"
                                :items="roleOptions"
                                item-title="label"
                                item-value="value"
                                label="Role"
                                variant="outlined"
                                density="compact"
                                :error-messages="editErrors.role"
                            />
                        </v-col>
                        <v-col cols="12">
                            <v-select
                                v-model="editForm.province_id"
                                :items="provinces"
                                item-title="name"
                                item-value="id"
                                label="Province"
                                variant="outlined"
                                density="compact"
                                clearable
                                :error-messages="editErrors.province_id"
                            />
                        </v-col>
                    </v-row>

                    <v-divider class="my-3" />
                    <div class="text-caption text-medium-emphasis text-uppercase font-weight-medium mb-2">Password</div>
                    <v-row dense>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="editForm.password"
                                label="New Password"
                                :type="showPassword ? 'text' : 'password'"
                                :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                variant="outlined"
                                density="compact"
                                placeholder="Leave blank to keep current"
                                persistent-placeholder
                                :error-messages="editErrors.password"
                                @click:append-inner="showPassword = !showPassword"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="editForm.password_confirmation"
                                label="Confirm Password"
                                :type="showPassword ? 'text' : 'password'"
                                variant="outlined"
                                density="compact"
                                :error-messages="editErrors.password_confirmation"
                            />
                        </v-col>
                    </v-row>

                    <v-divider class="my-3" />
                    <div class="text-caption text-medium-emphasis text-uppercase font-weight-medium mb-2">Profile</div>
                    <v-row dense>
                        <v-col cols="4" sm="3">
                            <v-text-field
                                v-model="editForm.prefix"
                                label="Prefix"
                                variant="outlined"
                                density="compact"
                                :error-messages="editErrors.prefix"
                            />
                        </v-col>
                        <v-col cols="8" sm="9">
                            <v-text-field
                                v-model="editForm.first_name"
                                label="First Name"
                                variant="outlined"
                                density="compact"
                                :error-messages="editErrors.first_name"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="editForm.middle_name"
                                label="Middle Name"
                                variant="outlined"
                                density="compact"
                                :error-messages="editErrors.middle_name"
                            />
                        </v-col>
                        <v-col cols="8" sm="7">
                            <v-text-field
                                v-model="editForm.last_name"
                                label="Last Name"
                                variant="outlined"
                                density="compact"
                                :error-messages="editErrors.last_name"
                            />
                        </v-col>
                        <v-col cols="4" sm="5">
                            <v-text-field
                                v-model="editForm.suffix"
                                label="Suffix"
                                variant="outlined"
                                density="compact"
                                :error-messages="editErrors.suffix"
                            />
                        </v-col>
                        <v-col cols="12">
                            <v-text-field
                                v-model="editForm.length_of_service"
                                label="Length of Service"
                                variant="outlined"
                                density="compact"
                                :error-messages="editErrors.length_of_service"
                            />
                        </v-col>
                    </v-row>
                </v-form>
            </v-card-text>

            <v-divider />
            <v-card-actions class="px-5 py-3 gap-2 justify-end">
                <v-btn variant="text" size="small" :disabled="editSaving" @click="editDialog = false">Cancel</v-btn>
                <v-btn variant="flat" color="warning" size="small" :loading="editSaving" @click="saveEdit">
                    Save Changes
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
        ></v-pagination>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const defPic = 'https://scontent.fcgy1-3.fna.fbcdn.net/v/t39.30808-1/569409499_2926389544213362_5572906559510250325_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=107&ccb=1-7&_nc_sid=1d2534&_nc_eui2=AeHxMS2Jaxqdlz7XjktrvQNCkuMFs6-OJrWS4wWzr44mtaR_gFGX3XynJKcVctLnDQznMva1uf7y4DJ9zvqkENur&_nc_ohc=t1KgQyv8YI4Q7kNvwFZDK9s&_nc_oc=AdovjHXEhGImiLI-b4UzqvAlKfytDYJV4eb0rG9Z9EgUyAyg_EF3UGX2mFLadgn20tFa5hK9DE54diCLrTUm3qlo&_nc_zt=24&_nc_ht=scontent.fcgy1-3.fna&_nc_gid=AYiNPwYYm5mm809vxgPhoA&_nc_ss=782a8&oh=00_Af5BeBLxqbptd619Z7yoL_PoCiaDpG1OQR9LWJj9XiJMMw&oe=6A13B822';

const props = defineProps({
    list:          { type: Object, required: true },
    initialSearch: { type: String, default: '' },
    initialRole:   { type: String, default: '' },
    provinces:     { type: Array, default: () => [] },
});

const currentPage = computed({
    get: () => props.list.meta.current_page,
    set: (val) => val,
});

const search       = ref(props.initialSearch);
const selectedRole = ref(props.initialRole || null);
const page         = usePage();
const deleteDialog = ref(false);
const deleting     = ref(false);
const targetUser   = ref(null);
const toggling     = ref(null);
const localState   = ref({});  // userId → boolean override for instant UI feedback

const isSuperAdmin  = computed(() => page.props.auth?.user?.role === 'super_admin');
const isOwnAccount  = (id) => page.props.auth?.user?.id === id;
const getActivate   = (user) => localState.value[user.id] ?? user.activate;

const editDialog  = ref(false);
const editSaving  = ref(false);
const editErrors  = ref({});
const editForm    = ref({});
const showPassword = ref(false);

const openEdit = (user) => {
    editErrors.value  = {};
    showPassword.value = false;
    editForm.value = {
        username:              user.username,
        email:                 user.email,
        dost_employee_id:      user.employee_id,
        role:                  user.role,
        province_id:           user.province_id,
        password:              '',
        password_confirmation: '',
        prefix:                user.prefix ?? '',
        first_name:            user.first_name ?? '',
        middle_name:           user.middle_name ?? '',
        last_name:             user.last_name ?? '',
        suffix:                user.suffix ?? '',
        length_of_service:     user.length_of_service ?? '',
        _userId:               user.id,
    };
    editDialog.value = true;
};

const saveEdit = async () => {
    editSaving.value = true;
    editErrors.value = {};
    try {
        await axios.put(`/users/${editForm.value._userId}`, editForm.value);
        editDialog.value = false;
        router.reload({ preserveScroll: true });
    } catch (err) {
        if (err.response?.status === 422) {
            editErrors.value = err.response.data.errors ?? {};
        }
    } finally {
        editSaving.value = false;
    }
};

const toggleActivation = (user) => {
    if (toggling.value || isOwnAccount(user.id)) return;

    const prev = getActivate(user);
    const next = !prev;

    // Optimistic update — flip immediately, no page reload
    localState.value = { ...localState.value, [user.id]: next };
    toggling.value   = user.id;

    router.patch(`/users/${user.id}/toggle-activation`, {}, {
        preserveScroll: true,
        preserveState:  true,
        onError: () => {
            // Revert on failure
            localState.value = { ...localState.value, [user.id]: prev };
        },
        onFinish: () => { toggling.value = null; },
    });
};

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

const changePage = (page) => {
    router.get(
        window.location.pathname,
        { page, search: search.value || undefined, role: selectedRole.value || undefined },
        { preserveScroll: true, preserveState: true }
    );
};

const roleOptions = [
    { label: 'Super Admin',        value: 'super_admin'         },
    { label: 'Sub Admin',          value: 'sub_admin'           },
    { label: 'Provincial Admin',   value: 'provincial_admin'    },
    { label: 'Provincial Director', value: 'provincial_director' },
    { label: 'Employee',           value: 'employee'            },
];

const filtered = computed(() => props.list.data);

const roleColor = (role) => {
    const map = { admin: 'indigo', 'sub-admin': 'purple', director: 'blue', employee: 'teal' };
    return map[role?.toLowerCase()] ?? 'grey';
};

const confirmDelete = (user) => {
    targetUser.value = user;
    deleteDialog.value = true;
};

const deleteUser = () => {
    if (!targetUser.value) return;
    deleting.value = true;
    router.delete(`/users/${targetUser.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value  = false;
            deleteDialog.value = false;
            targetUser.value   = null;
        },
    });
};
</script>
