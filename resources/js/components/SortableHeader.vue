<template>
    <button class="hover:text-foreground flex items-center gap-1 transition-colors" @click="$emit('sort', field)">
        <slot />
        <Icon v-if="currentSort === field" :name="direction === 'asc' ? 'chevron-up' : 'chevron-down'" class="h-3 w-3" />
        <Icon v-else name="chevrons-up-down" class="h-3 w-3 opacity-50" />
    </button>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';

interface Props {
    field: string;
    currentSort: string;
    direction: 'asc' | 'desc';
}

defineProps<Props>();
defineEmits<{
    sort: [field: string];
}>();
</script>
