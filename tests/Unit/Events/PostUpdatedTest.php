<?php

use App\Events\PostUpdated;
use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

describe('PostUpdated', function () {
    describe('construction', function () {
        it('creates event with post and no changes', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);

            $event = new PostUpdated($post);

            expect($event->post)->toBe($post)
                ->and($event->changes)->toBe([]);
        });

        it('creates event with post and changes', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            $changes = [
                'status' => ['old' => 'draft', 'new' => 'published'],
                'title' => ['old' => 'Old Title', 'new' => 'New Title'],
            ];

            $event = new PostUpdated($post, $changes);

            expect($event->post)->toBe($post)
                ->and($event->changes)->toBe($changes);
        });
    });

    describe('broadcasting', function () {
        beforeEach(function () {
            $this->user = User::factory()->create();
            $this->post = Post::factory()->create([
                'user_id' => $this->user->id,
                'title' => 'Updated Laravel Guide',
                'status' => 'published',
                'category' => 'tutorial',
                'views_count' => 200,
            ]);
            $this->post->load('user');
            $this->changes = [
                'status' => ['old' => 'draft', 'new' => 'published'],
                'title' => ['old' => 'Laravel Guide', 'new' => 'Updated Laravel Guide'],
            ];
            $this->event = new PostUpdated($this->post, $this->changes);
        });

        it('broadcasts on correct channels', function () {
            $channels = $this->event->broadcastOn();

            expect($channels)->toHaveCount(3)
                ->and($channels[0])->toBeInstanceOf(Channel::class)
                ->and($channels[0]->name)->toBe('posts')
                ->and($channels[1])->toBeInstanceOf(PrivateChannel::class)
                ->and($channels[1]->name)->toBe('private-user.'.$this->user->id)
                ->and($channels[2])->toBeInstanceOf(Channel::class)
                ->and($channels[2]->name)->toBe('post.'.$this->post->id);
        });

        it('has correct broadcast name', function () {
            expect($this->event->broadcastAs())->toBe('post.updated');
        });

        it('broadcasts with correct data', function () {
            $data = $this->event->broadcastWith();

            expect($data)->toHaveKeys([
                'id', 'title', 'status', 'category', 'views_count', 'updated_at', 'etag', 'changes', 'user',
            ])
                ->and($data['id'])->toBe($this->post->id)
                ->and($data['title'])->toBe('Updated Laravel Guide')
                ->and($data['status'])->toBe('published')
                ->and($data['category'])->toBe('tutorial')
                ->and($data['views_count'])->toBe(200)
                ->and($data['updated_at'])->toEqual($this->post->updated_at)
                ->and($data['etag'])->toBe($this->post->e_tag)
                ->and($data['changes'])->toBe($this->changes)
                ->and($data['user'])->toBe([
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ]);
        });
    });

    describe('change scenarios', function () {
        it('handles status change only', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            $post->load('user');
            $changes = ['status' => ['old' => 'draft', 'new' => 'published']];

            $event = new PostUpdated($post, $changes);
            $data = $event->broadcastWith();

            expect($data['changes'])->toBe($changes)
                ->and($data['changes'])->toHaveKey('status')
                ->and($data['changes'])->not->toHaveKey('title');
        });

        it('handles multiple changes', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            $post->load('user');
            $changes = [
                'status' => ['old' => 'draft', 'new' => 'published'],
                'title' => ['old' => 'Old', 'new' => 'New'],
                'category' => ['old' => 'general', 'new' => 'tutorial'],
            ];

            $event = new PostUpdated($post, $changes);
            $data = $event->broadcastWith();

            expect($data['changes'])->toBe($changes)
                ->and($data['changes'])->toHaveCount(3);
        });

        it('handles empty changes', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            $post->load('user');

            $event = new PostUpdated($post, []);
            $data = $event->broadcastWith();

            expect($data['changes'])->toBe([])
                ->and($data['changes'])->toBeEmpty();
        });
    });

    describe('etag handling', function () {
        it('includes etag in broadcast data', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            $post->load('user');

            $event = new PostUpdated($post);
            $data = $event->broadcastWith();

            expect($data['etag'])->toBe($post->e_tag);
        });

        it('handles etag attribute properly', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            $post->load('user');

            $event = new PostUpdated($post);
            $data = $event->broadcastWith();

            expect($data)->toHaveKey('etag')
                ->and($data['etag'])->not->toBeNull();
        });
    });
});
