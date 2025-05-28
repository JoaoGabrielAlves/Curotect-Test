<template>
    <Badge :variant="badgeVariant" :class="badgeClasses">
        {{ statusText }}
    </Badge>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';

interface Props {
    status: 'draft' | 'published' | 'archived';
}

const props = defineProps<Props>();

const statusConfig = {
    draft: {
        text: 'Draft',
        variant: 'secondary' as const,
        classes: 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-800',
    },
    published: {
        text: 'Published',
        variant: 'default' as const,
        classes: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800',
    },
    archived: {
        text: 'Archived',
        variant: 'outline' as const,
        classes: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800',
    },
};

const statusText = computed(() => statusConfig[props.status]?.text || props.status);
const badgeVariant = computed(() => statusConfig[props.status]?.variant || 'secondary');
const badgeClasses = computed(() => statusConfig[props.status]?.classes || '');
</script>
