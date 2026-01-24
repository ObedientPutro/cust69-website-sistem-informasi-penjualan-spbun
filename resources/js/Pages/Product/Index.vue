<script setup lang="ts">
import DatePicker from '@/Components/FormElements/DatePicker.vue';
import DateTimePicker from '@/Components/FormElements/DateTimePicker.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Alert from '@/Components/Ui/Alert.vue';
import Button from '@/Components/Ui/Button.vue';
import Modal from '@/Components/Ui/Modal.vue';
import ToggleSwitch from '@/Components/FormElements/ToggleSwitch.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {Head, router, useForm} from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    products: any;
    filters: any;
}>();

const swal = useSweetAlert();
const isCreateModalOpen = ref(false);
const isRestockModalOpen = ref(false);
const isSoundingModalOpen = ref(false);
const isEditMode = ref(false);
const selectedProduct = ref<any>(null);

const form = useForm({
    id: null,
    name: '',
    unit: 'Liter',
    price: 0,
    cost_price: 0,
    stock: 0,
});

const formRestock = useForm({
    product_id: null,
    date: new Date().toISOString().split('T')[0],
    volume_liter: '',
    total_cost: '',
    note: '',
});

const formSounding = useForm({
    product_id: null,
    recorded_at: '',
    physical_height_cm: '',
    physical_liter: '',
});

const columns = [
    { label: 'Nama Produk', key: 'name', sortable: true, align: 'left' },
    { label: 'Satuan', key: 'unit', sortable: true, align: 'center' },
    {
        label: 'Harga Beli (HPP)',
        key: 'cost_price',
        sortable: true,
        align: 'center',
    },
    { label: 'Harga Jual', key: 'price', sortable: true, align: 'center' },
    { label: 'Stok Sistem', key: 'stock', sortable: true, align: 'center' },
    { label: 'Status', key: 'is_active', sortable: true, align: 'center' },
];

const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    isCreateModalOpen.value = true;
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
    isCreateModalOpen.value = true;
};

const submitMaster = () => {
    const routeName = isEditMode.value ? 'products.update' : 'products.save';
    const routeParam = isEditMode.value ? form.id : undefined;
    form[isEditMode.value ? 'put' : 'post'](route(routeName, routeParam), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            form.reset();
        },
    });
};

const openRestockModal = (product: any) => {
    selectedProduct.value = product;
    formRestock.reset();
    formRestock.product_id = product.id;
    formRestock.date = new Date().toISOString().split('T')[0];
    isRestockModalOpen.value = true;
};

const submitRestock = () => {
    formRestock.post(route('inventory.restock'), {
        onSuccess: () => {
            isRestockModalOpen.value = false;
            formRestock.reset();
            swal.toast('Stok berhasil ditambahkan!', 'success');
        },
    });
};

const estimatedUnitCost = computed(() => {
    const vol = parseFloat(formRestock.volume_liter);
    const cost = parseFloat(formRestock.total_cost);
    return vol > 0 && cost > 0 ? cost / vol : 0;
});

const openSoundingModal = (product: any) => {
    selectedProduct.value = product;
    formSounding.reset();
    formSounding.product_id = product.id;

    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');

    formSounding.recorded_at = `${year}-${month}-${day} ${hours}:${minutes}`;
    isSoundingModalOpen.value = true;
};

const submitSounding = () => {
    formSounding.post(route('inventory.sounding'), {
        onSuccess: () => {
            isSoundingModalOpen.value = false;
            formSounding.reset();
            swal.toast('Hasil sounding tercatat!', 'success');
        },
    });
};

const toggleStatus = (product: any) => {
    router.patch(
        route('products.toggle-status', product.id),
        {},
        {
            preserveScroll: true,
            onError: () => {
                swal.toast('Gagal mengubah status', 'error');
            },
        },
    );
};
</script>

