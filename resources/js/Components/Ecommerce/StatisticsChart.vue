<script setup lang="ts">
import { ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const options = [
    { value: 'optionOne', label: 'Monthly' },
    { value: 'optionTwo', label: 'Quarterly' },
    { value: 'optionThree', label: 'Annually' },
];

const selected = ref('optionOne');

const series = ref([
    {
        name: 'Sales',
        data: [180, 190, 170, 160, 175, 165, 170, 205, 230, 210, 240, 235],
    },
    {
        name: 'Revenue',
        data: [40, 30, 50, 40, 55, 40, 70, 100, 110, 120, 150, 140],
    },
]);

const chartOptions = ref({
    legend: {
        show: false,
        position: 'top',
        horizontalAlign: 'left',
    },
    colors: ['#465FFF', '#9CB9FF'],
    chart: {
        fontFamily: 'Outfit, sans-serif',
        type: 'area',
        toolbar: {
            show: false,
        },
    },
    fill: {
        gradient: {
            enabled: true,
            opacityFrom: 0.55,
            opacityTo: 0,
        },
    },
    stroke: {
        curve: 'straight',
        width: [2, 2],
    },
    markers: {
        size: 0,
    },
    labels: {
        show: false,
        position: 'top',
    },
    grid: {
        xaxis: {
            lines: {
                show: false,
            },
        },
        yaxis: {
            lines: {
                show: true,
            },
        },
    },
    dataLabels: {
        enabled: false,
    },
    tooltip: {
        x: {
            format: 'dd MMM yyyy',
        },
    },
    xaxis: {
        type: 'category',
        categories: [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec',
        ],
        axisBorder: {
            show: false,
        },
        axisTicks: {
            show: false,
        },
        tooltip: {
            enabled: false,
        },
    },
    yaxis: {
        title: {
            style: {
                fontSize: '0px',
            },
        },
    },
});
</script>

<template>
    <div
        class="rounded-2xl border border-gray-200 bg-white px-5 pt-5 pb-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/[0.03]"
    >
        <div class="mb-6 flex flex-col gap-5 sm:flex-row sm:justify-between">
            <div class="w-full">
                <h3
                    class="text-lg font-semibold text-gray-800 dark:text-white/90"
                >
                    Statistics
                </h3>
                <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">
                    Target you’ve set for each month
                </p>
            </div>

            <div class="relative">
                <div
                    class="inline-flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900"
                >
                    <button
                        v-for="option in options"
                        :key="option.value"
                        @click="selected = option.value"
                        :class="[
                            selected === option.value
                                ? 'shadow-theme-xs bg-white text-gray-900 dark:bg-gray-800 dark:text-white'
                                : 'text-gray-500 dark:text-gray-400',
                            'text-theme-sm hover:shadow-theme-xs rounded-md px-3 py-2 font-medium hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white',
                        ]"
                    >
                        {{ option.label }}
                    </button>
                </div>
            </div>
        </div>
        <div class="custom-scrollbar max-w-full overflow-x-auto">
            <div
                id="chartThree"
                class="-ml-4 min-w-[1000px] pl-2 xl:min-w-full"
            >
                <VueApexCharts
                    type="area"
                    height="310"
                    :options="chartOptions"
                    :series="series"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.area-chart {
    width: 100%;
}
</style>
