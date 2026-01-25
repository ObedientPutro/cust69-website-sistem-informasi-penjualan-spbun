<script setup lang="ts">
import InputError from '@/Components/FormElements/InputError.vue';
import { ref } from 'vue';

const model = defineModel<File | null>();

const props = defineProps<{
    label?: string;
    error?: string;
    accept?: string;
}>();

const isDragging = ref(false);
const previewName = ref<string | null>(null);

// Handle Drag Events
const onDragOver = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = true;
};

const onDragLeave = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = false;
};

// Handle Drop
const onDrop = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = false;

    if (e.dataTransfer?.files.length) {
        handleFiles(e.dataTransfer.files[0]);
    }
};

// Handle Click (Manual Browse)
const fileInput = ref<HTMLInputElement | null>(null);
const triggerBrowse = () => {
    fileInput.value?.click();
};

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files?.length) {
        handleFiles(target.files[0]);
    }
};

// Common Handler
const handleFiles = (file: File) => {
    model.value = file;
    previewName.value = file.name;
    // Disini bisa tambah logic validasi size/type jika mau
};
</script>

<template>
    <div class="w-full">
        <label
            v-if="label"
            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
        >
            {{ label }}
        </label>

        <div
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
            @click="triggerBrowse"
            class="hover:border-brand-500 dark:hover:border-brand-500 relative flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed bg-gray-50 p-10 text-center transition-all hover:bg-gray-100 dark:bg-gray-900"
            :class="[
                error
                    ? 'border-error-300'
                    : isDragging
                      ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10'
                      : 'border-gray-300 dark:border-gray-700',
            ]"
        >
            <input
                ref="fileInput"
                type="file"
                class="hidden"
                :accept="accept"
                @change="onFileChange"
            />

            <div
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-400"
            >
                <svg
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M12 3C11.4477 3 11 3.44772 11 4V9H6C5.44772 9 5 9.44772 5 10C5 10.5523 5.44772 11 6 11H11V16C11 16.5523 11.4477 17 12 17C12.5523 17 13 16.5523 13 16V11H18C18.5523 11 19 10.5523 19 10C19 9.44772 18.5523 9 18 9H13V4C13 3.44772 12.5523 3 12 3Z"
                        fill="currentColor"
                        v-if="false"
                    />
                    <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M14.5019 3.91699C14.2852 3.91699 14.0899 4.00891 13.953 4.15589L8.57363 9.53186C8.28065 9.82466 8.2805 10.2995 8.5733 10.5925C8.8661 10.8855 9.34097 10.8857 9.63396 10.5929L13.7519 6.47752V18.667C13.7519 19.0812 14.0877 19.417 14.5019 19.417C14.9161 19.417 15.2519 19.0812 15.2519 18.667V6.48234L19.3653 10.5929C19.6583 10.8857 20.1332 10.8855 20.426 10.5925C20.7188 10.2995 20.7186 9.82463 20.4256 9.53184L15.0838 4.19378C14.9463 4.02488 14.7367 3.91699 14.5019 3.91699ZM5.91626 18.667C5.91626 18.2528 5.58047 17.917 5.16626 17.917C4.75205 17.917 4.41626 18.2528 4.41626 18.667V21.8337C4.41626 23.0763 5.42362 24.0837 6.66626 24.0837H22.3339C23.5766 24.0837 24.5839 23.0763 24.5839 21.8337V18.667C24.5839 18.2528 24.2482 17.917 23.8339 17.917C23.4197 17.917 23.0839 18.2528 23.0839 18.667V21.8337C23.0839 22.2479 22.7482 22.5837 22.3339 22.5837H6.66626C6.25205 22.5837 5.91626 22.2479 5.91626 21.8337V18.667Z"
                        fill="currentColor"
                    />
                </svg>
            </div>

            <div v-if="!previewName">
                <h4 class="mb-2 font-semibold text-gray-800 dark:text-white/90">
                    Click to upload or drag and drop
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    PNG, JPG or JPEG (max, 5MB)
                </p>
            </div>

            <div v-else class="text-center">
                <p class="text-brand-500 font-medium">{{ previewName }}</p>
                <p class="mt-1 text-xs text-gray-400">(Click to change)</p>
            </div>
        </div>

        <InputError :message="error" />
    </div>
</template>

<style scoped></style>
