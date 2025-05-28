<?php

use App\Events\PostDeleted;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

describe('PostDeleted', function () {
    describe('construction', function () {
        it('creates event with post details', function () {
            $postId = 123;
            $title = 'Laravel Testing Guide';
            $status = 'published';
            $userId = 456;

            $event = new PostDeleted($postId, $title, $status, $userId);

            expect($event->postId)->toBe($postId)
                ->and($event->title)->toBe($title)
                ->and($event->status)->toBe($status)
                ->and($event->userId)->toBe($userId);
        });
    });

    describe('broadcasting', function () {
        beforeEach(function () {
            $this->postId = 789;
            $this->title = 'Vue.js Components Guide';
            $this->status = 'published';
            $this->userId = 101;
            $this->event = new PostDeleted($this->postId, $this->title, $this->status, $this->userId);
        });

        it('broadcasts on correct channels', function () {
            $channels = $this->event->broadcastOn();

            expect($channels)->toHaveCount(2)
                ->and($channels[0])->toBeInstanceOf(Channel::class)
                ->and($channels[0]->name)->toBe('posts')
                ->and($channels[1])->toBeInstanceOf(PrivateChannel::class)
                ->and($channels[1]->name)->toBe('private-user.'.$this->userId);
        });

        it('has correct broadcast name', function () {
            expect($this->event->broadcastAs())->toBe('post.deleted');
        });

        it('broadcasts with correct data', function () {
            $data = $this->event->broadcastWith();

            expect($data)->toHaveKeys(['id', 'title', 'status', 'user_id'])
                ->and($data['id'])->toBe($this->postId)
                ->and($data['title'])->toBe($this->title)
                ->and($data['status'])->toBe($this->status)
                ->and($data['user_id'])->toBe($this->userId);
        });
    });

    describe('title scenarios', function () {
        it('handles empty title', function () {
            $event = new PostDeleted(1, '', 'draft', 100);
            $data = $event->broadcastWith();

            expect($data['title'])->toBe('');
        });

        it('handles special characters in title', function () {
            $title = 'Special "Characters" & Symbols @#$%';
            $event = new PostDeleted(2, $title, 'published', 200);
            $data = $event->broadcastWith();

            expect($data['title'])->toBe($title);
        });

        it('handles long title', function () {
            $title = str_repeat('Long Title Content ', 20);
            $event = new PostDeleted(3, $title, 'archived', 300);
            $data = $event->broadcastWith();

            expect($data['title'])->toBe($title);
        });

        it('handles unicode characters in title', function () {
            $title = 'Título en Español with émojis 🚀 and 中文字符';
            $event = new PostDeleted(4, $title, 'published', 400);
            $data = $event->broadcastWith();

            expect($data['title'])->toBe($title);
        });
    });

    describe('status scenarios', function () {
        it('handles published status', function () {
            $event = new PostDeleted(1, 'Test Post', 'published', 100);
            $data = $event->broadcastWith();

            expect($data['status'])->toBe('published');
        });

        it('handles draft status', function () {
            $event = new PostDeleted(2, 'Test Post', 'draft', 100);
            $data = $event->broadcastWith();

            expect($data['status'])->toBe('draft');
        });

        it('handles archived status', function () {
            $event = new PostDeleted(3, 'Test Post', 'archived', 100);
            $data = $event->broadcastWith();

            expect($data['status'])->toBe('archived');
        });
    });

    describe('id scenarios', function () {
        it('handles large post id', function () {
            $postId = 999999999;
            $event = new PostDeleted($postId, 'Test', 'published', 100);

            expect($event->postId)->toBe($postId)
                ->and($event->broadcastWith()['id'])->toBe($postId);
        });

        it('handles large user id', function () {
            $userId = 888888888;
            $event = new PostDeleted(100, 'Test', 'draft', $userId);

            expect($event->userId)->toBe($userId)
                ->and($event->broadcastWith()['user_id'])->toBe($userId);
        });
    });
});
