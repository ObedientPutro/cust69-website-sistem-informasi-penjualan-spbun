<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import Modal from '@/Components/Ui/Modal.vue';
import Button from '@/Components/Ui/Button.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import Alert from '@/Components/Ui/Alert.vue';

const props = defineProps<{ customers: any; filters: any }>();

const isModalOpen = ref(false);
const isEditMode = ref(false);
const form = useForm({ id: null, name: '', phone: '', ship_name: '', address: '' });

const columns = [
    { label: 'Nama Nelayan', key: 'name', sortable: true, align: 'left' },
    { label: 'No HP', key: 'phone', sortable: false, align: 'left' },
    { label: 'Nama Kapal / Kelompok', key: 'ship_name', sortable: true, align: 'left' },
    { label: 'Alamat', key: 'address', sortable: false, align: 'left' },
];

const openCreate = () => {
    isEditMode.value = false;
    form.reset();
    isModalOpen.value = true;
};

const openEdit = (customer: any) => {
    isEditMode.value = true;
    form.id = customer.id;
    form.name = customer.name;
    form.phone = customer.phone;
    form.ship_name = customer.ship_name;
    form.address = customer.address;
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
    if(confirm('Hapus data pelanggan ini?')) {
        form.delete(route('customers.delete', id));
    }
};
</script>

<template>
    <Head title="Database Pelanggan" />
    <AdminLayout>
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">Database Pelanggan (Nelayan)</h2>
            </div>
            <Button @click="openCreate">Tambah Nelayan</Button>
        </div>

        <DataTable :rows="customers.data" :columns="columns" :pagination="customers" :filters="filters">
            <template #actions-row="{ row }">
                <div class="flex gap-2 justify-center">
                    <Button size="sm" variant="outline" @click="openEdit(row)">Edit</Button>
                    <Button size="sm" variant="danger" @click="deleteCustomer(row.id)">Hapus</Button>
                </div>
            </template>
        </DataTable>

        <Modal :show="isModalOpen" :title="isEditMode ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru'" @close="isModalOpen = false">
            <form @submit.prevent="submit" class="space-y-4">
                <TextInput v-model="form.name" label="Nama Lengkap" required :error="form.errors.name" />
                <div class="grid grid-cols-2 gap-4">
                    <TextInput v-model="form.phone" label="No. HP" :error="form.errors.phone" />
                    <TextInput v-model="form.ship_name" label="Nama Kapal/Kelompok" :error="form.errors.ship_name" />
                </div>
                <TextArea v-model="form.address" label="Alamat" :error="form.errors.address" />

                <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                    <Button type="button" variant="outline" @click="isModalOpen = false">Batal</Button>
                    <Button type="submit" :processing="form.processing">Simpan</Button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>

<style scoped>

</style>
