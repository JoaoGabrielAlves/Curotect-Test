<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update the test user
        $testUser = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Test user created/updated:');
        $this->command->info('Email: test@example.com');
        $this->command->info('Password: password');
        $this->command->info("User ID: {$testUser->id}");

        // Create some additional users for variety
        $otherUsers = User::factory(3)->create();
        $allUsers = collect([$testUser])->merge($otherUsers);

        // Create posts for the test user and others
        $testUserPosts = Post::factory(10)->create([
            'user_id' => $testUser->id,
            'status' => 'published',
            'category' => fn () => fake()->randomElement(['Technology', 'Business', 'Health', 'Education', 'Entertainment']),
        ]);

        // Create some posts by other users
        $otherPosts = Post::factory(15)->create([
            'user_id' => fn () => $otherUsers->random()->id,
            'status' => fn () => fake()->randomElement(['published', 'draft', 'archived']),
            'category' => fn () => fake()->randomElement(['Technology', 'Business', 'Health', 'Education', 'Entertainment']),
        ]);

        $allPosts = $testUserPosts->merge($otherPosts);

        // Create comments on posts
        $allPosts->each(function ($post) use ($allUsers) {
            // Create 1-5 comments per post
            $commentCount = random_int(1, 5);

            for ($i = 0; $i < $commentCount; $i++) {
                $comment = Comment::factory()->create([
                    'post_id' => $post->id,
                    'user_id' => $allUsers->random()->id,
                    'status' => 'approved',
                ]);

                // 30% chance of having replies
                if (random_int(1, 100) <= 30) {
                    Comment::factory(random_int(1, 3))->create([
                        'post_id' => $post->id,
                        'parent_id' => $comment->id,
                        'user_id' => $allUsers->random()->id,
                        'status' => 'approved',
                    ]);
                }
            }
        });

        $this->command->info("Created {$allPosts->count()} posts with comments");
        $this->command->info("Test user has {$testUserPosts->count()} posts");
    }
}
