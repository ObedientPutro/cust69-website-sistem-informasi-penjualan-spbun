<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import SearchableSelect from '@/Components/FormElements/SearchableSelect.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import FileDropzone from '@/Components/FormElements/FileDropzone.vue';
import Modal from '@/Components/Ui/Modal.vue';
import Alert from '@/Components/Ui/Alert.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';
import IntegerInput from "@/Components/FormElements/IntegerInput.vue";
import DatePicker from "@/Components/FormElements/DatePicker.vue";
import TimePicker from "@/Components/FormElements/TimePicker.vue";

const props = defineProps<{
    products: any[];
    customers: any[];
    shipTypes: { value: string, label: string }[];
}>();

const swal = useSweetAlert();

// --- DATE & TIME HANDLING ---
// Default ke hari ini
const inputDate = ref(new Date().toISOString().split('T')[0]);
const inputTime = ref(new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false }));

const form = useForm({
    transaction_date: '', // Digabung saat submit
    customer_id: '',
    payment_method: 'cash',
    payment_proof: null as File | null,
    note: '',
    items: [{ product_id: '', quantity_liter: '0', price: 0 }]
});

// --- COMPUTED ---
const customerOptions = computed(() => {
    return props.customers.map(c => ({
        value: c.id,
        label: `${c.ship_name} (${c.manager_name})`,
        subLabel: `Pemilik: ${c.owner_name || '-'} | GT: ${c.gross_tonnage}`
    }));
});

const selectedCustomer = computed(() => {
    return props.customers.find(c => c.id == form.customer_id);
});

const grandTotal = computed(() => {
    return form.items.reduce((sum, item) => {
        const qty = parseFloat(item.quantity_liter) || 0;
        return sum + (qty * item.price);
    }, 0);
});

// Cek Limit Kredit (Jika Bon)
const isOverLimit = computed(() => {
    if (form.payment_method == 'bon' && selectedCustomer.value) {
        return grandTotal.value > parseFloat(selectedCustomer.value.credit_limit);
    }
    return false;
});

const isSubmitDisabled = computed(() => {
    return grandTotal.value <= 0 || isOverLimit.value || form.processing;
});

// --- METHODS ---
const onProductChange = (index: number) => {
    const selectedId = form.items[index].product_id;
    const product = props.products.find(p => p.id == selectedId);
    form.items[index].price = product ? parseFloat(product.price) : 0;
};

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

// --- SUBMIT TRANSACTION ---
const submit = () => {
    // Gabungkan Tanggal & Jam Inputan
    form.transaction_date = `${inputDate.value} ${inputTime.value}`;

    form.post(route('transactions.store-backdate'), {
        onSuccess: () => {
            swal.toast('Transaksi Lampau Berhasil Disimpan!', 'success');
        },
        onError: () => {
            swal.toast('Gagal menyimpan transaksi.', 'error');
        },
        preserveScroll: true
    });
};

// --- CUSTOMER MODAL (Sama persis dengan POS) ---
const isCustomerModalOpen = ref(false);
const newCustomerForm = useForm({
    manager_name: '', owner_name: '', ship_name: '', ship_type: 'fishing',
    gross_tonnage: 0, pk_engine: 0, phone: '', address: '', credit_limit: 0, photo: null,
});
const paymentMethods = [
    { value: 'cash', label: 'Tunai (Cash)' },
    { value: 'transfer', label: 'Transfer Bank' },
    { value: 'bon', label: 'Bon / Piutang' },
];

const submitNewCustomer = () => {
    newCustomerForm.post(route('customers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isCustomerModalOpen.value = false;
            newCustomerForm.reset();
            swal.toast('Pelanggan berhasil ditambahkan', 'success');
        },
        onError: () => swal.toast('Gagal menambah pelanggan', 'error')
    });
};
</script>

