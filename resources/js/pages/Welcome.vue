<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAnimatedCounter } from '@/composables/useAnimatedCounter';
import { useRealTimeUpdates } from '@/composables/useRealTimeUpdates';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, Database, Shield, Sparkles, Zap } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted } from 'vue';

interface Props {
    recentPosts?: Array<{
        id: number;
        title: string;
        content: string;
        category: string;
        views_count: number;
        created_at: string;
        user: {
            name: string;
        };
    }>;
    stats?: {
        totalPosts: number;
        totalUsers: number;
        totalViews: number;
    };
}

const props = withDefaults(defineProps<Props>(), {
    recentPosts: () => [],
    stats: () => ({
        totalPosts: 0,
        totalUsers: 0,
        totalViews: 0,
    }),
});

// Set up real-time updates and get reactive stats
const {
    connectToRealTime,
    isConnected,
    unsubscribeFromChannels,
    initializeStats,
    welcomeStats,
    recentPosts: realtimeRecentPosts,
} = useRealTimeUpdates();

// Set up animated counters
const totalPostsCounter = computed(() => welcomeStats.value.totalPosts || 0);
const totalUsersCounter = computed(() => welcomeStats.value.totalUsers || 0);
const totalViewsCounter = computed(() => welcomeStats.value.totalViews || 0);

const { animatedValue: animatedTotalPosts } = useAnimatedCounter(totalPostsCounter);
const { animatedValue: animatedTotalUsers } = useAnimatedCounter(totalUsersCounter);
const { animatedValue: animatedTotalViews } = useAnimatedCounter(totalViewsCounter);

onMounted(() => {
    // Initialize with props data
    initializeStats({
        welcomeStats: props.stats,
        recentPosts: props.recentPosts,
    });

    // Connect to real-time updates (public channel for welcome page)
    connectToRealTime();
});

onUnmounted(() => {
    unsubscribeFromChannels();
});

const features = [
    {
        icon: BookOpen,
        title: 'Interactive Data Grid',
        description: 'Advanced filtering, sorting, and pagination with real-time state management',
    },
    {
        icon: Zap,
        title: 'Real-time Updates',
        description: 'Optimistic UI updates with Laravel Echo broadcasting and conflict resolution',
    },
    {
        icon: Shield,
        title: 'Secure & Robust',
        description: 'ETag-based concurrency control, comprehensive validation, and authorization',
    },
    {
        icon: Database,
        title: 'Optimized Performance',
        description: 'Strategic database indexing, caching layers, and efficient query patterns',
    },
];
</script>

