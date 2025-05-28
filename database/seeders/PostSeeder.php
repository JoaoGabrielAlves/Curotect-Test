<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some users first
        $users = User::factory(10)->create();

        // Create posts with different statuses and categories
        $posts = collect();

        // Published posts (most common)
        $publishedPosts = Post::factory(50)
            ->published()
            ->recycle($users)
            ->create();
        $posts = $posts->merge($publishedPosts);

        // Draft posts
        $draftPosts = Post::factory(15)
            ->draft()
            ->recycle($users)
            ->create();
        $posts = $posts->merge($draftPosts);

        // Archived posts
        $archivedPosts = Post::factory(10)
            ->archived()
            ->recycle($users)
            ->create();
        $posts = $posts->merge($archivedPosts);

        // Create comments for posts
        $posts->each(function ($post) use ($users) {
            // Create 0-8 top-level comments per post
            $commentCount = rand(0, 8);

            $topLevelComments = Comment::factory($commentCount)
                ->approved()
                ->recycle($users)
                ->create([
                    'post_id' => $post->id,
                ]);

            // Create some replies to comments (nested comments)
            $topLevelComments->each(function ($comment) use ($users) {
                if (rand(1, 100) <= 30) { // 30% chance of having replies
                    Comment::factory(rand(1, 3))
                        ->approved()
                        ->recycle($users)
                        ->create([
                            'post_id' => $comment->post_id,
                            'parent_id' => $comment->id,
                        ]);
                }
            });
        });

        // Create some pending comments for moderation testing
        Comment::factory(20)
            ->pending()
            ->recycle($users)
            ->recycle($posts)
            ->create();
    }
}