<template>
    <Head title="Input Transaksi Lampau" />
    <AdminLayout>

        <div class="flex flex-col xl:flex-row gap-6 items-start h-full">

            <div class="w-full xl:w-2/3 space-y-6">

                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Mode Input Transaksi Lampau (Backdate).</strong>
                                Transaksi ini tidak akan dicatat dalam laporan shift operator harian, namun tetap terhitung dalam laporan penjualan/omset dan memotong stok saat ini.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Waktu & Pelanggan
                    </h3>

                    <Alert v-if="Object.keys(form.errors).length > 0" variant="error" title="Perhatian" message="Cek kembali data inputan Anda." class="mb-5"/>
                    <div v-if="form.errors.transaction_date" class="text-sm text-red-600 mb-2">{{ form.errors.transaction_date }}</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div class="flex gap-3">
                            <div class="w-2/3">
                                <DatePicker
                                    v-model="inputDate"
                                    label="Tanggal Transaksi"
                                    :config="{ maxDate: 'today' }"
                                    required
                                />
                            </div>
                            <div class="w-1/3">
                                <TimePicker
                                    v-model="inputTime"
                                    label="Jam"
                                    required
                                />
                            </div>
                        </div>

                        <div class="flex items-end gap-2">
                            <div class="w-full">
                                <SearchableSelect
                                    v-model="form.customer_id"
                                    :options="customerOptions"
                                    label="Cari Pelanggan (Wajib)"
                                    placeholder="Ketik Nama Kapal / Pengurus..."
                                    :error="form.errors.customer_id"
                                    required
                                />
                            </div>
                            <button @click="isCustomerModalOpen = true" type="button" class="flex-shrink-0 h-11 w-11 flex items-center justify-center rounded-lg bg-brand-600 text-white hover:bg-brand-700 transition shadow-sm" title="Tambah Pelanggan Baru">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div v-if="selectedCustomer" class="rounded-xl border border-blue-100 bg-blue-50/50 p-5 dark:border-blue-900/30 dark:bg-blue-900/10 animate-fade-in">
                        <div class="flex flex-col sm:flex-row items-start gap-4">
                            <div class="h-16 w-16 flex-shrink-0 rounded-full border-2 border-white bg-gray-200 overflow-hidden shadow-sm">
                                <img :src="selectedCustomer.photo_url" :alt="selectedCustomer.manager_name" class="h-full w-full object-cover">
                            </div>
                            <div class="flex-1 w-full">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ selectedCustomer.ship_name }}
                                        </h4>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600 dark:text-gray-300 mt-1">
                                            <p>Pengurus: <strong>{{ selectedCustomer.manager_name }}</strong></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">Limit Kredit</span>
                                        <p class="text-lg font-mono font-bold" :class="isOverLimit ? 'text-red-600' : 'text-green-600'">
                                            {{ formatRupiah(selectedCustomer.credit_limit) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                        Pilih Produk BBM
                    </h3>
                    <div v-if="form.errors.items" class="text-sm text-red-600 mb-2">{{ form.errors.items }}</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Produk</label>
                            <SelectInput
                                v-model="form.items[0].product_id"
                                @change="onProductChange(0)"
                                :error="form.errors['items.0.product_id']"
                            >
                                <option value="">-- Silakan Pilih --</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">
                                    {{ p.name }} (Sisa Stok: {{ p.stock }} {{ p.unit }})
                                </option>
                            </SelectInput>
                        </div>
                        <IntegerInput
                            v-model="form.items[0].quantity_liter"
                            label="Volume (Liter)"
                            placeholder="0"
                            suffix="Liter"
                            required
                            :error="form.errors['items.0.quantity_liter']"
                        />
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <span class="text-sm text-gray-500">Harga Satuan</span>
                        <span class="font-mono font-bold text-gray-800 dark:text-white text-lg">{{ formatRupiah(form.items[0].price) }} <span class="text-xs font-normal text-gray-400">/ Liter</span></span>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        Pembayaran
                    </h3>

                    <div v-if="form.payment_method === 'bon' && isOverLimit" class="mb-5 p-4 rounded-lg bg-red-50 border border-red-200 flex items-start gap-3">
                        <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <div>
                            <h4 class="font-bold text-red-700">Limit Kredit Tidak Mencukupi!</h4>
                            <p class="text-sm text-red-600 mt-1">Total tagihan melebihi sisa limit kredit pelanggan.</p>
                        </div>
                    </div>

                    <div v-if="form.payment_method === 'cash' || form.payment_method === 'transfer'" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg flex gap-3 text-sm text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-200 animate-fade-in">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <strong>Info Pembukuan:</strong>
                            <p class="mt-1">Pilih <strong>Cash/Transfer</strong> jika transaksi lampau ini <u>sudah lunas</u> saat itu.</p>
                        </div>
                    </div>

                    <div v-if="form.payment_method === 'bon'" class="mb-4 p-3 bg-orange-50 border border-orange-200 rounded-lg flex gap-3 text-sm text-orange-800 dark:bg-orange-900/20 dark:border-orange-800 dark:text-orange-200 animate-fade-in">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <div>
                            <strong>Pencatatan Piutang:</strong>
                            <p class="mt-1">Pilih <strong>Bon / Piutang</strong> jika sampai hari ini pelanggan <u>masih memiliki tunggakan</u> atas transaksi ini.</p>
                        </div>
                    </div>

                    <div class="mb-5">
                        <SelectInput v-model="form.payment_method" label="Metode Pembayaran" :error="form.errors.payment_method">
                            <option v-for="opt in paymentMethods" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </SelectInput>
                    </div>

                    <div v-if="form.payment_method === 'transfer'" class="animate-fade-in">
                        <FileDropzone v-model="form.payment_proof" label="Upload Bukti Transfer" accept="image/*" :error="form.errors.payment_proof" class="w-full" />
                    </div>

                    <div class="mt-5">
                        <TextArea v-model="form.note" label="Catatan Tambahan (Opsional)" placeholder="Contoh: Plat nomor kendaraan..." />
                    </div>
                </div>

            </div>

            <div class="w-full xl:w-1/3 xl:sticky xl:top-24">
                <div class="rounded-2xl bg-brand-600 text-white shadow-xl overflow-hidden">
                    <div class="p-6">
                        <p class="text-brand-100 text-sm font-medium uppercase tracking-wider mb-1">Total Tagihan</p>
                        <h2 class="text-4xl font-extrabold tracking-tight truncate">{{ formatRupiah(grandTotal) }}</h2>
                    </div>
                    <div class="bg-brand-700/50 p-6 backdrop-blur-sm">
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between items-center text-sm text-brand-100 border-b border-white/10 pb-2">
                                <span>Pelanggan</span><span class="font-medium truncate max-w-[150px]">{{ selectedCustomer ? selectedCustomer.manager_name : '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-brand-100 border-b border-white/10 pb-2">
                                <span>Produk</span><span class="font-medium">{{ products.find(p => p.id == form.items[0].product_id)?.name || '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-brand-100 border-b border-white/10 pb-2">
                                <span>Volume</span><span class="font-medium">{{ form.items[0].quantity_liter || 0 }} Liter</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-brand-100">
                                <span>Tgl Transaksi</span><span class="font-medium">{{ inputDate }} {{ inputTime }}</span>
                            </div>
                        </div>
                        <Button @click="submit" size="md" class="w-full justify-center py-4 text-lg font-bold text-gray-900 border-none shadow-lg disabled:opacity-70 disabled:cursor-not-allowed" :processing="form.processing" :disabled="isSubmitDisabled">
                            <span v-if="!form.processing">SIMPAN (BACKDATE)</span>
                            <span v-else>Memproses...</span>
                        </Button>
                    </div>
                </div>
            </div>

        </div>

        <Modal :show="isCustomerModalOpen" title="Tambah Pelanggan Baru" @close="isCustomerModalOpen = false">
            <form @submit.prevent="submitNewCustomer" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <TextInput v-model="newCustomerForm.manager_name" label="Nama Pengurus" required :error="newCustomerForm.errors.manager_name" />
                    <TextInput v-model="newCustomerForm.owner_name" label="Nama Pemilik" required :error="newCustomerForm.errors.owner_name" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <TextInput v-model="newCustomerForm.ship_name" label="Nama Kapal" required :error="newCustomerForm.errors.ship_name" />
                    <SelectInput v-model="newCustomerForm.ship_type" label="Jenis Kapal" required :error="newCustomerForm.errors.ship_type">
                        <option v-for="type in props.shipTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                    </SelectInput>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <TextInput v-model="newCustomerForm.gross_tonnage" type="number" step="0.01" label="GT Kapal" required :error="newCustomerForm.errors.gross_tonnage" />
                    <TextInput v-model="newCustomerForm.pk_engine" type="number" step="0.01" label="PK Mesin" required :error="newCustomerForm.errors.pk_engine" />
                </div>
                <TextInput v-model="newCustomerForm.phone" label="No. HP" required :error="newCustomerForm.errors.phone" />
                <TextArea v-model="newCustomerForm.address" label="Alamat" required :error="newCustomerForm.errors.address" />

                <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                    <Button type="button" variant="outline" @click="isCustomerModalOpen = false">Batal</Button>
                    <Button type="submit" :processing="newCustomerForm.processing">Simpan</Button>
                </div>
            </form>
        </Modal>

    </AdminLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
</style>
