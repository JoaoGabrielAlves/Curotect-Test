<?php

use App\Models\Post;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

describe('index', function () {
    it('returns posts with proper filters for authenticated users', function () {
        Post::factory()->create(['status' => 'published', 'title' => 'Test Post']);
        Post::factory()->create(['status' => 'draft']); // Should not appear

        $response = $this->actingAs($this->user)->get(route('posts.index', [
            'search' => 'Test',
            'category' => 'tech',
            'sort' => 'title',
            'direction' => 'asc',
            'per_page' => 15,
        ]));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Index')
                ->has('posts')
                ->has('categories')
                ->has('filters')
                ->where('showActions', false)
                ->where('showStatusFilter', false)
                ->where('filters.search', 'Test')
                ->where('filters.category', 'tech')
                ->where('filters.sort', 'title')
                ->where('filters.direction', 'asc')
                ->where('filters.per_page', 15)
            );
    });

    it('validates request parameters', function () {
        $response = $this->actingAs($this->user)->get(route('posts.index', [
            'search' => str_repeat('a', 300), // Too long
            'sort' => 'invalid_field',
            'direction' => 'invalid_direction',
            'per_page' => 200, // Too high
        ]));

        $response->assertSessionHasErrors(['search', 'sort', 'direction', 'per_page']);
    });

    it('validates search parameter maximum length', function () {
        $response = $this->actingAs($this->user)->get(route('posts.index', [
            'search' => str_repeat('a', 256), // Too long (max 255)
        ]));

        $response->assertSessionHasErrors(['search']);
    });

    it('validates category parameter maximum length', function () {
        $response = $this->actingAs($this->user)->get(route('posts.index', [
            'category' => str_repeat('a', 256), // Too long (max 255)
        ]));

        $response->assertSessionHasErrors(['category']);
    });

    it('validates sort parameter enum values', function () {
        $response = $this->actingAs($this->user)->get(route('posts.index', [
            'sort' => 'invalid_sort_field',
        ]));

        $response->assertSessionHasErrors(['sort']);
    });

    it('validates direction parameter enum values', function () {
        $response = $this->actingAs($this->user)->get(route('posts.index', [
            'direction' => 'sideways', // Invalid direction
        ]));

        $response->assertSessionHasErrors(['direction']);
    });

    it('validates per_page minimum value', function () {
        $response = $this->actingAs($this->user)->get(route('posts.index', [
            'per_page' => 3, // Too low (min 5)
        ]));

        $response->assertSessionHasErrors(['per_page']);
    });

    it('validates per_page maximum value', function () {
        $response = $this->actingAs($this->user)->get(route('posts.index', [
            'per_page' => 150, // Too high (max 100)
        ]));

        $response->assertSessionHasErrors(['per_page']);
    });

    it('validates per_page must be integer', function () {
        $response = $this->actingAs($this->user)->get(route('posts.index', [
            'per_page' => 'not-a-number',
        ]));

        $response->assertSessionHasErrors(['per_page']);
    });

    it('uses default values when parameters are not provided', function () {
        $response = $this->actingAs($this->user)->get(route('posts.index'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Index')
                ->where('filters.search', '')
                ->where('filters.category', '')
                ->where('filters.sort', 'created_at')
                ->where('filters.direction', 'desc')
                ->where('filters.per_page', 10)
            );
    });

    it('requires authentication', function () {
        $response = $this->get(route('posts.index'));

        $response->assertRedirect(route('login'));
    });
});

describe('myPosts', function () {
    it('returns user posts with management capabilities', function () {
        Post::factory()->create(['user_id' => $this->user->id, 'title' => 'My Post']);
        Post::factory()->create(['user_id' => $this->otherUser->id]); // Should not appear

        $response = $this->actingAs($this->user)->get(route('posts.my', [
            'search' => 'My',
            'status' => 'draft',
            'per_page' => 20,
        ]));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/MyPosts')
                ->has('posts')
                ->has('categories')
                ->where('showActions', true)
                ->where('showStatusFilter', true)
                ->where('filters.search', 'My')
                ->where('filters.status', 'draft')
                ->where('filters.per_page', 20)
            );
    });

    it('validates status filter for user posts', function () {
        $response = $this->actingAs($this->user)->get(route('posts.my', [
            'status' => 'invalid_status',
        ]));

        $response->assertSessionHasErrors(['status']);
    });

    it('requires authentication', function () {
        $response = $this->get(route('posts.my'));

        $response->assertRedirect(route('login'));
    });
});

