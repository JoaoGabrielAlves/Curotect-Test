<?php

namespace App\Contracts;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CommentRepositoryInterface
{
    /**
     * Find a comment by ID.
     */
    public function find(int $id): ?Comment;

    /**
     * Find a comment by ID with relationships.
     */
    public function findWithRelations(int $id, array $relations = []): ?Comment;

    /**
     * Create a new comment.
     */
    public function create(array $data): Comment;

    /**
     * Update a comment.
     */
    public function update(Comment $comment, array $data): bool;

    /**
     * Delete a comment.
     */
    public function delete(Comment $comment): bool;

    /**
     * Get comments for a specific post.
     */
    public function getByPost(Post $post, array $filters = []): Collection;

    /**
     * Get paginated comments for a specific post.
     */
    public function getByPostPaginated(Post $post, array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get comments by user.
     */
    public function getByUser(User $user, array $filters = []): Collection;

    /**
     * Get top-level comments for a post (no parent).
     */
    public function getTopLevelByPost(Post $post): Collection;

    /**
     * Get replies for a specific comment.
     */
    public function getReplies(Comment $comment): Collection;

    /**
     * Get comments pending moderation.
     */
    public function getPendingModeration(): Collection;
}
