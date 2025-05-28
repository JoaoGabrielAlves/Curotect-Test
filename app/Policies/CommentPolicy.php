<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

class CommentPolicy
{
    /**
     * Determine whether the user can view any comments.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view comments on published posts
        return true;
    }

    /**
     * Determine whether the user can view the comment.
     */
    public function view(?User $user, Comment $comment): bool
    {
        // Anyone can view approved comments on published posts
        if ($comment->status === 'approved' && $comment->post->status === 'published') {
            return true;
        }

        // Users can view their own comments regardless of status
        return $user && $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can create comments.
     */
    public function create(User $user, Post $post): bool
    {
        // Users can comment on published posts
        if ($post->status === 'published') {
            return true;
        }

        // Users can comment on their own posts regardless of status
        return $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can update the comment.
     */
    public function update(User $user, Comment $comment): bool
    {
        // Users can only update their own comments
        // And only within a reasonable time frame (e.g., 15 minutes)
        $canEdit = $user->id === $comment->user_id;
        $withinEditWindow = $comment->created_at->diffInMinutes(now()) <= 15;

        return $canEdit && $withinEditWindow;
    }

    /**
     * Determine whether the user can delete the comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // Users can delete their own comments
        // Post owners can delete comments on their posts
        return $user->id === $comment->user_id || $user->id === $comment->post->user_id;
    }

    /**
     * Determine whether the user can restore the comment.
     */
    public function restore(User $user, Comment $comment): bool
    {
        // Users can restore their own comments
        // Post owners can restore comments on their posts
        return $user->id === $comment->user_id || $user->id === $comment->post->user_id;
    }

    /**
     * Determine whether the user can permanently delete the comment.
     */
    public function forceDelete(User $user, Comment $comment): bool
    {
        // Users can force delete their own comments
        // Post owners can force delete comments on their posts
        return $user->id === $comment->user_id || $user->id === $comment->post->user_id;
    }

    /**
     * Determine whether the user can moderate comments.
     */
    public function moderate(User $user, Comment $comment): bool
    {
        // Post owners can moderate comments on their posts
        return $user->id === $comment->post->user_id;
    }
}
