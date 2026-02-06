<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { debounce, pickBy } from 'lodash';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Modal from '@/Components/Ui/Modal.vue';
import Button from '@/Components/Ui/Button.vue';
import MetricCard from '@/Components/Metrics/MetricCard.vue';
import Badge from '@/Components/Ui/Badge.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import ImageViewerModal from '@/Components/Ui/ImageViewerModal.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import DatePicker from '@/Components/FormElements/DatePicker.vue';
import FileDropzone from '@/Components/FormElements/FileDropzone.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';
import DecimalInput from "@/Components/FormElements/DecimalInput.vue";
import IntegerInput from "@/Components/FormElements/IntegerInput.vue";

const props = defineProps<{
    debts: any;
    products: any[];
    totalUnpaid: number;
    totalBonFilter: number;
    filters: any
}>();

const page = usePage();
const swal = useSweetAlert();
const isOwner = computed(() => page.props.auth.user.role === 'owner');

// --- FILTERS ---
const filterForm = ref({
    search: props.filters.search || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    product_id: props.filters.product_id || '',
    payment_status: props.filters.payment_status || '',
});

const applyFilter = debounce(() => {
    router.get(route('debts.index'), pickBy(filterForm.value), {
        preserveState: true, preserveScroll: true, replace: true
    });
}, 500);

watch(filterForm, () => applyFilter(), { deep: true });

// --- TABLE CONFIG ---
const columns = [
    { label: 'Waktu / Kode', key: 'transaction_date', sortable: true, align: 'left' },
    { label: 'Pelanggan', key: 'customer_name', sortable: false, align: 'left' },
    { label: 'Detail Item', key: 'items', sortable: false, align: 'left' },
    { label: 'Total Tagihan', key: 'grand_total', sortable: true, align: 'right' },
    { label: 'Status', key: 'payment_status', sortable: false, align: 'center' },
    { label: 'Aksi', key: 'actions', sortable: false, align: 'right' },
];

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'unpaid', label: 'Belum Lunas' },
    { value: 'paid', label: 'Lunas' },
];

// --- MODAL STATES ---
const isPayModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedTransaction = ref<any>(null);

// Form Bayar
const payForm = useForm({
    repayment_method: 'cash',
    payment_proof: null as File | null,
    _method: 'post',
});

// Form Edit (Sama dengan Transaction History)
const editForm = useForm({
    transaction_date: '',
    items: [] as any[],
    note: '',
});

// --- ACTIONS: PAY ---
const openPayModal = (row: any) => {
    selectedTransaction.value = row;
    payForm.reset();
    isPayModalOpen.value = true;
};

const submitRepayment = () => {
    payForm.post(route('debts.repay', selectedTransaction.value.id), {
        onSuccess: () => {
            isPayModalOpen.value = false;
            swal.toast('Pelunasan berhasil!', 'success');
        },
        preserveScroll: true
    });
};

// --- ACTIONS: EDIT (Owner Only) ---
const openEditModal = (trx: any) => {
    selectedTransaction.value = trx;
    editForm.transaction_date = trx.transaction_date;
    editForm.note = trx.note || '';
    editForm.items = trx.items.map((i: any) => ({
        product_id: i.product_id,
        quantity_liter: i.quantity_liter,
        price: i.price_per_liter // Display purpose
    }));
    isEditModalOpen.value = true;
};

const submitEdit = () => {
    // Kita gunakan route update milik Transaction karena Debt = Transaction
    editForm.put(route('transactions.update', selectedTransaction.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            swal.toast('Transaksi Bon direvisi', 'success');
        },
        onError: () => swal.toast('Gagal update', 'error')
    });
};

const isViewerOpen = ref(false);
const viewerUrl = ref<string | null>(null);
const viewerAlt = ref('');

const openProofViewer = (url: string, customerName: string) => {
    viewerUrl.value = url;
    viewerAlt.value = `Bukti Transfer - ${customerName}`;
    isViewerOpen.value = true;
};

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date: string) => new Date(date).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: '2-digit', hour: '2-digit', minute:'2-digit' });
</script>

