<?php

use App\Models\Post;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

describe('Post Creation', function () {
    it('shows create form to authenticated users', function () {
        $response = $this->actingAs($this->user)->get('/posts/create');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Create')
                ->has('categories')
            );
    });

    it('redirects unauthenticated users from create form', function () {
        $response = $this->get(route('posts.create'));

        $response->assertRedirect(route('login'));
    });

    it('creates a post with valid data', function () {
        $postData = [
            'title' => 'Test Post Title',
            'content' => 'This is a test post content that is long enough to pass validation.',
            'status' => 'draft',
            'category' => 'Technology',
        ];

        $response = $this->actingAs($this->user)->post(route('posts.store'), $postData);

        $response->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post Title',
            'content' => 'This is a test post content that is long enough to pass validation.',
            'status' => 'draft',
            'category' => 'Technology',
            'user_id' => $this->user->id,
        ]);
    });

    it('auto-sets published_at when status is published', function () {
        $postData = [
            'title' => 'Published Post',
            'content' => 'This post will be published immediately.',
            'status' => 'published',
            'category' => 'Technology',
        ];

        $this->actingAs($this->user)->post(route('posts.store'), $postData);

        $post = Post::where('title', 'Published Post')->first();
        expect($post->published_at)->not->toBeNull()
            ->and($post->status)->toBe('published');
    });

    it('validates required fields', function () {
        $response = $this->actingAs($this->user)->post(route('posts.store'), []);

        $response->assertSessionHasErrors(['title', 'content', 'status']);
    });

    it('validates title length', function () {
        $response = $this->actingAs($this->user)->post(route('posts.store'), [
            'title' => 'ab', // Too short
            'content' => 'Valid content that is long enough.',
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors(['title']);
    });

    it('validates content length', function () {
        $response = $this->actingAs($this->user)->post(route('posts.store'), [
            'title' => 'Valid Title',
            'content' => 'Short', // Too short
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors(['content']);
    });

    it('validates status values', function () {
        $response = $this->actingAs($this->user)->post(route('posts.store'), [
            'title' => 'Valid Title',
            'content' => 'Valid content that is long enough.',
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors(['status']);
    });
});

describe('Post Editing', function () {
    it('shows edit form to post owner', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

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

    it('denies access to edit form for non-owners', function () {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)->get(route('posts.edit', $post));

        $response->assertStatus(403);
    });

    it('redirects unauthenticated users from edit form', function () {
        $post = Post::factory()->create();

        $response = $this->get(route('posts.edit', $post));

        $response->assertRedirect(route('login'));
    });
});

describe('Post Updates', function () {
    it('updates post with valid data and correct etag', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $etag = $post->e_tag;

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated content that is long enough to pass validation.',
            'status' => 'published',
            'category' => 'Business',
            'etag' => $etag,
        ];

        $response = $this->actingAs($this->user)->put(route('posts.update', $post), $updateData);

        $response->assertRedirect();

        $post->refresh();
        expect($post->title)->toBe('Updated Title')
            ->and($post->content)->toBe('Updated content that is long enough to pass validation.')
            ->and($post->status)->toBe('published')
            ->and($post->category)->toBe('Business');
    });

    it('rejects update with invalid etag (concurrency control)', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated content that is long enough to pass validation.',
            'status' => 'published',
            'category' => 'Business',
            'etag' => 'invalid_etag',
        ];

        $response = $this->actingAs($this->user)->put(route('posts.update', $post), $updateData);

        $response->assertSessionHasErrors(['etag']);

        // Post should not be updated
        $post->refresh();
        expect($post->title)->not->toBe('Updated Title');
    });

    it('denies update to non-owners', function () {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);
        $etag = $post->e_tag;

        $updateData = [
            'title' => 'Hacked Title',
            'content' => 'Hacked content that is long enough to pass validation.',
            'status' => 'published',
            'etag' => $etag,
        ];

        $response = $this->actingAs($this->user)->put(route('posts.update', $post), $updateData);

        $response->assertStatus(403);
    });

    it('validates update data', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $etag = $post->e_tag;

        $response = $this->actingAs($this->user)->put(route('posts.update', $post), [
            'title' => '', // Invalid
            'content' => '', // Invalid
            'status' => 'invalid', // Invalid
            'etag' => $etag,
        ]);

        $response->assertSessionHasErrors(['title', 'content', 'status']);
    });

    it('requires etag for updates', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated content that is long enough to pass validation.',
            'status' => 'published',
            // Missing etag
        ];

        $response = $this->actingAs($this->user)->put(route('posts.update', $post), $updateData);

        $response->assertSessionHasErrors(['etag']);
    });
});

describe('Post Deletion', function () {
    it('allows post owner to delete post', function () {
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

    it('redirects unauthenticated users', function () {
        $post = Post::factory()->create();

        $response = $this->delete(route('posts.destroy', $post));

        $response->assertRedirect(route('login'));
    });
});

describe('ETag Concurrency Control', function () {
    it('generates consistent etags', function () {
        $post = Post::factory()->create();

        $etag1 = $post->e_tag;
        $etag2 = $post->e_tag;

        expect($etag1)->toBe($etag2);
    });

    it('generates different etags after update', function () {
        $post = Post::factory()->create();
        $originalEtag = $post->e_tag;

        // Simulate time passing and update
        sleep(1);
        $post->touch();
        $post->refresh();

        $newEtag = $post->e_tag;
        expect($newEtag)->not->toBe($originalEtag);
    });

    it('validates etag correctly', function () {
        $post = Post::factory()->create();
        $validEtag = $post->e_tag;
        $invalidEtag = 'invalid_etag';

        expect($post->isEtagValid($validEtag))->toBeTrue()
            ->and($post->isEtagValid($invalidEtag))->toBeFalse();
    });
});

describe('Authorization Policies', function () {
    it('allows any authenticated user to create posts', function () {
        $response = $this->actingAs($this->user)->post(route('posts.store'), [
            'title' => 'New Post',
            'content' => 'Content for the new post that is long enough.',
            'status' => 'draft',
        ]);

        $response->assertRedirect(); // Success
    });

    it('allows users to view their own drafts', function () {
        $draft = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)->get(route('posts.show', $draft));

        $response->assertStatus(200);
    });

    it('denies access to other users drafts', function () {
        $draft = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)->get(route('posts.show', $draft));

        $response->assertStatus(403);
    });

    it('allows anyone to view published posts', function () {
        $publishedPost = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->user)->get(route('posts.show', $publishedPost));

        $response->assertStatus(200);
    });
});

describe('Form Request Validation', function () {
    it('handles custom validation messages', function () {
        $response = $this->actingAs($this->user)->post(route('posts.store'), [
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

    it('prepares data correctly for published posts', function () {
        $postData = [
            'title' => 'Published Post',
            'content' => 'This post will be published.',
            'status' => 'published',
            // No published_at provided
        ];

        $this->actingAs($this->user)->post(route('posts.store'), $postData);

        $post = Post::where('title', 'Published Post')->first();
        expect($post->published_at)->not->toBeNull();
    });

    it('clears published_at for non-published posts', function () {
        $postData = [
            'title' => 'Draft Post',
            'content' => 'This post is a draft.',
            'status' => 'draft',
            'published_at' => now()->toDateTimeString(), // Should be cleared
        ];

        $this->actingAs($this->user)->post(route('posts.store'), $postData);

        $post = Post::where('title', 'Draft Post')->first();
        expect($post->published_at)->toBeNull();
    });
});
