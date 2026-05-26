<template>
  <v-card class="p-4 elevation-1 border-0 rounded-lg">
    <!-- Component name capitalized to avoid HTML tag conflicts -->
    <apexchart 
      type="line" 
      width="100%" 
      height="350" 
      :options="chartOptions" 
      :series="chartSeries"
    />
  </v-card>
</template>

<script setup>
import { ref, computed } from 'vue';
// Capitalized alias prevents native HTML element conflicts
import Apexchart from 'vue3-apexcharts'; 

// Correct syntax for defining props with default values
const props = defineProps({
  title: {
    type: String,
    default: 'Product Trends by Month'
  },
  labelX: {
    type: Array,
    default: () => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep']
  },
  data: {
    type: Array,
    default: () => [
      {
        name: "Desktops",
        data: [10, 41, 35, 51, 49, 62, 69, 91, 148]
      }
    ]
  }
});

// Use computed properties so the chart updates automatically if props change
const chartSeries = computed(() => props.data);

const chartOptions = computed(() => ({
  chart: {
    id: 'vue-line-chart',
    toolbar: { show: true }
  },
  xaxis: {
    categories: props.labelX
  },
  stroke: {
    curve: 'smooth'
  },
  title: {
    text: props.title,
    align: 'left'
  }
}));
</script>
