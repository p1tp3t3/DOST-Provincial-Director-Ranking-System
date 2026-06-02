<template>
    <Head title="Dashboard" />
        <div class="grid gap-4 w-full">

            <!-- Row 1: Director info + KPI stat cards -->
            <v-row>
                <v-col cols="12" md="4">
                    <DirectorInfoCard :director="director" />
                </v-col>

                <v-col cols="12" md="8">
                    <v-row class="h-100" dense>
                        <v-col v-for="stat in kpiStatCards" :key="stat.label" cols="6">
                            <v-card class="elevation-1 border-0 rounded-md pa-4 h-100">
                                <div class="d-flex align-center gap-3">
                                    <v-avatar :color="stat.color + '-lighten-5'" rounded="lg" size="42">
                                        <v-icon :color="stat.color" size="20">{{ stat.icon }}</v-icon>
                                    </v-avatar>
                                    <div>
                                        <div class="text-h6 font-weight-bold">{{ stat.value }}</div>
                                        <div class="text-caption text-medium-emphasis">{{ stat.label }}</div>
                                    </div>
                                </div>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-col>
            </v-row>

            <!-- Row 2: KPI trend + Pie -->
            <v-row dense>
                <v-col cols="12" md="8">
                    <LineGraphCard
                        title="KPI Score Trend"
                        :labelX="trendChart.labels"
                        :data="trendChart.series"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <PieChartCard
                        title="Outcome Breakdown"
                        :series="outcomePie.series"
                        :labels="outcomePie.labels"
                        :colors="outcomePie.colors"
                    />
                </v-col>
            </v-row>

            <!-- Row 3: KPI Outcomes (view-only) -->
            <v-card class="elevation-1 border-0 rounded-md">
                <div class="d-flex align-center justify-space-between px-5 pt-4 pb-3">
                    <div>
                        <div class="text-subtitle-2 font-weight-bold">KPI Outcomes</div>
                        <div class="text-caption text-medium-emphasis">Your performance indicators for this period</div>
                    </div>
                    <div class="d-flex align-center gap-2">
                        <v-chip size="small" variant="tonal" color="success">{{ kpiStats.outcomes_met }} Met</v-chip>
                        <v-chip size="small" variant="tonal" color="error">{{ kpiStats.outcomes_not_met }} Not Met</v-chip>
                    </div>
                </div>
                <v-divider></v-divider>
                <v-table density="comfortable">
                    <thead>
                        <tr>
                            <th class="text-caption text-medium-emphasis" width="50">#</th>
                            <th class="text-caption text-medium-emphasis">Outcome / Indicator</th>
                            <th class="text-caption text-medium-emphasis text-center" width="140">Target</th>
                            <th class="text-caption text-medium-emphasis text-center" width="140">Accomplished</th>
                            <th class="text-caption text-medium-emphasis text-center" width="100">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(outcome, i) in kpiOutcomes" :key="i">
                            <td class="text-body-2 text-medium-emphasis">{{ i + 1 }}</td>
                            <td class="text-body-2 py-3">{{ outcome.title }}</td>
                            <td class="text-body-2 text-center text-medium-emphasis">{{ outcome.target }}</td>
                            <td class="text-body-2 text-center font-weight-medium">{{ outcome.actual }}</td>
                            <td class="text-center">
                                <v-chip
                                    size="x-small"
                                    variant="tonal"
                                    :color="outcome.met ? 'success' : 'error'"
                                >
                                    <v-icon start size="11">
                                        {{ outcome.met ? 'mdi-check-circle-outline' : 'mdi-close-circle-outline' }}
                                    </v-icon>
                                    {{ outcome.met ? 'Met' : 'Not Met' }}
                                </v-chip>
                            </td>
                        </tr>
                    </tbody>
                </v-table>
            </v-card>

            <!-- Row 4: Employees (view-only) -->
            <v-card class="elevation-1 border-0 rounded-md">
                <div class="d-flex align-center justify-space-between px-5 pt-4 pb-3">
                    <div>
                        <div class="text-subtitle-2 font-weight-bold">Employees</div>
                        <div class="text-caption text-medium-emphasis">Staff under your provincial office</div>
                    </div>
                    <v-chip size="small" variant="tonal" color="primary">
                        {{ total_employees }} Employees
                    </v-chip>
                </div>
                <v-divider></v-divider>
                <div class="pa-4">
                    <NewEmployeeList :employees="mockEmployees" />
                </div>
            </v-card>

        </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import DirectorInfoCard from '@/Components/Cards/DirectorInfoCard.vue';
