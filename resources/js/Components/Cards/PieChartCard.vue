<template>
    <v-card class="elevation-1 border-0 rounded-lg pa-4 w-100">
        <apexchart
            type="pie"
            width="100%"
            :options="chartOptions"
            :series="series"
        />
    </v-card>
</template>

<script setup>
import { computed } from 'vue';
import apexchart from 'vue3-apexcharts';

const props = defineProps({
    title: {
        type: String,
        default: 'Distribution'
    },
    series: {
        type: Array,
        default: () => [1]
    },
    labels: {
        type: Array,
        default: () => ['No Data']
    },
    colors: {
        type: Array,
        default: () => ['#1867C0', '#4CAF50', '#FB8C00', '#F44336', '#9C27B0']
    }
});

const chartOptions = computed(() => ({
    chart: {
        type: 'pie',
        toolbar: { show: false },
        animations: { enabled: true }
    },
    title: {
        text: props.title,
        align: 'left',
        style: {
            fontSize: '13px',
            fontWeight: '600',
            fontFamily: 'inherit',
            color: '#374151'
        }
    },
    labels: props.labels,
    colors: props.colors,
    legend: {
        position: 'bottom',
        fontSize: '12px',
        fontFamily: 'inherit',
        markers: { size: 8 }
    },
    dataLabels: {
        style: {
            fontSize: '12px',
            fontFamily: 'inherit',
            fontWeight: '500'
        },
        dropShadow: { enabled: false }
    },
    stroke: { width: 0 },
    tooltip: {
        y: {
            formatter: (val) => `${val} Director${val !== 1 ? 's' : ''}`
        }
    },
    responsive: [
        {
            breakpoint: 480,
            options: { chart: { width: '100%' } }
        }
    ]
}));
</script>