describe('show', function () {
    it('returns post view for published posts', function () {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->actingAs($this->user)->get(route('posts.show', $post));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Show')
                ->has('post')
                ->where('post.data.id', $post->id)
                ->where('post.data.status', 'published')
            );
    });

    it('allows post owner to view draft posts', function () {
        $post = Post::factory()->create(['status' => 'draft', 'user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get(route('posts.show', $post));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Show')
                ->where('post.data.id', $post->id)
            );
    });

    it('returns 403 when post is not viewable', function () {
        $post = Post::factory()->create(['status' => 'draft', 'user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)->get(route('posts.show', $post));

        $response->assertStatus(403);
    });

    it('returns 404 for non-existent posts', function () {
        $response = $this->actingAs($this->user)->get(route('posts.show', 999999));

        $response->assertStatus(404);
    });

    it('requires authentication', function () {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->get(route('posts.show', $post));

        $response->assertRedirect(route('login'));
    });
});

describe('create', function () {
    it('returns create form with categories', function () {
        Post::factory()->create(['category' => 'Technology', 'status' => 'published']);
        Post::factory()->create(['category' => 'Business', 'status' => 'published']);

        $response = $this->actingAs($this->user)->get(route('posts.create'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Create')
                ->has('categories', 2)
            );
    });

    it('requires authentication', function () {
        $response = $this->get(route('posts.create'));

        $response->assertRedirect(route('login'));
    });
});

describe('store', function () {
    it('creates post and redirects to show page', function () {
        $requestData = [
            'title' => 'Test Post',
            'content' => 'Test content that meets minimum requirements',
            'status' => 'draft',
            'category' => 'Technology',
        ];

        $response = $this->actingAs($this->user)->post(route('posts.store'), $requestData);

        $response->assertRedirect();
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'content' => 'Test content that meets minimum requirements',
            'status' => 'draft',
            'user_id' => $this->user->id,
        ]);

        $post = Post::where('title', 'Test Post')->first();
        $response->assertRedirect(route('posts.show', $post));
    });

    it('validates required fields', function () {
        $response = $this->actingAs($this->user)->post(route('posts.store'), []);

        $response->assertSessionHasErrors(['title', 'content', 'status']);
    });

    it('requires authentication', function () {
        $response = $this->post(route('posts.store'), [
            'title' => 'Test Post',
            'content' => 'Test content',
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('login'));
    });
});

describe('edit', function () {
    it('returns edit form with post data and etag', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        Post::factory()->create(['category' => 'Technology', 'status' => 'published']);

        $response = $this->actingAs($this->user)->get(route('posts.edit', $post));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Edit')
                ->has('post', fn (Assert $postData) => $postData
                    ->where('data.id', $post->id)
                    ->has('data.etag')
                    ->etc()
                )
                ->has('categories')
            );
    });

    it('denies access to non-owners', function () {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)->get(route('posts.edit', $post));

        $response->assertStatus(403);
    });

    it('requires authentication', function () {
        $post = Post::factory()->create();

        $response = $this->get(route('posts.edit', $post));

        $response->assertRedirect(route('login'));
    });
});

describe('update', function () {
    it('updates post successfully with valid etag', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $etag = $post->e_tag;

        $requestData = [
            'title' => 'Updated Title',
            'content' => 'Updated content that meets minimum requirements',
            'status' => 'published',
            'etag' => $etag,
        ];

        $response = $this->actingAs($this->user)->put(route('posts.update', $post), $requestData);

        $response->assertRedirect(route('posts.show', $post));
        expect($post->fresh()->title)->toBe('Updated Title');
    });

    it('handles etag validation error', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $requestData = [
            'title' => 'Updated Title',
            'content' => 'Updated content that meets minimum requirements',
            'etag' => 'invalid_etag',
        ];

        $response = $this->actingAs($this->user)->put(route('posts.update', $post), $requestData);

        $response->assertSessionHasErrors(['etag']);
    });

    it('denies update to non-owners', function () {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);
        $etag = $post->e_tag;

        $requestData = [
            'title' => 'Hacked Title',
            'content' => 'Hacked content that meets minimum requirements',
            'etag' => $etag,
        ];

        $response = $this->actingAs($this->user)->put(route('posts.update', $post), $requestData);

        $response->assertStatus(403);
    });

    it('requires authentication', function () {
        $post = Post::factory()->create();

        $response = $this->put(route('posts.update', $post), [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'etag' => 'some_etag',
        ]);

        $response->assertRedirect(route('login'));
    });
});

describe('destroy', function () {
    it('deletes post and redirects to posts index', function () {
        $this->withoutExceptionHandling();
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete(route('posts.destroy', $post));

        $response->assertRedirect(route('posts.index'));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    });

    it('denies deletion to non-owners', function () {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)->delete(route('posts.destroy', $post));

        $response->assertStatus(403);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    });

    it('requires authentication', function () {
        $post = Post::factory()->create();

        $response = $this->delete(route('posts.destroy', $post));

        $response->assertRedirect(route('login'));
    });
});
