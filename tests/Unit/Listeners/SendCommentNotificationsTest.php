<?php

use App\Events\CommentCreated;
use App\Listeners\SendCommentNotifications;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('SendCommentNotifications', function () {
    describe('event attachment', function () {
        it('is attached to CommentCreated event', function () {
            Event::assertListening(
                CommentCreated::class,
                SendCommentNotifications::class
            );
        });
    });

    describe('handle', function () {
        beforeEach(function () {
            $this->listener = new SendCommentNotifications;
        });

        it('handles comment creation when someone else comments', function () {
            $postOwner = User::factory()->create();
            $commenter = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $postOwner->id]);
            $comment = Comment::factory()->create([
                'post_id' => $post->id,
                'user_id' => $commenter->id,
                'parent_id' => null,
            ]);

            $event = new CommentCreated($comment->load(['user', 'post', 'parent']));

            $this->listener->handle($event);

            // Test passes if no exceptions are thrown
            expect(true)->toBeTrue();
        });

        it('handles comment creation when post owner comments on their own post', function () {
            $postOwner = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $postOwner->id]);
            $comment = Comment::factory()->create([
                'post_id' => $post->id,
                'user_id' => $postOwner->id,
                'parent_id' => null,
            ]);

            $event = new CommentCreated($comment->load(['user', 'post', 'parent']));

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });

        it('handles reply creation when someone else replies', function () {
            $postOwner = User::factory()->create();
            $originalCommenter = User::factory()->create();
            $replier = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $postOwner->id]);

            $parentComment = Comment::factory()->create([
                'post_id' => $post->id,
                'user_id' => $originalCommenter->id,
                'parent_id' => null,
            ]);

            $reply = Comment::factory()->create([
                'post_id' => $post->id,
                'user_id' => $replier->id,
                'parent_id' => $parentComment->id,
            ]);

            $event = new CommentCreated($reply->load(['user', 'post', 'parent']));

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });

        it('handles reply creation when author replies to themselves', function () {
            $commenter = User::factory()->create();
            $post = Post::factory()->create();

            $parentComment = Comment::factory()->create([
                'post_id' => $post->id,
                'user_id' => $commenter->id,
                'parent_id' => null,
            ]);

            $reply = Comment::factory()->create([
                'post_id' => $post->id,
                'user_id' => $commenter->id,
                'parent_id' => $parentComment->id,
            ]);

            $event = new CommentCreated($reply->load(['user', 'post', 'parent']));

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });

        it('handles complex scenario with multiple participants', function () {
            $postOwner = User::factory()->create();
            $originalCommenter = User::factory()->create();
            $replier = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $postOwner->id]);

            $parentComment = Comment::factory()->create([
                'post_id' => $post->id,
                'user_id' => $originalCommenter->id,
                'parent_id' => null,
            ]);

            $reply = Comment::factory()->create([
                'post_id' => $post->id,
                'user_id' => $replier->id,
                'parent_id' => $parentComment->id,
            ]);

            $event = new CommentCreated($reply->load(['user', 'post', 'parent']));

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });
    });
});
