<?php

use App\Models\Post;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('Post Actions', function () {
    beforeEach(function () {
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();

        $this->user1Post = Post::factory()->create([
            'user_id' => $this->user1->id,
            'status' => 'published',
            'title' => 'User1 Post',
        ]);

        $this->user2Post = Post::factory()->create([
            'user_id' => $this->user2->id,
            'status' => 'published',
            'title' => 'User2 Post',
        ]);
    });

    it('allows post owner to edit their post', function () {
        $response = $this->actingAs($this->user1)->get(route('posts.edit', $this->user1Post));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Edit')
                ->where('post.data.id', $this->user1Post->id)
                ->where('post.data.title', 'User1 Post')
            );
    });

    it('prevents non-owner from editing post', function () {
        $response = $this->actingAs($this->user2)->get(route('posts.edit', $this->user1Post));

        $response->assertStatus(403); // Forbidden
    });

    it('allows post owner to delete their post', function () {
        $response = $this->actingAs($this->user1)->delete(route('posts.destroy', $this->user1Post));

        $response->assertStatus(302) // Redirect after deletion
            ->assertRedirect(route('posts.index'))
            ->assertSessionHas('success', 'Post deleted successfully!');

        // Verify post is actually deleted
        $this->assertDatabaseMissing('posts', [
            'id' => $this->user1Post->id,
        ]);
    });

    it('prevents non-owner from deleting post', function () {
        $response = $this->actingAs($this->user2)->delete(route('posts.destroy', $this->user1Post));

        $response->assertStatus(403); // Forbidden

        // Verify post still exists
        $this->assertDatabaseHas('posts', [
            'id' => $this->user1Post->id,
        ]);
    });

    it('prevents unauthenticated users from editing posts', function () {
        $response = $this->get(route('posts.edit', $this->user1Post));

        $response->assertStatus(302) // Redirect to login
            ->assertRedirect(route('login'));
    });

    it('prevents unauthenticated users from deleting posts', function () {
        $response = $this->delete(route('posts.destroy', $this->user1Post));

        $response->assertStatus(302) // Redirect to login
            ->assertRedirect(route('login'));

        // Verify post still exists
        $this->assertDatabaseHas('posts', [
            'id' => $this->user1Post->id,
        ]);
    });

    it('shows post actions only for post owners in data grid', function () {
        $response = $this->actingAs($this->user1)->get(route('posts.index'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Index')
                ->has('posts.data')
                ->has('posts.data.0.user')
                ->has('posts.data.0.can_edit')
                ->has('posts.data.0.can_delete')
            );
    });
});
