<script setup lang="ts">
import MetricCard from '@/Components/Metrics/MetricCard.vue';

defineProps<{
    stats: {
        today_profit: number;
        today_revenue: number;
        total_debt: number;
        bon_ratio: number;
    }
}>();

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 lg:grid-cols-4">
        <MetricCard
            title="Keuntungan Hari Ini (Est)"
            :value="formatRupiah(stats.today_profit)"
            color="success"
            trend-label="Net Profit"
        >
            <template #icon>
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"><path d="M21,18V19A2,2 0 0,1 19,21H5C3.89,21 3,20.1 3,19V5A2,2 0 0,1 5,3H19A2,2 0 0,1 21,5V6H12C10.89,6 10,6.9 10,8V16A2,2 0 0,0 12,18M12,16H22V8H12M16,13.5A1.5,1.5 0 0,1 14.5,12A1.5,1.5 0 0,1 16,10.5A1.5,1.5 0 0,1 17.5,12A1.5,1.5 0 0,1 16,13.5Z" /></svg>
            </template>
        </MetricCard>

        <MetricCard
            title="Omzet Penjualan"
            :value="formatRupiah(stats.today_revenue)"
            color="primary"
        >
            <template #icon>
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"><path d="M7,15H9C9,16.08 10.37,17 12,17C13.63,17 15,16.08 15,15C15,13.9 13.96,13.5 11.76,12.97C9.64,12.44 7,11.78 7,9C7,7.21 8.47,5.69 10.5,5.18V3H13.5V5.18C15.53,5.69 17,7.21 17,9H15C15,7.92 13.63,7 12,7C10.37,7 9,7.92 9,9C9,10.1 10.04,10.5 12.24,11.03C14.36,11.56 17,12.22 17,15C17,16.79 15.53,18.31 13.5,18.82V21H10.5V18.82C8.47,18.31 7,16.79 7,15Z" /></svg>
            </template>
        </MetricCard>

        <MetricCard
            title="Total Piutang Aktif"
            :value="formatRupiah(stats.total_debt)"
            color="warning"
            :trend="stats.bon_ratio"
            trend-label="% Bon Bulan Ini"
        >
            <template #icon>
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"><path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" /></svg>
            </template>
        </MetricCard>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Rasio Bon vs Cash</h4>
            <div class="mt-4 flex items-end gap-2">
                <div class="w-full">
                    <div class="mb-2 flex justify-between text-xs">
                        <span class="text-green-600 font-bold">Cash {{ 100 - stats.bon_ratio }}%</span>
                        <span class="text-orange-500 font-bold">Bon {{ stats.bon_ratio }}%</span>
                    </div>
                    <div class="flex h-3 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div class="bg-green-500" :style="{ width: (100 - stats.bon_ratio) + '%' }"></div>
                        <div class="bg-orange-500" :style="{ width: stats.bon_ratio + '%' }"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
