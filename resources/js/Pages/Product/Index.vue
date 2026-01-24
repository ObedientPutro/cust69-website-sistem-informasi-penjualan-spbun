<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Modal from '@/Components/Ui/Modal.vue';
import Button from '@/Components/Ui/Button.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import Alert from '@/Components/Ui/Alert.vue';
import Badge from '@/Components/Ui/Badge.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';

const props = defineProps<{
    products: any;
    filters: any;
}>();

const swal = useSweetAlert();
const isModalOpen = ref(false);
const isEditMode = ref(false);

const form = useForm({
    id: null,
    name: '',
    unit: 'Liter',
    price: 0,
    cost_price: 0,
    stock: 0,
});

const columns = [
    { label: 'Nama Produk', key: 'name', sortable: true, align: 'left' },
    { label: 'Satuan', key: 'unit', sortable: true, align: 'center' },
    { label: 'Harga Beli (HPP)', key: 'cost_price', sortable: true, align: 'center' },
    { label: 'Harga Jual', key: 'price', sortable: true, align: 'center' },
    { label: 'Stok Saat Ini', key: 'stock', sortable: true, align: 'center' },
];

const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
};

const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    form.unit = 'Liter'; // Default
    isModalOpen.value = true;
};

const openEditModal = (product: any) => {
    isEditMode.value = true;
    form.reset();
    form.clearErrors();

    form.id = product.id;
    form.name = product.name;
    form.unit = product.unit;
    form.price = parseFloat(product.price);
    form.cost_price = parseFloat(product.cost_price);

    isModalOpen.value = true;
};

const submit = () => {
    if (isEditMode.value) {
        form.put(route('products.update', form.id), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('products.save'), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
};

const deleteProduct = (product: any) => {
    swal.confirm(
        'Hapus Produk?',
        `Anda yakin ingin menghapus <b>${product.name}</b>? Pastikan produk ini belum pernah ditransaksikan.`
    ).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('products.delete', product.id), {
                preserveScroll: true,
            });
        }
    });
};
</script>

<template>
    <Head title="Master Data Produk" />

    <AdminLayout>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                    Master Produk BBM
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Kelola data bahan bakar, harga jual, dan monitoring stok.
                </p>
            </div>
        </div>

        <DataTable
            :rows="products.data"
            :columns="columns"
            :filters="filters"
            :pagination="products"
        >
            <template #actions>
                <Button @click="openCreateModal" size="sm" variant="primary">
                    <template #startIcon>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </template>
                    Tambah Produk
                </Button>
            </template>

            <template #cell-cost_price="{ row }">
                <span class="text-gray-500 dark:text-gray-400">
                    {{ formatRupiah(row.cost_price) }}
                </span>
            </template>

            <template #cell-price="{ row }">
                <span class="font-semibold text-gray-800 dark:text-white">
                    {{ formatRupiah(row.price) }}
                </span>
            </template>

            <template #cell-stock="{ row }">
                <Badge
                    :color="Number(row.stock) <= 100 ? 'error' : 'success'"
                    variant="light"
                    size="sm"
                >
                    {{ row.stock }} {{ row.unit }}
                </Badge>
            </template>

            <template #actions-row="{ row }">
                <div class="flex items-center justify-center gap-2">
                    <button
                        @click="openEditModal(row)"
                        class="p-2 text-gray-500 hover:text-brand-500 transition rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
                        title="Edit Produk"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button
                        @click="deleteProduct(row)"
                        class="p-2 text-gray-500 hover:text-error-500 transition rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
                        title="Hapus Produk"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <Modal
            :show="isModalOpen"
            :title="isEditMode ? 'Edit Produk' : 'Tambah Produk Baru'"
            maxWidth="lg"
            @close="isModalOpen = false"
        >
            <form @submit.prevent="submit" class="space-y-5">

                <Alert
                    v-if="Object.keys(form.errors).length > 0"
                    variant="error"
                    title="Error"
                    message="Periksa kembali inputan Anda."
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="col-span-1 md:col-span-2">
                        <TextInput
                            v-model="form.name"
                            label="Nama Produk"
                            placeholder="Contoh: Pertamax Turbo"
                            :error="form.errors.name"
                            required
                        />
                    </div>

                    <div>
                        <TextInput
                            v-model="form.unit"
                            label="Satuan"
                            placeholder="Liter"
                            :error="form.errors.unit"
                            disabled
                            required
                        />
                    </div>

                    <div>
                        <TextInput
                            v-if="!isEditMode"
                            v-model="form.stock"
                            type="number"
                            label="Stok Awal"
                            placeholder="0"
                            :error="form.errors.stock"
                        />

                        <div v-else>
                            <TextInput
                                v-model="products.data.find(p => p.id == form.id).stock"
                                label="Stok Saat Ini"
                                type="number"
                                disabled
                            />
                            <p class="text-xs text-gray-400 mt-1">
                                Stok dikelola melalui menu <b>Restock</b> (Masuk) atau <b>Penjualan</b> (Keluar).
                            </p>
                        </div>
                    </div>

                    <div class="col-span-1 md:col-span-2 border-t border-gray-100 dark:border-gray-700 pt-2">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-white">Pengaturan Harga (Rp)</h4>
                    </div>

                    <div>
                        <TextInput
                            v-model="form.cost_price"
                            type="number"
                            label="Estimasi HPP / Harga Beli"
                            placeholder="0"
                            :error="form.errors.cost_price"
                        />
                        <p class="text-xs text-gray-400 mt-1">
                            Akan terupdate otomatis (Weighted Average) saat ada transaksi Restock.
                        </p>
                    </div>

                    <div>
                        <TextInput
                            v-model="form.price"
                            type="number"
                            label="Harga Jual"
                            placeholder="0"
                            :error="form.errors.price"
                            required
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button
                        type="button"
                        @click="isModalOpen = false"
                        class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600"
                    >
                        Batal
                    </button>
                    <Button
                        type="submit"
                        variant="primary"
                        :processing="form.processing"
                    >
                        {{ isEditMode ? 'Simpan Perubahan' : 'Buat Produk' }}
                    </Button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>

<style scoped>

</style>
