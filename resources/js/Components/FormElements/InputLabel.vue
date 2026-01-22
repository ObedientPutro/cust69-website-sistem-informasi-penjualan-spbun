<script setup lang="ts">
defineProps<{
    value?: string;
    required?: boolean;
}>();
</script>

<template>
    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
        <span v-if="required" class="text-error-500 ml-1">*</span>
    </label>
</template>

<style scoped>

</style>
