<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    title: string;
    value: string | number;
    trend?: number;
    trendLabel?: string;
    icon?: any;
    color?: 'primary' | 'success' | 'warning' | 'error';
}>();

const isTrendPositive = computed(() => (props.trend || 0) > 0);

const colorClasses = {
    primary: 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
    success: 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-400',
    warning: 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
    error:   'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
};

const iconBgClass = computed(() => colorClasses[props.color || 'primary']);
</script>

<template>
    <div class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900/50">
        <div class="flex flex-wrap items-center justify-between gap-4 sm:flex-nowrap">

            <div class="flex items-center gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl transition-colors"
                    :class="iconBgClass"
                >
                    <component :is="icon" v-if="icon" class="h-6 w-6 fill-current" />
                    <slot name="icon" v-else />
                </div>

                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ title }}
                    </span>
                    <h4 class="text-2xl font-bold text-gray-800 dark:text-white">
                        {{ value }}
                    </h4>
                </div>
            </div>

            <div v-if="trend !== undefined" class="flex flex-col items-end gap-1 ml-auto sm:ml-0">
                <span
                    class="flex items-center gap-1 rounded-full py-1 px-2.5 text-xs font-bold"
                    :class="isTrendPositive
                        ? 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500'
                        : 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500'"
                >
                    <span v-if="isTrendPositive">↑</span>
                    <span v-else>↓</span>
                    {{ Math.abs(trend) }}%
                </span>
                <span v-if="trendLabel" class="text-[10px] text-gray-400">
                    {{ trendLabel }}
                </span>
            </div>

        </div>
    </div>
</template>

<style scoped>

</style>
