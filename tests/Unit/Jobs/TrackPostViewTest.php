<?php

use App\Contracts\PostRepositoryInterface;
use App\Events\PostViewed;
use App\Jobs\TrackPostView;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('TrackPostView', function () {
    describe('construction', function () {
        it('creates job with post id only', function () {
            $job = new TrackPostView(123);

            expect($job->postId)->toBe(123)
                ->and($job->userId)->toBeNull()
                ->and($job->ipAddress)->toBeNull()
                ->and($job->userAgent)->toBeNull();
        });

        it('creates job with all parameters', function () {
            $job = new TrackPostView(
                postId: 456,
                userId: 789,
                ipAddress: '192.168.1.1',
                userAgent: 'Mozilla/5.0 (compatible test)'
            );

            expect($job->postId)->toBe(456)
                ->and($job->userId)->toBe(789)
                ->and($job->ipAddress)->toBe('192.168.1.1')
                ->and($job->userAgent)->toBe('Mozilla/5.0 (compatible test)');
        });
    });

    describe('handle', function () {
        beforeEach(function () {
            $this->postRepository = app(PostRepositoryInterface::class);
        });

        it('tracks view for existing post', function () {
            $post = Post::factory()->create(['views_count' => 10]);
            $job = new TrackPostView($post->id, 123, '192.168.1.1', 'Test Agent');

            $job->handle($this->postRepository);

            $post->refresh();
            expect($post->views_count)->toBe(11);

            Event::assertDispatched(PostViewed::class, function ($event) use ($post) {
                return $event->post->id === $post->id;
            });
        });

        it('tracks anonymous view without user id', function () {
            $post = Post::factory()->create(['views_count' => 5]);
            $job = new TrackPostView($post->id, null, '10.0.0.1', 'Anonymous Browser');

            $job->handle($this->postRepository);

            $post->refresh();
            expect($post->views_count)->toBe(6);

            Event::assertDispatched(PostViewed::class, function ($event) use ($post) {
                return $event->post->id === $post->id;
            });
        });

        it('handles non-existent post gracefully', function () {
            $nonExistentId = 99999;
            $job = new TrackPostView($nonExistentId, 123);

            $job->handle($this->postRepository);

            Event::assertNotDispatched(PostViewed::class);
        });

        it('tracks view with minimal data', function () {
            $post = Post::factory()->create(['views_count' => 0]);
            $job = new TrackPostView($post->id);

            $job->handle($this->postRepository);

            $post->refresh();
            expect($post->views_count)->toBe(1);

            Event::assertDispatched(PostViewed::class);
        });

        it('works with authenticated user view', function () {
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id, 'views_count' => 25]);
            $job = new TrackPostView($post->id, $user->id, '127.0.0.1', 'Chrome/Latest');

            $job->handle($this->postRepository);

            $post->refresh();
            expect($post->views_count)->toBe(26);

            Event::assertDispatched(PostViewed::class, function ($event) use ($post) {
                return $event->post->id === $post->id;
            });
        });

        it('increments views multiple times correctly', function () {
            $post = Post::factory()->create(['views_count' => 100]);

            $job1 = new TrackPostView($post->id, 1);
            $job2 = new TrackPostView($post->id, 2);
            $job3 = new TrackPostView($post->id, null);

            $job1->handle($this->postRepository);
            $post->refresh();
            expect($post->views_count)->toBe(101);

            $job2->handle($this->postRepository);
            $post->refresh();
            expect($post->views_count)->toBe(102);

            $job3->handle($this->postRepository);
            $post->refresh();
            expect($post->views_count)->toBe(103);
        });
    });

    describe('queue configuration', function () {
        it('has correct retry and timeout settings', function () {
            $job = new TrackPostView(123);

            expect($job->tries)->toBe(3)
                ->and($job->timeout)->toBe(30);
        });
    });

    describe('failed', function () {
        it('handles job failure', function () {
            $job = new TrackPostView(123, 456);
            $exception = new \Exception('Test failure');

            // This tests that failed() method doesn't throw exceptions
            $job->failed($exception);

            expect(true)->toBeTrue();
        });
    });

    describe('edge cases', function () {
        beforeEach(function () {
            $this->postRepository = app(PostRepositoryInterface::class);
        });

        it('handles special characters in user agent', function () {
            $post = Post::factory()->create(['views_count' => 50]);
            $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) "Special & Characters"';
            $job = new TrackPostView($post->id, null, null, $userAgent);

            $job->handle($this->postRepository);

            $post->refresh();
            expect($post->views_count)->toBe(51)
                ->and($job->userAgent)->toBe($userAgent);
        });

        it('handles IPv6 addresses', function () {
            $post = Post::factory()->create(['views_count' => 75]);
            $ipv6Address = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
            $job = new TrackPostView($post->id, null, $ipv6Address);

            $job->handle($this->postRepository);

            $post->refresh();
            expect($post->views_count)->toBe(76)
                ->and($job->ipAddress)->toBe($ipv6Address);
        });

        it('handles large view counts', function () {
            $post = Post::factory()->create(['views_count' => 999999]);
            $job = new TrackPostView($post->id);

            $job->handle($this->postRepository);

            $post->refresh();
            expect($post->views_count)->toBe(1000000);
        });

        it('works with different post statuses', function () {
            $draftPost = Post::factory()->create(['status' => 'draft', 'views_count' => 0]);
            $publishedPost = Post::factory()->create(['status' => 'published', 'views_count' => 0]);
            $archivedPost = Post::factory()->create(['status' => 'archived', 'views_count' => 0]);

            $job1 = new TrackPostView($draftPost->id);
            $job2 = new TrackPostView($publishedPost->id);
            $job3 = new TrackPostView($archivedPost->id);

            $job1->handle($this->postRepository);
            $job2->handle($this->postRepository);
            $job3->handle($this->postRepository);

            $draftPost->refresh();
            $publishedPost->refresh();
            $archivedPost->refresh();

            expect($draftPost->views_count)->toBe(1)
                ->and($publishedPost->views_count)->toBe(1)
                ->and($archivedPost->views_count)->toBe(1);
        });
    });
});
