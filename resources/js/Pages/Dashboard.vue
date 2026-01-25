<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ExecutiveMetrics from '@/Components/Dashboard/ExecutiveMetrics.vue';
import StockTankList from '@/Components/Dashboard/StockTankList.vue';
import ChartCard from '@/Components/Metrics/ChartCard.vue';
import AreaChart from '@/Components/Metrics/AreaChart.vue';
import Alert from '@/Components/Ui/Alert.vue';

const props = defineProps<{
    financial: any;
    inventory: any[];
    trends: any;
    lists: any;
}>();

// Helper untuk format rupiah di template
const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
</script>

<template>
    <Head title="Executive Dashboard" />

    <AdminLayout>
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Kesehatan Bisnis (Real-time)</h2>
            <ExecutiveMetrics :stats="financial" />
        </div>

        <div class="grid grid-cols-12 gap-4 md:gap-6">

            <div class="col-span-12 xl:col-span-8 space-y-6">

                <ChartCard title="Tren Produk BBM" subtitle="Perbandingan volume penjualan 14 hari terakhir">
                    <AreaChart
                        :categories="trends.categories"
                        :series="trends.series"
                        height="350"
                        :colors="['#3C50E0', '#80CAEE', '#10B981']"
                    />
                </ChartCard>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-red-600">Top 5 Piutang Tertinggi</h3>
                        <a href="/debts" class="text-sm text-blue-500 hover:underline">Lihat Semua</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                            <tr class="text-xs text-gray-500 uppercase border-b dark:border-gray-700">
                                <th class="py-2">Nelayan / Kapal</th>
                                <th class="py-2 text-right">Total Bon</th>
                                <th class="py-2 text-center">Jumlah Transaksi</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(debtor, idx) in lists.debtors" :key="idx" class="border-b last:border-0 dark:border-gray-800">
                                <td class="py-3">
                                    <p class="font-bold text-gray-800 dark:text-white">{{ debtor.name }}</p>
                                    <p class="text-xs text-gray-500">{{ debtor.ship_name }}</p>
                                </td>
                                <td class="py-3 text-right font-mono font-bold text-red-500">
                                    {{ formatRupiah(debtor.total_debt) }}
                                </td>
                                <td class="py-3 text-center">
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">{{ debtor.bon_count }}x</span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="col-span-12 xl:col-span-4 space-y-6">

                <StockTankList :products="inventory" />

                <div v-if="financial.bon_ratio > 50" class="animate-pulse">
                    <Alert variant="warning" title="Peringatan Cashflow" message="Rasio Bon bulan ini di atas 50%. Pastikan cadangan kas cukup untuk Restock BBM." />
                </div>

            </div>
        </div>
    </AdminLayout>
</template>
