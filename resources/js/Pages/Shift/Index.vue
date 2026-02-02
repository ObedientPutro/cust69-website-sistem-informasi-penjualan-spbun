<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { debounce, pickBy } from 'lodash';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Button from '@/Components/Ui/Button.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import DatePicker from '@/Components/FormElements/DatePicker.vue';
import FileDropzone from '@/Components/FormElements/FileDropzone.vue';
import Badge from '@/Components/Ui/Badge.vue';
import Modal from '@/Components/Ui/Modal.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';

const props = defineProps<{
    products: any[];
    activeShifts: Record<string, any>;
    history: any; // Paginator Object
    filters: any; // Filter Object
}>();

const swal = useSweetAlert();

// --- CONFIG DATA TABLE ---
const columns = [
    { label: 'Waktu / Tanggal', key: 'opened_at', sortable: true, align: 'left' },
    { label: 'Produk', key: 'product', sortable: false, align: 'left' },
    { label: 'Petugas', key: 'opener', sortable: false, align: 'left' },
    { label: 'Meter Awal', key: 'opening_totalizer', sortable: true, align: 'right' },
    { label: 'Meter Akhir', key: 'closing_totalizer', sortable: true, align: 'right' },
    { label: 'Terjual (L)', key: 'total_sales_liter', sortable: true, align: 'right' },
    { label: 'Status', key: 'status', sortable: true, align: 'center' },
];

const filterForm = ref({
    search: props.filters.search || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    product_id: props.filters.product_id || '',
});

