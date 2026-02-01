<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';

// State
const dropdownOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);
const notifications = ref<any[]>([]);
const unreadCount = ref(0);
const isLoading = ref(false);
const lastNotificationId = ref<string | null>(null);

// --- API CALL ---
const fetchNotifications = async () => {
    try {
        const response = await axios.get(route('notifications.json'));
        const newNotifications = response.data.notifications;
        const newCount = response.data.unread_count;

        // LOGIC WEB PUSH ANTI-SPAM:
        if (newCount > 0 && newNotifications.length > 0) {
            const latest = newNotifications[0];

            // Ambil ID terakhir yang pernah muncul dari LocalStorage
            const storedLastId = localStorage.getItem('last_pushed_notif_id');

            // Cek apakah ID ini BEDA dari yang disimpan & statusnya belum dibaca
            if (String(latest.id) !== storedLastId && !latest.read_at) {

                showBrowserNotification(latest.title, latest.message);

                // SIMPAN ID ke LocalStorage agar tidak muncul lagi saat reload
                localStorage.setItem('last_pushed_notif_id', String(latest.id));
            }
        }

        notifications.value = newNotifications;
        unreadCount.value = newCount;
    } catch (error) {
        console.error("Gagal memuat notifikasi");
    }
};

const showBrowserNotification = (title: string, body: string) => {
    if (!("Notification" in window)) return;

    if (Notification.permission === "granted") {
        new Notification(title, {
            body: body,
            icon: '/images/logo/auth-logo.svg', // Pastikan path icon benar
        });
    }
};

// --- ACTIONS ---
const toggleDropdown = () => {
    dropdownOpen.value = !dropdownOpen.value;
    // Jika dibuka, kita refresh datanya biar up-to-date
    if (dropdownOpen.value) {
        fetchNotifications();
    }
};

const closeDropdown = () => {
    dropdownOpen.value = false;
};

const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        closeDropdown();
    }
};

const handleItemClick = (notification: any) => {
    closeDropdown();

    // 1. Tandai sudah dibaca via backend (async)
    if (!notification.read_at) {
        router.patch(route('notifications.read', notification.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Update UI lokal biar responsif
                unreadCount.value = Math.max(0, unreadCount.value - 1);
                notification.read_at = new Date().toISOString();
            }
        });
    }

    // 2. Redirect jika ada URL
    if (notification.url) {
        router.visit(notification.url);
    }
};

const handleViewAllClick = () => {
    closeDropdown();
    router.visit(route('notifications.index'));
};

// --- HELPER UI ---
const getIconColor = (type: string) => {
    switch (type) {
        case 'success': return 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400';
        case 'warning': return 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400';
        case 'error': return 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400';
        default: return 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400';
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    fetchNotifications();
    setInterval(fetchNotifications, 10000);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative" ref="dropdownRef">

        <button
            class="relative flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
            @click="toggleDropdown"
        >
            <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 z-10 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white ring-2 ring-white dark:ring-gray-900"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}

                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
            </span>

            <svg
                class="fill-current"
                width="20"
                height="20"
                viewBox="0 0 20 20"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z"
                    fill=""
                />
            </svg>
        </button>

        <div
            v-if="dropdownOpen"
            class="shadow-theme-lg dark:bg-gray-dark absolute -right-[80px] mt-2.5 flex h-auto max-h-[480px] w-[300px] sm:w-[360px] flex-col rounded-2xl border border-gray-200 bg-white p-4 sm:right-0 dark:border-gray-800 z-50"
        >
            <div class="mb-3 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                <h5 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Notifikasi
                </h5>
                <span class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-600 dark:text-gray-300">
                    {{ unreadCount }} Baru
                </span>
            </div>

            <ul class="flex h-auto flex-col overflow-y-auto custom-scrollbar gap-1">

                <li v-if="notifications.length === 0" class="text-center py-8 text-gray-500 text-sm">
                    Tidak ada notifikasi terbaru.
                </li>

                <li
                    v-for="item in notifications"
                    :key="item.id"
                    @click="handleItemClick(item)"
                    class="cursor-pointer rounded-lg p-3 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group"
                    :class="{ 'bg-blue-50/50 dark:bg-blue-900/10': !item.read_at }"
                >
                    <div class="flex gap-3">
                        <div
                            class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                            :class="getIconColor(item.type)"
                        >
                            <span v-if="item.type === 'success'" class="text-lg">✅</span>
                            <span v-else-if="item.type === 'warning'" class="text-lg">⚠️</span>
                            <span v-else-if="item.type === 'error'" class="text-lg">🚨</span>
                            <span v-else class="text-lg">ℹ️</span>

                            <span v-if="!item.read_at" class="absolute top-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-blue-500 dark:border-gray-900"></span>
                        </div>

                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800 dark:text-white mb-0.5 line-clamp-1">
                                {{ item.title }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 leading-relaxed">
                                {{ item.message }}
                            </p>
                            <p class="text-[10px] text-gray-400 mt-1">
                                {{ item.created_at }}
                            </p>
                        </div>
                    </div>
                </li>
            </ul>

            <button
                class="mt-3 flex w-full justify-center rounded-lg border border-gray-200 bg-white p-2 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 transition"
                @click="handleViewAllClick"
            >
                Lihat Semua Notifikasi
            </button>
        </div>
    </div>
</template>
