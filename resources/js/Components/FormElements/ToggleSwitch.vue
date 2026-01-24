<script setup lang="ts">
const enabled = defineModel<boolean>({ default: false });

const props = defineProps<{
    label?: string;
    disabled?: boolean;
}>();
</script>

<template>
    <div class="flex items-center">
        <button
            type="button"
            role="switch"
            :aria-checked="enabled"
            :disabled="disabled"
            @click="!disabled && (enabled = !enabled)"
            :class="[
                enabled ? 'bg-brand-500' : 'bg-gray-200 dark:bg-gray-700',
                disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
            ]"
            class="focus:ring-brand-500/20 relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors duration-200 ease-in-out focus:ring-2 focus:outline-none"
        >
            <span class="sr-only">{{ label || 'Toggle Setting' }}</span>

            <span
                :class="enabled ? 'translate-x-6' : 'translate-x-1'"
                class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
            />
        </button>

        <span
            v-if="label"
            @click="!disabled && (enabled = !enabled)"
            class="ml-3 cursor-pointer text-sm font-medium text-gray-700 select-none dark:text-gray-400"
        >
            {{ label }}
        </span>
    </div>
</template>

<style scoped></style>
