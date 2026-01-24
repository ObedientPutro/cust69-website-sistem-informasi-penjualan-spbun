<script setup>
import FlashMessage from '@/Components/Ui/FlashMessage.vue';
import { useSidebarProvider } from '@/Composables/useSidebar';
import AppHeader from '@/Layouts/AppHeader.vue';
import AppSidebar from '@/Layouts/AppSidebar.vue';
import Backdrop from '@/Layouts/Backdrop.vue';
import ThemeProvider from '@/Layouts/ThemeProvider.vue';
const { isExpanded, isHovered } = useSidebarProvider();
</script>

<template>
    <ThemeProvider>
        <FlashMessage />

        <div class="min-h-screen xl:flex">
            <app-sidebar />
            <Backdrop />
            <div
                class="flex-1 transition-all duration-300 ease-in-out"
                :class="[
                    isExpanded || isHovered ? 'lg:ml-[290px]' : 'lg:ml-[90px]',
                ]"
            >
                <app-header />
                <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                    <slot></slot>
                </div>
            </div>
        </div>
    </ThemeProvider>
</template>
