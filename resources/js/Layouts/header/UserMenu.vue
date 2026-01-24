<script setup>
import {
    ChevronDownIcon,
    InfoCircleIcon,
    LogoutIcon,
    SettingsIcon,
    UserCircleIcon,
} from '@/Components/Icons';
import { Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const dropdownOpen = ref(false);
const dropdownRef = ref(null);

const menuItems = [
    { href: '/profile', icon: UserCircleIcon, text: 'Edit profile' },
    { href: '/chat', icon: SettingsIcon, text: 'Account settings' },
    { href: '/profile', icon: InfoCircleIcon, text: 'Support' },
];

const toggleDropdown = () => {
    dropdownOpen.value = !dropdownOpen.value;
};

const closeDropdown = () => {
    dropdownOpen.value = false;
};

const signOut = () => {
    // Implement sign out logic here
    console.log('Signing out...');
    closeDropdown();
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <button
            class="flex items-center text-gray-700 dark:text-gray-400"
            @click.prevent="toggleDropdown"
        >
            <span class="mr-3 h-11 w-11 overflow-hidden rounded-full">
                <img src="/images/user/owner.jpg" alt="User" />
            </span>

            <span class="text-theme-sm mr-1 block font-medium">Musharof </span>

            <ChevronDownIcon :class="{ 'rotate-180': dropdownOpen }" />
        </button>

        <!-- Dropdown Start -->
        <div
            v-if="dropdownOpen"
            class="shadow-theme-lg dark:bg-gray-dark absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800"
        >
            <div>
                <span
                    class="text-theme-sm block font-medium text-gray-700 dark:text-gray-400"
                >
                    Musharof Chowdhury
                </span>
                <span
                    class="text-theme-xs mt-0.5 block text-gray-500 dark:text-gray-400"
                >
                    randomuser@pimjo.com
                </span>
            </div>

            <ul
                class="flex flex-col gap-1 border-b border-gray-200 pt-4 pb-3 dark:border-gray-800"
            >
                <li v-for="item in menuItems" :key="item.href">
                    <Link
                        href="item.href"
                        class="group text-theme-sm flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                    >
                        <!-- SVG icon would go here -->
                        <component
                            :is="item.icon"
                            class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300"
                        />
                        {{ item.text }}
                    </Link>
                </li>
            </ul>
            <Link
                href="/signin"
                @click="signOut"
                class="group text-theme-sm mt-3 flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
            >
                <LogoutIcon
                    class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300"
                />
                Sign out
            </Link>
        </div>
        <!-- Dropdown End -->
    </div>
</template>
