<template>
    <!-- Toolbar -->
    <div class="d-flex align-center justify-space-between mb-3 flex-wrap gap-2">
        <div class="d-flex align-center gap-2 flex-wrap">
            <v-text-field
                v-model="search"
                placeholder="Search province or director..."
                variant="solo-filled"
                density="compact"
                hide-details
                clearable
                prepend-inner-icon="mdi-magnify"
                style="min-width: 220px; max-width: 280px;"
            />
            <v-select
                v-model="selectedCategory"
                :items="categoryOptions"
                item-title="label"
                item-value="value"
                placeholder="All Categories"
                variant="solo-filled"
                density="compact"
                hide-details
                clearable
                style="min-width: 150px; max-width: 170px;"
            />
        </div>

        <div class="d-flex align-center gap-3">
            <span class="text-caption text-medium-emphasis">
                {{ filtered.length }} of {{ list.length }} provinces
            </span>
            <v-btn-toggle v-model="viewMode" mandatory density="compact" variant="outlined" divided>
                <v-btn value="table" icon size="small">
                    <v-icon size="16">mdi-table</v-icon>
                    <v-tooltip activator="parent" location="bottom">Table View</v-tooltip>
                </v-btn>
                <v-btn value="cards" icon size="small">
                    <v-icon size="16">mdi-view-grid</v-icon>
                    <v-tooltip activator="parent" location="bottom">Card View</v-tooltip>
                </v-btn>
            </v-btn-toggle>
        </div>
    </div>

    <!-- Cards View -->
    <v-row v-if="viewMode === 'cards'" dense>
        <v-col
            v-for="(item, i) in filtered"
            :key="i"
            cols="12"
            sm="6"
            md="4"
            lg="3"
            class="d-flex"
        >
            <v-card
                width="100%"
                elevation="0"
                rounded="lg"
                class="province-card d-flex flex-column"
                border
                @click="viewOfficeDetails(item)"
            >
                <v-card-item class="pt-4 pb-2 px-4 flex-grow-1">
                    <div class="d-flex align-start justify-space-between gap-2 mb-3">
                        <div class="text-subtitle-2 font-weight-bold line-height-tight">
                            {{ item.name }}
                        </div>
                        <div class="d-flex align-center gap-1 flex-shrink-0">
                            <v-chip
                                :color="categoryColor(item.category)"
                                size="x-small"
                                variant="tonal"
                                class="font-weight-medium"
                            >{{ item.category_label }}</v-chip>
                        </div>
                    </div>

                    <v-divider />

                    <div class="mt-2 d-flex align-center justify-space-between">
                        <div v-if="item.provincial_director" class="d-flex align-center gap-2">
                            <v-avatar :color="nameColor(item.provincial_director.name)" size="24">
                                <span class="text-caption font-weight-bold" style="font-size:0.6rem; color:white;">
                                    {{ initials(item.provincial_director.name) }}
                                </span>
                            </v-avatar>
                            <span class="text-body-2 text-truncate" style="max-width:130px;">
                                {{ item.provincial_director.name }}
                            </span>
                        </div>
                        <div v-else class="d-flex align-center gap-1">
                            <v-icon size="14" color="warning">mdi-account-alert-outline</v-icon>
                            <span class="text-caption text-warning font-weight-medium">Vacant</span>
                        </div>
                        <div class="d-flex align-center gap-1 text-medium-emphasis">
                            <v-icon size="13">mdi-account-group-outline</v-icon>
                            <span class="text-caption">{{ item.employee_member_count }}</span>
                        </div>
                    </div>
                </v-card-item>
            </v-card>
        </v-col>

        <v-col v-if="filtered.length === 0" cols="12">
            <div class="text-center py-10">
                <v-icon size="36" color="grey-lighten-2" class="mb-2">mdi-map-search-outline</v-icon>
                <div class="text-body-2 text-medium-emphasis">No provinces found matching "<strong>{{ search }}</strong>"</div>
            </div>
        </v-col>
    </v-row>

    <!-- Table View -->
    <v-card v-else elevation="0" border rounded="lg">
        <v-data-table
            :headers="tableHeaders"
            :items="filtered"
            :search="search"
            density="compact"
            hover
            hide-default-footer
            :items-per-page="-1"
            @click:row="(_, { item }) => viewOfficeDetails(item)"
            class="province-table"
        >
            <template #item.name="{ item }">
                <span class="text-body-2 font-weight-medium">{{ item.name }}</span>
            </template>

            <template #item.category_label="{ item }">
                <v-chip
                    :color="categoryColor(item.category)"
                    size="x-small"
                    variant="tonal"
                    class="font-weight-medium"
                >{{ item.category_label }}</v-chip>
            </template>

            <template #item.provincial_director="{ item }">
                <div v-if="item.provincial_director" class="d-flex align-center gap-2 py-1">
                    <v-avatar :color="nameColor(item.provincial_director.name)" size="24">
                        <span style="font-size:0.6rem; font-weight:700; color:white;">
                            {{ initials(item.provincial_director.name) }}
                        </span>
                    </v-avatar>
                    <span class="text-body-2">{{ item.provincial_director.name }}</span>
                </div>
                <div v-else class="d-flex align-center gap-1">
                    <v-icon size="14" color="warning">mdi-account-alert-outline</v-icon>
                    <span class="text-caption text-warning font-weight-medium">Vacant</span>
                </div>
            </template>

            <template #item.employee_member_count="{ item }">
                <div class="d-flex align-center gap-1 text-medium-emphasis">
                    <v-icon size="13">mdi-account-group-outline</v-icon>
                    <span class="text-body-2">{{ item.employee_member_count }}</span>
                </div>
            </template>

            <template #item.actions>
                <v-icon size="14" color="primary">mdi-arrow-right</v-icon>
            </template>

            <template #no-data>
                <div class="text-center py-8">
                    <v-icon size="32" color="grey-lighten-2" class="mb-2">mdi-map-search-outline</v-icon>
                    <div class="text-body-2 text-medium-emphasis">No provinces found</div>
                </div>
            </template>
        </v-data-table>
    </v-card>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    list: { type: Array, default: () => [] }
});

