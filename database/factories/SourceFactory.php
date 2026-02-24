<?php

namespace Database\Factories;

use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

class SourceFactory extends Factory
{
    protected $model = Source::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company() . ' FTP',
            'base_url' => 'http://' . fake()->ipv4() . ':' . fake()->numberBetween(80, 9999),
            'scraper_type' => 'dflix',
            'config' => ['timeout' => 30, 'retries' => 3],
            'is_active' => true,
            'health_score' => fake()->randomFloat(2, 0, 100),
            'priority' => fake()->numberBetween(1, 10),
            'last_scan_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function healthy(): static
    {
        return $this->state(['health_score' => 95.00, 'is_active' => true]);
    }
}
