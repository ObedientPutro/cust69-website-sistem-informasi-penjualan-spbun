<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, router, usePage, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Button from '@/Components/Ui/Button.vue';
import DatePicker from '@/Components/FormElements/DatePicker.vue';
import Badge from '@/Components/Ui/Badge.vue';
import Modal from '@/Components/Ui/Modal.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';
import DecimalInput from "@/Components/FormElements/DecimalInput.vue";

const props = defineProps<{
    logs: any;
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
// ... (Watcher & applyFilter sama) ...
const applyFilter = debounce(() => {
    router.get(route('sounding-history.index'), filterForm.value, {
        preserveState: true, preserveScroll: true, replace: true
    });
}, 300);

watch(filterForm, () => applyFilter(), { deep: true });

const exportData = (format) => {
    const params = new URLSearchParams({ ...filterForm.value, format } as any).toString();
    window.location.href = route('sounding-history.export') + '?' + params;
};

// --- EDIT LOGIC (BARU) ---
const isEditModalOpen = ref(false);
const editForm = useForm({
    id: null,
    recorded_at: '',
    time: '', // Helper untuk jam
    physical_height_cm: '',
    physical_liter: '',
});

const openEditModal = (row: any) => {
    editForm.id = row.id;
    const dt = new Date(row.recorded_at);
    editForm.recorded_at = row.recorded_at; // Simpan raw ISO
    // Untuk tampilan input (pisahkan date & time jika perlu, atau pakai DateTimePicker)
    // Disini saya asumsikan pakai text input datetime-local simpel atau manipulasi string
    // Kita pakai DatePicker (Date only) + Time Input manual agar mudah
    editForm.recorded_at = row.recorded_at.split('T')[0];
    editForm.time = dt.toTimeString().slice(0,5);

    editForm.physical_height_cm = row.physical_height_cm;
    editForm.physical_liter = row.physical_liter;
    isEditModalOpen.value = true;
};

const submitEdit = () => {
    // Gabungkan Date + Time
    const fullDateTime = `${editForm.recorded_at} ${editForm.time}:00`;

    editForm.transform((data) => ({
        ...data,
        recorded_at: fullDateTime
    })).put(route('sounding-history.update', editForm.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
            swal.toast('Audit Tangki Diperbarui', 'success');
        },
        onError: () => swal.toast('Gagal update', 'error')
    });
};

const columns = [
    { label: 'Waktu Cek', key: 'recorded_at', sortable: true, align: 'left' },
    { label: 'Produk', key: 'product_name', sortable: false, align: 'left' },
    { label: 'Stok Sistem', key: 'system_liter_snapshot', sortable: false, align: 'center' },
    { label: 'Stok Fisik', key: 'physical_liter', sortable: false, align: 'center' },
    { label: 'Selisih (L)', key: 'difference', sortable: true, align: 'center' },
    { label: 'Petugas', key: 'user_name', sortable: false, align: 'left' },
    { label: 'Aksi', key: 'actions', sortable: false, align: 'right' },
];

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
                        <Button variant="outline" size="sm" @click="exportData('csv')">CSV</Button>
                        <Button variant="primary" size="sm" @click="exportData('pdf')">PDF</Button>
                    </div>
                </div>
            </template>

            <template #cell-recorded_at="{ row }"><span class="text-gray-600 dark:text-gray-300">{{ formatDateTime(row.recorded_at) }}</span></template>
            <template #cell-product_name="{ row }"><span class="font-medium text-gray-800 dark:text-white">{{ row.product?.name }}</span></template>
            <template #cell-system_liter_snapshot="{ row }"><span class="text-gray-500">{{ row.system_liter_snapshot }}</span></template>
            <template #cell-physical_liter="{ row }"><span class="font-bold text-gray-800 dark:text-white">{{ row.physical_liter }}</span></template>
            <template #cell-difference="{ row }">
                <Badge :color="getDifferenceStatus(Number(row.difference), Number(row.system_liter_snapshot))" size="sm" variant="light">
                    {{ Number(row.difference) > 0 ? '+' : '' }}{{ row.difference }} L
                </Badge>
            </template>
            <template #cell-user_name="{ row }"><span class="text-xs bg-gray-100 px-2 py-1 rounded dark:bg-gray-800">{{ row.user?.name }}</span></template>

            <template #cell-actions="{ row }">
                <button
                    v-if="isOwner"
                    @click="openEditModal(row)"
                    class="rounded-lg border border-orange-200 bg-orange-50 p-2 text-orange-600 transition hover:bg-orange-100 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-400 dark:hover:bg-orange-500/20"
                    title="Edit Hasil Audit"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </button>
            </template>
        </DataTable>

        <Modal :show="isEditModalOpen" title="Edit Hasil Audit Tangki" @close="isEditModalOpen = false">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <DatePicker v-model="editForm.recorded_at" label="Tanggal Cek" required />
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jam</label>
                        <input type="time" v-model="editForm.time" class="w-full rounded-lg border-gray-300 bg-transparent py-2.5 px-4 text-sm focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900" required />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <TextInput v-model="editForm.physical_height_cm" type="number" label="Tinggi (cm)" placeholder="Opsional" />
                    <DecimalInput
                        v-model="editForm.physical_liter"
                        label="Stok Fisik (Liter)"
                        placeholder="0.000"
                        suffix="Liter"
                        required
                    />
                </div>

                <div class="flex justify-end gap-3 mt-4 border-t pt-4">
                    <Button type="button" variant="outline" @click="isEditModalOpen = false">Batal</Button>
                    <Button type="submit" variant="primary" :processing="editForm.processing">Simpan Perubahan</Button>
                </div>
            </form>
        </Modal>

    </AdminLayout>
</template>
