import { ApiError, postsApi, type CreatePostData, type Post, type UpdatePostData } from '@/services/api';
import { router } from '@inertiajs/vue3';
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query';

// Query keys for cache management
export const postKeys = {
    all: ['posts'] as const,
    lists: () => [...postKeys.all, 'list'] as const,
    list: (filters: Record<string, any>) => [...postKeys.lists(), { filters }] as const,
    details: () => [...postKeys.all, 'detail'] as const,
    detail: (id: number) => [...postKeys.details(), id] as const,
};

// Get all posts
export const usePostsQuery = () => {
    return useQuery({
        queryKey: postKeys.lists(),
        queryFn: postsApi.getPosts,
        staleTime: 5 * 60 * 1000, // 5 minutes
    });
};

// Get single post
export const usePostQuery = (id: number) => {
    return useQuery({
        queryKey: postKeys.detail(id),
        queryFn: () => postsApi.getPost(id),
        staleTime: 5 * 60 * 1000,
    });
};

// Create post with optimistic updates
export const useCreatePost = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: postsApi.createPost,
        onMutate: async (newPost: CreatePostData) => {
            // Cancel outgoing refetches
            await queryClient.cancelQueries({ queryKey: postKeys.lists() });

            // Snapshot previous value
            const previousPosts = queryClient.getQueryData<Post[]>(postKeys.lists());

            // Optimistically update cache
            if (previousPosts) {
                const tempPost: Post = {
                    id: Date.now(), // Temporary ID
                    ...newPost,
                    created_at: new Date().toISOString(),
                    updated_at: new Date().toISOString(),
                    etag: 'temp',
                    user: {
                        id: 1, // Will be replaced with actual user
                        name: 'You',
                        email: '',
                    },
                };

                queryClient.setQueryData<Post[]>(postKeys.lists(), [...previousPosts, tempPost]);
            }

            return { previousPosts };
        },
        onError: (error: ApiError, newPost, context) => {
            // Rollback on error
            if (context?.previousPosts) {
                queryClient.setQueryData(postKeys.lists(), context.previousPosts);
            }

            // Handle validation errors
            if (error.status === 422 && error.errors) {
                // Let the form handle validation errors
                throw error;
            }
        },
        onSuccess: (data: Post) => {
            // Update cache with real data
            queryClient.setQueryData<Post[]>(postKeys.lists(), (old) => {
                if (!old) return [data];

                // Replace temp post with real post
                return old.map((post) => (post.id > Date.now() - 1000 ? data : post));
            });

            // Navigate to the new post
            router.visit(`/posts/${data.id}`);
        },
        onSettled: () => {
            // Refetch to ensure consistency
            queryClient.invalidateQueries({ queryKey: postKeys.lists() });
        },
    });
};

// Update post with optimistic updates
export const useUpdatePost = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ id, data }: { id: number; data: UpdatePostData }) => postsApi.updatePost(id, data),
        onMutate: async ({ id, data }) => {
            // Cancel outgoing refetches
            await queryClient.cancelQueries({ queryKey: postKeys.detail(id) });
            await queryClient.cancelQueries({ queryKey: postKeys.lists() });

            // Snapshot previous values
            const previousPost = queryClient.getQueryData<Post>(postKeys.detail(id));
            const previousPosts = queryClient.getQueryData<Post[]>(postKeys.lists());

            // Optimistically update cache
            if (previousPost) {
                const optimisticPost = { ...previousPost, ...data };
                queryClient.setQueryData(postKeys.detail(id), optimisticPost);
            }

            if (previousPosts) {
                queryClient.setQueryData<Post[]>(postKeys.lists(), (old) => {
                    if (!old) return old;
                    return old.map((post) => (post.id === id ? { ...post, ...data } : post));
                });
            }

            return { previousPost, previousPosts };
        },
        onError: (error: ApiError, { id }, context) => {
            // Rollback on error
            if (context?.previousPost) {
                queryClient.setQueryData(postKeys.detail(id), context.previousPost);
            }
            if (context?.previousPosts) {
                queryClient.setQueryData(postKeys.lists(), context.previousPosts);
            }

            // Handle specific errors
            if (error.status === 409) {
                // Concurrency conflict - refresh data
                queryClient.invalidateQueries({ queryKey: postKeys.detail(id) });
            } else if (error.status === 422 && error.errors) {
                // Validation errors - let component handle these
                throw error;
            }
        },
        onSuccess: (data: Post, { id }) => {
            // Update cache with real data
            queryClient.setQueryData(postKeys.detail(id), data);
            queryClient.setQueryData<Post[]>(postKeys.lists(), (old) => {
                if (!old) return [data];
                return old.map((post) => (post.id === id ? data : post));
            });

            // Show success notification
            // Don't navigate automatically - let component handle it
        },
        onSettled: (data, error, { id }) => {
            // Refetch to ensure consistency
            queryClient.invalidateQueries({ queryKey: postKeys.detail(id) });
            queryClient.invalidateQueries({ queryKey: postKeys.lists() });
        },
    });
};

// Delete post with optimistic updates
export const useDeletePost = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: postsApi.deletePost,
        onMutate: async (id: number) => {
            // Cancel outgoing refetches
            await queryClient.cancelQueries({ queryKey: postKeys.lists() });

            // Snapshot previous value
            const previousPosts = queryClient.getQueryData<Post[]>(postKeys.lists());

            // Optimistically remove from cache
            if (previousPosts) {
                queryClient.setQueryData<Post[]>(
                    postKeys.lists(),
                    previousPosts.filter((post) => post.id !== id),
                );
            }

            return { previousPosts };
        },
        onError: (error: ApiError, id, context) => {
            // Rollback on error
            if (context?.previousPosts) {
                queryClient.setQueryData(postKeys.lists(), context.previousPosts);
            }
        },
        onSuccess: (data, id) => {
            // Remove from detail cache
            queryClient.removeQueries({ queryKey: postKeys.detail(id) });

            // Show success notification

            // Don't navigate automatically - let component handle it
        },
        onSettled: () => {
            // Refetch to ensure consistency
            queryClient.invalidateQueries({ queryKey: postKeys.lists() });
        },
    });
};
