<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\DashboardService;

beforeEach(function () {
    $this->dashboardService = app(DashboardService::class);
    $this->user = User::factory()->create();
});

describe('getWelcomeData', function () {
    it('returns welcome page data with stats and recent posts', function () {
        Post::factory()->count(8)->create(['status' => 'published']);
        Post::factory()->count(2)->create(['status' => 'draft']); // Should not be included
        User::factory()->count(5)->create();

        $result = $this->dashboardService->getWelcomeData();

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys(['recentPosts', 'stats'])
            ->and($result['recentPosts'])->toBeArray()
            ->and($result['stats'])->toBeArray()
            ->and($result['stats'])->toHaveKeys(['totalPosts', 'totalUsers', 'totalViews'])
            ->and(count($result['recentPosts']))->toBeLessThanOrEqual(6)
            ->and($result['stats']['totalPosts'])->toBe(8)
            ->and($result['stats']['totalUsers'])->toBeGreaterThanOrEqual(6);
    });

    it('handles empty data gracefully', function () {
        $result = $this->dashboardService->getWelcomeData();

        expect($result)->toBeArray()
            ->and($result['recentPosts'])->toBeArray()
            ->and($result['stats']['totalPosts'])->toBe(0)
            ->and($result['stats']['totalViews'])->toBe(0);
    });

    it('limits recent posts to 6 items', function () {
        Post::factory()->count(10)->create(['status' => 'published']);

        $result = $this->dashboardService->getWelcomeData();

        expect(count($result['recentPosts']))->toBe(6);
    });
});

describe('getDashboardData', function () {
    it('returns complete dashboard data for user', function () {
        Post::factory()->count(3)->create(['user_id' => $this->user->id]);
        User::factory()->count(2)->create();

        $result = $this->dashboardService->getDashboardData($this->user);

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys(['userStats', 'recentPosts', 'systemStats'])
            ->and($result['userStats'])->toBeArray()
            ->and($result['recentPosts'])->toBeArray()
            ->and($result['systemStats'])->toBeArray();
    });
});

describe('getUserStats', function () {
    it('returns complete user statistics', function () {
        // Create posts for user
        Post::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'published',
            'views_count' => 50,
        ]);
        Post::factory()->count(1)->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'views_count' => 0,
        ]);

        // Create comments on user's posts (make sure this post is published)
        $userPost = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
            'views_count' => 25,
        ]);
        Comment::factory()->count(3)->create(['post_id' => $userPost->id]);

        $result = $this->dashboardService->getUserStats($this->user);

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys([
                'totalPosts',
                'publishedPosts',
                'draftPosts',
                'totalViews',
                'totalComments',
            ])
            ->and($result['totalPosts'])->toBe(4) // 2 published + 1 draft + 1 for comments
            ->and($result['publishedPosts'])->toBe(3) // 2 explicitly published + 1 for comments
            ->and($result['draftPosts'])->toBe(1)
            ->and($result['totalViews'])->toBe(125) // (50 * 2) + 25
            ->and($result['totalComments'])->toBe(3);
    });

    it('returns zero stats for user with no content', function () {
        $result = $this->dashboardService->getUserStats($this->user);

        expect($result)->toBeArray()
            ->and($result['totalPosts'])->toBe(0)
            ->and($result['publishedPosts'])->toBe(0)
            ->and($result['draftPosts'])->toBe(0)
            ->and($result['totalViews'])->toBe(0)
            ->and($result['totalComments'])->toBe(0);
    });
});

describe('getUserRecentPosts', function () {
    it('returns user recent posts with proper data structure', function () {
        Post::factory()->count(7)->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);

        $result = $this->dashboardService->getUserRecentPosts($this->user);

        expect($result)->toBeArray()
            ->and(count($result))->toBe(5); // Limited to 5 posts

        if (count($result) > 0) {
            $firstPost = $result[0];
            expect($firstPost)->toBeArray()
                ->and($firstPost)->toHaveKeys(['id', 'title', 'status']);
        }
    });

    it('includes comments count in post data', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        Comment::factory()->count(3)->create(['post_id' => $post->id]);

        $result = $this->dashboardService->getUserRecentPosts($this->user);

        expect($result)->toBeArray()
            ->and(count($result))->toBe(1);

        $postData = $result[0];
        expect($postData)->toHaveKey('comments_count');
    });

    it('orders posts by creation date desc', function () {
        $oldPost = Post::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2),
        ]);
        $newPost = Post::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $result = $this->dashboardService->getUserRecentPosts($this->user);

        expect($result[0]['id'])->toBe($newPost->id)
            ->and($result[1]['id'])->toBe($oldPost->id);
    });

    it('returns empty array for user with no posts', function () {
        $result = $this->dashboardService->getUserRecentPosts($this->user);

        expect($result)->toBeArray()
            ->and(count($result))->toBe(0);
    });
});

describe('getSystemStats', function () {
    it('returns complete system statistics', function () {
        User::factory()->count(10)->create();
        Post::factory()->count(15)->create(['views_count' => 100]);

        $result = $this->dashboardService->getSystemStats();

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys([
                'totalUsers',
                'totalPosts',
                'totalViews',
                'postsThisMonth',
            ])
            ->and($result['totalUsers'])->toBeGreaterThanOrEqual(11) // 10 + 1 from beforeEach
            ->and($result['totalPosts'])->toBe(15)
            ->and($result['totalViews'])->toBe(1500); // 15 * 100
    });

    it('counts posts from current month correctly', function () {
        Post::factory()->count(3)->create([
            'created_at' => now(),
            'views_count' => 50,
        ]);
        Post::factory()->count(2)->create([
            'created_at' => now()->subMonth(),
            'views_count' => 25,
        ]);

        $result = $this->dashboardService->getSystemStats();

        expect($result['postsThisMonth'])->toBe(3)
            ->and($result['totalPosts'])->toBe(5)
            ->and($result['totalViews'])->toBe(200); // (3 * 50) + (2 * 25)
    });

    it('handles empty database gracefully', function () {
        $result = $this->dashboardService->getSystemStats();

        expect($result)->toBeArray()
            ->and($result['totalUsers'])->toBeGreaterThanOrEqual(1) // At least the user from beforeEach
            ->and($result['totalPosts'])->toBe(0)
            ->and($result['totalViews'])->toBe(0)
            ->and($result['postsThisMonth'])->toBe(0);
    });
});
