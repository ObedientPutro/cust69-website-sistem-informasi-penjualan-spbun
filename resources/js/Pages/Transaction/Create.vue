<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import DecimalInput from '@/Components/FormElements/DecimalInput.vue';
import SearchableSelect from '@/Components/FormElements/SearchableSelect.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import FileDropzone from '@/Components/FormElements/FileDropzone.vue';
import Modal from '@/Components/Ui/Modal.vue';
import Alert from '@/Components/Ui/Alert.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';

const props = defineProps<{
    products: any[];
    customers: any[];
    shipTypes: { value: string, label: string }[];
    activeShifts: Record<string, number>;
}>();

const page = usePage();
const swal = useSweetAlert();
const isOwner = computed(() => page.props.auth.user.role == 'owner');

// --- DATE & TIME HANDLING ---
const todayDate = new Date().toISOString().split('T')[0]; // YYYY-MM-DD (Fixed)
const currentTime = ref(new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false })); // HH:mm (Editable)

const form = useForm({
    pump_shift_id: null as number | null,
    transaction_date: '', // Will be combined on submit
    customer_id: '',
    payment_method: 'cash',
    payment_proof: null as File | null,
    note: '',
    items: [{ product_id: '', quantity_liter: '0.000', price: 0 }]
});

// ... (Logic customerOptions, activeShifts, isShiftOpen, dll tetap sama) ...
const customerOptions = computed(() => {
    return props.customers.map(c => ({
        value: c.id,
        label: `${c.ship_name} (${c.manager_name})`,
        subLabel: `Pemilik: ${c.owner_name || '-'} | GT: ${c.gross_tonnage}`
    }));
});

const currentProductShiftId = computed(() => {
    const prodId = form.items[0].product_id;
    if (!prodId) return null;
    return props.activeShifts[prodId] || null;
});

const isShiftOpen = computed(() => {
    if (!form.items[0].product_id) return true;
    return !!currentProductShiftId.value;
});

watch(() => form.items[0].product_id, (newVal) => {
    if (newVal && props.activeShifts[newVal]) {
        form.pump_shift_id = props.activeShifts[newVal];
    } else {
        form.pump_shift_id = null;
    }
});

const isSubmitDisabled = computed(() => {
    return grandTotal.value <= 0 || isOverLimit.value || !isShiftOpen.value || form.processing;
});

// ... (Customer Modal Logic tetap sama) ...
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

const selectedCustomer = computed(() => {
    return props.customers.find(c => c.id == form.customer_id);
});

const isOverLimit = computed(() => {
    if (form.payment_method == 'bon' && selectedCustomer.value) {
        return grandTotal.value > parseFloat(selectedCustomer.value.credit_limit);
    }
    return false;
});

const onProductChange = (index: number) => {
    const selectedId = form.items[index].product_id;
    const product = props.products.find(p => p.id == selectedId);
    form.items[index].price = product ? parseFloat(product.price) : 0;
};

const grandTotal = computed(() => {
    return form.items.reduce((sum, item) => {
        const qty = parseFloat(item.quantity_liter) || 0;
        return sum + (qty * item.price);
    }, 0);
});

// --- SUBMIT ---
const submit = () => {
    // Gabungkan Tanggal Fixed + Jam Inputan
    form.transaction_date = `${todayDate} ${currentTime.value}`;

    form.post(route('transactions.save'), {
        onSuccess: () => {
            form.reset();
            form.items = [{ product_id: '', quantity_liter: '', price: 0 }];
            // Reset jam ke sekarang lagi
            currentTime.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false });
            swal.toast('Transaksi Berhasil!', 'success');
        },
        preserveScroll: true
    });
};

const submitNewCustomer = () => {
    newCustomerForm.post(route('customers.save'), {
        preserveScroll: true,
        onSuccess: () => {
            isCustomerModalOpen.value = false;
            newCustomerForm.reset();
            swal.toast('Pelanggan berhasil ditambahkan', 'success');
        },
        onError: () => swal.toast('Gagal menambah pelanggan', 'error')
    });
};

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
</script>

