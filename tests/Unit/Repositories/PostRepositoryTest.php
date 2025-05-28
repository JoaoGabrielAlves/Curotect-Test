<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Repositories\PostRepository;

beforeEach(function () {
    $this->repository = app(PostRepository::class);
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

describe('getPaginatedPosts', function () {
    it('returns paginated posts with proper visibility rules for authenticated users', function () {
        $this->actingAs($this->user);

        // Create posts with different statuses
        $publishedPost = Post::factory()->create(['status' => 'published', 'user_id' => $this->otherUser->id]);
        $draftPost = Post::factory()->create(['status' => 'draft', 'user_id' => $this->otherUser->id]);
        $userDraftPost = Post::factory()->create(['status' => 'draft', 'user_id' => $this->user->id]);

        $result = $this->repository->getPaginatedPosts();

        expect($result->items())->toHaveCount(2)
            ->and($result->pluck('id'))->toContain($publishedPost->id)
            ->and($result->pluck('id'))->toContain($userDraftPost->id)
            ->and($result->pluck('id'))->not->toContain($draftPost->id);
    });

    it('returns only published posts for unauthenticated users', function () {
        $publishedPost = Post::factory()->create(['status' => 'published']);
        Post::factory()->create(['status' => 'draft']);

        $result = $this->repository->getPaginatedPosts();

        expect($result->items())->toHaveCount(1)
            ->and($result->first()->id)->toBe($publishedPost->id);
    });

    it('filters posts by search term', function () {
        $matchingPost = Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
        ]);
        Post::factory()->create([
            'title' => 'Vue.js Components',
            'status' => 'published',
        ]);

        $result = $this->repository->getPaginatedPosts(['search' => 'Laravel']);

        expect($result->items())->toHaveCount(1)
            ->and($result->first()->id)->toBe($matchingPost->id);
    });

    it('filters posts by category', function () {
        $techPost = Post::factory()->create(['category' => 'Technology', 'status' => 'published']);
        Post::factory()->create(['category' => 'Business', 'status' => 'published']);

        $result = $this->repository->getPaginatedPosts(['category' => 'Technology']);

        expect($result->items())->toHaveCount(1)
            ->and($result->first()->id)->toBe($techPost->id);
    });

    it('sorts posts by different fields', function () {
        $oldPost = Post::factory()->create([
            'title' => 'A Old Post',
            'status' => 'published',
            'created_at' => now()->subDays(2),
        ]);
        $newPost = Post::factory()->create([
            'title' => 'Z New Post',
            'status' => 'published',
            'created_at' => now()->subDay(),
        ]);

        // Test sorting by title ascending
        $result = $this->repository->getPaginatedPosts([
            'sort' => 'title',
            'direction' => 'asc',
        ]);

        expect($result->first()->id)->toBe($oldPost->id);

        // Test sorting by created_at descending (default)
        $result = $this->repository->getPaginatedPosts([
            'sort' => 'created_at',
            'direction' => 'desc',
        ]);

        expect($result->first()->id)->toBe($newPost->id);
    });

    it('sorts posts by user name', function () {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $postA = Post::factory()->create(['user_id' => $userA->id, 'status' => 'published']);
        Post::factory()->create(['user_id' => $userB->id, 'status' => 'published']);

        $result = $this->repository->getPaginatedPosts([
            'sort' => 'user_name',
            'direction' => 'asc',
        ]);

        expect($result->first()->id)->toBe($postA->id);
    });

    it('applies status filter with visibility constraints', function () {
        $this->actingAs($this->user);

        Post::factory()->create(['status' => 'published', 'user_id' => $this->otherUser->id]);
        $userDraftPost = Post::factory()->create(['status' => 'draft', 'user_id' => $this->user->id]);
        Post::factory()->create(['status' => 'draft', 'user_id' => $this->otherUser->id]);

        // Filter for draft posts - should only return current user's drafts
        $result = $this->repository->getPaginatedPosts(['status' => 'draft']);

        expect($result->items())->toHaveCount(1)
            ->and($result->first()->id)->toBe($userDraftPost->id);
    });
});

