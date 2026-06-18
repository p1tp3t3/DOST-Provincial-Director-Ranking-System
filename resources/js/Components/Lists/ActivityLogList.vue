<template>
    <!-- Toolbar -->
    <div class="d-flex align-center justify-space-between mb-4 flex-wrap gap-3">
        <div class="d-flex align-center gap-3 flex-wrap">
            <v-text-field
                v-model="search"
                placeholder="Search by name, action, or description..."
                variant="solo-filled"
                density="compact"
                hide-details
                clearable
                prepend-inner-icon="mdi-magnify"
                style="min-width:260px; max-width:320px;"
            />
            <v-select
                v-model="selectedType"
                :items="typeOptions"
                item-title="label"
                item-value="value"
                placeholder="All Types"
                variant="solo-filled"
                density="compact"
                hide-details
                clearable
                style="min-width:160px; max-width:190px;"
            />
        </div>
        <span class="text-caption text-medium-emphasis">
            {{ filtered.length }} of {{ list.data.length }} logs
        </span>
    </div>

    <!-- Table -->
    <v-card class="elevation-1 border-0 rounded-md">
        <v-table density="comfortable" hover>
            <thead>
                <tr>
                    <th class="text-caption text-medium-emphasis" width="50">#</th>
                    <th class="text-caption text-medium-emphasis">User</th>
                    <th class="text-caption text-medium-emphasis">Role</th>
                    <th class="text-caption text-medium-emphasis">Action</th>
                    <th class="text-caption text-medium-emphasis">Description</th>
                    <th class="text-caption text-medium-emphasis" width="160">Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(log, i) in filtered" :key="log.id">
                    <td class="text-body-2 text-medium-emphasis">{{ i + 1 }}</td>
                    <td>
                        <div class="d-flex align-center gap-3 py-2">
                            <v-avatar :color="roleColor(log.role) + '-lighten-5'" size="32" rounded="lg">
                                <v-icon :color="roleColor(log.role)" size="15">mdi-account-outline</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-body-2 font-weight-medium">{{ log.name }}</div>
                                <div class="text-caption text-medium-emphasis">{{ log.employee_id ?? '—' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <v-chip size="x-small" variant="tonal" :color="roleColor(log.role)">
                            {{ roleLabel(log.role) }}
                        </v-chip>
                    </td>
                    <td>
                        <v-chip size="x-small" variant="tonal" :color="typeColor(log.type)">
                            <v-icon start size="11">{{ typeIcon(log.type) }}</v-icon>
                            {{ log.type }}
                        </v-chip>
                    </td>
                    <td class="text-body-2 text-medium-emphasis" style="max-width:300px;">
                        <span class="text-truncate d-block">{{ log.description }}</span>
                    </td>
                    <td class="text-caption text-medium-emphasis">{{ formatDate(log.created_at) }}</td>
                </tr>

                <tr v-if="filtered.length === 0">
                    <td colspan="6">
                        <div class="text-center py-12">
                            <v-icon size="40" color="grey-lighten-2" class="mb-3">mdi-text-search</v-icon>
                            <div class="text-body-2 text-medium-emphasis">No logs found</div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </v-table>

        <!-- Pagination -->
        <div v-if="list.meta.last_page > 1" class="d-flex justify-end px-4 py-3">
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
    </v-card>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    list: { type: Object, required: true },
});

const currentPage = computed({
    get: () => props.list.meta.current_page,
    set: (val) => val,
});

const changePage = (page) => {
    search.value = '';
    router.get(window.location.pathname, { page }, { preserveScroll: true, preserveState: true });
};

const search       = ref('');
const selectedType = ref(null);

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

const filtered = computed(() => {
    let data = props.list.data;
    const q = search.value.toLowerCase().trim();
    if (q) {
        const m = (v) => (v ?? '').toLowerCase().includes(q);
        data = data.filter(l => m(l.name) || m(l.type) || m(l.description));
    }
    if (selectedType.value) {
        data = data.filter(l => l.type === selectedType.value);
    }
    return data;
});

const formatDate = (dt) => {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('en-PH', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const typeColor = (type) => ({
    login:    'success',
    logout:   'grey',
    create:   'indigo',
    update:   'blue',
    delete:   'error',
    generate: 'teal',
    export:   'purple',
    view:     'orange',
}[type] ?? 'grey');

const typeIcon = (type) => ({
    login:    'mdi-login',
    logout:   'mdi-logout',
    create:   'mdi-plus-circle-outline',
    update:   'mdi-pencil-outline',
    delete:   'mdi-trash-can-outline',
    generate: 'mdi-account-multiple-plus-outline',
    export:   'mdi-export',
    view:     'mdi-eye-outline',
}[type] ?? 'mdi-circle-outline');

const roleColor = (role) => ({
    super_admin:          'indigo',
    sub_admin:            'purple',
    provincial_admin:     'teal',
    provincial_director:  'blue',
    employee:             'grey',
}[role] ?? 'grey');

const roleLabel = (role) => ({
    super_admin:          'Super Admin',
    sub_admin:            'Sub Admin',
    provincial_admin:     'Prov. Admin',
    provincial_director:  'Director',
    employee:             'Employee',
}[role] ?? role);
</script>
