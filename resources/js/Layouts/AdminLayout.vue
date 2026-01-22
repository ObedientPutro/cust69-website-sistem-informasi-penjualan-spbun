<script setup>
import AppSidebar from '@/Layouts/AppSidebar.vue';
import AppHeader from '@/Layouts/AppHeader.vue';
import Backdrop from '@/Layouts/Backdrop.vue';
import { useSidebarProvider } from '@/Composables/useSidebar';
const { isExpanded, isHovered } = useSidebarProvider();
import ThemeProvider from '@/Layouts/ThemeProvider.vue'
</script>

<template>
    <ThemeProvider>
        <div class="min-h-screen xl:flex">
            <app-sidebar />
            <Backdrop />
            <div
                class="flex-1 transition-all duration-300 ease-in-out"
                :class="[isExpanded || isHovered ? 'lg:ml-[290px]' : 'lg:ml-[90px]']"
            >
                <app-header />
                <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
                    <slot></slot>
                </div>
            </div>
        </div>
    </ThemeProvider>
</template>
