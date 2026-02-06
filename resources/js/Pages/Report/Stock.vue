<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DatePicker from '@/Components/FormElements/DatePicker.vue';
import Button from '@/Components/Ui/Button.vue';
import MetricCard from '@/Components/Metrics/MetricCard.vue';
import Badge from '@/Components/Ui/Badge.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import { RefreshIcon, BoxIcon, DocsIcon } from '@/Components/Icons';

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

// Auto-reload
watch(form, () => {
    router.get(route('reports.stock'), form.value, {
        preserveState: true,
        replace: true
    });
}, { deep: true });

const exportData = (format: 'pdf' | 'csv') => {
    const url = route('reports.export', { type: 'stock', ...form.value, format });
    window.location.href = url;
};

// Hitung Summary Stok
const summary = computed(() => {
    const totalIn = props.data.reduce((sum, item) => sum + Number(item.qty_in), 0);
    const totalOut = props.data.reduce((sum, item) => sum + Number(item.qty_out), 0);
    return {
        in: totalIn,
        out: totalOut,
        net: totalIn - totalOut // Surplus/Defisit Periode Ini
    };
});

const formatDate = (date: string) => new Date(date).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <Head title="Laporan Arus Stok" />
    <AdminLayout>
        <div class="mb-6 flex flex-col md:flex-row justify-between items-end gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">Laporan Arus Stok (Stock Flow)</h2>
                <p class="text-sm text-gray-500">Mutasi barang masuk (Restock) dan keluar (Penjualan).</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="w-36"><DatePicker v-model="form.start_date" placeholder="Mulai" /></div>
                <span class="text-gray-400">-</span>
                <div class="w-36"><DatePicker v-model="form.end_date" placeholder="Sampai" /></div>

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
                    <template #startIcon>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    </template>
                    PDF
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <MetricCard
                title="Total Masuk (Restock)"
                :value="summary.in + ' L'"
                color="success"
                :icon="RefreshIcon"
            >
                <template #icon><span class="text-2xl">📥</span></template>
            </MetricCard>
            <MetricCard
                title="Total Keluar (Jual)"
                :value="summary.out + ' L'"
                color="warning"
                :icon="BoxIcon"
            >
                <template #icon><span class="text-2xl">📤</span></template>
            </MetricCard>
            <MetricCard
                title="Selisih (Net Flow)"
                :value="(summary.net > 0 ? '+' : '') + summary.net + ' L'"
                :color="summary.net >= 0 ? 'primary' : 'error'"
                :icon="DocsIcon"
            />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 border-b dark:border-gray-700 uppercase text-xs text-gray-500 font-semibold">
                    <tr>
                        <th class="px-6 py-4">Waktu</th>
                        <th class="px-6 py-4">Tipe Mutasi</th>
                        <th class="px-6 py-4">Produk</th>
                        <th class="px-6 py-4">Pelanggan</th>
                        <th class="px-6 py-4">Referensi</th>
                        <th class="px-6 py-4 text-center text-green-600 bg-green-50 dark:bg-green-900/10">Masuk</th>
                        <th class="px-6 py-4 text-center text-orange-600 bg-orange-50 dark:bg-orange-900/10">Keluar</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr v-for="(row, idx) in data" :key="idx" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300">
                            {{ formatDate(row.date) }}
                        </td>
                        <td class="px-6 py-4">
                            <Badge
                                :color="row.qty_in > 0 ? 'success' : 'warning'"
                                variant="light"
                            >
                                {{ row.type }}
                            </Badge>
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-800 dark:text-white">
                            {{ row.product_name }}
                        </td>
                        <td class="px-6 py-4">
                            {{ row.customer_name }}
                        </td>
                        <td class="px-6 py-4 font-mono text-xs text-gray-500">
                            {{ row.ref }}
                        </td>
                        <td class="px-6 py-4 text-center bg-green-50/30 dark:bg-green-900/5">
                            <span v-if="row.qty_in > 0" class="font-bold text-green-600">+{{ row.qty_in }}</span>
                            <span v-else class="text-gray-300">-</span>
                        </td>
                        <td class="px-6 py-4 text-center bg-orange-50/30 dark:bg-orange-900/5">
                            <span v-if="row.qty_out > 0" class="font-bold text-orange-600">-{{ row.qty_out }}</span>
                            <span v-else class="text-gray-300">-</span>
                        </td>
                    </tr>
                    <tr v-if="data.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            Tidak ada pergerakan stok pada periode ini.
                        </td>
                    </tr>
                    </tbody>
                    <tfoot v-if="data.length > 0" class="bg-gray-50 dark:bg-gray-800 border-t dark:border-gray-700 font-bold">
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-right">TOTAL VOLUME</td>
                        <td class="px-6 py-4 text-center text-green-600 bg-green-50 dark:bg-green-900/20">{{ summary.in }}</td>
                        <td class="px-6 py-4 text-center text-orange-600 bg-orange-50 dark:bg-orange-900/20">{{ summary.out }}</td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </AdminLayout>
</template>

<style scoped>

</style>
