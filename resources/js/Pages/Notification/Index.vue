<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';

const props = defineProps<{
    notifications: any;
}>();

const swal = useSweetAlert();

// --- ACTIONS ---
const markAsRead = (id: string) => {
    router.patch(route('notifications.read', id), {}, { preserveScroll: true });
};

const deleteNotification = (id: string) => {
    // Alert untuk Hapus per item
    swal.confirm('Hapus Notifikasi?', 'Notifikasi ini akan dihapus permanen.')
        .then((res) => {
            if (res.isConfirmed) {
                router.delete(route('notifications.destroy', id), { preserveScroll: true });
            }
        });
};

const markAllRead = () => {
    // FIX: Teks Alert diperbaiki (Bukan Hapus)
    swal.confirm('Tandai Semua Dibaca?', 'Status notifikasi baru akan diubah menjadi terbaca.', 'Ya, tandai')
        .then((res) => {
            if (res.isConfirmed) {
                router.post(route('notifications.read-all'), {}, {
                    onSuccess: () => swal.toast('Berhasil ditandai dibaca.', 'success')
                });
            }
        });
};

const deleteAllRead = () => {
    swal.confirm('Bersihkan Riwayat?', 'Semua notifikasi yang SUDAH DIBACA akan dihapus permanen.')
        .then((res) => {
            if (res.isConfirmed) {
                router.delete(route('notifications.destroy-read'), {
                    onSuccess: () => swal.toast('Riwayat notifikasi dibersihkan.', 'success')
                });
            }
        });
};

// ... (Helper Icon & BgClass sama seperti sebelumnya) ...
const getIcon = (type: string) => {
    switch (type) {
        case 'success': return '✅';
        case 'warning': return '⚠️';
        case 'error': return '🚨';
        default: return 'ℹ️';
    }
};

const getBgClass = (type: string) => {
    switch (type) {
        case 'success': return 'bg-green-50 dark:bg-green-900/10 border-green-100 dark:border-green-800';
        case 'warning': return 'bg-orange-50 dark:bg-orange-900/10 border-orange-100 dark:border-orange-800';
        case 'error': return 'bg-red-50 dark:bg-red-900/10 border-red-100 dark:border-red-800';
        default: return 'bg-blue-50 dark:bg-blue-900/10 border-blue-100 dark:border-blue-800';
    }
};

const timeAgo = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
};
</script>

<template>
    <Head title="Pusat Notifikasi" />
    <AdminLayout>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">Notifikasi Sistem</h2>
                <p class="text-sm text-gray-500">Pantau aktivitas operator dan status sistem.</p>
            </div>

            <div class="flex gap-2">
                <Button
                    v-if="notifications.total > 0"
                    @click="markAllRead"
                    variant="outline"
                    size="sm"
                >
                    ✓ Tandai Semua Dibaca
                </Button>

                <Button
                    v-if="notifications.total > 0"
                    @click="deleteAllRead"
                    variant="danger"
                    size="sm"
                >
                    🗑️ Hapus Riwayat
                </Button>
            </div>
        </div>

        <div class="space-y-3">
            <div
                v-for="notif in notifications.data"
                :key="notif.id"
                class="relative rounded-xl border p-4 transition-all flex flex-col sm:flex-row gap-4 items-start"
                :class="[
                    getBgClass(notif.data.type),
                    notif.read_at ? 'opacity-70 grayscale-[0.5]' : 'opacity-100 border-l-4 border-l-brand-500 shadow-md'
                ]"
            >
                <div class="flex-shrink-0 text-2xl bg-white dark:bg-gray-800 p-2 rounded-full shadow-sm w-12 h-12 flex items-center justify-center">
                    {{ getIcon(notif.data.type) }}
                </div>

                <div class="flex-1 w-full">
                    <div class="flex justify-between items-start">
                        <h4 class="font-bold text-gray-800 dark:text-white text-base">
                            {{ notif.data.title }}
                            <span v-if="!notif.read_at" class="ml-2 inline-block w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                        </h4>
                        <span class="text-xs text-gray-500 whitespace-nowrap">{{ timeAgo(notif.created_at) }}</span>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 mb-3 leading-relaxed">
                        {{ notif.data.message }}
                    </p>

                    <div v-if="notif.data.url" class="flex flex-wrap gap-2 items-center text-xs">
                        <a
                            :href="notif.data.url"
                            class="text-brand-600 font-medium hover:underline flex items-center gap-1"
                        >
                            Lihat Detail →
                        </a>
                    </div>
                </div>

                <div class="flex sm:flex-col gap-2 mt-2 sm:mt-0 self-end sm:self-center">
                    <button
                        v-if="!notif.read_at"
                        @click="markAsRead(notif.id)"
                        class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition"
                        title="Tandai Sudah Dibaca"
                    >
                        ✓
                    </button>

                    <button
                        @click="deleteNotification(notif.id)"
                        class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                        title="Hapus Notifikasi"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <div v-if="notifications.data.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <p class="text-gray-500">Tidak ada notifikasi.</p>
            </div>
        </div>

        <div v-if="notifications.links.length > 3" class="mt-6 flex justify-center gap-1">
            <Link
                v-for="(link, i) in notifications.links"
                :key="i"
                :href="link.url ?? '#'"
                v-html="link.label"
                class="px-3 py-1 border rounded text-sm"
                :class="link.active ? 'bg-brand-500 text-white' : 'bg-white text-gray-700'"
            />
        </div>

    </AdminLayout>
</template>
