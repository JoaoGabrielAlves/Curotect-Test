<?php

use App\Events\PostCreated;
use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

describe('PostCreated', function () {
    describe('construction', function () {
        it('creates event with post', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);

            $event = new PostCreated($post);

            expect($event->post)->toBe($post);
        });
    });

    describe('broadcasting', function () {
        beforeEach(function () {
            $this->user = User::factory()->create();
            $this->post = Post::factory()->create([
                'user_id' => $this->user->id,
                'title' => 'Laravel Testing Guide',
                'status' => 'published',
                'category' => 'tutorial',
                'views_count' => 150,
            ]);
            $this->post->load('user');
            $this->event = new PostCreated($this->post);
        });

        it('broadcasts on correct channels', function () {
            $channels = $this->event->broadcastOn();

            expect($channels)->toHaveCount(2)
                ->and($channels[0])->toBeInstanceOf(Channel::class)
                ->and($channels[0]->name)->toBe('posts')
                ->and($channels[1])->toBeInstanceOf(PrivateChannel::class)
                ->and($channels[1]->name)->toBe('private-user.'.$this->user->id);
        });

        it('has correct broadcast name', function () {
            expect($this->event->broadcastAs())->toBe('post.created');
        });

        it('broadcasts with correct data', function () {
            $data = $this->event->broadcastWith();

            expect($data)->toHaveKeys([
                'id', 'title', 'status', 'category', 'views_count', 'created_at', 'user',
            ])
                ->and($data['id'])->toBe($this->post->id)
                ->and($data['title'])->toBe('Laravel Testing Guide')
                ->and($data['status'])->toBe('published')
                ->and($data['category'])->toBe('tutorial')
                ->and($data['views_count'])->toBe(150)
                ->and($data['created_at'])->toEqual($this->post->created_at)
                ->and($data['user'])->toBe([
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ]);
        });
    });

    describe('different post statuses', function () {
        it('handles draft posts', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'status' => 'draft',
            ]);
            $post->load('user');

            $event = new PostCreated($post);
            $data = $event->broadcastWith();

            expect($data['status'])->toBe('draft');
        });

        it('handles pending posts', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);
            $post->load('user');

            $event = new PostCreated($post);
            $data = $event->broadcastWith();

            expect($data['status'])->toBe('pending');
        });

        it('handles archived posts', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'status' => 'archived',
            ]);
            $post->load('user');

            $event = new PostCreated($post);
            $data = $event->broadcastWith();

            expect($data['status'])->toBe('archived');
        });
    });
});
