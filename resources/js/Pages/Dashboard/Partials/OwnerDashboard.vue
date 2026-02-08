<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ChartCard from '@/Components/Metrics/ChartCard.vue';
import AreaChart from '@/Components/Metrics/AreaChart.vue';
import DonutChart from '@/Components/Metrics/DonutChart.vue';
import Badge from '@/Components/Ui/Badge.vue';
import DataTable from '@/Components/Tables/DataTable.vue'; // Gunakan Component DataTable

const props = defineProps<{
    metrics: any;
    inventory: any[];
    trends: {
        volume_series: { categories: string[], series: any[] };
        payment_stats: any[];
        debt_ratio_series: { labels: string[], data: number[] }
    };
    lists: {
        debtors: any[],
        recent_transactions: any[]
    };
    active_shifts: any[];
}>();

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
const formatNumber = (val: number) => new Intl.NumberFormat('id-ID').format(val);

// Helper untuk warna status stok
const getStockStatusClass = (status: string) => {
    switch (status) {
        case 'empty':
        case 'critical':
            return 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300';
        case 'warning':
            return 'bg-orange-50 border-orange-200 text-orange-800 dark:bg-orange-900/20 dark:border-orange-800 dark:text-orange-300';
        default:
            return 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300';
    }
};

const getStockIcon = (status: string) => {
    if (status === 'empty' || status === 'critical') {
        return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
    } else if (status === 'warning') {
        return 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z';
    } else {
        return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
    }
};

// --- CONFIG TABEL ---
// Kolom untuk Top Debtor
const debtorsColumns = [
    { label: 'Nama Kapal / Pemilik', key: 'identity', align: 'left' },
    { label: 'Total Hutang', key: 'total_debt', align: 'left' },
    { label: 'Frekuensi', key: 'bon_count', align: 'center' },
];

// Kolom untuk Recent Transactions
const transactionsColumns = [
    { label: 'Kapal / Pelanggan', key: 'customer', align: 'left' },
    { label: 'Produk', key: 'items', align: 'left' },
    { label: 'Total', key: 'total', align: 'left' },
    { label: 'Status', key: 'status', align: 'center' },
];
</script>