<template>
    <Head title="Kasir POS" />
    <AdminLayout>

        <div class="flex flex-col xl:flex-row gap-6 items-start h-full">

            <div class="w-full xl:w-2/3 space-y-6">

                <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Waktu & Pelanggan
                    </h3>

                    <Alert v-if="Object.keys(form.errors).length > 0" variant="error" title="Perhatian" message="Cek kembali data inputan Anda." class="mb-5"/>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div class="flex gap-3">
                            <div class="w-2/3">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal (Hari Ini)</label>
                                <input
                                    type="date"
                                    :value="todayDate"
                                    disabled
                                    class="w-full rounded-lg border-gray-300 bg-gray-100 text-gray-500 cursor-not-allowed dark:bg-gray-800 dark:border-gray-700"
                                >
                            </div>
                            <div class="w-1/3">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam</label>
                                <input
                                    type="time"
                                    v-model="currentTime"
                                    class="w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-700"
                                >
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
                                            <p>Pemilik: <strong>{{ selectedCustomer.owner_name }}</strong></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">Limit Kredit</span>
                                        <p class="text-lg font-mono font-bold" :class="isOverLimit ? 'text-red-600' : 'text-green-600'">
                                            {{ formatRupiah(selectedCustomer.credit_limit) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                                    <div class="p-2 bg-white dark:bg-gray-800 rounded border border-blue-100 dark:border-gray-700">
                                        <span class="block text-gray-400">GT Kapal</span>
                                        <span class="font-bold text-gray-700 dark:text-gray-200">{{ selectedCustomer.gross_tonnage }} Ton</span>
                                    </div>
                                    <div class="p-2 bg-white dark:bg-gray-800 rounded border border-blue-100 dark:border-gray-700">
                                        <span class="block text-gray-400">Mesin (PK)</span>
                                        <span class="font-bold text-gray-700 dark:text-gray-200">{{ selectedCustomer.pk_engine }} HP</span>
                                    </div>
                                    <div class="p-2 bg-white dark:bg-gray-800 rounded border border-blue-100 dark:border-gray-700">
                                        <span class="block text-gray-400">Jenis</span>
                                        <span class="font-bold text-gray-700 dark:text-gray-200 capitalize">{{ selectedCustomer.ship_type.label || selectedCustomer.ship_type }}</span>
                                    </div>
                                    <div class="p-2 bg-white dark:bg-gray-800 rounded border border-blue-100 dark:border-gray-700">
                                        <span class="block text-gray-400">No HP</span>
                                        <span class="font-bold text-gray-700 dark:text-gray-200">{{ selectedCustomer.phone }}</span>
                                    </div>
                                </div>

                                <div class="mt-2 text-xs text-gray-500">
                                    <span class="font-semibold">Alamat:</span> {{ selectedCustomer.address }}
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
                        <DecimalInput
                            v-model="form.items[0].quantity_liter"
                            label="Volume Pembelian"
                            placeholder="0.000"
                            suffix="Liter"
                            required
                            :error="form.errors['items.0.quantity_liter']"
                        />
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <span class="text-sm text-gray-500">Harga Satuan</span>
                        <span class="font-mono font-bold text-gray-800 dark:text-white text-lg">{{ formatRupiah(form.items[0].price) }} <span class="text-xs font-normal text-gray-400">/ Liter</span></span>
                    </div>
                    <div v-if="form.items[0].product_id && !isShiftOpen" class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 flex items-start gap-3 animate-fade-in">
                        <div class="p-2 bg-red-100 rounded-full text-red-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg></div>
                        <div>
                            <h4 class="font-bold text-red-800 text-lg">Shift Belum Dibuka!</h4>
                            <p class="text-sm text-red-700 mt-1">Produk ini belum memiliki shift aktif. Silakan buka shift terlebih dahulu.</p>
                        </div>
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
                                <span>Metode</span><span class="font-bold bg-white/20 px-2 py-0.5 rounded capitalize">{{ form.payment_method }}</span>
                            </div>
                        </div>
                        <Button @click="submit" size="md" class="w-full justify-center py-4 text-lg font-bold text-gray-900 border-none shadow-lg disabled:opacity-70 disabled:cursor-not-allowed" :processing="form.processing" :disabled="isSubmitDisabled">
                            <span v-if="!isShiftOpen">SHIFT TERKUNCI</span>
                            <span v-else-if="!form.processing">PROSES BAYAR</span>
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
                        <option v-for="type in shipTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
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
