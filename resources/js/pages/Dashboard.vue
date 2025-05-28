<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAnimatedCounter } from '@/composables/useAnimatedCounter';
import { useRealTimeUpdates } from '@/composables/useRealTimeUpdates';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { BarChart3, Calendar, Eye, FileText, MessageSquare, PlusCircle, TrendingUp, Users } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted } from 'vue';

interface Props {
    userStats: {
        totalPosts: number;
        publishedPosts: number;
        draftPosts: number;
        totalViews: number;
        totalComments: number;
    };
    recentPosts: Array<{
        id: number;
        title: string;
        status: string;
        views_count: number;
        comments_count: number;
        created_at: string;
    }>;
    systemStats: {
        totalUsers: number;
        totalPosts: number;
        totalViews: number;
        postsThisMonth: number;
    };
}

const props = defineProps<Props>();
const page = usePage();

// Set up real-time updates and get reactive stats
const {
    connectToRealTime,
    isConnected,
    unsubscribeFromChannels,
    initializeStats,
    dashboardStats,
    userStats: realtimeUserStats,
    recentPosts: realtimeRecentPosts,
} = useRealTimeUpdates();

// Set up animated counters for user stats
const userTotalPostsCounter = computed(() => realtimeUserStats.value.totalPosts || 0);
const userPublishedPostsCounter = computed(() => realtimeUserStats.value.publishedPosts || 0);
const userDraftPostsCounter = computed(() => realtimeUserStats.value.draftPosts || 0);
const userTotalViewsCounter = computed(() => realtimeUserStats.value.totalViews || 0);
const userTotalCommentsCounter = computed(() => realtimeUserStats.value.totalComments || 0);

const { animatedValue: animatedUserTotalPosts } = useAnimatedCounter(userTotalPostsCounter);
const { animatedValue: animatedUserPublishedPosts } = useAnimatedCounter(userPublishedPostsCounter);
const { animatedValue: animatedUserDraftPosts } = useAnimatedCounter(userDraftPostsCounter);
const { animatedValue: animatedUserTotalViews } = useAnimatedCounter(userTotalViewsCounter);
const { animatedValue: animatedUserTotalComments } = useAnimatedCounter(userTotalCommentsCounter);

// Set up animated counters for system stats
const systemTotalUsersCounter = computed(() => dashboardStats.value.totalUsers || 0);
const systemTotalPostsCounter = computed(() => dashboardStats.value.totalPosts || 0);
const systemTotalViewsCounter = computed(() => dashboardStats.value.totalViews || 0);
const systemPostsThisMonthCounter = computed(() => dashboardStats.value.postsThisMonth || 0);

const { animatedValue: animatedSystemTotalUsers } = useAnimatedCounter(systemTotalUsersCounter);
const { animatedValue: animatedSystemTotalPosts } = useAnimatedCounter(systemTotalPostsCounter);
const { animatedValue: animatedSystemTotalViews } = useAnimatedCounter(systemTotalViewsCounter);
const { animatedValue: animatedSystemPostsThisMonth } = useAnimatedCounter(systemPostsThisMonthCounter);

onMounted(() => {
    // Initialize with props data
    initializeStats({
        dashboardStats: props.systemStats,
        userStats: props.userStats,
        recentPosts: props.recentPosts,
    });

    const userId = page.props.auth.user?.id;
    if (userId) {
        connectToRealTime(userId);
    }
});