<template>
    <div class="space-y-6">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
            <div class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 transition-all hover:shadow-lg dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                        <svg class="fill-current" width="28" height="28" viewBox="0 0 24 24">
                            <path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Omset Bulan Ini</span>
                        <h4 class="mt-1 text-2xl font-extrabold text-gray-800 dark:text-white">
                            {{ formatRupiah(metrics.revenue_month) }}
                        </h4>
                    </div>
                </div>
                <div class="mt-4 flex items-center">
                    <span class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-bold"
                          :class="metrics.revenue_growth >= 0
                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                            : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
                        <span v-if="metrics.revenue_growth >= 0">↗</span><span v-else>↘</span>
                        {{ Math.abs(metrics.revenue_growth) }}%
                    </span>
                    <span class="ml-2 text-xs text-gray-400">vs bulan lalu</span>
                </div>
            </div>

            <div class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 transition-all hover:shadow-lg dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400">
                        <svg class="fill-current" width="28" height="28" viewBox="0 0 24 24">
                            <path d="M5,6H23V18H5V6M14,9A3,3 0 0,1 17,12A3,3 0 0,1 14,15A3,3 0 0,1 11,12A3,3 0 0,1 14,9M9,8A2,2 0 0,1 7,10V14A2,2 0 0,1 9,16H19A2,2 0 0,1 21,14V10A2,2 0 0,1 19,8H9M1,10H3V20H19V22H1V10Z" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wider text-green-600 dark:text-green-400">Est. Profit Hari Ini</span>
                        <h4 class="mt-1 text-2xl font-extrabold text-gray-800 dark:text-white">
                            {{ formatRupiah(metrics.today_profit) }}
                        </h4>
                    </div>
                </div>
                <div class="mt-4 flex items-center">
                    <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        Gross Margin
                    </span>
                    <span class="ml-2 text-xs text-gray-400">(Estimasi Laba)</span>
                </div>
            </div>

            <div class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 transition-all hover:shadow-lg dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400">
                        <svg class="fill-current" width="28" height="28" viewBox="0 0 24 24">
                            <path d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M12,5A3,3 0 1,1 9,8A3,3 0 0,1 12,5M17.13,17C15.92,18.85 14.11,20.24 12,20.92C9.89,20.24 8.08,18.85 6.87,17C6.53,16.5 6.24,16 6,15.47C6,13.82 8.71,12.47 12,12.47C15.29,12.47 18,13.82 18,15.47C17.76,16 17.47,16.5 17.13,17Z" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400">Total Piutang Aktif</span>
                        <h4 class="mt-1 text-2xl font-extrabold text-gray-800 dark:text-white">
                            {{ formatRupiah(metrics.total_debt) }}
                        </h4>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-xs text-gray-400">Resiko tertunda</span>
                    <Link :href="route('debts.index')" class="flex items-center gap-1 text-xs font-bold text-orange-600 hover:text-orange-700 hover:underline">
                        Lihat Detail
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </Link>
                </div>
            </div>

            <div class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 transition-all hover:shadow-lg dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400">
                        <svg class="fill-current" width="28" height="28" viewBox="0 0 24 24">
                            <path d="M21 16.5C21 16.88 20.79 17.21 20.47 17.38L12.57 21.82C12.41 21.94 12.21 22 12 22S11.59 21.94 11.43 21.82L3.53 17.38C3.21 17.21 3 16.88 3 16.5V7.5C3 7.12 3.21 6.79 3.53 6.62L11.43 2.18C11.59 2.06 11.79 2 12 2S12.41 2.06 12.57 2.18L20.47 6.62C20.79 6.79 21 7.12 21 7.5V16.5Z" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">Volume Bulan Ini</span>
                        <h4 class="mt-1 text-2xl font-extrabold text-gray-800 dark:text-white">
                            {{ formatNumber(metrics.volume_month) }} <span class="text-sm font-medium text-gray-500">Liter</span>
                        </h4>
                    </div>
                </div>
                <div class="mt-4 flex items-center">
                    <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        Akumulasi
                    </span>
                    <span class="ml-2 text-xs text-gray-400">Total Liter Keluar</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">

            <div class="col-span-12 xl:col-span-8 space-y-6">

                <ChartCard title="Tren Pembelian BBM" subtitle="Perbandingan volume per produk (14 hari terakhir)">
                    <AreaChart
                        :categories="trends.volume_series.categories"
                        :series="trends.volume_series.series"
                        height="350"
                        :colors="['#3C50E0', '#80CAEE', '#10B981', '#F59E0B']"
                    />
                </ChartCard>

                <div class="space-y-2">
                    <div class="flex justify-between items-center px-1">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Top 5 Piutang Tertinggi</h3>
                        <Link :href="route('debts.index')" class="text-xs bg-gray-100 px-2 py-1 rounded hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Kelola Piutang</Link>
                    </div>

                    <DataTable
                        :rows="lists.debtors"
                        :columns="debtorsColumns"
                        :enableActions="false"
                        :enableSearch="false"
                    >
                        <template #cell-identity="{ row }">
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-800 dark:text-white text-base">{{ row.ship_name }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ row.owner_name }}</span>
                            </div>
                        </template>

                        <template #cell-total_debt="{ row }">
                            <span class="font-mono font-bold text-red-500">{{ formatRupiah(row.total_debt) }}</span>
                        </template>

                        <template #cell-bon_count="{ row }">
                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs text-gray-600 dark:text-gray-300">{{ row.bon_count }}x</span>
                        </template>
                    </DataTable>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-center px-1">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Transaksi Terakhir</h3>
                        <Link :href="route('history.transactions.index')" class="text-xs bg-gray-100 px-2 py-1 rounded hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Lihat Semua</Link>
                    </div>

                    <DataTable
                        :rows="lists.recent_transactions"
                        :columns="transactionsColumns"
                        :enableActions="false"
                        :enableSearch="false"
                    >
                        <template #cell-customer="{ row }">
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-800 dark:text-white text-sm">
                                    {{ row.ship_name ?? row.customer }}
                                </span>
                                <span v-if="row.owner_name" class="text-xs text-gray-500 dark:text-gray-400">{{ row.owner_name }}</span>
                            </div>
                        </template>

                        <template #cell-items="{ row }">
                            <span class="font-medium text-gray-600 dark:text-gray-300">{{ row.items }}</span>
                        </template>

                        <template #cell-total="{ row }">
                            <span class="font-mono font-medium text-gray-800 dark:text-white">{{ formatRupiah(row.total) }}</span>
                        </template>

                        <template #cell-status="{ row }">
                            <Badge :color="row.status === 'paid' ? 'success' : (row.status === 'returned' ? 'error' : 'warning')" size="sm">
                                {{ row.status }}
                            </Badge>
                        </template>
                    </DataTable>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-4 space-y-6">

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-2">Rasio Pembayaran</h3>
                    <DonutChart
                        :series="trends.debt_ratio_series.data"
                        :labels="trends.debt_ratio_series.labels"
                        :colors="['#10B981', '#EF4444']"
                        height="250"
                    />
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-4">Metode Pembayaran</h3>
                    <div class="space-y-4">
                        <div v-for="(method, idx) in trends.payment_stats" :key="idx">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ method.label }}</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ method.count }} Trx</span>
                            </div>
                            <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden dark:bg-gray-700">
                                <div
                                    class="h-full rounded-full"
                                    :class="method.color"
                                    :style="{ width: method.percent + '%' }"
                                ></div>
                            </div>
                            <p class="text-[10px] mt-1" :class="method.text_color">{{ method.percent }}% dari total</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 dark:text-white">Estimasi Stok</h3>
                        <Link :href="route('restock-history.index')" class="text-xs font-bold text-brand-600 hover:underline">+ Restock</Link>
                    </div>

                    <div class="space-y-3">
                        <div v-for="item in inventory" :key="item.id"
                             class="p-4 rounded-xl border flex justify-between items-start transition-all hover:shadow-sm"
                             :class="getStockStatusClass(item.status)"
                        >
                            <div class="flex-1">
                                <p class="text-[10px] uppercase font-bold tracking-wider opacity-70 mb-0.5">
                                    {{ item.name }}
                                </p>
                                <h4 class="text-lg font-extrabold leading-tight">
                                    {{ item.days_to_empty }}
                                </h4>
                                <div class="mt-2 flex items-center gap-2 text-xs font-medium opacity-90">
                                    <span>Sisa: {{ formatNumber(item.stock) }} {{ item.unit }}</span>
                                </div>
                            </div>

                            <div class="p-2 rounded-full bg-white/50 backdrop-blur-sm dark:bg-black/20">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getStockIcon(item.status)" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 dark:text-white">Shift Aktif</h3>
                        <Link :href="route('shifts.index')" class="text-xs bg-gray-100 px-2 py-1 rounded hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Lihat</Link>
                    </div>
                    <div v-if="active_shifts.length > 0" class="space-y-3">
                        <div v-for="(shift, idx) in active_shifts" :key="idx" class="flex items-center justify-between bg-green-50 p-3 rounded-lg border border-green-100 dark:bg-green-900/10 dark:border-green-800">
                            <div>
                                <p class="text-sm font-bold text-gray-800 dark:text-white">{{ shift.product_name }}</p>
                                <p class="text-xs text-green-700 dark:text-green-400">{{ shift.opener_name }}</p>
                            </div>
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ shift.opened_at_time }}</span>
                        </div>
                    </div>
                    <div v-else class="text-center py-4 text-gray-400 text-sm italic">Tidak ada shift terbuka.</div>
                </div>

            </div>
        </div>
    </div>
</template>
