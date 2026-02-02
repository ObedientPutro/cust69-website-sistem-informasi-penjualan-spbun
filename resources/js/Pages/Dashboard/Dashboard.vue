<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useSweetAlert } from '@/Composables/useSweetAlert';
import OwnerDashboard from './Partials/OwnerDashboard.vue';
import OperatorDashboard from './Partials/OperatorDashboard.vue';

const props = defineProps<{
    financial: any;       // Data metrics (Omset, Profit, dll)
    inventory: any[];     // Data Stok Tangki
    trends: {             // Data untuk Chart
        volume_series: any;
        payment_method_series: any;
        debt_ratio_series: any;
    };
    lists: {              // Data Tabel
        debtors: any[];
        recent_transactions: any[];
    };
    active_shifts: any[]; // List Shift yang sedang OPEN
}>();

const page = usePage();
const swal = useSweetAlert();
const user = computed(() => page.props.auth.user);
const isOwner = computed(() => user.value.role === 'owner');

onMounted(() => {
    // Flash message welcome / verified
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('verified') == '1') {
        swal.toast('Email berhasil diverifikasi!', 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout>

        <template v-if="isOwner">
            <OwnerDashboard
                :metrics="financial"
                :inventory="inventory"
                :trends="trends"
                :lists="lists"
                :active_shifts="active_shifts"
            />
        </template>

        <template v-else>
            <OperatorDashboard
                :insights="insights"
                :inventory="inventory"
            />
        </template>

    </AdminLayout>
</template>
