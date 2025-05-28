<?php

namespace Tests\Feature\Events;

use App\Events\PostDeleted;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PostDeletedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_deleted_event_is_dispatched_with_correct_data(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'status' => 'published',
        ]);

        $this->actingAs($user)
            ->delete(route('posts.destroy', $post));

        Event::assertDispatched(PostDeleted::class, function ($event) use ($post) {
            return $event->postId === $post->id &&
                   $event->title === $post->title &&
                   $event->status === $post->status &&
                   $event->userId === $post->user_id;
        });
    }

    public function test_post_deleted_event_broadcasts_to_correct_channels(): void
    {
        $postId = 1;
        $title = 'Test Post';
        $status = 'published';
        $userId = 2;

        $event = new PostDeleted($postId, $title, $status, $userId);

        $channels = $event->broadcastOn();

        $this->assertCount(2, $channels);
        $this->assertEquals('posts', $channels[0]->name);
        $this->assertEquals('private-user.2', $channels[1]->name);
    }

    public function test_post_deleted_event_broadcast_data(): void
    {
        $postId = 1;
        $title = 'Test Post';
        $status = 'draft';
        $userId = 2;

        $event = new PostDeleted($postId, $title, $status, $userId);

        $broadcastData = $event->broadcastWith();

        $expected = [
            'id' => $postId,
            'title' => $title,
            'status' => $status,
            'user_id' => $userId,
        ];

        $this->assertEquals($expected, $broadcastData);
    }

    public function test_post_deleted_event_handles_different_statuses(): void
    {
        $statuses = ['draft', 'published', 'archived'];

        foreach ($statuses as $status) {
            $event = new PostDeleted(1, 'Test Post', $status, 100);
            $data = $event->broadcastWith();

            $this->assertEquals($status, $data['status']);
        }
    }
}
