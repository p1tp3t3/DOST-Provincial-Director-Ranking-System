<template>
    <!-- Toolbar -->
    <div class="d-flex align-center justify-space-between mb-4 flex-wrap gap-2">
        <v-text-field
            v-model="search"
            placeholder="Search by name, province, or ID..."
            variant="solo-filled"
            density="compact"
            hide-details
            clearable
            prepend-inner-icon="mdi-magnify"
            style="max-width: 300px;"
        />
        <span class="text-caption text-medium-emphasis">
            {{ filteredDirectors.length }} of {{ list.data.length }} directors
        </span>
    </div>

    <!-- Director Cards -->
    <v-row>
        <v-col
            v-for="director in filteredDirectors"
            :key="director.id"
            cols="12"
            sm="6"
            md="4"
            lg="3"
            class="d-flex"
        >
            <v-card
                width="100%"
                elevation="1"
                rounded="lg"
                class="director-card d-flex flex-column"
                @click="router.visit(`/profile/${director.id}`)"
            >
                <!-- Avatar area -->
                <div class="d-flex flex-column align-center pt-6 pb-3 px-4">
                    <v-avatar size="80" class="mb-3">
                        <v-img :src="defPic" :alt="director.name" cover></v-img>
                    </v-avatar>

                    <div class="text-center">
                        <div class="text-subtitle-2 font-weight-bold text-wrap line-height-tight mb-1">
                            {{ director.name }}
                        </div>
                        <div class="text-caption text-medium-emphasis">
                            {{ director.employee_id }}
                        </div>
                    </div>
                </div>

                <v-divider class="mx-4"></v-divider>

                <!-- Info rows -->
                <div class="px-4 py-3 d-flex flex-column gap-2 flex-grow-1">
                    <div class="d-flex align-center gap-2">
                        <v-icon size="14" color="medium-emphasis">mdi-map-marker-outline</v-icon>
                        <span class="text-caption text-medium-emphasis">{{ director.province }}</span>
                    </div>
                    <div class="d-flex align-center gap-2">
                        <v-icon size="14" color="medium-emphasis">mdi-calendar-check-outline</v-icon>
                        <span class="text-caption text-medium-emphasis">{{ director.length_of_service }} yrs of service</span>
                    </div>
                </div>

                <!-- View profile footer -->
                <div class="view-footer px-4 py-2 d-flex align-center justify-space-between">
                    <span class="text-caption text-primary font-weight-medium">View Profile</span>
                    <v-icon size="14" color="primary">mdi-arrow-right</v-icon>
                </div>
            </v-card>
        </v-col>

        <!-- Empty State -->
        <v-col v-if="filteredDirectors.length === 0" cols="12">
            <div class="text-center py-12">
                <v-icon size="40" color="grey-lighten-2" class="mb-3">mdi-account-search-outline</v-icon>
                <div class="text-body-2 text-medium-emphasis">
                    No directors found matching "<strong>{{ search }}</strong>"
                </div>
            </div>
        </v-col>
    </v-row>

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
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const defPic = 'https://scontent.fcgy1-3.fna.fbcdn.net/v/t39.30808-1/569409499_2926389544213362_5572906559510250325_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=107&ccb=1-7&_nc_sid=1d2534&_nc_eui2=AeHxMS2Jaxqdlz7XjktrvQNCkuMFs6-OJrWS4wWzr44mtaR_gFGX3XynJKcVctLnDQznMva1uf7y4DJ9zvqkENur&_nc_ohc=t1KgQyv8YI4Q7kNvwFZDK9s&_nc_oc=AdovjHXEhGImiLI-b4UzqvAlKfytDYJV4eb0rG9Z9EgUyAyg_EF3UGX2mFLadgn20tFa5hK9DE54diCLrTUm3qlo&_nc_zt=24&_nc_ht=scontent.fcgy1-3.fna&_nc_gid=AYiNPwYYm5mm809vxgPhoA&_nc_ss=782a8&oh=00_Af5BeBLxqbptd619Z7yoL_PoCiaDpG1OQR9LWJj9XiJMMw&oe=6A13B822';

const props = defineProps({
    list: {
        type: Object,
        required: true
    }
});

const search = ref('');

const filteredDirectors = computed(() => {
    const query = search.value.toLowerCase().trim();
    if (!query) return props.list.data;
    return props.list.data.filter(d =>
        d.name?.toLowerCase().includes(query)        ||
        d.province?.toLowerCase().includes(query)   ||
        d.employee_id?.toLowerCase().includes(query)
    );
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
</script>

<style scoped>
.director-card {
    cursor: pointer;
    transition: box-shadow 0.2s, transform 0.2s;
    border: 1px solid transparent;
}
.director-card:hover {
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
.director-card:hover .view-footer {
    opacity: 1;
}
</style>
