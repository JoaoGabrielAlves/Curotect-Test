<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostViewed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Post $post
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('posts'),
            new Channel('post.'.$this->post->id),
            new PrivateChannel('user.'.$this->post->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'post.viewed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->post->id,
            'views_count' => $this->post->views_count,
            'title' => $this->post->title,
        ];
    }
}
