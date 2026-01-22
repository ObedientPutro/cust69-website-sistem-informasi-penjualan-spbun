<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';

const page = usePage();
const swal = useSweetAlert();

// Pantau perubahan pada props 'flash' dari Inertia
watch(() => page.props.flash, (flash: any) => {
    if (flash.success) {
        swal.toast(flash.success, 'success');
    }
    if (flash.error) {
        swal.alert('Error!', flash.error, 'error');
    }
    if (flash.warning) {
        swal.toast(flash.warning, 'warning');
    }
}, { deep: true });
</script>

<template>
    <div v-show="false"></div>
</template>

<style scoped>

</style>
