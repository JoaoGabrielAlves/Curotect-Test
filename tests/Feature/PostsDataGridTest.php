<?php

use App\Jobs\TrackPostView;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->users = User::factory(5)->create();

    $this->publishedPosts = Post::factory(15)
        ->published()
        ->recycle($this->users)
        ->create([
            'category' => fn () => fake()->randomElement(['Technology', 'Business', 'Health']),
        ]);

    $this->draftPosts = Post::factory(5)
        ->draft()
        ->recycle($this->users)
        ->create();

    $this->archivedPosts = Post::factory(3)
        ->archived()
        ->recycle($this->users)
        ->create();

    $this->publishedPosts->take(10)->each(function ($post) {
        Comment::factory(random_int(1, 5))
            ->approved()
            ->recycle($this->users)
            ->create(['post_id' => $post->id]);
    });

    $this->actingAs(User::query()->first());
});

describe('Posts Data Grid', function () {
    it('displays posts in data grid format', function () {
        $response = $this->get(route('posts.index'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Index')
                ->has('posts.data') // Just check that data exists, pagination will control the count
                ->has('categories')
                ->has('filters')
                ->where('posts.meta.total', 15) // Total should be 15 but pagination limits per page
            );
    });

    it('paginates posts correctly', function () {
        $response = $this->get(route('posts.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('posts.meta.current_page')
            ->has('posts.meta.last_page')
            ->has('posts.meta.per_page')
            ->has('posts.meta.total')
            ->where('posts.meta.total', 15) // We created 15 published posts
        );
    });

    it('shows only published posts in public index', function () {
        $response = $this->get(route('posts.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('posts.data') // Just check that data exists
            ->where('posts.meta.total', 15) // Total count should be 15
        );
    });

    it('includes user and comments count with posts', function () {
        $response = $this->get(route('posts.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('posts.data.0', fn (Assert $post) => $post
                ->has('id')
                ->has('title')
                ->has('status')
                ->has('category')
                ->has('views_count')
                ->has('created_at')
                ->has('user', fn (Assert $user) => $user
                    ->has('id')
                    ->has('name')
                    ->has('email')
                    ->etc()
                )
                ->has('comments_count')
                ->etc()
            )
        );
    });

    it('filters posts by search term', function () {
        // Clear existing posts first
        Post::query()->delete();

        $searchPost = Post::factory()->create([
            'title' => 'Unique Search Title',
            'content' => 'This is searchable content',
            'status' => 'published', // Make sure it's published so it's visible
            'user_id' => $this->users->first()->id,
        ]);

        $response = $this->get(route('posts.index', ['search' => 'Unique']));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.data.0.title', 'Unique Search Title')
                ->where('posts.meta.total', 1)
            );
    });

    it('filters posts by category', function () {
        // Clear existing posts first
        Post::query()->delete();

        Post::factory(3)->create([
            'category' => 'Technology',
            'status' => 'published', // Make sure they're published so they're visible
            'user_id' => $this->users->first()->id,
        ]);

        $response = $this->get(route('posts.index', ['category' => 'Technology']));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.meta.total', 3)
                ->has('posts.data', 3)
            );
    });

    it('filters posts by status', function () {
        $response = $this->get(route('posts.index', ['status' => 'published']));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('posts.meta.total', 15) // We created 15 published posts
        );
    });

    it('sorts posts by title ascending', function () {
        // Clear existing posts first
        Post::query()->delete();

        // Create posts with specific titles for testing
        Post::factory()->create([
            'title' => 'A First Post',
            'status' => 'published', // Make sure it's published so it's visible
            'user_id' => $this->users->first()->id,
        ]);
        Post::factory()->create([
            'title' => 'Z Last Post',
            'status' => 'published', // Make sure it's published so it's visible
            'user_id' => $this->users->first()->id,
        ]);

        $response = $this->get(route('posts.index', ['sort' => 'title', 'direction' => 'asc']));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('posts.data.0.title', 'A First Post')
        );
    });

    it('sorts posts by created_at descending by default', function () {
        $response = $this->get(route('posts.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort', 'created_at')
            ->where('filters.direction', 'desc')
        );
    });

    it('sorts posts by views_count', function () {
        // Clear existing posts first
        Post::query()->delete();

        Post::factory()->create([
            'title' => 'High Views Post',
            'views_count' => 1000,
            'status' => 'published', // Make sure it's published so it's visible
            'user_id' => $this->users->first()->id,
        ]);
        Post::factory()->create([
            'title' => 'Low Views Post',
            'views_count' => 10,
            'status' => 'published', // Make sure it's published so it's visible
            'user_id' => $this->users->first()->id,
        ]);

        $response = $this->get(route('posts.index', ['sort' => 'views_count', 'direction' => 'desc']));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.data.0.title', 'High Views Post')
            );
    });

    it('sorts posts by user name', function () {
        // Clear existing posts to ensure clean test
        Post::query()->delete();

        $userA = User::factory()->create(['name' => 'Alice Smith']);
        $userZ = User::factory()->create(['name' => 'Zoe Johnson']);

        Post::factory()->create([
            'title' => 'Post by Alice',
            'user_id' => $userA->id,
            'status' => 'published',
        ]);
        Post::factory()->create([
            'title' => 'Post by Zoe',
            'user_id' => $userZ->id,
            'status' => 'published',
        ]);

        $response = $this->get(route('posts.index', ['sort' => 'user_name', 'direction' => 'asc']));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('posts.data.0.title', 'Post by Alice')
        );
    });

    it('maintains filters in pagination links', function () {
        $response = $this->get(route('posts.index', [
            'search' => 'test',
            'category' => 'Technology',
            'per_page' => 5,
        ]));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'test')
                ->where('filters.category', 'Technology')
                ->where('filters.per_page', 5) // Query parameters are strings
            );
    });

    it('validates query parameters', function () {
        $response = $this->get(route('posts.index', [
            'sort' => 'invalid_field',
            'direction' => 'invalid',
            'per_page' => 1000,
        ]));

        // Should redirect back with validation errors for invalid parameters
        $response->assertStatus(302);
    });

    it('returns available categories for filter dropdown', function () {
        $response = $this->get(route('posts.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('categories')
            ->where('categories', fn ($categories) => collect($categories)->contains('Technology') &&
                collect($categories)->contains('Business') &&
                collect($categories)->contains('Health')
            )
        );
    });

    it('combines multiple filters correctly', function () {
        // Clear existing posts first
        Post::query()->delete();

        Post::factory()->create([
            'title' => 'Tech Article',
            'category' => 'Technology',
            'status' => 'published',
            'user_id' => $this->users->first()->id,
        ]);

        $response = $this->get(route('posts.index', [
            'search' => 'Tech',
            'category' => 'Technology',
            'status' => 'published',
        ]));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.meta.total', 1)
                ->where('posts.data.0.title', 'Tech Article')
            );
    });

    it('preserves URL parameters for state management', function () {
        $response = $this->get(route('posts.index', [
            'search' => 'test',
            'category' => 'Technology',
            'sort' => 'title',
            'direction' => 'asc',
            'per_page' => 10,
        ]));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'test')
                ->where('filters.category', 'Technology')
                ->where('filters.sort', 'title')
                ->where('filters.direction', 'asc')
                ->where('filters.per_page', 10) // Query parameters are strings
            );
    });
});

