<?php

namespace App\Listeners;

use App\Events\PostDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandlePostDeletion implements ShouldQueue
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
    public function handle(PostDeleted $event): void
    {
        Log::info('Post deletion notification', [
            'post_id' => $event->postId,
            'title' => $event->title,
            'user_id' => $event->userId,
        ]);

        // In a real app, this might notify:
        // - Moderators if it was a published post
        // - Users who had commented on the post
        // - Analytics systems
    }
}
