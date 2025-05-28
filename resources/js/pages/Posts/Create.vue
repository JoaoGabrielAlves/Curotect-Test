<template>
    <Head title="Create Post" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create New Post</h1>
                    <p class="text-gray-600 dark:text-gray-400">Share your thoughts with the world</p>
                </div>
                <Link :href="route('posts.index')">
                    <Button variant="outline" class="flex items-center gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Posts
                    </Button>
                </Link>
            </div>

            <!-- Form Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="h-5 w-5" />
                        Post Details
                    </CardTitle>
                    <CardDescription> Fill in the information below to create your new post. </CardDescription>
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
                                    {{ isSubmitting ? 'Creating...' : 'Create Post' }}
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
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useCreatePost } from '@/hooks/usePosts';
import AppLayout from '@/layouts/AppLayout.vue';
import { ApiError } from '@/services/api';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, FileText, Loader2, Save, Tag } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

interface Props {
    categories: string[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Posts',
        href: '/posts',
    },
    {
        title: 'Create Post',
        href: '/posts/create',
    },
];

// Form state
const form = reactive({
    title: '',
    content: '',
    category: 'none',
    status: 'draft' as 'draft' | 'published' | 'archived',
    published_at: '',
});

const customCategory = ref('');
const hasAttemptedSubmit = ref(false);
const errors = ref<Record<string, string>>({});

// TanStack Query mutation
const createPostMutation = useCreatePost();

// Computed states
const isSubmitting = computed(() => createPostMutation.isPending.value);

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
        await createPostMutation.mutateAsync({
            title: form.title,
            content: form.content,
            category: form.category === 'none' ? undefined : form.category || undefined,
            status: form.status,
            published_at: form.published_at || undefined,
        });
    } catch (error) {
        if (error instanceof ApiError && error.status === 422 && error.errors) {
            // Handle validation errors
            Object.entries(error.errors).forEach(([field, messages]) => {
                errors.value[field] = messages[0];
            });
        } else {
            // Handle other errors
            console.error('Failed to create post:', error);
        }
    }
};
</script>
