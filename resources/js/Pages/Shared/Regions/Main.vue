<template>
    <Head title="Regions" />
    <div class="grid gap-4 w-full">

        <!-- Header -->
        <v-card class="border-0 elevation-1 bg-white rounded-md">
            <div class="d-flex align-center justify-space-between py-3 px-5 flex-wrap gap-2">
                <div>
                    <div class="text-subtitle-1 font-weight-bold text-grey-darken-3">Regions</div>
                    <div class="text-caption text-grey">
                        {{ scoped ? 'Provinces in your region' : 'All regions and their provinces' }}
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <v-chip color="indigo" variant="tonal" label size="small">
                        <v-icon start size="14">mdi-earth</v-icon>
                        {{ filteredRegions.length }} region{{ filteredRegions.length !== 1 ? 's' : '' }}
                    </v-chip>
                    <v-chip color="teal" variant="tonal" label size="small">
                        <v-icon start size="14">mdi-map-marker-outline</v-icon>
                        {{ totalProvinces }} province{{ totalProvinces !== 1 ? 's' : '' }}
                    </v-chip>
                </div>
            </div>
        </v-card>

        <!-- Filters row -->
        <div class="d-flex align-center gap-3 flex-wrap">
            <!-- Island tabs -->
            <div v-if="!scoped" class="d-flex gap-2 flex-wrap">
                <v-btn
                    v-for="tab in islandTabs"
                    :key="tab.value"
                    :variant="islandFilter === tab.value ? 'flat' : 'tonal'"
                    :color="islandFilter === tab.value ? 'indigo' : 'grey'"
                    size="small"
                    rounded="lg"
                    @click="islandFilter = tab.value"
                >
                    {{ tab.label }}
                    <span class="ml-1 text-caption">({{ tab.count }})</span>
                </v-btn>
            </div>

            <v-spacer v-if="!scoped" />

            <!-- Region dropdown -->
            <v-select
                v-model="regionFilter"
                :items="regionOptions"
                item-title="label"
                item-value="value"
                placeholder="All Regions"
                variant="solo-filled"
                density="compact"
                hide-details
                clearable
                style="min-width:200px; max-width:260px;"
            />

            <!-- Category dropdown -->
            <v-select
                v-model="categoryFilter"
                :items="categoryOptions"
                item-title="label"
                item-value="value"
                placeholder="All Categories"
                variant="solo-filled"
                density="compact"
                hide-details
                clearable
                style="min-width:150px; max-width:170px;"
            />
        </div>

        <!-- Directory list -->
        <v-card class="elevation-1 rounded-md overflow-hidden">
            <v-table>
                <tbody>
                    <template v-for="region in filteredRegions.filter(r => visibleProvinces(r).length > 0)" :key="region.id">

                        <!-- Region header row -->
                        <tr class="region-row">
                            <td colspan="5" class="pa-0">
                                <div class="region-header d-flex align-center gap-3 px-4 py-2 flex-wrap">
                                    <v-icon size="16" color="white">mdi-earth</v-icon>
                                    <span class="region-label">{{ region.name }}</span>
                                    <v-chip
                                        :color="islandChipColor(region.island_under)"
                                        variant="flat"
                                        size="x-small"
                                        label
                                        class="text-capitalize"
                                        style="opacity:.85;"
                                    >{{ region.island_under }}</v-chip>

                                    <!-- Regional Director -->
                                    <div class="d-flex align-center gap-2 ml-4">
                                        <v-avatar
                                            v-if="region.regional_director"
                                            :color="nameColor(region.regional_director)"
                                            size="22"
                                        >
                                            <span style="font-size:0.58rem;font-weight:700;color:#fff;">
                                                {{ initials(region.regional_director) }}
                                            </span>
                                        </v-avatar>
                                        <span v-if="region.regional_director" class="region-director-name">
                                            {{ region.regional_director }}
                                        </span>
                                        <span v-else class="region-vacant">
                                            <v-icon size="13">mdi-account-alert-outline</v-icon>
                                            Vacant
                                        </span>
                                    </div>

                                    <span class="region-count ml-auto">
                                        {{ visibleProvinces(region).length }} province{{ visibleProvinces(region).length !== 1 ? 's' : '' }}
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <!-- Province rows -->
                        <tr
                            v-for="prov in visibleProvinces(region)"
                            :key="prov.id"
                            class="province-row"
                            @click="goToProvince(prov.id)"
                        >
                            <td style="width:35%">
                                <div class="d-flex align-center gap-2 py-1">
                                    <v-icon size="15" color="blue-grey-lighten-1">mdi-map-marker-outline</v-icon>
                                    <span class="text-body-2 font-weight-medium text-grey-darken-3">{{ prov.name }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="cell-label">Category</div>
                                <v-chip
                                    :color="categoryColor(prov.category)"
                                    size="x-small"
                                    label
                                    variant="tonal"
                                    class="text-capitalize font-weight-medium"
                                >{{ prov.category }}</v-chip>
                            </td>
                            <td>
                                <div class="cell-label">Provincial Director</div>
                                <div v-if="prov.provincial_director" class="d-flex align-center gap-2">
                                    <v-avatar :color="nameColor(prov.provincial_director)" size="24">
                                        <span style="font-size:0.6rem;font-weight:700;color:#fff;">
                                            {{ initials(prov.provincial_director) }}
                                        </span>
                                    </v-avatar>
                                    <span class="text-body-2">{{ prov.provincial_director }}</span>
                                </div>
                                <div v-else class="d-flex align-center gap-1">
                                    <v-icon size="14" color="warning">mdi-account-alert-outline</v-icon>
                                    <span class="text-caption text-warning font-weight-medium">Vacant</span>
                                </div>
                            </td>
                            <td>
                                <div class="cell-label">Staff</div>
                                <div class="d-flex align-center gap-1">
                                    <v-icon size="13" color="blue-grey-lighten-1">mdi-account-group-outline</v-icon>
                                    <span class="text-body-2 text-grey-darken-2">{{ prov.staff_count }}</span>
                                </div>
                            </td>
                            <td class="text-right" style="width:40px;">
                                <v-icon size="14" color="primary">mdi-arrow-right</v-icon>
                            </td>
                        </tr>

                    </template>

                    <!-- Overall empty state -->
                    <tr v-if="filteredRegions.filter(r => visibleProvinces(r).length > 0).length === 0">
                        <td colspan="5" class="text-center py-10">
                            <v-icon size="36" color="grey-lighten-2" class="mb-2 d-block mx-auto">mdi-earth-off</v-icon>
                            <span class="text-body-2 text-medium-emphasis">No regions found.</span>
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>

    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    regions: { type: Array,   default: () => [] },
    scoped:  { type: Boolean, default: false },
});

