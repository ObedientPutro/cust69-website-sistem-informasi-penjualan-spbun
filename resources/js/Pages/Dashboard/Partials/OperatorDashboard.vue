<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// UPDATE PROPS DISINI
const props = defineProps<{
    active_shifts: any[]; // Terima langsung array shift
    inventory: any[];     // Terima data stok
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const currentDate = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
</script>

<template>
    <div class="max-w-5xl mx-auto space-y-8 mt-4">

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold uppercase tracking-wide">Operator Area</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ currentDate }}</span>
                </div>
                <h1 class="text-3xl font-extrabold text-gray-800 dark:text-white">
                    Selamat Bertugas, <span class="text-brand-600">{{ user.name }}!</span> 👋
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-2 text-lg">
                    Jangan lupa untuk selalu <strong class="text-gray-700 dark:text-gray-200">Cek Fisik (Sounding)</strong> sebelum memulai dan setelah menutup shift.
                </p>
            </div>
            <div class="hidden md:block">
                <div class="h-24 w-24 bg-brand-50 dark:bg-brand-900/20 rounded-full flex items-center justify-center text-brand-500 dark:text-brand-400">
                    <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-sm flex flex-col h-full">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Status Shift Hari Ini</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pantauan shift operasional yang sedang berjalan.</p>
                    </div>
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600 dark:text-blue-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    </div>
                </div>

                <div class="space-y-4 flex-1">
                    <div v-if="active_shifts && active_shifts.length > 0">
                        <div v-for="(shift, idx) in active_shifts" :key="idx" class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/10 border border-green-100 dark:border-green-800 rounded-xl transition hover:shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-green-200 dark:bg-green-800 flex items-center justify-center text-green-700 dark:text-green-100 font-bold">
                                    {{ shift.product_name.charAt(0) }}
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800 dark:text-white">{{ shift.product_name }}</p>
                                    <p class="text-xs text-green-700 dark:text-green-400 font-medium">Petugas: {{ shift.opener_name }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-1 bg-white dark:bg-gray-800 text-green-600 dark:text-green-400 text-xs font-bold rounded shadow-sm border border-green-100 dark:border-green-900 uppercase">
                                    OPEN
                                </span>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 flex items-center justify-end gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ shift.opened_at_time }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 mb-3 text-gray-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 font-medium">Belum ada shift yang dibuka.</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">Pompa terkunci. Buka shift untuk mulai.</p>

                        <Link :href="route('shifts.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-bold hover:bg-brand-700 transition shadow-lg shadow-brand-200 dark:shadow-none">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            Buka Shift Sekarang
                        </Link>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-sm flex flex-col h-full">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Ketersediaan Stok</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Estimasi sisa stok di tangki saat ini.</p>
                    </div>
                    <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg text-orange-600 dark:text-orange-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    </div>
                </div>

                <div class="space-y-5 flex-1">
                    <div v-for="item in inventory" :key="item.id" class="group">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ item.name }}</span>
                            <span class="font-mono font-bold" :class="item.status === 'critical' ? 'text-red-500' : 'text-gray-800 dark:text-white'">
                                {{ item.stock }} <span class="text-xs font-normal text-gray-500">{{ item.unit }}</span>
                            </span>
                        </div>
                        <div class="h-2 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-1000 ease-out"
                                :class="{
                                    'bg-red-500': item.status === 'critical' || item.status === 'empty',
                                    'bg-yellow-500': item.status === 'warning',
                                    'bg-blue-500': item.status === 'safe'
                                }"
                                :style="{ width: Math.min(100, Math.max(5, (item.stock / 5000 * 100))) + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <Link :href="route('transactions.create')" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 transition shadow-lg shadow-green-200 dark:shadow-none hover:translate-y-[-2px]">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Masuk Menu Kasir (POS)
                    </Link>
                </div>
            </div>

        </div>
    </div>
</template>
