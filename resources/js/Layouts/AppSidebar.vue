<script setup>
import { ref, computed } from "vue";
import { Link, usePage } from '@inertiajs/vue3'; // Import usePage

import {
    GridIcon,
    CalenderIcon,
    UserCircleIcon,
    ChatIcon,
    MailIcon,
    DocsIcon,
    PieChartIcon,
    ChevronDownIcon,
    HorizontalDots,
    PageIcon,
    TableIcon,
    ListIcon,
    PlugInIcon,
} from "@/Components/Icons";
import { useSidebar } from '@/Composables/useSidebar';

// 1. Setup Data Sidebar & User
const { isExpanded, isMobileOpen, isHovered, openSubmenu } = useSidebar();
const page = usePage();
const userRole = computed(() => page.props.auth.user.role); // Ambil role: 'owner' | 'admin' | 'operator'

// 2. Definisi Menu dengan Permissions (Roles)
const menuGroupsRaw = [
    {
        title: "Menu Utama",
        items: [
            {
                icon: GridIcon,
                name: "Dashboard",
                path: "/dashboard",
                roles: ['owner', 'admin', 'operator'], // Semua bisa lihat
            },
            {
                icon: UserCircleIcon,
                name: "Kelola User",
                path: "/users",
                roles: ['owner'], // HANYA OWNER (Sesuai Request)
            },
            {
                name: "Master Data",
                icon: ListIcon,
                roles: ['owner', 'admin'], // Owner & Admin
                subItems: [
                    { name: "Produk BBM", path: "/products", roles: ['owner', 'admin'] },
                    { name: "Tangki / Stok", path: "/tanks", roles: ['owner', 'admin'] },
                ],
            },
            {
                name: "Transaksi",
                icon: TableIcon,
                roles: ['owner', 'admin', 'operator'],
                subItems: [
                    { name: "Penjualan (POS)", path: "/pos", roles: ['admin', 'operator'] },
                    { name: "Riwayat Transaksi", path: "/transactions", roles: ['owner', 'admin'] },
                ],
            },
        ],
    },
    {
        title: "Laporan",
        items: [
            {
                icon: PieChartIcon,
                name: "Laporan Keuangan",
                path: "/reports/financial",
                roles: ['owner', 'admin'],
            },
            {
                icon: DocsIcon,
                name: "Laporan Stok",
                path: "/reports/stock",
                roles: ['owner', 'admin'],
            },
        ],
    },
];

// 3. Logic Filtering Menu Berdasarkan Role
const menuGroups = computed(() => {
    const role = userRole.value;

    return menuGroupsRaw.map(group => {
        // Filter Items utama
        const filteredItems = group.items.filter(item => {
            // Cek apakah role user ada di daftar roles item tersebut
            return item.roles ? item.roles.includes(role) : true;
        }).map(item => {
            // Jika punya subItems, filter juga subItems-nya
            if (item.subItems) {
                const filteredSubItems = item.subItems.filter(subItem =>
                    subItem.roles ? subItem.roles.includes(role) : true
                );
                return { ...item, subItems: filteredSubItems };
            }
            return item;
        });

        return { ...group, items: filteredItems };
    }).filter(group => group.items.length > 0); // Hapus grup jika isinya kosong setelah difilter
});

const route = usePage(); // Helper untuk cek active route (opsional, bisa pakai useRoute)
const isActive = (path) => window.location.pathname.startsWith(path); // Simple active check

const toggleSubmenu = (groupIndex, itemIndex) => {
    const key = `${groupIndex}-${itemIndex}`;
    openSubmenu.value = openSubmenu.value === key ? null : key;
};

const isSubmenuOpen = (groupIndex, itemIndex) => {
    const key = `${groupIndex}-${itemIndex}`;
    return openSubmenu.value === key;
};

const startTransition = (el) => {
    el.style.height = "auto";
    const height = el.scrollHeight;
    el.style.height = "0px";
    el.offsetHeight; // force reflow
    el.style.height = height + "px";
};

const endTransition = (el) => {
    el.style.height = "";
};
</script>

<template>
    <aside
        :class="[
      'fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-99999 border-r border-gray-200',
      {
        'lg:w-[290px]': isExpanded || isMobileOpen || isHovered,
        'lg:w-[90px]': !isExpanded && !isHovered,
        'translate-x-0 w-[290px]': isMobileOpen,
        '-translate-x-full': !isMobileOpen,
        'lg:translate-x-0': true,
      },
    ]"
        @mouseenter="!isExpanded && (isHovered = true)"
        @mouseleave="isHovered = false"
    >
        <div
            :class="[
        'py-8 flex',
        !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-start',
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

        <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
            <nav class="mb-6">
                <div class="flex flex-col gap-4">

                    <div v-for="(menuGroup, groupIndex) in menuGroups" :key="groupIndex">
                        <h2
                            :class="[
                'mb-4 text-xs uppercase flex leading-[20px] text-gray-400',
                !isExpanded && !isHovered
                  ? 'lg:justify-center'
                  : 'justify-start',
              ]"
                        >
                            <template v-if="isExpanded || isHovered || isMobileOpen">
                                {{ menuGroup.title }}
                            </template>
                            <HorizontalDots v-else />
                        </h2>

                        <ul class="flex flex-col gap-4">
                            <li v-for="(item, index) in menuGroup.items" :key="item.name">

                                <button
                                    v-if="item.subItems && item.subItems.length > 0"
                                    @click="toggleSubmenu(groupIndex, index)"
                                    :class="[
                    'menu-item group w-full',
                    {
                      'menu-item-active': isSubmenuOpen(groupIndex, index),
                      'menu-item-inactive': !isSubmenuOpen(groupIndex, index),
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
                                        v-if="isExpanded || isHovered || isMobileOpen"
                                        class="menu-item-text"
                                    >{{ item.name }}</span
                                    >
                                    <ChevronDownIcon
                                        v-if="isExpanded || isHovered || isMobileOpen"
                                        :class="[
                      'ml-auto w-5 h-5 transition-transform duration-200',
                      {
                        'rotate-180 text-brand-500': isSubmenuOpen(groupIndex, index),
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
                      'menu-item-active': isActive(item.path),
                      'menu-item-inactive': !isActive(item.path),
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
                                        v-if="isExpanded || isHovered || isMobileOpen"
                                        class="menu-item-text"
                                    >{{ item.name }}</span
                                    >
                                </Link>

                                <transition
                                    @enter="startTransition"
                                    @after-enter="endTransition"
                                    @before-leave="startTransition"
                                    @after-leave="endTransition"
                                >
                                    <div
                                        v-show="
                      isSubmenuOpen(groupIndex, index) &&
                      (isExpanded || isHovered || isMobileOpen)
                    "
                                    >
                                        <ul class="mt-2 space-y-1 ml-9">
                                            <li v-for="subItem in item.subItems" :key="subItem.name">
                                                <Link
                                                    :href="subItem.path"
                                                    :class="[
                            'menu-dropdown-item',
                            {
                              'menu-dropdown-item-active': isActive(subItem.path),
                              'menu-dropdown-item-inactive': !isActive(subItem.path),
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
