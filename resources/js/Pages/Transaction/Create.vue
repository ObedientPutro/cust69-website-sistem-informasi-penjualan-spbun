<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import TextArea from '@/Components/FormElements/TextArea.vue';
import DatePicker from '@/Components/FormElements/DatePicker.vue';
import Alert from '@/Components/Ui/Alert.vue';
import MetricCard from '@/Components/Metrics/MetricCard.vue';

const props = defineProps<{
    products: any[];
    customers: any[];
}>();

const page = usePage();
// Cek Role untuk REQ-C02 (Backdate)
const isOwner = computed(() => page.props.auth.user.role === 'owner');

// Form State
const form = useForm({
    transaction_date: new Date().toISOString().split('T')[0],
    customer_id: '',
    payment_method: 'cash',
    payment_proof: null as File | null,
    note: '',
    // Items (Support multiple, tapi default 1 row untuk SPBU)
    items: [
        { product_id: '', quantity_liter: 0, price: 0 } // price untuk display
    ]
});

// Helper: Payment Options
const paymentMethods = [
    { value: 'cash', label: 'Tunai (Cash)' },
    { value: 'transfer', label: 'Transfer Bank' },
    { value: 'bon', label: 'Bon / Piutang' },
];

// --- REACTIVE LOGIC ---

// 1. Update Harga saat Produk Dipilih
const onProductChange = (index: number) => {
    const selectedId = form.items[index].product_id;
    const product = props.products.find(p => p.id == selectedId);

    if (product) {
        form.items[index].price = parseFloat(product.price);
    } else {
        form.items[index].price = 0;
    }
};

// 2. Hitung Subtotal per Item
const getSubtotal = (index: number) => {
    const item = form.items[index];
    return (parseFloat(item.quantity_liter as any) || 0) * item.price;
};

// 3. Hitung Grand Total
const grandTotal = computed(() => {
    return form.items.reduce((sum, item, index) => sum + getSubtotal(index), 0);
});

// 4. Handle File Upload
const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.payment_proof = target.files[0];
    }
};

// 5. Submit
const submit = () => {
    form.post(route('transactions.save'), {
        onSuccess: () => {
            form.reset();
            // Reset tanggal ke hari ini
            form.transaction_date = new Date().toISOString().split('T')[0];
            // Reset manual item pertama
            form.items = [{ product_id: '', quantity_liter: 0, price: 0 }];
        },
        preserveScroll: true
    });
};

const formatRupiah = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

</script>

<template>
    <Head title="Kasir / Input Penjualan" />
    <AdminLayout>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b pb-2 dark:border-gray-700">
                        Input Penjualan
                    </h3>

                    <Alert v-if="Object.keys(form.errors).length > 0" variant="error" title="Error" message="Periksa kembali inputan Anda." class="mb-4"/>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <DatePicker
                                v-model="form.transaction_date"
                                label="Tanggal Transaksi"
                                :disabled="!isOwner"
                                :error="form.errors.transaction_date"
                            />
                            <p v-if="!isOwner" class="text-xs text-gray-400 mt-1">*Hanya Owner yang bisa ubah tanggal.</p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Metode Pembayaran</label>
                            <SelectInput
                                v-model="form.payment_method"
                                class="w-full rounded-lg border border-gray-300 bg-transparent py-2.5 px-4 text-sm focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            >
                                <option v-for="opt in paymentMethods" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </SelectInput>
                        </div>
                    </div>

                    <div v-if="form.payment_method === 'bon'" class="mb-4 p-4 bg-orange-50 rounded-lg border border-orange-100 dark:bg-orange-900/10 dark:border-orange-800">
                        <label class="mb-1.5 block text-sm font-medium text-orange-800 dark:text-orange-400">Pilih Pelanggan (Wajib)</label>
                        <SelectInput
                            v-model="form.customer_id"
                            class="w-full rounded-lg border border-orange-300 bg-white py-2.5 px-4 text-sm focus:border-orange-500"
                        >
                            <option value="">-- Pilih Customer --</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </SelectInput>
                        <span v-if="form.errors.customer_id" class="text-xs text-red-500">{{ form.errors.customer_id }}</span>
                    </div>

                    <div v-if="form.payment_method === 'transfer'" class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-100 dark:bg-blue-900/10 dark:border-blue-800">
                        <label class="mb-1.5 block text-sm font-medium text-blue-800 dark:text-blue-400">Bukti Transfer (Wajib)</label>
                        <input
                            type="file"
                            @change="handleFileChange"
                            accept="image/*"
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200"
                        />
                        <span v-if="form.errors.payment_proof" class="text-xs text-red-500">{{ form.errors.payment_proof }}</span>
                    </div>

                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 dark:bg-gray-800/50 dark:border-gray-700">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Produk BBM</label>
                                <SelectInput
                                    v-model="form.items[0].product_id"
                                    @change="onProductChange(0)"
                                    class="w-full rounded-lg border border-gray-300 bg-white py-2.5 px-4 text-sm focus:border-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="">-- Pilih Produk --</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">
                                        {{ p.name }} (Stok: {{ p.stock }} {{ p.unit }})
                                    </option>
                                </SelectInput>
                                <span v-if="form.errors['items.0.product_id']" class="text-xs text-red-500">Pilih produk dulu.</span>
                            </div>

                            <div>
                                <TextInput
                                    v-model="form.items[0].quantity_liter"
                                    type="number"
                                    step="0.01"
                                    label="Jumlah Liter"
                                    placeholder="0.00"
                                />
                                <span v-if="form.errors['items.0.quantity_liter']" class="text-xs text-red-500">Isi jumlah liter.</span>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center text-sm text-gray-500 dark:text-gray-400 border-t pt-3 dark:border-gray-700">
                            <span>Harga / Liter:</span>
                            <span class="font-mono font-semibold text-gray-800 dark:text-gray-200">
                                {{ formatRupiah(form.items[0].price) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <TextArea v-model="form.note" label="Catatan (Opsional)" placeholder="Nomor Plat Kendaraan, dll..." />
                    </div>

                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="sticky top-24 space-y-6">

                    <div class="rounded-2xl bg-brand-600 p-6 text-white shadow-lg">
                        <p class="text-brand-100 text-sm font-medium uppercase tracking-wider">Total Tagihan</p>
                        <h2 class="text-3xl font-bold mt-2">{{ formatRupiah(grandTotal) }}</h2>
                        <p class="mt-4 text-sm text-brand-100 opacity-80">
                            {{ form.items[0].quantity_liter }} Liter &times; {{ formatRupiah(form.items[0].price) }}
                        </p>
                    </div>

                    <Button
                        @click="submit"
                        variant="primary"
                        size="md"
                        class="w-full justify-center py-4 text-lg shadow-xl"
                        :processing="form.processing"
                        :disabled="grandTotal <= 0"
                    >
                        Proses Pembayaran
                    </Button>

                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<style scoped>

</style>
