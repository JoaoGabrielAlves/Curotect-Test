<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    // Cache duration constants (in minutes)
    private const POST_CACHE_DURATION = 60;        // 1 hour

    private const CATEGORIES_CACHE_DURATION = 240; // 4 hours

    private const DASHBOARD_CACHE_DURATION = 30;   // 30 minutes

    private const TRENDING_CACHE_DURATION = 60;    // 1 hour

    private const COMMENT_CACHE_DURATION = 60;     // 1 hour

    private const WELCOME_CACHE_DURATION = 60;     // 1 hour

    private const SYSTEM_STATS_CACHE_DURATION = 120; // 2 hours

    /**
     * Clear all the main caches we use.
     */
    public function clearAllCaches(): void
    {
        Cache::forget('posts.categories');
        Cache::forget('posts.trending');
    }

    /**
     * Clear cache for a specific post.
     */
    public function clearPostCache(int $postId): void
    {
        Cache::forget("post.{$postId}");
    }

    /**
     * Clear comment caches for a specific post.
     */
    public function clearPostCommentCaches(int $postId): void
    {
        Cache::forget("comments.post.{$postId}");
        Cache::forget("comments.toplevel.post.{$postId}");
    }

    /**
     * Clear dashboard cache for a user.
     */
    public function clearUserDashboardCache(int $userId): void
    {
        Cache::forget("dashboard.user.{$userId}");
    }

    /**
     * Clear the categories cache.
     */
    public function clearCategoriesCache(): void
    {
        Cache::forget('posts.categories');
    }

    /**
     * Clear the trending posts cache.
     */
    public function clearTrendingCache(): void
    {
        Cache::forget('posts.trending');
    }

    /**
     * Get a post from cache or load it fresh.
     */
    public function getOrCachePost(int $postId, callable $callback)
    {
        return Cache::remember(
            "post.{$postId}",
            self::POST_CACHE_DURATION,
            $callback
        );
    }

    /**
     * Get comments for a post from cache or load them fresh.
     */
    public function getOrCachePostComments(int $postId, array $filters, callable $callback)
    {
        $cacheKey = "comments.post.{$postId}.".md5(serialize($filters));

        return Cache::remember(
            $cacheKey,
            self::COMMENT_CACHE_DURATION,
            $callback
        );
    }

    /**
     * Get top-level comments for a post from cache or load them fresh.
     */
    public function getOrCacheTopLevelComments(int $postId, callable $callback)
    {
        return Cache::remember(
            "comments.toplevel.post.{$postId}",
            self::COMMENT_CACHE_DURATION,
            $callback
        );
    }

    /**
     * Get categories from cache or load them fresh.
     */
    public function getOrCacheCategories(callable $callback)
    {
        return Cache::remember(
            'posts.categories',
            self::CATEGORIES_CACHE_DURATION,
            $callback
        );
    }

    /**
     * Get trending posts from cache or load them fresh.
     */
    public function getOrCacheTrending(callable $callback)
    {
        return Cache::remember(
            'posts.trending',
            self::TRENDING_CACHE_DURATION,
            $callback
        );
    }

    /**
     * Get dashboard data from cache or load it fresh.
     */
    public function getOrCacheDashboard(int $userId, callable $callback)
    {
        return Cache::remember(
            "dashboard.user.{$userId}",
            self::DASHBOARD_CACHE_DURATION,
            $callback
        );
    }

    /**
     * Clear caches that need updating when posts change.
     */
    public function clearPostRelatedCaches(?int $postId = null): void
    {
        $this->clearCategoriesCache();
        $this->clearTrendingCache();

        if ($postId) {
            $this->clearPostCache($postId);
        }
    }

    /**
     * Clear caches that need updating when comments change.
     */
    public function clearCommentRelatedCaches(int $postId): void
    {
        $this->clearPostCache($postId);
        $this->clearPostCommentCaches($postId);
        $this->clearTrendingCache();
    }

    /**
     * Get dashboard stats from cache or load them fresh.
     */
    public function getOrCacheDashboardStats(int $userId, callable $callback)
    {
        return Cache::remember(
            "dashboard.stats.user.{$userId}",
            self::DASHBOARD_CACHE_DURATION,
            $callback
        );
    }

    /**
     * Get dashboard recent posts from cache or load them fresh.
     */
    public function getOrCacheDashboardRecentPosts(int $userId, callable $callback)
    {
        return Cache::remember(
            "dashboard.recent-posts.user.{$userId}",
            self::DASHBOARD_CACHE_DURATION,
            $callback
        );
    }

    /**
     * Get system stats from cache or load them fresh.
     */
    public function getOrCacheSystemStats(callable $callback)
    {
        return Cache::remember(
            'system.stats',
            self::SYSTEM_STATS_CACHE_DURATION,
            $callback
        );
    }

    /**
     * Get welcome stats from cache or load them fresh.
     */
    public function getOrCacheWelcomeStats(callable $callback)
    {
        return Cache::remember(
            'welcome.stats',
            self::WELCOME_CACHE_DURATION,
            $callback
        );
    }

    /**
     * Get welcome recent posts from cache or load them fresh.
     */
    public function getOrCacheWelcomeRecentPosts(callable $callback)
    {
        return Cache::remember(
            'welcome.recent-posts',
            self::WELCOME_CACHE_DURATION,
            $callback
        );
    }

    /**
     * Clear dashboard caches for a user.
     */
    public function clearUserDashboardCaches(int $userId): void
    {
        Cache::forget("dashboard.user.{$userId}");
        Cache::forget("dashboard.stats.user.{$userId}");
        Cache::forget("dashboard.recent-posts.user.{$userId}");
    }

    /**
     * Clear welcome page caches.
     */
    public function clearWelcomeCaches(): void
    {
        Cache::forget('welcome.stats');
        Cache::forget('welcome.recent-posts');
    }

    /**
     * Clear system stats cache.
     */
    public function clearSystemStatsCache(): void
    {
        Cache::forget('system.stats');
    }
}
