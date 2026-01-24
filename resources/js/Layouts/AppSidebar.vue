<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from "vue";
import { useSidebar } from '@/Composables/useSidebar';
import {
    ChevronDownIcon,
    DocsIcon,
    GridIcon,
    HorizontalDots,
    ListIcon,
    PieChartIcon,
    TableIcon,
    SendIcon,
} from '@/Components/Icons';
const { isExpanded, isMobileOpen, isHovered, openSubmenu } = useSidebar();
const page = usePage();
const userRole = computed(() => page.props.auth.user.role);

const menuGroupsRaw = [
    {
        title: 'Menu Utama',
        items: [
            {
                icon: GridIcon,
                name: 'Dashboard',
                path: '/dashboard',
                roles: ['owner'],
            },
            {
                name: 'Transaksi',
                icon: SendIcon,
                roles: ['owner'],
                subItems: [
                    {
                        name: 'POS Kasir',
                        path: '/pos',
                        roles: ['owner'],
                    },
                    {
                        name: 'Transaksi Bon',
                        path: '/products',
                        roles: ['owner'],
                    },
                ],
            },
            {
                name: 'Master Data',
                icon: ListIcon,
                roles: ['owner'],
                subItems: [
                    {
                        name: 'Kelola User',
                        path: '/users',
                        roles: ['owner'],
                    },
                    {
                        name: 'Produk BBM',
                        path: '/products',
                        roles: ['owner'],
                    },
                    {
                        name: 'Kelola Customer',
                        path: '/customers',
                        roles: ['owner'],
                    },
                ],
            },
            {
                name: 'Riwayat Transaksi',
                icon: TableIcon,
                roles: ['owner'],
                subItems: [
                    {
                        name: "Mutasi Stock",
                        path: "/history/restocks",
                        roles: ['owner']
                    },
                    {
                        name: "Mutasi Sounding",
                        path: "/history/soundings",
                        roles: ['owner']
                    },
                    {
                        name: 'Mutasi Transaksi',
                        path: '/transactions',
                        roles: ['owner'],
                    },
                ],
            },
        ],
    },
    {
        title: 'Laporan',
        items: [
            {
                icon: PieChartIcon,
                name: 'Laporan Keuangan',
                path: '/reports/financial',
                roles: ['owner'],
            },
            {
                icon: DocsIcon,
                name: 'Laporan Stok',
                path: '/reports/stock',
                roles: ['owner'],
            },
        ],
    },
];

const menuGroups = computed(() => {
    const role = userRole.value;
    return menuGroupsRaw.map(group => {
        const filteredItems = group.items.filter(item => item.roles ? item.roles.includes(role) : true)
            .map(item => {
                if (item.subItems) {
                    return { ...item, subItems: item.subItems.filter(si => si.roles ? si.roles.includes(role) : true) };
                }
                return item;
            });
        return { ...group, items: filteredItems };
    }).filter(group => group.items.length > 0);
});

const isActive = (path) => window.location.pathname.startsWith(path);
const toggleSubmenu = (groupIndex, itemIndex) => {
    const key = `${groupIndex}-${itemIndex}`;
    openSubmenu.value = openSubmenu.value === key ? null : key;
};
const isSubmenuOpen = (groupIndex, itemIndex) => `${groupIndex}-${itemIndex}` === openSubmenu.value;

const checkActiveMenu = () => {
    const currentPath = window.location.pathname;
    menuGroups.value.forEach((group, gIndex) => {
        group.items.forEach((item, iIndex) => {
            if (item.subItems) {
                const hasActiveChild = item.subItems.some(sub => currentPath.startsWith(sub.path));
                if (hasActiveChild) {
                    openSubmenu.value = `${gIndex}-${iIndex}`;
                }
            }
        });
    });
};

onMounted(() => {
    checkActiveMenu();
});

watch(() => page.url, () => {
    checkActiveMenu();
});

const onEnter = (el) => {
    el.style.height = '0';
    el.offsetHeight;
    el.style.height = el.scrollHeight + 'px';
};

const onAfterEnter = (el) => {
    el.style.height = 'auto';
};

const onBeforeLeave = (el) => {
    el.style.height = el.scrollHeight + 'px';
    el.offsetHeight;
};

const onLeave = (el) => {
    el.style.height = '0';
};
</script>

