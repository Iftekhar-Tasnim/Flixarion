<?php

namespace Database\Factories;

use App\Models\Content;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContentFactory extends Factory
{
    protected $model = Content::class;

    public function definition(): array
    {
        return [
            'tmdb_id' => fake()->unique()->numberBetween(1000, 999999),
            'type' => fake()->randomElement(['movie', 'series']),
            'title' => fake()->sentence(3),
            'original_title' => fake()->sentence(3),
            'year' => fake()->numberBetween(2000, 2026),
            'description' => fake()->paragraph(),
            'poster_path' => '/posters/' . fake()->uuid() . '.jpg',
            'rating' => fake()->randomFloat(1, 1, 10),
            'vote_count' => fake()->numberBetween(0, 10000),
            'runtime' => fake()->numberBetween(80, 200),
            'language' => 'en',
            'enrichment_status' => 'completed',
            'confidence_score' => fake()->randomFloat(2, 80, 100),
            'is_published' => true,
            'is_featured' => false,
            'watch_count' => fake()->numberBetween(0, 5000),
        ];
    }

    public function movie(): static
    {
        return $this->state(['type' => 'movie']);
    }

    public function series(): static
    {
        return $this->state(['type' => 'series']);
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
