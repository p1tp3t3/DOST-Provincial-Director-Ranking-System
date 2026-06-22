<template>
    <Head title="Dashboard" />
        <div class="grid gap-4 w-full">

            <!-- Director Info + KPI Stats -->
            <v-row>
                <v-col cols="12" md="4">
                    <DirectorInfoCard :director="director" />
                </v-col>
                <v-col cols="12" md="8">
                    <v-row>
                        <v-col cols="12" sm="6">
                            <QuantityCard title="Total Users" :quantity="1" :icon="RiGroup2Line" color="indigo" />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <QuantityCard title="Active Users" :quantity="2" :icon="RiUserFollowFill" color="success" />
                        </v-col>
                    </v-row>
                </v-col>
            </v-row>

            <!-- Charts Row -->
            <v-row dense>
                <v-col cols="12" md="15" class="d-flex">
                    <LineGraphCard
                        class="flex-grow-1"
                        title="Monthly User Logins"
                        :labelX="trendChart.labels"
                        :data="trendChart.series"
                    />
                </v-col>
            </v-row>
            <!-- Employees -->
            <v-card class="elevation-1 border-0 rounded-md">
                <div class="d-flex align-center justify-space-between px-5 pt-4 pb-3">
                    <div>
                        <div class="text-subtitle-2 font-weight-bold">Employees</div>
                        <div class="text-caption text-medium-emphasis">Provincial employees under this office</div>
                    </div>
                    <v-chip size="small" variant="tonal" color="primary">{{ mockEmployees.length }} Employees</v-chip>
                </div>
                <v-divider></v-divider>
                <div class="pa-4">
                    <NewEmployeeList :employees="mockEmployees" />
                </div>
            </v-card>

        </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import LineGraphCard from '@/Components/Cards/LineGraphCard.vue';
import PieChartCard from '@/Components/Cards/PieChartCard.vue';
import QuantityCard from '@/Components/Cards/QuantityCard.vue';
import DirectorInfoCard from '@/Components/Cards/DirectorInfoCard.vue';
import KpiOutcomeList from '@/Components/Lists/KpiOutcomeList.vue';
import NewEmployeeList from '@/Components/Lists/NewEmployeeList.vue';
import {
    RiCheckDoubleLine,
    RiAlertLine,
    RiListCheck3,
    RiMedalLine,
    RiGroup2Line,
    RiUserFollowFill,
    RiUserStarFill,
} from '@remixicon/vue';

defineProps({
    total_employees: { type: Number, default: 0 },
});

// Provincial director (mock - replace with backend props when ready)
const director = ref({
    name:              'Maria Cristina B. Dela Cruz',
    id:                'DOST-XI-2019-0042',
    province:          'Davao del Norte',
    length_of_service: 7,
    kpi_score:         97,
});

// KPI summary stats (mock - replace with backend props when ready)
const kpiStats = ref({
    outcomes_met:     10,
    outcomes_not_met:  2,
    total_indicators: 12,
    met_rate:         83,
});

// Monthly KPI trend
const trendChart = ref({
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    series: [
        { name: 'KPI Score', data: [78, 81, 80, 85, 83, 88, 90, 87, 92, 89, 91, 97] },
        { name: 'Target',    data: [80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80] },
    ]
});

// Pie chart
const outcomePie = computed(() => ({
    series: [kpiStats.value.outcomes_met, kpiStats.value.outcomes_not_met],
    labels: ['Met', 'Not Met'],
    colors: ['#4CAF50', '#F44336'],
}));

// KPI outcomes (mock - replace with backend data when ready)
const kpiOutcomes = ref([
    { title: 'Conduct of S&T trainings and workshops',           target: '4 trainings',   actual: '4 trainings',  met: true  },
    { title: 'Number of SETUP beneficiaries assisted',           target: '10 firms',      actual: '12 firms',     met: true  },
    { title: 'Technology transfer activities implemented',       target: '3 activities',  actual: '3 activities', met: true  },
    { title: 'Research and development projects completed',      target: '2 projects',    actual: '2 projects',   met: true  },
    { title: 'Percentage of budget utilization',                 target: '90%',           actual: '93%',          met: true  },
    { title: 'Submission of reports on time',                    target: '100%',          actual: '100%',         met: true  },
    { title: 'Number of science scholars monitored',             target: '25 scholars',   actual: '24 scholars',  met: false },
    { title: 'Industry cluster engagement activities conducted', target: '6 activities',  actual: '4 activities', met: false },
    { title: 'Stakeholder satisfaction rating',                  target: '4.5 / 5.0',    actual: '4.7 / 5.0',    met: true  },
    { title: 'Coordination meetings with LGUs held',             target: '4 meetings',    actual: '4 meetings',   met: true  },
    { title: 'Conduct of barangay-level science activities',     target: '3 activities',  actual: '3 activities', met: true  },
    { title: 'Submission of provincial S&T plan',                target: '1 plan',        actual: '1 plan',       met: true  },
]);

// Employees - view only (mock - replace with backend data when ready)
const mockEmployees = ref([
    { name: 'Rosa L. Aguilar',     id: 'DOST-XI-EMP-0021', position: 'Science Research Analyst'    },
    { name: 'Dante M. Borja',      id: 'DOST-XI-EMP-0034', position: 'Administrative Officer'      },
    { name: 'Claire B. Camposano', id: 'DOST-XI-EMP-0047', position: 'Project Development Officer' },
    { name: 'Felix R. Dalisay',    id: 'DOST-XI-EMP-0058', position: 'Science Research Specialist' },
    { name: 'Marites G. Estrada',  id: 'DOST-XI-EMP-0063', position: 'Accountant III'              },
    { name: 'Arnold T. Fuentes',   id: 'DOST-XI-EMP-0072', position: 'Engineer II'                 },
    { name: 'Jenny P. Gomez',      id: 'DOST-XI-EMP-0081', position: 'IT Officer I'                },
    { name: 'Rodel C. Hidalgo',    id: 'DOST-XI-EMP-0095', position: 'Administrative Aide VI'      },
]);

const onEditOutcome = (outcome) => {
    console.log('Edit outcome:', outcome);
};
</script>
