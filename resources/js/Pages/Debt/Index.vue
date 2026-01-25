<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Modal from '@/Components/Ui/Modal.vue';
import Button from '@/Components/Ui/Button.vue';
import MetricCard from '@/Components/Metrics/MetricCard.vue';
import Badge from '@/Components/Ui/Badge.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import FileDropzone from '@/Components/FormElements/FileDropzone.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';

const props = defineProps<{ debts: any; totalDebt: number; filters: any }>();
const swal = useSweetAlert();

const isPayModalOpen = ref(false);
const selectedTransaction = ref<any>(null);

const form = useForm({
    repayment_method: 'cash',
    payment_proof: null as File | null,
    _method: 'post',
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
    form.payment_proof = null;
    isPayModalOpen.value = true;
};

const submitRepayment = () => {
    form.post(route('debts.repay', selectedTransaction.value.id), {
        onSuccess: () => {
            isPayModalOpen.value = false;
            swal.toast('Pelunasan berhasil!', 'success');
        },
        preserveScroll: true
    });
};

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date: string) => new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
</script>

<template>
    <Head title="Manajemen Piutang" />
    <AdminLayout>

        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            <MetricCard
                title="Total Piutang (Belum Lunas)"
                :value="formatRupiah(totalDebt)"
                color="error"
            >
                <template #icon>
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </template>
            </MetricCard>
        </div>

        <div class="flex justify-between items-end mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">Daftar Tagihan</h2>
                <p class="text-sm text-gray-500">Monitoring transaksi bon pelanggan.</p>
            </div>
        </div>

        <DataTable :rows="debts.data" :columns="columns" :pagination="debts" :filters="filters">
            <template #cell-transaction_date="{ row }">
                <span class="text-gray-600 dark:text-gray-300">{{ formatDate(row.transaction_date) }}</span>
            </template>
            <template #cell-customer_name="{ row }">
                <div class="flex flex-col">
                    <span class="font-bold text-gray-800 dark:text-white">{{ row.customer?.name }}</span>
                    <span class="text-xs text-gray-500 italic">{{ row.customer?.ship_name }}</span>
                </div>
            </template>
            <template #cell-grand_total="{ row }">
                <span class="font-mono text-red-600 font-bold bg-red-50 px-2 py-1 rounded dark:bg-red-900/20">
                    {{ formatRupiah(row.grand_total) }}
                </span>
            </template>
            <template #cell-payment_status="{ row }">
                <Badge color="error" variant="light">Belum Lunas</Badge>
            </template>

            <template #actions-row="{ row }">
                <Button size="sm" variant="primary" @click="openPayModal(row)">
                    <template #startIcon>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </template>
                    Bayar
                </Button>
            </template>
        </DataTable>

        <Modal :show="isPayModalOpen" title="Konfirmasi Pelunasan" maxWidth="md" @close="isPayModalOpen = false">

            <div class="mb-5 p-4 bg-gray-50 rounded-xl border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-500">Pelanggan</span>
                    <span class="font-bold text-gray-800 dark:text-white">{{ selectedTransaction?.customer?.name }}</span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-500">Tanggal Bon</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        {{ selectedTransaction ? formatDate(selectedTransaction.transaction_date) : '-' }}
                    </span>
                </div>
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600 flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Tagihan</span>
                    <span class="text-xl font-extrabold text-red-600">
                        {{ selectedTransaction ? formatRupiah(selectedTransaction.grand_total) : 0 }}
                    </span>
                </div>
            </div>

            <form @submit.prevent="submitRepayment" class="space-y-5">
                <SelectInput
                    v-model="form.repayment_method"
                    label="Metode Pelunasan"
                >
                    <option value="cash">Tunai (Cash)</option>
                    <option value="transfer">Transfer Bank</option>
                </SelectInput>

                <div v-if="form.repayment_method === 'transfer'" class="animate-fade-in">
                    <FileDropzone
                        v-model="form.payment_proof"
                        label="Bukti Transfer (Wajib)"
                        accept="image/*"
                        :error="form.errors.payment_proof"
                    />
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 dark:border-gray-700 pt-4">
                    <Button type="button" variant="outline" @click="isPayModalOpen = false">Batal</Button>
                    <Button type="submit" variant="primary" :processing="form.processing">Proses Lunas</Button>
                </div>
            </form>
        </Modal>

    </AdminLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-in; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>