describe('findWithRelations', function () {
    it('finds post with default relations', function () {
        $post = Post::factory()->create();

        $result = $this->repository->findWithRelations($post->id);

        expect($result)->not->toBeNull()
            ->and($result->id)->toBe($post->id)
            ->and($result->relationLoaded('user'))->toBeTrue();
    });

    it('returns null for non-existent post', function () {
        $result = $this->repository->findWithRelations(999);

        expect($result)->toBeNull();
    });

    it('loads specified relations', function () {
        $post = Post::factory()->create();

        $result = $this->repository->findWithRelations($post->id, ['user', 'comments']);

        expect($result)->not->toBeNull()
            ->and($result->relationLoaded('user'))->toBeTrue()
            ->and($result->relationLoaded('comments'))->toBeTrue();
    });
});

describe('create', function () {
    it('creates a new post', function () {
        $data = [
            'title' => 'Test Post',
            'content' => 'Test content',
            'status' => 'draft',
            'user_id' => $this->user->id,
        ];

        $result = $this->repository->create($data);

        expect($result)->toBeInstanceOf(Post::class)
            ->and($result->title)->toBe($data['title'])
            ->and($result->content)->toBe($data['content'])
            ->and($result->status)->toBe($data['status'])
            ->and($result->user_id)->toBe($data['user_id']);

        $this->assertDatabaseHas('posts', $data);
    });
});

describe('update', function () {
    it('updates post successfully', function () {
        $post = Post::factory()->create(['title' => 'Original Title']);
        $updateData = ['title' => 'Updated Title'];

        $result = $this->repository->update($post, $updateData);

        expect($result)->toBeTrue()
            ->and($post->fresh()->title)->toBe('Updated Title');
    });
});

describe('delete', function () {
    it('deletes post successfully', function () {
        $post = Post::factory()->create();

        $result = $this->repository->delete($post);

        expect($result)->toBeTrue();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    });
});

describe('getCategories', function () {
    it('returns distinct categories', function () {
        Post::factory()->create(['category' => 'Technology', 'status' => 'published']);
        Post::factory()->create(['category' => 'Business', 'status' => 'published']);
        Post::factory()->create(['category' => 'Technology', 'status' => 'published']); // Duplicate
        Post::factory()->create(['category' => null, 'status' => 'published']); // Null category

        $result = $this->repository->getCategories();

        expect($result)->toHaveCount(2)
            ->and($result->toArray())->toContain('Technology')
            ->and($result->toArray())->toContain('Business');
    });
});

describe('getByUser', function () {
    it('returns posts by specific user', function () {
        $userPost = Post::factory()->create(['user_id' => $this->user->id]);
        Post::factory()->create(['user_id' => $this->otherUser->id]);

        $result = $this->repository->getByUser($this->user->id);

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($userPost->id);
    });

    it('filters posts by status when provided', function () {
        $publishedPost = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);
        Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $result = $this->repository->getByUser($this->user->id, ['status' => 'published']);

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($publishedPost->id);
    });
});

describe('getUserPostsPaginated', function () {
    it('returns paginated posts for specific user', function () {
        Post::factory()->count(15)->create(['user_id' => $this->user->id]);
        Post::factory()->count(5)->create(['user_id' => $this->otherUser->id]);

        $result = $this->repository->getUserPostsPaginated($this->user->id, [], 10);

        expect($result->items())->toHaveCount(10)
            ->and($result->total())->toBe(15)
            ->and($result->currentPage())->toBe(1);
    });

    it('applies search filter to user posts', function () {
        $matchingPost = Post::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Laravel Guide',
        ]);

        Post::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Vue Components',
        ]);

        $result = $this->repository->getUserPostsPaginated(
            $this->user->id,
            ['search' => 'Laravel']
        );

        expect($result->items())->toHaveCount(1)
            ->and($result->first()->id)->toBe($matchingPost->id);
    });
});

describe('getPublicPostsPaginated', function () {
    it('returns only published posts', function () {
        $publishedPost = Post::factory()->create(['status' => 'published']);
        Post::factory()->create(['status' => 'draft']);

        $result = $this->repository->getPublicPostsPaginated();

        expect($result->items())->toHaveCount(1)
            ->and($result->first()->id)->toBe($publishedPost->id);
    });
});

describe('getTrendingPosts', function () {
    it('returns posts ordered by views count', function () {
        Post::factory()->create([
            'status' => 'published',
            'views_count' => 10,
            'created_at' => now()->subDays(2),
        ]);
        $highViewsPost = Post::factory()->create([
            'status' => 'published',
            'views_count' => 100,
            'created_at' => now()->subDays(3),
        ]);

        $result = $this->repository->getTrendingPosts(5);

        expect($result->first()->id)->toBe($highViewsPost->id);
    });

    it('limits results to specified count', function () {
        Post::factory()->count(10)->create(['status' => 'published']);

        $result = $this->repository->getTrendingPosts(3);

        expect($result)->toHaveCount(3);
    });
});

