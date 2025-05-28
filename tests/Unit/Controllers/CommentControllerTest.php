<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->post = Post::factory()->published()->create();
});

describe('store', function () {
    it('creates comment successfully', function () {
        $commentData = [
            'content' => 'This is a test comment with sufficient length to meet validation requirements.',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('comments.store', [
                'post' => $this->post,
            ]), $commentData);

        $response->assertRedirect(route('posts.show', $this->post));

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a test comment with sufficient length to meet validation requirements.',
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'parent_id' => null,
        ]);
    });

    it('creates reply to existing comment', function () {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->otherUser->id,
        ]);

        $replyData = [
            'content' => 'This is a reply to the parent comment with sufficient length.',
            'parent_id' => $parentComment->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->post), $replyData);

        $response->assertRedirect(route('posts.show', $this->post));

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a reply to the parent comment with sufficient length.',
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'parent_id' => $parentComment->id,
        ]);
    });

    it('validates required fields', function () {
        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $this->post), []);

        $response->assertSessionHasErrors(['content']);
    });

    it('validates content length', function () {
        $response = $this->actingAs($this->user)->post(route('comments.store', $this->post), [
            'content' => 'ab', // Too short (min 3)
        ]);

        $response->assertSessionHasErrors(['content']);
    });

    it('validates content maximum length', function () {
        $response = $this->actingAs($this->user)->post(route('comments.store', $this->post), [
            'content' => str_repeat('a', 1001), // Too long (max 1000)
        ]);

        $response->assertSessionHasErrors(['content']);
    });

    it('validates parent comment exists and belongs to same post', function () {
        $otherPost = Post::factory()->create();
        $otherComment = Comment::factory()->create(['post_id' => $otherPost->id]);

        $response = $this->actingAs($this->user)->post(route('comments.store', $this->post), [
            'content' => 'This is a reply to wrong post comment',
            'parent_id' => $otherComment->id, // Comment from different post
        ]);

        $response->assertSessionHasErrors(['parent_id']);
    });

    it('validates parent_id must exist in comments table', function () {
        $response = $this->actingAs($this->user)->post(route('comments.store', $this->post), [
            'content' => 'This is a test comment',
            'parent_id' => 99999, // Non-existent comment ID
        ]);

        $response->assertSessionHasErrors(['parent_id']);
    });

    it('validates parent_id accepts negative numbers as invalid', function () {
        $response = $this->actingAs($this->user)->post(route('comments.store', $this->post), [
            'content' => 'This is a test comment',
            'parent_id' => -1, // Negative number should fail exists validation
        ]);

        $response->assertSessionHasErrors(['parent_id']);
    });

    it('requires authentication', function () {
        $response = $this->post(route('comments.store', $this->post), [
            'content' => 'Test comment',
        ]);

        $response->assertRedirect(route('login'));
    });

    it('prevents commenting on non-existent posts', function () {
        $response = $this->actingAs($this->user)
            ->post(route('comments.store', 999999), [
                'content' => 'This is a test comment with sufficient length.',
            ]);

        $response->assertStatus(404);
    });
});

describe('update', function () {
    it('updates comment successfully', function () {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
        ]);

        $updateData = [
            'content' => 'Updated comment content with sufficient length for validation requirements.',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('comments.update', $comment), $updateData);

        $response->assertRedirect(route('posts.show', $this->post));

        expect($comment->fresh()->content)
            ->toBe('Updated comment content with sufficient length for validation requirements.');
    });

    it('denies update to non-owners', function () {
        $comment = Comment::factory()->create([
            'user_id' => $this->otherUser->id,
            'post_id' => $this->post->id,
        ]);

        $updateData = [
            'content' => 'Hacked comment content with sufficient length.',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('comments.update', $comment), $updateData);

        $response->assertStatus(403);
    });

    it('validates content on update', function () {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('comments.update', $comment), [
                'content' => 'Hi', // Only 2 characters, should fail min:3 validation
            ]);

        $response->assertSessionHasErrors(['content']);
    });

    it('requires authentication', function () {
        $comment = Comment::factory()->create();

        $response = $this->put(route('comments.update', $comment), [
            'content' => 'Updated content',
        ]);

        $response->assertRedirect(route('login'));
    });

    it('handles non-existent comments', function () {
        $response = $this->actingAs($this->user)
            ->put(route('comments.update', 999999), [
                'content' => 'Updated content with sufficient length.',
            ]);

        $response->assertStatus(404);
    });
});

describe('destroy', function () {
    it('deletes comment successfully', function () {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('comments.destroy', $comment));

        $response->assertRedirect(route('posts.show', $this->post));

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    });

    it('deletes comment with replies (cascade)', function () {
        $parentComment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
        ]);

        $reply = Comment::factory()->create([
            'user_id' => $this->otherUser->id,
            'post_id' => $this->post->id,
            'parent_id' => $parentComment->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('comments.destroy', $parentComment));

        $response->assertRedirect(route('posts.show', $this->post));

        // Both parent and reply should be deleted
        $this->assertDatabaseMissing('comments', ['id' => $parentComment->id]);
        $this->assertDatabaseMissing('comments', ['id' => $reply->id]);
    });

    it('denies deletion to non-owners', function () {
        $comment = Comment::factory()->create([
            'user_id' => $this->otherUser->id,
            'post_id' => $this->post->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('comments.destroy', $comment));

        $response->assertStatus(403);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
        ]);
    });

    it('requires authentication', function () {
        $comment = Comment::factory()->create();

        $response = $this->delete(route('comments.destroy', $comment));

        $response->assertRedirect(route('login'));
    });

    it('handles non-existent comments', function () {
        $response = $this->actingAs($this->user)
            ->delete(route('comments.destroy', 999999));

        $response->assertStatus(404);
    });
});

describe('authorization', function () {
    it('allows post owner to manage any comment on their post', function () {
        $postOwner = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $postOwner->id, 'status' => 'published']);

        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $post->id,
        ]);

        // Post owner should be able to delete any comment on their post
        $response = $this->actingAs($postOwner)
            ->delete(route('comments.destroy', $comment));

        $response->assertRedirect(route('posts.show', $post));
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    });

    it('prevents commenting on draft posts by non-owners', function () {
        $draftPost = Post::factory()->create([
            'status' => 'draft',
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $draftPost), [
                'content' => 'This is a test comment with sufficient length.',
            ]);

        $response->assertStatus(403);
    });
});
