<script setup lang="ts">
import InputError from '@/Components/FormElements/InputError.vue';
import { computed } from 'vue';

// Model Value (String/Number/Null)
const model = defineModel<string | number | null>();

interface Option {
    value: string | number;
    label: string;
}

const props = defineProps<{
    label?: string;
    placeholder?: string;
    options?: Option[]; // Opsional: Bisa lempar array [{value: 1, label: 'Admin'}]
    error?: string;
    disabled?: boolean;
    required?: boolean;
}>();

// Styling Logic (Mirip TextInput biar konsisten)
const selectClasses = computed(() => {
    const base =
        'dark:bg-dark-900 h-11 w-full appearance-none rounded-lg border bg-transparent px-4 py-2.5 pr-11 text-sm shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden focus:ring-3 transition-colors duration-200';

    if (props.disabled) {
        return `${base} border-gray-300 bg-gray-50 text-gray-500 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400`;
    }

    if (props.error) {
        return `${base} border-error-300 text-gray-800 focus:border-error-300 focus:ring-error-500/10 dark:border-error-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-error-800`;
    }

    // Normal State & Selected Logic
    // Jika value terisi, warna teks normal. Jika kosong (placeholder), warna agak abu.
    const textColor = model.value
        ? 'text-gray-800 dark:text-white/90'
        : 'text-gray-400 dark:text-gray-400';
    return `${base} border-gray-300 ${textColor} focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:focus:border-brand-800`;
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

        <div class="relative z-20 bg-transparent">
            <select v-model="model" :disabled="disabled" :class="selectClasses">
                <option v-if="placeholder" value="" disabled selected>
                    {{ placeholder }}
                </option>

                <template v-if="options">
                    <option
                        v-for="opt in options"
                        :key="opt.value"
                        :value="opt.value"
                        class="text-gray-700 dark:bg-gray-900 dark:text-gray-400"
                    >
                        {{ opt.label }}
                    </option>
                </template>

                <slot v-else />
            </select>

            <span
                class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400"
            >
                <svg
                    class="stroke-current"
                    width="20"
                    height="20"
                    viewBox="0 0 20 20"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396"
                        stroke=""
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </span>
        </div>

        <InputError :message="error" />
    </div>
</template>

<style scoped></style>
