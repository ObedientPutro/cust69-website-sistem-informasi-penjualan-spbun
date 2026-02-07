<script setup lang="ts">
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import ToggleSwitch from '@/Components/FormElements/ToggleSwitch.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Alert from '@/Components/Ui/Alert.vue';
import Badge from '@/Components/Ui/Badge.vue';
import Button from '@/Components/Ui/Button.vue';
import Modal from '@/Components/Ui/Modal.vue';
import FileDropzone from '@/Components/FormElements/FileDropzone.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ImageViewerModal from '@/Components/Ui/ImageViewerModal.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    users: any;
    filters: any;
}>();

const page = usePage();
const roleOptions = page.props.enums.user_roles;

console.log(roleOptions);

const swal = useSweetAlert();
const isModalOpen = ref(false);
const isEditMode = ref(false);

const form = useForm({
    id: null,
    name: '',
    email: '',
    nip: '',
    role: 'operator',
    phone: '',
    address: '',
    password: '',
    password_confirmation: '',
    photo: null as File | null,
    _method: 'post',
});

const columns = [
    { label: 'User Info', key: 'name', sortable: true, align: 'left' },
    { label: 'Role', key: 'role', sortable: true, align: 'center' },
    { label: 'NIP', key: 'nip', sortable: true, align: 'left' },
    { label: 'No. HP', key: 'phone', sortable: false, align: 'left' },
    { label: 'Status', key: 'is_active', sortable: true, align: 'center' },
];

const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    form._method = 'post';
    isModalOpen.value = true;
};

const openEditModal = (user: any) => {
    isEditMode.value = true;
    form.reset();
    form.clearErrors();

    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.nip = user.nip;
    form.role = user.role;
    form.phone = user.phone;
    form.address = user.address;
    form.photo = null;
    form._method = 'put';

    isModalOpen.value = true;
};

const submit = () => {
    if (isEditMode.value) {
        form.post(route('users.update', form.id), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('users.save'), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
};

const deleteUser = (user: any) => {
    swal.confirm(
        'Hapus User?',
        `Anda yakin ingin menghapus user ${user.name}? Data yang dihapus tidak dapat dikembalikan.`,
    ).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('users.delete', user.id), {
                preserveScroll: true,
            });
        }
    });
};

const toggleStatus = (user: any) => {
    router.patch(
        route('users.toggle-status', user.id),
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
</script>

<template>
    <Head title="Manajemen User" />

    <AdminLayout>
        <div
            class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                    Manajemen User
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Kelola data pegawai, role akses, dan status akun.
                </p>
            </div>
        </div>

        <DataTable
            :rows="users.data"
            :columns="columns"
            :filters="filters"
            :pagination="users"
            :searchInfo="'Cari Nama User'"
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
                    Tambah User
                </Button>
            </template>

            <template #cell-name="{ row }">
                <div class="flex items-center gap-3">
                    <button
                        @click="openImageViewer(row.photo_url, row.name)"
                        class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-full border border-gray-200 dark:border-gray-700 transition hover:scale-110 hover:ring-2 hover:ring-brand-500 cursor-zoom-in"
                        title="Lihat Foto"
                    >
                        <img
                            :src="row.photo_url"
                            :alt="row.name"
                            class="h-full w-full object-cover"
                        />
                    </button>

                    <div>
                        <p class="font-medium text-gray-800 dark:text-white">
                            {{ row.name }}
                        </p>
                        <p class="text-xs text-gray-500">{{ row.email }}</p>
                    </div>
                </div>
            </template>

            <template #cell-role="{ row }">
                <Badge
                    :color="
                        row.role === 'admin'
                            ? 'primary'
                            : row.role === 'owner'
                              ? 'dark'
                              : 'info'
                    "
                    variant="light"
                    size="sm"
                >
                    {{ row.role.toUpperCase() }}
                </Badge>
            </template>

            <template #cell-nip="{ row }">
                <span class="font-mono text-xs text-gray-500">{{
                    row.nip || '-'
                }}</span>
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
                <div class="flex items-center justify-end gap-2">
                    <button
                        @click="openEditModal(row)"
                        class="rounded-lg border border-blue-200 bg-blue-50 p-2 text-blue-600 transition hover:bg-blue-100 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                        title="Edit Data"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>

                    <button
                        @click="deleteUser(row)"
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

        <Modal
            :show="isModalOpen"
            :title="isEditMode ? 'Edit Data User' : 'Tambah User Baru'"
            maxWidth="2xl"
            @close="isModalOpen = false"
        >
            <form @submit.prevent="submit" class="space-y-5">
                <Alert
                    v-if="Object.keys(form.errors).length > 0 && !form.isDirty"
                    variant="error"
                    title="Terjadi Kesalahan"
                    message="Harap lengkapi semua field yang wajib diisi."
                />

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="col-span-1 md:col-span-2">
                        <FileDropzone
                            v-model="form.photo"
                            label="Foto Profil (Opsional)"
                            accept="image/*"
                            :error="form.errors.photo"
                        />
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <TextInput
                            v-model="form.name"
                            label="Nama Lengkap"
                            placeholder="Contoh: Budi Santoso"
                            :error="form.errors.name"
                            required
                        />
                    </div>

                    <div class="col-span-1">
                        <TextInput
                            v-model="form.email"
                            type="email"
                            label="Alamat Email"
                            placeholder="email@spbun.com"
                            :error="form.errors.email"
                            required
                        />
                    </div>

                    <div class="col-span-1">
                        <TextInput
                            v-model="form.nip"
                            label="NIP (Nomor Induk)"
                            placeholder="Masukkan NIP"
                            :error="form.errors.nip"
                            required
                        />
                    </div>

                    <div class="col-span-1">
                        <SelectInput
                            v-model="form.role"
                            label="Jabatan / Role"
                            :options="roleOptions"
                            :error="form.errors.role"
                            required
                        />
                    </div>

                    <div class="col-span-1">
                        <TextInput
                            v-model="form.phone"
                            label="No. Handphone"
                            placeholder="08xxxxxxxxxx"
                            :error="form.errors.phone"
                            required
                        />
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <TextArea
                            v-model="form.address"
                            label="Alamat Lengkap"
                            placeholder="Jalan, Kelurahan, Kecamatan..."
                            :error="form.errors.address"
                            required
                            :rows="3"
                        />
                    </div>

                    <div
                        class="col-span-1 my-1 border-t border-gray-100 pt-2 md:col-span-2 dark:border-gray-700"
                    >
                        <h4
                            class="mb-2 text-sm font-semibold text-gray-800 dark:text-white"
                        >
                            Keamanan Akun
                        </h4>
                    </div>

                    <div class="col-span-1">
                        <TextInput
                            v-model="form.password"
                            type="password"
                            label="Password"
                            placeholder="******"
                            :error="form.errors.password"
                            :required="!isEditMode"
                        />
                        <p v-if="isEditMode" class="mt-1 text-xs text-gray-500">
                            *Isi hanya jika ingin ubah password.
                        </p>
                    </div>

                    <div class="col-span-1">
                        <TextInput
                            v-model="form.password_confirmation"
                            type="password"
                            label="Konfirmasi Password"
                            placeholder="******"
                            :required="!isEditMode || form.password.length > 0"
                        />
                    </div>
                </div>
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

        <ImageViewerModal
            :show="isViewerOpen"
            :image-src="selectedImageUrl"
            :alt-text="selectedImageAlt"
            @close="isViewerOpen = false"
        />

    </AdminLayout>
</template>
