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
    suffix?: string;
}>();

const emit = defineEmits(['update:modelValue']);

const inputRef = ref<HTMLInputElement | null>(null);
const displayValue = ref('');

// Helper: Format angka (Titik) ke string tampilan (Koma)
// Contoh: 10.5 -> "10,500"
const formatToDisplay = (val: string | number) => {
    if (val === '' || val === null || val === undefined) return '';

    // Pastikan input berupa angka float standar (titik)
    const num = parseFloat(val.toString());

    if (isNaN(num)) return '';

    // Format ke 3 desimal, lalu ganti titik dengan koma
    return num.toFixed(2).replace('.', ',');
};

// Watcher: Sinkronisasi data dari Parent ke Tampilan
watch(() => props.modelValue, (newVal) => {
    // Hanya update tampilan jika element tidak sedang difokus (menghindari kursor lompat)
    if (document.activeElement !== inputRef.value) {
        displayValue.value = newVal ? formatToDisplay(newVal) : '';
    }
}, { immediate: true });

// Handle Input (Saat mengetik)
const onInput = (e: Event) => {
    const target = e.target as HTMLInputElement;
    let val = target.value;

    // 1. Auto-convert Titik ke Koma (UX: User numpad sering pakai titik)
    val = val.replace('.', ',');

    // 2. Hapus karakter selain Angka dan Koma
    val = val.replace(/[^0-9,]/g, '');

    // 3. Pastikan hanya ada satu koma
    const parts = val.split(',');
    if (parts.length > 2) {
        val = parts[0] + ',' + parts.slice(1).join('');
    }

    // 4. Batasi maksimal 3 angka di belakang koma
    if (val.includes(',')) {
        const [integer, decimal] = val.split(',');
        if (decimal && decimal.length > 3) {
            val = `${integer},${decimal.substring(0, 3)}`;
        }
    }

    // Update tampilan di input
    displayValue.value = val;

    // EMIT KE PARENT: Ganti Koma kembali ke Titik agar Backend/Math aman
    // Contoh visual: "10,5" -> Data: "10.5"
    emit('update:modelValue', val.replace(',', '.'));
};

// Handle Blur (Saat keluar fokus) -> Format Rapi
const onBlur = () => {
    if (displayValue.value) {
        // Normalisasi: Ubah koma ke titik dulu untuk diparsing
        const normalized = displayValue.value.replace(',', '.');
        const formatted = formatToDisplay(normalized);

        displayValue.value = formatted;

        // Emit nilai yang sudah diformat (dengan titik)
        emit('update:modelValue', formatted.replace(',', '.'));
    } else {
        // Jika kosong, set default tampilan 0,000 tapi data 0
        displayValue.value = '0,000';
        emit('update:modelValue', '0');
    }
};

const onFocus = (e: FocusEvent) => {
    (e.target as HTMLInputElement).select();
};

const inputClasses = computed(() => {
    const base = 'h-11 w-full rounded-lg border bg-transparent py-2.5 pl-4 text-sm shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden focus:ring-3 transition-colors duration-200 font-mono font-medium';
    const paddingRight = props.suffix ? 'pr-12' : 'pr-4';

    if (props.error) return `${base} ${paddingRight} border-error-300 text-error-900 focus:border-error-300 focus:ring-error-500/10 dark:border-error-700 dark:text-error-100`;

    return `${base} ${paddingRight} border-gray-300 text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white`;
});
</script>

<template>
    <div class="w-full">
        <label v-if="label" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ label }} <span v-if="required" class="text-error-500">*</span>
        </label>

        <div class="relative">
            <input
                ref="inputRef"
                type="text"
                inputmode="decimal"
                :value="displayValue"
                :placeholder="placeholder || '0,00'"
                :disabled="disabled"
                :class="inputClasses"
                @input="onInput"
                @blur="onBlur"
                @focus="onFocus"
            />

            <div v-if="suffix" class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4">
                <span class="text-gray-500 sm:text-sm dark:text-gray-400">{{ suffix }}</span>
            </div>
        </div>

        <InputError :message="error" />
    </div>
</template>
