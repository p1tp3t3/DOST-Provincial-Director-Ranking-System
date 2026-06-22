<template>
    <Head title="Dashboard" />
        <div class="grid gap-4 w-full">

            <!-- Filter Toolbar -->
            <v-card class="border-0 elevation-1 bg-white rounded-md">
                <div class="d-flex align-center justify-space-between py-2 px-5 ga-4 flex-wrap">
                    <div>
                        <span class="text-caption text-grey">Filter Statistics by Period</span>
                    </div>
                    <div class="d-flex align-center ga-3 flex-wrap flex-sm-nowrap" style="max-width: 650px; width: 100%; justify-content: flex-end;">
                        <v-select
                            v-model="selectedYear"
                            :items="yearOptions"
                            placeholder="Year"
                            variant="solo-filled"
                            density="compact"
                            hide-details
                            max-width="120"
                            clearable
                            @update:model-value="applyFilters"
                        />
                        <v-select
                            v-model="selectedMonth"
                            :items="monthOptions"
                            item-title="name"
                            item-value="id"
                            placeholder="Month"
                            variant="solo-filled"
                            density="compact"
                            hide-details
                            max-width="150"
                            clearable
                            @update:model-value="applyFilters"
                        />
                    </div>
                </div>
            </v-card>

            <!-- Primary Stat Cards -->
            <v-row>
                <v-col cols="12" sm="4">
                    <QuantityCard title="Total Provinces" :quantity="total_provinces" :icon="RiMapPin2Line" color="indigo" />
                </v-col>
                <v-col cols="12" sm="4">
                    <QuantityCard title="Total Directors" :quantity="total_directors" :icon="RiUserStarLine" color="indigo" />
                </v-col>
                <v-col cols="12" sm="4">
                    <QuantityCard title="Total Employees" :quantity="total_employees" :icon="RiGroupLine" color="indigo" />
                </v-col>
            </v-row>

            <!-- KPI Summary Cards -->
            <v-row>
                <v-col cols="12" sm="4">
                    <QuantityCard title="KPI Outcomes Met (%)" :quantity="kpiStats.met_rate" :icon="RiCheckDoubleLine" color="success" />
                </v-col>
                <v-col cols="12" sm="4">
                    <QuantityCard title="Avg. Director Score" :quantity="kpiStats.avg_score" :icon="RiMedalLine" color="success" />
                </v-col>
                <v-col cols="12" sm="4">
                    <QuantityCard title="Below-Target Indicators" :quantity="kpiStats.below_count" :icon="RiAlertLine" color="danger" />
                </v-col>
            </v-row>

            <!-- Charts Row -->
            <v-row dense>
                <v-col cols="12" md="8" class="d-flex">
                    <LineGraphCard
                        class="flex-grow-1"
                        title="Monthly KPI Performance Trend"
                        :labelX="trendChart.labels"
                        :data="trendChart.series"
                    />
                </v-col>
                <v-col cols="12" md="4" class="d-flex">
                    <PieChartCard
                        class="flex-grow-1"
                        title="Director Performance Distribution"
                        :series="performancePie.series"
                        :labels="performancePie.labels"
                        :colors="performancePie.colors"
                    />
                </v-col>
            </v-row>

            <!-- Director Rankings Table -->
            <v-card class="elevation-1 border-0 rounded-md">
                <div class="d-flex align-center justify-space-between px-5 pt-4 pb-3">
                    <div>
                        <div class="text-subtitle-2 font-weight-bold">Director Performance Rankings</div>
                        <div class="text-caption text-medium-emphasis">Ranked by overall KPI score for the selected period</div>
                    </div>
                    <v-chip size="small" variant="tonal" color="primary">{{ mockRankings.length }} Directors</v-chip>
                </div>
                <v-divider></v-divider>
                <v-table density="comfortable" hover>
                    <thead>
                        <tr>
                            <th class="text-caption text-medium-emphasis" width="60">Rank</th>
                            <th class="text-caption text-medium-emphasis">Director</th>
                            <th class="text-caption text-medium-emphasis">Province</th>
                            <th class="text-caption text-medium-emphasis text-center" width="120">KPI Score</th>
                            <th class="text-caption text-medium-emphasis text-center" width="110">Outcomes Met</th>
                            <th class="text-caption text-medium-emphasis text-center" width="100">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(director, i) in mockRankings" :key="i">
                            <td>
                                <div class="d-flex align-center justify-center">
                                    <v-icon v-if="i === 0" color="amber-darken-2" size="18">mdi-trophy</v-icon>
                                    <v-icon v-else-if="i === 1" color="blue-grey-lighten-1" size="18">mdi-medal</v-icon>
                                    <v-icon v-else-if="i === 2" color="orange-darken-2" size="18">mdi-medal</v-icon>
                                    <span v-else class="text-body-2 text-medium-emphasis">{{ i + 1 }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-center gap-3 py-1">
                                    <v-avatar size="32">
                                        <v-img :src="defPic" cover></v-img>
                                    </v-avatar>
                                    <div>
                                        <div class="text-body-2 font-weight-medium">{{ director.name }}</div>
                                        <div class="text-caption text-medium-emphasis">{{ director.id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-body-2">{{ director.province }}</td>
                            <td class="text-center">
                                <div class="d-flex align-center justify-center gap-2">
                                    <v-progress-linear
                                        :model-value="director.score"
                                        :color="scoreColor(director.score)"
                                        height="6"
                                        rounded
                                        bg-color="grey-lighten-3"
                                        style="max-width: 60px"
                                    ></v-progress-linear>
                                    <span class="text-body-2 font-weight-medium">{{ director.score }}</span>
                                </div>
                            </td>
                            <td class="text-body-2 text-center">
                                {{ director.met }} / {{ director.total }}
                            </td>
                            <td class="text-center">
                                <span
                                    class="text-caption font-weight-medium"
                                    :class="director.score >= 85 ? 'text-success' : director.score >= 70 ? 'text-warning' : 'text-error'"
                                >
                                    {{ director.score >= 85 ? 'Excellent' : director.score >= 70 ? 'Good' : 'Needs Improvement' }}
                                </span>
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
import LineGraphCard from '@/Components/Cards/LineGraphCard.vue';
import QuantityCard from '@/Components/Cards/QuantityCard.vue';
import PieChartCard from '@/Components/Cards/PieChartCard.vue';
import {
    RiMapPin2Line,
    RiUserStarLine,
    RiGroupLine,
    RiCheckDoubleLine,
    RiMedalLine,
    RiAlertLine,
} from '@remixicon/vue';

const props = defineProps({
    total_provinces: { type: Number, default: 0 },
    total_users:     { type: Number, default: 0 },
    total_directors: { type: Number, default: 0 },
    total_employees: { type: Number, default: 0 },
    filters:         { type: Object, default: () => ({}) }
});

const defPic = 'https://scontent.fcgy1-3.fna.fbcdn.net/v/t39.30808-1/569409499_2926389544213362_5572906559510250325_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=107&ccb=1-7&_nc_sid=1d2534&_nc_eui2=AeHxMS2Jaxqdlz7XjktrvQNCkuMFs6-OJrWS4wWzr44mtaR_gFGX3XynJKcVctLnDQznMva1uf7y4DJ9zvqkENur&_nc_ohc=t1KgQyv8YI4Q7kNvwFZDK9s&_nc_oc=AdovjHXEhGImiLI-b4UzqvAlKfytDYJV4eb0rG9Z9EgUyAyg_EF3UGX2mFLadgn20tFa5hK9DE54diCLrTUm3qlo&_nc_zt=24&_nc_ht=scontent.fcgy1-3.fna&_nc_gid=AYiNPwYYm5mm809vxgPhoA&_nc_ss=782a8&oh=00_Af5BeBLxqbptd619Z7yoL_PoCiaDpG1OQR9LWJj9XiJMMw&oe=6A13B822';

// Filter state
const selectedYear  = ref(props.filters?.year  || null);
const selectedMonth = ref(props.filters?.month || null);

const currentYear = new Date().getFullYear();
const yearOptions = ref(Array.from({ length: currentYear - 2020 + 1 }, (_, i) => currentYear - i));
const monthOptions = ref([
    { id: 1,  name: 'January'   }, { id: 2,  name: 'February'  }, { id: 3,  name: 'March'     },
    { id: 4,  name: 'April'     }, { id: 5,  name: 'May'       }, { id: 6,  name: 'June'      },
    { id: 7,  name: 'July'      }, { id: 8,  name: 'August'    }, { id: 9,  name: 'September' },
    { id: 10, name: 'October'   }, { id: 11, name: 'November'  }, { id: 12, name: 'December'  },
]);

const applyFilters = () => {
    router.get('/dashboard', {
        year:  selectedYear.value,
        month: selectedMonth.value,
    }, { preserveState: true, replace: true });
};

// KPI summary stats (mock - replace with backend props when ready)
const kpiStats = ref({
    met_rate:    87,
    avg_score:   91,
    below_count: 14,
});

// Line chart data
const trendChart = ref({
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    series: [
        { name: 'KPI Score',         data: [78, 81, 80, 85, 83, 88, 90, 87, 92, 89, 91, 94] },
        { name: 'Target',            data: [80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80] },
    ]
});

// Director rankings (mock - replace with backend data when ready)
const mockRankings = ref([
    { name: 'Maria Cristina B. Dela Cruz', id: 'DOST-XI-2019-0042', province: 'Davao del Norte',   score: 97, met: 11, total: 12 },
    { name: 'Jose Ramon T. Villanueva',    id: 'DOST-XI-2017-0011', province: 'Davao del Sur',     score: 94, met: 10, total: 12 },
    { name: 'Luz Milagros C. Santos',      id: 'DOST-XI-2020-0078', province: 'Davao Occidental',  score: 91, met: 10, total: 12 },
    { name: 'Eduardo P. Reyes',            id: 'DOST-XI-2016-0005', province: 'Davao Oriental',    score: 88, met: 9,  total: 12 },
    { name: 'Fatima Grace M. Alonto',      id: 'DOST-XI-2021-0093', province: 'Davao de Oro',      score: 85, met: 9,  total: 12 },
    { name: 'Ricardo B. Montoya',          id: 'DOST-XI-2018-0034', province: 'Davao City',        score: 79, met: 8,  total: 12 },
    { name: 'Annalyn S. Corpuz',           id: 'DOST-XI-2022-0107', province: 'Compostela Valley', score: 74, met: 7,  total: 12 },
    { name: 'Hernando D. Flores',          id: 'DOST-XI-2015-0002', province: 'South Cotabato',    score: 68, met: 6,  total: 12 },
]);

const scoreColor = (score) => {
    if (score >= 85) return 'success';
    if (score >= 70) return 'warning';
    return 'error';
};

// Pie chart - derived from rankings so it always stays in sync
const performancePie = computed(() => {
    const excellent        = mockRankings.value.filter(d => d.score >= 85).length;
    const good             = mockRankings.value.filter(d => d.score >= 70 && d.score < 85).length;
    const needsImprovement = mockRankings.value.filter(d => d.score < 70).length;
    return {
        series: [excellent, good, needsImprovement],
        labels: ['Excellent (≥85)', 'Good (70–84)', 'Needs Improvement (<70)'],
        colors: ['#4CAF50', '#FB8C00', '#F44336'],
    };
});
</script>