import LineGraphCard from '@/Components/Cards/LineGraphCard.vue';
import PieChartCard from '@/Components/Cards/PieChartCard.vue';
import NewEmployeeList from '@/Components/Lists/NewEmployeeList.vue';

const props = defineProps({
    total_employees: { type: Number, default: 0 },
});

// ── Director profile (mock — replace with backend prop) ──────────
const director = ref({
    name:              'Maria Cristina B. Dela Cruz',
    id:                'DOST-XI-2019-0042',
    province:          'Davao del Norte',
    length_of_service: 7,
    kpi_score:         91,
});

// ── KPI summary ──────────────────────────────────────────────────
const kpiStats = ref({
    outcomes_met:     10,
    outcomes_not_met:  2,
    total_indicators: 12,
    met_rate:         83,
});

const kpiStatCards = computed(() => [
    { label: 'Outcomes Met',     value: kpiStats.value.outcomes_met,     color: 'success', icon: 'mdi-check-circle-outline'  },
    { label: 'Outcomes Not Met', value: kpiStats.value.outcomes_not_met, color: 'error',   icon: 'mdi-close-circle-outline'   },
    { label: 'Total Indicators', value: kpiStats.value.total_indicators, color: 'indigo',  icon: 'mdi-clipboard-list-outline' },
    { label: 'Met Rate',         value: kpiStats.value.met_rate + '%',   color: 'teal',    icon: 'mdi-percent-outline'        },
]);

// ── Charts ───────────────────────────────────────────────────────
const trendChart = ref({
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    series: [
        { name: 'KPI Score', data: [78, 81, 80, 85, 83, 88, 90, 87, 92, 89, 91, 97] },
        { name: 'Target',    data: [80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80] },
    ],
});

const outcomePie = computed(() => ({
    series: [kpiStats.value.outcomes_met, kpiStats.value.outcomes_not_met],
    labels: ['Met', 'Not Met'],
    colors: ['#4CAF50', '#F44336'],
}));

// ── KPI outcomes (view-only) ─────────────────────────────────────
const kpiOutcomes = ref([
    { title: 'Conduct of S&T trainings and workshops',           target: '4 trainings',  actual: '4 trainings',  met: true  },
    { title: 'Number of SETUP beneficiaries assisted',           target: '10 firms',     actual: '12 firms',     met: true  },
    { title: 'Technology transfer activities implemented',       target: '3 activities', actual: '3 activities', met: true  },
    { title: 'Research and development projects completed',      target: '2 projects',   actual: '2 projects',   met: true  },
    { title: 'Percentage of budget utilization',                 target: '90%',          actual: '93%',          met: true  },
    { title: 'Submission of reports on time',                    target: '100%',         actual: '100%',         met: true  },
    { title: 'Number of science scholars monitored',             target: '25 scholars',  actual: '24 scholars',  met: false },
    { title: 'Industry cluster engagement activities conducted', target: '6 activities', actual: '4 activities', met: false },
    { title: 'Stakeholder satisfaction rating',                  target: '4.5 / 5.0',   actual: '4.7 / 5.0',   met: true  },
    { title: 'Coordination meetings with LGUs held',             target: '4 meetings',   actual: '4 meetings',   met: true  },
    { title: 'Conduct of barangay-level science activities',     target: '3 activities', actual: '3 activities', met: true  },
    { title: 'Submission of provincial S&T plan',                target: '1 plan',       actual: '1 plan',       met: true  },
]);

// ── Employees (view-only) ────────────────────────────────────────
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
</script>
