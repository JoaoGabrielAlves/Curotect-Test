<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('welcome', function () {
    it('displays welcome page for guests', function () {
        $response = $this->get(route('home'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
            );
    });

    it('displays welcome page for authenticated users', function () {
        $response = $this->actingAs($this->user)->get(route('home'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
            );
    });
});

describe('dashboard', function () {
    it('displays dashboard with user statistics', function () {
        // Create test data for the user
        $publishedPosts = Post::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);

        Post::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        // Create comments on user's posts
        Comment::factory()->count(5)->create([
            'post_id' => $publishedPosts->first()->id,
        ]);

        // Create comments by the user
        Comment::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'post_id' => Post::factory()->create(['status' => 'published'])->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('userStats')
                ->has('recentPosts')
                ->has('systemStats')
                ->where('systemStats.totalPosts', 6) // 4 published + 2 draft
                ->where('userStats.publishedPosts', 3)
                ->where('userStats.draftPosts', 2)
                ->where('userStats.totalComments', 5) // Comments by user
            );
    });

    it('displays dashboard with empty statistics for new user', function () {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('userStats')
                ->has('recentPosts')
                ->has('systemStats')
                ->where('systemStats.totalPosts', 0)
                ->where('userStats.publishedPosts', 0)
                ->where('userStats.draftPosts', 0)
                ->where('userStats.totalComments', 0)
            );
    });

    it('displays recent posts correctly', function () {
        // Create posts with different dates
        Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
            'created_at' => now()->subDays(10),
            'title' => 'Old Post',
        ]);

        Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
            'created_at' => now()->subDay(),
            'title' => 'Recent Post',
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('recentPosts', 2)
                ->where('recentPosts.0.title', 'Recent Post') // Most recent first
                ->where('recentPosts.1.title', 'Old Post')
            );
    });

    it('limits recent posts to 5 items', function () {
        // Create more than 5 posts
        Post::factory()->count(8)->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('recentPosts', 5) // Should be limited to 5
            );
    });

    it('requires authentication', function () {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    });

    it('includes post relationships in recent posts', function () {
        Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);

        // Add some comments to the post
        Comment::factory()->count(3)->create([
            'post_id' => Post::where('user_id', $this->user->id)->first()->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('recentPosts.0.user') // Should include user relationship
                ->has('recentPosts.0.comments_count') // Should include comments count
            );
    });
});
