<script setup lang="ts">
import { computed } from 'vue';

// Menggunakan defineModel agar bisa sync true/false langsung
const checked = defineModel<boolean | any[]>('checked');

const props = defineProps<{
    value?: any; // Value untuk checkbox group
    label?: string; // Teks di sebelah kanan checkbox
    disabled?: boolean;
}>();

// Logic untuk mengecek apakah checkbox ini terpilih (baik boolean single atau array group)
const isChecked = computed(() => {
    if (Array.isArray(checked.value)) {
        return checked.value.includes(props.value);
    }
    return checked.value === true; // Handle boolean
});

// Logic saat diklik
const handleChange = (e: Event) => {
    if (props.disabled) return;

    if (Array.isArray(checked.value)) {
        const newValue = [...checked.value];
        if (isChecked.value) {
            newValue.splice(newValue.indexOf(props.value), 1);
        } else {
            newValue.push(props.value);
        }
        checked.value = newValue;
    } else {
        checked.value = !checked.value;
    }
};
</script>

<template>
    <label
        class="flex cursor-pointer items-center text-sm font-medium text-gray-700 select-none dark:text-gray-400"
    >
        <div class="relative">
            <input
                type="checkbox"
                :value="value"
                :checked="isChecked"
                @change="handleChange"
                :disabled="disabled"
                class="sr-only"
            />

            <div
                :class="[
                    isChecked
                        ? 'border-brand-500 bg-brand-500'
                        : 'border-gray-300 bg-transparent dark:border-gray-700',
                    disabled
                        ? 'cursor-not-allowed opacity-50'
                        : 'hover:border-brand-500 dark:hover:border-brand-500',
                ]"
                class="mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px] transition-colors duration-200"
            >
                <span
                    :class="isChecked ? 'opacity-100' : 'opacity-0'"
                    class="transition-opacity duration-200"
                >
                    <svg
                        width="14"
                        height="14"
                        viewBox="0 0 14 14"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M11.6666 3.5L5.24992 9.91667L2.33325 7"
                            stroke="white"
                            stroke-width="1.94437"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </span>
            </div>
        </div>

        <span v-if="label">{{ label }}</span>
        <slot v-else />
    </label>
</template>

<style scoped></style>
