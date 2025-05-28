<?php

use App\Models\Post;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('Post Visibility Rules', function () {
    beforeEach(function () {
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();

        // Create posts with different statuses for user1
        $this->user1PublishedPost = Post::factory()->create([
            'user_id' => $this->user1->id,
            'status' => 'published',
            'title' => 'User1 Published Post',
        ]);

        $this->user1DraftPost = Post::factory()->create([
            'user_id' => $this->user1->id,
            'status' => 'draft',
            'title' => 'User1 Draft Post',
        ]);

        $this->user1ArchivedPost = Post::factory()->create([
            'user_id' => $this->user1->id,
            'status' => 'archived',
            'title' => 'User1 Archived Post',
        ]);

        // Create posts with different statuses for user2
        $this->user2PublishedPost = Post::factory()->create([
            'user_id' => $this->user2->id,
            'status' => 'published',
            'title' => 'User2 Published Post',
        ]);

        $this->user2DraftPost = Post::factory()->create([
            'user_id' => $this->user2->id,
            'status' => 'draft',
            'title' => 'User2 Draft Post',
        ]);

        $this->user2ArchivedPost = Post::factory()->create([
            'user_id' => $this->user2->id,
            'status' => 'archived',
            'title' => 'User2 Archived Post',
        ]);
    });

    it('applies search filter within visibility constraints', function () {
        $response = $this->actingAs($this->user1)->get('/posts?search=User2');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1) // Only User2's published post
                ->where('posts.data.0.title', 'User2 Published Post')
            );
    });
});
