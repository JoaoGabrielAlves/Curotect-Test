<?php

namespace App\Listeners;

use App\Events\PostUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandlePostUpdates implements ShouldQueue
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
    public function handle(PostUpdated $event): void
    {
        $post = $event->post;
        $changes = $event->changes;

        // Handle status changes
        if (isset($changes['status'])) {
            $this->handleStatusChange($post, $changes['status']);
        }

        // Handle moderation requirements
        if ($post->status === 'pending') {
            $this->notifyModerators($post);
        }
    }

    private function handleStatusChange($post, $statusChange): void
    {
        Log::info('Post status changed', [
            'post_id' => $post->id,
            'old_status' => $statusChange['old'],
            'new_status' => $statusChange['new'],
            'user_id' => $post->user_id,
        ]);
    }

    private function notifyModerators($post): void
    {
        Log::info('Content pending moderation', [
            'post_id' => $post->id,
            'user_id' => $post->user_id,
        ]);
    }
}
