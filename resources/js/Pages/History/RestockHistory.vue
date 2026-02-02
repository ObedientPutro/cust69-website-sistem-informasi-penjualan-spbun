<script setup lang="ts">
import { ref, watch, computed } from 'vue'; // Tambah computed
import { Head, router, usePage, useForm } from '@inertiajs/vue3'; // Tambah usePage, useForm
import { debounce } from 'lodash';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Button from '@/Components/Ui/Button.vue';
import DatePicker from '@/Components/FormElements/DatePicker.vue';
import MetricCard from '@/Components/Metrics/MetricCard.vue';
import Modal from '@/Components/Ui/Modal.vue'; // Import Modal
import TextInput from '@/Components/FormElements/TextInput.vue'; // Import Input
import SelectInput from '@/Components/FormElements/SelectInput.vue'; // Import Select
import TextArea from '@/Components/FormElements/TextArea.vue'; // Import Textarea
import { DocsIcon } from '@/Components/Icons';
import { useSweetAlert } from '@/Composables/useSweetAlert';
import DecimalInput from "@/Components/FormElements/DecimalInput.vue"; // Import Alert

const props = defineProps<{
    logs: any;
    summary: { total_volume: number; total_cost: number };
    products: any[];
    filters: any;
}>();

const page = usePage();
const swal = useSweetAlert();
const isOwner = computed(() => page.props.auth.user.role === 'owner');

// --- FILTER LOGIC (Tetap sama) ---
const filterForm = ref({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
    product_id: props.filters.product_id || '',
});
// ... (watcher applyFilter & exportData tetap sama) ...
const applyFilter = debounce(() => {
    router.get(route('restock-history.index'), filterForm.value, {
        preserveState: true, preserveScroll: true, replace: true
    });
}, 300);

watch(filterForm, () => applyFilter(), { deep: true });

const exportData = (format) => {
    const params = new URLSearchParams({ ...filterForm.value, format } as any).toString();
    window.location.href = route('restock-history.export') + '?' + params;
};

// --- EDIT LOGIC (BARU) ---
const isEditModalOpen = ref(false);
const editForm = useForm({
    id: null,
    date: '',
    product_id: '',
    volume_liter: '',
    total_cost: '',
    note: '',
});

const openEditModal = (row: any) => {
    editForm.id = row.id;
    editForm.date = row.date.split('T')[0]; // Ambil YYYY-MM-DD
    editForm.product_id = row.product_id;
    editForm.volume_liter = row.volume_liter;
    editForm.total_cost = row.total_cost;
    editForm.note = row.note;
    isEditModalOpen.value = true;
};

const submitEdit = () => {
    editForm.put(route('restock-history.update', editForm.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
            swal.toast('Data Restock Diperbarui', 'success');
        },
        onError: () => swal.toast('Gagal update', 'error')
    });
};

const columns = [
    { label: 'Tanggal Masuk', key: 'date', sortable: true, align: 'left' },
    { label: 'No. DO / Ref', key: 'note', sortable: false, align: 'left' },
    { label: 'Produk', key: 'product_name', sortable: false, align: 'left' },
    { label: 'Volume (L)', key: 'volume_liter', sortable: true, align: 'center' },
    { label: 'Total Harga', key: 'total_cost', sortable: true, align: 'right' },
    { label: 'Admin', key: 'user_name', sortable: false, align: 'left' },
    { label: 'Aksi', key: 'actions', sortable: false, align: 'right' }, // Kolom Aksi Baru
];

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (date: string) => new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
</script>

<template>
    <Head title="Riwayat Penebusan DO" />
    <AdminLayout>

        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Riwayat Penebusan DO</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <MetricCard title="Total Pembelian (Periode Ini)" :value="formatRupiah(summary.total_cost)" color="primary" :icon="DocsIcon">
                    <template #icon><svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg></template>
                </MetricCard>
                <MetricCard title="Total Volume Masuk" :value="summary.total_volume + ' Liter'" color="success">
                    <template #icon><svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg></template>
                </MetricCard>
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
                        <Button variant="outline" size="sm" @click="exportData('csv')">CSV</Button>
                        <Button variant="primary" size="sm" @click="exportData('pdf')">PDF</Button>
                    </div>
                </div>
            </template>

            <template #cell-date="{ row }"><span class="text-gray-600 dark:text-gray-300">{{ formatDate(row.date) }}</span></template>
            <template #cell-product_name="{ row }"><span class="font-bold text-gray-800 dark:text-white">{{ row.product?.name }}</span></template>
            <template #cell-total_cost="{ row }"><span class="font-mono text-gray-700 dark:text-gray-300">{{ formatRupiah(row.total_cost) }}</span></template>
            <template #cell-user_name="{ row }"><span class="text-xs bg-gray-100 px-2 py-1 rounded dark:bg-gray-800">{{ row.user?.name }}</span></template>

            <template #cell-actions="{ row }">
                <button
                    v-if="isOwner"
                    @click="openEditModal(row)"
                    class="rounded-lg border border-orange-200 bg-orange-50 p-2 text-orange-600 transition hover:bg-orange-100 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-400 dark:hover:bg-orange-500/20"
                    title="Edit Restock"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </button>
            </template>
        </DataTable>

        <Modal :show="isEditModalOpen" title="Edit Data Restock DO" @close="isEditModalOpen = false">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div class="p-3 bg-yellow-50 text-yellow-800 text-sm rounded-lg border border-yellow-200">
                    <strong>Perhatian:</strong> Mengubah Volume akan otomatis menghitung ulang stok produk saat ini (Rollback & Re-apply).
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <DatePicker v-model="editForm.date" label="Tanggal Masuk" disabled />

                    <SelectInput v-model="editForm.product_id" label="Produk" disabled>
                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </SelectInput>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <DecimalInput
                        v-model="editForm.volume_liter"
                        label="Volume (Liter)"
                        placeholder="0.000"
                        suffix="Liter"
                        required
                    />
                    <TextInput v-model="editForm.total_cost" type="number" label="Total Harga (Rp)" required />
                </div>

                <TextArea v-model="editForm.note" label="No. DO / Catatan" />

                <div class="flex justify-end gap-3 mt-4 border-t pt-4">
                    <Button type="button" variant="outline" @click="isEditModalOpen = false">Batal</Button>
                    <Button type="submit" variant="primary" :processing="editForm.processing">Simpan Perubahan</Button>
                </div>
            </form>
        </Modal>

    </AdminLayout>
</template>
