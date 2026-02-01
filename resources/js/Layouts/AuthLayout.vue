<script setup lang="ts">
import CommonGridShape from '@/Components/Common/CommonGridShape.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
    description: string;
}>();
</script>

<template>
    <div class="flex min-h-screen w-full bg-gray-50 text-gray-700 dark:bg-gray-900 dark:text-gray-200">
        <div class="flex w-full flex-col lg:flex-row">

            <div class="flex w-full flex-1 flex-col justify-center lg:w-1/2 bg-white dark:bg-gray-900">
                <div class="mx-auto flex w-full max-w-md flex-col justify-center px-6 py-12 lg:px-8">

                    <div class="lg:hidden mb-6 text-center">
                        <Link :href="route('dashboard')" class="inline-block">
                            <img src="/images/logo/auth-logo.svg" alt="Logo" class="h-10 w-auto mx-auto" />
                        </Link>
                    </div>

                    <div class="mb-8">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ title }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                            {{ description }}
                        </p>
                    </div>

                    <slot />

                </div>
            </div>

            <div
                class="bg-brand-950 relative hidden h-full w-full items-center lg:grid lg:w-1/2 dark:bg-white/5"
            >
                <div class="z-1 flex items-center justify-center">
                    <CommonGridShape />
                    <div
                        class="relative z-10 flex max-w-xs flex-col items-center"
                    >
                        <Link :href="route('dashboard')" class="mb-4 block">
                            <img
                                width="{231}"
                                height="{48}"
                                src="/images/logo/auth-logo.svg"
                                alt="Logo"
                                class="h-12 w-auto"
                            />
                        </Link>
                        <p class="text-center text-gray-400 dark:text-white/60">
                            Sistem Manajemen Stok & Penjualan Bahan Bakar Minyak
                            untuk Nelayan
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
