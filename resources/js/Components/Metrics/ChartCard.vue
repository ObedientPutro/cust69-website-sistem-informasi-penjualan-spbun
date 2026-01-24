<script setup lang="ts">
defineProps<{
    title: string;
    subtitle?: string;
}>();
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03] shadow-sm">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                    {{ title }}
                </h3>
                <p v-if="subtitle" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ subtitle }}
                </p>
            </div>

            <div v-if="$slots.action">
                <slot name="action" />
            </div>
        </div>

        <div>
            <slot />
        </div>
    </div>
</template>

<style scoped>

</style>
