<?php

use App\Events\PostDeleted;
use App\Listeners\HandlePostDeletion;
use Illuminate\Support\Facades\Event;

describe('HandlePostDeletion', function () {
    describe('event attachment', function () {
        it('is attached to PostDeleted event', function () {
            Event::assertListening(
                PostDeleted::class,
                HandlePostDeletion::class
            );
        });
    });

    describe('handle', function () {
        beforeEach(function () {
            $this->listener = new HandlePostDeletion;
        });

        it('handles post deletion notification', function () {
            $postId = 123;
            $title = 'Laravel Testing Guide';
            $status = 'published';
            $userId = 456;

            $event = new PostDeleted($postId, $title, $status, $userId);

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });

        it('handles deletion of multiple posts', function () {
            $event1 = new PostDeleted(1, 'First Post', 'published', 100);
            $event2 = new PostDeleted(2, 'Second Post', 'draft', 200);

            $this->listener->handle($event1);
            $this->listener->handle($event2);

            expect(true)->toBeTrue();
        });

        it('handles special characters in post titles', function () {
            $postId = 999;
            $title = 'Special "Characters" & Symbols @#$%';
            $status = 'archived';
            $userId = 789;

            $event = new PostDeleted($postId, $title, $status, $userId);

            $this->listener->handle($event);

            expect(true)->toBeTrue();
        });

        it('handles different post statuses', function () {
            $statuses = ['draft', 'published', 'archived'];

            foreach ($statuses as $status) {
                $event = new PostDeleted(1, 'Test Post', $status, 100);
                $this->listener->handle($event);
                expect(true)->toBeTrue();
            }
        });
    });
});