<template>
    <Head title="Manajemen Piutang" />
    <AdminLayout>

        <div class="mb-6 flex flex-col xl:flex-row justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">Manajemen Piutang (Bon)</h2>
                <p class="text-sm text-gray-500">Monitoring dan pelunasan transaksi piutang pelanggan.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="w-32"><DatePicker v-model="filterForm.start_date" placeholder="Mulai" /></div>
                <span class="text-gray-400">-</span>
                <div class="w-32"><DatePicker v-model="filterForm.end_date" placeholder="Sampai" /></div>

                <div class="w-40">
                    <SelectInput v-model="filterForm.product_id" class="text-sm">
                        <option value="">Semua Produk</option>
                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </SelectInput>
                </div>

                <div class="w-32">
                    <SelectInput v-model="filterForm.payment_status" class="text-sm">
                        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </SelectInput>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <MetricCard
                title="Sisa Piutang (Belum Lunas)"
                :value="formatRupiah(totalUnpaid)"
                color="error"
            >
                <template #icon>
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </template>
            </MetricCard>
            <MetricCard
                title="Total Nilai Bon (Filter Ini)"
                :value="formatRupiah(totalBonFilter)"
                color="warning"
            >
                <template #icon>
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </template>
            </MetricCard>
        </div>

        <DataTable :rows="debts.data" :columns="columns" :pagination="debts" :filters="filters" :enableActions="false">
            <template #cell-transaction_date="{ row }">
                <div class="text-sm">
                    <span class="font-bold text-brand-600 dark:text-brand-400 block">{{ row.trx_code }}</span>
                    <span class="text-xs text-gray-500">{{ formatDate(row.transaction_date) }}</span>
                </div>
            </template>

            <template #cell-customer_name="{ row }">
                <div v-if="row.customer" class="flex flex-col">
                    <span class="font-bold text-gray-800 dark:text-white">{{ row.customer.name }}</span>
                    <span class="text-xs text-gray-500 italic">{{ row.customer.ship_name }}</span>
                </div>
                <span v-else class="text-gray-400 italic">Umum</span>
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

            <template #cell-payment_status="{ row }">
                <Badge :color="row.payment_status === 'paid' ? 'success' : 'error'" variant="light">
                    {{ row.payment_status === 'paid' ? 'Lunas' : 'Belum Lunas' }}
                </Badge>
                <div v-if="row.paid_at" class="mt-1 text-[10px] text-green-600">
                    Paid: {{ new Date(row.paid_at).toLocaleDateString('id-ID') }}
                </div>
            </template>

            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-2">
                    <button
                        v-if="row.payment_proof_url"
                        @click="openProofViewer(row.payment_proof_url, row.customer?.name || 'Pelanggan')"
                        class="rounded-lg border border-purple-200 bg-purple-50 p-2 text-purple-600 transition hover:bg-purple-100 dark:border-purple-500/20 dark:bg-purple-500/10 dark:text-purple-400 dark:hover:bg-purple-500/20"
                        title="Lihat Bukti Transfer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </button>

                    <button
                        v-if="isOwner && row.payment_status !== 'returned'"
                        @click="openEditModal(row)"
                        class="rounded-lg border border-orange-200 bg-orange-50 p-2 text-orange-600 transition hover:bg-orange-100 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-400 dark:hover:bg-orange-500/20"
                        title="Edit Data Bon"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </button>

                    <Button
                        v-if="row.payment_status === 'unpaid'"
                        size="sm"
                        variant="primary"
                        @click="openPayModal(row)"
                    >
                        <template #startIcon>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </template>
                        Bayar
                    </Button>
                </div>
            </template>
        </DataTable>

        <ImageViewerModal
            :show="isViewerOpen"
            :image-src="viewerUrl"
            :alt-text="viewerAlt"
            @close="isViewerOpen = false"
        />

        <Modal :show="isPayModalOpen" title="Konfirmasi Pelunasan" maxWidth="md" @close="isPayModalOpen = false">
            <div class="mb-5 p-4 bg-gray-50 rounded-xl border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-500">Pelanggan</span>
                    <span class="font-bold text-gray-800 dark:text-white">{{ selectedTransaction?.customer?.name }}</span>
                </div>
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600 flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Tagihan</span>
                    <span class="text-xl font-extrabold text-red-600">
                        {{ selectedTransaction ? formatRupiah(selectedTransaction.grand_total) : 0 }}
                    </span>
                </div>
            </div>

            <form @submit.prevent="submitRepayment" class="space-y-5">
                <SelectInput v-model="payForm.repayment_method" label="Metode Pelunasan">
                    <option value="cash">Tunai (Cash)</option>
                    <option value="transfer">Transfer Bank</option>
                </SelectInput>

                <div v-if="payForm.repayment_method === 'transfer'" class="animate-fade-in">
                    <FileDropzone v-model="payForm.payment_proof" label="Bukti Transfer (Wajib)" accept="image/*" :error="payForm.errors.payment_proof" />
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 dark:border-gray-700 pt-4">
                    <Button type="button" variant="outline" @click="isPayModalOpen = false">Batal</Button>
                    <Button type="submit" variant="primary" :processing="payForm.processing">Proses Lunas</Button>
                </div>
            </form>
        </Modal>

        <Modal :show="isEditModalOpen" title="Edit Transaksi Bon (Owner)" @close="isEditModalOpen = false">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div class="p-3 bg-yellow-50 text-yellow-800 text-sm rounded-lg dark:bg-yellow-900/20 dark:text-yellow-200">
                    <strong>Perhatian:</strong> Mengubah jumlah liter akan otomatis menyesuaikan limit kredit pelanggan dan stok produk.
                </div>
                <div v-for="(item, index) in editForm.items" :key="index" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border dark:border-gray-700">
                    <div class="flex justify-between text-sm font-bold mb-2">
                        <span>{{ products.find(p => p.id == item.product_id)?.name || 'Produk' }}</span>
                    </div>
                    <IntegerInput
                        v-model="item.quantity_liter"
                        label="Volume (Liter)"
                        placeholder="0"
                        suffix="Liter"
                        required
                    />
                </div>
                <TextArea v-model="editForm.note" label="Catatan Revisi" placeholder="Alasan perubahan..." />
                <div class="flex justify-end gap-3 mt-4">
                    <Button type="button" variant="outline" @click="isEditModalOpen = false">Batal</Button>
                    <Button type="submit" :processing="editForm.processing">Simpan Perubahan</Button>
                </div>
            </form>
        </Modal>

    </AdminLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-in; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>
