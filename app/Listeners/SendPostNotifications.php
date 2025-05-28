<?php

namespace App\Listeners;

use App\Events\PostCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendPostNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PostCreated $event): void
    {
        $post = $event->post;

        // Only notify for published posts
        if ($post->status !== 'published') {
            return;
        }

        Log::info('Notifying followers of new post', [
            'post_id' => $post->id,
            'author_id' => $post->user_id,
            'title' => $post->title,
        ]);
    }
}