describe('search', function () {
    it('searches posts by title and content', function () {
        $titleMatch = Post::factory()->create([
            'title' => 'Laravel Testing',
            'content' => 'Vue components',
            'status' => 'published',
        ]);
        $contentMatch = Post::factory()->create([
            'title' => 'Vue Guide',
            'content' => 'Laravel framework',
            'status' => 'published',
        ]);
        $noMatch = Post::factory()->create([
            'title' => 'React Tutorial',
            'content' => 'JavaScript library',
            'status' => 'published',
        ]);

        $result = $this->repository->search('Laravel');

        expect($result)->toHaveCount(2)
            ->and($result->pluck('id'))->toContain($titleMatch->id)
            ->and($result->pluck('id'))->toContain($contentMatch->id)
            ->and($result->pluck('id'))->not->toContain($noMatch->id);
    });

    it('applies category filter in search', function () {
        $techPost = Post::factory()->create([
            'title' => 'Laravel Guide',
            'category' => 'Technology',
            'status' => 'published',
        ]);
        Post::factory()->create([
            'title' => 'Laravel Business',
            'category' => 'Business',
            'status' => 'published',
        ]);

        $result = $this->repository->search('Laravel', ['category' => 'Technology']);

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($techPost->id);
    });
});

describe('getPendingModeration', function () {
    it('returns posts with pending status', function () {
        $pendingPost = Post::factory()->create(['status' => 'pending']);
        Post::factory()->create(['status' => 'published']);

        $result = $this->repository->getPendingModeration();

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($pendingPost->id);
    });
});

describe('incrementViews', function () {
    it('increments post views count', function () {
        $post = Post::factory()->create(['views_count' => 5]);

        $this->repository->incrementViews($post);

        expect($post->fresh()->views_count)->toBe(6);
    });
});

describe('getUserPostsCount', function () {
    it('returns total count of user posts', function () {
        Post::factory()->count(5)->create(['user_id' => $this->user->id]);
        Post::factory()->count(3)->create(['user_id' => $this->otherUser->id]);

        $result = $this->repository->getUserPostsCount($this->user->id);

        expect($result)->toBe(5);
    });

    it('filters count by status when provided', function () {
        Post::factory()->count(3)->create(['user_id' => $this->user->id, 'status' => 'published']);
        Post::factory()->count(2)->create(['user_id' => $this->user->id, 'status' => 'draft']);

        $result = $this->repository->getUserPostsCount($this->user->id, ['status' => 'published']);

        expect($result)->toBe(3);
    });
});

describe('getUserTotalViews', function () {
    it('returns sum of views across all user posts', function () {
        Post::factory()->create(['user_id' => $this->user->id, 'views_count' => 100]);
        Post::factory()->create(['user_id' => $this->user->id, 'views_count' => 50]);
        Post::factory()->create(['user_id' => $this->otherUser->id, 'views_count' => 200]);

        $result = $this->repository->getUserTotalViews($this->user->id);

        expect($result)->toBe(150);
    });

    it('returns zero when user has no posts', function () {
        $result = $this->repository->getUserTotalViews($this->user->id);

        expect($result)->toBe(0);
    });
});

describe('getUserTotalComments', function () {
    it('returns count of approved comments on user posts', function () {
        $userPost1 = Post::factory()->create(['user_id' => $this->user->id]);
        $userPost2 = Post::factory()->create(['user_id' => $this->user->id]);
        $otherPost = Post::factory()->create(['user_id' => $this->otherUser->id]);

        Comment::factory()->count(3)->create(['post_id' => $userPost1->id, 'status' => 'approved']);
        Comment::factory()->count(2)->create(['post_id' => $userPost2->id, 'status' => 'approved']);
        Comment::factory()->create(['post_id' => $userPost1->id, 'status' => 'pending']);
        Comment::factory()->count(4)->create(['post_id' => $otherPost->id, 'status' => 'approved']);

        $result = $this->repository->getUserTotalComments($this->user->id);

        expect($result)->toBe(5);
    });
});