describe('Posts Data Grid Performance', function () {
    it('uses efficient queries with eager loading', function () {
        // Enable query logging
        DB::enableQueryLog();

        $this->get(route('posts.index'));

        $queries = DB::getQueryLog();

        expect(count($queries))->toBeLessThanOrEqual(8);

        $queryStrings = collect($queries)->pluck('query')->implode(' ');
        expect($queryStrings)->toContain('posts');
    });
});

describe('Posts Show Page', function () {
    it('displays individual post with comments', function () {
        $post = $this->publishedPosts->first();

        $response = $this->get(route('posts.show', $post));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Show')
                ->has('post', fn (Assert $postData) => $postData
                    ->where('data.id', $post->id)
                    ->has('data.user')
                    ->has('data.comments')
                    ->etc()
                )
            );
    });

    it('increments views count when viewing post', function () {
        $post = $this->publishedPosts->first();

        $this->get(route('posts.show', $post));

        Queue::assertPushed(TrackPostView::class);
    });

    it('loads comments with nested replies efficiently', function () {
        $post = $this->publishedPosts->first();

        // Create a comment with replies
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $this->users->first()->id,
        ]);

        Comment::factory(2)->create([
            'post_id' => $post->id,
            'parent_id' => $comment->id,
            'user_id' => $this->users->first()->id,
        ]);

        DB::enableQueryLog();

        $response = $this->get(route('posts.show', $post));

        $queries = DB::getQueryLog();

        expect(count($queries))->toBeLessThanOrEqual(16);

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('post.data.comments.0.replies')
            );
    });
});
