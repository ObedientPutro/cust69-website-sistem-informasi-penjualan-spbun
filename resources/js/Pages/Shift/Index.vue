<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import TextInput from '@/Components/FormElements/TextInput.vue';
import SelectInput from '@/Components/FormElements/SelectInput.vue';
import FileDropzone from '@/Components/FormElements/FileDropzone.vue';
import Alert from '@/Components/Ui/Alert.vue';
import MetricCard from '@/Components/Metrics/MetricCard.vue';
import Badge from '@/Components/Ui/Badge.vue';

const props = defineProps<{
    activeShift: any; // Object Shift aktif (null jika tidak ada)
    history: any;
    products: any[];
}>();

// --- FORM BUKA SHIFT ---
const openForm = useForm({
    product_id: '',
    opening_totalizer: '',
    opening_proof: null as File | null,
});

const submitOpen = () => {
    openForm.post(route('shifts.store'), {
        onSuccess: () => openForm.reset(),
    });
};

// --- FORM TUTUP SHIFT ---
const closeForm = useForm({
    closing_totalizer: '',
    cash_collected: '', // Uang Fisik
    closing_proof: null as File | null,
});

const submitClose = () => {
    if (!props.activeShift) return;

    closeForm.post(route('shifts.close', props.activeShift.id), {
        onSuccess: () => closeForm.reset(),
    });
};

// Helper Format
const formatDate = (date: string) => new Date(date).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
</script>

<template>
    <Head title="Shift Operasional" />
    <AdminLayout>

        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Operasional Shift (Totalisator)</h2>
            <p class="text-sm text-gray-500">Input meteran awal dan akhir untuk membuka akses transaksi.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

            <div class="space-y-6">

                <div v-if="activeShift" class="rounded-2xl border border-green-200 bg-green-50 p-6 dark:border-green-800 dark:bg-green-900/20">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="animate-pulse h-3 w-3 rounded-full bg-green-500"></span>
                            <h3 class="text-lg font-bold text-green-800 dark:text-green-400">Shift Aktif</h3>
                        </div>
                        <Badge variant="light" color="success">OPEN</Badge>
                    </div>

                    <div class="space-y-3 text-sm text-green-900 dark:text-green-300">
                        <div class="flex justify-between border-b border-green-200 pb-2 dark:border-green-800">
                            <span>Produk</span>
                            <span class="font-bold">{{ activeShift.product?.name }}</span>
                        </div>
                        <div class="flex justify-between border-b border-green-200 pb-2 dark:border-green-800">
                            <span>Waktu Buka</span>
                            <span class="font-mono">{{ formatDate(activeShift.opened_at) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Totalisator Awal</span>
                            <span class="font-mono font-bold text-lg">{{ activeShift.opening_totalizer }}</span>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-center py-6">
                        <div class="mx-auto h-12 w-12 text-gray-400 mb-3">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Tidak ada Shift Aktif</h3>
                        <p class="text-sm text-gray-500">Silakan buka shift baru untuk memulai transaksi penjualan.</p>
                    </div>
                </div>

            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-sm">

                <div v-if="activeShift">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Tutup Shift (Closing)</h3>
                    <form @submit.prevent="submitClose" class="space-y-4">
                        <TextInput
                            v-model="closeForm.closing_totalizer"
                            type="number"
                            step="0.01"
                            label="Angka Totalisator Akhir (Wajib)"
                            placeholder="Contoh: 12500.50"
                            required
                            :error="closeForm.errors.closing_totalizer"
                        />
                        <p class="text-xs text-gray-500">*Harus lebih besar dari {{ activeShift.opening_totalizer }}</p>

                        <TextInput
                            v-model="closeForm.cash_collected"
                            type="number"
                            label="Total Uang Fisik (Cash)"
                            placeholder="Hitung uang di laci..."
                            required
                            :error="closeForm.errors.cash_collected"
                        />

                        <FileDropzone
                            v-model="closeForm.closing_proof"
                            label="Foto Meteran Akhir (Opsional)"
                            accept="image/*"
                        />

                        <div class="pt-4 border-t dark:border-gray-700">
                            <Button type="submit" variant="danger" class="w-full justify-center" :processing="closeForm.processing">
                                Tutup Shift & Kunci Transaksi
                            </Button>
                        </div>
                    </form>
                </div>

                <div v-else>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Buka Shift Baru</h3>
                    <form @submit.prevent="submitOpen" class="space-y-4">

                        <SelectInput
                            v-model="openForm.product_id"
                            label="Pilih Produk / Nozzle"
                            required
                            :error="openForm.errors.product_id"
                        >
                            <option value="">-- Pilih Produk BBM --</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} (Stok: {{ p.stock }})</option>
                        </SelectInput>

                        <Alert
                            v-if="openForm.errors.product_id"
                            variant="error"
                            :message="openForm.errors.product_id"
                            class="mt-1"
                        />

                        <TextInput
                            v-model="openForm.opening_totalizer"
                            type="number"
                            step="0.01"
                            label="Angka Totalisator Awal"
                            placeholder="Masukkan angka di mesin..."
                            required
                            :error="openForm.errors.opening_totalizer"
                        />

                        <FileDropzone
                            v-model="openForm.opening_proof"
                            label="Foto Meteran Awal (Opsional)"
                            accept="image/*"
                        />

                        <div class="pt-4 border-t dark:border-gray-700">
                            <Button type="submit" variant="primary" class="w-full justify-center" :processing="openForm.processing">
                                Buka Shift Sekarang
                            </Button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <h3 class="font-bold text-gray-800 dark:text-white">Riwayat Shift Anda</h3>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 border-b dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3">Tanggal</th>
                    <th class="px-6 py-3">Produk</th>
                    <th class="px-6 py-3 text-right">Meter Awal</th>
                    <th class="px-6 py-3 text-right">Meter Akhir</th>
                    <th class="px-6 py-3 text-center">Status</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="item in history.data" :key="item.id">
                    <td class="px-6 py-3">{{ formatDate(item.date) }}</td>
                    <td class="px-6 py-3 font-bold">{{ item.product.name }}</td>
                    <td class="px-6 py-3 text-right font-mono">{{ item.opening_totalizer }}</td>
                    <td class="px-6 py-3 text-right font-mono">{{ item.closing_totalizer || '-' }}</td>
                    <td class="px-6 py-3 text-center">
                        <Badge :color="item.status === 'open' ? 'success' : (item.status === 'audited' ? 'primary' : 'warning')">
                            {{ item.status.toUpperCase() }}
                        </Badge>
                    </td>
                </tr>
                <tr v-if="history.data.length === 0">
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada riwayat shift.</td>
                </tr>
                </tbody>
            </table>
        </div>

    </AdminLayout>
</template>

<style scoped>

</style>
