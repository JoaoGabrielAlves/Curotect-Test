<template>
    <Head :title="post.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div v-if="!post" class="flex h-full flex-1 items-center justify-center">
            <div class="text-center">
                <h2 class="text-xl font-semibold">Post not found</h2>
                <p class="text-muted-foreground">The post you're looking for doesn't exist.</p>
            </div>
        </div>

        <div v-else class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ post.title }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">Post details and comments</p>
                </div>
                <div class="flex items-center gap-4">
                    <Link v-if="$page.props.auth.user?.id === post.user?.id" :href="route('posts.edit', post.id)">
                        <Button variant="outline" class="flex items-center gap-2">
                            <Edit class="h-4 w-4" />
                            Edit Post
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

            <!-- Post Content -->
            <Card>
                <CardHeader>
                    <div class="flex items-start justify-between">
                        <div class="space-y-2">
                            <CardTitle class="text-xl">{{ post.title }}</CardTitle>
                            <div class="text-muted-foreground flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-1">
                                    <UserIcon class="h-4 w-4" />
                                    <span>{{ post.user?.name || 'Unknown' }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <Calendar class="h-4 w-4" />
                                    <span>{{ formatDate(post.created_at) }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <Eye class="h-4 w-4" />
                                    <span>{{ post.views_count || 0 }} views</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <StatusBadge :status="post.status" />
                            <Badge v-if="post.category" variant="outline" class="flex items-center gap-1">
                                <Tag class="h-3 w-3" />
                                {{ post.category }}
                            </Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="prose prose-gray dark:prose-invert max-w-none">
                        <p class="leading-relaxed whitespace-pre-wrap">{{ post.content }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Add Comment Form (only for authenticated users) -->
            <CommentForm v-if="$page.props.auth.user" :post-id="post.id" />

            <!-- Comments Section -->
            <Card id="comments-section">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <MessageSquare class="h-5 w-5" />
                        Comments ({{ post.comments?.length || 0 }})
                    </CardTitle>
                    <CardDescription> Join the conversation and share your thoughts </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="!post.comments || post.comments.length === 0" class="py-8 text-center">
                        <MessageSquare class="text-muted-foreground mx-auto mb-4 h-12 w-12" />
                        <h3 class="mb-2 text-lg font-medium">No comments yet</h3>
                        <p class="text-muted-foreground">Be the first to comment on this post!</p>
                    </div>

                    <div v-else class="space-y-6">
                        <div v-for="comment in post.comments" :key="comment.id" class="space-y-4">
                            <!-- Main Comment -->
                            <div
                                :id="`comment-${comment.id}`"
                                class="rounded-lg border p-4"
                                :class="{ 'ring-opacity-50 ring-2 ring-blue-500': isNewComment(comment.id) }"
                            >
                                <div class="mb-3 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold">{{ comment.user?.name || 'Unknown' }}</span>
                                        <StatusBadge :status="comment.status" />
                                    </div>
                                    <span class="text-muted-foreground text-sm">{{ formatDate(comment.created_at) }}</span>
                                </div>
                                <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ comment.content }}</p>
                            </div>

                            <!-- Replies -->
                            <div v-if="comment.replies && comment.replies.length > 0" class="ml-6 space-y-3">
                                <div
                                    v-for="reply in comment.replies"
                                    :key="reply.id"
                                    :id="`comment-${reply.id}`"
                                    class="rounded-lg border border-dashed p-3"
                                    :class="{ 'ring-opacity-50 ring-2 ring-blue-500': isNewComment(reply.id) }"
                                >
                                    <div class="mb-2 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold">{{ reply.user?.name || 'Unknown' }}</span>
                                            <StatusBadge :status="reply.status" />
                                        </div>
                                        <span class="text-muted-foreground text-xs">{{ formatDate(reply.created_at) }}</span>
                                    </div>
                                    <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ reply.content }}</p>
                                </div>
                            </div>

                            <Separator v-if="comment !== post.comments[post.comments.length - 1]" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import CommentForm from '@/components/CommentForm.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Post, type User } from '@/stores/posts';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Edit, Eye, MessageSquare, Tag, User as UserIcon } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

interface Comment {
    id: number;
    content: string;
    status: string;
    created_at: string;
    user: User;
    replies?: Comment[];
}

interface PostWithComments extends Post {
    comments: Comment[];
    etag?: string;
    can_edit?: boolean;
    can_delete?: boolean;
    reading_time?: number;
    is_owner?: boolean;
    excerpt?: string;
}

interface Props {
    post: {
        data: PostWithComments;
    };
}

const props = defineProps<Props>();
const page = usePage();

// Extract the actual post data from the resource wrapper
const post = computed(() => props.post?.data);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Posts',
        href: '/posts',
    },
    {
        title: post.value?.title || 'Post',
        href: `/posts/${post.value?.id || ''}`,
    },
];

const newCommentId = computed(() => page.props.flash?.new_comment_id);

const isNewComment = (commentId: number) => {
    return newCommentId.value && newCommentId.value === commentId;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Scroll to new comment when page loads
onMounted(() => {
    if (newCommentId.value) {
        setTimeout(() => {
            const commentElement = document.getElementById(`comment-${newCommentId.value}`);
            if (commentElement) {
                commentElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });

                // Remove the highlight after a few seconds
                setTimeout(() => {
                    commentElement.classList.remove('ring-2', 'ring-blue-500', 'ring-opacity-50');
                }, 3000);
            }
        }, 100);
    }
});
</script>
