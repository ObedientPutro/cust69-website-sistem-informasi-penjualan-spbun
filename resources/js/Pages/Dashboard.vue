<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue'
import EcommerceMetrics from '@/Components/Ecommerce/EcommerceMetrics.vue'
import MonthlyTarget from '@/Components/Ecommerce/MonthlySale.vue'
import MonthlySale from '@/Components/Ecommerce/MonthlyTarget.vue'
import CustomerDemographic from '@/Components/Ecommerce/CustomerDemographic.vue'
import StatisticsChart from '@/Components/Ecommerce/StatisticsChart.vue'
import RecentOrders from '@/Components/Ecommerce/RecentOrders.vue'
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout>
        <div class="grid grid-cols-12 gap-4 md:gap-6">
            <div class="col-span-12 space-y-6 xl:col-span-7">
                <ecommerce-metrics />
                <monthly-target />
            </div>
            <div class="col-span-12 xl:col-span-5">
                <monthly-sale />
            </div>

            <div class="col-span-12">
                <statistics-chart />
            </div>

            <div class="col-span-12 xl:col-span-5">
                <customer-demographic />
            </div>

            <div class="col-span-12 xl:col-span-7">
                <recent-orders />
            </div>
        </div>
    </AdminLayout>
</template>
