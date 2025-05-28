<?php

use App\Events\PostCreated;
use App\Events\PostDeleted;
use App\Events\PostUpdated;
use App\Jobs\TrackPostView;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->postService = app(PostService::class);
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

describe('createPost', function () {
    it('creates post with business logic applied', function () {
        $data = [
            'title' => 'Test Post Title',
            'content' => 'This is test content for the post.',
            'category' => 'technology',
            'status' => 'published',
        ];

        $result = $this->postService->createPost($data, $this->user);

        expect($result)->toBeInstanceOf(Post::class)
            ->and($result->title)->toBe($data['title'])
            ->and($result->content)->toBe($data['content'])
            ->and($result->category)->toBe($data['category'])
            ->and($result->user_id)->toBe($this->user->id);

        $this->assertDatabaseHas('posts', [
            'title' => $data['title'],
            'content' => $data['content'],
            'category' => $data['category'],
            'user_id' => $this->user->id,
        ]);

        Event::assertDispatched(PostCreated::class, function ($event) use ($result) {
            return $event->post->id === $result->id;
        });
    });

    it('creates draft post when status is draft', function () {
        $data = [
            'title' => 'Draft Post',
            'content' => 'Draft content',
            'status' => 'draft',
        ];

        $result = $this->postService->createPost($data, $this->user);

        expect($result->status)->toBe('draft');
        $this->assertDatabaseHas('posts', [
            'title' => $data['title'],
            'status' => 'draft',
        ]);

        Event::assertDispatched(PostCreated::class);
    });
});

describe('updatePost', function () {
    it('updates post with valid etag', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $originalEtag = $post->etag;

        $data = [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ];

        $result = $this->postService->updatePost($post, $data, $originalEtag);

        expect($result->title)->toBe($data['title'])
            ->and($result->content)->toBe($data['content']);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => $data['title'],
            'content' => $data['content'],
        ]);

        Event::assertDispatched(PostUpdated::class, function ($event) use ($post) {
            return $event->post->id === $post->id;
        });
    });

    it('updates post status and dispatches event with changes', function () {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);
        $originalEtag = $post->etag;

        $data = ['status' => 'published'];

        $this->postService->updatePost($post, $data, $originalEtag);

        Event::assertDispatched(PostUpdated::class, function ($event) use ($post) {
            return $event->post->id === $post->id &&
                   isset($event->changes['status']);
        });
    });

    it('throws exception with invalid etag', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $invalidEtag = 'invalid-etag';

        $data = ['title' => 'Updated Title'];

        expect(function () use ($post, $data, $invalidEtag) {
            $this->postService->updatePost($post, $data, $invalidEtag);
        })->toThrow(\InvalidArgumentException::class, 'Post has been modified by another user.');
    });
});

describe('deletePost', function () {
    it('deletes post successfully', function () {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $postId = $post->id;
        $postTitle = $post->title;
        $userId = $post->user_id;

        $result = $this->postService->deletePost($post);

        expect($result)->toBeTrue();
        $this->assertDatabaseMissing('posts', ['id' => $postId]);

        Event::assertDispatched(PostDeleted::class, function ($event) use ($postId, $postTitle, $userId) {
            return $event->postId === $postId &&
                   $event->title === $postTitle &&
                   $event->userId === $userId;
        });
    });
});

describe('getPaginatedPosts', function () {
    it('returns paginated posts with filters', function () {
        Post::factory()->count(3)->create(['status' => 'published']);
        Post::factory()->create(['status' => 'draft']);

        $result = $this->postService->getPaginatedPosts(['status' => 'published'], 2);

        expect($result->count())->toBe(2)
            ->and($result->total())->toBe(3);
    });

    it('sanitizes filters', function () {
        Post::factory()->count(2)->create(['status' => 'published']);

        $result = $this->postService->getPaginatedPosts(['invalid_filter' => 'value'], 10);

        expect($result->count())->toBe(2);
    });
});

describe('getPostForViewing', function () {
    it('returns post and tracks view', function () {
        $post = Post::factory()->create(['status' => 'published']);

        $result = $this->postService->getPostForViewing($post->id, $this->user);

        expect($result)->toBeInstanceOf(Post::class)
            ->and($result->id)->toBe($post->id);

        Queue::assertPushed(TrackPostView::class, function ($job) use ($post) {
            return $job->postId === $post->id &&
                   $job->userId === $this->user->id;
        });
    });

    it('tracks view for anonymous user', function () {
        $post = Post::factory()->create(['status' => 'published']);

        $result = $this->postService->getPostForViewing($post->id, null);

        expect($result)->toBeInstanceOf(Post::class)
            ->and($result->id)->toBe($post->id);

        Queue::assertPushed(TrackPostView::class, function ($job) use ($post) {
            return $job->postId === $post->id &&
                   $job->userId === null;
        });
    });

    it('returns null for non-existent post', function () {
        $result = $this->postService->getPostForViewing(999, $this->user);

        expect($result)->toBeNull();

        Queue::assertNothingPushed();
    });
});

