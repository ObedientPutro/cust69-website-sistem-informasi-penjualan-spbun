<script setup lang="ts">
import { ref, computed } from 'vue'; // Tambah computed
import { Head, useForm, router, usePage } from '@inertiajs/vue3'; // Tambah usePage
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Modal from '@/Components/Ui/Modal.vue';
import Button from '@/Components/Ui/Button.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import ToggleSwitch from '@/Components/FormElements/ToggleSwitch.vue';
import FileDropzone from '@/Components/FormElements/FileDropzone.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import ImageViewerModal from '@/Components/Ui/ImageViewerModal.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';
import CurrencyInput from "@/Components/FormElements/CurrencyInput.vue";

const props = defineProps<{
    customers: any;
    filters: any;
    shipTypes: { value: string, label: string }[]
}>();
const swal = useSweetAlert();
const page = usePage();

// Cek apakah user adalah Owner
const isOwner = computed(() => page.props.auth.user.role === 'owner');

// --- STATE MODAL UTAMA (CRUD DATA) ---
const isModalOpen = ref(false);
const isEditMode = ref(false);

const form = useForm({
    id: null,
    manager_name: '',
    owner_name: '',
    phone: '',
    ship_name: '',
    ship_type: 'fishing',
    gross_tonnage: 0,
    pk_engine: 0,
    address: '',
    credit_limit: 0,
    photo: null as File | null,
    _method: 'post',
});

// --- STATE MODAL LIMIT (BARU - KHUSUS OWNER) ---
const isLimitModalOpen = ref(false);
const selectedCustomer = ref<any>(null);

const limitForm = useForm({
    type: 'add', // 'add' atau 'subtract'
    amount: 0,
});

// Computed Preview Limit Baru
const newLimitPreview = computed(() => {
    if (!selectedCustomer.value) return 0;
    const current = parseFloat(selectedCustomer.value.credit_limit || 0);
    const amount = parseFloat(limitForm.amount) || 0;

    let result = 0;
    if (limitForm.type === 'add') {
        result = current + amount;
    } else {
        result = current - amount;
    }

    // Batas 0 (Tidak boleh negatif)
    return result < 0 ? 0 : result;
});

const params = ref({
    search: props.filters.search || '',
    sort: props.filters.sort || 'created_at',
    direction: props.filters.direction || 'desc'
});

const columns = [
    { label: 'Nama Pengurus', key: 'manager_name', sortable: true, align: 'left' },
    { label: 'Kapal', key: 'ship_name', sortable: true, align: 'left' },
    { label: 'Pemilik', key: 'owner_name', sortable: true, align: 'left' },
    { label: 'GT / PK', key: 'gross_tonnage', sortable: true, align: 'center' },
    { label: 'Limit & Piutang', key: 'credit_info', sortable: true, align: 'left' },
    { label: 'Status', key: 'is_active', sortable: true, align: 'center' },
];

const openCreate = () => {
    isEditMode.value = false;
    form.reset();
    form.credit_limit = 0; // Default 0
    form.gross_tonnage = 0;
    form.pk_engine = 0;
    form.ship_type = 'fishing';
    form._method = 'post';
    isModalOpen.value = true;
};

const openEdit = (customer: any) => {
    isEditMode.value = true;
    form.reset();
    form.id = customer.id;
    form.manager_name = customer.manager_name;
    form.owner_name = customer.owner_name;
    form.phone = customer.phone;
    form.ship_name = customer.ship_name;
    form.ship_type = customer.ship_type;
    form.gross_tonnage = parseFloat(customer.gross_tonnage);
    form.pk_engine = parseFloat(customer.pk_engine);
    form.address = customer.address;
    form.photo = null;
    form._method = 'put';
    isModalOpen.value = true;
};

// --- LOGIC LIMIT BARU ---
const openLimitModal = (customer: any) => {
    selectedCustomer.value = customer;
    limitForm.reset();
    limitForm.type = 'add';
    limitForm.amount = 0;
    isLimitModalOpen.value = true;
};

