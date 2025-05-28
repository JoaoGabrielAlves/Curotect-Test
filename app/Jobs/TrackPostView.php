<?php

namespace App\Jobs;

use App\Contracts\PostRepositoryInterface;
use App\Events\PostViewed;
use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TrackPostView implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public int $postId,
        public ?int $userId = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null
    ) {}

    public function handle(PostRepositoryInterface $postRepository): void
    {
        $post = Post::find($this->postId);

        if (! $post) {
            Log::warning('Attempted to track view for non-existent post', [
                'post_id' => $this->postId,
            ]);

            return;
        }

        // Increment view count
        $postRepository->incrementViews($post);

        // Refresh the post to get updated view count
        $post->refresh();

        // Dispatch real-time event
        PostViewed::dispatch($post);

        // Log analytics data
        Log::info('Post viewed', [
            'post_id' => $this->postId,
            'user_id' => $this->userId,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'timestamp' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to track post view', [
            'post_id' => $this->postId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
