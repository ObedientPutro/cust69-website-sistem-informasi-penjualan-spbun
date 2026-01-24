<script setup lang="ts">
import { ref } from 'vue';
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import InputError from '@/Components/FormElements/InputError.vue';

const model = defineModel<string | Date | null>();

const props = defineProps<{
    label?: string;
    placeholder?: string;
    error?: string;
    config?: object;
    disabled?: boolean;
    required?: boolean;
}>();

const defaultConfig = {
    enableTime: true,
    dateFormat: 'Y-m-d H:i',
    altInput: true,
    altFormat: 'F j, Y H:i',
    allowInput: true,
    time_24hr: true,
    ...props.config
};
</script>

<template>
    <div class="w-full">
        <label v-if="label" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ label }} <span v-if="required" class="text-error-500">*</span>
        </label>

        <div class="relative">
            <flat-pickr
                v-model="model"
                :config="defaultConfig"
                class="w-full rounded-lg border bg-transparent py-2.5 pl-4 pr-11 text-sm shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden focus:ring-3 transition-colors duration-200"
                :class="[
                    error
                        ? 'border-error-300 text-gray-800 focus:border-error-300 focus:ring-error-500/10 dark:border-error-700 dark:bg-gray-900 dark:text-white/90'
                        : 'border-gray-300 text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800',
                    disabled ? 'bg-gray-50 text-gray-500 cursor-not-allowed dark:bg-gray-800 dark:text-gray-400' : ''
                ]"
                :placeholder="placeholder || 'Pilih tanggal & jam'"
                :disabled="disabled"
            />

            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 pointer-events-none">
                <svg class="fill-current" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 4H18V2H16V4H8V2H6V4H5C3.89 4 3 4.9 3 6V20C3 21.1 3.89 22 5 22H19C20.1 22 21 21.1 21 20V6C21 4.9 20.1 4 19 4ZM19 20H5V10H19V20ZM19 8H5V6H19V8ZM12 13H7V18H12V13Z" />
                </svg>
            </span>
        </div>

        <InputError v-if="error" :message="error" />
    </div>
</template>

<style scoped>

</style>
