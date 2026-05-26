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
            {{ filtered.length }} of {{ list.data.length }} users
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
                    <th class="text-caption text-medium-emphasis text-center" width="100">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(user, i) in filtered" :key="user.id">
                    <td class="text-body-2 text-medium-emphasis">{{ i + 1 }}</td>
                    <td>
                        <div class="d-flex align-center gap-3 py-2">
                            <v-avatar size="34">
                                <v-img :src="defPic" cover></v-img>
                            </v-avatar>
                            <span class="text-body-2 font-weight-medium">{{ user.name }}</span>
                        </div>
                    </td>
                    <td class="text-body-2 text-medium-emphasis">{{ user.id }}</td>
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
                    <td colspan="6">
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
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const defPic = 'https://scontent.fcgy1-3.fna.fbcdn.net/v/t39.30808-1/569409499_2926389544213362_5572906559510250325_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=107&ccb=1-7&_nc_sid=1d2534&_nc_eui2=AeHxMS2Jaxqdlz7XjktrvQNCkuMFs6-OJrWS4wWzr44mtaR_gFGX3XynJKcVctLnDQznMva1uf7y4DJ9zvqkENur&_nc_ohc=t1KgQyv8YI4Q7kNvwFZDK9s&_nc_oc=AdovjHXEhGImiLI-b4UzqvAlKfytDYJV4eb0rG9Z9EgUyAyg_EF3UGX2mFLadgn20tFa5hK9DE54diCLrTUm3qlo&_nc_zt=24&_nc_ht=scontent.fcgy1-3.fna&_nc_gid=AYiNPwYYm5mm809vxgPhoA&_nc_ss=782a8&oh=00_Af5BeBLxqbptd619Z7yoL_PoCiaDpG1OQR9LWJj9XiJMMw&oe=6A13B822';

const props = defineProps({
    list: { type: Object, required: true },
});
const currentPage = computed({
    get: () => props.list.meta.current_page,
    set: (val) => val
});
const changePage = (page) => {
    search.value = '';
    router.get(
        window.location.pathname,
        { page },
        { preserveScroll: true, preserveState: true }
    );
};

const search       = ref('');
const selectedRole = ref(null);
const deleteDialog = ref(false);
const deleting     = ref(false);
const targetUser   = ref(null);

const roleOptions = [
    { label: 'Admin',         value: 'admin'     },
    { label: 'Sub-Admin',     value: 'sub-admin' },
    { label: 'Director',      value: 'director'  },
    { label: 'Employee',      value: 'employee'  },
];

const filtered = computed(() => {
    let data = props.list.data;
    const q = search.value.toLowerCase().trim();
    if (q) {
        data = data.filter(u =>
            u.name?.toLowerCase().includes(q)  ||
            u.email?.toLowerCase().includes(q) ||
            u.id?.toLowerCase().includes(q)
        );
    }
    if (selectedRole.value) {
        data = data.filter(u => u.role?.toLowerCase() === selectedRole.value);
    }
    return data;
});

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