const search = ref('');
const selectedCategory = ref(null);
const viewMode = ref('table');

const categoryOptions = [
    { label: 'Micro',  value: 'micro'  },
    { label: 'Small',  value: 'small'  },
    { label: 'Medium', value: 'medium' },
    { label: 'Large',  value: 'large'  },
];

const tableHeaders = [
    { title: 'Province',          key: 'name',                 sortable: true  },
    { title: 'Category',          key: 'category_label',       sortable: true  },
    { title: 'Provincial Director', key: 'provincial_director', sortable: false },
    { title: 'Staff',             key: 'employee_member_count', sortable: true, align: 'center' },
    { title: '',                  key: 'actions',               sortable: false, align: 'end', width: '40px' },
];

// Group provinces by size category, then alphabetically within each group, so the
// CSTC clusters (CAMANAVA, PAMAMAZON, ...) sit with the other Large provinces
// instead of floating to the top in raw DB order.
const CATEGORY_ORDER = { micro: 0, small: 1, medium: 2, large: 3 };

const filtered = computed(() => {
    let data = props.list;
    const q = search.value.toLowerCase().trim();
    if (q) {
        data = data.filter(item =>
            item.name?.toLowerCase().includes(q) ||
            item.provincial_director?.name?.toLowerCase().includes(q)
        );
    }
    if (selectedCategory.value) {
        data = data.filter(item => item.category === selectedCategory.value);
    }
    return [...data].sort((a, b) => {
        const ca = CATEGORY_ORDER[a.category] ?? 99;
        const cb = CATEGORY_ORDER[b.category] ?? 99;
        if (ca !== cb) return ca - cb;
        return (a.name ?? '').localeCompare(b.name ?? '');
    });
});

const palette = ['#5C6BC0','#42A5F5','#26A69A','#66BB6A','#FFA726','#EC407A','#AB47BC','#78909C'];

const nameColor = (name = '') => {
    const idx = [...name].reduce((a, c) => a + c.charCodeAt(0), 0) % palette.length;
    return palette[idx];
};

const initials = (name = '') =>
    name.split(' ').filter(Boolean).slice(0, 2).map(n => n[0]?.toUpperCase() ?? '').join('');

const categoryColor = (cat) => ({
    micro: 'blue-grey', small: 'teal', medium: 'indigo', large: 'deep-purple'
}[cat] ?? 'grey');

const viewOfficeDetails = (item) => router.visit(`/province-directories/${item.id}`);
</script>

<style scoped>
.province-card {
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.province-card:hover {
    border-color: rgba(var(--v-theme-primary), 0.5) !important;
    box-shadow: 0 2px 10px rgba(var(--v-theme-primary), 0.08) !important;
}
.line-height-tight { line-height: 1.3 !important; }
.province-table :deep(tr) { cursor: pointer; }
</style>