<template>
    <Head title="Laravel Vue Inertia Challenge">
        <meta
            name="description"
            content="A modern full-stack application showcasing Laravel, Vue.js, and Inertia.js best practices with advanced data management and real-time features."
        />
    </Head>

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-slate-950 dark:via-slate-900 dark:to-slate-800">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 border-b border-slate-200 bg-white/80 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-purple-600">
                            <Sparkles class="h-5 w-5 text-white" />
                        </div>
                        <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-xl font-bold text-transparent">
                            Laravel Vue Challenge
                        </span>
                        <div v-if="isConnected" class="ml-2 flex items-center gap-1 text-xs text-green-600">
                            <div class="h-1.5 w-1.5 rounded-full bg-green-500"></div>
                            Live
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('posts.index')"
                            class="text-slate-600 transition-colors hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                        >
                            Browse Posts
                        </Link>

                        <template v-if="$page.props.auth.user">
                            <Link :href="route('dashboard')">
                                <Button variant="outline">Dashboard</Button>
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')">
                                <Button variant="ghost">Sign In</Button>
                            </Link>
                            <Link :href="route('register')">
                                <Button>Get Started</Button>
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative py-20 lg:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="mb-6 text-4xl font-bold text-slate-900 lg:text-6xl dark:text-white">
                        Modern Full-Stack
                        <span class="block bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"> Web Application </span>
                    </h1>
                    <p class="mx-auto mb-8 max-w-3xl text-xl text-slate-600 dark:text-slate-400">
                        Built with Laravel 12, Vue.js 3, and Inertia.js. Featuring advanced data management, real-time updates, and enterprise-level
                        architecture patterns.
                    </p>

                    <div class="flex flex-col justify-center gap-4 sm:flex-row">
                        <Link :href="route('posts.index')">
                            <Button size="lg" class="w-full sm:w-auto">
                                Explore Posts
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </Link>
                        <Link :href="route('register')" v-if="!$page.props.auth.user">
                            <Button variant="outline" size="lg" class="w-full sm:w-auto"> Join Community </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="bg-white/50 py-16 backdrop-blur-sm dark:bg-slate-900/50" v-if="welcomeStats && welcomeStats.totalPosts > 0">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ animatedTotalPosts.toLocaleString() }}</div>
                        <div class="text-slate-600 dark:text-slate-400">Published Posts</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ animatedTotalUsers.toLocaleString() }}</div>
                        <div class="text-slate-600 dark:text-slate-400">Active Users</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ animatedTotalViews.toLocaleString() }}</div>
                        <div class="text-slate-600 dark:text-slate-400">Total Views</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-16 text-center">
                    <h2 class="mb-4 text-3xl font-bold text-slate-900 lg:text-4xl dark:text-white">Enterprise-Grade Features</h2>
                    <p class="mx-auto max-w-2xl text-xl text-slate-600 dark:text-slate-400">
                        Showcasing modern web development patterns and best practices
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                    <Card v-for="feature in features" :key="feature.title" class="border-slate-200 dark:border-slate-800">
                        <CardHeader>
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-purple-600">
                                <component :is="feature.icon" class="h-6 w-6 text-white" />
                            </div>
                            <CardTitle class="text-slate-900 dark:text-white">{{ feature.title }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CardDescription class="text-slate-600 dark:text-slate-400">
                                {{ feature.description }}
                            </CardDescription>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Recent Posts Section -->
        <section
            class="bg-slate-50 py-20 dark:bg-slate-900"
            v-if="realtimeRecentPosts && Array.isArray(realtimeRecentPosts) && realtimeRecentPosts.length > 0"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-16 text-center">
                    <h2 class="mb-4 text-3xl font-bold text-slate-900 lg:text-4xl dark:text-white">Latest Posts</h2>
                    <p class="text-xl text-slate-600 dark:text-slate-400">Discover the latest content from our community</p>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="post in (realtimeRecentPosts || []).slice(0, 6)"
                        :key="post.id"
                        class="cursor-pointer border-slate-200 transition-shadow hover:shadow-lg dark:border-slate-800"
                    >
                        <CardHeader>
                            <div class="mb-2 flex items-center justify-between">
                                <Badge variant="secondary">{{ post.category }}</Badge>
                                <span class="text-sm text-slate-500">{{ post.views_count }} views</span>
                            </div>
                            <CardTitle class="line-clamp-2 text-slate-900 dark:text-white">
                                <Link :href="route('posts.show', post.id)" class="transition-colors hover:text-blue-600">
                                    {{ post.title }}
                                </Link>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CardDescription class="mb-4 line-clamp-3 text-slate-600 dark:text-slate-400">
                                {{ post.content?.substring(0, 150) }}{{ post.content?.length > 150 ? '...' : '' }}
                            </CardDescription>
                            <div class="flex items-center justify-between text-sm text-slate-500">
                                <span>by {{ post.user.name }}</span>
                                <span>{{ new Date(post.created_at).toLocaleDateString() }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="mt-12 text-center">
                    <Link :href="route('posts.index')">
                        <Button variant="outline" size="lg">
                            View All Posts
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl bg-gradient-to-r from-blue-600 to-purple-600 p-8 text-center lg:p-16">
                    <!-- Content for authenticated users -->
                    <template v-if="$page.props.auth.user">
                        <h2 class="mb-4 text-3xl font-bold text-white lg:text-4xl">Ready to Share Your Ideas?</h2>
                        <p class="mx-auto mb-8 max-w-2xl text-xl text-blue-100">
                            Welcome back, {{ $page.props.auth.user.name }}! Start creating amazing content and engage with our community.
                        </p>

                        <div class="flex flex-col justify-center gap-4 sm:flex-row">
                            <Link :href="route('posts.create')">
                                <Button size="lg" variant="secondary" class="w-full sm:w-auto">
                                    Create Your Next Post
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Button>
                            </Link>
                            <Link :href="route('dashboard')">
                                <Button
                                    size="lg"
                                    variant="outline"
                                    class="w-full border-white text-white hover:bg-white hover:text-blue-600 sm:w-auto"
                                >
                                    Go to Dashboard
                                </Button>
                            </Link>
                        </div>
                    </template>

                    <!-- Content for guest users -->
                    <template v-else>
                        <h2 class="mb-4 text-3xl font-bold text-white lg:text-4xl">Ready to Get Started?</h2>
                        <p class="mx-auto mb-8 max-w-2xl text-xl text-blue-100">
                            Join our community and start creating amazing content with our modern platform.
                        </p>

                        <div class="flex flex-col justify-center gap-4 sm:flex-row">
                            <Link :href="route('register')">
                                <Button size="lg" variant="secondary" class="w-full sm:w-auto">
                                    Sign Up Now
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Button>
                            </Link>
                            <Link :href="route('posts.index')">
                                <Button
                                    size="lg"
                                    variant="outline"
                                    class="w-full border-white text-white hover:bg-white hover:text-blue-600 sm:w-auto"
                                >
                                    Browse Posts
                                </Button>
                            </Link>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="text-center">
                    <div class="mb-4 flex items-center justify-center space-x-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-gradient-to-br from-blue-600 to-purple-600">
                            <Sparkles class="h-4 w-4 text-white" />
                        </div>
                        <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-lg font-semibold text-transparent">
                            Laravel Vue Challenge
                        </span>
                    </div>
                    <p class="mb-4 text-slate-600 dark:text-slate-400">Built with ❤️ using Laravel, Vue.js, and modern web technologies.</p>
                    <div class="flex justify-center space-x-6 text-sm text-slate-500">
                        <a href="https://laravel.com/docs" target="_blank" class="hover:text-slate-700 dark:hover:text-slate-300"> Laravel Docs </a>
                        <a href="https://vuejs.org/guide/" target="_blank" class="hover:text-slate-700 dark:hover:text-slate-300"> Vue.js Guide </a>
                        <a href="https://inertiajs.com/" target="_blank" class="hover:text-slate-700 dark:hover:text-slate-300"> Inertia.js </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
