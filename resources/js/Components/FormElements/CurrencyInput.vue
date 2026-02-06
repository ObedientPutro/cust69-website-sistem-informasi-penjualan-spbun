<script setup lang="ts">
import InputError from '@/Components/FormElements/InputError.vue';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    modelValue: string | number;
    label?: string;
    placeholder?: string;
    error?: string;
    disabled?: boolean;
    required?: boolean;
    prefix?: string; // Opsional: Bisa tambah "Rp" di depan
}>();

const emit = defineEmits(['update:modelValue']);
const displayValue = ref('');

// Helper: Format Angka ke Ribuan (10000 -> 10.000)
const formatCurrency = (val: string | number) => {
    if (!val) return '';
    // Hapus non-digit untuk safety
    const cleanNum = val.toString().replace(/\D/g, '');
    if (!cleanNum) return '';

    // Format ke Indonesia (titik sebagai pemisah ribuan)
    return new Intl.NumberFormat('id-ID').format(parseInt(cleanNum));
};

// Watcher: Jika data dari Database masuk (misal Edit Mode)
watch(() => props.modelValue, (newVal) => {
    displayValue.value = formatCurrency(newVal);
}, { immediate: true });

// Handle Input
const onInput = (e: Event) => {
    const target = e.target as HTMLInputElement;
    let rawVal = target.value;

    // 1. Ambil hanya angkanya (Hapus titik/huruf)
    const cleanVal = rawVal.replace(/\D/g, '');

    // 2. Format Tampilan (tambah titik lagi)
    if (cleanVal) {
        displayValue.value = new Intl.NumberFormat('id-ID').format(parseInt(cleanVal));
        // 3. Emit Angka Murni ke Parent (Backend butuh integer/float, bukan string bertitik)
        emit('update:modelValue', parseInt(cleanVal));
    } else {
        displayValue.value = '';
        emit('update:modelValue', 0);
    }
};

const inputClasses = computed(() => {
    const base = 'h-11 w-full rounded-lg border bg-transparent py-2.5 text-sm shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden focus:ring-3 transition-colors duration-200 font-mono font-medium';
    // Padding left lebih besar jika ada Prefix (Rp)
    const paddingLeft = props.prefix ? 'pl-10' : 'pl-4';

    if (props.error) return `${base} ${paddingLeft} pr-4 border-error-300 text-error-900 focus:border-error-300 focus:ring-error-500/10 dark:border-error-700 dark:text-error-100`;

    return `${base} ${paddingLeft} pr-4 border-gray-300 text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white`;
});
</script>

<template>
    <div class="w-full">
        <label v-if="label" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ label }} <span v-if="required" class="text-error-500">*</span>
        </label>

        <div class="relative">
            <div v-if="prefix" class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <span class="text-gray-500 sm:text-sm dark:text-gray-400 font-bold">{{ prefix }}</span>
            </div>

            <input
                type="text"
                inputmode="numeric"
                :value="displayValue || 0"
                :placeholder="placeholder || '0'"
                :disabled="disabled"
                :class="inputClasses"
                @input="onInput"
            />
        </div>

        <InputError :message="error" />
    </div>
</template>

<style scoped>

</style>