<template>
    <Head title="Master Produk & Inventori" />

    <AdminLayout>
        <div
            class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                    Inventori BBM
                </h2>
                <p class="text-sm text-gray-500">
                    Kelola produk, stok masuk (Restock), dan audit tangki.
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
                    Tambah Produk Baru
                </Button>
            </template>

            <template #cell-cost_price="{ row }">
                <span class="text-sm text-gray-500">{{
                    formatRupiah(row.cost_price)
                }}</span>
            </template>

            <template #cell-price="{ row }">
                <span class="font-semibold text-gray-800 dark:text-white">{{
                    formatRupiah(row.price)
                }}</span>
            </template>

            <template #cell-stock="{ row }">
                <div class="flex flex-col items-center justify-center">
                    <span
                        class="text-sm font-semibold"
                        :class="{
                            'text-emerald-600': Number(row.stock) > 500,
                            'text-red-500':
                                Number(row.stock) >= 0 &&
                                Number(row.stock) <= 500,
                            'text-yellow-700': Number(row.stock) < 0,
                        }"
                    >
                        {{ row.stock }} {{ row.unit }}
                    </span>

                    <div
                        v-if="Number(row.stock) < 0"
                        class="mt-1.5 flex items-center gap-1 rounded-md border border-yellow-200 bg-yellow-50 px-2 py-1 text-yellow-700 shadow-sm"
                    >
                        <svg
                            class="h-3 w-3 shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                        <span
                            class="text-[10px] font-bold tracking-wide uppercase"
                            >Negative Stock</span
                        >
                    </div>

                    <div
                        v-else-if="Number(row.stock) <= 500"
                        class="mt-1 text-[10px] font-medium text-red-500"
                    >
                        Stok Menipis
                    </div>
                </div>
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
                <div class="flex items-center justify-center gap-2">
                    <button
                        @click="openRestockModal(row)"
                        class="rounded-lg border border-blue-200 bg-blue-50 p-2 text-blue-600 transition hover:bg-blue-100 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                        title="Terima BBM (Restock)"
                    >
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
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            ></path>
                        </svg>
                    </button>

                    <button
                        @click="openSoundingModal(row)"
                        class="rounded-lg border border-purple-200 bg-purple-50 p-2 text-purple-600 transition hover:bg-purple-100 dark:border-purple-500/20 dark:bg-purple-500/10 dark:text-purple-400 dark:hover:bg-purple-500/20"
                        title="Sounding Tangki (Opname)"
                    >
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
                                d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                            ></path>
                        </svg>
                    </button>

                    <div
                        class="mx-1 h-4 w-px bg-gray-300 dark:bg-gray-700"
                    ></div>

                    <button
                        @click="openEditModal(row)"
                        class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-300"
                        title="Edit Produk"
                    >
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
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                            ></path>
                        </svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <Modal
            :show="isCreateModalOpen"
            :title="isEditMode ? 'Edit Master Produk' : 'Tambah Produk Baru'"
            maxWidth="lg"
            @close="isCreateModalOpen = false"
        >
            <form @submit.prevent="submitMaster" class="space-y-5">
                <Alert
                    v-if="Object.keys(form.errors).length > 0"
                    variant="error"
                    title="Error"
                    message="Cek inputan Anda."
                />

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="col-span-2">
                        <TextInput
                            v-model="form.name"
                            label="Nama Produk"
                            placeholder="Ex: Pertamax"
                            :error="form.errors.name"
                            required
                        />
                    </div>
                    <div>
                        <TextInput
                            v-model="form.unit"
                            label="Satuan"
                            :error="form.errors.unit"
                            required
                            disabled
                        />
                    </div>

                    <div v-if="!isEditMode">
                        <TextInput
                            v-model="form.stock"
                            type="number"
                            label="Stok Awal"
                            placeholder="0"
                            :error="form.errors.stock"
                        />
                    </div>
                    <div v-else class="flex flex-col justify-center">
                        <span
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Stok Saat Ini</span
                        >
                        <div
                            class="rounded border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-500 italic dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                        >
                            Update stok via tombol Restock / Sounding.
                        </div>
                    </div>

                    <div
                        class="col-span-2 mt-2 border-t border-gray-200 pt-2 dark:border-gray-700"
                    >
                        <h4
                            class="text-sm font-bold text-gray-800 dark:text-white"
                        >
                            Pengaturan Harga
                        </h4>
                    </div>
                    <div>
                        <TextInput
                            v-model="form.cost_price"
                            type="number"
                            label="HPP (Modal)"
                            :error="form.errors.cost_price"
                        />
                    </div>
                    <div>
                        <TextInput
                            v-model="form.price"
                            type="number"
                            label="Harga Jual"
                            :error="form.errors.price"
                            required
                        />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isCreateModalOpen = false"
                        >Batal</Button
                    >
                    <Button type="submit" :processing="form.processing"
                        >Simpan</Button
                    >
                </div>
            </form>
        </Modal>

        <Modal
            :show="isRestockModalOpen"
            title="Penebusan DO (Restock)"
            maxWidth="md"
            @close="isRestockModalOpen = false"
        >
            <form @submit.prevent="submitRestock" class="space-y-4">
                <div
                    class="mb-2 rounded-lg border border-blue-100 bg-blue-50 p-3 dark:border-blue-800/30 dark:bg-blue-900/20"
                >
                    <div class="flex justify-between">
                        <span
                            class="text-xs font-bold text-blue-600 uppercase dark:text-blue-400"
                            >Produk</span
                        >
                        <span
                            class="text-xs font-bold text-blue-600 uppercase dark:text-blue-400"
                            >Stok Sistem</span
                        >
                    </div>
                    <div class="flex items-end justify-between">
                        <span
                            class="text-lg font-bold text-blue-800 dark:text-blue-200"
                            >{{ selectedProduct?.name }}</span
                        >
                        <span
                            class="font-mono text-sm text-blue-700 dark:text-blue-300"
                            >{{ selectedProduct?.stock }}
                            {{ selectedProduct?.unit }}</span
                        >
                    </div>
                </div>

                <DatePicker
                    v-model="formRestock.date"
                    label="Tanggal Masuk (DO)"
                    placeholder="Pilih Tanggal"
                    required
                    :error="formRestock.errors.date"
                />

                <div class="grid grid-cols-2 gap-4">
                    <TextInput
                        v-model="formRestock.volume_liter"
                        type="number"
                        step="0.01"
                        label="Volume (Liter)"
                        placeholder="0"
                        required
                        :error="formRestock.errors.volume_liter"
                    />
                    <TextInput
                        v-model="formRestock.total_cost"
                        type="number"
                        label="Total Bayar (Rp)"
                        placeholder="0"
                        required
                        :error="formRestock.errors.total_cost"
                    />
                </div>

                <div
                    class="text-right text-xs text-gray-500 dark:text-gray-400"
                >
                    Estimasi HPP Baru:
                    <span class="font-bold text-gray-800 dark:text-gray-200"
                        >{{ formatRupiah(estimatedUnitCost) }} / Liter</span
                    >
                </div>

                <TextArea
                    v-model="formRestock.note"
                    label="Catatan / No. DO"
                    rows="2"
                />

                <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isRestockModalOpen = false"
                        >Batal</Button
                    >
                    <Button type="submit" :processing="formRestock.processing"
                        >Simpan Stok</Button
                    >
                </div>
            </form>
        </Modal>

        <Modal
            :show="isSoundingModalOpen"
            title="Stok Opname (Sounding)"
            maxWidth="md"
            @close="isSoundingModalOpen = false"
        >
            <form @submit.prevent="submitSounding" class="space-y-4">
                <div
                    class="mb-2 rounded-lg border border-purple-100 bg-purple-50 p-3 dark:border-purple-800/30 dark:bg-purple-900/20"
                >
                    <div class="flex justify-between">
                        <span
                            class="text-xs font-bold text-purple-600 uppercase dark:text-purple-400"
                            >Produk</span
                        >
                        <span
                            class="text-xs font-bold text-purple-600 uppercase dark:text-purple-400"
                            >Stok Sistem</span
                        >
                    </div>
                    <div class="flex items-end justify-between">
                        <span
                            class="text-lg font-bold text-purple-800 dark:text-purple-200"
                            >{{ selectedProduct?.name }}</span
                        >
                        <span
                            class="font-mono text-sm text-purple-700 dark:text-purple-300"
                            >{{ selectedProduct?.stock }}
                            {{ selectedProduct?.unit }}</span
                        >
                    </div>
                </div>

                <DateTimePicker
                    v-model="formSounding.recorded_at"
                    label="Waktu Pengukuran (Tgl & Jam)"
                    placeholder="Pilih Waktu"
                    required
                    :error="formSounding.errors.recorded_at"
                />

                <div class="grid grid-cols-2 gap-4">
                    <TextInput
                        v-model="formSounding.physical_height_cm"
                        type="number"
                        step="0.1"
                        label="Tinggi (CM)"
                        placeholder="Opsional"
                    />
                    <TextInput
                        v-model="formSounding.physical_liter"
                        type="number"
                        step="0.01"
                        label="Volume Fisik (Liter)"
                        placeholder="Hasil Ukur"
                        required
                        :error="formSounding.errors.physical_liter"
                    />
                </div>

                <Alert
                    variant="warning"
                    title="Perhatian"
                    message="Data ini hanya untuk laporan audit (selisih). Stok sistem tidak berubah otomatis."
                />

                <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isSoundingModalOpen = false"
                        >Batal</Button
                    >
                    <Button type="submit" :processing="formSounding.processing"
                        >Simpan Audit</Button
                    >
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
