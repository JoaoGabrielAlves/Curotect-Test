<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->post = Post::factory()->create([
        'status' => 'published',
        'user_id' => $this->user->id,
    ]);
});

describe('Comment Creation', function () {
    it('allows authenticated users to add comments to published posts', function () {
        $commentData = [
            'content' => 'This is a test comment.',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->post), $commentData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment added successfully!');

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a test comment.',
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'status' => 'approved',
            'parent_id' => null,
        ]);
    });

    it('returns new comment ID in session for scroll functionality', function () {
        $commentData = [
            'content' => 'This comment should return its ID.',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->post), $commentData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment added successfully!');
        $response->assertSessionHas('new_comment_id');

        // Verify the comment was created and the ID matches
        $comment = Comment::where('content', 'This comment should return its ID.')->first();

        expect($comment)->not->toBeNull()
            ->and(session('new_comment_id'))->toBe($comment->id);
    });

    it('clears post cache when a comment is added', function () {
        // The new simplified cache system uses just the post ID
        $cacheKey = "post.{$this->post->id}";

        // Simulate cached post data
        Cache::put($cacheKey, $this->post, 3600);
        expect(Cache::has($cacheKey))->toBeTrue();

        $commentData = [
            'content' => 'This comment should clear the cache.',
        ];

        $this->actingAs($this->user)
            ->post(route('comments.store', $this->post), $commentData);

        // Cache should be cleared after comment creation
        expect(Cache::has($cacheKey))->toBeFalse();
    });

    it('prevents unauthenticated users from adding comments', function () {
        $commentData = [
            'content' => 'This is a test comment.',
        ];

        $response = $this->post(route('comments.store', $this->post), $commentData);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('comments', [
            'content' => 'This is a test comment.',
        ]);
    });

    it('validates comment content is required', function () {
        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->post), [
                'content' => '',
            ]);

        $response->assertSessionHasErrors(['content']);
        $this->assertDatabaseMissing('comments', [
            'post_id' => $this->post->id,
        ]);
    });

    it('validates comment content minimum length', function () {
        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->post), [
                'content' => 'Hi',
            ]);

        $response->assertSessionHasErrors(['content']);
        $this->assertDatabaseMissing('comments', [
            'content' => 'Hi',
        ]);
    });

    it('validates comment content maximum length', function () {
        $longContent = str_repeat('a', 1001);

        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->post), [
                'content' => $longContent,
            ]);

        $response->assertSessionHasErrors(['content']);
        $this->assertDatabaseMissing('comments', [
            'content' => $longContent,
        ]);
    });

    it('prevents commenting on draft posts by other users', function () {
        $draftPost = Post::factory()->create([
            'status' => 'draft',
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $draftPost), [
                'content' => 'This should not work.',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('comments', [
            'content' => 'This should not work.',
        ]);
    });

    it('allows post owners to comment on their own draft posts', function () {
        $draftPost = Post::factory()->create([
            'status' => 'draft',
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $draftPost), [
                'content' => 'Owner commenting on own draft.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment added successfully!');

        $this->assertDatabaseHas('comments', [
            'content' => 'Owner commenting on own draft.',
            'post_id' => $draftPost->id,
            'user_id' => $this->user->id,
        ]);
    });

    it('allows adding replies to existing comments', function () {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->post), [
                'content' => 'This is a reply.',
                'parent_id' => $parentComment->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment added successfully!');

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a reply.',
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => $parentComment->id,
        ]);
    });

    it('validates parent comment exists when replying', function () {
        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->post), [
                'content' => 'This is a reply to non-existent comment.',
                'parent_id' => 999999,
            ]);

        $response->assertSessionHasErrors(['parent_id']);
        $this->assertDatabaseMissing('comments', [
            'content' => 'This is a reply to non-existent comment.',
        ]);
    });
});
