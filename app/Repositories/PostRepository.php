<?php

namespace App\Repositories;

use App\Contracts\PostRepositoryInterface;
use App\Models\Post;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

readonly class PostRepository implements PostRepositoryInterface
{
    public function __construct(
        private CacheService $cacheService
    ) {}

    /**
     * Get paginated posts with filters and sorting.
     * Users see their own posts plus published posts from others.
     */
    public function getPaginatedPosts(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Post::with(['user:id,name,email'])
            ->withCount('comments');

        $currentUserId = Auth::id();

        $query->where(function ($q) use ($currentUserId) {
            $q->where('status', 'published')
                ->orWhere('user_id', $currentUserId);
        });

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where(function ($q) use ($filters, $currentUserId) {
                if ($filters['status'] === 'published') {
                    $q->where('status', 'published');
                } else {
                    $q->where('user_id', $currentUserId)
                        ->where('status', $filters['status']);
                }
            });
        }

        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';

        if ($sortField === 'user_name') {
            $query->join('users', 'posts.user_id', '=', 'users.id')
                ->select('posts.*')
                ->orderBy('users.name', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Find a post with its relationships loaded.
     */
    public function findWithRelations(int $id, array $relations = []): ?Post
    {
        return $this->cacheService->getOrCachePost($id, function () use ($id, $relations) {
            $defaultRelations = [
                'user:id,name,email',
                'comments' => function ($query) {
                    $query->approved()
                        ->topLevel()
                        ->with(['user:id,name,email', 'replies.user:id,name,email'])
                        ->orderBy('created_at', 'desc');
                },
            ];

            $relations = empty($relations) ? $defaultRelations : $relations;

            return Post::with($relations)->find($id);
        });
    }

    /**
     * Create a new post.
     */
    public function create(array $data): Post
    {
        $post = Post::create($data);

        $this->cacheService->clearPostRelatedCaches();

        return $post;
    }

    /**
     * Update a post.
     */
    public function update(Post $post, array $data): bool
    {
        $updated = $post->update($data);

        if ($updated) {
            $this->cacheService->clearPostRelatedCaches($post->id);
        }

        return $updated;
    }

    /**
     * Delete a post.
     */
    public function delete(Post $post): bool
    {
        $postId = $post->id;
        $deleted = $post->delete();

        if ($deleted) {
            $this->cacheService->clearPostRelatedCaches($postId);
        }

        return $deleted;
    }

    /**
     * Get all available categories.
     */
    public function getCategories(): SupportCollection
    {
        return $this->cacheService->getOrCacheCategories(function () {
            return Post::select('category')
                ->distinct()
                ->whereNotNull('category')
                ->orderBy('category')
                ->pluck('category');
        });
    }

    /**
     * Get posts by a specific user.
     */
    public function getByUser(int $userId, array $filters = []): Collection
    {
        $query = Post::where('user_id', $userId)
            ->with(['user:id,name,email'])
            ->withCount('comments');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get paginated posts by user with filters and sorting.
     */
    public function getUserPostsPaginated(int $userId, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Post::where('user_id', $userId)
            ->with(['user:id,name,email'])
            ->withCount('comments');

        // Search in title and content
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                    ->orWhere('content', 'ILIKE', "%{$search}%");
            });
        }

        // Filter by category
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Handle sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';

        if ($sortField === 'user_name') {
            $query->join('users', 'posts.user_id', '=', 'users.id')
                ->select('posts.*')
                ->orderBy('users.name', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get paginated public posts (published only).
     */
    public function getPublicPostsPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Post::with(['user:id,name,email'])
            ->withCount('comments')
            ->where('status', 'published');

        // Apply search filter
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                    ->orWhere('content', 'ILIKE', "%{$search}%");
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';

        if ($sortField === 'user_name') {
            $query->join('users', 'posts.user_id', '=', 'users.id')
                ->select('posts.*')
                ->orderBy('users.name', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get trending posts based on views and recent activity.
     */
    public function getTrendingPosts(int $limit = 10): Collection
    {
        return $this->cacheService->getOrCacheTrending(function () use ($limit) {
            return Post::published()
                ->with(['user:id,name,email'])
                ->withCount('comments')
                ->where('created_at', '>=', now()->subDays(7))
                ->orderByDesc('views_count')
                ->orderByDesc('comments_count')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Search posts by content with full-text search.
     */
    public function search(string $query, array $filters = []): Collection
    {
        $searchQuery = Post::published()
            ->with(['user:id,name,email'])
            ->withCount('comments');

        $searchQuery->where(function ($q) use ($query) {
            $q->where('title', 'ILIKE', "%{$query}%")
                ->orWhere('content', 'ILIKE', "%{$query}%");
        });

        if (! empty($filters['category'])) {
            $searchQuery->where('category', $filters['category']);
        }

        if (! empty($filters['user_id'])) {
            $searchQuery->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['date_from'])) {
            $searchQuery->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $searchQuery->where('created_at', '<=', $filters['date_to']);
        }

        return $searchQuery->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    /**
     * Get posts that need content moderation.
     */
    public function getPendingModeration(): Collection
    {
        return Post::whereIn('status', ['pending', 'flagged'])
            ->with(['user:id,name,email'])
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Increment post views efficiently.
     */
    public function incrementViews(Post $post): void
    {
        DB::table('posts')
            ->where('id', $post->id)
            ->increment('views_count');
    }

    /**
     * Get user posts count with optional filters.
     */
    public function getUserPostsCount(int $userId, array $filters = []): int
    {
        $query = Post::where('user_id', $userId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->count();
    }

    /**
     * Get user's total views across all posts.
     */
    public function getUserTotalViews(int $userId): int
    {
        return Post::where('user_id', $userId)->sum('views_count') ?? 0;
    }

    /**
     * Get user's total comments across all posts.
     */
    public function getUserTotalComments(int $userId): int
    {
        return DB::table('comments')
            ->join('posts', 'comments.post_id', '=', 'posts.id')
            ->where('posts.user_id', $userId)
            ->where('comments.status', 'approved')
            ->count();
    }

    /**
     * Get user's recent posts.
     */
    public function getUserRecentPosts(int $userId, int $limit = 5): Collection
    {
        return Post::where('user_id', $userId)
            ->with(['user:id,name,email'])
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get total users count.
     */
    public function getTotalUsersCount(): int
    {
        return DB::table('users')->count();
    }

    /**
     * Get total posts count.
     */
    public function getTotalPostsCount(): int
    {
        return Post::count();
    }

    /**
     * Get total published posts count.
     */
    public function getTotalPublishedPostsCount(): int
    {
        return Post::where('status', 'published')->count();
    }

    /**
     * Get total views count across all posts.
     */
    public function getTotalViewsCount(): int
    {
        return Post::sum('views_count') ?? 0;
    }

    /**
     * Get posts count for current month.
     */
    public function getPostsThisMonthCount(): int
    {
        return Post::where('created_at', '>=', now()->startOfMonth())
            ->where('status', 'published')
            ->count();
    }

    /**
     * Get recent published posts for welcome page.
     */
    public function getRecentPublishedPosts(int $limit = 6): Collection
    {
        return Post::published()
            ->with(['user:id,name,email'])
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get posts that need moderation.
     */
    public function getPostsForModeration(): Collection
    {
        return Post::whereIn('status', ['pending', 'flagged'])
            ->with(['user:id,name,email'])
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
