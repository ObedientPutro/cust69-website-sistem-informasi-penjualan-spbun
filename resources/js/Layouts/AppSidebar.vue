<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from "vue";
import { useSidebar } from '@/Composables/useSidebar';
import {
    ChevronDownIcon,
    GridIcon,
    HorizontalDots,
    PieChartIcon,
    UserGroupIcon,
    UserCircleIcon,
    SendIcon,
    SettingsIcon,
    BoxIcon,
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
                path: route('dashboard'),
                roles: ['owner', 'operator'],
            },
            {
                name: 'Transaksi',
                icon: SendIcon,
                roles: ['owner', 'operator'],
                subItems: [
                    {
                        name: 'POS Kasir',
                        path: route('transactions.create'),
                        roles: ['owner', 'operator'],
                    },
                    {
                        name: 'Manajemen Piutang',
                        path: route('debts.index'),
                        roles: ['owner', 'operator'],
                    },
                    {
                        name: 'Shift Totalisator',
                        path: route('shifts.index'),
                        roles: ['owner', 'operator'],
                    },
                    {
                        name: 'Mutasi Transaksi',
                        path: route('history.transactions.index'),
                        roles: ['owner', 'operator'],
                    },
                ],
            },
            {
                icon: UserCircleIcon,
                name: 'Kelola Pelanggan',
                path: route('customers.index'),
                roles: ['owner', 'operator'],
            },
            {
                name: 'Produk BBM',
                icon: BoxIcon,
                roles: ['owner', 'operator'],
                subItems: [
                    {
                        name: 'Kelola Data Produk',
                        path: route('products.index'),
                        roles: ['owner', 'operator'],
                    },
                    {
                        name: 'Riwayat Pembelian Stock',
                        path: route('restock-history.index'),
                        roles: ['owner', 'operator'],
                    },
                    {
                        name: 'Riwayat Sounding',
                        path: route('sounding-history.index'),
                        roles: ['owner', 'operator'],
                    },
                ],
            },
        ],
    },
    {
        title: 'Menu Khusus Owner',
        items: [
            {
                name: 'Laporan Transaksi',
                icon: PieChartIcon,
                roles: ['owner'],
                subItems: [
                    {
                        name: 'Laporan Penjualan',
                        path: route('reports.sales'),
                        roles: ['owner'],
                    },
                    {
                        name: 'Laporan Laba Rugi',
                        path: route('reports.profit'),
                        roles: ['owner'],
                    },
                    {
                        name: 'Laporan Stok',
                        path: route('reports.stock'),
                        roles: ['owner'],
                    },
                ],
            },
            {
                icon: UserGroupIcon,
                name: 'Kelola Akun Pengguna',
                path: route('users.index'),
                roles: ['owner'],
            },
            {
                icon: SettingsIcon,
                name: 'Pengaturan Website',
                path: route('settings.index'),
                roles: ['owner'],
            }
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

const getPathFromUrl = (url) => {
    if (!url) return '';
    try {
        const parsedUrl = new URL(url, window.location.origin);
        return parsedUrl.pathname;
    } catch (e) {
        return url;
    }
};

const isActive = (url) => {
    const currentPath = window.location.pathname;
    const targetPath = getPathFromUrl(url);
    if (currentPath === targetPath) return true;
    return targetPath !== '/' && currentPath.startsWith(targetPath + '/');

};
const toggleSubmenu = (groupIndex, itemIndex) => {
    const key = `${groupIndex}-${itemIndex}`;
    openSubmenu.value = openSubmenu.value === key ? null : key;
};
const isSubmenuOpen = (groupIndex, itemIndex) => `${groupIndex}-${itemIndex}` === openSubmenu.value;

const checkActiveMenu = () => {
    menuGroups.value.forEach((group, gIndex) => {
        group.items.forEach((item, iIndex) => {
            if (item.subItems) {
                const hasActiveChild = item.subItems.some(sub => isActive(sub.path));
                if (hasActiveChild) {
                    openSubmenu.value = `${gIndex}-${iIndex}`;
                }
            }
        });
    });
};

// Cek apakah device mendukung hover (Mouse)
// Jika touchscreen, logic ini akan return false, jadi sidebar TIDAK expand saat ditap
const handleMouseEnter = () => {
    if (window.matchMedia('(hover: hover)').matches) {
        if (!isExpanded.value) {
            isHovered.value = true;
        }
    }
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
        @mouseenter="handleMouseEnter"
        @mouseleave="isHovered = false"
    >
        <div
            :class="[
                'flex h-24 items-center',
                !isExpanded && !isHovered
                    ? 'lg:justify-center'
                    : 'justify-start',
            ]"
        >
            <Link href="/dashboard">
                <img
                    v-if="isExpanded || isHovered || isMobileOpen"
                    class="dark:hidden h-9 w-auto mx-auto"
                    src="/images/logo/logo-light.png"
                    alt="Logo"
                />
                <img
                    v-if="isExpanded || isHovered || isMobileOpen"
                    class="hidden dark:block h-9 w-auto mx-auto"
                    src="/images/logo/logo-dark.png"
                    alt="Logo"
                />
                <img
                    v-else
                    class="h-9 w-auto mx-auto"
                    src="/images/logo/logo-icon.png"
                    alt="Logo"
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