const applyFilter = debounce(() => {
    router.get(route('shifts.index'), pickBy(filterForm.value), {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

watch(filterForm, () => applyFilter(), { deep: true });

// --- FORM & MODAL LOGIC (Tetap Sama) ---
const openForm = useForm({
    product_id: '',
    opening_totalizer: '',
    opening_proof: null as File | null,
});

const closeForm = useForm({
    closing_totalizer: '',
    cash_collected: '',
    closing_proof: null as File | null,
});

const isOpenModalVisible = ref(false);
const isCloseModalVisible = ref(false);
const selectedProduct = ref<any>(null);
const selectedShift = ref<any>(null);

const handleOpenClick = (product: any) => {
    selectedProduct.value = product;
    openForm.reset();
    openForm.product_id = product.id;
    isOpenModalVisible.value = true;
};

const handleCloseClick = (product: any, shift: any) => {
    selectedProduct.value = product;
    selectedShift.value = shift;
    closeForm.reset();
    isCloseModalVisible.value = true;
};

const submitOpen = () => {
    openForm.post(route('shifts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isOpenModalVisible.value = false;
            swal.toast('Shift Berhasil Dibuka!', 'success');
        },
        onError: () => swal.toast('Gagal membuka shift', 'error')
    });
};

const submitClose = () => {
    if (!selectedShift.value) return;

    closeForm.post(route('shifts.close', selectedShift.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isCloseModalVisible.value = false;
            swal.toast('Shift Ditutup & Data Tersimpan', 'success');
        },
        onError: () => swal.toast('Gagal menutup shift', 'error')
    });
};

// --- HELPER ---
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('id-ID', {
        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'
    });
};

const formatNumber = (num: number | string) => {
    return Number(num).toLocaleString('id-ID');
};
</script>

<template>
    <Head title="Operasional Shift" />
    <AdminLayout>

        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Kontrol Shift Pompa</h2>
            <p class="text-sm text-gray-500">Kelola buka/tutup shift untuk setiap produk BBM.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
            <div v-for="product in products" :key="product.id"
                 class="relative rounded-2xl border transition-all duration-300 shadow-sm overflow-hidden flex flex-col"
                 :class="activeShifts[product.id]
                    ? 'border-green-200 bg-green-50/30 dark:border-green-800/50 dark:bg-green-900/10'
                    : 'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900'"
            >
                <div class="px-5 py-3 border-b flex justify-between items-center"
                     :class="activeShifts[product.id] ? 'border-green-100 bg-green-100/50 dark:border-green-800 dark:bg-green-900/20' : 'border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-800'"
                >
                    <h3 class="font-bold text-lg text-gray-800 dark:text-white">{{ product.name }}</h3>
                    <Badge :color="activeShifts[product.id] ? 'success' : 'secondary'">
                        {{ activeShifts[product.id] ? 'SHIFT OPEN' : 'CLOSED' }}
                    </Badge>
                </div>

                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div v-if="activeShifts[product.id]" class="space-y-4">
                        <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                            <img :src="activeShifts[product.id].opener?.photo_url" class="h-10 w-10 rounded-full bg-gray-200 object-cover">
                            <div>
                                <p class="text-xs text-gray-500 uppercase font-bold">Dibuka Oleh</p>
                                <p class="text-sm font-medium dark:text-gray-200">{{ activeShifts[product.id].opener?.name }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/10 rounded-lg border border-blue-100 dark:border-blue-800">
                                <span class="text-xs text-blue-600 dark:text-blue-400 block mb-1">Jam Buka</span>
                                <span class="font-mono font-bold text-gray-800 dark:text-white">
                                    {{ new Date(activeShifts[product.id].opened_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) }}
                                </span>
                            </div>
                            <div class="p-3 bg-orange-50 dark:bg-orange-900/10 rounded-lg border border-orange-100 dark:border-orange-800">
                                <span class="text-xs text-orange-600 dark:text-orange-400 block mb-1">Meter Awal</span>
                                <span class="font-mono font-bold text-gray-800 dark:text-white">
                                    {{ formatNumber(activeShifts[product.id].opening_totalizer) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-gray-400">
                        <svg class="mx-auto h-12 w-12 opacity-50 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <p class="text-sm">Pompa terkunci. <br>Silakan buka shift untuk memulai.</p>
                    </div>
                    <div class="mt-6">
                        <Button
                            v-if="activeShifts[product.id]"
                            @click="handleCloseClick(product, activeShifts[product.id])"
                            variant="danger"
                            class="w-full justify-center"
                        >
                            Tutup Shift (Closing)
                        </Button>
                        <Button
                            v-else
                            @click="handleOpenClick(product)"
                            variant="primary"
                            class="w-full justify-center"
                        >
                            Buka Shift Baru
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Riwayat Aktivitas Shift</h3>
        </div>

        <DataTable
            :rows="history.data"
            :columns="columns"
            :pagination="history"
            :filters="filters"
            :enable-actions="false"
        >
            <template #filters>
                <div class="flex flex-col md:flex-row gap-3 w-full">
                    <div class="flex gap-2 w-full md:w-auto">
                        <div class="w-32"><DatePicker v-model="filterForm.start_date" placeholder="Mulai" /></div>
                        <div class="w-32"><DatePicker v-model="filterForm.end_date" placeholder="Sampai" /></div>
                    </div>

                    <div class="w-full md:w-48">
                        <SelectInput v-model="filterForm.product_id" class="text-sm w-full">
                            <option value="">Semua Produk</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </SelectInput>
                    </div>
                </div>
            </template>

            <template #cell-opened_at="{ row }">
                <div class="text-sm">
                    <span class="block font-medium text-gray-800 dark:text-white">{{ formatDate(row.opened_at) }}</span>
                    <span v-if="row.closed_at" class="text-xs text-gray-500">
                        Tutup: {{ formatDate(row.closed_at) }}
                    </span>
                </div>
            </template>

            <template #cell-product="{ row }">
                <span class="font-bold text-gray-700 dark:text-gray-300">{{ row.product?.name }}</span>
            </template>

            <template #cell-opener="{ row }">
                <div class="flex flex-col text-xs gap-1">
                    <div class="flex items-center gap-1">
                        <span class="w-8 text-gray-400">Buka:</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ row.opener?.name }}</span>
                    </div>
                    <div v-if="row.closer" class="flex items-center gap-1">
                        <span class="w-8 text-gray-400">Tutup:</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ row.closer?.name }}</span>
                    </div>
                </div>
            </template>

            <template #cell-opening_totalizer="{ row }">
                <span class="font-mono text-gray-600 dark:text-gray-400">{{ formatNumber(row.opening_totalizer) }}</span>
            </template>

            <template #cell-closing_totalizer="{ row }">
                <span class="font-mono text-gray-600 dark:text-gray-400">{{ row.closing_totalizer ? formatNumber(row.closing_totalizer) : '-' }}</span>
            </template>

            <template #cell-total_sales_liter="{ row }">
                <span v-if="row.total_sales_liter > 0" class="font-bold font-mono text-gray-800 dark:text-white">
                    {{ formatNumber(row.total_sales_liter) }}
                </span>
                <span v-else class="-">-</span>
            </template>

            <template #cell-status="{ row }">
                <Badge :color="row.status === 'open' ? 'success' : 'secondary'" class="text-[10px] uppercase">
                    {{ row.status }}
                </Badge>
            </template>
        </DataTable>

        <Modal :show="isOpenModalVisible" title="Buka Shift Operasional" @close="isOpenModalVisible = false">
            <form @submit.prevent="submitOpen" class="space-y-4">
                <div class="bg-blue-50 p-4 rounded-xl mb-4 dark:bg-blue-900/20">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        Anda akan membuka shift untuk produk: <br>
                        <span class="font-bold text-lg">{{ selectedProduct?.name }}</span>
                    </p>
                </div>
                <TextInput v-model="openForm.opening_totalizer" type="number" step="0.01" label="Angka Meteran Awal" required :error="openForm.errors.opening_totalizer" />
                <FileDropzone v-model="openForm.opening_proof" label="Foto Bukti Meteran (Opsional)" accept="image/*" />
                <div class="pt-4 border-t dark:border-gray-700 flex justify-end gap-3">
                    <Button type="button" variant="outline" @click="isOpenModalVisible = false">Batal</Button>
                    <Button type="submit" :processing="openForm.processing">Buka Shift</Button>
                </div>
            </form>
        </Modal>

        <Modal :show="isCloseModalVisible" title="Tutup Shift (Closing)" @close="isCloseModalVisible = false">
            <form @submit.prevent="submitClose" class="space-y-4">
                <div class="bg-orange-50 p-4 rounded-xl mb-4 dark:bg-orange-900/20">
                    <p class="text-sm text-orange-800 dark:text-orange-300">
                        Menutup shift untuk: <strong>{{ selectedProduct?.name }}</strong><br>
                        Meter Awal: <span class="font-mono">{{ selectedShift?.opening_totalizer }}</span>
                    </p>
                </div>
                <TextInput v-model="closeForm.closing_totalizer" type="number" step="0.01" label="Angka Meteran Akhir (Wajib)" required :error="closeForm.errors.closing_totalizer" />
                <TextInput v-model="closeForm.cash_collected" type="number" label="Total Uang Fisik (Cash Drawer)" required :error="closeForm.errors.cash_collected" />
                <FileDropzone v-model="closeForm.closing_proof" label="Foto Bukti Meteran Akhir (Opsional)" accept="image/*" />
                <div class="pt-4 border-t dark:border-gray-700 flex justify-end gap-3">
                    <Button type="button" variant="outline" @click="isCloseModalVisible = false">Batal</Button>
                    <Button type="submit" variant="danger" :processing="closeForm.processing">Tutup & Simpan</Button>
                </div>
            </form>
        </Modal>

    </AdminLayout>
</template>

<style scoped>

</style>
