<template>
    <v-card class="elevation-1 border-0 rounded-md h-100">
        <v-card-item class="pa-5">
            <div class="text-caption text-medium-emphasis font-weight-medium mb-4" style="letter-spacing:0.05em; text-transform:uppercase; font-size:0.6rem;">
                Provincial Director
            </div>
            <div class="d-flex align-center gap-4">
                <v-avatar size="56">
                    <v-img :src="defPic" cover></v-img>
                </v-avatar>
                <div>
                    <div class="text-subtitle-2 font-weight-bold">{{ director.name }}</div>
                    <div class="text-caption text-medium-emphasis mb-2">{{ director.id }}</div>
                    <v-chip size="x-small" color="indigo" variant="tonal">Provincial Director</v-chip>
                </div>
            </div>
            <v-divider class="my-4"></v-divider>
            <div class="d-flex flex-column gap-2">
                <div class="d-flex align-center gap-2">
                    <v-icon size="14" color="medium-emphasis">mdi-map-marker-outline</v-icon>
                    <span class="text-caption text-medium-emphasis">{{ director.province }}</span>
                </div>
                <div class="d-flex align-center gap-2">
                    <v-icon size="14" color="medium-emphasis">mdi-calendar-check-outline</v-icon>
                    <span class="text-caption text-medium-emphasis">{{ director.length_of_service }} yrs of service</span>
                </div>
                <div class="d-flex align-center justify-space-between mt-2">
                    <span class="text-caption text-medium-emphasis">Overall KPI Score</span>
                    <span
                        class="text-body-2 font-weight-bold"
                        :class="director.kpi_score >= 85 ? 'text-success' : director.kpi_score >= 70 ? 'text-warning' : 'text-error'"
                    >{{ director.kpi_score }}</span>
                </div>
                <v-progress-linear
                    :model-value="director.kpi_score"
                    :color="director.kpi_score >= 85 ? 'success' : director.kpi_score >= 70 ? 'warning' : 'error'"
                    height="6"
                    rounded
                    bg-color="grey-lighten-3"
                ></v-progress-linear>
            </div>
        </v-card-item>
    </v-card>
</template>

<script setup>
const defPic = 'https://scontent.fcgy1-3.fna.fbcdn.net/v/t39.30808-1/569409499_2926389544213362_5572906559510250325_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=107&ccb=1-7&_nc_sid=1d2534&_nc_eui2=AeHxMS2Jaxqdlz7XjktrvQNCkuMFs6-OJrWS4wWzr44mtaR_gFGX3XynJKcVctLnDQznMva1uf7y4DJ9zvqkENur&_nc_ohc=t1KgQyv8YI4Q7kNvwFZDK9s&_nc_oc=AdovjHXEhGImiLI-b4UzqvAlKfytDYJV4eb0rG9Z9EgUyAyg_EF3UGX2mFLadgn20tFa5hK9DE54diCLrTUm3qlo&_nc_zt=24&_nc_ht=scontent.fcgy1-3.fna&_nc_gid=AYiNPwYYm5mm809vxgPhoA&_nc_ss=782a8&oh=00_Af5BeBLxqbptd619Z7yoL_PoCiaDpG1OQR9LWJj9XiJMMw&oe=6A13B822';

defineProps({
    director: { type: Object, required: true },
});
</script>
