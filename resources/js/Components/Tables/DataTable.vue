<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { ref, watch } from 'vue';

const props = withDefaults(defineProps<{
    rows: any[];
    columns: { label: string; key: string; sortable?: boolean; align?: 'left' | 'center' | 'right' }[];
    filters?: { search?: string; sort?: string; direction?: string };
    pagination?: any;
    enableActions?: boolean;
    enableSearch?: boolean;
    searchInfo?: string;
}>(), {
    enableActions: true,
    enableSearch: true
});

const search = ref(props.filters?.search || '');
const currentSort = ref(props.filters?.sort || '');
const currentDirection = ref(props.filters?.direction || 'desc');

const getAlignClass = (align?: string) => {
    switch (align) {
        case 'center':
            return 'text-center';
        case 'right':
            return 'text-right';
        default:
            return 'text-left'; // Default Left
    }
};

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
            ...params,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
};
</script>

<template>
    <div
        class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
    >
        <div
            v-if="enableSearch || $slots.actions || $slots.filters"
            class="flex flex-col items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 sm:flex-row dark:border-gray-700"
        >
            <div
                class="flex flex-col items-center justify-between gap-4 sm:flex-row w-full"
            >
                <div v-if="enableSearch" class="relative w-full sm:w-72">
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="searchInfo ?? 'Search data...'"
                        class="focus:border-brand-500 focus:ring-brand-500/20 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-10 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white placeholder:text-gray-400"
                    />
                    <span
                        class="absolute top-1/2 left-3 -translate-y-1/2 text-gray-400"
                    >
                        <svg
                            width="20"
                            height="20"
                            viewBox="0 0 20 20"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="9.80553" cy="9.80553" r="7.49047" />
                            <path d="M15.0153 15.4043L17.9519 18.3334" />
                        </svg>
                    </span>
                </div>
                <div v-else class="w-full"></div>

                <div class="flex gap-2">
                    <slot name="actions" />
                </div>
            </div>

            <div
                v-if="$slots.filters"
                class="flex flex-col gap-3 border-t border-gray-100 pt-2 sm:flex-row dark:border-gray-700/50"
            >
                <slot name="filters" />
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                    <th v-for="col in columns" :key="col.key"
                        class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 cursor-pointer hover:text-brand-500"
                        :class="getAlignClass(col.align)"
                        @click="col.sortable && handleSort(col.key)"
                    >
                        <div class="flex items-center gap-1" :class="{'justify-center': col.align === 'center', 'justify-end': col.align === 'right'}">
                            {{ col.label }}
                        </div>
                    </th>

                    <th v-if="enableActions" class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 text-center">
                        Actions
                    </th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="(row, index) in rows" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                    <td v-for="col in columns" :key="col.key" class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap" :class="getAlignClass(col.align)">
                        <slot :name="`cell-${col.key}`" :row="row">
                            {{ row[col.key] }}
                        </slot>
                    </td>

                    <td v-if="enableActions" class="px-5 py-4 text-sm text-center whitespace-nowrap">
                        <slot name="actions-row" :row="row" />
                    </td>
                </tr>
                <tr v-if="rows.length === 0">
                    <td :colspan="columns.length + (enableActions ? 1 : 0)" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400 italic">
                        No data available.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="pagination && pagination.links && pagination.links.length > 3"
            class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 px-5 py-4 sm:flex-row dark:border-gray-700"
        >
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Showing {{ pagination.from }} to {{ pagination.to }} of
                {{ pagination.total }} entries
            </div>
            <div class="flex flex-wrap justify-center gap-1">
                <button
                    v-for="(link, i) in pagination.links"
                    :key="i"
                    @click="changePage(link.url)"
                    :disabled="!link.url || link.active"
                    v-html="link.label"
                    class="rounded border px-3 py-1 text-sm transition"
                    :class="[
                        link.active
                            ? 'bg-brand-500 border-brand-500 text-white'
                            : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700',
                        !link.url ? 'cursor-not-allowed opacity-50' : '',
                    ]"
                ></button>
            </div>
        </div>
    </div>
</template>
