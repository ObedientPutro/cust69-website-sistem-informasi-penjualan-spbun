<script setup lang="ts">
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps<{
    series: { name: string; data: number[] }[];
    categories: string[];
    height?: number;
    colors?: string[];
}>();

const chartOptions = computed(() => ({
    chart: {
        fontFamily: 'Outfit, sans-serif',
        type: 'area',
        toolbar: { show: false },
        zoom: { enabled: false }
    },
    colors: props.colors || ['#465FFF', '#10B981'], // Default Blue & Emerald
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.05,
            stops: [0, 90, 100]
        }
    },
    stroke: {
        curve: 'smooth', // smooth / straight
        width: 2,
    },
    dataLabels: { enabled: false },
    grid: {
        borderColor: '#e5e7eb',
        strokeDashArray: 4,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } },
    },
    xaxis: {
        categories: props.categories,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: '#9ca3af', fontSize: '12px' }
        },
        tooltip: { enabled: false },
    },
    yaxis: {
        labels: {
            style: { colors: '#9ca3af', fontSize: '12px' }
        },
    },
    legend: {
        position: 'top',
        horizontalAlign: 'right',
        fontFamily: 'Outfit',
    },
}));
</script>

<template>
    <div class="w-full overflow-hidden">
        <VueApexCharts
            type="area"
            :height="height || 300"
            :options="chartOptions"
            :series="series"
        />
    </div>
</template>

<style scoped>

</style>
