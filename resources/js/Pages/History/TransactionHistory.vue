<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { debounce, pickBy } from 'lodash';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Button from '@/Components/Ui/Button.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import ImageViewerModal from '@/Components/Ui/ImageViewerModal.vue';
import DatePicker from '@/Components/FormElements/DatePicker.vue';
import MetricCard from '@/Components/Metrics/MetricCard.vue';
import Badge from '@/Components/Ui/Badge.vue';
import Modal from '@/Components/Ui/Modal.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';
import IntegerInput from "@/Components/FormElements/IntegerInput.vue";

const props = defineProps<{
    transactions: any;
    products: any[];
    filters: any;
    summary: {
        total_omset: number;
        total_transaksi: number;
        total_void_count: number;
        total_void_amount: number;
    };
}>();

const page = usePage();
const swal = useSweetAlert();
const isOwner = computed(() => page.props.auth.user.role === 'owner');

// --- FILTER STATE ---
const filterForm = ref({
    search: props.filters.search || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    product_id: props.filters.product_id || '',
    payment_status: props.filters.payment_status || '',
    payment_method: props.filters.payment_method || '',
});

// Debounce khusus Search agar tidak terlalu sering reload
const applySearch = debounce(() => {
    applyFilter();
}, 500);

const applyFilter = () => {
    router.get(route('history.transactions.index'), pickBy(filterForm.value), {
        preserveState: true, preserveScroll: true, replace: true
    });
};

// Watchers
watch(() => filterForm.value.search, applySearch);
watch(() => [
    filterForm.value.start_date,
    filterForm.value.end_date,
    filterForm.value.product_id,
    filterForm.value.payment_status,
    filterForm.value.payment_method
], applyFilter);

watch(() => props.filters, (newFilters) => {
    filterForm.value.search = newFilters.search || '';
    filterForm.value.start_date = newFilters.start_date || '';
    filterForm.value.end_date = newFilters.end_date || '';
    filterForm.value.product_id = newFilters.product_id || '';
    filterForm.value.payment_status = newFilters.payment_status || '';
    filterForm.value.payment_method = newFilters.payment_method || '';
}, { deep: true });

// --- TABLE COLUMNS ---
const columns = [
    { label: 'Kode / Waktu', key: 'transaction_date', sortable: true, align: 'left' },
    { label: 'Pelanggan', key: 'customer', sortable: false, align: 'left' },
    { label: 'Detail Item', key: 'items', sortable: false, align: 'left' },
    { label: 'Total', key: 'grand_total', sortable: true, align: 'right' },
    { label: 'Metode', key: 'payment_method', sortable: false, align: 'center' },
    { label: 'Status', key: 'payment_status', sortable: false, align: 'center' },
];

const paymentMethodOptions = [
    { value: '', label: 'Semua Metode' },
    { value: 'cash', label: 'Cash' },
    { value: 'transfer', label: 'Transfer' },
    { value: 'bon', label: 'Bon' },
];

const paymentStatusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'paid', label: 'Lunas' },
    { value: 'unpaid', label: 'Belum Lunas' },
    { value: 'returned', label: 'Return' },
];

// --- EXPORT ---
const exportData = (format: 'csv' | 'pdf') => {
    const params = new URLSearchParams({ ...pickBy(filterForm.value), format } as any).toString();
    window.open(route('history.transactions.export') + '?' + params, '_blank');
};

// --- MODAL & FORM LOGIC (Edit/Return) ---
const isEditModalOpen = ref(false);
const isReturnModalOpen = ref(false);
const selectedTrx = ref<any>(null);

const editForm = useForm({
    transaction_date: '',
    items: [] as any[],
    note: '',
});

const returnForm = useForm({ reason: '' });

const openEditModal = (trx: any) => {
    selectedTrx.value = trx;
    editForm.transaction_date = trx.transaction_date;
    editForm.note = trx.note || '';
    editForm.items = trx.items.map((i: any) => ({
        product_id: i.product_id,
        quantity_liter: i.quantity_liter,
        price: i.price_per_liter
    }));
    isEditModalOpen.value = true;
};

const submitEdit = () => {
    editForm.put(route('transactions.update', selectedTrx.value.id), {
        preserveScroll: true,
        onSuccess: () => { isEditModalOpen.value = false; swal.toast('Transaksi direvisi', 'success'); },
        onError: () => swal.toast('Gagal update', 'error')
    });
};

const openReturnModal = (trx: any) => {
    selectedTrx.value = trx;
    returnForm.reset();
    isReturnModalOpen.value = true;
};

const submitReturn = () => {
    swal.confirm('Batalkan Transaksi?', 'Stok akan dikembalikan.', 'Ya, kembalikan').then((r) => {
        if (r.isConfirmed) {
            returnForm.post(route('transactions.return', selectedTrx.value.id), {
                preserveScroll: true,
                onSuccess: () => { isReturnModalOpen.value = false; swal.toast('Transaksi dibatalkan', 'success'); }
            });
        }
    });
};

const isViewerOpen = ref(false);
const viewerUrl = ref<string | null>(null);
const viewerAlt = ref('');