onUnmounted(() => {
    unsubscribeFromChannels();
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Welcome Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Welcome back, {{ $page.props.auth.user.name }}!</h1>
                    <p class="text-gray-600 dark:text-gray-400">Here's what's happening with your content today.</p>
                    <div v-if="isConnected" class="mt-1 flex items-center gap-2 text-sm text-green-600">
                        <div class="h-2 w-2 rounded-full bg-green-500"></div>
                        Real-time updates active
                    </div>
                </div>
                <Link :href="route('posts.create')">
                    <Button class="flex items-center gap-2">
                        <PlusCircle class="h-4 w-4" />
                        Create Post
                    </Button>
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- User's Posts -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Your Posts</CardTitle>
                        <FileText class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ animatedUserTotalPosts }}</div>
                        <p class="text-muted-foreground text-xs">{{ animatedUserPublishedPosts }} published, {{ animatedUserDraftPosts }} drafts</p>
                    </CardContent>
                </Card>

                <!-- Total Views -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Views</CardTitle>
                        <Eye class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ animatedUserTotalViews.toLocaleString() }}</div>
                        <p class="text-muted-foreground text-xs">Across all your posts</p>
                    </CardContent>
                </Card>

                <!-- Comments -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Comments</CardTitle>
                        <MessageSquare class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ animatedUserTotalComments }}</div>
                        <p class="text-muted-foreground text-xs">On your posts</p>
                    </CardContent>
                </Card>

                <!-- System Stats -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Community</CardTitle>
                        <Users class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ animatedSystemTotalUsers }}</div>
                        <p class="text-muted-foreground text-xs">Total users</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Main Content Area -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Recent Posts -->
                <div class="lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle>Your Recent Posts</CardTitle>
                                    <CardDescription> Manage and track your latest content </CardDescription>
                                </div>
                                <Link :href="route('posts.my')">
                                    <Button variant="outline" size="sm">View All</Button>
                                </Link>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div
                                    v-for="post in realtimeRecentPosts"
                                    :key="post.id"
                                    class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                                >
                                    <div class="min-w-0 flex-1">
                                        <div class="mb-1 flex items-center gap-2">
                                            <Link
                                                :href="route('posts.show', post.id)"
                                                class="truncate font-medium text-gray-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-400"
                                            >
                                                {{ post.title }}
                                            </Link>
                                            <StatusBadge :status="post.status" />
                                        </div>
                                        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                            <span class="flex items-center gap-1">
                                                <Eye class="h-3 w-3" />
                                                {{ post.views_count }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <MessageSquare class="h-3 w-3" />
                                                {{ post.comments_count }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <Calendar class="h-3 w-3" />
                                                {{ new Date(post.created_at).toLocaleDateString() }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex items-center gap-2">
                                        <Link :href="route('posts.edit', post.id)">
                                            <Button variant="ghost" size="sm">Edit</Button>
                                        </Link>
                                    </div>
                                </div>

                                <div v-if="!realtimeRecentPosts || realtimeRecentPosts.length === 0" class="py-8 text-center">
                                    <FileText class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                    <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">No posts yet</h3>
                                    <p class="mb-4 text-gray-500 dark:text-gray-400">Get started by creating your first post.</p>
                                    <Link :href="route('posts.create')">
                                        <Button>
                                            <PlusCircle class="mr-2 h-4 w-4" />
                                            Create Your First Post
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Quick Actions & Stats -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                            <CardDescription> Common tasks and shortcuts </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <Link :href="route('posts.create')" class="block">
                                <Button variant="outline" class="w-full justify-start">
                                    <PlusCircle class="mr-2 h-4 w-4" />
                                    Create New Post
                                </Button>
                            </Link>
                            <Link :href="route('posts.my', { status: 'draft' })" class="block">
                                <Button variant="outline" class="w-full justify-start">
                                    <FileText class="mr-2 h-4 w-4" />
                                    View Drafts
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>

                    <!-- Platform Stats -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Platform Overview</CardTitle>
                            <CardDescription> Community activity and growth </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total Posts</span>
                                <span class="font-medium">{{ animatedSystemTotalPosts.toLocaleString() }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total Views</span>
                                <span class="font-medium">{{ animatedSystemTotalViews.toLocaleString() }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">This Month</span>
                                <span class="flex items-center gap-1 font-medium">
                                    <TrendingUp class="h-3 w-3 text-green-500" />
                                    {{ animatedSystemPostsThisMonth }}
                                </span>
                            </div>
                            <div class="border-t pt-2">
                                <Link :href="route('posts.index')" class="block">
                                    <Button variant="ghost" size="sm" class="w-full">
                                        <BarChart3 class="mr-2 h-4 w-4" />
                                        View All Posts
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
