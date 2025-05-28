<?php

namespace App\Services;

use App\Contracts\PostRepositoryInterface;
use App\Events\PostCreated;
use App\Events\PostDeleted;
use App\Events\PostUpdated;
use App\Jobs\TrackPostView;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class PostService
{
    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly CacheService $cacheService
    ) {}

    /**
     * Get paginated posts with filters and business rules.
     */
    public function getPaginatedPosts(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $filters = $this->sanitizeFilters($filters);

        return $this->postRepository->getPaginatedPosts($filters, $perPage);
    }

    /**
     * Get a post for viewing with tracking.
     */
    public function getPostForViewing(int $id, ?User $user = null): ?Post
    {
        $post = $this->postRepository->findWithRelations($id);

        if (! $post) {
            return null;
        }

        $this->trackPostView($post, $user);

        return $post;
    }

    /**
     * Create a new post with all the business logic.
     *
     * @throws Throwable
     */
    public function createPost(array $data, User $user): Post
    {
        return DB::transaction(function () use ($data, $user) {
            $data = $this->preparePostData($data, $user);

            $post = $this->postRepository->create($data);

            $this->handlePostCreated($post);

            return $post;
        });
    }

    /**
     * Update a post with concurrency control.
     *
     * @throws Throwable
     */
    public function updatePost(Post $post, array $data, string $etag): Post
    {
        return DB::transaction(function () use ($post, $data, $etag) {
            if (! $post->isEtagValid($etag)) {
                throw new InvalidArgumentException('Post has been modified by another user.');
            }

            $originalStatus = $post->status;
            $data = $this->preparePostUpdateData($data, $post);

            $this->postRepository->update($post, $data);

            $this->handlePostUpdated($post, $data, $originalStatus);

            return $post->fresh();
        });
    }

    /**
     * Delete a post and clean up.
     *
     * @throws Throwable
     */
    public function deletePost(Post $post): bool
    {
        return DB::transaction(function () use ($post) {
            // Capture the data before deletion
            $postData = [
                'id' => $post->id,
                'title' => $post->title,
                'status' => $post->status,
                'user_id' => $post->user_id,
            ];

            $deleted = $this->postRepository->delete($post);

            if ($deleted) {
                $this->handlePostDeleted($postData);
            }

            return $deleted;
        });
    }

    /**
     * Get trending posts.
     */
    public function getTrendingPosts(int $limit = 10): Collection
    {
        return $this->postRepository->getTrendingPosts($limit);
    }

    /**
     * Search posts with logging.
     */
    public function searchPosts(string $query, array $filters = []): Collection
    {
        $query = $this->sanitizeSearchQuery($query);

        return $this->postRepository->search($query, $filters);
    }

    /**
     * Get user's posts with privacy controls.
     */
    public function getUserPostsCollection(User $user, ?User $viewer = null, array $filters = []): Collection
    {
        if ($viewer && $viewer->id !== $user->id) {
            $filters['status'] = 'published';
        }

        return $this->postRepository->getByUser($user->id, $filters);
    }

    /**
     * Get paginated user posts for the management interface.
     */
    public function getUserPosts(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $filters = $this->sanitizeFilters($filters);

        return $this->postRepository->getUserPostsPaginated($user->id, $filters, $perPage);
    }

    /**
     * Get public posts only.
     */
    public function getPublicPosts(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $filters = $this->sanitizeFilters($filters);
        $filters['status'] = 'published';

        return $this->postRepository->getPublicPostsPaginated($filters, $perPage);
    }

    /**
     * Get posts that need moderation.
     */
    public function getPostsForModeration(): Collection
    {
        return $this->postRepository->getPostsForModeration();
    }

    /**
     * Get all available categories.
     */
    public function getCategories(): SupportCollection
    {
        return $this->postRepository->getCategories();
    }

    /**
     * Handle post moderation (approve/reject/flag).
     *
     * @throws Throwable
     */
    public function moderatePost(Post $post, string $action, ?string $reason = null): bool
    {
        $validActions = ['approve', 'reject', 'flag'];

        if (! in_array($action, $validActions)) {
            throw new InvalidArgumentException("Invalid moderation action: {$action}");
        }

        return DB::transaction(function () use ($post, $action, $reason) {
            $success = match ($action) {
                'approve' => $this->approvePost($post),
                'reject' => $this->rejectPost($post, $reason),
                'flag' => $this->flagPost($post, $reason),
            };

            if ($success) {
                Log::info('Post moderated', [
                    'post_id' => $post->id,
                    'action' => $action,
                    'reason' => $reason,
                    'moderator_id' => Auth::id(),
                ]);
            }

            return $success;
        });
    }

    /**
     * Track post view asynchronously.
     */
    private function trackPostView(Post $post, ?User $user): void
    {
        TrackPostView::dispatch(
            $post->id,
            $user?->id,
            request()->ip(),
            request()->userAgent()
        );
    }

    /**
     * Prepare post data for creation.
     */
    private function preparePostData(array $data, User $user): array
    {
        $data['user_id'] = $user->id;

        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if ($data['status'] !== 'published') {
            $data['published_at'] = null;
        }

        return $data;
    }

    /**
     * Prepare post data for updates.
     */
    private function preparePostUpdateData(array $data, Post $post): array
    {
        if (isset($data['status']) && $data['status'] !== $post->status) {
            if ($data['status'] === 'published' && empty($data['published_at'])) {
                $data['published_at'] = now();
            } elseif ($data['status'] !== 'published') {
                $data['published_at'] = null;
            }
        }

        return $data;
    }

    /**
     * Handle post creation business logic.
     */
    private function handlePostCreated(Post $post): void
    {
        $this->cacheService->clearPostRelatedCaches();

        PostCreated::dispatch($post->load('user'));
    }

    /**
     * Handle post update business logic.
     */
    private function handlePostUpdated(Post $post, array $data, string $originalStatus): void
    {
        $this->cacheService->clearPostRelatedCaches($post->id);

        $changes = [];
        if (isset($data['status'])) {
            $changes['status'] = [
                'old' => $originalStatus,
                'new' => $data['status'],
            ];
        }

        PostUpdated::dispatch($post->load('user'), $changes);
    }

    /**
     * Handle post deletion cleanup.
     */
    private function handlePostDeleted(array $postData): void
    {
        $this->cacheService->clearPostRelatedCaches();

        PostDeleted::dispatch(
            $postData['id'],
            $postData['title'],
            $postData['status'],
            $postData['user_id']
        );
    }

    /**
     * Approve a post.
     */
    private function approvePost(Post $post): bool
    {
        return $this->postRepository->update($post, ['status' => 'published', 'published_at' => now()]);
    }

    /**
     * Reject a post with optional reason.
     */
    private function rejectPost(Post $post, ?string $reason): bool
    {
        $success = $this->postRepository->update($post, ['status' => 'rejected']);

        if ($success && $reason) {
            Log::info('Post rejected', ['post_id' => $post->id, 'reason' => $reason]);
        }

        return $success;
    }

    /**
     * Flag a post for review.
     */
    private function flagPost(Post $post, ?string $reason): bool
    {
        Log::warning('Post flagged', ['post_id' => $post->id, 'reason' => $reason]);

        return true;
    }

    /**
     * Sanitize filters for security and consistency.
     */
    private function sanitizeFilters(array $filters): array
    {
        if (isset($filters['search'])) {
            $filters['search'] = strip_tags(trim($filters['search']));
        }

        if (isset($filters['status']) && ! in_array($filters['status'], ['draft', 'published', 'archived'])) {
            unset($filters['status']);
        }

        return $filters;
    }

    /**
     * Sanitize search query.
     */
    private function sanitizeSearchQuery(string $query): string
    {
        return strip_tags(trim($query));
    }

    /**
     * Get user statistics for dashboard.
     */
    public function getUserStats(int $userId): array
    {
        return $this->cacheService->getOrCacheDashboardStats($userId, function () use ($userId) {
            return [
                'total_posts' => $this->postRepository->getUserPostsCount($userId),
                'total_views' => $this->postRepository->getUserTotalViews($userId),
                'total_comments' => $this->postRepository->getUserTotalComments($userId),
            ];
        });
    }

    /**
     * Get user's recent posts for dashboard.
     */
    public function getUserRecentPosts(int $userId, int $limit = 5): Collection
    {
        return $this->postRepository->getUserRecentPosts($userId, $limit);
    }

    /**
     * Get system-wide statistics.
     */
    public function getSystemStats(): array
    {
        return $this->cacheService->getOrCacheSystemStats(function () {
            return [
                'total_users' => $this->postRepository->getTotalUsersCount(),
                'total_posts' => $this->postRepository->getTotalPostsCount(),
                'published_posts' => $this->postRepository->getTotalPublishedPostsCount(),
                'total_views' => $this->postRepository->getTotalViewsCount(),
                'posts_this_month' => $this->postRepository->getPostsThisMonthCount(),
            ];
        });
    }

    /**
     * Get welcome page statistics.
     */
    public function getWelcomeStats(): array
    {
        return $this->cacheService->getOrCacheWelcomeStats(function () {
            return [
                'total_posts' => $this->postRepository->getTotalPublishedPostsCount(),
                'total_views' => $this->postRepository->getTotalViewsCount(),
                'posts_this_month' => $this->postRepository->getPostsThisMonthCount(),
            ];
        });
    }

    /**
     * Get recent posts for welcome page.
     */
    public function getWelcomeRecentPosts(int $limit = 6): Collection
    {
        return $this->postRepository->getRecentPublishedPosts($limit);
    }
}