const submitLimit = () => {
    limitForm.put(route('customers.update-limit', selectedCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isLimitModalOpen.value = false;
            swal.toast('Limit Kredit Berhasil Diubah', 'success');
        },
        onError: () => swal.toast('Gagal mengubah limit', 'error')
    });
};

const submit = () => {
    const routeName = isEditMode.value ? 'customers.update' : 'customers.save';
    const params = isEditMode.value ? form.id : undefined;

    form.post(route(routeName, params), {
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

const isViewerOpen = ref(false);
const selectedImageUrl = ref<string | null>(null);
const selectedImageAlt = ref('');

const openImageViewer = (url: string, alt: string) => {
    selectedImageUrl.value = url;
    selectedImageAlt.value = alt;
    isViewerOpen.value = true;
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
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </template>
                    Tambah Nelayan
                </Button>
            </template>

            <template #cell-manager_name="{ row }">
                <div class="flex items-center gap-3">
                    <button
                        @click="openImageViewer(row.photo_url, row.manager_name)"
                        class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-full border border-gray-200 dark:border-gray-700 transition hover:scale-110 hover:ring-2 hover:ring-orange-500 cursor-zoom-in"
                    >
                        <img :src="row.photo_url" :alt="row.manager_name" class="h-full w-full object-cover" />
                    </button>
                    <div>
                        <p class="font-medium text-gray-800 dark:text-white">{{ row.manager_name }}</p>
                    </div>
                </div>
            </template>

            <template #cell-gross_tonnage="{ row }">
                <div class="text-xs">
                    <span class="block font-medium">GT: {{ row.gross_tonnage }}</span>
                    <span class="block text-gray-500">PK: {{ row.pk_engine }}</span>
                </div>
            </template>

            <template #cell-credit_info="{ row }">
                <div class="flex flex-col gap-1 w-full min-w-[140px]">
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500">Limit:</span>
                        <span class="font-bold text-gray-800 dark:text-white">{{ formatRupiah(row.credit_limit) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500">Terpakai:</span>
                        <span class="font-bold text-red-500">{{ formatRupiah(row.used_credit || 0) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700 mt-1 overflow-hidden">
                        <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-500"
                             :style="{ width: Math.min(((row.used_credit || 0) / (row.credit_limit || 1)) * 100, 100) + '%' }">
                        </div>
                    </div>
                </div>
            </template>

            <template v-if="isOwner" #cell-is_active="{ row }">
                <div class="flex justify-center">
                    <ToggleSwitch
                        :model-value="Boolean(row.is_active)"
                        @update:model-value="toggleStatus(row)"
                        :label="row.is_active ? 'Aktif' : 'Non-Aktif'"
                    />
                </div>
            </template>
            <template v-else #cell-is_active="{ row }">
                <div class="flex justify-center">
                    <p>{{ row.is_active ? 'Aktif' : 'Non-Aktif' }}</p>
                </div>
            </template>

            <template #actions-row="{ row }">
                <div class="flex gap-2 justify-center">

                    <button
                        @click="openEdit(row)"
                        class="rounded-lg border border-orange-200 bg-orange-50 p-2 text-orange-600 transition hover:bg-orange-100 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-400 dark:hover:bg-orange-500/20"
                        title="Edit Data Pelanggan"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </button>

                    <button
                        v-if="isOwner"
                        @click="openLimitModal(row)"
                        class="rounded-lg border border-green-200 bg-green-50 p-2 text-green-600 transition hover:bg-green-100 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400 dark:hover:bg-green-500/20"
                        title="Kelola Limit Kredit"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>

                    <button
                        v-if="isOwner"
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
                <TextInput v-model="form.ship_name" label="Nama Kapal" required :error="form.errors.ship_name" />
                <TextInput v-model="form.owner_name" label="Nama Pemilik Kapal" required :error="form.errors.owner_name" />
                <TextInput v-model="form.manager_name" label="Nama Pengurus (Nakhoda)" required :error="form.errors.manager_name" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <SelectInput v-model="form.ship_type" label="Jenis Kapal" :options="shipTypes" :error="form.errors.ship_type" required />
                    <TextInput v-model="form.phone" label="No. Handphone" placeholder="0812..." required :error="form.errors.phone" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <TextInput v-model="form.gross_tonnage" type="number" step="0.01" label="Gross Tonnage (GT)" required :error="form.errors.gross_tonnage" />
                    <TextInput v-model="form.pk_engine" type="number" step="0.01" label="PK Mesin (HP)" required :error="form.errors.pk_engine" />
                </div>

                <div v-if="!isEditMode" class="p-3 bg-blue-50 rounded-lg text-sm text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                    <span class="font-bold">Info:</span> Pelanggan baru otomatis memiliki <strong>Limit Kredit 0</strong>. Hubungi Owner untuk menaikkan limit.
                </div>

                <TextArea v-model="form.address" label="Alamat Lengkap" required :error="form.errors.address" />

                <div class="col-span-1 md:col-span-2">
                    <FileDropzone v-model="form.photo" label="Foto KTP / Wajah (Opsional)" accept="image/*" :error="form.errors.photo" />
                </div>
            </form>

            <template #footer>
                <button type="button" @click="isModalOpen = false" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">Batal</button>
                <Button type="button" @click="submit" variant="primary" :processing="form.processing">
                    {{ isEditMode ? 'Simpan Perubahan' : 'Buat User' }}
                </Button>
            </template>
        </Modal>

        <Modal :show="isLimitModalOpen" title="Kelola Limit Kredit" maxWidth="md" @close="isLimitModalOpen = false">
            <div v-if="selectedCustomer" class="space-y-6">

                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-xl border dark:border-gray-700">
                    <h4 class="font-bold text-gray-800 dark:text-white mb-2">{{ selectedCustomer.ship_name }}</h4>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-500">Limit Plafon Saat Ini:</span>
                        <span class="font-bold">{{ formatRupiah(selectedCustomer.credit_limit) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Hutang Belum Dibayar:</span>
                        <span class="font-bold text-red-500">{{ formatRupiah(selectedCustomer.used_credit || 0) }}</span>
                    </div>
                </div>

                <form @submit.prevent="submitLimit">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Perubahan</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer p-3 border rounded-lg w-full transition hover:bg-green-50" :class="limitForm.type === 'add' ? 'border-green-500 bg-green-50 ring-1 ring-green-500' : 'border-gray-200'">
                                <input type="radio" v-model="limitForm.type" value="add" class="text-green-600 focus:ring-green-500">
                                <span class="font-bold text-green-700">Tambah (+)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer p-3 border rounded-lg w-full transition hover:bg-red-50" :class="limitForm.type === 'subtract' ? 'border-red-500 bg-red-50 ring-1 ring-red-500' : 'border-gray-200'">
                                <input type="radio" v-model="limitForm.type" value="subtract" class="text-red-600 focus:ring-red-500">
                                <span class="font-bold text-red-700">Kurang (-)</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-6">
                        <CurrencyInput
                            v-model="limitForm.amount"
                            label="Nominal Perubahan"
                            prefix="Rp"
                            placeholder="0"
                            required
                            class="text-lg font-bold"
                        />
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg flex justify-between items-center mb-6">
                        <span class="text-blue-800 dark:text-blue-300 text-sm font-medium">Limit Baru Nanti:</span>
                        <span class="text-xl font-extrabold text-blue-700 dark:text-blue-200">
                            {{ formatRupiah(newLimitPreview) }}
                        </span>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t dark:border-gray-700">
                        <Button type="button" variant="outline" @click="isLimitModalOpen = false">Batal</Button>
                        <Button type="submit" variant="primary" :processing="limitForm.processing">Simpan Limit</Button>
                    </div>
                </form>
            </div>
        </Modal>

        <ImageViewerModal
            :show="isViewerOpen"
            :image-src="selectedImageUrl"
            :alt-text="selectedImageAlt"
            @close="isViewerOpen = false"
        />

    </AdminLayout>
</template>

<style scoped>
</style>
