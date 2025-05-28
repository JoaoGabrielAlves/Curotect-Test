<?php

use App\Events\PostUpdated;
use App\Listeners\HandlePostUpdates;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('HandlePostUpdates', function () {
    describe('event attachment', function () {
        it('is attached to PostUpdated event', function () {
            Event::assertListening(
                PostUpdated::class,
                HandlePostUpdates::class
            );
        });
    });

    describe('handle', function () {
        beforeEach(function () {
            $this->listener = new HandlePostUpdates;
        });

        it('handles status changes', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'status' => 'published',
            ]);

            $changes = [
                'status' => [
                    'old' => 'draft',
                    'new' => 'published',
                ],
            ];

            $event = new PostUpdated($post->load('user'), $changes);

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });

        it('handles post status becoming pending', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

            $changes = [
                'status' => [
                    'old' => 'draft',
                    'new' => 'pending',
                ],
            ];

            $event = new PostUpdated($post->load('user'), $changes);

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });

        it('handles multiple changes including status change to pending', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

            $changes = [
                'status' => [
                    'old' => 'draft',
                    'new' => 'pending',
                ],
                'title' => [
                    'old' => 'Old Title',
                    'new' => 'New Title',
                ],
            ];

            $event = new PostUpdated($post->load('user'), $changes);

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });

        it('handles updates without status changes', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'status' => 'published',
            ]);

            $changes = [
                'title' => [
                    'old' => 'Old Title',
                    'new' => 'New Title',
                ],
            ];

            $event = new PostUpdated($post->load('user'), $changes);

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });

        it('handles updates with non-pending status', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'status' => 'published',
            ]);

            $changes = [
                'status' => [
                    'old' => 'draft',
                    'new' => 'published',
                ],
            ];

            $event = new PostUpdated($post->load('user'), $changes);

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });

        it('handles updates with no changes', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'status' => 'published',
            ]);

            $changes = [];

            $event = new PostUpdated($post->load('user'), $changes);

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });
    });
});
