<template>
    <!-- Toolbar -->
    <div class="d-flex align-center justify-space-between mb-4 flex-wrap gap-2">
        <div class="d-flex align-center gap-3 flex-wrap">
            <v-text-field
                v-model="search"
                placeholder="Search by province or director..."
                variant="solo-filled"
                density="compact"
                hide-details
                clearable
                prepend-inner-icon="mdi-magnify"
                style="min-width: 240px; max-width: 300px;"
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
                style="min-width: 160px; max-width: 180px;"
            />
        </div>
        <span class="text-caption text-medium-emphasis">
            {{ filtered.length }} of {{ list.length }} provinces
        </span>
    </div>

    <!-- Province Cards -->
    <v-row>
        <v-col
            v-for="(item, i) in filtered"
            :key="i"
            cols="12"
            sm="6"
            md="4"
            class="d-flex"
        >
            <v-card
                width="100%"
                elevation="1"
                rounded="lg"
                class="province-card d-flex flex-column"
                @click="viewOfficeDetails(item)"
            >
                <v-card-item class="pt-5 pb-3 px-4 flex-grow-1">

                    <!-- Province name + employee count -->
                    <div class="d-flex align-start justify-space-between gap-2 mb-4">
                        <div class="text-subtitle-1 font-weight-bold line-height-tight">
                            {{ item.name + ` (${item.category_label})` }}
                        </div>
                        <div class="d-flex align-center gap-1 flex-shrink-0 text-medium-emphasis">
                            <v-icon size="14">mdi-account-group-outline</v-icon>
                            <span class="text-caption">{{ item.employee_member_count }}</span>
                        </div>
                    </div>

                    <v-divider></v-divider>

                    <!-- Director info -->
                    <div class="mt-3">
                        <div class="text-caption text-medium-emphasis mb-1 font-weight-medium" style="letter-spacing:0.04em; text-transform:uppercase; font-size:0.6rem;">
                            Provincial Director
                        </div>

                        <div v-if="item.provincial_director" class="d-flex align-center gap-2">
                            <v-avatar size="28">
                                <v-img :src="defPic" cover></v-img>
                            </v-avatar>
                            <span class="text-body-2 font-weight-medium text-truncate">
                                {{ item.provincial_director.name }}
                            </span>
                        </div>

                        <div v-else class="d-flex align-center gap-2">
                            <v-icon size="16" color="warning">mdi-account-alert-outline</v-icon>
                            <span class="text-body-2 text-warning font-weight-medium">Vacant</span>
                        </div>
                    </div>

                </v-card-item>

                <!-- View footer -->
                <div class="view-footer px-4 py-2 d-flex align-center justify-space-between">
                    <span class="text-caption text-primary font-weight-medium">View Directory</span>
                    <v-icon size="14" color="primary">mdi-arrow-right</v-icon>
                </div>
            </v-card>
        </v-col>

        <!-- Empty state -->
        <v-col v-if="filtered.length === 0" cols="12">
            <div class="text-center py-12">
                <v-icon size="40" color="grey-lighten-2" class="mb-3">mdi-map-search-outline</v-icon>
                <div class="text-body-2 text-medium-emphasis">
                    No provinces found matching "<strong>{{ search }}</strong>"
                </div>
            </div>
        </v-col>
    </v-row>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const defPic = 'https://scontent.fcgy1-3.fna.fbcdn.net/v/t39.30808-1/569409499_2926389544213362_5572906559510250325_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=107&ccb=1-7&_nc_sid=1d2534&_nc_eui2=AeHxMS2Jaxqdlz7XjktrvQNCkuMFs6-OJrWS4wWzr44mtaR_gFGX3XynJKcVctLnDQznMva1uf7y4DJ9zvqkENur&_nc_ohc=t1KgQyv8YI4Q7kNvwFZDK9s&_nc_oc=AdovjHXEhGImiLI-b4UzqvAlKfytDYJV4eb0rG9Z9EgUyAyg_EF3UGX2mFLadgn20tFa5hK9DE54diCLrTUm3qlo&_nc_zt=24&_nc_ht=scontent.fcgy1-3.fna&_nc_gid=AYiNPwYYm5mm809vxgPhoA&_nc_ss=782a8&oh=00_Af5BeBLxqbptd619Z7yoL_PoCiaDpG1OQR9LWJj9XiJMMw&oe=6A13B822';

const props = defineProps({
    list: {
        type: Array,
        default: () => []
    }
});

const search = ref('');
const selectedCategory = ref(null)

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
    return data;
});

const categoryOptions = [
    { label: 'Micro',  value: 'mic'  },
    { label: 'Small',  value: 's'  },
    { label: 'Medium', value: 'm' },
    { label: 'Large',  value: 'l'  },
];

const viewOfficeDetails = (item) => {
    router.visit(`/province-directories/${item.id}`)
};
</script>

<style scoped>
.province-card {
    cursor: pointer;
    transition: box-shadow 0.2s, transform 0.2s;
    border: 1px solid transparent;
}
.province-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.10) !important;
    transform: translateY(-2px);
    border-color: rgba(var(--v-theme-primary), 0.2);
}
.line-height-tight {
    line-height: 1.3 !important;
}
.view-footer {
    border-top: 1px solid rgba(0,0,0,0.06);
    opacity: 0;
    transition: opacity 0.2s;
}
.province-card:hover .view-footer {
    opacity: 1;
}
</style>
