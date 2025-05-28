<template>
    <div class="space-y-4">
        <!-- Filters Section -->
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
                <!-- Search Input -->
                <div class="max-w-sm flex-1">
                    <Input v-model="localFilters.search" placeholder="Search posts..." class="h-9" />
                </div>

                <!-- Category Filter -->
                <Select v-model="localFilters.category" @update:model-value="updateFilters">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="All Categories" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Categories</SelectItem>
                        <SelectItem v-for="category in categories" :key="category" :value="category">
                            {{ category }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- Status Filter -->
                <Select v-if="showStatusFilter" v-model="localFilters.status" @update:model-value="updateFilters">
                    <SelectTrigger class="w-[140px]">
                        <SelectValue placeholder="All Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Status</SelectItem>
                        <SelectItem value="published">Published</SelectItem>
                        <SelectItem value="draft">Draft</SelectItem>
                        <SelectItem value="archived">Archived</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Per Page Selector -->
            <div class="flex items-center gap-2">
                <span class="text-muted-foreground text-sm">Show:</span>
                <Select :model-value="String(localFilters.per_page)" @update:model-value="handlePerPageChange">
                    <SelectTrigger class="w-[80px]">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="5">5</SelectItem>
                        <SelectItem value="10">10</SelectItem>
                        <SelectItem value="15">15</SelectItem>
                        <SelectItem value="25">25</SelectItem>
                        <SelectItem value="50">50</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- Data Table -->
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>
                            <SortableHeader field="title" :current-sort="localFilters.sort" :direction="localFilters.direction" @sort="handleSort">
                                Title
                            </SortableHeader>
                        </TableHead>
                        <TableHead>
                            <SortableHeader
                                field="user_name"
                                :current-sort="localFilters.sort"
                                :direction="localFilters.direction"
                                @sort="handleSort"
                            >
                                Author
                            </SortableHeader>
                        </TableHead>
                        <TableHead>Category</TableHead>
                        <TableHead v-if="showStatusFilter">Status</TableHead>
                        <TableHead>
                            <SortableHeader
                                field="views_count"
                                :current-sort="localFilters.sort"
                                :direction="localFilters.direction"
                                @sort="handleSort"
                            >
                                Views
                            </SortableHeader>
                        </TableHead>
                        <TableHead>Comments</TableHead>
                        <TableHead>
                            <SortableHeader
                                field="created_at"
                                :current-sort="localFilters.sort"
                                :direction="localFilters.direction"
                                @sort="handleSort"
                            >
                                Created
                            </SortableHeader>
                        </TableHead>
                        <TableHead v-if="showActions">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="loading">
                        <TableCell :colspan="columnCount" class="py-8 text-center">
                            <div class="flex items-center justify-center">
                                <Icon name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                                Loading posts...
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableRow v-else-if="!posts?.data.length">
                        <TableCell :colspan="columnCount" class="text-muted-foreground py-8 text-center"> No posts found </TableCell>
                    </TableRow>
                    <TableRow v-else v-for="post in posts.data" :key="post.id" class="hover:bg-muted/50 cursor-pointer" @click="viewPost(post.id)">
                        <TableCell class="font-medium">
                            <div class="max-w-[300px] truncate" :title="post.title">
                                {{ post.title }}
                            </div>
                        </TableCell>
                        <TableCell>
                            <div class="flex items-center gap-2">
                                <div class="bg-primary/10 flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium">
                                    {{ post.user?.name?.charAt(0).toUpperCase() }}
                                </div>
                                {{ post.user?.name }}
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge v-if="post.category" variant="secondary">
                                {{ post.category }}
                            </Badge>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                        <TableCell v-if="showStatusFilter">
                            <StatusBadge :status="post.status" />
                        </TableCell>
                        <TableCell>
                            <div class="flex items-center gap-1">
                                <Icon name="eye" class="text-muted-foreground h-3 w-3" />
                                {{ post.views_count.toLocaleString() }}
                            </div>
                        </TableCell>
                        <TableCell>
                            <div class="flex items-center gap-1">
                                <Icon name="message-circle" class="text-muted-foreground h-3 w-3" />
                                {{ post.comments_count || 0 }}
                            </div>
                        </TableCell>
                        <TableCell>
                            <time :datetime="post.created_at" class="text-muted-foreground text-sm">
                                {{ formatDate(post.created_at) }}
                            </time>
                        </TableCell>
                        <TableCell v-if="showActions">
                            <PostActions :post="post" />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div v-if="posts && posts.meta.last_page > 1" class="flex items-center justify-between">
            <div class="text-muted-foreground text-sm">Showing {{ posts.meta.from }} to {{ posts.meta.to }} of {{ posts.meta.total }} results</div>
            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" :disabled="posts.meta.current_page === 1" @click="goToPage(posts.meta.current_page - 1)">
                    <Icon name="chevron-left" class="h-4 w-4" />
                    Previous
                </Button>

                <div class="flex items-center gap-1">
                    <Button
                        v-for="link in paginationLinks"
                        :key="link.label"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        :disabled="!link.url"
                        @click="goToPage(parseInt(link.label))"
                        class="min-w-[40px]"
                    >
                        {{ link.label }}
                    </Button>
                </div>

                <Button
                    variant="outline"
                    size="sm"
                    :disabled="posts.meta.current_page === posts.meta.last_page"
                    @click="goToPage(posts.meta.current_page + 1)"
                >
                    Next
                    <Icon name="chevron-right" class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { usePostsStore } from '@/stores/posts';
import type { PaginatedPosts, PostFilters } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, onMounted, ref, watch } from 'vue';

