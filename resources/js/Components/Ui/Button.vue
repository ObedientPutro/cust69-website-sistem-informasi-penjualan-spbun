<script setup lang="ts">
interface ButtonProps {
    size?: 'sm' | 'md';
    variant?: 'primary' | 'outline' | 'danger';
    startIcon?: object;
    endIcon?: object;
    onClick?: () => void;
    className?: string;
    disabled?: boolean;
    processing?: boolean;
}

const props = withDefaults(defineProps<ButtonProps>(), {
    size: 'md',
    variant: 'primary',
    className: '',
    disabled: false,
    processing: false,
});

const sizeClasses = {
    sm: 'px-4 py-3 text-sm',
    md: 'px-5 py-3.5 text-sm',
};

const variantClasses = {
    primary:
        'bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600 disabled:bg-brand-300',
    outline:
        'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03] dark:hover:text-gray-300',
    danger: 'bg-error-500 text-white hover:bg-error-600',
};

const onClick = () => {
    if (!props.disabled && !props.processing && props.onClick) {
        props.onClick();
    }
};
</script>

<template>
    <button
        :class="[
            'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition disabled:cursor-not-allowed disabled:opacity-50',
            sizeClasses[size],
            variantClasses[variant],
            className,
        ]"
        @click="onClick"
        :disabled="disabled || processing"
    >
        <span v-if="processing" class="flex items-center gap-2">
            <svg
                class="h-5 w-5 animate-spin"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                ></circle>
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
            </svg>
            <span v-if="$slots.default">Loading...</span>
        </span>

        <template v-else>
            <span v-if="startIcon" class="flex items-center">
                <component :is="startIcon" />
            </span>
            <slot></slot>
            <span v-if="endIcon" class="flex items-center">
                <component :is="endIcon" />
            </span>
        </template>
    </button>
</template>
