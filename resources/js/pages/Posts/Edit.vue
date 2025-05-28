<template>
    <Head :title="`Edit ${postData?.title || 'Post'}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Post</h1>
                    <p class="text-gray-600 dark:text-gray-400">Update your post content and settings</p>
                </div>
                <div class="flex items-center gap-4">
                    <Link :href="route('posts.show', postData?.id)">
                        <Button variant="outline" class="flex items-center gap-2">
                            <Eye class="h-4 w-4" />
                            View Post
                        </Button>
                    </Link>
                    <Link :href="route('posts.index')">
                        <Button variant="outline" class="flex items-center gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Back to Posts
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Form Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Edit class="h-5 w-5" />
                        Post Details
                    </CardTitle>
                    <CardDescription> Update the information below to modify your post. </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitForm" class="space-y-6">
                        <!-- Title -->
                        <div class="space-y-2">
                            <Label for="title">Title *</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                type="text"
                                placeholder="Enter post title..."
                                :class="{ 'border-destructive': shouldShowError('title') }"
                                @input="clearFieldError('title')"
                            />
                            <p v-if="shouldShowError('title')" class="text-destructive text-sm">
                                {{ errors.title }}
                            </p>
                        </div>

                        <!-- Content -->
                        <div class="space-y-2">
                            <Label for="content">Content *</Label>
                            <Textarea
                                id="content"
                                v-model="form.content"
                                :rows="12"
                                placeholder="Write your post content..."
                                :class="{ 'border-destructive': shouldShowError('content') }"
                                @input="clearFieldError('content')"
                            />
                            <p v-if="shouldShowError('content')" class="text-destructive text-sm">
                                {{ errors.content }}
                            </p>
                        </div>

                        <!-- Category -->
                        <div class="space-y-2">
                            <Label for="category">Category</Label>
                            <div class="flex items-center gap-3">
                                <Select v-model="form.category" class="flex-1" @update:model-value="clearFieldError('category')">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a category..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">No Category</SelectItem>
                                        <SelectItem v-for="category in categories" :key="category" :value="category">
                                            {{ category }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>

                                <!-- Selected Category Badge -->
                                <Badge
                                    v-if="form.category && form.category !== 'custom' && form.category !== 'none'"
                                    variant="outline"
                                    class="flex items-center gap-1"
                                >
                                    <Tag class="h-3 w-3" />
                                    {{ form.category }}
                                </Badge>
                            </div>

                            <!-- Custom Category Input -->
                            <Input
                                v-if="form.category === 'custom'"
                                v-model="customCategory"
                                type="text"
                                placeholder="Enter custom category..."
                                @blur="handleCustomCategory"
                            />

                            <p v-if="shouldShowError('category')" class="text-destructive text-sm">
                                {{ errors.category }}
                            </p>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <Label for="status">Status *</Label>
                            <Select v-model="form.status" @update:model-value="clearFieldError('status')">
                                <SelectTrigger :class="{ 'border-destructive': shouldShowError('status') }">
                                    <SelectValue placeholder="Select status..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="published">Published</SelectItem>
                                    <SelectItem value="archived">Archived</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="shouldShowError('status')" class="text-destructive text-sm">
                                {{ errors.status }}
                            </p>
                        </div>

                        <!-- Publication Date (only if published) -->
                        <div v-if="form.status === 'published'" class="space-y-2">
                            <Label for="published_at">Publication Date</Label>
                            <Input
                                id="published_at"
                                v-model="form.published_at"
                                type="datetime-local"
                                :class="{ 'border-destructive': shouldShowError('published_at') }"
                                @input="clearFieldError('published_at')"
                            />
                            <p v-if="shouldShowError('published_at')" class="text-destructive text-sm">
                                {{ errors.published_at }}
                            </p>
                            <p class="text-muted-foreground text-sm">Leave empty to publish immediately</p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-between border-t pt-6">
                            <div class="flex items-center gap-4">
                                <Button type="submit" :disabled="isSubmitting" class="flex items-center gap-2">
                                    <Loader2 v-if="isSubmitting" class="h-4 w-4 animate-spin" />
                                    <Save v-else class="h-4 w-4" />
                                    {{ isSubmitting ? 'Updating...' : 'Update Post' }}
                                </Button>

                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="saveDraft"
                                    :disabled="isSubmitting"
                                    v-if="form.status !== 'draft'"
                                    class="flex items-center gap-2"
                                >
                                    <FileText class="h-4 w-4" />
                                    Save as Draft
                                </Button>
                            </div>

                            <Button
                                type="button"
                                variant="destructive"
                                @click="confirmDelete"
                                :disabled="isSubmitting || isDeleting"
                                class="flex items-center gap-2"
                            >
                                <Loader2 v-if="isDeleting" class="h-4 w-4 animate-spin" />
                                <Trash2 v-else class="h-4 w-4" />
                                {{ isDeleting ? 'Deleting...' : 'Delete Post' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>

    <!-- Delete Dialog -->
    <Dialog v-model:open="showDeleteDialog">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Delete Post</DialogTitle>
                <DialogDescription> Are you sure you want to delete "{{ postData?.title }}"? This action cannot be undone. </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="showDeleteDialog = false">Cancel</Button>
                <Button variant="destructive" @click="deletePost" :disabled="isDeleting">
                    <Loader2 v-if="isDeleting" class="mr-2 h-4 w-4 animate-spin" />
                    Delete Post
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useDeletePost, useUpdatePost } from '@/hooks/usePosts';
import AppLayout from '@/layouts/AppLayout.vue';
import { ApiError } from '@/services/api';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Edit, Eye, FileText, Loader2, Save, Tag, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Post {
    id: number;
    title: string;
    content: string;
    status: string;
    category: string;
    published_at: string;
    created_at: string;
    updated_at: string;
    etag: string;
    user: User;
}

interface Props {
    post: {
        data: Post;
    };
    categories: string[];
}

const props = defineProps<Props>();

// Extract the actual post data from the resource wrapper
const postData = computed(() => props.post?.data);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Posts',
        href: '/posts',
    },
    {
        title: postData.value?.title || 'Post',
        href: route('posts.show', postData.value?.id),
    },
    {
        title: 'Edit',
        href: route('posts.edit', postData.value?.id),
    },
];

