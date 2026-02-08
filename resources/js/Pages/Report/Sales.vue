<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DatePicker from '@/Components/FormElements/DatePicker.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import Button from '@/Components/Ui/Button.vue';
import MetricCard from '@/Components/Metrics/MetricCard.vue';
import Badge from '@/Components/Ui/Badge.vue';
import { DocsIcon, BoxCubeIcon, PlugInIcon, WarningIcon, ChevronDownIcon, ChevronRightIcon } from '@/Components/Icons';

const props = defineProps<{
    data: any[];
    products: any[];
    filters: any;
}>();

const form = ref({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
    product_id: props.filters.product_id || '',
});

const expandedRows = ref<string[]>([]);
const toggleRow = (date: string) => {
    if (expandedRows.value.includes(date)) expandedRows.value = expandedRows.value.filter(d => d !== date);
    else expandedRows.value.push(date);
};

watch(form, () => {
    router.get(route('reports.sales'), form.value, { preserveState: true, replace: true });
}, { deep: true });

const exportData = (format: 'pdf' | 'csv') => {
    const url = route('reports.export', { type: 'sales', ...form.value, format });
    window.open(url, '_blank');
};

// Summary Logic
const summary = computed(() => {
    return props.data.reduce((acc, d) => ({
        omset: acc.omset + d.sys_total,
        sys_liter: acc.sys_liter + d.sys_liter,
        diff_liter: acc.diff_liter + d.diff_liter,
        phys_cash: acc.phys_cash + d.phys_cash,
        sys_backdate: acc.sys_backdate + (d.sys_backdate || 0), // Hitung total backdate
    }), { omset: 0, sys_liter: 0, diff_liter: 0, phys_cash: 0, sys_backdate: 0 });
});

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date: string) => new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
</script>