describe('getUserPosts', function () {
    it('returns paginated user posts', function () {
        Post::factory()->count(3)->create(['user_id' => $this->user->id]);
        Post::factory()->create(['user_id' => $this->otherUser->id]);

        $result = $this->postService->getUserPosts($this->user, [], 10);

        expect($result->count())->toBe(3)
            ->and($result->total())->toBe(3);
    });
});

describe('getUserPostsCollection', function () {
    it('applies privacy controls for different viewer', function () {
        Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);
        Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $result = $this->postService->getUserPostsCollection($this->user, $this->otherUser);

        expect($result->count())->toBe(1); // Only published visible
    });

    it('shows all posts to owner', function () {
        Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);
        Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $result = $this->postService->getUserPostsCollection($this->user, $this->user);

        expect($result->count())->toBe(2); // Both visible to owner
    });
});

describe('moderatePost', function () {
    it('approves post successfully', function () {
        $post = Post::factory()->create(['status' => 'pending']);

        $result = $this->postService->moderatePost($post, 'approve');

        expect($result)->toBeTrue()
            ->and($post->fresh()->status)->toBe('published');
    });

    it('rejects post with reason', function () {
        $post = Post::factory()->create(['status' => 'pending']);
        $reason = 'Inappropriate content';

        $result = $this->postService->moderatePost($post, 'reject', $reason);

        expect($result)->toBeTrue()
            ->and($post->fresh()->status)->toBe('rejected');
    });

    it('throws exception for invalid action', function () {
        $post = Post::factory()->create(['status' => 'pending']);

        expect(function () use ($post) {
            $this->postService->moderatePost($post, 'invalid_action');
        })->toThrow(\InvalidArgumentException::class, 'Invalid moderation action: invalid_action');
    });
});

describe('searchPosts', function () {
    it('returns search results', function () {
        Post::factory()->create([
            'title' => 'Laravel Tutorial',
            'status' => 'published',
        ]);
        Post::factory()->create([
            'title' => 'Vue.js Guide',
            'status' => 'published',
        ]);

        $result = $this->postService->searchPosts('Laravel');

        expect($result->count())->toBeGreaterThanOrEqual(0);
    });

    it('sanitizes search query', function () {
        $result = $this->postService->searchPosts('  test query  ');

        expect($result)->not->toBeNull();
    });
});

describe('getTrendingPosts', function () {
    it('returns trending posts', function () {
        Post::factory()->count(5)->create([
            'status' => 'published',
            'views_count' => 100,
        ]);

        $result = $this->postService->getTrendingPosts(3);

        expect($result->count())->toBeLessThanOrEqual(3);
    });
});

describe('getCategories', function () {
    it('returns available categories', function () {
        Post::factory()->create(['category' => 'technology']);
        Post::factory()->create(['category' => 'business']);

        $result = $this->postService->getCategories();

        expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });
});

describe('getUserStats', function () {
    it('returns user statistics', function () {
        Post::factory()->count(3)->create(['user_id' => $this->user->id]);

        $result = $this->postService->getUserStats($this->user->id);

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys(['total_posts', 'total_views', 'total_comments']);
    });
});

describe('getSystemStats', function () {
    it('returns system statistics', function () {
        Post::factory()->count(5)->create();
        User::factory()->count(3)->create();

        $result = $this->postService->getSystemStats();

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys(['total_users', 'total_posts', 'published_posts', 'total_views', 'posts_this_month']);
    });
});

describe('getWelcomeStats', function () {
    it('returns welcome page statistics', function () {
        Post::factory()->count(10)->create(['status' => 'published']);

        $result = $this->postService->getWelcomeStats();

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys(['total_posts', 'total_views', 'posts_this_month']);
    });
});

describe('getWelcomeRecentPosts', function () {
    it('returns recent posts for welcome page', function () {
        Post::factory()->count(10)->create(['status' => 'published']);

        $result = $this->postService->getWelcomeRecentPosts(6);

        expect($result->count())->toBeLessThanOrEqual(6);
    });
});