// Form state
const form = reactive({
    title: '',
    content: '',
    category: '',
    status: 'draft' as 'draft' | 'published' | 'archived',
    published_at: '',
    etag: '',
});

const customCategory = ref('');
const hasAttemptedSubmit = ref(false);
const errors = ref<Record<string, string>>({});
const showDeleteDialog = ref(false);

// TanStack Query mutations
const updatePostMutation = useUpdatePost();
const deletePostMutation = useDeletePost();

// Computed states
const isSubmitting = computed(() => updatePostMutation.isPending.value);
const isDeleting = computed(() => deletePostMutation.isPending.value);

// Initialize form with post data
onMounted(() => {
    form.title = postData.value?.title || '';
    form.content = postData.value?.content || '';
    form.category = postData.value?.category || 'none';
    form.status = postData.value?.status as 'draft' | 'published' | 'archived';
    form.published_at = postData.value?.published_at || '';
    form.etag = postData.value?.etag || '';
});

// Methods
const handleCustomCategory = () => {
    if (customCategory.value.trim()) {
        form.category = customCategory.value.trim();
        customCategory.value = '';
    }
};

const clearFieldError = (field: string) => {
    if (errors.value[field]) {
        errors.value[field] = '';
    }
};

const shouldShowError = (field: string) => {
    return hasAttemptedSubmit.value && errors.value[field];
};

const saveDraft = () => {
    const originalStatus = form.status;
    form.status = 'draft';
    submitForm();
    form.status = originalStatus;
};

const submitForm = async () => {
    hasAttemptedSubmit.value = true;
    errors.value = {};

    try {
        const result = await updatePostMutation.mutateAsync({
            id: postData.value?.id || 0,
            data: {
                title: form.title,
                content: form.content,
                category: form.category === 'none' ? undefined : form.category || undefined,
                status: form.status,
                published_at: form.published_at || undefined,
                etag: form.etag,
            },
        });

        // Navigate to the updated post after successful update
        router.visit(`/posts/${result.id}`);
    } catch (error) {
        if (error instanceof ApiError && error.status === 422 && error.errors) {
            // Handle validation errors
            Object.entries(error.errors).forEach(([field, messages]) => {
                errors.value[field] = messages[0];
            });
        } else if (error instanceof ApiError && error.status === 409) {
            // Handle concurrency conflicts
            errors.value.etag = 'This post has been modified by another user. Please refresh and try again.';
        } else {
            // Handle other errors - toast will be shown by the mutation
            console.error('Failed to update post:', error);
        }
    }
};

const confirmDelete = () => {
    showDeleteDialog.value = true;
};

const deletePost = async () => {
    if (!postData.value?.id) return;

    try {
        await deletePostMutation.mutateAsync(postData.value.id);
        showDeleteDialog.value = false;

        // Navigate to posts list after successful deletion
        router.visit('/posts');
    } catch (error) {
        // Error toast will be shown by the mutation
        console.error('Failed to delete post:', error);
    }
};
</script>
