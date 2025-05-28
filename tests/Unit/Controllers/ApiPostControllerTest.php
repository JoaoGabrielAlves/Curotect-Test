<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

describe('store', function () {
    it('creates post successfully and returns JSON response', function () {
        $requestData = [
            'title' => 'Test Post Title',
            'content' => 'This is a comprehensive test post content that meets the minimum length requirements.',
            'status' => 'draft',
            'category' => 'Technology',
        ];

        $response = $this->actingAs($this->user)->postJson(route('api.posts.store'), $requestData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Post created successfully!',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'content',
                    'excerpt',
                    'status',
                    'category',
                    'views_count',
                    'comments_count',
                    'created_at',
                    'updated_at',
                    'published_at',
                    'etag',
                    'can_edit',
                    'can_delete',
                    'reading_time',
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post Title',
            'content' => 'This is a comprehensive test post content that meets the minimum length requirements.',
            'status' => 'draft',
            'category' => 'Technology',
            'user_id' => $this->user->id,
        ]);
    });

    it('auto-sets published_at when status is published', function () {
        $requestData = [
            'title' => 'Published Post',
            'content' => 'This post will be published immediately with proper content length.',
            'status' => 'published',
            'category' => 'Technology',
        ];

        $response = $this->actingAs($this->user)->postJson(route('api.posts.store'), $requestData);

        $response->assertStatus(201);

        $post = Post::where('title', 'Published Post')->first();
        expect($post->published_at)->not->toBeNull()
            ->and($post->status)->toBe('published');
    });

    it('requires authentication', function () {
        $response = $this->postJson(route('api.posts.store'), [
            'title' => 'Test Post',
            'content' => 'Test content',
            'status' => 'draft',
        ]);

        $response->assertStatus(401);
    });

    it('validates required fields', function () {
        $response = $this->actingAs($this->user)->postJson(route('api.posts.store'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content', 'status']);
    });

    it('validates title length constraints', function () {
        $response = $this->actingAs($this->user)->postJson(route('api.posts.store'), [
            'title' => 'ab', // Too short
            'content' => 'Valid content that meets the minimum length requirements for testing.',
            'status' => 'draft',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    it('validates content length constraints', function () {
        $response = $this->actingAs($this->user)->postJson(route('api.posts.store'), [
            'title' => 'Valid Title',
            'content' => 'Short', // Too short
            'status' => 'draft',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    });

    it('validates status enum values', function () {
        $response = $this->actingAs($this->user)->postJson(route('api.posts.store'), [
            'title' => 'Valid Title',
            'content' => 'Valid content that meets the minimum length requirements for testing.',
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    });

    it('validates category length if provided', function () {
        $response = $this->actingAs($this->user)->postJson(route('api.posts.store'), [
            'title' => 'Valid Title',
            'content' => 'Valid content that meets the minimum length requirements for testing.',
            'status' => 'draft',
            'category' => str_repeat('a', 256), // Too long
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    });

    it('validates title maximum length', function () {
        $response = $this->actingAs($this->user)->postJson(route('api.posts.store'), [
            'title' => str_repeat('a', 256), // Too long (max 255)
            'content' => 'Valid content that meets the minimum length requirements for testing.',
            'status' => 'draft',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    it('validates content maximum length', function () {
        $response = $this->actingAs($this->user)->postJson(route('api.posts.store'), [
            'title' => 'Valid Title',
            'content' => str_repeat('a', 65536), // Too long (max 65535)
            'status' => 'draft',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    });

    it('validates published_at must be future date', function () {
        $response = $this->actingAs($this->user)->postJson(route('api.posts.store'), [
            'title' => 'Valid Title',
            'content' => 'Valid content that meets the minimum length requirements for testing.',
            'status' => 'published',
            'published_at' => now()->subDay()->toISOString(), // Past date
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['published_at']);
    });

    it('validates published_at date format', function () {
        $response = $this->actingAs($this->user)->postJson(route('api.posts.store'), [
            'title' => 'Valid Title',
            'content' => 'Valid content that meets the minimum length requirements for testing.',
            'status' => 'published',
            'published_at' => 'invalid-date-format',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['published_at']);
    });
});

describe('update', function () {
    it('updates post successfully with valid etag', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $etag = $post->e_tag;

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated content that meets the minimum length requirements for testing.',
            'status' => 'published',
            'category' => 'Business',
            'etag' => $etag,
        ];

        $response = $this->actingAs($this->user)->putJson(route('api.posts.update', $post), $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Post updated successfully!',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'content',
                    'excerpt',
                    'status',
                    'category',
                    'views_count',
                    'comments_count',
                    'created_at',
                    'updated_at',
                    'published_at',
                    'etag',
                    'can_edit',
                    'can_delete',
                    'reading_time',
                ],
            ]);

        $post->refresh();
        expect($post->title)->toBe('Updated Title')
            ->and($post->content)->toBe('Updated content that meets the minimum length requirements for testing.')
            ->and($post->status)->toBe('published')
            ->and($post->category)->toBe('Business');
    });

    it('handles concurrency conflict with invalid etag', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated content that meets the minimum length requirements for testing.',
            'status' => 'published',
            'category' => 'Business',
            'etag' => 'invalid-etag',
        ];

        $response = $this->actingAs($this->user)->putJson(route('api.posts.update', $post), $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['etag'])
            ->assertJsonFragment([
                'etag' => ['This post has been modified by another user. Please refresh and try again.'],
            ]);

        // Post should not be updated
        $post->refresh();
        expect($post->title)->not->toBe('Updated Title');
    });

    it('denies update to non-owners', function () {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);
        $etag = $post->e_tag;

        $updateData = [
            'title' => 'Hacked Title',
            'content' => 'Hacked content that meets the minimum length requirements for testing.',
            'status' => 'published',
            'etag' => $etag,
        ];

        $response = $this->actingAs($this->user)->putJson(route('api.posts.update', $post), $updateData);

        $response->assertStatus(403);
    });

    it('requires authentication', function () {
        $post = Post::factory()->create();

        $response = $this->putJson(route('api.posts.update', $post), [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'etag' => 'some-etag',
        ]);

        $response->assertStatus(401);
    });

    it('validates required etag field', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->putJson(route('api.posts.update', $post), [
            'title' => 'Updated Title',
            'content' => 'Updated content that meets requirements',
            'status' => 'draft',
            // Missing etag
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['etag']);
    });

    it('validates title and content length on update', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $etag = $post->e_tag;

        $response = $this->actingAs($this->user)->putJson(route('api.posts.update', $post), [
            'title' => '', // Too short
            'content' => 'x', // Too short
            'status' => 'invalid_status', // Invalid
            'etag' => $etag,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content', 'status']);
    });

    it('handles non-existent post', function () {
        $response = $this->actingAs($this->user)->putJson(route('api.posts.update', 999999), [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'etag' => 'some-etag',
        ]);

        $response->assertStatus(404);
    });
});

describe('destroy', function () {
    it('deletes post successfully', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->deleteJson(route('api.posts.destroy', $post));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Post deleted successfully!',
            ]);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    });

    it('denies deletion to non-owners', function () {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)->deleteJson(route('api.posts.destroy', $post));

        $response->assertStatus(403);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    });

    it('requires authentication', function () {
        $post = Post::factory()->create();

        $response = $this->deleteJson(route('api.posts.destroy', $post));

        $response->assertStatus(401);
    });

    it('handles non-existent post', function () {
        $response = $this->actingAs($this->user)->deleteJson(route('api.posts.destroy', 999999));

        $response->assertStatus(404);
    });

    it('handles posts with dependencies gracefully', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        // Create some comments to test cascade deletion
        Comment::factory()->count(3)->create(['post_id' => $post->id]);

        $response = $this->actingAs($this->user)->deleteJson(route('api.posts.destroy', $post));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('comments', ['post_id' => $post->id]);
    });
});
