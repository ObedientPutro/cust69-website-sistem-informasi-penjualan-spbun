<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    title: string;
    value: string | number;
    trend?: number; // Persentase kenaikan/penurunan (misal: 12.5 atau -5)
    trendLabel?: string; // Teks kecil di bawah (misal: "vs bulan lalu")
    icon?: any; // Bisa berupa Component Icon atau String SVG
    color?: 'primary' | 'success' | 'warning' | 'error'; // Warna Icon Background
}>();

const isTrendPositive = computed(() => (props.trend || 0) > 0);

const colorClasses = {
    primary: 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
    success: 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-400',
    warning: 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
    error:   'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
};

// Default ke primary jika tidak ada props color
const iconBgClass = computed(() => colorClasses[props.color || 'primary']);
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03] shadow-sm">
        <div class="flex items-center justify-between">
            <div
                class="flex h-12 w-12 items-center justify-center rounded-xl transition-colors"
                :class="iconBgClass"
            >
                <component :is="icon" v-if="icon" class="h-6 w-6 fill-current" />
                <slot name="icon" v-else />
            </div>

            <div v-if="trend !== undefined" class="flex items-center gap-1">
                <span
                    class="flex items-center gap-1 rounded-full py-0.5 px-2.5 text-xs font-medium"
                    :class="isTrendPositive
                        ? 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500'
                        : 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500'"
                >
                    <span v-if="isTrendPositive">↑</span>
                    <span v-else>↓</span>
                    {{ Math.abs(trend) }}%
                </span>
            </div>
        </div>

        <div class="mt-4">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white">
                {{ value }}
            </h4>
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ title }} <span v-if="trendLabel" class="text-xs opacity-70">({{ trendLabel }})</span>
            </span>
        </div>
    </div>
</template>

<style scoped>

</style>
