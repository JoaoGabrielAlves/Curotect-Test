<?php

use App\Models\Post;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Post Form Validation', function () {
    it('shows create form with proper structure', function () {
        $response = $this->actingAs($this->user)->get('/posts/create');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Create')
                ->has('categories')
            );
    });

    it('allows form submission with empty fields and returns validation errors', function () {
        $response = $this->actingAs($this->user)->post('/posts', []);

        $response->assertSessionHasErrors(['title', 'content', 'status']);
        $response->assertRedirect();
    });

    it('shows specific validation messages for each field', function () {
        $response = $this->actingAs($this->user)->post('/posts', [
            'title' => 'ab', // Too short
            'content' => 'short', // Too short
            'status' => 'invalid', // Invalid status
        ]);

        $response->assertSessionHasErrors([
            'title' => 'The post title must be at least 3 characters.',
            'content' => 'The post content must be at least 10 characters.',
            'status' => 'The selected status is invalid.',
        ]);
    });

    it('accepts valid post data and creates post', function () {
        $postData = [
            'title' => 'Valid Post Title',
            'content' => 'This is a valid post content that meets the minimum requirements.',
            'status' => 'draft',
            'category' => 'Technology',
        ];

        $response = $this->actingAs($this->user)->post('/posts', $postData);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('posts', [
            'title' => 'Valid Post Title',
            'content' => 'This is a valid post content that meets the minimum requirements.',
            'status' => 'draft',
            'category' => 'Technology',
            'user_id' => $this->user->id,
        ]);
    });

    it('validates published posts require future publication date when specified', function () {
        $pastDate = now()->subDay()->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->user)->post('/posts', [
            'title' => 'Valid Post Title',
            'content' => 'This is a valid post content that meets the minimum requirements.',
            'status' => 'published',
            'published_at' => $pastDate,
        ]);

        $response->assertSessionHasErrors(['published_at']);
    });

    it('allows published posts with future publication date', function () {
        $futureDate = now()->addDay()->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->user)->post('/posts', [
            'title' => 'Valid Post Title',
            'content' => 'This is a valid post content that meets the minimum requirements.',
            'status' => 'published',
            'published_at' => $futureDate,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });

    it('allows published posts without publication date (immediate publish)', function () {
        $response = $this->actingAs($this->user)->post('/posts', [
            'title' => 'Valid Post Title',
            'content' => 'This is a valid post content that meets the minimum requirements.',
            'status' => 'published',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $post = Post::where('title', 'Valid Post Title')->first();
        expect($post->published_at)->not->toBeNull();
    });

    it('shows edit form with proper structure and etag', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get("/posts/{$post->id}/edit");

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

    it('allows edit form submission with invalid data and returns validation errors', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $etag = $post->e_tag;

        $response = $this->actingAs($this->user)->put("/posts/{$post->id}", [
            'title' => '', // Empty
            'content' => '', // Empty
            'status' => '', // Empty
            'etag' => $etag,
        ]);

        $response->assertSessionHasErrors(['title', 'content', 'status']);
    });

    it('updates post successfully with valid data and correct etag', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $etag = $post->e_tag;

        $updateData = [
            'title' => 'Updated Post Title',
            'content' => 'This is updated content that meets the minimum length requirement.',
            'status' => 'published',
            'category' => 'Business',
            'etag' => $etag,
        ];

        $response = $this->actingAs($this->user)->put("/posts/{$post->id}", $updateData);

        $response->assertRedirect(); // Should redirect to post show page

        $post->refresh();
        expect($post->title)->toBe('Updated Post Title')
            ->and($post->content)->toBe('This is updated content that meets the minimum length requirement.')
            ->and($post->status)->toBe('published')
            ->and($post->category)->toBe('Business');
    });

    it('handles concurrency conflicts with etag validation', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated content that meets the minimum length requirement.',
            'status' => 'published',
            'etag' => 'invalid_etag',
        ];

        $response = $this->actingAs($this->user)->put("/posts/{$post->id}", $updateData);

        $response->assertSessionHasErrors(['etag']);

        // Post should not be updated
        $post->refresh();
        expect($post->title)->not->toBe('Updated Title');
    });
});

describe('Form User Experience', function () {
    it('preserves form data on validation errors', function () {
        $postData = [
            'title' => 'ab', // Too short, will cause validation error
            'content' => 'Valid content that meets the minimum length requirement.',
            'status' => 'draft',
            'category' => 'Technology',
        ];

        $response = $this->actingAs($this->user)->post('/posts', $postData);

        $response->assertSessionHasErrors(['title']);

        // Check that valid data is preserved in the session
        $response->assertSessionHasInput('content', 'Valid content that meets the minimum length requirement.');
        $response->assertSessionHasInput('status', 'draft');
        $response->assertSessionHasInput('category', 'Technology');
    });

    it('handles published posts with automatic published_at setting', function () {
        $postData = [
            'title' => 'Published Post',
            'content' => 'This post will be published immediately.',
            'status' => 'published',
            // No published_at provided - should be auto-set
        ];

        $this->actingAs($this->user)->post('/posts', $postData);

        $post = Post::where('title', 'Published Post')->first();
        expect($post->published_at)->not->toBeNull()
            ->and($post->status)->toBe('published');
    });

    it('clears published_at for draft posts', function () {
        $postData = [
            'title' => 'Draft Post',
            'content' => 'This post is saved as a draft.',
            'status' => 'draft',
            'published_at' => now()->toDateTimeString(), // Should be cleared
        ];

        $this->actingAs($this->user)->post('/posts', $postData);

        $post = Post::where('title', 'Draft Post')->first();
        expect($post->published_at)->toBeNull()
            ->and($post->status)->toBe('draft');
    });

    it('rejects past publication dates for new posts', function () {
        $postData = [
            'title' => 'Future Post',
            'content' => 'This post has a past publication date.',
            'status' => 'published',
            'published_at' => now()->subHour()->toDateTimeString(), // Past date
        ];

        $response = $this->actingAs($this->user)->post('/posts', $postData);

        $response->assertSessionHasErrors(['published_at' => 'The publication date cannot be in the past.']);
    });

    it('rejects past publication dates for post updates', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $etag = $post->e_tag;

        $updateData = [
            'title' => 'Updated Post',
            'content' => 'This post has a past publication date.',
            'status' => 'published',
            'published_at' => now()->subHour()->toDateTimeString(), // Past date
            'etag' => $etag,
        ];

        $response = $this->actingAs($this->user)->put("/posts/{$post->id}", $updateData);

        $response->assertSessionHasErrors(['published_at' => 'The publication date cannot be in the past.']);
    });

    it('redirects to my posts after successful deletion', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete("/posts/{$post->id}");

        $response->assertRedirect(route('posts.index'));
        $response->assertSessionHas('success', 'Post deleted successfully!');
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    });
});
