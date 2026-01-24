<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Modal from '@/Components/Ui/Modal.vue';
import Button from '@/Components/Ui/Button.vue';
import MetricCard from '@/Components/Metrics/MetricCard.vue';
import Badge from '@/Components/Ui/Badge.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';

const props = defineProps<{ debts: any; totalDebt: number; filters: any }>();
const swal = useSweetAlert();

// --- STATE PELUNASAN ---
const isPayModalOpen = ref(false);
const selectedTransaction = ref<any>(null);

const form = useForm({
    repayment_method: 'cash',
    payment_proof: null as File | null,
});

const columns = [
    { label: 'Tgl Transaksi', key: 'transaction_date', sortable: true, align: 'left' },
    { label: 'Pelanggan', key: 'customer_name', sortable: false, align: 'left' },
    { label: 'Total Tagihan', key: 'grand_total', sortable: true, align: 'right' },
    { label: 'Status', key: 'payment_status', sortable: false, align: 'center' },
];

const openPayModal = (row: any) => {
    selectedTransaction.value = row;
    form.reset();
    isPayModalOpen.value = true;
};

const submitRepayment = () => {
    form.post(route('debts.repay', selectedTransaction.value.id), {
        onSuccess: () => {
            isPayModalOpen.value = false;
            swal.toast('Pelunasan berhasil!', 'success');
        }
    });
};

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date: string) => new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
</script>

<template>
    <Head title="Pelunasan Bon" />
    <AdminLayout>

        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">Daftar Tagihan (Bon)</h2>
                <p class="text-sm text-gray-500">Cari dan proses pelunasan piutang pelanggan.</p>
            </div>
            <MetricCard title="Total Piutang Belum Lunas" :value="formatRupiah(totalDebt)" color="error" />
        </div>

        <DataTable :rows="debts.data" :columns="columns" :pagination="debts" :filters="filters">
            <template #cell-transaction_date="{ row }">
                {{ formatDate(row.transaction_date) }}
            </template>
            <template #cell-customer_name="{ row }">
                <span class="font-bold text-gray-800 dark:text-white">{{ row.customer?.name }}</span>
                <span class="block text-xs text-gray-500">{{ row.customer?.ship_name }}</span>
            </template>
            <template #cell-grand_total="{ row }">
                <span class="font-mono text-red-600 font-bold">{{ formatRupiah(row.grand_total) }}</span>
            </template>
            <template #cell-payment_status="{ row }">
                <Badge color="error" variant="light">Belum Lunas</Badge>
            </template>

            <template #actions-row="{ row }">
                <Button size="sm" variant="primary" @click="openPayModal(row)">
                    Bayar Lunas
                </Button>
            </template>
        </DataTable>

        <Modal :show="isPayModalOpen" title="Konfirmasi Pelunasan" maxWidth="md" @close="isPayModalOpen = false">
            <div class="mb-4 p-3 bg-gray-50 rounded border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm text-gray-500">Pelanggan: <b class="text-gray-800 dark:text-white">{{ selectedTransaction?.customer?.name }}</b></p>
                <p class="text-sm text-gray-500">Tagihan: <b class="text-red-600 text-lg">{{ selectedTransaction ? formatRupiah(selectedTransaction.grand_total) : 0 }}</b></p>
            </div>

            <form @submit.prevent="submitRepayment" class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Metode Pelunasan</label>
                    <select v-model="form.repayment_method" class="w-full rounded-lg border-gray-300 py-2.5 px-4 text-sm dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                        <option value="cash">Tunai (Cash)</option>
                        <option value="transfer">Transfer Bank</option>
                    </select>
                </div>

                <div v-if="form.repayment_method === 'transfer'">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Bukti Transfer (Opsional)</label>
                    <input type="file" @change="e => form.payment_proof = e.target.files[0]" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                    <Button type="button" variant="outline" @click="isPayModalOpen = false">Batal</Button>
                    <Button type="submit" :processing="form.processing">Proses Lunas</Button>
                </div>
            </form>
        </Modal>

    </AdminLayout>
</template>

<style scoped>

</style>