const islandFilter   = ref('all');
const categoryFilter = ref(null);
const regionFilter   = ref(null);

const categoryOptions = [
    { label: 'Micro',  value: 'micro'  },
    { label: 'Small',  value: 'small'  },
    { label: 'Medium', value: 'medium' },
    { label: 'Large',  value: 'large'  },
    { label: 'CSTC',   value: 'cstc'   },
];

const regionOptions = computed(() =>
    props.regions.map(r => ({ label: r.name, value: r.id }))
);

const islandTabs = computed(() => {
    const counts = { all: props.regions.length, luzon: 0, visayas: 0, mindanao: 0 };
    for (const r of props.regions) {
        if (r.island_under in counts) counts[r.island_under]++;
    }
    return [
        { value: 'all',      label: 'All Islands', count: counts.all      },
        { value: 'luzon',    label: 'Luzon',        count: counts.luzon    },
        { value: 'visayas',  label: 'Visayas',      count: counts.visayas  },
        { value: 'mindanao', label: 'Mindanao',     count: counts.mindanao },
    ];
});

const filteredRegions = computed(() => {
    let list = islandFilter.value === 'all'
        ? props.regions
        : props.regions.filter(r => r.island_under === islandFilter.value);

    if (regionFilter.value !== null) {
        list = list.filter(r => r.id === regionFilter.value);
    }
    return list;
});

const visibleProvinces = (region) => {
    let list = region.provinces;
    if (categoryFilter.value) {
        list = list.filter(p => p.category === categoryFilter.value);
    }
    return list;
};

const totalProvinces = computed(() =>
    filteredRegions.value.reduce((sum, r) => sum + visibleProvinces(r).length, 0)
);

const goToProvince = (encryptedId) => router.visit(`/province-directories/${encryptedId}`);

const palette = ['#5C6BC0','#42A5F5','#26A69A','#66BB6A','#FFA726','#EC407A','#AB47BC','#78909C'];
const nameColor = (name = '') => {
    const idx = [...name].reduce((a, c) => a + c.charCodeAt(0), 0) % palette.length;
    return palette[idx];
};
const initials = (name = '') =>
    name.split(' ').filter(Boolean).slice(0, 2).map(n => n[0]?.toUpperCase() ?? '').join('');

const islandChipColor = (island) => ({
    luzon:    'blue-darken-3',
    visayas:  'green-darken-2',
    mindanao: 'orange-darken-2',
}[island] ?? 'grey');

const categoryColor = (cat) => ({
    micro:  'blue-grey',
    small:  'teal',
    medium: 'indigo',
    large:  'deep-purple',
    cstc:   'orange',
}[cat] ?? 'grey');
</script>

<style scoped>
.region-header {
    background: linear-gradient(135deg, #1e3a6e 0%, #0f2044 100%);
    min-height: 40px;
}
.region-label {
    font-size: 0.82rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.02em;
}
.region-director-name {
    font-size: 0.78rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.85);
}
.region-vacant {
    font-size: 0.72rem;
    color: #fbbf24;
    display: flex;
    align-items: center;
    gap: 3px;
}
.region-count {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.55);
}
.province-row {
    cursor: pointer;
    transition: background 0.12s;
}
.province-row:hover {
    background: #f0f4ff !important;
}
.cell-label {
    font-size: 0.67rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #94a3b8;
    margin-bottom: 2px;
}
</style>
