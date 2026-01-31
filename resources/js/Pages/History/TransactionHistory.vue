<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { debounce, pickBy } from 'lodash';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Button from '@/Components/Ui/Button.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import DatePicker from '@/Components/FormElements/DatePicker.vue';
import MetricCard from '@/Components/Metrics/MetricCard.vue';
import Badge from '@/Components/Ui/Badge.vue';

const props = defineProps<{
    transactions: any;
    products: any[];
    filters: any;
    summary: { total_omset: number; total_transaksi: number };
}>();

const filterForm = ref({
search: props.filters.search || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    product_id: props.filters.product_id || '',
    payment_status: props.filters.payment_status || '',
    payment_method: props.filters.payment_method || '',
});

const columns = [
    { label: 'Waktu', key: 'transaction_date', sortable: true, align: 'left' },
    { label: 'Pelanggan', key: 'customer', sortable: false, align: 'left' },
    { label: 'Detail Item', key: 'items', sortable: false, align: 'left' },
    { label: 'Total', key: 'grand_total', sortable: true, align: 'right' },
    { label: 'Metode', key: 'payment_method', sortable: false, align: 'center' },
    { label: 'Status', key: 'payment_status', sortable: false, align: 'center' },
    { label: 'Kasir', key: 'user', sortable: false, align: 'left' },
];

const paymentMethodOptions = [
    { value: '', label: 'Semua Metode' },
    { value: 'cash', label: 'Cash (Tunai)' },
    { value: 'transfer', label: 'Transfer' },
    { value: 'bon', label: 'Bon (Piutang)' },
];

const paymentStatusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'paid', label: 'Lunas' },
    { value: 'unpaid', label: 'Belum Lunas' },
];

const applyFilter = debounce(() => {
    const params = pickBy(filterForm.value);

    router.get(route('history.transactions.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 500);

watch(filterForm, () => applyFilter(), { deep: true });

const exportData = (format: 'csv' | 'pdf') => {
    const cleanFilters = pickBy(filterForm.value);

    const params = new URLSearchParams({
        ...cleanFilters,
        format
    } as any).toString();

    window.location.href = route('history.transactions.export') + '?' + params;
};

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date: string) => new Date(date).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <Head title="Mutasi Transaksi" />
    <AdminLayout>

        <div class="mb-6 flex flex-col gap-4">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Mutasi Transaksi Penjualan</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <MetricCard
                    title="Total Omset (Filter Ini)"
                    :value="formatRupiah(summary.total_omset)"
                    color="success"
                >
                    <template #icon>
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </template>
                </MetricCard>
                <MetricCard
                    title="Total Transaksi"
                    :value="summary.total_transaksi"
                    color="primary"
                >
                    <template #icon>
                        <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </template>
                </MetricCard>
            </div>
        </div>

        <DataTable
            :rows="transactions.data"
            :columns="columns"
            :pagination="transactions"
            :filters="filters"
            :enableActions="false"
        >
            <template #filters>
                <div class="flex flex-col gap-4 w-full">
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="w-full md:w-40">
                            <DatePicker v-model="filterForm.start_date" placeholder="Dari Tanggal" />
                        </div>
                        <div class="w-full md:w-40">
                            <DatePicker v-model="filterForm.end_date" placeholder="Sampai Tanggal" />
                        </div>
                        <div class="w-full md:w-64">
                            <SelectInput v-model="filterForm.product_id" class="w-full rounded-lg border-gray-300 bg-transparent py-2.5 px-4 text-sm dark:bg-gray-900 dark:border-gray-700">
                                <option value="">Semua Produk</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </SelectInput>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row gap-3 justify-between items-center">
                        <div class="flex gap-3 w-full md:w-auto">
                            <SelectInput v-model="filterForm.payment_method" class="rounded-lg border-gray-300 text-sm dark:bg-gray-900 dark:border-gray-700">
                                <option v-for="opt in paymentMethodOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </SelectInput>
                            <SelectInput v-model="filterForm.payment_status" class="rounded-lg border-gray-300 text-sm dark:bg-gray-900 dark:border-gray-700">
                                <option v-for="opt in paymentStatusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </SelectInput>
                        </div>

                        <div class="flex gap-2">
                            <Button size="sm" variant="outline" @click="exportData('csv')">CSV</Button>
                            <Button size="sm" variant="primary" @click="exportData('pdf')">PDF</Button>
                        </div>
                    </div>
                </div>
            </template>

            <template #cell-transaction_date="{ row }">
                <div class="text-sm">
                    <span class="font-medium text-gray-800 dark:text-white">{{ formatDate(row.transaction_date) }}</span>
                    <div class="text-xs text-gray-500">#{{ row.id }}</div>
                </div>
            </template>

            <template #cell-customer="{ row }">
                <div v-if="row.customer">
                    <div class="font-bold text-gray-800 dark:text-white">{{ row.customer.name }}</div>
                    <div class="text-xs text-gray-500">{{ row.customer.ship_name }}</div>
                </div>
                <span v-else class="text-gray-400 italic">Umum (Cash)</span>
            </template>

            <template #cell-items="{ row }">
                <div class="flex flex-col gap-1">
                    <div v-for="item in row.items" :key="item.id" class="text-xs flex justify-between gap-4">
                        <span>{{ item.product?.name }}</span>
                        <span class="font-mono text-gray-500">{{ item.quantity_liter }}L</span>
                    </div>
                </div>
            </template>

            <template #cell-grand_total="{ row }">
                <span class="font-bold text-gray-800 dark:text-white">{{ formatRupiah(row.grand_total) }}</span>
            </template>

            <template #cell-payment_method="{ row }">
                <span class="uppercase text-xs font-bold text-gray-500">{{ row.payment_method }}</span>
            </template>

            <template #cell-payment_status="{ row }">
                <Badge :color="row.payment_status === 'paid' ? 'success' : 'error'" variant="light">
                    {{ row.payment_status === 'paid' ? 'Lunas' : 'Belum Lunas' }}
                </Badge>
                <div v-if="row.paid_at && row.payment_method === 'bon'" class="mt-1 text-[10px] text-green-600">
                    Paid: {{ new Date(row.paid_at).toLocaleDateString('id-ID') }}
                </div>
            </template>

            <template #cell-user="{ row }">
                <span class="text-xs bg-gray-100 px-2 py-1 rounded dark:bg-gray-800">{{ row.user?.name }}</span>
            </template>

        </DataTable>

    </AdminLayout>
</template>

<style scoped>

</style>
