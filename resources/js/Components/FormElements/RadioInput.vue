<script setup lang="ts">
// Kita tidak pakai defineModel 'boolean' karena radio button itu memilih value spesifik
const model = defineModel<string | number | boolean>();

const props = defineProps<{
    value: string | number | boolean; // Nilai dari radio ini (misal: 'Laki-laki')
    label?: string;
    name?: string;
    disabled?: boolean;
}>();

// Cek apakah radio ini sedang dipilih
const isChecked = () => model.value === props.value;
</script>

<template>
    <label class="flex items-center text-sm font-medium text-gray-700 cursor-pointer select-none dark:text-gray-400 group">
        <div class="relative">
            <input
                type="radio"
                :name="name"
                :value="value"
                v-model="model"
                :disabled="disabled"
                class="sr-only"
            />

            <div
                :class="[
                    isChecked() ? 'border-brand-500' : 'border-gray-300 dark:border-gray-700',
                    disabled ? 'opacity-50 cursor-not-allowed' : 'group-hover:border-brand-500'
                ]"
                class="mr-3 flex h-5 w-5 items-center justify-center rounded-full border-[1.25px] bg-transparent transition-colors duration-200"
            >
                <div
                    :class="isChecked() ? 'scale-100' : 'scale-0'"
                    class="h-2.5 w-2.5 rounded-full bg-brand-500 transition-transform duration-200"
                ></div>
            </div>
        </div>

        <span v-if="label">{{ label }}</span>
        <slot v-else />
    </label>
</template>

<style scoped>

</style>
