<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" :class="className" v-bind="$attrs">
        <!-- Main sparkle/star -->
        <path fill="currentColor" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" opacity="0.9" />
        <!-- Small accent sparkles -->
        <path fill="currentColor" d="M6 6l-1 2-2-1 2-1 1 2zM20 4l-0.5 1-1-0.5 1-0.5 0.5 1zM18 18l-0.5 1-1-0.5 1-0.5 0.5 1z" opacity="1" />
    </svg>
</template>
