<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { debounce, pickBy } from 'lodash';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Button from '@/Components/Ui/Button.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import DatePicker from '@/Components/FormElements/DatePicker.vue';
import FileDropzone from '@/Components/FormElements/FileDropzone.vue';
import Badge from '@/Components/Ui/Badge.vue';
import Modal from '@/Components/Ui/Modal.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';
import CurrencyInput from "@/Components/FormElements/CurrencyInput.vue";

const props = defineProps<{
    products: any[];
    activeShifts: Record<string, any>;
    history: any;
    filters: any;
}>();

const page = usePage();
const swal = useSweetAlert();
const isOwner = computed(() => page.props.auth.user.role === 'owner');

// --- CONFIG DATA TABLE ---
const columns = [
    { label: 'Waktu / Tanggal', key: 'opened_at', sortable: true, align: 'left' },
    { label: 'Produk', key: 'product', sortable: false, align: 'center' },
    { label: 'Petugas', key: 'opener', sortable: false, align: 'left' },
    { label: 'Meteran', key: 'totalizers', sortable: false, align: 'left' },
    { label: 'Rekonsiliasi (Fisik vs Sys)', key: 'reconciliation', sortable: false, align: 'center' },
    { label: 'Status', key: 'status', sortable: true, align: 'center' },
    { label: 'Aksi', key: 'actions', sortable: false, align: 'center' },
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
    opening_totalizer: 0,
    opening_proof: null as File | null,
});

const closeForm = useForm({
    closing_totalizer: 0,
    cash_collected: 0,
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

const isAuditModalVisible = ref(false);
const auditForm = useForm({ owner_note: '' });
const shiftToAudit = ref<any>(null);

const getDiff = (row: any) => {
    if (row.status !== 'closed') return 0;
    return Math.abs(parseFloat(row.total_sales_liter) - parseFloat(row.system_transaction_liter));
};

const needsAudit = (row: any) => {
    return row.status === 'closed' && !row.is_audited && getDiff(row) > 1; // Toleransi 1 Liter
};

const openAuditModal = (row: any) => {
    shiftToAudit.value = row;
    auditForm.reset();
    isAuditModalVisible.value = true;
};

const submitAudit = () => {
    auditForm.put(route('shifts.audit', shiftToAudit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isAuditModalVisible.value = false;
            swal.toast('Shift berhasil diaudit', 'success');
        }
    });
};

const isNoteModalVisible = ref(false);
const selectedNote = ref('');
const selectedShiftInfo = ref('');

const openNoteModal = (row: any) => {
    selectedNote.value = row.owner_note;
    selectedShiftInfo.value = `${row.product?.name} (${formatDate(row.opened_at)})`;
    isNoteModalVisible.value = true;
};

// --- HELPER ---
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('id-ID', {
        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'
    });
};

