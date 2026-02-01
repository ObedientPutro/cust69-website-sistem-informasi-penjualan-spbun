<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DatePicker from '@/Components/FormElements/DatePicker.vue';
import Button from '@/Components/Ui/Button.vue';
import MetricCard from '@/Components/Metrics/MetricCard.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import { PieChartIcon, InfoCircleIcon, BarChartIcon } from '@/Components/Icons';

const props = defineProps<{
    data: any[];
    filters: any;
    products: any[];
}>();

const form = ref({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
    product_id: props.filters.product_id || '',
});

// Update data saat tanggal berubah
watch(form, () => {
    router.get(route('reports.profit'), form.value, { preserveState: true, replace: true });
}, { deep: true });

const exportData = (format: 'pdf' | 'csv') => {
    const url = route('reports.export', { type: 'profit', ...form.value, format });
    window.location.href = url;
};

// Hitung Total Footer
const totals = computed(() => {
    return props.data.reduce((acc, row) => ({
        omzet: acc.omzet + row.omzet,
        hpp: acc.hpp + row.hpp,
        profit: acc.profit + row.gross_profit
    }), { omzet: 0, hpp: 0, profit: 0 });
});

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
const formatDate = (date: string) => new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
</script>

<template>
    <Head title="Laporan Laba Rugi" />
    <AdminLayout>

        <div class="mb-6 flex flex-col md:flex-row justify-between items-end gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">Laporan Laba Rugi (Gross Profit)</h2>
                <p class="text-sm text-gray-500">Analisis keuntungan kotor berdasarkan selisih Harga Jual dan HPP.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="w-36"><DatePicker v-model="form.start_date" /></div>
                <span class="text-gray-400">-</span>
                <div class="w-36"><DatePicker v-model="form.end_date" /></div>

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
                <Button size="sm" variant="primary" @click="exportData('pdf')">
                    <template #startIcon><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg></template>
                    PDF
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <MetricCard title="Total Omset" :value="formatRupiah(totals.omzet)" color="primary" :icon="PieChartIcon" />
            <MetricCard title="Total HPP (Modal)" :value="formatRupiah(totals.hpp)" color="warning" :icon="BarChartIcon" />
            <MetricCard title="Laba Kotor" :value="formatRupiah(totals.profit)" color="success" :icon="InfoCircleIcon" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 border-b dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3 font-semibold text-gray-700 dark:text-gray-300">Tanggal</th>
                    <th class="px-6 py-3 font-semibold text-gray-700 dark:text-gray-300 text-right">Omset</th>
                    <th class="px-6 py-3 font-semibold text-gray-700 dark:text-gray-300 text-right">HPP</th>
                    <th class="px-6 py-3 font-semibold text-gray-700 dark:text-gray-300 text-right">Laba</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="(row, idx) in data" :key="idx" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-6 py-3">{{ formatDate(row.date) }}</td>
                    <td class="px-6 py-3 text-right">{{ formatRupiah(row.omzet) }}</td>
                    <td class="px-6 py-3 text-right text-red-500">({{ formatRupiah(row.hpp) }})</td>
                    <td class="px-6 py-3 text-right font-bold text-green-600">{{ formatRupiah(row.gross_profit) }}</td>
                </tr>
                <tr v-if="data.length === 0">
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">Tidak ada data pada periode ini.</td>
                </tr>
                </tbody>
                <tfoot v-if="data.length > 0" class="bg-gray-50 dark:bg-gray-800 font-bold border-t dark:border-gray-700">
                <tr>
                    <td class="px-6 py-3">TOTAL</td>
                    <td class="px-6 py-3 text-right">{{ formatRupiah(totals.omzet) }}</td>
                    <td class="px-6 py-3 text-right text-red-600">({{ formatRupiah(totals.hpp) }})</td>
                    <td class="px-6 py-3 text-right text-green-600">{{ formatRupiah(totals.profit) }}</td>
                </tr>
                </tfoot>
            </table>
        </div>

    </AdminLayout>
</template>

<style scoped>

</style>
