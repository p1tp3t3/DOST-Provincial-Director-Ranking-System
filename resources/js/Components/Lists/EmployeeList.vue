<template>
    <!-- Filter Controls Group -->
    <div class="d-flex align-center ga-3 mb-4" style="width: 100%;">
        
        <!-- 💡 Dropdown hidden automatically if the logged-in user is already restricted to a single province -->
        <v-select
            v-if="!auth?.province"
            v-model="selectedProvince"
            :items="provinceOptions"
            placeholder="Filter by Province"
            variant="solo-filled"
            density="compact"
            hide-details
            max-width="200"
            clearable
        />

        <!-- Main Input Search Field -->
        <v-text-field
            v-model="search"
            placeholder="Search by name..."
            variant="solo-filled"
            density="compact"
            hide-details
            max-width="250"
            clearable
            prepend-inner-icon="mdi-magnify"
        />
    </div>
    
    <!-- Vuetify Data Table -->
    <v-data-table
        :headers="headers"
        :items="filteredList"
        :search="search"
        :items-per-page="10"
        hover
        class="custom-styled-table border"
    >
        <!-- Slot to handle the custom auto-incrementing index row -->
        <template #item.index="{ index }">
            <span class="text-grey-darken-1 font-weight-medium">
                {{ index + 1 }}
            </span>
        </template>

        <template #item.actions="{ item }">
            <div class="d-flex ga-2 justify-start">
                <v-btn
                    variant="flat"
                    @click="viewUser(item)"
                    class="bg-blue-500 text-white px-2"
                    size="small"
                >
                    <!-- 💡 Fix: Capitalized component tags to match your script setup imports -->
                    <RiEye2Fill class="w-4 h-4 me-1" />
                    <span>View</span>
                </v-btn>
                <v-btn
                    variant="flat"
                    class="bg-yellow-500 text-white px-2"
                    size="small"
                >
                    <RiPenNibFill class="w-4 h-4 me-1" />
                    <span>Edit</span>
                </v-btn>
            </div>
        </template>
        
        <template #no-data>
            <v-empty-state
                icon="mdi-magnify"
                text="Try adjusting your search terms or filters. Sometimes less specific terms or broader queries can help you find what you're looking for."
                title="We couldn't find a match."
            ></v-empty-state>
        </template>
    </v-data-table>
</template>

<script setup>
import { ref, computed } from 'vue'
import { getAuth } from '@/helper-functions'
import { RiEye2Fill, RiPenNibFill } from '@remixicon/vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    list: {
        type: Array,
        default: () => [] 
    }
})

const search = ref('')
const selectedProvince = ref(null)

// 💡 1. FIX: Declare and unpack your helper function first before writing dependencies
const auth = getAuth()

// 💡 2. FIX: Wrap headers in a computed property so it can reactively update when auth mounts
const headers = computed(() => {
    // Determine if the province column should show (e.g. if the user is a super/sub admin with NO local province scope assigned)
    const showProvinceColumn = !auth?.province;

    return [
        { title: '#', key: 'index', align: 'start', sortable: false }, 
        { title: 'ID', key: 'id', align: 'start', sortable: true },
        { title: 'Name', key: 'name', sortable: true },
        { title: 'Email', key: 'email', sortable: true },
        
        ...(showProvinceColumn ? [{ title: 'Province', key: 'province', sortable: true }] : []),
        
        { title: 'Position', key: 'position', sortable: true },
        { title: 'Actions', key: 'actions', sortable: false, align: 'start' }
    ]
})

const provinceOptions = computed(() => {
    const listData = props.list || []
    return [...new Set(listData.map(e => e.province))].filter(Boolean)
})

// Computed Filter Logic
const filteredList = computed(() => {
    let data = props.list || []

    if (selectedProvince.value) {
        data = data.filter(item => item.province === selectedProvince.value)
    }

    return data
})

// Action Methods
const viewUser = (user) => {
  router.visit(`/profile/${user.id}`)
}
</script>

<style scoped>
/* Vuetify 3 Layout Overrides */
:deep(.custom-styled-table .v-data-table__header) {
    background-color: #1e293b !important; 
}

:deep(.custom-styled-table .v-data-table-col-header__contents) {
    color: #ffffff !important;           
    font-weight: 700 !important;          
    font-size: 0.8rem !important;
    text-transform: uppercase !important; 
    letter-spacing: 0.5px !important;
}

:deep(.custom-styled-table .v-data-table-col-header__icon) {
    color: #ffffff !important;           
}

:deep(.custom-styled-table .v-data-table__td) {
    border-bottom: 1px solid #000000 !important; 
}

:deep(.custom-styled-table .v-data-table__td:not(:last-child)) {
    border-right: 1px solid #000000 !important;
}
</style>
