<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Button from '@/Components/Ui/Button.vue';
import DatePicker from '@/Components/FormElements/DatePicker.vue';
import Badge from '@/Components/Ui/Badge.vue';

const props = defineProps<{
    logs: any;
    products: any[];
    filters: any;
}>();

const filterForm = ref({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
    product_id: props.filters.product_id || '',
});

const columns = [
    { label: 'Waktu Cek', key: 'recorded_at', sortable: true, align: 'left' },
    { label: 'Produk', key: 'product_name', sortable: false, align: 'left' },
    { label: 'Stok Sistem', key: 'system_liter_snapshot', sortable: false, align: 'center' },
    { label: 'Stok Fisik', key: 'physical_liter', sortable: false, align: 'center' },
    { label: 'Selisih (L)', key: 'difference', sortable: true, align: 'center' }, // Highlight
    { label: 'Petugas', key: 'user_name', sortable: false, align: 'left' },
];

const applyFilter = debounce(() => {
    router.get(route('sounding-history.index'), filterForm.value, {
        preserveState: true, preserveScroll: true, replace: true
    });
}, 300);

watch(filterForm, () => applyFilter(), { deep: true });

const exportData = (format) => {
    const params = new URLSearchParams({ ...filterForm.value, format }).toString();
    window.location.href = route('sounding-history.export') + '?' + params;
};
const formatDateTime = (date: string) => new Date(date).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });

const getDifferenceStatus = (diff: number, systemStock: number) => {
    if (diff > 0) return 'success';
    const lossPercentage = Math.abs(diff) / (systemStock || 1);

    if (lossPercentage > 0.005) return 'error';
    return 'warning';
};
</script>

<template>
    <Head title="Riwayat Audit Tangki" />
    <AdminLayout>

        <div class="mb-6 flex justify-between items-end">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">Riwayat Sounding (Audit)</h2>
                <p class="text-sm text-gray-500">Log kontrol kualitas dan audit stok fisik tangki.</p>
            </div>
        </div>

        <DataTable :rows="logs.data" :columns="columns" :pagination="logs" :filters="filters" :enableActions="false">
            <template #filters>
                <div class="flex flex-col sm:flex-row gap-3 w-full justify-between items-end">
                    <div class="flex gap-2 w-full sm:w-auto">
                        <div class="w-36"><DatePicker v-model="filterForm.start_date" placeholder="Start" /></div>
                        <div class="w-36"><DatePicker v-model="filterForm.end_date" placeholder="End" /></div>
                        <div class="w-48">
                            <select v-model="filterForm.product_id" class="w-full rounded-lg border-gray-300 bg-transparent py-2.5 px-4 text-sm focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900">
                                <option value="">Semua Produk</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="exportData('csv')"
                            title="Download Excel/CSV"
                        >
                            <template #startIcon>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </template>
                            CSV
                        </Button>
                        <Button
                            variant="primary"
                            size="sm"
                            @click="exportData('pdf')"
                            title="Download PDF"
                        >
                            <template #startIcon>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </template>
                            PDF
                        </Button>
                    </div>
                </div>
            </template>

            <template #cell-recorded_at="{ row }">
                <span class="text-gray-600 dark:text-gray-300">{{ formatDateTime(row.recorded_at) }}</span>
            </template>
            <template #cell-product_name="{ row }">
                <span class="font-medium text-gray-800 dark:text-white">{{ row.product?.name }}</span>
            </template>

            <template #cell-system_liter_snapshot="{ row }">
                <span class="text-gray-500">{{ row.system_liter_snapshot }}</span>
            </template>
            <template #cell-physical_liter="{ row }">
                <span class="font-bold text-gray-800 dark:text-white">{{ row.physical_liter }}</span>
            </template>

            <template #cell-difference="{ row }">
                <Badge
                    :color="getDifferenceStatus(Number(row.difference), Number(row.system_liter_snapshot))"
                    size="sm"
                    variant="light"
                >
                    {{ Number(row.difference) > 0 ? '+' : '' }}{{ row.difference }} L
                </Badge>
            </template>

            <template #cell-user_name="{ row }">
                <span class="text-xs bg-gray-100 px-2 py-1 rounded dark:bg-gray-800">{{ row.user?.name }}</span>
            </template>
        </DataTable>
    </AdminLayout>
</template>

<style scoped>

</style>
