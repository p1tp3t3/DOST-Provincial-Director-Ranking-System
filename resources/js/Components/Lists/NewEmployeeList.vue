<template>
    <!-- Toolbar -->
    <div class="d-flex align-center justify-space-between mb-3 flex-wrap gap-2">
        <v-text-field
            v-model="search"
            placeholder="Search by name, ID, or position..."
            variant="solo-filled"
            density="compact"
            hide-details
            clearable
            prepend-inner-icon="mdi-magnify"
            style="max-width: 280px;"
        />
        <span class="text-caption text-medium-emphasis">
            {{ filtered.length }} of {{ employees.length }} employees
        </span>
    </div>

    <!-- Employee Cards -->
    <v-row dense>
        <v-col
            v-for="(emp, i) in filtered"
            :key="i"
            cols="12"
            sm="6"
            md="4"
            lg="3"
        >
            <v-card
                elevation="0"
                border
                rounded="lg"
                class="employee-card"
                @click="router.visit(`/profile/${emp.id}`)"
            >
                <div class="d-flex align-center gap-3 pa-3">
                    <v-avatar :color="nameColor(emp.name)" size="38" rounded="lg" class="flex-shrink-0">
                        <span class="avatar-initials">{{ initials(emp.name) }}</span>
                    </v-avatar>
                    <div class="overflow-hidden" style="min-width:0; flex:1;">
                        <div class="text-body-2 font-weight-medium text-truncate">{{ emp.name }}</div>
                        <div class="d-flex align-center gap-1 mt-1">
                            <span class="text-caption text-medium-emphasis text-truncate" style="min-width:0; flex:1;">{{ emp.position }}</span>
                            <v-chip
                                v-if="emp.status"
                                size="x-small"
                                variant="tonal"
                                :color="emp.status === 'permanent' ? 'teal' : 'orange'"
                                class="flex-shrink-0"
                                style="font-size:0.6rem;"
                            >{{ emp.status }}</v-chip>
                        </div>
                    </div>
                </div>
            </v-card>
        </v-col>

        <!-- Empty state -->
        <v-col v-if="filtered.length === 0" cols="12">
            <div class="text-center py-8">
                <v-icon size="32" color="grey-lighten-2" class="mb-2">mdi-account-search-outline</v-icon>
                <div class="text-body-2 text-medium-emphasis">
                    No employees found matching "<strong>{{ search }}</strong>"
                </div>
            </div>
        </v-col>
    </v-row>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    employees: { type: Array, default: () => [] },
});

const search = ref('');

const filtered = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return props.employees;
    return props.employees.filter(e =>
        e.name?.toLowerCase().includes(q) ||
        e.id?.toLowerCase().includes(q)   ||
        e.position?.toLowerCase().includes(q)
    );
});

const palette = ['#5C6BC0','#42A5F5','#26A69A','#66BB6A','#FFA726','#EC407A','#AB47BC','#78909C'];
const nameColor = (name = '') => palette[[...name].reduce((a, c) => a + c.charCodeAt(0), 0) % palette.length];
const initials  = (name = '') => name.split(' ').filter(Boolean).slice(0, 2).map(n => n[0]?.toUpperCase() ?? '').join('');
</script>

<style scoped>
.employee-card {
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.employee-card:hover {
    border-color: rgba(var(--v-theme-primary), 0.5) !important;
    box-shadow: 0 2px 10px rgba(var(--v-theme-primary), 0.08) !important;
}
.avatar-initials {
    font-size: 0.72rem;
    font-weight: 700;
    color: white;
}
</style>
