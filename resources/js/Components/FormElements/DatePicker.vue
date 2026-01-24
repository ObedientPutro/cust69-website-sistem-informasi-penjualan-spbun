<script setup lang="ts">
import InputError from '@/Components/FormElements/InputError.vue';
import 'flatpickr/dist/flatpickr.css';
import flatPickr from 'vue-flatpickr-component';

// Theme CSS override for dark mode (bisa dipindah ke app.css nanti)
// Tapi flatpickr punya tema sendiri, kita styling wrapper-nya saja.

const model = defineModel<string | Date | null>();

const props = defineProps<{
    label?: string;
    placeholder?: string;
    error?: string;
    config?: object; // Untuk override config flatpickr
    disabled?: boolean;
    required?: boolean;
}>();

// Default Config
const defaultConfig = {
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'F j, Y', // Tampil "January 21, 2026"
    allowInput: true,
    ...props.config,
};
</script>

<template>
    <div class="w-full">
        <label
            v-if="label"
            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
        >
            {{ label }} <span v-if="required" class="text-error-500">*</span>
        </label>

        <div class="relative">
            <flat-pickr
                v-model="model"
                :config="defaultConfig"
                class="shadow-theme-xs w-full rounded-lg border bg-transparent py-2.5 pr-11 pl-4 text-sm transition-colors duration-200 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden"
                :class="[
                    error
                        ? 'border-error-300 focus:border-error-300 focus:ring-error-500/10 dark:border-error-700 text-gray-800 dark:bg-gray-900 dark:text-white/90'
                        : 'focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 border-gray-300 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90',
                    disabled
                        ? 'cursor-not-allowed bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-400'
                        : '',
                ]"
                :placeholder="placeholder || 'Select date'"
                :disabled="disabled"
            />

            <span
                class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-gray-500 dark:text-gray-400"
            >
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
                        d="M6.66659 1.5415C7.0808 1.5415 7.41658 1.87729 7.41658 2.2915V2.99984H12.5833V2.2915C12.5833 1.87729 12.919 1.5415 13.3333 1.5415C13.7475 1.5415 14.0833 1.87729 14.0833 2.2915V2.99984L15.4166 2.99984C16.5212 2.99984 17.4166 3.89527 17.4166 4.99984V7.49984V15.8332C17.4166 16.9377 16.5212 17.8332 15.4166 17.8332H4.58325C3.47868 17.8332 2.58325 16.9377 2.58325 15.8332V7.49984V4.99984C2.58325 3.89527 3.47868 2.99984 4.58325 2.99984L5.91659 2.99984V2.2915C5.91659 1.87729 6.25237 1.5415 6.66659 1.5415ZM6.66659 4.49984H4.58325C4.30711 4.49984 4.08325 4.7237 4.08325 4.99984V6.74984H15.9166V4.99984C15.9166 4.7237 15.6927 4.49984 15.4166 4.49984H13.3333H6.66659ZM15.9166 8.24984H4.08325V15.8332C4.08325 16.1093 4.30711 16.3332 4.58325 16.3332H15.4166C15.6927 16.3332 15.9166 16.1093 15.9166 15.8332V8.24984Z"
                        fill=""
                    />
                </svg>
            </span>
        </div>

        <InputError :message="error" />
    </div>
</template>

<style scoped></style>
