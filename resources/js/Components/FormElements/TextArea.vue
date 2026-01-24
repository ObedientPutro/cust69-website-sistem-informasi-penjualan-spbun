<script setup lang="ts">
import InputError from '@/Components/FormElements/InputError.vue';
import { computed } from 'vue';

const model = defineModel<string>();

const props = defineProps<{
    label?: string;
    placeholder?: string;
    rows?: number;
    error?: string;
    disabled?: boolean;
    required?: boolean;
}>();

const inputClasses = computed(() => {
    const base =
        'w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden focus:ring-3 transition-colors duration-200';

    if (props.disabled) {
        return `${base} border-gray-300 bg-gray-50 text-gray-500 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400`;
    }

    if (props.error) {
        return `${base} border-error-300 text-gray-800 focus:border-error-300 focus:ring-error-500/10 dark:border-error-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-error-800`;
    }

    return `${base} border-gray-300 text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800`;
});
</script>

<template>
    <div class="w-full">
        <label
            v-if="label"
            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
        >
            {{ label }} <span v-if="required" class="text-error-500">*</span>
        </label>

        <textarea
            v-model="model"
            :rows="rows || 4"
            :placeholder="placeholder"
            :disabled="disabled"
            :class="inputClasses"
        ></textarea>

        <InputError :message="error" />
    </div>
</template>

<style scoped></style>
