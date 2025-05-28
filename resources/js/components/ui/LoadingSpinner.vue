<template>
    <div :class="containerClasses">
        <div :class="spinnerClasses">
            <svg
                class="animate-spin"
                :class="iconClasses"
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
        </div>
        <span v-if="message" :class="messageClasses">{{ message }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    size?: 'sm' | 'md' | 'lg' | 'xl';
    variant?: 'default' | 'overlay' | 'inline';
    message?: string;
    centered?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    variant: 'default',
    centered: false,
});

const containerClasses = computed(() => {
    const base = 'flex items-center';
    const variants = {
        default: 'gap-2',
        overlay: 'fixed inset-0 z-50 bg-background/80 backdrop-blur-sm justify-center',
        inline: 'gap-2',
    };
    const centered = props.centered ? 'justify-center' : '';
    
    return `${base} ${variants[props.variant]} ${centered}`;
});

const spinnerClasses = computed(() => {
    return 'flex items-center justify-center';
});

const iconClasses = computed(() => {
    const sizes = {
        sm: 'h-4 w-4',
        md: 'h-6 w-6',
        lg: 'h-8 w-8',
        xl: 'h-12 w-12',
    };
    
    return `text-primary ${sizes[props.size]}`;
});

const messageClasses = computed(() => {
    const sizes = {
        sm: 'text-sm',
        md: 'text-base',
        lg: 'text-lg',
        xl: 'text-xl',
    };
    
    return `text-muted-foreground ${sizes[props.size]}`;
});
</script> 