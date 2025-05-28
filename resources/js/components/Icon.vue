<script setup lang="ts">
import { cn } from '@/lib/utils';
import * as icons from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    name: string;
    class?: string;
    size?: number | string;
    color?: string;
    strokeWidth?: number | string;
}

const props = withDefaults(defineProps<Props>(), {
    class: '',
    size: 16,
    strokeWidth: 2,
});

const className = computed(() => cn('h-4 w-4', props.class));

// Memoize icon name transformations to avoid repeated string operations
const iconNameCache = new Map<string, string>();

const icon = computed(() => {
    // Check cache first
    if (iconNameCache.has(props.name)) {
        const cachedIconName = iconNameCache.get(props.name)!;
        return (icons as Record<string, any>)[cachedIconName];
    }

    // Convert kebab-case to PascalCase (e.g., "trash-2" -> "Trash2")
    const iconName = props.name
        .split('-')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join('');

    // Cache the result
    iconNameCache.set(props.name, iconName);

    return (icons as Record<string, any>)[iconName];
});
</script>

<template>
    <component :is="icon" :class="className" :size="size" :stroke-width="strokeWidth" :color="color" />
</template>