<template>
    <aside
        :class="[
            'fixed top-0 left-0 z-99999 mt-16 flex h-screen flex-col border-r border-gray-200 bg-white px-5 text-gray-900 transition-all duration-300 ease-in-out lg:mt-0 dark:border-gray-800 dark:bg-gray-900',
            {
                'lg:w-[290px]': isExpanded || isMobileOpen || isHovered,
                'lg:w-[90px]': !isExpanded && !isHovered,
                'w-[290px] translate-x-0': isMobileOpen,
                '-translate-x-full': !isMobileOpen,
                'lg:translate-x-0': true,
            },
        ]"
        @mouseenter="!isExpanded && (isHovered = true)"
        @mouseleave="isHovered = false"
    >
        <div
            :class="[
                'flex py-8',
                !isExpanded && !isHovered
                    ? 'lg:justify-center'
                    : 'justify-start',
            ]"
        >
            <Link href="/dashboard">
                <img
                    v-if="isExpanded || isHovered || isMobileOpen"
                    class="dark:hidden"
                    src="/images/logo/logo.svg"
                    alt="Logo"
                    width="150"
                    height="40"
                />
                <img
                    v-if="isExpanded || isHovered || isMobileOpen"
                    class="hidden dark:block"
                    src="/images/logo/logo-dark.svg"
                    alt="Logo"
                    width="150"
                    height="40"
                />
                <img
                    v-else
                    src="/images/logo/logo-icon.svg"
                    alt="Logo"
                    width="32"
                    height="32"
                />
            </Link>
        </div>

        <div
            class="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear"
        >
            <nav class="mb-6">
                <div class="flex flex-col gap-4">
                    <div
                        v-for="(menuGroup, groupIndex) in menuGroups"
                        :key="groupIndex"
                    >
                        <h2
                            :class="[
                                'mb-4 flex text-xs leading-[20px] text-gray-400 uppercase',
                                !isExpanded && !isHovered
                                    ? 'lg:justify-center'
                                    : 'justify-start',
                            ]"
                        >
                            <template
                                v-if="isExpanded || isHovered || isMobileOpen"
                            >
                                {{ menuGroup.title }}
                            </template>
                            <HorizontalDots v-else />
                        </h2>

                        <ul class="flex flex-col gap-4">
                            <li
                                v-for="(item, index) in menuGroup.items"
                                :key="item.name"
                            >
                                <button
                                    v-if="
                                        item.subItems &&
                                        item.subItems.length > 0
                                    "
                                    @click="toggleSubmenu(groupIndex, index)"
                                    :class="[
                                        'menu-item group w-full',
                                        {
                                            'menu-item-active': isSubmenuOpen(
                                                groupIndex,
                                                index,
                                            ),
                                            'menu-item-inactive':
                                                !isSubmenuOpen(
                                                    groupIndex,
                                                    index,
                                                ),
                                        },
                                        !isExpanded && !isHovered
                                            ? 'lg:justify-center'
                                            : 'lg:justify-start',
                                    ]"
                                >
                                    <span
                                        :class="[
                                            isSubmenuOpen(groupIndex, index)
                                                ? 'menu-item-icon-active'
                                                : 'menu-item-icon-inactive',
                                        ]"
                                    >
                                        <component :is="item.icon" />
                                    </span>
                                    <span
                                        v-if="
                                            isExpanded ||
                                            isHovered ||
                                            isMobileOpen
                                        "
                                        class="menu-item-text"
                                        >{{ item.name }}</span
                                    >
                                    <ChevronDownIcon
                                        v-if="
                                            isExpanded ||
                                            isHovered ||
                                            isMobileOpen
                                        "
                                        :class="[
                                            'ml-auto h-5 w-5 transition-transform duration-200',
                                            {
                                                'text-brand-500 rotate-180':
                                                    isSubmenuOpen(
                                                        groupIndex,
                                                        index,
                                                    ),
                                            },
                                        ]"
                                    />
                                </button>

                                <Link
                                    v-else-if="item.path"
                                    :href="item.path"
                                    :class="[
                                        'menu-item group',
                                        {
                                            'menu-item-active': isActive(
                                                item.path,
                                            ),
                                            'menu-item-inactive': !isActive(
                                                item.path,
                                            ),
                                        },
                                    ]"
                                >
                                    <span
                                        :class="[
                                            isActive(item.path)
                                                ? 'menu-item-icon-active'
                                                : 'menu-item-icon-inactive',
                                        ]"
                                    >
                                        <component :is="item.icon" />
                                    </span>
                                    <span
                                        v-if="
                                            isExpanded ||
                                            isHovered ||
                                            isMobileOpen
                                        "
                                        class="menu-item-text"
                                        >{{ item.name }}</span
                                    >
                                </Link>

                                <transition
                                    @enter="onEnter"
                                    @after-enter="onAfterEnter"
                                    @before-leave="onBeforeLeave"
                                    @leave="onLeave"
                                >
                                    <div
                                        v-show="
                                            isSubmenuOpen(groupIndex, index) &&
                                            (isExpanded ||
                                                isHovered ||
                                                isMobileOpen)
                                        "
                                        class="overflow-hidden transition-[height] duration-300 ease-in-out"
                                    >
                                        <ul class="mt-2 ml-9 space-y-1">
                                            <li
                                                v-for="subItem in item.subItems"
                                                :key="subItem.name"
                                            >
                                                <Link
                                                    :href="subItem.path"
                                                    :class="[
                                                        'menu-dropdown-item',
                                                        {
                                                            'menu-dropdown-item-active':
                                                                isActive(
                                                                    subItem.path,
                                                                ),
                                                            'menu-dropdown-item-inactive':
                                                                !isActive(
                                                                    subItem.path,
                                                                ),
                                                        },
                                                    ]"
                                                >
                                                    {{ subItem.name }}
                                                </Link>
                                            </li>
                                        </ul>
                                    </div>
                                </transition>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </aside>
</template>
