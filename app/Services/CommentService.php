<?php

namespace App\Services;

use App\Contracts\CommentRepositoryInterface;
use App\Events\CommentCreated;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class CommentService
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        private CacheService $cacheService
    ) {}

    /**
     * Create a new comment with all the business logic.
     *
     * @throws Throwable
     */
    public function createComment(array $data, Post $post, User $user): Comment
    {
        return DB::transaction(function () use ($data, $post, $user) {
            $data = $this->prepareCommentData($data, $post, $user);

            $comment = $this->commentRepository->create($data);

            $this->handleCommentCreated($comment);

            return $comment;
        });
    }

    /**
     * Update a comment with business logic.
     *
     * @throws Throwable
     */
    public function updateComment(Comment $comment, array $data): Comment
    {
        return DB::transaction(function () use ($comment, $data) {
            $data = $this->prepareCommentUpdateData($data, $comment);

            $this->commentRepository->update($comment, $data);

            $this->handleCommentUpdated($comment);

            return $comment->fresh();
        });
    }

    /**
     * Delete a comment and clean up.
     *
     * @throws Throwable
     */
    public function deleteComment(Comment $comment): bool
    {
        return DB::transaction(function () use ($comment) {
            $this->handleCommentDeleting($comment);

            $deleted = $this->commentRepository->delete($comment);

            if ($deleted) {
                $this->handleCommentDeleted($comment);
            }

            return $deleted;
        });
    }

    /**
     * Get comments for a post with business rules applied.
     */
    public function getPostComments(Post $post, array $filters = []): Collection
    {
        $filters = $this->sanitizeCommentFilters($filters);

        return $this->commentRepository->getByPost($post, $filters);
    }

    /**
     * Get user's comments with privacy controls.
     */
    public function getUserComments(User $user, ?User $viewer = null, array $filters = []): Collection
    {
        if ($viewer && $viewer->id !== $user->id) {
            $filters['status'] = 'approved';
        }

        return $this->commentRepository->getByUser($user, $filters);
    }

    /**
     * Handle comment moderation (approve, reject, flag).
     *
     * @throws Throwable
     */
    public function moderateComment(Comment $comment, string $action, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($comment, $action, $reason) {
            $success = match ($action) {
                'approve' => $this->approveComment($comment),
                'reject' => $this->rejectComment($comment, $reason),
                'flag' => $this->flagComment($comment, $reason),
                default => false,
            };

            if ($success) {
                Log::info('Comment moderated', [
                    'comment_id' => $comment->id,
                    'action' => $action,
                    'reason' => $reason,
                    'moderator_id' => Auth::id(),
                ]);
            }

            return $success;
        });
    }

    /**
     * Get comments that need moderation.
     */
    public function getCommentsForModeration(): Collection
    {
        return $this->commentRepository->getPendingModeration();
    }

    /**
     * Set up comment data for creation.
     */
    private function prepareCommentData(array $data, Post $post, User $user): array
    {
        return [
            'content' => trim($data['content']),
            'status' => $this->determineCommentStatus($user, $post),
            'post_id' => $post->id,
            'user_id' => $user->id,
            'parent_id' => $data['parent_id'] ?? null,
        ];
    }

    /**
     * Set up comment data for updates.
     */
    private function prepareCommentUpdateData(array $data, Comment $comment): array
    {
        $updateData = [];

        if (isset($data['content'])) {
            $updateData['content'] = trim($data['content']);
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        return $updateData;
    }

    /**
     * Figure out what status a new comment should have.
     */
    private function determineCommentStatus(User $user, Post $post): string
    {
        // Post owners get auto-approved
        if ($user->id === $post->user_id) {
            return 'approved';
        }

        // Auto-approve for now - could add reputation checks later
        return 'approved';
    }

    /**
     * Handle everything that needs to happen after creating a comment.
     */
    private function handleCommentCreated(Comment $comment): void
    {
        $this->cacheService->clearCommentRelatedCaches($comment->post_id);

        // Fire event for broadcasting and notifications
        CommentCreated::dispatch($comment->load(['user', 'post', 'parent']));

        Log::info('Comment created', [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
            'user_id' => $comment->user_id,
        ]);
    }

    /**
     * Handle tasks after updating a comment.
     */
    private function handleCommentUpdated(Comment $comment): void
    {
        $this->cacheService->clearCommentRelatedCaches($comment->post_id);

        Log::info('Comment updated', [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
        ]);
    }

    /**
     * Handle tasks before deleting a comment.
     */
    private function handleCommentDeleting(Comment $comment): void
    {
        Log::info('Comment being deleted', [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
            'has_replies' => $comment->replies()->exists(),
        ]);
    }

    /**
     * Handle cleanup after deleting a comment.
     */
    private function handleCommentDeleted(Comment $comment): void
    {
        $this->cacheService->clearCommentRelatedCaches($comment->post_id);

        Log::info('Comment deleted', [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
        ]);
    }

    /**
     * Approve a comment.
     */
    private function approveComment(Comment $comment): bool
    {
        return $this->commentRepository->update($comment, ['status' => 'approved']);
    }

    /**
     * Reject a comment with optional reason.
     */
    private function rejectComment(Comment $comment, ?string $reason): bool
    {
        $success = $this->commentRepository->update($comment, ['status' => 'rejected']);

        if ($success && $reason) {
            Log::info('Comment rejected', ['comment_id' => $comment->id, 'reason' => $reason]);
        }

        return $success;
    }

    /**
     * Flag a comment for review.
     */
    private function flagComment(Comment $comment, ?string $reason): bool
    {
        $success = $this->commentRepository->update($comment, ['status' => 'flagged']);

        if ($success) {
            Log::warning('Comment flagged', ['comment_id' => $comment->id, 'reason' => $reason]);
        }

        return $success;
    }

    /**
     * Clean up comment filters to prevent issues.
     */
    private function sanitizeCommentFilters(array $filters): array
    {
        if (isset($filters['status']) && ! in_array($filters['status'], ['approved', 'pending', 'rejected', 'flagged'])) {
            unset($filters['status']);
        }

        return $filters;
    }
}
