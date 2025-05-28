<template>
    <Head title="My Posts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Posts</h1>
                    <p class="text-gray-600 dark:text-gray-400">Manage and organize your content</p>
                </div>
                <Button @click="createPost" class="flex items-center gap-2">
                    <Plus class="h-4 w-4" />
                    Create Post
                </Button>
            </div>

            <!-- Data Grid -->
            <PostsDataGrid
                :posts="posts"
                :categories="categories"
                :filters="filters"
                :show-actions="showActions"
                :show-status-filter="showStatusFilter"
            />
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import PostsDataGrid from '@/components/PostsDataGrid.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PaginatedPosts, PostFilters } from '@/stores/posts';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

interface Props {
    posts: PaginatedPosts;
    categories: string[];
    filters: PostFilters;
    showActions: boolean;
    showStatusFilter: boolean;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'My Posts',
        href: '/posts/my',
    },
];

const createPost = () => {
    router.get('/posts/create');
};
</script>
