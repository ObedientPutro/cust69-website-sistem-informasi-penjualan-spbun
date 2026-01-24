<script setup lang="ts">
import { ref, computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps<{
    series: { name: string; data: number[] }[];
    categories: string[]; // Label X-Axis (Jan, Feb, Mar...)
    height?: number;
    colors?: string[];
}>();

const chartOptions = computed(() => ({
    colors: props.colors || ['#465fff', '#9CB9FF'],
    chart: {
        fontFamily: 'Outfit, sans-serif',
        type: 'bar',
        toolbar: { show: false },
        zoom: { enabled: false }
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '40%',
            borderRadius: 4,
            borderRadiusApplication: 'end',
        },
    },
    dataLabels: { enabled: false },
    stroke: {
        show: true,
        width: 4,
        colors: ['transparent'],
    },
    xaxis: {
        categories: props.categories,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: {
                colors: '#9ca3af', // gray-400
                fontSize: '12px',
                fontFamily: 'Outfit, sans-serif',
            },
        },
    },
    yaxis: {
        labels: {
            style: {
                colors: '#9ca3af',
                fontSize: '12px',
                fontFamily: 'Outfit, sans-serif',
            },
        },
    },
    grid: {
        borderColor: '#e5e7eb', // gray-200
        strokeDashArray: 4,
        yaxis: { lines: { show: true } },
        xaxis: { lines: { show: false } },
        padding: { top: 0, right: 0, bottom: 0, left: 10 }
    },
    legend: {
        show: true,
        position: 'top',
        horizontalAlign: 'left',
        fontFamily: 'Outfit',
        markers: { radius: 99 },
    },
    tooltip: {
        theme: 'light', // Bisa didinamiskan dengan dark mode detect
        y: {
            formatter: (val: number) => val.toLocaleString(),
        },
    },
}));
</script>

<template>
    <div class="w-full overflow-hidden">
        <VueApexCharts
            type="bar"
            :height="height || 300"
            :options="chartOptions"
            :series="series"
        />
    </div>
</template>

<style scoped>

</style>