<template>
    <Head title="Rekonsiliasi Penjualan" />
    <AdminLayout>

        <div class="mb-6 flex flex-col xl:flex-row justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">Laporan Penjualan Lengkap</h2>
                <p class="text-sm text-gray-500">Rekonsiliasi Liter (Fisik vs Sistem) & Setoran Uang.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="w-32"><DatePicker v-model="form.start_date" /></div>
                <span class="text-gray-400">-</span>
                <div class="w-32"><DatePicker v-model="form.end_date" /></div>
                <div class="w-48">
                    <SelectInput
                        v-model="form.product_id"
                        :options="[
                            { value: '', label: 'Semua Produk' },
                            ...products.map(p => ({value: p.id, label: p.name}))
                        ]"
                    />
                </div>

                <div class="h-8 w-px bg-gray-300 dark:bg-gray-700 mx-2"></div>

                <Button size="sm" variant="outline" @click="exportData('csv')">CSV</Button>
                <Button size="sm" variant="primary" @click="exportData('pdf')">PDF</Button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <MetricCard title="Total Omset (Gross)" :value="formatRupiah(summary.omset)" color="success" :icon="DocsIcon" />
            <MetricCard title="Uang Fisik (Laci)" :value="formatRupiah(summary.phys_cash)" color="primary" :icon="PlugInIcon" />
            <MetricCard title="Transaksi Lampau (Backdate)" :value="formatRupiah(summary.sys_backdate)" color="warning" :icon="WarningIcon" />
            <MetricCard title="Selisih Liter Net" :value="summary.diff_liter.toLocaleString() + ' L'" :color="summary.diff_liter < -5 ? 'error' : 'success'" :icon="BoxCubeIcon" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-x-auto shadow-sm">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-100 dark:bg-gray-800 border-b dark:border-gray-700 uppercase text-[10px] text-gray-500 font-bold tracking-wider text-center">
                <tr>
                    <th rowspan="2" class="w-10 bg-gray-50 dark:bg-gray-800 sticky left-0 z-10"></th>
                    <th rowspan="2" class="px-4 py-2 border-r dark:border-gray-700 bg-gray-50 dark:bg-gray-800 sticky left-10 z-10 text-left">Tanggal</th>

                    <th colspan="3" class="px-4 py-2 border-r dark:border-gray-700 bg-blue-50 dark:bg-blue-900/20 text-blue-700">Volume (Liter)</th>
                    <th colspan="5" class="px-4 py-2 border-r dark:border-gray-700 bg-green-50 dark:bg-green-900/20 text-green-700">Omset Sistem (Rp)</th>
                    <th colspan="2" class="px-4 py-2 bg-orange-50 dark:bg-orange-900/20 text-orange-700">Realisasi Fisik (Shift)</th>
                </tr>
                <tr>
                    <th class="px-3 py-2 bg-blue-50/50">Fisik (Mesin)</th>
                    <th class="px-3 py-2 bg-blue-50/50">Sistem</th>
                    <th class="px-3 py-2 bg-blue-50/50 border-r dark:border-gray-700">Selisih</th>

                    <th class="px-3 py-2 bg-green-50/50">Cash</th>
                    <th class="px-3 py-2 bg-green-50/50">Transfer</th>
                    <th class="px-3 py-2 bg-green-50/50">Bon</th>
                    <th class="px-3 py-2 bg-yellow-50/50 text-yellow-700">Backdate</th>
                    <th class="px-3 py-2 bg-green-50/50 border-r dark:border-gray-700">Total</th>

                    <th class="px-3 py-2 bg-orange-50/50">Uang Laci</th>
                    <th class="px-3 py-2 bg-orange-50/50">Beda Kas</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                <template v-for="(day, idx) in data" :key="idx">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer transition" @click="toggleRow(day.date)">
                        <td class="px-2 text-center text-gray-400 sticky left-0 bg-white dark:bg-gray-900 z-10">
                            <component :is="expandedRows.includes(day.date) ? ChevronDownIcon : ChevronRightIcon" class="w-4 h-4" />
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-white border-r dark:border-gray-700 sticky left-10 bg-white dark:bg-gray-900 z-10">
                            {{ formatDate(day.date) }}
                        </td>

                        <td class="px-4 py-3 text-center">{{ day.phys_liter }}</td>
                        <td class="px-4 py-3 text-center font-bold">{{ day.sys_liter }}</td>
                        <td class="px-4 py-3 text-center border-r dark:border-gray-700">
                            <Badge :color="day.diff_liter === 0 ? 'success' : 'error'" size="sm">
                                {{ day.diff_liter > 0 ? '+' : '' }}{{ day.diff_liter }}
                            </Badge>
                        </td>

                        <td class="px-4 py-3 text-right">{{ formatRupiah(day.sys_cash) }}</td>
                        <td class="px-4 py-3 text-right">{{ formatRupiah(day.sys_transfer) }}</td>
                        <td class="px-4 py-3 text-right">{{ formatRupiah(day.sys_bon) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-yellow-600 bg-yellow-50/30">
                            {{ day.sys_backdate > 0 ? formatRupiah(day.sys_backdate) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold border-r dark:border-gray-700">{{ formatRupiah(day.sys_total) }}</td>

                        <td class="px-4 py-3 text-right font-bold text-gray-800 dark:text-white">{{ formatRupiah(day.phys_cash) }}</td>
                        <td class="px-4 py-3 text-center">
                                <span :class="day.diff_cash < 0 ? 'text-red-500 font-bold' : 'text-green-500'">
                                    {{ formatRupiah(day.diff_cash) }}
                                </span>
                        </td>
                    </tr>

                    <tr v-if="expandedRows.includes(day.date)" class="bg-gray-50/50 dark:bg-gray-800/30 animate-fade-in">
                        <td colspan="12" class="p-4 pl-12"> <div class="flex flex-col lg:flex-row gap-6">
                            <div class="w-full lg:w-1/2">
                                <h4 class="font-bold text-gray-500 text-xs mb-2 flex items-center gap-2">
                                    <BoxCubeIcon class="w-4 h-4"/> DETAIL SHIFT (FISIK)
                                </h4>
                                <table class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2">Produk</th>
                                        <th class="px-3 py-2 text-right">Awal</th>
                                        <th class="px-3 py-2 text-right">Akhir</th>
                                        <th class="px-3 py-2 text-right">Terjual (L)</th>
                                        <th class="px-3 py-2 text-right">Uang Laci</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <template v-for="s in day.shifts" :key="s.id">
                                        <tr class="border-b dark:border-gray-700">
                                            <td class="px-3 py-2">{{ s.product?.name }}</td>
                                            <td class="px-3 py-2 text-right">{{ s.opening_totalizer }}</td>
                                            <td class="px-3 py-2 text-right">{{ s.closing_totalizer }}</td>
                                            <td class="px-3 py-2 text-right font-bold">{{ s.total_sales_liter }}</td>
                                            <td class="px-3 py-2 text-right">{{ formatRupiah(s.cash_collected) }}</td>
                                        </tr>
                                        <tr v-if="s.is_audited && s.owner_note" class="bg-yellow-50 dark:bg-yellow-900/10 border-b border-yellow-100 dark:border-yellow-800/30">
                                            <td colspan="5" class="px-3 py-2 text-[10px] italic text-yellow-700 dark:text-yellow-400">
                                                <span class="font-bold uppercase">[Audit Owner]:</span> {{ s.owner_note }}
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-if="!day.shifts.length">
                                        <td colspan="5" class="px-3 py-4 text-center text-gray-400 italic">Tidak ada data shift fisik</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="w-full lg:w-1/2">
                                <h4 class="font-bold text-gray-500 text-xs mb-2 flex items-center gap-2">
                                    <DocsIcon class="w-4 h-4"/> DETAIL TRANSAKSI (SISTEM)
                                </h4>
                                <table class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2">Waktu</th>
                                        <th class="px-3 py-2">Pelanggan</th>
                                        <th class="px-3 py-2">Metode</th>
                                        <th class="px-3 py-2 text-right">Total (Rp)</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="t in day.transactions" :key="t.id" class="border-b dark:border-gray-700" :class="{'bg-yellow-50/50 dark:bg-yellow-900/10': t.is_backdate}">
                                        <td class="px-3 py-2 flex items-center gap-2">
                                            {{ new Date(t.transaction_date).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) }}
                                            <span v-if="t.is_backdate" class="px-1.5 py-0.5 text-[10px] bg-yellow-200 text-yellow-800 rounded font-bold" title="Transaksi Lampau">BKD</span>
                                        </td>
                                        <td class="px-3 py-2">{{ t.customer?.ship_name || 'Umum' }}</td>
                                        <td class="px-3 py-2 uppercase text-xs font-bold">{{ t.payment_method }}</td>
                                        <td class="px-3 py-2 text-right">{{ formatRupiah(t.grand_total) }}</td>
                                    </tr>
                                    <tr v-if="!day.transactions.length">
                                        <td colspan="4" class="px-3 py-4 text-center text-gray-400 italic">Tidak ada transaksi sistem</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-in; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
</style>
