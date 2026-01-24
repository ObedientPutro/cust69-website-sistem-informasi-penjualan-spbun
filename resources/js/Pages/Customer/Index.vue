<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Modal from '@/Components/Ui/Modal.vue';
import Button from '@/Components/Ui/Button.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import ToggleSwitch from '@/Components/FormElements/ToggleSwitch.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';

const props = defineProps<{ customers: any; filters: any }>();
const swal = useSweetAlert();

const isModalOpen = ref(false);
const isEditMode = ref(false);

const form = useForm({
    id: null,
    name: '',
    phone: '',
    ship_name: '',
    address: '',
    credit_limit: 0
});

const params = ref({
    search: props.filters.search || '',
    sort: props.filters.sort || 'created_at',
    direction: props.filters.direction || 'desc'
});

const columns = [
    { label: 'Nama Nelayan', key: 'name', sortable: true, align: 'left' },
    { label: 'Kapal / Kelompok', key: 'ship_name', sortable: true, align: 'left' },
    { label: 'No HP', key: 'phone', sortable: false, align: 'left' },
    { label: 'Limit Bon (Rp)', key: 'credit_limit', sortable: true, align: 'center' },
    { label: 'Status Kredit', key: 'is_active', sortable: true, align: 'center' },
    { label: 'Alamat', key: 'address', sortable: false, align: 'left' },
];

const handleSort = (columnKey) => {
    params.value.sort = columnKey;
    params.value.direction = params.value.direction === 'asc' ? 'desc' : 'asc';

    router.get(route('customers.index'), params.value, {
        preserveState: true,
        preserveScroll: true
    });
};

const openCreate = () => {
    isEditMode.value = false;
    form.reset();
    form.credit_limit = 0; // Default
    isModalOpen.value = true;
};

const openEdit = (customer: any) => {
    isEditMode.value = true;
    form.id = customer.id;
    form.name = customer.name;
    form.phone = customer.phone;
    form.ship_name = customer.ship_name;
    form.address = customer.address;
    form.credit_limit = parseFloat(customer.credit_limit);
    isModalOpen.value = true;
};

const submit = () => {
    const action = isEditMode.value ? 'customers.update' : 'customers.save';
    const params = isEditMode.value ? form.id : undefined;

    form[isEditMode.value ? 'put' : 'post'](route(action, params), {
        onSuccess: () => { isModalOpen.value = false; form.reset(); }
    });
};

const deleteCustomer = (id: number) => {
    swal.confirm('Hapus Pelanggan?', 'Data yang dihapus tidak bisa dikembalikan.')
        .then((result) => {
            if (result.isConfirmed) {
                router.delete(route('customers.delete', id), {
                    onError: () => swal.toast('Gagal menghapus data', 'error')
                });
            }
        });
};

const toggleStatus = (customer: any) => {
    router.patch(
        route('customers.toggle-status', customer.id),
        {},
        {
            preserveScroll: true,
            onError: () => {
                swal.toast('Gagal mengubah status', 'error');
            },
        },
    );
};

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
</script>

<template>
    <Head title="Manajemen Pelanggan" />
    <AdminLayout>
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">Manajemen Pelanggan</h2>
                <p class="text-sm text-gray-500">Kelola data nelayan dan limit piutang.</p>
            </div>
        </div>

        <DataTable :rows="customers.data" :columns="columns" :pagination="customers" :filters="filters">
            <template #actions>
                <Button @click="openCreate" size="sm" variant="primary">
                    <template #startIcon>
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                    </template>
                    Tambah Nelayan
                </Button>
            </template>

            <template #cell-credit_limit="{ row }">
                <span class="font-mono font-medium text-gray-700 dark:text-gray-300">
                    {{ formatRupiah(row.credit_limit) }}
                </span>
            </template>

            <template #cell-is_active="{ row }">
                <div class="flex justify-center">
                    <ToggleSwitch
                        :model-value="Boolean(row.is_active)"
                        @update:model-value="toggleStatus(row)"
                        :label="row.is_active ? 'Aktif' : 'Non-Aktif'"
                    />
                </div>
            </template>

            <template #actions-row="{ row }">
                <div class="flex gap-2 justify-center">

                    <button
                        @click="openEdit(row)"
                        class="rounded-lg border border-blue-200 bg-blue-50 p-2 text-blue-600 transition hover:bg-blue-100 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                        title="Edit Data"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>

                    <button
                        @click="deleteCustomer(row.id)"
                        class="rounded-lg border border-red-200 bg-red-50 p-2 text-red-600 transition hover:bg-red-100 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                        title="Hapus Data"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>

                </div>
            </template>
        </DataTable>

        <Modal :show="isModalOpen" :title="isEditMode ? 'Edit Pelanggan' : 'Tambah Nelayan Baru'" @close="isModalOpen = false">
            <form @submit.prevent="submit" class="space-y-4">
                <TextInput
                    v-model="form.name"
                    label="Nama Lengkap (Sesuai KTP)"
                    placeholder="Contoh: Budi Santoso"
                    required
                    :error="form.errors.name"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <TextInput
                        v-model="form.phone"
                        label="No. Handphone"
                        placeholder="0812..."
                        required
                        :error="form.errors.phone"
                    />
                    <TextInput
                        v-model="form.ship_name"
                        label="Nama Kapal / Kelompok"
                        placeholder="KM. Sejahtera 01"
                        required
                        :error="form.errors.ship_name"
                    />
                </div>

                <div class="p-4 bg-yellow-50 rounded-xl border border-yellow-100 dark:bg-yellow-900/10 dark:border-yellow-800">
                    <TextInput
                        v-model="form.credit_limit"
                        type="number"
                        label="Limit Kredit (Maksimal Bon)"
                        placeholder="0"
                        required
                        :error="form.errors.credit_limit"
                    />
                    <p class="text-xs text-yellow-600 mt-1">
                        *Masukkan angka 0 jika nelayan ini tidak diizinkan berhutang.
                    </p>
                </div>

                <TextArea
                    v-model="form.address"
                    label="Alamat Lengkap"
                    required
                    :error="form.errors.address"
                />
            </form>

            <template #footer>
                <button
                    type="button"
                    @click="isModalOpen = false"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-gray-200 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    Batal
                </button>
                <Button
                    type="button"
                    @click="submit"
                    variant="primary"
                    :processing="form.processing"
                >
                    {{ isEditMode ? 'Simpan Perubahan' : 'Buat User' }}
                </Button>
            </template>
        </Modal>
    </AdminLayout>
</template>

<style scoped>

</style>
