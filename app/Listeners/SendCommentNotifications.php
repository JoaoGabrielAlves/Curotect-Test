<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendCommentNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment;

        // Notify post owner of new comment (if not commenting on own post)
        if ($comment->user_id !== $comment->post->user_id) {
            $this->notifyPostOwner($comment);
        }

        // Notify parent comment author of reply (if it's a reply and not replying to self)
        if ($comment->parent_id && $comment->user_id !== $comment->parent->user_id) {
            $this->notifyCommentAuthor($comment);
        }
    }

    private function notifyPostOwner($comment): void
    {
        Log::info('Notifying post owner of new comment', [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
            'post_owner_id' => $comment->post->user_id,
            'commenter_id' => $comment->user_id,
        ]);
    }

    private function notifyCommentAuthor($reply): void
    {
        Log::info('Notifying comment author of reply', [
            'reply_id' => $reply->id,
            'parent_comment_id' => $reply->parent_id,
            'original_author_id' => $reply->parent->user_id,
            'replier_id' => $reply->user_id,
        ]);
    }
}
