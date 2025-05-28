<?php

use App\Events\PostCreated;
use App\Listeners\SendPostNotifications;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('SendPostNotifications', function () {
    describe('event attachment', function () {
        it('is attached to PostCreated event', function () {
            Event::assertListening(
                PostCreated::class,
                SendPostNotifications::class
            );
        });
    });

    describe('handle', function () {
        beforeEach(function () {
            $this->listener = new SendPostNotifications;
        });

        it('handles published post creation', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'status' => 'published',
                'title' => 'Laravel Best Practices',
            ]);

            $event = new PostCreated($post->load('user'));

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });

        it('handles draft post creation', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'status' => 'draft',
                'title' => 'Draft Post',
            ]);

            $event = new PostCreated($post->load('user'));

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });

        it('handles archived post creation', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'status' => 'archived',
                'title' => 'Archived Post',
            ]);

            $event = new PostCreated($post->load('user'));

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });

        it('handles pending post creation', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'status' => 'pending',
                'title' => 'Pending Post',
            ]);

            $event = new PostCreated($post->load('user'));

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });
    });
});
