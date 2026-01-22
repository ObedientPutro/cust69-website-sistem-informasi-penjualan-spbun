<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';

// Model menerima array value (misal: [1, 3] atau ['apple', 'banana'])
const model = defineModel<any[]>({ default: [] });

interface Option {
    value: string | number;
    label: string;
}

const props = defineProps<{
    options: Option[];
    placeholder?: string;
    error?: string;
}>();

const isOpen = ref(false);
const containerRef = ref<HTMLElement | null>(null);

// Helper: Cek apakah item terpilih
const isSelected = (option: Option) => model.value.includes(option.value);

// Helper: Ambil label dari value yang terpilih untuk ditampilkan sebagai Chip
const selectedOptions = computed(() => {
    return props.options.filter(opt => model.value.includes(opt.value));
});

// Toggle Dropdown
const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

// Select/Deselect Logic
const toggleOption = (option: Option) => {
    const index = model.value.indexOf(option.value);
    if (index === -1) {
        // Add
        model.value = [...model.value, option.value];
    } else {
        // Remove
        const newValue = [...model.value];
        newValue.splice(index, 1);
        model.value = newValue;
    }
};

// Remove via Chip "x" button
const removeValue = (value: string | number) => {
    const index = model.value.indexOf(value);
    if (index !== -1) {
        const newValue = [...model.value];
        newValue.splice(index, 1);
        model.value = newValue;
    }
};

// Close when clicking outside
const handleClickOutside = (event: MouseEvent) => {
    if (containerRef.value && !containerRef.value.contains(event.target as Node)) {
        isOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <div
            @click="toggleDropdown"
            class="min-h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm shadow-theme-xs cursor-pointer flex items-center flex-wrap gap-2 transition-colors duration-200"
            :class="[
                error
                    ? 'border-error-300 focus:border-error-300 focus:ring-error-500/10 dark:border-error-700'
                    : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700',
                isOpen ? 'border-brand-300 ring-3 ring-brand-500/10' : '',
                'dark:bg-gray-900 dark:text-white/90'
            ]"
        >
            <span v-if="selectedOptions.length === 0" class="text-gray-400">
                {{ placeholder || 'Select items...' }}
            </span>

            <div
                v-for="item in selectedOptions"
                :key="item.value"
                class="flex items-center rounded-full bg-gray-100 border border-gray-200 px-2.5 py-1 text-sm text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-white/90"
            >
                <span>{{ item.label }}</span>
                <button
                    @click.stop="removeValue(item.value)"
                    class="ml-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none"
                >
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.40717 4.46881C3.11428 4.17591 3.11428 3.70104 3.40717 3.40815C3.70006 3.11525 4.17494 3.11525 4.46783 3.40815L6.99943 5.93975L9.53095 3.40822C9.82385 3.11533 10.2987 3.11533 10.5916 3.40822C10.8845 3.70112 10.8845 4.17599 10.5916 4.46888L8.06009 7.00041L10.5916 9.53193C10.8845 9.82482 10.8845 10.2997 10.5916 10.5926C10.2987 10.8855 9.82385 10.8855 9.53095 10.5926L6.99943 8.06107L4.46783 10.5927C4.17494 10.8856 3.70006 10.8856 3.40717 10.5927C3.11428 10.2998 3.11428 9.8249 3.40717 9.53201L5.93877 7.00041L3.40717 4.46881Z" fill="currentColor"/>
                    </svg>
                </button>
            </div>

            <div class="ml-auto pl-2">
                <svg :class="{ 'rotate-180': isOpen }" class="transition-transform duration-200 text-gray-500" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>

        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div v-if="isOpen" class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900">
                <ul class="max-h-60 overflow-y-auto py-1">
                    <li
                        v-for="option in options"
                        :key="option.value"
                        @click="toggleOption(option)"
                        class="relative cursor-pointer select-none py-2 pl-3 pr-9 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                        :class="{ 'bg-gray-50 dark:bg-white/5 font-medium text-brand-500 dark:text-brand-400': isSelected(option) }"
                    >
                        <span class="block truncate">{{ option.label }}</span>

                        <span v-if="isSelected(option)" class="absolute inset-y-0 right-0 flex items-center pr-4 text-brand-500">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </li>
                </ul>
            </div>
        </transition>

        <p v-if="error" class="mt-1 text-sm text-error-500 animate-fade-in-down">{{ error }}</p>
    </div>
</template>

<style scoped>

</style>
