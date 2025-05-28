<?php

namespace App\Repositories;

use App\Contracts\CommentRepositoryInterface;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

readonly class CommentRepository implements CommentRepositoryInterface
{
    public function __construct(
        private CacheService $cacheService
    ) {}

    /**
     * Find a comment by ID.
     */
    public function find(int $id): ?Comment
    {
        return Comment::find($id);
    }

    /**
     * Find a comment with its relationships loaded.
     */
    public function findWithRelations(int $id, array $relations = []): ?Comment
    {
        $defaultRelations = [
            'user:id,name,email',
            'post:id,title,user_id',
            'replies.user:id,name,email',
        ];

        $relations = empty($relations) ? $defaultRelations : $relations;

        return Comment::with($relations)->find($id);
    }

    /**
     * Create a new comment.
     */
    public function create(array $data): Comment
    {
        return Comment::create($data);
    }

    /**
     * Update a comment.
     */
    public function update(Comment $comment, array $data): bool
    {
        return $comment->update($data);
    }

    /**
     * Delete a comment.
     */
    public function delete(Comment $comment): bool
    {
        return $comment->delete();
    }

    /**
     * Get comments for a specific post.
     */
    public function getByPost(Post $post, array $filters = []): Collection
    {
        return $this->cacheService->getOrCachePostComments($post->id, $filters, function () use ($post, $filters) {
            $query = Comment::where('post_id', $post->id)
                ->with(['user:id,name,email', 'replies.user:id,name,email']);

            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            } else {
                $query->where('status', 'approved');
            }

            if (isset($filters['parent_id'])) {
                $query->where('parent_id', $filters['parent_id']);
            } else {
                $query->whereNull('parent_id');
            }

            return $query->orderBy('created_at', 'desc')->get();
        });
    }

    /**
     * Get paginated comments for a post.
     */
    public function getByPostPaginated(Post $post, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Comment::where('post_id', $post->id)
            ->with(['user:id,name,email', 'replies.user:id,name,email']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', 'approved');
        }

        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        } else {
            $query->whereNull('parent_id');
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get comments by user.
     */
    public function getByUser(User $user, array $filters = []): Collection
    {
        $query = Comment::where('user_id', $user->id)
            ->with(['post:id,title', 'user:id,name,email']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get top-level comments for a post.
     */
    public function getTopLevelByPost(Post $post): Collection
    {
        return $this->cacheService->getOrCacheTopLevelComments($post->id, function () use ($post) {
            return Comment::where('post_id', $post->id)
                ->whereNull('parent_id')
                ->where('status', 'approved')
                ->with(['user:id,name,email', 'replies.user:id,name,email'])
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Get replies for a comment.
     */
    public function getReplies(Comment $comment): Collection
    {
        return Comment::where('parent_id', $comment->id)
            ->where('status', 'approved')
            ->with(['user:id,name,email'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get comments waiting for moderation.
     */
    public function getPendingModeration(): Collection
    {
        return Comment::where('status', 'pending')
            ->with(['user:id,name,email', 'post:id,title'])
            ->orderBy('created_at')
            ->get();
    }
}