const openProofViewer = (url: string, trxCode: string) => {
    viewerUrl.value = url;
    viewerAlt.value = `Bukti Transfer - ${trxCode}`;
    isViewerOpen.value = true;
};

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date: string) => new Date(date).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: '2-digit', hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <Head title="Mutasi Transaksi" />
    <AdminLayout>

        <div class="mb-6 flex flex-col xl:flex-row justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">Mutasi Transaksi</h2>
                <p class="text-sm text-gray-500">Rekapitulasi data penjualan, edit, dan return.</p>
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
                        <option v-for="opt in paymentStatusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </SelectInput>
                </div>

                <div class="w-40 hidden md:block">
                    <SelectInput v-model="filterForm.payment_method" class="text-sm">
                        <option v-for="opt in paymentMethodOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </SelectInput>
                </div>

                <div class="hidden md:block h-9 w-px bg-gray-300 dark:bg-gray-700 mx-1"></div>

                <Button size="sm" variant="outline" @click="exportData('csv')">CSV</Button>
                <Button size="sm" variant="primary" @click="exportData('pdf')">PDF</Button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
            <MetricCard title="Omset Bersih (Valid)" :value="formatRupiah(summary.total_omset)" color="success">
                <template #icon>
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </template>
            </MetricCard>

            <MetricCard title="Total Transaksi" :value="summary.total_transaksi" color="primary">
                <template #icon>
                    <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </template>
            </MetricCard>

            <MetricCard
                title="Batal / Return"
                :value="summary.total_void_count + ' Trx'"
                :trend-label="'Total: ' + formatRupiah(summary.total_void_amount)"
                color="error"
            >
                <template #icon>
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </template>
            </MetricCard>
        </div>

        <DataTable
            :rows="transactions.data"
            :columns="columns"
            :pagination="transactions"
            :filters="filters"
            :enableActions="true"
            :searchInfo="'Cari Kode TRX atau Nama Kapal'"
        >
            <template #cell-transaction_date="{ row }">
                <div class="text-sm">
                    <span class="font-bold text-brand-600 dark:text-brand-400 block">{{ row.trx_code }}</span>
                    <span class="text-xs text-gray-500">{{ formatDate(row.transaction_date) }}</span>
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
                <Badge :color="row.payment_status === 'paid' ? 'success' : (row.payment_status === 'returned' ? 'warning' : 'error')" variant="light">
                    {{ row.payment_status === 'paid' ? 'Lunas' : (row.payment_status === 'returned' ? 'Void/Return' : 'Belum Lunas') }}
                </Badge>
                <div v-if="row.paid_at && row.payment_method === 'bon'" class="mt-1 text-[10px] text-green-600">
                    Paid: {{ new Date(row.paid_at).toLocaleDateString('id-ID') }}
                </div>
            </template>

            <template #actions-row="{ row }">
                <div class="flex justify-end items-center gap-2">
                    <button
                        v-if="row.payment_proof_url"
                        @click="openProofViewer(row.payment_proof_url, row.trx_code)"
                        class="rounded-lg border border-purple-200 bg-purple-50 p-2 text-purple-600 transition hover:bg-purple-100 dark:border-purple-500/20 dark:bg-purple-500/10 dark:text-purple-400 dark:hover:bg-purple-500/20"
                        title="Lihat Bukti Transfer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </button>

                    <a
                        :href="route('transactions.print', row.id)"
                        target="_blank"
                        class="rounded-lg border border-blue-200 bg-blue-50 p-2 text-blue-600 transition hover:bg-blue-100 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                        title="Cetak Struk"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    </a>

                    <template v-if="isOwner && row.payment_status !== 'returned'">
                        <button
                            @click="openEditModal(row)"
                            class="rounded-lg border border-orange-200 bg-orange-50 p-2 text-orange-600 transition hover:bg-orange-100 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-400 dark:hover:bg-orange-500/20"
                            title="Edit Transaksi"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>

                        <button
                            @click="openReturnModal(row)"
                            class="rounded-lg border border-red-200 bg-red-50 p-2 text-red-600 transition hover:bg-red-100 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                            title="Return / Void"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </button>
                    </template>
                </div>
            </template>
        </DataTable>

        <ImageViewerModal
            :show="isViewerOpen"
            :image-src="viewerUrl"
            :alt-text="viewerAlt"
            @close="isViewerOpen = false"
        />

        <Modal :show="isEditModalOpen" title="Edit Transaksi (Owner)" @close="isEditModalOpen = false">
            <form @submit.prevent="submitEdit" class="space-y-4">
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

        <Modal :show="isReturnModalOpen" title="Return / Void Transaksi" @close="isReturnModalOpen = false">
            <form @submit.prevent="submitReturn" class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-300">Konfirmasi pembatalan transaksi? Stok akan dikembalikan.</p>
                <TextArea v-model="returnForm.reason" label="Alasan (Wajib)" required />
                <div class="flex justify-end gap-3 mt-4">
                    <Button type="button" variant="outline" @click="isReturnModalOpen = false">Batal</Button>
                    <Button type="submit" variant="danger" :processing="returnForm.processing">Konfirmasi</Button>
                </div>
            </form>
        </Modal>

    </AdminLayout>
</template>
