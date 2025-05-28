import type { PaginatedPosts, PostFilters } from '@/types';
import { router } from '@inertiajs/vue3';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const usePostsStore = defineStore('posts', () => {
    const posts = ref<PaginatedPosts | null>(null);
    const categories = ref<string[]>([]);
    const loading = ref(false);

    const filters = ref<PostFilters>({
        search: '',
        category: '',
        status: '',
        sort: 'created_at',
        direction: 'desc',
        per_page: 10,
    });

    const hasData = computed(() => posts.value && posts.value.data.length > 0);
    const totalPosts = computed(() => posts.value?.meta.total || 0);
    const currentPage = computed(() => posts.value?.meta.current_page || 1);

    const fetchPosts = async (newFilters?: Partial<PostFilters>) => {
        loading.value = true;

        if (newFilters) {
            Object.assign(filters.value, newFilters);
        }

        const params = new URLSearchParams();
        if (filters.value.search) params.append('search', filters.value.search);
        if (filters.value.category) params.append('category', filters.value.category);
        if (filters.value.status) params.append('status', filters.value.status);
        if (filters.value.sort) params.append('sort', filters.value.sort);
        if (filters.value.direction) params.append('direction', filters.value.direction);
        if (filters.value.per_page) params.append('per_page', filters.value.per_page.toString());

        router.reload({
            data: Object.fromEntries(params),
            only: ['posts', 'filters'],
        });

        loading.value = false;
    };

    const sortBy = (column: string) => {
        const newDirection = filters.value.sort === column && filters.value.direction === 'asc' ? 'desc' : 'asc';
        Object.assign(filters.value, { sort: column, direction: newDirection });
        fetchPosts();
    };

    const goToPage = (page: number) => {
        const params = new URLSearchParams();
        if (filters.value.search) params.append('search', filters.value.search);
        if (filters.value.category) params.append('category', filters.value.category);
        if (filters.value.status) params.append('status', filters.value.status);
        if (filters.value.sort) params.append('sort', filters.value.sort);
        if (filters.value.direction) params.append('direction', filters.value.direction);
        if (filters.value.per_page) params.append('per_page', filters.value.per_page.toString());
        params.append('page', page.toString());

        router.reload({
            data: Object.fromEntries(params),
            only: ['posts'],
        });
    };

    return {
        posts,
        categories,
        loading,
        filters,
        hasData,
        totalPosts,
        currentPage,
        fetchPosts,
        sortBy,
        goToPage,
        setPosts: (newPosts: PaginatedPosts) => {
            posts.value = newPosts;
        },
        setCategories: (newCategories: string[]) => {
            categories.value = newCategories;
        },
        setFilters: (newFilters: PostFilters) => {
            filters.value = newFilters;
        },
    };
});
