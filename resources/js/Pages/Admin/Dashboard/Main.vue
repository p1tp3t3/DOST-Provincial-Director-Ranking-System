<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <div class="grid gap-4 w-full">

            <!-- Primary Stat Cards -->
            <v-row>
                <v-col cols="12" sm="3">
                    <QuantityCard title="Total Users" :quantity="total_users" :icon="RiGroupLine" color="indigo" />
                </v-col>
                <v-col cols="12" sm="3">
                    <QuantityCard title="Active Users" :quantity="active_users" :icon="RiUserFollowLine" color="success" />
                </v-col>
                <v-col cols="12" sm="3">
                    <QuantityCard title="Provincial Directors" :quantity="total_directors" :icon="RiUserStarLine" color="indigo" />
                </v-col>
                <v-col cols="12" sm="3">
                    <QuantityCard title="Sub-Administrators" :quantity="total_sub_admins" :icon="RiShieldUserLine" color="indigo" />
                </v-col>
            </v-row>

            <!-- Charts Row -->
            <v-row dense>
                <v-col cols="12" md="8" class="d-flex">
                    <LineGraphCard
                        class="flex-grow-1"
                        title="Monthly User Logins"
                        :labelX="loginTrend.labels"
                        :data="loginTrend.series"
                    />
                </v-col>
                <v-col cols="12" md="4" class="d-flex">
                    <PieChartCard
                        class="flex-grow-1"
                        title="User Role Distribution"
                        :series="rolePie.series"
                        :labels="rolePie.labels"
                        :colors="rolePie.colors"
                    />
                </v-col>
            </v-row>

            <!-- Recent Users + Active Users -->
            <v-row dense>
                <v-col cols="12" md="6" class="d-flex">
                    <RecentUsersCard class="flex-grow-1" :users="recentUsers" />
                </v-col>
                <v-col cols="12" md="6" class="d-flex">
                    <ActiveUsersCard class="flex-grow-1" :users="activeUsers" />
                </v-col>
            </v-row>

            <!-- Activity Log -->
            <ActivityLogCard :logs="activityLogs" />

        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import LineGraphCard from '@/Components/Cards/LineGraphCard.vue';
import PieChartCard from '@/Components/Cards/PieChartCard.vue';
import QuantityCard from '@/Components/Cards/QuantityCard.vue';
import RecentUsersCard from '@/Components/Cards/RecentUsersCard.vue';
import ActivityLogCard from '@/Components/Cards/ActivityLogCard.vue';
import ActiveUsersCard from '@/Components/Cards/ActiveUsersCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    RiGroupLine,
    RiUserFollowLine,
    RiUserStarLine,
    RiShieldUserLine,
} from '@remixicon/vue';

const props = defineProps({
    total_users:      { type: Number, default: 0 },
    active_users:     { type: Number, default: 0 },
    total_sub_admins: { type: Number, default: 0 },
    total_directors:  { type: Number, default: 0 },
    total_employees:  { type: Number, default: 0 },
    total_provinces:  { type: Number, default: 0 },
});

// Login activity trend (mock — replace with backend data when ready)
const loginTrend = ref({
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    series: [
        { name: 'Logins', data: [42, 38, 55, 61, 58, 70, 65, 72, 68, 80, 75, 88] },
    ]
});

// Role distribution pie
const rolePie = computed(() => ({
    series: [props.total_sub_admins || 3, props.total_directors || 8, props.total_employees || 45],
    labels: ['Sub-Admins', 'Directors', 'Employees'],
    colors: ['#7C3AED', '#1D4ED8', '#0D9488'],
}));

// Recently added users (mock — replace with backend data when ready)
const recentUsers = ref([
    { name: 'Maria Santos',    email: 'maria.santos@dost.gov.ph',    role: 'director'  },
    { name: 'Juan dela Cruz',  email: 'juan.delacruz@dost.gov.ph',   role: 'sub-admin' },
    { name: 'Anna Reyes',      email: 'anna.reyes@dost.gov.ph',      role: 'employee'  },
    { name: 'Carlo Mendoza',   email: 'carlo.mendoza@dost.gov.ph',   role: 'employee'  },
    { name: 'Liza Villanueva', email: 'liza.villanueva@dost.gov.ph', role: 'director'  },
]);

// Active users (mock — replace with backend data when ready)
const activeUsers = ref([
    { name: 'Juan dela Cruz',  role: 'sub-admin', last_active: 'just now'   },
    { name: 'Maria Santos',    role: 'director',  last_active: '2 mins ago' },
    { name: 'Anna Reyes',      role: 'employee',  last_active: '5 mins ago' },
    { name: 'Carlo Mendoza',   role: 'employee',  last_active: '8 mins ago' },
    { name: 'Liza Villanueva', role: 'director',  last_active: '12 mins ago'},
]);

// Activity logs (mock — replace with backend data when ready)
const activityLogs = ref([
    { user: 'Juan dela Cruz',  role: 'sub-admin', action: 'Updated director KPI scores',  module: 'KPI',      datetime: 'May 26, 09:14 AM' },
    { user: 'Maria Santos',    role: 'director',  action: 'Submitted quarterly report',   module: 'Reports',  datetime: 'May 26, 08:52 AM' },
    { user: 'Anna Reyes',      role: 'employee',  action: 'Logged in',                    module: 'Auth',     datetime: 'May 26, 08:30 AM' },
    { user: 'Carlo Mendoza',   role: 'employee',  action: 'Uploaded project document',    module: 'Projects', datetime: 'May 25, 04:45 PM' },
    { user: 'Liza Villanueva', role: 'director',  action: 'Updated personal profile',     module: 'Profile',  datetime: 'May 25, 03:20 PM' },
    { user: 'Juan dela Cruz',  role: 'sub-admin', action: 'Created new employee account', module: 'Users',    datetime: 'May 25, 01:10 PM' },
    { user: 'Maria Santos',    role: 'director',  action: 'Logged in',                    module: 'Auth',     datetime: 'May 25, 08:05 AM' },
]);
</script>
