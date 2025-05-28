<?php

namespace App\Contracts;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

interface PostRepositoryInterface
{
    /**
     * Get paginated posts with filters, sorting, and eager loading.
     */
    public function getPaginatedPosts(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Find a post by ID with relationships.
     */
    public function findWithRelations(int $id, array $relations = []): ?Post;

    /**
     * Create a new post.
     */
    public function create(array $data): Post;

    /**
     * Update a post.
     */
    public function update(Post $post, array $data): bool;

    /**
     * Delete a post.
     */
    public function delete(Post $post): bool;

    /**
     * Get available categories.
     */
    public function getCategories(): SupportCollection;

    /**
     * Get posts by user.
     */
    public function getByUser(int $userId, array $filters = []): Collection;

    /**
     * Get trending posts based on views and recent activity.
     */
    public function getTrendingPosts(int $limit = 10): Collection;

    /**
     * Search posts by content.
     */
    public function search(string $query, array $filters = []): Collection;

    /**
     * Get posts that need content moderation.
     */
    public function getPendingModeration(): Collection;

    /**
     * Increment post views efficiently.
     */
    public function incrementViews(Post $post): void;

    /**
     * Get paginated posts by user with filters and sorting.
     */
    public function getUserPostsPaginated(int $userId, array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get paginated public posts (published only).
     */
    public function getPublicPostsPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get user posts count with optional filters.
     */
    public function getUserPostsCount(int $userId, array $filters = []): int;

    /**
     * Get user's total views across all posts.
     */
    public function getUserTotalViews(int $userId): int;

    /**
     * Get user's total comments across all posts.
     */
    public function getUserTotalComments(int $userId): int;

    /**
     * Get user's recent posts.
     */
    public function getUserRecentPosts(int $userId, int $limit = 5): Collection;

    /**
     * Get total users count.
     */
    public function getTotalUsersCount(): int;

    /**
     * Get total posts count.
     */
    public function getTotalPostsCount(): int;

    /**
     * Get total published posts count.
     */
    public function getTotalPublishedPostsCount(): int;

    /**
     * Get total views count across all posts.
     */
    public function getTotalViewsCount(): int;

    /**
     * Get posts count for current month.
     */
    public function getPostsThisMonthCount(): int;

    /**
     * Get recent published posts for welcome page.
     */
    public function getRecentPublishedPosts(int $limit = 6): Collection;

    /**
     * Get posts that need moderation.
     */
    public function getPostsForModeration(): Collection;
}
