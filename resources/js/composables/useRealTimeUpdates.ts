import type { DashboardStats, InitializeStatsData, PostDeletedEventData, PostEventData, RecentPost, UserStats, WelcomeStats } from '@/types';
import { useQueryClient } from '@tanstack/vue-query';
import { onMounted, onUnmounted, ref } from 'vue';

export function useRealTimeUpdates() {
    const queryClient = useQueryClient();
    const isConnected = ref(false);
    const channels = ref<string[]>([]);
    let updateTimeout: ReturnType<typeof setTimeout> | null = null;

    const welcomeStats = ref<WelcomeStats>({
        totalPosts: 0,
        totalUsers: 0,
        totalViews: 0,
    });

    const dashboardStats = ref<DashboardStats>({
        totalUsers: 0,
        totalPosts: 0,
        totalViews: 0,
        postsThisMonth: 0,
    });

    const userStats = ref<UserStats>({
        totalPosts: 0,
        publishedPosts: 0,
        draftPosts: 0,
        totalViews: 0,
        totalComments: 0,
    });

    const recentPosts = ref<RecentPost[]>([]);

    const initializeStats = (data: InitializeStatsData) => {
        if (data.welcomeStats) {
            welcomeStats.value = { ...data.welcomeStats };
        }
        if (data.dashboardStats) {
            dashboardStats.value = { ...data.dashboardStats };
        }
        if (data.userStats) {
            userStats.value = { ...data.userStats };
        }
        if (data.recentPosts) {
            recentPosts.value = [...data.recentPosts];
        }
    };

    const updateStats = (type: 'post' | 'view') => {
        if (type === 'post') {
            welcomeStats.value.totalPosts += 1;
            dashboardStats.value.totalPosts += 1;
        } else if (type === 'view') {
            welcomeStats.value.totalViews += 1;
            dashboardStats.value.totalViews += 1;
        }
    };

    const updateUserStats = (type: 'post' | 'view' | 'comment') => {
        if (type === 'post') {
            userStats.value.totalPosts += 1;
            userStats.value.publishedPosts += 1;
        } else if (type === 'view') {
            userStats.value.totalViews += 1;
        } else if (type === 'comment') {
            userStats.value.totalComments += 1;
        }
    };

    const updateStatsOnDelete = () => {
        welcomeStats.value.totalPosts -= 1;
        dashboardStats.value.totalPosts -= 1;
    };

    const updateUserStatsOnDelete = () => {
        userStats.value.totalPosts -= 1;
        userStats.value.publishedPosts -= 1;
    };

    // Debounced user stats update to prevent animation jumping
    const updateUserStatsDebounced = (changes: { published?: number; draft?: number }) => {
        if (updateTimeout) {
            clearTimeout(updateTimeout);
        }

        updateTimeout = setTimeout(() => {
            if (changes.published) {
                userStats.value.publishedPosts += changes.published;
            }
            if (changes.draft) {
                userStats.value.draftPosts += changes.draft;
            }
        }, 50); // Small delay to batch updates
    };

    const subscribeToChannel = (channelName: string) => {
        if (!window.Echo || channels.value.includes(channelName)) return null;

        const channel = window.Echo.channel(channelName);

        channel
            .listen('.post.created', (data: PostEventData) => {
                updateStats('post');
                if (data.status === 'published' && recentPosts.value.length > 0) {
                    recentPosts.value.unshift(data);
                    recentPosts.value = recentPosts.value.slice(0, 10);
                }
                queryClient.invalidateQueries({ queryKey: ['posts'] });
            })
            .listen('.post.viewed', () => {
                updateStats('view');
            })
            .listen('.post.updated', (data: PostEventData) => {
                queryClient.invalidateQueries({ queryKey: ['posts'] });
                const index = recentPosts.value.findIndex((post) => post.id === data.id);
                if (index !== -1) {
                    recentPosts.value[index] = { ...recentPosts.value[index], ...data };
                }

                if (data.changes?.status) {
                    const { old: oldStatus, new: newStatus } = data.changes.status;
                    if (oldStatus !== 'published' && newStatus === 'published') {
                        updateStats('post');
                    } else if (oldStatus === 'published' && newStatus !== 'published') {
                        updateStatsOnDelete();
                    }
                }
            })
            .listen('.post.deleted', (data: PostDeletedEventData) => {
                queryClient.invalidateQueries({ queryKey: ['posts'] });
                recentPosts.value = recentPosts.value.filter((post) => post.id !== data.id);

                if (data.status === 'published') {
                    updateStatsOnDelete();
                }
            })
            .listen('.comment.created', () => {
                queryClient.invalidateQueries({ queryKey: ['posts'] });
            });

        channels.value.push(channelName);
        return channel;
    };

    const subscribeToUserChannel = (userId: number) => {
        if (!window.Echo) return null;

        const channelName = `user.${userId}`;
        if (channels.value.includes(channelName)) return null;

        const channel = window.Echo.private(channelName);

        channel
            .listen('.post.created', (data: PostEventData) => {
                updateUserStats('post');
                if (data.status === 'published' && recentPosts.value.length > 0) {
                    recentPosts.value.unshift(data);
                    recentPosts.value = recentPosts.value.slice(0, 10);
                }
                queryClient.invalidateQueries({ queryKey: ['user-posts'] });
            })
            .listen('.post.viewed', () => {
                updateUserStats('view');
            })
            .listen('.post.updated', (data: PostEventData) => {
                queryClient.invalidateQueries({ queryKey: ['user-posts'] });
                const index = recentPosts.value.findIndex((post) => post.id === data.id);
                if (index !== -1) {
                    recentPosts.value[index] = { ...recentPosts.value[index], ...data };
                }

                if (data.changes?.status) {
                    const { old: oldStatus, new: newStatus } = data.changes.status;

                    // Use debounced updates to prevent animation jumping
                    const changes: { published?: number; draft?: number } = {};

                    if (oldStatus === 'draft' && newStatus === 'published') {
                        changes.published = 1;
                        changes.draft = -1;
                    } else if (oldStatus === 'published' && newStatus === 'draft') {
                        changes.published = -1;
                        changes.draft = 1;
                    } else if (oldStatus === 'published' && newStatus === 'archived') {
                        changes.published = -1;
                    } else if (oldStatus === 'draft' && newStatus === 'archived') {
                        changes.draft = -1;
                    } else if (oldStatus === 'archived' && newStatus === 'published') {
                        changes.published = 1;
                    } else if (oldStatus === 'archived' && newStatus === 'draft') {
                        changes.draft = 1;
                    }

                    updateUserStatsDebounced(changes);
                }
            })
            .listen('.post.deleted', (data: PostDeletedEventData) => {
                queryClient.invalidateQueries({ queryKey: ['user-posts'] });
                recentPosts.value = recentPosts.value.filter((post) => post.id !== data.id);

                if (data.user_id === userId) {
                    updateUserStatsOnDelete();
                }
            })
            .listen('.comment.created', () => {
                updateUserStats('comment');
                queryClient.invalidateQueries({ queryKey: ['user-posts'] });
            });

        channels.value.push(channelName);
        return channel;
    };

    const connectToRealTime = (userId?: number) => {
        if (!window.Echo) {
            console.warn('Echo not available');
            return;
        }

        subscribeToChannel('posts');

        if (userId) {
            subscribeToUserChannel(userId);
        }

        isConnected.value = true;
    };

    const unsubscribeFromChannels = () => {
        if (!window.Echo) return;

        channels.value.forEach((channelName) => {
            if (channelName.startsWith('user.')) {
                window.Echo.leave(channelName);
            } else {
                window.Echo.leave(channelName);
            }
        });
        channels.value = [];
        isConnected.value = false;
    };

    onMounted(() => {
        if (window.Echo) {
            isConnected.value = true;
        }
    });

    onUnmounted(() => {
        unsubscribeFromChannels();
    });

    return {
        isConnected,
        welcomeStats,
        dashboardStats,
        userStats,
        recentPosts,
        connectToRealTime,
        unsubscribeFromChannels,
        initializeStats,
    };
}
