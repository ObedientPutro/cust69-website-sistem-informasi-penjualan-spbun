<script setup lang="ts">
import InputError from '@/Components/FormElements/InputError.vue';
import { computed, ref, watch } from 'vue';

// Model value bisa string atau number
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

const formatToDecimal = (val: string | number) => {
    if (val === '' || val === null || val === undefined) return '';
    const num = parseFloat(val.toString());
    return isNaN(num) ? '' : num.toFixed(3);
};

watch(() => props.modelValue, (newVal) => {
    if (document.activeElement !== inputRef.value) {
        displayValue.value = newVal ? formatToDecimal(newVal) : '';
    }
}, { immediate: true });

const onInput = (e: Event) => {
    const target = e.target as HTMLInputElement;
    let val = target.value;

    val = val.replace(/[^0-9.]/g, '');

    const dots = val.split('.').length - 1;
    if (dots > 1) val = val.slice(0, -1);

    if (val.includes('.')) {
        const [integer, decimal] = val.split('.');
        if (decimal && decimal.length > 3) {
            val = `${integer}.${decimal.substring(0, 3)}`;
        }
    }

    displayValue.value = val;
    emit('update:modelValue', val);
};

const onBlur = () => {
    if (displayValue.value) {
        const formatted = formatToDecimal(displayValue.value);
        displayValue.value = formatted;
        emit('update:modelValue', formatted);
    } else {
        displayValue.value = '0.000';
        emit('update:modelValue', '0.000');
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
                :placeholder="placeholder || '0.000'"
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

<style scoped>

</style>
