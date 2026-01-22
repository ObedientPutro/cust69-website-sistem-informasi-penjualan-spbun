<script setup lang="ts">
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import InputError from '@/Components/FormElements/InputError.vue';

const model = defineModel<string>();

const props = defineProps<{
    label?: string;
    error?: string;
    required?: boolean;
}>();

const config = {
    enableTime: true,
    noCalendar: true, // Hapus kalender
    dateFormat: "H:i", // Format 24 jam (09:00)
    time_24hr: true,
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
                :config="config"
                class="w-full rounded-lg border bg-transparent py-2.5 pl-4 pr-11 text-sm shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden focus:ring-3 transition-colors duration-200"
                :class="[
                    error
                        ? 'border-error-300 text-gray-800 focus:border-error-300 focus:ring-error-500/10 dark:border-error-700 dark:bg-gray-900 dark:text-white/90'
                        : 'border-gray-300 text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800'
                ]"
                placeholder="00:00"
            />

            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 pointer-events-none">
                <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04175 9.99984C3.04175 6.15686 6.1571 3.0415 10.0001 3.0415C13.8431 3.0415 16.9584 6.15686 16.9584 9.99984C16.9584 13.8428 13.8431 16.9582 10.0001 16.9582C6.1571 16.9582 3.04175 13.8428 3.04175 9.99984ZM10.0001 1.5415C5.32867 1.5415 1.54175 5.32843 1.54175 9.99984C1.54175 14.6712 5.32867 18.4582 10.0001 18.4582C14.6715 18.4582 18.4584 14.6712 18.4584 9.99984C18.4584 5.32843 14.6715 1.5415 10.0001 1.5415ZM9.99998 10.7498C9.58577 10.7498 9.24998 10.4141 9.24998 9.99984V5.4165C9.24998 5.00229 9.58577 4.6665 9.99998 4.6665C10.4142 4.6665 10.75 5.00229 10.75 5.4165V9.24984H13.3334C13.7476 9.24984 14.0834 9.58562 14.0834 9.99984C14.0834 10.4141 13.7476 10.7498 13.3334 10.7498H10.0001H9.99998Z" fill=""/>
                </svg>
            </span>
        </div>

        <InputError :message="error" />
    </div>
</template>

<style scoped>

</style>
