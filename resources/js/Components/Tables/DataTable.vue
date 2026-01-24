<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';

// Definisi Props dengan Type Alignment
const props = defineProps<{
    rows: any[];
    // Tambahkan properti 'align' (optional)
    columns: {
        label: string;
        key: string;
        sortable?: boolean;
        align?: 'left' | 'center' | 'right';
    }[];
    filters?: { search?: string; sort?: string; direction?: string };
    pagination?: any;
}>();

const search = ref(props.filters?.search || '');
const currentSort = ref(props.filters?.sort || '');
const currentDirection = ref(props.filters?.direction || 'desc');

// Helper untuk class alignment
const getAlignClass = (align?: string) => {
    switch (align) {
        case 'center': return 'text-center';
        case 'right': return 'text-right';
        default: return 'text-left'; // Default Left
    }
};

// ... Logic Search, Sort, Pagination SAMA SEPERTI SEBELUMNYA ...
const handleSearch = debounce((value: string) => {
    refreshTable({ search: value, page: 1 });
}, 300);

watch(search, (value) => {
    handleSearch(value);
});

const handleSort = (key: string) => {
    let direction = 'asc';
    if (currentSort.value === key) {
        direction = currentDirection.value === 'asc' ? 'desc' : 'asc';
    }
    currentSort.value = key;
    currentDirection.value = direction;
    refreshTable({ sort: key, direction: direction });
};

const changePage = (url: string | null) => {
    if (url) router.get(url, {}, { preserveState: true, preserveScroll: true });
};

const refreshTable = (params: object) => {
    router.get(
        window.location.pathname,
        {
            search: search.value,
            sort: currentSort.value,
            direction: currentDirection.value,
            ...params
        },
        { preserveState: true, replace: true, preserveScroll: true }
    );
};
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="relative w-full sm:w-72">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search data..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 bg-transparent text-sm focus:border-brand-500 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900"
                />
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9.80553" cy="9.80553" r="7.49047"/><path d="M15.0153 15.4043L17.9519 18.3334"/></svg>
                </span>
            </div>

            <div class="flex gap-2">
                <slot name="actions" />
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-200 dark:bg-white/[0.02] dark:border-gray-700">
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        class="px-5 py-3 font-medium text-gray-500 text-sm dark:text-gray-400 select-none"
                        :class="[
                                getAlignClass(col.align), // Class Dinamis (Left/Center/Right)
                                { 'cursor-pointer hover:text-brand-500 hover:bg-gray-100 dark:hover:bg-white/5 transition': col.sortable }
                            ]"
                        @click="col.sortable ? handleSort(col.key) : null"
                    >
                        <div class="flex items-center gap-1" :class="{'justify-center': col.align === 'center', 'justify-end': col.align === 'right'}">
                            {{ col.label }}

                            <span v-if="col.sortable" class="flex flex-col text-[10px] leading-none text-gray-400">
                                    <svg class="w-3 h-3 transition-colors" :class="currentSort === col.key && currentDirection === 'asc' ? 'text-brand-500' : 'text-gray-300'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" /></svg>
                                    <svg class="w-3 h-3 -mt-1 transition-colors" :class="currentSort === col.key && currentDirection === 'desc' ? 'text-brand-500' : 'text-gray-300'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </span>
                        </div>
                    </th>

                    <th class="px-5 py-3 font-medium text-gray-500 text-sm dark:text-gray-400 text-center w-[120px]">
                        Actions
                    </th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="(row, index) in rows" :key="index" class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition">
                    <td
                        v-for="col in columns"
                        :key="col.key"
                        class="px-5 py-4 text-sm text-gray-800 dark:text-white/90"
                        :class="getAlignClass(col.align)"
                    >
                        <slot :name="'cell-' + col.key" :row="row">
                            {{ row[col.key] }}
                        </slot>
                    </td>

                    <td class="px-5 py-4 text-center">
                        <slot name="actions-row" :row="row" />
                    </td>
                </tr>
                <tr v-if="rows.length === 0">
                    <td :colspan="columns.length + 1" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                        No data found.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div v-if="pagination && pagination.links.length > 3" class="px-5 py-4 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-3">
            <div class="text-sm text-gray-500">
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
            </div>
            <div class="flex flex-wrap gap-1 justify-center">
                <button
                    v-for="(link, i) in pagination.links"
                    :key="i"
                    @click="changePage(link.url)"
                    :disabled="!link.url || link.active"
                    v-html="link.label"
                    class="px-3 py-1 rounded text-sm transition border"
                    :class="[
                        link.active
                            ? 'bg-brand-500 text-white border-brand-500'
                            : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700',
                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                ></button>
            </div>
        </div>
    </div>
</template>
