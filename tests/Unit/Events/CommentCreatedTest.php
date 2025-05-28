<?php

use App\Events\CommentCreated;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

describe('CommentCreated', function () {
    describe('construction', function () {
        it('creates event with comment', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create();
            $comment = Comment::factory()->create([
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]);

            $event = new CommentCreated($comment);

            expect($event->comment)->toBe($comment);
        });
    });

    describe('broadcasting', function () {
        beforeEach(function () {
            $this->user = User::factory()->create();
            $this->postOwner = User::factory()->create();
            $this->post = Post::factory()->create(['user_id' => $this->postOwner->id]);
            $this->comment = Comment::factory()->create([
                'post_id' => $this->post->id,
                'user_id' => $this->user->id,
            ]);
            $this->comment->load(['user', 'post']);
            $this->event = new CommentCreated($this->comment);
        });

        it('broadcasts on correct channels', function () {
            $channels = $this->event->broadcastOn();

            expect($channels)->toHaveCount(3)
                ->and($channels[0])->toBeInstanceOf(Channel::class)
                ->and($channels[0]->name)->toBe('comments')
                ->and($channels[1])->toBeInstanceOf(Channel::class)
                ->and($channels[1]->name)->toBe('post.'.$this->post->id)
                ->and($channels[2])->toBeInstanceOf(PrivateChannel::class)
                ->and($channels[2]->name)->toBe('private-user.'.$this->postOwner->id);
        });

        it('has correct broadcast name', function () {
            expect($this->event->broadcastAs())->toBe('comment.created');
        });

        it('broadcasts with correct data', function () {
            $data = $this->event->broadcastWith();

            expect($data)->toHaveKeys([
                'id', 'content', 'post_id', 'parent_id', 'created_at', 'user',
            ])
                ->and($data['id'])->toBe($this->comment->id)
                ->and($data['content'])->toBe($this->comment->content)
                ->and($data['post_id'])->toBe($this->comment->post_id)
                ->and($data['parent_id'])->toBe($this->comment->parent_id)
                ->and($data['created_at'])->toEqual($this->comment->created_at)
                ->and($data['user'])->toBe([
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ]);
        });
    });

    describe('reply scenarios', function () {
        it('handles reply comments correctly', function () {
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

            $reply->load(['user', 'post']);
            $event = new CommentCreated($reply);

            $data = $event->broadcastWith();

            expect($data['parent_id'])->toBe($parentComment->id)
                ->and($data['user']['id'])->toBe($replier->id);
        });
    });
});
