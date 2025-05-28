<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Technology', 'Business', 'Health', 'Education', 'Entertainment', 'Sports', 'Travel', 'Food'];
        $statuses = ['draft', 'published', 'archived'];

        $publishedAt = $this->faker->boolean(70) ? $this->faker->dateTimeBetween('-6 months', 'now') : null;

        return [
            'title' => $this->faker->sentence(rand(4, 8)),
            'content' => $this->faker->paragraphs(rand(3, 8), true),
            'status' => $this->faker->randomElement($statuses),
            'category' => $this->faker->randomElement($categories),
            'views_count' => $this->faker->numberBetween(0, 10000),
            'user_id' => User::factory(),
            'published_at' => $publishedAt,
        ];
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the post is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the post is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }
}
