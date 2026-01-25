<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import DateTimePicker from '@/Components/FormElements/DateTimePicker.vue';
import FileDropzone from '@/Components/FormElements/FileDropzone.vue';
import Alert from '@/Components/Ui/Alert.vue';

const props = defineProps<{
    products: any[];
    customers: any[];
}>();

const page = usePage();
const isOwner = computed(() => page.props.auth.user.role == 'owner');

const form = useForm({
    transaction_date: new Date().toISOString().split('T')[0],
    customer_id: '',
    payment_method: 'cash',
    payment_proof: null as File | null,
    note: '',
    items: [
        { product_id: '', quantity_liter: '', price: 0 }
    ]
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

const submit = () => {
    form.post(route('transactions.save'), {
        onSuccess: () => {
            form.reset();
            form.transaction_date = new Date().toISOString().split('T')[0];
            form.items = [{ product_id: '', quantity_liter: '', price: 0 }];
        },
        preserveScroll: true
    });
};

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
</script>

<template>
    <Head title="Kasir SPBU" />
    <AdminLayout>

        <div class="flex flex-col lg:flex-row gap-6 items-start h-full">

            <div class="w-full lg:w-2/3 space-y-6">

                <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        Data Transaksi
                    </h3>

                    <Alert v-if="Object.keys(form.errors).length > 0" variant="error" title="Perhatian" message="Mohon lengkapi data yang wajib diisi." class="mb-5"/>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <DateTimePicker
                            v-model="form.transaction_date"
                            label="Waktu Transaksi"
                            :disabled="!isOwner"
                            :error="form.errors.transaction_date"
                            placeholder="Pilih Tanggal & Jam"
                        />

                        <SelectInput
                            v-model="form.payment_method"
                            label="Metode Pembayaran"
                            class="w-full"
                        >
                            <option v-for="opt in paymentMethods" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </SelectInput>
                    </div>

                    <div v-if="form.payment_method === 'bon'" class="mt-5 p-4 bg-orange-50 rounded-xl border border-orange-100 dark:bg-orange-900/10 dark:border-orange-800/30 animate-fade-in">
                        <SelectInput
                            v-model="form.customer_id"
                            label="Pilih Pelanggan (Wajib)"
                            :error="form.errors.customer_id"
                        >
                            <option value="">-- Cari Nama Nelayan --</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">
                                {{ c.name }} (Limit: {{ formatRupiah(c.credit_limit) }})
                            </option>
                        </SelectInput>

                        <div v-if="selectedCustomer" class="mt-2 flex items-center justify-between text-xs px-3 py-2 rounded-lg border"
                             :class="isOverLimit ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
                        >
                            <span>Sisa Limit Kredit: <strong>{{ formatRupiah(selectedCustomer.credit_limit) }}</strong></span>
                            <span v-if="isOverLimit" class="font-bold flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Limit Tidak Cukup!
                            </span>
                            <span v-else class="font-bold">Aman</span>
                        </div>
                    </div>

                    <div v-if="form.payment_method === 'transfer'" class="mt-5 animate-fade-in">
                        <FileDropzone
                            v-model="form.payment_proof"
                            label="Upload Bukti Transfer"
                            accept="image/*"
                            :error="form.errors.payment_proof"
                        />
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Rincian Bahan Bakar
                    </h3>

                    <div class="p-5 bg-gray-50 rounded-xl border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Produk</label>
                                <SelectInput
                                    v-model="form.items[0].product_id"
                                    @change="onProductChange(0)"
                                    :error="form.errors['items.0.product_id']"
                                >
                                    <option value="">-- Produk BBM --</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">
                                        {{ p.name }} (Stok: {{ p.stock }} {{ p.unit }})
                                    </option>
                                </SelectInput>
                            </div>

                            <TextInput
                                v-model="form.items[0].quantity_liter"
                                type="number"
                                step="0.01"
                                label="Volume (Liter)"
                                placeholder="0.00"
                                :error="form.errors['items.0.quantity_liter']"
                            />
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <span class="text-sm text-gray-500">Harga Satuan</span>
                            <span class="font-mono font-bold text-gray-800 dark:text-white text-lg">
                                {{ formatRupiah(form.items[0].price) }} <span class="text-xs font-normal text-gray-400">/ Liter</span>
                            </span>
                        </div>
                    </div>

                    <div class="mt-5">
                        <TextArea v-model="form.note" label="Catatan Tambahan" placeholder="Contoh: Plat nomor kendaraan..." />
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-1/3 lg:sticky lg:top-24">
                <div class="rounded-2xl bg-brand-600 text-white shadow-xl overflow-hidden">
                    <div class="p-6">
                        <p class="text-brand-100 text-sm font-medium uppercase tracking-wider mb-1">Total Tagihan</p>
                        <h2 class="text-4xl font-extrabold tracking-tight truncate">
                            {{ formatRupiah(grandTotal) }}
                        </h2>
                    </div>

                    <div class="bg-brand-700/50 p-6 backdrop-blur-sm">
                        <div class="flex justify-between items-center mb-2 text-sm text-brand-100">
                            <span>Metode</span>
                            <span class="font-bold bg-white/20 px-2 py-0.5 rounded capitalize">{{ form.payment_method }}</span>
                        </div>
                        <div v-if="form.payment_method === 'bon'" class="flex justify-between items-center mb-4 text-sm text-brand-100">
                            <span>Pelanggan</span>
                            <span class="font-medium truncate max-w-[150px]">
                                {{ customers.find(c => c.id == form.customer_id)?.name || '-' }}
                            </span>
                        </div>

                        <Button
                            @click="submit"
                            variant="primary"
                            size="md"
                            class="w-full justify-center py-4 text-lg font-bold hover:bg-gray-400 border-none shadow-none"
                            :processing="form.processing"
                            :disabled="grandTotal <= 0 || isOverLimit"
                        >
                            <span v-if="!form.processing">PROSES BAYAR</span>
                            <span v-else>Memproses...</span>
                        </Button>
                    </div>
                </div>

                <p class="mt-4 text-xs text-center text-gray-400">
                    Pastikan data sudah benar sebelum memproses transaksi.
                </p>
            </div>

        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
