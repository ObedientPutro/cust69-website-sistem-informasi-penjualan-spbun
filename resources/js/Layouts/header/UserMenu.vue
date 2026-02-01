<script setup>
import {
    ChevronDownIcon,
    LogoutIcon,
    UserCircleIcon,
} from '@/Components/Icons';
import { Link, router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, computed } from 'vue';

const dropdownOpen = ref(false);
const dropdownRef = ref(null);

const page = usePage();
const user = computed(() => page.props.auth.user);

const menuItems = [
    { href: route('profile.edit'), icon: UserCircleIcon, text: 'Sunting Akun' },
];

const toggleDropdown = () => {
    dropdownOpen.value = !dropdownOpen.value;
};

const closeDropdown = () => {
    dropdownOpen.value = false;
};

const signOut = () => {
    router.post(route('logout'), {}, {
        onFinish: () => closeDropdown(),
    });
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
            <span class="mr-3 h-11 w-11 overflow-hidden rounded-full border border-gray-200 dark:border-gray-700">
                <img
                    :src="user.photo_url"
                    :alt="user.name"
                    class="h-full w-full object-cover"
                />
            </span>

            <span class="text-theme-sm mr-1 block font-medium">
                {{ user.name.split(' ')[0] }}
            </span>

            <ChevronDownIcon :class="{ 'rotate-180': dropdownOpen }" />
        </button>

        <!-- Dropdown Start -->
        <div
            v-if="dropdownOpen"
            class="shadow-theme-lg dark:bg-gray-dark absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800"
        >
            <div class="px-3 py-2">
                <span
                    class="text-theme-sm block font-medium text-gray-700 dark:text-gray-400 truncate"
                >
                    {{ user.name }}
                </span>
                <span
                    class="text-theme-xs mt-0.5 block text-gray-500 dark:text-gray-400 truncate"
                >
                    {{ user.email }}
                </span>
                <span
                    class="text-[10px] uppercase font-bold tracking-wider mt-1 px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 inline-block"
                >
                    {{ user.role }}
                </span>
            </div>

            <ul
                class="flex flex-col gap-1 border-b border-gray-200 pt-4 pb-3 dark:border-gray-800"
            >
                <li v-for="item in menuItems" :key="item.href">
                    <Link
                        :href="item.href"
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
            <button
                @click="signOut"
                class="group text-theme-sm mt-3 flex w-full items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300 text-left"
            >
                <LogoutIcon
                    class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300"
                />
                Sign out
            </button>
        </div>
        <!-- Dropdown End -->
    </div>
</template>
