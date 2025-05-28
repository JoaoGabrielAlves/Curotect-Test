<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $postId,
        public string $title,
        public string $status,
        public int $userId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('posts'),
            new PrivateChannel('user.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'post.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->postId,
            'title' => $this->title,
            'status' => $this->status,
            'user_id' => $this->userId,
        ];
    }
}
