<script setup lang="ts">
import InputError from '@/Components/FormElements/InputError.vue';
import { computed, onMounted, ref, useSlots } from 'vue';

const model = defineModel<string | number>();

const props = defineProps<{
    type?: string;
    placeholder?: string;
    label?: string;
    error?: string;
    success?: boolean;
    disabled?: boolean;
    required?: boolean;
    readonly?: boolean;
}>();

const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });

// Dynamic Classes
const inputClasses = computed(() => {
    const base =
        'h-11 w-full rounded-lg border bg-transparent py-2.5 text-sm shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden focus:ring-3 transition-colors duration-200';

    // Padding kiri (Prefix Slot)
    const paddingLeft = useSlots().prefix ? 'pl-11' : 'pl-4';

    // Padding kanan (Suffix Slot / Error Icon)
    const paddingRight =
        useSlots().suffix || props.error || props.success ? 'pr-11' : 'pr-4';

    if (props.disabled) {
        return `${base} ${paddingLeft} ${paddingRight} border-gray-300 bg-gray-50 text-gray-500 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400`;
    }

    if (props.error) {
        return `${base} ${paddingLeft} ${paddingRight} border-error-300 text-gray-800 focus:border-error-300 focus:ring-error-500/10 dark:border-error-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-error-800`;
    }

    if (props.success) {
        return `${base} ${paddingLeft} ${paddingRight} border-success-300 text-gray-800 focus:border-success-300 focus:ring-success-500/10 dark:border-success-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-success-800`;
    }

    // Normal
    return `${base} ${paddingLeft} ${paddingRight} border-gray-300 text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800`;
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

        <div class="relative">
            <div
                v-if="$slots.prefix"
                class="pointer-events-none absolute top-1/2 left-0 -translate-y-1/2 pl-4 text-gray-500 dark:text-gray-400"
            >
                <slot name="prefix" />
            </div>

            <input
                ref="input"
                v-model="model"
                :type="type || 'text'"
                :placeholder="placeholder"
                :disabled="disabled"
                :class="inputClasses"
                :readonly="readonly"
            />

            <div
                class="pointer-events-none absolute top-1/2 right-4 flex -translate-y-1/2 items-center"
            >
                <div
                    v-if="$slots.suffix"
                    class="pointer-events-auto flex items-center"
                >
                    <slot name="suffix" />
                </div>

                <span v-else-if="error" class="text-error-500">
                    <svg
                        width="20"
                        height="20"
                        viewBox="0 0 20 20"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M10 18.3333C5.39763 18.3333 1.66667 14.6024 1.66667 10C1.66667 5.39763 5.39763 1.66667 10 1.66667C14.6024 1.66667 18.3333 5.39763 18.3333 10C18.3333 14.6024 14.6024 18.3333 10 18.3333ZM9.16667 12.5V14.1667H10.8333V12.5H9.16667ZM9.16667 5.83333V10.8333H10.8333V5.83333H9.16667Z"
                            fill="currentColor"
                        />
                    </svg>
                </span>

                <span v-else-if="success" class="text-success-500">
                    <svg
                        width="20"
                        height="20"
                        viewBox="0 0 20 20"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M16.6667 5L7.50001 14.1667L3.33334 10"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </span>
            </div>
        </div>

        <InputError :message="error" />
    </div>
</template>
