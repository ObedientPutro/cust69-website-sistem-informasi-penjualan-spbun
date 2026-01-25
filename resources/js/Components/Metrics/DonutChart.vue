<script setup lang="ts">
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps<{
    series: number[]; // [Value1, Value2]
    labels: string[]; // ['Label1', 'Label2']
    height?: number;
    colors?: string[];
}>();

const chartOptions = computed(() => ({
    chart: {
        type: 'donut',
        fontFamily: 'Outfit, sans-serif',
    },
    colors: props.colors || ['#10B981', '#EF4444'], // Success (Paid), Error (Debt)
    labels: props.labels,
    legend: {
        show: true,
        position: 'bottom',
        fontFamily: 'Outfit',
    },
    plotOptions: {
        pie: {
            donut: {
                size: '75%',
                labels: {
                    show: true,
                    name: { show: true },
                    value: {
                        show: true,
                        fontWeight: 'bold',
                    },
                    total: {
                        show: true,
                        label: 'Total',
                        fontWeight: 'bold',
                    }
                }
            }
        }
    },
    dataLabels: { enabled: false },
    stroke: { show: false },
}));
</script>

<template>
    <div class="w-full flex justify-center">
        <VueApexCharts
            type="donut"
            :height="height || 300"
            :options="chartOptions"
            :series="series"
        />
    </div>
</template>

<style scoped>

</style>
