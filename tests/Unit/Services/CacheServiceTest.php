<?php

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->cacheService = app(CacheService::class);
    Cache::flush(); // Clear cache before each test
});

describe('clearAllCaches', function () {
    it('clears all main application caches', function () {
        // Set up some cache data first
        Cache::put('posts.categories', ['tech', 'business'], 60);
        Cache::put('posts.trending', ['post1', 'post2'], 60);

        expect(Cache::has('posts.categories'))->toBeTrue()
            ->and(Cache::has('posts.trending'))->toBeTrue();

        $this->cacheService->clearAllCaches();

        expect(Cache::has('posts.categories'))->toBeFalse()
            ->and(Cache::has('posts.trending'))->toBeFalse();
    });
});

describe('clearPostCache', function () {
    it('clears cache for specific post', function () {
        $postId = 123;

        // Set up cache data
        Cache::put("post.{$postId}", ['data'], 60);

        expect(Cache::has("post.{$postId}"))->toBeTrue();

        $this->cacheService->clearPostCache($postId);

        expect(Cache::has("post.{$postId}"))->toBeFalse();
    });
});

describe('clearUserDashboardCache', function () {
    it('clears dashboard cache for specific user', function () {
        $userId = 456;

        // Set up cache data
        Cache::put("dashboard.user.{$userId}", ['dashboard_data'], 60);

        expect(Cache::has("dashboard.user.{$userId}"))->toBeTrue();

        $this->cacheService->clearUserDashboardCache($userId);

        expect(Cache::has("dashboard.user.{$userId}"))->toBeFalse();
    });
});

describe('clearCategoriesCache', function () {
    it('clears categories cache', function () {
        Cache::put('posts.categories', ['tech', 'business'], 60);

        expect(Cache::has('posts.categories'))->toBeTrue();

        $this->cacheService->clearCategoriesCache();

        expect(Cache::has('posts.categories'))->toBeFalse();
    });
});

describe('clearTrendingCache', function () {
    it('clears trending posts cache', function () {
        Cache::put('posts.trending', ['trending_posts'], 60);

        expect(Cache::has('posts.trending'))->toBeTrue();

        $this->cacheService->clearTrendingCache();

        expect(Cache::has('posts.trending'))->toBeFalse();
    });
});

describe('getOrCachePost', function () {
    it('caches and retrieves post data', function () {
        $postId = 123;
        $expectedData = ['id' => $postId, 'title' => 'Test Post'];

        $result = $this->cacheService->getOrCachePost($postId, function () use ($expectedData) {
            return $expectedData;
        });

        expect($result)->toBe($expectedData)
            ->and(Cache::has("post.{$postId}"))->toBeTrue()
            ->and(Cache::get("post.{$postId}"))->toBe($expectedData);
    });

    it('returns cached data on subsequent calls', function () {
        $postId = 123;
        $expectedData = ['id' => $postId, 'title' => 'Test Post'];

        // First call - should cache
        $this->cacheService->getOrCachePost($postId, function () use ($expectedData) {
            return $expectedData;
        });

        // Second call - should return cached data without calling callback
        $result = $this->cacheService->getOrCachePost($postId, function () {
            return ['different' => 'data']; // This shouldn't be called
        });

        expect($result)->toBe($expectedData);
    });
});

describe('getOrCacheCategories', function () {
    it('caches and retrieves categories', function () {
        $expectedCategories = collect(['tech', 'business', 'lifestyle']);

        $result = $this->cacheService->getOrCacheCategories(function () use ($expectedCategories) {
            return $expectedCategories;
        });

        expect($result)->toBe($expectedCategories)
            ->and(Cache::has('posts.categories'))->toBeTrue();
    });
});

describe('getOrCacheTrending', function () {
    it('caches and retrieves trending posts', function () {
        $expectedTrending = collect(['post1', 'post2', 'post3']);

        $result = $this->cacheService->getOrCacheTrending(function () use ($expectedTrending) {
            return $expectedTrending;
        });

        expect($result)->toBe($expectedTrending)
            ->and(Cache::has('posts.trending'))->toBeTrue();
    });
});

describe('getOrCacheDashboard', function () {
    it('caches and retrieves dashboard data for user', function () {
        $userId = 456;
        $expectedData = ['stats' => ['posts' => 5], 'recent' => []];

        $result = $this->cacheService->getOrCacheDashboard($userId, function () use ($expectedData) {
            return $expectedData;
        });

        expect($result)->toBe($expectedData)
            ->and(Cache::has("dashboard.user.{$userId}"))->toBeTrue();
    });
});

describe('clearPostRelatedCaches', function () {
    it('clears categories and trending caches', function () {
        Cache::put('posts.categories', ['tech'], 60);
        Cache::put('posts.trending', ['posts'], 60);

        expect(Cache::has('posts.categories'))->toBeTrue()
            ->and(Cache::has('posts.trending'))->toBeTrue();

        $this->cacheService->clearPostRelatedCaches();

        expect(Cache::has('posts.categories'))->toBeFalse()
            ->and(Cache::has('posts.trending'))->toBeFalse();
    });

    it('clears specific post cache when post ID provided', function () {
        $postId = 123;
        Cache::put("post.{$postId}", ['data'], 60);
        Cache::put('posts.categories', ['tech'], 60);
        Cache::put('posts.trending', ['posts'], 60);

        $this->cacheService->clearPostRelatedCaches($postId);

        expect(Cache::has("post.{$postId}"))->toBeFalse()
            ->and(Cache::has('posts.categories'))->toBeFalse()
            ->and(Cache::has('posts.trending'))->toBeFalse();
    });
});

describe('clearCommentRelatedCaches', function () {
    it('clears post and trending caches when comment changes', function () {
        $postId = 123;
        Cache::put("post.{$postId}", ['data'], 60);
        Cache::put('posts.trending', ['posts'], 60);

        expect(Cache::has("post.{$postId}"))->toBeTrue()
            ->and(Cache::has('posts.trending'))->toBeTrue();

        $this->cacheService->clearCommentRelatedCaches($postId);

        expect(Cache::has("post.{$postId}"))->toBeFalse()
            ->and(Cache::has('posts.trending'))->toBeFalse();
    });
});