// UI Components
import Icon from '@/components/Icon.vue';
import PostActions from '@/components/PostActions.vue';
import SortableHeader from '@/components/SortableHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

interface Props {
    posts: PaginatedPosts;
    categories: string[];
    filters: PostFilters;
    showActions?: boolean;
    showStatusFilter?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showActions: true,
    showStatusFilter: true,
});

// Store
const postsStore = usePostsStore();
const page = usePage();

// Get current route path without query parameters
const currentPath = computed(() => {
    try {
        return new URL(page.url, window.location.origin).pathname;
    } catch (error) {
        console.warn('Failed to parse current URL:', error);
        return '/posts'; // fallback
    }
});

// Helper function to normalize filter values for UI
const normalizeFilters = (filters: PostFilters): PostFilters => {
    return {
        ...filters,
        category: filters.category || 'all',
        status: filters.status || 'all',
    };
};

// Local state - use ref for better performance with large filter objects
const localFilters = ref<PostFilters>(normalizeFilters(props.filters));
const loading = ref(false);

// Initialize store with props data
onMounted(() => {
    postsStore.setPosts(props.posts);
    postsStore.setCategories(props.categories);
    postsStore.setFilters(props.filters);
});

// Computed
const paginationLinks = computed(() => {
    if (!props.posts?.meta?.links) return [];

    return props.posts.meta.links
        .filter((link) => link.label !== '&laquo; Previous' && link.label !== 'Next &raquo;')
        .filter((link) => !isNaN(parseInt(link.label)) || link.label === '...');
});

const columnCount = computed(() => {
    // Base columns: Title, Author, Category, Status, Views, Comments, Created
    let cols = 6;
    // Add Actions column if shown
    if (props.showActions) cols++;
    if (props.showStatusFilter) cols++;

    return cols;
});

// Methods
const debouncedSearch = useDebounceFn(() => {
    updateFilters();
}, 300);

const updateFilters = () => {
    const params = new URLSearchParams();

    // Always include search parameter, even if empty
    params.append('search', localFilters.value.search || '');

    // Handle category filter - convert "all" to empty string for backend
    if (localFilters.value.category && localFilters.value.category !== 'all') {
        params.append('category', localFilters.value.category);
    }

    // Handle status filter - convert "all" to empty string for backend
    if (localFilters.value.status && localFilters.value.status !== 'all') {
        params.append('status', localFilters.value.status);
    }

    if (localFilters.value.sort) params.append('sort', localFilters.value.sort);
    if (localFilters.value.direction) params.append('direction', localFilters.value.direction);
    if (localFilters.value.per_page) params.append('per_page', localFilters.value.per_page.toString());

    router.get(currentPath.value, Object.fromEntries(params), {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleSort = (field: string) => {
    const newDirection = localFilters.value.sort === field && localFilters.value.direction === 'asc' ? 'desc' : 'asc';

    localFilters.value.sort = field;
    localFilters.value.direction = newDirection;
    updateFilters();
};

const handlePerPageChange = (value: string) => {
    localFilters.value.per_page = parseInt(value);
    updateFilters();
};

const goToPage = (page: number) => {
    if (page < 1 || page > props.posts.meta.last_page) return;

    const params = new URLSearchParams();

    // Handle each filter properly
    if (localFilters.value.search) {
        params.append('search', localFilters.value.search);
    }

    if (localFilters.value.category && localFilters.value.category !== 'all') {
        params.append('category', localFilters.value.category);
    }

    if (localFilters.value.status && localFilters.value.status !== 'all') {
        params.append('status', localFilters.value.status);
    }

    if (localFilters.value.sort) {
        params.append('sort', localFilters.value.sort);
    }

    if (localFilters.value.direction) {
        params.append('direction', localFilters.value.direction);
    }

    if (localFilters.value.per_page) {
        params.append('per_page', localFilters.value.per_page.toString());
    }

    params.set('page', String(page));

    router.get(currentPath.value, Object.fromEntries(params), {
        preserveState: true,
        preserveScroll: true,
    });
};

const viewPost = (postId: number) => {
    router.get(`/posts/${postId}`);
};

// Memoized date formatting to avoid repeated date operations
const dateFormatCache = new Map<string, string>();

const formatDate = (dateString: string) => {
    if (dateFormatCache.has(dateString)) {
        return dateFormatCache.get(dateString)!;
    }

    const formatted = new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

    dateFormatCache.set(dateString, formatted);
    return formatted;
};

// Watch for search changes
watch(
    () => localFilters.value.search,
    () => {
        debouncedSearch();
    },
);

// Watch for prop changes
watch(
    () => props.filters,
    (newFilters) => {
        localFilters.value = normalizeFilters(newFilters);
    },
    { deep: true },
);

watch(
    () => props.posts,
    (newPosts) => {
        if (newPosts) {
            postsStore.setPosts(newPosts);
            postsStore.setCategories(props.categories);
            postsStore.setFilters(props.filters);
        }
    },
    { deep: true },
);
</script>
