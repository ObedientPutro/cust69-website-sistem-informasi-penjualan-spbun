<script setup lang="ts">
defineProps<{
    products: any[]
}>();
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="mb-6 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Monitoring Stok (Tangki)</h3>
        </div>

        <div class="space-y-5">
            <div v-for="item in products" :key="item.id" class="group">
                <div class="mb-2 flex justify-between items-end">
                    <div>
                        <p class="font-bold text-gray-800 dark:text-white">{{ item.name }}</p>
                        <p class="text-xs text-gray-500">
                            Habis dalam: <span :class="item.status === 'critical' ? 'text-red-600 font-bold' : 'text-gray-600'">{{ item.days_to_empty }} Hari</span>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-mono text-lg font-bold text-gray-800 dark:text-white">{{ item.stock }} <span class="text-xs font-normal text-gray-500">{{ item.unit }}</span></p>
                        <p class="text-xs text-gray-400">Rata-rata keluar: {{ item.avg_sales }}/hari</p>
                    </div>
                </div>

                <div class="relative h-4 w-full rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                    <div
                        class="absolute top-0 left-0 h-full rounded-full transition-all duration-1000 ease-out"
                        :class="{
                            'bg-red-500': item.status === 'critical',
                            'bg-yellow-500': item.status === 'warning',
                            'bg-blue-500': item.status === 'safe',
                        }"
                        style="width: 100%;"
                    ></div>
                    <div
                        class="absolute top-0 right-0 h-full bg-gray-200/50 dark:bg-black/50"
                        :style="{ width: Math.max(0, 100 - (item.stock / 5000 * 100)) + '%' }"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
