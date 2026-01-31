<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DatePicker from '@/Components/FormElements/DatePicker.vue';
import Button from '@/Components/Ui/Button.vue';
import MetricCard from '@/Components/Metrics/MetricCard.vue';
import Badge from '@/Components/Ui/Badge.vue';

const props = defineProps<{
    data: any[];
    filters: any;
}>();

const form = ref({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

// Auto-reload saat tanggal diubah
watch(form, () => {
    router.get(route('reports.sales'), form.value, {
        preserveState: true,
        replace: true
    });
}, { deep: true });

const exportData = (format: 'pdf' | 'csv') => {
    const url = route('reports.export', { type: 'sales', ...form.value, format });
    window.location.href = url;
};

// Hitung Summary Cepat
const summary = computed(() => {
    return {
        total_omset: props.data.reduce((sum, item) => sum + Number(item.grand_total), 0),
        total_qty: props.data.reduce((sum, item) => {
            // Sum quantity dari nested items
            return sum + item.items.reduce((q: number, i: any) => q + Number(i.quantity_liter), 0);
        }, 0),
        transaction_count: props.data.length
    };
});

// Helpers
const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
const formatDate = (date: string) => new Date(date).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <Head title="Laporan Penjualan" />
    <AdminLayout>

        <div class="mb-6 flex flex-col md:flex-row justify-between items-end gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">Laporan Penjualan</h2>
                <p class="text-sm text-gray-500">Rekapitulasi detail transaksi per nota.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="w-36"><DatePicker v-model="form.start_date" placeholder="Mulai" /></div>
                <span class="text-gray-400">-</span>
                <div class="w-36"><DatePicker v-model="form.end_date" placeholder="Sampai" /></div>

                <div class="h-8 w-px bg-gray-300 dark:bg-gray-700 mx-2"></div>

                <Button size="sm" variant="outline" @click="exportData('csv')">CSV</Button>
                <Button size="sm" variant="primary" @click="exportData('pdf')">
                    <template #startIcon>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    </template>
                    PDF
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <MetricCard
                title="Total Omset"
                :value="formatRupiah(summary.total_omset)"
                color="success"
            />
            <MetricCard
                title="Total Volume Terjual"
                :value="summary.total_qty + ' Liter'"
                color="primary"
            />
            <MetricCard
                title="Jumlah Transaksi"
                :value="summary.transaction_count"
                color="warning"
            />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 border-b dark:border-gray-700 uppercase text-xs text-gray-500 font-semibold">
                    <tr>
                        <th class="px-6 py-4">Waktu</th>
                        <th class="px-6 py-4">No. Nota</th>
                        <th class="px-6 py-4">Pelanggan</th>
                        <th class="px-6 py-4">Rincian Item</th>
                        <th class="px-6 py-4 text-center">Metode</th>
                        <th class="px-6 py-4 text-right">Total</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr v-for="(row, idx) in data" :key="idx" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300">
                            {{ formatDate(row.transaction_date) }}
                        </td>
                        <td class="px-6 py-4 font-mono text-xs text-gray-500">
                            #{{ row.id }}
                        </td>
                        <td class="px-6 py-4">
                            <div v-if="row.customer">
                                <p class="font-bold text-gray-800 dark:text-white">{{ row.customer.name }}</p>
                                <p class="text-xs text-gray-500">{{ row.customer.ship_name }}</p>
                            </div>
                            <span v-else class="text-gray-400 italic">Umum (Cash)</span>
                        </td>
                        <td class="px-6 py-4">
                            <ul class="space-y-1">
                                <li v-for="item in row.items" :key="item.id" class="text-xs flex justify-between gap-4 w-40">
                                    <span class="text-gray-700 dark:text-gray-300">{{ item.product?.name }}</span>
                                    <span class="font-mono text-gray-500">{{ item.quantity_liter }}L</span>
                                </li>
                            </ul>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <Badge variant="light" color="gray" class="uppercase text-[10px]">
                                {{ row.payment_method }}
                            </Badge>
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-gray-800 dark:text-white">
                            {{ formatRupiah(row.grand_total) }}
                        </td>
                    </tr>
                    <tr v-if="data.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            Tidak ada data penjualan pada periode ini.
                        </td>
                    </tr>
                    </tbody>
                    <tfoot v-if="data.length > 0" class="bg-gray-50 dark:bg-gray-800 border-t dark:border-gray-700 font-bold">
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-right">GRAND TOTAL</td>
                        <td class="px-6 py-4 text-right text-brand-600 text-lg">
                            {{ formatRupiah(summary.total_omset) }}
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </AdminLayout>
</template>

<style scoped>

</style>
