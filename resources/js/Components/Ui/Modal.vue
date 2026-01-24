<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';

const props = withDefaults(
    defineProps<{
        show?: boolean;
        title?: string;
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
    }>(),
    {
        show: false,
        maxWidth: 'lg',
    },
);

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};

const closeOnEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.show) close();
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const maxWidthClass = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
}[props.maxWidth];
</script>

<template>
    <teleport to="body">
        <transition leave-active-class="duration-200">
            <div
                v-show="show"
                class="fixed inset-0 z-99999 flex items-center justify-center px-4 py-6 sm:px-0"
            >
                <transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-show="show"
                        class="fixed inset-0 transform transition-all"
                        @click="close"
                    >
                        <div
                            class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
                        />
                    </div>
                </transition>

                <transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-show="show"
                        :class="[
                            'relative flex max-h-[90vh] w-full transform flex-col rounded-2xl bg-white shadow-xl transition-all dark:bg-gray-800',
                            maxWidthClass,
                        ]"
                    >
                        <div
                            class="flex flex-none items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700"
                        >
                            <h3
                                class="text-lg font-semibold text-gray-800 dark:text-white"
                            >
                                {{ title }}
                            </h3>
                            <button
                                @click="close"
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <div
                            class="custom-scrollbar flex-1 overflow-y-auto p-6"
                        >
                            <slot />
                        </div>

                        <div
                            v-if="$slots.footer"
                            class="flex flex-none justify-end gap-3 rounded-b-2xl border-t border-gray-100 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/50"
                        >
                            <slot name="footer" />
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>