const formatNumber = (num: number | string) => {
    if (num === null || num === undefined || num === '') return '-';

    // 1. Pastikan nilai adalah angka float
    const value = typeof num === 'string' ? parseFloat(num) : num;

    // 2. Format ID tanpa pemisah ribuan (useGrouping: false)
    // Contoh: 12345.50 -> "12345,50"
    return new Intl.NumberFormat('id-ID', {
        useGrouping: false // <--- INI KUNCINYA (Matikan Titik Ribuan)
    }).format(value);
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

        <DataTable :rows="history.data" :columns="columns" :pagination="history" :filters="filters" :enable-actions="false">
            <template #cell-opened_at="{ row }">
                <div class="text-sm">
                    <span class="block font-medium text-gray-800 dark:text-white">{{ formatDate(row.opened_at) }}</span>
                    <span v-if="row.closed_at" class="text-xs text-gray-500">Tutup: {{ formatDate(row.closed_at) }}</span>
                </div>
            </template>

            <template #cell-product="{ row }"><span class="font-bold text-gray-700 dark:text-gray-300">{{ row.product?.name }}</span></template>

            <template #cell-opener="{ row }">
                <div class="flex flex-col text-xs gap-1">
                    <div class="flex items-center gap-1"><span class="w-8 text-gray-400">Buka:</span><span class="font-medium">{{ row.opener?.name }}</span></div>
                    <div v-if="row.closer" class="flex items-center gap-1"><span class="w-8 text-gray-400">Tutup:</span><span class="font-medium">{{ row.closer?.name }}</span></div>
                </div>
            </template>

            <template #cell-totalizers="{ row }">
                <div class="text-xs text-right">
                    <div><span class="text-gray-400">Aw:</span> <span class="font-mono">{{ formatNumber(row.opening_totalizer) }}</span></div>
                    <div v-if="row.closing_totalizer"><span class="text-gray-400">Ak:</span> <span class="font-mono">{{ formatNumber(row.closing_totalizer) }}</span></div>
                </div>
            </template>

            <template #cell-reconciliation="{ row }">
                <div v-if="row.status === 'closed'" class="flex flex-col items-center gap-1.5 w-full">

                    <div class="flex justify-between items-center w-full text-xs px-2">
                        <div class="flex flex-col items-start">
                            <span class="text-[10px] text-gray-400 uppercase font-bold">Fisik (Mesin)</span>
                            <span class="font-bold text-gray-700 dark:text-gray-300" title="Meter Akhir - Awal">
                                {{ formatNumber(row.total_sales_liter) }}
                            </span>
                        </div>

                        <span class="text-gray-300 mx-1">vs</span>

                        <div class="flex flex-col items-end">
                            <span class="text-[10px] text-gray-400 uppercase font-bold">Sistem (POS)</span>
                            <span class="font-bold text-blue-600">
                                {{ formatNumber(row.system_transaction_liter) }}
                            </span>
                        </div>
                    </div>

                    <div v-if="Math.abs(getDiff(row)) > 1" class="w-full text-center">
                        <div class="inline-flex items-center gap-1 px-2 py-1 rounded border text-xs font-bold w-full justify-center"
                             :class="getDiff(row) > 0
                                ? 'bg-orange-50 text-orange-700 border-orange-200'
                                : 'bg-red-50 text-red-700 border-red-200'">

                            <span v-if="getDiff(row) > 0">⚠ Lebih: {{ formatNumber(getDiff(row)) }} L</span>
                            <span v-else>⚠ Kurang: {{ formatNumber(Math.abs(getDiff(row))) }} L</span>
                        </div>
                    </div>

                    <div v-else class="w-full text-center">
                        <div class="inline-flex items-center gap-1 px-2 py-1 rounded bg-green-50 border border-green-200 text-green-700 text-xs font-bold w-full justify-center">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Match
                        </div>
                    </div>

                </div>
                <span v-else class="text-xs text-gray-400 italic">Shift Berjalan...</span>
            </template>

            <template #cell-status="{ row }">
                <div class="flex flex-col gap-1 items-center">
                    <Badge :color="row.status === 'open' ? 'success' : 'secondary'" class="text-[10px] uppercase">{{ row.status }}</Badge>
                    <Badge v-if="row.is_audited" color="primary" class="text-[10px]">Audited</Badge>
                </div>
            </template>

            <template #cell-actions="{ row }">
                <Button
                    v-if="isOwner && needsAudit(row)"
                    variant="danger"
                    @click="openAuditModal(row)"
                    class="whitespace-nowrap"
                >
                    ⚠ Audit Sekarang
                </Button>
                <button
                    v-if="row.is_audited"
                    @click="openNoteModal(row)"
                    class="flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition"
                    title="Lihat Catatan Audit"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Lihat Catatan
                </button>
            </template>
        </DataTable>

        <Modal :show="isNoteModalVisible" title="Catatan Audit Owner" @close="isNoteModalVisible = false" maxWidth="md">
            <div class="space-y-4">
                <div class="bg-gray-50 p-3 rounded-lg border dark:bg-gray-800 dark:border-gray-700">
                    <p class="text-xs text-gray-500 uppercase font-bold">Shift</p>
                    <p class="font-medium text-gray-800 dark:text-white">{{ selectedShiftInfo }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">Isi Catatan:</label>
                    <div class="p-4 bg-yellow-50 text-yellow-900 rounded-xl border border-yellow-200 text-sm leading-relaxed whitespace-pre-wrap dark:bg-yellow-900/20 dark:text-yellow-100 dark:border-yellow-800">
                        {{ selectedNote }}
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <Button type="button" variant="outline" @click="isNoteModalVisible = false">Tutup</Button>
                </div>
            </div>
        </Modal>

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
                <TextInput
                    v-model="closeForm.closing_totalizer"
                    type="number"
                    step="0.01"
                    label="Angka Meteran Akhir (Wajib)"
                    required
                    :error="closeForm.errors.closing_totalizer"
                />
                <CurrencyInput
                    v-model="closeForm.cash_collected"
                    label="Total Uang Fisik / Uang Cash Penjualan Shift Sekarang"
                    prefix="Rp"
                    required
                    :error="closeForm.errors.cash_collected"
                />
                <FileDropzone v-model="closeForm.closing_proof" label="Foto Bukti Meteran Akhir (Opsional)" accept="image/*" />
                <div class="pt-4 border-t dark:border-gray-700 flex justify-end gap-3">
                    <Button type="button" variant="outline" @click="isCloseModalVisible = false">Batal</Button>
                    <Button type="submit" variant="danger" :processing="closeForm.processing">Tutup & Simpan</Button>
                </div>
            </form>
        </Modal>

        <Modal :show="isAuditModalVisible" title="Audit Selisih Shift" @close="isAuditModalVisible = false">
            <div v-if="shiftToAudit" class="space-y-4">
                <div class="bg-red-50 p-4 rounded-xl border border-red-100 dark:bg-red-900/20 dark:border-red-800">
                    <h4 class="font-bold text-red-800 dark:text-red-300 mb-2">Terdeteksi Selisih Volume!</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="block text-gray-500">Penjualan Fisik (Mesin):</span>
                            <span class="font-mono font-bold text-gray-800 dark:text-white text-lg">{{ formatNumber(shiftToAudit.total_sales_liter) }} L</span>
                        </div>
                        <div>
                            <span class="block text-gray-500">Tercatat di Sistem:</span>
                            <span class="font-mono font-bold text-blue-600 text-lg">{{ formatNumber(shiftToAudit.system_transaction_liter) }} L</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-red-200 dark:border-red-700 flex justify-between items-center">
                        <span class="text-red-700 font-bold">Total Selisih:</span>
                        <span class="text-xl font-black text-red-600">{{ formatNumber(getDiff(shiftToAudit)) }} Liter</span>
                    </div>
                </div>

                <form @submit.prevent="submitAudit" class="space-y-4">
                    <TextArea
                        v-model="auditForm.owner_note"
                        label="Catatan Audit (Wajib)"
                        placeholder="Jelaskan penyebab selisih (misal: kebocoran selang, tera ulang, atau kelalaian petugas)..."
                        required
                        rows="3"
                        :error="auditForm.errors.owner_note"
                    />

                    <div class="flex justify-end gap-3 pt-2">
                        <Button type="button" variant="outline" @click="isAuditModalVisible = false">Batal</Button>
                        <Button type="submit" variant="primary" :processing="auditForm.processing">Simpan & Selesaikan Audit</Button>
                    </div>
                </form>
            </div>
        </Modal>

    </AdminLayout>
</template>

<style scoped>

</style>
