<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { ChevronDownIcon } from '@/Components/Icons';
import InputError from '@/Components/FormElements/InputError.vue';

interface Option {
    value: string | number;
    label: string;
    subLabel?: string;
}

const props = defineProps<{
    modelValue: string | number | null;
    options: Option[];
    label?: string;
    placeholder?: string;
    error?: string;
    disabled?: boolean;
    required?: boolean;
}>();

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref<HTMLElement | null>(null);

// Inisialisasi label saat load (jika ada value awal)
const selectedLabel = computed(() => {
    const selected = props.options.find(opt => opt.value == props.modelValue);
    return selected ? selected.label : '';
});

// Update searchQuery jika modelValue berubah dari luar (misal reset form)
watch(() => props.modelValue, (newVal) => {
    if (!newVal) {
        searchQuery.value = '';
    } else {
        const selected = props.options.find(opt => opt.value == newVal);
        if (selected) searchQuery.value = selected.label;
    }
});

// Filter Options
const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();

    return props.options.filter(opt =>
        opt.label.toLowerCase().includes(query) ||
        (opt.subLabel && opt.subLabel.toLowerCase().includes(query))
    );
});

const toggleDropdown = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        // Jika dibuka, kosongkan search agar user lihat semua opsi,
        // TAPI simpan state sebelumnya jika batal
    }
};

const selectOption = (option: Option) => {
    emit('update:modelValue', option.value);
    emit('change', option);
    searchQuery.value = option.label;
    isOpen.value = false;
};

// Handle Click Outside
const handleClickOutside = (event: MouseEvent) => {
    if (containerRef.value && !containerRef.value.contains(event.target as Node)) {
        isOpen.value = false;
        // Jika user mengetik tapi tidak memilih, kembalikan ke label yang benar
        if (props.modelValue) {
            const selected = props.options.find(opt => opt.value == props.modelValue);
            searchQuery.value = selected ? selected.label : '';
        } else {
            searchQuery.value = '';
        }
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <div class="w-full relative" ref="containerRef">
        <label v-if="label" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ label }} <span v-if="required" class="text-error-500">*</span>
        </label>

        <div class="relative">
            <input
                type="text"
                v-model="searchQuery"
                @focus="isOpen = true"
                :placeholder="placeholder || 'Ketik untuk mencari...'"
                :disabled="disabled"
                class="dark:bg-dark-900 h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 pr-11 text-sm shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden focus:ring-3 transition-colors duration-200"
                :class="[
                    error
                        ? 'border-error-300 text-gray-800 focus:border-error-300 focus:ring-error-500/10 dark:border-error-700'
                        : 'border-gray-300 text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:focus:border-brand-800',
                    disabled ? 'bg-gray-50 cursor-not-allowed text-gray-500' : ''
                ]"
            />

            <button
                type="button"
                @click="toggleDropdown"
                class="absolute right-0 top-0 h-full w-10 flex items-center justify-center text-gray-500 hover:text-gray-700"
                :disabled="disabled"
            >
                <ChevronDownIcon class="h-5 w-5 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" />
            </button>
        </div>

        <div
            v-if="isOpen"
            class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800 max-h-60 overflow-y-auto custom-scrollbar"
        >
            <ul v-if="filteredOptions.length > 0" class="py-1">
                <li
                    v-for="opt in filteredOptions"
                    :key="opt.value"
                    @click="selectOption(opt)"
                    class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer flex flex-col"
                    :class="{ 'bg-gray-50 dark:bg-gray-700/50': modelValue === opt.value }"
                >
                    <span class="font-medium">{{ opt.label }}</span>
                    <span v-if="opt.subLabel" class="text-xs text-gray-500">{{ opt.subLabel }}</span>
                </li>
            </ul>
            <div v-else class="px-4 py-3 text-sm text-gray-500 text-center">
                Tidak ditemukan.
            </div>
        </div>

        <InputError :message="error" />
    </div>
</template>

<style scoped>
/* Opsional: Style scrollbar agar rapi */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
</style>
