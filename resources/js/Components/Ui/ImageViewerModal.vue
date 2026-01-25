<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue';

const props = defineProps<{
    show: boolean;
    imageSrc: string | null;
    altText?: string;
}>();

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};

const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', handleKeydown));
onUnmounted(() => document.removeEventListener('keydown', handleKeydown));

watch(() => props.show, (val) => {
    document.body.style.overflow = val ? 'hidden' : '';
});
</script>

<template>
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/90 backdrop-blur-sm p-4"
            @click="close"
        >
            <button
                class="absolute top-5 right-5 text-white/70 hover:text-white transition p-2 rounded-full bg-white/10 hover:bg-white/20"
                @click.stop="close"
            >
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            <div class="relative max-w-full max-h-full" @click.stop>
                <img
                    :src="imageSrc || ''"
                    :alt="altText"
                    class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl transition-transform duration-300 scale-100"
                />

                <p v-if="altText" class="mt-4 text-center text-white/90 text-lg font-medium">
                    {{ altText }}
                </p>
            </div>
        </div>
    </transition>
</template>

<style scoped>

</style>