describe('getUserRecentPosts', function () {
    it('returns recent posts ordered by creation date', function () {
        $oldPost = Post::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(3),
        ]);
        $newPost = Post::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
        ]);
        Post::factory()->create(['user_id' => $this->otherUser->id]);

        $result = $this->repository->getUserRecentPosts($this->user->id);

        expect($result)->toHaveCount(2)
            ->and($result->first()->id)->toBe($newPost->id)
            ->and($result->last()->id)->toBe($oldPost->id);
    });

    it('limits results to specified count', function () {
        Post::factory()->count(10)->create(['user_id' => $this->user->id]);

        $result = $this->repository->getUserRecentPosts($this->user->id, 3);

        expect($result)->toHaveCount(3);
    });
});

describe('getTotalUsersCount', function () {
    it('returns total number of users', function () {
        User::factory()->count(10)->create();

        $result = $this->repository->getTotalUsersCount();

        expect($result)->toBe(12); // 10 created + 2 in beforeEach
    });
});

describe('getTotalPostsCount', function () {
    it('returns total number of posts', function () {
        Post::factory()->count(8)->create();

        $result = $this->repository->getTotalPostsCount();

        expect($result)->toBe(8);
    });
});

describe('getTotalPublishedPostsCount', function () {
    it('returns count of published posts only', function () {
        Post::factory()->count(5)->create(['status' => 'published']);
        Post::factory()->count(3)->create(['status' => 'draft']);
        Post::factory()->count(2)->create(['status' => 'archived']);

        $result = $this->repository->getTotalPublishedPostsCount();

        expect($result)->toBe(5);
    });
});

describe('getTotalViewsCount', function () {
    it('returns sum of all post views', function () {
        Post::factory()->create(['views_count' => 100]);
        Post::factory()->create(['views_count' => 250]);
        Post::factory()->create(['views_count' => 75]);

        $result = $this->repository->getTotalViewsCount();

        expect($result)->toBe(425);
    });

    it('returns zero when no posts exist', function () {
        $result = $this->repository->getTotalViewsCount();

        expect($result)->toBe(0);
    });
});

describe('getPostsThisMonthCount', function () {
    it('returns count of published posts from current month', function () {
        Post::factory()->count(3)->create([
            'status' => 'published',
            'created_at' => now()->startOfMonth()->addDays(5),
        ]);
        Post::factory()->count(2)->create([
            'status' => 'draft',
            'created_at' => now()->startOfMonth()->addDays(5),
        ]);
        Post::factory()->create([
            'status' => 'published',
            'created_at' => now()->subMonth(),
        ]);

        $result = $this->repository->getPostsThisMonthCount();

        expect($result)->toBe(3);
    });
});

describe('getRecentPublishedPosts', function () {
    it('returns recent published posts with relationships', function () {
        $publishedPost = Post::factory()->create([
            'status' => 'published',
            'created_at' => now()->subDay(),
        ]);
        Post::factory()->create([
            'status' => 'draft',
            'created_at' => now(),
        ]);

        $result = $this->repository->getRecentPublishedPosts();

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($publishedPost->id)
            ->and($result->first()->relationLoaded('user'))->toBeTrue();
    });

    it('limits results to specified count', function () {
        Post::factory()->count(10)->create(['status' => 'published']);

        $result = $this->repository->getRecentPublishedPosts(4);

        expect($result)->toHaveCount(4);
    });

    it('orders posts by creation date descending', function () {
        $oldPost = Post::factory()->create([
            'status' => 'published',
            'created_at' => now()->subDays(2),
        ]);
        $newPost = Post::factory()->create([
            'status' => 'published',
            'created_at' => now()->subDay(),
        ]);

        $result = $this->repository->getRecentPublishedPosts();

        expect($result->first()->id)->toBe($newPost->id)
            ->and($result->last()->id)->toBe($oldPost->id);
    });
});

describe('getPostsForModeration', function () {
    it('returns posts that need moderation', function () {
        $pendingPost = Post::factory()->create(['status' => 'pending']);
        $flaggedPost = Post::factory()->create(['status' => 'flagged']);
        Post::factory()->create(['status' => 'published']);
        Post::factory()->create(['status' => 'draft']);

        $result = $this->repository->getPostsForModeration();

        expect($result)->toHaveCount(2)
            ->and($result->pluck('id'))->toContain($pendingPost->id)
            ->and($result->pluck('id'))->toContain($flaggedPost->id);
    });

    it('orders moderation posts by creation date descending', function () {
        $oldPending = Post::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subDays(2),
        ]);
        $newPending = Post::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subDay(),
        ]);

        $result = $this->repository->getPostsForModeration();

        expect($result->first()->id)->toBe($newPending->id)
            ->and($result->last()->id)->toBe($oldPending->id);
    });
});
